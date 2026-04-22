# 🧪 Documentation des tests — IPSSI Examens

Guide complet pour lancer, écrire et comprendre les tests de la plateforme.

> **Framework** : PHP natif (pas de PHPUnit/autres dépendances)
> **Harness** : `backend/tests/run_all.php`
> **Philosophie** : tests rapides, isolés, lisibles

---

## 🎯 Lancer les tests

### Harness unifié (recommandé)

```bash
# Tous les tests
php backend/tests/run_all.php

# Options
php backend/tests/run_all.php --quick             # Unit tests uniquement
php backend/tests/run_all.php --security          # Tests sécurité uniquement
php backend/tests/run_all.php --filter=banque     # Filtre par nom
php backend/tests/run_all.php --verbose           # Détails des échecs
php backend/tests/run_all.php --no-color          # Pour CI sans couleurs
php backend/tests/run_all.php --help              # Aide
```

### Tests individuels

```bash
php backend/test_banque_manager.php
php backend/test_examen_manager.php
php backend/tests/test_security_csrf.php
# etc.
```

### Codes de sortie

| Code | Signification |
|:-:|---|
| 0 | Tous les tests passés |
| 1 | Au moins un test échoué |
| 2 | Erreur d'exécution |

Utilisable dans scripts :
```bash
php backend/tests/run_all.php && echo "OK" || echo "FAIL"
```

---

## 📊 Catégories de tests

### 🔵 UNIT (tests unitaires)

Tests des classes manager isolées (business logic pure).

| Suite | Fichier | Tests |
|---|---|:-:|
| banque_manager | `backend/test_banque_manager.php` | 28 |
| examen_manager | `backend/test_examen_manager.php` | 26 |
| passage_manager | `backend/test_passage_manager.php` | 28 |
| analytics_manager | `backend/test_analytics_manager.php` | 37 |
| mailer | `backend/test_mailer.php` | 18 |

### 🟣 INTEGRATION (tests API)

Tests des endpoints REST (via manager pour rapidité).

| Suite | Fichier | Tests |
|---|---|:-:|
| api_examens | `backend/test_api_examens.php` | 11 |
| api_passages | `backend/test_api_passages.php` | 25 |
| api_corrections | `backend/test_api_corrections.php` | 11 |
| api_analytics | `backend/test_api_analytics.php` | 18 |

### 🟡 SECURITY (tests sécurité — P8.1)

Tests dédiés à la robustesse contre les attaques.

| Suite | Fichier | Thème |
|---|---|---|
| security_csrf | `backend/tests/test_security_csrf.php` | Tokens CSRF |
| security_auth | `backend/tests/test_security_auth.php` | Bcrypt, rôles, sessions |
| security_xss | `backend/tests/test_security_xss.php` | Échappement HTML |
| security_injection | `backend/tests/test_security_injection.php` | Path traversal, injections |

---

## ✍️ Écrire un nouveau test

### Template minimal

```php
<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

use Examens\Lib\MonManager;

echo "🧪 Test MonManager\n";
echo str_repeat("=", 60) . "\n\n";

$tests = 0;
$passed = 0;

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

// ========== TESTS ==========
$mgr = new MonManager();

test('Description du test', function() use ($mgr) {
    return $mgr->method() === 'expected';
});

// ========== BILAN ==========
echo "\n" . str_repeat("=", 60) . "\n";
echo "RESULTAT : $passed / $tests tests passes";
echo $passed === $tests ? " ✅\n\n" : " ❌\n\n";
exit($passed === $tests ? 0 : 1);
```

### Bonnes pratiques

1. **Nommage explicite** : le nom du test doit décrire le comportement testé
   ```php
   // ✅ Bon
   test('ExamenManager::publish() refuse un examen en status draft', ...);
   
   // ❌ Mauvais
   test('publish test', ...);
   ```

2. **Isolation** : chaque test doit être indépendant, pas d'effet de bord persistant
   ```php
   // Cleanup en fin de test OU en début
   $createdIds = [];
   // ... tests ...
   foreach ($createdIds as $id) {
       @unlink(data_path('examens') . '/' . $id . '.json');
   }
   ```

3. **Return true en succès** : le framework utilise `=== true` strictement
   ```php
   test('...', function() {
       return $x === 'y'; // OK, bool
   });
   
   test('...', function() {
       if ($bad) return 'Message d\'erreur'; // String = fail avec message
       return true;
   });
   ```

4. **Use strict types** : toujours `declare(strict_types=1)`

5. **Messages d'erreur utiles** : retourner une string en cas d'échec
   ```php
   test('...', function() {
       foreach ($items as $item) {
           if (!$valid) return "Item {$item['id']} invalide : ...";
       }
       return true;
   });
   ```

---

## 🔒 Tests de sécurité

### Philosophie

Les tests sécurité vérifient :
1. **Protection CSRF** : tokens validés timing-safe
2. **Bcrypt** : hashes forts, cost ≥ 10
3. **XSS** : échappement HTML dans tous les outputs
4. **Injections** : path traversal, null bytes, SQL-like refusés

### Payloads classiques testés

```php
$XSS_PAYLOADS = [
    '<script>alert("XSS")</script>',
    '<img src=x onerror="alert(1)">',
    '<svg onload="alert(1)">',
    'javascript:alert(1)',
    '<scr<script>ipt>...',  // Nested
];

$PATH_TRAVERSAL = [
    '../../etc/passwd',
    'EXM-../../root',
    "EXM-TEST\x00.evil",  // Null byte
];
```

### Ajouter un test sécurité

1. Créer `backend/tests/test_security_XXXXX.php`
2. Ajouter au `run_all.php` dans `$ALL_SUITES`
3. Utiliser la catégorie `'security'`

---

## 📈 Métriques actuelles

### Tests passants (P8.1)

```
UNIT         : 5 suites · 137 tests
INTEGRATION  : 4 suites · 65 tests
SECURITY     : 4 suites · ~60 tests (P8.1)
────────────────────────────────────────
TOTAL        : 13 suites · ~260 tests
```

### Couverture

| Domaine | Coverage |
|---|:-:|
| Managers (backend) | 100% méthodes publiques |
| APIs (REST) | 100% endpoints |
| Sécurité | CSRF + Auth + XSS + Injection |
| Frontend | Manuel (pas de tests E2E automatisés actuellement) |

---

## 🚀 Pipeline CI/CD (P8.6 à venir)

Quand le workflow GitHub Actions sera en place :
- **Push** → lance `run_all.php --no-color`
- **Échec** → bloque le merge
- **Badge** dans README

---

## 🐛 Debug d'un test qui échoue

### 1. Relancer avec --verbose

```bash
php backend/tests/run_all.php --verbose
```

### 2. Lancer la suite isolée

```bash
php backend/test_examen_manager.php
```

### 3. Afficher var_dump dans le test

```php
test('...', function() {
    $result = $mgr->method();
    var_dump($result);  // Debug
    return $result['ok'] === true;
});
```

### 4. Vérifier l'état des données

```bash
# Voir les passages créés
ls -la data/passages/

# Cleanup manuel si besoin
rm -rf data/passages/*
rm -rf data/examens/EXM-TEST-*
```

---

## 📚 Conventions fichiers

| Pattern | Type | Où |
|---|---|---|
| `test_*_manager.php` | Tests unit | `backend/` |
| `test_api_*.php` | Tests integration | `backend/` |
| `test_security_*.php` | Tests security | `backend/tests/` |
| `run_all.php` | Harness | `backend/tests/` |

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
