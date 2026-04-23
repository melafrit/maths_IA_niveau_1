# 🎯 Prompts VS Code — Phases 0 à 4

> **Prompts optimisés copier-coller pour Claude Code / Cursor / Copilot Agent**

| Champ | Valeur |
|---|---|
| **Livrable** | 3/5 |
| **Phases couvertes** | P0, P1, P2, P3, P4 |
| **Version** | 1.0 |
| **Auteur** | Mohamed EL AFRIT |
| **Licence** | CC BY-NC-SA 4.0 |

---

## 📖 Comment utiliser ces prompts

### 🛠️ Dans VS Code avec Claude Code / Cursor

1. **Ouvrir** le repo `certio` (anciennement `maths_IA_niveau_1`) dans VS Code
2. **Créer la branche** de la phase :
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feat/pX-nom-phase
   ```
3. **Copier-coller** le prompt complet de la phase dans ton assistant IA
4. **Valider** que l'IA a bien compris (elle doit lister les tâches)
5. **Laisser exécuter** (commit après commit si possible)
6. **Vérifier** chaque livrable (voir Definition of Done)
7. **Merge** vers develop + tag

### 💡 Règles d'or

- **1 prompt = 1 phase** (ne pas mélanger)
- **Toujours** lire les fichiers existants avant de modifier
- **Tests obligatoires** (ne pas passer à la phase suivante sans tests verts)
- **Commits atomiques** (une feature = un commit)
- **Conventional commits** obligatoires

### 🤖 Spécificités par outil IA

| Outil | Spécificités |
|---|---|
| **Claude Code** | Support agentique, peut exécuter bash, lire/écrire multiples fichiers. Meilleur pour prompts longs. |
| **Cursor** | Mode Composer pour multi-fichiers, Chat pour QA ponctuelles. Très rapide. |
| **Copilot Agent** | Intégration native VS Code. Plus basique, découper les prompts en étapes. |

---

## 📋 Sommaire

- [Prompt P0 — Fondations & Rebranding](#prompt-p0--fondations--rebranding)
- [Prompt P1 — CBM Core](#prompt-p1--cbm-core)
- [Prompt P2 — Types de questions étendus](#prompt-p2--types-de-questions-étendus)
- [Prompt P3 — Scoring & Analytics](#prompt-p3--scoring--analytics)
- [Prompt P4 — Documentation interactive](#prompt-p4--documentation-interactive)

---

# Prompt P0 — Fondations & Rebranding

## 📝 Informations phase

- **Durée** : 3 jours (18-22h)
- **Branche Git** : `feat/p0-fondations`
- **Tag final** : `v2.0.0-alpha.0`
- **Dépendances** : aucune

## 🎯 Objectif de la phase

Rebrander intégralement l'application IPSSI Examens en **Certio**, créer la structure des 13 nouveaux managers v2.0, et poser les fondations (i18n, PWA, workspace par défaut).

---

## 🤖 PROMPT À COPIER DANS VS CODE

```
# CONTEXTE PROJET

Tu assistes Mohamed EL AFRIT (mohamed@elafrit.com) sur la migration v1 → v2 d'une plateforme web d'examens QCM.

## Projet : Certio v2.0 (anciennement IPSSI Examens)

**Stack technique** :
- Backend : PHP 8.3 strict types, AUCUNE dépendance Composer
- Frontend : React 18 via CDN UMD + Babel Standalone (pas de bundler)
- Persistance : fichiers JSON dans data/
- Namespace PHP : Examens\Lib (à renommer vers Certio\Lib en Phase 7 - PAS MAINTENANT)

**Architecture layered stricte (5 couches)** :
1. Présentation : React 18 dans HTML
2. Routing : backend/public/index.php
3. API : backend/api/*.php (9 endpoints)
4. Métier : backend/lib/*.php (16 managers actuels)
5. Persistance : data/*.json via FileStorage

**Managers existants (v1)** :
Auth, Session, Csrf, Logger, Response, FileStorage, BanqueManager, ExamenManager, PassageManager, AnalyticsManager, BackupManager, HealthChecker, RateLimiter, RoleRateLimiter, Mailer, EmailTemplate

**Tests** : 389/389 passent actuellement. Ne PAS les casser.

# OBJECTIF DE LA PHASE 0

Rebrander IPSSI Examens → Certio et poser les fondations v2.0 (sans implémenter la logique métier des nouveaux managers).

# TÂCHES À EXÉCUTER (dans l'ordre)

## Tâche 1 — Configuration de branding centralisée

**1.1** Créer `backend/config/branding.php` avec ce contenu exact :
```php
<?php
/**
 * Configuration centralisée du branding Certio
 * Modifier UNIQUEMENT ce fichier pour changer le nom de l'app partout.
 * 
 * @license CC BY-NC-SA 4.0
 * @author Mohamed EL AFRIT <mohamed@elafrit.com>
 */
declare(strict_types=1);

return [
    'app_name'        => 'Certio',
    'app_tagline'     => 'Certainty-Based Assessment Platform',
    'app_version'     => '2.0.0-alpha.0',
    'app_url'         => 'https://certio.app',
    'logo_path'       => '/assets/img/logo.svg',
    'logo_alt_path'   => '/assets/img/logo-dark.svg',
    'favicon_path'    => '/assets/img/favicon.ico',
    'primary_color'   => '#1a365d',
    'secondary_color' => '#48bb78',
    'accent_color'    => '#ed8936',
    'success_color'   => '#48bb78',
    'danger_color'    => '#e53e3e',
    'contact_email'   => 'mohamed@elafrit.com',
    'support_email'   => 'mohamed@elafrit.com',
    'noreply_email'   => 'mohamed@elafrit.com',
    'copyright_owner' => 'Mohamed EL AFRIT',
    'copyright_year'  => '2026',
    'copyright_email' => 'mohamed@elafrit.com',
    'license'         => 'CC BY-NC-SA 4.0',
    'license_url'     => 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
];
```

**1.2** Créer `frontend/assets/branding.js` avec ce contenu exact :
```javascript
/**
 * Configuration centralisée du branding Certio (côté client)
 * Modifier UNIQUEMENT ce fichier pour changer le nom de l'app partout.
 * 
 * @license CC BY-NC-SA 4.0
 * @author Mohamed EL AFRIT <mohamed@elafrit.com>
 */
(function(global) {
  'use strict';
  
  global.BRANDING = {
    appName: 'Certio',
    appTagline: 'Certainty-Based Assessment Platform',
    appVersion: '2.0.0-alpha.0',
    appUrl: 'https://certio.app',
    logoUrl: '/assets/img/logo.svg',
    logoAltUrl: '/assets/img/logo-dark.svg',
    faviconUrl: '/assets/img/favicon.ico',
    primaryColor: '#1a365d',
    secondaryColor: '#48bb78',
    accentColor: '#ed8936',
    contactEmail: 'mohamed@elafrit.com',
    supportEmail: 'mohamed@elafrit.com',
    copyrightOwner: 'Mohamed EL AFRIT',
    copyrightYear: '2026',
    license: 'CC BY-NC-SA 4.0',
    licenseUrl: 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
  };
  
  // Helper : remplace {appName}, {copyrightYear}, etc. dans un texte
  global.BRANDING.format = function(template) {
    return template.replace(/\{(\w+)\}/g, (match, key) => {
      return global.BRANDING[key] !== undefined ? global.BRANDING[key] : match;
    });
  };
  
  Object.freeze(global.BRANDING);
})(window);
```

**1.3** Créer `backend/lib/BrandingManager.php` :
```php
<?php
declare(strict_types=1);

namespace Examens\Lib;

/**
 * BrandingManager — Charge la config branding et fournit helpers
 * 
 * @license CC BY-NC-SA 4.0
 * @author Mohamed EL AFRIT
 */
final class BrandingManager
{
    private static ?array $config = null;
    
