<?php
/**
 * /api/banque — Gestion de la banque de questions (admin uniquement)
 *
 * Routes REST :
 *
 *   LECTURE :
 *   GET    /api/banque/stats                               - Stats globales
 *   GET    /api/banque/modules                             - Liste des modules
 *   GET    /api/banque/{mod}/chapitres                     - Chapitres d'un module
 *   GET    /api/banque/{mod}/{chap}/themes                 - Thèmes d'un chapitre
 *   GET    /api/banque/{mod}/{chap}/{theme}                - Questions d'un thème
 *   GET    /api/banque/{mod}/{chap}/{theme}/validate       - Validation thème
 *
 *   CRUD QUESTIONS :
 *   GET    /api/banque/questions                           - Liste + filtres (query)
 *   GET    /api/banque/questions/{id}                      - Détail d'une question
 *   POST   /api/banque/questions                           - Créer (body JSON)
 *   PUT    /api/banque/questions/{id}                      - Modifier
 *   DELETE /api/banque/questions/{id}                      - Supprimer
 *
 *   ACTIONS :
 *   POST   /api/banque/search                              - Recherche (body JSON)
 *   POST   /api/banque/draw                                - Tirage aléatoire (body JSON)
 *
 * Auth : Admin uniquement sur TOUTES les routes.
 * Format : { ok: true, data: ... } ou { ok: false, error: {code, message, details} }
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

use Examens\Lib\Auth;
use Examens\Lib\BanqueManager;
use Examens\Lib\Csrf;
use Examens\Lib\Logger;
use Examens\Lib\Response;
use Examens\Lib\Session;
use Examens\Lib\Validator;

Session::start();

$auth = new Auth();
$logger = new Logger('banque');
$bm = new BanqueManager();

// Toutes les routes requièrent l'authentification admin
$admin = $auth->requireAdmin();

// ============================================================================
// Parsing de l'URL
// ============================================================================

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ============================================================================
// Helpers d'authorisation/erreur standards
// ============================================================================

/**
 * Réponse JSON standardisée : Response::json wrap automatiquement en {ok:true, data:...}
 */
function bankResponse($data, int $status = 200): void
{
    Response::json($data, $status);
}

/**
 * Réponse d'erreur standardisée : Response::error wrap en {ok:false, error:...}
 */
function bankError(string $code, string $message, int $status = 400, array $details = []): void
{
    Response::error($code, $message, $status, $details);
}

// ============================================================================
// ROUTING
// ============================================================================

// ----------------------------------------------------------------------------
// GET /api/banque/stats
// ----------------------------------------------------------------------------
if ($path === '/api/banque/stats' && $method === 'GET') {
    try {
        $stats = $bm->getStats();
        bankResponse($stats);
    } catch (\Throwable $e) {
        $logger->error('Erreur getStats', ['error' => $e->getMessage()]);
        bankError('server_error', 'Erreur récupération stats', 500);
    }
}

// ----------------------------------------------------------------------------
// GET /api/banque/modules
// ----------------------------------------------------------------------------
if ($path === '/api/banque/modules' && $method === 'GET') {
    try {
        $modules = $bm->listModules();
        bankResponse(['modules' => $modules, 'total' => count($modules)]);
    } catch (\Throwable $e) {
        $logger->error('Erreur listModules', ['error' => $e->getMessage()]);
        bankError('server_error', 'Erreur récupération modules', 500);
    }
}

