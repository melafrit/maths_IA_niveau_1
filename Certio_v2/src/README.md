# 💻 Code source Laravel Certio v2.0

> Ce dossier contiendra le projet Laravel complet après Phase P0 (Bootstrap).

---

## 📅 Statut

🚧 **En attente de Phase P0 (Bootstrap Laravel)**

À ce jour, ce dossier est vide. Il sera rempli en Phase P0 (4 jours) avec :
- Projet Laravel 11 initialisé
- Packages installés (Fortify, Socialite, Spatie, Filament, etc.)
- Vue 3 + Inertia + Tailwind configurés
- CI/CD GitHub Actions
- Structure complète de l'application

---

## 🚀 Comment démarrer l'installation (Phase P0)

### Prérequis

```bash
# Vérifier les versions
php --version       # >= 8.3
composer --version  # >= 2.x
node --version      # >= 20.x
npm --version       # >= 10.x
git --version       # >= 2.40
```

### Installation commandes rapides

⚠️ **Ne pas exécuter maintenant** — suivre le prompt P0 pour l'installation complète guidée.

```bash
# Dans le dossier Certio_v2/src/
composer create-project laravel/laravel . "^11.0"
composer require laravel/breeze --dev
php artisan breeze:install vue --pest
composer require laravel/fortify laravel/socialite laravel/sanctum laravel/scout
composer require spatie/laravel-permission spatie/laravel-activitylog spatie/laravel-backup
composer require filament/filament maatwebsite/excel spatie/browsershot
composer require --dev pestphp/pest pestphp/pest-plugin-laravel larastan/larastan laravel/pint
npm install
npm run build

# Configuration SQLite
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
# Éditer .env : DB_CONNECTION=sqlite, DB_DATABASE=/path/absolu/database/database.sqlite
php artisan migrate
```

---

## 📂 Structure cible (après P0)

```
src/
├── app/
│   ├── Actions/
│   │   ├── Cbm/
│   │   ├── Exam/
│   │   ├── Passage/
│   │   └── Student/
│   ├── Enums/
│   │   ├── QuestionType.php
│   │   ├── ExamStatus.php
│   │   ├── CorrectionVisibility.php
│   │   └── Visibility.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   ├── Prof/
│   │   │   ├── Student/
│   │   │   └── Auth/
│   │   ├── Middleware/
│   │   │   ├── EnsureWorkspaceScope.php
│   │   │   └── RoleRateLimit.php
│   │   └── Requests/
│   ├── Mail/
│   │   └── CorrectionsAvailableMail.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Workspace.php
│   │   ├── Exam.php
│   │   ├── Question.php
│   │   ├── Passage.php
│   │   ├── CbmPreset.php
│   │   └── CommunityQuestion.php
│   ├── Policies/
│   │   ├── ExamPolicy.php
│   │   ├── QuestionPolicy.php
│   │   └── PassagePolicy.php
│   ├── Services/
│   │   ├── CbmScoringService.php
│   │   ├── ScoringService.php
│   │   ├── StudentDashboardService.php
│   │   ├── QuestionTypeResolver.php
│   │   ├── DocumentationService.php
│   │   └── CommunityBankService.php
│   └── Traits/
│       ├── HasUuid.php
│       └── BelongsToWorkspace.php
│
├── bootstrap/
│
├── config/
│   ├── branding.php              # 🔑 Config centrale Certio
│   ├── app.php
│   ├── auth.php
│   ├── fortify.php
│   └── services.php
│
├── database/
│   ├── factories/
│   ├── migrations/
│   │   ├── xxxx_create_workspaces_table.php
│   │   ├── xxxx_add_certio_fields_to_users_table.php
│   │   ├── xxxx_create_exams_table.php
│   │   ├── xxxx_create_questions_table.php
│   │   ├── xxxx_create_passages_table.php
│   │   ├── xxxx_create_cbm_presets_table.php
│   │   ├── xxxx_create_community_questions_table.php
│   │   ├── xxxx_add_corrections_visibility_to_exams_table.php
│   │   └── xxxx_add_reference_to_questions_table.php
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── DefaultWorkspaceSeeder.php
│   │   └── CommunityQuestionsSeeder.php
│   └── database.sqlite           # 💾 BDD SQLite
│
├── public/
│   ├── assets/
│   │   └── img/
│   │       └── logo.svg
│   ├── build/                    # Vite output
│   ├── manifest.json             # PWA
│   ├── service-worker.js         # PWA
│   └── index.php                 # Entry point
│
├── resources/
│   ├── css/
│   │   └── app.css               # Tailwind
│   ├── js/
│   │   ├── app.js                # Entry Inertia + Vue
│   │   ├── Components/
│   │   │   ├── CbmMatrixEditor.vue
│   │   │   ├── CbmCertaintyInput.vue
│   │   │   ├── QuestionEditor.vue
│   │   │   ├── QuestionBulkSelector.vue
│   │   │   └── Student/
│   │   │       ├── KpiCard.vue
│   │   │       ├── ProgressChart.vue
│   │   │       ├── ThemeRadarChart.vue
│   │   │       └── QuestionCorrection.vue
│   │   ├── Layouts/
│   │   │   ├── AdminLayout.vue
│   │   │   ├── ProfLayout.vue
│   │   │   └── StudentLayout.vue
│   │   └── Pages/
│   │       ├── Admin/
│   │       ├── Prof/
│   │       └── Student/
│   │           ├── Dashboard.vue
│   │           ├── History.vue
│   │           └── Passage/
│   │               ├── Show.vue
│   │               └── Correction.vue
│   ├── lang/
│   │   ├── fr.json
│   │   └── en.json
│   ├── markdown/                 # Documentation interactive
│   │   ├── admin/
│   │   ├── prof/
│   │   ├── student/
│   │   └── shared/
│   └── views/
│       ├── app.blade.php         # Layout Inertia
│       └── emails/
│           └── corrections-available.blade.php
│
├── routes/
│   ├── web.php                   # Routes Inertia
│   ├── api.php                   # API REST publique
│   ├── channels.php              # Broadcasting
│   └── console.php               # Artisan commands
│
├── storage/
│   ├── app/
│   │   ├── backups/
│   │   └── exports/
│   └── framework/
│
├── tests/
│   ├── Feature/
│   │   ├── Cbm/
│   │   ├── Exam/
│   │   ├── Passage/
│   │   ├── Student/
│   │   └── Auth/
│   ├── Unit/
│   │   ├── Services/
│   │   └── Actions/
│   └── Pest.php                  # Config Pest
│
├── vendor/                       # Composer deps (gitignored)
├── node_modules/                 # npm deps (gitignored)
│
├── .env                          # Variables env (gitignored)
├── .env.example                  # Template
├── .gitignore
├── artisan                       # CLI Laravel
├── composer.json
├── composer.lock
├── package.json
├── package-lock.json
├── phpstan.neon                  # Config Larastan
├── phpunit.xml
├── pint.json                     # Config Pint
├── tailwind.config.js
├── vite.config.js
└── README.md
```