    public static function load(): array
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../config/branding.php';
        }
        return self::$config;
    }
    
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::load()[$key] ?? $default;
    }
    
    public static function appName(): string
    {
        return self::get('app_name', 'Certio');
    }
    
    public static function appVersion(): string
    {
        return self::get('app_version', '2.0.0');
    }
    
    public static function email(string $type = 'contact'): string
    {
        $key = $type . '_email';
        return self::get($key, 'mohamed@elafrit.com');
    }
    
    public static function copyrightLine(): string
    {
        $year = self::get('copyright_year');
        $owner = self::get('copyright_owner');
        $license = self::get('license');
        return "© {$year} {$owner} — {$license}";
    }
    
    /**
     * Remplace les placeholders {app_name}, {email}, etc. dans un texte
     */
    public static function format(string $template): string
    {
        $config = self::load();
        return preg_replace_callback('/\{(\w+)\}/', function($matches) use ($config) {
            return $config[$matches[1]] ?? $matches[0];
        }, $template);
    }
}
```

## Tâche 2 — Remplacement global "IPSSI" → "Certio"

**2.1** Faire un `grep -ri "ipssi" --include="*.php" --include="*.js" --include="*.html" --include="*.md" backend/ frontend/ docs/` pour lister toutes les occurrences.

**2.2** Remplacer dans TOUS les fichiers PHP/JS/HTML :
- `IPSSI Examens` → référence à `BrandingManager::appName()` en PHP ou `window.BRANDING.appName` en JS
- Si c'est un texte statique (commentaire, titre HTML) : remplacer par `Certio`
- `IPSSI` seul → `Certio`
- Adresses email `m.elafrit@ecole-ipssi.net` → `mohamed@elafrit.com`
- Domaine `ecole-ipssi.net` → `elafrit.com`

**2.3** Dans les templates emails `backend/templates/emails/*.html` :
- Remplacer les strings codées en dur par `{app_name}`, `{copyright_year}`, `{contact_email}`
- S'assurer que `Mailer` passe ces variables lors du render

**2.4** Dans les pages HTML `frontend/**/*.html` :
- Title : `<title>IPSSI Examens</title>` → `<title>Certio</title>`
- Ajouter `<script src="/assets/branding.js"></script>` AVANT les scripts React
- Footer : utiliser `window.BRANDING.format('© {copyrightYear} {copyrightOwner}')`

**2.5** Dans la documentation `docs/*.md` :
- Titres principaux : "IPSSI Examens" → "Certio"
- Adresses email partout
- Mentions école IPSSI → supprimer ou remplacer par "votre établissement"

## Tâche 3 — Placeholder logo + favicon

**3.1** Créer `frontend/assets/img/logo.svg` avec un logo placeholder (peut être un cercle avec "C" ou "Certio" en texte) :
```xml
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 60" width="200" height="60">
  <rect width="60" height="60" rx="12" fill="#1a365d"/>
  <text x="30" y="42" font-family="Arial, sans-serif" font-size="32" font-weight="bold" fill="white" text-anchor="middle">C</text>
  <text x="70" y="42" font-family="Arial, sans-serif" font-size="28" font-weight="600" fill="#1a365d">Certio</text>
</svg>
```

**3.2** Créer `frontend/assets/img/favicon.svg` (version simple du logo).

**3.3** Générer `frontend/assets/img/favicon.ico` depuis le SVG (placeholder 32x32).

**3.4** Créer un README dans `frontend/assets/img/` expliquant que ces fichiers sont des placeholders à remplacer par les vrais assets marketing.

## Tâche 4 — 13 Managers skeleton

Créer les 13 fichiers ci-dessous dans `backend/lib/`. Chacun doit :
- Avoir le namespace `Examens\Lib`
- Avoir `declare(strict_types=1);`
- Avoir la classe `final class` avec constructeur (accepter FileStorage, Logger au minimum)
- Avoir un docblock avec description de responsabilité
- Avoir une méthode `version()` retournant `'2.0.0-alpha.0'`
- Avoir les méthodes publiques principales avec signature et body `throw new \RuntimeException('Not implemented in P0');`

### CbmManager.php
- Rôle : gestion des matrices de scoring CBM (Certainty-Based Marking)
- Méthodes : `createMatrix(array $levels, array $scoring): array`, `validateMatrix(array $matrix): bool`, `calculateScore(array $answer, array $matrix): float`, `calibration(array $passages): array`, `savePreset(string $userId, array $matrix, string $name): string`, `listPresets(string $userId): array`

### WorkspaceManager.php
- Rôle : multi-tenant, isolation par école
- Méthodes : `create(array $data): array`, `get(string $id): ?array`, `update(string $id, array $data): bool`, `delete(string $id): bool`, `list(array $filters = []): array`, `getForUser(string $userId): ?array`

### TotpManager.php
- Rôle : 2FA (TOTP Google Authenticator)
- Méthodes : `generateSecret(): string`, `generateQrCodeUrl(string $secret, string $label): string`, `validate(string $code, string $secret): bool`, `enableForUser(string $userId, string $secret): bool`, `disableForUser(string $userId): bool`

### SsoManager.php
- Rôle : SSO Google/Microsoft
- Méthodes : `getAuthUrl(string $provider, string $state): string`, `handleCallback(string $provider, string $code): ?array`, `linkAccount(string $userId, string $provider, string $ssoId): bool`

### CommunityBankManager.php
- Rôle : banque communautaire de questions
- Méthodes : `publish(string $questionId, string $license): array`, `unpublish(string $communityId): bool`, `fork(string $communityId, string $targetWorkspaceId): string`, `vote(string $communityId, string $userId, int $stars): bool`, `flag(string $communityId, string $userId, string $reason): bool`, `listPublic(array $filters = []): array`, `search(string $query): array`

### ImportManager.php
- Rôle : import questions depuis Moodle/Word/Excel
- Méthodes : `importMoodleXml(string $xmlContent): array`, `importWordDocx(string $filePath): array`, `importExcelXlsx(string $filePath): array`, `validate(array $questions): array`

### ExportManager.php
- Rôle : export questions/examens vers SCORM/xAPI/LTI
- Méthodes : `exportScorm12(string $examenId): string`, `exportScorm2004(string $examenId): string`, `exportXapi(string $passageId): array`, `generateLtiConfig(): array`

### DocumentationManager.php
- Rôle : serveur doc Markdown avec RBAC
- Méthodes : `listDocs(string $role): array`, `getDoc(string $path): ?string`, `checkAccess(string $path, string $role): bool`, `search(string $query, string $role): array`

### I18nManager.php
- Rôle : traductions FR/EN
- Méthodes : `load(string $locale): array`, `translate(string $key, array $params = [], ?string $locale = null): string`, `currentLocale(): string`, `availableLocales(): array`

### AntiCheatAnalyzer.php
- Rôle : calcul score de confiance par passage
- Méthodes : `analyzePassage(array $passage): array`, `calculateConfidenceScore(array $passage): float`, `detectAnomalies(array $passage): array`

### AuditLogger.php
- Rôle : audit log centralisé
- Méthodes : `log(string $action, string $userId, array $context = []): void`, `list(array $filters = []): array`, `search(string $query): array`

### QuestionTypeResolver.php
- Rôle : résolution logique par type de question
- Méthodes : `getType(array $question): string`, `validateAnswer(array $question, array $answer): bool`, `isCorrect(array $question, array $answer): bool`, `calculateScore(array $question, array $answer, string $mode): float`

## Tâche 5 — i18n de base

**5.1** Créer `frontend/assets/i18n/fr.json` avec 50+ clés principales :
```json
{
  "app": {
    "name": "Certio",
    "tagline": "Plateforme d'évaluation par certitude",
    "loading": "Chargement...",
    "error": "Une erreur est survenue"
  },
  "common": {
    "save": "Enregistrer",
    "cancel": "Annuler",
    "delete": "Supprimer",
    "edit": "Modifier",
    "close": "Fermer",
    "confirm": "Confirmer",
    "yes": "Oui",
    "no": "Non",
    "search": "Rechercher",
    "filter": "Filtrer",
    "export": "Exporter",
    "import": "Importer",
    "back": "Retour",
    "next": "Suivant",
    "previous": "Précédent"
  },
  "auth": {
    "login": "Connexion",
    "logout": "Déconnexion",
    "email": "E-mail",
    "password": "Mot de passe",
    "login_title": "Se connecter à {appName}",
    "login_button": "Se connecter",
    "logout_success": "Vous êtes déconnecté"
  },
  "roles": {
    "admin": "Administrateur",
    "enseignant": "Enseignant",
    "etudiant": "Étudiant"
  },
  "examens": {
    "title": "Examens",
    "new": "Nouvel examen",
    "status": {
      "draft": "Brouillon",
      "published": "Publié",
      "closed": "Clôturé",
      "archived": "Archivé"
    }
  },
  "cbm": {
    "enabled": "CBM activé",
    "disabled": "CBM désactivé",
    "certainty": "Degré de certitude",
    "certainty_prompt": "Quelle est votre certitude sur cette réponse ?"
  },
  "copyright": {
    "line": "© {year} {owner} — {license}"
  }
}
```

**5.2** Créer `frontend/assets/i18n/en.json` avec traduction anglaise des mêmes clés.

**5.3** Implémenter `I18nManager::load()` et `::translate()` (logique simple : charge le JSON correspondant, translate avec placeholder replacement).

## Tâche 6 — PWA setup

**6.1** Créer `frontend/manifest.json` :
```json
{
  "name": "Certio",
  "short_name": "Certio",
  "description": "Certainty-Based Assessment Platform",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#1a365d",
  "icons": [
    {"src": "/assets/img/pwa/icon-72.png",  "sizes": "72x72",   "type": "image/png"},
    {"src": "/assets/img/pwa/icon-96.png",  "sizes": "96x96",   "type": "image/png"},
    {"src": "/assets/img/pwa/icon-128.png", "sizes": "128x128", "type": "image/png"},
    {"src": "/assets/img/pwa/icon-144.png", "sizes": "144x144", "type": "image/png"},
    {"src": "/assets/img/pwa/icon-152.png", "sizes": "152x152", "type": "image/png"},
    {"src": "/assets/img/pwa/icon-192.png", "sizes": "192x192", "type": "image/png"},
    {"src": "/assets/img/pwa/icon-384.png", "sizes": "384x384", "type": "image/png"},
    {"src": "/assets/img/pwa/icon-512.png", "sizes": "512x512", "type": "image/png"}
  ]
}
```

**6.2** Créer `frontend/service-worker.js` (version minimale) :
```javascript
const CACHE_NAME = 'certio-v2.0.0-alpha';
const CORE_ASSETS = [
  '/',
  '/assets/branding.js',
  '/assets/main.css'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(CORE_ASSETS))
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then(response => response || fetch(event.request))
  );
});
```

**6.3** Enregistrer le SW dans toutes les pages HTML :
```html
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js').catch(console.warn);
  }
</script>
```

**6.4** Créer `frontend/assets/img/pwa/` avec README expliquant les 8 tailles d'icônes à produire.

## Tâche 7 — Structure dossiers v2

Créer les nouveaux dossiers avec fichiers `.gitkeep` :
- `data/workspaces/`
- `data/community/`
- `data/audit/`
- `data/config/`
- `docs-interactive/admin/`
- `docs-interactive/prof/`
- `docs-interactive/etudiant/`
- `docs-interactive/shared/`

## Tâche 8 — Workspace par défaut

Créer `data/workspaces/WKS-DEFAULT.json` :
```json
{
  "id": "WKS-DEFAULT",
  "schema_version": 2,
  "name": "Workspace par défaut",
  "slug": "default",
  "plan": "free",
  "branding": {
    "logo_url": "/assets/img/logo.svg",
    "primary_color": "#1a365d",
    "secondary_color": "#48bb78"
  },
  "settings": {
    "allow_community_publish": true,
    "allow_sso_google": false,
    "allow_sso_microsoft": false,
    "default_locale": "fr"
  },
  "limits": {
    "max_profs": 999999,
    "max_students_per_month": 999999,
    "max_storage_mb": 999999
  },
  "admins": [],
  "created_at": "2026-05-01T00:00:00Z",
  "status": "active"
}
```

## Tâche 9 — Script de migration skeleton

Créer `scripts/migrate-v1-to-v2.php` skeleton :
```php
<?php
declare(strict_types=1);

/**
 * Migration v1 → v2 pour Certio
 * 
 * Usage :
 *   php scripts/migrate-v1-to-v2.php --dry-run
 *   php scripts/migrate-v1-to-v2.php --execute
 * 
 * @license CC BY-NC-SA 4.0
 * @author Mohamed EL AFRIT
 */