// ----------------------------------------------------------------------------
// GET /api/banque/questions           - Liste avec filtres
// ----------------------------------------------------------------------------
if ($path === '/api/banque/questions' && $method === 'GET') {
    try {
        $filters = [];

        // Filtres via query string
        if (!empty($_GET['module']))    $filters['module']    = (string) $_GET['module'];
        if (!empty($_GET['chapitre']))  $filters['chapitre']  = (string) $_GET['chapitre'];
        if (!empty($_GET['theme']))     $filters['theme']     = (string) $_GET['theme'];
        if (!empty($_GET['difficulte'])) {
            // Peut être string ou comma-separated list
            $d = (string) $_GET['difficulte'];
            $filters['difficulte'] = strpos($d, ',') !== false ? explode(',', $d) : $d;
        }
        if (!empty($_GET['type'])) {
            $t = (string) $_GET['type'];
            $filters['type'] = strpos($t, ',') !== false ? explode(',', $t) : $t;
        }
        if (!empty($_GET['tags'])) {
            $tags = (string) $_GET['tags'];
            $filters['tags'] = explode(',', $tags);
        }

        // Pagination optionnelle
        $limit = isset($_GET['limit']) ? max(1, min(1000, (int) $_GET['limit'])) : null;
        $offset = max(0, (int) ($_GET['offset'] ?? 0));

        $questions = $bm->listQuestions($filters);
        $total = count($questions);

        if ($limit !== null) {
            $questions = array_slice($questions, $offset, $limit);
        }

        bankResponse([
            'questions' => $questions,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'count' => count($questions),
            'filters' => $filters,
        ]);
    } catch (\InvalidArgumentException $e) {
        bankError('bad_request', $e->getMessage(), 400);
    } catch (\Throwable $e) {
        $logger->error('Erreur listQuestions', ['error' => $e->getMessage()]);
        bankError('server_error', 'Erreur récupération questions', 500);
    }
}

// ----------------------------------------------------------------------------
// POST /api/banque/questions           - Créer une question
// ----------------------------------------------------------------------------
if ($path === '/api/banque/questions' && $method === 'POST') {
    Csrf::requireValid();

    $body = Response::getJsonBody();

    // Extraire localisation (obligatoire)
    $module = trim((string) ($body['module'] ?? ''));
    $chapitre = trim((string) ($body['chapitre'] ?? ''));
    $theme = trim((string) ($body['theme'] ?? ''));
    $question = $body['question'] ?? null;

    if ($module === '' || $chapitre === '' || $theme === '') {
        bankError('bad_request', 'module, chapitre et theme sont requis', 400);
    }
    if (!is_array($question)) {
        bankError('bad_request', 'question doit être un objet JSON', 400);
    }

    try {
        $created = $bm->createQuestion($module, $chapitre, $theme, $question);
        $logger->info('Question créée par admin', [
            'admin_id' => $admin['id'] ?? null,
            'question_id' => $created['id'] ?? null,
            'theme' => "$module/$chapitre/$theme",
        ]);
        bankResponse(['question' => $created, 'location' => [
            'module' => $module,
            'chapitre' => $chapitre,
            'theme' => $theme,
        ]], 201);
    } catch (\InvalidArgumentException $e) {
        bankError('validation_failed', $e->getMessage(), 400);
    } catch (\RuntimeException $e) {
        bankError('conflict', $e->getMessage(), 409);
    } catch (\Throwable $e) {
        $logger->error('Erreur createQuestion', ['error' => $e->getMessage()]);
        bankError('server_error', 'Erreur création question', 500);
    }
}

// ----------------------------------------------------------------------------
// GET/PUT/DELETE /api/banque/questions/{id}
// ----------------------------------------------------------------------------
if (preg_match('#^/api/banque/questions/([a-z0-9]+-[a-z]+-[0-9]+)/?$#', $path, $m)) {
    $id = $m[1];

    // GET - détail
    if ($method === 'GET') {
        $q = $bm->getQuestion($id);
        if ($q === null) {
            bankError('not_found', "Question introuvable : $id", 404);
        }
        bankResponse(['question' => $q]);
    }

    // PUT - modifier
    if ($method === 'PUT') {
        Csrf::requireValid();

        $body = Response::getJsonBody();
        $updates = $body['updates'] ?? null;

        if (!is_array($updates) || empty($updates)) {
            bankError('bad_request', 'updates doit être un objet JSON non vide', 400);
        }

        try {
            $updated = $bm->updateQuestion($id, $updates);
            $logger->info('Question modifiée par admin', [
                'admin_id' => $admin['id'] ?? null,
                'question_id' => $id,
                'fields_updated' => array_keys($updates),
            ]);
            bankResponse(['question' => $updated]);
        } catch (\RuntimeException $e) {
            bankError('not_found', $e->getMessage(), 404);
        } catch (\InvalidArgumentException $e) {
            bankError('validation_failed', $e->getMessage(), 400);
        } catch (\Throwable $e) {
            $logger->error('Erreur updateQuestion', ['error' => $e->getMessage(), 'id' => $id]);
            bankError('server_error', 'Erreur modification question', 500);
        }
    }

    // DELETE - supprimer
    if ($method === 'DELETE') {
        Csrf::requireValid();

        try {
            $success = $bm->deleteQuestion($id);
            if ($success) {
                $logger->info('Question supprimée par admin', [
                    'admin_id' => $admin['id'] ?? null,
                    'question_id' => $id,
                ]);
                bankResponse(['deleted' => true, 'id' => $id]);
            } else {
                bankError('server_error', 'Échec suppression', 500);
            }
        } catch (\RuntimeException $e) {
            bankError('not_found', $e->getMessage(), 404);
        } catch (\Throwable $e) {
            $logger->error('Erreur deleteQuestion', ['error' => $e->getMessage(), 'id' => $id]);
            bankError('server_error', 'Erreur suppression question', 500);
        }
    }

    bankError('method_not_allowed', 'Méthode non autorisée pour questions/{id}', 405);
}

