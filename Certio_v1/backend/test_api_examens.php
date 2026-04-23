<?php
/**
 * test_api_examens.php — Tests intégration ExamenManager + scenarios API
 *
 * Simule les operations qui seraient faites par l'API :
 *   - Creer un prof + admin de test
 *   - Creer examens avec le prof
 *   - Verifier les regles d'auth (prof != admin pour lister tous)
 *   - Verifier by-code avec bon status/dates
 *   - Transitions de cycle de vie
 *
 * Usage : php backend/test_api_examens.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Examens\Lib\ExamenManager;
use Examens\Lib\Auth;

echo "🧪 Test API Examens (scenarios)\n";
echo str_repeat("=", 60) . "\n\n";

$em = new ExamenManager();
$tests = 0;
$passed = 0;
$createdIds = [];

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

// ========== SCENARIO 1 : Creation basique ==========
echo "\n📝 CREATION\n";

$profId = 'PROF-API1-TEST';
$adminId = 'ADMN-API1-TEST';

$baseData = [
    'titre' => 'Examen API Test',
    'description' => 'Test scenario API',
    'questions' => ['vec-faci-01', 'vec-faci-02', 'mat-faci-01'],
    'duree_sec' => 1800,
    'date_ouverture' => date('c', strtotime('+1 hour')),
    'date_cloture' => date('c', strtotime('+3 hours')),
];

test('Prof cree un examen', function() use ($em, $baseData, $profId, &$createdIds) {
    $e = $em->create($baseData, $profId);
    $createdIds[] = $e['id'];
    return $e['created_by'] === $profId && $e['status'] === 'draft';
});

test('Admin cree un examen', function() use ($em, $baseData, $adminId, &$createdIds) {
    $e = $em->create(array_merge($baseData, ['titre' => 'Examen Admin']), $adminId);
    $createdIds[] = $e['id'];
    return $e['created_by'] === $adminId;
});

// ========== SCENARIO 2 : Filtrage par ownership ==========
echo "\n🔒 OWNERSHIP\n";

test('list() avec filter created_by=prof retourne ses examens', function() use ($em, $profId) {
    $list = $em->list(['created_by' => $profId]);
    foreach ($list as $e) {
        if ($e['created_by'] !== $profId) return false;
    }
    return count($list) >= 1;
});

test('list() avec filter created_by=admin retourne ses examens', function() use ($em, $adminId) {
    $list = $em->list(['created_by' => $adminId]);
    foreach ($list as $e) {
        if ($e['created_by'] !== $adminId) return false;
    }
    return count($list) >= 1;
});

test('list() sans filter retourne TOUS les examens (simule admin)', function() use ($em, $profId, $adminId) {
    $list = $em->list();
    $hasBoth = false;
    $foundProf = false;
    $foundAdmin = false;
    foreach ($list as $e) {
        if ($e['created_by'] === $profId) $foundProf = true;
        if ($e['created_by'] === $adminId) $foundAdmin = true;
    }
    return $foundProf && $foundAdmin;
});

// ========== SCENARIO 3 : By-code ==========
echo "\n🎟️  BY-CODE\n";

test('getByAccessCode() sur draft retourne mais API doit bloquer', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    $e = $em->get($createdIds[0]);
    $found = $em->getByAccessCode($e['access_code']);
    // Le manager retourne l'examen, c'est l'API qui verifie le status
    return $found !== null && $found['status'] === 'draft';
});

test('getByAccessCode() avec code inexistant retourne null', function() use ($em) {
    return $em->getByAccessCode('ZZZZZZ') === null;
});

// ========== SCENARIO 4 : Cycle de vie ==========
echo "\n🔄 CYCLE DE VIE\n";

test('publish() draft -> published', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    $pub = $em->publish($createdIds[0]);
    return $pub['status'] === 'published';
});

test('close() published -> closed', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    $closed = $em->close($createdIds[0]);
    return $closed['status'] === 'closed';
});

test('archive() closed -> archived', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    $arch = $em->archive($createdIds[0]);
    return $arch['status'] === 'archived';
});

// ========== SCENARIO 5 : Scenario API complet (creation + publication + by-code) ==========
echo "\n🎯 SCENARIO COMPLET\n";

test('Prof cree examen + publie + etudiant y accede via code', function() use ($em, $baseData, $profId, &$createdIds) {
    // Prof cree
    $e = $em->create(array_merge($baseData, ['titre' => 'Scenario complet']), $profId);
    $createdIds[] = $e['id'];
    $code = $e['access_code'];

    // Prof publie
    $em->publish($e['id']);

    // Etudiant cherche par code
    $found = $em->getByAccessCode($code);
    if ($found === null) return 'Etudiant ne trouve pas l\'examen';
    if ($found['status'] !== 'published') return 'Status incorrect';

    // Construire la version publique (ce que l'API renverrait)
    $public = [
        'id' => $found['id'],
        'titre' => $found['titre'],
        'duree_sec' => $found['duree_sec'],
        'nb_questions' => count($found['questions']),
        'access_code' => $found['access_code'],
    ];

    return isset($public['id'])
        && $public['nb_questions'] === 3
        && !isset($public['questions']); // pas de liste de questions dans la version publique
});

// ========== Cleanup ==========
echo "\n🧹 CLEANUP\n";

foreach ($createdIds as $id) {
    try {
        $e = $em->get($id);
        if ($e === null) continue;
        $path = data_path('examens') . '/' . $id . '.json';
        if (file_exists($path)) {
            @unlink($path);
            echo "  Supprime : $id\n";
        }
    } catch (\Throwable $err) {
        echo "  ⚠️ Erreur cleanup $id\n";
    }
}

// ========== BILAN ==========
echo "\n" . str_repeat("=", 60) . "\n";
echo "RESULTAT : $passed / $tests tests passes";
echo $passed === $tests ? " ✅\n\n" : " ❌\n\n";

exit($passed === $tests ? 0 : 1);
