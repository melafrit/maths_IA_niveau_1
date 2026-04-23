# 📋 Note de cadrage — Certio v2.0

> **Plateforme multi-écoles d'évaluation avec Certainty-Based Marking**

| Champ | Valeur |
|---|---|
| **Projet** | Certio v2.0 (anciennement IPSSI Examens v1.0) |
| **Code projet** | CERTIO-V2-2026 |
| **Version document** | 1.0 |
| **Date** | Avril 2026 |
| **Auteur** | Mohamed EL AFRIT |
| **Contact** | mohamed@elafrit.com |
| **Statut** | Cadrage validé |
| **Licence** | CC BY-NC-SA 4.0 |

---

## Sommaire

1. [Contexte et vision](#1-contexte-et-vision)
2. [Objectifs et KPIs](#2-objectifs-et-kpis)
3. [Scope v2.0](#3-scope-v20)
4. [Architecture cible](#4-architecture-cible)
5. [Modèle de données évolué](#5-modèle-de-données-évolué)
6. [Parties prenantes et RACI](#6-parties-prenantes-et-raci)
7. [Risques et mitigations](#7-risques-et-mitigations)
8. [Critères de succès](#8-critères-de-succès)
9. [Budget et coûts estimatifs](#9-budget-et-coûts-estimatifs)
10. [Annexes](#10-annexes)

---

## 1. Contexte et vision

### 1.1 Historique

**IPSSI Examens v1.0** a été développée en 2025-2026 pour répondre aux besoins internes de l'école IPSSI dans le cadre du module "Mathématiques appliquées à l'IA" (Bachelor 2 Informatique). La plateforme a démontré sa robustesse avec :

- **389/389 tests automatisés** ✅
- **17 managers PHP** en architecture layered stricte
- **9 endpoints API REST**
- **320 questions** dans la banque initiale
- **Architecture zéro-dépendance** (PHP natif + React CDN)
- **Déploiement compatible OVH** mutualisé et VPS

### 1.2 Pourquoi une v2.0 ?

Après retour d'expérience et analyse du marché éducatif francophone, plusieurs constats :

1. **La plateforme est mature techniquement** mais **limitée fonctionnellement** pour un usage au-delà d'IPSSI
2. **Le QCM traditionnel encourage le hasard** : un étudiant qui ne sait pas peut répondre au hasard et avoir 25% par chance
3. **Aucun outil francophone** n'offre le **CBM (Certainty-Based Marking)** en SaaS accessible
4. **Le marché des LMS/QCM** français est dominé par des outils étrangers lourds (Moodle, Canvas)
5. **Les écoles et centres de formation** cherchent une alternative simple, moderne, souveraine
6. **Notre stack "no-build"** est un atout pour les déploiements légers

### 1.3 Vision produit

> **Certio** est la première plateforme francophone d'évaluation basée sur la **certitude**, offrant aux enseignants un outil pédagogiquement supérieur et scientifiquement rigoureux pour mesurer la **vraie connaissance** des étudiants.

**Positionnement** : l'anti-QCM traditionnel. Certio combat l'effet du hasard en récompensant l'honnêteté intellectuelle et en forçant les étudiants à développer leur **métacognition**.

### 1.4 Positionnement marché

| Concurrent | Forces | Faiblesses | Certio a l'avantage sur |
|---|---|---|---|
| **Moodle Quiz** | Ecosystem complet, gratuit | Lourd, pas de CBM, UX datée | CBM, UX moderne, légèreté |
| **Wooclap** | Moderne, interactif | Cher, pas de CBM, orienté live | CBM, examens asynchrones |
| **Kahoot!** | Ludique, populaire | Pas adapté évaluation sérieuse | Rigueur évaluative, CBM |
| **ProProfs** | Pro, nombreuses features | Anglais, cher, US | Francophone, souveraineté |
| **Google Forms** | Gratuit, simple | Pas d'anti-triche, pas de CBM | Sécurité, CBM, analytics |

**Avantage concurrentiel unique de Certio** : 🎲 **seule plateforme francophone avec CBM natif paramétrable**.

### 1.5 Marché cible

1. **Enseignement supérieur privé** — écoles d'informatique, commerce, ingénieurs
2. **Centres de formation professionnelle** — CPF, Pôle Emploi, Afpa
3. **Lycées privés** (Bac général, STMG, STI2D)
4. **Entreprises** — évaluations internes, onboarding, certifications métier
5. **Formateurs indépendants** — freelances, coachs, consultants

---

## 2. Objectifs et KPIs

### 2.1 Objectifs stratégiques

| Objectif | Description | Horizon |
|---|---|---|
| **O1 — Universalisation** | Transformer IPSSI Examens en produit multi-écoles | v2.0 |
| **O2 — Différenciation** | Premier acteur francophone CBM natif | v2.0 |
| **O3 — Qualité pédagogique** | Être reconnu par la communauté académique | v2.0-2.1 |
| **O4 — Effet de réseau** | Banque communautaire attractive | v2.0-2.1 |
| **O5 — Souveraineté numérique** | 100% hébergé OVH France, RGPD-first | v2.0 |
| **O6 — Monétisation** | Modèle SaaS viable (Free/Pro/Enterprise) | v2.1+ |

### 2.2 Objectifs opérationnels (SMART)

| # | Objectif | Mesurable | Délai |
|---|---|---|---|
| **OP1** | Publier v2.0 en production | ✅ Release notes + déploiement prod | Q3 2026 |
| **OP2** | Migrer 100% des examens IPSSI sans perte | ✅ Script migration + 0 erreur | Phase 7 |
| **OP3** | Couverture tests > 85% | ✅ Rapport couverture | Phase 6 |
| **OP4** | Accessibilité WCAG AA | ✅ Audit axe-core passé | Phase 6 |
| **OP5** | Onboarding 3 écoles pilotes | ✅ 3 contrats pilotes signés | Q4 2026 |
| **OP6** | 1000+ questions communautaires | ✅ Compteur banque | Q1 2027 |

### 2.3 KPIs de succès post-release

#### 📊 Adoption
- Nombre d'écoles actives (workspaces) : **cible = 10** à 6 mois
- Nombre de profs actifs : **cible = 100** à 6 mois
- Nombre d'étudiants ayant passé au moins 1 examen : **cible = 2000** à 6 mois
- Nombre d'examens créés : **cible = 500** à 6 mois
- Nombre de passages réalisés : **cible = 10 000** à 6 mois

#### 🎯 Engagement
- Taux d'activation CBM (examens où CBM est activé) : **cible = 40%**
- Questions partagées communauté : **cible = 2 000**
- Taux de fork de questions communautaires : **cible = 25%**
- NPS (Net Promoter Score) prof : **cible ≥ 40**
- NPS étudiant : **cible ≥ 30**

#### 🏥 Qualité technique
- Uptime : **≥ 99.5%**
- Temps de réponse médian API : **< 200ms**
- Taux d'erreur 5xx : **< 0.1%**
- Score Lighthouse Performance : **≥ 90**
- Score WCAG AA : **100% critères respectés**

#### 💰 Business (v2.1+)
- Conversion Free → Pro : **cible = 5%**
- MRR (Monthly Recurring Revenue) : **cible = 2000€ à 12 mois**
- CAC (Customer Acquisition Cost) : **< 50€**
- Churn mensuel : **< 5%**

---

## 3. Scope v2.0

### 3.1 In-scope (livrable v2.0)

#### 🏷️ **Rebranding complet**
- ✅ Nom : **Certio** (modifiable via config centrale)
- ✅ Centralisation branding (`config/branding.php` + `branding.js`)
- ✅ Email unique : `mohamed@elafrit.com` (partout)
- ✅ Logo et favicon (placeholders SVG fournis, remplaçables)
- ✅ Palette de couleurs modifiable
- ✅ Suppression de toutes références "IPSSI"

#### 🎲 **CBM (Certainty-Based Marking) 100% paramétrable**
- ✅ Matrice de scoring libre (N niveaux × 2 colonnes [juste|faux])
- ✅ Prof choisit le nombre de niveaux (2 à 10)
- ✅ Prof nomme chaque niveau (ex: "Je devine", "Je pense", "Je suis sûr")
- ✅ Prof définit le score de chaque cellule
- ✅ Sauvegarde de **presets personnels** réutilisables
- ✅ Import/export JSON d'une matrice CBM
- ✅ Mode simple : un slider % ou une échelle Likert au choix
- ✅ Visualisations pour l'étudiant : graphe "mes scores CBM"
- ✅ Analytics prof : calibration par étudiant (over/underconfidence)

#### 🎯 **Types de questions étendus (7 types)**
- ✅ Vrai/Faux (2 choix, 1 bonne réponse)
- ✅ QCM 4 radio (format actuel v1)
- ✅ QCM 5 radio
- ✅ QCM N radio (4-10 propositions configurables)
- ✅ QCM 4 checkbox (multi-réponses)
- ✅ QCM 5 checkbox
- ✅ QCM N checkbox (4-10 propositions, M bonnes réponses)

#### ⚖️ **Barèmes multi-réponses (checkbox)**
- ✅ Tout ou rien (all-or-nothing)
- ✅ Proportionnel strict (+1 bonne cochée, -1 fausse cochée)
- ✅ Proportionnel normalisé (min 0)
- ✅ Personnalisable par examen

#### 🔄 **Migration unifiée**
- ✅ Schéma v2 unique pour tous les examens
- ✅ Flag `cbm_enabled: true/false` par examen
- ✅ Conversion silencieuse au 1er accès prof
- ✅ Script de migration `v1-to-v2.php` avec dry-run
- ✅ Backup automatique avant migration
- ✅ Possibilité de rollback

#### 📚 **Documentation interactive intégrée**
- ✅ Pages de doc en Markdown (`.md`) dans `/docs/`
- ✅ Parser côté client (`marked.js` via CDN)
- ✅ Navigation automatique depuis structure dossier
- ✅ Accès contrôlé par rôle (RBAC)
  - **Admin** voit tout
  - **Prof** voit partie prof + étudiant
  - **Étudiant** voit uniquement guide étudiant
- ✅ Placeholders pour 4 familles de médias
- ✅ Prompts IA documentés pour chaque placeholder
- ✅ Recherche basique (titre, tags)
- ✅ Table des matières auto-générée

#### 🔐 **Sécurité avancée**
- ✅ 2FA TOTP (Google Authenticator) admin + prof
- ✅ Audit log complet (qui, quoi, quand, IP)
- ✅ Page admin de consultation audit log
- ✅ Anti-triche renforcé :
  - Empreinte navigateur (fingerprint)
  - Détection copy/paste/blur
  - Détection ouverture devtools
  - Score de confiance par passage

#### 🏫 **Multi-tenant (Workspaces)**
- ✅ Isolation stricte des données par workspace (école)
- ✅ Chaque workspace a son admin, profs, étudiants
- ✅ Branding par workspace (logo, couleurs, nom école)
- ✅ SSO : Google OAuth + Microsoft OAuth (minimum)
- ✅ Limite par plan (exemple : Free = 1 workspace, 5 profs)
- ✅ Super-admin Certio voit tous les workspaces

#### 📤 **Intégrations LMS**
- ✅ Import questions Moodle XML
- ✅ Import questions Word (.docx avec format Aiken)
- ✅ Import questions Excel (.xlsx template fourni)
- ✅ Export SCORM 1.2 et 2004
- ✅ Export xAPI (Tin Can)
- ✅ Compatibilité LTI 1.3 (Moodle, Canvas, Blackboard)
- ✅ API REST publique documentée (OpenAPI 3.1)
- ✅ Swagger UI embarqué dans admin

#### 🌍 **Accessibilité, i18n, mobile**
- ✅ WCAG AA complet
  - Navigation clavier 100%
  - Contraste conforme
  - Lecteurs d'écran (JAWS, NVDA, VoiceOver)
  - Indicateurs focus visibles
  - aria-* labels partout
- ✅ Internationalisation (i18n)
  - Français (par défaut)
  - Anglais
  - Architecture prête pour AR, ES
- ✅ Mode sombre complet (déjà partiel en v1)
- ✅ PWA (Progressive Web App)
  - Manifest
  - Service Worker
  - Mode hors-ligne pour passages en cours
  - Installable sur mobile/desktop

#### 🌐 **Banque de questions communautaire**
- ✅ 3 niveaux de visibilité : Privé / Workspace / Communauté
- ✅ Publier une question en communauté (action explicite du prof)
- ✅ Choix de licence (CC BY, CC BY-SA, CC0, CC BY-NC)
- ✅ Attribution automatique de l'auteur original
- ✅ Fork d'une question communautaire vers sa banque perso
- ✅ Historique des forks (audit trail)
- ✅ Système de votes :
  - ⭐ qualité (1-5 étoiles)
  - 👍 utile
  - 🚩 signaler (modération)
- ✅ Modération : super-admin Certio valide/rejette les signalements
- ✅ Filtres : niveau d'études, matière, langue, auteur, rating
- ✅ Recherche full-text sur énoncés + tags
- ✅ Stats anonymisées : "utilisée 247 fois, taux réussite moyen 62%"
- ✅ Opt-out total possible par workspace

### 3.2 Out-of-scope v2.0 → reporté en v2.1+

Ces fonctionnalités sont **souhaitables mais reportées** pour garantir une v2.0 livrable :

- ❌ Génération IA de questions/distracteurs (v2.1)
- ❌ Mode adaptatif IRT (Item Response Theory) (v2.1)
- ❌ Proctoring webcam avancé (v2.2)
- ❌ Support vidéo/audio dans questions (v2.1)
- ❌ Co-correction multi-profs (v2.1)
- ❌ Webhooks externes (v2.1)
- ❌ Plans tarifaires + Stripe (v2.1)
- ❌ Landing page marketing (projet séparé)
- ❌ App mobile native (iOS/Android) (v3.0)
- ❌ Migration vers SQL (v3.0 si nécessaire)
- ❌ Monitoring Grafana/Prometheus (v2.1)
- ❌ Dockerfile production (v2.1)

### 3.3 Hors scope définitif

- ❌ Correction automatique de copies rédigées (IA)
- ❌ Surveillance en direct avec caméra (trop invasif)
- ❌ Reconnaissance faciale
- ❌ Modèle de coaching individuel IA
- ❌ LMS complet (cours, parcours pédagogiques)

---

## 4. Architecture cible

### 4.1 Principes architecturaux

Certio v2.0 conserve les **principes fondateurs** qui ont fait la qualité de la v1 :

1. **Zero-dependency philosophy**
   - Backend : PHP 8.3 natif (pas de Composer obligatoire)
   - Frontend : React 18 CDN + Babel Standalone (pas de bundler)
   - Persistance : fichiers JSON (pas de SQL obligatoire)

2. **Architecture layered stricte** (5 couches)
   - Pas de flèche qui saute une couche
   - Middleware-based security
   - Managers = business logic

3. **Fail-open + graceful degradation**
   - Si un middleware plante → log + requête passe
   - Si un service externe est down → dégradation gracieuse

4. **Security by default**
   - CSRF tokens partout
   - Rate limiting par rôle
   - HMAC signatures sur passages
   - bcrypt cost 12

5. **Testability first**
   - Tous les managers testables en isolation
   - Tests automatisés > 85% couverture
   - E2E obligatoire sur workflows critiques

### 4.2 Stack technique v2.0

| Couche | Technologie | Évolution vs v1 |
|---|---|---|
| **Frontend Core** | React 18 CDN + Babel Standalone | ✅ inchangé |
| **Math** | KaTeX CDN | ✅ inchangé |
| **Graphs** | Recharts CDN | ✅ inchangé |
| **Excel** | SheetJS CDN | ✅ inchangé |
| **Python (démos)** | Pyodide | ✅ inchangé |
| **Markdown** | marked.js CDN | 🆕 ajouté (doc) |
| **i18n** | Custom (JSON FR/EN) | 🆕 ajouté |
| **PWA** | Service Worker natif | 🆕 ajouté |
| **Backend** | PHP 8.3 strict types | ✅ inchangé |
| **Auth 2FA** | OTPHP (lib PHP native) | 🆕 ajouté |
| **SSO** | OAuth 2.0 (custom) | 🆕 ajouté |
| **LMS Export** | SCORM zip builder custom | 🆕 ajouté |
| **Persistance** | JSON files | ✅ inchangé |
| **Session** | PHP native | ✅ inchangé |
| **Tests** | Custom harness (v1) | ✅ étendu |
| **CI/CD** | GitHub Actions | ✅ étendu |
| **Hébergement** | OVH VPS Ubuntu 22.04 | ✅ inchangé |
| **SMTP** | OVH Email Pro | ✅ inchangé |

### 4.3 Nouveaux composants (Managers v2.0)

Ajouts à la couche métier (backend/lib/) :

| Nouveau Manager | Responsabilité |
|---|---|
| **CbmManager** | Gestion matrices scoring, calcul scores CBM |
| **QuestionTypeResolver** | Résout scoring selon type de question |
| **WorkspaceManager** | Multi-tenant, isolation par école |
| **TotpManager** | 2FA (génération, validation, QR codes) |
| **SsoManager** | Google/Microsoft OAuth |
| **AuditLogger** | Audit log centralisé |
| **AntiCheatAnalyzer** | Score de confiance passage |
| **CommunityBankManager** | Banque communautaire (publish, fork, votes) |
| **ImportManager** | Import Moodle/Word/Excel |
| **ExportManager** | Export SCORM/xAPI/LTI |
| **DocumentationManager** | Serveur doc Markdown avec RBAC |
| **I18nManager** | Gestion traductions |
| **BrandingManager** | Centralisation branding dynamique |

### 4.4 Changements majeurs vs v1

#### 🔴 Breaking changes
- Schéma des examens v1 → v2 (migration automatique au 1er accès)
- Schéma des questions v1 → v2 (avec nouveaux champs : `type`, `options[]`, `correct_answers[]`)
- Schéma des passages v1 → v2 (avec `cbm_data`, `workspace_id`)
- Toutes les références "IPSSI" → "Certio" (code + docs + UI)

#### 🟡 Changements non-breaking
- Ajout de nouveaux endpoints (`/api/workspaces`, `/api/community`, `/api/cbm`)
- Nouveaux managers (coexistent avec anciens)
- Nouveaux rôles (`super_admin` pour Certio, `workspace_admin`)
- Nouveaux champs dans comptes (`workspace_id`, `totp_secret`, `sso_provider`)

#### 🟢 Ajouts purs
- Documentation interactive
- Banque communautaire
- Import/Export LMS
- i18n
- PWA

### 4.5 Compatibilité ascendante

- ✅ Tous les examens v1 migrés automatiquement vers v2
- ✅ Les URLs existantes continuent de fonctionner
- ✅ Les comptes utilisateurs existants restent valides (mot de passe conservé)
- ✅ Les passages soumis restent consultables (format v1 preservé en lecture seule)
- ✅ Les backups v1 restaurables (avec conversion à la volée)

---

## 5. Modèle de données évolué

### 5.1 Schéma Examen v2

```json
{
  "id": "EXM-XXXX-YYYY",
  "schema_version": 2,
  "workspace_id": "WKS-XXXX",
  "creator_id": "USR-XXXX",
  "title": "Examen Math appliqués à l'IA",
  "description": "...",
  "status": "draft|published|closed|archived",
  "created_at": "2026-04-23T10:00:00Z",
  "updated_at": "2026-04-23T11:00:00Z",
  "access_code": "ABC23K-9P",
  "date_ouverture": "2026-05-01T09:00:00Z",
  "date_cloture": "2026-05-15T23:59:59Z",
  "duration_minutes": 60,
  "max_passages": null,
  "shuffle_questions": true,
  "shuffle_options": true,
  "anti_cheat": {
    "detect_focus_loss": true,
    "detect_copy_paste": true,
    "detect_devtools": true,
    "detect_fingerprint": true,
    "max_focus_loss": 3
  },
  "cbm": {
    "enabled": true,
    "mode": "custom",
    "matrix": {
      "levels": [
        {"id": 1, "label": "Je devine", "value": 0},
        {"id": 2, "label": "Je pense", "value": 50},
        {"id": 3, "label": "Je suis sûr", "value": 100}
      ],
      "scoring": [
        {"level_id": 1, "correct": 1,  "incorrect": 0},
        {"level_id": 2, "correct": 2,  "incorrect": -1},
        {"level_id": 3, "correct": 3,  "incorrect": -3}
      ]
    }
  },
  "scoring": {
    "multi_answer_mode": "proportional_normalized",
    "total_points": 100,
    "passing_score": 60
  },
  "questions": [
    { "question_id": "QST-XXXX", "weight": 1.0, "order": 1 }
  ],
  "notifications": {
    "notify_creator_on_submit": true,
    "notify_student_on_submit": true,
    "custom_email_template_id": null
  },
  "locale": "fr",
  "tags": ["mathématiques", "IA", "bac+2"]
}
```

### 5.2 Schéma Question v2

```json
{
  "id": "QST-XXXX",
  "schema_version": 2,
  "workspace_id": "WKS-XXXX",
  "creator_id": "USR-XXXX",
  "visibility": "private|workspace|community",
  "type": "true_false|mcq_single|mcq_multiple",
  "subtype_config": {
    "num_options": 4,
    "shuffle_options": true
  },
  "statement": "Quelle est la dérivée de $f(x) = x^2$ ?",
  "statement_format": "markdown_with_latex",
  "options": [
    {"id": "A", "text": "$2x$",     "is_correct": true},
    {"id": "B", "text": "$x$",      "is_correct": false},
    {"id": "C", "text": "$2$",      "is_correct": false},
    {"id": "D", "text": "$x^2/2$",  "is_correct": false}
  ],
  "explanation": "La dérivée de $x^n$ est $nx^{n-1}$...",
  "difficulty": "easy|medium|hard|expert",
  "tags": ["calcul", "dérivée"],
  "module": "Mathématiques",
  "chapitre": "Analyse",
  "theme": "Dérivées",
  "locale": "fr",
  "created_at": "2026-01-15T10:00:00Z",
  "updated_at": "2026-04-23T11:00:00Z",
  "community": {
    "published_at": null,
    "license": "CC-BY-SA",
    "forked_from": null,
    "original_author_id": null,
    "usage_count": 0,
    "rating_average": null,
    "rating_count": 0,
    "flag_count": 0
  }
}
```

### 5.3 Schéma Passage v2

```json
{
  "id": "PSG-WXYZ-5678",
  "schema_version": 2,
  "token": "uuid-v4-...",
  "workspace_id": "WKS-XXXX",
  "examen_id": "EXM-XXXX-YYYY",
  "student": {
    "nom": "Dupont",
    "prenom": "Alice",
    "email": "alice.dupont@example.com",
    "custom_fields": {}
  },
  "status": "in_progress|submitted|expired|invalidated",
  "sub_status": "answering|reviewing|auto_saving",
  "started_at": "2026-05-01T10:00:00Z",
  "submitted_at": null,
  "expires_at": "2026-05-01T11:00:00Z",
  "questions_order": ["QST-001", "QST-002", ...],
  "answers": {
    "QST-001": {
      "selected_options": ["A"],
      "cbm_level_id": 3,
      "answered_at": "2026-05-01T10:05:00Z",
      "time_spent_seconds": 45
    }
  },
  "focus_events": [
    {"type": "blur", "at": "2026-05-01T10:10:00Z", "duration_ms": 2500}
  ],
  "anti_cheat_signals": {
    "copy_paste_count": 0,
    "devtools_opened": false,
    "browser_fingerprint": "abc123...",
    "fingerprint_changed": false,
    "confidence_score": 0.95
  },
  "score": {
    "raw": null,
    "max": null,
    "percentage": null,
    "cbm_score": null,
    "cbm_calibration": null,
    "passed": null
  },
  "signature_sha256": "...",
  "created_at": "2026-05-01T10:00:00Z"
}
```

### 5.4 Schéma Workspace (nouveau)

```json
{
  "id": "WKS-XXXX",
  "name": "École IPSSI",
  "slug": "ipssi",
  "plan": "free|pro|enterprise",
  "branding": {
    "logo_url": "/assets/workspaces/ipssi/logo.svg",
    "primary_color": "#1a365d",
    "secondary_color": "#48bb78"
  },
  "settings": {
    "allow_community_publish": true,
    "allow_sso_google": true,
    "allow_sso_microsoft": true,
    "default_locale": "fr",
    "custom_email_from": "noreply@ipssi.edu"
  },
  "limits": {
    "max_profs": 100,
    "max_students_per_month": 5000,
    "max_storage_mb": 10240
  },
  "admins": ["USR-XXXX", "USR-YYYY"],
  "created_at": "2026-04-23T10:00:00Z",
  "subscription_end": "2027-04-22T23:59:59Z",
  "status": "active|suspended|cancelled"
}
```

### 5.5 Schéma BanqueCommunautaire

```json
{
  "id": "CBK-XXXX",
  "question_id": "QST-XXXX",
  "original_workspace_id": "WKS-XXXX",
  "published_by_user_id": "USR-XXXX",
  "published_at": "2026-04-23T10:00:00Z",
  "license": "CC-BY|CC-BY-SA|CC-BY-NC|CC-0",
  "review_status": "pending|approved|rejected",
  "reviewed_by_user_id": null,
  "reviewed_at": null,
  "stats": {
    "view_count": 0,
    "fork_count": 0,
    "usage_count": 0,
    "success_rate_average": null
  },
  "ratings": [
    {"user_id": "USR-ZZZZ", "stars": 5, "at": "2026-04-24"}
  ],
  "flags": []
}
```

### 5.6 Script de migration v1 → v2

Détaillé en Phase 7. Principes :

1. Backup complet avant migration
2. Itération sur tous les fichiers JSON v1
3. Conversion via mapping `schema_v1 → schema_v2`
4. Ajout des nouveaux champs (valeurs par défaut)
5. Validation du schéma v2
6. Dry-run disponible
7. Rollback possible

---

## 6. Parties prenantes et RACI

### 6.1 Acteurs

| Acteur | Rôle | Intérêt |
|---|---|---|
| **Mohamed EL AFRIT** | Product Owner + Architect + Dev | Success produit |
| **IA Assistant** (Claude Code/Cursor) | Dev assistant | Qualité code généré |
| **Écoles pilotes** | UAT + retours | Outil fonctionnel |
| **Profs utilisateurs** | Utilisateurs core | Pédagogie supérieure |
| **Étudiants** | Utilisateurs finaux | Expérience fluide |
| **OVH** | Fournisseur infra | SLA maintenu |

### 6.2 Matrice RACI simplifiée

| Tâche | PO/Dev | IA Assistant | Pilotes |
|---|:-:|:-:|:-:|
| Définir specs | **R/A** | C | I |
| Écrire code | **R** | C | – |
| Tester code | **A** | C/R | – |
| Valider UX | **A** | C | **R** |
| Documentation | **R/A** | C | I |
| Déploiement | **R/A** | C | I |
| UAT | **A** | – | **R** |

**Légende** : R=Responsable · A=Approbateur · C=Consulté · I=Informé

---

## 7. Risques et mitigations

### 7.1 Risques techniques

| # | Risque | Probabilité | Impact | Mitigation |
|---|---|:-:|:-:|---|
| **R1** | Migration v1→v2 corrompt des données | Moyen | Critique | Backup + dry-run + validation schema |
| **R2** | Performance dégradée (workspaces) | Moyen | Élevé | Benchmarks + index fichiers |
| **R3** | Conflit JSON avec concurrence | Faible | Moyen | Verrouillage `flock()` renforcé |
| **R4** | CDN React/KaTeX down | Faible | Critique | Self-hosting des libs en fallback |
| **R5** | SSO OAuth complexité | Moyen | Moyen | Utiliser librairies testées, pas from-scratch |
| **R6** | Tests flaky ralentissent CI | Moyen | Faible | Retry + timeouts ajustés |

### 7.2 Risques fonctionnels

| # | Risque | Probabilité | Impact | Mitigation |
|---|---|:-:|:-:|---|
| **R7** | CBM mal compris par profs | Élevé | Élevé | Doc interactive + tuto vidéo + FAQ |
| **R8** | CBM mal compris par étudiants | Élevé | Élevé | Mini-tutoriel au 1er examen CBM |
| **R9** | Banque communautaire avec contenus low-quality | Moyen | Moyen | Modération + système de votes |
| **R10** | Questions communautaires pas assez | Moyen | Faible | Seed initial (100+ questions Mohamed) |
| **R11** | Résistance changement v1 → v2 | Moyen | Moyen | Rétro-compat + communication |

### 7.3 Risques humains / organisationnels

| # | Risque | Probabilité | Impact | Mitigation |
|---|---|:-:|:-:|---|
| **R12** | Dev solo = bus factor 1 | Certain | Critique | Doc excellente + tests exhaustifs |
| **R13** | Perte de motivation mi-chantier | Moyen | Élevé | Phases courtes + célébrer wins |
| **R14** | Scope creep (tentation ajouts) | Élevé | Élevé | Stick to planning + backlog v2.1 |
| **R15** | Retours écoles pilotes négatifs | Faible | Élevé | UAT early + iterations |

---

## 8. Critères de succès

### 8.1 Critères techniques (Definition of Done v2.0)

- [ ] Tous les 389 tests v1 passent + 200+ nouveaux tests
- [ ] Couverture de tests ≥ 85%
- [ ] Aucun warning PHP (strict types partout)
- [ ] Aucun warning JS console (pure React)
- [ ] Lighthouse Performance ≥ 90
- [ ] Lighthouse Accessibility ≥ 95
- [ ] WCAG AA validé par audit axe-core
- [ ] Tous les secrets en variables d'env / config
- [ ] Documentation technique à jour (ARCHITECTURE, GUIDE_DEV)
- [ ] README à jour avec badges CI

### 8.2 Critères fonctionnels

- [ ] Un prof peut créer un examen avec CBM en < 5 minutes
- [ ] Un étudiant peut passer un examen CBM sans tutoriel préalable (UX claire)
- [ ] Le rebranding "Certio → X" se fait en modifiant 2 fichiers
- [ ] La migration v1 → v2 prend < 5 minutes pour 1000 examens
- [ ] Import Moodle XML fonctionne avec fichiers test fournis
- [ ] SSO Google/Microsoft fonctionne en < 3 clics
- [ ] La banque communautaire a 100+ questions seed au lancement
- [ ] Doc interactive accessible en < 2 clics depuis admin

### 8.3 Critères utilisateur (UAT pilotes)

- [ ] 3 écoles pilotes confirment "prêt pour production"
- [ ] 10 profs pilotes confirment "mieux que v1"
- [ ] NPS prof ≥ 40
- [ ] NPS étudiant ≥ 30
- [ ] Zero régression bloquante sur fonctions v1

---

## 9. Budget et coûts estimatifs

### 9.1 Temps de développement

| Phase | Jours (solo) | Jours (avec IA assist) |
|---|:-:|:-:|
| Phase 0 : Fondations | 5 | **3** |
| Phase 1 : CBM Core | 8 | **5** |
| Phase 2 : Types questions | 6 | **4** |
| Phase 3 : Scoring analytics | 5 | **4** |
| Phase 4 : Doc interactive | 7 | **5** |
| Phase 5 : Améliorations | 12 | **9** |
| Phase 6 : Tests | 5 | **4** |
| Phase 7 : Déploiement | 3 | **2** |
| **TOTAL** | **51 jours** | **36 jours** |

### 9.2 Coûts externes (infrastructure, outils)

| Poste | Coût mensuel | Annuel |
|---|:-:|:-:|
| OVH VPS Starter Ubuntu | 5 € | 60 € |
| OVH Email Pro | 2 € | 24 € |
| OVH Object Storage (backups) | 3 € | 36 € |
| Nom de domaine `certio.app` | – | 25 € |
| Cloudflare Free (CDN + DNS) | 0 € | 0 € |
| GitHub Pro | 0 € | 0 € |
| UptimeRobot Free | 0 € | 0 € |
| Claude Code / Cursor Pro (IA assist) | 20 € | 240 € |
| **TOTAL** | **30 €/mois** | **385 €/an** |

### 9.3 Coûts optionnels (marketing v2.1+)

- Logo pro par designer : 200-500 €
- Landing page certio.app : 300-800 € (ou 0 si DIY)
- SEO initial : 200 €
- Comptes réseaux sociaux : 0 €
- ProductHunt launch : 0 €

---

## 10. Annexes

### A. Stack technique complète

Voir `docs/ARCHITECTURE.md` (existant, à mettre à jour en Phase 0).

### B. Structure fichiers cible

```
certio/
├── backend/
│   ├── config/
│   │   ├── branding.php         🆕
│   │   └── workspaces.php       🆕
│   ├── lib/
│   │   ├── [16 managers v1]     ✅
│   │   ├── CbmManager.php       🆕
│   │   ├── WorkspaceManager.php 🆕
│   │   ├── TotpManager.php      🆕
│   │   ├── SsoManager.php       🆕
│   │   ├── CommunityBankManager.php 🆕
│   │   ├── ImportManager.php    🆕
│   │   ├── ExportManager.php    🆕
│   │   ├── DocumentationManager.php 🆕
│   │   ├── I18nManager.php      🆕
│   │   ├── AntiCheatAnalyzer.php 🆕
│   │   └── AuditLogger.php      🆕
│   ├── api/
│   │   ├── [9 endpoints v1]     ✅
│   │   ├── workspaces.php       🆕
│   │   ├── community.php        🆕
│   │   ├── cbm.php              🆕
│   │   ├── imports.php          🆕
│   │   ├── exports.php          🆕
│   │   └── docs.php             🆕
│   └── public/
│       └── index.php            ✅ (routes étendues)
├── frontend/
│   ├── admin/
│   │   ├── [pages v1]           ✅
│   │   ├── workspaces.html      🆕
│   │   ├── community.html       🆕
│   │   ├── audit.html           🆕
│   │   └── docs.html            🆕
│   ├── prof/
│   │   ├── [pages renommées]    ✅
│   │   ├── cbm-config.html      🆕
│   │   └── docs.html            🆕
│   ├── etudiant/
│   │   ├── [pages v1]           ✅
│   │   └── docs.html            🆕
│   └── assets/
│       ├── branding.js          🆕
│       ├── i18n/                🆕
│       ├── components/          ✅ (enrichi)
│       └── pwa/                 🆕
├── docs-interactive/            🆕
│   ├── admin/
│   ├── prof/
│   ├── etudiant/
│   └── shared/
├── data/
│   ├── [structure v1]           ✅
│   ├── workspaces/              🆕
│   ├── community/               🆕
│   ├── audit/                   🆕
│   └── config/                  🆕
└── scripts/
    ├── [scripts v1]             ✅
    └── migrate-v1-to-v2.php     🆕
```

### C. Glossaire

- **CBM** : Certainty-Based Marking, système d'évaluation qui intègre la certitude de la réponse
- **RBAC** : Role-Based Access Control
- **RACI** : Responsible, Accountable, Consulted, Informed
- **LMS** : Learning Management System
- **SCORM** : Sharable Content Object Reference Model
- **LTI** : Learning Tools Interoperability
- **xAPI** : Experience API (Tin Can)
- **WCAG** : Web Content Accessibility Guidelines
- **PWA** : Progressive Web App
- **SSO** : Single Sign-On
- **TOTP** : Time-based One-Time Password
- **NPS** : Net Promoter Score
- **MRR** : Monthly Recurring Revenue
- **UAT** : User Acceptance Testing
- **CAC** : Customer Acquisition Cost

### D. Références académiques CBM

- Gardner, R.W. (1932). "The Use of the Term Probability in a Mathematical Sense"
- Dubois, D. (2005). "Certainty-based assessment in French higher education"
- Petitjean, S. (2010). "Adaptive certainty-based marking in DUT"
- Brier, G.W. (1950). "Verification of forecasts expressed in terms of probability"
- Gardner-Medwin, A.R. (2006). "Confidence-based marking: Towards deeper learning"

---

## Conclusion

Certio v2.0 est un projet **ambitieux mais parfaitement cadré**. Chaque feature sélectionnée a une justification pédagogique, technique ou business solide. Le planning en 8 phases garantit une livraison incrémentale et testable.

**Prochaines étapes immédiates** :
1. ✅ Validation de cette note de cadrage
2. ➡️ Génération du planning détaillé (livrable 2)
3. ➡️ Génération des prompts VS Code par phase (livrables 3-4)
4. ➡️ Préparation des inputs (livrable 5)

---

© 2026 Mohamed EL AFRIT — mohamed@elafrit.com  
Certio v2.0 — Certainty-Based Assessment Platform  
Licence : CC BY-NC-SA 4.0