$options = getopt('', ['dry-run', 'execute', 'backup-dir:']);

if (!isset($options['dry-run']) && !isset($options['execute'])) {
    echo "Usage: php migrate-v1-to-v2.php [--dry-run|--execute]\n";
    exit(1);
}

$dryRun = isset($options['dry-run']);

echo "=== MIGRATION v1 → v2 ===\n";
echo "Mode: " . ($dryRun ? "DRY-RUN" : "EXECUTE") . "\n\n";

// TODO: implémentation complète en Phase 7
echo "Skeleton uniquement. Implémentation complète en Phase 7.\n";
```

## Tâche 10 — Tests de non-régression

**10.1** Exécuter `php backend/tests/run_all.php` et vérifier que les 389 tests passent toujours.

**10.2** Créer `backend/tests/test_branding_manager.php` avec 10 tests :
- load() retourne un array
- appName() retourne 'Certio'
- email('contact') retourne 'mohamed@elafrit.com'
- copyrightLine() contient l'année 2026
- format('Bienvenue sur {app_name}') retourne 'Bienvenue sur Certio'
- etc.

**10.3** Ajouter test_branding_manager.php au harness `run_all.php`.

## Tâche 11 — Documentation

**11.1** Créer `docs/SCHEMAS_V2.md` documentant les futurs schémas v2 (Examen, Question, Passage, Workspace, Community) — peut copier depuis la note de cadrage.

**11.2** Mettre à jour `docs/ARCHITECTURE.md` :
- Renommer "IPSSI Examens" → "Certio"
- Ajouter section "Managers v2.0 (skeleton en P0)"
- Ajouter section "i18n et PWA"

**11.3** Créer `docs/CHANGELOG.md` avec entrée :
```
## [2.0.0-alpha.0] - 2026-05-XX
### Added
- Rebranding complet IPSSI → Certio
- Config branding centralisée (backend/config/branding.php)
- 13 managers skeleton (CBM, Workspace, TOTP, SSO, Community, etc.)
- i18n FR/EN (base)
- PWA manifest + service worker
- Workspace par défaut
- Skeleton script migration v1→v2

