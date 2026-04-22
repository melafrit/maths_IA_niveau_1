<?php
/**
 * test_api_passages.php — Tests intégration scenarios complets
 *
 * Scenarios testes :
 *   1. Etudiant demarre un passage
 *   2. Etudiant repond a des questions
 *   3. Etudiant log focus event
 *   4. Etudiant soumet
 *   5. Verification de signature
 *   6. Filtrage prof vs admin
 *   7. Invalidation prof
 *
 * Usage : php backend/test_api_passages.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Examens\Lib\ExamenManager;
use Examens\Lib\PassageManager;

echo "🧪 Test API Passages (scenarios)\n";
echo str_repeat("=", 60) . "\n\n";

$em = new ExamenManager();
$pm = new PassageManager();

$tests = 0;
$passed = 0;
$createdExams = [];
$createdPassages = [];

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

// ========== SETUP : examen publie ==========
echo "\n🏗️  SETUP\n";

$examData = [
    'titre' => 'Examen API Passages Test',
    'description' => 'Test workflow complet',
    'questions' => ['vec-faci-01', 'vec-faci-02', 'mat-faci-01', 'vec-moye-01'],
    'duree_sec' => 3600,
    'date_ouverture' => date('c', strtotime('-30 min')),
    'date_cloture' => date('c', strtotime('+2 hours')),
    'max_passages' => 1,
    'shuffle_questions' => true,
    'shuffle_options' => true,
    'show_correction_after' => true,
    'correction_delay_min' => 0,
];

$profId = 'PROF-APIP-TEST';
$adminId = 'ADMN-APIP-TEST';

$exam = $em->create($examData, $profId);
$createdExams[] = $exam['id'];
$exam = $em->publish($exam['id']);
echo "  Examen publie : {$exam['id']} (4 questions, 1h)\n";

$studentInfo = [
    'nom' => 'TestStudent',
    'prenom' => 'Alice',
    'email' => 'alice@test.fr',
];

$savedToken = null;
$savedPassageId = null;

// ========== ROUTE 1 : POST /api/passages/start ==========
echo "\n🚀 ROUTE 1 : START\n";

test('Demarrage valide : retourne token UUID + questions shuffled', function() use ($pm, $exam, $studentInfo, &$savedToken, &$savedPassageId, &$createdPassages) {
    $passage = $pm->start($exam['id'], $studentInfo);
    $savedToken = $passage['token'];
    $savedPassageId = $passage['id'];
    $createdPassages[] = $passage['id'];

    return isset($passage['token'])
        && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4/', $passage['token'])
        && count($passage['question_order']) === 4;
});

test('Demarrage sur examen draft → exception not_available', function() use ($pm, $em, $examData, $studentInfo, &$createdExams) {
    $draft = $em->create($examData, 'PROF-DRAFT');
    $createdExams[] = $draft['id'];
    try {
        $pm->start($draft['id'], ['nom' => 'X', 'prenom' => 'X', 'email' => 'xx@test.fr']);
        return false;
    } catch (\RuntimeException $e) {
        return strpos($e->getMessage(), 'non disponible') !== false;
    }
});

test('Max_passages=1 : deuxieme start → exception', function() use ($pm, $exam, $studentInfo) {
    try {
        $pm->start($exam['id'], $studentInfo);
        return false;
    } catch (\RuntimeException $e) {
        return strpos($e->getMessage(), 'maximal') !== false;
    }
});

// ========== ROUTE 2 : GET /api/passages/{token}/progress ==========
echo "\n📊 ROUTE 2 : PROGRESS\n";

test('progress() retourne toutes les questions avec shuffle', function() use ($pm, &$savedToken) {
    $passage = $pm->getByToken($savedToken);
    return $passage !== null
        && count($passage['question_order']) === 4
        && count($passage['option_shuffle_maps']) === 4;
});

test('progress() avec token invalide retourne null', function() use ($pm) {
    $invalid = $pm->getByToken('00000000-0000-4000-8000-000000000000');
    return $invalid === null;
});

test('isExpired() verifie le temps restant', function() use ($pm, &$savedToken) {
    $passage = $pm->getByToken($savedToken);
    return $pm->isExpired($passage) === false;
});

// ========== ROUTE 3 : POST /api/passages/{token}/answer ==========
echo "\n💾 ROUTE 3 : ANSWER\n";

test('Sauvegarde reponse question 1', function() use ($pm, &$savedToken) {
    $passage = $pm->getByToken($savedToken);
    $firstQ = $passage['question_order'][0];
    $updated = $pm->saveAnswer($savedToken, $firstQ, 2);
    return isset($updated['answers'][$firstQ]);
});

test('Sauvegarde reponse question 2', function() use ($pm, &$savedToken) {
    $passage = $pm->getByToken($savedToken);
    $q2 = $passage['question_order'][1];
    $updated = $pm->saveAnswer($savedToken, $q2, 0);
    return count((array) $updated['answers']) === 2;
});

test('answer_index invalide → exception', function() use ($pm, &$savedToken) {
    $passage = $pm->getByToken($savedToken);
    $q = $passage['question_order'][0];
    try {
        $pm->saveAnswer($savedToken, $q, 99);
        return false;
    } catch (\InvalidArgumentException $e) {
        return true;
    }
});

test('Question inconnue → exception', function() use ($pm, &$savedToken) {
    try {
        $pm->saveAnswer($savedToken, 'hors-examen-01', 0);
        return false;
    } catch (\InvalidArgumentException $e) {
        return true;
    }
});

// ========== ROUTE 4 : POST /api/passages/{token}/focus-event ==========
echo "\n👁️  ROUTE 4 : FOCUS-EVENT\n";

test('Log event blur', function() use ($pm, &$savedToken) {
    $updated = $pm->logFocusEvent($savedToken, ['type' => 'blur', 'duration_ms' => 3000]);
    return count($updated['focus_events']) >= 1;
});

test('Log event copy (anomalie grave)', function() use ($pm, &$savedToken) {
    $updated = $pm->logFocusEvent($savedToken, ['type' => 'copy']);
    return count($updated['focus_events']) >= 2;
});

test('Type invalide → exception', function() use ($pm, &$savedToken) {
    try {
        $pm->logFocusEvent($savedToken, ['type' => 'invalid_type']);
        return false;
    } catch (\InvalidArgumentException $e) {
        return true;
    }
});

// ========== ROUTE 5 : POST /api/passages/{token}/submit ==========
echo "\n✅ ROUTE 5 : SUBMIT\n";

test('Soumission finalise le passage', function() use ($pm, &$savedToken) {
    $submitted = $pm->submit($savedToken);
    return $submitted['status'] === 'submitted'
        && strlen($submitted['signature_sha256']) === 64
        && $submitted['score_brut'] !== null;
});

test('Signature correcte apres submit', function() use ($pm, &$savedToken) {
    $passage = $pm->getByToken($savedToken);
    return $pm->verifySignature($passage) === true;
});

test('Re-submit → exception already_submitted', function() use ($pm, &$savedToken) {
    try {
        $pm->submit($savedToken);
        return false;
    } catch (\RuntimeException $e) {
        return strpos($e->getMessage(), 'non soumettable') !== false;
    }
});

test('SaveAnswer apres submit → exception', function() use ($pm, &$savedToken) {
    $passage = $pm->getByToken($savedToken);
    try {
        $pm->saveAnswer($savedToken, $passage['question_order'][0], 1);
        return false;
    } catch (\RuntimeException $e) {
        return strpos($e->getMessage(), 'non modifiable') !== false;
    }
});

// ========== ROUTES PROF : list + stats + detail + invalidate ==========
echo "\n🔒 ROUTES PROF/ADMIN\n";

test('list() retourne le passage soumis', function() use ($pm, $exam, &$createdPassages) {
    $list = $pm->list(['examen_id' => $exam['id']]);
    return count($list) >= 1;
});

test('list() filtre par email', function() use ($pm) {
    $list = $pm->list(['email' => 'alice@test.fr']);
    foreach ($list as $p) {
        if (strtolower($p['student_info']['email']) !== 'alice@test.fr') return false;
    }
    return count($list) >= 1;
});

test('list() filtre par status=submitted', function() use ($pm) {
    $list = $pm->list(['status' => 'submitted']);
    foreach ($list as $p) {
        if ($p['status'] !== 'submitted') return false;
    }
    return count($list) >= 1;
});

test('getStats() par examen_id', function() use ($pm, $exam) {
    $stats = $pm->getStats($exam['id']);
    return isset($stats['total']) && isset($stats['avg_score_pct']);
});

test('getStats() globales (pas de filter)', function() use ($pm) {
    $stats = $pm->getStats();
    return isset($stats['total']) && $stats['total'] >= 1;
});

test('get() par ID retourne passage complet', function() use ($pm, &$savedPassageId) {
    $p = $pm->get($savedPassageId);
    return $p !== null && isset($p['signature_sha256']);
});

test('invalidate() change status', function() use ($pm, &$savedPassageId) {
    $invalidated = $pm->invalidate($savedPassageId, 'Fraude detectee lors du test');
    return $invalidated['status'] === 'invalidated'
        && ($invalidated['invalidation_reason'] ?? '') === 'Fraude detectee lors du test';
});

// ========== SCENARIO COMPLET (end-to-end) ==========
echo "\n🎯 SCENARIO COMPLET (end-to-end)\n";

test('Workflow complet : start → 4 reponses → submit → verify signature', function() use ($pm, $em, $examData, &$createdExams, &$createdPassages) {
    // Creer un nouvel examen pour ce test
    $newExam = $em->create($examData, 'PROF-E2E');
    $createdExams[] = $newExam['id'];
    $newExam = $em->publish($newExam['id']);

    // Start
    $p = $pm->start($newExam['id'], ['nom' => 'E2E', 'prenom' => 'Test', 'email' => 'e2e@test.fr']);
    $createdPassages[] = $p['id'];
    $token = $p['token'];

    // 4 reponses (toutes bonnes)
    $banque = new \Examens\Lib\BanqueManager();
    foreach ($p['question_order'] as $qId) {
        $q = $banque->getQuestion($qId);
        if ($q === null) continue;
        $correctOriginal = $q['correct'];
        $map = $p['option_shuffle_maps'][$qId];
        $shufflePosition = array_search($correctOriginal, $map, true);
        $pm->saveAnswer($token, $qId, $shufflePosition);
    }

    // Focus event
    $pm->logFocusEvent($token, ['type' => 'blur', 'duration_ms' => 1500]);

    // Submit
    $submitted = $pm->submit($token);

    // Verify
    $valid = $pm->verifySignature($submitted);

    return $submitted['status'] === 'submitted'
        && $submitted['score_brut'] === 4
        && $submitted['score_pct'] === 100.0
        && $valid === true
        && count($submitted['focus_events']) === 1;
});

// ========== CLEANUP ==========
echo "\n🧹 CLEANUP\n";

foreach ($createdPassages as $id) {
    $path = data_path('passages') . '/' . $id . '.json';
    if (file_exists($path)) @unlink($path);
}
echo "  Supprime " . count($createdPassages) . " passage(s)\n";

foreach ($createdExams as $id) {
    $path = data_path('examens') . '/' . $id . '.json';
    if (file_exists($path)) @unlink($path);
}
echo "  Supprime " . count($createdExams) . " examen(s)\n";

// ========== BILAN ==========
echo "\n" . str_repeat("=", 60) . "\n";
echo "RESULTAT : $passed / $tests tests passes";
echo $passed === $tests ? " ✅\n\n" : " ❌\n\n";

exit($passed === $tests ? 0 : 1);
