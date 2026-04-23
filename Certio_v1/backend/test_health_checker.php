<?php
/**
 * test_health_checker.php — Tests de HealthChecker
 *
 * Usage : php backend/test_health_checker.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Examens\Lib\HealthChecker;

echo "🧪 Test HealthChecker\n";
echo str_repeat("=", 60) . "\n\n";

$tests = 0;
$passed = 0;

function test(string $name, callable $fn): void {
    global $tests, $passed;
    $tests++;
    echo "  [" . str_pad((string)$tests, 2, ' ', STR_PAD_LEFT) . "] $name ... ";
    try {
        $result = $fn();
        if ($result === true) {
            echo "✅\n";
            $passed++;
        } else {
            echo "❌ " . (is_string($result) ? $result : var_export($result, true)) . "\n";
        }
    } catch (\Throwable $e) {
        echo "❌ EXCEPTION : " . $e->getMessage() . "\n";
    }
}

$hc = new HealthChecker();

// ============================================================================
// TESTS CHECK-ALL
// ============================================================================

echo "\n🩺 CHECK ALL\n";

test('checkAll() retourne structure complete', function() use ($hc) {
    $report = $hc->checkAll();
    $requiredKeys = ['status', 'timestamp', 'duration_ms', 'checks'];
    foreach ($requiredKeys as $k) {
        if (!array_key_exists($k, $report)) return "Missing key : $k";
    }
    return true;
});

test('checkAll() status est ok, warning ou error', function() use ($hc) {
    $report = $hc->checkAll();
    return in_array($report['status'], ['ok', 'warning', 'error'], true);
});

test('checkAll() inclut tous les checks attendus', function() use ($hc) {
    $report = $hc->checkAll();
    $expected = ['disk', 'memory', 'filesystem', 'counters', 'backups', 'logs', 'php'];
    foreach ($expected as $check) {
        if (!isset($report['checks'][$check])) return "Missing check : $check";
    }
    return true;
});

test('checkAll() duration_ms est positif et raisonnable (<10s)', function() use ($hc) {
    $report = $hc->checkAll();
    return $report['duration_ms'] >= 0 && $report['duration_ms'] < 10000;
});

test('checkAll() timestamp est ISO 8601', function() use ($hc) {
    $report = $hc->checkAll();
    return preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $report['timestamp']) === 1;
});

// ============================================================================
// TESTS CHECKS INDIVIDUELS
// ============================================================================

echo "\n💾 DISK\n";

test('checkDisk() retourne free/total/usage_pct', function() use ($hc) {
    $r = $hc->checkDisk();
    return isset($r['free_bytes'])
        && isset($r['total_bytes'])
        && isset($r['usage_pct'])
        && isset($r['free_human']);
});

test('checkDisk() free <= total', function() use ($hc) {
    $r = $hc->checkDisk();
    return $r['free_bytes'] <= $r['total_bytes'];
});

test('checkDisk() usage_pct entre 0 et 100', function() use ($hc) {
    $r = $hc->checkDisk();
    return $r['usage_pct'] >= 0 && $r['usage_pct'] <= 100;
});

echo "\n🧠 MEMORY\n";

test('checkMemory() retourne current/peak/limit', function() use ($hc) {
    $r = $hc->checkMemory();
    return isset($r['current_bytes'])
        && isset($r['peak_bytes'])
        && isset($r['limit_bytes']);
});

test('checkMemory() peak >= current', function() use ($hc) {
    $r = $hc->checkMemory();
    return $r['peak_bytes'] >= $r['current_bytes'];
});

test('checkMemory() human formats presents', function() use ($hc) {
    $r = $hc->checkMemory();
    return isset($r['current_human'])
        && isset($r['peak_human'])
        && isset($r['limit_human']);
});

echo "\n📁 FILESYSTEM\n";

test('checkFilesystem() retourne directories detail', function() use ($hc) {
    $r = $hc->checkFilesystem();
    return isset($r['directories'])
        && is_array($r['directories'])
        && count($r['directories']) > 0;
});

test('checkFilesystem() chaque dir a exists/readable/writable/ok', function() use ($hc) {
    $r = $hc->checkFilesystem();
    foreach ($r['directories'] as $name => $info) {
        $keys = ['exists', 'readable', 'writable', 'ok'];
        foreach ($keys as $k) {
            if (!array_key_exists($k, $info)) return "Dir $name missing key : $k";
        }
    }
    return true;
});

echo "\n📊 COUNTERS\n";

test('checkCounters() retourne compteurs standards', function() use ($hc) {
    $r = $hc->checkCounters();
    $expectedKeys = ['examens_total', 'passages_total', 'comptes_total', 'backups_total'];
    foreach ($expectedKeys as $k) {
        if (!isset($r['counts'][$k])) return "Missing counter : $k";
    }
    return true;
});

test('checkCounters() tous compteurs sont des int >= 0', function() use ($hc) {
    $r = $hc->checkCounters();
    foreach ($r['counts'] as $name => $value) {
        if (!is_int($value) || $value < 0) return "Counter $name invalid : " . var_export($value, true);
    }
    return true;
});

echo "\n💾 BACKUPS\n";

test('checkBackups() retourne total et status', function() use ($hc) {
    $r = $hc->checkBackups();
    return isset($r['total'])
        && isset($r['status'])
        && isset($r['message']);
});

test('checkBackups() : si backups presents, last_backup non null', function() use ($hc) {
    $r = $hc->checkBackups();
    if ($r['total'] === 0) return true; // Pas de backups, OK
    return $r['last_backup'] !== null
        && $r['last_backup_age_sec'] !== null;
});

echo "\n📝 LOGS\n";

test('checkLogs() retourne size et files_count', function() use ($hc) {
    $r = $hc->checkLogs();
    return isset($r['size_bytes'])
        && isset($r['files_count'])
        && isset($r['size_human']);
});

echo "\n🐘 PHP\n";

test('checkPhp() retourne version et extensions', function() use ($hc) {
    $r = $hc->checkPhp();
    return isset($r['version'])
        && isset($r['extensions'])
        && isset($r['extensions']['json'])
        && $r['extensions']['json'] === true;
});

test('checkPhp() status toujours ok', function() use ($hc) {
    $r = $hc->checkPhp();
    return $r['status'] === 'ok';
});

// ============================================================================
// TESTS AGGREGATION
// ============================================================================

echo "\n🧮 AGGREGATION\n";

test('Status global prend le pire individuellement', function() use ($hc) {
    $report = $hc->checkAll();
    $individualStatuses = array_column($report['checks'], 'status');
    if (in_array('error', $individualStatuses, true)) {
        return $report['status'] === 'error';
    }
    if (in_array('warning', $individualStatuses, true)) {
        return $report['status'] === 'warning';
    }
    return $report['status'] === 'ok';
});

// ============================================================================
// BILAN
// ============================================================================

echo "\n" . str_repeat("=", 60) . "\n";
echo "RESULTAT : $passed / $tests tests passes";
echo $passed === $tests ? " ✅\n\n" : " ❌\n\n";

exit($passed === $tests ? 0 : 1);
