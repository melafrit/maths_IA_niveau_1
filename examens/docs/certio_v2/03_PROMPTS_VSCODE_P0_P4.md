# 🎯 Prompts VS Code optimisés — Phases 0 à 4

> **Livrable 3/5 — Prompts prêts à copier-coller dans Claude Code / Cursor / Copilot Agent**

| Champ | Valeur |
|---|---|
| **Livrable** | 3/5 |
| **Phases couvertes** | P0, P1, P2, P3, P4 |
| **Version** | 1.0 |
| **Auteur** | Mohamed EL AFRIT |
| **Licence** | CC BY-NC-SA 4.0 |

---

## 📖 Guide d'utilisation

### 🎯 Comment utiliser ces prompts

1. **Dans VS Code**, ouvrir le projet `maths_IA_niveau_1/examens/` 
2. Lancer **Claude Code** / **Cursor Chat** / **Copilot Agent**
3. **Copier le prompt complet** de la phase que tu veux lancer
4. **Coller** dans l'IA et laisser travailler
5. **Review** les changements proposés
6. **Valider** ou demander corrections
7. **Commit** selon la convention donnée

### 🤖 IA recommandées par phase

| Phase | IA recommandée | Pourquoi |
|:-:|---|---|
| **P0** | Claude Code / Copilot Agent | Grep & replace massif, refactoring |
| **P1** | Claude Code | Logique métier complexe, tests |
| **P2** | Claude Code / Cursor | React components adaptatifs |
| **P3** | Cursor / Claude Code | UI Analytics, data viz |
| **P4** | Claude Code | Markdown parsing, RBAC |

### 📋 Conventions communes

- **Branches** : `feat/pX-nom-phase`
- **Commits** : Conventional Commits (`feat(scope):`, `fix(scope):`, etc.)
- **Tests** : créer en parallèle du code, pas après
- **Docs** : mettre à jour `docs/` à chaque ajout de feature

---

## Sommaire

