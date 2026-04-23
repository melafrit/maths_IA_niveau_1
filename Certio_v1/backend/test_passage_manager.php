<?php
/**
 * test_passage_manager.php — Tests CLI du PassageManager
 *
 * Usage : php backend/test_passage_manager.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Examens\Lib\ExamenManager;
use Examens\Lib\PassageManager;

echo "🧪 Test PassageManager\n";
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

// ========== SETUP : creer un examen publie ==========
echo "\n🏗️  SETUP : creer examen publie\n";

$examData = [
    'titre' => 'Examen test PassageManager',
    'description' => 'Test',
    'questions' => ['vec-faci-01', 'vec-faci-02', 'mat-faci-01'],
    'duree_sec' => 1800, // 30 min
    'date_ouverture' => date('c', strtotime('-1 hour')), // deja ouvert
    'date_cloture' => date('c', strtotime('+2 hours')), // ferme dans 2h
    'max_passages' => 2,
    'shuffle_questions' => true,
    'shuffle_options' => true,
];

$examPublie = $em->create($examData, 'PROF-TEST-PSG');
$createdExams[] = $examPublie['id'];
$examPublie = $em->publish($examPublie['id']);
echo "  Examen publie : {$examPublie['id']} (3 questions, 30 min)\n";

$studentInfo = [
    'nom' => 'Dupont',
    'prenom' => 'Jean',
    'email' => 'jean.dupont@test.ipssi.net',
];

// ========== TESTS START ==========
echo "\n🚀 START\n";

test('start() cree un passage valide', function() use ($pm, $examPublie, $studentInfo, &$createdPassages) {
    $p = $pm->start($examPublie['id'], $studentInfo);
    $createdPassages[] = $p['id'];
    return isset($p['id']) && isset($p['token'])
        && str_starts_with($p['id'], 'PSG-')
        && $p['status'] === 'in_progress';
});

test('start() genere un token unique UUID v4', function() use ($pm, $examPublie, $studentInfo, &$createdPassages) {
    $studentInfo2 = array_merge($studentInfo, ['email' => 'autre@test.fr']);
    $p = $pm->start($examPublie['id'], $studentInfo2);
    $createdPassages[] = $p['id'];
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $p['token']) === 1;
});

test('start() avec email invalide → exception', function() use ($pm, $examPublie) {
    try {
        $pm->start($examPublie['id'], ['nom' => 'Test', 'prenom' => 'T', 'email' => 'pas-valide']);
        return false;
    } catch (\InvalidArgumentException $e) {
        return strpos($e->getMessage(), 'email') !== false;
    }
});

test('start() sur examen inexistant → exception', function() use ($pm, $studentInfo) {
    try {
        $pm->start('EXM-FAKE-FAKE', $studentInfo);
        return false;
    } catch (\RuntimeException $e) {
        return strpos($e->getMessage(), 'introuvable') !== false;
    }
});

test('start() sur examen non publie → exception', function() use ($pm, $em, $examData, $studentInfo, &$createdExams) {
    $draft = $em->create($examData, 'PROF-TEST-PSG');
    $createdExams[] = $draft['id'];
    try {
        $pm->start($draft['id'], $studentInfo);
        return false;
    } catch (\RuntimeException $e) {
        return strpos($e->getMessage(), 'non disponible') !== false;
    }
});

test('start() shuffle_questions genere ordre aleatoire', function() use ($pm, $examPublie, &$createdPassages) {
    $p = $pm->start($examPublie['id'], [
        'nom' => 'T', 'prenom' => 'T', 'email' => 'shuffle@test.fr',
    ]);
    $createdPassages[] = $p['id'];
    return is_array($p['question_order']) && count($p['question_order']) === 3;
});

test('start() shuffle_options genere maps par question', function() use ($pm, $examPublie, &$createdPassages) {
    $p = $pm->start($examPublie['id'], [
        'nom' => 'T', 'prenom' => 'T', 'email' => 'opts@test.fr',
    ]);
    $createdPassages[] = $p['id'];
    return isset($p['option_shuffle_maps'])
        && count($p['option_shuffle_maps']) === 3
        && count($p['option_shuffle_maps'][$p['question_order'][0]]) === 4;
});

// ========== TESTS MAX PASSAGES ==========
echo "\n🔒 MAX_PASSAGES\n";

test('max_passages=2 : premier passage OK', function() use ($pm, $examPublie, &$createdPassages) {
    $p = $pm->start($examPublie['id'], [
        'nom' => 'Test', 'prenom' => 'Max', 'email' => 'max@test.fr',
    ]);
    $createdPassages[] = $p['id'];
    return $p !== null;
});

test('max_passages=2 : deuxieme passage OK', function() use ($pm, $examPublie, &$createdPassages) {
    $p = $pm->start($examPublie['id'], [
        'nom' => 'Test', 'prenom' => 'Max', 'email' => 'max@test.fr',
    ]);
    $createdPassages[] = $p['id'];
    return $p !== null;
});

test('max_passages=2 : troisieme passage → exception', function() use ($pm, $examPublie) {
    try {
        $pm->start($examPublie['id'], [
            'nom' => 'Test', 'prenom' => 'Max', 'email' => 'max@test.fr',
        ]);
        return false;
    } catch (\RuntimeException $e) {
        return strpos($e->getMessage(), 'maximal') !== false;
    }
});

// ========== TESTS SAUVEGARDE ==========
echo "\n💾 SAUVEGARDE PROGRESSIVE\n";

test('saveAnswer() sauvegarde la reponse', function() use ($pm, &$createdPassages) {
    if (empty($createdPassages)) return false;
    $token = $pm->get($createdPassages[0])['token'];
    $passage = $pm->getByToken($token);
    $firstQuestion = $passage['question_order'][0];

    $updated = $pm->saveAnswer($token, $firstQuestion, 2);
    return isset($updated['answers'][$firstQuestion])
        && $updated['answers'][$firstQuestion]['answer_index'] === 2;
});

test('saveAnswer() avec question hors examen → exception', function() use ($pm, &$createdPassages) {
    if (empty($createdPassages)) return false;
    $token = $pm->get($createdPassages[0])['token'];
    try {
        $pm->saveAnswer($token, 'fake-fake-99', 0);
        return false;
    } catch (\InvalidArgumentException $e) {
        return true;
    }
});

test('saveAnswer() avec answer_index invalide → exception', function() use ($pm, &$createdPassages) {
    if (empty($createdPassages)) return false;
    $token = $pm->get($createdPassages[0])['token'];
    $firstQuestion = $pm->getByToken($token)['question_order'][0];
    try {
        $pm->saveAnswer($token, $firstQuestion, 5);
        return false;
    } catch (\InvalidArgumentException $e) {
        return true;
    }
});

// ========== TESTS FOCUS EVENTS ==========
echo "\n👁️  FOCUS-LOCK\n";

test('logFocusEvent() enregistre un event blur', function() use ($pm, &$createdPassages) {
    if (empty($createdPassages)) return false;
    $token = $pm->get($createdPassages[0])['token'];
    $updated = $pm->logFocusEvent($token, [
        'type' => 'blur',
        'duration_ms' => 5000,
    ]);
    return count($updated['focus_events']) >= 1;
});

test('logFocusEvent() avec type invalide → exception', function() use ($pm, &$createdPassages) {
    if (empty($createdPassages)) return false;
    $token = $pm->get($createdPassages[0])['token'];
    try {
        $pm->logFocusEvent($token, ['type' => 'invalid']);
        return false;
    } catch (\InvalidArgumentException $e) {
        return true;
    }
});

// ========== TESTS SOUMISSION ==========
echo "\n✅ SOUMISSION\n";

test('submit() finalise le passage', function() use ($pm, &$createdPassages) {
    if (empty($createdPassages)) return false;
    $token = $pm->get($createdPassages[0])['token'];

    // Repondre a toutes les questions
    $passage = $pm->getByToken($token);
    foreach ($passage['question_order'] as $qId) {
        $pm->saveAnswer($token, $qId, random_int(0, 3));
    }

    $submitted = $pm->submit($token);
    return $submitted['status'] === 'submitted'
        && isset($submitted['signature_sha256'])
        && strlen($submitted['signature_sha256']) === 64
        && $submitted['score_brut'] !== null
        && $submitted['score_max'] === 3;
});

test('submit() calcule score correct', function() use ($pm, $examPublie, &$createdPassages) {
    $p = $pm->start($examPublie['id'], [
        'nom' => 'Score', 'prenom' => 'Test', 'email' => 'score@test.fr',
    ]);
    $createdPassages[] = $p['id'];

    // Obtenir les bonnes reponses
    $banque = new \Examens\Lib\BanqueManager();
    $shuffleMaps = $p['option_shuffle_maps'];

    // Repondre correctement a toutes les questions
    foreach ($p['question_order'] as $qId) {
        $question = $banque->getQuestion($qId);
        if ($question === null) continue;

        $correctOriginal = $question['correct'];
        $map = $shuffleMaps[$qId];
        // Trouver la position shuffle qui correspond a l'original
        $userAnswerShuffle = array_search($correctOriginal, $map, true);
        $pm->saveAnswer($p['token'], $qId, $userAnswerShuffle);
    }

    $submitted = $pm->submit($p['token']);
    return $submitted['score_brut'] === 3 && $submitted['score_pct'] === 100.0;
});

test('submit() sur passage deja soumis → exception', function() use ($pm, &$createdPassages) {
    if (empty($createdPassages)) return false;
    $token = $pm->get($createdPassages[0])['token'];
    try {
        $pm->submit($token);
        return false;
    } catch (\RuntimeException $e) {
        return strpos($e->getMessage(), 'non soumettable') !== false;
    }
});

// ========== TESTS SIGNATURE ==========
echo "\n🔐 SIGNATURE\n";

test('computeSignature() genere hash SHA-256 64 chars', function() use ($pm, &$createdPassages) {
    if (empty($createdPassages)) return false;
    $passage = $pm->get($createdPassages[0]);
    $sig = $pm->computeSignature($passage);
    return strlen($sig) === 64 && preg_match('/^[0-9a-f]{64}$/', $sig) === 1;
});

test('verifySignature() valide un passage soumis', function() use ($pm, &$createdPassages) {
    if (empty($createdPassages)) return false;
    $passage = $pm->get($createdPassages[0]);
    return $pm->verifySignature($passage) === true;
});

test('verifySignature() detecte modification de donnees', function() use ($pm, &$createdPassages) {
    if (empty($createdPassages)) return false;
    $passage = $pm->get($createdPassages[0]);
    $tampered = $passage;
    $tampered['score_brut'] = 999;
    return $pm->verifySignature($tampered) === false;
});

// ========== TESTS LECTURE ==========
echo "\n📖 LECTURE\n";

test('list() filtre par examen_id', function() use ($pm, $examPublie, &$createdPassages) {
    $list = $pm->list(['examen_id' => $examPublie['id']]);
    return count($list) >= count($createdPassages) - 1; // sauf le draft qui n'a pas ete utilise
});

test('list() filtre par email', function() use ($pm) {
    $list = $pm->list(['email' => 'max@test.fr']);
    foreach ($list as $p) {
        if (strtolower($p['student_info']['email']) !== 'max@test.fr') return false;
    }
    return count($list) === 2;
});

test('list() filtre par status', function() use ($pm) {
    $list = $pm->list(['status' => 'submitted']);
    foreach ($list as $p) {
        if ($p['status'] !== 'submitted') return false;
    }
    return count($list) >= 2;
});

test('countPassagesByEmailAndExam() compte correctement', function() use ($pm, $examPublie) {
    $count = $pm->countPassagesByEmailAndExam('max@test.fr', $examPublie['id']);
    return $count === 2;
});

// ========== TESTS STATS ==========
echo "\n📊 STATS\n";

test('getStats() calcule moyennes', function() use ($pm, $examPublie) {
    $stats = $pm->getStats($examPublie['id']);
    return isset($stats['total'])
        && isset($stats['by_status'])
        && isset($stats['avg_score_pct'])
        && $stats['total'] >= 1;
});

test('getStats() sans examen_id retourne stats globales', function() use ($pm) {
    $stats = $pm->getStats();
    return isset($stats['total']) && $stats['total'] >= 1;
});

// ========== TESTS INVALIDATION ==========
echo "\n⛔ INVALIDATION\n";

test('invalidate() change le status', function() use ($pm, &$createdPassages) {
    if (empty($createdPassages)) return false;
    $invalidated = $pm->invalidate($createdPassages[0], 'Fraude detectee');
    return $invalidated['status'] === 'invalidated'
        && ($invalidated['invalidation_reason'] ?? '') === 'Fraude detectee';
});

// ========== CLEANUP ==========
echo "\n🧹 CLEANUP\n";

$dataPath = data_path('passages');
foreach ($createdPassages as $id) {
    $path = $dataPath . '/' . $id . '.json';
    if (file_exists($path)) {
        @unlink($path);
    }
}
echo "  Supprime " . count($createdPassages) . " passage(s)\n";

foreach ($createdExams as $id) {
    $path = data_path('examens') . '/' . $id . '.json';
    if (file_exists($path)) {
        @unlink($path);
    }
}
echo "  Supprime " . count($createdExams) . " examen(s)\n";

// ========== BILAN ==========
echo "\n" . str_repeat("=", 60) . "\n";
echo "RESULTAT : $passed / $tests tests passes";
echo $passed === $tests ? " ✅\n\n" : " ❌\n\n";

exit($passed === $tests ? 0 : 1);
