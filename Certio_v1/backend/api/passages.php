<?php
/**
 * /api/passages — Gestion des passages d'examen
 *
 * Routes :
 *
 *   PUBLIQUES (etudiants, pas d'auth mais token requis) :
 *     POST   /api/passages/start                  - Demarrer (retourne token)
 *     GET    /api/passages/{token}/progress       - Reprendre / etat
 *     POST   /api/passages/{token}/answer         - Sauvegarder reponse
 *     POST   /api/passages/{token}/focus-event    - Log anti-triche
 *     POST   /api/passages/{token}/submit         - Finaliser
 *
 *   AUTHENTIFIEES (prof/admin) :
 *     GET    /api/passages                        - Liste
 *     GET    /api/passages/stats                  - Stats
 *     GET    /api/passages/{id}                   - Detail complet
 *     POST   /api/passages/{id}/invalidate        - Fraude
 *
 * Securite :
 *   - Routes etudiant : validite du token = seule auth
 *   - CSRF desactive pour routes etudiant (pas de session etudiant)
 *   - Verification systematique status=in_progress pour modifier
 *   - Prof/admin : requireOwnerOrAdmin sur examen associe
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
use Examens\Lib\PassageManager;
use Examens\Lib\Response;
use Examens\Lib\Session;

Session::start();

$auth = new Auth();
$logger = new Logger('passages');
$pm = new PassageManager();
$em = new ExamenManager();

// ============================================================================
// Parsing URL
// ============================================================================

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ============================================================================
// Helper : vérifier owner examen ou admin (pour routes prof)
// ============================================================================

function requirePassageOwnerOrAdmin(array $passage, array $user, ExamenManager $em): void
{
    $isAdmin = ($user['role'] ?? '') === Auth::ROLE_ADMIN;
    if ($isAdmin) return;

    // L'examen doit avoir été créé par ce prof
    $examen = $em->get($passage['examen_id']);
    if ($examen === null) {
        Response::notFound('Examen associe introuvable');
    }

    if (($examen['created_by'] ?? null) !== ($user['id'] ?? null)) {
        Response::forbidden('Vous ne pouvez voir que les passages de vos examens.');
    }
}

// ============================================================================
// Token pattern (UUID v4 relaxed)
// ============================================================================

$TOKEN_PATTERN = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';
$ID_PATTERN = 'PSG-[A-Z0-9]{4}-[A-Z0-9]{4}';

// ============================================================================
// ROUTE 1 : POST /api/passages/start (PUBLIC)
// ============================================================================
if ($path === '/api/passages/start' && $method === 'POST') {
    $body = Response::getJsonBody();

    $examenId = $body['examen_id'] ?? null;
    $studentInfo = $body['student_info'] ?? null;

    if (!$examenId) {
        Response::badRequest('examen_id requis');
    }
    if (!is_array($studentInfo)) {
        Response::badRequest('student_info requis (objet avec nom, prenom, email)');
    }

    try {
        $passage = $pm->start($examenId, $studentInfo);

        // Construire la version "premiere reponse" (pas de revelation du correct)
        $banque = new \Examens\Lib\BanqueManager();
        $questions = [];

        foreach ($passage['question_order'] as $qId) {
            $q = $banque->getQuestion($qId);
            if ($q === null) continue;

            // Construire options dans l'ordre shuffle
            $shuffleMap = $passage['option_shuffle_maps'][$qId] ?? [0, 1, 2, 3];
            $shuffledOptions = [];
            foreach ($shuffleMap as $origIdx) {
                $shuffledOptions[] = $q['options'][$origIdx] ?? '';
            }

            $questions[] = [
                'id' => $q['id'],
                'enonce' => $q['enonce'],
                'options' => $shuffledOptions,
                'type' => $q['type'],
                'difficulte' => $q['difficulte'],
                // Pas de 'correct', 'hint', 'explanation' pendant le passage !
            ];
        }

        $logger->info('Passage demarre via API', [
            'passage_id' => $passage['id'],
            'examen_id' => $examenId,
            'email' => $studentInfo['email'] ?? '',
        ]);

        Response::json([
            'token' => $passage['token'],
            'passage_id' => $passage['id'],
            'examen' => [
                'id' => $passage['examen_id'],
                'titre' => $em->get($examenId)['titre'],
                'duree_sec' => $em->get($examenId)['duree_sec'],
                'start_time' => $passage['start_time'],
            ],
            'questions' => $questions,
            'answers' => (object)($passage['answers'] ?? []),
        ], 201);
    } catch (\InvalidArgumentException $e) {
        Response::error('validation_failed', $e->getMessage(), 400);
    } catch (\RuntimeException $e) {
        // Erreurs metier : examen introuvable, non publie, fenetre fermee, max_passages
        $code = 'start_failed';
        $msg = $e->getMessage();
        if (strpos($msg, 'maximal') !== false) $code = 'max_passages_reached';
        elseif (strpos($msg, 'pas encore ouvert') !== false) $code = 'not_yet_open';
        elseif (strpos($msg, 'deja ferme') !== false) $code = 'closed';
        elseif (strpos($msg, 'non disponible') !== false) $code = 'not_available';
        elseif (strpos($msg, 'introuvable') !== false) $code = 'not_found';
        Response::error($code, $msg, strpos($code, 'not_found') === 0 ? 404 : 403);
    } catch (\Throwable $e) {
        $logger->error('Erreur start passage', ['error' => $e->getMessage()]);
        Response::serverError('Erreur demarrage passage');
    }
}

// ============================================================================
// Routes prof/admin (avant les routes token-based pour eviter conflit)
// ============================================================================

// ROUTE 6 : GET /api/passages (AUTH)
if ($path === '/api/passages' && $method === 'GET') {
    $user = $auth->requireAuth();
    $isAdmin = ($user['role'] ?? '') === Auth::ROLE_ADMIN;

    $filters = [];
    if (!empty($_GET['examen_id'])) $filters['examen_id'] = $_GET['examen_id'];
    if (!empty($_GET['email'])) $filters['email'] = $_GET['email'];
    if (!empty($_GET['status'])) {
        $s = (string) $_GET['status'];
        $filters['status'] = strpos($s, ',') !== false ? explode(',', $s) : $s;
    }
    if (!empty($_GET['since'])) $filters['since'] = $_GET['since'];
    if (!empty($_GET['until'])) $filters['until'] = $_GET['until'];

    try {
        $passages = $pm->list($filters);

        // Prof : filtrer pour ne garder que ses examens
        if (!$isAdmin) {
            $myExamens = [];
            foreach ($em->list(['created_by' => $user['id']]) as $ex) {
                $myExamens[$ex['id']] = true;
            }
            $passages = array_values(array_filter($passages, function($p) use ($myExamens) {
                return isset($myExamens[$p['examen_id']]);
            }));
        }

        // Pagination
        $limit = isset($_GET['limit']) ? max(1, min(1000, (int) $_GET['limit'])) : null;
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $total = count($passages);
        if ($limit !== null) {
            $passages = array_slice($passages, $offset, $limit);
        }

        Response::json([
            'passages' => $passages,
            'total' => $total,
            'count' => count($passages),
            'offset' => $offset,
            'limit' => $limit,
            'filters' => $filters,
        ]);
    } catch (\Throwable $e) {
        $logger->error('Erreur list passages', ['error' => $e->getMessage()]);
        Response::serverError('Erreur liste passages');
    }
}

// ROUTE 7 : GET /api/passages/stats (ADMIN ou prof sur son examen)
if ($path === '/api/passages/stats' && $method === 'GET') {
    $user = $auth->requireAuth();
    $isAdmin = ($user['role'] ?? '') === Auth::ROLE_ADMIN;

    $examenId = $_GET['examen_id'] ?? null;

    // Si prof : doit specifier examen_id et etre owner
    if (!$isAdmin) {
        if (!$examenId) {
            Response::forbidden('Specifiez un examen_id (stats globales reservees aux admins)');
        }
        $examen = $em->get($examenId);
        if ($examen === null) {
            Response::notFound("Examen introuvable : $examenId");
        }
        if (($examen['created_by'] ?? null) !== $user['id']) {
            Response::forbidden('Vous ne pouvez voir que les stats de vos examens.');
        }
    }

    try {
        $stats = $pm->getStats($examenId);
        Response::json($stats);
    } catch (\Throwable $e) {
        $logger->error('Erreur stats passages', ['error' => $e->getMessage()]);
        Response::serverError('Erreur stats');
    }
}

// ============================================================================
// ROUTES BY TOKEN (PUBLIC étudiants)
// ============================================================================

// ROUTE 2 : GET /api/passages/{token}/progress
if (preg_match("#^/api/passages/($TOKEN_PATTERN)/progress/?$#", $path, $m)) {
    if ($method !== 'GET') Response::methodNotAllowed();

    $token = $m[1];
    $passage = $pm->getByToken($token);

    if ($passage === null) {
        Response::notFound("Token invalide ou passage introuvable");
    }

    // Verifier expiration et auto-expirer si besoin
    if ($passage['status'] === PassageManager::STATUS_IN_PROGRESS && $pm->isExpired($passage)) {
        $passage = $pm->expire($passage['id']);
    }

    $examen = $em->get($passage['examen_id']);

    // Re-construire les questions avec options shuffled (pas de spoilers)
    $banque = new \Examens\Lib\BanqueManager();
    $questions = [];
    foreach ($passage['question_order'] as $qId) {
        $q = $banque->getQuestion($qId);
        if ($q === null) continue;
        $shuffleMap = $passage['option_shuffle_maps'][$qId] ?? [0, 1, 2, 3];
        $shuffledOptions = [];
        foreach ($shuffleMap as $origIdx) {
            $shuffledOptions[] = $q['options'][$origIdx] ?? '';
        }
        $questions[] = [
            'id' => $q['id'],
            'enonce' => $q['enonce'],
            'options' => $shuffledOptions,
            'type' => $q['type'],
            'difficulte' => $q['difficulte'],
        ];
    }

    // Calculer temps restant si in_progress
    $timeRemaining = null;
    if ($passage['status'] === PassageManager::STATUS_IN_PROGRESS && $examen !== null) {
        $startTs = strtotime($passage['start_time']);
        $dureeSec = (int) $examen['duree_sec'];
        $deadline = $startTs + $dureeSec;
        $examClose = strtotime($examen['date_cloture']);
        $effective = min($deadline, $examClose);
        $timeRemaining = max(0, $effective - time());
    }

    Response::json([
        'passage_id' => $passage['id'],
        'token' => $token,
        'status' => $passage['status'],
        'examen' => $examen !== null ? [
            'id' => $examen['id'],
            'titre' => $examen['titre'],
            'duree_sec' => $examen['duree_sec'],
        ] : null,
        'student_info' => $passage['student_info'],
        'start_time' => $passage['start_time'],
        'end_time' => $passage['end_time'],
        'time_remaining_sec' => $timeRemaining,
        'questions' => $questions,
        'answers' => (object) ($passage['answers'] ?? []),
        'nb_answered' => count((array) $passage['answers']),
        'nb_total' => count($passage['question_order']),
    ]);
}

// ROUTE 3 : POST /api/passages/{token}/answer
if (preg_match("#^/api/passages/($TOKEN_PATTERN)/answer/?$#", $path, $m)) {
    if ($method !== 'POST') Response::methodNotAllowed();

    $token = $m[1];
    $body = Response::getJsonBody();

    $questionId = $body['question_id'] ?? null;
    $answerIndex = $body['answer_index'] ?? null;

    if (!$questionId || $answerIndex === null) {
        Response::badRequest('question_id et answer_index requis');
    }

    try {
        $updated = $pm->saveAnswer($token, (string) $questionId, (int) $answerIndex);
        Response::json([
            'saved' => true,
            'question_id' => $questionId,
            'nb_answered' => count((array) $updated['answers']),
            'nb_total' => count($updated['question_order']),
        ]);
    } catch (\InvalidArgumentException $e) {
        Response::error('validation_failed', $e->getMessage(), 400);
    } catch (\RuntimeException $e) {
        $code = 'save_failed';
        $msg = $e->getMessage();
        if (strpos($msg, 'introuvable') !== false) $code = 'not_found';
        elseif (strpos($msg, 'expire') !== false || strpos($msg, 'Temps ecoule') !== false) {
            $code = 'expired';
        } elseif (strpos($msg, 'non modifiable') !== false) {
            $code = 'not_modifiable';
        }
        Response::error($code, $msg, $code === 'not_found' ? 404 : 400);
    } catch (\Throwable $e) {
        $logger->error('Erreur saveAnswer', ['error' => $e->getMessage()]);
        Response::serverError('Erreur sauvegarde reponse');
    }
}

// ROUTE 4 : POST /api/passages/{token}/focus-event
if (preg_match("#^/api/passages/($TOKEN_PATTERN)/focus-event/?$#", $path, $m)) {
    if ($method !== 'POST') Response::methodNotAllowed();

    $token = $m[1];
    $body = Response::getJsonBody();

    $eventType = $body['type'] ?? null;
    if (!$eventType) {
        Response::badRequest('type requis (blur, focus, visibility_change, copy, paste, rightclick, devtools)');
    }

    try {
        $updated = $pm->logFocusEvent($token, [
            'type' => $eventType,
            'duration_ms' => (int) ($body['duration_ms'] ?? 0),
            'details' => $body['details'] ?? null,
        ]);
        Response::json([
            'logged' => true,
            'type' => $eventType,
            'total_events' => count($updated['focus_events']),
        ]);
    } catch (\InvalidArgumentException $e) {
        Response::error('validation_failed', $e->getMessage(), 400);
    } catch (\RuntimeException $e) {
        Response::notFound($e->getMessage());
    } catch (\Throwable $e) {
        $logger->error('Erreur focus event', ['error' => $e->getMessage()]);
        Response::serverError('Erreur log focus');
    }
}

// ROUTE 5 : POST /api/passages/{token}/submit
if (preg_match("#^/api/passages/($TOKEN_PATTERN)/submit/?$#", $path, $m)) {
    if ($method !== 'POST') Response::methodNotAllowed();

    $token = $m[1];

    try {
        $submitted = $pm->submit($token);

        // Verifier si l'examen doit afficher la correction
        $examen = $em->get($submitted['examen_id']);
        $canShowCorrection = (bool) ($examen['show_correction_after'] ?? true);
        $correctionDelay = (int) ($examen['correction_delay_min'] ?? 0);
        $correctionReady = $canShowCorrection && $correctionDelay === 0;

        Response::json([
            'submitted' => true,
            'passage_id' => $submitted['id'],
            'status' => $submitted['status'],
            'score' => [
                'brut' => $submitted['score_brut'],
                'max' => $submitted['score_max'],
                'pct' => $submitted['score_pct'],
            ],
            'duration_sec' => $submitted['duration_sec'],
            'end_time' => $submitted['end_time'],
            'signature' => substr($submitted['signature_sha256'], 0, 16) . '...', // preview seulement
            'correction_available' => $correctionReady,
            'correction_delay_min' => $correctionDelay,
        ]);
    } catch (\RuntimeException $e) {
        $code = 'submit_failed';
        $msg = $e->getMessage();
        if (strpos($msg, 'introuvable') !== false) $code = 'not_found';
        elseif (strpos($msg, 'non soumettable') !== false) $code = 'already_submitted';
        Response::error($code, $msg, $code === 'not_found' ? 404 : 400);
    } catch (\Throwable $e) {
        $logger->error('Erreur submit', ['error' => $e->getMessage()]);
        Response::serverError('Erreur soumission');
    }
}

// ============================================================================
// ROUTES BY ID (AUTH - prof/admin)
// ============================================================================

// ROUTE 8 : GET /api/passages/{id} (AUTH)
if (preg_match("#^/api/passages/($ID_PATTERN)/?$#", $path, $m)) {
    $id = $m[1];
    $user = $auth->requireAuth();

    $passage = $pm->get($id);
    if ($passage === null) {
        Response::notFound("Passage introuvable : $id");
    }

    if ($method === 'GET') {
        requirePassageOwnerOrAdmin($passage, $user, $em);

        // Enrichir avec verification de signature (pour prof)
        $signatureValid = $passage['signature_sha256']
            ? $pm->verifySignature($passage)
            : null;

        Response::json([
            'passage' => $passage,
            'signature_valid' => $signatureValid,
            'is_expired' => $pm->isExpired($passage),
        ]);
    }

    Response::methodNotAllowed('GET uniquement pour passages/{id}');
}

// ROUTE 9 : POST /api/passages/{id}/invalidate (AUTH)
if (preg_match("#^/api/passages/($ID_PATTERN)/invalidate/?$#", $path, $m)) {
    if ($method !== 'POST') Response::methodNotAllowed();

    $id = $m[1];
    $user = $auth->requireAuth();
    Csrf::requireValid();

    $passage = $pm->get($id);
    if ($passage === null) {
        Response::notFound("Passage introuvable : $id");
    }

    requirePassageOwnerOrAdmin($passage, $user, $em);

    $body = Response::getJsonBody();
    $reason = trim($body['reason'] ?? '');

    if (empty($reason)) {
        Response::badRequest('reason requis pour invalider un passage');
    }

    try {
        $invalidated = $pm->invalidate($id, $reason);
        $logger->warning('Passage invalide par prof/admin', [
            'id' => $id,
            'by' => $user['id'],
            'reason' => $reason,
        ]);
        Response::json([
            'invalidated' => true,
            'passage' => $invalidated,
        ]);
    } catch (\Throwable $e) {
        $logger->error('Erreur invalidate', ['error' => $e->getMessage()]);
        Response::serverError('Erreur invalidation');
    }
}

// ============================================================================
// Route non reconnue
// ============================================================================
Response::notFound('Route /api/passages non reconnue : ' . $path);
