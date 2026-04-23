# 🎯 Prompts VS Code Laravel — Phases P-1 à P4

> **Livrable C/4 — Prompts prêts à copier-coller dans Claude Code / Cursor / Copilot Agent**

| Champ | Valeur |
|---|---|
| **Livrable** | C/4 (Laravel) |
| **Phases couvertes** | P-1, P0, P1, P2, P3, P4 |
| **Version** | 1.0 |
| **Auteur** | Mohamed EL AFRIT |
| **Licence** | CC BY-NC-SA 4.0 |

---

## 📖 Guide d'utilisation rapide

### 🎯 Workflow

1. Ouvrir le projet `certio/` dans VS Code
2. Lancer **Claude Code** (recommandé pour Laravel)
3. Coller le prompt de la phase
4. Review + valider + commit
5. Passer à la phase suivante

### 🤖 IA recommandée par phase

| Phase | IA | Pourquoi |
|:-:|---|---|
| **P-1 Formation** | Claude Code | Aide l'apprentissage, explique |
| **P0 Bootstrap** | Copilot Agent | Exécution rapide install packages |
| **P1 Migration** | Claude Code | Logique métier complexe |
| **P2 CBM** | Claude Code | Service + UI Vue |
| **P3 Questions** | Cursor | UI composants adaptatives |
| **P4 Analytics** | Claude Code | Queries Eloquent complexes |

### 📋 Conventions Laravel

- **Branches** : `feat/pX-nom-phase`
- **Commits** : Conventional Commits avec scope Laravel
- **Tests** : Pest en parallèle du code, pas après
- **Format code** : Laravel Pint après chaque phase
- **Static analysis** : Larastan niveau 8

---

## Sommaire

