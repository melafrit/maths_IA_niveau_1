# 🏛️ Architecture — IPSSI Examens

> Documentation technique complète du système : composants, flux, sécurité, API.

**Version** : 0.8.0 · **Tests** : 389/389 ✅ · **License** : CC BY-NC-SA 4.0

---

## 📖 Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Architecture globale](#2-architecture-globale)
3. [Stack technologique](#3-stack-technologique)
4. [Structure des fichiers](#4-structure-des-fichiers)
5. [Modèle de données](#5-modèle-de-données)
6. [API REST](#6-api-rest)
7. [Sécurité](#7-sécurité)
8. [Flux de données](#8-flux-de-données)
9. [Composants frontend](#9-composants-frontend)
10. [Tests et qualité](#10-tests-et-qualité)

---

## 1. Vue d'ensemble

### Objectif

Plateforme web d'**examens en ligne** pour IPSSI, conçue pour :
- Créer des examens à partir d'une **banque de questions** (320 QCM)
- Permettre aux étudiants de passer les examens via un **code d'accès**
- Calculer automatiquement les **scores** et produire des **corrections** (PDF + email)
- Fournir au prof des **analytics approfondies** (scores, distracteurs, historique)
- Détecter les tentatives de **fraude** (copy/paste, devtools, perte focus)

### Public cible

| Rôle | Capacités |
|---|---|
| **Administrateur** | Gestion utilisateurs, dashboard système, backups, monitoring |
| **Enseignant (prof)** | Création d'examens, analytics, correction, banque de questions |
| **Étudiant** | Passage d'examens avec code, consultation correction |

### Caractéristiques techniques

- **Zéro base de données SQL** : tout en fichiers JSON (portable, versionnable)
- **Zéro dépendance Composer** : PHP natif uniquement (bcrypt, hash, json, etc.)
- **Frontend sans build** : React via CDN UMD + Babel standalone (transpile navigateur)
- **Isolation des données** : chaque rôle ne voit que ses propres données
- **Compatible OVH mutualisé** : pas besoin de serveur dédié

---

## 2. Architecture globale

### Diagramme haut niveau

```
┌─────────────────────────────────────────────────────────────────┐
│                         NAVIGATEUR                              │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────────┐    │
│  │  Admin      │  │  Prof        │  │  Étudiant           │    │
│  │  Dashboard  │  │  Dashboard   │  │  Passage + correction│   │
│  └──────┬──────┘  └──────┬───────┘  └──────────┬──────────┘    │
└─────────┼─────────────────┼──────────────────────┼──────────────┘
          │                 │                      │
          │  HTTPS (GET/POST/PUT/DELETE)           │
          │  Cookies session + CSRF token          │
          │                 │                      │
┌─────────┴─────────────────┴──────────────────────┴──────────────┐
│                    SERVEUR PHP (8.2+)                           │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │            backend/public/index.php (Router)            │   │
│  │  - /health (+detailed=1)      ← monitoring             │   │
│  │  - /api/{endpoint}            ← dispatch               │   │
│  │                                                          │   │
│  │  Middleware automatique :                                │   │
│  │  → Rate limiting par rôle (RoleRateLimiter)             │   │
│  │  → CSRF check sur POST/PUT/DELETE                       │   │
│  └─────────────────────────────────────────────────────────┘   │
│                              │                                   │
│       ┌──────────────────────┼──────────────────────┐           │
│       ▼                      ▼                      ▼           │
│  ┌─────────┐           ┌──────────┐          ┌───────────┐     │
│  │   API   │           │   LIB    │          │   VIEWS   │     │
│  │ (7 mod) │──────────▶│ Managers │          │  (JSON)   │     │
│  │         │           │          │          │           │     │
│  │ examens │           │ Examen   │          │           │     │
│  │ passages│           │ Passage  │          │ Response  │     │
│  │ analytc │           │ Analytics│          │           │     │
│  │ backups │           │ Backup   │          │           │     │
│  │ banque  │           │ Banque   │          │           │     │
│  │ auth    │           │ Auth     │          │           │     │
│  │ correctn│           │ Mailer   │          │           │     │
│  │         │           │ Health   │          │           │     │
│  └─────────┘           └────┬─────┘          └───────────┘     │
│                             │                                   │
│                             ▼                                   │
│                     ┌───────────────┐                          │
│                     │  FileStorage  │                          │
│                     └───────┬───────┘                          │
│                             │                                   │
└─────────────────────────────┼───────────────────────────────────┘
                              │
                              ▼
            ┌─────────────────────────────────────┐
            │        DATA/ (Filesystem)            │
            │  ├── examens/                        │
            │  ├── passages/                       │
            │  ├── comptes/                        │
            │  ├── banque/                         │
            │  ├── sessions/                       │
            │  ├── backups/     ← backup.sh        │
            │  ├── logs/                           │
            │  └── _ratelimit/  ← RateLimiter      │
            └─────────────────────────────────────┘
                              │
                              ▼
            ┌─────────────────────────────────────┐
            │         Cron (optionnel)             │
            │  03:00 → backup.sh --keep=14         │
            └─────────────────────────────────────┘
```

### Architecture en couches

```
┌──────────────────────────────────────────┐
│  Couche PRÉSENTATION (Frontend React)    │  ← admin/prof/etudiant *.html + *.jsx
├──────────────────────────────────────────┤
│  Couche ROUTING (index.php)              │  ← dispatch, middleware
├──────────────────────────────────────────┤
│  Couche API (backend/api/*.php)          │  ← validation, auth, REST
├──────────────────────────────────────────┤
│  Couche MÉTIER (backend/lib/*.php)       │  ← Managers, business logic
├──────────────────────────────────────────┤
│  Couche PERSISTANCE (FileStorage)        │  ← JSON files
└──────────────────────────────────────────┘
```

---

## 3. Stack technologique

### Backend (PHP 8.2+)

| Composant | Version | Usage |
|---|---|---|
| PHP | 8.2, 8.3 | Runtime principal |
| PDO | — | Non utilisé (pas de SQL) |
| Bcrypt | natif | Hashage mots de passe (cost 12) |
| JSON | natif | Sérialisation données |
| OpenSSL | natif | Signatures HMAC SHA-256 |
| Session PHP | natif | Sessions utilisateur (file-based) |

**Aucune dépendance Composer** — code 100% PHP natif.

### Frontend

| Composant | Version | Usage |
|---|---|---|
| React | 18 (UMD) | UI library |
| ReactDOM | 18 | Rendu |
| Babel Standalone | 7 | Transpile JSX dans le navigateur |
| Recharts | 2.12.7 | Graphiques analytics |
| KaTeX | 0.16.9 | Rendu mathématique |
| Pyodide | 0.24.1 | Exécution Python navigateur (mini-projet) |
| SheetJS | 0.18.5 | Export Excel |
| prop-types | 15.8.1 | Recharts dependency |

**Aucun bundler** — tout via CDN (jsdelivr/unpkg).

### Scripts système

| Outil | Usage |
|---|---|
| Bash | Backups, restore, cron install |
| tar/gzip | Archives de données |
| sha256sum | Vérification intégrité |

### CI/CD

| Outil | Usage |
|---|---|
| GitHub Actions | Pipeline tests + lint automatiques |

---

## 4. Structure des fichiers

### Arborescence racine

```
maths_IA_niveau_1/
├── .github/
│   └── workflows/
│       ├── tests.yml            ← CI harness PHP
│       └── lint.yml             ← Lint rapide
├── examens/                     ← Projet principal
│   ├── backend/                 ← Code serveur
│   ├── frontend/                ← Code client
│   ├── data/                    ← Données (non-versionné en prod)
│   ├── scripts/                 ← Scripts bash
│   └── docs/                    ← Documentation
```

### Dossier `backend/`

```
backend/
├── bootstrap.php                ← Autoload + config
├── public/
│   └── index.php                ← Router + middleware
├── lib/                         ← Managers (business logic)
│   ├── Auth.php                 ← Authentification
│   ├── Session.php              ← Sessions
│   ├── Csrf.php                 ← Tokens CSRF
│   ├── Logger.php               ← Logging
│   ├── Response.php             ← JSON responses
│   ├── FileStorage.php          ← R/W JSON files
│   ├── BanqueManager.php        ← Questions (320)
│   ├── ExamenManager.php        ← CRUD examens
│   ├── PassageManager.php       ← Passages étudiants
│   ├── AnalyticsManager.php     ← Statistiques
│   ├── BackupManager.php        ← API backups
│   ├── HealthChecker.php        ← Monitoring
│   ├── RateLimiter.php          ← Bruteforce login
│   ├── RoleRateLimiter.php      ← Rate limit API par rôle
│   ├── EmailTemplate.php        ← Templates emails
│   └── Mailer.php               ← Envoi emails
├── api/                         ← Endpoints REST
│   ├── auth.php                 ← Login/logout
│   ├── banque.php               ← Gestion questions
│   ├── comptes.php              ← Gestion users
│   ├── examens.php              ← CRUD examens
│   ├── passages.php             ← Passages
│   ├── corrections.php          ← Corrections + PDF
│   ├── analytics.php            ← Stats
│   └── backups.php              ← Gestion backups
├── templates/emails/            ← Templates HTML emails
└── tests/                       ← Tests unitaires + sécurité + E2E
    ├── run_all.php              ← Harness unifié
    ├── test_security_*.php      ← 4 suites security
    └── test_e2e_workflow.php    ← Workflows E2E
```

### Dossier `frontend/`

```
frontend/
├── admin/                       ← Pages HTML admin
│   ├── banque.html
│   ├── examens.html
│   ├── analytics.html
│   └── monitoring.html
├── etudiant/                    ← Pages HTML étudiant
│   ├── passage.html
│   └── correction.html
├── assets/                      ← JS/JSX/CSS
│   ├── main.css                 ← Design system
│   ├── components-base.js       ← Button, Input, Box, Card
│   ├── components-advanced.js   ← Modal, Table, Toast
│   ├── hooks.js                 ← useApi, useAuth, useDebounce
│   ├── analytics.jsx            ← App analytics
│   ├── analytics-*.jsx          ← Vues analytics
│   ├── monitoring.jsx           ← Dashboard système
│   └── ... (50+ composants)
└── public/                      ← Images, favicons, static
```

### Dossier `data/`

```
data/
├── examens/
│   └── EXM-ABCD-1234.json      ← Un fichier par examen
├── passages/
│   └── PSG-WXYZ-5678.json      ← Un fichier par passage étudiant
├── comptes/
│   └── USR-xxxx.json           ← Un fichier par user
├── banque/
│   └── vecteurs/
│       ├── vec-faci-01.json    ← Structure module/chapitre/question
│       └── ...
├── sessions/                    ← Sessions PHP (auto)
├── backups/
│   ├── backup_2026-04-22_030000.tar.gz
│   └── backup_2026-04-22_030000.tar.gz.sha256
├── logs/
│   ├── app.log                 ← Logs généraux
│   ├── auth.log                ← Login/logout
│   └── backups.log             ← Backups
├── _ratelimit/                 ← Buckets rate limit
└── config/                     ← Config runtime (optionnel)
```

---

## 5. Modèle de données

### Schéma : Examen

```json
{
  "id": "EXM-ABCD-1234",
  "titre": "Contrôle Maths IA",
  "description": "Test de connaissances...",
  "status": "draft | published | closed | archived",
  "created_by": "PROF-xxxx",
  "created_at": "2026-04-22T10:00:00+02:00",
  "updated_at": "2026-04-22T10:05:00+02:00",
  "questions": ["vec-faci-01", "vec-faci-02", "mat-moye-03"],
  "duree_sec": 3600,
  "date_ouverture": "2026-04-23T08:00:00+02:00",
  "date_cloture": "2026-04-30T20:00:00+02:00",
  "max_passages": 1,
  "shuffle_questions": true,
  "shuffle_options": true,
  "show_correction_after": true,
  "correction_delay_min": 0,
  "access_code": "ABC23K-9P"
}
```

### Schéma : Passage

```json
{
  "id": "PSG-WXYZ-5678",
  "examen_id": "EXM-ABCD-1234",
  "token": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",  // UUID v4
  "access_code_used": "ABC23K-9P",
  "student_info": {
    "nom": "Dupont",
    "prenom": "Alice",
    "email": "alice@test.fr"
  },
  "question_order": ["vec-faci-02", "vec-faci-01", "mat-moye-03"],
  "option_shuffle_maps": {
    "vec-faci-01": [2, 0, 3, 1]  // Permutation des options A/B/C/D
  },
  "answers": {
    "vec-faci-01": 2,   // Index de la réponse choisie
    "vec-faci-02": 0
  },
  "start_time": "2026-04-23T09:00:00+02:00",
  "end_time": "2026-04-23T09:45:00+02:00",
  "duration_sec": 2700,
  "status": "in_progress | submitted | expired | invalidated",
  "score_brut": 8,
  "score_max": 10,
  "score_pct": 80.0,
  "signature_sha256": "...",   // HMAC pour intégrité
  "focus_events": [
    {"type": "copy", "timestamp": "..."},
    {"type": "blur", "duration_ms": 3000}
  ],
  "score_details": {
    "vec-faci-01": {"correct": true, "answer": 2, "expected": 2},
    ...
  },
  "created_at": "...",
  "updated_at": "..."
}
```

### Schéma : Compte (User)

```json
{
  "id": "USR-admin-001",
  "email": "admin@ipssi.fr",
  "nom": "Admin",
  "prenom": "Principal",
  "role": "admin | enseignant",
  "password_hash": "$2y$12$...",   // Bcrypt
  "created_at": "...",
  "last_login": "...",
  "active": true
}
```

### Schéma : Question banque

```json
{
  "id": "vec-faci-01",
  "module": "vecteurs",
  "chapitre": "operations",
  "theme": "somme",
  "difficulte": "facile | moyen | difficile | tres_difficile",
  "type": "qcm",
  "enonce": "Quelle est la somme de $\\vec{u}=(1,2)$ et $\\vec{v}=(3,4)$ ?",
  "options": [
    {"text": "(2, 6)", "correct": false},
    {"text": "(4, 6)", "correct": true},
    {"text": "(4, 8)", "correct": false},
    {"text": "(3, 8)", "correct": false}
  ],
  "explication": "On additionne composante par composante...",
  "tags": ["base", "calcul"]
}
```

---

## 6. API REST

Toutes les routes suivent le format : `/api/{module}/{action}`

### Format de réponse

**Succès** :
```json
{
  "ok": true,
  "data": { /* contenu */ }
}
```

**Erreur** :
```json
{
  "ok": false,
  "error": {
    "message": "Message explicite",
    "code": "error_code"
  }
}
```

### Modules API

| Module | Endpoints principaux | Auth requise |
|---|---|---|
| `/api/health` | GET | Non |
| `/api/auth` | POST login, logout, me | Variable |
| `/api/banque` | CRUD questions | Admin/Prof |
| `/api/examens` | CRUD examens + publish/close | Prof |
| `/api/passages` | start, saveAnswer, submit | Public (par token) |
| `/api/corrections` | get par token + PDF | Public (par token) |
| `/api/analytics` | Dashboard + stats | Prof/Admin |
| `/api/backups` | Gestion backups | Admin |
| `/api/comptes` | CRUD users | Admin |

### Headers standards

**Requête (écriture)** :
```
Content-Type: application/json
X-CSRF-Token: <token>
Cookie: PHPSESSID=...
```

**Réponse (rate limit)** :
```
X-RateLimit-Limit: 500
X-RateLimit-Remaining: 487
X-RateLimit-Reset: 1714751234
```

**Réponse (429)** :
```
HTTP/1.1 429 Too Many Requests
Retry-After: 42
```

### Exemple d'endpoint : POST /api/examens

**Requête** :
```http
POST /api/examens HTTP/1.1
Content-Type: application/json
X-CSRF-Token: abc123...
Cookie: PHPSESSID=xyz

{
  "titre": "Test",
  "questions": ["vec-faci-01", "vec-faci-02"],
  "duree_sec": 1800,
  "date_ouverture": "2026-04-23T08:00:00+02:00",
  "date_cloture": "2026-04-30T20:00:00+02:00",
  "max_passages": 1
}
```

**Réponse** :
```http
HTTP/1.1 200 OK
Content-Type: application/json
X-RateLimit-Limit: 500
X-RateLimit-Remaining: 499

{
  "ok": true,
  "data": {
    "id": "EXM-ABCD-1234",
    "status": "draft",
    "access_code": "ABC23K-9P",
    ...
  }
}
```

---

## 7. Sécurité

### Couches de sécurité

```
┌─────────────────────────────────────────┐
│ 1. Rate limiting par rôle               │  ← Middleware index.php
├─────────────────────────────────────────┤
│ 2. Auth (session + bcrypt)              │  ← Auth.php
├─────────────────────────────────────────┤
│ 3. CSRF tokens (POST/PUT/DELETE)        │  ← Csrf.php
├─────────────────────────────────────────┤
│ 4. Validation stricte IDs (regex)       │  ← Managers
├─────────────────────────────────────────┤
│ 5. Signatures HMAC (passages)           │  ← PassageManager
├─────────────────────────────────────────┤
│ 6. Échappement HTML output              │  ← EmailTemplate::e()
└─────────────────────────────────────────┘
```

### Détails sécurité

#### Authentification
- **Bcrypt cost 12** pour tous les mots de passe
- Salt aléatoire (généré automatiquement par bcrypt)
- `password_verify()` timing-safe
- Sessions PHP file-based (`data/sessions/`)

#### CSRF
- Token généré à la session (base64url 32 chars)
- Validation timing-safe via `hash_equals()`
- Requis sur tous POST/PUT/DELETE
- Regénération possible (`Csrf::regenerate()`)

#### Rate limiting
| Rôle | Limite |
|---|:-:|
| admin | Illimité |
| enseignant | 500/min |
| étudiant | 60/min |
| anonyme | 30/min |

Fenêtre glissante 60s, stockage fichier.

#### Signatures HMAC
Chaque passage soumis est signé avec SHA-256 + salt secret pour détecter toute modification ultérieure.

```php
$signature = hash_hmac('sha256', 
    json_encode($passage_data), 
    $SECRET_SALT
);
```

#### Path traversal
Tous les IDs sont validés par regex avant lecture fichier :
- `EXM-[A-Z0-9]{4}-[A-Z0-9]{4}` (examens)
- `PSG-[A-Z0-9]{4}-[A-Z0-9]{4}` (passages)
- `vec-faci-01`, etc. (banque)

Un ID invalide → `InvalidArgumentException` immédiatement.

### Tests sécurité

**85 tests dédiés** couvrent :
- CSRF (18) : generation, validation, attaques
- Auth (24) : bcrypt, sessions, rôles
- XSS (20) : payloads classiques, templates emails
- Injection (23) : path traversal, null bytes, shell

---

## 8. Flux de données

### Flux 1 — Création d'un examen

```
Prof (navigateur)
    │
    ├── 1. GET /admin/examens.html
    │   └── React fetch /api/banque pour liste questions
    │
    ├── 2. Click "Nouvel examen"
    │   └── Formulaire avec pick questions
    │
    ├── 3. POST /api/examens
    │   │
    │   ├── 3a. Middleware rate limit (prof = 500/min)
    │   ├── 3b. CSRF check
    │   ├── 3c. Auth requis (role prof/admin)
    │   │
    │   └── ExamenManager::create()
    │       ├── Validation : titre, questions existent
    │       ├── Génère EXM-XXXX-YYYY + access_code
    │       ├── Écrit data/examens/EXM-xxx.json
    │       └── Log dans data/logs/app.log
    │
    └── 4. Affiche examen créé (status: draft)

Prof click "Publier"
    │
    └── PATCH /api/examens/{id}/publish
        └── ExamenManager::publish()
            ├── status: draft → published
            └── Prêt pour passages étudiants
```

### Flux 2 — Passage d'un étudiant

```
Étudiant (navigateur)
    │
    ├── 1. Saisit code d'accès "ABC23K-9P"
    │   └── POST /api/passages/access
    │       └── ExamenManager::getByAccessCode()
    │
    ├── 2. Infos étudiant (nom, prenom, email)
    │   └── POST /api/passages/start
    │       │
    │       ├── Middleware rate limit (anonyme = 30/min)
    │       │
    │       └── PassageManager::start()
    │           ├── Vérifie max_passages (anti-doublon)
    │           ├── Génère PSG-xxx + UUID token
    │           ├── Shuffle questions + options (si configuré)
    │           ├── Écrit data/passages/PSG-xxx.json
    │           └── Retourne token (valide 1h)
    │
    ├── 3. Répond aux questions (auto-save)
    │   └── POST /api/passages/answer (pour chaque question)
    │       └── PassageManager::saveAnswer(token, qId, index)
    │
    ├── 4. Event focus (copy, blur, etc.)
    │   └── POST /api/passages/focus-event
    │       └── PassageManager::logFocusEvent()
    │
    └── 5. Click "Soumettre"
        │
        └── POST /api/passages/submit
            └── PassageManager::submit()
                ├── Calcul score (compare answers vs expected)
                ├── Calcul duration_sec
                ├── Génère signature_sha256
                ├── status: in_progress → submitted
                ├── Envoie email auto (EmailTemplate::etudiant_submission)
                └── Retourne correction_url
```

### Flux 3 — Consultation analytics

```
Prof (navigateur)
    │
    ├── 1. GET /admin/analytics.html
    │   └── React fetch /api/analytics/prof/overview
    │       └── AnalyticsManager::getProfOverview(profId)
    │
    ├── 2. Click sur un examen
    │   ├── /api/analytics/examen/{id}/overview
    │   │   └── KPIs : total, avg_score, passages actifs
    │   │
    │   ├── /api/analytics/examen/{id}/scores
    │   │   └── Distribution histogramme 10 buckets
    │   │
    │   ├── /api/analytics/examen/{id}/questions
    │   │   └── Stats par Q + option_analysis
    │   │
    │   └── /api/analytics/examen/{id}/passages
    │       └── Liste enrichie avec filtres/tri/pagination
    │
    └── 3. Export
        └── Client-side : buildXRows() → CSV / Excel / PDF
```

---

## 9. Composants frontend

### Design system

Basé sur des **tokens CSS** (`main.css`) :
- Couleurs : `--color-bg`, `--color-text`, `--color-primary`, etc.
- Espacements : `--space-1` (4px) → `--space-6` (64px)
- Radius : `--radius-sm`, `--radius-md`, `--radius-lg`
- Fonts : `--font-mono`, `--font-sans`

### Composants React réutilisables

```
components-base.js :
  - Button (variants: primary, ghost, danger)
  - Input, Textarea
  - Box (type: info, success, warning, error)
  - Card
  - Modal
  - Spinner
  - Tabs

components-advanced.js :
  - DataTable (avec tri, recherche, pagination)
  - ToastProvider + useToast
  - ConfirmDialog
  - DatePicker
  - FileUpload

hooks.js :
  - useApi (fetch avec CSRF auto)
  - useAuth (login state)
  - useDebounce
```

### Pages principales

| Page | Composants clés |
|---|---|
| `admin/banque.html` | Tree questions + éditeur KaTeX |
| `admin/examens.html` | Liste + wizard création |
| `admin/analytics.html` | Dashboard + 5 graphs Recharts |
| `admin/monitoring.html` | Health dashboard temps réel |
| `etudiant/passage.html` | UI examen + timer + anti-triche |
| `etudiant/correction.html` | Score + détail Q par Q |

---

## 10. Tests et qualité

### Vue d'ensemble

```
┌─────────────────────────────────────────┐
│        TESTS BACKEND (389/389)          │
├─────────────────────────────────────────┤
│  UNIT         199 tests  (8 suites)     │
│  INTEGRATION   65 tests  (4 suites)     │
│  SECURITY      85 tests  (4 suites)     │
│  E2E           40 tests  (1 suite)      │
└─────────────────────────────────────────┘
```

### Harness unifié

```bash
# Tout lancer
php backend/tests/run_all.php

# Catégories
php backend/tests/run_all.php --quick      # unit only
php backend/tests/run_all.php --security   # security only

# Filter
php backend/tests/run_all.php --filter=banque

# CI-friendly
php backend/tests/run_all.php --no-color
```

### Pipeline CI/CD

GitHub Actions exécute automatiquement à chaque push :

**`tests.yml`** (3-5 min) :
- Matrix PHP 8.2 + 8.3
- Syntaxe PHP (tous les fichiers)
- Harness complet
- Scripts backup + verify hash
- Endpoint /health live
- Parse JSX (Babel)

**`lint.yml`** (<1 min) :
- `php -l` rapide
- Structure projet
- Vérif mention license

### Badges status

```markdown
[![Tests](.../tests.yml/badge.svg)]
[![Lint](.../lint.yml/badge.svg)]
[![Tests](badge-389-passed)]
```

---

## 🎯 Prochaines évolutions

Voir `docs/ROADMAP.md` pour les évolutions prévues :
- Export examens (portabilité)
- Import questions depuis CSV/Excel
- Corrections manuelles (questions ouvertes)
- Mode dégradé hors-ligne
- Intégration SSO (OAuth2)

---

## 📞 Support et contact

- **Auteur** : Mohamed EL AFRIT
- **Email** : m.elafrit@ecole-ipssi.net
- **Repo** : https://github.com/melafrit/maths_IA_niveau_1

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
