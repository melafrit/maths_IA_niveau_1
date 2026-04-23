<?php
/**
 * BackupManager.php — Gestion des backups depuis PHP
 *
 * Expose une API pour :
 *   - Lister les backups disponibles
 *   - Declencher un nouveau backup (via script bash)
 *   - Verifier l'integrite (hash SHA-256)
 *   - Supprimer un backup
 *   - Statistiques (taille totale, nb backups, plus ancien, plus recent)
 *
 * Tous les backups sont stockes dans data/backups/backup_*.tar.gz avec
 * leur hash dans backup_*.tar.gz.sha256.
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class BackupManager
{
    private string $backupsDir;
    private string $scriptsDir;
    private Logger $logger;

    public function __construct(?string $backupsDir = null, ?string $scriptsDir = null, ?Logger $logger = null)
    {
        $this->backupsDir = $backupsDir ?? data_path('backups');
        $this->scriptsDir = $scriptsDir ?? dirname(data_path('backups'), 2) . '/scripts';
        $this->logger = $logger ?? new Logger();

        if (!is_dir($this->backupsDir)) {
            @mkdir($this->backupsDir, 0755, true);
        }
    }

    /**
     * Liste tous les backups disponibles (plus recents en premier).
     *
     * @return array<array{id: string, filename: string, path: string, size: int, size_human: string, created_at: string, hash: string|null, verified: bool}>
     */
    public function list(): array
    {
        if (!is_dir($this->backupsDir)) {
            return [];
        }

        $pattern = $this->backupsDir . '/backup_*.tar.gz';
        $files = glob($pattern) ?: [];

        // Trier par date decroissante (plus recent d'abord)
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        $result = [];
        foreach ($files as $path) {
            $result[] = $this->buildBackupInfo($path);
        }

        return $result;
    }

    /**
     * Recupere les infos d'un backup par son ID (nom sans extension).
     */
    public function get(string $id): ?array
    {
        $safeId = $this->sanitizeId($id);
        if ($safeId === null) {
            return null;
        }

        $path = $this->backupsDir . '/' . $safeId . '.tar.gz';
        if (!file_exists($path)) {
            return null;
        }

        return $this->buildBackupInfo($path);
    }

    /**
     * Declenche un backup synchrone via le script bash.
     *
     * @param int $keep Nombre de backups a garder (rotation)
     * @return array{success: bool, output: string, backup_id: string|null, duration_sec: float}
     */
    public function createBackup(int $keep = 14): array
    {
        $script = $this->scriptsDir . '/backup.sh';
        if (!file_exists($script)) {
            $this->logger->error("BackupManager: script introuvable : $script");
            return [
                'success' => false,
                'output' => "Script introuvable : $script",
                'backup_id' => null,
                'duration_sec' => 0.0,
            ];
        }

        if (!is_executable($script)) {
            @chmod($script, 0755);
        }

        // Enregistrer les backups existants avant pour trouver le nouveau
        $beforeIds = array_map(
            fn($b) => $b['id'],
            $this->list()
        );

        $start = microtime(true);
        $cmd = escapeshellcmd($script) . ' --keep=' . (int) $keep . ' --quiet 2>&1';
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);
        $duration = microtime(true) - $start;

        $outputStr = implode("\n", $output);

        // Identifier le nouveau backup
        $afterIds = array_map(fn($b) => $b['id'], $this->list());
        $newIds = array_diff($afterIds, $beforeIds);
        $newBackupId = array_values($newIds)[0] ?? null;

        $success = $returnCode === 0 && $newBackupId !== null;

        $this->logger->info(sprintf(
            'BackupManager::createBackup: %s (duree=%.2fs, id=%s)',
            $success ? 'OK' : 'FAIL',
            $duration,
            $newBackupId ?? 'null'
        ));

        return [
            'success' => $success,
            'output' => $outputStr,
            'backup_id' => $newBackupId,
            'duration_sec' => round($duration, 2),
        ];
    }

    /**
     * Verifie l'integrite d'un backup (hash SHA-256).
     */
    public function verify(string $id): array
    {
        $info = $this->get($id);
        if ($info === null) {
            return ['valid' => false, 'error' => 'Backup introuvable'];
        }

        if ($info['hash'] === null) {
            return ['valid' => false, 'error' => 'Pas de hash stocke'];
        }

        $currentHash = hash_file('sha256', $info['path']);
        if ($currentHash === false) {
            return ['valid' => false, 'error' => 'Impossible de calculer le hash'];
        }

        $valid = hash_equals($info['hash'], $currentHash);

        return [
            'valid' => $valid,
            'expected' => $info['hash'],
            'actual' => $currentHash,
        ];
    }

    /**
     * Supprime un backup (archive + fichier hash).
     */
    public function delete(string $id): bool
    {
        $info = $this->get($id);
        if ($info === null) {
            return false;
        }

        $ok = true;
        if (file_exists($info['path'])) {
            $ok = @unlink($info['path']) && $ok;
        }
        $hashPath = $info['path'] . '.sha256';
        if (file_exists($hashPath)) {
            $ok = @unlink($hashPath) && $ok;
        }

        $this->logger->info("BackupManager::delete: $id " . ($ok ? 'OK' : 'FAIL'));

        return $ok;
    }

    /**
     * Statistiques globales sur les backups.
     */
    public function getStats(): array
    {
        $backups = $this->list();

        if (empty($backups)) {
            return [
                'total_backups' => 0,
                'total_size_bytes' => 0,
                'total_size_human' => '0B',
                'oldest' => null,
                'newest' => null,
            ];
        }

        $totalSize = array_sum(array_column($backups, 'size'));

        return [
            'total_backups' => count($backups),
            'total_size_bytes' => $totalSize,
            'total_size_human' => $this->bytesHuman($totalSize),
            'oldest' => end($backups)['created_at'] ?? null,
            'newest' => $backups[0]['created_at'] ?? null,
        ];
    }

    // ========================================================================
    // HELPERS PRIVES
    // ========================================================================

    private function buildBackupInfo(string $path): array
    {
        $basename = basename($path);
        $id = preg_replace('/\.tar\.gz$/', '', $basename);

        $hashPath = $path . '.sha256';
        $hash = null;
        if (file_exists($hashPath)) {
            $content = @file_get_contents($hashPath);
            if ($content !== false) {
                $hash = trim(explode(' ', $content, 2)[0] ?? '');
            }
        }

        $size = filesize($path) ?: 0;
        $mtime = filemtime($path) ?: 0;

        return [
            'id' => $id,
            'filename' => $basename,
            'path' => $path,
            'size' => $size,
            'size_human' => $this->bytesHuman($size),
            'created_at' => date('c', $mtime),
            'hash' => $hash,
            'verified' => $hash !== null, // Presume verified (verify() pour recalculer)
        ];
    }

    private function sanitizeId(string $id): ?string
    {
        // Format attendu : backup_YYYY-MM-DD_HHmmss ou safety_before_restore_YYYY-MM-DD_HHmmss
        if (preg_match('/^(backup|safety_before_restore)_\d{4}-\d{2}-\d{2}_\d{6}$/', $id) !== 1) {
            return null;
        }
        return $id;
    }

    private function bytesHuman(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . 'B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . 'K';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . 'M';
        return round($bytes / 1073741824, 2) . 'G';
    }
}
