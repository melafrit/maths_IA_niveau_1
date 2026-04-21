<?php
/**
 * Logger — Logger applicatif simple
 *
 * Écrit des logs au format JSONL (une entrée JSON par ligne).
 * Supporte les niveaux : debug, info, warning, error.
 * Rotation automatique par date (un fichier par jour).
 *
 * Exemple d'usage :
 *   $logger = new Logger('auth');
 *   $logger->info('Login réussi', ['email' => 'user@example.com']);
 *   $logger->error('Échec connexion', ['reason' => 'invalid_password']);
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class Logger
{
    public const DEBUG = 'debug';
    public const INFO = 'info';
    public const WARNING = 'warning';
    public const ERROR = 'error';

    private const LEVEL_PRIORITIES = [
        self::DEBUG => 0,
        self::INFO => 1,
        self::WARNING => 2,
        self::ERROR => 3,
    ];

    private string $channel;
    private string $logDir;
    private string $minLevel;

    public function __construct(string $channel = 'app')
    {
        $this->channel = $channel;
        $this->logDir = config('paths.logs', EXAMENS_ROOT . '/../data/logs');
        $this->minLevel = config('logs.level', 'info');

        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0750, true);
        }
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Écrit une entrée de log au format JSONL.
     */
    public function log(string $level, string $message, array $context = []): void
    {
        // Filtrer selon le niveau minimum
        $minPriority = self::LEVEL_PRIORITIES[$this->minLevel] ?? 1;
        $currentPriority = self::LEVEL_PRIORITIES[$level] ?? 1;
        if ($currentPriority < $minPriority) {
            return;
        }

        $entry = [
            'ts' => date('c'),
            'level' => $level,
            'channel' => $this->channel,
            'message' => $message,
            'context' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        // Un fichier par jour : auth_2026-04-21.log
        $filename = sprintf(
            '%s/%s_%s.log',
            $this->logDir,
            $this->channel,
            date('Y-m-d')
        );

        // Écriture JSONL avec verrouillage
        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        @file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Lit les N dernières entrées du log.
     *
     * @return array<array> Entrées décodées
     */
    public function tail(int $n = 100): array
    {
        $filename = sprintf(
            '%s/%s_%s.log',
            $this->logDir,
            $this->channel,
            date('Y-m-d')
        );

        if (!file_exists($filename)) {
            return [];
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $lines = array_slice($lines, -$n);
        return array_map(static function ($line) {
            $decoded = json_decode($line, true);
            return is_array($decoded) ? $decoded : ['raw' => $line];
        }, $lines);
    }
}
