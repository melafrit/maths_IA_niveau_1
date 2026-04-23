# 📦 Kit d'inputs, checklists et guide de suivi

> **Livrable 5/5 — Tout ce dont tu as besoin pour démarrer et piloter Certio v2.0**

| Champ | Valeur |
|---|---|
| **Livrable** | 5/5 (FINAL) |
| **Version** | 1.0 |
| **Auteur** | Mohamed EL AFRIT |
| **Licence** | CC BY-NC-SA 4.0 |

---

## Sommaire

1. [Kit d'inputs à préparer](#1-kit-dinputs-à-préparer)
2. [Checklists de validation par phase](#2-checklists-de-validation-par-phase)
3. [Checklists transverses](#3-checklists-transverses)
4. [Templates prêts à remplir](#4-templates-prêts-à-remplir)
5. [Guide de suivi projet](#5-guide-de-suivi-projet)
6. [Gestion des risques en continu](#6-gestion-des-risques-en-continu)
7. [Plan de communication](#7-plan-de-communication)
8. [Synthèse finale du chantier](#8-synthèse-finale-du-chantier)

---

## 1. Kit d'inputs à préparer

### 🎨 1.1 Assets design

#### Logo
À préparer **avant la Phase 0** ou utiliser le placeholder :

- **Format** : SVG (vectoriel) + PNG (1x, 2x, 3x)
- **Tailles PNG** : 512x512, 192x192, 144x144, 96x96, 72x72
- **Variantes** : 
  - `logo-full.svg` — logo + texte
  - `logo-icon.svg` — icône seule
  - `logo-dark.svg` — pour fond sombre
  - `logo-light.svg` — pour fond clair

**Où créer** :
- **Quick/Free** : [Canva](https://canva.com), [LogoAI](https://logoai.com), [Looka](https://looka.com)
- **Pro** : Fiverr/Upwork (100-300€), graphiste freelance
- **IA** : Prompt DALL-E 3 / Midjourney :
  ```
  Modern minimalist logo for "Certio" - an online exam platform.
  Symbol: shield + checkmark + graduation cap, unified in geometric style.
  Color: deep navy blue (#1a365d) + mint green accent (#48bb78).
  Clean sans-serif typography for "Certio" wordmark.
  Flat design, vector style, works in 1-color and full-color.
  Transparent background, SVG-compatible, professional brand quality.
  ```

**Placeholder temporaire OK pour démarrage** — créé automatiquement en Phase 0.

#### Favicon
- **Format** : ICO (16x16, 32x32) + PNG (32x32, 180x180 pour Apple)
- **Générer depuis logo** : [RealFaviconGenerator](https://realfavicongenerator.net/)

#### Palette de couleurs

```css
:root {
  /* Couleurs principales */
  --color-primary: #1a365d;        /* Deep navy - trust, professionalism */
  --color-primary-light: #2c5282;
  --color-primary-dark: #0f2547;
  
  --color-secondary: #48bb78;      /* Mint green - growth, positive */
  --color-secondary-light: #68d391;
  --color-secondary-dark: #2f855a;
  
  --color-accent: #ed8936;         /* Warm orange - action, attention */
  --color-accent-light: #f6ad55;
  --color-accent-dark: #c05621;
  
  /* États */
  --color-success: #38a169;
  --color-warning: #d69e2e;
  --color-danger:  #e53e3e;
  --color-info:    #3182ce;
  
  /* Neutres (mode clair) */
  --color-bg:           #ffffff;
  --color-bg-secondary: #f7fafc;
  --color-text:         #1a202c;
  --color-text-muted:   #718096;
  --color-border:       #e2e8f0;
  
  /* Mode sombre */
  --color-bg-dark:           #0d1117;
  --color-bg-secondary-dark: #161b22;
  --color-text-dark:         #c9d1d9;
  --color-border-dark:       #30363d;
}
```

#### Typographie (Google Fonts)

```html
<!-- Dans <head> des pages HTML -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
```

- **UI & body** : Inter (sans-serif, lisible, moderne)
- **Code** : JetBrains Mono (monospace dev-friendly)
- **Alternatives** : Source Sans 3, Poppins, Space Grotesk

---

### 📊 1.2 Data de test

À préparer pour tests dev et UAT :

#### Utilisateurs fictifs

Créer `scripts/seed-test-users.php` :

```json
[
  {
    "id": "USR-TEST-001",
    "email": "admin.test@certio.test",
    "password": "Admin123!",
    "role": "admin",
    "nom": "Testeur",
    "prenom": "Admin"
  },
  {
    "id": "USR-TEST-002",
    "email": "prof1.test@certio.test",
    "password": "Prof123!",
    "role": "enseignant",
    "nom": "Dupont",
    "prenom": "Jean"
  },
  {
    "id": "USR-TEST-003",
    "email": "prof2.test@certio.test",
    "password": "Prof123!",
    "role": "enseignant",
    "nom": "Martin",
    "prenom": "Sophie"
  }
  // + 10 étudiants fictifs (emails étudiants)
]
```

#### Examens sample

3 examens types pour démonstrations :
- **Examen 1** : Maths (10 questions CBM, mix types)
- **Examen 2** : Informatique (15 questions, choix multiple)
- **Examen 3** : Culture générale (20 questions, Vrai/Faux majoritaires)

#### Questions seed communautaire

**100+ questions** pré-remplies pour populer la banque communautaire au lancement :
- 30 maths
- 25 informatique
- 20 culture générale
- 15 langues
- 10 logique/raisonnement

À récupérer depuis ta banque IPSSI existante (320 questions) — sélectionner les 100 meilleures.

---

### 🔑 1.3 Credentials à obtenir

#### Google OAuth 2.0 (pour SSO — Phase 5B)

1. Aller sur [Google Cloud Console](https://console.cloud.google.com/)
2. Créer nouveau projet "Certio"
3. APIs & Services → OAuth consent screen
   - User Type: External
   - App name: Certio
   - Support email: mohamed@elafrit.com
   - Authorized domains: certio.app, elafrit.com
4. Credentials → Create OAuth 2.0 Client ID
   - Application type: Web application
   - Authorized redirect URIs:
     - `https://certio.app/api/auth/google/callback`
     - `http://localhost:8765/api/auth/google/callback` (dev)

**Récupérer** :
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`

#### Microsoft OAuth (Phase 5B)

1. [Azure Portal](https://portal.azure.com/) → Azure Active Directory
2. App registrations → New registration
   - Name: Certio
   - Supported account types: Personal + Work
   - Redirect URI: `https://certio.app/api/auth/microsoft/callback`
3. Certificates & secrets → New client secret

**Récupérer** :
- `MICROSOFT_CLIENT_ID` (Application ID)
- `MICROSOFT_CLIENT_SECRET`
- `MICROSOFT_TENANT_ID` (ou `common` pour multi-tenant)

#### OVH SMTP (Phase 0 - déjà en place)

- Host : `ssl0.ovh.net`
- Port : `465`
- Encryption : `SSL`
- Username : `noreply@elafrit.com`
- Password : *mot de passe OVH Email Pro*

#### OVH Object Storage (pour backups Phase 7)

1. [OVH Manager](https://www.ovh.com/manager/) → Public Cloud → Object Storage
2. Create bucket `certio-backups`
3. Credentials → S3 Access Key

**Récupérer** :
- `OVH_S3_ACCESS_KEY`
- `OVH_S3_SECRET_KEY`
- `OVH_S3_ENDPOINT` (ex: `https://s3.gra.cloud.ovh.net`)
- `OVH_S3_REGION` (ex: `gra`)

#### Domaine + DNS (pour prod)

Si pas déjà fait :
- Acheter `certio.app` (~25€/an) — [Namecheap](https://namecheap.com) ou OVH
- Configurer DNS :
  - A record: `certio.app` → IP VPS OVH
  - CNAME: `www.certio.app` → `certio.app`
  - TXT records: SPF, DKIM, DMARC (emails)

**Stocker les credentials dans** `backend/.env` (⚠️ gitignore) :

```env
# .env (ne JAMAIS commit)
GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxx
MICROSOFT_CLIENT_ID=xxx-xxx-xxx
MICROSOFT_CLIENT_SECRET=xxx~xxx
SMTP_PASSWORD=xxx
OVH_S3_ACCESS_KEY=xxx
OVH_S3_SECRET_KEY=xxx
APP_ENV=production
APP_DEBUG=false
ENCRYPTION_KEY=$(openssl rand -hex 32)
```

---

### 📁 1.4 Fichiers samples pour tests imports

#### Moodle XML sample
Créer `tests/fixtures/sample_moodle_quiz.xml` :
```xml
<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <question type="multichoice">
    <name><text>Question test 1</text></name>
    <questiontext format="html">
      <text><![CDATA[Quelle est la dérivée de f(x) = x² ?]]></text>
    </questiontext>
    <single>true</single>
    <answer fraction="100">
      <text>2x</text>
      <feedback><text>Correct !</text></feedback>
    </answer>
    <answer fraction="0">
      <text>x</text>
    </answer>
    <answer fraction="0">
      <text>2</text>
    </answer>
    <answer fraction="0">
      <text>x²/2</text>
    </answer>
  </question>
  <!-- 10+ questions minimum pour test -->
</quiz>
```

#### Word Aiken sample
Créer `tests/fixtures/sample_aiken.docx` avec contenu :
```
Q1. Quelle est la capitale de la France ?
A. Londres
B. Paris *
C. Madrid
D. Rome
ANSWER: B

Q2. Combien font 7 × 8 ?
A. 54
B. 56 *
C. 64
D. 49
ANSWER: B
```

#### Excel template
Créer `tests/fixtures/import_template.xlsx` avec colonnes :
| type | statement | option_A | option_B | option_C | option_D | correct_A | correct_B | correct_C | correct_D | explanation | difficulty | tags | module |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| mcq_single_4 | Q... | ... | ... | ... | ... | N | Y | N | N | ... | easy | math | Maths |

---

### 📸 1.5 Screenshots/vidéos à produire pour la doc

#### Screenshots (Phase 4)

À capturer avec CleanShot X, Snagit ou Chrome DevTools Screenshot :

**Admin** :
- Dashboard principal
- Liste utilisateurs
- Création workspace
- Page monitoring santé
- Page backups
- Page audit log
- Community moderation

**Prof** :
- Banque questions vide
- Banque questions remplie (filtres)
- Création examen étape 1 (paramètres)
- Création examen étape 2 (questions)
- Création examen étape 3 (CBM)
- Page suivi passages temps réel
- Analytics dashboard
- Export Excel

**Étudiant** :
- Page accès par code
- Page saisie infos
- Question avec CBM input
- Page confirmation soumission
- Page correction détaillée
- Téléchargement PDF

#### GIFs animés (workflow courts)
Créer avec [LICEcap](https://www.cockos.com/licecap/) (gratuit) ou Kap :
- Workflow "Créer et publier examen" (30-60s)
- Workflow "Passer examen CBM" étudiant (30-60s)
- Workflow "Consulter calibration CBM"

#### Vidéos tuto (Phase 4-5)
3-5 minutes par vidéo, scripts à préparer :

**Tutos prof** :
1. "Premiers pas : créer votre premier examen" (5min)
2. "Comprendre le CBM en 3 minutes" (3min)
3. "Analytics : exploiter les résultats" (5min)

**Tutos étudiant** :
1. "Comment passer un examen Certio" (2min)
2. "Comprendre votre correction CBM" (3min)

**Outils** :
- **Loom** (gratuit, cloud) — rapide
- **OBS Studio** (gratuit, local) — contrôle total
- **ScreenFlow / Camtasia** (payant) — montage pro
- **IA** : [HeyGen](https://heygen.com), [Synthesia](https://synthesia.io) — avatar parlant
- **Voice-over** : [ElevenLabs](https://elevenlabs.io) — TTS naturel

---

### 🎙️ 1.6 Scripts podcasts/narrations

Pour les placeholders audio de la doc interactive.

**Format** : scripts 2-3 minutes par thème.

**Thèmes suggérés** :
1. "Pourquoi le CBM change l'évaluation" (3min, à destination des profs)
2. "Comment Certio respecte votre vie privée" (2min, RGPD)
3. "L'histoire de Certio" (2min, intro générale)
4. "Les 5 erreurs à éviter en créant un QCM" (4min, pédagogique)

**Workflow production** :
1. Rédiger script texte
2. Enregistrer soi-même (Audacity) ou via ElevenLabs
3. Export MP3 + transcription
4. Embed dans page doc avec lecteur HTML5

---

## 2. Checklists de validation par phase

### ✅ Phase 0 — Fondations & Rebranding

#### Code
- [ ] `backend/config/branding.php` créé et valide
- [ ] `frontend/assets/branding.js` créé
- [ ] 13 managers skeleton créés (`php -l` OK pour chacun)
- [ ] `docs/SCHEMAS_V2.md` rédigé
- [ ] `frontend/manifest.json` valide
- [ ] `frontend/service-worker.js` présent
- [ ] `frontend/assets/i18n/fr.json` + `en.json` créés
- [ ] Dossiers `data/workspaces/`, `data/community/`, `data/audit/` créés

#### Tests
- [ ] Tous les 389 tests v1 passent encore (0 régression)
- [ ] `test_branding_manager.php` créé avec 8+ tests
- [ ] `test_managers_skeleton.php` créé

#### Rebranding
- [ ] `grep -ri "ipssi examens" --include="*.php"` → 0 résultat
- [ ] `grep -ri "ipssi" --include="*.html"` → seulement cas justifiés
- [ ] Email templates mis à jour
- [ ] README.md avec titre "Certio"
- [ ] LICENSE et copyright à jour

#### Visuel
- [ ] Page d'accueil affiche "Certio"
- [ ] Logo placeholder SVG visible
- [ ] Favicon présent
- [ ] Footer avec "© 2026 Mohamed EL AFRIT"

#### Git
- [ ] Branche `feat/p0-fondations` créée
- [ ] Commits fréquents avec Conventional Commits
- [ ] PR mergée dans `develop`
- [ ] Tag `v2.0.0-alpha.0` créé

---

### ✅ Phase 1 — CBM Core

#### Code
- [ ] `CbmManager` avec 15+ méthodes implémentées
- [ ] Tests `test_cbm_manager.php` : 30+ tests, > 90% coverage
- [ ] Endpoint `/api/cbm/*` (9 routes) fonctionnel
- [ ] Composants React : `CbmMatrixEditor`, `CbmCertaintyInput`, `CbmScoreBreakdown`, `CbmOnboardingModal`
- [ ] Presets save/load/delete fonctionnels
- [ ] Import/export JSON roundtrip validé

#### Fonctionnel
- [ ] Je peux créer matrice 3 niveaux en < 2min
- [ ] Je peux sauver un preset et le réutiliser
- [ ] Mini-tuto onboarding s'affiche au 1er passage CBM
- [ ] Score CBM affiché correctement dans correction
- [ ] Calibration (over/under/well) calculée
- [ ] Graphe Recharts fonctionne

#### Tests E2E
- [ ] Test E2E workflow complet CBM passé
- [ ] 0 régression sur examens non-CBM

#### Git
- [ ] Tag `v2.0.0-alpha.1`

---

### ✅ Phase 2 — Types de questions

#### Code
- [ ] `QuestionTypeResolver` implémenté
- [ ] 7 types supportés end-to-end
- [ ] Schéma Question v2 validé
- [ ] Migration questions v1 → v2 réussie sur 320 questions

#### UI
- [ ] `<QuestionEditor>` adapte selon type choisi
- [ ] `<QuestionRenderer>` affiche radio ou checkbox selon type
- [ ] Validation contraintes (V/F = 2 opts ex.)
- [ ] Preview live fonctionne

#### Tests
- [ ] 40+ tests QuestionTypeResolver passent
- [ ] Migration 320 questions sans erreur
- [ ] E2E avec chacun des 7 types

#### Git
- [ ] Tag `v2.0.0-alpha.2`

---

### ✅ Phase 3 — Scoring & Analytics

#### Code
- [ ] 3 modes scoring multi-réponses codés
- [ ] Combinaison CBM + multi correcte
- [ ] 4 nouveaux endpoints analytics
- [ ] Composants : `CbmCalibrationChart`, `DistractorsAnalysis`, `StudentRadar`
- [ ] Dashboard étudiant `/etudiant/dashboard.html`
- [ ] Exports CSV/Excel avec colonnes CBM

#### Validation
- [ ] Prof voit calibration de ses étudiants
- [ ] Étudiant accède à son dashboard
- [ ] Export Excel multi-feuilles OK
- [ ] Graphes lisibles et accessibles

#### Git
- [ ] Tag `v2.0.0-beta.1` 🎉 (premier beta !)

---

### ✅ Phase 4 — Documentation interactive

#### Code
- [ ] `DocumentationManager` avec RBAC strict
- [ ] Endpoint `/api/docs` fonctionnel
- [ ] `DocsViewer` React avec sidebar, main, TOC
- [ ] Système placeholders 4 types fonctionne
- [ ] Recherche full-text basique OK

#### Contenu
- [ ] 8+ pages admin
- [ ] 10+ pages prof
- [ ] 4+ pages étudiant
- [ ] 5+ pages shared
- [ ] Placeholders avec prompts IA documentés
- [ ] Pages responsives

#### RBAC
- [ ] Admin voit tout
- [ ] Prof ne voit pas section admin
- [ ] Étudiant ne voit que section étudiant

#### Git
- [ ] Tag `v2.0.0-beta.2`

---

### ✅ Phase 5 — Améliorations v2.0

#### 5A Sécurité
- [ ] 2FA TOTP fonctionne avec Google Authenticator réel
- [ ] Backup codes single-use
- [ ] Audit log capture toutes actions sensibles
- [ ] Page admin audit avec filtres
- [ ] Anti-cheat score calculé

#### 5B Multi-tenant
- [ ] Isolation stricte workspaces (test : prof A ne voit pas B)
- [ ] SSO Google fonctionne end-to-end
- [ ] SSO Microsoft fonctionne end-to-end
- [ ] Page admin workspaces
- [ ] Migration users v1 → DEFAULT OK

#### 5C LMS
- [ ] Import Moodle XML OK (sample)
- [ ] Import Word Aiken OK
- [ ] Import Excel template OK
- [ ] Export SCORM 1.2 importable dans Moodle test
- [ ] Export SCORM 2004 OK
- [ ] Swagger UI accessible

#### 5D A11y + i18n + PWA
- [ ] axe-core : 0 violation bloquante
- [ ] Lighthouse A11y ≥ 95
- [ ] Lighthouse PWA = 100
- [ ] i18n toutes strings externalisées
- [ ] Traduction EN complète
- [ ] PWA installable mobile + desktop
- [ ] Mode hors-ligne passage OK

#### 5E Community
- [ ] Publish/unpublish fonctionne
- [ ] Fork avec attribution OK
- [ ] Votes et flags OK
- [ ] Modération super-admin OK
- [ ] 100+ questions seed

#### Git (fin de P5)
- [ ] Tag `v2.0.0-rc.1` 🎊 (Release Candidate 1)

---

### ✅ Phase 6 — Tests complets

- [ ] Coverage global ≥ 85%
- [ ] 5 workflows E2E passent
- [ ] OWASP Top 10 review complet
- [ ] 0 vulnérabilité critique
- [ ] Charge 100 req/s < 200ms p95
- [ ] axe-core automatisé : 0 erreur
- [ ] Lighthouse : Perf ≥ 90
- [ ] 630+ tests total
- [ ] Rapport `docs/TEST_REPORT_V2.md`

- [ ] Tag `v2.0.0-rc.2`

---

### ✅ Phase 7 — Déploiement

#### Pré-déploiement
- [ ] `v2.0.0-rc.2` validé
- [ ] Backup prod v1 complet
- [ ] Migration testée sur copie prod (dry-run + réel)
- [ ] Release notes rédigées
- [ ] Email utilisateurs prêt
- [ ] Monitoring actif

#### Déploiement
- [ ] Mode maintenance activé
- [ ] Pull tag `v2.0.0-rc.2` sur prod
- [ ] Migration production exécutée
- [ ] Smoke tests OK
- [ ] Mode maintenance retiré
- [ ] Tag `v2.0.0` final 🎉

#### Post-déploiement (48h)
- [ ] Aucune erreur 5xx anormale
- [ ] Utilisateurs v1 peuvent se connecter
- [ ] Nouveaux examens CBM fonctionnels
- [ ] Email annonce envoyé
- [ ] Release GitHub publiée
- [ ] Post blog / LinkedIn
- [ ] README à jour

---

## 3. Checklists transverses

### 🎯 Checklist UAT (User Acceptance Testing)

Avec **3 écoles pilotes** et **10 profs testeurs**, avant release finale.

#### Testeurs pilotes
- [ ] 3 écoles identifiées (partenaires potentiels)
- [ ] 10 profs recrutés
- [ ] 50 étudiants volontaires

#### Scénarios à tester
- [ ] Prof crée compte, complète profil
- [ ] Prof configure 2FA
- [ ] Prof crée examen CBM
- [ ] Prof invite étudiants
- [ ] Étudiants passent examen (10 étudiants)
- [ ] Prof consulte résultats et analytics
- [ ] Prof exporte en CSV et Excel
- [ ] Prof publie 3 questions en communauté
- [ ] Autre prof fork une question communautaire
- [ ] Admin supervise activité

#### Retours attendus
- [ ] NPS Prof ≥ 40
- [ ] NPS Étudiant ≥ 30
- [ ] 80% des testeurs "prêt pour prod"
- [ ] Liste des bugs/améliorations documentée
- [ ] Témoignages utilisables marketing

---

### 🛡️ Checklist RGPD / Compliance

- [ ] Politique de confidentialité rédigée (`/docs/privacy.md`)
- [ ] Consentement cookies (bannière)
- [ ] Droit d'accès aux données (export JSON)
- [ ] Droit à l'oubli (suppression compte complet)
- [ ] Mentions légales
- [ ] Data Processing Agreement (DPA) template pour écoles
- [ ] Registre des traitements
- [ ] DPO identifié (ou auto-désigné)
- [ ] Chiffrement des données sensibles (TOTP secrets)
- [ ] Logs d'accès aux données persos
- [ ] Notifications de violation si nécessaire

---

### ♿ Checklist WCAG AA

#### Perceptible
- [ ] Alt text sur toutes images
- [ ] Transcriptions vidéos
- [ ] Contrastes 4.5:1 minimum (texte normal)
- [ ] Contrastes 3:1 minimum (texte large)
- [ ] Pas de dépendance à la couleur seule
- [ ] Text resize 200% sans perte

#### Utilisable
- [ ] Navigation clavier 100%
- [ ] Focus visible
- [ ] Skip links présents
- [ ] Pas de timing strict non-ajustable
- [ ] Pas de flashs épileptiques
- [ ] Titres de pages explicites
- [ ] Headings hierarchy correcte

#### Compréhensible
- [ ] Langue page déclarée (html lang)
- [ ] Navigation cohérente
- [ ] Erreurs formulaire expliquées
- [ ] Labels associés aux inputs

#### Robuste
- [ ] HTML valide (W3C)
- [ ] ARIA roles pertinents
- [ ] Compatible lecteurs d'écran NVDA, JAWS, VoiceOver

---

### 🔐 Checklist Sécurité

- [ ] bcrypt cost ≥ 12
- [ ] HMAC SHA-256 sur passages
- [ ] CSRF tokens partout (POST/PUT/DELETE)
- [ ] Rate limiting par rôle
- [ ] Session hijacking prevention
- [ ] XSS protection (DOMPurify)
- [ ] SQL injection N/A (pas de SQL)
- [ ] JSON injection tests
- [ ] File upload validation (taille, type MIME)
- [ ] Secrets dans .env (jamais en git)
- [ ] HTTPS forcé en prod
- [ ] HSTS header
- [ ] CSP (Content Security Policy)
- [ ] X-Frame-Options: DENY
- [ ] Audit log des actions sensibles
- [ ] Déconnexion automatique inactivité

---

### 🚀 Checklist SEO / Marketing (v2.1+)

- [ ] Meta tags title + description
- [ ] Open Graph tags (FB, LinkedIn)
- [ ] Twitter Cards
- [ ] Sitemap.xml
- [ ] robots.txt
- [ ] Schema.org structured data
- [ ] Pages fast loading (Core Web Vitals)
- [ ] URL rewriting propre (/exam/{slug})
- [ ] SSL valide (A+ sur SSLLabs)
- [ ] Landing page marketing (certio.app)
- [ ] Blog (content marketing)
- [ ] Comptes sociaux (LinkedIn, Twitter)
- [ ] Google Business
- [ ] Analytics (Plausible ou Matomo — RGPD-friendly)

---

## 4. Templates prêts à remplir

### 📧 Template : Email annonce v2.0

**Objet** : 🎉 Certio v2.0 est arrivé — nouvelle version de votre plateforme d'examens

---

Bonjour [PRENOM],

Nous sommes ravis de vous annoncer que votre plateforme **IPSSI Examens** devient aujourd'hui **Certio v2.0** — la première plateforme francophone d'évaluation avec **Certainty-Based Marking**.

**Ce qui change pour vous** : rien, mais tout en mieux. Vos comptes et examens existants sont automatiquement migrés. Vous pourrez vous connecter avec vos identifiants habituels sur https://certio.app.

**Les nouveautés majeures** :

🎲 **Certainty-Based Marking** — évaluez la *vraie* connaissance, pas le hasard. Configurez vos propres matrices de scoring.

🎯 **7 types de questions** — Vrai/Faux, QCM avec N options, QCM multi-réponses (checkbox). Toute la flexibilité dont vous avez besoin.

🏫 **Multi-écoles (Workspaces)** — Certio est désormais utilisable par plusieurs établissements avec isolation complète des données.

🔐 **Sécurité renforcée** — authentification à deux facteurs (2FA), audit log complet, anti-triche amélioré.

📤 **Intégrations LMS** — imports Moodle/Word/Excel, exports SCORM, LTI compatible Moodle/Canvas.

🌐 **Banque communautaire** — partagez vos questions avec d'autres profs (optionnel).

📱 **Mobile & hors-ligne** — Certio s'installe comme une app et fonctionne même sans connexion.

📚 **Documentation intégrée** — accessible directement dans votre espace admin.

**Découvrez tout ça** :
- 🚀 Connectez-vous : https://certio.app
- 📖 Documentation : https://certio.app/docs
- 🎬 Tutoriel vidéo (3 min) : [lien YouTube]
- 💬 Besoin d'aide ? Écrivez-moi : mohamed@elafrit.com

Votre feedback est précieux — n'hésitez pas à me répondre directement à cet email.

Bonne découverte !

**Mohamed EL AFRIT**  
Créateur de Certio  
mohamed@elafrit.com

P.S. — Si vous aviez apprécié IPSSI Examens v1, je pense que Certio v2 va vraiment vous surprendre. Le CBM change la donne pédagogique. 🎯

---

### 📰 Template : Release notes (CHANGELOG_V2.md)

```markdown
# Certio v2.0.0 — Release Notes
**Date de release** : [DATE]  
**Type** : Major release

## 🎯 Résumé en 1 phrase
IPSSI Examens v1 devient Certio v2.0, la première plateforme francophone d'évaluation SaaS avec Certainty-Based Marking.

## ✨ Nouveautés

### Features majeures
- 🎲 CBM 100% paramétrable
- 🎯 7 types de questions
- 🏫 Multi-tenant avec Workspaces
- 🔐 2FA TOTP
- 🌐 Banque communautaire
- 📤 Imports Moodle/Word/Excel + Exports SCORM/LTI

### Améliorations
- WCAG AA complet
- Traduction EN
- PWA installable + offline
- Documentation intégrée

## 🔄 Migration
Migration automatique depuis v1. Aucune action requise des utilisateurs.

## 📊 Stats
- 630+ tests (vs 389 en v1)
- Coverage 87%
- Lighthouse : Perf 92, A11y 97, PWA 100

## 🙏 Merci
Merci aux 3 écoles pilotes et 10 profs testeurs qui ont rendu cette release possible.

## 📝 Changelog technique détaillé
[généré automatiquement depuis git log]
```

---

### 📱 Template : Post LinkedIn

```
🎉 Je suis heureux de présenter Certio v2.0 — le résultat de 4 mois de développement intensif.

Certio est une plateforme d'évaluation QCM en ligne avec une particularité qui la rend unique dans le paysage francophone :

🎲 Le Certainty-Based Marking (CBM).

Concrètement : après chaque réponse, l'étudiant indique son niveau de certitude. Le score final récompense non seulement l'exactitude, mais aussi l'honnêteté intellectuelle.

Résultat ? 
✅ Moins de réponses au hasard
✅ Meilleur développement métacognitif
✅ Évaluation plus juste

Les autres features de Certio v2.0 :
🏫 Multi-écoles (Workspaces isolés)
🔐 2FA + audit log + anti-triche IA
📤 Imports Moodle/Word/Excel, exports SCORM
🌐 Banque de questions communautaire
♿ WCAG AA + PWA installable

Stack technique :
⚙️ PHP 8.3 natif (zéro dépendance Composer)
⚛️ React 18 via CDN (zéro bundler)
💾 Fichiers JSON (zéro SQL)
🧪 630+ tests automatisés

Certio est disponible sur https://certio.app — gratuit pour essayer.

Merci aux écoles pilotes qui ont testé en avant-première 🙏

#Edtech #CBM #EdTechFrance #Open #Education #SaaS #Evaluation

---

[Lien vers certio.app]
[Image de la plateforme]
```

---

### 📝 Template : Post blog (format long)

```markdown
# Certio v2.0 : repenser l'évaluation à l'ère de l'honnêteté intellectuelle

[cover image]

## Pourquoi j'ai créé Certio

En 2025, alors que j'enseignais un module de Mathématiques pour l'IA à l'IPSSI, j'ai réalisé quelque chose : mes étudiants répondaient au hasard.

Pas par paresse, mais parce que le QCM classique **récompense le hasard** : 25% de chance d'avoir juste sur 4 options. Un étudiant qui sait peut rater, un étudiant qui ignore peut réussir. Où est la pédagogie ?

## La découverte du CBM

Un jour, je tombe sur un papier de recherche : le **Certainty-Based Marking** (Gardner-Medwin, 2006). L'idée est simple mais géniale :

> "Demande à l'étudiant s'il est sûr de sa réponse. Récompense l'honnêteté, punis la surconfiance."

Exemple concret :
- Étudiant A : répond juste en disant "pas sûr" → +1 point
- Étudiant B : répond juste en disant "certain" → +3 points
- Étudiant C : répond faux en disant "certain" → -3 points

L'étudiant C a menti à lui-même. Il sera sanctionné. L'étudiant A, honnête, est récompensé modérément. Le système encourage la **métacognition** : connaître ce que l'on sait.

## De IPSSI Examens à Certio

J'ai d'abord développé une plateforme interne pour IPSSI. Solide, 389 tests, mais... limitée à une école.

Après analyse du marché, un constat :
- Moodle est lourd, pas de CBM natif
- Wooclap est cher, orienté live
- Aucun acteur francophone n'offre CBM en SaaS

D'où Certio v2.0 — que j'annonce aujourd'hui.

## Les 7 piliers de Certio v2.0

### 1. CBM 100% paramétrable
Vous choisissez votre matrice de scoring. 2 à 10 niveaux de certitude. Libre.

### 2. Multi-tenant
Chaque école est isolée. SSO Google/Microsoft. Branding par école.

### 3. 7 types de questions
Vrai/Faux, QCM N radio, QCM N checkbox. 3 modes scoring multi-réponses.

### 4. Sécurité
2FA, audit log, anti-triche avec score de confiance.

### 5. Intégrations LMS
Import Moodle/Word/Excel, export SCORM/LTI. Compatible Moodle, Canvas.

### 6. Banque communautaire
Partagez vos questions (optionnel). Fork avec attribution. Rating.

### 7. Accessibilité
WCAG AA. PWA mobile. Mode hors-ligne.

## La philosophie technique

J'ai choisi de garder Certio **simple et souverain** :
- ⚙️ PHP 8.3 natif (pas de Composer)
- ⚛️ React 18 CDN (pas de bundler Webpack)
- 💾 Fichiers JSON (pas de SQL obligatoire)
- 🇫🇷 Hébergé OVH France, RGPD-first

Résultat : **déployable en 5 minutes sur n'importe quel hébergeur PHP**. Pas besoin d'équipe DevOps.

## Essayez Certio

- 🚀 https://certio.app
- 📚 Documentation intégrée
- 💬 mohamed@elafrit.com

Si vous êtes enseignant, formateur, ou responsable pédagogique, j'aimerais votre retour.

Certio est en **CC BY-NC-SA 4.0** — utilisable gratuitement en contexte éducatif.

---

**Mohamed EL AFRIT**  
Créateur de Certio  
Enseignant-formateur, Consultant SI
```

---

### 🎤 Template : Feedback pilotes

Google Form à envoyer aux testeurs pilotes :

```
# Feedback Certio v2.0 — Testeurs pilotes

Merci d'avoir testé Certio v2.0 ! Votre retour nous aidera à améliorer la plateforme avant la release publique.

## Section 1 : Vous

1. Votre rôle : [Prof / Étudiant / Admin]
2. Votre école : [texte]
3. Combien d'heures avez-vous testé ? [<2h / 2-5h / 5-10h / >10h]

## Section 2 : NPS (Net Promoter Score)

4. Sur une échelle de 0 à 10, recommanderiez-vous Certio à un collègue ? [slider 0-10]
5. Pourquoi cette note ? [texte long]

## Section 3 : Features

6. Quelles features avez-vous utilisées ? [checkbox]
   - Création examen
   - CBM
   - Analytics
   - Import/Export
   - SSO
   - Community
   - 2FA
   - Documentation

7. Note par feature (1-5 étoiles) :
   - Création examen : ___
   - CBM : ___
   - Analytics : ___
   - UI/UX général : ___
   - Performance : ___
   - Documentation : ___

## Section 4 : Problèmes rencontrés

8. Avez-vous rencontré des bugs ? [Oui/Non]
9. Si oui, lesquels ? [texte long]
10. Quelles features manquent à votre avis ? [texte long]

## Section 5 : Verbatim

11. Ce que vous avez le PLUS aimé : [texte]
12. Ce qui vous a le plus frustré : [texte]
13. Une suggestion d'amélioration majeure ? [texte]

## Section 6 : Témoignage (optionnel)

14. Acceptez-vous qu'on cite votre feedback (anonymisé ou nommé) pour communication ? [Oui/Non]
15. Si oui, votre témoignage en 2-3 phrases : [texte]

Merci infiniment ! 🙏
```

---

## 5. Guide de suivi projet

### 📅 Rituels recommandés

#### Rituel quotidien (15 min, début de session dev)

1. **Relecture du plan** (2 min)
   - Quelle phase en cours ?
   - Quelle tâche du jour ?
2. **Pull dernières changes** (1 min)
   ```bash
   git fetch --all
   git pull --rebase
   ```
3. **Review todo list** (5 min)
   - Qu'est-ce que j'ai fini hier ?
   - Qu'est-ce que je vise aujourd'hui ?
   - Quels blockers ?
4. **Lancer tests baseline** (2 min)
   ```bash
   php backend/tests/run_all.php
   ```
5. **C'est parti !** (5 min)
   - Checkout branche de phase
   - Ouvrir prompt IA pertinent
   - Premier commit du jour

#### Rituel fin de journée (10 min)

1. **Commit WIP** si travail pas fini
2. **Push** sur remote
3. **Update journal** dans `docs/DEV_JOURNAL.md`
   ```markdown
   ## 2026-05-15
   - ✅ Implémenté CbmManager::calculateScore()
   - ✅ 15 nouveaux tests passent
   - 🚧 WIP sur UI CbmMatrixEditor
   - 🐛 Bug trouvé : validateMatrix ne gère pas levels vides
   - 🎯 Demain : finir UI + tests E2E CBM
   ```
4. **Update tableau Kanban** si utilisé

#### Rituel hebdomadaire (30 min, dimanche soir)

1. **Bilan semaine**
   - Tâches terminées
   - Blockers résolus
   - Progression % dans la phase
2. **Ajustement planning**
   - Réel vs estimé
   - Décaler si besoin
3. **Review métriques**
   - Coverage tests
   - Nombre de commits
   - LOC ajoutées
4. **Célébrer les wins** 🎉
   - Prendre une pause
   - Partager progrès (social media, pair)

---

### 📊 KPIs à tracker

Créer `docs/PROJECT_DASHBOARD.md` (mis à jour hebdo) :

```markdown
# Certio v2.0 — Project Dashboard

## Week X / 16

### 📊 Progression globale
- Phase en cours : P3 (Scoring & Analytics)
- Progression phase : ██████░░░░ 60%
- Progression projet : ████░░░░░░ 40%

### 🎯 KPIs techniques
- Tests totaux : 487 (+36 cette semaine)
- Coverage : 86.2%
- Lignes de code : +2341
- Commits : 23

### ⏱️ Temps passé
- Total projet : 22j / 36j estimés
- Cette semaine : 4 jours
- Moyenne : 2.2j/semaine

### 🐛 Bugs
- Nouveaux : 3
- Résolus : 5
- En cours : 2

### ✨ Wins de la semaine
- CBM scoring combiné fonctionnel
- Premier beta (v2.0.0-beta.1) taggé
- Dashboard étudiant validé

### 🚧 Blockers
- Intégration SheetJS pour export Excel : complexité sous-estimée
```

---

### 🗂️ Board Kanban suggéré

Utiliser **GitHub Projects** (intégré au repo) ou **Trello** :

**Colonnes** :
1. 📋 **Backlog** — toutes les tâches futures
2. 🎯 **Cette phase** — tâches de la phase active
3. 🏗️ **En cours** — 1-2 tâches max en WIP
4. 👀 **À tester** — tâches codées mais pas validées
5. ✅ **Done** — tâches terminées

**Labels** :
- `phase-0` à `phase-7` — identification phase
- `bug`, `feature`, `refactor`, `docs`, `test`
- `priority-high`, `priority-medium`, `priority-low`
- `blocked`, `needs-review`, `ready`

**Workflow** :
- Créer une **Issue GitHub** pour chaque tâche importante
- Linker PR → Issue
- Fermer Issue à merge PR
- Auto-move dans colonne "Done"

---

## 6. Gestion des risques en continu

### 🚨 Matrice de risques active

Maintenir `docs/RISK_LOG.md` à jour :

```markdown
# Risk Log — Certio v2.0

## R1 : Migration v1→v2 corrompt données
- **Probabilité** : Moyen → Faible (après tests)
- **Impact** : Critique
- **Mitigation** : ✅ Script dry-run + rollback automatique
- **Status** : Sous contrôle

## R2 : Performance dégradée avec workspaces
- **Probabilité** : Moyen
- **Impact** : Élevé
- **Mitigation** : Benchmarks en Phase 6 + optimisation si besoin
- **Status** : À surveiller

## R14 : Scope creep
- **Probabilité** : Élevé
- **Impact** : Élevé
- **Mitigation** : Stick to scope defined, defer to v2.1
- **Status** : ⚠️ Actif — tentation Community v2 en sus

[etc...]
```

### 🛑 Red flags à surveiller

Indicateurs d'alerte qui nécessitent réaction immédiate :

1. **Tests qui régressent** : plus de 5% des tests qui passaient échouent → investiguer
2. **Performance qui se dégrade** : API latence p95 > 300ms → profiler
3. **Coverage qui chute** : coverage < 80% → ajouter tests
4. **Retard de phase** : > 30% de retard → ré-estimer ou réduire scope
5. **Démotivation** : 3 jours sans commit → pause + réflexion
6. **Questions ouvertes** : blocage > 1j sur une question → demander aide / itérer

### 🔥 Protocole "Mode crise"

Si un bug critique apparaît en prod (post-release) :

1. **STOP** tout dev en cours
2. **Créer branche `hotfix/vX.X.X-bug-description`** depuis `main`
3. **Fixer** + tester localement
4. **Deploy hotfix** en prod rapidement
5. **Tag** `v2.0.1` (patch release)
6. **Backport** dans `develop`
7. **Post-mortem** : pourquoi le bug est passé les tests ? → ajouter test de régression

---

## 7. Plan de communication

### 📢 Pré-lancement (2 semaines avant v2.0)

| Semaine | Actions |
|:-:|---|
| **-2** | Annonce "teasing" sur LinkedIn + Twitter |
| | Email pilotes : "v2.0 arrive le [DATE]" |
| **-1** | Blog post : "Pourquoi j'ai créé Certio" |
| | Demo vidéo 2min |
| | Préparer landing page certio.app |

### 🚀 Jour J (release)

- ☑️ Email annonce aux utilisateurs v1
- ☑️ Post LinkedIn (avec screenshots)
- ☑️ Post Twitter/X (thread 5-10 tweets)
- ☑️ Update README GitHub
- ☑️ Blog post long format
- ☑️ Mise à jour site certio.app
- ☑️ Notification in-app pour users connectés

### 📈 Post-lancement (mois 1)

- Semaine 1 : monitoring intensif + répondre aux users
- Semaine 2 : post "Retour d'expérience : 1 semaine de Certio v2"
- Semaine 3 : article "Qu'est-ce que le CBM ?" (SEO)
- Semaine 4 : bilan + annonce roadmap v2.1

### 🎤 Opportunités futures

- **ProductHunt launch** (après validation pilotes)
- **Conférences EdTech** (Learning Expo, EduSpot, SETT)
- **Podcast** invité (podcasts EdTech FR)
- **Interview presse** (Educpros, Le Monde Campus)
- **Ateliers** gratuits pour profs (Zoom 1h "Découvrir CBM")

---

## 8. Synthèse finale du chantier

### 📦 Ce que tu as maintenant

**5 livrables complets** dans le dossier `examens/docs/certio_v2/` :

| # | Livrable | Contenu | Lignes |
|:-:|---|---|:-:|
| 1 | Note de cadrage | Vision, scope, architecture, KPIs, risques | 878 |
| 2 | Planning 8 phases | Timeline, Gantt, Git flow, checkpoints | 1026 |
| 3 | Prompts VS Code P0-P4 | Fondations, CBM, Questions, Scoring, Docs | 1899 |
| 4 | Prompts VS Code P5-P7 | Sécu, Multi-tenant, LMS, A11y, Community, Tests, Déploiement | 2055 |
| 5 | Kit inputs + checklists | Assets, credentials, templates, rituels | en cours |
| | **TOTAL** | **Documentation projet complète** | **~6800** |

### 🎯 Pour démarrer dès demain

**Jour 1 (dans VS Code) :**
1. ✅ Ouvrir `/examens/docs/certio_v2/03_PROMPTS_VSCODE_P0_P4.md`
2. ✅ Copier le **Prompt Phase 0**
3. ✅ Créer branche `feat/p0-fondations`
4. ✅ Coller le prompt dans Claude Code / Cursor / Copilot Agent
5. ✅ Commencer par la **Tâche 1 (branding config)**
6. ✅ Commit et review, passer à la tâche 2
7. ✅ Répéter jusqu'à finition P0 (~3 jours)

**Semaine 1 :**
- Tag `v2.0.0-alpha.0` après P0
- Démarrer P1 (CBM Core)

**Mois 1 :**
- Phases P0, P1 terminées
- Premier beta

**Mois 4 :**
- v2.0.0 en production 🎉

### 💎 Ce qui rend ce plan solide

1. **Tout est écrit** : zéro blocage "que faire maintenant ?"
2. **Prompts autosuffisants** : l'IA comprend sans contexte supplémentaire
3. **Checkpoints clairs** : chaque phase a sa Definition of Done
4. **Git flow strict** : traçabilité complète
5. **Tests en continu** : qualité garantie
6. **Scope bien délimité** : pas de scope creep
7. **Migration gérée** : pas de perte de données v1

### 🚀 Après v2.0 : roadmap v2.1+

Une fois v2.0 en prod et stable (3-6 mois), considérer :

**v2.1 (Q4 2026 - Q1 2027)** :
- 🤖 Génération IA de questions (OpenAI/Claude API)
- 🎯 Mode adaptatif IRT (Item Response Theory)
- 📹 Support vidéo/audio dans questions
- 🔗 Webhooks externes
- 💳 Plans tarifaires + Stripe
- 📊 Monitoring Grafana/Prometheus

**v2.2 (Q2-Q3 2027)** :
- 🎥 Proctoring webcam (optionnel)
- 🤝 Co-correction multi-profs
- 🐳 Docker production
- 🌍 Langues supplémentaires (ES, AR)

**v3.0 (2028)** :
- 📱 App mobile native (iOS + Android)
- 🗄️ Migration optionnelle vers PostgreSQL
- 🧠 Coaching IA individuel
- 🌐 Marketplace premium (questions payantes)

### 🙏 Mot de la fin

Tu as entre les mains un **plan complet, testé et structuré** pour construire un produit SaaS à partir d'une base solide.

Les 6800+ lignes de documentation ne sont pas juste des pages — c'est une **roadmap exécutable** qui t'évite :
- ❌ La paralysie "par où commencer ?"
- ❌ Les oublis techniques
- ❌ Le scope creep
- ❌ Les erreurs classiques de refactoring
- ❌ Les problèmes de gestion de projet solo

Avec ça + un assistant IA (Claude Code, Cursor, Copilot) + 2-4 jours par semaine, tu peux livrer **Certio v2.0 en 3-4 mois**.

Et surtout : **amuse-toi**. Construire un produit est une aventure exceptionnelle. Célèbre chaque phase, chaque tag, chaque feature qui marche.

Tu construis quelque chose de **rare en France** : une plateforme éducative souveraine, open, avec une vraie différenciation pédagogique (le CBM).

Le monde de l'éducation a besoin de Certio. Fonce ! 🚀

---

## 🎊 Fin des 5 livrables de cadrage

**Certio v2.0 est officiellement cadré.**

Bonne construction ! Et quand tu auras livré v2.0, raconte-moi — j'adore voir les projets aboutir.

---

© 2026 Mohamed EL AFRIT — mohamed@elafrit.com  
Certio v2.0 — Certainty-Based Assessment Platform  
Licence : CC BY-NC-SA 4.0

**"La vraie mesure d'une connaissance, c'est la conscience qu'on en a."**
