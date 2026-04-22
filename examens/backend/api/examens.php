<?php
/**
 * /api/examens — Gestion des examens (prof/admin)
 *
 * Routes REST :
 *
 *   LECTURE :
 *   GET    /api/examens                          - Liste (auth prof/admin)
 *   GET    /api/examens/stats                    - Stats globales (admin)
 *   GET    /api/examens/by-code/{code}           - Par code d'acces (public limite)
 *   GET    /api/examens/{id}                     - Detail (createur/admin)
 *
 *   ECRITURE :
 *   POST   /api/examens                          - Creer (auth)
 *   PUT    /api/examens/{id}                     - Modifier (createur/admin)
 *   DELETE /api/examens/{id}                     - Supprimer draft (createur/admin)
 *
 *   CYCLE DE VIE :
 *   POST   /api/examens/{id}/publish             - draft -> published
 *   POST   /api/examens/{id}/close               - published -> closed
 *   POST   /api/examens/{id}/archive             - closed/draft -> archived
 *
 * Regles d'auth :
 *   - Auth requise sur toutes les routes (sauf by-code pour etudiants)
 *   - Les profs ne voient/modifient que LEURS examens
 *   - Les admins voient/modifient TOUS les examens
 *   - Stats admin uniquement
 *   - by-code ne renvoie QUE les examens publies et dans la fenetre d'ouverture
 *
 * Format : { ok: true, data: ... } ou { ok: false, error: {...} }
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

use Examens\Lib\Auth;
use Examens\Lib\Csrf;
use Examens\Lib\ExamenManager;
use Examens\Lib\Logger;
use Examens\Lib\Response;
use Examens\Lib\Session;

Session::start();

$auth = new Auth();
$logger = new Logger('examens');
$em = new ExamenManager();

// ============================================================================
// Parsing de l'URL
// ============================================================================

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ============================================================================
// Helper : vérifier la propriété (créateur) ou admin
// ============================================================================

function requireOwnerOrAdmin(array $examen, array $user): void
{
    $isOwner = ($examen['created_by'] ?? null) === ($user['id'] ?? null);
    $isAdmin = ($user['role'] ?? '') === Auth::ROLE_ADMIN;

    if (!$isOwner && !$isAdmin) {
        Response::forbidden('Vous ne pouvez modifier que vos propres examens.');
    }
}

// ============================================================================
// ROUTING
// ============================================================================

// ----------------------------------------------------------------------------
// GET /api/examens/by-code/{code}       - Public limite (pour etudiants)
// ----------------------------------------------------------------------------
if (preg_match('#^/api/examens/by-code/([A-Z0-9]{3,10})/?$#i', $path, $m)) {
    if ($method !== 'GET') {
        Response::methodNotAllowed();
    }

    $code = strtoupper($m[1]);
    $examen = $em->getByAccessCode($code);

    if ($examen === null) {
        Response::notFound("Aucun examen avec le code : $code");
    }

    // Seulement les examens publies et dans la fenetre d'ouverture
    $now = time();
    $openTs = strtotime($examen['date_ouverture']);
    $closeTs = strtotime($examen['date_cloture']);

    if ($examen['status'] !== ExamenManager::STATUS_PUBLISHED) {
        Response::error(
            'not_available',
            'Cet examen n\'est pas encore disponible ou est deja cloture.',
            403,
            ['status' => $examen['status']]
        );
    }

    if ($now < $openTs) {
        Response::error(
            'not_yet_open',
            'Cet examen ne sera disponible qu\'a partir de la date d\'ouverture.',
            403,
            [
                'date_ouverture' => $examen['date_ouverture'],
                'opens_in_sec' => $openTs - $now,
            ]
        );
    }

    if ($now > $closeTs) {
        Response::error(
            'closed',
            'La periode d\'ouverture de cet examen est terminee.',
            403,
            ['date_cloture' => $examen['date_cloture']]
        );
    }

    // Renvoyer une version PUBLIQUE (sans info sensible)
    $public = [
        'id' => $examen['id'],
        'titre' => $examen['titre'],
        'description' => $examen['description'],
        'duree_sec' => $examen['duree_sec'],
        'nb_questions' => count($examen['questions']),
        'date_ouverture' => $examen['date_ouverture'],
        'date_cloture' => $examen['date_cloture'],
        'max_passages' => $examen['max_passages'],
        'access_code' => $examen['access_code'],
    ];

    Response::json($public);
}

// ----------------------------------------------------------------------------
// GET /api/examens/stats                - Admin only
// ----------------------------------------------------------------------------
if ($path === '/api/examens/stats' && $method === 'GET') {
    $auth->requireAdmin();

    try {
        $stats = $em->getStats();
        Response::json($stats);
    } catch (\Throwable $e) {
        $logger->error('Erreur getStats examens', ['error' => $e->getMessage()]);
        Response::serverError('Erreur stats examens');
    }
}

// ----------------------------------------------------------------------------
// GET /api/examens                      - Liste (auth)
// ----------------------------------------------------------------------------
if ($path === '/api/examens' && $method === 'GET') {
    $user = $auth->requireAuth();
    $isAdmin = ($user['role'] ?? '') === Auth::ROLE_ADMIN;

    $filters = [];

    // Filtres query string
    if (!empty($_GET['status'])) {
        $s = (string) $_GET['status'];
        $filters['status'] = strpos($s, ',') !== false ? explode(',', $s) : $s;
    }
    if (!empty($_GET['after'])) {
        $filters['after'] = (string) $_GET['after'];
    }
    if (!empty($_GET['before'])) {
        $filters['before'] = (string) $_GET['before'];
    }

    // Prof = uniquement ses examens (sauf admin)
    if (!$isAdmin) {
        $filters['created_by'] = $user['id'];
    } elseif (!empty($_GET['created_by'])) {
        $filters['created_by'] = (string) $_GET['created_by'];
    }

    try {
        $examens = $em->list($filters);

        // Pagination optionnelle
        $limit = isset($_GET['limit']) ? max(1, min(1000, (int) $_GET['limit'])) : null;
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $total = count($examens);

        if ($limit !== null) {
            $examens = array_slice($examens, $offset, $limit);
        }

        Response::json([
            'examens' => $examens,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'count' => count($examens),
            'filters' => $filters,
        ]);
    } catch (\Throwable $e) {
        $logger->error('Erreur listExamens', ['error' => $e->getMessage()]);
        Response::serverError('Erreur liste examens');
    }
}

// ----------------------------------------------------------------------------
// POST /api/examens                     - Creer (auth)
// ----------------------------------------------------------------------------
if ($path === '/api/examens' && $method === 'POST') {
    $user = $auth->requireAuth();
    Csrf::requireValid();

    $body = Response::getJsonBody();

    try {
        $examen = $em->create($body, $user['id']);
        $logger->info('Examen cree', [
            'id' => $examen['id'],
            'titre' => $examen['titre'],
            'created_by' => $user['id'],
        ]);
        Response::json(['examen' => $examen], 201);
    } catch (\InvalidArgumentException $e) {
        Response::error('validation_failed', $e->getMessage(), 400);
    } catch (\Throwable $e) {
        $logger->error('Erreur createExamen', ['error' => $e->getMessage()]);
        Response::serverError('Erreur creation examen');
    }
}

// ----------------------------------------------------------------------------
// GET / PUT / DELETE /api/examens/{id}
// ----------------------------------------------------------------------------
if (preg_match('#^/api/examens/(EXM-[A-Z0-9]{4}-[A-Z0-9]{4})/?$#', $path, $m)) {
    $id = $m[1];
    $user = $auth->requireAuth();
    $examen = $em->get($id);

    if ($examen === null) {
        Response::notFound("Examen introuvable : $id");
    }

    // GET : lecture (owner ou admin)
    if ($method === 'GET') {
        requireOwnerOrAdmin($examen, $user);
        Response::json(['examen' => $examen]);
    }

    // PUT : modification
    if ($method === 'PUT') {
        requireOwnerOrAdmin($examen, $user);
        Csrf::requireValid();

        $body = Response::getJsonBody();
        $updates = $body['updates'] ?? null;

        if (!is_array($updates) || empty($updates)) {
            Response::badRequest('updates doit etre un objet JSON non vide');
        }

        try {
            $updated = $em->update($id, $updates);
            $logger->info('Examen modifie', [
                'id' => $id,
                'by' => $user['id'],
                'fields' => array_keys($updates),
            ]);
            Response::json(['examen' => $updated]);
        } catch (\InvalidArgumentException $e) {
            Response::error('validation_failed', $e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            Response::notFound($e->getMessage());
        } catch (\Throwable $e) {
            $logger->error('Erreur updateExamen', ['error' => $e->getMessage(), 'id' => $id]);
            Response::serverError('Erreur modification examen');
        }
    }

    // DELETE : suppression (draft only, owner/admin)
    if ($method === 'DELETE') {
        requireOwnerOrAdmin($examen, $user);
        Csrf::requireValid();

        try {
            $success = $em->delete($id);
            if ($success) {
                $logger->info('Examen supprime', ['id' => $id, 'by' => $user['id']]);
                Response::json(['deleted' => true, 'id' => $id]);
            } else {
                Response::serverError('Echec suppression');
            }
        } catch (\InvalidArgumentException $e) {
            Response::error('not_allowed', $e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            Response::notFound($e->getMessage());
        } catch (\Throwable $e) {
            $logger->error('Erreur deleteExamen', ['error' => $e->getMessage(), 'id' => $id]);
            Response::serverError('Erreur suppression examen');
        }
    }

    Response::methodNotAllowed('GET, PUT ou DELETE uniquement pour examens/{id}');
}

// ----------------------------------------------------------------------------
// POST /api/examens/{id}/publish
// POST /api/examens/{id}/close
// POST /api/examens/{id}/archive
// ----------------------------------------------------------------------------
if (preg_match('#^/api/examens/(EXM-[A-Z0-9]{4}-[A-Z0-9]{4})/(publish|close|archive)/?$#', $path, $m)) {
    if ($method !== 'POST') {
        Response::methodNotAllowed('POST uniquement pour transitions');
    }

    $id = $m[1];
    $action = $m[2];
    $user = $auth->requireAuth();
    $examen = $em->get($id);

    if ($examen === null) {
        Response::notFound("Examen introuvable : $id");
    }

    requireOwnerOrAdmin($examen, $user);
    Csrf::requireValid();

    try {
        $updated = match($action) {
            'publish' => $em->publish($id),
            'close' => $em->close($id),
            'archive' => $em->archive($id),
            default => throw new \InvalidArgumentException('Action inconnue'),
        };

        $logger->info('Transition examen', [
            'id' => $id,
            'action' => $action,
            'by' => $user['id'],
            'new_status' => $updated['status'],
        ]);

        Response::json([
            'examen' => $updated,
            'action' => $action,
            'new_status' => $updated['status'],
        ]);
    } catch (\InvalidArgumentException $e) {
        Response::error('invalid_transition', $e->getMessage(), 400);
    } catch (\RuntimeException $e) {
        Response::notFound($e->getMessage());
    } catch (\Throwable $e) {
        $logger->error('Erreur transition examen', [
            'error' => $e->getMessage(),
            'id' => $id,
            'action' => $action,
        ]);
        Response::serverError('Erreur transition examen');
    }
}

// ----------------------------------------------------------------------------
// Route non reconnue
// ----------------------------------------------------------------------------
Response::notFound('Route /api/examens non reconnue : ' . $path);
