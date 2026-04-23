<?php
/**
 * test_rate_limiter.php — Tests de RoleRateLimiter
 *
 * Usage : php backend/test_rate_limiter.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Examens\Lib\RoleRateLimiter;

echo "🧪 Test RoleRateLimiter\n";
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

$rl = new RoleRateLimiter();

// Identifiers uniques pour eviter collisions avec autres tests
$TEST_PREFIX = 'test_p85_' . substr(md5((string)time() . mt_rand()), 0, 6);

// ============================================================================
// TESTS LIMITES PAR ROLE
// ============================================================================

echo "\n🎯 LIMITES PAR ROLE\n";

test('LIMITS contient admin=-1 (illimite)', function() {
    return RoleRateLimiter::LIMITS['admin'] === -1;
});

test('LIMITS contient enseignant=500', function() {
    return RoleRateLimiter::LIMITS['enseignant'] === 500;
});

test('LIMITS contient etudiant=60', function() {
    return RoleRateLimiter::LIMITS['etudiant'] === 60;
});

test('LIMITS contient anonyme=30', function() {
    return RoleRateLimiter::LIMITS['anonyme'] === 30;
});

test('WINDOW_SEC = 60', function() {
    return RoleRateLimiter::WINDOW_SEC === 60;
});

// ============================================================================
// TESTS CHECK ADMIN (illimité)
// ============================================================================

echo "\n👑 ADMIN (illimite)\n";

test('check() admin : allowed=true toujours', function() use ($rl, $TEST_PREFIX) {
    $check = $rl->check('admin', $TEST_PREFIX . '_admin');
    return $check['allowed'] === true && !empty($check['unlimited']);
});

test('check() admin : limit=-1', function() use ($rl, $TEST_PREFIX) {
    $check = $rl->check('admin', $TEST_PREFIX . '_admin');
    return $check['limit'] === -1;
});

test('100 requetes admin : toutes allowed', function() use ($rl, $TEST_PREFIX) {
    $id = $TEST_PREFIX . '_admin_100';
    $allDone = 0;
    for ($i = 0; $i < 100; $i++) {
        $c = $rl->check('admin', $id);
        if ($c['allowed']) $allDone++;
    }
    return $allDone === 100;
});

// ============================================================================
// TESTS CHECK ENSEIGNANT
// ============================================================================

echo "\n👨‍🏫 ENSEIGNANT (500/min)\n";

test('check() enseignant : premiere requete allowed + limit=500', function() use ($rl, $TEST_PREFIX) {
    $id = $TEST_PREFIX . '_prof_1';
    $rl->reset('enseignant', $id); // Clean state
    $check = $rl->check('enseignant', $id);
    return $check['allowed'] === true
        && $check['limit'] === 500;
});

test('check() enseignant : remaining decroit', function() use ($rl, $TEST_PREFIX) {
    $id = $TEST_PREFIX . '_prof_2';
    $rl->reset('enseignant', $id);

    $c1 = $rl->check('enseignant', $id);
    $c2 = $rl->check('enseignant', $id);

    return $c2['remaining'] < $c1['remaining'];
});

// ============================================================================
// TESTS CHECK ETUDIANT (60/min)
// ============================================================================

echo "\n🎓 ETUDIANT (60/min)\n";

test('check() etudiant : limit=60', function() use ($rl, $TEST_PREFIX) {
    $id = $TEST_PREFIX . '_student_1';
    $rl->reset('etudiant', $id);
    $check = $rl->check('etudiant', $id);
    return $check['limit'] === 60;
});

test('61e requete etudiant : refusee (429)', function() use ($rl, $TEST_PREFIX) {
    $id = $TEST_PREFIX . '_student_limit';
    $rl->reset('etudiant', $id);

    // 60 requetes OK
    for ($i = 0; $i < 60; $i++) {
        $c = $rl->check('etudiant', $id);
        if (!$c['allowed']) return "Bloque trop tot a la requete #$i";
    }
    // 61e : refusee
    $final = $rl->check('etudiant', $id);
    return $final['allowed'] === false
        && $final['retry_after'] !== null
        && $final['retry_after'] > 0;
});

// ============================================================================
// TESTS CHECK ANONYME (30/min)
// ============================================================================

echo "\n👻 ANONYME (30/min)\n";

test('check() anonyme : limit=30', function() use ($rl, $TEST_PREFIX) {
    $id = $TEST_PREFIX . '_anon_1';
    $rl->reset('anonyme', $id);
    $check = $rl->check('anonyme', $id);
    return $check['limit'] === 30;
});

test('31e requete anonyme : refusee', function() use ($rl, $TEST_PREFIX) {
    $id = $TEST_PREFIX . '_anon_limit';
    $rl->reset('anonyme', $id);

    for ($i = 0; $i < 30; $i++) {
        $rl->check('anonyme', $id);
    }
    $final = $rl->check('anonyme', $id);
    return $final['allowed'] === false;
});

// ============================================================================
// TESTS ISOLATION
// ============================================================================

echo "\n🔐 ISOLATION\n";

test('Deux identifiers differents = compteurs separes', function() use ($rl, $TEST_PREFIX) {
    $id1 = $TEST_PREFIX . '_iso_1';
    $id2 = $TEST_PREFIX . '_iso_2';
    $rl->reset('etudiant', $id1);
    $rl->reset('etudiant', $id2);

    // Saturer id1
    for ($i = 0; $i < 60; $i++) $rl->check('etudiant', $id1);

    // id2 doit toujours marcher
    $c2 = $rl->check('etudiant', $id2);
    return $c2['allowed'] === true;
});

test('Meme identifier, roles differents = compteurs separes', function() use ($rl, $TEST_PREFIX) {
    $id = $TEST_PREFIX . '_dual_role';
    $rl->reset('etudiant', $id);
    $rl->reset('enseignant', $id);

    // Saturer etudiant
    for ($i = 0; $i < 60; $i++) $rl->check('etudiant', $id);

    // Enseignant doit marcher
    $cProf = $rl->check('enseignant', $id);
    return $cProf['allowed'] === true;
});

// ============================================================================
// TESTS HEADERS HTTP
// ============================================================================

echo "\n📤 HEADERS HTTP\n";

test('headers() admin : X-RateLimit-Limit=unlimited', function() use ($rl, $TEST_PREFIX) {
    $c = $rl->check('admin', $TEST_PREFIX . '_hdr_admin');
    $h = $rl->headers($c);
    return ($h['X-RateLimit-Limit'] ?? '') === 'unlimited';
});

test('headers() enseignant : 3 headers standard', function() use ($rl, $TEST_PREFIX) {
    $id = $TEST_PREFIX . '_hdr_prof';
    $rl->reset('enseignant', $id);
    $c = $rl->check('enseignant', $id);
    $h = $rl->headers($c);
    return isset($h['X-RateLimit-Limit'])
        && isset($h['X-RateLimit-Remaining'])
        && isset($h['X-RateLimit-Reset']);
});

test('headers() bloque : Retry-After present', function() use ($rl, $TEST_PREFIX) {
    $id = $TEST_PREFIX . '_hdr_429';
    $rl->reset('etudiant', $id);
    for ($i = 0; $i < 61; $i++) $rl->check('etudiant', $id);
    $c = $rl->check('etudiant', $id);
    $h = $rl->headers($c);
    return isset($h['Retry-After']);
});

// ============================================================================
// TESTS RESET / STATS
// ============================================================================

echo "\n♻️  RESET / STATS\n";

test('reset() remet le compteur a zero', function() use ($rl, $TEST_PREFIX) {
    $id = $TEST_PREFIX . '_reset';

    // Saturer
    for ($i = 0; $i < 60; $i++) $rl->check('etudiant', $id);
    $before = $rl->check('etudiant', $id);

    // Reset
    $rl->reset('etudiant', $id);
    $after = $rl->check('etudiant', $id);

    return $before['allowed'] === false
        && $after['allowed'] === true
        && $after['remaining'] === 59;
});

test('getStats() retourne limites + window', function() use ($rl) {
    $stats = $rl->getStats();
    return isset($stats['limits'])
        && isset($stats['window_sec'])
        && $stats['window_sec'] === 60;
});

// ============================================================================
// CLEANUP
// ============================================================================

echo "\n🧹 CLEANUP\n";

test('Cleanup des buckets test', function() use ($rl, $TEST_PREFIX) {
    // Reset tous les identifiers de test
    $ids = [
        ['admin', $TEST_PREFIX . '_admin'],
        ['admin', $TEST_PREFIX . '_admin_100'],
        ['enseignant', $TEST_PREFIX . '_prof_1'],
        ['enseignant', $TEST_PREFIX . '_prof_2'],
        ['enseignant', $TEST_PREFIX . '_hdr_prof'],
        ['enseignant', $TEST_PREFIX . '_dual_role'],
        ['etudiant', $TEST_PREFIX . '_student_1'],
        ['etudiant', $TEST_PREFIX . '_student_limit'],
        ['etudiant', $TEST_PREFIX . '_hdr_429'],
        ['etudiant', $TEST_PREFIX . '_reset'],
        ['etudiant', $TEST_PREFIX . '_iso_1'],
        ['etudiant', $TEST_PREFIX . '_iso_2'],
        ['etudiant', $TEST_PREFIX . '_dual_role'],
        ['anonyme', $TEST_PREFIX . '_anon_1'],
        ['anonyme', $TEST_PREFIX . '_anon_limit'],
        ['anonyme', $TEST_PREFIX . '_hdr_admin'],
    ];
    foreach ($ids as [$role, $id]) {
        $rl->reset($role, $id);
    }
    return true;
});

// ============================================================================
// BILAN
// ============================================================================

echo "\n" . str_repeat("=", 60) . "\n";
echo "RESULTAT : $passed / $tests tests passes";
echo $passed === $tests ? " ✅\n\n" : " ❌\n\n";

exit($passed === $tests ? 0 : 1);
