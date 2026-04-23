<?php
/**
 * RoleRateLimiter.php — Rate limiting API par role
 *
 * Middleware qui applique un rate limit different selon le role :
 *   - admin      : illimite
 *   - enseignant : 500/min
 *   - etudiant   : 60/min
 *   - anonyme    : 30/min (par IP)
 *
 * Reutilise RateLimiter existant (bucket par role+id, file storage).
 *
 * Usage :
 *   $rl = new RoleRateLimiter();
 *   $check = $rl->check($role, $identifier);
 *   if (!$check['allowed']) {
 *     // 429 Too Many Requests
 *   }
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class RoleRateLimiter
{
    public const LIMITS = [
        'admin'      => -1,    // -1 = illimite (pas de check)
        'enseignant' => 500,   // 500 requetes / minute
        'etudiant'   => 60,    // 60 requetes / minute
        'anonyme'    => 30,    // 30 requetes / minute
    ];

    public const WINDOW_SEC = 60;

    private array $limiters = [];

    /**
     * Verifie si une requete est autorisee pour (role, identifier).
     *
     * @param string $role       admin/enseignant/etudiant/anonyme
     * @param string $identifier IP, user ID, session ID
     * @return array{allowed: bool, limit: int, remaining: int, reset_at: int, retry_after: int|null, unlimited?: bool}
     */
    public function check(string $role, string $identifier): array
    {
        $limit = self::LIMITS[$role] ?? self::LIMITS['anonyme'];

        // Admin : illimite
        if ($limit === -1) {
            return [
                'allowed' => true,
                'limit' => -1,
                'remaining' => -1,
                'reset_at' => time() + self::WINDOW_SEC,
                'retry_after' => null,
                'unlimited' => true,
            ];
        }

        // Obtenir le RateLimiter pour ce role
        $rl = $this->getLimiter($role, $limit);

        $remainingBefore = $rl->remaining($identifier);
        $allowed = $rl->attempt($identifier); // Incrémente si OK

        $retryAfter = null;
        if (!$allowed) {
            $retryAfter = $rl->retryAfter($identifier);
        }

        $remaining = $allowed ? max(0, $remainingBefore - 1) : 0;

        return [
            'allowed' => $allowed,
            'limit' => $limit,
            'remaining' => $remaining,
            'reset_at' => time() + ($retryAfter ?? self::WINDOW_SEC),
            'retry_after' => $retryAfter,
        ];
    }

    /**
     * Headers HTTP standards pour rate limiting.
     */
    public function headers(array $check): array
    {
        if (!empty($check['unlimited'])) {
            return ['X-RateLimit-Limit' => 'unlimited'];
        }

        $headers = [
            'X-RateLimit-Limit' => (string) $check['limit'],
            'X-RateLimit-Remaining' => (string) $check['remaining'],
            'X-RateLimit-Reset' => (string) $check['reset_at'],
        ];

        if (!$check['allowed'] && $check['retry_after'] !== null) {
            $headers['Retry-After'] = (string) max(1, $check['retry_after']);
        }

        return $headers;
    }

    /**
     * Reset les buckets d'un identifier (pour tests).
     */
    public function reset(string $role, string $identifier): void
    {
        $limit = self::LIMITS[$role] ?? self::LIMITS['anonyme'];
        if ($limit === -1) return;

        $rl = $this->getLimiter($role, $limit);
        $rl->reset($identifier);
    }

    /**
     * Stats globales pour monitoring.
     */
    public function getStats(): array
    {
        return [
            'limits' => self::LIMITS,
            'window_sec' => self::WINDOW_SEC,
        ];
    }

    // ========================================================================
    // HELPERS PRIVES
    // ========================================================================

    private function getLimiter(string $role, int $limit): RateLimiter
    {
        if (!isset($this->limiters[$role])) {
            // Bucket = "api_" + role (ex: api_enseignant)
            $this->limiters[$role] = new RateLimiter(
                'api_' . $role,
                $limit,
                self::WINDOW_SEC
            );
        }
        return $this->limiters[$role];
    }
}
