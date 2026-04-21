<?php
/**
 * RateLimiter — Limitation de débit basée sur fichier
 *
 * Implémentation simple d'un rate limiter sans Redis ni base de données,
 * adapté à OVH mutualisé.
 *
 * Stratégie : sliding window sur fichier JSON par clé (ex: par IP).
 * Stocké dans data/_ratelimit/<bucket>.json.
 *
 * Usage :
 *   $rl = new RateLimiter('login', 5, 900);  // 5 tentatives / 15 min
 *   if (!$rl->attempt($_SERVER['REMOTE_ADDR'])) {
 *     Response::rateLimited("Trop de tentatives, réessayez plus tard.");
 *   }
 *   // Login OK :
 *   $rl->reset($_SERVER['REMOTE_ADDR']);
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class RateLimiter
{
    private string $bucket;
    private int $maxAttempts;
    private int $windowSec;
    private FileStorage $storage;
    private string $storageDir;

    /**
     * @param string $bucket    Nom du bucket (ex: 'login', 'api_ia', 'submit')
     * @param int    $maxAttempts Nombre max d'attempts dans la fenêtre
     * @param int    $windowSec   Durée de la fenêtre en secondes
     */
    public function __construct(
        string $bucket,
        int $maxAttempts = 5,
        int $windowSec = 900
    ) {
        $this->bucket = preg_replace('/[^a-z0-9_-]/i', '', $bucket);
        $this->maxAttempts = $maxAttempts;
        $this->windowSec = $windowSec;
        $this->storage = new FileStorage(new Logger('ratelimit'));
        $this->storageDir = data_path('_ratelimit');

        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0750, true);
        }
    }

    /**
     * Tente une action. Retourne true si autorisée, false si bloquée.
     * Incrémente le compteur si autorisée.
     */
    public function attempt(string $key): bool
    {
        $remaining = $this->remaining($key);
        if ($remaining <= 0) {
            return false;
        }
        $this->record($key);
        return true;
    }

    /**
     * Enregistre une tentative (sans vérifier la limite).
     * Utile pour comptabiliser un échec après vérification.
     */
    public function record(string $key): void
    {
        $path = $this->pathFor($key);
        $this->storage->update($path, function (array $data): array {
            $now = time();
            $cutoff = $now - $this->windowSec;

            $attempts = $data['attempts'] ?? [];
            $attempts = array_filter($attempts, fn($t) => $t > $cutoff);
            $attempts[] = $now;

            return [
                'attempts' => array_values($attempts),
                'updated_at' => $now,
            ];
        });
    }

    /**
     * Retourne le nombre de tentatives restantes dans la fenêtre.
     */
    public function remaining(string $key): int
    {
        $count = $this->countRecent($key);
        return max(0, $this->maxAttempts - $count);
    }

    /**
     * Retourne le nombre de secondes avant le déblocage si bloqué, sinon 0.
     */
    public function retryAfter(string $key): int
    {
        $path = $this->pathFor($key);
        $data = $this->storage->read($path);

        if (!is_array($data) || empty($data['attempts'])) {
            return 0;
        }

        $now = time();
        $cutoff = $now - $this->windowSec;
        $attempts = array_filter($data['attempts'], fn($t) => $t > $cutoff);

        if (count($attempts) < $this->maxAttempts) {
            return 0;
        }

        // Quand est-ce que la plus ancienne sortira de la fenêtre ?
        $oldest = min($attempts);
        $unlockAt = $oldest + $this->windowSec;
        return max(0, $unlockAt - $now);
    }

    /**
     * Vérifie si une clé est actuellement bloquée.
     */
    public function isBlocked(string $key): bool
    {
        return $this->remaining($key) <= 0;
    }

    /**
     * Réinitialise complètement le compteur pour une clé (à appeler après succès).
     */
    public function reset(string $key): void
    {
        $path = $this->pathFor($key);
        $this->storage->delete($path);
    }

    /**
     * Nettoie les fichiers de rate limit expirés (à lancer en cron).
     */
    public function cleanup(): int
    {
        $deleted = 0;
        $files = $this->storage->glob($this->storageDir . '/*.json');
        foreach ($files as $f) {
            $data = $this->storage->read($f);
            $updated = $data['updated_at'] ?? 0;
            // Supprimer les fichiers inactifs depuis > 24h
            if (($updated + max(86400, $this->windowSec)) < time()) {
                if ($this->storage->delete($f)) {
                    $deleted++;
                }
            }
        }
        return $deleted;
    }

    // ========================================================================
    // INTERNES
    // ========================================================================

    private function countRecent(string $key): int
    {
        $path = $this->pathFor($key);
        $data = $this->storage->read($path);

        if (!is_array($data) || empty($data['attempts'])) {
            return 0;
        }

        $cutoff = time() - $this->windowSec;
        $recent = array_filter($data['attempts'], fn($t) => $t > $cutoff);
        return count($recent);
    }

    private function pathFor(string $key): string
    {
        // Hash la clé pour éviter problèmes de noms de fichiers (IPv6, emails...)
        $safeKey = hash('sha256', $key);
        return $this->storageDir . '/' . $this->bucket . '_' . substr($safeKey, 0, 16) . '.json';
    }
}
