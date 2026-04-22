<?php
/**
 * run_all.php — Harness unifié de tests IPSSI
 *
 * Lance TOUTE la suite de tests backend et affiche un rapport consolidé.
 *
 * Usage :
 *   php backend/tests/run_all.php           # Lance tout
 *   php backend/tests/run_all.php --quick   # Tests rapides uniquement
 *   php backend/tests/run_all.php --security # Tests sécurité uniquement
 *   php backend/tests/run_all.php --filter=banque  # Filtre par nom
 *   php backend/tests/run_all.php --verbose # Affiche détails
 *
 * Codes retour :
 *   0  : tous les tests passés
 *   1  : au moins un test échoué
 *   2  : erreur d'exécution
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

// ============================================================================
// Configuration
// ============================================================================

$BACKEND_DIR = dirname(__DIR__);
$PROJECT_ROOT = dirname($BACKEND_DIR);

// Toutes les suites de tests connues
$ALL_SUITES = [
    // Managers
    'banque_manager'       => ['file' => 'test_banque_manager.php', 'category' => 'unit'],
    'examen_manager'       => ['file' => 'test_examen_manager.php', 'category' => 'unit'],
    'passage_manager'      => ['file' => 'test_passage_manager.php', 'category' => 'unit'],
    'analytics_manager'    => ['file' => 'test_analytics_manager.php', 'category' => 'unit'],
    'mailer'               => ['file' => 'test_mailer.php', 'category' => 'unit'],

    // APIs
    'api_examens'          => ['file' => 'test_api_examens.php', 'category' => 'integration'],
    'api_passages'         => ['file' => 'test_api_passages.php', 'category' => 'integration'],
    'api_corrections'      => ['file' => 'test_api_corrections.php', 'category' => 'integration'],
    'api_analytics'        => ['file' => 'test_api_analytics.php', 'category' => 'integration'],

    // Sécurité (P8.1)
    'security_csrf'        => ['file' => 'tests/test_security_csrf.php', 'category' => 'security'],
    'security_auth'        => ['file' => 'tests/test_security_auth.php', 'category' => 'security'],
    'security_xss'         => ['file' => 'tests/test_security_xss.php', 'category' => 'security'],
    'security_injection'   => ['file' => 'tests/test_security_injection.php', 'category' => 'security'],
];

// ============================================================================
// Parse des arguments CLI
// ============================================================================

$opts = [
    'filter' => null,
    'quick' => false,
    'security_only' => false,
    'verbose' => false,
    'no_color' => false,
];

foreach ($argv as $arg) {
    if ($arg === '--quick') $opts['quick'] = true;
    elseif ($arg === '--security' || $arg === '--security-only') $opts['security_only'] = true;
    elseif ($arg === '--verbose' || $arg === '-v') $opts['verbose'] = true;
    elseif ($arg === '--no-color') $opts['no_color'] = true;
    elseif (str_starts_with($arg, '--filter=')) {
        $opts['filter'] = substr($arg, strlen('--filter='));
    }
    elseif ($arg === '--help' || $arg === '-h') {
        echo "Usage: php backend/tests/run_all.php [OPTIONS]\n\n";
        echo "Options:\n";
        echo "  --quick              Tests rapides uniquement (skip integration + security)\n";
        echo "  --security           Tests securite uniquement\n";
        echo "  --filter=NAME        Filtre par nom de suite (ex: banque)\n";
        echo "  --verbose, -v        Affiche les details de chaque test\n";
        echo "  --no-color           Desactive les couleurs\n";
        echo "  --help, -h           Affiche cette aide\n";
        exit(0);
    }
}

// ============================================================================
// Helpers couleurs
// ============================================================================

function color(string $text, string $color): string {
    global $opts;
    if ($opts['no_color']) return $text;
    $codes = [
        'red' => '31', 'green' => '32', 'yellow' => '33',
        'blue' => '34', 'magenta' => '35', 'cyan' => '36',
        'bold' => '1', 'dim' => '2',
    ];
    $code = $codes[$color] ?? '0';
    return "\033[{$code}m{$text}\033[0m";
}

function ok(string $t): string { return color($t, 'green'); }
function fail(string $t): string { return color($t, 'red'); }
function warn(string $t): string { return color($t, 'yellow'); }
function info(string $t): string { return color($t, 'cyan'); }
function bold(string $t): string { return color($t, 'bold'); }

// ============================================================================
// Filtrage des suites
// ============================================================================

$selectedSuites = $ALL_SUITES;

if ($opts['security_only']) {
    $selectedSuites = array_filter($selectedSuites, fn($s) => $s['category'] === 'security');
}

if ($opts['quick']) {
    $selectedSuites = array_filter($selectedSuites, fn($s) => $s['category'] === 'unit');
}

if ($opts['filter']) {
    $filter = $opts['filter'];
    $selectedSuites = array_filter(
        $selectedSuites,
        fn($s, $k) => str_contains($k, $filter),
        ARRAY_FILTER_USE_BOTH
    );
}

// ============================================================================
// Header
// ============================================================================

echo "\n";
echo color("═══════════════════════════════════════════════════════════════", 'cyan') . "\n";
echo bold("  🧪 IPSSI — Harness de tests unifié P8.1") . "\n";
echo color("═══════════════════════════════════════════════════════════════", 'cyan') . "\n";
echo "  Date     : " . date('Y-m-d H:i:s') . "\n";
echo "  PHP      : " . PHP_VERSION . "\n";
echo "  Suites   : " . count($selectedSuites) . " à exécuter\n";
if ($opts['filter']) echo "  Filter   : '{$opts['filter']}'\n";
if ($opts['security_only']) echo "  Mode     : security only\n";
if ($opts['quick']) echo "  Mode     : quick\n";
echo color("═══════════════════════════════════════════════════════════════", 'cyan') . "\n\n";

// ============================================================================
// Exécution
// ============================================================================

$results = [];
$totalTests = 0;
$totalPassed = 0;
$totalFailed = 0;
$totalDuration = 0.0;
$failedSuites = [];

foreach ($selectedSuites as $name => $config) {
    $file = $BACKEND_DIR . '/' . $config['file'];

    // Vérifier que le fichier existe
    if (!file_exists($file)) {
        echo warn("  ⚠  $name : fichier manquant ($file)") . "\n";
        $results[$name] = ['status' => 'missing', 'passed' => 0, 'total' => 0, 'duration' => 0];
        continue;
    }

    $catLabel = strtoupper($config['category']);
    $catColor = [
        'unit' => 'blue',
        'integration' => 'magenta',
        'security' => 'yellow',
    ][$config['category']] ?? 'cyan';

    echo color("[$catLabel]", $catColor) . " " . bold(str_pad($name, 22));

    $start = microtime(true);

    // Exécuter le test en capturant la sortie
    $output = [];
    $returnCode = 0;
    exec("php " . escapeshellarg($file) . " 2>&1", $output, $returnCode);
    $duration = microtime(true) - $start;

    $outputStr = implode("\n", $output);

    // Parser le résultat
    $passed = 0;
    $total = 0;
    if (preg_match('/RESULTAT\s*:\s*(\d+)\s*\/\s*(\d+)/u', $outputStr, $m) ||
        preg_match('/RÉSULTAT\s*:\s*(\d+)\s*\/\s*(\d+)/u', $outputStr, $m)) {
        $passed = (int) $m[1];
        $total = (int) $m[2];
    }

    $status = ($returnCode === 0 && $total > 0 && $passed === $total) ? 'pass' : 'fail';

    // Affichage
    if ($status === 'pass') {
        echo "  " . ok("✅ PASS") . "  " . bold("$passed/$total")
            . color(sprintf(" (%.2fs)", $duration), 'dim') . "\n";
        $totalPassed += $passed;
    } else {
        echo "  " . fail("❌ FAIL") . "  " . bold("$passed/$total")
            . color(sprintf(" (%.2fs)", $duration), 'dim') . "\n";
        $failedSuites[] = $name;

        // Afficher les lignes d'erreur si verbose
        if ($opts['verbose']) {
            $errorLines = array_filter($output, fn($l) => str_contains($l, '❌') || str_contains($l, 'FAIL'));
            foreach (array_slice($errorLines, 0, 10) as $line) {
                echo "      " . color(trim($line), 'red') . "\n";
            }
        }
    }

    $results[$name] = [
        'status' => $status,
        'passed' => $passed,
        'total' => $total,
        'duration' => $duration,
        'category' => $config['category'],
    ];

    $totalTests += $total;
    $totalFailed += ($total - $passed);
    $totalDuration += $duration;
}

// ============================================================================
// Rapport final
// ============================================================================

echo "\n";
echo color("═══════════════════════════════════════════════════════════════", 'cyan') . "\n";
echo bold("  📊 RAPPORT FINAL") . "\n";
echo color("═══════════════════════════════════════════════════════════════", 'cyan') . "\n\n";

// Par catégorie
$byCategory = [];
foreach ($results as $name => $r) {
    $cat = $r['category'] ?? 'unknown';
    if (!isset($byCategory[$cat])) {
        $byCategory[$cat] = ['passed' => 0, 'total' => 0, 'suites' => 0, 'failures' => 0];
    }
    $byCategory[$cat]['passed'] += $r['passed'];
    $byCategory[$cat]['total'] += $r['total'];
    $byCategory[$cat]['suites'] += 1;
    if ($r['status'] === 'fail') $byCategory[$cat]['failures'] += 1;
}

echo bold("  Par catégorie :\n");
foreach ($byCategory as $cat => $stats) {
    $label = str_pad(strtoupper($cat), 15);
    $line = sprintf(
        "    %s  %d suites · %s tests",
        $label,
        $stats['suites'],
        $stats['total'] > 0
            ? ok("{$stats['passed']}/{$stats['total']} ✓")
            : warn("0/0")
    );
    if ($stats['failures'] > 0) {
        $line .= "  " . fail("⚠ {$stats['failures']} failure(s)");
    }
    echo $line . "\n";
}

// Total
$passRate = $totalTests > 0 ? ($totalPassed / $totalTests) * 100 : 0;
echo "\n";
echo bold("  Total :") . "\n";
echo "    Suites exécutées  : " . count($results) . "\n";
echo "    Tests exécutés    : " . bold(strval($totalTests)) . "\n";

if ($totalPassed === $totalTests && $totalTests > 0) {
    echo "    Résultat          : " . ok("✅ $totalPassed/$totalTests (100%)") . "\n";
} else {
    echo "    Résultat          : " . fail("⚠ $totalPassed/$totalTests") . " (" . sprintf('%.1f', $passRate) . "%)\n";
    echo "    Échecs            : " . fail(strval($totalFailed)) . "\n";
}

echo "    Durée totale      : " . sprintf("%.2fs", $totalDuration) . "\n";

if (!empty($failedSuites)) {
    echo "\n" . fail(bold("  ❌ Suites échouées :")) . "\n";
    foreach ($failedSuites as $s) {
        echo "    - $s\n";
    }
    echo "\n" . warn("  💡 Relancer avec --verbose pour voir les détails.") . "\n";
}

echo "\n" . color("═══════════════════════════════════════════════════════════════", 'cyan') . "\n";

// Code de sortie
if (!empty($failedSuites)) {
    exit(1);
}
exit(0);
