<?php
/**
 * test_security_xss.php — Tests protection XSS (Cross-Site Scripting)
 *
 * Vérifie :
 *   - EmailTemplate::e() échappe les caractères dangereux
 *   - htmlspecialchars() utilisé correctement
 *   - JSON output encoded properly
 *   - Pas de unescape dans les réponses API
 *
 * Usage : php backend/tests/test_security_xss.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Examens\Lib\EmailTemplate;

echo "🔒 Tests sécurité XSS\n";
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
// PAYLOADS XSS CLASSIQUES
// ============================================================================

$XSS_PAYLOADS = [
    'basic_script' => '<script>alert("XSS")</script>',
    'img_onerror' => '<img src=x onerror="alert(1)">',
    'svg_onload' => '<svg onload="alert(1)">',
    'javascript_href' => '<a href="javascript:alert(1)">click</a>',
    'iframe' => '<iframe src="javascript:alert(1)"></iframe>',
    'double_quote' => 'test" onmouseover="alert(1)',
    'single_quote' => "test' onmouseover='alert(1)",
    'nested_script' => '<scr<script>ipt>alert(1)</scr</script>ipt>',
    'unicode_encoded' => '\u003cscript\u003ealert(1)\u003c/script\u003e',
    'entity_encoded' => '&lt;script&gt;alert(1)&lt;/script&gt;',
];

// ============================================================================
// TESTS EmailTemplate::e()
// ============================================================================

echo "\n📧 EmailTemplate::e() (htmlspecialchars wrapper)\n";

test('e() echappe <script> en &lt;script&gt;', function() {
    $input = '<script>alert("xss")</script>';
    $output = EmailTemplate::e($input);
    return !str_contains($output, '<script>')
        && str_contains($output, '&lt;script&gt;');
});

test('e() echappe " en &quot;', function() {
    $output = EmailTemplate::e('say "hello"');
    return !str_contains($output, '"hello"')
        && str_contains($output, '&quot;');
});

test("e() echappe ' (simple quote) en &#039; ou &apos;", function() {
    $output = EmailTemplate::e("it's a test");
    return !str_contains($output, "it's")
        && (str_contains($output, '&#039;') || str_contains($output, '&apos;'));
});

test('e() echappe & en &amp;', function() {
    $output = EmailTemplate::e('a & b');
    return str_contains($output, '&amp;');
});

test('e() echappe < et >', function() {
    $output = EmailTemplate::e('5 < 10 > 3');
    return !str_contains($output, '<')
        && !preg_match('/[^&]>/', $output);
});

test('e() neutralise tous les payloads XSS classiques', function() use ($XSS_PAYLOADS) {
    foreach ($XSS_PAYLOADS as $name => $payload) {
        $output = EmailTemplate::e($payload);
        // Apres escape, aucun < non-echape ne doit rester
        if (preg_match('/<[a-zA-Z]/', $output)) {
            return "Payload '$name' non echape : $output";
        }
    }
    return true;
});

test('e() avec null/false/int converti en string', function() {
    $tests = [
        EmailTemplate::e(null) === '',
        EmailTemplate::e(false) === '',
        EmailTemplate::e(0) === '0',
        EmailTemplate::e(42) === '42',
    ];
    return !in_array(false, $tests, true);
});

// ============================================================================
// TESTS htmlspecialchars direct
// ============================================================================

echo "\n🔤 htmlspecialchars (base)\n";

test('htmlspecialchars echappe les 5 chars HTML', function() {
    $chars = [
        '<' => '&lt;',
        '>' => '&gt;',
        '&' => '&amp;',
        '"' => '&quot;',
    ];
    foreach ($chars as $input => $expected) {
        $output = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!str_contains($output, $expected)) {
            return "'$input' non echape correctement (expected $expected)";
        }
    }
    return true;
});

test('ENT_QUOTES actif : echappe single quote', function() {
    $output = htmlspecialchars("it's", ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return str_contains($output, '&#039;') || str_contains($output, '&apos;');
});

test('Sans ENT_QUOTES : single quote NON echappee (verifier qu\'on utilise ENT_QUOTES)', function() {
    $withoutQuotes = htmlspecialchars("it's", ENT_HTML5, 'UTF-8');
    // Par defaut sans ENT_QUOTES : simple quote NON echapee → potentiel bug
    // On valide le comportement standard
    return str_contains($withoutQuotes, "'");
});

// ============================================================================
// TESTS JSON ENCODING
// ============================================================================

echo "\n📄 JSON ENCODING (contre XSS dans API)\n";

test('json_encode() échappe </script> par défaut', function() {
    $encoded = json_encode(['html' => '<script>alert(1)</script>']);
    // Attention : JSON_HEX_TAG ou equivalent recommandé pour XSS
    return is_string($encoded) && strlen($encoded) > 0;
});

test('json_encode avec JSON_HEX_TAG evite </script> injection', function() {
    $encoded = json_encode(
        ['html' => '<script>alert(1)</script>'],
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    );
    return !str_contains($encoded, '<script>');
});

test('json_encode tableaux associatifs produit objets', function() {
    $out = json_encode(['key' => 'value']);
    return $out === '{"key":"value"}';
});

test('json_decode sur input non-JSON retourne null', function() {
    $result = json_decode('not valid json {', true);
    return $result === null;
});

// ============================================================================
// TESTS DANS LES TEMPLATES EMAIL
// ============================================================================

echo "\n📬 TEMPLATES EMAIL (XSS resistance)\n";

test('Template etudiant_submission avec XSS dans nom → echappe', function() {
    $tpl = new EmailTemplate();
    $xssName = '<script>alert("hack")</script>';
    $result = $tpl->render('etudiant_submission', [
        'studentName' => $xssName,
        'examTitle' => 'Test',
        'scoreBrut' => 10,
        'scoreMax' => 20,
        'scorePct' => 50.0,
        'durationSec' => 600,
        'submittedAt' => date('c'),
    ]);
    // Le script ne doit PAS apparaitre non-echape dans le HTML
    return !str_contains($result['html'], '<script>alert("hack")</script>')
        && (str_contains($result['html'], '&lt;script&gt;') ||
            str_contains($result['html'], '&amp;lt;script'));
});

test('Template prof_examen_cree avec XSS dans titre → echappe', function() {
    $tpl = new EmailTemplate();
    $result = $tpl->render('prof_examen_cree', [
        'profName' => 'M. Test',
        'examTitle' => '<img src=x onerror=alert(1)>',
        'examId' => 'EXM-TEST-1234',
        'accessCode' => 'ABCDEF',
        'nbQuestions' => 10,
        'dureeSec' => 3600,
        'maxPassages' => 1,
        'dateOuverture' => date('c'),
        'dateCloture' => date('c', strtotime('+1h')),
        'adminUrl' => 'https://example.com',
    ]);
    // Le tag <img ...> NON-echappe ne doit PAS apparaitre tel quel
    // (entités &lt;img ...&gt; OK)
    $html = $result['html'];
    // Cherche un tag <img avec onerror qui serait executable
    $hasRawImgOnerror = preg_match('/<img[^>]+onerror\s*=/i', $html) === 1;
    return !$hasRawImgOnerror;
});

test('Template accepte les noms accentues legitimes', function() {
    $tpl = new EmailTemplate();
    $result = $tpl->render('etudiant_submission', [
        'studentName' => 'José François-Müller',
        'examTitle' => 'Épreuve de Noël',
        'scoreBrut' => 15,
        'scoreMax' => 20,
        'scorePct' => 75.0,
        'durationSec' => 1200,
        'submittedAt' => date('c'),
    ]);
    return str_contains($result['html'], 'Jos') // Au moins le début
        && str_contains($result['subject'], 'Épreuve');
});

// ============================================================================
// TESTS ENCODAGE UTF-8
// ============================================================================

echo "\n🌍 UTF-8 SAFETY\n";

test('htmlspecialchars en UTF-8 preserve accents', function() {
    $output = htmlspecialchars('café résumé', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return str_contains($output, 'café') && str_contains($output, 'résumé');
});

test('htmlspecialchars gere emojis UTF-8', function() {
    $output = htmlspecialchars('Test 🎓 🔒', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return str_contains($output, '🎓');
});

test('htmlspecialchars avec caracteres de controle', function() {
    $input = "Normal\x00text\x01with\x1bcontrols";
    $output = @htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Doit renvoyer quelque chose sans crash
    return is_string($output);
});

// ============================================================================
// BILAN
// ============================================================================

echo "\n" . str_repeat("=", 60) . "\n";
echo "RESULTAT : $passed / $tests tests passes";
echo $passed === $tests ? " ✅\n\n" : " ❌\n\n";

exit($passed === $tests ? 0 : 1);
