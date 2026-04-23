<?php
/**
 * GET /api/health
 *
 * Endpoint de monitoring. Retourne l'état général de la plateforme.
 * Utilisable par Uptime Robot ou similaire.
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

use Examens\Lib\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::methodNotAllowed();
}

// Vérifications basiques
$checks = [
    'php_version_ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'data_writable'  => is_writable(data_path()),
    'logs_writable'  => is_writable(config('paths.logs', data_path('logs'))),
    'openssl_ok'     => extension_loaded('openssl'),
    'json_ok'        => extension_loaded('json'),
    'mbstring_ok'    => extension_loaded('mbstring'),
];

$allOk = !in_array(false, $checks, true);

Response::json([
    'status'      => $allOk ? 'ok' : 'degraded',
    'version'     => $GLOBALS['CONFIG']['app']['version']
                    ?? $GLOBALS['CONFIG']['version']
                    ?? 'dev',
    'environment' => $GLOBALS['CONFIG']['app']['environment']
                    ?? $GLOBALS['CONFIG']['environment']
                    ?? 'unknown',
    'timestamp'   => date('c'),
    'php_version' => PHP_VERSION,
    'uptime_sec'  => round(microtime(true) - EXAMENS_START_TIME, 4),
    'checks'      => $checks,
], $allOk ? 200 : 503);
