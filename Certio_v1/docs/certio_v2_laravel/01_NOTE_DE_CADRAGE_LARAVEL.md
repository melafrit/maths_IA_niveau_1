# 📋 Note de cadrage révisée — Certio v2.0 (Stack Laravel)

> **Plateforme multi-écoles d'évaluation avec Certainty-Based Marking**  
> **Stack technique révisée : Laravel 11 + Vue 3 + SQLite**

| Champ | Valeur |
|---|---|
| **Projet** | Certio v2.0 (refonte Laravel) |
| **Code projet** | CERTIO-V2-LARAVEL-2026 |
| **Version document** | 2.0 (révision suite choix stack) |
| **Date** | Avril 2026 |
| **Auteur** | Mohamed EL AFRIT |
| **Contact** | mohamed@elafrit.com |
| **Statut** | Cadrage révisé validé |
| **Licence** | CC BY-NC-SA 4.0 |
| **Remplace** | v1 de la note de cadrage (stack PHP natif) |

---

## 📢 Changements majeurs vs v1 de la note de cadrage

Cette version **remplace** la note de cadrage initiale (stack PHP natif). Principales évolutions :

| Aspect | v1 (PHP natif) | v2 (Laravel) |
|---|---|---|
| **Philosophie** | Zero-dependency | Framework battle-tested |
| **Backend** | PHP 8.3 natif | **Laravel 11** (PHP 8.3) |
| **Frontend** | React 18 CDN + Babel | **Vue 3 + Inertia + Tailwind** |
| **Persistance** | JSON files | **SQLite (WAL mode)** |
| **ORM** | Manuel (FileStorage) | **Eloquent** |
| **Build** | Aucun | **Vite** |
| **Tests** | Custom harness | **Pest** |
| **Déploiement** | Simple copie | VPS + **Deployer** |
| **Admin panel** | Custom | **Filament** |
| **Stratégie v1** | Migration directe | **Geler v1 + migration finale** |
| **Durée totale** | 36 jours | **50 jours** (incl. formation Laravel) |

**Vision produit et scope fonctionnel = 100% inchangés.** Seule la technique évolue.

---

## Sommaire

