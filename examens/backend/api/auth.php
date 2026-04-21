<?php
/**
 * /api/auth/{action}
 *
 * Endpoints d'authentification :
 *   POST /api/auth/login         - Connexion (email + password)
 *   POST /api/auth/logout        - Déconnexion
 *   GET  /api/auth/me            - Compte courant
 *   GET  /api/auth/csrf-token    - Récupère le token CSRF courant
 *   POST /api/auth/change-password - Change son propre mot de passe
 *
 * Réponses au format JSON standard :
 *   {ok: true, data: {...}}
 *   {ok: false, error: {code, message, details}}
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

use Examens\Lib\Auth;
use Examens\Lib\Csrf;
use Examens\Lib\Logger;
use Examens\Lib\RateLimiter;
use Examens\Lib\Response;
use Examens\Lib\Session;
use Examens\Lib\Utils;
use Examens\Lib\Validator;

// Démarrer la session
Session::start();

// Parser l'action depuis l'URL : /api/auth/{action}
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = '/' . trim($path, '/');
if (!preg_match('#^/api/auth/([a-z0-9_-]+)/?$#i', $path, $m)) {
    Response::notFound('Action auth invalide');
}
$action = strtolower($m[1]);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$auth = new Auth();
$logger = new Logger('auth');

// ============================================================================
// Routing
// ============================================================================

switch ($action) {

    // ------------------------------------------------------------------------
    // GET /api/auth/csrf-token
    // ------------------------------------------------------------------------
    case 'csrf-token':
        if ($method !== 'GET') {
            Response::methodNotAllowed();
        }
        Response::json([
            'token' => Csrf::token(),
        ]);
        break;

    // ------------------------------------------------------------------------
    // GET /api/auth/me
    // ------------------------------------------------------------------------
    case 'me':
        if ($method !== 'GET') {
            Response::methodNotAllowed();
        }
        $user = $auth->user();
        if ($user === null) {
            Response::json([
                'authenticated' => false,
                'user' => null,
            ]);
        }
        Response::json([
            'authenticated' => true,
            'user' => $user,
            'csrf_token' => Csrf::token(),
        ]);
        break;

    // ------------------------------------------------------------------------
    // POST /api/auth/login
    // ------------------------------------------------------------------------
    case 'login':
        if ($method !== 'POST') {
            Response::methodNotAllowed();
        }

        $body = Response::getJsonBody();
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        $v = new Validator(['email' => $email, 'password' => $password]);
        $v->required('email')->email('email');
        $v->required('password')->minLength('password', 1); // verif vraie en DB

        if (!$v->isValid()) {
            Response::badRequest('Email ou mot de passe invalide.', $v->errors());
        }

        // Rate limiting par IP
        $maxAttempts = (int)config('security.rate_limit.login_max_attempts', 5);
        $window = (int)config('security.rate_limit.login_window_sec', 900);
        $rl = new RateLimiter('login', $maxAttempts, $window);
        $clientIp = Utils::clientIp();

        if ($rl->isBlocked($clientIp)) {
            $retry = $rl->retryAfter($clientIp);
            Response::rateLimited(
                "Trop de tentatives échouées. Réessayez dans " . ceil($retry / 60) . " minutes.",
                $retry
            );
        }

        // Tentative de login
        $compte = $auth->login($email, $password);

        if ($compte === null) {
            // Échec : enregistrer
            $rl->record($clientIp);
            $remaining = $rl->remaining($clientIp);
            Response::error(
                'invalid_credentials',
                'Email ou mot de passe incorrect.' .
                ($remaining > 0 ? " ({$remaining} tentative(s) restante(s))" : ''),
                401
            );
        }

        // Succès : reset rate limit + retour
        $rl->reset($clientIp);

        Response::json([
            'user' => $compte,
            'csrf_token' => Csrf::token(),
            'message' => 'Connexion réussie.',
        ]);
        break;

    // ------------------------------------------------------------------------
    // POST /api/auth/logout
    // ------------------------------------------------------------------------
    case 'logout':
        if ($method !== 'POST') {
            Response::methodNotAllowed();
        }

        // CSRF requis pour logout (anti-CSRF logout = anti-DOS gentil)
        // On accepte sans CSRF si pas connecté (idempotent)
        if ($auth->isAuthenticated()) {
            Csrf::requireValid();
        }

        $auth->logout();
        Response::json(['message' => 'Déconnexion réussie.']);
        break;

    // ------------------------------------------------------------------------
    // POST /api/auth/change-password
    // Change son propre mot de passe (auth requise + CSRF + ancien mdp)
    // ------------------------------------------------------------------------
    case 'change-password':
        if ($method !== 'POST') {
            Response::methodNotAllowed();
        }

        $user = $auth->requireAuth();
        Csrf::requireValid();

        $body = Response::getJsonBody();
        $currentPwd = (string)($body['current_password'] ?? '');
        $newPwd = (string)($body['new_password'] ?? '');
        $confirmPwd = (string)($body['confirm_password'] ?? '');

        $v = new Validator([
            'current_password' => $currentPwd,
            'new_password' => $newPwd,
            'confirm_password' => $confirmPwd,
        ]);
        $v->required('current_password')->required('new_password')->required('confirm_password');
        $v->minLength('new_password', 8);

        if (!$v->isValid()) {
            Response::badRequest('Champs invalides.', $v->errors());
        }

        if ($newPwd !== $confirmPwd) {
            Response::badRequest('La confirmation du mot de passe ne correspond pas.');
        }

        if ($newPwd === $currentPwd) {
            Response::badRequest('Le nouveau mot de passe doit être différent de l\'ancien.');
        }

        // Vérifier l'ancien mot de passe
        $compte = $auth->findByEmail($user['email']);
        if (!$compte || !password_verify($currentPwd, $compte['password_hash'])) {
            $logger->warning('Change-password : ancien mot de passe incorrect', [
                'id' => $user['id'],
                'email' => $user['email'],
            ]);
            Response::error('invalid_current_password', 'L\'ancien mot de passe est incorrect.', 401);
        }

        try {
            $auth->updatePassword($user['id'], $newPwd);
            Response::json(['message' => 'Mot de passe modifié avec succès.']);
        } catch (\Throwable $e) {
            $logger->error('Erreur changement mot de passe', ['error' => $e->getMessage()]);
            Response::serverError('Une erreur est survenue lors de la modification.');
        }
        break;

    // ------------------------------------------------------------------------
    // Action inconnue
    // ------------------------------------------------------------------------
    default:
        Response::notFound("Action auth inconnue : {$action}");
}
