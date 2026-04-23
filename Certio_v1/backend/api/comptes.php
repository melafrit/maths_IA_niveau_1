<?php
/**
 * /api/comptes(/{id})
 *
 * CRUD des comptes enseignants. Admin uniquement (sauf cas spéciaux).
 *
 *   GET    /api/comptes              - Liste tous les comptes (admin)
 *   POST   /api/comptes              - Créer un compte (admin)
 *   GET    /api/comptes/{id}         - Détail d'un compte (admin OU soi-même)
 *   PUT    /api/comptes/{id}         - Modifier un compte (admin OU soi-même limité)
 *   DELETE /api/comptes/{id}         - Désactiver un compte (admin)
 *   POST   /api/comptes/{id}/enable  - Réactiver (admin)
 *   POST   /api/comptes/{id}/destroy - Suppression définitive (admin, dangereux)
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

use Examens\Lib\Auth;
use Examens\Lib\Csrf;
use Examens\Lib\Logger;
use Examens\Lib\Response;
use Examens\Lib\Session;
use Examens\Lib\Validator;

Session::start();

$auth = new Auth();
$logger = new Logger('auth');

// Parser l'URL : /api/comptes ou /api/comptes/{id} ou /api/comptes/{id}/{sub}
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = '/' . trim($path, '/');

$id = null;
$sub = null;

if (preg_match('#^/api/comptes/([A-Z0-9-]{14})/?([a-z0-9_-]+)?/?$#', $path, $m)) {
    $id = $m[1];
    $sub = $m[2] ?? null;
} elseif (!preg_match('#^/api/comptes/?$#', $path)) {
    Response::notFound('Route comptes invalide');
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ============================================================================
// Routing
// ============================================================================

// ------- GET /api/comptes (liste) -------
if ($id === null && $method === 'GET') {
    $auth->requireAdmin();
    $comptes = $auth->listComptes();
    Response::json([
        'comptes' => $comptes,
        'total' => count($comptes),
    ]);
}

// ------- POST /api/comptes (création) -------
if ($id === null && $method === 'POST') {
    $admin = $auth->requireAdmin();
    Csrf::requireValid();

    $body = Response::getJsonBody();
    $email = trim((string)($body['email'] ?? ''));
    $password = (string)($body['password'] ?? '');
    $nom = trim((string)($body['nom'] ?? ''));
    $prenom = trim((string)($body['prenom'] ?? ''));
    $role = (string)($body['role'] ?? Auth::ROLE_ENSEIGNANT);

    $v = new Validator([
        'email' => $email,
        'password' => $password,
        'nom' => $nom,
        'prenom' => $prenom,
        'role' => $role,
    ]);
    $v->required('email')->email('email');
    $v->required('password')->minLength('password', 8);
    $v->required('nom')->maxLength('nom', 100);
    $v->required('prenom')->maxLength('prenom', 100);
    $v->required('role')->in('role', [Auth::ROLE_ADMIN, Auth::ROLE_ENSEIGNANT]);

    if (!$v->isValid()) {
        Response::badRequest('Données de création invalides.', $v->errors());
    }

    try {
        $created = $auth->createCompte([
            'email' => $email,
            'password' => $password,
            'nom' => $nom,
            'prenom' => $prenom,
            'role' => $role,
            'created_by' => $admin['id'],
        ]);
        Response::json([
            'compte' => $created,
            'message' => 'Compte créé avec succès.',
        ], 201);
    } catch (\InvalidArgumentException $e) {
        Response::badRequest($e->getMessage());
    } catch (\Throwable $e) {
        $logger->error('Erreur création compte', ['error' => $e->getMessage()]);
        Response::serverError('Erreur lors de la création du compte.');
    }
}

// ------- À partir d'ici, on a un ID -------
if ($id === null) {
    Response::methodNotAllowed();
}

$user = $auth->requireAuth();
$isAdmin = ($user['role'] ?? '') === Auth::ROLE_ADMIN;
$isSelf = ($user['id'] ?? '') === $id;

$target = $auth->findById($id);
if ($target === null) {
    Response::notFound('Compte introuvable.');
}

// ------- GET /api/comptes/{id} -------
if ($sub === null && $method === 'GET') {
    if (!$isAdmin && !$isSelf) {
        Response::forbidden('Vous ne pouvez consulter que votre propre compte.');
    }
    Response::json(['compte' => $target]);
}

// ------- PUT /api/comptes/{id} -------
if ($sub === null && $method === 'PUT') {
    Csrf::requireValid();

    if (!$isAdmin && !$isSelf) {
        Response::forbidden('Vous ne pouvez modifier que votre propre compte.');
    }

    $body = Response::getJsonBody();
    $updates = [];

    // Champs modifiables par tout le monde (sur soi-même)
    if (isset($body['nom'])) {
        $updates['nom'] = trim((string)$body['nom']);
    }
    if (isset($body['prenom'])) {
        $updates['prenom'] = trim((string)$body['prenom']);
    }
    if (isset($body['preferences']) && is_array($body['preferences'])) {
        $updates['preferences'] = $body['preferences'];
    }

    // Champs réservés à l'admin
    if ($isAdmin) {
        if (isset($body['role']) && in_array($body['role'], [Auth::ROLE_ADMIN, Auth::ROLE_ENSEIGNANT], true)) {
            $updates['role'] = $body['role'];
        }
        if (isset($body['active'])) {
            $updates['active'] = (bool)$body['active'];
        }
    }

    if (empty($updates)) {
        Response::badRequest('Aucune modification valide à appliquer.');
    }

    // Garde-fou : empêcher l'auto-désactivation ou changement de rôle pour soi-même
    if ($isSelf) {
        if (isset($updates['active']) && $updates['active'] === false) {
            Response::badRequest('Vous ne pouvez pas désactiver votre propre compte.');
        }
        if (isset($updates['role']) && $updates['role'] !== ($user['role'] ?? '')) {
            Response::badRequest('Vous ne pouvez pas modifier votre propre rôle.');
        }
    }

    // Garde-fou : ne pas désactiver le dernier admin
    if ($isAdmin && isset($updates['active']) && $updates['active'] === false) {
        if (($target['role'] ?? '') === Auth::ROLE_ADMIN && $auth->countActiveAdmins() <= 1) {
            Response::badRequest('Impossible de désactiver le dernier administrateur actif.');
        }
    }

    if ($auth->updateCompte($id, $updates)) {
        $refreshed = $auth->findById($id);
        Response::json([
            'compte' => $refreshed,
            'message' => 'Compte modifié avec succès.',
        ]);
    } else {
        Response::serverError('Erreur lors de la modification.');
    }
}

// ------- DELETE /api/comptes/{id} (= disable, soft) -------
if ($sub === null && $method === 'DELETE') {
    $auth->requireAdmin();
    Csrf::requireValid();

    if ($isSelf) {
        Response::badRequest('Vous ne pouvez pas désactiver votre propre compte.');
    }

    if (($target['role'] ?? '') === Auth::ROLE_ADMIN && $auth->countActiveAdmins() <= 1) {
        Response::badRequest('Impossible de désactiver le dernier administrateur actif.');
    }

    if ($auth->disableCompte($id)) {
        Response::json(['message' => 'Compte désactivé avec succès.']);
    } else {
        Response::serverError('Erreur lors de la désactivation.');
    }
}

// ------- POST /api/comptes/{id}/enable -------
if ($sub === 'enable' && $method === 'POST') {
    $auth->requireAdmin();
    Csrf::requireValid();
    if ($auth->enableCompte($id)) {
        Response::json(['message' => 'Compte réactivé avec succès.']);
    } else {
        Response::serverError('Erreur lors de la réactivation.');
    }
}

// ------- POST /api/comptes/{id}/destroy (suppression définitive) -------
if ($sub === 'destroy' && $method === 'POST') {
    $auth->requireAdmin();
    Csrf::requireValid();

    if ($isSelf) {
        Response::badRequest('Vous ne pouvez pas supprimer votre propre compte.');
    }
    if (($target['role'] ?? '') === Auth::ROLE_ADMIN && $auth->countActiveAdmins() <= 1) {
        Response::badRequest('Impossible de supprimer le dernier administrateur.');
    }

    if ($auth->deleteCompte($id)) {
        Response::json(['message' => 'Compte supprimé définitivement.']);
    } else {
        Response::serverError('Erreur lors de la suppression.');
    }
}

Response::methodNotAllowed();
