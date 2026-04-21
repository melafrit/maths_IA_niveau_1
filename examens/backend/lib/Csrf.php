<?php
/**
 * Csrf — Protection CSRF (Cross-Site Request Forgery)
 *
 * Génère et vérifie des tokens CSRF stockés en session.
 *
 * Usage côté serveur :
 *   $token = Csrf::token();              // Récupère ou génère le token
 *   if (!Csrf::verify($_POST['_csrf'])) {
 *     Response::forbidden('Token CSRF invalide');
 *   }
 *
 * Usage côté frontend :
 *   - GET /api/auth/csrf-token retourne le token courant
 *   - Inclure dans header X-CSRF-Token ou body _csrf à chaque POST/PUT/DELETE
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class Csrf
{
    private const SESSION_KEY = '_csrf_token';
    private const SESSION_KEY_TIMESTAMP = '_csrf_token_ts';

    /**
     * Récupère le token CSRF actuel ou en génère un nouveau si absent ou expiré.
     */
    public static function token(): string
    {
        $existing = Session::get(self::SESSION_KEY);
        $ts = Session::get(self::SESSION_KEY_TIMESTAMP, 0);
        $ttl = (int)config('security.csrf.token_ttl', 7200);

        if ($existing && (time() - $ts) < $ttl) {
            return $existing;
        }

        return self::regenerate();
    }

    /**
     * Force la régénération d'un nouveau token (à appeler après login par ex.).
     */
    public static function regenerate(): string
    {
        $length = (int)config('security.csrf.token_length', 32);
        $token = Utils::generateToken($length);
        Session::set(self::SESSION_KEY, $token);
        Session::set(self::SESSION_KEY_TIMESTAMP, time());
        return $token;
    }

    /**
     * Vérifie qu'un token soumis correspond à celui en session (timing-safe).
     *
     * @param string|null $submitted Token soumis (peut venir du body, du header...)
     */
    public static function verify(?string $submitted): bool
    {
        if ($submitted === null || $submitted === '') {
            return false;
        }

        $stored = Session::get(self::SESSION_KEY);
        if ($stored === null || $stored === '') {
            return false;
        }

        // Vérifier l'expiration
        $ts = Session::get(self::SESSION_KEY_TIMESTAMP, 0);
        $ttl = (int)config('security.csrf.token_ttl', 7200);
        if ((time() - $ts) > $ttl) {
            return false;
        }

        return Utils::secureEquals($stored, $submitted);
    }

    /**
     * Détruit le token CSRF (à appeler au logout).
     */
    public static function clear(): void
    {
        Session::delete(self::SESSION_KEY);
        Session::delete(self::SESSION_KEY_TIMESTAMP);
    }

    /**
     * Récupère le token soumis depuis (par ordre de priorité) :
     *   1. Header HTTP X-CSRF-Token
     *   2. Body JSON, champ "_csrf"
     *   3. Form-encoded, champ _csrf
     */
    public static function getSubmittedToken(): ?string
    {
        // 1. Header
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'X-CSRF-Token') === 0) {
                return is_string($value) ? $value : null;
            }
        }

        // 2. JSON body
        $body = Response::getJsonBody();
        if (isset($body['_csrf']) && is_string($body['_csrf'])) {
            return $body['_csrf'];
        }

        // 3. POST form-encoded
        if (isset($_POST['_csrf']) && is_string($_POST['_csrf'])) {
            return $_POST['_csrf'];
        }

        return null;
    }

    /**
     * Middleware-like : vérifie le CSRF et arrête si invalide.
     * Pratique en début d'endpoint mutant.
     */
    public static function requireValid(): void
    {
        $token = self::getSubmittedToken();
        if (!self::verify($token)) {
            Response::forbidden('Token CSRF invalide ou expiré');
        }
    }
}