### Changed
- Email contact: m.elafrit@ecole-ipssi.net → mohamed@elafrit.com
- Titres pages HTML : IPSSI Examens → Certio
```

# COMMITS À FAIRE (dans l'ordre)

Fais des commits atomiques Conventional Commits :

```bash
git commit -m "feat(branding): add centralized branding config (PHP + JS)"
git commit -m "feat(branding): add BrandingManager with helpers"
git commit -m "refactor(rebrand): replace IPSSI → Certio in PHP files"
git commit -m "refactor(rebrand): replace IPSSI → Certio in HTML/JS files"
git commit -m "refactor(rebrand): replace email and domain references"
git commit -m "feat(assets): add logo and favicon placeholders"
git commit -m "feat(managers): add 13 new managers skeleton (CBM, Workspace, etc.)"
git commit -m "feat(i18n): add FR/EN translation files and I18nManager"
git commit -m "feat(pwa): add manifest.json and service-worker.js"
git commit -m "feat(structure): create v2 data directories"
git commit -m "feat(workspaces): add default workspace"
git commit -m "feat(migration): add migrate-v1-to-v2.php skeleton"
git commit -m "test(branding): add tests for BrandingManager"
git commit -m "docs(v2): add SCHEMAS_V2.md and update ARCHITECTURE"
git commit -m "docs(changelog): add v2.0.0-alpha.0 entry"
```

# CRITÈRES D'ACCEPTATION (Definition of Done)

Avant de marquer P0 comme terminée, vérifier :

- [ ] `grep -ri "ipssi" --include="*.php" --include="*.js" --include="*.html"` → 0 résultat
- [ ] `grep -ri "ecole-ipssi.net"` → 0 résultat
- [ ] `grep -ri "m.elafrit@ecole-ipssi.net"` → 0 résultat
- [ ] Tous les titles HTML affichent "Certio"
- [ ] `window.BRANDING.appName === 'Certio'` dans la console
- [ ] `BrandingManager::appName() === 'Certio'` en PHP
- [ ] Les 13 fichiers managers existent avec `version() === '2.0.0-alpha.0'`
- [ ] `php -l backend/lib/*.php` : 0 erreur
- [ ] `php backend/tests/run_all.php` : 389 + 10 nouveaux tests PASS
- [ ] `data/workspaces/WKS-DEFAULT.json` existe et est un JSON valide
- [ ] Manifest PWA accessible à `/manifest.json`
- [ ] Service worker enregistré dans console navigateur
- [ ] Logo affiché dans header de toutes les pages
- [ ] Copyright affiché correctement dans footer

# RÈGLES IMPORTANTES

1. **NE PAS** implémenter la logique métier des nouveaux managers (juste skeleton)
2. **NE PAS** renommer le namespace `Examens\Lib` vers `Certio\Lib` maintenant (Phase 7)
3. **TOUJOURS** préserver les 389 tests existants
4. **TOUJOURS** faire des commits atomiques avec Conventional Commits
5. **TOUJOURS** ajouter un docblock en tête de chaque nouveau fichier PHP avec `@author Mohamed EL AFRIT` et `@license CC BY-NC-SA 4.0`
6. **TOUJOURS** utiliser strict types PHP (`declare(strict_types=1);`)
7. **NE JAMAIS** commit des secrets ou tokens
8. **NE JAMAIS** supprimer de tests existants

# PROCÉDURE DE VALIDATION

1. Exécute toutes les tâches dans l'ordre
2. Lance les tests : `php backend/tests/run_all.php`
3. Fais les commits atomiques
4. Tag final : `git tag v2.0.0-alpha.0`
5. Push : `git push origin feat/p0-fondations --tags`

Tu peux commencer maintenant. Confirme que tu as bien compris avant d'exécuter, et avance par petites étapes testables.
```

---

# Prompt P1 — CBM Core

## 📝 Informations phase

- **Durée** : 5 jours (30-34h)
- **Branche Git** : `feat/p1-cbm-core`
- **Tag final** : `v2.0.0-alpha.1`
- **Dépendances** : P0 complétée

## 🎯 Objectif de la phase

Implémenter le **Certainty-Based Marking (CBM) 100% paramétrable** : matrices de scoring, UI prof, UI étudiant, calcul scores, calibration, presets.

---

## 🤖 PROMPT À COPIER DANS VS CODE

```
# CONTEXTE

Tu travailles sur Certio v2.0 — plateforme d'évaluation web.
Auteur : Mohamed EL AFRIT (mohamed@elafrit.com)
Repo : certio (anciennement maths_IA_niveau_1)

**Phase précédente (P0)** : rebranding + skeleton 13 managers complétés. Tu dois avoir :
- `backend/lib/CbmManager.php` (skeleton, méthodes stub)
- Config branding en place
- i18n FR/EN de base

# OBJECTIF DE LA PHASE 1 — CBM CORE

Implémenter complètement le système CBM (Certainty-Based Marking) :
1. CbmManager fonctionnel (matrice, calcul score, calibration, presets)
2. API `/api/cbm/*` (presets CRUD, validation, import/export)
3. UI prof : éditeur de matrice dans création/édition examen
4. UI étudiant : saisie de certitude après chaque réponse + mini-tuto
5. Affichage scores CBM dans correction + email

# QU'EST-CE QUE LE CBM ?

**Certainty-Based Marking** = l'étudiant doit déclarer son niveau de certitude pour chaque réponse.
- Si juste + très certain → score très élevé
- Si juste + peu certain → score moyen
- Si faux + peu certain → petit malus
- Si faux + très certain → gros malus

Exemple matrice 3 niveaux :
| Niveau | Label                | Si juste | Si faux |
|--------|----------------------|----------|---------|
| 1      | Je devine            | +1       | 0       |
| 2      | Je pense             | +2       | -1      |
| 3      | Je suis sûr          | +3       | -3      |

Le CBM encourage l'**honnêteté intellectuelle** et développe la **métacognition**.

# SCHÉMA DE MATRICE CBM

```json
{
  "mode": "custom",
  "levels": [
    {"id": 1, "label": "Je devine",   "value": 0},
    {"id": 2, "label": "Je pense",    "value": 50},
    {"id": 3, "label": "Je suis sûr", "value": 100}
  ],
  "scoring": [
    {"level_id": 1, "correct": 1, "incorrect": 0},
    {"level_id": 2, "correct": 2, "incorrect": -1},
    {"level_id": 3, "correct": 3, "incorrect": -3}
  ]
}
```

**Contraintes** :
- 2 ≤ `levels.length` ≤ 10
- Chaque level doit avoir id unique, label non vide, value entre 0 et 100
- `scoring.length === levels.length` (une règle par niveau)
- Scores peuvent être négatifs (pour les incorrects)

# TÂCHES DÉTAILLÉES

## Tâche 1 — CbmManager complet

Implémenter dans `backend/lib/CbmManager.php` :

### Méthodes à implémenter

**`createMatrix(array $levels, array $scoring): array`**
- Construit une matrice CBM valide
- Valide les inputs
- Retourne structure JSON
- Throw InvalidArgumentException si invalide

**`validateMatrix(array $matrix): array`**
- Retourne `['valid' => bool, 'errors' => string[]]`
- Vérifie :
  - 2 ≤ count(levels) ≤ 10
  - level.id unique
  - level.label non vide
  - 0 ≤ level.value ≤ 100
  - count(scoring) === count(levels)
  - Chaque scoring référence un level.id existant

**`calculateScore(bool $isCorrect, int $levelId, array $matrix): float`**
- Retourne le score selon juste/faux et niveau de certitude
- Throw si level_id inconnu

**`calibration(array $passages): array`**
- Reçoit une liste de passages avec réponses + certitudes
- Retourne structure :
```php
[
  'overconfidence_rate' => 0.3,  // fraction avec haute certitude mais faux
  'underconfidence_rate' => 0.2, // fraction avec basse certitude mais juste
  'by_level' => [
    ['level_id' => 1, 'correct_pct' => 40, 'expected_pct' => 30],
    ['level_id' => 2, 'correct_pct' => 65, 'expected_pct' => 60],
    ...
  ]
]
```

**`savePreset(string $userId, array $matrix, string $name): string`**
- Sauvegarde matrice comme preset personnel du prof
- Retourne l'ID du preset (ex: `PST-XXXX`)
- Fichier : `data/comptes/{userId}/cbm_presets/PST-XXXX.json`

**`listPresets(string $userId): array`**
- Retourne liste des presets du user

**`getPreset(string $userId, string $presetId): ?array`**

**`deletePreset(string $userId, string $presetId): bool`**

**`updatePreset(string $userId, string $presetId, array $matrix, string $name): bool`**

**`exportToJson(array $matrix): string`**
- Retourne JSON pretty-print

**`importFromJson(string $json): array`**
- Parse + valide + retourne matrice

**`calibrationForStudent(string $examenId, string $studentEmail): array`**
- Calibration spécifique à un étudiant

### Fichiers de test

Créer `backend/tests/test_cbm_manager.php` avec 30+ tests unitaires couvrant :
- Création matrice valide
- Création matrice invalide (trop de niveaux, trop peu, labels vides, values hors range, etc.)
- Calcul score correct pour tous les niveaux
- Calcul score incorrect pour tous les niveaux
- Import/export JSON round-trip
- Presets CRUD
- Calibration avec dataset synthétique

Ajouter au harness `run_all.php`.

## Tâche 2 — API /api/cbm

Créer `backend/api/cbm.php` avec les endpoints :

- `GET /api/cbm/presets` → liste presets du user connecté (prof)
- `GET /api/cbm/presets/{id}` → un preset
- `POST /api/cbm/presets` → crée (body : `{name, matrix}`)
- `PUT /api/cbm/presets/{id}` → update
- `DELETE /api/cbm/presets/{id}` → delete
- `POST /api/cbm/validate` → valide une matrice (body : `{matrix}`)
- `POST /api/cbm/import` → importe JSON (body : `{json}`)
- `POST /api/cbm/export` → exporte JSON
- `GET /api/cbm/calibration/{examenId}` → calibration agrégée (prof only)
- `GET /api/cbm/calibration/student/{examenId}/{email}` → pour étudiant spécifique

**Sécurité** :
- Auth prof requise pour tous sauf `/api/cbm/calibration/student/` qui est accessible par l'étudiant lui-même (via token de passage)
- CSRF sur POST/PUT/DELETE
- Rate limit prof : 500/min (déjà en place)

Ajouter le routing dans `backend/public/index.php`.

Écrire tests intégration dans `backend/tests/test_api_cbm.php` (15+ tests).

## Tâche 3 — UI Prof : CbmMatrixEditor

### Créer `frontend/assets/components/CbmMatrixEditor.jsx`

Composant React fonctionnel. Props :
- `initialMatrix: object | null`
- `onChange: (matrix) => void`
- `onSave: (matrix) => void`

### UI attendue

```
┌─────────────────────────────────────────────┐
│ Configuration CBM                           │
├─────────────────────────────────────────────┤
│ Nombre de niveaux : [ 3 ▼ ]  (2-10)         │
│                                             │
│ ┌─────────────────────────────────────────┐ │
│ │ # │ Label       │ % │ Juste │ Faux     │ │
│ │ 1 │ Je devine   │0% │ [  1] │ [  0]    │ │
│ │ 2 │ Je pense    │50%│ [  2] │ [ -1]    │ │
│ │ 3 │ Je suis sûr │100│ [  3] │ [ -3]    │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ 💾 [Sauver preset] 📂 [Charger] 📤 [Export] │
│ 📥 [Importer JSON]                          │
└─────────────────────────────────────────────┘
```

### Fonctionnalités

- Editable rows (input labels, values, scores)
- Slider ou input number pour nombre de niveaux
- Preview des scores en live
- Validation en temps réel (error messages sous les champs)
- Appel API `/api/cbm/presets` pour save/load
- Modal "Charger preset" avec liste
- Bouton "Import JSON" ouvre file input
- Bouton "Export JSON" télécharge fichier

### Intégration

Dans `frontend/admin/examens.html`, page création/édition examen, ajouter :
- Toggle "Activer CBM" (switch)
- Si activé → afficher `<CbmMatrixEditor>` 
- Matrix sauvée dans `examen.cbm`

## Tâche 4 — UI Étudiant : CbmCertaintyInput

### Créer `frontend/assets/components/CbmCertaintyInput.jsx`

Props :
- `matrix: object` (la matrice de l'examen)
- `currentLevelId: number | null`
- `onChange: (levelId) => void`
- `locked: boolean` (après soumission question)

### UI attendue (après réponse de l'étudiant)

```
Votre réponse : [option sélectionnée]

┌─────────────────────────────────────────────┐
│ 🤔 Quelle est votre certitude ?             │
│                                             │
│ ○ Je devine (+1 si juste, 0 si faux)        │
│ ○ Je pense  (+2 si juste, -1 si faux)       │
│ ● Je suis sûr (+3 si juste, -3 si faux)    │
│                                             │
└─────────────────────────────────────────────┘
```

### Intégration

Dans `frontend/etudiant/passage.html` :
- Après sélection de la réponse par l'étudiant, afficher `<CbmCertaintyInput>`
- Envoyer niveau de certitude avec la réponse : `POST /api/passages/answer { token, questionId, answer, cbm_level_id }`
- Mettre à jour `PassageManager::saveAnswer` pour accepter `cbm_level_id`

## Tâche 5 — Mini-tutoriel CBM étudiant

Créer composant `<CbmIntroModal>` qui s'affiche au 1er accès à un examen CBM :

Contenu en 3 étapes :
1. "Ce test utilise le CBM — explications"
2. "Pour chaque réponse, vous devrez indiquer votre certitude"
3. "Soyez honnête : l'honnêteté paie plus que le hasard"

Avec illustrations (placeholders SVG simples pour l'instant).

Flag dans localStorage : `cbm_intro_seen` → ne pas re-montrer.

Bouton "Passer le tuto" en haut à droite.

## Tâche 6 — Affichage score CBM dans correction

Dans `frontend/etudiant/correction.html` :

Ajouter section "Votre performance CBM" :
- Graphe scatter : x = certitude, y = réussite
- Indicateur textuel : 
  - "Vous êtes overconfident sur X questions"
  - "Vous êtes bien calibré sur Y questions"  
  - "Vous êtes underconfident sur Z questions"
- Tableau détaillé par question : certitude, réponse, juste/faux, score CBM

Utiliser Recharts (déjà chargé en CDN).

Dans `backend/api/corrections.php`, enrichir la réponse avec `cbm_analysis`.

## Tâche 7 — Email confirmation enrichi

Modifier `backend/templates/emails/etudiant_submission.html` :

Ajouter section CBM si activé :
```html
{{#if cbm_enabled}}
<div style="background: #f0f9ff; padding: 15px; border-radius: 8px; margin: 20px 0;">
  <h3>🎯 Performance CBM</h3>
  <p>Score CBM : <strong>{{cbm_score}}</strong></p>
  <p>Calibration : {{cbm_calibration_message}}</p>
</div>
{{/if}}
```

Passer les variables depuis `PassageManager::submit()` vers `Mailer::sendTemplate()`.

## Tâche 8 — Tests E2E CBM

Créer `backend/tests/e2e/test_cbm_workflow.php` :

Scénario :
1. Prof crée examen avec CBM activé (matrice 3 niveaux)
2. Prof publie → obtient code
3. Étudiant accède via code
4. Étudiant répond à 5 questions avec différentes certitudes
5. Étudiant soumet
6. Vérifier : score CBM calculé correctement
7. Vérifier : calibration accessible côté prof
8. Vérifier : email envoyé avec score CBM

## Tâche 9 — Documentation

Créer `docs/CBM_GUIDE.md` avec :
- Qu'est-ce que le CBM (théorie, pourquoi)
- Comment créer une matrice CBM (guide prof)
- Comment passer un examen CBM (guide étudiant)
- Comprendre la calibration (over/underconfidence)
- FAQ

# COMMITS À FAIRE

```bash
git commit -m "feat(cbm): implement CbmManager core (matrix, score, calibration)"
git commit -m "test(cbm): add 30+ unit tests for CbmManager"
git commit -m "feat(cbm): add API endpoints /api/cbm/*"
git commit -m "test(cbm): add API integration tests"
git commit -m "feat(cbm): add CbmMatrixEditor React component (teacher UI)"
git commit -m "feat(cbm): integrate CBM toggle and editor in exam creation"
git commit -m "feat(cbm): add CbmCertaintyInput React component (student UI)"
git commit -m "feat(cbm): add CBM intro modal for students"
git commit -m "feat(cbm): show CBM analysis in correction page"
git commit -m "feat(cbm): enrich confirmation email with CBM score"
git commit -m "test(cbm): add E2E test for complete CBM workflow"
git commit -m "docs(cbm): add CBM_GUIDE.md"
```

# CRITÈRES D'ACCEPTATION (Definition of Done)

- [ ] `CbmManager` : toutes méthodes implémentées
- [ ] `CbmManager` : couverture tests ≥ 90%
- [ ] API `/api/cbm/*` : 10 endpoints fonctionnels
- [ ] Un prof peut créer matrice 3 niveaux en < 2min
- [ ] Un prof peut sauver/charger un preset
- [ ] Un prof peut exporter/importer JSON
- [ ] Un étudiant voit mini-tuto au 1er examen CBM
- [ ] Un étudiant saisit certitude après chaque réponse
- [ ] Score final intègre CBM correctement
- [ ] Calibration calculée (over/underconfidence)
- [ ] Correction affiche graphe + analyse CBM
- [ ] Email inclut score CBM si activé
- [ ] Test E2E CBM workflow passe
- [ ] 0 régression sur examens sans CBM

# RÈGLES IMPORTANTES

1. **Préserver** le comportement v1 pour les examens sans CBM (`cbm.enabled = false`)
2. **Tester** chaque nouveau endpoint avec CSRF + auth
3. **Valider** la matrice côté serveur ET côté client
4. **Accessibilité** : tous les inputs ont des labels aria-*
5. **Performance** : lazy load CbmMatrixEditor (import dynamique)
6. **i18n** : toutes les strings passées par I18nManager

# VALIDATION FINALE

1. Lance `php backend/tests/run_all.php` → tous verts
2. Manuel : crée un examen CBM, passe-le, vérifie correction
3. Merge vers develop
4. Tag `v2.0.0-alpha.1`
5. Push

Confirme que tu as compris et commence par la Tâche 1.
```

---

# Prompt P2 — Types de questions étendus

## 📝 Informations phase

- **Durée** : 4 jours (24-28h)
- **Branche Git** : `feat/p2-question-types`
- **Tag final** : `v2.0.0-alpha.2`
- **Dépendances** : P0, P1 complétées

## 🎯 Objectif de la phase

Supporter **7 types de questions** avec UI création adaptive et UI réponse adaptive.

---

## 🤖 PROMPT À COPIER DANS VS CODE

```
# CONTEXTE

Tu travailles sur Certio v2.0.
Auteur : Mohamed EL AFRIT (mohamed@elafrit.com)

Phases précédentes complétées :
- P0 : rebranding + skeleton managers + QuestionTypeResolver skeleton
- P1 : CBM Core fonctionnel

# OBJECTIF DE LA PHASE 2

Supporter 7 types de questions :
1. `true_false` : 2 choix, 1 bonne (Vrai/Faux)
2. `mcq_single_4` : 4 propositions, 1 bonne (radio, format v1)
3. `mcq_single_5` : 5 propositions, 1 bonne
4. `mcq_single_n` : N propositions (4-10), 1 bonne
5. `mcq_multiple_4` : 4 propositions, M bonnes (checkbox)
6. `mcq_multiple_5` : 5 propositions, M bonnes
7. `mcq_multiple_n` : N propositions (4-10), M bonnes

# SCHÉMA QUESTION v2

```json
{
  "id": "QST-XXXX",
  "schema_version": 2,
  "workspace_id": "WKS-DEFAULT",
  "creator_id": "USR-XXXX",
  "visibility": "private",
  "type": "mcq_single_4",
  "subtype_config": {
    "num_options": 4,
    "shuffle_options": true
  },
  "statement": "Quelle est la dérivée de $f(x) = x^2$ ?",
  "statement_format": "markdown_with_latex",
  "options": [
    {"id": "A", "text": "$2x$",    "is_correct": true},
    {"id": "B", "text": "$x$",     "is_correct": false},
    {"id": "C", "text": "$2$",     "is_correct": false},
    {"id": "D", "text": "$x^2/2$", "is_correct": false}
  ],
  "explanation": "...",
  "difficulty": "easy",
  "tags": ["dérivée"],
  "module": "Mathématiques",
  "chapitre": "Analyse",
  "theme": "Dérivées",
  "locale": "fr",
  "created_at": "2026-05-01T10:00:00Z"
}
```

# TÂCHES

## Tâche 1 — QuestionTypeResolver complet

Implémenter `backend/lib/QuestionTypeResolver.php` :

**Constantes** :
```php
public const TYPE_TRUE_FALSE     = 'true_false';
public const TYPE_MCQ_SINGLE_4   = 'mcq_single_4';
public const TYPE_MCQ_SINGLE_5   = 'mcq_single_5';
public const TYPE_MCQ_SINGLE_N   = 'mcq_single_n';
public const TYPE_MCQ_MULTIPLE_4 = 'mcq_multiple_4';
public const TYPE_MCQ_MULTIPLE_5 = 'mcq_multiple_5';
public const TYPE_MCQ_MULTIPLE_N = 'mcq_multiple_n';

public const ALL_TYPES = [
    self::TYPE_TRUE_FALSE,
    self::TYPE_MCQ_SINGLE_4,
    self::TYPE_MCQ_SINGLE_5,
    self::TYPE_MCQ_SINGLE_N,
    self::TYPE_MCQ_MULTIPLE_4,
    self::TYPE_MCQ_MULTIPLE_5,
    self::TYPE_MCQ_MULTIPLE_N,
];
```

**Méthodes** :

`getType(array $question): string`
- Retourne le type
- Throw si type inconnu

`isMultiple(string $type): bool`
- True pour `mcq_multiple_*`

`expectedNumOptions(string $type, array $subtypeConfig = []): array`
- Retourne `['min' => 2, 'max' => 2]` pour true_false
- `['min' => 4, 'max' => 4]` pour mcq_*_4
- `['min' => 5, 'max' => 5]` pour mcq_*_5
- `['min' => 4, 'max' => 10]` pour mcq_*_n

`validateQuestion(array $question): array`
- Retourne `['valid' => bool, 'errors' => string[]]`
- Vérifie :
  - Type est dans ALL_TYPES
  - Nombre d'options correct pour le type
  - Pour `mcq_single_*` : exactement 1 option avec is_correct=true
  - Pour `mcq_multiple_*` : au moins 1 option avec is_correct=true
  - Pour true_false : exactement 2 options (texte "Vrai" et "Faux" acceptés ou non)
  - Chaque option a id, text (non vide), is_correct (bool)

`validateAnswer(array $question, array $answer): bool`
- Pour single : answer.selected_options doit contenir exactement 1 id
- Pour multiple : answer.selected_options array de 0 à N ids
- Tous les ids doivent référencer des options existantes

`isCorrect(array $question, array $answer): bool`
- Compare selected_options avec options.is_correct=true
- Pour single : égalité stricte
- Pour multiple : selected_options === correct_options (set egality)

`calculateBaseScore(array $question, array $answer, string $mode = 'all_or_nothing'): float`
- Phase 2 : implémenter seulement `all_or_nothing` (1.0 si correct, 0.0 sinon)
- Les modes `proportional_strict` et `proportional_normalized` en Phase 3

Tests dans `backend/tests/test_question_type_resolver.php` (40+ tests).

## Tâche 2 — UI Prof : QuestionEditor refactoré

Refactoriser `frontend/assets/components/QuestionEditor.jsx` pour supporter les 7 types.

### UI attendue

```
Type de question : [ QCM choix unique 4 ▼ ]
                   ├ Vrai / Faux
                   ├ QCM choix unique 4
                   ├ QCM choix unique 5
                   ├ QCM choix unique N
                   ├ QCM choix multiple 4
                   ├ QCM choix multiple 5
                   └ QCM choix multiple N

[Si type "_n" : Nombre d'options : [ 4 ▼ ] (4-10)]

Énoncé : [textarea avec preview markdown]

Options :
┌──┬────────────────────────┬─────────┐
│  │ Texte                  │ Correct │
├──┼────────────────────────┼─────────┤
│A │ [ input ]              │ [ ● ]   │ (radio si single)
│B │ [ input ]              │ [ ○ ]   │
│C │ [ input ]              │ [ ○ ]   │
│D │ [ input ]              │ [ ○ ]   │
└──┴────────────────────────┴─────────┘

Explication : [textarea]

[Options avancées : module, chapitre, thème, tags, difficulté]

🔍 Aperçu :
[Rendu fidèle de la question]

[💾 Enregistrer]  [Annuler]
```

### Logique

- Au changement de type, ajuster le nombre d'options (ajouter/retirer)
- Pour single : radio buttons (1 seul correct)
- Pour multiple : checkboxes (N corrects)
- Pour true_false : 2 options Vrai/Faux verrouillées
- Validation côté client avant submit
- Preview live via KaTeX pour le LaTeX

## Tâche 3 — UI Étudiant : QuestionRenderer adaptive

Refactoriser `frontend/assets/components/QuestionRenderer.jsx` :

### UI selon type

**true_false** :
```
┌─────────────────────┐  ┌─────────────────────┐
│       ✓ Vrai        │  │       ✗ Faux        │
└─────────────────────┘  └─────────────────────┘
```
(Deux gros boutons radio)

**mcq_single_*** :
```
○ Option A : texte
○ Option B : texte
● Option C : texte
○ Option D : texte
```
(Radio buttons)

**mcq_multiple_*** :
```
☐ Option A : texte
☑ Option B : texte
☐ Option C : texte
☑ Option D : texte
```
(Checkboxes + indicateur "cochez toutes les bonnes réponses")

### Accessibilité

- Navigation clavier (Tab, Space, Enter)
- aria-labels explicites
- Focus visible
- Support lecteurs d'écran

## Tâche 4 — Mise à jour BanqueManager

Dans `backend/lib/BanqueManager.php` :

- `createQuestion()` : valider via `QuestionTypeResolver::validateQuestion()`
- `updateQuestion()` : idem
- `searchQuestions()` : ajouter filtre par type

## Tâche 5 — Mise à jour PassageManager

Dans `backend/lib/PassageManager.php` :

- `saveAnswer()` : accepter `selected_options` (array) au lieu de `answer_index` (int)
- `submit()` : utiliser `QuestionTypeResolver::isCorrect()` pour déterminer juste/faux
- Migration automatique des anciennes réponses (format v1 → v2)

## Tâche 6 — Migration v1 → v2 questions

Dans `scripts/migrate-v1-to-v2.php`, implémenter la conversion :

**v1** (présumé) :
```json
{
  "id": "QST-XXXX",
  "question": "...",
  "options": ["A", "B", "C", "D"],
  "correct_index": 0
}
```

**v2** :
```json
{
  "id": "QST-XXXX",
  "schema_version": 2,
  "type": "mcq_single_4",
  "subtype_config": {"num_options": 4, "shuffle_options": false},
  "statement": "...",
  "statement_format": "markdown_with_latex",
  "options": [
    {"id": "A", "text": "A", "is_correct": true},
    {"id": "B", "text": "B", "is_correct": false},
    {"id": "C", "text": "C", "is_correct": false},
    {"id": "D", "text": "D", "is_correct": false}
  ],
  "workspace_id": "WKS-DEFAULT",
  "visibility": "private"
}
```

Migration idempotente (détecter schema_version === 2 et skip).

Tests : migrer les 320 questions banque + rollback possible.

## Tâche 7 — Documentation

Créer `docs/QUESTION_TYPES.md` avec :
- Les 7 types décrits
- Exemples JSON de chaque
- Capture d'écran UI de chaque (placeholder pour l'instant)
- Guide "Quel type choisir ?"

Mise à jour `docs/ARCHITECTURE.md` : section "Types de questions".

# COMMITS

```bash
git commit -m "feat(questions): implement QuestionTypeResolver for 7 types"
git commit -m "test(questions): add 40+ tests for QuestionTypeResolver"
git commit -m "feat(questions): refactor QuestionEditor UI for 7 types (teacher)"
git commit -m "feat(questions): refactor QuestionRenderer UI for 7 types (student)"
git commit -m "refactor(banque): use QuestionTypeResolver for validation"
git commit -m "refactor(passages): support multi-answer questions"
git commit -m "feat(migration): convert v1 questions to v2 schema"
git commit -m "test(migration): validate all 320 bank questions migrate OK"
git commit -m "docs(questions): add QUESTION_TYPES.md guide"
```

# CRITÈRES D'ACCEPTATION

- [ ] QuestionTypeResolver : tous 7 types supportés
- [ ] Tests > 90% couverture du resolver
- [ ] UI création : sélection type fluide
- [ ] UI réponse : visuel clair radio vs checkbox
- [ ] Migration v1 → v2 : 320/320 questions OK
- [ ] Accessibilité WCAG préservée
- [ ] 0 régression : examens v1 fonctionnent en v2

# TAG FINAL

```bash
git tag v2.0.0-alpha.2
git push origin feat/p2-question-types --tags
```

Confirme et commence par la Tâche 1.
```

---

# Prompt P3 — Scoring & Analytics

## 📝 Informations phase

- **Durée** : 4 jours (24-28h)
- **Branche Git** : `feat/p3-scoring-analytics`
- **Tag final** : `v2.0.0-beta.1`
- **Dépendances** : P0, P1, P2 complétées

## 🎯 Objectif de la phase

Scoring multi-réponses (3 modes) + combinaison CBM × multi-réponses + analytics enrichis pour prof et étudiant.

---

## 🤖 PROMPT À COPIER DANS VS CODE

```
# CONTEXTE

Certio v2.0. Phases P0, P1, P2 OK.

Tu dois maintenant ajouter :
1. Scoring multi-réponses (tout ou rien, proportionnel strict, proportionnel normalisé)
2. Combinaison CBM × multi-réponses
3. Analytics prof enrichis (calibration, distracteurs, radar)
4. Dashboard étudiant personnel
5. Exports CSV/Excel enrichis avec colonnes CBM

# 3 MODES DE SCORING MULTI-RÉPONSES

Exemple : question `mcq_multiple_4` avec A et C corrects. L'étudiant coche A et D.

**Mode 1 : all_or_nothing**
- Score = 1.0 si toutes les bonnes sont cochées et aucune fausse, sinon 0.0
- Dans l'exemple : étudiant coche A (bon) et D (faux), et manque C (bon) → 0.0

**Mode 2 : proportional_strict**
- +1 par bonne cochée, -1 par fausse cochée
- Normalisé entre 0 et total des bonnes
- Formule : `score = max(0, (bonnes_cochées - fausses_cochées) / total_bonnes)`
- Exemple : (1 - 1) / 2 = 0.0

**Mode 3 : proportional_normalized** (plus clément)
- `score = (bonnes_cochées / total_bonnes) - pénalité_fausses`
- Pénalité fausses = `fausses_cochées / total_fausses × 0.5`
- Clampé entre 0 et 1
- Exemple : 1/2 - 1/2×0.5 = 0.25

# TÂCHES

## Tâche 1 — Implémenter 3 modes scoring

Dans `QuestionTypeResolver::calculateBaseScore()` :

```php
public function calculateBaseScore(
    array $question,
    array $answer,
    string $mode = 'all_or_nothing'
): float {
    if (!$this->isMultiple($question['type'])) {
        return $this->isCorrect($question, $answer) ? 1.0 : 0.0;
    }
    
    $correctIds = array_column(
        array_filter($question['options'], fn($o) => $o['is_correct']),
        'id'
    );
    $selectedIds = $answer['selected_options'] ?? [];
    
    $correctSelected = count(array_intersect($selectedIds, $correctIds));
    $incorrectSelected = count(array_diff($selectedIds, $correctIds));
    $totalCorrect = count($correctIds);
    $totalIncorrect = count($question['options']) - $totalCorrect;
    
    switch ($mode) {
        case 'all_or_nothing':
            return ($correctSelected === $totalCorrect && $incorrectSelected === 0) ? 1.0 : 0.0;
        
        case 'proportional_strict':
            if ($totalCorrect === 0) return 0.0;
            return max(0.0, min(1.0, ($correctSelected - $incorrectSelected) / $totalCorrect));
        
        case 'proportional_normalized':
            $positive = $totalCorrect > 0 ? $correctSelected / $totalCorrect : 0;
            $penalty = $totalIncorrect > 0 ? ($incorrectSelected / $totalIncorrect) * 0.5 : 0;
            return max(0.0, min(1.0, $positive - $penalty));
        
        default:
            throw new \InvalidArgumentException("Unknown scoring mode: {$mode}");
    }
}
```

Ajouter tests (30+ pour les 3 modes × cas variés).

## Tâche 2 — Combinaison CBM × multi-réponses

Dans `PassageManager::submit()`, logique score finale :

```
score_final_question = score_base × multiplicateur_CBM
```

Où :
- `score_base` ∈ [0, 1] selon mode multi-réponses
- `multiplicateur_CBM` = valeur de la matrice selon certitude × juste/faux

**Cas délicat** : partial correct (ex: score_base = 0.5) — considérer "juste" ou "faux" pour CBM ?
- Règle proposée : **juste si score_base ≥ 0.5**, sinon **faux**
- Rendre cette règle configurable : `examen.cbm.partial_threshold = 0.5`

Exemple : score_base = 0.7, certitude = "Je suis sûr" (niveau 3, +3 correct / -3 faux)
→ considéré juste → multiplicateur = +3 × 0.7 = +2.1

Implémenter dans `CbmManager::calculateScoreWithPartial()`.

Tests (20+).

## Tâche 3 — Analytics enrichis (API)

### Nouveaux endpoints dans `backend/api/analytics.php`

**`GET /api/analytics/cbm-calibration/{examenId}`**
Retourne pour l'examen :
```json
{
  "students": [
    {
      "email": "alice@...",
      "calibration": {
        "overconfidence_rate": 0.3,
        "underconfidence_rate": 0.1,
        "well_calibrated_rate": 0.6,
        "by_level": [...]
      }
    }
  ],
  "aggregate": {
    "average_overconfidence": 0.25,
    "distribution_by_level": [...]
  }
}
```

**`GET /api/analytics/distractors/{examenId}`**
Pour chaque question : options les plus choisies (bonnes ET mauvaises), avec pourcentages.
```json
{
  "questions": [
    {
      "question_id": "QST-001",
      "distractors_analysis": [
        {"option_id": "A", "text": "...", "is_correct": true,  "selected_pct": 62},
        {"option_id": "B", "text": "...", "is_correct": false, "selected_pct": 28},
        {"option_id": "C", "text": "...", "is_correct": false, "selected_pct": 8},
        {"option_id": "D", "text": "...", "is_correct": false, "selected_pct": 2}
      ],
      "most_common_wrong": "B",
      "discrimination_index": 0.42
    }
  ]
}
```

**`GET /api/analytics/student-radar/{examenId}`**
Performance par thème/chapitre :
```json
{
  "themes": ["Dérivées", "Intégrales", "Limites"],
  "students": [
    {
      "email": "alice@...",
      "scores_by_theme": [85, 60, 72]
    }
  ]
}
```

**`GET /api/analytics/student-progression/{email}`**
Historique de tous les passages de l'étudiant dans le temps.

## Tâche 4 — Composants React Analytics

### `<CbmCalibrationChart>`
Scatter plot : x = certitude déclarée (0-100%), y = taux de réussite (0-100%)
- Ligne diagonale = calibration parfaite
- Points au-dessus = underconfidence
- Points en-dessous = overconfidence

### `<DistractorsAnalysis>`
Bar chart horizontal pour chaque question :
- 4 barres colorées (vert si correct, rouge si incorrect)
- Pourcentage de choix
- Click sur question → détail

### `<StudentRadar>`
Radar chart (Recharts) : performance par thème
- Un radar par étudiant
- Possibilité overlay plusieurs étudiants

### `<ProgressionChart>`
Line chart : évolution des scores dans le temps

Utiliser Recharts (déjà disponible en CDN).

Intégrer dans `/admin/analytics.html` (prof view).

## Tâche 5 — Dashboard étudiant

Créer `/etudiant/dashboard.html` (nouvelle page) :

Sections :
1. **Statistiques globales** : nombre d'examens passés, score moyen, meilleure/pire note
2. **Progression** : `<ProgressionChart>` sur 12 derniers mois
3. **Calibration CBM** : si l'étudiant a fait des examens CBM
4. **Thèmes forts / faibles** : `<StudentRadar>` sur ses thèmes
5. **Historique** : tableau de tous ses passages avec lien vers correction

Endpoint `GET /api/etudiant/dashboard/{email}` (accessible via token après passage).

## Tâche 6 — Exports enrichis

### CSV amélioré

`GET /api/examens/{id}/export.csv`

Colonnes :
```
student_email, student_nom, student_prenom, started_at, submitted_at, duration_seconds,
score_brut, score_max, score_percent, passed,
cbm_enabled, cbm_score, cbm_calibration_rate, cbm_overconfidence_rate,
question_1_answer, question_1_correct, question_1_cbm_level, question_1_score, ...
```

### Excel multi-feuilles

`GET /api/examens/{id}/export.xlsx`

Feuilles :
1. **Résumé** : 1 ligne par étudiant avec scores agrégés
2. **Détail par question** : 1 ligne par (étudiant, question)
3. **CBM Analysis** : calibration détaillée si CBM activé
4. **Question Stats** : stats par question (% réussite, distracteurs)

Utiliser PHPSpreadsheet ou implémenter soi-même (ou générer via SheetJS côté client).

Si générer côté client : endpoint retourne JSON, client génère xlsx avec SheetJS.

## Tâche 7 — Documentation

`docs/SCORING.md` :
- 3 modes de scoring multi-réponses (avec exemples)
- Comment choisir son mode
- Combinaison CBM × multi-réponses (formule, exemples)

`docs/ANALYTICS.md` :
- Guide des 8 analytics disponibles
- Comment interpréter calibration
- Comment interpréter distracteurs
- Screenshots (placeholders)

# COMMITS

```bash
git commit -m "feat(scoring): implement 3 multi-answer scoring modes"
git commit -m "test(scoring): add 30+ tests for scoring modes"
git commit -m "feat(cbm): combine CBM with multi-answer scoring"
git commit -m "feat(analytics): add 4 new analytics API endpoints"
git commit -m "feat(analytics): add CbmCalibrationChart React component"
git commit -m "feat(analytics): add DistractorsAnalysis component"
git commit -m "feat(analytics): add StudentRadar and ProgressionChart"
git commit -m "feat(student): add personal dashboard page"
git commit -m "feat(exports): enrich CSV export with CBM columns"
git commit -m "feat(exports): enrich Excel export with 4 sheets"
git commit -m "docs(scoring): add SCORING.md and ANALYTICS.md"
```

# CRITÈRES D'ACCEPTATION

- [ ] 3 modes scoring testés (20+ cas chacun)
- [ ] CBM × multi-réponses : formule documentée et testée
- [ ] 4 nouveaux endpoints analytics fonctionnels
- [ ] Dashboard étudiant : toutes sections affichées
- [ ] Excel export : 4 feuilles correctes
- [ ] Performance : /api/analytics/* répond < 500ms pour 100 passages

# TAG

```bash
git tag v2.0.0-beta.1
git push origin feat/p3-scoring-analytics --tags
```

Confirme et commence.
```

---

# Prompt P4 — Documentation interactive

## 📝 Informations phase

- **Durée** : 5 jours (30-34h)
- **Branche Git** : `feat/p4-documentation`
- **Tag final** : `v2.0.0-beta.2`
- **Dépendances** : P0 complétée (peut être fait en parallèle de P1-P3)

## 🎯 Objectif de la phase

Documentation interactive intégrée dans l'admin avec Markdown hybride, RBAC, placeholders IA.

---

## 🤖 PROMPT À COPIER DANS VS CODE

```
# CONTEXTE

Certio v2.0. Phase P0 complétée (skeleton DocumentationManager et I18nManager en place).

# OBJECTIF DE LA PHASE 4

Implémenter une documentation interactive complète accessible depuis l'interface admin, avec :
1. Parsing Markdown côté client (marked.js CDN)
2. Navigation auto depuis structure dossier
3. RBAC : admin voit tout, prof voit prof+étudiant, étudiant voit seulement étudiant
4. Placeholders pour 4 familles de médias avec prompts IA documentés
5. Recherche full-text basique
6. TOC auto + breadcrumbs

# STRUCTURE DE FICHIERS

```
docs-interactive/
├── admin/
│   ├── 01-dashboard.md
│   ├── 02-comptes.md
│   ├── 03-workspaces.md
│   ├── 04-backups.md
│   ├── 05-monitoring.md
│   ├── 06-audit.md
│   ├── 07-settings.md
│   └── 08-community.md
├── prof/
│   ├── 01-demarrage.md
│   ├── 02-banque-questions.md
│   ├── 03-creation-examens.md
│   ├── 04-cbm.md
│   ├── 05-distribution.md
│   ├── 06-suivi-passages.md
│   ├── 07-analytics.md
│   ├── 08-exports.md
│   ├── 09-faq.md
│   └── 10-advanced.md
├── etudiant/
│   ├── 01-passer-examen.md
│   ├── 02-comprendre-cbm.md
│   ├── 03-correction.md
│   └── 04-faq.md
└── shared/
    ├── glossaire.md
    ├── support.md
    ├── licences.md
    ├── privacy.md
    └── about.md
```

# TÂCHES

## Tâche 1 — DocumentationManager complet

Implémenter `backend/lib/DocumentationManager.php` :

**`listDocs(string $role): array`**
- Retourne structure arbre pour le rôle :
```php
[
  'admin' => [
    ['path' => 'admin/01-dashboard.md', 'title' => 'Dashboard', 'order' => 1],
    ...
  ],
  'prof' => [...],
  'etudiant' => [...],
  'shared' => [...]
]
```
- Admin : voit tout
- Prof : voit prof + etudiant + shared
- Étudiant : voit etudiant + shared uniquement

**`getDoc(string $path): ?string`**
- Lit le fichier markdown
- Retourne contenu string ou null
- Vérifie path sécurisé (pas de ../)

**`checkAccess(string $path, string $role): bool`**
- Admin → tous
- Prof → pas admin/*
- Étudiant → uniquement etudiant/* et shared/*

**`search(string $query, string $role): array`**
- Recherche full-text dans les markdowns accessibles pour le rôle
- Retourne liste de matches avec extrait (snippet)
- Simple grep + highlighting

**`getMetadata(string $path): array`**
- Parse le front matter YAML du markdown (si présent)
- Retourne titre, description, tags, dernière modification

Tests (20+).

## Tâche 2 — API /api/docs

Créer `backend/api/docs.php` :

- `GET /api/docs/tree` : structure arbre filtrée par rôle
- `GET /api/docs/content?path=admin/01-dashboard.md` : contenu markdown
- `GET /api/docs/search?q=cbm` : recherche
- `GET /api/docs/metadata?path=...` : métadonnées

**Sécurité** :
- Auth requise (n'importe quel rôle)
- Validation path (regex + pas de `..`)
- Rate limit selon rôle

## Tâche 3 — UI React DocsViewer

Créer `frontend/assets/components/DocsViewer.jsx` :

### Layout

```
┌─────────┬────────────────────────────────┬─────────┐
│ SIDEBAR │ CONTENT                        │ TOC     │
│         │                                │         │
│ [Search]│ # Titre page                   │ 1. Intro│
│         │                                │ 2. Use  │
│ ▼ Admin │ Contenu markdown parsé         │ 3. Tips │
│   · ...│ avec code, images, etc.        │         │
│ ▼ Prof  │                                │         │
│   · ...│                                │         │
│ ▼ Etu.. │                                │         │
└─────────┴────────────────────────────────┴─────────┘
```

### Composants internes

- `<DocsSidebar>` : navigation arbre
- `<DocsSearch>` : barre de recherche avec suggestions
- `<DocsContent>` : affichage markdown parsé
- `<DocsToc>` : TOC auto-générée depuis les headings
- `<DocsBreadcrumbs>` : en haut
- `<DocsPlaceholder>` : pour les placeholders

### Fonctionnalités

- **Parse markdown** : via `marked.js` (CDN) + DOMPurify pour XSS
- **Syntax highlighting** : highlight.js (CDN)
- **KaTeX** : pour le LaTeX (déjà disponible)
- **Mermaid** : pour les diagrammes (ajouter CDN)
- **PlantUML** : via plantuml-encoder pour URL (ajouter CDN)
- **Navigation clavier** : arrow keys dans sidebar
- **Search highlighting** : surligner les matches
- **Dark mode** : respecter le thème global
- **Responsive** : sidebar collapse sur mobile

## Tâche 4 — Système de placeholders

### Syntaxe markdown custom

```markdown
:::diagram type=mermaid
description: "Architecture globale"
prompt: "Génère un diagramme d'architecture [...]"
file: "./assets/architecture.mmd"
:::

:::image
description: "Capture du dashboard admin"
prompt: "Capture d'écran annotée du dashboard [...]"
file: "./assets/dashboard-screenshot.png"
alt: "Dashboard admin Certio"
:::

:::video
description: "Tuto : créer son premier examen"
prompt: "Vidéo 3min expliquant [...]"
file: "./assets/tuto-first-exam.mp4"
embed: "https://youtube.com/..."
:::

:::interactive
description: "Quiz de compréhension CBM"
prompt: "Quiz React de 5 questions sur CBM [...]"
type: "quiz"
config: {...}
:::
```

### Parser

Créer `frontend/assets/components/MarkdownParser.js` :
- Fonction `parseMarkdown(text)` qui :
  1. Extrait les blocs `:::type ... :::`
  2. Les remplace par `<div data-placeholder="..."></div>`
  3. Parse le markdown restant avec marked.js
  4. Remonte les placeholders avec React components

### Composant `<DocsPlaceholder>`

Affiche :
- Si `file` existe et accessible → affiche le contenu (image, diagramme rendu, vidéo)
- Si pas de `file` → affiche :
  ```
  ┌────────────────────────────────────────┐
  │ 🎨 Placeholder (type)                  │
  │                                        │
  │ Description : ...                      │
  │                                        │
  │ Pour générer ce contenu, utilisez :   │
  │ [PROMPT IA à copier]                  │
  │                                        │
  │ Outil recommandé : DALL-E / Mermaid    │
  │                                        │
  │ [📋 Copier le prompt]                 │
  └────────────────────────────────────────┘
  ```

## Tâche 5 — Contenu initial (20+ pages)

### admin/01-dashboard.md

```markdown
---
title: Dashboard administrateur
description: Vue d'ensemble du dashboard Certio pour les administrateurs
order: 1
tags: [admin, dashboard]
updated: 2026-05-01
---

# 📊 Dashboard administrateur

Bienvenue dans le dashboard administrateur de Certio. Cette page vous présente une vue d'ensemble de votre plateforme.

## Ce que vous pouvez voir

:::image
description: "Capture annotée du dashboard admin avec numéros sur chaque zone"
prompt: "Create a screenshot annotation of the Certio admin dashboard. Include numbered callouts pointing to: (1) Top stats cards, (2) Recent activity feed, (3) Quick actions sidebar, (4) Alert notifications area, (5) User account dropdown. Style: modern flat design, subtle shadows."
file: "./assets/dashboard-admin-screenshot.png"
alt: "Dashboard admin avec annotations"
:::

## Statistiques principales

Le dashboard affiche :
- **Nombre d'utilisateurs actifs** par workspace
- **Nombre d'examens créés** ce mois-ci
- **Nombre de passages effectués**
- **Santé système** (CPU, RAM, espace disque)

## Actions rapides

:::diagram type=mermaid
description: "Flow des actions rapides disponibles pour l'admin"
prompt: "Génère un flowchart Mermaid pour les actions rapides de l'admin dans Certio"
file: "./assets/admin-actions-flow.mmd"
:::

[... etc.]
```

### Écrire 20+ pages similaires

Pour chaque page :
- Front matter YAML (title, description, order, tags, updated)
- Intro courte
- Sections avec h2/h3
- 2-3 placeholders par page (diagramme, image, vidéo ou interactif)
- Prompts IA **complets et précis** dans chaque placeholder
- Exemples concrets
- FAQ en fin de page si pertinent

**Focus sur la qualité du contenu** : ce seront les premières docs vues par les utilisateurs.

### Répartition suggérée

- 8 pages admin (Dashboard, Comptes, Workspaces, Backups, Monitoring, Audit, Settings, Community)
- 10 pages prof (Démarrage, Banque, Création examens, CBM, Distribution, Suivi, Analytics, Exports, FAQ, Advanced)
- 4 pages étudiant (Passer examen, Comprendre CBM, Correction, FAQ)
- 5 pages shared (Glossaire, Support, Licences, Privacy, About)

## Tâche 6 — Pages HTML admin/prof/etudiant

### `/frontend/admin/docs.html`

Page HTML hébergeant le `<DocsViewer>` avec React :
```html
<!DOCTYPE html>
<html lang="fr">
<head>
  <title>Certio — Documentation</title>
  <link rel="stylesheet" href="/assets/main.css">
  <script src="/assets/branding.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/dompurify/dist/purify.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
  <script src="https://unpkg.com/@highlightjs/cdn-assets/highlight.min.js"></script>
  <!-- React + Babel déjà inclus -->
</head>
<body>
  <div id="app"></div>
  <script type="text/babel" src="/assets/components/DocsViewer.jsx"></script>
  <script type="text/babel">
    ReactDOM.render(<DocsViewer userRole="admin" />, document.getElementById('app'));
  </script>
</body>
</html>
```

### Variantes

- `/frontend/prof/docs.html` : `userRole="enseignant"`
- `/frontend/etudiant/docs.html` : `userRole="etudiant"`

### Lien depuis dashboards existants

Ajouter bouton "📖 Documentation" dans :
- Sidebar admin
- Sidebar prof
- Menu étudiant (après connexion)

## Tâche 7 — Recherche full-text

Implémenter dans `DocumentationManager::search()` :
- Lit tous les .md accessibles pour le rôle
- Grep simple (case insensitive)
- Retourne matches avec contexte (50 chars avant + après)
- Highlight les occurrences dans le snippet

UI côté client :
- Barre de recherche en haut
- Debounce 300ms
- Dropdown avec résultats
- Click → navigate + highlight

## Tâche 8 — Tests E2E

Créer `backend/tests/test_api_docs.php` :
- Auth requise → 401 si pas connecté
- Admin access all ✓
- Prof access prof + etudiant + shared, pas admin
- Étudiant access etudiant + shared seulement
- Search fonctionne
- Path traversal bloqué (../)

Test manuel :
- Ouvrir /admin/docs.html → navigation OK
- Ouvrir /prof/docs.html → pas de section admin
- Rechercher "CBM" → résultats pertinents
- Cliquer sur un placeholder → dialog avec prompt IA

## Tâche 9 — Documentation (de la doc)

`docs/DOCUMENTATION_GUIDE.md` :
- Comment écrire une nouvelle page de doc
- Syntaxe des placeholders
- Conventions (front matter, structure)
- Comment remplir un placeholder (générer asset)

# COMMITS

```bash
git commit -m "feat(docs): implement DocumentationManager with RBAC"
git commit -m "test(docs): add 20+ tests for DocumentationManager"
git commit -m "feat(docs): add API endpoints /api/docs/*"
git commit -m "feat(docs): add DocsViewer React component"
git commit -m "feat(docs): add placeholder system (4 types)"
git commit -m "feat(docs): add 20+ initial markdown pages"
git commit -m "feat(docs): add /admin/docs.html, /prof/docs.html, /etudiant/docs.html"
git commit -m "feat(docs): add sidebar links to documentation"
git commit -m "feat(docs): implement full-text search"
git commit -m "test(docs): add E2E tests for RBAC and search"
git commit -m "docs(meta): add DOCUMENTATION_GUIDE.md"
```

# CRITÈRES D'ACCEPTATION

- [ ] Admin accède à toutes les docs
- [ ] Prof n'accède pas à admin/*
- [ ] Étudiant n'accède qu'à etudiant/* et shared/*
- [ ] Recherche trouve "CBM" dans pages pertinentes
- [ ] Mermaid diagrams rendus correctement
- [ ] Placeholders affichent prompt IA si asset absent
- [ ] 20+ pages écrites avec contenu utile
- [ ] TOC auto-générée fonctionne
- [ ] Responsive mobile (sidebar collapsable)
- [ ] Lien "Documentation" visible dans chaque dashboard

# TAG

```bash
git tag v2.0.0-beta.2
git push origin feat/p4-documentation --tags
```

Confirme et commence.
```

---

## 📋 Récapitulatif des 5 prompts

| Phase | Branche Git | Durée | Tag | Livrable clé |
|:-:|---|:-:|:-:|---|
| **P0** | `feat/p0-fondations` | 3j | `v2.0.0-alpha.0` | Rebranding + skeleton managers |
| **P1** | `feat/p1-cbm-core` | 5j | `v2.0.0-alpha.1` | CBM fonctionnel complet |
| **P2** | `feat/p2-question-types` | 4j | `v2.0.0-alpha.2` | 7 types de questions |
| **P3** | `feat/p3-scoring-analytics` | 4j | `v2.0.0-beta.1` | Scoring complet + analytics |
| **P4** | `feat/p4-documentation` | 5j | `v2.0.0-beta.2` | Doc interactive RBAC |
| **TOTAL P0-P4** | | **21 jours** | | Core Certio v2.0 beta |

---

## 💡 Conseils d'exécution

### Pour Claude Code

- Copie le prompt en entier
- Laisse l'agent explorer les fichiers existants en premier
- Valide régulièrement (commits atomiques)
- Utilise `/clear` entre phases pour contexte propre

### Pour Cursor

- Utilise le mode **Composer** (pas Chat)
- Active "Agent mode" pour multi-fichiers
- Garde les notes de cadrage en context

### Pour Copilot Agent

- Découpe en sous-tâches plus petites
- Donne les schémas JSON explicitement
- Valide chaque sous-tâche avant la suivante

---

## ⏭️ Prochain livrable (4/5)

Le **livrable 4** contiendra les prompts VS Code pour **Phases P5 à P7** :
- P5 : Améliorations v2.0 (2FA, Workspaces, SSO, LMS, i18n, PWA, Community)
- P6 : Tests complets (unitaires, E2E, sécurité, perf, a11y)
- P7 : Migration & Déploiement production

---

© 2026 Mohamed EL AFRIT — mohamed@elafrit.com  
Certio v2.0 — CC BY-NC-SA 4.0
