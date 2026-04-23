<?php
/**
 * test_banque_manager.php — Test CLI du BanqueManager
 *
 * Usage : php backend/test_banque_manager.php
 * (Script temporaire à supprimer après validation P4)
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Examens\Lib\BanqueManager;

echo "🧪 Test BanqueManager\n";
echo str_repeat("=", 60) . "\n\n";

$bm = new BanqueManager();
$tests = 0;
$passed = 0;

function test(string $name, callable $fn): void {
    global $tests, $passed;
    $tests++;
    echo "  [$tests] $name ... ";
    try {
        $result = $fn();
        if ($result === true || $result === null) {
            echo "✅\n";
            $passed++;
        } else {
            echo "❌ (résultat : " . var_export($result, true) . ")\n";
        }
    } catch (\Throwable $e) {
        echo "❌ EXCEPTION : " . $e->getMessage() . "\n";
    }
}

// ========== TESTS LECTURE ==========
echo "\n📖 LECTURE\n";

test('listModules() retourne un tableau non vide', function() use ($bm) {
    $modules = $bm->listModules();
    return is_array($modules) && count($modules) >= 1;
});

test('listModules() contient maths-ia', function() use ($bm) {
    return in_array('maths-ia', $bm->listModules(), true);
});

test('listChapitres(maths-ia) retourne 4 chapitres (j1-j4)', function() use ($bm) {
    $c = $bm->listChapitres('maths-ia');
    return count($c) === 4 && in_array('j1-representation', $c, true);
});

test('listThemes(maths-ia, j1-representation) retourne 4 thèmes', function() use ($bm) {
    return count($bm->listThemes('maths-ia', 'j1-representation')) === 4;
});

test('getTheme(maths-ia, j1, vecteurs) contient 20 questions', function() use ($bm) {
    $t = $bm->getTheme('maths-ia', 'j1-representation', 'vecteurs');
    return $t !== null && count($t['questions']) === 20;
});

test('getTheme(inexistant) retourne null', function() use ($bm) {
    return $bm->getTheme('fake', 'fake', 'fake') === null;
});

// ========== TESTS CRUD ==========
echo "\n📝 CRUD\n";

test('listQuestions() retourne 320 questions', function() use ($bm) {
    return count($bm->listQuestions()) === 320;
});

test('listQuestions({difficulte: facile}) retourne 80 questions', function() use ($bm) {
    $r = $bm->listQuestions(['difficulte' => 'facile']);
    return count($r) === 80;
});

test('listQuestions({module, difficulte: expert}) filtre correctement', function() use ($bm) {
    $r = $bm->listQuestions(['module' => 'maths-ia', 'difficulte' => 'expert']);
    return count($r) === 80;
});

test('listQuestions({type: code}) retourne seulement les questions code', function() use ($bm) {
    $r = $bm->listQuestions(['type' => 'code']);
    return count($r) >= 5 && count($r) <= 20
        && count(array_filter($r, fn($q) => $q['type'] === 'code')) === count($r);
});

test('getQuestion(vec-faci-01) retourne la bonne question', function() use ($bm) {
    $q = $bm->getQuestion('vec-faci-01');
    return $q !== null
        && $q['id'] === 'vec-faci-01'
        && $q['difficulte'] === 'facile'
        && $q['_theme'] === 'vecteurs';
});

test('getQuestion(inexistant) retourne null', function() use ($bm) {
    return $bm->getQuestion('fake-fake-99') === null;
});

// ========== TESTS TIRAGE ==========
echo "\n🎲 TIRAGE ALÉATOIRE\n";

test('drawRandom avec quotas personnalisés retourne le bon nombre', function() use ($bm) {
    $r = $bm->drawRandom(
        ['module' => 'maths-ia', 'chapitre' => 'j1-representation'],
        ['facile' => 2, 'moyen' => 3, 'difficile' => 1, 'expert' => 1],
        42 // seed
    );
    return count($r) === 7;
});

test('drawRandom respecte les quotas par niveau', function() use ($bm) {
    $r = $bm->drawRandom(
        ['module' => 'maths-ia'],
        ['facile' => 5, 'moyen' => 3, 'difficile' => 2, 'expert' => 0],
        123
    );
    $counts = ['facile' => 0, 'moyen' => 0, 'difficile' => 0, 'expert' => 0];
    foreach ($r as $q) {
        $counts[$q['difficulte']]++;
    }
    return $counts === ['facile' => 5, 'moyen' => 3, 'difficile' => 2, 'expert' => 0];
});

test('drawRandom avec quota impossible lève exception', function() use ($bm) {
    try {
        $bm->drawRandom(['theme' => 'vecteurs'], ['facile' => 99]);
        return false;
    } catch (\InvalidArgumentException $e) {
        return true;
    }
});

test('drawEquitable(20) tire 5 par niveau', function() use ($bm) {
    $r = $bm->drawEquitable(['module' => 'maths-ia'], 20, 456);
    $counts = array_fill_keys(['facile', 'moyen', 'difficile', 'expert'], 0);
    foreach ($r as $q) $counts[$q['difficulte']]++;
    return $counts === ['facile' => 5, 'moyen' => 5, 'difficile' => 5, 'expert' => 5];
});

test('drawRandom avec seed donne résultats reproductibles', function() use ($bm) {
    $r1 = $bm->drawRandom(['module' => 'maths-ia'], ['facile' => 3], 789);
    $r2 = $bm->drawRandom(['module' => 'maths-ia'], ['facile' => 3], 789);
    // Même seed → mêmes IDs (mais peut-être pas même ordre à cause du shuffle final)
    $ids1 = array_column($r1, 'id');
    $ids2 = array_column($r2, 'id');
    sort($ids1);
    sort($ids2);
    return $ids1 === $ids2;
});

// ========== TESTS RECHERCHE ==========
echo "\n🔍 RECHERCHE\n";

test('searchQuestions(gradient) trouve des résultats', function() use ($bm) {
    $r = $bm->searchQuestions('gradient');
    return count($r) >= 10; // beaucoup de questions parlent de gradient
});

test('searchQuestions retourne triés par score', function() use ($bm) {
    $r = $bm->searchQuestions('sigmoïde');
    if (count($r) < 2) return false;
    // Vérifier tri décroissant
    for ($i = 0; $i < count($r) - 1; $i++) {
        if ($r[$i]['_score'] < $r[$i+1]['_score']) return false;
    }
    return true;
});

test('searchQuestions(inexistant) retourne vide', function() use ($bm) {
    return count($bm->searchQuestions('xyzabcinexistant')) === 0;
});

// ========== TESTS VALIDATION ==========
echo "\n✔️  VALIDATION\n";

test('validateTheme(vecteurs) est valide', function() use ($bm) {
    $r = $bm->validateTheme('maths-ia', 'j1-representation', 'vecteurs');
    return $r['valid'] === true && empty($r['errors']);
});

test('validateTheme retourne les bonnes stats', function() use ($bm) {
    $r = $bm->validateTheme('maths-ia', 'j1-representation', 'vecteurs');
    return $r['stats']['total'] === 20
        && $r['stats']['by_level']['facile'] === 5;
});

test('validateQuestion({}) détecte tous les champs manquants', function() use ($bm) {
    $errors = $bm->validateQuestion([]);
    return count($errors) >= 11; // 11 champs requis
});

test('validateQuestion(id invalide) détecte le problème', function() use ($bm) {
    $errors = $bm->validateQuestion([
        'id' => 'bad_id',
        'enonce' => 'test',
        'options' => ['a','b','c','d'],
        'correct' => 0,
        'difficulte' => 'facile',
        'type' => 'conceptuel',
        'tags' => [],
        'hint' => 'x',
        'explanation' => 'y',
        'traps' => 'z',
        'references' => 'w',
    ]);
    return count($errors) >= 1
        && strpos(implode('|', $errors), 'ID invalide') !== false;
});

test('validateQuestion(options < 4) détecte le problème', function() use ($bm) {
    $errors = $bm->validateQuestion([
        'id' => 'tst-faci-01',
        'enonce' => 'test',
        'options' => ['a','b','c'], // seulement 3
        'correct' => 0,
        'difficulte' => 'facile',
        'type' => 'conceptuel',
        'tags' => [],
        'hint' => 'x', 'explanation' => 'y', 'traps' => 'z', 'references' => 'w',
    ]);
    return count($errors) >= 1;
});

// ========== TESTS STATS ==========
echo "\n📊 STATS GLOBALES\n";

test('getStats() retourne 320 questions au total', function() use ($bm) {
    $s = $bm->getStats();
    return $s['total_questions'] === 320;
});

test('getStats() retourne 80 par niveau', function() use ($bm) {
    $s = $bm->getStats();
    return $s['by_level']['facile'] === 80
        && $s['by_level']['moyen'] === 80
        && $s['by_level']['difficile'] === 80
        && $s['by_level']['expert'] === 80;
});

test('getStats() contient la hiérarchie complète', function() use ($bm) {
    $s = $bm->getStats();
    return count($s['modules']) === 1
        && $s['modules'][0]['module'] === 'maths-ia'
        && count($s['modules'][0]['chapitres']) === 4;
});

// ========== BILAN ==========
echo "\n" . str_repeat("=", 60) . "\n";
echo "RÉSULTAT : $passed / $tests tests passés";
if ($passed === $tests) {
    echo " ✅\n\n";
    exit(0);
} else {
    echo " ❌\n\n";
    exit(1);
}
