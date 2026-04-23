<?php
/**
 * Bootstrap — Chargement initial du backend
 *
 * Ce fichier est inclus par tous les points d'entrée (index.php, API, scripts CLI).
 * Il initialise :
 *   - Autoloader simple (PSR-4 lite)
 *   - Configuration (chargement de config.php)
 *   - Timezone
 *   - Error handling
 *   - Session (si contexte web)
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

// ============================================================================
// Constantes et racine
// ============================================================================

define('EXAMENS_ROOT', realpath(__DIR__));
define('EXAMENS_START_TIME', microtime(true));

// ============================================================================
// Autoloader PSR-4 simplifié
// Charge les classes depuis backend/lib/ selon le namespace Examens\Lib\
// ============================================================================

spl_autoload_register(function (string $class): void {
    $prefix = 'Examens\\Lib\\';
    $baseDir = EXAMENS_ROOT . '/lib/';

    // Vérifier que le namespace matche
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Convertir le nom de classe en chemin de fichier
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// ============================================================================
// Chargement de la configuration
// ============================================================================

$configFile = EXAMENS_ROOT . '/config.php';

if (!file_exists($configFile)) {
    // En dev : on tolère le config.sample.php
    $sampleFile = EXAMENS_ROOT . '/config.sample.php';
    if (file_exists($sampleFile)) {
        $CONFIG = require $sampleFile;
        error_log('⚠️ Utilisation de config.sample.php — copiez-le en config.php et personnalisez-le');
    } else {
        http_response_code(500);
        die('❌ config.php manquant. Copiez config.sample.php en config.php et configurez-le.');
    }
} else {
    $CONFIG = require $configFile;
}

// Rend la configuration accessible globalement
$GLOBALS['CONFIG'] = $CONFIG;

// ============================================================================
// Timezone
// ============================================================================

date_default_timezone_set($CONFIG['timezone'] ?? 'Europe/Paris');

// ============================================================================
// Error handling
// ============================================================================

if (!empty($CONFIG['debug'])) {
    // Mode développement : afficher les erreurs
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    // Mode production : silence total côté client, logger côté serveur
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ($CONFIG['paths']['logs'] ?? EXAMENS_ROOT . '/../data/logs') . '/php_errors.log');
}

// ============================================================================
// Création des dossiers nécessaires (si manquants)
// ============================================================================

foreach (($CONFIG['paths'] ?? []) as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
}

// ============================================================================
// Configuration de la session (uniquement en contexte web, pas en CLI)
// ============================================================================

if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $sessionPath = $CONFIG['paths']['sessions'] ?? EXAMENS_ROOT . '/../data/sessions';

    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0750, true);
    }

    session_name('EXAMENS_SESSID');
    session_save_path($sessionPath);

    session_set_cookie_params([
        'lifetime' => $CONFIG['security']['session_lifetime'] ?? 7200,
        'path' => '/',
        'domain' => '',
        'secure' => (bool)($CONFIG['security']['session_secure'] ?? false),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    // Le démarrage effectif se fait dans Session::start() pour pouvoir
    // régénérer les IDs sur demande sans conflit.
}

// ============================================================================
// Helpers globaux
// ============================================================================

/**
 * Retourne une valeur de configuration par clé "path.dot.notation".
 */
function config(string $key, $default = null)
{
    $parts = explode('.', $key);
    $value = $GLOBALS['CONFIG'] ?? [];
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/**
 * Retourne le chemin absolu vers un dossier de données.
 */
function data_path(string $subpath = ''): string
{
    $base = config('paths.data', EXAMENS_ROOT . '/../data');
    return $subpath === '' ? $base : rtrim($base, '/') . '/' . ltrim($subpath, '/');
}
