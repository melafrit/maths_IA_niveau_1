<?php
/**
 * test_examen_manager.php — Tests CLI du ExamenManager
 *
 * Usage : php backend/test_examen_manager.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Examens\Lib\ExamenManager;

echo "🧪 Test ExamenManager\n";
echo str_repeat("=", 60) . "\n\n";

$em = new ExamenManager();
$tests = 0;
$passed = 0;
$createdIds = []; // pour cleanup

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

// ========== SETUP ==========
// Utiliser des IDs de questions existantes de la banque
$sampleQuestions = [
    'vec-faci-01', 'vec-faci-02', 'vec-moye-01',
    'mat-faci-01', 'mat-moye-01',
];

$sampleExamData = [
    'titre' => 'Examen de test',
    'description' => 'Description de test',
    'questions' => $sampleQuestions,
    'duree_sec' => 1800, // 30 min
    'date_ouverture' => date('c', strtotime('+1 hour')),
    'date_cloture' => date('c', strtotime('+2 hours')),
];

$createdBy = 'PROF-TEST-0001';

// ========== TESTS CRÉATION ==========
echo "\n📝 CRÉATION\n";

test('create() avec données valides', function() use ($em, $sampleExamData, $createdBy, &$createdIds) {
    $e = $em->create($sampleExamData, $createdBy);
    $createdIds[] = $e['id'];
    return isset($e['id']) && str_starts_with($e['id'], 'EXM-');
});

test('create() avec titre vide → exception', function() use ($em, $sampleExamData, $createdBy) {
    try {
        $em->create(array_merge($sampleExamData, ['titre' => '']), $createdBy);
        return false;
    } catch (\InvalidArgumentException $e) {
        return strpos($e->getMessage(), 'Titre') !== false;
    }
});

test('create() avec durée trop courte (<60s) → exception', function() use ($em, $sampleExamData, $createdBy) {
    try {
        $em->create(array_merge($sampleExamData, ['duree_sec' => 30]), $createdBy);
        return false;
    } catch (\InvalidArgumentException $e) {
        // Message en unicode JSON (Durée → Dur\u00e9e)
        return strpos($e->getMessage(), 'courte') !== false;
    }
});

test('create() avec question inexistante → exception', function() use ($em, $sampleExamData, $createdBy) {
    try {
        $em->create(
            array_merge($sampleExamData, ['questions' => ['fake-fake-99']]),
            $createdBy
        );
        return false;
    } catch (\InvalidArgumentException $e) {
        return strpos($e->getMessage(), 'inexistantes') !== false;
    }
});

test('create() génère un ID unique EXM-XXXX-XXXX', function() use ($em, $sampleExamData, $createdBy, &$createdIds) {
    $e1 = $em->create($sampleExamData, $createdBy);
    $e2 = $em->create($sampleExamData, $createdBy);
    $createdIds[] = $e1['id'];
    $createdIds[] = $e2['id'];
    return $e1['id'] !== $e2['id']
        && preg_match('/^EXM-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $e1['id']);
});

test('create() génère un access_code 6 chars', function() use ($em, $sampleExamData, $createdBy, &$createdIds) {
    $e = $em->create($sampleExamData, $createdBy);
    $createdIds[] = $e['id'];
    return isset($e['access_code'])
        && strlen($e['access_code']) === 6
        && preg_match('/^[A-Z0-9]+$/', $e['access_code']);
});

test('create() status initial = draft', function() use ($em, $sampleExamData, $createdBy, &$createdIds) {
    $e = $em->create($sampleExamData, $createdBy);
    $createdIds[] = $e['id'];
    return $e['status'] === 'draft';
});

// ========== TESTS LECTURE ==========
echo "\n📖 LECTURE\n";

test('get() retourne un examen créé', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    $e = $em->get($createdIds[0]);
    return $e !== null && $e['id'] === $createdIds[0];
});

test('get() avec ID inexistant → null', function() use ($em) {
    return $em->get('EXM-FAKE-FAKE') === null;
});

test('list() retourne tous les examens', function() use ($em, &$createdIds) {
    $all = $em->list();
    return is_array($all) && count($all) >= count($createdIds);
});

test('list({created_by}) filtre correctement', function() use ($em, $createdBy, &$createdIds) {
    $filtered = $em->list(['created_by' => $createdBy]);
    return count($filtered) === count($createdIds);
});

test('list({status: draft}) filtre correctement', function() use ($em) {
    $drafts = $em->list(['status' => 'draft']);
    foreach ($drafts as $e) {
        if ($e['status'] !== 'draft') return false;
    }
    return true;
});

test('getByAccessCode() fonctionne', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    $e = $em->get($createdIds[0]);
    $found = $em->getByAccessCode($e['access_code']);
    return $found !== null && $found['id'] === $e['id'];
});

test('getByAccessCode() avec code invalide → null', function() use ($em) {
    return $em->getByAccessCode('ZZZZZZ') === null;
});

// ========== TESTS UPDATE ==========
echo "\n✏️  UPDATE\n";

test('update() en status draft → OK', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    $id = $createdIds[0];
    $updated = $em->update($id, ['titre' => 'Titre modifié']);
    return $updated['titre'] === 'Titre modifié';
});

test('update() champ immuable (id) → OK mais ignoré', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    $id = $createdIds[0];
    $updated = $em->update($id, ['id' => 'EXM-HACK-HACK']);
    return $updated['id'] === $id; // ID inchangé
});

test('update() avec durée invalide → exception', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    try {
        $em->update($createdIds[0], ['duree_sec' => 10]);
        return false;
    } catch (\InvalidArgumentException $e) {
        return true;
    }
});

// ========== TESTS CYCLE DE VIE ==========
echo "\n🔄 CYCLE DE VIE\n";

test('publish() en draft → published', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    $id = $createdIds[0];
    $published = $em->publish($id);
    return $published['status'] === 'published';
});

test('publish() sur published → exception', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    try {
        $em->publish($createdIds[0]);
        return false;
    } catch (\InvalidArgumentException $e) {
        return true;
    }
});

test('update() champ non-autorisé en published → exception', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    try {
        $em->update($createdIds[0], ['questions' => ['vec-faci-01']]);
        return false;
    } catch (\InvalidArgumentException $e) {
        return strpos($e->getMessage(), 'non modifiable') !== false;
    }
});

test('update() titre en published → OK', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    $updated = $em->update($createdIds[0], ['titre' => 'Titre published']);
    return $updated['titre'] === 'Titre published';
});

test('close() en published → closed', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    $closed = $em->close($createdIds[0]);
    return $closed['status'] === 'closed';
});

test('archive() en closed → archived', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    $archived = $em->archive($createdIds[0]);
    return $archived['status'] === 'archived';
});

test('delete() en status ≠ draft → exception', function() use ($em, &$createdIds) {
    if (empty($createdIds)) return false;
    try {
        $em->delete($createdIds[0]);
        return false;
    } catch (\InvalidArgumentException $e) {
        return true;
    }
});

// ========== TESTS STATS ==========
echo "\n📊 STATS\n";

test('getStats() retourne les bons totaux', function() use ($em, &$createdIds) {
    $stats = $em->getStats();
    return isset($stats['total'])
        && isset($stats['by_status'])
        && isset($stats['by_owner'])
        && $stats['total'] >= count($createdIds);
});

test('getStats() by_status contient les 4 niveaux', function() use ($em) {
    $stats = $em->getStats();
    return isset($stats['by_status']['draft'])
        && isset($stats['by_status']['published'])
        && isset($stats['by_status']['closed'])
        && isset($stats['by_status']['archived']);
});

// ========== CLEANUP ==========
echo "\n🧹 CLEANUP\n";

foreach ($createdIds as $id) {
    $path = null;
    try {
        $e = $em->get($id);
        if ($e === null) continue;

        // Pour les examens autres que draft, on archive ou on supprime le fichier direct
        if ($e['status'] === 'draft') {
            $em->delete($id);
            echo "  Supprimé draft : $id\n";
        } else {
            // Suppression directe du fichier
            $path = data_path('examens') . '/' . $id . '.json';
            if (file_exists($path)) {
                @unlink($path);
                echo "  Supprimé ($e[status]) : $id\n";
            }
        }
    } catch (\Throwable $err) {
        echo "  ⚠️ Erreur cleanup $id : {$err->getMessage()}\n";
    }
}

// ========== BILAN ==========
echo "\n" . str_repeat("=", 60) . "\n";
echo "RÉSULTAT : $passed / $tests tests passés";
echo $passed === $tests ? " ✅\n\n" : " ❌\n\n";

exit($passed === $tests ? 0 : 1);
