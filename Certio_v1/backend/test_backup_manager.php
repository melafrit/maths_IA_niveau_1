<?php
/**
 * test_backup_manager.php — Tests de BackupManager
 *
 * Usage : php backend/test_backup_manager.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Examens\Lib\BackupManager;

echo "🧪 Test BackupManager\n";
echo str_repeat("=", 60) . "\n\n";

$tests = 0;
$passed = 0;
$createdBackupIds = [];

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

$bm = new BackupManager();

// ============================================================================
// TESTS BASIQUES
// ============================================================================

echo "\n📦 BASIQUES\n";

test('list() retourne un array (meme vide)', function() use ($bm) {
    $result = $bm->list();
    return is_array($result);
});

test('list() retourne des infos completes pour chaque backup', function() use ($bm) {
    $backups = $bm->list();
    if (empty($backups)) return true; // Pas de backups existants, OK
    $first = $backups[0];
    $requiredKeys = ['id', 'filename', 'path', 'size', 'size_human', 'created_at', 'hash', 'verified'];
    foreach ($requiredKeys as $k) {
        if (!array_key_exists($k, $first)) return "Missing key : $k";
    }
    return true;
});

test('list() trie par date decroissante (plus recent d\'abord)', function() use ($bm) {
    $backups = $bm->list();
    if (count($backups) < 2) return true; // Pas assez pour tester
    for ($i = 0; $i < count($backups) - 1; $i++) {
        if (strcmp($backups[$i]['created_at'], $backups[$i + 1]['created_at']) < 0) {
            return 'Pas trie par date decroissante';
        }
    }
    return true;
});

test('getStats() sur liste vide retourne structure correcte', function() use ($bm) {
    $stats = $bm->getStats();
    return isset($stats['total_backups'])
        && isset($stats['total_size_bytes'])
        && isset($stats['total_size_human']);
});

// ============================================================================
// TESTS CREATION
// ============================================================================

echo "\n🆕 CREATION BACKUP\n";

test('createBackup() declenche le script et retourne structure correcte', function() use ($bm, &$createdBackupIds) {
    $result = $bm->createBackup(20); // Keep 20 pour eviter rotation aggressive
    $requiredKeys = ['success', 'output', 'backup_id', 'duration_sec'];
    foreach ($requiredKeys as $k) {
        if (!array_key_exists($k, $result)) return "Missing key : $k";
    }
    if ($result['success'] === true && $result['backup_id'] !== null) {
        $createdBackupIds[] = $result['backup_id'];
    }
    return is_bool($result['success']) && is_string($result['output']);
});

test('createBackup() reussit avec keep > 0 (creation effective)', function() use ($bm, &$createdBackupIds) {
    $result = $bm->createBackup(20);
    if ($result['success'] !== true) {
        return 'Creation echouee : ' . substr($result['output'], 0, 200);
    }
    if ($result['backup_id'] === null) return 'backup_id null malgre success';
    $createdBackupIds[] = $result['backup_id'];
    return true;
});

test('createBackup() produit un ID au format attendu', function() use (&$createdBackupIds) {
    if (empty($createdBackupIds)) return true; // Skip si aucune creation
    $id = $createdBackupIds[0];
    return preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{6}$/', $id) === 1;
});

// ============================================================================
// TESTS GET / VERIFY / DELETE
// ============================================================================

echo "\n🔍 GET / VERIFY\n";

test('get() sur ID valide retourne les infos', function() use ($bm, &$createdBackupIds) {
    if (empty($createdBackupIds)) return true;
    $info = $bm->get($createdBackupIds[0]);
    if ($info === null) return 'Backup introuvable';
    return $info['id'] === $createdBackupIds[0];
});

test('get() sur ID invalide retourne null', function() use ($bm) {
    return $bm->get('invalid-id-xxx') === null;
});

test('get() refuse path traversal', function() use ($bm) {
    return $bm->get('../../etc/passwd') === null
        && $bm->get('backup_../../../evil') === null;
});

test('get() sur ID non-existant mais format valide retourne null', function() use ($bm) {
    return $bm->get('backup_9999-99-99_999999') === null;
});

test('verify() sur backup valide retourne valid=true', function() use ($bm, &$createdBackupIds) {
    if (empty($createdBackupIds)) return true;
    $result = $bm->verify($createdBackupIds[0]);
    if (!isset($result['valid'])) return 'Missing valid key';
    return $result['valid'] === true;
});

test('verify() sur ID inexistant retourne erreur', function() use ($bm) {
    $result = $bm->verify('backup_9999-99-99_999999');
    return isset($result['valid'])
        && $result['valid'] === false
        && isset($result['error']);
});

// ============================================================================
// TESTS STATS
// ============================================================================

echo "\n📊 STATS\n";

test('getStats() reflete les backups crees', function() use ($bm, &$createdBackupIds) {
    $stats = $bm->getStats();
    if (empty($createdBackupIds)) {
        // Peut y avoir d'autres backups déjà présents
        return $stats['total_backups'] >= 0;
    }
    return $stats['total_backups'] >= count($createdBackupIds);
});

test('getStats() size_bytes > 0 si backups presents', function() use ($bm) {
    $stats = $bm->getStats();
    if ($stats['total_backups'] === 0) return true; // Pas de backups, OK
    return $stats['total_size_bytes'] > 0;
});

test('getStats() retourne oldest/newest si backups presents', function() use ($bm) {
    $stats = $bm->getStats();
    if ($stats['total_backups'] === 0) return true;
    return $stats['oldest'] !== null && $stats['newest'] !== null;
});

// ============================================================================
// TESTS DELETE
// ============================================================================

echo "\n🗑️ DELETE\n";

test('delete() supprime un backup', function() use ($bm, &$createdBackupIds) {
    if (empty($createdBackupIds)) return true;
    // Prendre le dernier cree (pas le premier car utilise par tests precedents)
    $idToDelete = end($createdBackupIds);
    $ok = $bm->delete($idToDelete);
    if (!$ok) return 'delete retourne false';
    // Verifier que le backup a disparu
    $info = $bm->get($idToDelete);
    if ($info !== null) return 'backup encore present apres delete';
    // Retirer de la liste pour ne pas re-delete au cleanup
    $createdBackupIds = array_filter($createdBackupIds, fn($id) => $id !== $idToDelete);
    return true;
});

test('delete() sur ID inexistant retourne false', function() use ($bm) {
    return $bm->delete('backup_9999-99-99_999999') === false;
});

// ============================================================================
// CLEANUP
// ============================================================================

echo "\n🧹 CLEANUP\n";

test('Cleanup des backups test crees', function() use ($bm, &$createdBackupIds) {
    $errors = 0;
    foreach ($createdBackupIds as $id) {
        $info = $bm->get($id);
        if ($info !== null) {
            if (!$bm->delete($id)) $errors++;
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
