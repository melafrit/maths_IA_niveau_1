<?php
/**
 * /api/analytics — Routes REST pour les analytics
 *
 * Routes (toutes en GET) :
 *
 *   PAR EXAMEN (auth: owner ou admin) :
 *     GET /api/analytics/examen/{id}/overview        - KPIs globaux
 *     GET /api/analytics/examen/{id}/scores          - Distribution scores
 *     GET /api/analytics/examen/{id}/questions       - Stats par Q + distracteurs
 *     GET /api/analytics/examen/{id}/timeline        - Passages dans le temps
 *     GET /api/analytics/examen/{id}/focus-heatmap   - Anti-triche
 *     GET /api/analytics/examen/{id}/passages        - Historique enrichi
 *
 *   PAR PROF (auth: utilisateur connecte) :
 *     GET /api/analytics/prof/overview               - Dashboard prof (ses examens)
 *
 *   PAR ETUDIANT (auth: admin OU prof avec passage commun) :
 *     GET /api/analytics/student/{email}             - Historique multi-examens
 *
 * Format reponse : { ok: true, data: ... }
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

use Examens\Lib\AnalyticsManager;
use Examens\Lib\Auth;
use Examens\Lib\ExamenManager;
use Examens\Lib\Logger;
use Examens\Lib\PassageManager;
use Examens\Lib\Response;
use Examens\Lib\Session;

Session::start();

$auth = new Auth();
$logger = new Logger('analytics_api');
$am = new AnalyticsManager();
$em = new ExamenManager();
$pm = new PassageManager();

// ============================================================================
// Parsing URL
// ============================================================================

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$EXAM_PATTERN = 'EXM-[A-Z0-9]{4}-[A-Z0-9]{4}';

// ============================================================================
// Helper : verifier auth examen (owner ou admin)
// ============================================================================

function requireExamenOwnerOrAdmin(string $examenId, array $user, ExamenManager $em): array
{
    $examen = $em->get($examenId);
    if ($examen === null) {
        Response::notFound("Examen introuvable : $examenId");
    }
    $isAdmin = ($user['role'] ?? '') === Auth::ROLE_ADMIN;
    if (!$isAdmin && ($examen['created_by'] ?? null) !== ($user['id'] ?? null)) {
        Response::forbidden('Vous ne pouvez voir que les analytics de vos examens.');
    }
    return $examen;
}

// ============================================================================
// Helper : parser query params communs
// ============================================================================

function parseListOptions(): array
{
    $opts = [];
    if (!empty($_GET['status'])) {
        $s = (string) $_GET['status'];
        $opts['status'] = strpos($s, ',') !== false ? explode(',', $s) : $s;
    }
    if (!empty($_GET['search'])) $opts['search'] = (string) $_GET['search'];
    if (!empty($_GET['email'])) $opts['email'] = (string) $_GET['email'];
    if (isset($_GET['since'])) $opts['since'] = (string) $_GET['since'];
    if (isset($_GET['until'])) $opts['until'] = (string) $_GET['until'];
    if (isset($_GET['min_score_pct'])) $opts['min_score_pct'] = (float) $_GET['min_score_pct'];
    if (isset($_GET['max_score_pct'])) $opts['max_score_pct'] = (float) $_GET['max_score_pct'];
    if (!empty($_GET['with_anomalies'])) {
        $opts['with_anomalies'] = filter_var($_GET['with_anomalies'], FILTER_VALIDATE_BOOLEAN);
    }
    if (!empty($_GET['sort'])) {
        $valid = ['date', 'score', 'name', 'duration'];
        $s = (string) $_GET['sort'];
        if (in_array($s, $valid, true)) $opts['sort'] = $s;
    }
    if (!empty($_GET['order'])) {
        $o = strtolower((string) $_GET['order']);
        if (in_array($o, ['asc', 'desc'], true)) $opts['order'] = $o;
    }
    if (isset($_GET['limit'])) $opts['limit'] = max(1, min(500, (int) $_GET['limit']));
    if (isset($_GET['offset'])) $opts['offset'] = max(0, (int) $_GET['offset']);
    return $opts;
}

// ============================================================================
// ROUTE 1 : GET /api/analytics/examen/{id}/overview
// ============================================================================
if (preg_match("#^/api/analytics/examen/($EXAM_PATTERN)/overview/?$#", $path, $m)) {
    if ($method !== 'GET') Response::methodNotAllowed();

    $user = $auth->requireAuth();
    requireExamenOwnerOrAdmin($m[1], $user, $em);

    try {
        $overview = $am->getExamenOverview($m[1]);
        Response::json($overview);
    } catch (\RuntimeException $e) {
        Response::notFound($e->getMessage());
    } catch (\Throwable $e) {
        $logger->error('Erreur overview', ['error' => $e->getMessage()]);
        Response::serverError('Erreur calcul overview');
    }
}

// ============================================================================
// ROUTE 2 : GET /api/analytics/examen/{id}/scores
// ============================================================================
if (preg_match("#^/api/analytics/examen/($EXAM_PATTERN)/scores/?$#", $path, $m)) {
    if ($method !== 'GET') Response::methodNotAllowed();

    $user = $auth->requireAuth();
    requireExamenOwnerOrAdmin($m[1], $user, $em);

    try {
        $dist = $am->getScoreDistribution($m[1]);
        Response::json($dist);
    } catch (\Throwable $e) {
        $logger->error('Erreur scores', ['error' => $e->getMessage()]);
        Response::serverError('Erreur distribution scores');
    }
}

// ============================================================================
// ROUTE 3 : GET /api/analytics/examen/{id}/questions
// ============================================================================
if (preg_match("#^/api/analytics/examen/($EXAM_PATTERN)/questions/?$#", $path, $m)) {
    if ($method !== 'GET') Response::methodNotAllowed();

    $user = $auth->requireAuth();
    requireExamenOwnerOrAdmin($m[1], $user, $em);

    $withDetails = filter_var(
        $_GET['with_details'] ?? 'true',
        FILTER_VALIDATE_BOOLEAN
    );

    try {
        $stats = $am->getQuestionStats($m[1], $withDetails);
        Response::json($stats);
    } catch (\Throwable $e) {
        $logger->error('Erreur questions stats', ['error' => $e->getMessage()]);
        Response::serverError('Erreur stats par question');
    }
}

// ============================================================================
// ROUTE 4 : GET /api/analytics/examen/{id}/timeline
// ============================================================================
if (preg_match("#^/api/analytics/examen/($EXAM_PATTERN)/timeline/?$#", $path, $m)) {
    if ($method !== 'GET') Response::methodNotAllowed();

    $user = $auth->requireAuth();
    requireExamenOwnerOrAdmin($m[1], $user, $em);

    try {
        $timeline = $am->getTimeline($m[1]);
        Response::json([
            'examen_id' => $m[1],
            'timeline' => $timeline,
        ]);
    } catch (\Throwable $e) {
        $logger->error('Erreur timeline', ['error' => $e->getMessage()]);
        Response::serverError('Erreur timeline');
    }
}

// ============================================================================
// ROUTE 5 : GET /api/analytics/examen/{id}/focus-heatmap
// ============================================================================
if (preg_match("#^/api/analytics/examen/($EXAM_PATTERN)/focus-heatmap/?$#", $path, $m)) {
    if ($method !== 'GET') Response::methodNotAllowed();

    $user = $auth->requireAuth();
    requireExamenOwnerOrAdmin($m[1], $user, $em);

    try {
        $hm = $am->getFocusHeatmap($m[1]);
        Response::json($hm);
    } catch (\Throwable $e) {
        $logger->error('Erreur heatmap', ['error' => $e->getMessage()]);
        Response::serverError('Erreur heatmap');
    }
}

// ============================================================================
// ROUTE 6 : GET /api/analytics/examen/{id}/passages (HISTORIQUE)
// ============================================================================
if (preg_match("#^/api/analytics/examen/($EXAM_PATTERN)/passages/?$#", $path, $m)) {
    if ($method !== 'GET') Response::methodNotAllowed();

    $user = $auth->requireAuth();
    requireExamenOwnerOrAdmin($m[1], $user, $em);

    $opts = parseListOptions();
    $opts['examen_id'] = $m[1];

    try {
        $result = $am->listPassagesEnriched($opts);
        Response::json($result);
    } catch (\Throwable $e) {
        $logger->error('Erreur historique passages', ['error' => $e->getMessage()]);
        Response::serverError('Erreur historique');
    }
}

// ============================================================================
// ROUTE 7 : GET /api/analytics/prof/overview
// ============================================================================
if ($path === '/api/analytics/prof/overview' && $method === 'GET') {
    $user = $auth->requireAuth();
    $isAdmin = ($user['role'] ?? '') === Auth::ROLE_ADMIN;

    // Si admin et prof_id en query → consulter celui-la
    $targetProfId = $user['id'];
    if ($isAdmin && !empty($_GET['prof_id'])) {
        $targetProfId = (string) $_GET['prof_id'];
    }

    try {
        $overview = $am->getProfOverview($targetProfId);
        Response::json($overview);
    } catch (\Throwable $e) {
        $logger->error('Erreur prof overview', ['error' => $e->getMessage()]);
        Response::serverError('Erreur overview prof');
    }
}

// ============================================================================
// ROUTE 8 : GET /api/analytics/student/{email}
// ============================================================================
if (preg_match('#^/api/analytics/student/(.+)/?$#', $path, $m)) {
    if ($method !== 'GET') Response::methodNotAllowed();

    $email = urldecode($m[1]);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Response::badRequest('Email invalide');
    }

    $user = $auth->requireAuth();
    $isAdmin = ($user['role'] ?? '') === Auth::ROLE_ADMIN;

    try {
        $history = $am->getStudentHistory($email);

        // Auth check : si pas admin, le prof ne peut voir que les passages
        // sur SES examens => filtrer
        if (!$isAdmin) {
            // Recuperer la liste des examen_ids du prof
            $myExamens = [];
            foreach ($em->list(['created_by' => $user['id']]) as $e) {
                $myExamens[$e['id']] = true;
            }

            $filteredPassages = array_values(array_filter(
                $history['passages'],
                fn($p) => isset($myExamens[$p['examen_id']])
            ));

            if (empty($filteredPassages)) {
                Response::forbidden(
                    'Cet etudiant n\'a passe aucun de vos examens (acces refuse).'
                );
            }

            $history['passages'] = $filteredPassages;
            $history['nb_passages'] = count($filteredPassages);

            // Recalculer les stats sur le sous-ensemble
            $scores = array_values(array_filter(
                array_map(fn($p) => $p['score_pct'], $filteredPassages),
                fn($s) => $s !== null
            ));
            $history['avg_score_pct'] = !empty($scores)
                ? round(array_sum($scores) / count($scores), 2) : 0;
            $history['best_score_pct'] = !empty($scores) ? max($scores) : 0;
            $history['worst_score_pct'] = !empty($scores) ? min($scores) : 0;
            $history['total_time_sec'] = array_sum(
                array_map(fn($p) => $p['duration_sec'] ?? 0, $filteredPassages)
            );
            $history['filtered_to_prof'] = $user['id'];
        }

        Response::json($history);
    } catch (\Throwable $e) {
        $logger->error('Erreur student history', ['error' => $e->getMessage()]);
        Response::serverError('Erreur historique etudiant');
    }
}

// ============================================================================
// Route non reconnue
// ============================================================================
Response::notFound('Route /api/analytics non reconnue : ' . $path);