1. [Prompt Phase P-1 — Formation Laravel](#prompt-phase-p-1--formation-laravel)
2. [Prompt Phase P0 — Bootstrap Laravel](#prompt-phase-p0--bootstrap-laravel)
3. [Prompt Phase P1 — Migration données](#prompt-phase-p1--migration-données)
4. [Prompt Phase P2 — CBM Core + Duplication](#prompt-phase-p2--cbm-core--duplication)
5. [Prompt Phase P3 — Types questions + Multi-select](#prompt-phase-p3--types-questions--multi-select)
6. [Prompt Phase P4 — Scoring & Analytics](#prompt-phase-p4--scoring--analytics)

---

## Prompt Phase P-1 — Formation Laravel

### 🎯 À copier-coller (5 jours)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P-1 : FORMATION

Je démarre un projet appelé **Certio v2.0** en Laravel. Avant d'attaquer le vrai projet, je dois me former à la stack Laravel 11 + Vue 3 + Inertia + SQLite + Pest.

## 📚 Objectif
Construire un POC "Mini-Certio" qui inclut :
- Auth avec Breeze (Inertia + Vue)
- CRUD Exams basique
- CRUD Questions basique
- 1 page d'accueil prof avec liste examens
- 5 tests Pest qui passent

## 🎯 Mission

Aide-moi à construire ce POC en me guidant pas à pas. À chaque étape :
1. Explique-moi ce qu'on fait
2. Donne-moi la/les commandes/code
3. Explique-moi pourquoi on le fait comme ça
4. Montre-moi 1 ou 2 variantes possibles avec leurs pros/cons

Si je pose une question, réponds de façon pédagogique.

## 📋 Étapes à suivre

### Étape 1 : Setup projet
- Créer projet Laravel 11
- Installer PHP 8.3, Composer, Node.js
- `composer create-project laravel/laravel mini-certio`
- Configurer SQLite dans .env
- Vérifier que `php artisan serve` fonctionne

### Étape 2 : Installer Breeze Inertia + Vue
- `composer require laravel/breeze --dev`
- `php artisan breeze:install vue --pest`
- `npm install && npm run dev`
- Tester login/register auto-généré

### Étape 3 : Model Exam + Migration
- Créer migration pour table `exams` avec champs :
  - id, title, description, status (draft/published/closed)
  - duration_minutes, creator_id (user), timestamps
- Créer Model Exam avec cast status en enum
- Créer Factory ExamFactory
- Créer Seeder qui crée 5 examens pour un user test

### Étape 4 : CRUD Exams
- Créer ExamController avec index, create, store, show, edit, update, destroy
- Créer FormRequest `StoreExamRequest` pour validation
- Créer Inertia pages :
  - `Pages/Exams/Index.vue` (liste)
  - `Pages/Exams/Create.vue` (form création)
  - `Pages/Exams/Edit.vue` (form édition)
  - `Pages/Exams/Show.vue` (détail)
- Ajouter routes dans web.php
- Ajouter lien "Mes examens" dans nav
- Protéger par middleware auth

### Étape 5 : Model Question + Relation
- Migration `questions` : id, exam_id, statement, type, options (json), timestamps
- Model Question avec relation `belongsTo` vers Exam
- Model Exam avec relation `hasMany` vers questions
- Factory + seeder pour questions

### Étape 6 : Ajouter questions à un examen
- Dans `Pages/Exams/Show.vue` : afficher la liste des questions
- Bouton "Ajouter une question" qui ouvre modal
- Form pour créer une question liée à l'examen

### Étape 7 : Tests Pest
Écrire 5 tests Feature :

```php
// tests/Feature/ExamTest.php
test('authenticated user can see exams index', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/exams')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Exams/Index'));
});

test('authenticated user can create exam', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post('/exams', [
        'title' => 'Test Exam',
        'description' => 'Description',
        'duration_minutes' => 60,
    ])->assertRedirect();
    
    expect(Exam::where('title', 'Test Exam')->first())->not->toBeNull();
});

test('guest cannot access exams', function () {
    $this->get('/exams')->assertRedirect('/login');
});

// 2 autres tests au choix
```

### Étape 8 : Debug tools
- Installer Laravel Telescope : `composer require laravel/telescope --dev`
- `php artisan telescope:install && php artisan migrate`
- Explorer `/telescope` dans le navigateur

### Étape 9 : Déployer sur GitHub
- git init, push sur repo public `mini-certio`
- README expliquant ce que c'est

## 🎓 Points clés à comprendre

À la fin de ces étapes, je dois pouvoir expliquer ces concepts :

1. Laravel MVC (Model, Controller, View/Page)
2. Eloquent ORM : relations, factories, scopes
3. Inertia : comment ça remplace une API REST
4. Vue 3 Composition API avec `<script setup>`
5. FormRequest pour validation
6. Middleware auth + authorization
7. Pest : structure et assertions
8. Laravel Telescope pour debug

## 🎁 Bonus (si temps)

Si j'ai fini en avance :
- Ajouter Tailwind CSS custom styling
- Ajouter une page de dashboard avec stats
- Installer Filament et créer 1 resource admin
- Déployer le POC sur un VPS de test

## ✅ Critères de succès

- [ ] Projet Laravel fonctionne
- [ ] Breeze installé avec Inertia+Vue
- [ ] CRUD Exams complet et fonctionnel
- [ ] Relation Exam ↔ Question OK
- [ ] 5 tests Pest passent
- [ ] Code pushé sur GitHub
- [ ] Je comprends tous les concepts listés ci-dessus

## 🚀 Let's go !

Commence par l'Étape 1. Guide-moi, explique-moi, et passons à l'étape suivante seulement quand je valide.
```

---

## Prompt Phase P0 — Bootstrap Laravel

### 🎯 À copier-coller (4 jours)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P0 : BOOTSTRAP

La Phase P-1 (formation + POC) est terminée. Je connais Laravel 11, Inertia, Vue 3, Pest. Je démarre maintenant le vrai projet Certio v2.0.

## 📚 Documents de référence
- `certio_v2_laravel/01_NOTE_DE_CADRAGE_LARAVEL.md` (vision complète)
- `certio_v2_laravel/02_PLANNING_LARAVEL_REVISE.md` (planning 9 phases)

## 🎯 Objectif Phase P0 (4 jours)

Bootstrap complet du projet Certio Laravel :
1. Création du projet Laravel 11 neuf (repo séparé)
2. Installation de tous les packages (Fortify, Socialite, Spatie, Filament, etc.)
3. Setup Inertia + Vue 3 + Tailwind
4. Configuration centralisée branding
5. PWA manifest + service worker
6. i18n FR/EN de base
7. CI/CD GitHub Actions
8. Déploiement staging VPS Ubuntu

## 📋 TÂCHES DÉTAILLÉES

### Tâche 1 — Création projet Laravel

```bash
# Créer projet dans un nouveau dossier (distinct du POC)
composer create-project laravel/laravel certio "^11.0"
cd certio

# Git init + nouveau repo
git init
git remote add origin https://github.com/melafrit/certio.git
git branch -M main
```

### Tâche 2 — Configuration SQLite

Dans `.env` :
```env
APP_NAME=Certio
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/certio/database/database.sqlite
DB_FOREIGN_KEYS=true

MAIL_MAILER=smtp
MAIL_HOST=ssl0.ovh.net
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
```

```bash
touch database/database.sqlite
php artisan key:generate
php artisan migrate
```

### Tâche 3 — Installer Breeze Inertia + Vue

```bash
composer require laravel/breeze --dev
php artisan breeze:install vue --pest
npm install
npm run build
```

### Tâche 4 — Installer packages Laravel core

```bash
# Auth & Security
composer require laravel/fortify laravel/socialite laravel/sanctum

# Search
composer require laravel/scout

# Spatie packages
composer require spatie/laravel-permission
composer require spatie/laravel-activitylog
composer require spatie/laravel-backup
composer require spatie/browsershot
composer require spatie/laravel-query-builder

# Imports/Exports
composer require maatwebsite/excel

# Admin panel
composer require filament/filament

# Dev packages
composer require --dev larastan/larastan
composer require --dev laravel/pint
composer require --dev barryvdh/laravel-debugbar
composer require --dev laravel/telescope
composer require --dev nunomaduro/collision

# Publier les configs
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
php artisan telescope:install
php artisan fortify:install
php artisan migrate
```

### Tâche 5 — Installer packages NPM

```bash
npm install @inertiajs/vue3@latest
npm install marked dompurify
npm install chart.js vue-chartjs
npm install katex
npm install vue-i18n@9
npm install @vueuse/core
npm install zod
```

### Tâche 6 — Configuration centralisée branding

Créer `config/branding.php` :

```php
<?php

return [
    'app_name' => env('APP_NAME', 'Certio'),
    'app_slug' => 'certio',
    'app_tagline' => 'Certainty-Based Assessment Platform',
    'app_description' => 'Plateforme d\'évaluation avec Certainty-Based Marking',
    'app_url' => env('APP_URL', 'https://certio.app'),
    'app_version' => '2.0.0',
    
    'logo_svg' => '/assets/img/logo.svg',
    'logo_png' => '/assets/img/logo.png',
    'favicon' => '/assets/img/favicon.ico',
    
    'colors' => [
        'primary' => '#1a365d',
        'secondary' => '#48bb78',
        'accent' => '#ed8936',
        'success' => '#38a169',
        'danger' => '#e53e3e',
        'warning' => '#d69e2e',
        'info' => '#3182ce',
    ],
    
    'contact_email' => 'mohamed@elafrit.com',
    'support_email' => 'mohamed@elafrit.com',
    'noreply_email' => 'mohamed@elafrit.com',
    
    'copyright_owner' => 'Mohamed EL AFRIT',
    'copyright_year' => '2026',
    'license' => 'CC BY-NC-SA 4.0',
    'license_url' => 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
    
    'features' => [
        'cbm_enabled_default' => false,
        'community_bank_enabled' => true,
        'multi_tenant_enabled' => true,
        'sso_google_enabled' => true,
        'sso_microsoft_enabled' => true,
        'pwa_enabled' => true,
        'i18n_enabled' => true,
        'default_locale' => 'fr',
        'available_locales' => ['fr', 'en'],
    ],
];
```

Créer helper `app/Helpers/branding.php` :

```php
<?php

if (!function_exists('branding')) {
    function branding(string $key, $default = null) {
        return config("branding.$key", $default);
    }
}
```

Créer `resources/js/branding.js` :

```javascript
export const branding = {
  appName: 'Certio',
  appSlug: 'certio',
  appTagline: 'Certainty-Based Assessment Platform',
  appVersion: '2.0.0',
  logoUrl: '/assets/img/logo.svg',
  colors: {
    primary: '#1a365d',
    secondary: '#48bb78',
    accent: '#ed8936',
  },
};
```

### Tâche 7 — Assets statiques

Créer dossier `public/assets/img/` avec :
- `logo.svg` (placeholder Certio)
- `favicon.ico`
- `icon-192.png` et `icon-512.png` (pour PWA)

Logo SVG placeholder :
```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 60" width="200" height="60">
  <rect width="200" height="60" fill="#1a365d"/>
  <text x="100" y="40" font-family="Arial, sans-serif" font-size="28" 
        font-weight="bold" fill="white" text-anchor="middle">Certio</text>
</svg>
```

### Tâche 8 — PWA Manifest + Service Worker

Créer `public/manifest.json` :
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
    { "src": "/assets/img/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/assets/img/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ],
  "categories": ["education", "productivity"],
  "lang": "fr"
}
```

Créer `public/service-worker.js` (minimal, étoffé en P6) :
```javascript
const CACHE_NAME = 'certio-v2.0.0';
const CACHE_URLS = ['/', '/assets/img/logo.svg'];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(CACHE_URLS))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(response => response || fetch(event.request))
  );
});
```

Dans `resources/views/app.blade.php`, ajouter dans le `<head>` :
```html
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#1a365d">
<link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon">
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/service-worker.js');
    });
  }
</script>
```

### Tâche 9 — i18n FR/EN

Créer `resources/lang/fr.json` :
```json
{
  "common": {
    "save": "Enregistrer",
    "cancel": "Annuler",
    "delete": "Supprimer",
    "edit": "Modifier",
    "create": "Créer",
    "search": "Rechercher",
    "loading": "Chargement…"
  },
  "auth": {
    "login": "Connexion",
    "logout": "Déconnexion",
    "email": "Email",
    "password": "Mot de passe"
  },
  "exam": {
    "create": "Créer un examen",
    "title": "Titre",
    "duration": "Durée",
    "questions": "Questions"
  }
}
```

Même structure pour `resources/lang/en.json` en anglais.

Dans `resources/js/app.js`, config Vue-i18n :
```javascript
import { createI18n } from 'vue-i18n';
import fr from '../lang/fr.json';
import en from '../lang/en.json';

const i18n = createI18n({
  legacy: false,
  locale: 'fr',
  fallbackLocale: 'en',
  messages: { fr, en },
});

createInertiaApp({
  setup({ el, App, props, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(i18n)
      .mount(el);
  },
});
```

### Tâche 10 — CI/CD GitHub Actions

Créer `.github/workflows/tests.yml` :
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, sqlite3, pdo_sqlite
          coverage: xdebug
      
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      
      - name: Copy .env
        run: cp .env.example .env
      
      - name: Install Composer deps
        run: composer install --no-interaction --prefer-dist
      
      - name: Install NPM deps
        run: npm ci
      
      - name: Build assets
        run: npm run build
      
      - name: Generate key
        run: php artisan key:generate
      
      - name: Create SQLite database
        run: |
          touch database/database.sqlite
          php artisan migrate --force
      
      - name: Run Pest tests
        run: ./vendor/bin/pest --coverage
      
      - name: Run Pint (code style)
        run: ./vendor/bin/pint --test
      
      - name: Run Larastan (static analysis)
        run: ./vendor/bin/phpstan analyse --memory-limit=2G
```

Créer `phpstan.neon` :
```yaml
includes:
    - ./vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app/
        - config/
        - database/
    level: 5  # augmenter progressivement à 8
    ignoreErrors:
        - '#Unsafe usage of new static.*#'
    excludePaths:
        - ./*/*/FileToBeExcluded.php
```

### Tâche 11 — Setup VPS staging

Sur le VPS Ubuntu :

```bash
# Installer dépendances
sudo apt update
sudo apt install -y nginx php8.3-fpm php8.3-sqlite3 php8.3-xml php8.3-mbstring \
  php8.3-curl php8.3-zip composer nodejs npm certbot python3-certbot-nginx

# Clone repo
cd /var/www
sudo git clone https://github.com/melafrit/certio.git
cd certio
sudo chown -R www-data:www-data .

# Installer deps
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install
sudo -u www-data npm run build

# Configurer .env prod
sudo cp .env.example .env
sudo nano .env  # éditer APP_ENV=production, APP_DEBUG=false, etc.
sudo -u www-data php artisan key:generate
sudo -u www-data touch database/database.sqlite
sudo -u www-data php artisan migrate --force

# Nginx config
sudo nano /etc/nginx/sites-available/certio
```

Contenu config Nginx :
```nginx
server {
    listen 80;
    server_name staging.certio.app;
    root /var/www/certio/public;
    index index.php;
    
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/certio /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# SSL Let's Encrypt
sudo certbot --nginx -d staging.certio.app

# Test
curl https://staging.certio.app
```

### Tâche 12 — README.md initial

Créer README.md professionnel avec :
- Badge version
- Description courte
- Installation locale
- Tech stack
- Licence

## ✅ CRITÈRES D'ACCEPTATION

- [ ] `composer validate` → OK
- [ ] `php artisan test` → tests Breeze passent (3-5 tests)
- [ ] `npm run build` → succès sans warnings
- [ ] `./vendor/bin/pint --test` → code style OK
- [ ] `./vendor/bin/phpstan analyse` → niveau 5 OK
- [ ] Page d'accueil affiche "Certio" avec logo
- [ ] Login/register Breeze fonctionnent
- [ ] `https://staging.certio.app` accessible en HTTPS
- [ ] GitHub Actions green sur push
- [ ] PWA manifest valide (chrome://manifest)
- [ ] i18n switch FR/EN fonctionne
- [ ] Tag `v2.0.0-alpha.0` créé et pushé

## 📝 COMMITS ATTENDUS

- `chore(init): create Laravel 11 project`
- `chore(deps): install Breeze with Inertia+Vue`
- `chore(deps): install Fortify, Socialite, Sanctum, Scout`
- `chore(deps): install Spatie packages`
- `chore(deps): install Filament admin`
- `chore(deps): install dev tools (Pint, Larastan, Telescope)`
- `feat(branding): add centralized branding config`
- `feat(pwa): add manifest and service worker`
- `feat(i18n): add FR/EN base translations`
- `chore(ci): add GitHub Actions workflow`
- `docs(readme): add initial README`

**Commence par la Tâche 1 (création projet Laravel) et guide-moi pas à pas.**
```

---

## Prompt Phase P1 — Migration données

### 🎯 À copier-coller (5 jours)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P1 : MIGRATION DONNÉES

La Phase P0 (Bootstrap) est terminée. Tu as maintenant Certio Laravel installé avec tous les packages.

## 📚 Documents de référence
- `certio_v2_laravel/01_NOTE_DE_CADRAGE_LARAVEL.md` section 5 (Modèle de données)
- Ancienne base v1 : JSON files dans `/var/www/certio-v1/data/`

## 🎯 Objectif Phase P1 (5 jours)

1. Créer toutes les migrations Laravel (15+ tables)
2. Créer tous les Models Eloquent avec relations
3. Créer Enums PHP 8.1+ (QuestionType, ExamStatus, etc.)
4. Créer Factories + Seeders
5. Implémenter Artisan command `certio:migrate-from-v1`
6. Tester migration sur copie des données v1 réelles

## 📋 TÂCHES

### Tâche 1 — Migrations SQL (jour 1)

Créer les migrations suivantes dans l'ordre :

#### 1.1 Créer migration `workspaces`

```bash
php artisan make:migration create_workspaces_table
```

```php
Schema::create('workspaces', function (Blueprint $table) {
    $table->id();
    $table->string('uuid')->unique(); // WKS-XXXX-YYYY
    $table->string('name');
    $table->string('slug')->unique();
    $table->enum('plan', ['free', 'pro', 'enterprise'])->default('free');
    $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active');
    $table->json('branding')->nullable();
    $table->json('settings')->nullable();
    $table->json('limits')->nullable();
    $table->timestamp('subscription_end')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['status', 'plan']);
});
```

#### 1.2 Étendre `users`

```bash
php artisan make:migration add_certio_fields_to_users_table
```

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('uuid')->unique()->after('id');
    $table->foreignId('workspace_id')->nullable()->after('uuid')
        ->constrained()->nullOnDelete();
    $table->string('nom')->nullable()->after('name');
    $table->string('prenom')->nullable()->after('nom');
    $table->enum('role', ['super_admin', 'admin', 'enseignant', 'etudiant'])
        ->default('etudiant')->after('email');
    $table->json('ai_api_keys')->nullable(); // Chiffrées
    $table->string('totp_secret')->nullable();
    $table->boolean('totp_enabled')->default(false);
    $table->json('totp_backup_codes')->nullable();
    $table->timestamp('last_login_at')->nullable();
    $table->softDeletes();
});
```

#### 1.3 Autres migrations

Créer dans cet ordre :
- `exams` (voir note de cadrage section 5.2)
- `questions` (section 5.3)
- `exam_question` (pivot)
- `passages` (section 5.4)
- `cbm_presets` (section 5.5)
- `community_questions` (section 5.6)
- `community_question_ratings`
- `community_question_flags`

**IMPORTANT SQLite** : Activer foreign keys dans `config/database.php` :
```php
'sqlite' => [
    'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
    // ...
],
```

#### 1.4 FTS5 Virtual table pour recherche

Créer migration `create_questions_fts_table.php` :
```php
public function up(): void
{
    DB::statement("
        CREATE VIRTUAL TABLE questions_fts USING fts5(
            statement, 
            explanation, 
            tags,
            content='questions',
            content_rowid='id'
        )
    ");
    
    // Triggers pour sync auto
    DB::statement("
        CREATE TRIGGER questions_ai AFTER INSERT ON questions BEGIN
            INSERT INTO questions_fts(rowid, statement, explanation, tags) 
            VALUES (new.id, new.statement, new.explanation, new.tags);
        END;
    ");
    
    DB::statement("
        CREATE TRIGGER questions_au AFTER UPDATE ON questions BEGIN
            UPDATE questions_fts 
            SET statement = new.statement, 
                explanation = new.explanation, 
                tags = new.tags
            WHERE rowid = old.id;
        END;
    ");
    
    DB::statement("
        CREATE TRIGGER questions_ad AFTER DELETE ON questions BEGIN
            DELETE FROM questions_fts WHERE rowid = old.id;
        END;
    ");
}
```

### Tâche 2 — Enums PHP 8.1+ (jour 2)

Créer les enums dans `app/Enums/` :

#### 2.1 QuestionType

```php
<?php
namespace App\Enums;

enum QuestionType: string
{
    case TrueFalse = 'true_false';
    case McqSingle4 = 'mcq_single_4';
    case McqSingle5 = 'mcq_single_5';
    case McqSingleN = 'mcq_single_n';
    case McqMultiple4 = 'mcq_multiple_4';
    case McqMultiple5 = 'mcq_multiple_5';
    case McqMultipleN = 'mcq_multiple_n';
    
    public function label(): string
    {
        return match($this) {
            self::TrueFalse => 'Vrai / Faux',
            self::McqSingle4 => 'QCM 4 options (choix unique)',
            self::McqSingle5 => 'QCM 5 options (choix unique)',
            self::McqSingleN => 'QCM N options (choix unique)',
            self::McqMultiple4 => 'QCM 4 options (choix multiple)',
            self::McqMultiple5 => 'QCM 5 options (choix multiple)',
            self::McqMultipleN => 'QCM N options (choix multiple)',
        };
    }
    
    public function isMultiple(): bool
    {
        return str_starts_with($this->value, 'mcq_multiple');
    }
    
    public function defaultOptionsCount(): int
    {
        return match(true) {
            $this === self::TrueFalse => 2,
            str_ends_with($this->value, '_4') => 4,
            str_ends_with($this->value, '_5') => 5,
            default => 4,
        };
    }
    
    public function canBeConfigured(): bool
    {
        return str_ends_with($this->value, '_n');
    }
}
```

#### 2.2 Autres enums

Créer aussi :
- `ExamStatus` (draft, published, closed, archived)
- `PassageStatus` (in_progress, submitted, expired, invalidated)
- `Visibility` (private, workspace, community)
- `License` (CC-BY, CC-BY-SA, CC-BY-NC, CC-0)
- `ReviewStatus` (pending, approved, rejected)
- `UserRole` (super_admin, admin, enseignant, etudiant)

### Tâche 3 — Traits réutilisables

Créer dans `app/Traits/` :

#### 3.1 HasUuid

```php
<?php
namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = static::generateUuid();
            }
        });
    }
    
    protected static function generateUuid(): string
    {
        // Format: PRE-XXXX-YYYY
        $prefix = static::uuidPrefix();
        $part1 = strtoupper(Str::random(4));
        $part2 = strtoupper(Str::random(4));
        return "{$prefix}-{$part1}-{$part2}";
    }
    
    protected static function uuidPrefix(): string
    {
        // Override dans chaque model
        return strtoupper(substr(class_basename(static::class), 0, 3));
    }
    
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
```

#### 3.2 BelongsToWorkspace

```php
<?php
namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Workspace;

trait BelongsToWorkspace
{
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
    
    public function scopeForWorkspace($query, $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }
    
    public function scopeCurrentWorkspace($query)
    {
        if (auth()->check() && auth()->user()->workspace_id) {
            return $query->where('workspace_id', auth()->user()->workspace_id);
        }
        return $query;
    }
}
```

### Tâche 4 — Models Eloquent (jour 2-3)

#### 4.1 Model Exam

```bash
php artisan make:model Exam
```

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\ExamStatus;
use App\Traits\HasUuid;
use App\Traits\BelongsToWorkspace;

class Exam extends Model
{
    use HasFactory, SoftDeletes, HasUuid, BelongsToWorkspace;
    
    protected $fillable = [
        'uuid', 'workspace_id', 'creator_id', 'title', 'description',
        'status', 'access_code', 'date_ouverture', 'date_cloture',
        'duration_minutes', 'max_passages', 'shuffle_questions',
        'shuffle_options', 'anti_cheat_config', 'cbm_enabled',
        'cbm_matrix', 'multi_answer_mode', 'total_points',
        'passing_score', 'locale', 'tags',
    ];
    
    protected $casts = [
        'status' => ExamStatus::class,
        'date_ouverture' => 'datetime',
        'date_cloture' => 'datetime',
        'duration_minutes' => 'integer',
        'max_passages' => 'integer',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'anti_cheat_config' => 'array',
        'cbm_enabled' => 'boolean',
        'cbm_matrix' => 'array',
        'total_points' => 'integer',
        'passing_score' => 'integer',
        'tags' => 'array',
    ];
    
    protected static function uuidPrefix(): string
    {
        return 'EXM';
    }
    
    // Relations
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
    
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class)
            ->withPivot('weight', 'order')
            ->orderBy('pivot_order');
    }
    
    public function passages(): HasMany
    {
        return $this->hasMany(Passage::class);
    }
    
    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', ExamStatus::Published);
    }
    
    public function scopeActive($query)
    {
        return $query->published()
            ->where(function ($q) {
                $q->whereNull('date_ouverture')
                  ->orWhere('date_ouverture', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('date_cloture')
                  ->orWhere('date_cloture', '>', now());
            });
    }
    
    // Accessors / Mutators
    public function getUrlAttribute(): string
    {
        return route('exams.show', $this);
    }
    
    public function getIsActiveAttribute(): bool
    {
        if ($this->status !== ExamStatus::Published) return false;
        if ($this->date_ouverture && $this->date_ouverture->isFuture()) return false;
        if ($this->date_cloture && $this->date_cloture->isPast()) return false;
        return true;
    }
    
    // Méthodes métier
    public function canAcceptNewPassages(): bool
    {
        if (!$this->is_active) return false;
        if ($this->max_passages && $this->passages()->count() >= $this->max_passages) return false;
        return true;
    }
    
    public function hasCbmEnabled(): bool
    {
        return $this->cbm_enabled && !empty($this->cbm_matrix);
    }
    
    public function regenerateAccessCode(): void
    {
        $this->access_code = strtoupper(substr(md5(uniqid()), 0, 8));
        $this->save();
    }
}
```

Faire pareil pour : `Workspace`, `Question`, `Passage`, `CbmPreset`, `CommunityQuestion`, etc.

### Tâche 5 — Factories + Seeders (jour 3)

Créer `database/factories/ExamFactory.php` :

```php
<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Exam;
use App\Models\User;
use App\Models\Workspace;
use App\Enums\ExamStatus;

class ExamFactory extends Factory
{
    protected $model = Exam::class;
    
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'creator_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status' => ExamStatus::Draft,
            'duration_minutes' => $this->faker->randomElement([30, 60, 90, 120]),
            'total_points' => 100,
            'passing_score' => 60,
            'locale' => 'fr',
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'cbm_enabled' => false,
        ];
    }
    
    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ExamStatus::Published,
            'access_code' => strtoupper(substr(md5(uniqid()), 0, 8)),
        ]);
    }
    
    public function withCbm(array $matrix = null): static
    {
        return $this->state(fn () => [
            'cbm_enabled' => true,
            'cbm_matrix' => $matrix ?? [
                'levels' => [
                    ['id' => 1, 'label' => 'Incertain', 'value' => 0],
                    ['id' => 2, 'label' => 'Plutôt sûr', 'value' => 50],
                    ['id' => 3, 'label' => 'Certain', 'value' => 100],
                ],
                'scoring' => [
                    ['level_id' => 1, 'correct' => 1, 'incorrect' => 0],
                    ['level_id' => 2, 'correct' => 2, 'incorrect' => -1],
                    ['level_id' => 3, 'correct' => 3, 'incorrect' => -3],
                ],
            ],
        ]);
    }
}
```

Créer `database/seeders/DefaultWorkspaceSeeder.php` :
```php
public function run(): void
{
    Workspace::firstOrCreate(
        ['slug' => 'default'],
        [
            'uuid' => 'WKS-DEFAULT-0001',
            'name' => 'Workspace par défaut',
            'plan' => 'enterprise',
            'status' => 'active',
            'settings' => [
                'allow_community_publish' => true,
                'allow_sso_google' => false,
            ],
            'limits' => [
                'max_profs' => -1,
                'max_students_per_month' => -1,
            ],
        ]
    );
}
```

### Tâche 6 — Artisan Command Migration v1 → v2 (jour 4)

```bash
php artisan make:command MigrateFromV1
```

Implémenter dans `app/Console/Commands/MigrateFromV1.php` :

```php
<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Workspace;
use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Passage;
use App\Enums\QuestionType;
use App\Enums\ExamStatus;

