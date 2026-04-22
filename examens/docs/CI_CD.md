# 🤖 CI/CD — IPSSI Examens

Guide du pipeline d'intégration continue via **GitHub Actions**.

---

## 🎯 Workflows

### 1. `tests.yml` — Harness complet

**Déclenchement** : push/PR sur `main` touchant `examens/`

**Matrix** : PHP 8.2 et 8.3 en parallèle

**Étapes** :
1. Checkout code
2. Setup PHP avec extensions (json, mbstring, openssl, curl, zip)
3. Info environnement (versions, modules)
4. Préparation `data/` (permissions, sous-dossiers)
5. Rendre scripts bash exécutables
6. Vérification syntaxe PHP (tous les `.php`)
7. **Lancer `php backend/tests/run_all.php --no-color`**
8. Tester scripts backup + verify hash
9. Tester endpoint `/health` en live
10. Cleanup

**Job séparé** : `frontend-lint` parse tous les `.jsx` avec Babel

**Durée estimée** : 3-5 min

### 2. `lint.yml` — Lint rapide

**Déclenchement** : tous push/PR

**Jobs parallèles** :
- `php-syntax` : `php -l` sur tous les `.php`
- `markdown-check` : liste des docs
- `structure-check` : dossiers/fichiers critiques + mention CC

**Durée estimée** : <1 min

---

## 📊 Status badges

À ajouter dans `examens/README.md` :

```markdown
[![Tests](https://github.com/melafrit/maths_IA_niveau_1/actions/workflows/tests.yml/badge.svg)](https://github.com/melafrit/maths_IA_niveau_1/actions/workflows/tests.yml)
[![Lint](https://github.com/melafrit/maths_IA_niveau_1/actions/workflows/lint.yml/badge.svg)](https://github.com/melafrit/maths_IA_niveau_1/actions/workflows/lint.yml)
```

---

## 🔧 Configuration locale vs CI

### En local

```bash
# Lancer tout le harness
php backend/tests/run_all.php

# Rapide seulement
php backend/tests/run_all.php --quick

# Avec détails
php backend/tests/run_all.php --verbose
```

### En CI (GitHub Actions)

Le harness est lancé en `--no-color` avec code de sortie pour que GitHub Actions détecte les échecs.

Exit code :
- `0` : tous les tests passent → ✅ workflow vert
- `1` : au moins un échec → ❌ workflow rouge
- `2` : erreur d'exécution → ❌ workflow rouge

---

## 🐛 Debug workflows

### Voir les logs

1. Aller sur https://github.com/melafrit/maths_IA_niveau_1/actions
2. Cliquer sur le workflow échoué
3. Expand chaque step pour voir l'output

### Re-lancer un workflow

Depuis GitHub :
1. Page Actions → sélectionner le run
2. Bouton "Re-run jobs" → "Re-run failed jobs"

### Tester localement

Pour simuler l'environnement CI avant de push :

```bash
# Simuler PHP 8.2 (si installé)
php8.2 backend/tests/run_all.php --no-color

# Simuler PHP 8.3 (défaut)
php backend/tests/run_all.php --no-color

# Test script backup
./scripts/backup.sh --keep=3
```

---

## 📋 Matrix PHP

| Version | Status | Commentaire |
|---|---|---|
| 8.2 | ✅ Testé | Version minimum compatible |
| 8.3 | ✅ Testé | Version recommandée |

Si besoin d'ajouter 8.4 (sortie nov 2024) :
```yaml
matrix:
  php-version: ['8.2', '8.3', '8.4']
```

---

## 🚫 Skip CI

Pour commits qui ne doivent pas déclencher le CI :

```bash
git commit -m "docs: typo fix [skip ci]"
```

Ou préfixes reconnus : `[ci skip]`, `[skip actions]`.

---

## 🎯 Prochaines améliorations possibles

- **Couverture de code** : ajouter PCOV + upload vers Codecov
- **Benchmarks** : mesurer perf du harness (régression)
- **Déploiement auto** : sur tag `v*.*.*`, deploy sur serveur
- **Notifications** : webhook Discord/Slack si fail sur `main`
- **Dependabot** : suivi des dépendances npm/composer

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
