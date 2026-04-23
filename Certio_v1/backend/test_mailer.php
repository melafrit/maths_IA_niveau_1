<?php
/**
 * test_mailer.php — Tests Mailer + EmailTemplate
 *
 * Usage : php backend/test_mailer.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Examens\Lib\Mailer;
use Examens\Lib\EmailTemplate;

// Definir mode dev pour les tests (sans envoyer de vrais emails)
if (!defined('MAILER_MODE')) define('MAILER_MODE', 'dev');

echo "🧪 Test Mailer + EmailTemplate\n";
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

// ========== TESTS MAILER ==========
echo "\n📬 MAILER\n";

$mailer = new Mailer();
$logPath = $mailer->getLogPath();

// Nettoyer le log pour avoir un test propre
if (file_exists($logPath)) @unlink($logPath);

test('Mailer::getMode() retourne dev', function() use ($mailer) {
    return $mailer->getMode() === 'dev';
});

test('send() avec email invalide → false', function() use ($mailer) {
    $ok = $mailer->send('pas-un-email', 'Test', '<p>Hello</p>');
    return $ok === false;
});

test('send() mode dev : ecrit dans log', function() use ($mailer, $logPath) {
    $ok = $mailer->send('test@test.fr', 'Test Subject', '<p>Hello world</p>');
    return $ok === true && file_exists($logPath);
});

test('readLog() retourne les emails ecrits', function() use ($mailer) {
    $emails = $mailer->readLog(5);
    if (count($emails) === 0) return 'Aucun email dans le log';
    $last = end($emails);
    return $last['subject'] === 'Test Subject'
        && in_array('test@test.fr', $last['to'], true);
});

test('send() avec options.reply_to custom', function() use ($mailer) {
    $ok = $mailer->send('user@test.fr', 'Sujet', '<p>Body</p>', [
        'reply_to' => 'custom@test.fr',
    ]);
    $emails = $mailer->readLog(1);
    $last = end($emails);
    return $ok && $last['reply_to'] === 'custom@test.fr';
});

test('send() a plusieurs destinataires', function() use ($mailer) {
    $ok = $mailer->send(['a@test.fr', 'b@test.fr'], 'Multi', '<p>Hi</p>');
    $emails = $mailer->readLog(1);
    $last = end($emails);
    return $ok && count($last['to']) === 2;
});

test('send() HTML converti en texte brut automatiquement', function() use ($mailer) {
    $ok = $mailer->send('t@test.fr', 'HTML Test',
        '<p>Hello <strong>world</strong></p><br><p>Line 2</p>');
    $emails = $mailer->readLog(1);
    $last = end($emails);
    return $ok && str_contains($last['text_body'], 'Hello')
        && str_contains($last['text_body'], 'world')
        && !str_contains($last['text_body'], '<strong>');
});

test('Mode disabled : send() return true mais rien logged', function() {
    // Simuler mode disabled
    $reflectionClass = new ReflectionClass(Mailer::class);
    $mailerDisabled = $reflectionClass->newInstance();
    $modeProperty = $reflectionClass->getProperty('mode');
    $modeProperty->setAccessible(true);
    $modeProperty->setValue($mailerDisabled, 'disabled');

    return $mailerDisabled->send('test@test.fr', 'Test', '<p>Hi</p>') === true;
});

// ========== TESTS EMAILTEMPLATE ==========
echo "\n📄 EMAILTEMPLATE\n";

$tpl = new EmailTemplate();

test('render() template inconnu → exception', function() use ($tpl) {
    try {
        $tpl->render('fake_template', []);
        return false;
    } catch (\InvalidArgumentException $e) {
        return true;
    }
});

test('render() etudiant_submission retourne subject + html', function() use ($tpl) {
    $result = $tpl->render('etudiant_submission', [
        'studentName' => 'Jean Dupont',
        'examTitle' => 'Contrôle Test',
        'scoreBrut' => 15,
        'scoreMax' => 20,
        'scorePct' => 75.0,
        'durationSec' => 2700,
        'submittedAt' => date('c'),
        'correctionUrl' => 'https://example.com/correction',
    ]);
    return isset($result['subject']) && isset($result['html'])
        && str_contains($result['html'], 'Jean Dupont')
        && str_contains($result['html'], 'Contrôle Test')
        && str_contains($result['html'], '15');
});

test('render() etudiant_submission avec mention correcte', function() use ($tpl) {
    $result = $tpl->render('etudiant_submission', [
        'studentName' => 'Alice',
        'examTitle' => 'Test',
        'scoreBrut' => 18,
        'scoreMax' => 20,
        'scorePct' => 90.0,
        'durationSec' => 1800,
        'submittedAt' => date('c'),
    ]);
    return str_contains($result['html'], 'Excellent'); // >= 90%
});

test('render() etudiant_correction', function() use ($tpl) {
    $result = $tpl->render('etudiant_correction', [
        'studentName' => 'Bob',
        'examTitle' => 'Exam Math',
        'correctionUrl' => 'https://example.com/corr',
    ]);
    return str_contains($result['html'], 'Bob')
        && str_contains($result['html'], 'https://example.com/corr')
        && str_contains($result['subject'], 'correction');
});

test('render() prof_examen_cree avec access code', function() use ($tpl) {
    $result = $tpl->render('prof_examen_cree', [
        'profName' => 'M. Martin',
        'examTitle' => 'DS Maths IA',
        'examId' => 'EXM-ABCD-1234',
        'accessCode' => 'XYZ789',
        'nbQuestions' => 20,
        'dureeSec' => 3600,
        'maxPassages' => 1,
        'dateOuverture' => date('c'),
        'dateCloture' => date('c', strtotime('+2 hours')),
        'adminUrl' => 'https://example.com/admin',
    ]);
    return str_contains($result['html'], 'M. Martin')
        && str_contains($result['html'], 'XYZ789')
        && str_contains($result['html'], 'EXM-ABCD-1234');
});

test('render() prof_premier_passage', function() use ($tpl) {
    $result = $tpl->render('prof_premier_passage', [
        'profName' => 'Mme Dubois',
        'examTitle' => 'Examen',
        'studentName' => 'Alice Test',
        'scoreBrut' => 12,
        'scoreMax' => 15,
        'scorePct' => 80.0,
        'adminUrl' => 'https://example.com',
    ]);
    return str_contains($result['html'], 'Alice Test')
        && str_contains($result['html'], '12/15')
        && str_contains($result['subject'], 'Premier');
});

test('render() prof_cloture avec stats', function() use ($tpl) {
    $result = $tpl->render('prof_cloture', [
        'profName' => 'Prof',
        'examTitle' => 'Examen Final',
        'nbPassages' => 42,
        'avgScorePct' => 72.5,
        'minScorePct' => 25,
        'maxScorePct' => 98,
        'anomaliesCount' => 3,
        'adminUrl' => 'https://example.com',
    ]);
    return str_contains($result['html'], '42')
        && str_contains($result['html'], '72.5')
        && str_contains($result['html'], '3 passage(s) ont été signalés');
});

test('e() echappe bien le HTML', function() {
    $dangerous = '<script>alert("XSS")</script>';
    $escaped = EmailTemplate::e($dangerous);
    return !str_contains($escaped, '<script>')
        && str_contains($escaped, '&lt;script&gt;');
});

// ========== TESTS INTEGRATION ==========
echo "\n🔗 INTEGRATION\n";

test('Envoyer un email via template', function() use ($tpl, $mailer) {
    $rendered = $tpl->render('etudiant_submission', [
        'studentName' => 'Integration Test',
        'examTitle' => 'Test Integration',
        'scoreBrut' => 10,
        'scoreMax' => 15,
        'scorePct' => 66.67,
        'durationSec' => 1200,
        'submittedAt' => date('c'),
    ]);

    $ok = $mailer->send('integration@test.fr', $rendered['subject'], $rendered['html']);
    return $ok === true;
});

test('Template + Mailer : email loggé avec bon contenu', function() use ($mailer) {
    $emails = $mailer->readLog(1);
    $last = end($emails);
    return $last && str_contains($last['html_body'], 'Integration Test')
        && str_contains($last['subject'], 'Test Integration');
});

// Cleanup
echo "\n🧹 CLEANUP\n";
if (file_exists($logPath)) {
    @unlink($logPath);
    echo "  Log emails.log supprime\n";
}

// ========== BILAN ==========
echo "\n" . str_repeat("=", 60) . "\n";
echo "RESULTAT : $passed / $tests tests passes";
echo $passed === $tests ? " ✅\n\n" : " ❌\n\n";

exit($passed === $tests ? 0 : 1);