// ----------------------------------------------------------------------------
// POST /api/banque/search
// ----------------------------------------------------------------------------
if ($path === '/api/banque/search' && $method === 'POST') {
    $body = Response::getJsonBody();

    $query = trim((string) ($body['query'] ?? ''));
    $filters = is_array($body['filters'] ?? null) ? $body['filters'] : [];
    $fields = is_array($body['fields'] ?? null) ? $body['fields'] : ['enonce', 'tags', 'id'];

    if ($query === '') {
        bankError('bad_request', 'query est requis', 400);
    }

    try {
        $results = $bm->searchQuestions($query, $filters, $fields);

        // Pagination
        $limit = isset($body['limit']) ? max(1, min(500, (int) $body['limit'])) : 100;
        $total = count($results);
        $paginated = array_slice($results, 0, $limit);

        bankResponse([
            'results' => $paginated,
            'total' => $total,
            'count' => count($paginated),
            'query' => $query,
            'filters' => $filters,
        ]);
    } catch (\Throwable $e) {
        $logger->error('Erreur searchQuestions', ['error' => $e->getMessage(), 'query' => $query]);
        bankError('server_error', 'Erreur recherche', 500);
    }
}

// ----------------------------------------------------------------------------
// POST /api/banque/draw              - Tirage aléatoire personnalisé
// ----------------------------------------------------------------------------
if ($path === '/api/banque/draw' && $method === 'POST') {
    $body = Response::getJsonBody();

    $scope = is_array($body['scope'] ?? null) ? $body['scope'] : [];
    $quotas = is_array($body['quotas'] ?? null) ? $body['quotas'] : null;
    $seed = isset($body['seed']) ? (int) $body['seed'] : null;
    $strategy = (string) ($body['strategy'] ?? 'custom'); // 'custom' | 'equitable'
    $n = isset($body['n']) ? (int) $body['n'] : null;

    try {
        if ($strategy === 'equitable') {
            if ($n === null || $n <= 0) {
                bankError('bad_request', "Avec strategy='equitable', n est requis (int > 0)", 400);
            }
            $questions = $bm->drawEquitable($scope, $n, $seed);
        } else {
            if (!is_array($quotas)) {
                bankError('bad_request', "Avec strategy='custom' (défaut), quotas est requis : {facile: N, moyen: N, ...}", 400);
            }
            // Convertir les quotas en int
            $quotas = array_map('intval', $quotas);
            $questions = $bm->drawRandom($scope, $quotas, $seed);
        }

        bankResponse([
            'questions' => $questions,
            'count' => count($questions),
            'strategy' => $strategy,
            'scope' => $scope,
            'quotas' => $quotas,
            'seed' => $seed,
        ]);
    } catch (\InvalidArgumentException $e) {
        bankError('bad_request', $e->getMessage(), 400);
    } catch (\Throwable $e) {
        $logger->error('Erreur drawRandom', ['error' => $e->getMessage()]);
        bankError('server_error', 'Erreur tirage aléatoire', 500);
    }
}

