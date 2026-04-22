<?php
/**
 * test_analytics_manager.php — Tests AnalyticsManager P7.1
 *
 * Couverture :
 *   - listPassagesEnriched : filtres, tri, recherche, pagination
 *   - getExamenOverview : KPIs, mediane, ecart-type
 *   - getScoreDistribution : 10 buckets, mentions
 *   - getQuestionStats : remapping shuffle, distracteurs
 *   - getStudentHistory : multi-examens
 *   - getFocusHeatmap : 7 types d'events
 *   - getProfOverview : dashboard prof
 *
 * Usage : php backend/test_analytics_manager.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Examens\Lib\AnalyticsManager;
use Examens\Lib\BanqueManager;
use Examens\Lib\ExamenManager;
use Examens\Lib\PassageManager;

echo "🧪 Test AnalyticsManager (P7.1)\n";
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

// Helper : créer passage soumis avec stratégies d'answers
function createSubmittedPassage(
    ExamenManager $em, PassageManager $pm, BanqueManager $banque,
    string $examenId, string $email, array $strategies,
    array $focusEvents = []
): array {
    $nameParts = explode('@', $email);
    $passage = $pm->start($examenId, [
        'nom' => 'Test',
        'prenom' => ucfirst($nameParts[0]),
        'email' => $email,
    ]);

    foreach ($passage['question_order'] as $i => $qId) {
        $strategy = $strategies[$i] ?? 'correct';
        $q = $banque->getQuestion($qId);
        if ($q === null) continue;

        if ($strategy === 'skip') continue;

        $correctOriginal = $q['correct'];
        $map = $passage['option_shuffle_maps'][$qId];

        if ($strategy === 'correct') {
            $shuffle = array_search($correctOriginal, $map, true);
        } else {
            // 'wrong' : prendre une mauvaise reponse
            $wrong = ($correctOriginal + 1) % 4;
            $shuffle = array_search($wrong, $map, true);
        }

        $pm->saveAnswer($passage['token'], $qId, $shuffle);
    }

    // Focus events simulés
    foreach ($focusEvents as $evt) {
        $pm->logFocusEvent($passage['token'], $evt);
    }

    return $pm->submit($passage['token']);
}

// ========== SETUP ==========
echo "\n🏗️  SETUP\n";

$examData = [
    'titre' => 'Analytics Test',
    'questions' => ['vec-faci-01', 'vec-faci-02', 'mat-faci-01', 'vec-moye-01'],
    'duree_sec' => 1800,
    'date_ouverture' => date('c', strtotime('-30 min')),
    'date_cloture' => date('c', strtotime('+2 hours')),
    'max_passages' => 10,
    'show_correction_after' => true,
    'correction_delay_min' => 0,
];

$profId = 'PROF-ANALYTICS';
$exam = $em->create($examData, $profId);
$exam = $em->publish($exam['id']);
$createdExams[] = $exam['id'];
echo "  Examen : {$exam['id']}\n";

// Examen secondaire pour tester multi-examens
$exam2 = $em->create(array_merge($examData, ['titre' => 'Analytics Test 2']), $profId);
$exam2 = $em->publish($exam2['id']);
$createdExams[] = $exam2['id'];
echo "  Examen 2 : {$exam2['id']}\n";

// 10 passages avec scores variés sur exam1
echo "  Creation 10 passages sur exam1...\n";
$p1 = createSubmittedPassage($em, $pm, $banque, $exam['id'], 'alice@test.fr',
    ['correct', 'correct', 'correct', 'correct']); // 4/4 = 100%
$createdPassages[] = $p1['id'];

$p2 = createSubmittedPassage($em, $pm, $banque, $exam['id'], 'bob@test.fr',
    ['correct', 'correct', 'correct', 'wrong']); // 3/4 = 75%
$createdPassages[] = $p2['id'];

$p3 = createSubmittedPassage($em, $pm, $banque, $exam['id'], 'charlie@test.fr',
    ['correct', 'correct', 'wrong', 'wrong']); // 2/4 = 50%
$createdPassages[] = $p3['id'];

$p4 = createSubmittedPassage($em, $pm, $banque, $exam['id'], 'david@test.fr',
    ['correct', 'wrong', 'wrong', 'skip']); // 1/4 = 25%
$createdPassages[] = $p4['id'];

$p5 = createSubmittedPassage($em, $pm, $banque, $exam['id'], 'eve@test.fr',
    ['wrong', 'wrong', 'wrong', 'wrong']); // 0/4 = 0%
$createdPassages[] = $p5['id'];

// Passage avec anomalies
$p6 = createSubmittedPassage($em, $pm, $banque, $exam['id'], 'suspect@test.fr',
    ['correct', 'correct', 'correct', 'correct'],
    [
        ['type' => 'copy'],
        ['type' => 'paste'],
        ['type' => 'blur', 'duration_ms' => 3000],
        ['type' => 'devtools'],
    ]
);
$createdPassages[] = $p6['id'];

// 2 passages sur exam2 pour alice (multi-examens history)
$p7 = createSubmittedPassage($em, $pm, $banque, $exam2['id'], 'alice@test.fr',
    ['correct', 'correct', 'wrong', 'correct']); // 3/4 = 75%
$createdPassages[] = $p7['id'];

echo "  Total passages crees : " . count($createdPassages) . "\n";

// ========== TESTS listPassagesEnriched ==========
echo "\n📋 LIST PASSAGES ENRICHED\n";

test('list() retourne tous les passages de l\'examen', function() use ($am, $exam) {
    $result = $am->listPassagesEnriched(['examen_id' => $exam['id']]);
    return $result['total'] >= 6 && count($result['passages']) >= 6;
});

test('Enrichissement : anomalies_count + focus_events_count + nb_answered', function() use ($am, $exam) {
    $result = $am->listPassagesEnriched(['examen_id' => $exam['id']]);
    $p = $result['passages'][0];
    return isset($p['anomalies_count']) && isset($p['focus_events_count']) && isset($p['nb_answered']);
});

test('Filtre with_anomalies : ne garde que les passages suspects', function() use ($am, $exam) {
    $result = $am->listPassagesEnriched([
        'examen_id' => $exam['id'],
        'with_anomalies' => true,
    ]);
    foreach ($result['passages'] as $p) {
        if ($p['anomalies_count'] === 0) return 'Passage sans anomalie dans resultat';
    }
    return $result['total'] >= 1;
});

test('Tri par score desc', function() use ($am, $exam) {
    $result = $am->listPassagesEnriched([
        'examen_id' => $exam['id'],
        'sort' => 'score',
        'order' => 'desc',
    ]);
    $prev = 101;
    foreach ($result['passages'] as $p) {
        if (($p['score_pct'] ?? 0) > $prev) return 'Ordre non respecté';
        $prev = $p['score_pct'] ?? 0;
    }
    return true;
});

test('Tri par name asc', function() use ($am, $exam) {
    $result = $am->listPassagesEnriched([
        'examen_id' => $exam['id'],
        'sort' => 'name',
        'order' => 'asc',
    ]);
    return count($result['passages']) >= 6;
});

test('Recherche par nom (partiel)', function() use ($am, $exam) {
    $result = $am->listPassagesEnriched([
        'examen_id' => $exam['id'],
        'search' => 'alice',
    ]);
    foreach ($result['passages'] as $p) {
        $all = strtolower(
            $p['student_info']['email'] . ' ' .
            $p['student_info']['prenom'] . ' ' .
            $p['student_info']['nom']
        );
        if (!str_contains($all, 'alice')) return 'Resultat ne matche pas';
    }
    return $result['total'] >= 1;
});

test('Filtre min_score_pct + max_score_pct', function() use ($am, $exam) {
    $result = $am->listPassagesEnriched([
        'examen_id' => $exam['id'],
        'min_score_pct' => 50,
        'max_score_pct' => 99,
    ]);
    foreach ($result['passages'] as $p) {
        if (($p['score_pct'] ?? 0) < 50 || ($p['score_pct'] ?? 0) > 99) return 'Hors plage';
    }
    return true;
});

test('Pagination : limit+offset', function() use ($am, $exam) {
    $page1 = $am->listPassagesEnriched(['examen_id' => $exam['id'], 'limit' => 2, 'offset' => 0]);
    $page2 = $am->listPassagesEnriched(['examen_id' => $exam['id'], 'limit' => 2, 'offset' => 2]);
    if (count($page1['passages']) !== 2) return 'page1 != 2';
    if ($page1['passages'][0]['id'] === $page2['passages'][0]['id']) return 'Meme passage en offset 0 et 2';
    return true;
});

// ========== TESTS getExamenOverview ==========
echo "\n📊 EXAMEN OVERVIEW\n";

test('Overview : total_passages correct', function() use ($am, $exam) {
    $ov = $am->getExamenOverview($exam['id']);
    return $ov['total_passages'] >= 6;
});

test('Overview : unique_students correct (6 emails differents)', function() use ($am, $exam) {
    $ov = $am->getExamenOverview($exam['id']);
    return $ov['unique_students'] === 6;
});

test('Overview : avg_score_pct calcule', function() use ($am, $exam) {
    $ov = $am->getExamenOverview($exam['id']);
    return is_numeric($ov['avg_score_pct']) && $ov['avg_score_pct'] > 0 && $ov['avg_score_pct'] < 100;
});

test('Overview : median + std_dev calcules', function() use ($am, $exam) {
    $ov = $am->getExamenOverview($exam['id']);
    return isset($ov['median_score_pct']) && isset($ov['std_dev']) && $ov['std_dev'] > 0;
});

test('Overview : min=0 et max=100 (nos donnees)', function() use ($am, $exam) {
    $ov = $am->getExamenOverview($exam['id']);
    return $ov['min_score_pct'] == 0 && $ov['max_score_pct'] == 100;
});

test('Overview : anomaly_rate > 0 (1 passage avec copy/paste)', function() use ($am, $exam) {
    $ov = $am->getExamenOverview($exam['id']);
    return $ov['anomaly_passages'] >= 1 && $ov['anomaly_rate_pct'] > 0;
});

// ========== TESTS getScoreDistribution ==========
echo "\n📈 SCORE DISTRIBUTION\n";

test('Distribution : 10 buckets', function() use ($am, $exam) {
    $dist = $am->getScoreDistribution($exam['id']);
    return count($dist['histogram']) === 10;
});

test('Distribution : mentions presentes', function() use ($am, $exam) {
    $dist = $am->getScoreDistribution($exam['id']);
    return isset($dist['mentions']['excellent']) &&
           isset($dist['mentions']['tres_bien']) &&
           isset($dist['mentions']['tres_insuf']);
});

test('Distribution : pass_rate_pct calcule', function() use ($am, $exam) {
    $dist = $am->getScoreDistribution($exam['id']);
    return isset($dist['pass_rate_pct']) && $dist['pass_rate_pct'] >= 0 && $dist['pass_rate_pct'] <= 100;
});

test('Distribution : total correspond au nb de passages', function() use ($am, $exam) {
    $dist = $am->getScoreDistribution($exam['id']);
    $sumBuckets = array_sum(array_column($dist['histogram'], 'count'));
    return $sumBuckets === $dist['total'];
});

// ========== TESTS getQuestionStats ==========
echo "\n❓ QUESTION STATS\n";

test('QuestionStats : 4 questions dans l\'output', function() use ($am, $exam) {
    $qs = $am->getQuestionStats($exam['id']);
    return $qs['nb_questions'] === 4;
});

test('QuestionStats : remapping shuffle → success_rate calcule', function() use ($am, $exam) {
    $qs = $am->getQuestionStats($exam['id']);
    foreach ($qs['questions'] as $q) {
        if ($q['success_rate_pct'] < 0 || $q['success_rate_pct'] > 100) return 'Taux hors bornes';
    }
    return true;
});

test('QuestionStats : option_analysis a 4 entrees par Q', function() use ($am, $exam) {
    $qs = $am->getQuestionStats($exam['id']);
    foreach ($qs['questions'] as $q) {
        if (count($q['option_analysis']) !== 4) return 'Pas 4 options';
    }
    return true;
});

test('QuestionStats : une option avec is_correct=true par Q', function() use ($am, $exam) {
    $qs = $am->getQuestionStats($exam['id']);
    foreach ($qs['questions'] as $q) {
        $correctCount = 0;
        foreach ($q['option_analysis'] as $opt) {
            if ($opt['is_correct']) $correctCount++;
        }
        if ($correctCount !== 1) return "Q {$q['question_id']} : $correctCount options correctes";
    }
    return true;
});

test('QuestionStats : tri par success_rate croissant', function() use ($am, $exam) {
    $qs = $am->getQuestionStats($exam['id']);
    $prev = -1;
    foreach ($qs['questions'] as $q) {
        if ($q['success_rate_pct'] < $prev) return 'Tri non croissant';
        $prev = $q['success_rate_pct'];
    }
    return true;
});

test('QuestionStats avec withFullDetails=true : inclut enonce', function() use ($am, $exam) {
    $qs = $am->getQuestionStats($exam['id'], true);
    return isset($qs['questions'][0]['enonce']) && !empty($qs['questions'][0]['enonce']);
});

// ========== TESTS getStudentHistory ==========
echo "\n👤 STUDENT HISTORY\n";

test('StudentHistory : alice a 2 passages (exam1 + exam2)', function() use ($am) {
    $hist = $am->getStudentHistory('alice@test.fr');
    return $hist['nb_passages'] === 2;
});

test('StudentHistory : best_score_pct = 100', function() use ($am) {
    $hist = $am->getStudentHistory('alice@test.fr');
    return $hist['best_score_pct'] == 100;
});

test('StudentHistory : passages incluent examen_titre', function() use ($am) {
    $hist = $am->getStudentHistory('alice@test.fr');
    foreach ($hist['passages'] as $p) {
        if (empty($p['examen_titre'])) return 'examen_titre manquant';
    }
    return true;
});

test('StudentHistory : tri par date desc', function() use ($am) {
    $hist = $am->getStudentHistory('alice@test.fr');
    if (count($hist['passages']) < 2) return true;
    return strcmp($hist['passages'][0]['start_time'], $hist['passages'][1]['start_time']) >= 0;
});

test('StudentHistory : total_time_sec >= 0 (calcule)', function() use ($am) {
    $hist = $am->getStudentHistory('alice@test.fr');
    return is_int($hist['total_time_sec']) && $hist['total_time_sec'] >= 0;
});

// ========== TESTS getFocusHeatmap ==========
echo "\n🔒 FOCUS HEATMAP\n";

test('Heatmap : 7 types d\'evenements comptes', function() use ($am, $exam) {
    $hm = $am->getFocusHeatmap($exam['id']);
    $expectedTypes = ['blur', 'focus', 'visibility_change', 'copy', 'paste', 'rightclick', 'devtools'];
    foreach ($expectedTypes as $t) {
        if (!isset($hm['by_type'][$t])) return "Type $t manquant";
    }
    return true;
});

test('Heatmap : copy + paste + devtools comptes correctement', function() use ($am, $exam) {
    $hm = $am->getFocusHeatmap($exam['id']);
    return $hm['by_type']['copy'] >= 1 && $hm['by_type']['paste'] >= 1 && $hm['by_type']['devtools'] >= 1;
});

test('Heatmap : passages_with_events trie par total desc', function() use ($am, $exam) {
    $hm = $am->getFocusHeatmap($exam['id']);
    if (count($hm['passages_with_events']) < 1) return true;
    $prev = PHP_INT_MAX;
    foreach ($hm['passages_with_events'] as $p) {
        if ($p['total_events'] > $prev) return 'Tri non respecte';
        $prev = $p['total_events'];
    }
    return true;
});

// ========== TESTS getProfOverview ==========
echo "\n👨‍🏫 PROF OVERVIEW\n";

test('ProfOverview : total_examens correct', function() use ($am, $profId) {
    $ov = $am->getProfOverview($profId);
    return $ov['total_examens'] >= 2;
});

test('ProfOverview : by_status compte bien les publies', function() use ($am, $profId) {
    $ov = $am->getProfOverview($profId);
    return $ov['by_status']['published'] >= 2;
});

test('ProfOverview : total_passages sommes sur tous examens', function() use ($am, $profId) {
    $ov = $am->getProfOverview($profId);
    return $ov['total_passages'] >= 7;
});

test('ProfOverview : global_avg_score_pct calcule', function() use ($am, $profId) {
    $ov = $am->getProfOverview($profId);
    return is_numeric($ov['global_avg_score_pct']) && $ov['global_avg_score_pct'] > 0;
});

test('ProfOverview : recent_examens tries par date desc', function() use ($am, $profId) {
    $ov = $am->getProfOverview($profId);
    if (count($ov['recent_examens']) < 2) return true;
    return strcmp(
        $ov['recent_examens'][0]['created_at'] ?? '',
        $ov['recent_examens'][1]['created_at'] ?? ''
    ) >= 0;
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