---

## 🎯 Étapes suivantes

Pour démarrer le développement :

### 1️⃣ Lire la documentation

Commencer par :
- [`../README.md`](../README.md) — Vue d'ensemble Certio v2.0
- [`../docs/01_NOTE_DE_CADRAGE_LARAVEL.md`](../docs/01_NOTE_DE_CADRAGE_LARAVEL.md) — Vision + architecture
- [`../docs/02_PLANNING_LARAVEL_REVISE.md`](../docs/02_PLANNING_LARAVEL_REVISE.md) — Planning 9 phases

### 2️⃣ Se former à Laravel (Phase P-1 — 5 jours)

Ouvrir [`../docs/03_PROMPTS_VSCODE_LARAVEL_P-1_P4.md`](../docs/03_PROMPTS_VSCODE_LARAVEL_P-1_P4.md) et suivre le **Prompt Phase P-1**.

Cette phase consiste à :
- Apprendre Laravel 11, Eloquent, Inertia, Vue 3, Pest
- Construire un POC "Mini-Certio" (dans un dossier séparé, pas ici)
- Valider qu'on maîtrise la stack

### 3️⃣ Bootstrap du projet (Phase P0 — 4 jours)

Une fois la formation terminée, ouvrir le **Prompt Phase P0** dans [`../docs/03_PROMPTS_VSCODE_LARAVEL_P-1_P4.md`](../docs/03_PROMPTS_VSCODE_LARAVEL_P-1_P4.md).

Ce prompt guide pour :
- Créer le projet Laravel dans ce dossier `src/`
- Installer tous les packages
- Configurer branding, i18n, PWA
- Setup CI/CD GitHub Actions
- Premier deploy sur VPS staging

---

## 💡 Conseils

### Pour un dev solo avec assistant IA

1. **Ne sautez pas P-1** : la formation Laravel est essentielle pour être productif
2. **Suivez les prompts dans l'ordre** : chaque phase construit sur la précédente
3. **Testez au fur et à mesure** : Pest > 85% coverage à maintenir
4. **Commitez souvent** : Conventional Commits avec scopes Laravel
5. **Utilisez Laravel Telescope en dev** : pour debug et comprendre le framework

### Workflow Git recommandé

```bash
# Pour chaque phase
git checkout develop
git pull origin develop
git checkout -b feat/pX-nom-phase

# Pendant la phase
git add .
git commit -m "feat(scope): description"
git push -u origin feat/pX-nom-phase

# Fin de phase
# PR sur GitHub, merge dans develop
git checkout develop
git pull
git tag vX.Y.Z-alpha.X
git push --tags
```

---

## 🐛 Support

En cas de problème :

1. Vérifier les **critères d'acceptation** du prompt en cours
2. Relire la **note de cadrage** section concernée
3. Consulter la [**doc Laravel officielle**](https://laravel.com/docs/11.x)
4. Poser la question à **Claude Code / Cursor / ChatGPT** avec le contexte du prompt
5. Contacter : **mohamed@elafrit.com**

---

## 📜 Licence

© 2026 Mohamed EL AFRIT — [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)
