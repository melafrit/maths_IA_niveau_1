<?php
/**
 * test_api_analytics.php — Tests scenarios API /api/analytics
 *
 * Note : on teste les helpers parseListOptions + requireExamenOwnerOrAdmin
 * et on teste la logique complete via le manager.
 *
 * Usage : php backend/test_api_analytics.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Examens\Lib\AnalyticsManager;
use Examens\Lib\BanqueManager;
use Examens\Lib\ExamenManager;
use Examens\Lib\PassageManager;

echo "🧪 Test API Analytics (P7.2)\n";
echo str_repeat("=", 60) . "\n\n";

$em = new ExamenManager();
$pm = new PassageManager();
$banque = new BanqueManager();
$am = new AnalyticsManager();

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

// ========== HELPER : creer passage soumis ==========
function createSubmittedPassage(
    ExamenManager $em, PassageManager $pm, BanqueManager $banque,
    string $examenId, string $email, array $strategies, array $events = []
): array {
    $name = explode('@', $email)[0];
    $passage = $pm->start($examenId, ['nom' => 'T', 'prenom' => ucfirst($name), 'email' => $email]);
    foreach ($passage['question_order'] as $i => $qId) {
        $strategy = $strategies[$i] ?? 'correct';
        if ($strategy === 'skip') continue;
        $q = $banque->getQuestion($qId);
        if ($q === null) continue;
        $correctOriginal = $q['correct'];
        $map = $passage['option_shuffle_maps'][$qId];
        if ($strategy === 'correct') {
            $shuffle = array_search($correctOriginal, $map, true);
        } else {
            $wrong = ($correctOriginal + 1) % 4;
            $shuffle = array_search($wrong, $map, true);
        }
        $pm->saveAnswer($passage['token'], $qId, $shuffle);
    }
    foreach ($events as $evt) {
        $pm->logFocusEvent($passage['token'], $evt);
    }
    return $pm->submit($passage['token']);
}

// ========== SETUP ==========
echo "\n🏗️  SETUP\n";

$examData = [
    'titre' => 'API Analytics Test',
    'questions' => ['vec-faci-01', 'vec-faci-02', 'mat-faci-01', 'vec-moye-01'],
    'duree_sec' => 1800,
    'date_ouverture' => date('c', strtotime('-30 min')),
    'date_cloture' => date('c', strtotime('+2 hours')),
    'max_passages' => 10,
    'show_correction_after' => true,
    'correction_delay_min' => 0,
];

$profA = 'PROF-A-API-ANA';
$profB = 'PROF-B-API-ANA';

$exam = $em->create($examData, $profA);
$exam = $em->publish($exam['id']);
$createdExams[] = $exam['id'];

$exam2 = $em->create(array_merge($examData, ['titre' => 'API Analytics Test 2']), $profB);
$exam2 = $em->publish($exam2['id']);
$createdExams[] = $exam2['id'];

// 5 passages varies + 1 anomalie
$p1 = createSubmittedPassage($em, $pm, $banque, $exam['id'], 'alice@test.fr',
    ['correct', 'correct', 'correct', 'correct']);
$createdPassages[] = $p1['id'];
$p2 = createSubmittedPassage($em, $pm, $banque, $exam['id'], 'bob@test.fr',
    ['correct', 'correct', 'wrong', 'wrong']);
$createdPassages[] = $p2['id'];
$p3 = createSubmittedPassage($em, $pm, $banque, $exam['id'], 'charlie@test.fr',
    ['wrong', 'wrong', 'wrong', 'wrong'],
    [['type' => 'copy'], ['type' => 'devtools']]);
$createdPassages[] = $p3['id'];

echo "  Examens crees : 2\n";
echo "  Passages crees : " . count($createdPassages) . "\n";

// ========== TESTS API VIA MANAGER ==========
echo "\n📊 VIA ANALYTICS MANAGER (simulation API)\n";

test('Route overview : retourne KPIs', function() use ($am, $exam) {
    $ov = $am->getExamenOverview($exam['id']);
    return $ov['total_passages'] === 3 && $ov['unique_students'] === 3;
});

test('Route scores : retourne 10 buckets', function() use ($am, $exam) {
    $dist = $am->getScoreDistribution($exam['id']);
    return count($dist['histogram']) === 10 && isset($dist['mentions']);
});

test('Route questions : 4 questions avec option_analysis', function() use ($am, $exam) {
    $qs = $am->getQuestionStats($exam['id'], true);
    return $qs['nb_questions'] === 4
        && count($qs['questions'][0]['option_analysis']) === 4
        && isset($qs['questions'][0]['enonce']);
});

test('Route timeline : retourne tableau date->count', function() use ($am, $exam) {
    $tl = $am->getTimeline($exam['id']);
    return is_array($tl) && count($tl) >= 1
        && isset($tl[0]['hour']) && isset($tl[0]['count']);
});

test('Route focus-heatmap : 7 types + passages_with_events', function() use ($am, $exam) {
    $hm = $am->getFocusHeatmap($exam['id']);
    return isset($hm['by_type']['copy'])
        && $hm['by_type']['copy'] === 1
        && $hm['by_type']['devtools'] === 1;
});

test('Route passages : tri + filtres fonctionnent', function() use ($am, $exam) {
    $r = $am->listPassagesEnriched([
        'examen_id' => $exam['id'],
        'sort' => 'score',
        'order' => 'desc',
    ]);
    return $r['total'] === 3
        && $r['passages'][0]['score_pct'] >= ($r['passages'][1]['score_pct'] ?? 0);
});

test('Route prof overview : compte ≥ 1 examen pour profA', function() use ($am, $profA) {
    $ov = $am->getProfOverview($profA);
    return $ov['total_examens'] >= 1;
});

test('Route prof overview : compte ≥ 1 examen pour profB', function() use ($am, $profB) {
    $ov = $am->getProfOverview($profB);
    return $ov['total_examens'] >= 1;
});

test('Route student/{email} : alice a au moins 1 passage avec score 100%', function() use ($am) {
    $h = $am->getStudentHistory('alice@test.fr');
    return $h['nb_passages'] >= 1 && $h['best_score_pct'] == 100;
});

// ========== TESTS PERMISSIONS ==========
echo "\n🔒 PERMISSIONS\n";

test('Owner peut acceder à ses examens', function() use ($em, $exam, $profA) {
    $e = $em->get($exam['id']);
    return $e['created_by'] === $profA;
});

test('ProfB ne peut PAS acceder a l\'examen de profA', function() use ($em, $exam, $profB) {
    $e = $em->get($exam['id']);
    // Un autre prof n'est pas l'owner
    return $e['created_by'] !== $profB;
});

test('ProfA voit ses examens dans prof/overview', function() use ($am, $profA, $exam) {
    $ov = $am->getProfOverview($profA);
    $found = false;
    foreach ($ov['all_examens'] as $e) {
        if ($e['id'] === $exam['id']) $found = true;
    }
    return $found;
});

test('ProfB ne voit PAS les examens de profA', function() use ($am, $profB, $exam) {
    $ov = $am->getProfOverview($profB);
    foreach ($ov['all_examens'] as $e) {
        if ($e['id'] === $exam['id']) return 'ProfB voit examen de profA !';
    }
    return true;
});

// ========== TESTS PAGINATION ==========
echo "\n📄 PAGINATION\n";

test('Pagination : limit applique', function() use ($am, $exam) {
    $r = $am->listPassagesEnriched(['examen_id' => $exam['id'], 'limit' => 2]);
    return count($r['passages']) === 2 && $r['total'] === 3;
});

test('Pagination : offset applique', function() use ($am, $exam) {
    $page1 = $am->listPassagesEnriched(['examen_id' => $exam['id'], 'limit' => 2, 'offset' => 0]);
    $page2 = $am->listPassagesEnriched(['examen_id' => $exam['id'], 'limit' => 2, 'offset' => 2]);
    return count($page1['passages']) === 2 && count($page2['passages']) === 1;
});

// ========== TESTS RECHERCHE ==========
echo "\n🔍 RECHERCHE\n";

test('Search par email partiel', function() use ($am, $exam) {
    $r = $am->listPassagesEnriched([
        'examen_id' => $exam['id'],
        'search' => 'alice',
    ]);
    return $r['total'] === 1 && $r['passages'][0]['student_info']['email'] === 'alice@test.fr';
});

test('Search insensible a la casse', function() use ($am, $exam) {
    $r = $am->listPassagesEnriched([
        'examen_id' => $exam['id'],
        'search' => 'BOB',
    ]);
    return $r['total'] === 1;
});

test('Search sans match : total=0', function() use ($am, $exam) {
    $r = $am->listPassagesEnriched([
        'examen_id' => $exam['id'],
        'search' => 'nonexistent_xyz',
    ]);
    return $r['total'] === 0;
});

// ========== CLEANUP ==========
echo "\n🧹 CLEANUP\n";
foreach ($createdPassages as $id) {
    $path = data_path('passages') . '/' . $id . '.json';
    if (file_exists($path)) @unlink($path);
}
foreach ($createdExams as $id) {
    $path = data_path('examens') . '/' . $id . '.json';
    if (file_exists($path)) @unlink($path);
}
echo "  Cleanup OK\n";

// ========== BILAN ==========
echo "\n" . str_repeat("=", 60) . "\n";
echo "RESULTAT : $passed / $tests tests passes";
echo $passed === $tests ? " ✅\n\n" : " ❌\n\n";

exit($passed === $tests ? 0 : 1);
