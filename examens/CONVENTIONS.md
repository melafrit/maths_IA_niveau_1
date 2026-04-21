# 📏 Conventions du projet

> Ce document définit les conventions de code, de commit, de branches et de
> documentation pour le projet **Plateforme d'examens IPSSI**.

**Version** : 1.0
**Date** : 2026-04-21
**Auteur** : Mohamed EL AFRIT — IPSSI

---

## 📁 Structure des fichiers

### Conventions de nommage

| Type | Convention | Exemple |
|---|---|---|
| **Dossiers** | `snake_case` minuscule | `scenarios_tests/`, `backend/api/` |
| **Fichiers PHP classes** | `PascalCase.php` | `ScoreCalculator.php` |
| **Fichiers PHP scripts** | `snake_case.php` | `init_comptes.php` |
| **Fichiers HTML/JS** | `snake_case.html/.js` | `examen_creer.html` |
| **Fichiers JSON** | `snake_case.json` | `examen_config.json` |
| **Documents Markdown** | `UPPER_SNAKE_CASE.md` | `README.md`, `NOTE_CADRAGE.md` |
| **Variables JSON** | `snake_case` | `"examen_code"`, `"date_debut"` |

### Organisation physique

- **Pas plus de 500 lignes** par fichier PHP/JS (au-delà : découper)
- **1 classe = 1 fichier** (en PHP)
- **Tests miroir** : un test par classe, dans `tests/backend/`
- **Documentation** de chaque dossier avec un `README.md` expliquant son rôle

---

## 💬 Conventions de commit (Conventional Commits)

### Format

```
<type>(<scope>): <sujet court>

<corps optionnel>

<footer optionnel>
```

### Types autorisés

| Type | Usage |
|---|---|
| `feat` | Nouvelle fonctionnalité |
| `fix` | Correction de bug |
| `docs` | Documentation uniquement |
| `style` | Formatage, points-virgules, espaces (pas de changement fonctionnel) |
| `refactor` | Refactoring sans changement fonctionnel |
| `perf` | Amélioration de performance |
| `test` | Ajout ou correction de tests |
| `chore` | Maintenance (deps, config, scripts) |
| `ci` | CI/CD (GitHub Actions) |
| `build` | Build (webpack, rollup, etc.) |

### Scope (optionnel mais recommandé)

| Scope | Usage |
|---|---|
| `auth` | Authentification |
| `banque` | Banque de questions |
| `examens` | Création/gestion examens |
| `etudiant` | Parcours étudiant |
| `correction` | Correction détaillée |
| `analytics` | Analyses statistiques |
| `ia` | Génération IA |
| `email` | Notifications email |
| `rgpd` | Conformité RGPD |
| `design` | Design system |
| `api` | Couche API backend |
| `ui` | Composants d'interface |
| `tests` | Suite de tests |
| `docs` | Documentation |
| `backup` | Sauvegardes |

### Sujet

- **Mode impératif présent** : "Add", "Fix", "Update", PAS "Added", "Fixing"
- **En français** (le projet est francophone)
- **Pas de point final**
- **Max 70 caractères**

### Exemples

```
feat(auth): ajoute l'authentification par email + bcrypt
fix(examens): corrige le calcul de score quand difficulte est modifiee
docs(banque): documente le format JSON d'import
refactor(ia): extrait AnthropicClient de IaClient
test(scoring): ajoute 5 tests anti-fraude difficulte
chore(deps): met a jour React vers 18.2.0
ci(github): configure workflow Playwright
```

### Corps (si nécessaire)