class MigrateFromV1 extends Command
{
    protected $signature = 'certio:migrate-from-v1 
                            {--source= : Path to v1 data directory}
                            {--dry-run : Validate without writing}
                            {--verbose : Detailed output}';
    
    protected $description = 'Migrate data from Certio v1 (JSON files) to v2 (SQLite)';
    
    private array $stats = [];
    
    public function handle(): int
    {
        $source = $this->option('source');
        $dryRun = $this->option('dry-run');
        
        if (!$source || !is_dir($source)) {
            $this->error('❌ Source directory not found');
            return self::FAILURE;
        }
        
        $this->info("🚀 Migrating from v1: $source");
        $this->info($dryRun ? "📝 DRY RUN mode" : "💾 REAL migration");
        $this->newLine();
        
        try {
            DB::beginTransaction();
            
            $workspace = $this->ensureDefaultWorkspace();
            $this->migrateUsers($source, $workspace);
            $this->migrateQuestions($source, $workspace);
            $this->migrateExams($source, $workspace);
            $this->migratePassages($source, $workspace);
            
            if ($dryRun) {
                DB::rollBack();
                $this->warn('📝 DRY RUN - Changes rolled back');
            } else {
                DB::commit();
                $this->info('✅ Migration completed successfully');
            }
            
            $this->printStats();
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
    
    private function ensureDefaultWorkspace(): Workspace
    {
        return Workspace::firstOrCreate(
            ['slug' => 'default'],
            [
                'uuid' => 'WKS-DEFAULT-0001',
                'name' => 'Workspace par défaut (migré v1)',
                'plan' => 'enterprise',
                'status' => 'active',
            ]
        );
    }
    
    private function migrateUsers(string $source, Workspace $workspace): void
    {
        $file = "$source/users.json";
        if (!file_exists($file)) {
            $this->warn("⚠️  users.json not found");
            return;
        }
        
        $users = json_decode(file_get_contents($file), true);
        $this->stats['users'] = ['total' => count($users), 'migrated' => 0];
        
        $bar = $this->output->createProgressBar(count($users));
        $bar->start();
        
        foreach ($users as $v1User) {
            User::updateOrCreate(
                ['email' => $v1User['email']],
                [
                    'uuid' => $this->generateUuid('USR'),
                    'workspace_id' => $workspace->id,
                    'name' => ($v1User['prenom'] ?? '') . ' ' . ($v1User['nom'] ?? ''),
                    'nom' => $v1User['nom'] ?? null,
                    'prenom' => $v1User['prenom'] ?? null,
                    'role' => $v1User['role'] ?? 'etudiant',
                    'password' => $v1User['password_hash'] ?? bcrypt('changeme'),
                    'created_at' => $v1User['created_at'] ?? now(),
                ]
            );
            
            $this->stats['users']['migrated']++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
    }
    
    private function migrateQuestions(string $source, Workspace $workspace): void
    {
        $file = "$source/questions.json";
        if (!file_exists($file)) return;
        
        $questions = json_decode(file_get_contents($file), true);
        $this->stats['questions'] = ['total' => count($questions), 'migrated' => 0];
        
        $bar = $this->output->createProgressBar(count($questions));
        $bar->start();
        
        foreach ($questions as $v1Q) {
            // Transform options (v1: array texts + correct_index → v2: array objets)
            $options = [];
            foreach ($v1Q['options'] as $idx => $text) {
                $options[] = [
                    'id' => chr(65 + $idx),
                    'text' => $text,
                    'is_correct' => ($idx === ($v1Q['correct_index'] ?? 0)),
                ];
            }
            
            // Déterminer le type selon nombre options
            $n = count($options);
            $type = match(true) {
                $n === 2 => QuestionType::TrueFalse,
                $n === 4 => QuestionType::McqSingle4,
                $n === 5 => QuestionType::McqSingle5,
                default => QuestionType::McqSingleN,
            };
            
            Question::create([
                'uuid' => $this->generateUuid('QST'),
                'workspace_id' => $workspace->id,
                'creator_id' => $v1Q['creator_id'] ?? 1,
                'visibility' => 'private',
                'type' => $type,
                'subtype_config' => ['num_options' => $n],
                'statement' => $v1Q['statement'],
                'options' => $options,
                'explanation' => $v1Q['explanation'] ?? null,
                'difficulty' => $v1Q['difficulty'] ?? 'medium',
                'tags' => $v1Q['tags'] ?? [],
                'module' => $v1Q['module'] ?? null,
                'chapitre' => $v1Q['chapitre'] ?? null,
                'theme' => $v1Q['theme'] ?? null,
                'locale' => 'fr',
                'created_at' => $v1Q['created_at'] ?? now(),
            ]);
            
            $this->stats['questions']['migrated']++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
    }
    
    private function migrateExams(string $source, Workspace $workspace): void
    {
        // Similaire pour exams + pivot exam_question
    }
    
    private function migratePassages(string $source, Workspace $workspace): void
    {
        // Similaire pour passages
    }
    
    private function generateUuid(string $prefix): string
    {
        return "{$prefix}-" . strtoupper(substr(md5(uniqid()), 0, 4)) 
             . "-" . strtoupper(substr(md5(uniqid()), 0, 4));
    }
    
    private function printStats(): void
    {
        $this->newLine();
        $this->info('📊 Migration Statistics:');
        $this->table(
            ['Entity', 'Total', 'Migrated'],
            collect($this->stats)->map(fn ($v, $k) => [$k, $v['total'], $v['migrated']])->toArray()
        );
    }
}
```

### Tâche 7 — Tests Pest

Créer tests pour chaque Model dans `tests/Feature/Models/` :
- `ExamTest.php` : relations, scopes, méthodes
- `QuestionTest.php` : enum type, options
- `UserTest.php` : workspace relation
- `PassageTest.php` : status transitions

Créer `tests/Feature/MigrationV1Test.php` :
```php
test('can migrate v1 data to v2', function () {
    // Setup fixtures v1
    $fixturesPath = base_path('tests/fixtures/v1');
    
    // Run migration
    $this->artisan('certio:migrate-from-v1', [
        '--source' => $fixturesPath,
    ])->assertSuccessful();
    
    // Assertions
    expect(\App\Models\User::count())->toBeGreaterThan(0);
    expect(\App\Models\Question::count())->toBeGreaterThan(0);
});

test('dry run does not write to database', function () {
    $fixturesPath = base_path('tests/fixtures/v1');
    $beforeCount = \App\Models\User::count();
    
    $this->artisan('certio:migrate-from-v1', [
        '--source' => $fixturesPath,
        '--dry-run' => true,
    ])->assertSuccessful();
    
    expect(\App\Models\User::count())->toBe($beforeCount);
});
```

## ✅ CRITÈRES D'ACCEPTATION

- [ ] `php artisan migrate:fresh --seed` → OK
- [ ] Toutes relations fonctionnent (test via tinker)
- [ ] `php artisan certio:migrate-from-v1 --source=/path --dry-run` → OK
- [ ] Migration réelle sur copie v1 → counts cohérents
- [ ] Pest tests > 85% coverage sur Models
- [ ] `./vendor/bin/phpstan` niveau 5 OK

## 📝 COMMITS ATTENDUS

- `feat(db): add workspaces migration`
- `feat(db): extend users table with Certio fields`
- `feat(db): add exams + questions + pivot migrations`
- `feat(db): add passages + cbm_presets migrations`
- `feat(db): add community_questions tables`
- `feat(db): add FTS5 virtual table for search`
- `feat(enums): add QuestionType + ExamStatus + other enums`
- `feat(traits): add HasUuid + BelongsToWorkspace`
- `feat(models): add Exam + Question models with relations`
- `feat(models): add Passage + CbmPreset + Workspace`
- `feat(factories): add factories for all models`
- `feat(seeders): add DefaultWorkspaceSeeder`
- `feat(migration): add MigrateFromV1 Artisan command`
- `test(migration): add tests for v1 migration`

**Guide-moi tâche par tâche en commençant par les migrations SQL (Tâche 1).**
```

---

## Prompt Phase P2 — CBM Core + Duplication

### 🎯 À copier-coller (5 jours)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P2 : CBM CORE + DUPLICATION

Phases P-1, P0, P1 terminées. Tu as maintenant les modèles Eloquent et la BDD avec données migrées.

## 🎯 Objectif Phase P2 (5 jours)

1. Implémenter CBM 100% paramétrable (CbmScoringService)
2. UI prof : éditeur matrice CBM (Vue 3)
3. UI étudiant : saisie certitude
4. Calcul scores + calibration
5. Presets CBM réutilisables
6. 🆕 **Duplication d'examen** avec options

## 📋 TÂCHES

### Tâche 1 — CbmScoringService (jour 1)

Créer `app/Services/CbmScoringService.php` :

```php
<?php
namespace App\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;

class CbmScoringService
{
    /**
     * Matrice par défaut 3 niveaux.
     */
    public function getDefaultMatrix(): array
    {
        return [
            'levels' => [
                ['id' => 1, 'label' => 'Incertain', 'value' => 0],
                ['id' => 2, 'label' => 'Plutôt sûr', 'value' => 50],
                ['id' => 3, 'label' => 'Certain', 'value' => 100],
            ],
            'scoring' => [
                ['level_id' => 1, 'correct' => 1, 'incorrect' => 0],
                ['level_id' => 2, 'correct' => 2, 'incorrect' => -1],
                ['level_id' => 3, 'correct' => 3, 'incorrect' => -3],
            ],
        ];
    }
    
    /**
     * Valide une matrice CBM.
     */
    public function validateMatrix(array $matrix): array
    {
        $errors = [];
        $warnings = [];
        
        if (!isset($matrix['levels']) || !is_array($matrix['levels'])) {
            $errors[] = 'Matrix must have levels array';
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
        
        $levels = $matrix['levels'];
        $scoring = $matrix['scoring'] ?? [];
        
        if (count($levels) < 2) $errors[] = 'At least 2 levels required';
        if (count($levels) > 10) $errors[] = 'Maximum 10 levels allowed';
        
        $levelIds = [];
        foreach ($levels as $level) {
            if (!isset($level['id'], $level['label'], $level['value'])) {
                $errors[] = 'Level missing required fields';
                continue;
            }
            if (in_array($level['id'], $levelIds)) {
                $errors[] = "Duplicate level id: {$level['id']}";
            }
            if ($level['value'] < 0 || $level['value'] > 100) {
                $errors[] = "Level value must be 0-100";
            }
            $levelIds[] = $level['id'];
        }
        
        foreach ($scoring as $score) {
            if (!in_array($score['level_id'], $levelIds)) {
                $errors[] = "Scoring references unknown level_id: {$score['level_id']}";
            }
            if ($score['correct'] < $score['incorrect']) {
                $warnings[] = "Level {$score['level_id']}: correct < incorrect (unusual)";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
    
    /**
     * Calcule le score CBM d'une réponse.
     */
    public function calculateScore(bool $isCorrect, int $cbmLevelId, array $matrix): float
    {
        $scoring = collect($matrix['scoring'])
            ->firstWhere('level_id', $cbmLevelId);
        
        if (!$scoring) {
            throw new InvalidArgumentException("Level $cbmLevelId not found in matrix");
        }
        
        return $isCorrect ? (float) $scoring['correct'] : (float) $scoring['incorrect'];
    }
    
    /**
     * Calcule la calibration d'un étudiant.
     */
    public function calculateCalibration(Collection $passages): array
    {
        $totalAnswers = 0;
        $byLevel = [];
        
        foreach ($passages as $passage) {
            foreach (($passage->answers ?? []) as $answer) {
                $levelId = $answer['cbm_level_id'] ?? null;
                if (!$levelId) continue;
                
                $level = collect($passage->exam->cbm_matrix['levels'] ?? [])
                    ->firstWhere('id', $levelId);
                if (!$level) continue;
                
                $isCorrect = $answer['is_correct'] ?? false;
                
                if (!isset($byLevel[$levelId])) {
                    $byLevel[$levelId] = [
                        'level_id' => $levelId,
                        'label' => $level['label'],
                        'expected_rate' => $level['value'] / 100,
                        'actual_correct' => 0,
                        'actual_total' => 0,
                    ];
                }
                
                $byLevel[$levelId]['actual_total']++;
                if ($isCorrect) $byLevel[$levelId]['actual_correct']++;
                $totalAnswers++;
            }
        }
        
        // Calculer actual_rate par niveau
        foreach ($byLevel as &$level) {
            $level['actual_rate'] = $level['actual_total'] > 0 
                ? $level['actual_correct'] / $level['actual_total'] 
                : 0;
            $level['delta'] = $level['actual_rate'] - $level['expected_rate'];
        }
        
        // Score global
        $globalScore = 1.0;
        foreach ($byLevel as $level) {
            $globalScore -= abs($level['delta']) / count($byLevel);
        }
        
        // Tendance
        $avgDelta = collect($byLevel)->avg('delta');
        $tendency = match(true) {
            abs($avgDelta) < 0.05 => 'well_calibrated',
            $avgDelta > 0.05 => 'underconfident',
            default => 'overconfident',
        };
        
        return [
            'global_score' => round($globalScore, 2),
            'tendency' => $tendency,
            'per_level' => array_values($byLevel),
            'total_answers' => $totalAnswers,
        ];
    }
}
```

### Tâche 2 — CbmPreset CRUD

Actions dans `app/Actions/Cbm/` :
- `SavePreset.php`
- `ListPresets.php`
- `DeletePreset.php`
- `UpdatePreset.php`

Controller `app/Http/Controllers/Prof/CbmPresetController.php` :

```php
class CbmPresetController extends Controller
{
    public function index()
    {
        return Inertia::render('Prof/CbmPresets/Index', [
            'presets' => auth()->user()->cbmPresets()->latest()->get(),
        ]);
    }
    
    public function store(StoreCbmPresetRequest $request, SavePreset $action)
    {
        $preset = $action->execute(auth()->user(), $request->validated());
        return back()->with('success', 'Preset enregistré');
    }
    
    // update, destroy...
}
```

### Tâche 3 — UI CbmMatrixEditor (Vue 3)

Créer `resources/js/Components/CbmMatrixEditor.vue` (voir code complet dans le planning).

Intégrer dans `Pages/Prof/Exams/Create.vue` et `Edit.vue`.

### Tâche 4 — UI CbmCertaintyInput (Étudiant)

```vue
<!-- resources/js/Components/CbmCertaintyInput.vue -->
<script setup>
defineProps({
  matrix: Object,
  currentLevelId: Number,
})
const emit = defineEmits(['select'])
</script>

<template>
  <div class="cbm-certainty-input" role="radiogroup" aria-label="Niveau de certitude">
    <p class="text-sm font-medium mb-2">Quel est votre niveau de certitude ?</p>
    <div class="flex gap-2">
      <button
        v-for="level in matrix.levels"
        :key="level.id"
        :class="[
          'flex-1 p-3 rounded border-2 transition',
          currentLevelId === level.id 
            ? 'border-blue-500 bg-blue-50' 
            : 'border-gray-200 hover:border-gray-300'
        ]"
        @click="emit('select', level.id)"
        :aria-pressed="currentLevelId === level.id"
      >
        <div class="font-semibold text-sm">{{ level.label }}</div>
        <div class="text-xs text-gray-500">{{ level.value }}%</div>
      </button>
    </div>
  </div>
</template>
```

### Tâche 5 — Intégration dans SubmitPassage

Action `app/Actions/Passage/SubmitPassage.php` :

```php
class SubmitPassage
{
    public function __construct(
        private CbmScoringService $cbmService,
    ) {}
    
    public function execute(Passage $passage): Passage
    {
        $exam = $passage->exam;
        $totalScore = 0;
        $maxScore = 0;
        $processedAnswers = [];
        
        foreach ($passage->answers ?? [] as $qId => $answer) {
            $question = $exam->questions()->where('questions.id', $qId)->first();
            if (!$question) continue;
            
            $weight = $question->pivot->weight;
            $maxScore += $weight;
            
            // Score base
            $isCorrect = $this->checkAnswer($question, $answer);
            $baseScore = $isCorrect ? 1.0 : 0.0;
            
            // Score CBM si activé
            $cbmScore = null;
            if ($exam->cbm_enabled && isset($answer['cbm_level_id'])) {
                $cbmScore = $this->cbmService->calculateScore(
                    $isCorrect,
                    $answer['cbm_level_id'],
                    $exam->cbm_matrix
                );
                $finalScore = $cbmScore * $weight;
            } else {
                $finalScore = $baseScore * $weight;
            }
            
            $totalScore += $finalScore;
            
            $processedAnswers[$qId] = array_merge($answer, [
                'is_correct' => $isCorrect,
                'base_score' => $baseScore,
                'cbm_score' => $cbmScore,
                'final_score' => $finalScore,
            ]);
        }
        
        // Calibration
        $calibration = null;
        if ($exam->cbm_enabled) {
            $calibration = $this->cbmService->calculateCalibration(
                collect([$passage])
            );
        }
        
        $passage->update([
            'answers' => $processedAnswers,
            'score_raw' => $totalScore,
            'score_max' => $maxScore,
            'score_percentage' => $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0,
            'cbm_calibration' => $calibration,
            'status' => PassageStatus::Submitted,
            'submitted_at' => now(),
        ]);
        
        event(new PassageSubmitted($passage));
        
        return $passage;
    }
}
```

### Tâche 6 — 🆕 Duplication d'examen (0.5j)

Action `app/Actions/Exam/CloneExam.php` :

```php
<?php
namespace App\Actions\Exam;

use App\Models\Exam;
use App\Models\User;
use App\Enums\ExamStatus;
use Illuminate\Support\Facades\DB;

class CloneExam
{
    public function execute(Exam $original, User $user, array $options = []): Exam
    {
        return DB::transaction(function () use ($original, $user, $options) {
            $clone = $original->replicate();
            $clone->uuid = null; // Sera regénéré via HasUuid
            $clone->title = $original->title . ' (copie)';
            $clone->status = ExamStatus::Draft;
            $clone->access_code = null;
            $clone->creator_id = $user->id;
            
            // Options d'exclusion
            if (!($options['clone_cbm'] ?? true)) {
                $clone->cbm_enabled = false;
                $clone->cbm_matrix = null;
            }
            
            if (!($options['clone_anti_cheat'] ?? true)) {
                $clone->anti_cheat_config = null;
            }
            
            if (!($options['clone_dates'] ?? false)) {
                $clone->date_ouverture = null;
                $clone->date_cloture = null;
            }
            
            $clone->save();
            
            // Cloner les questions
            foreach ($original->questions as $question) {
                $clone->questions()->attach($question->id, [
                    'weight' => $question->pivot->weight,
                    'order' => $question->pivot->order,
                ]);
            }
            
            // Audit log
            activity()
                ->causedBy($user)
                ->performedOn($clone)
                ->withProperties([
                    'cloned_from_id' => $original->id,
                    'cloned_from_uuid' => $original->uuid,
                    'options' => $options,
                ])
                ->log('exam.cloned');
            
            return $clone;
        });
    }
}
```

FormRequest `app/Http/Requests/CloneExamRequest.php` :

```php
class CloneExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('clone', $this->route('exam'));
    }
    
    public function rules(): array
    {
        return [
            'clone_cbm' => 'boolean',
            'clone_anti_cheat' => 'boolean',
            'clone_dates' => 'boolean',
        ];
    }
}
```

Policy `app/Policies/ExamPolicy.php` (ajouter) :

```php
public function clone(User $user, Exam $exam): bool
{
    // Le prof peut cloner ses propres examens ou ceux de son workspace
    return $user->workspace_id === $exam->workspace_id
        && in_array($user->role, ['enseignant', 'admin']);
}
```

Controller method dans `app/Http/Controllers/Prof/ExamController.php` :

```php
public function clone(Exam $exam, CloneExamRequest $request, CloneExam $action)
{
    $clone = $action->execute($exam, $request->user(), $request->validated());
    
    return redirect()->route('prof.exams.edit', $clone)
        ->with('success', 'Examen dupliqué. Vous pouvez maintenant le modifier.');
}
```

Route :

```php
// routes/web.php
Route::post('/prof/exams/{exam}/clone', [ExamController::class, 'clone'])
    ->name('prof.exams.clone');
```

UI dans `Pages/Prof/Exams/Index.vue` :

```vue
<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'

const cloneModal = ref(null)
const cloneForm = useForm({
  clone_cbm: true,
  clone_anti_cheat: true,
  clone_dates: false,
})

function openCloneModal(exam) {
  cloneModal.value = exam
}

function confirmClone() {
  cloneForm.post(route('prof.exams.clone', cloneModal.value), {
    onSuccess: () => { cloneModal.value = null }
  })
}
</script>

<template>
  <!-- Dans la liste d'examens -->
  <Button @click="openCloneModal(exam)" variant="secondary" size="sm">
    📋 Dupliquer
  </Button>
  
  <!-- Modal -->
  <Modal v-if="cloneModal" :show="!!cloneModal" @close="cloneModal = null">
    <template #title>Dupliquer "{{ cloneModal.title }}"</template>
    
    <div class="space-y-3">
      <label class="flex items-center gap-2">
        <input type="checkbox" v-model="cloneForm.clone_cbm" />
        <span>Copier la configuration CBM</span>
      </label>
      
      <label class="flex items-center gap-2">
        <input type="checkbox" v-model="cloneForm.clone_anti_cheat" />
        <span>Copier la configuration anti-triche</span>
      </label>
      
      <label class="flex items-center gap-2">
        <input type="checkbox" v-model="cloneForm.clone_dates" />
        <span>Copier les dates d'ouverture/clôture</span>
      </label>
      
      <p class="text-sm text-gray-600">
        ℹ️ Les questions seront copiées. Un nouveau code d'accès sera généré.
      </p>
    </div>
    
    <template #footer>
      <Button @click="cloneModal = null" variant="secondary">Annuler</Button>
      <Button @click="confirmClone" :disabled="cloneForm.processing">
        Dupliquer
      </Button>
    </template>
  </Modal>
</template>
```

### Tâche 7 — Tests Pest

```php
// tests/Feature/Cbm/CbmScoringTest.php
test('default matrix has 3 levels')
    ->expect(fn() => app(CbmScoringService::class)->getDefaultMatrix())
    ->toHaveKeys(['levels', 'scoring'])
    ->and->toBe(3);

test('validates a valid 3-level matrix', function () {
    $matrix = app(CbmScoringService::class)->getDefaultMatrix();
    $result = app(CbmScoringService::class)->validateMatrix($matrix);
    expect($result['valid'])->toBeTrue();
});

test('calculates correct score when certain and correct', function () {
    $matrix = app(CbmScoringService::class)->getDefaultMatrix();
    $score = app(CbmScoringService::class)->calculateScore(true, 3, $matrix);
    expect($score)->toBe(3.0);
});

// ... 15+ tests
```

Tests duplication :
```php
// tests/Feature/Exam/CloneExamTest.php
test('can clone exam with questions', function () {
    $user = User::factory()->create(['role' => 'enseignant']);
    $exam = Exam::factory()->withCbm()->create(['creator_id' => $user->id]);
    $exam->questions()->attach(Question::factory()->count(5)->create());
    
    $clone = app(CloneExam::class)->execute($exam, $user);
    
    expect($clone->questions)->toHaveCount(5)
        ->and($clone->cbm_enabled)->toBeTrue()
        ->and($clone->title)->toContain('(copie)');
});

test('can exclude CBM when cloning', function () {
    $user = User::factory()->create(['role' => 'enseignant']);
    $exam = Exam::factory()->withCbm()->create(['creator_id' => $user->id]);
    
    $clone = app(CloneExam::class)->execute($exam, $user, ['clone_cbm' => false]);
    
    expect($clone->cbm_enabled)->toBeFalse();
});
```

## ✅ CRITÈRES D'ACCEPTATION

- [ ] CbmScoringService avec 10+ méthodes et 30+ tests
- [ ] UI Matrix Editor fonctionnelle (création + édition)
- [ ] Presets save/load/delete OK
- [ ] Import/export JSON matrice
- [ ] UI étudiant avec certainty input
- [ ] Score CBM calculé correctement
- [ ] Calibration calculée
- [ ] Duplication examen avec options fonctionne
- [ ] Tests Pest > 85%
- [ ] `./vendor/bin/pint --test` OK
- [ ] Tag `v2.0.0-alpha.1`

## 📝 COMMITS ATTENDUS

- `feat(cbm): add CbmScoringService with validation`
- `test(cbm): add 30+ tests for CbmScoringService`
- `feat(cbm): add CbmPreset CRUD API`
- `feat(cbm): add CbmMatrixEditor Vue component`
- `feat(cbm): add CbmCertaintyInput for students`
- `feat(passage): integrate CBM scoring in SubmitPassage`
- `feat(exam): add CloneExam action with options`
- `feat(exam): add clone exam UI modal`
- `test(exam): add 10+ tests for exam cloning`
- `chore: bump version to v2.0.0-alpha.1`

**Commence par Tâche 1 (CbmScoringService) et avance méthodiquement.**
```

---

## Prompt Phase P3 — Types questions + Multi-select

### 🎯 À copier-coller (5 jours)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P3 : TYPES QUESTIONS + MULTI-SELECT

Phases P0, P1, P2 terminées. Tu as le CBM fonctionnel et la duplication d'examen.

## 🎯 Objectif Phase P3 (5 jours)

1. 7 types de questions supportés end-to-end
2. QuestionTypeResolver service
3. UI création question adaptive (Vue 3)
4. UI réponse étudiant adaptive
5. 🆕 **Multi-select rapide** de questions
6. 🆕 Bulk actions sur questions

## 📋 TÂCHES

### Tâche 1 — QuestionTypeResolver (jour 1)

Service `app/Services/QuestionTypeResolver.php` :

```php
<?php
namespace App\Services;

use App\Models\Question;
use App\Enums\QuestionType;

class QuestionTypeResolver
{
    /**
     * Valide la structure d'une question selon son type.
     */
    public function validateQuestion(array $data): array
    {
        $errors = [];
        $type = QuestionType::tryFrom($data['type'] ?? '');
        
        if (!$type) {
            $errors[] = 'Invalid question type';
            return ['valid' => false, 'errors' => $errors];
        }
        
        $options = $data['options'] ?? [];
        $expectedCount = $this->getExpectedOptionsCount($type, $data['subtype_config'] ?? []);
        
        if ($type !== QuestionType::TrueFalse) {
            if (count($options) !== $expectedCount) {
                $errors[] = "Expected $expectedCount options, got " . count($options);
            }
        }
        
        $correctCount = collect($options)->where('is_correct', true)->count();
        
        if ($type->isMultiple()) {
            if ($correctCount < 1) {
                $errors[] = 'Multiple choice questions must have at least 1 correct option';
            }
        } else {
            if ($correctCount !== 1) {
                $errors[] = 'Single choice questions must have exactly 1 correct option';
            }
        }
        
        // Check unique IDs
        $ids = collect($options)->pluck('id');
        if ($ids->count() !== $ids->unique()->count()) {
            $errors[] = 'Option IDs must be unique';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
    
    public function getExpectedOptionsCount(QuestionType $type, array $config = []): int
    {
        return match($type) {
            QuestionType::TrueFalse => 2,
            QuestionType::McqSingle4, QuestionType::McqMultiple4 => 4,
            QuestionType::McqSingle5, QuestionType::McqMultiple5 => 5,
            QuestionType::McqSingleN, QuestionType::McqMultipleN => 
                $config['num_options'] ?? 4,
        };
    }
    
    /**
     * Vérifie si une réponse d'étudiant est correcte.
     */
    public function isCorrect(Question $question, array $answer): bool
    {
        $correctIds = collect($question->options)
            ->where('is_correct', true)
            ->pluck('id')
            ->sort()
            ->values()
            ->all();
        
        $selectedIds = collect($answer['selected_options'] ?? [])
            ->sort()
            ->values()
            ->all();
        
        return $correctIds === $selectedIds;
    }
    
    /**
     * Calcule le score selon le mode (pour questions multiples).
     */
    public function calculateScore(
        Question $question, 
        array $answer, 
        string $mode = 'all_or_nothing'
    ): float {
        if (!$question->type->isMultiple()) {
            return $this->isCorrect($question, $answer) ? 1.0 : 0.0;
        }
        
        $correctIds = collect($question->options)
            ->where('is_correct', true)
            ->pluck('id')
            ->all();
        
        $selectedIds = $answer['selected_options'] ?? [];
        
        $correctSelected = count(array_intersect($selectedIds, $correctIds));
        $wrongSelected = count(array_diff($selectedIds, $correctIds));
        $totalCorrect = count($correctIds);
        
        return match($mode) {
            'all_or_nothing' => 
                ($correctSelected === $totalCorrect && $wrongSelected === 0) ? 1.0 : 0.0,
            'proportional_strict' => 
                max(0, min(1, ($correctSelected - $wrongSelected) / $totalCorrect)),
            'proportional_normalized' => 
                max(0, ($correctSelected - $wrongSelected) / $totalCorrect),
            default => throw new \InvalidArgumentException("Unknown mode: $mode"),
        };
    }
}
```

Tests Pest complets (40+ tests).

### Tâche 2 — UI QuestionEditor adaptive (jour 2)

Composant `resources/js/Components/QuestionEditor.vue` avec :
- Sélecteur type (7 options avec icônes)
- Sub-config dynamique
- Options adaptives (radio pour single, checkbox pour multiple)
- Validation live
- Preview markdown + LaTeX (via marked.js + KaTeX)

### Tâche 3 — UI QuestionRenderer (jour 3)

Composant `resources/js/Components/QuestionRenderer.vue` pour afficher la question à l'étudiant avec support radio/checkbox selon le type.

### Tâche 4 — 🆕 Multi-select rapide (jour 4)

Composant principal `resources/js/Components/QuestionBulkSelector.vue` (voir le planning pour le code complet).

Action backend `app/Actions/Exam/AttachQuestionsBulk.php` :

```php
<?php
namespace App\Actions\Exam;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class AttachQuestionsBulk
{
    public function execute(Exam $exam, array $questionIds): array
    {
        return DB::transaction(function () use ($exam, $questionIds) {
            // Vérifier que les questions appartiennent au même workspace
            $validQuestions = Question::whereIn('id', $questionIds)
                ->where('workspace_id', $exam->workspace_id)
                ->whereNotIn('id', $exam->questions->pluck('id'))
                ->pluck('id')
                ->toArray();
            
            if (empty($validQuestions)) {
                return ['attached' => 0, 'skipped' => count($questionIds)];
            }
            
            $currentMaxOrder = $exam->questions()->max('exam_question.order') ?? 0;
            
            $attachData = [];
            foreach ($validQuestions as $i => $qId) {
                $attachData[$qId] = [
                    'order' => $currentMaxOrder + $i + 1,
                    'weight' => 1.0,
                ];
            }
            
            $exam->questions()->attach($attachData);
            
            activity()->causedBy(auth()->user())
                ->performedOn($exam)
                ->withProperties([
                    'attached_question_ids' => $validQuestions,
                    'count' => count($validQuestions),
                ])
                ->log('exam.questions_bulk_attached');
            
            return [
                'attached' => count($validQuestions),
                'skipped' => count($questionIds) - count($validQuestions),
            ];
        });
    }
}
```

Route + Controller :
```php
// routes/web.php
Route::post('/prof/exams/{exam}/questions/bulk-attach', 
    [ExamController::class, 'bulkAttachQuestions']
)->name('prof.exams.questions.bulk-attach');

// Controller
public function bulkAttachQuestions(
    Exam $exam, 
    BulkAttachQuestionsRequest $request, 
    AttachQuestionsBulk $action
) {
    $result = $action->execute($exam, $request->validated('question_ids'));
    
    return back()->with('success', 
        "{$result['attached']} question(s) ajoutée(s). {$result['skipped']} ignorée(s)."
    );
}
```

Page `Pages/Prof/Exams/AddQuestions.vue` intègre `<QuestionBulkSelector>`.

### Tâche 5 — Migration questions v1 → v2 dans Artisan (jour 5)

Compléter `MigrateFromV1` pour bien transformer questions v1 en 7 types v2.

### Tâche 6 — Tests + Polish

Tests complets + documentation + Tag `v2.0.0-alpha.2`.

## ✅ CRITÈRES D'ACCEPTATION

- [ ] 7 types supportés end-to-end
- [ ] QuestionTypeResolver > 85% coverage
- [ ] UI Editor adaptive fonctionne
- [ ] UI Renderer avec radio/checkbox
- [ ] Multi-select fonctionne avec 100+ questions
- [ ] Bulk actions (add, duplicate, export, delete)
- [ ] Ctrl+A, Esc shortcuts OK
- [ ] Filtres combinables (module/chapitre/difficulté/type/search)
- [ ] Shift-click pour range selection
- [ ] Tests Pest > 85%
- [ ] Tag `v2.0.0-alpha.2`

## 📝 COMMITS ATTENDUS

- `feat(question): add QuestionTypeResolver service`
- `test(question): add 40+ tests for QuestionTypeResolver`
- `feat(question): add adaptive QuestionEditor Vue component`
- `feat(question): add adaptive QuestionRenderer for students`
- `feat(exam): add QuestionBulkSelector Vue component`
- `feat(exam): add AttachQuestionsBulk action`
- `feat(exam): add bulk actions (duplicate, export, delete)`
- `feat(migration): complete v1 to v2 question migration`
- `test(question): add E2E tests for 7 types`
- `chore: bump version to v2.0.0-alpha.2`

**Commence par Tâche 1 (QuestionTypeResolver) et ses tests Pest.**
```

---

## Prompt Phase P4 — Scoring & Analytics

### 🎯 À copier-coller (5 jours)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P4 : SCORING & ANALYTICS

Phases P0 à P3 terminées. Tu as les 7 types questions + CBM + multi-select.

## 🎯 Objectif Phase P4 (5 jours)

1. 3 modes scoring multi-réponses intégrés
2. Analytics enrichis prof (calibration, distracteurs, radar)
3. Dashboard étudiant personnel
4. Exports CSV/Excel/PDF avec colonnes CBM
5. Laravel Scout + FTS5 recherche

## 📋 TÂCHES (résumé, détailler selon besoin)

### Jour 1 — ScoringService avec 3 modes
### Jour 2 — AnalyticsService + endpoints
### Jour 3 — Composants Vue analytics (Chart.js)
### Jour 4 — Dashboard étudiant
### Jour 5 — Exports Excel (Maatwebsite) + PDF (Browsershot)

## ✅ CRITÈRES D'ACCEPTATION

- [ ] 3 modes scoring fonctionnels
- [ ] Analytics calibration, distracteurs, radar
- [ ] Dashboard étudiant complet
- [ ] Exports CSV/Excel multi-feuilles
- [ ] Export PDF via Browsershot
- [ ] Recherche FTS5 fonctionnelle
- [ ] Tests > 85%
- [ ] Tag `v2.0.0-beta.1` 🎉

**Guide-moi phase par phase avec tous les détails.**
```

---

## Conclusion du Livrable C

Ces prompts P-1 à P4 couvrent les **premières 26 jours** du projet :
- P-1 (5j) : Formation Laravel + POC
- P0 (4j) : Bootstrap projet
- P1 (5j) : Migration données + Models
- P2 (5j) : CBM + Duplication
- P3 (5j) : Types Q + Multi-select
- P4 (5j) : Scoring + Analytics

Le **Livrable D** couvrira P5 à P8 (25 jours restants) avec :
- Documentation interactive
- Améliorations (Sécurité, Multi-tenant, SSO, LMS, i18n, PWA, Community)
- Tests + Admin Filament
- Migration + Déploiement

---

© 2026 Mohamed EL AFRIT — mohamed@elafrit.com  
Certio v2.0 Laravel — CC BY-NC-SA 4.0
