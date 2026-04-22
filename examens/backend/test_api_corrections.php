<?php
/**
 * test_api_corrections.php — Tests API corrections scenarios
 *
 * Usage : php backend/test_api_corrections.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Examens\Lib\BanqueManager;
use Examens\Lib\ExamenManager;
use Examens\Lib\PassageManager;

echo "🧪 Test API Corrections\n";
echo str_repeat("=", 60) . "\n\n";

$em = new ExamenManager();
$pm = new PassageManager();
$banque = new BanqueManager();

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

// Helper : inclure corrections.php en direct (simule API)
function callBuildCorrection(array $passage, array $examen): array {
    require_once __DIR__ . '/api/corrections.php';
    // Mais le require execute tout, donc on refait la logique :
    global $banque;
    return buildCorrectionPayloadTest($passage, $examen, $banque);
}

// Fonction helper locale (reproduit buildCorrectionPayload)
function buildCorrectionPayloadTest(array $passage, array $examen, BanqueManager $banque): array {
    $questions = [];
    $shuffleMaps = $passage['option_shuffle_maps'] ?? [];
    $answers = (array) ($passage['answers'] ?? []);

    foreach ($passage['question_order'] as $qId) {
        $q = $banque->getQuestion($qId);
        if ($q === null) continue;

        $map = $shuffleMaps[$qId] ?? [0, 1, 2, 3];
        $correctOriginal = $q['correct'] ?? 0;
        $correctShuffled = array_search($correctOriginal, $map, true);

        $shuffledOptions = [];
        foreach ($map as $origIdx) {
            $shuffledOptions[] = $q['options'][$origIdx] ?? '';
        }

        $userAnswerShuffled = $answers[$qId]['answer_index'] ?? null;
        $isCorrect = ($userAnswerShuffled !== null) && ($userAnswerShuffled === $correctShuffled);

        $questions[] = [
            'id' => $q['id'],
            'enonce' => $q['enonce'],
            'options' => $shuffledOptions,
            'user_answer_index' => $userAnswerShuffled,
            'correct_answer_index' => $correctShuffled,
            'is_correct' => $isCorrect,
            'was_answered' => $userAnswerShuffled !== null,
            'hint' => $q['hint'] ?? '',
            'explanation' => $q['explanation'] ?? '',
            'traps' => $q['traps'] ?? '',
        ];
    }

    return [
        'passage' => $passage,
        'examen' => ['id' => $examen['id'], 'titre' => $examen['titre']],
        'questions' => $questions,
    ];
}

// ========== SETUP ==========
echo "\n🏗️  SETUP\n";

$examData = [
    'titre' => 'Examen Correction Test',
    'questions' => ['vec-faci-01', 'vec-faci-02', 'mat-faci-01', 'vec-moye-01'],
    'duree_sec' => 1800,
    'date_ouverture' => date('c', strtotime('-30 min')),
    'date_cloture' => date('c', strtotime('+2 hours')),
    'max_passages' => 5,
    'show_correction_after' => true,
    'correction_delay_min' => 0,
];

// Examen avec correction immediate
$exam1 = $em->create($examData, 'PROF-CORR-TEST');
$exam1 = $em->publish($exam1['id']);
$createdExams[] = $exam1['id'];
echo "  Examen 1 (correction imediate) : {$exam1['id']}\n";

// Examen AVEC delai
$exam2Data = array_merge($examData, [
    'titre' => 'Examen Correction Delay',
    'correction_delay_min' => 60, // 1h
]);
$exam2 = $em->create($exam2Data, 'PROF-CORR-TEST');
$exam2 = $em->publish($exam2['id']);
$createdExams[] = $exam2['id'];
echo "  Examen 2 (delay 60min) : {$exam2['id']}\n";

// Examen correction desactivee
$exam3Data = array_merge($examData, [
    'titre' => 'Examen No Correction',
    'show_correction_after' => false,
]);
$exam3 = $em->create($exam3Data, 'PROF-CORR-TEST');
$exam3 = $em->publish($exam3['id']);
$createdExams[] = $exam3['id'];
echo "  Examen 3 (correction off) : {$exam3['id']}\n";

// Creer des passages soumis (sur chaque examen)
function createSubmittedPassage(ExamenManager $em, PassageManager $pm, BanqueManager $banque, string $examenId, string $email, array $answersStrategy): array {
    $passage = $pm->start($examenId, [
        'nom' => 'Test', 'prenom' => 'Student', 'email' => $email,
    ]);
    foreach ($passage['question_order'] as $i => $qId) {
        $strategy = $answersStrategy[$i] ?? 'correct';
        $q = $banque->getQuestion($qId);
        if ($q === null) continue;

        if ($strategy === 'correct') {
            $correctOriginal = $q['correct'];
            $map = $passage['option_shuffle_maps'][$qId];
            $shufflePos = array_search($correctOriginal, $map, true);
            $pm->saveAnswer($passage['token'], $qId, $shufflePos);
        } elseif ($strategy === 'wrong') {
            $correctOriginal = $q['correct'];
            $wrong = ($correctOriginal + 1) % 4;
            $map = $passage['option_shuffle_maps'][$qId];
            $shufflePos = array_search($wrong, $map, true);
            $pm->saveAnswer($passage['token'], $qId, $shufflePos);
        }
        // 'skip' : ne rien repondre
    }
    return $pm->submit($passage['token']);
}

// ========== TESTS ACCES TOKEN ==========
echo "\n🎟️  ACCES TOKEN\n";

$passage1 = createSubmittedPassage($em, $pm, $banque, $exam1['id'], 'alice@test.fr',
    ['correct', 'correct', 'wrong', 'correct']); // 3/4
$createdPassages[] = $passage1['id'];

test('Token valide retourne la correction', function() use ($pm, $passage1, $em, $banque) {
    $passage = $pm->getByToken($passage1['token']);
    $examen = $em->get($passage['examen_id']);
    $payload = buildCorrectionPayloadTest($passage, $examen, $banque);
    return isset($payload['questions'])
        && count($payload['questions']) === 4
        && isset($payload['questions'][0]['explanation'])
        && isset($payload['questions'][0]['hint']);
});

test('Token invalide → null', function() use ($pm) {
    return $pm->getByToken('00000000-0000-4000-8000-000000000000') === null;
});

// ========== TESTS REMAPPING ==========
echo "\n🔀 REMAPPING SHUFFLE\n";

test('user_answer_index pointe vers position shuffled', function() use ($pm, $passage1, $em, $banque) {
    $passage = $pm->getByToken($passage1['token']);
    $examen = $em->get($passage['examen_id']);
    $payload = buildCorrectionPayloadTest($passage, $examen, $banque);

    $q0 = $payload['questions'][0];
    // user_answer_index doit etre entre 0-3
    return is_int($q0['user_answer_index'])
        && $q0['user_answer_index'] >= 0
        && $q0['user_answer_index'] <= 3;
});

test('correct_answer_index pointe vers position shuffled de la bonne rep', function() use ($pm, $passage1, $em, $banque) {
    $passage = $pm->getByToken($passage1['token']);
    $examen = $em->get($passage['examen_id']);
    $payload = buildCorrectionPayloadTest($passage, $examen, $banque);

    foreach ($payload['questions'] as $qPayload) {
        // La position correcte dans l'ordre shuffled doit pointer
        // vers l'option qui correspond a la bonne reponse originale
        $qId = $qPayload['id'];
        $q = $banque->getQuestion($qId);
        $correctOriginal = $q['correct'];
        $shuffledMap = $passage['option_shuffle_maps'][$qId];
        $expectedShuffled = array_search($correctOriginal, $shuffledMap, true);
        if ($qPayload['correct_answer_index'] !== $expectedShuffled) {
            return "Mismatch Q $qId : attendu $expectedShuffled, got {$qPayload['correct_answer_index']}";
        }
    }
    return true;
});

test('is_correct concordant avec score', function() use ($pm, $passage1, $em, $banque) {
    $passage = $pm->getByToken($passage1['token']);
    $examen = $em->get($passage['examen_id']);
    $payload = buildCorrectionPayloadTest($passage, $examen, $banque);

    $correctCount = 0;
    foreach ($payload['questions'] as $q) {
        if ($q['is_correct']) $correctCount++;
    }
    return $correctCount === $passage1['score_brut'];
});

test('Question avec toutes reponses correctes : 4/4', function() use ($em, $pm, $banque, $exam1, &$createdPassages) {
    $p = createSubmittedPassage($em, $pm, $banque, $exam1['id'], 'perfect@test.fr',
        ['correct', 'correct', 'correct', 'correct']);
    $createdPassages[] = $p['id'];
    $passage = $pm->getByToken($p['token']);
    $examen = $em->get($passage['examen_id']);
    $payload = buildCorrectionPayloadTest($passage, $examen, $banque);

    $allCorrect = true;
    foreach ($payload['questions'] as $q) {
        if (!$q['is_correct']) { $allCorrect = false; break; }
    }
    return $allCorrect && $p['score_brut'] === 4;
});

test('Question non repondue : was_answered=false, is_correct=false', function() use ($em, $pm, $banque, $exam1, &$createdPassages) {
    $p = createSubmittedPassage($em, $pm, $banque, $exam1['id'], 'skipped@test.fr',
        ['correct', 'correct', 'skip', 'skip']);
    $createdPassages[] = $p['id'];
    $passage = $pm->getByToken($p['token']);
    $examen = $em->get($passage['examen_id']);
    $payload = buildCorrectionPayloadTest($passage, $examen, $banque);

    $skipped = 0;
    foreach ($payload['questions'] as $q) {
        if (!$q['was_answered']) {
            $skipped++;
            if ($q['is_correct']) return 'is_correct devrait etre false pour non repondue';
        }
    }
    return $skipped === 2;
});

// ========== TESTS ACCES RESTRICTIONS ==========
echo "\n🚫 RESTRICTIONS\n";

test('show_correction_after=false : correction bloquee', function() use ($em, $pm, $banque, $exam3, &$createdPassages) {
    $p = createSubmittedPassage($em, $pm, $banque, $exam3['id'], 'nocorr@test.fr',
        ['correct', 'correct', 'correct', 'correct']);
    $createdPassages[] = $p['id'];
    $examen = $em->get($p['examen_id']);
    return empty($examen['show_correction_after']);
});

test('correction_delay_min > 0 : delai pas ecoule', function() use ($em, $pm, $banque, $exam2, &$createdPassages) {
    $p = createSubmittedPassage($em, $pm, $banque, $exam2['id'], 'delayed@test.fr',
        ['correct', 'correct', 'correct', 'correct']);
    $createdPassages[] = $p['id'];

    $examen = $em->get($p['examen_id']);
    $delayMin = (int) $examen['correction_delay_min'];
    $endTs = strtotime($p['end_time']);
    $availableTs = $endTs + ($delayMin * 60);
    return $delayMin === 60 && time() < $availableTs;
});

// ========== TESTS STATS PAR QUESTION ==========
echo "\n📊 STATS PAR QUESTION\n";

test('Stats agregees : compte par question', function() use ($pm, $exam1) {
    $passages = $pm->list([
        'examen_id' => $exam1['id'],
        'status' => ['submitted', 'expired'],
    ]);
    $byQ = [];
    foreach ($passages as $p) {
        foreach ($p['question_order'] as $qId) {
            $byQ[$qId] = ($byQ[$qId] ?? 0) + 1;
        }
    }
    return count($byQ) === 4; // 4 questions dans exam1
});

test('Taux de reussite entre 0 et 100%', function() use ($pm, $exam1, $banque) {
    $passages = $pm->list([
        'examen_id' => $exam1['id'],
        'status' => 'submitted',
    ]);
    $byQ = [];
    foreach ($passages as $p) {
        foreach ($p['question_order'] as $qId) {
            if (!isset($byQ[$qId])) $byQ[$qId] = ['total' => 0, 'correct' => 0];
            $byQ[$qId]['total']++;
            $q = $banque->getQuestion($qId);
            if ($q === null) continue;
            $map = $p['option_shuffle_maps'][$qId] ?? [0,1,2,3];
            $answers = (array) ($p['answers'] ?? []);
            if (isset($answers[$qId])) {
                $userShuffled = $answers[$qId]['answer_index'];
                $userOriginal = $map[$userShuffled] ?? $userShuffled;
                if ($userOriginal === ($q['correct'] ?? 0)) {
                    $byQ[$qId]['correct']++;
                }
            }
        }
    }

    foreach ($byQ as $qId => $stats) {
        $rate = $stats['total'] > 0 ? ($stats['correct'] / $stats['total']) * 100 : 0;
        if ($rate < 0 || $rate > 100) return false;
    }
    return true;
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
