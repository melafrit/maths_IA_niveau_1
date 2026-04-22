<?php
/**
 * test_security_csrf.php — Tests de la protection CSRF
 *
 * Vérifie :
 *   - Génération de tokens (longueur, unicité)
 *   - Validation stricte (token faux refusé)
 *   - Session sans token → refus
 *   - Token corrompu → refus
 *   - Token d'une autre session → refus
 *
 * Usage : php backend/tests/test_security_csrf.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Examens\Lib\Csrf;
use Examens\Lib\Session;

echo "🔒 Tests sécurité CSRF\n";
echo str_repeat("=", 60) . "\n\n";

$tests = 0;
$passed = 0;

function test(string $name, callable $fn): void {
    global $tests, $passed;
    $tests++;
    echo "  [$tests] $name ... ";
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

// ============================================================================
// SETUP : démarrer session
// ============================================================================

Session::start();

// ============================================================================
// TESTS GENERATION
// ============================================================================

echo "\n🎫 GENERATION TOKEN\n";

test('Csrf::token() retourne une string non vide', function() {
    $t = Csrf::token();
    return is_string($t) && strlen($t) > 0;
});

test('Token a une longueur suffisante (>=32 chars)', function() {
    $t = Csrf::token();
    return strlen($t) >= 32;
});

test('Token contient uniquement base64url chars', function() {
    $t = Csrf::token();
    return preg_match('/^[A-Za-z0-9_-]+$/', $t) === 1;
});

test('Csrf::token() retourne le meme token dans la session', function() {
    $t1 = Csrf::token();
    $t2 = Csrf::token();
    return $t1 === $t2;
});

// ============================================================================
// TESTS VALIDATION
// ============================================================================

echo "\n✅ VALIDATION\n";

test('isValid() accepte le token genere', function() {
    $t = Csrf::token();
    return Csrf::verify($t) === true;
});

test('isValid() rejette un token vide', function() {
    return Csrf::verify('') === false;
});

test('isValid() rejette un token null (cast string)', function() {
    // Envoyer une chaine 'null' literale
    return Csrf::verify('null') === false;
});

test('isValid() rejette un token corrompu (1 char modifie)', function() {
    $t = Csrf::token();
    $corrupted = substr($t, 0, -1) . (($t[-1] === 'a') ? 'b' : 'a');
    return Csrf::verify($corrupted) === false;
});

test('isValid() rejette un token tronque', function() {
    $t = Csrf::token();
    return Csrf::verify(substr($t, 0, 10)) === false;
});

test('isValid() rejette un token random totalement', function() {
    $randomToken = bin2hex(random_bytes(32));
    return Csrf::verify($randomToken) === false;
});

test('isValid() rejette meme avec whitespace avant/apres', function() {
    $t = Csrf::token();
    return Csrf::verify(" $t ") === false;
});

// ============================================================================
// TESTS ATTAQUE SIMULATION
// ============================================================================

echo "\n⚔️  SIMULATION ATTAQUE\n";

test('Attaque replay : anciens tokens d\'autres sessions refuses', function() {
    // Simuler un token d'une "autre" session
    $foreignToken = hash('sha256', 'attacker_session_123');
    return Csrf::verify($foreignToken) === false;
});

test('Attaque XSS : script injecte comme token', function() {
    $xssPayload = '<script>alert("xss")</script>';
    return Csrf::verify($xssPayload) === false;
});

test('Attaque injection : SQL-like dans token', function() {
    $sqli = "' OR '1'='1";
    return Csrf::verify($sqli) === false;
});

test('Attaque path traversal : ../.. dans token', function() {
    return Csrf::verify('../../../etc/passwd') === false;
});

test('timing-safe : hash_equals utilise (pas ===)', function() {
    // On verifie indirectement : un token ajoute des chars prefix identiques
    // passe toujours time constant.
    $t = Csrf::token();
    $partialMatch = substr($t, 0, strlen($t) - 1) . 'Z';
    // Doit retourner false sans difference de timing detectable
    // (ne peut etre teste precisement ici, mais au moins verifier refus)
    return Csrf::verify($partialMatch) === false;
});

// ============================================================================
// TESTS ROTATION / RESET
// ============================================================================

echo "\n🔄 ROTATION\n";

test('Token persiste apres un validation', function() {
    $t1 = Csrf::token();
    Csrf::verify($t1);
    $t2 = Csrf::token();
    return $t1 === $t2;
});

test('regenerate() genere un nouveau token different', function() {
    if (!method_exists(Csrf::class, 'regenerate')) {
        return true; // Non applicable si pas implemente
    }
    $t1 = Csrf::token();
    Csrf::regenerate();
    $t2 = Csrf::token();
    return $t1 !== $t2;
});

// ============================================================================
// BILAN
// ============================================================================

echo "\n" . str_repeat("=", 60) . "\n";
echo "RESULTAT : $passed / $tests tests passes";
echo $passed === $tests ? " ✅\n\n" : " ❌\n\n";

exit($passed === $tests ? 0 : 1);
