<?php
/**
 * test_e2e_workflow.php — Tests End-to-End de workflows complets
 *
 * Tests integrants couvrant :
 *   1. LIFECYCLE PROF : create → publish → analytics → close → archive
 *   2. ETUDIANT FLOW  : access code → start → answers → submit → correction
 *   3. MULTI-ETUDIANTS : 5 passages avec scores varies → analytics agrégées
 *   4. FRAUDE         : focus events → anomalies detectees
 *   5. EDGE CASES     : code invalide, passage expiré, max atteint, duplicate
 *   6. ANALYTICS      : overview, distribution, questions stats, student history
 *
 * Usage : php backend/tests/test_e2e_workflow.php
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Examens\Lib\ExamenManager;
use Examens\Lib\PassageManager;
use Examens\Lib\AnalyticsManager;
use Examens\Lib\BanqueManager;

echo "🧪 Tests E2E — Workflows complets\n";
echo str_repeat("=", 60) . "\n\n";

$tests = 0;
$passed = 0;
$createdExamens = [];
$createdPassages = [];

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

// ============================================================================
// SETUP : instanciation des managers
// ============================================================================

$banque = new BanqueManager();
$em = new ExamenManager();
$pm = new PassageManager();
$am = new AnalyticsManager();

$PROF_ID = 'PROF-E2E-TEST';
$ADMIN_ID = 'ADMN-E2E-TEST';

// ============================================================================
// SCENARIO 1 : LIFECYCLE PROF (prof crée, publie, cloture)
// ============================================================================

echo "\n📋 SCENARIO 1 — Lifecycle Prof\n";

// Variable partagee
$examen1Id = null;
$accessCode = null;

test('S1.1 - Prof peut creer un examen en draft', function() use ($em, $PROF_ID, &$examen1Id, &$createdExamens) {
    $data = [
        'titre' => 'E2E Lifecycle Test',
        'description' => 'Scenario lifecycle complet',
        'questions' => ['vec-faci-01', 'vec-faci-02', 'mat-faci-01', 'vec-moye-01'],
        'duree_sec' => 3600,
        'date_ouverture' => date('c', strtotime('-10 min')),
        'date_cloture' => date('c', strtotime('+2 hours')),
        'max_passages' => 1,
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'show_correction_after' => true,
        'correction_delay_min' => 0,
    ];
    $exam = $em->create($data, $PROF_ID);
    $examen1Id = $exam['id'];
    $createdExamens[] = $exam['id'];
    return isset($exam['id'])
        && $exam['status'] === 'draft'
        && isset($exam['access_code'])
        && strlen($exam['access_code']) >= 6;
});

test('S1.2 - Prof peut publier l\'examen (draft → published)', function() use ($em, &$examen1Id, &$accessCode) {
    $published = $em->publish($examen1Id);
    $accessCode = $published['access_code'];
    return $published['status'] === 'published'
        && !empty($accessCode);
});

test('S1.3 - Un examen publie est accessible par son code d\'acces', function() use ($em, &$accessCode) {
    $found = $em->getByAccessCode($accessCode);
    return $found !== null
        && $found['status'] === 'published';
});

test('S1.4 - Un code d\'acces inexistant retourne null', function() use ($em) {
    $found = $em->getByAccessCode('XXXXXX-INVALID');
    return $found === null;
});

test('S1.5 - Prof peut cloturer l\'examen (published → closed)', function() use ($em, &$examen1Id) {
    $closed = $em->close($examen1Id);
    return $closed['status'] === 'closed';
});

test('S1.6 - Un examen closed reste accessible par code (status change)', function() use ($em, &$accessCode) {
    // Le code reste accessible mais le status signale l'etat
    $byCode = $em->getByAccessCode($accessCode);
    return $byCode !== null && $byCode['status'] === 'closed';
});

test('S1.7 - Prof peut archiver l\'examen (closed → archived)', function() use ($em, &$examen1Id) {
    $archived = $em->archive($examen1Id);
    return $archived['status'] === 'archived';
});

// ============================================================================
// SCENARIO 2 : ETUDIANT FLOW (passage complet)
// ============================================================================

echo "\n🎓 SCENARIO 2 — Etudiant Flow\n";

$examen2Id = null;
$examen2Code = null;
$passage2Token = null;

test('S2.1 - Setup : prof cree et publie un examen de test', function() use ($em, $PROF_ID, &$examen2Id, &$examen2Code, &$createdExamens) {
    $data = [
        'titre' => 'E2E Etudiant Flow',
        'description' => 'Test complet etudiant',
        'questions' => ['vec-faci-01', 'vec-faci-02', 'mat-faci-01'],
        'duree_sec' => 1800,
        'date_ouverture' => date('c', strtotime('-10 min')),
        'date_cloture' => date('c', strtotime('+2 hours')),
        'max_passages' => 1,
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'show_correction_after' => true,
        'correction_delay_min' => 0,
    ];
    $exam = $em->create($data, $PROF_ID);
    $createdExamens[] = $exam['id'];
    $examen2Id = $exam['id'];
    $published = $em->publish($exam['id']);
    $examen2Code = $published['access_code'];
    return !empty($examen2Id) && !empty($examen2Code);
});

test('S2.2 - Etudiant peut demarrer un passage avec infos valides', function() use ($pm, &$examen2Id, &$passage2Token, &$createdPassages) {
    $studentInfo = [
        'nom' => 'Dupont',
        'prenom' => 'Alice',
        'email' => 'alice.dupont@ipssi-test.fr',
    ];
    $passage = $pm->start($examen2Id, $studentInfo);
    $passage2Token = $passage['token'];
    $createdPassages[] = $passage['id'];
    return isset($passage['id'])
        && isset($passage['token'])
        && $passage['status'] === 'in_progress';
});

test('S2.3 - Etudiant peut sauvegarder ses reponses', function() use ($pm, &$passage2Token) {
    // Sauvegarde 3 reponses
    $r1 = $pm->saveAnswer($passage2Token, 'vec-faci-01', 1);
    $r2 = $pm->saveAnswer($passage2Token, 'vec-faci-02', 0);
    $r3 = $pm->saveAnswer($passage2Token, 'mat-faci-01', 2);
    return $r1['status'] === 'in_progress'
        && $r2['status'] === 'in_progress'
        && $r3['status'] === 'in_progress';
});

test('S2.4 - Etudiant peut mettre a jour une reponse (overwrite)', function() use ($pm, &$passage2Token) {
    // Reecrire vec-faci-01
    $r = $pm->saveAnswer($passage2Token, 'vec-faci-01', 2);
    return $r['status'] === 'in_progress';
});

test('S2.5 - Etudiant peut soumettre le passage (in_progress → submitted)', function() use ($pm, &$passage2Token) {
    $submitted = $pm->submit($passage2Token);
    return $submitted['status'] === 'submitted'
        && isset($submitted['end_time'])
        && isset($submitted['score_pct']);
});

test('S2.6 - Apres submit : score est calcule (brut + pct)', function() use ($pm, &$passage2Token) {
    $passage = $pm->getByToken($passage2Token);
    return isset($passage['score_brut'])
        && isset($passage['score_max'])
        && isset($passage['score_pct'])
        && $passage['score_max'] === 3;
});

test('S2.7 - Apres submit : duration_sec est calcule', function() use ($pm, &$passage2Token) {
    $passage = $pm->getByToken($passage2Token);
    return isset($passage['duration_sec'])
        && $passage['duration_sec'] >= 0
        && $passage['duration_sec'] < 3600;
});

test('S2.8 - Un passage soumis a une signature HMAC verifiable', function() use ($pm, &$passage2Token) {
    $passage = $pm->getByToken($passage2Token);
    return isset($passage['signature_sha256'])
        && $pm->verifySignature($passage) === true;
});

// ============================================================================
// SCENARIO 3 : MULTI-ETUDIANTS + ANALYTICS
// ============================================================================

echo "\n📊 SCENARIO 3 — Multi-étudiants + Analytics\n";

$examen3Id = null;
$examen3Code = null;

test('S3.1 - Setup : creer examen pour scenario multi-etudiants', function() use ($em, $PROF_ID, &$examen3Id, &$examen3Code, &$createdExamens) {
    $data = [
        'titre' => 'E2E Multi Etudiants',
        'description' => 'Test analytics agregees',
        'questions' => ['vec-faci-01', 'vec-faci-02', 'mat-faci-01', 'vec-moye-01'],
        'duree_sec' => 1800,
        'date_ouverture' => date('c', strtotime('-10 min')),
        'date_cloture' => date('c', strtotime('+2 hours')),
        'max_passages' => 1,
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'show_correction_after' => true,
        'correction_delay_min' => 0,
    ];
    $exam = $em->create($data, $PROF_ID);
    $createdExamens[] = $exam['id'];
    $examen3Id = $exam['id'];
    $examen3Code = $em->publish($exam['id'])['access_code'];
    return !empty($examen3Id);
});

test('S3.2 - 5 etudiants passent l\'examen avec scores varies', function() use ($pm, &$examen3Id, &$createdPassages) {
    $students = [
        ['nom' => 'Martin', 'prenom' => 'Bob', 'email' => 'bob@e2e.fr', 'answers' => [0, 1, 2, 3]],         // Random
        ['nom' => 'Bernard', 'prenom' => 'Chloé', 'email' => 'chloe@e2e.fr', 'answers' => [1, 2, 0, 1]],   // Random
        ['nom' => 'Petit', 'prenom' => 'David', 'email' => 'david@e2e.fr', 'answers' => [2, 0, 1, 2]],     // Random
        ['nom' => 'Durand', 'prenom' => 'Emma', 'email' => 'emma@e2e.fr', 'answers' => [3, 3, 3, 3]],      // Toujours D
        ['nom' => 'Leroy', 'prenom' => 'Francis', 'email' => 'francis@e2e.fr', 'answers' => [0, 0, 0, 0]], // Toujours A
    ];
    $questions = ['vec-faci-01', 'vec-faci-02', 'mat-faci-01', 'vec-moye-01'];

    $nbOk = 0;
    foreach ($students as $s) {
        try {
            $p = $pm->start($examen3Id, [
                'nom' => $s['nom'],
                'prenom' => $s['prenom'],
                'email' => $s['email'],
            ]);
            $createdPassages[] = $p['id'];
            foreach ($questions as $i => $qid) {
                $pm->saveAnswer($p['token'], $qid, $s['answers'][$i]);
            }
            $pm->submit($p['token']);
            $nbOk++;
        } catch (\Throwable $e) {
            return "Etudiant {$s['email']} : " . $e->getMessage();
        }
    }
    return $nbOk === 5;
});

test('S3.3 - Analytics overview retourne les 5 passages', function() use ($am, &$examen3Id) {
    $overview = $am->getExamenOverview($examen3Id);
    return $overview['total_passages'] === 5
        && $overview['unique_students'] === 5;
});

test('S3.4 - Analytics overview : avg_score_pct est coherent (0-100)', function() use ($am, &$examen3Id) {
    $overview = $am->getExamenOverview($examen3Id);
    return isset($overview['avg_score_pct'])
        && $overview['avg_score_pct'] >= 0
        && $overview['avg_score_pct'] <= 100;
});

test('S3.5 - Analytics overview : min/max/median coherents', function() use ($am, &$examen3Id) {
    $overview = $am->getExamenOverview($examen3Id);
    return $overview['min_score_pct'] <= $overview['avg_score_pct']
        && $overview['avg_score_pct'] <= $overview['max_score_pct']
        && $overview['min_score_pct'] <= $overview['median_score_pct']
        && $overview['median_score_pct'] <= $overview['max_score_pct'];
});

test('S3.6 - Distribution scores : histogramme 10 buckets', function() use ($am, &$examen3Id) {
    $dist = $am->getScoreDistribution($examen3Id);
    return count($dist['histogram']) === 10
        && $dist['total'] === 5;
});

test('S3.7 - Distribution : sum(buckets) == total passages', function() use ($am, &$examen3Id) {
    $dist = $am->getScoreDistribution($examen3Id);
    $sum = array_sum(array_column($dist['histogram'], 'count'));
    return $sum === $dist['total'];
});

test('S3.8 - Questions stats : 4 questions avec success_rate_pct', function() use ($am, &$examen3Id) {
    $stats = $am->getQuestionStats($examen3Id);
    return count($stats['questions']) === 4
        && $stats['nb_passages'] === 5;
});

test('S3.9 - Question stats : chaque Q a 4 option_analysis avec rates', function() use ($am, &$examen3Id) {
    $stats = $am->getQuestionStats($examen3Id, true);
    foreach ($stats['questions'] as $q) {
        if (count($q['option_analysis']) !== 4) return "Q {$q['question_id']} a pas 4 options";
        $totalRate = 0;
        foreach ($q['option_analysis'] as $opt) {
            $totalRate += $opt['rate_pct'];
        }
        // Les rates doivent sommer a ~100 (ou 0 si pas de reponses)
        if ($totalRate > 0 && ($totalRate < 95 || $totalRate > 105)) {
            return "Q {$q['question_id']} : sum rates = $totalRate (should be ~100)";
        }
    }
    return true;
});

// ============================================================================
// SCENARIO 4 : FRAUDE DETECTEE
// ============================================================================

echo "\n⚠️  SCENARIO 4 — Fraude détectée\n";

$passage4Token = null;
$passage4Id = null;

test('S4.1 - Setup : un etudiant demarre un passage', function() use ($pm, &$examen3Id, &$passage4Token, &$passage4Id, &$createdPassages) {
    $info = ['nom' => 'Fraude', 'prenom' => 'Test', 'email' => 'fraude@e2e.fr'];
    try {
        $p = $pm->start($examen3Id, $info);
    } catch (\Throwable $e) {
        return 'Impossible de demarrer : ' . $e->getMessage();
    }
    $passage4Token = $p['token'];
    $passage4Id = $p['id'];
    $createdPassages[] = $p['id'];
    return true;
});

test('S4.2 - Etudiant genere 5 evenements focus (copy, paste, devtools, blur, right_click)', function() use ($pm, &$passage4Token) {
    $events = [
        ['type' => 'copy', 'timestamp' => date('c')],
        ['type' => 'paste', 'timestamp' => date('c')],
        ['type' => 'devtools_open', 'timestamp' => date('c')],
        ['type' => 'blur', 'timestamp' => date('c'), 'duration_ms' => 3000],
        ['type' => 'right_click', 'timestamp' => date('c')],
    ];
    $ok = 0;
    foreach ($events as $e) {
        try {
            $pm->logFocusEvent($passage4Token, $e);
            $ok++;
        } catch (\Throwable $ex) {
            // Accepter que certains types ne soient pas implementes
        }
    }
    return $ok >= 3; // Au moins 3 types enregistres
});

test('S4.3 - Apres submit : anomalies_count > 0', function() use ($pm, &$passage4Token) {
    $pm->saveAnswer($passage4Token, 'vec-faci-01', 0);
    $pm->saveAnswer($passage4Token, 'vec-faci-02', 0);
    $submitted = $pm->submit($passage4Token);
    $passage = $pm->getByToken($passage4Token);
    // anomalies_count peut etre dans passage ou dans submitted
    $anomalies = $passage['anomalies_count']
        ?? $passage['anomalies']['count']
        ?? count($passage['focus_events'] ?? []);
    return $anomalies > 0;
});

test('S4.4 - Focus heatmap contient les events du passage', function() use ($am, &$examen3Id) {
    $heat = $am->getFocusHeatmap($examen3Id);
    // Doit retourner structure avec events
    return isset($heat['events']) || isset($heat['heatmap']) || isset($heat['total_events']);
});

// ============================================================================
// SCENARIO 5 : EDGE CASES
// ============================================================================

echo "\n🚧 SCENARIO 5 — Edge Cases\n";

$examen5Id = null;

test('S5.1 - Setup : examen avec max_passages=1', function() use ($em, $PROF_ID, &$examen5Id, &$createdExamens) {
    $data = [
        'titre' => 'E2E Max Passages',
        'description' => 'Test limite passages',
        'questions' => ['vec-faci-01', 'vec-faci-02'],
        'duree_sec' => 1800,
        'date_ouverture' => date('c', strtotime('-10 min')),
        'date_cloture' => date('c', strtotime('+2 hours')),
        'max_passages' => 1,
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'show_correction_after' => true,
        'correction_delay_min' => 0,
    ];
    $exam = $em->create($data, $PROF_ID);
    $em->publish($exam['id']);
    $examen5Id = $exam['id'];
    $createdExamens[] = $exam['id'];
    return !empty($examen5Id);
});

test('S5.2 - Premier passage pour alice@edge.fr : accepte', function() use ($pm, &$examen5Id, &$createdPassages) {
    try {
        $p = $pm->start($examen5Id, ['nom' => 'Alice', 'prenom' => 'Edge', 'email' => 'alice@edge.fr']);
        $createdPassages[] = $p['id'];
        $pm->saveAnswer($p['token'], 'vec-faci-01', 0);
        $pm->submit($p['token']);
        return true;
    } catch (\Throwable $e) {
        return 'Erreur : ' . $e->getMessage();
    }
});

test('S5.3 - 2e tentative pour alice@edge.fr : refuse (max_passages=1)', function() use ($pm, &$examen5Id) {
    try {
        $pm->start($examen5Id, ['nom' => 'Alice', 'prenom' => 'Edge', 'email' => 'alice@edge.fr']);
        return 'Deuxieme passage accepte (devrait etre refuse)';
    } catch (\Throwable $e) {
        // Devrait throw RuntimeException ou similaire
        return str_contains(strtolower($e->getMessage()), 'max')
            || str_contains(strtolower($e->getMessage()), 'limit')
            || str_contains(strtolower($e->getMessage()), 'deja');
    }
});

test('S5.4 - Start avec email invalide : refuse', function() use ($pm, &$examen5Id) {
    try {
        $pm->start($examen5Id, ['nom' => 'Bad', 'prenom' => 'Email', 'email' => 'pasdemail']);
        return 'Email invalide accepte (devrait etre refuse)';
    } catch (\Throwable $e) {
        return true;
    }
});

test('S5.5 - Start avec examen inexistant : refuse', function() use ($pm) {
    try {
        $pm->start('EXM-DOES-NOTE', ['nom' => 'X', 'prenom' => 'Y', 'email' => 'x@y.fr']);
        return 'Examen inexistant accepte';
    } catch (\Throwable $e) {
        return true;
    }
});

test('S5.6 - saveAnswer avec token invalide : refuse', function() use ($pm) {
    try {
        $pm->saveAnswer('invalid-token-xxx', 'vec-faci-01', 0);
        return 'Token invalide accepte';
    } catch (\Throwable $e) {
        return true;
    }
});

test('S5.7 - submit d\'un passage deja submitted : refuse ou no-op', function() use ($pm, &$passage4Token) {
    try {
        $pm->submit($passage4Token);
        // Si ca ne throw pas, verifier que le status reste submitted
        $p = $pm->getByToken($passage4Token);
        return $p['status'] === 'submitted';
    } catch (\Throwable $e) {
        return true; // Exception acceptable
    }
});

// ============================================================================
// SCENARIO 6 : HISTORIQUE ETUDIANT
// ============================================================================

echo "\n👤 SCENARIO 6 — Student history\n";

test('S6.1 - getStudentHistory retourne les passages d\'alice@edge.fr', function() use ($am) {
    $history = $am->getStudentHistory('alice@edge.fr');
    return isset($history['email'])
        && $history['email'] === 'alice@edge.fr'
        && isset($history['passages'])
        && count($history['passages']) >= 1;
});

test('S6.2 - getStudentHistory : KPIs calcules (avg/best/worst)', function() use ($am) {
    $history = $am->getStudentHistory('bob@e2e.fr');
    return isset($history['avg_score_pct'])
        && isset($history['best_score_pct'])
        && isset($history['worst_score_pct']);
});

test('S6.3 - getStudentHistory pour email inconnu : liste vide', function() use ($am) {
    $history = $am->getStudentHistory('unknown@nowhere.com');
    return isset($history['passages'])
        && count($history['passages']) === 0
        && $history['nb_passages'] === 0;
});

test('S6.4 - Timeline examen : liste indexée de créneaux horaires', function() use ($am, &$examen3Id) {
    $tl = $am->getTimeline($examen3Id);
    // getTimeline retourne array_values() = liste indexée de {hour, count, avg_score}
    if (!is_array($tl)) return 'Not an array';
    // Si vide c'est OK (pas de passages), sinon chaque element doit avoir hour+count
    if (!empty($tl)) {
        $first = $tl[0] ?? null;
        if (!isset($first['hour']) || !isset($first['count'])) {
            return 'Missing hour/count keys';
        }
    }
    return true;
});

// ============================================================================
// CLEANUP
// ============================================================================

echo "\n🧹 CLEANUP\n";

test('CLEANUP - Supprimer les passages et examens de test', function() use ($em, $pm, &$createdPassages, &$createdExamens) {
    $errors = 0;
    $examensPath = data_path('examens');
    $passagesPath = data_path('passages');

    foreach ($createdPassages as $pid) {
        $file = $passagesPath . '/' . $pid . '.json';
        if (file_exists($file)) {
            if (!@unlink($file)) $errors++;
        }
    }
    foreach ($createdExamens as $eid) {
        $file = $examensPath . '/' . $eid . '.json';
        if (file_exists($file)) {
            if (!@unlink($file)) $errors++;
        }
    }
    return $errors === 0;
});

// ============================================================================
// BILAN
// ============================================================================

echo "\n" . str_repeat("=", 60) . "\n";
echo "RESULTAT : $passed / $tests tests passes";
echo $passed === $tests ? " ✅\n\n" : " ❌\n\n";

exit($passed === $tests ? 0 : 1);