1. [Contexte et décision de refonte](#1-contexte-et-décision-de-refonte)
2. [Objectifs et KPIs (inchangés)](#2-objectifs-et-kpis-inchangés)
3. [Scope v2.0 (inchangé)](#3-scope-v20-inchangé)
4. [Architecture cible Laravel](#4-architecture-cible-laravel)
5. [Modèle de données SQL (migrations Laravel)](#5-modèle-de-données-sql-migrations-laravel)
6. [Stratégie de transition v1 → Laravel v2](#6-stratégie-de-transition-v1--laravel-v2)
7. [Risques techniques révisés](#7-risques-techniques-révisés)
8. [Budget et planning révisés](#8-budget-et-planning-révisés)
9. [Formation Laravel requise](#9-formation-laravel-requise)
10. [Annexes Laravel](#10-annexes-laravel)

---

## 1. Contexte et décision de refonte

### 1.1 Pourquoi Laravel plutôt que PHP natif ?

Après audit de la stack v1 (PHP natif + JSON files + React CDN), constats :

**Forces actuelles à préserver** :
- ✅ 389 tests qui passent — qualité code démontrée
- ✅ Architecture layered propre
- ✅ Simplicité conceptuelle
- ✅ Coût de maintenance faible

**Limites identifiées pour SaaS multi-écoles** :
- ⚠️ Concurrence JSON files (flock basique) → risque corruption à >50 écritures/sec
- ⚠️ Recherches lentes (grep) → inapplicable à 10 000+ questions
- ⚠️ Reporting complexe (cross-fichiers) → latence analytics dégradée
- ⚠️ Perception "pas pro" par prospects écoles
- ⚠️ Stack peu standardisée → difficile de recruter si besoin plus tard
- ⚠️ Écosystème limité (pas de queue, scheduler, notifications standardisées)

**Décision** : migrer vers Laravel + SQLite pour gagner :
- 🛡️ **Stabilité enterprise-grade** (Laravel = 10+ ans de maturité)
- 🚀 **Productivité x3** (ORM, migrations, validation, auth natifs)
- 📦 **Écosystème riche** (Socialite SSO, Fortify 2FA, Scout search, etc.)
- 🎯 **Crédibilité** (Laravel = standard pro, reconnu recruteurs/investisseurs)
- 🔧 **Maintenance future** (si un autre dev rejoint, Laravel est universel)

### 1.2 Pourquoi SQLite plutôt que PostgreSQL/MySQL ?

SQLite est un choix **stratégique et mature** pour Certio :

**Avantages techniques** :
- ✅ **Performances extraordinaires** : <1ms par requête
- ✅ **WAL mode** = concurrence excellente (500+ écritures/sec)
- ✅ **ACID complet** → pas de corruption
- ✅ **FTS5** (Full-Text Search) natif → recherche rapide
- ✅ **Zero maintenance** : 1 fichier, pas de serveur BDD à gérer

**Avantages ops** :
- ✅ **Backups = copier 1 fichier**
- ✅ **Migration sur nouveau VPS** = scp + c'est fini
- ✅ **Pas de config MySQL/Postgres** à maintenir
- ✅ **Moins cher** (pas de BDD managée externe)

**Utilisation en prod à grande échelle** :
- Fly.io utilise SQLite pour des millions d'apps
- Turso = SQLite distribuée edge
- Cloudflare D1 = SQLite serverless
- WhatsApp = SQLite local pour milliards d'utilisateurs

**Pour Certio** : SQLite supporte sans problème :
- 500+ écoles simultanées
- 10 000 passages/jour
- 1M+ questions en banque

**Migration future vers PostgreSQL** : 1 commande Laravel si besoin (`DB_CONNECTION=pgsql`). Le code Eloquent reste identique.

### 1.3 Pourquoi Vue 3 + Inertia plutôt que React ?

- **Inertia.js** = SPA **sans API REST séparée** → gain de productivité énorme
- **Vue 3 Composition API** = syntaxe encore plus simple que React hooks
- **Écosystème Laravel-natif** (Breeze/Jetstream supportent Vue)
- **`<script setup>`** ultra-concis (30% moins de code que React)
- **Communauté française** très active sur Vue

Inertia.js permet de naviguer comme une SPA mais avec la structure d'une app monolithique (contrôleurs Laravel classiques qui retournent des pages Vue). Le meilleur des deux mondes pour un dev solo.

---

## 2. Objectifs et KPIs (inchangés)

### 2.1 Objectifs stratégiques

Identiques à la v1 de la note :

| Objectif | Description | Horizon |
|---|---|---|
| **O1** | Universalisation multi-écoles | v2.0 |
| **O2** | Premier acteur francophone CBM natif | v2.0 |
| **O3** | Qualité pédagogique reconnue | v2.0-2.1 |
| **O4** | Effet de réseau (banque communautaire) | v2.0-2.1 |
| **O5** | Souveraineté numérique (OVH France RGPD) | v2.0 |
| **O6** | Monétisation SaaS viable | v2.1+ |

### 2.2 KPIs (inchangés)

Adoption, engagement, qualité technique, business — voir note v1 section 2.3.

**KPIs techniques spécifiques à Laravel** ajoutés :
- Score Laravel Pint (code style) : **100%**
- Score Larastan (analyse statique) : **niveau 8**
- Test coverage Pest : **≥ 85%**

---

## 3. Scope v2.0 (inchangé fonctionnellement)

**Aucun changement fonctionnel**. Toutes les features prévues en v1 cadrage restent identiques :

### In-scope

- 🏷️ **Rebranding** Certio (config centralisée)
- 🎲 **CBM 100% paramétrable** (matrice libre 2-10 niveaux)
- 🎯 **7 types de questions** (V/F, QCM N radio/checkbox)
- ⚖️ **3 modes scoring multi-réponses**
- 🔄 **Format unifié** avec CBM optionnel
- 📚 **Documentation interactive** avec RBAC
- 🔐 **Sécurité** (2FA TOTP, audit log, anti-triche)
- 🏫 **Multi-tenant Workspaces** + **SSO Google/Microsoft**
- 📤 **Intégrations LMS** (Moodle, SCORM, xAPI, LTI)
- 🌍 **Accessibilité** (WCAG AA) + **i18n** (FR/EN) + **PWA**
- 🌐 **Banque communautaire** avec modération

### Out-of-scope v2.0

Voir note v1 — reporté en v2.1+.

---

## 4. Architecture cible Laravel

### 4.1 Principes architecturaux

Philosophie Laravel adaptée :

1. **Convention over Configuration** — suivre les conventions Laravel
2. **Eloquent models** = business objects riches
3. **Single Responsibility Controllers** — controllers fins, services robustes
4. **FormRequests** pour validation — jamais de validation dans controllers
5. **Events + Listeners** pour découplage (ex: PassageSubmitted event)
6. **Queues** pour tâches asynchrones (emails, exports, backups)
7. **Policies** pour autorisation (Gate::authorize)
8. **Resources** pour sérialisation API
9. **Tests first** avec Pest (Feature + Unit)
10. **Artisan commands** pour scripts admin

### 4.2 Stack technique détaillée

#### Backend (PHP)

```
Laravel 11.x                 # Framework
├── PHP 8.3                  # Strict types
├── Eloquent ORM             # Base de données
├── Laravel Fortify          # Auth + 2FA TOTP
├── Laravel Sanctum          # API tokens
├── Laravel Socialite        # SSO OAuth
├── Laravel Scout            # Full-text search (avec SQLite FTS5)
├── Laravel Excel            # Maatwebsite/Laravel-Excel
├── Browsershot              # PDF rendering via Chrome headless
├── Intervention Image       # Manipulation images
├── Spatie Permissions       # RBAC (roles & permissions)
├── Spatie Backup            # Backups automatisés
├── Spatie ActivityLog       # Audit log
├── Spatie QueryBuilder      # Filtres API avancés
├── Spatie MediaLibrary      # Gestion assets
├── Laravel Pest             # Tests expressifs
└── Larastan                 # Analyse statique
```

#### Frontend (JS)

```
Vue 3.x                      # Framework UI
├── Inertia.js v2           # SPA sans API REST
├── Vite 5                   # Build tool ultra-rapide
├── Tailwind CSS 3           # Utility-first CSS
├── shadcn-vue               # Composants UI pro (Copy-paste)
├── VueUse                   # Composables utilitaires
├── Pinia                    # State management (si besoin)
├── Vue-i18n                 # Internationalisation
├── KaTeX                    # Rendu LaTeX
├── Chart.js (vue-chartjs)   # Graphiques
├── marked                   # Parse Markdown (docs)
├── DOMPurify                # Sanitize HTML
├── axios                    # HTTP client
└── Vitest                   # Tests unit frontend (si besoin)
```

#### Infrastructure

```
VPS Ubuntu 22.04 LTS (OVH)
├── Nginx 1.24               # Reverse proxy
├── PHP 8.3-FPM              # PHP processor
├── SQLite 3.45+             # Base de données (WAL)
├── Redis 7 (optionnel)      # Cache + queues
├── Supervisor               # Process manager (queues workers)
├── Certbot                  # SSL Let's Encrypt
├── UFW                      # Firewall
├── Fail2ban                 # Anti-bruteforce
├── Deployer                 # Déploiement PHP-native
└── GitHub Actions           # CI/CD
```

### 4.3 Structure projet Laravel

```
certio-laravel/
├── app/
│   ├── Actions/              # Single-action classes (Pattern Spatie)
│   │   ├── Cbm/
│   │   │   ├── CalculateCbmScore.php
│   │   │   └── ValidateCbmMatrix.php
│   │   ├── Exam/
│   │   │   ├── CreateExam.php
│   │   │   ├── PublishExam.php
│   │   │   └── CloneExam.php
│   │   └── Passage/
│   │       ├── StartPassage.php
│   │       ├── SavePassageAnswer.php
│   │       └── SubmitPassage.php
│   ├── Enums/                # Enums typés (PHP 8.1+)
│   │   ├── QuestionType.php
│   │   ├── ExamStatus.php
│   │   └── PassageStatus.php
│   ├── Events/               # Domain events
│   │   ├── ExamPublished.php
│   │   ├── PassageSubmitted.php
│   │   └── QuestionPublishedToCommunity.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   ├── Prof/
│   │   │   ├── Student/
│   │   │   └── Api/
│   │   ├── Middleware/
│   │   │   ├── EnsureWorkspaceScope.php
│   │   │   └── RoleRateLimit.php
│   │   ├── Requests/         # FormRequests validation
│   │   │   ├── StoreExamRequest.php
│   │   │   ├── UpdateCbmMatrixRequest.php
│   │   │   └── SubmitPassageRequest.php
│   │   └── Resources/        # API Resources (sérialisation)
│   ├── Listeners/            # Event handlers
│   │   ├── SendPassageSubmittedEmail.php
│   │   └── LogAuditEntry.php
│   ├── Models/               # Eloquent models
│   │   ├── User.php
│   │   ├── Workspace.php
│   │   ├── Exam.php
│   │   ├── Question.php
│   │   ├── Passage.php
│   │   ├── CbmPreset.php
│   │   ├── CommunityQuestion.php
│   │   └── AuditLog.php
│   ├── Policies/             # Authorization
│   │   ├── ExamPolicy.php
│   │   ├── QuestionPolicy.php
│   │   └── PassagePolicy.php
│   ├── Services/             # Services complexes
│   │   ├── CbmScoringService.php
│   │   ├── AntiCheatService.php
│   │   ├── LmsImportService.php
│   │   └── LmsExportService.php
│   └── Providers/            # Service providers
├── bootstrap/
├── config/                   # Fichiers config
│   ├── branding.php          # 🔑 Config centrale Certio
│   ├── app.php
│   ├── auth.php
│   └── ...
├── database/
│   ├── factories/            # Factories pour tests
│   ├── migrations/           # Schemas SQL
│   │   ├── 2026_05_01_create_workspaces_table.php
│   │   ├── 2026_05_01_create_exams_table.php
│   │   ├── 2026_05_01_create_questions_table.php
│   │   ├── 2026_05_01_create_passages_table.php
│   │   ├── 2026_05_01_create_cbm_presets_table.php
│   │   └── ...
│   ├── seeders/              # Données seed
│   │   ├── DatabaseSeeder.php
│   │   ├── DefaultWorkspaceSeeder.php
│   │   └── CommunityQuestionsSeeder.php
│   └── database.sqlite       # 💾 SQLite file
├── public/
│   ├── assets/
│   │   └── img/
│   │       ├── logo.svg
│   │       └── favicon.ico
│   ├── build/                # Vite build output
│   ├── manifest.json         # PWA
│   ├── service-worker.js     # PWA
│   └── index.php             # Entry point
├── resources/
│   ├── css/
│   │   └── app.css           # Tailwind entry
│   ├── js/
│   │   ├── app.js            # Inertia entry
│   │   ├── Components/       # Vue components réutilisables
│   │   │   ├── CbmMatrixEditor.vue
│   │   │   ├── CbmCertaintyInput.vue
│   │   │   ├── QuestionEditor.vue
│   │   │   ├── QuestionRenderer.vue
│   │   │   └── ...
│   │   ├── Layouts/
│   │   │   ├── AdminLayout.vue
│   │   │   ├── ProfLayout.vue
│   │   │   └── StudentLayout.vue
│   │   ├── Pages/            # Pages Inertia
│   │   │   ├── Admin/
│   │   │   ├── Prof/
│   │   │   └── Student/
│   │   └── Composables/      # Composables Vue
│   │       ├── useApi.js
│   │       └── useI18n.js
│   ├── lang/                 # i18n
│   │   ├── fr.json
│   │   └── en.json
│   ├── views/
│   │   ├── app.blade.php     # Layout principal Inertia
│   │   └── emails/           # Templates email Markdown
│   │       ├── passage-submitted.blade.php
│   │       └── welcome.blade.php
│   └── markdown/             # Doc interactive
│       ├── admin/
│       ├── prof/
│       ├── student/
│       └── shared/
├── routes/
│   ├── web.php               # Routes Inertia
│   ├── api.php               # Routes API publique
│   ├── channels.php          # Broadcasting
│   └── console.php           # Artisan commands
├── storage/                  # Logs, cache, uploads
│   ├── app/
│   │   ├── backups/
│   │   └── exports/
│   └── framework/
├── tests/
│   ├── Feature/              # Tests Feature (intégration)
│   │   ├── Cbm/
│   │   ├── Exam/
│   │   ├── Passage/
│   │   └── Auth/
│   ├── Unit/                 # Tests unitaires
│   │   ├── Services/
│   │   └── Actions/
│   └── Pest.php              # Config Pest
├── vendor/                   # Composer dependencies
├── node_modules/             # npm dependencies
├── .env                      # Variables environnement (gitignore)
├── .env.example              # Template
├── .gitignore
├── artisan                   # CLI Laravel
├── composer.json
├── package.json
├── phpunit.xml
├── tailwind.config.js
├── vite.config.js
└── README.md
```

### 4.4 Nouveaux composants Laravel vs managers v1

| Manager v1 (PHP natif) | Équivalent Laravel |
|---|---|
| `Auth.php` | Laravel Auth + Fortify (natif) |
| `Session.php` | Laravel Session (natif) |
| `Csrf.php` | Laravel CSRF middleware (natif) |
| `Logger.php` | Laravel Log (Monolog) |
| `Response.php` | Laravel Response helpers |
| `FileStorage.php` | Eloquent Models + Storage facade |
| `BanqueManager.php` | `QuestionService` + `Question` model |
| `ExamenManager.php` | `ExamService` + `Exam` model |
| `PassageManager.php` | `PassageService` + `Passage` model |
| `AnalyticsManager.php` | `AnalyticsService` + queries Eloquent |
| `BackupManager.php` | Spatie Backup package |
| `HealthChecker.php` | Spatie HealthChecks |
| `RateLimiter.php` | Laravel RateLimiter (natif) |
| `RoleRateLimiter.php` | Custom middleware + RateLimiter |
| `Mailer.php` | Laravel Mail (natif) |
| `EmailTemplate.php` | Blade + Markdown emails |
| 🆕 `CbmManager.php` | `CbmScoringService` + `CbmPreset` model |
| 🆕 `WorkspaceManager.php` | `WorkspaceService` + `Workspace` model |
| 🆕 `TotpManager.php` | Laravel Fortify TOTP (natif !) |
| 🆕 `SsoManager.php` | Laravel Socialite (natif !) |
| 🆕 `CommunityBankManager.php` | `CommunityService` + `CommunityQuestion` model |
| 🆕 `ImportManager.php` | `LmsImportService` |
| 🆕 `ExportManager.php` | `LmsExportService` |
| 🆕 `DocumentationManager.php` | `DocumentationService` + Markdown parser |
| 🆕 `I18nManager.php` | Laravel Localization (natif) |
| 🆕 `AntiCheatAnalyzer.php` | `AntiCheatService` |
| 🆕 `AuditLogger.php` | Spatie ActivityLog (natif) |
| 🆕 `BrandingManager.php` | `config('branding')` + helper |

**Résultat** : **60% du code manager est remplacé par des packages Laravel** testés et maintenus par des milliers de développeurs.

### 4.5 Packages Laravel tiers utilisés

| Package | Usage | Remplace |
|---|---|---|
| `laravel/fortify` | Auth + 2FA TOTP | TotpManager custom |
| `laravel/socialite` | SSO OAuth | SsoManager custom |
| `laravel/sanctum` | API tokens | Custom |
| `laravel/scout` | Full-text search | grep custom |
| `laravel/pint` | Code formatting | Manuel |
| `spatie/laravel-permission` | RBAC | Roles custom |
| `spatie/laravel-backup` | Backups automatisés | BackupManager custom |
| `spatie/laravel-activitylog` | Audit log | AuditLogger custom |
| `spatie/laravel-query-builder` | Filtres API | Custom |
| `spatie/browsershot` | PDF via Chrome | Impression navigateur |
| `maatwebsite/excel` | Export/Import Excel | SheetJS côté client |
| `livewire/flux` (optionnel) | UI components Filament | - |
| `filament/filament` | Admin panel | Custom admin |
| `pestphp/pest` | Tests | Custom harness |
| `larastan/larastan` | Static analysis | - |
| `barryvdh/laravel-debugbar` (dev) | Debug toolbar | - |

---

## 5. Modèle de données SQL (migrations Laravel)

### 5.1 Migration : workspaces

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique(); // WKS-XXXX-YYYY
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('plan', ['free', 'pro', 'enterprise'])->default('free');
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active');
            $table->json('branding')->nullable(); // logo, couleurs...
            $table->json('settings')->nullable(); // allow_community_publish, etc.
            $table->json('limits')->nullable(); // max_profs, max_students...
            $table->timestamp('subscription_end')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
```

### 5.2 Migration : exams

```php
Schema::create('exams', function (Blueprint $table) {
    $table->id();
    $table->string('uuid')->unique(); // EXM-XXXX-YYYY
    $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
    $table->foreignId('creator_id')->constrained('users');
    $table->string('title');
    $table->text('description')->nullable();
    $table->enum('status', ['draft', 'published', 'closed', 'archived'])->default('draft');
    $table->string('access_code')->unique()->nullable();
    $table->timestamp('date_ouverture')->nullable();
    $table->timestamp('date_cloture')->nullable();
    $table->integer('duration_minutes')->default(60);
    $table->integer('max_passages')->nullable();
    $table->boolean('shuffle_questions')->default(false);
    $table->boolean('shuffle_options')->default(false);
    $table->json('anti_cheat_config')->nullable();
    $table->boolean('cbm_enabled')->default(false);
    $table->json('cbm_matrix')->nullable();
    $table->enum('multi_answer_mode', [
        'all_or_nothing', 
        'proportional_strict', 
        'proportional_normalized'
    ])->default('all_or_nothing');
    $table->integer('total_points')->default(100);
    $table->integer('passing_score')->default(60);
    $table->string('locale', 5)->default('fr');
    $table->json('tags')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['workspace_id', 'status']);
    $table->index(['access_code']);
});
```

### 5.3 Migration : questions

```php
Schema::create('questions', function (Blueprint $table) {
    $table->id();
    $table->string('uuid')->unique(); // QST-XXXX
    $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
    $table->foreignId('creator_id')->constrained('users');
    $table->enum('visibility', ['private', 'workspace', 'community'])->default('private');
    $table->enum('type', [
        'true_false',
        'mcq_single_4', 'mcq_single_5', 'mcq_single_n',
        'mcq_multiple_4', 'mcq_multiple_5', 'mcq_multiple_n'
    ]);
    $table->json('subtype_config')->nullable(); // num_options, etc.
    $table->text('statement'); // Markdown + LaTeX
    $table->json('options'); // [{id: 'A', text: '...', is_correct: true}, ...]
    $table->text('explanation')->nullable();
    $table->enum('difficulty', ['easy', 'medium', 'hard', 'expert'])->default('medium');
    $table->json('tags')->nullable();
    $table->string('module')->nullable();
    $table->string('chapitre')->nullable();
    $table->string('theme')->nullable();
    $table->string('locale', 5)->default('fr');
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['workspace_id', 'visibility']);
    $table->index(['type']);
    $table->index(['module', 'chapitre']);
});

// Table pivot : questions <-> exams (many-to-many)
Schema::create('exam_question', function (Blueprint $table) {
    $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
    $table->foreignId('question_id')->constrained()->cascadeOnDelete();
    $table->float('weight')->default(1.0);
    $table->integer('order')->default(0);
    $table->primary(['exam_id', 'question_id']);
});
```

### 5.4 Migration : passages

```php
Schema::create('passages', function (Blueprint $table) {
    $table->id();
    $table->string('uuid')->unique(); // PSG-WXYZ-5678
    $table->string('token')->unique(); // UUID v4
    $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
    $table->foreignId('exam_id')->constrained();
    $table->string('student_nom');
    $table->string('student_prenom');
    $table->string('student_email');
    $table->json('student_custom_fields')->nullable();
    $table->enum('status', ['in_progress', 'submitted', 'expired', 'invalidated'])->default('in_progress');
    $table->enum('sub_status', ['answering', 'reviewing', 'auto_saving'])->nullable();
    $table->timestamp('started_at');
    $table->timestamp('submitted_at')->nullable();
    $table->timestamp('expires_at');
    $table->json('questions_order'); // ordre mélangé
    $table->json('answers')->nullable(); // {QST-001: {selected_options, cbm_level_id, ...}}
    $table->json('focus_events')->nullable();
    $table->json('anti_cheat_signals')->nullable();
    $table->float('score_raw')->nullable();
    $table->float('score_max')->nullable();
    $table->float('score_percentage')->nullable();
    $table->float('cbm_score')->nullable();
    $table->json('cbm_calibration')->nullable();
    $table->boolean('passed')->nullable();
    $table->string('signature_sha256')->nullable();
    $table->timestamps();
    
    $table->index(['workspace_id', 'status']);
    $table->index(['exam_id']);
    $table->index(['student_email']);
    $table->index(['token']);
});
```

### 5.5 Migration : cbm_presets

```php
Schema::create('cbm_presets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('workspace_id')->constrained();
    $table->string('name');
    $table->text('description')->nullable();
    $table->json('matrix'); // {levels: [...], scoring: [...]}
    $table->boolean('is_shared_in_workspace')->default(false);
    $table->integer('usage_count')->default(0);
    $table->timestamps();
    
    $table->index(['user_id']);
    $table->index(['workspace_id', 'is_shared_in_workspace']);
});
```

### 5.6 Migration : community_questions

```php
Schema::create('community_questions', function (Blueprint $table) {
    $table->id();
    $table->string('uuid')->unique(); // CBK-XXXX
    $table->foreignId('question_id')->constrained(); // Original
    $table->foreignId('original_workspace_id')->constrained('workspaces');
    $table->foreignId('original_author_id')->constrained('users');
    $table->json('question_snapshot'); // Copie complète au moment du publish
    $table->enum('license', ['CC-BY', 'CC-BY-SA', 'CC-BY-NC', 'CC-0']);
    $table->enum('review_status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users');
    $table->timestamp('reviewed_at')->nullable();
    $table->text('review_reason')->nullable();
    $table->integer('view_count')->default(0);
    $table->integer('fork_count')->default(0);
    $table->integer('usage_count')->default(0);
    $table->float('rating_average')->nullable();
    $table->integer('rating_count')->default(0);
    $table->integer('flag_count')->default(0);
    $table->timestamp('published_at');
    $table->timestamps();
    
    $table->index(['review_status']);
    $table->index(['license']);
    $table->fullText(['question_snapshot']); // Search FTS5
});

// Ratings
Schema::create('community_question_ratings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('community_question_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->integer('stars'); // 1-5
    $table->text('comment')->nullable();
    $table->timestamps();
    
    $table->unique(['community_question_id', 'user_id']);
});

// Flags
Schema::create('community_question_flags', function (Blueprint $table) {
    $table->id();
    $table->foreignId('community_question_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained();
    $table->string('reason');
    $table->text('details')->nullable();
    $table->enum('status', ['pending', 'reviewed', 'actioned'])->default('pending');
    $table->timestamps();
});
```

### 5.7 Migration : audit_logs (via Spatie ActivityLog)

Installation :
```bash
composer require spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
php artisan migrate
```

Laravel génère automatiquement la table `activity_log` avec :
- `log_name`, `description`, `subject_type`, `subject_id`
- `causer_type`, `causer_id` (qui a fait l'action)
- `properties` (JSON détails)
- `created_at`, `updated_at`

### 5.8 Modèles Eloquent (exemples)

#### Model Exam

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\ExamStatus;

class Exam extends Model
{
    use SoftDeletes, HasUuid, BelongsToWorkspace;
    
    protected $fillable = [
        'workspace_id', 'creator_id', 'title', 'description',
        'status', 'access_code', 'date_ouverture', 'date_cloture',
        'duration_minutes', 'max_passages', 'shuffle_questions',
        'shuffle_options', 'anti_cheat_config', 'cbm_enabled',
        'cbm_matrix', 'multi_answer_mode', 'total_points',
        'passing_score', 'locale', 'tags',
    ];
    
    protected $casts = [
        'status' => ExamStatus::class,
        'anti_cheat_config' => 'array',
        'cbm_matrix' => 'array',
        'tags' => 'array',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'cbm_enabled' => 'boolean',
        'date_ouverture' => 'datetime',
        'date_cloture' => 'datetime',
    ];
    
    // Relations
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
    
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
    
    // Méthodes métier
    public function canAcceptNewPassages(): bool
    {
        if ($this->status !== ExamStatus::Published) return false;
        if ($this->max_passages && $this->passages()->count() >= $this->max_passages) return false;
        return true;
    }
    
    public function hasCbmEnabled(): bool
    {
        return $this->cbm_enabled && !empty($this->cbm_matrix);
    }
}
```

#### Enum ExamStatus

```php
<?php
namespace App\Enums;

enum ExamStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';
    case Archived = 'archived';
    
    public function label(): string
    {
        return match($this) {
            self::Draft => 'Brouillon',
            self::Published => 'Publié',
            self::Closed => 'Clôturé',
            self::Archived => 'Archivé',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::Draft => 'gray',
            self::Published => 'green',
            self::Closed => 'yellow',
            self::Archived => 'red',
        };
    }
}
```

---

## 6. Stratégie de transition v1 → Laravel v2

### 6.1 Décision : Geler v1, développer Laravel v2 tranquillement

**Avantages de cette approche** :
- ✅ **Zéro pression** : v1 continue de fonctionner pour IPSSI actuellement
- ✅ **Apprentissage serein** : tu prends le temps de bien faire
- ✅ **Tests approfondis** : v2 bien testée avant switch
- ✅ **Rollback facile** : v1 reste dispo en cas de problème v2
- ✅ **Migration planifiée** : date de bascule choisie, pas subie

**Phases** :

1. **Gel v1** (maintenant)
   - Pas de nouvelles features sur v1
   - Corrections critiques seulement (bugs bloquants)
   - Continue de fonctionner pour IPSSI actuel
   
2. **Développement v2 Laravel** (3-4 mois)
   - Refonte complète en Laravel
   - Sur un nouveau repo ou nouvelle branche
   - Aucun impact sur v1 en prod
   
3. **Période de test v2** (1 semaine)
   - Tests pilotes avec 1-2 écoles volontaires
   - v2 sur domaine `staging.certio.app`
   - Feedback et corrections
   
4. **Migration des données** (1 jour)
   - Script d'export v1 (JSON) → import v2 (SQLite)
   - Testé préalablement sur copie prod
   
5. **Switch DNS** (1 heure)
   - `certio.app` pointe sur Laravel v2
   - v1 archivée sur `v1.certio.app` (read-only)
   
6. **Post-migration** (1 mois)
   - Support utilisateurs
   - Monitoring intensif
   - v1 désactivée définitivement après validation

### 6.2 Repo strategy

Option recommandée : **nouveau repo** `certio-laravel`

```
github.com/melafrit/maths_IA_niveau_1  → v1 (figée, archivée)
github.com/melafrit/certio            → v2 Laravel (nouveau)
```

Pourquoi un nouveau repo :
- ✅ Clean slate, pas de confusion avec v1
- ✅ Historique Git clair
- ✅ Possibilité d'open-source v1 si tu veux
- ✅ README et docs spécifiques v2

### 6.3 Script de migration données (Phase 8)

```php
// database/migrations/seed-from-v1.php
// ou artisan command

php artisan certio:migrate-from-v1 --source=/path/to/v1/data --dry-run
php artisan certio:migrate-from-v1 --source=/path/to/v1/data
```

Ce command :
1. Lit les JSON files de v1
2. Transforme au schéma v2
3. Insère dans SQLite via Eloquent (transactions)
4. Valide intégrité post-migration
5. Génère un rapport CSV

---

## 7. Risques techniques révisés

### 7.1 Nouveaux risques spécifiques Laravel

| # | Risque | Probabilité | Impact | Mitigation |
|---|---|:-:|:-:|---|
| **L1** | Courbe apprentissage Laravel | Moyen | Moyen | 5j formation + Laracasts Pro |
| **L2** | Trop de magie Laravel = bugs cachés | Faible | Moyen | Larastan niveau 8 + tests Pest |
| **L3** | SQLite limité (en théorie) | Faible | Élevé | Benchmarks + migration PostgreSQL facile |
| **L4** | Vite build casse en prod | Faible | Élevé | Test staging + CI build |
| **L5** | Écart entre dev et prod | Moyen | Moyen | Laravel Sail (Docker dev) + CI |
| **L6** | Spatie packages évoluent | Faible | Faible | Lock versions + tests avant upgrade |
| **L7** | Filament upgrade complexe | Faible | Moyen | Eviter Filament si trop couplant |

### 7.2 Risques éliminés vs v1

- ✅ ~~Concurrence JSON~~ → SQLite transactions ACID
- ✅ ~~Performance recherches~~ → FTS5 + index
- ✅ ~~Backups complexes~~ → 1 fichier
- ✅ ~~2FA/SSO custom~~ → Packages testés
- ✅ ~~Rate limiting custom~~ → Laravel natif
- ✅ ~~Audit log custom~~ → Spatie ActivityLog

### 7.3 Gestion du risque L1 (apprentissage)

Plan formation en Phase P-1 (5-8 jours) :

1. **Jour 1** : Installation Laravel + premier CRUD
2. **Jour 2** : Eloquent ORM + migrations + seeders
3. **Jour 3** : Auth + middleware + authorization
4. **Jour 4** : Inertia + Vue 3 + Tailwind
5. **Jour 5** : Tests Pest + debug Telescope

Ressources :
- [Laravel Bootcamp](https://bootcamp.laravel.com/) — gratuit, officiel
- [Laracasts](https://laracasts.com/) — 15€/mois, le meilleur
- [Spatie Blog](https://spatie.be/blog) — articles avancés
- [Laravel News](https://laravel-news.com/) — veille

---

## 8. Budget et planning révisés

### 8.1 Temps de développement révisé

| Phase | Stack v1 PHP | Stack v2 Laravel |
|---|:-:|:-:|
| P-1 Formation Laravel | - | **5j** 🆕 |
| P0 Setup/Bootstrap | 3j | **4j** |
| P1 Migration v1→v2 (ou CBM) | 5j | **5j** |
| P2 CBM Core | 5j | **5j** |
| P3 Types questions | 4j | **4j** |
| P4 Scoring/Analytics | 4j | **5j** |
| P5 Doc interactive | 5j | **4j** |
| P6 Améliorations | 9j | **10j** |
| P7 Tests + Admin Filament | 4j | **5j** |
| P8 Déploiement | 2j | **3j** |
| **TOTAL** | **36 jours** | **50 jours** |

**+14 jours** pour :
- Formation Laravel (5j)
- Migration données (2j)
- Admin Filament (1j)
- Setup VPS spécifique Laravel (1j)
- Réécriture tests en Pest (3j)
- Intégrations packages Spatie (2j)

### 8.2 Timeline

En supposant **2-3 jours actifs par semaine** (17j/mois) :

| Mois | Phases | Output |
|---|---|---|
| **Mai 2026** | P-1 Formation + P0 Bootstrap | Laravel app skeleton |
| **Juin 2026** | P1 Migration + P2 CBM | v2.0-alpha.1 (CBM core OK) |
| **Juillet 2026** | P3 Questions + P4 Analytics | v2.0-beta.1 |
| **Août 2026** | P5 Docs + P6 Améliorations | v2.0-rc.1 |
| **Septembre 2026** | P7 Tests + P8 Déploiement | **v2.0.0 en prod** 🎉 |

**Release visée : fin septembre 2026** (5 mois total).

### 8.3 Coûts externes révisés

| Poste | Mensuel | Annuel |
|---|:-:|:-:|
| OVH VPS Starter | 5€ | 60€ |
| OVH Email Pro | 2€ | 24€ |
| OVH Object Storage (backups) | 3€ | 36€ |
| Domaine certio.app | - | 25€ |
| **Laracasts Pro** (formation) | **15€** | **90€** (6 mois) |
| Claude Code / Cursor Pro | 20€ | 240€ |
| GitHub Pro | 0€ | 0€ |
| **TOTAL Phase projet** | ~45€ | **475€** |

**TOTAL projet (5 mois)** : **~225€** en frais externes.

---

## 9. Formation Laravel requise

### 9.1 Plan de formation express (Phase P-1, 5 jours)

**Jour 1 — Fondamentaux Laravel (4h)**
- Installation Laravel 11 + structure projet
- Routing, Controllers, Blade
- Premier CRUD simple
- ✅ Hands-on : Todo app Laravel

**Jour 2 — Eloquent & BDD (4h)**
- Migrations + Seeders + Factories
- Eloquent Models + Relations
- Scopes + Accessors + Mutators
- ✅ Hands-on : Blog avec articles + commentaires

**Jour 3 — Auth & Security (4h)**
- Laravel Breeze (install auth)
- Middleware custom
- Policies + Gates
- FormRequests validation
- ✅ Hands-on : Ajouter roles à todo app

**Jour 4 — Frontend Inertia + Vue 3 (5h)**
- Installation Inertia
- Premier Page Vue
- Shared data + Flash messages
- Forms avec validation
- Vite HMR
- ✅ Hands-on : Convertir blog en SPA Inertia

**Jour 5 — Tests & DevX (3h)**
- Pest : syntax et structure
- Feature tests + Unit tests
- Factories pour tests
- Laravel Telescope (debug)
- ✅ Hands-on : 10 tests Pest sur blog

### 9.2 Ressources formation

**Gratuit (priorité)** :
- [Laravel Bootcamp](https://bootcamp.laravel.com/) — OFFICIEL, 6-8h
- [Laravel Docs](https://laravel.com/docs) — référence absolue
- [Laracasts Free](https://laracasts.com/series/laravel-8-from-scratch) — série gratuite
- [Inertia.js Docs](https://inertiajs.com/) — clair

**Payant recommandé** :
- **Laracasts Pro** — 15€/mois, 6 mois conseillés = **90€**
  - "Laravel 11 From Scratch" (série)
  - "Build a Forum with Laravel" (projet complet)
  - "Inertia.js" (dédié)
  - "Pest Testing" (dédié)

**Articles/Blogs** :
- [Spatie.be/blog](https://spatie.be/blog) — best practices
- [Laravel News](https://laravel-news.com/) — veille
- [Christoph Rumpel](https://christoph-rumpel.com/) — tutos Inertia
- [Jeffrey Way Twitter](https://twitter.com/jeffrey_way) — créateur Laracasts

### 9.3 Projet de validation formation

Avant de démarrer Certio, construire un **mini-projet de validation** en 1 jour :

**"Mini-Certio"** :
- Auth (Login/Register avec Breeze)
- CRUD Exams basique
- CRUD Questions basique  
- Passage test simple (sans CBM)
- 3 tests Pest qui passent
- Déployé sur VPS test

Si tu réussis ça en 1 jour → **tu es prêt pour Certio v2 complet**.

---

## 10. Annexes Laravel

### A. Commandes Artisan utiles

```bash
# Installation
composer create-project laravel/laravel certio "^11.0"
cd certio

# Inertia + Vue Starter
composer require laravel/breeze --dev
php artisan breeze:install vue

# Packages Certio
composer require laravel/fortify laravel/socialite laravel/sanctum laravel/scout
composer require spatie/laravel-permission spatie/laravel-activitylog spatie/laravel-backup
composer require spatie/browsershot maatwebsite/excel
composer require filament/filament

# Dev dependencies
composer require --dev pestphp/pest pestphp/pest-plugin-laravel
composer require --dev larastan/larastan laravel/pint barryvdh/laravel-debugbar

# Config base
cp .env.example .env
php artisan key:generate

# DB (SQLite)
touch database/database.sqlite
# Modifier .env: DB_CONNECTION=sqlite, DB_DATABASE=absolute_path

# Migrations
php artisan migrate
php artisan db:seed

# Tests
php artisan test
./vendor/bin/pest

# Frontend
npm install
npm run dev        # Dev avec HMR
npm run build      # Prod build

# Admin panel
php artisan filament:install --panels

# Dev quotidien
php artisan serve                    # Start dev server
php artisan queue:work                # Workers queues
php artisan schedule:work             # Cron dev
php artisan tinker                    # REPL Laravel
php artisan make:controller ExamController
php artisan make:model Exam -m        # Model + migration
php artisan make:request StoreExamRequest
php artisan make:action CreateExam
php artisan make:test ExamTest --pest
```

### B. Structure `.env`

```env
APP_NAME=Certio
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database SQLite
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/certio/database/database.sqlite
DB_FOREIGN_KEYS=true

# Mail (OVH Email Pro)
MAIL_MAILER=smtp
MAIL_HOST=ssl0.ovh.net
MAIL_PORT=465
MAIL_USERNAME=noreply@elafrit.com
MAIL_PASSWORD=...
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=mohamed@elafrit.com
MAIL_FROM_NAME=Certio

# SSO
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://certio.app/auth/google/callback

MICROSOFT_CLIENT_ID=...
MICROSOFT_CLIENT_SECRET=...
MICROSOFT_REDIRECT_URI=https://certio.app/auth/microsoft/callback

# Backups
BACKUP_ARCHIVE_PASSWORD=...
FILESYSTEM_DISK=local
```

### C. Glossaire Laravel

- **Artisan** : CLI Laravel
- **Blade** : template engine Laravel
- **Eloquent** : ORM Laravel
- **Inertia** : bridge Laravel ↔ Vue/React sans API REST
- **Livewire** : alternative à Inertia (pur PHP)
- **Vite** : build tool frontend (successor Webpack)
- **Tinker** : REPL PHP pour Laravel (comme `irb` en Ruby)
- **Fortify** : package auth Laravel (login, 2FA, etc.)
- **Socialite** : package SSO OAuth
- **Sanctum** : package API tokens
- **Scout** : full-text search
- **Horizon** : dashboard queues Redis
- **Telescope** : debug toolbar + request inspector
- **Pulse** : monitoring prod Laravel
- **Pint** : code formatter (Laravel equivalent of Prettier)
- **Pest** : framework de tests (basé sur PHPUnit mais expressif)
- **Larastan** : analyse statique (basé sur PHPStan)
- **Filament** : admin panel builder

### D. Anti-patterns à éviter

- ❌ Mettre de la logique métier dans les controllers
- ❌ Requêtes SQL raw quand Eloquent marche
- ❌ N+1 queries (utiliser `with()` pour eager loading)
- ❌ Pas de tests
- ❌ Secrets dans `config/*.php` (utiliser `.env`)
- ❌ `dd()` laissés en production (utiliser Log)
- ❌ Désactiver CSRF middleware
- ❌ Oublier `->cache()` sur queries lentes
- ❌ Laisser Composer dev en prod (`--no-dev`)

---

## Conclusion

Cette refonte Laravel + SQLite est un choix **solide et pérenne** qui va transformer Certio en produit SaaS professionnel :

- **+14 jours d'investissement** (formation + migration) pour **+300% de productivité** sur les évolutions futures
- **Stabilité enterprise-grade** pour convaincre écoles et investisseurs
- **Écosystème extensible** (Laravel Forge, Nova, Vapor, Cloud...) si tu veux scaler
- **Recrutabilité** : si un jour tu recrutes un dev, Laravel = standard

**Prochaine étape** : le **planning détaillé 9 phases Laravel** (Livrable B).

---

© 2026 Mohamed EL AFRIT — mohamed@elafrit.com  
Certio v2.0 — Certainty-Based Assessment Platform  
Licence : CC BY-NC-SA 4.0
