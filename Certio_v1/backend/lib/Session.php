<?php
/**
 * Session — Gestion des sessions PHP sécurisées
 *
 * Encapsule l'API session_* de PHP avec :
 *   - Démarrage paresseux (lazy)
 *   - Régénération périodique de l'ID (anti session fixation)
 *   - Helpers get/set/has/delete
 *   - Stockage flash (messages one-shot)
 *   - Destruction propre au logout
 *
 * Le démarrage de la session utilise les paramètres de cookies définis dans
 * bootstrap.php (HttpOnly, SameSite=Strict, Secure en prod).
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class Session
{
    private const FLASH_KEY = '_flash';
    private const META_KEY  = '_meta';

    /**
     * Démarre la session si pas encore démarrée.
     * Régénère l'ID si la dernière régénération est trop ancienne.
     */
    public static function start(): void
    {
        if (php_sapi_name() === 'cli') {
            return; // Pas de session en CLI
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (!session_start()) {
            throw new \RuntimeException('Impossible de démarrer la session.');
        }

        // Initialiser les métadonnées de session
        if (!isset($_SESSION[self::META_KEY])) {
            $_SESSION[self::META_KEY] = [
                'created_at'        => time(),
                'last_regenerated'  => time(),
                'last_activity'     => time(),
                'fingerprint'       => self::computeFingerprint(),
            ];
        }

        // Vérifier le fingerprint (anti-hijacking basique)
        $currentFp = self::computeFingerprint();
        if (($_SESSION[self::META_KEY]['fingerprint'] ?? '') !== $currentFp) {
            // Fingerprint changé -> probable hijacking, on détruit
            self::destroy();
            return;
        }

        // Régénérer l'ID périodiquement (anti session fixation)
        $regenInterval = (int)config('security.session.regenerate_id', 300);
        $lastRegen = $_SESSION[self::META_KEY]['last_regenerated'] ?? 0;
        if (time() - $lastRegen > $regenInterval) {
            self::regenerate();
        }

        // Mettre à jour la dernière activité
        $_SESSION[self::META_KEY]['last_activity'] = time();
    }

    /**
     * Régénère l'ID de session (sans perdre les données).
     */
    public static function regenerate(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        session_regenerate_id(true);
        $_SESSION[self::META_KEY]['last_regenerated'] = time();
    }

    /**
     * Récupère une valeur de session.
     */
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Définit une valeur de session.
     */
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Vérifie l'existence d'une clé.
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Supprime une clé.
     */
    public static function delete(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Détruit complètement la session (logout).
     */
    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        // Supprimer le cookie de session côté client
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    // ========================================================================
    // FLASH (messages one-shot)
    // ========================================================================

    /**
     * Définit un message flash (lu une seule fois puis supprimé).
     */
    public static function flash(string $key, $value): void
    {
        self::start();
        if (!isset($_SESSION[self::FLASH_KEY])) {
            $_SESSION[self::FLASH_KEY] = [];
        }
        $_SESSION[self::FLASH_KEY][$key] = $value;
    }

    /**
     * Récupère et supprime un message flash.
     */
    public static function getFlash(string $key, $default = null)
    {
        self::start();
        $value = $_SESSION[self::FLASH_KEY][$key] ?? $default;
        unset($_SESSION[self::FLASH_KEY][$key]);
        return $value;
    }

    // ========================================================================
    // INTROSPECTION
    // ========================================================================

    /**
     * Retourne l'ID de session actuel.
     */
    public static function id(): string
    {
        self::start();
        return session_id() ?: '';
    }

    /**
     * Retourne les métadonnées (created_at, last_regenerated, etc.).
     */
    public static function meta(): array
    {
        self::start();
        return $_SESSION[self::META_KEY] ?? [];
    }

    // ========================================================================
    // INTERNES
    // ========================================================================

    /**
     * Calcule un fingerprint basé sur User-Agent et fragments d'IP
     * pour détecter le vol de session.
     *
     * On ne prend PAS l'IP entière (mobile change souvent), juste les 2 premiers
     * octets pour les IPv4 (réseau) et le préfixe pour IPv6.
     */
    private static function computeFingerprint(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Garder seulement les 2 premiers octets de l'IPv4 (résiste au changement
        // de réseau Wi-Fi vs mobile sur même opérateur)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $ip = $parts[0] . '.' . ($parts[1] ?? '0') . '.x.x';
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Garder les 4 premiers blocs
            $parts = explode(':', $ip);
            $ip = implode(':', array_slice($parts, 0, 4)) . '::x';
        }

        $salt = config('security.app_salt', 'CHANGE_ME');
        return hash('sha256', $ua . '|' . $ip . '|' . $salt);
    }
}
