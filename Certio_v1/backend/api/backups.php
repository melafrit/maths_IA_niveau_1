<?php
/**
 * /api/backups — Routes REST pour la gestion des backups
 *
 * Routes (admin uniquement) :
 *
 *   GET    /api/backups                   - Liste des backups
 *   GET    /api/backups/stats             - Stats (nb, taille totale, oldest/newest)
 *   POST   /api/backups                   - Declencher un nouveau backup (body: {keep: 14})
 *   GET    /api/backups/{id}              - Infos d'un backup
 *   GET    /api/backups/{id}/verify       - Verifier hash SHA-256
 *   GET    /api/backups/{id}/download     - Telecharger l'archive
 *   DELETE /api/backups/{id}              - Supprimer un backup
 *
 * Auth : admin uniquement (Auth::ROLE_ADMIN)
 * CSRF : requis sur POST/DELETE
 *
 * Format reponse : { ok: true, data: ... } ou { ok: false, error: { message, code } }
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

use Examens\Lib\Auth;
use Examens\Lib\BackupManager;
use Examens\Lib\Csrf;
use Examens\Lib\Logger;
use Examens\Lib\Response;
use Examens\Lib\Session;

Session::start();

$auth = new Auth();
$logger = new Logger('backups_api');
$bm = new BackupManager();

// ============================================================================
// Helpers
// ============================================================================

function requireAdmin(Auth $auth): void {
    if (!$auth->isLoggedIn()) {
        Response::error('Non authentifie', 401);
    }
    $user = $auth->getCurrentUser();
    if (($user['role'] ?? '') !== Auth::ROLE_ADMIN) {
        Response::error('Admin requis', 403);
    }
}

// ============================================================================
// Parsing URL
// ============================================================================

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ============================================================================
// Routes
// ============================================================================

// GET /api/backups
if ($method === 'GET' && $path === '/api/backups') {
    requireAdmin($auth);
    Response::json(['ok' => true, 'data' => [
        'backups' => $bm->list(),
    ]]);
}

// GET /api/backups/stats
if ($method === 'GET' && $path === '/api/backups/stats') {
    requireAdmin($auth);
    Response::json(['ok' => true, 'data' => $bm->getStats()]);
}

// POST /api/backups (declencher nouveau backup)
if ($method === 'POST' && $path === '/api/backups') {
    requireAdmin($auth);
    Csrf::requireValid();

    $body = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $keep = (int) ($body['keep'] ?? 14);
    if ($keep < 1 || $keep > 365) {
        Response::error('keep doit etre entre 1 et 365', 400);
    }

    $logger->info("createBackup declenche (keep=$keep) par " . ($auth->getCurrentUser()['id'] ?? 'unknown'));
    $result = $bm->createBackup($keep);

    if (!$result['success']) {
        Response::error('Backup echec : ' . substr($result['output'], 0, 200), 500);
    }

    Response::json(['ok' => true, 'data' => [
        'backup_id' => $result['backup_id'],
        'duration_sec' => $result['duration_sec'],
        'info' => $bm->get($result['backup_id']),
    ]]);
}

// Routes avec ID (pattern match)
if (preg_match('#^/api/backups/([a-zA-Z0-9_-]+)(?:/([a-z]+))?$#', $path, $matches)) {
    $id = $matches[1];
    $action = $matches[2] ?? null;

    requireAdmin($auth);

    // GET /api/backups/{id}
    if ($method === 'GET' && $action === null) {
        $info = $bm->get($id);
        if ($info === null) {
            Response::error('Backup introuvable', 404);
        }
        Response::json(['ok' => true, 'data' => $info]);
    }

    // GET /api/backups/{id}/verify
    if ($method === 'GET' && $action === 'verify') {
        $result = $bm->verify($id);
        Response::json(['ok' => true, 'data' => $result]);
    }

    // GET /api/backups/{id}/download
    if ($method === 'GET' && $action === 'download') {
        $info = $bm->get($id);
        if ($info === null) {
            Response::error('Backup introuvable', 404);
        }
        if (!file_exists($info['path'])) {
            Response::error('Fichier physique introuvable', 404);
        }

        $logger->info("Download backup $id par " . ($auth->getCurrentUser()['id'] ?? 'unknown'));

        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . $info['filename'] . '"');
        header('Content-Length: ' . $info['size']);
        header('X-Content-SHA256: ' . ($info['hash'] ?? ''));
        readfile($info['path']);
        exit;
    }

    // DELETE /api/backups/{id}
    if ($method === 'DELETE' && $action === null) {
        Csrf::requireValid();

        $ok = $bm->delete($id);
        if (!$ok) {
            Response::error('Suppression echouee ou backup introuvable', 404);
        }

        $logger->info("Delete backup $id par " . ($auth->getCurrentUser()['id'] ?? 'unknown'));
        Response::json(['ok' => true, 'data' => ['deleted' => true, 'id' => $id]]);
    }
}

// Aucune route trouvee
Response::error('Route introuvable : ' . $method . ' ' . $path, 404);
