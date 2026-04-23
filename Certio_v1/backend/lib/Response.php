<?php
/**
 * Response — Helpers pour réponses JSON standardisées
 *
 * Toutes les réponses API suivent ce format :
 *   Succès : { "ok": true, "data": ... }
 *   Erreur : { "ok": false, "error": { "code": "XXX", "message": "..." } }
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class Response
{
    /**
     * Réponse de succès avec données.
     */
    public static function json($data = null, int $status = 200): void
    {
        self::setHeaders($status);
        echo json_encode([
            'ok' => true,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Réponse d'erreur structurée.
     *
     * @param string $code    Code machine (ex: 'invalid_credentials', 'not_found')
     * @param string $message Message lisible par l'utilisateur (en français)
     * @param int    $status  Code HTTP (400, 401, 403, 404, 500...)
     */
    public static function error(string $code, string $message, int $status = 400, array $details = []): void
    {
        self::setHeaders($status);
        echo json_encode([
            'ok' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => (object)$details, // objet vide plutôt que tableau vide
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // ------------------------------------------------------------------------
    // Raccourcis pour les cas fréquents
    // ------------------------------------------------------------------------

    public static function notFound(string $message = 'Ressource introuvable'): void
    {
        self::error('not_found', $message, 404);
    }

    public static function unauthorized(string $message = 'Authentification requise'): void
    {
        self::error('unauthorized', $message, 401);
    }

    public static function forbidden(string $message = 'Accès refusé'): void
    {
        self::error('forbidden', $message, 403);
    }

    public static function badRequest(string $message = 'Requête invalide', array $details = []): void
    {
        self::error('bad_request', $message, 400, $details);
    }

    public static function methodNotAllowed(string $message = 'Méthode non autorisée'): void
    {
        self::error('method_not_allowed', $message, 405);
    }

    public static function serverError(string $message = 'Erreur interne du serveur'): void
    {
        self::error('server_error', $message, 500);
    }

    public static function rateLimited(string $message = 'Trop de requêtes, réessayez plus tard', int $retryAfter = 60): void
    {
        header('Retry-After: ' . $retryAfter);
        self::error('rate_limited', $message, 429, ['retry_after' => $retryAfter]);
    }

    // ------------------------------------------------------------------------
    // Utilitaires
    // ------------------------------------------------------------------------

    /**
     * Récupère le body JSON de la requête courante.
     *
     * @return array Données décodées (vide si erreur)
     */
    public static function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Définit les headers JSON + CORS (si nécessaire).
     */
    private static function setHeaders(int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        // Désactiver le cache pour les réponses API par défaut
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }
}
