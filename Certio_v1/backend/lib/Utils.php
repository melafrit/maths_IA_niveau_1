<?php
/**
 * Utils — Fonctions utilitaires diverses
 *
 * Regroupe les helpers qui ne justifient pas une classe à part entière :
 *   - Génération d'identifiants et codes aléatoires
 *   - Manipulation de chaînes
 *   - Formatage
 *   - Chiffrement/déchiffrement AES-256
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class Utils
{
    /**
     * Alphabet sans ambiguïtés (pas de I, O, 0, 1) pour les codes d'examen
     * et suffixes aléatoires. Utilisé pour IPSSI-B2-2026-A4F7 (décision Q3).
     */
    public const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    // ========================================================================
    // GÉNÉRATEURS ALÉATOIRES
    // ========================================================================

    /**
     * Génère une chaîne aléatoire depuis un alphabet donné.
     *
     * Utilise random_int() (cryptographiquement sûr).
     */
    public static function randomString(int $length, string $alphabet = self::CODE_ALPHABET): string
    {
        $out = '';
        $alphabetLen = strlen($alphabet);
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $alphabetLen - 1)];
        }
        return $out;
    }

    /**
     * Génère un code d'examen au format "PREFIXE-XXXX" (décision Q3).
     *
     * @param string $prefix Ex: "IPSSI-B2-2026"
     * @param int    $suffixLength Nombre de caractères aléatoires (défaut 4)
     */
    public static function generateExamCode(string $prefix, int $suffixLength = 4): string
    {
        $suffix = self::randomString($suffixLength);
        return strtoupper($prefix) . '-' . $suffix;
    }

    /**
     * Génère un identifiant unique interne (style UUIDv4-like mais plus court).
     * Format : "AAAA-BBBB-CCCC" (12 caractères significatifs).
     */
    public static function generateId(): string
    {
        return sprintf(
            '%s-%s-%s',
            self::randomString(4),
            self::randomString(4),
            self::randomString(4)
        );
    }

    /**
     * Génère un token sécurisé pour URL (accès correction, reset password, etc.)
     * Utilise bytes aléatoires + base64 URL-safe.
     */
    public static function generateToken(int $bytes = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    // ========================================================================
    // CHAÎNES
    // ========================================================================

    /**
     * Normalise un nom pour sécurité filename : UPPERCASE + retrait accents.
     * Ex: "Élodie Mc'Donald" -> "ELODIE_MCDONALD"
     */
    public static function normalizeNom(string $nom): string
    {
        $nom = mb_strtoupper($nom, 'UTF-8');
        $nom = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nom);
        $nom = preg_replace('/[^A-Z0-9]/', '_', $nom ?: '');
        $nom = preg_replace('/_+/', '_', $nom);
        return trim($nom, '_');
    }

    /**
     * Normalise un prénom : Title Case + retrait accents.
     * Ex: "MARIE-ÉLODIE" -> "Marie_Elodie"
     */
    public static function normalizePrenom(string $prenom): string
    {
        $prenom = mb_convert_case($prenom, MB_CASE_TITLE, 'UTF-8');
        $prenom = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $prenom);
        $prenom = preg_replace('/[^A-Za-z0-9]/', '_', $prenom ?: '');
        $prenom = preg_replace('/_+/', '_', $prenom);
        return trim($prenom, '_');
    }

    /**
     * Normalise un email : trim + lowercase.
     */
    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email), 'UTF-8');
    }

    /**
     * Tronque une chaîne avec ellipse si trop longue.
     */
    public static function truncate(string $str, int $maxLength = 180, string $suffix = '…'): string
    {
        if (mb_strlen($str, 'UTF-8') <= $maxLength) {
            return $str;
        }
        return mb_substr($str, 0, $maxLength - mb_strlen($suffix, 'UTF-8'), 'UTF-8') . $suffix;
    }

    // ========================================================================
    // FORMATAGE
    // ========================================================================

    /**
     * Formate une durée en secondes en "1h42m33s" ou "42m13s".
     */
    public static function formatDuration(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        if ($h > 0) {
            return sprintf('%dh%02dm%02ds', $h, $m, $s);
        }
        return sprintf('%dm%02ds', $m, $s);
    }

    /**
     * Formate une date ISO pour affichage français.
     */
    public static function formatDate(string $iso, string $format = 'd/m/Y H:i'): string
    {
        try {
            $dt = new \DateTime($iso);
            return $dt->format($format);
        } catch (\Exception $e) {
            return $iso;
        }
    }

    /**
     * Retourne la date actuelle au format ISO 8601 (UTC par défaut).
     */
    public static function now(bool $utc = false): string
    {
        $tz = $utc ? new \DateTimeZone('UTC') : null;
        return (new \DateTime('now', $tz))->format('c');
    }

    // ========================================================================
    // CHIFFREMENT AES-256-GCM (pour clés API IA notamment)
    // ========================================================================

    /**
     * Chiffre une chaîne avec AES-256-GCM.
     * La clé doit être une chaîne de 32 octets (256 bits).
     *
     * @return string Chaîne chiffrée encodée en base64 (IV + ciphertext + tag)
     */
    public static function encrypt(string $plaintext, string $key): string
    {
        if (strlen($key) !== 32) {
            // Dériver 32 octets depuis la clé via SHA-256 si longueur différente
            $key = hash('sha256', $key, true);
        }
        $iv = random_bytes(12); // 96 bits recommandé pour GCM
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        if ($ciphertext === false) {
            throw new \RuntimeException('Échec du chiffrement AES-256-GCM');
        }
        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Déchiffre une chaîne produite par encrypt().
     *
     * @return string|null Chaîne en clair ou null si échec (données corrompues/modifiées)
     */
    public static function decrypt(string $encrypted, string $key): ?string
    {
        if (strlen($key) !== 32) {
            $key = hash('sha256', $key, true);
        }
        $data = base64_decode($encrypted, true);
        if ($data === false || strlen($data) < 28) {
            return null;
        }
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        return $plaintext === false ? null : $plaintext;
    }

    // ========================================================================
    // SIGNATURES ET HASHES
    // ========================================================================

    /**
     * Hash SHA-256 d'une chaîne (format hexadécimal).
     */
    public static function sha256(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Hash SHA-256 avec salt (pour signatures CSV d'examens, cf. décision Q8).
     *
     * @param array $parts Éléments à concaténer et hasher
     * @param string $salt Salt d'application
     */
    public static function signParts(array $parts, string $salt): string
    {
        $payload = implode('||', array_map(fn($p) => (string)$p, $parts));
        return hash('sha256', $payload . $salt);
    }

    // ========================================================================
    // RÉSEAU / IP
    // ========================================================================

    /**
     * Récupère l'IP client en tenant compte des proxies (X-Forwarded-For).
     * Uniquement si IP proxy est dans la whitelist de config.
     */
    public static function clientIp(array $trustedProxies = []): string
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if (in_array($remote, $trustedProxies, true)) {
            $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            if ($xff !== '') {
                $ips = array_map('trim', explode(',', $xff));
                return $ips[0]; // première IP = client réel
            }
        }

        return $remote;
    }

    /**
     * Récupère le User-Agent tronqué à une longueur raisonnable (anti-bloat).
     */
    public static function userAgent(int $maxLength = 255): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return self::truncate($ua, $maxLength);
    }

    // ========================================================================
    // COMPARAISONS SÉCURISÉES
    // ========================================================================

    /**
     * Comparaison timing-safe (résistante aux timing attacks).
     */
    public static function secureEquals(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }
}