// ----------------------------------------------------------------------------
// GET /api/banque/{module}/chapitres
// ----------------------------------------------------------------------------
if (preg_match('#^/api/banque/([a-z0-9_-]+)/chapitres/?$#', $path, $m)) {
    if ($method !== 'GET') {
        bankError('method_not_allowed', 'GET uniquement', 405);
    }
    try {
        $module = $m[1];
        $chapitres = $bm->listChapitres($module);
        if (empty($chapitres) && !in_array($module, $bm->listModules(), true)) {
            bankError('not_found', "Module introuvable : $module", 404);
        }
        bankResponse([
            'module' => $module,
            'chapitres' => $chapitres,
            'total' => count($chapitres),
        ]);
    } catch (\InvalidArgumentException $e) {
        bankError('bad_request', $e->getMessage(), 400);
    } catch (\Throwable $e) {
        $logger->error('Erreur listChapitres', ['error' => $e->getMessage()]);
        bankError('server_error', 'Erreur récupération chapitres', 500);
    }
}

// ----------------------------------------------------------------------------
// GET /api/banque/{module}/{chapitre}/themes
// ----------------------------------------------------------------------------
if (preg_match('#^/api/banque/([a-z0-9_-]+)/([a-z0-9_-]+)/themes/?$#', $path, $m)) {
    if ($method !== 'GET') {
        bankError('method_not_allowed', 'GET uniquement', 405);
    }
    try {
        $module = $m[1];
        $chapitre = $m[2];
        $themes = $bm->listThemes($module, $chapitre);
        if (empty($themes) && !in_array($chapitre, $bm->listChapitres($module), true)) {
            bankError('not_found', "Chapitre introuvable : $module/$chapitre", 404);
        }
        bankResponse([
            'module' => $module,
            'chapitre' => $chapitre,
            'themes' => $themes,
            'total' => count($themes),
        ]);
    } catch (\InvalidArgumentException $e) {
        bankError('bad_request', $e->getMessage(), 400);
    } catch (\Throwable $e) {
        $logger->error('Erreur listThemes', ['error' => $e->getMessage()]);
        bankError('server_error', 'Erreur récupération thèmes', 500);
    }
}

// ----------------------------------------------------------------------------
// GET /api/banque/{module}/{chapitre}/{theme}
// GET /api/banque/{module}/{chapitre}/{theme}/validate
// ----------------------------------------------------------------------------
if (preg_match('#^/api/banque/([a-z0-9_-]+)/([a-z0-9_-]+)/([a-z0-9_-]+)(/validate)?/?$#', $path, $m)) {
    if ($method !== 'GET') {
        bankError('method_not_allowed', 'GET uniquement', 405);
    }
    try {
        $module = $m[1];
        $chapitre = $m[2];
        $theme = $m[3];
        $isValidate = !empty($m[4]);

        if ($isValidate) {
            $report = $bm->validateTheme($module, $chapitre, $theme);
            if (!empty($report['errors']) && $report['errors'][0] === 'Thème introuvable') {
                bankError('not_found', "Thème introuvable : $module/$chapitre/$theme", 404);
            }
            bankResponse([
                'module' => $module,
                'chapitre' => $chapitre,
                'theme' => $theme,
                'report' => $report,
            ]);
        } else {
            $data = $bm->getTheme($module, $chapitre, $theme);
            if ($data === null) {
                bankError('not_found', "Thème introuvable : $module/$chapitre/$theme", 404);
            }
            bankResponse([
                'module' => $module,
                'chapitre' => $chapitre,
                'theme' => $theme,
                'meta' => $data['_meta'] ?? [],
                'questions' => $data['questions'] ?? [],
                'count' => count($data['questions'] ?? []),
            ]);
        }
    } catch (\InvalidArgumentException $e) {
        bankError('bad_request', $e->getMessage(), 400);
    } catch (\Throwable $e) {
        $logger->error('Erreur getTheme', ['error' => $e->getMessage()]);
        bankError('server_error', 'Erreur récupération thème', 500);
    }
}

// ----------------------------------------------------------------------------
// Route non reconnue
// ----------------------------------------------------------------------------
bankError('not_found', 'Route /api/banque non reconnue : ' . $path, 404);