1. [Prompt Phase 0 — Fondations & Rebranding](#prompt-phase-0--fondations--rebranding)
2. [Prompt Phase 1 — CBM Core](#prompt-phase-1--cbm-core)
3. [Prompt Phase 2 — Types de questions étendus](#prompt-phase-2--types-de-questions-étendus)
4. [Prompt Phase 3 — Scoring & Analytics](#prompt-phase-3--scoring--analytics)
5. [Prompt Phase 4 — Documentation interactive](#prompt-phase-4--documentation-interactive)

---

## Prompt Phase 0 — Fondations & Rebranding

### 🎯 À copier-coller dans Claude Code / Cursor

```
# CONTEXTE PROJET CERTIO v2.0 — PHASE 0 : FONDATIONS & REBRANDING

Tu travailles sur le projet **Certio v2.0** (anciennement IPSSI Examens v1.0), une plateforme web d'évaluation QCM avec Certainty-Based Marking. Le code actuel est en v1.0 production-ready (389/389 tests passent).

## 📚 Documents de référence à lire en priorité
- `examens/docs/certio_v2/01_NOTE_DE_CADRAGE.md` (vision complète v2.0)
- `examens/docs/certio_v2/02_PLANNING_8_PHASES.md` (planning détaillé)
- `examens/docs/ARCHITECTURE.md` (architecture v1 existante)

## 📦 État actuel du projet
- Backend : PHP 8.3 natif, 16 managers dans `backend/lib/`
- Frontend : React 18 CDN + Babel Standalone
- Persistance : JSON files dans `data/`
- 9 endpoints API REST dans `backend/api/`
- 389 tests automatisés qui passent

## 🎯 Objectif Phase 0 (3 jours)

Transformer le code IPSSI Examens en **Certio v2.0** en :
1. Centralisant le branding (nom modifiable en 2 fichiers)
2. Remplaçant toutes les références "IPSSI" par "Certio"
3. Créant les 13 nouveaux managers skeleton pour la v2.0
4. Mettant en place i18n (FR/EN)
5. Mettant en place la PWA (manifest + service worker)
6. Préparant la structure data/ pour v2

## 🔧 Stack technique (garder la philosophie)
- PHP 8.3 strict types, AUCUNE dépendance Composer
- React 18 via CDN (pas de bundler)
- Persistance JSON (pas de SQL)
- Tests via harness custom existant

---

## 📋 TÂCHES DÉTAILLÉES

### Tâche 1 — Centralisation du branding

Créer `backend/config/branding.php` :
```php
<?php
declare(strict_types=1);

/**
 * Configuration centrale du branding Certio.
 * Modifier ce fichier uniquement pour rebranding complet.
 *
 * @package Certio
 * @license CC-BY-NC-SA-4.0
 */

return [
    'app_name'        => 'Certio',
    'app_slug'        => 'certio',
    'app_tagline'     => 'Certainty-Based Assessment Platform',
    'app_description' => 'Plateforme d\'évaluation avec Certainty-Based Marking',
    'app_url'         => 'https://certio.app',
    'app_version'     => '2.0.0',
    
    'logo_svg_path'   => '/assets/img/logo.svg',
    'logo_png_path'   => '/assets/img/logo.png',
    'favicon_path'    => '/assets/img/favicon.ico',
    
    'colors' => [
        'primary'   => '#1a365d',  // Deep navy
        'secondary' => '#48bb78',  // Mint green
        'accent'    => '#ed8936',  // Warm orange
        'success'   => '#38a169',
        'danger'    => '#e53e3e',
        'warning'   => '#d69e2e',
        'info'      => '#3182ce',
    ],
    
    'contact_email'   => 'mohamed@elafrit.com',
    'support_email'   => 'mohamed@elafrit.com',
    'noreply_email'   => 'mohamed@elafrit.com',
    'admin_email'     => 'mohamed@elafrit.com',
    
    'copyright_owner' => 'Mohamed EL AFRIT',
    'copyright_year'  => '2026',
    'license'         => 'CC BY-NC-SA 4.0',
    'license_url'     => 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
    
    'smtp' => [
        'host'      => 'ssl0.ovh.net',
        'port'      => 465,
        'encryption'=> 'ssl',
        'from_name' => 'Certio',
    ],
    
    'features' => [
        'cbm_enabled_default'       => false,
        'community_bank_enabled'    => true,
        'multi_tenant_enabled'      => true,
        'sso_google_enabled'        => true,
        'sso_microsoft_enabled'     => true,
        'pwa_enabled'               => true,
        'i18n_enabled'              => true,
        'default_locale'            => 'fr',
        'available_locales'         => ['fr', 'en'],
    ],
];
```

Créer `frontend/assets/branding.js` :
```javascript
// Configuration centrale du branding Certio (frontend)
// Modifier ce fichier pour rebranding complet.
window.BRANDING = {
  appName: 'Certio',
  appSlug: 'certio',
  appTagline: 'Certainty-Based Assessment Platform',
  appVersion: '2.0.0',
  logoUrl: '/assets/img/logo.svg',
  faviconUrl: '/assets/img/favicon.ico',
  colors: {
    primary:   '#1a365d',
    secondary: '#48bb78',
    accent:    '#ed8936',
    success:   '#38a169',
    danger:    '#e53e3e',
    warning:   '#d69e2e',
    info:      '#3182ce',
  },
  contactEmail: 'mohamed@elafrit.com',
  supportEmail: 'mohamed@elafrit.com',
  copyrightOwner: 'Mohamed EL AFRIT',
  copyrightYear: 2026,
  license: 'CC BY-NC-SA 4.0',
  licenseUrl: 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
};
```

### Tâche 2 — Remplacer toutes les occurrences "IPSSI"

**⚠️ IMPORTANT** : Certaines occurrences "IPSSI" doivent être **conservées** car elles correspondent au contenu pédagogique du module de Mathématiques pour IA (course name `ipssi_maths_ia`, chemins dans banque de questions, commentaires historiques). Identifie ces cas d'usage et demande confirmation avant de les toucher.

**À remplacer** (branding uniquement) :
- Titres HTML : "IPSSI Examens" → `<?= BRANDING['app_name'] ?>` (PHP) ou `{BRANDING.appName}` (React)
- Footers : "© IPSSI" → "© Mohamed EL AFRIT"
- Email templates : "IPSSI" → "Certio"
- README principal : "IPSSI Examens" → "Certio"
- Tous les `.md` dans `docs/` (sauf historique)
- Copyright dans headers de code : `@package IPSSI` → `@package Certio`

**À NE PAS toucher** :
- Variables de banque questions : `module: "IPSSI_MathIA"` (contenu pédagogique)
- Logs historiques
- Fichiers de tests qui testent du contenu IPSSI
- Documentation de référence du module de maths (dans `/docs/MODULE_MATHS_IA.md` s'il existe)

Procédure :
1. `grep -ri "ipssi" --include="*.php" --include="*.js" --include="*.html" --include="*.md" -l` pour lister
2. Traiter fichier par fichier
3. Commit intermédiaires toutes les 10 fichiers

### Tâche 3 — Créer les 13 nouveaux managers skeleton

Dans `backend/lib/`, créer les fichiers suivants avec structure vide mais valide :

#### `BrandingManager.php`
```php
<?php
declare(strict_types=1);
namespace Certio\Lib;

/**
 * Gère le branding dynamique de l'application.
 * Charge la config depuis backend/config/branding.php.
 *
 * @package Certio\Lib
 * @author Mohamed EL AFRIT
 * @license CC-BY-NC-SA-4.0
 */
class BrandingManager
{
    private array $config;
    
    public function __construct(?string $configPath = null)
    {
        $path = $configPath ?? __DIR__ . '/../config/branding.php';
        $this->config = require $path;
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
    
    public function getAll(): array
    {
        return $this->config;
    }
    
    public function getAppName(): string
    {
        return $this->config['app_name'];
    }
    
    public function getContactEmail(): string
    {
        return $this->config['contact_email'];
    }
    
    // Autres getters...
}
```

Même approche pour les autres managers (créer squelettes valides) :
- `CbmManager.php` — skeleton avec méthodes `createMatrix()`, `calculateScore()`, `listPresets()` (vides)
- `WorkspaceManager.php` — skeleton avec `create()`, `get()`, `list()`, `update()`
- `TotpManager.php` — skeleton avec `generateSecret()`, `validate()`
- `SsoManager.php` — skeleton avec `initiateGoogleFlow()`, `handleCallback()`
- `CommunityBankManager.php` — skeleton avec `publish()`, `fork()`, `vote()`
- `ImportManager.php` — skeleton avec `importMoodleXml()`, `importWord()`, `importExcel()`
- `ExportManager.php` — skeleton avec `exportScorm12()`, `exportScorm2004()`, `exportXApi()`
- `DocumentationManager.php` — skeleton avec `listDocs($role)`, `getDoc($path)`, `search()`
- `I18nManager.php` — skeleton avec `translate($key, $locale)`, `setLocale()`
- `AntiCheatAnalyzer.php` — skeleton avec `analyzePassage($passage)` → score 0-1
- `AuditLogger.php` — skeleton avec `log($action, $context)`, `getRecent()`
- `QuestionTypeResolver.php` — skeleton avec `getType($question)`, `validateAnswer()`, `isCorrect()`

**Chaque manager doit** :
- Avoir un header PHP complet (package, author, license)
- Être dans le namespace `Certio\Lib`
- Avoir des méthodes typées (paramètres + retour)
- Avoir des docblocks PHPDoc
- Retourner des valeurs par défaut (stub) avec un comment `// TODO: Phase X implementation`

### Tâche 4 — Structure i18n (FR/EN)

Créer `frontend/assets/i18n/fr.json` :
```json
{
  "common": {
    "save": "Enregistrer",
    "cancel": "Annuler",
    "delete": "Supprimer",
    "edit": "Modifier",
    "create": "Créer",
    "search": "Rechercher",
    "loading": "Chargement…",
    "error": "Erreur",
    "success": "Succès"
  },
  "auth": {
    "login": "Connexion",
    "logout": "Déconnexion",
    "email": "Email",
    "password": "Mot de passe",
    "forgot_password": "Mot de passe oublié ?",
    "remember_me": "Se souvenir de moi"
  },
  "exam": {
    "create": "Créer un examen",
    "title": "Titre",
    "duration": "Durée",
    "questions": "Questions",
    "publish": "Publier",
    "access_code": "Code d'accès"
  },
  "cbm": {
    "enable": "Activer le CBM",
    "matrix": "Matrice de scoring",
    "level_label": "Libellé du niveau",
    "score_if_correct": "Score si juste",
    "score_if_incorrect": "Score si faux"
  }
}
```

Créer `frontend/assets/i18n/en.json` (même structure en anglais).

Créer `frontend/assets/i18n/i18n.js` :
```javascript
window.I18n = (function() {
  let currentLocale = 'fr';
  let translations = {};
  
  async function loadLocale(locale) {
    const response = await fetch(`/assets/i18n/${locale}.json`);
    translations = await response.json();
    currentLocale = locale;
    localStorage.setItem('certio_locale', locale);
  }
  
  function t(key, params = {}) {
    const keys = key.split('.');
    let value = translations;
    for (const k of keys) {
      value = value?.[k];
      if (value === undefined) return key;
    }
    // Replace {param} with params.param
    return Object.entries(params).reduce(
      (str, [p, v]) => str.replace(`{${p}}`, v),
      value
    );
  }
  
  function getLocale() { return currentLocale; }
  
  // Auto-load from localStorage or default
  const saved = localStorage.getItem('certio_locale') || 'fr';
  loadLocale(saved);
  
  return { t, loadLocale, getLocale };
})();
```

### Tâche 5 — PWA Manifest + Service Worker

Créer `frontend/manifest.json` :
```json
{
  "name": "Certio",
  "short_name": "Certio",
  "description": "Certainty-Based Assessment Platform",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#1a365d",
  "orientation": "any",
  "icons": [
    { "src": "/assets/img/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/assets/img/icons/icon-512.png", "sizes": "512x512", "type": "image/png" },
    { "src": "/assets/img/icons/icon-512-maskable.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
  ],
  "categories": ["education", "productivity"],
  "lang": "fr"
}
```

Créer `frontend/service-worker.js` :
```javascript
const CACHE_NAME = 'certio-v2.0.0';
const CACHE_URLS = [
  '/',
  '/assets/css/main.css',
  '/assets/branding.js',
  '/assets/i18n/fr.json',
  '/assets/i18n/en.json',
  '/assets/img/logo.svg',
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(CACHE_URLS))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request);
    })
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => 
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
});
```

Dans les pages HTML principales, ajouter :
```html
<link rel="manifest" href="/manifest.json">
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/service-worker.js');
    });
  }
</script>
```

### Tâche 6 — Structure data/ pour v2

Créer les dossiers suivants avec `.gitkeep` :
```
data/
├── workspaces/          (NOUVEAU)
├── community/           (NOUVEAU)
│   ├── pending/
│   ├── approved/
│   └── rejected/
├── audit/               (NOUVEAU)
├── config/              (NOUVEAU)
├── totp/                (NOUVEAU, secrets 2FA encryptés)
├── i18n/                (pour traductions dynamiques futures)
```

Créer workspace par défaut `data/workspaces/WKS-DEFAULT.json` :
```json
{
  "id": "WKS-DEFAULT",
  "name": "Workspace par défaut",
  "slug": "default",
  "plan": "enterprise",
  "branding": {},
  "settings": {
    "allow_community_publish": true,
    "allow_sso_google": false,
    "allow_sso_microsoft": false,
    "default_locale": "fr"
  },
  "limits": {
    "max_profs": -1,
    "max_students_per_month": -1,
    "max_storage_mb": -1
  },
  "admins": [],
  "created_at": "2026-04-23T00:00:00Z",
  "status": "active"
}
```

### Tâche 7 — Documentation

Créer `docs/SCHEMAS_V2.md` avec les schémas JSON v2 complets (copier depuis la note de cadrage section 5).

Mettre à jour `README.md` principal :
- Titre : "Certio" au lieu de "IPSSI Examens"
- Ajouter badge v2.0.0-alpha.0
- Mentionner le CBM comme feature phare
- Link vers note de cadrage

---

## ✅ CRITÈRES D'ACCEPTATION

Avant de créer la PR de la Phase 0 :

- [ ] `grep -ri "ipssi examens" --include="*.php"` → 0 résultat (ou justifications)
- [ ] `grep -ri "ipssi" --include="*.md" -l | wc -l` → 0 (sauf docs historiques justifiées)
- [ ] `php -l backend/config/branding.php` → OK
- [ ] Tous les 13 managers skeleton créés avec `php -l` OK
- [ ] `php backend/tests/run_all.php` → 389/389 tests passent
- [ ] Page d'accueil affiche "Certio" et le bon logo
- [ ] Manifest PWA valide (via chrome://manifest)
- [ ] i18n.js fonctionne : `I18n.t('common.save')` retourne "Enregistrer"
- [ ] Dossiers `data/workspaces/`, `data/community/`, `data/audit/` créés

## 🧪 TESTS À ÉCRIRE

Créer `backend/tests/test_branding_manager.php` avec :
- Test `get()` avec clé existante
- Test `get()` avec clé inexistante (défaut)
- Test `getAppName()` retourne "Certio"
- Test `getContactEmail()` retourne "mohamed@elafrit.com"

Créer `backend/tests/test_managers_skeleton.php` avec :
- Test instanciation de chacun des 13 managers
- Test méthodes publiques répondent (même avec stub)

## 🎨 DESIGN ASSETS PLACEHOLDER

Créer placeholder SVG pour logo :
```svg
<!-- /frontend/assets/img/logo.svg -->
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 60" width="200" height="60">
  <rect width="200" height="60" fill="#1a365d"/>
  <text x="100" y="40" font-family="Arial, sans-serif" font-size="28" font-weight="bold" 
        fill="white" text-anchor="middle">Certio</text>
</svg>
```

Créer icons PNG placeholder 192x192 et 512x512 (même logo simple).

## 📝 COMMITS ATTENDUS

Faire commits fréquents avec messages Conventional Commits :
- `feat(branding): add centralized branding config`
- `chore(rebrand): replace IPSSI with Certio in PHP files`
- `chore(rebrand): replace IPSSI with Certio in React/HTML`
- `chore(rebrand): update email templates`
- `feat(managers): add 13 manager skeletons for v2`
- `feat(i18n): add FR/EN base translations`
- `feat(pwa): add manifest and service worker`
- `feat(data): add v2 data structure folders`
- `docs(schemas): add SCHEMAS_V2.md reference`
- `test(branding): add BrandingManager tests`

## 🚀 FINALISATION

1. Créer une PR `feat/p0-fondations` → `develop`
2. Tag `v2.0.0-alpha.0` après merge
3. Release notes : "Phase 0 completed — Rebranding Certio + foundation managers"

**Commence par la Tâche 1 (branding config) et demande-moi confirmation avant de passer à la suivante.**
```

---

## Prompt Phase 1 — CBM Core

### 🎯 À copier-coller dans Claude Code / Cursor

```
# CONTEXTE PROJET CERTIO v2.0 — PHASE 1 : CBM CORE

Tu continues sur **Certio v2.0**. La Phase 0 (Fondations & Rebranding) est terminée. Tu as maintenant 13 managers skeleton et le branding centralisé.

## 📚 Documents de référence
- `examens/docs/certio_v2/01_NOTE_DE_CADRAGE.md` (section 5 : schémas JSON v2)
- `examens/docs/SCHEMAS_V2.md`
- Manager skeleton `backend/lib/CbmManager.php` (créé en P0)

## 🎯 Objectif Phase 1 (5 jours)

Implémenter le **CBM (Certainty-Based Marking) 100% paramétrable** :
1. Logique métier complète dans `CbmManager`
2. UI prof : éditeur de matrice CBM
3. UI étudiant : saisie certitude
4. Calcul et affichage des scores CBM
5. Presets CBM réutilisables par prof
6. Calibration (over/underconfidence)

## 📋 TÂCHES DÉTAILLÉES

### Tâche 1 — CbmManager (business logic)

Implémenter dans `backend/lib/CbmManager.php` :

```php
<?php
declare(strict_types=1);
namespace Certio\Lib;

class CbmManager
{
    private FileStorage $storage;
    private Logger $logger;
    
    public function __construct(FileStorage $storage, Logger $logger)
    {
        $this->storage = $storage;
        $this->logger = $logger;
    }
    
    /**
     * Crée une matrice CBM à partir de levels et scoring.
     *
     * @param array $levels   Ex: [['id'=>1,'label'=>'Incertain','value'=>0], ...]
     * @param array $scoring  Ex: [['level_id'=>1,'correct'=>1,'incorrect'=>0], ...]
     * @return array Matrice normalisée
     * @throws \InvalidArgumentException si invalide
     */
    public function createMatrix(array $levels, array $scoring): array;
    
    /**
     * Valide une matrice CBM (cohérence structure et valeurs).
     *
     * Règles:
     * - Au moins 2 niveaux, maximum 10
     * - Chaque niveau a id unique, label non vide, value 0-100
     * - Scoring contient une entrée par niveau
     * - Scores correct >= scores incorrect (pas obligatoire mais warning)
     *
     * @return array ['valid' => bool, 'errors' => [], 'warnings' => []]
     */
    public function validateMatrix(array $matrix): array;
    
    /**
     * Calcule le score CBM d'une réponse.
     *
     * @param bool $isCorrect    Réponse juste ou non
     * @param int  $cbmLevelId   Niveau déclaré par l'étudiant
     * @param array $matrix      Matrice de l'examen
     * @return float Score obtenu
     */
    public function calculateScore(bool $isCorrect, int $cbmLevelId, array $matrix): float;
    
    /**
     * Calcule la calibration d'un étudiant sur un ensemble de passages.
     * Identifie s'il est over/underconfident.
     *
     * Formule simplifiée:
     * - Pour chaque niveau de certitude, calculer taux de réussite réel
     * - Comparer avec la valeur attendue (value du niveau)
     * - Retourner un score global et détail par niveau
     *
     * @param array $passages Liste de passages avec leurs réponses et niveaux
     * @return array ['global_score' => float, 'per_level' => [...], 'tendency' => 'well_calibrated|overconfident|underconfident']
     */
    public function calculateCalibration(array $passages): array;
    
    /**
     * Sauvegarde un preset CBM pour un utilisateur.
     */
    public function savePreset(string $userId, string $name, array $matrix): string; // Retourne preset ID
    
    /**
     * Liste les presets CBM d'un utilisateur.
     */
    public function listPresets(string $userId): array;
    
    public function getPreset(string $userId, string $presetId): ?array;
    public function deletePreset(string $userId, string $presetId): bool;
    public function updatePreset(string $userId, string $presetId, array $updates): bool;
    
    /**
     * Exporte une matrice CBM en JSON (pour partage/backup).
     */
    public function exportMatrix(array $matrix): string; // JSON string
    
    /**
     * Importe une matrice CBM depuis JSON.
     * @throws \InvalidArgumentException si JSON invalide
     */
    public function importMatrix(string $json): array;
    
    /**
     * Retourne une matrice par défaut (3 niveaux simple).
     */
    public function getDefaultMatrix(): array;
}
```

**Matrice par défaut** à implémenter dans `getDefaultMatrix()` :
```php
return [
    'levels' => [
        ['id' => 1, 'label' => 'Incertain',  'value' => 0],
        ['id' => 2, 'label' => 'Plutôt sûr', 'value' => 50],
        ['id' => 3, 'label' => 'Certain',    'value' => 100],
    ],
    'scoring' => [
        ['level_id' => 1, 'correct' => 1, 'incorrect' => 0],
        ['level_id' => 2, 'correct' => 2, 'incorrect' => -1],
        ['level_id' => 3, 'correct' => 3, 'incorrect' => -3],
    ],
];
```

### Tâche 2 — Tests unitaires CbmManager

Créer `backend/tests/test_cbm_manager.php` avec minimum 30 tests :
- validateMatrix() avec matrices valides (3 cas)
- validateMatrix() avec erreurs (10 cas)
- calculateScore() tous les cas (6 cas)
- calculateCalibration() (5 cas)
- savePreset/listPresets/deletePreset (6 cas)
- exportMatrix/importMatrix roundtrip (3 cas)
- getDefaultMatrix (2 cas)

Utiliser le harness de tests existant (`backend/tests/run_all.php`).

### Tâche 3 — Endpoint API CBM

Créer `backend/api/cbm.php` avec routes :
- `GET  /api/cbm/presets` — liste presets de l'utilisateur connecté
- `POST /api/cbm/presets` — crée un preset (body: name, matrix)
- `GET  /api/cbm/presets/{id}` — détail preset
- `PUT  /api/cbm/presets/{id}` — modifier
- `DELETE /api/cbm/presets/{id}` — supprimer
- `POST /api/cbm/validate` — valider une matrice (body: matrix)
- `POST /api/cbm/export/{id}` — export JSON
- `POST /api/cbm/import` — import JSON (body: json_string)
- `GET  /api/cbm/default-matrix` — retourne matrice par défaut

**Tous les endpoints** :
- Nécessitent auth (sauf default-matrix)
- Sont soumis au rate limit standard prof/admin
- Protégés par CSRF pour POST/PUT/DELETE

### Tâche 4 — UI Prof : Éditeur matrice CBM

Créer composant React `<CbmMatrixEditor>` dans `frontend/assets/components-cbm.jsx` :

```jsx
function CbmMatrixEditor({ initialMatrix, onChange, onSavePreset }) {
  const [matrix, setMatrix] = React.useState(initialMatrix || getDefaultMatrix());
  const [presets, setPresets] = React.useState([]);
  
  // Charger presets du prof
  React.useEffect(() => {
    fetch('/api/cbm/presets', { credentials: 'include' })
      .then(r => r.json())
      .then(data => setPresets(data.data || []));
  }, []);
  
  function addLevel() {
    if (matrix.levels.length >= 10) return;
    const newId = Math.max(...matrix.levels.map(l => l.id)) + 1;
    setMatrix({
      ...matrix,
      levels: [...matrix.levels, { id: newId, label: 'Nouveau niveau', value: 50 }],
      scoring: [...matrix.scoring, { level_id: newId, correct: 1, incorrect: 0 }]
    });
  }
  
  function removeLevel(id) {
    if (matrix.levels.length <= 2) return;
    setMatrix({
      levels: matrix.levels.filter(l => l.id !== id),
      scoring: matrix.scoring.filter(s => s.level_id !== id)
    });
  }
  
  function updateLevel(id, field, value) { /* ... */ }
  function updateScoring(levelId, field, value) { /* ... */ }
  
  function loadPreset(presetId) { /* fetch /api/cbm/presets/{id}, setMatrix */ }
  function savePreset() { /* prompt name, POST /api/cbm/presets */ }
  function exportJson() { /* download as .json */ }
  function importJson(file) { /* read file, parse, validate, setMatrix */ }
  
  return (
    <div className="cbm-matrix-editor">
      <h3>Matrice de scoring CBM</h3>
      
      {/* Barre d'actions */}
      <div className="cbm-actions">
        <select onChange={e => loadPreset(e.target.value)}>
          <option value="">Charger un preset…</option>
          {presets.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
        </select>
        <button onClick={savePreset}>💾 Sauvegarder preset</button>
        <button onClick={exportJson}>📤 Exporter JSON</button>
        <label>
          📥 Importer JSON
          <input type="file" accept=".json" onChange={e => importJson(e.target.files[0])} />
        </label>
      </div>
      
      {/* Tableau des niveaux */}
      <table className="cbm-levels-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Libellé</th>
            <th>Valeur %</th>
            <th>Score si juste</th>
            <th>Score si faux</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {matrix.levels.map((level, idx) => {
            const scoring = matrix.scoring.find(s => s.level_id === level.id);
            return (
              <tr key={level.id}>
                <td>{idx + 1}</td>
                <td>
                  <input type="text" value={level.label} 
                         onChange={e => updateLevel(level.id, 'label', e.target.value)} />
                </td>
                <td>
                  <input type="number" min="0" max="100" value={level.value}
                         onChange={e => updateLevel(level.id, 'value', Number(e.target.value))} />
                </td>
                <td>
                  <input type="number" value={scoring.correct}
                         onChange={e => updateScoring(level.id, 'correct', Number(e.target.value))} />
                </td>
                <td>
                  <input type="number" value={scoring.incorrect}
                         onChange={e => updateScoring(level.id, 'incorrect', Number(e.target.value))} />
                </td>
                <td>
                  <button onClick={() => removeLevel(level.id)} 
                          disabled={matrix.levels.length <= 2}>🗑️</button>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
      
      <button onClick={addLevel} disabled={matrix.levels.length >= 10}>
        + Ajouter un niveau
      </button>
      
      {/* Preview */}
      <CbmMatrixPreview matrix={matrix} />
    </div>
  );
}
```

Intégrer dans la page de création/édition d'examen.

### Tâche 5 — UI Étudiant : Saisie certitude

Créer composant `<CbmCertaintyInput>` :

```jsx
function CbmCertaintyInput({ matrix, onSelect, currentLevelId }) {
  return (
    <div className="cbm-certainty-input" role="radiogroup" aria-label="Niveau de certitude">
      <p>Quel est votre niveau de certitude ?</p>
      <div className="cbm-levels">
        {matrix.levels.map(level => (
          <button
            key={level.id}
            className={`cbm-level-btn ${currentLevelId === level.id ? 'selected' : ''}`}
            onClick={() => onSelect(level.id)}
            aria-pressed={currentLevelId === level.id}
          >
            <span className="cbm-label">{level.label}</span>
            <span className="cbm-value">{level.value}%</span>
          </button>
        ))}
      </div>
    </div>
  );
}
```

Mini-tutoriel onboarding (modal 1ère fois) :
```jsx
function CbmOnboardingModal({ onClose }) {
  return (
    <Modal title="Bienvenue dans un examen CBM !" onClose={onClose}>
      <p>Cet examen utilise le système <strong>Certainty-Based Marking</strong>.</p>
      <p>Après chaque réponse, vous devrez indiquer votre niveau de certitude.</p>
      <ul>
        <li>✅ <strong>Si juste</strong> : plus vous êtes certain, plus vous gagnez de points.</li>
        <li>❌ <strong>Si faux</strong> : plus vous êtes certain, plus vous perdez de points.</li>
        <li>🎯 <strong>Stratégie</strong> : soyez honnête avec vous-même !</li>
      </ul>
      <button onClick={onClose}>J'ai compris, commencer</button>
    </Modal>
  );
}
```

Stocker le flag "onboarding_shown" en localStorage pour ne pas le remontrer.

### Tâche 6 — Calcul scores + correction

Modifier `PassageManager::submit()` pour :
1. Pour chaque réponse : calculer score base + score CBM si CBM activé
2. Calculer calibration de l'étudiant
3. Stocker dans `passage.score`:
   ```json
   {
     "raw": 45,
     "max": 60,
     "percentage": 75,
     "cbm_score": 42,
     "cbm_calibration": {
       "global_score": 0.82,
       "tendency": "well_calibrated",
       "per_level": [...]
     }
   }
   ```

Créer composant `<CbmScoreBreakdown>` pour la page correction :
- Affiche score total + score CBM
- Graphe "Mes scores par question" (Recharts bar chart)
- Indicateur calibration (texte + emoji 🎯 / ⚠️ / 🤔)

### Tâche 7 — Tests E2E

Créer `backend/tests/e2e/test_cbm_workflow.php` :
- Créer examen avec CBM activé
- Ajouter 5 questions
- Démarrer passage
- Répondre aux 5 questions avec certitudes différentes
- Soumettre
- Vérifier score final
- Vérifier calibration calculée

## ✅ CRITÈRES D'ACCEPTATION

- [ ] CbmManager avec 15+ méthodes typées
- [ ] Tests CbmManager > 90% coverage
- [ ] API `/api/cbm/*` fonctionne (9 endpoints)
- [ ] UI prof crée matrice 3 niveaux en < 2min
- [ ] UI étudiant saisit certitude fluide
- [ ] Score CBM affiché dans correction
- [ ] Calibration calculée correctement
- [ ] Presets save/load fonctionnels
- [ ] Export/import JSON roundtrip OK
- [ ] Tests E2E passent

## 📝 COMMITS ATTENDUS

- `feat(cbm): implement CbmManager core logic`
- `test(cbm): add 30+ tests for CbmManager`
- `feat(cbm): add /api/cbm endpoints`
- `feat(cbm): add CbmMatrixEditor React component`
- `feat(cbm): add CbmCertaintyInput for student`
- `feat(cbm): add CBM scoring in PassageManager`
- `feat(cbm): add calibration calculation`
- `feat(cbm): add correction score breakdown`
- `test(cbm): add E2E workflow test`

## 🚀 FINALISATION

1. PR `feat/p1-cbm-core` → `develop`
2. Tag `v2.0.0-alpha.1`

**Commence par la Tâche 1 (CbmManager) et avance méthodiquement.**
```

---

## Prompt Phase 2 — Types de questions étendus

### 🎯 À copier-coller

```
# CONTEXTE PROJET CERTIO v2.0 — PHASE 2 : TYPES DE QUESTIONS ÉTENDUS

Tu continues sur **Certio v2.0**. Les Phases 0 et 1 sont terminées.

## 🎯 Objectif Phase 2 (4 jours)

Étendre le système pour supporter **7 types de questions** :
1. `true_false` — Vrai/Faux (2 opts, 1 correcte)
2. `mcq_single_4` — QCM 4 radio (format v1)
3. `mcq_single_5` — QCM 5 radio
4. `mcq_single_n` — QCM N radio (4-10)
5. `mcq_multiple_4` — QCM 4 checkbox
6. `mcq_multiple_5` — QCM 5 checkbox
7. `mcq_multiple_n` — QCM N checkbox

## 📋 TÂCHES DÉTAILLÉES

### Tâche 1 — Schéma Question v2

Mettre à jour `docs/SCHEMAS_V2.md` et implémenter la validation du schéma.

Chaque question v2 doit avoir :
- `type` (un des 7 types)
- `subtype_config.num_options` (pour les `_n`)
- `options` (array d'objets avec `id`, `text`, `is_correct`)
- `statement` (énoncé)
- `explanation` (optionnelle)
- `difficulty`, `tags`, `module`, `chapitre`, `theme`

### Tâche 2 — QuestionTypeResolver

Implémenter dans `backend/lib/QuestionTypeResolver.php` :

```php
class QuestionTypeResolver
{
    public const TYPES = [
        'true_false',
        'mcq_single_4',
        'mcq_single_5',
        'mcq_single_n',
        'mcq_multiple_4',
        'mcq_multiple_5',
        'mcq_multiple_n',
    ];
    
    public function getType(array $question): string;
    
    /**
     * Valide la structure d'une question selon son type.
     * @return array ['valid' => bool, 'errors' => []]
     */
    public function validateQuestion(array $question): array;
    
    /**
     * Valide une réponse d'étudiant selon le type.
     */
    public function validateAnswer(array $question, mixed $answer): array;
    
    /**
     * Détermine si la réponse est correcte (complète).
     * Pour multiple: si TOUTES les bonnes sont cochées ET AUCUNE mauvaise.
     */
    public function isCorrect(array $question, mixed $answer): bool;
    
    /**
     * Retourne les IDs des options correctes.
     */
    public function getCorrectOptionIds(array $question): array;
    
    /**
     * Retourne le nombre d'options pour un type.
     */
    public function getNumOptions(string $type, array $subtypeConfig = []): int;
    
    /**
     * Retourne si le type est à choix multiple (checkbox).
     */
    public function isMultipleChoice(string $type): bool;
}
```

### Tâche 3 — UI Création question (React)

Refactor `<QuestionEditor>` React pour supporter tous les types :

```jsx
function QuestionEditor({ question, onSave }) {
  const [type, setType] = React.useState(question?.type || 'mcq_single_4');
  const [numOptions, setNumOptions] = React.useState(4);
  const [statement, setStatement] = React.useState('');
  const [options, setOptions] = React.useState([]);
  const [explanation, setExplanation] = React.useState('');
  
  const isMultiple = type.startsWith('mcq_multiple');
  const isConfigurable = type.endsWith('_n');
  
  // Reset options when type changes
  React.useEffect(() => {
    const n = isConfigurable ? numOptions : 
              type === 'true_false' ? 2 :
              type.includes('_4') ? 4 : 5;
    setOptions(Array.from({length: n}, (_, i) => ({
      id: String.fromCharCode(65 + i), // A, B, C, ...
      text: '',
      is_correct: false
    })));
  }, [type, numOptions]);
  
  function updateOption(idx, field, value) { /* ... */ }
  
  function validateAndSave() {
    const correctCount = options.filter(o => o.is_correct).length;
    if (isMultiple && correctCount < 1) {
      alert('Au moins une bonne réponse requise');
      return;
    }
    if (!isMultiple && correctCount !== 1) {
      alert('Exactement une bonne réponse requise');
      return;
    }
    onSave({ type, subtype_config: { num_options: options.length }, 
             statement, options, explanation });
  }
  
  return (
    <div className="question-editor">
      <div className="form-group">
        <label>Type de question</label>
        <select value={type} onChange={e => setType(e.target.value)}>
          <option value="true_false">Vrai / Faux</option>
          <optgroup label="Choix unique (1 bonne réponse)">
            <option value="mcq_single_4">QCM 4 options</option>
            <option value="mcq_single_5">QCM 5 options</option>
            <option value="mcq_single_n">QCM N options configurables</option>
          </optgroup>
          <optgroup label="Choix multiple (plusieurs bonnes)">
            <option value="mcq_multiple_4">QCM multi 4 options</option>
            <option value="mcq_multiple_5">QCM multi 5 options</option>
            <option value="mcq_multiple_n">QCM multi N options</option>
          </optgroup>
        </select>
      </div>
      
      {isConfigurable && (
        <div className="form-group">
          <label>Nombre d'options</label>
          <input type="number" min="4" max="10" 
                 value={numOptions} onChange={e => setNumOptions(Number(e.target.value))} />
        </div>
      )}
      
      <div className="form-group">
        <label>Énoncé (Markdown + LaTeX)</label>
        <textarea value={statement} onChange={e => setStatement(e.target.value)} />
      </div>
      
      <div className="form-group">
        <label>Options</label>
        {options.map((opt, idx) => (
          <div key={idx} className="option-row">
            <span className="option-id">{opt.id}</span>
            <input type="text" value={opt.text} 
                   onChange={e => updateOption(idx, 'text', e.target.value)} />
            <label>
              <input type={isMultiple ? 'checkbox' : 'radio'} 
                     name="correct"
                     checked={opt.is_correct}
                     onChange={e => updateOption(idx, 'is_correct', e.target.checked)} />
              Correcte
            </label>
          </div>
        ))}
      </div>
      
      <div className="form-group">
        <label>Explication (optionnelle, Markdown + LaTeX)</label>
        <textarea value={explanation} onChange={e => setExplanation(e.target.value)} />
      </div>
      
      <button onClick={validateAndSave}>Enregistrer</button>
      
      {/* Preview */}
      <QuestionPreview question={{ type, statement, options, explanation }} />
    </div>
  );
}
```

### Tâche 4 — UI Réponse étudiant

Refactor `<QuestionRenderer>` pour rendre selon le type :

```jsx
function QuestionRenderer({ question, answer, onAnswer }) {
  const isMultiple = question.type.startsWith('mcq_multiple');
  
  function handleSelect(optionId) {
    if (isMultiple) {
      const current = answer?.selected_options || [];
      const updated = current.includes(optionId)
        ? current.filter(id => id !== optionId)
        : [...current, optionId];
      onAnswer({ selected_options: updated });
    } else {
      onAnswer({ selected_options: [optionId] });
    }
  }
  
  return (
    <div className="question-renderer">
      <div className="statement" 
           dangerouslySetInnerHTML={{__html: renderMarkdown(question.statement)}} />
      
      <div className="options" role={isMultiple ? 'group' : 'radiogroup'}>
        {question.options.map(opt => {
          const isSelected = (answer?.selected_options || []).includes(opt.id);
          return (
            <label key={opt.id} className={`option ${isSelected ? 'selected' : ''}`}>
              <input
                type={isMultiple ? 'checkbox' : 'radio'}
                name={`q-${question.id}`}
                value={opt.id}
                checked={isSelected}
                onChange={() => handleSelect(opt.id)}
              />
              <span className="option-id">{opt.id}.</span>
              <span className="option-text"
                    dangerouslySetInnerHTML={{__html: renderMarkdown(opt.text)}} />
            </label>
          );
        })}
      </div>
      
      {isMultiple && (
        <p className="hint">💡 Cochez toutes les bonnes réponses</p>
      )}
    </div>
  );
}
```

### Tâche 5 — Migration questions v1 → v2

Implémenter dans `scripts/migrate-v1-to-v2.php` la partie questions :

```php
function migrateQuestion(array $v1): array {
    // v1 format : options + correct_index
    // v2 format : options[].is_correct = true|false
    
    $options = [];
    foreach ($v1['options'] as $idx => $text) {
        $options[] = [
            'id' => chr(65 + $idx), // A, B, C, D
            'text' => $text,
            'is_correct' => ($idx === $v1['correct_index']),
        ];
    }
    
    // Déterminer le type selon le nombre d'options
    $n = count($options);
    $type = match(true) {
        $n === 2 => 'true_false',
        $n === 4 => 'mcq_single_4',
        $n === 5 => 'mcq_single_5',
        default => 'mcq_single_n',
    };
    
    return [
        'id' => $v1['id'],
        'schema_version' => 2,
        'workspace_id' => 'WKS-DEFAULT',
        'creator_id' => $v1['creator_id'] ?? 'LEGACY',
        'visibility' => 'private',
        'type' => $type,
        'subtype_config' => ['num_options' => $n],
        'statement' => $v1['statement'],
        'statement_format' => 'markdown_with_latex',
        'options' => $options,
        'explanation' => $v1['explanation'] ?? '',
        'difficulty' => $v1['difficulty'] ?? 'medium',
        'tags' => $v1['tags'] ?? [],
        'module' => $v1['module'] ?? '',
        'chapitre' => $v1['chapitre'] ?? '',
        'theme' => $v1['theme'] ?? '',
        'locale' => 'fr',
        'created_at' => $v1['created_at'] ?? date('c'),
        'updated_at' => date('c'),
        'community' => [
            'published_at' => null,
            'license' => null,
            'forked_from' => null,
            'original_author_id' => null,
            'usage_count' => 0,
            'rating_average' => null,
            'rating_count' => 0,
            'flag_count' => 0,
        ],
    ];
}
```

### Tâche 6 — Tests

Créer `backend/tests/test_question_type_resolver.php` avec 40+ tests :
- Validation pour chaque type (8 cas x 7 types = 56 tests min)
- Validation réponses
- isCorrect pour tous les cas
- getCorrectOptionIds
- Migration v1 → v2 (20 questions sample)

## ✅ CRITÈRES D'ACCEPTATION

- [ ] 7 types supportés end-to-end
- [ ] Création question intuitive (< 3min)
- [ ] Réponse étudiant fluide
- [ ] Migration 320 questions v1 sans erreur
- [ ] Tests > 85% coverage
- [ ] 0 régression v1

## 📝 COMMITS ATTENDUS

- `feat(questions): add QuestionTypeResolver`
- `test(questions): add QuestionTypeResolver tests`
- `feat(questions): extend QuestionEditor for 7 types`
- `feat(questions): extend QuestionRenderer for multi-choice`
- `feat(migration): migrate questions v1 to v2`
- `docs(questions): update QUESTION_TYPES.md`

## 🚀 FINALISATION

1. PR `feat/p2-question-types` → `develop`
2. Tag `v2.0.0-alpha.2`
```

---

## Prompt Phase 3 — Scoring & Analytics

### 🎯 À copier-coller

```
# CONTEXTE CERTIO v2.0 — PHASE 3 : SCORING & ANALYTICS

Phases 0, 1, 2 terminées. Tu as le CBM core + 7 types de questions.

## 🎯 Objectif Phase 3 (4 jours)

1. Scoring multi-réponses (3 modes)
2. Combinaison CBM + multi-réponses
3. Analytics enrichis pour prof (calibration, distracteurs, radar)
4. Dashboard étudiant personnel
5. Exports CSV/Excel avec colonnes CBM

## 📋 TÂCHES

### Tâche 1 — Modes scoring multi-réponses

Étendre `QuestionTypeResolver::calculateScore()` :

```php
/**
 * Calcule le score d'une réponse selon le mode.
 *
 * Modes supportés:
 * - 'all_or_nothing': tout juste = 1, sinon 0
 * - 'proportional_strict': (bonnes cochées - fausses cochées), clamped à [0, total_correctes]
 * - 'proportional_normalized': max(0, (bonnes - fausses) / total_correctes)
 *
 * @param array  $question  La question v2
 * @param array  $answer    Réponse de l'étudiant
 * @param string $mode      'all_or_nothing' | 'proportional_strict' | 'proportional_normalized'
 * @return float Score entre 0.0 et 1.0
 */
public function calculateScore(array $question, array $answer, string $mode = 'all_or_nothing'): float
{
    $correctIds = $this->getCorrectOptionIds($question);
    $selectedIds = $answer['selected_options'] ?? [];
    
    // Pour questions non-multiple, toujours all_or_nothing
    if (!$this->isMultipleChoice($question['type'])) {
        return $this->isCorrect($question, $answer) ? 1.0 : 0.0;
    }
    
    $correctSelected = count(array_intersect($selectedIds, $correctIds));
    $wrongSelected = count(array_diff($selectedIds, $correctIds));
    $totalCorrect = count($correctIds);
    
    switch ($mode) {
        case 'all_or_nothing':
            return ($correctSelected === $totalCorrect && $wrongSelected === 0) ? 1.0 : 0.0;
        
        case 'proportional_strict':
            $score = $correctSelected - $wrongSelected;
            return max(0.0, min(1.0, $score / $totalCorrect));
        
        case 'proportional_normalized':
            return max(0.0, ($correctSelected - $wrongSelected) / $totalCorrect);
        
        default:
            throw new \InvalidArgumentException("Mode inconnu: $mode");
    }
}
```

Ajouter `scoring.multi_answer_mode` dans le schéma Examen v2.

### Tâche 2 — Combinaison CBM + multi-réponses

Dans `PassageManager::computeScore()`, combiner :

```php
foreach ($answers as $qId => $answer) {
    $question = $this->getQuestion($qId);
    
    // Score base selon type
    $baseScore = $typeResolver->calculateScore($question, $answer, $examen['scoring']['multi_answer_mode']);
    
    if ($examen['cbm']['enabled']) {
        // Déterminer si "juste" pour le CBM (seuil 50%)
        $isCorrect = $baseScore >= 0.5;
        $cbmScore = $cbmManager->calculateScore($isCorrect, $answer['cbm_level_id'], $examen['cbm']['matrix']);
        // Score final = baseScore × (cbmScore / maxCbmScore) + bonus/malus
        $finalScore = $baseScore * $cbmScore;
    } else {
        $finalScore = $baseScore;
    }
    
    $totalScore += $finalScore * ($question['weight'] ?? 1.0);
}
```

### Tâche 3 — Analytics enrichis

Nouveaux endpoints dans `backend/api/analytics.php` :
- `GET /api/analytics/cbm-calibration/{examenId}` — calibration de tous les passages
- `GET /api/analytics/distractors/{examenId}` — fréquence de chaque mauvaise réponse
- `GET /api/analytics/student-radar/{studentEmail}` — radar par thème/chapitre
- `GET /api/analytics/question-difficulty/{examenId}` — taux de réussite par question

Composants React à créer dans `frontend/assets/components-analytics-v2.jsx` :

#### `<CbmCalibrationChart>`
Scatter plot : X = certitude moyenne déclarée, Y = taux de réussite réel
- Ligne parfaite y=x
- Points au-dessus = underconfident
- Points en dessous = overconfident

#### `<DistractorsAnalysis>`
Bar chart par question : pour chaque option, nombre de sélections
- Option correcte en vert
- Distracteurs populaires en rouge
- Permet d'identifier les "attracteurs trompeurs"

#### `<StudentRadar>`
Radar chart : pour un étudiant, performance par thème
- Axes = thèmes/chapitres
- Valeur = moyenne scores sur questions du thème

### Tâche 4 — Dashboard étudiant

Créer page `/frontend/etudiant/dashboard.html` :

```jsx
function StudentDashboard({ studentEmail }) {
  const [history, setHistory] = React.useState([]);
  const [calibration, setCalibration] = React.useState(null);
  const [radar, setRadar] = React.useState(null);
  
  React.useEffect(() => {
    // Charger historique passages de l'étudiant
    fetch(`/api/passages/history?email=${studentEmail}`, { credentials: 'include' })
      .then(r => r.json()).then(data => setHistory(data.data));
    
    // Charger calibration globale
    fetch(`/api/analytics/student-calibration?email=${studentEmail}`, { credentials: 'include' })
      .then(r => r.json()).then(data => setCalibration(data.data));
    
    // Charger radar
    fetch(`/api/analytics/student-radar/${studentEmail}`, { credentials: 'include' })
      .then(r => r.json()).then(data => setRadar(data.data));
  }, [studentEmail]);
  
  return (
    <div className="student-dashboard">
      <h1>Mon tableau de bord</h1>
      
      <div className="kpi-grid">
        <KpiCard title="Examens passés" value={history.length} />
        <KpiCard title="Score moyen" value={calculateAverage(history) + '%'} />
        <KpiCard title="Calibration CBM" value={calibration?.tendency} />
        <KpiCard title="Thème fort" value={findStrongestTheme(radar)} />
      </div>
      
      <section>
        <h2>Progression dans le temps</h2>
        <LineChart data={history.map(h => ({ date: h.submitted_at, score: h.score.percentage }))} />
      </section>
      
      <section>
        <h2>Performance par thème</h2>
        <StudentRadar data={radar} />
      </section>
      
      <section>
        <h2>Calibration CBM</h2>
        <CalibrationDetail data={calibration} />
      </section>
      
      <section>
        <h2>Historique détaillé</h2>
        <PassagesTable history={history} />
      </section>
    </div>
  );
}
```

### Tâche 5 — Exports enrichis

Étendre `AnalyticsManager::exportToCsv()` et `exportToExcel()` :

Colonnes CSV (ajoutées) :
- `cbm_enabled` (Y/N)
- `cbm_level_declared`
- `cbm_score`
- `calibration_score`
- `tendency` (well_calibrated/over/under)

Excel multi-feuilles :
- **Feuille 1** : Résumé (scores globaux)
- **Feuille 2** : Détail par question (avec colonnes CBM)
- **Feuille 3** : Analyse CBM (calibration par étudiant)
- **Feuille 4** : Distracteurs (pour aide au prof)

## ✅ CRITÈRES D'ACCEPTATION

- [ ] 3 modes scoring multi-réponses fonctionnels
- [ ] Combinaison CBM × multi correcte
- [ ] 4 nouveaux endpoints analytics
- [ ] Dashboard étudiant avec 4 sections
- [ ] Export Excel multi-feuilles OK
- [ ] Tests > 85%

## 📝 COMMITS ATTENDUS

- `feat(scoring): add 3 multi-answer scoring modes`
- `feat(scoring): combine CBM with multi-answer scoring`
- `feat(analytics): add calibration endpoint`
- `feat(analytics): add distractors endpoint`
- `feat(analytics): add student radar`
- `feat(dashboard): add student dashboard page`
- `feat(export): enrich CSV/Excel with CBM data`

## 🚀 FINALISATION

1. PR `feat/p3-scoring-analytics` → `develop`
2. Tag `v2.0.0-beta.1` (premier beta !)
```

---

## Prompt Phase 4 — Documentation interactive

### 🎯 À copier-coller

```
# CONTEXTE CERTIO v2.0 — PHASE 4 : DOCUMENTATION INTERACTIVE

Peut être faite **en parallèle** des Phases 1-3 (dépend seulement de P0).

## 🎯 Objectif Phase 4 (5 jours)

Intégrer une **documentation interactive Markdown hybride** avec :
- Parser côté client (marked.js)
- Navigation auto depuis arborescence
- RBAC par rôle (admin/prof/étudiant)
- 4 familles de placeholders
- Recherche full-text basique

## 📋 TÂCHES

### Tâche 1 — DocumentationManager

Implémenter `backend/lib/DocumentationManager.php` :

```php
class DocumentationManager
{
    private string $docsRoot;
    private Logger $logger;
    
    public function __construct(string $docsRoot, Logger $logger) {
        $this->docsRoot = $docsRoot; // ex: /path/to/docs-interactive/
        $this->logger = $logger;
    }
    
    /**
     * Retourne l'arborescence des docs accessibles pour un rôle.
     *
     * RBAC rules:
     * - admin: tout (admin/, prof/, etudiant/, shared/)
     * - enseignant: prof/, etudiant/, shared/
     * - etudiant: etudiant/, shared/
     *
     * @return array Tree avec {name, path, type: 'folder'|'file', children?}
     */
    public function listDocs(string $role): array;
    
    /**
     * Charge le contenu d'un doc (Markdown source).
     * Vérifie les droits d'accès selon le rôle.
     *
     * @throws \RuntimeException si non autorisé
     * @throws \RuntimeException si fichier introuvable
     */
    public function getDoc(string $path, string $role): string;
    
    /**
     * Vérifie l'accès à un chemin.
     */
    public function checkAccess(string $path, string $role): bool;
    
    /**
     * Recherche full-text dans les docs accessibles par le rôle.
     *
     * @return array Liste de {path, title, snippet, score}
     */
    public function search(string $query, string $role, int $limit = 20): array;
    
    /**
     * Extrait la TOC (table des matières) d'un markdown.
     * @return array [{level: int, text: string, anchor: string}, ...]
     */
    public function extractToc(string $markdown): array;
    
    /**
     * Parse les placeholders custom dans le markdown.
     * Remplace :::diagram ... ::: par HTML renderable.
     */
    public function parsePlaceholders(string $markdown): string;
}
```

### Tâche 2 — Endpoint `/api/docs`

```php
// backend/api/docs.php

// GET /api/docs — liste arbre
// GET /api/docs/{path} — contenu d'un doc
// POST /api/docs/search — recherche
```

Protection :
- Auth obligatoire (pour tous)
- Filtrage auto par rôle de l'utilisateur connecté

### Tâche 3 — UI React DocsViewer

Créer `frontend/assets/components-docs.jsx` :

```jsx
function DocsViewer({ userRole }) {
  const [tree, setTree] = React.useState(null);
  const [currentPath, setCurrentPath] = React.useState(null);
  const [content, setContent] = React.useState('');
  const [toc, setToc] = React.useState([]);
  const [searchQuery, setSearchQuery] = React.useState('');
  const [searchResults, setSearchResults] = React.useState([]);
  
  React.useEffect(() => {
    fetch('/api/docs', { credentials: 'include' })
      .then(r => r.json())
      .then(data => setTree(data.data));
  }, []);
  
  async function loadDoc(path) {
    const res = await fetch(`/api/docs/${encodeURIComponent(path)}`, { credentials: 'include' });
    const data = await res.json();
    setCurrentPath(path);
    setContent(data.content);
    setToc(data.toc);
  }
  
  async function search(query) {
    const res = await fetch('/api/docs/search', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
      credentials: 'include',
      body: JSON.stringify({ query })
    });
    const data = await res.json();
    setSearchResults(data.data);
  }
  
  return (
    <div className="docs-viewer">
      {/* Sidebar navigation */}
      <aside className="docs-sidebar">
        <input 
          type="search" 
          placeholder="Rechercher…"
          value={searchQuery}
          onChange={e => { setSearchQuery(e.target.value); search(e.target.value); }}
        />
        {searchResults.length > 0 ? (
          <SearchResults results={searchResults} onSelect={loadDoc} />
        ) : (
          <DocsTree tree={tree} onSelect={loadDoc} currentPath={currentPath} />
        )}
      </aside>
      
      {/* Main content */}
      <main className="docs-main">
        <Breadcrumbs path={currentPath} />
        <MarkdownRenderer content={content} />
      </main>
      
      {/* TOC droite */}
      <aside className="docs-toc">
        <h3>Table des matières</h3>
        <Toc items={toc} />
      </aside>
    </div>
  );
}

function MarkdownRenderer({ content }) {
  const [html, setHtml] = React.useState('');
  
  React.useEffect(() => {
    // marked.js parsing + custom placeholders
    const rendered = marked.parse(content, {
      gfm: true,
      breaks: true,
      highlight: (code, lang) => hljs.highlight(code, { language: lang }).value
    });
    // Sanitize + render placeholders
    const cleaned = DOMPurify.sanitize(rendered);
    const withPlaceholders = renderPlaceholders(cleaned);
    setHtml(withPlaceholders);
    
    // Post-render: Mermaid diagrams
    setTimeout(() => {
      if (window.mermaid) window.mermaid.init();
      // KaTeX inline
      renderMathInElement(document.querySelector('.docs-main'));
    }, 100);
  }, [content]);
  
  return <div className="markdown-body" dangerouslySetInnerHTML={{__html: html}} />;
}
```

### Tâche 4 — Système de placeholders

Parser custom dans `MarkdownRenderer` pour 4 types :

```markdown
:::diagram type=mermaid description="Architecture globale"
graph TB
  A[Client] --> B[Server]
:::

:::image description="Dashboard admin" prompt="DALL-E: generate dashboard..."
![Dashboard](./assets/dashboard.png)
:::

:::video description="Tuto création examen" prompt="HeyGen: generate tuto..."
https://youtube.com/watch?v=XXX
:::

:::interactive type="quiz"
{
  "question": "Qu'est-ce que le CBM ?",
  "options": ["Hasard", "Certitude", "Mémoire"],
  "correct": 1
}
:::
```

Parsing JS :
```javascript
function renderPlaceholders(html) {
  // Regex pour ::: XXX ... :::
  return html.replace(/:::([a-z]+)(.*?)\n([\s\S]*?):::/g, (match, type, attrs, content) => {
    const parsedAttrs = parseAttributes(attrs);
    switch(type) {
      case 'diagram': return renderDiagram(content, parsedAttrs);
      case 'image': return renderImagePlaceholder(content, parsedAttrs);
      case 'video': return renderVideoPlaceholder(content, parsedAttrs);
      case 'interactive': return renderInteractive(content, parsedAttrs);
      default: return match;
    }
  });
}
```

Si asset manquant, afficher un placeholder avec :
- Type attendu
- Description
- Prompt IA à utiliser pour générer
- Bouton "Copier le prompt"

### Tâche 5 — Contenu initial (20+ pages)

Créer structure `/docs-interactive/` :

```
docs-interactive/
├── admin/
│   ├── 01-dashboard.md
│   ├── 02-comptes-utilisateurs.md
│   ├── 03-workspaces.md
│   ├── 04-banque-communautaire.md
│   ├── 05-backups-restauration.md
│   ├── 06-monitoring-sante.md
│   ├── 07-audit-log.md
│   └── 08-configuration-systeme.md
├── prof/
│   ├── 01-premiers-pas.md
│   ├── 02-banque-questions.md
│   ├── 03-creer-examen.md
│   ├── 04-cbm-guide.md
│   ├── 05-distribuer-examen.md
│   ├── 06-suivre-passages.md
│   ├── 07-analytics-guide.md
│   ├── 08-exports.md
│   ├── 09-import-moodle.md
│   └── 10-faq-prof.md
├── etudiant/
│   ├── 01-comment-passer-examen.md
│   ├── 02-comprendre-cbm.md
│   ├── 03-consulter-correction.md
│   └── 04-faq-etudiant.md
└── shared/
    ├── 01-glossaire.md
    ├── 02-support.md
    ├── 03-licences.md
    ├── 04-privacy-rgpd.md
    └── 05-about.md
```

Pour chaque page, inclure :
- Titre H1
- Table des matières
- Contenu structuré
- Placeholders IA pour contenus à générer
- Liens vers autres pages pertinentes

### Tâche 6 — Pages HTML d'entrée

Créer :
- `/frontend/admin/docs.html` → charge DocsViewer avec role=admin
- `/frontend/prof/docs.html` → role=enseignant
- `/frontend/etudiant/docs.html` → role=etudiant

Menu depuis dashboards admin/prof/étudiant → lien "📚 Documentation".

## ✅ CRITÈRES D'ACCEPTATION

- [ ] DocumentationManager avec RBAC strict
- [ ] Admin voit tout, prof partial, étudiant minimal
- [ ] 20+ pages markdown initiales
- [ ] Placeholders fonctionnels (4 types)
- [ ] Recherche trouve dans pages accessibles
- [ ] Mermaid, KaTeX, code highlighting OK
- [ ] Responsive mobile

## 📝 COMMITS ATTENDUS

- `feat(docs): add DocumentationManager with RBAC`
- `feat(docs): add /api/docs endpoint`
- `feat(docs): add DocsViewer React component`
- `feat(docs): add 4 types of placeholders`
- `docs(content): add 8 admin documentation pages`
- `docs(content): add 10 prof documentation pages`
- `docs(content): add 4 student documentation pages`
- `docs(content): add 5 shared documentation pages`
- `feat(docs): add search full-text`

## 🚀 FINALISATION

1. PR `feat/p4-documentation` → `develop`
2. Tag `v2.0.0-beta.2`
```

---

## 💡 Conseils d'utilisation des prompts

### Si l'IA propose trop de choses d'un coup

Dis-lui : *"Limite-toi à la Tâche 1 pour l'instant. Je validerai avant de passer à la Tâche 2."*

### Si un prompt est trop long pour le contexte de l'IA

Découpe par tâche :
- Copie uniquement la **Tâche N** avec son contexte
- Plus court = meilleure qualité

### Si l'IA oublie le contexte

Re-colle le **bloc "CONTEXTE PROJET CERTIO v2.0"** en début de chaque tâche.

### Pour Copilot Agent (VS Code)

Copilot Agent préfère des prompts plus courts et orientés-action. Formule comme :
*"Implémente la Tâche X du plan Certio v2.0 Phase Y"* en fournissant le fichier de référence.

### Pour Claude Code (terminal)

Claude Code accepte de très longs prompts. Tu peux coller tout le bloc d'une phase et dire :
*"Lis ce plan et commence par la Tâche 1. Fais un commit après chaque tâche validée."*

---

## Conclusion

Ces 5 prompts (P0-P4) te permettent de construire le **core de Certio v2.0**. La partie suivante (P5-P7) sera livrée dans le **Livrable 4/5**.

**Estimation totale** : ~21 jours pour cette première moitié (P0-P4).

---

© 2026 Mohamed EL AFRIT — mohamed@elafrit.com  
Certio v2.0 — CC BY-NC-SA 4.0
