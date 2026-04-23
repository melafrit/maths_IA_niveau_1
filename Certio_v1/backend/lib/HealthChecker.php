<?php
/**
 * HealthChecker.php — Vérifications santé de la plateforme
 *
 * Centralise tous les checks systeme pour le monitoring :
 *   - Disque : espace libre, usage /data/
 *   - Memoire : PHP memory_get_usage
 *   - Filesystem : permissions read/write
 *   - Compteurs : examens, passages, users
 *   - Backups : dernier backup + age
 *   - Logs : taille cumulee
 *   - Performance : duree des checks
 *
 * Chaque check retourne un status : 'ok' | 'warning' | 'error'
 * Le status global est le pire des checks individuels.
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class HealthChecker
{
    private string $dataDir;

    // Seuils configurables (bytes)
    private int $diskWarnBytes;    // Disque libre sous ce seuil = warning
    private int $diskErrorBytes;   // Disque libre sous ce seuil = error
    private int $logsWarnBytes;    // Logs plus gros = warning
    private int $backupMaxAgeSec;  // Dernier backup plus vieux = warning

    public function __construct(?string $dataDir = null)
    {
        $this->dataDir = $dataDir ?? data_path('');
        // Seuils par defaut
        $this->diskWarnBytes   = 500 * 1024 * 1024;    // 500 MB
        $this->diskErrorBytes  = 100 * 1024 * 1024;    // 100 MB
        $this->logsWarnBytes   = 100 * 1024 * 1024;    // 100 MB
        $this->backupMaxAgeSec = 48 * 3600;            // 48h
    }

    /**
     * Lance tous les checks et retourne un rapport consolide.
     *
     * @return array{status: string, timestamp: string, duration_ms: float, checks: array}
     */
    public function checkAll(): array
    {
        $start = microtime(true);

        $checks = [
            'disk'       => $this->checkDisk(),
            'memory'     => $this->checkMemory(),
            'filesystem' => $this->checkFilesystem(),
            'counters'   => $this->checkCounters(),
            'backups'    => $this->checkBackups(),
            'logs'       => $this->checkLogs(),
            'php'        => $this->checkPhp(),
        ];

        // Status global = pire des statuts
        $globalStatus = $this->aggregateStatus(array_column($checks, 'status'));

        return [
            'status'      => $globalStatus,
            'timestamp'   => date('c'),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
            'checks'      => $checks,
        ];
    }

    /**
     * Check disque : espace libre, usage data/
     */
    public function checkDisk(): array
    {
        $free = @disk_free_space($this->dataDir) ?: 0;
        $total = @disk_total_space($this->dataDir) ?: 0;
        $used = $total - $free;
        $usagePct = $total > 0 ? round(($used / $total) * 100, 1) : 0;

        // Taille du dossier data/
        $dataSize = $this->dirSize($this->dataDir);

        $status = 'ok';
        $message = 'Espace disque OK';
        if ($free < $this->diskErrorBytes) {
            $status = 'error';
            $message = 'Disque presque plein (critique)';
        } elseif ($free < $this->diskWarnBytes) {
            $status = 'warning';
            $message = 'Disque faible';
        }

        return [
            'status' => $status,
            'message' => $message,
            'free_bytes' => (int) $free,
            'free_human' => $this->bytesHuman((int) $free),
            'total_bytes' => (int) $total,
            'total_human' => $this->bytesHuman((int) $total),
            'usage_pct' => $usagePct,
            'data_size_bytes' => $dataSize,
            'data_size_human' => $this->bytesHuman($dataSize),
        ];
    }

    /**
     * Check memoire PHP.
     */
    public function checkMemory(): array
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->parseMemoryLimit(ini_get('memory_limit') ?: '128M');

        $usagePct = $limit > 0 ? round(($peak / $limit) * 100, 1) : 0;

        $status = 'ok';
        $message = 'Memoire OK';
        if ($usagePct > 90) {
            $status = 'error';
            $message = 'Memoire quasi saturee';
        } elseif ($usagePct > 75) {
            $status = 'warning';
            $message = 'Memoire elevee';
        }

        return [
            'status' => $status,
            'message' => $message,
            'current_bytes' => $current,
            'current_human' => $this->bytesHuman($current),
            'peak_bytes' => $peak,
            'peak_human' => $this->bytesHuman($peak),
            'limit_bytes' => $limit,
            'limit_human' => $this->bytesHuman($limit),
            'usage_pct' => $usagePct,
        ];
    }

    /**
     * Check filesystem : permissions R/W sur dossiers critiques.
     */
    public function checkFilesystem(): array
    {
        $dirs = [
            'examens'  => data_path('examens'),
            'passages' => data_path('passages'),
            'comptes'  => data_path('comptes'),
            'banque'   => data_path('banque'),
            'logs'     => data_path('logs'),
            'sessions' => data_path('sessions'),
        ];

        $details = [];
        $allOk = true;

        foreach ($dirs as $name => $path) {
            $exists = is_dir($path);
            $readable = $exists && is_readable($path);
            $writable = $exists && is_writable($path);
            $ok = $exists && $readable && $writable;
            $details[$name] = [
                'path' => $path,
                'exists' => $exists,
                'readable' => $readable,
                'writable' => $writable,
                'ok' => $ok,
            ];
            if (!$ok) $allOk = false;
        }

        return [
            'status' => $allOk ? 'ok' : 'error',
            'message' => $allOk ? 'Tous les dossiers accessibles' : 'Probleme d\'acces sur certains dossiers',
            'directories' => $details,
        ];
    }

    /**
     * Compteurs : nb examens, passages, users.
     */
    public function checkCounters(): array
    {
        $counts = [
            'examens_total'    => $this->countFiles(data_path('examens'), '*.json'),
            'passages_total'   => $this->countFiles(data_path('passages'), '*.json'),
            'comptes_total'    => $this->countFiles(data_path('comptes'), '*.json'),
            'sessions_active'  => $this->countFiles(data_path('sessions'), 'sess_*'),
            'backups_total'    => $this->countFiles(data_path('backups'), 'backup_*.tar.gz'),
        ];

        return [
            'status' => 'ok',
            'message' => 'Compteurs collectes',
            'counts' => $counts,
        ];
    }

    /**
     * Etat des backups : dernier backup + age.
     */
    public function checkBackups(): array
    {
        $backupsDir = data_path('backups');
        $backups = glob($backupsDir . '/backup_*.tar.gz') ?: [];

        if (empty($backups)) {
            return [
                'status' => 'warning',
                'message' => 'Aucun backup disponible',
                'total' => 0,
                'last_backup' => null,
                'last_backup_age_sec' => null,
            ];
        }

        // Trier par mtime desc
        usort($backups, fn($a, $b) => filemtime($b) - filemtime($a));
        $latest = $backups[0];
        $age = time() - filemtime($latest);

        $totalSize = 0;
        foreach ($backups as $f) {
            $totalSize += filesize($f) ?: 0;
        }

        $status = 'ok';
        $message = 'Backups a jour';
        if ($age > $this->backupMaxAgeSec * 2) {
            $status = 'error';
            $message = 'Dernier backup tres ancien (>4j)';
        } elseif ($age > $this->backupMaxAgeSec) {
            $status = 'warning';
            $message = 'Dernier backup vieux (>48h)';
        }

        return [
            'status' => $status,
            'message' => $message,
            'total' => count($backups),
            'last_backup' => basename($latest),
            'last_backup_at' => date('c', filemtime($latest)),
            'last_backup_age_sec' => $age,
            'last_backup_age_human' => $this->durationHuman($age),
            'total_size_bytes' => $totalSize,
            'total_size_human' => $this->bytesHuman($totalSize),
        ];
    }

    /**
     * Check logs : taille cumulee.
     */
    public function checkLogs(): array
    {
        $logsDir = data_path('logs');
        $size = $this->dirSize($logsDir);
        $files = glob($logsDir . '/*.log') ?: [];

        $status = 'ok';
        $message = 'Logs OK';
        if ($size > $this->logsWarnBytes * 2) {
            $status = 'warning';
            $message = 'Logs tres volumineux (>200MB)';
        } elseif ($size > $this->logsWarnBytes) {
            $status = 'warning';
            $message = 'Logs volumineux (>100MB)';
        }

        return [
            'status' => $status,
            'message' => $message,
            'size_bytes' => $size,
            'size_human' => $this->bytesHuman($size),
            'files_count' => count($files),
        ];
    }

    /**
     * Info PHP/environnement.
     */
    public function checkPhp(): array
    {
        return [
            'status' => 'ok',
            'message' => 'PHP OK',
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'timezone' => date_default_timezone_get(),
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() !== false,
            'extensions' => [
                'json' => extension_loaded('json'),
                'mbstring' => extension_loaded('mbstring'),
                'openssl' => extension_loaded('openssl'),
                'curl' => extension_loaded('curl'),
                'zip' => extension_loaded('zip'),
            ],
        ];
    }

    // ========================================================================
    // HELPERS PRIVES
    // ========================================================================

    private function aggregateStatus(array $statuses): string
    {
        if (in_array('error', $statuses, true)) return 'error';
        if (in_array('warning', $statuses, true)) return 'warning';
        return 'ok';
    }

    private function countFiles(string $dir, string $pattern): int
    {
        if (!is_dir($dir)) return 0;
        $files = glob($dir . '/' . $pattern) ?: [];
        return count($files);
    }

    private function dirSize(string $dir): int
    {
        if (!is_dir($dir)) return 0;
        $size = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Throwable $e) {
            return 0;
        }
        return $size;
    }

    private function bytesHuman(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . 'B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . 'K';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . 'M';
        return round($bytes / 1073741824, 2) . 'G';
    }

    private function durationHuman(int $seconds): string
    {
        if ($seconds < 60) return $seconds . 's';
        if ($seconds < 3600) return floor($seconds / 60) . 'min';
        if ($seconds < 86400) return floor($seconds / 3600) . 'h';
        return floor($seconds / 86400) . 'j';
    }

    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        if ($limit === '-1') return 0;
        $last = strtolower(substr($limit, -1));
        $num = (int) $limit;
        return match($last) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }
}