Expliquer :
- **Pourquoi** ce changement (contexte)
- **Quoi** exactement (détails techniques importants)
- **Impact** (ce que ça casse, ce que ça change pour l'utilisateur)

### Footer

- `BREAKING CHANGE:` pour les changements incompatibles
- `Closes #X` pour fermer une Issue GitHub
- `Refs #X` pour référencer

---

## 🔀 Gestion des branches

### Branches principales

- **`main`** : branche de production, toujours stable et déployable
- **`develop`** : branche d'intégration (optionnel, utilisée si besoin de PR)

### Branches de travail (feature branches)

Format : `<type>/<phase>-<short-description>`

| Préfixe | Usage | Exemple |
|---|---|---|
| `feat/` | Nouvelle fonctionnalité | `feat/P1-auth-login` |
| `fix/` | Correction de bug | `fix/P5-timer-refresh` |
| `refactor/` | Refactoring | `refactor/P3-banque-io` |
| `test/` | Ajout de tests | `test/P8-e2e-focus-lock` |
| `docs/` | Documentation | `docs/P9-guide-prof` |

### Workflow

```bash
# Créer une branche depuis main
git checkout main
git pull
git checkout -b feat/P1-auth-login

# Travailler, commiter (petits commits fréquents)
git add .
git commit -m "feat(auth): ajoute la page login HTML"
git commit -m "feat(auth): implemente Auth.php avec bcrypt"
git commit -m "test(auth): ajoute tests PHPUnit login"

# Pousser régulièrement
git push origin feat/P1-auth-login

# Merge dans main (via PR ou direct si solo)
git checkout main
git merge feat/P1-auth-login
git push origin main
git branch -d feat/P1-auth-login
```

### Règles
- **Petits commits fréquents** plutôt que gros commits monolithiques
- **Tests passent** avant merge
- **Pull Request recommandée** si collaboration (même solo pour discipline)
- **Squash merge** si trop de commits "WIP" à nettoyer

---

## 🖥️ Conventions PHP (PSR-12)

### Style

```php
<?php
declare(strict_types=1);

namespace Examens\Lib;

use Examens\Lib\Logger;

class ScoreCalculator
{
    private const POINTS_MAX = 92;

    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function calculate(array $answers, array $bareme): array
    {
        // ...
        return [
            'points' => $points,
            'note' => round($points / self::POINTS_MAX * 20, 2),
        ];
    }
}
```

### Règles principales

- **Indentation** : 4 espaces (pas de tabs)
- **Namespace** : `Examens\<Subsystem>` (ex: `Examens\Lib\Auth`)
- **`declare(strict_types=1);`** en tête de chaque fichier
- **Accolades** : même ligne pour contrôles, ligne suivante pour fonctions/classes
- **Nommage** :
  - Variables : `$camelCase`
  - Constantes : `UPPER_SNAKE_CASE`
  - Classes : `PascalCase`
  - Méthodes : `camelCase`
- **Commentaires** : français, PHPDoc pour les méthodes publiques

### Linter
```bash
vendor/bin/phpcs --standard=PSR12 backend/
```

---

## 🌐 Conventions JavaScript/React

### Style

```javascript
// Fichier : frontend/assets/components/Button.js
import React from 'react';
import { useTheme } from '../hooks/useTheme';

/**
 * Composant Button polyvalent avec 5 variants.
 */
export function Button({
  variant = 'primary',
  onClick,
  disabled = false,
  icon,
  children,
  ...props
}) {
  const { theme } = useTheme();

  return (
    <button
      onClick={onClick}
      disabled={disabled}
      style={{
        background: theme.accent,
        color: '#ffffff',
        padding: '10px 20px',
      }}
      {...props}
    >
      {icon && <span>{icon}</span>}
      {children}
    </button>
  );
}
```

### Règles principales

- **Indentation** : 2 espaces
- **Quotes** : `'` pour JS, `"` pour JSX attributs
- **Semi-colons** : toujours (explicite)
- **Nommage** :
  - Variables/fonctions : `camelCase`
  - Constantes globales : `UPPER_SNAKE_CASE`
  - Composants React : `PascalCase`
  - Fichiers composants : `PascalCase.js`
  - Hooks : `useXxx` (convention React)
- **Destructuration des props** en haut de composant
- **PropTypes ou TypeScript** : on reste en JS standard pour simplicité (pas de TS en v1)

### Linter
```bash
npx eslint frontend/
```

---

## 🎨 Conventions CSS

### Variables CSS (tokens)

```css
:root {
  /* Couleurs primaires */
  --color-primary: #3b82f6;
  --color-primary-hover: #2563eb;
  --color-primary-active: #1d4ed8;

  /* Spacing (base 4px) */
  --spacing-xs: 4px;
  --spacing-sm: 8px;
  --spacing-md: 16px;
  --spacing-lg: 24px;
  --spacing-xl: 32px;

  /* Typographie */
  --font-sans: 'Inter', sans-serif;
  --font-heading: 'Manrope', sans-serif;
  --font-mono: 'JetBrains Mono', monospace;

  --text-xs: 12px;
  --text-sm: 14px;
  --text-base: 16px;
  --text-lg: 18px;
  --text-xl: 24px;
  --text-2xl: 32px;
}
```

### Nommage des classes

- **BEM-like** : `.block__element--modifier`
- Exemple : `.button`, `.button__icon`, `.button--primary`, `.button--disabled`

---

## 🌍 Conventions i18n

### Fichier de traduction

```json
{
  "common.login": "Connexion",
  "common.logout": "Déconnexion",
  "common.save": "Enregistrer",
  "common.cancel": "Annuler",
  "auth.email": "Adresse email",
  "auth.password": "Mot de passe",
  "auth.error.invalid": "Email ou mot de passe incorrect",
  "exam.title": "Examen",
  "exam.start": "Commencer l'examen",
  "exam.remaining": "Temps restant : {time}",
  "student.name": "Nom",
  "student.firstname": "Prénom",
  "student.email": "Email"
}
```

### Convention des clés

- **Hiérarchie en points** : `namespace.context.element`
- **Namespace** : `common`, `auth`, `exam`, `student`, `admin`, `errors`...
- **Variables** : `{nom}` entre accolades

---

## 📝 Documentation

### Code

- **Commentaires en français** (projet francophone)
- **PHPDoc / JSDoc** pour les fonctions publiques
- **Commentaires "pourquoi"** pas "quoi" (le code montre le quoi)

```php
/**
 * Calcule la note sur 20 à partir des réponses.
 *
 * IMPORTANT : utilise la difficulté AUTORITATIVE depuis les corrections,
 * PAS celle du CSV (qui pourrait être modifiée par un étudiant malveillant).
 * Voir fix de sécurité Phase 4 du projet qcm_eval_J1_J2.
 *
 * @param array $answers Réponses parsées du CSV
 * @param array $corrections Corrections authoritative depuis la banque
 * @return array ['points' => int, 'note_sur_20' => float]
 */
public function calculate(array $answers, array $corrections): array
```

### Fichiers Markdown

- **Titre principal** avec `#`
- **Table des matières** pour documents > 3 pages
- **Métadonnées en tête** : version, date, auteur
- **Blocs de code** avec coloration syntaxique (`\`\`\`php`, `\`\`\`javascript`, etc.)
- **Liens relatifs** entre documents du même repo

### READMEs des sous-dossiers

Chaque sous-dossier important a un `README.md` expliquant son rôle :
- `backend/README.md`
- `frontend/README.md`
- `data/README.md`
- `tests/README.md`
- `scenarios_tests/README.md`

---

## 🧪 Tests

### Nommage des tests

#### PHPUnit (backend)

```php
class ScoreCalculatorTest extends TestCase
{
    public function test_score_simple_avec_toutes_bonnes_reponses(): void
    {
        // ...
    }

    public function test_anti_fraude_difficulte_modifiee_dans_csv(): void
    {
        // ...
    }
}
```

- **Fichier** : `NomClasseTest.php`
- **Méthode** : `test_<comportement_teste>`
- **En français** (cohérent avec le reste)
- **Un test = un comportement** (pas 10 assertions)

#### Playwright (E2E)

```javascript
// tests/e2e/student_complete_flow.spec.js
test.describe('Parcours étudiant complet', () => {
  test('passage nominal avec toutes les bonnes réponses', async ({ page }) => {
    // ...
  });

  test('annulation pour triche détectée', async ({ page }) => {
    // ...
  });
});
```

### Assertions claires

```php
// ✅ BON
$this->assertSame(15.43, $score['note_sur_20'], 'Note calculée incorrecte');

// ❌ MAUVAIS
$this->assertTrue(abs($score['note'] - 15.43) < 0.01);
```

---

## 📦 Versioning (Semantic Versioning)

Format : `MAJOR.MINOR.PATCH`

| Composant | Quand incrémenter ? |
|---|---|
| **MAJOR** | Changement incompatible (ex: nouvelle structure de banque) |
| **MINOR** | Nouvelle fonctionnalité rétrocompatible |
| **PATCH** | Correction de bug |

Versions prévues :
- `0.0.1` : P0 cadrage (pré-alpha)
- `0.1.0` : P1 fondations backend
- `0.2.0` : P2 design system
- ... (une minor par phase)
- `1.0.0` : Fin du soft launch, première production stable
- `1.x.x` : Maintenance et petites améliorations
- `2.0.0` : Migration vers MySQL (changement majeur)

---

## 🔒 Sécurité

### Secrets (JAMAIS commités)

Ne JAMAIS commiter dans Git :
- Mots de passe, clés API, tokens
- Fichier `backend/config.php` (copier `config.sample.php`)
- Fichier `data/comptes/enseignants.json` (contient des hashes)
- Fichiers de sessions PHP
- Certificats SSL
- Données étudiants réelles (anonymiser pour les tests)

Ces fichiers sont dans `.gitignore`.

### Bonnes pratiques

- **Toujours valider** les entrées utilisateur
- **Toujours échapper** les sorties (XSS prevention)
- **Toujours utiliser** les requêtes préparées (SQL injection prevention, si MySQL v2)
- **HTTPS obligatoire** en production
- **Session destroy** au logout
- **Rate limiting** sur les endpoints sensibles
- **CSRF token** dans les formulaires modifiants

---

## 📚 Résumé pour démarrer

### Avant chaque commit

1. ✅ Code formaté selon les conventions
2. ✅ Pas de `console.log` ou `var_dump` oubliés
3. ✅ Tests unitaires passent
4. ✅ Message de commit au bon format
5. ✅ Pas de secrets commités

### Avant chaque PR / merge

1. ✅ Branche à jour avec `main`
2. ✅ Tous les tests passent
3. ✅ CI GitHub Actions vert
4. ✅ Revue manuelle des changements
5. ✅ CHANGELOG mis à jour

---

*© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0*
