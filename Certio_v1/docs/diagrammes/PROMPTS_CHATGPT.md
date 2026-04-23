# 🎨 Prompts ChatGPT pour Schémas & Diagrammes

> **Pack complet de prompts optimisés** pour générer automatiquement tous les
> schémas d'architecture et diagrammes UML de la plateforme IPSSI Examens.

**Comment utiliser ce document** :
1. Choisir le prompt adapté à votre besoin (section 1 à 15)
2. **Copier-coller** le prompt dans ChatGPT (GPT-4, GPT-4o, GPT-5)
3. ChatGPT retournera du code **Mermaid**, **PlantUML** ou **DOT/Graphviz**
4. Rendre le diagramme sur :
   - **Mermaid** : https://mermaid.live/
   - **PlantUML** : https://www.plantuml.com/plantuml/
   - **DOT** : https://dreampuf.github.io/GraphvizOnline/
   - Ou export direct PNG/SVG depuis ces sites

---

## 📖 Table des matières

### 🏛️ Schémas d'architecture (1-8)
1. [Architecture globale (high-level)](#1-architecture-globale-high-level)
2. [Architecture détaillée en couches](#2-architecture-détaillée-en-couches)
3. [Diagramme de déploiement OVH](#3-diagramme-de-déploiement-ovh)
4. [Flux de données — Création examen](#4-flux-de-données--création-examen)
5. [Flux de données — Passage étudiant](#5-flux-de-données--passage-étudiant)
6. [Flux de données — Analytics](#6-flux-de-données--analytics)
7. [Architecture sécurité (6 couches)](#7-architecture-sécurité-6-couches)
8. [Topologie réseau production](#8-topologie-réseau-production)

### 📐 Diagrammes UML (9-15)
9. [Diagramme de classes complet](#9-diagramme-de-classes-complet)
10. [Diagramme de classes — Managers](#10-diagramme-de-classes--managers)
11. [Diagramme de séquence — Passage examen](#11-diagramme-de-séquence--passage-examen)
12. [Diagramme de séquence — Authentification](#12-diagramme-de-séquence--authentification)
13. [Diagramme d'états — Examen](#13-diagramme-détats--examen)
14. [Diagramme d'états — Passage](#14-diagramme-détats--passage)
15. [Diagramme de cas d'utilisation](#15-diagramme-de-cas-dutilisation)

### 🎁 Bonus (16-20)
16. [ERD — Modèle de données (fichiers JSON)](#16-erd--modèle-de-données-fichiers-json)
17. [Diagramme de composants React](#17-diagramme-de-composants-react)
18. [Workflow CI/CD GitHub Actions](#18-workflow-cicd-github-actions)
19. [Diagramme des rôles et permissions](#19-diagramme-des-rôles-et-permissions)
20. [Activity Diagram — Workflow complet examen](#20-activity-diagram--workflow-complet-examen)

---

## 🧠 Contexte à inclure dans chaque prompt

**IMPORTANT** : Tous les prompts ci-dessous commencent par ce bloc de contexte.
Ne pas l'omettre — c'est lui qui garantit la cohérence des schémas.

```
Je dispose d'une plateforme web d'examens en ligne développée pour IPSSI
(école d'informatique). Voici ses caractéristiques principales :

- **Nom** : IPSSI Examens
- **Version** : 1.0.0 (389 tests backend, 100% passing)
- **Stack backend** : PHP 8.3 natif (sans Composer, sans SQL)
- **Stack frontend** : React 18 (via CDN) + Babel Standalone + KaTeX + Recharts
- **Persistance** : Fichiers JSON (pas de base de données)
- **Hébergement cible** : OVH (mutualisé ou VPS Ubuntu/Nginx/PHP-FPM)

**Rôles utilisateurs** :
- admin : tous droits + monitoring + backups (rate limit illimité)
- enseignant : création examens + analytics (500 req/min)
- étudiant : passage d'examens par code (60 req/min)
- anonyme : avant connexion (30 req/min)

**Composants backend principaux** :
- Auth (bcrypt cost 12)
- Csrf (tokens base64url 32 chars)
- Session (PHP natif file-based)
- BanqueManager (320 questions en JSON)
- ExamenManager (CRUD + publish/close)
- PassageManager (UUID tokens, signature HMAC SHA-256)
- AnalyticsManager (KPIs, distribution scores, distracteurs)
- BackupManager (tar.gz + SHA-256, rotation 14)
- HealthChecker (7 checks : disk/memory/fs/counters/backups/logs/php)
- RateLimiter + RoleRateLimiter (sliding window 60s)
- Mailer (SMTP + templates HTML)
- EmailTemplate
- Logger
- FileStorage
- Response

**Endpoints API REST** (routes /api/*) :
- /api/health (+?detailed=1)
- /api/auth : login, logout, me
- /api/banque : CRUD questions
- /api/examens : CRUD + publish + close
- /api/passages : start, saveAnswer, focus-event, submit
- /api/corrections : get par token + PDF
- /api/analytics : overview, scores, questions, timeline, focus-heatmap, passages
- /api/backups : list, stats, create, verify, download, delete
- /api/comptes : CRUD users (admin only)

**Entités (fichiers JSON)** :
- Examen (EXM-XXXX-YYYY)
- Passage (PSG-XXXX-YYYY + UUID token)
- Compte (USR-xxxx)
- Question (module-difficulte-num, ex: vec-faci-01)

**Sécurité** :
- 6 couches : rate limiting → auth → CSRF → validation → HMAC → escape HTML
- Path traversal refusé via regex strictes
- Signatures HMAC SHA-256 sur passages
- Sessions PHP file-based

**Pipeline CI/CD** :
- GitHub Actions (tests.yml + lint.yml)
- Matrix PHP 8.2 + 8.3
- 17 suites de tests, 389 tests, 4 catégories
```

---

## 1. Architecture globale (high-level)

### 🎯 Objectif
Vue d'ensemble du système en **une seule image**, accessible à un non-technique.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE CI-DESSUS]

🎨 TÂCHE :
Génère un diagramme d'architecture HAUT NIVEAU (high-level) en utilisant la
syntaxe Mermaid (flowchart), adapté pour être inclus dans une documentation
professionnelle.

📋 EXIGENCES :
1. Montrer les 3 grandes couches : Client / Serveur / Données
2. Représenter les 3 types d'utilisateurs (admin, prof, étudiant) avec des icônes
3. Montrer le routeur PHP (index.php) comme point central
4. Représenter les principaux managers backend (max 6 pour garder lisible)
5. Montrer le stockage filesystem (data/)
6. Afficher le cron de backup comme élément externe
7. Utiliser des couleurs distinctes pour chaque couche :
   - Client : bleu clair
   - Backend : orange
   - Data : vert
   - Cron/externe : gris
8. Flèches orientées montrant le sens des requêtes (HTTPS)
9. Ajouter un titre en haut : "IPSSI Examens - Architecture globale v1.0.0"

📏 FORMAT DE SORTIE :
- Code Mermaid valide (flowchart LR ou TB selon ce qui rend le mieux)
- Pas de commentaire en dehors du code
- Précédé d'un titre markdown : ## Architecture globale IPSSI Examens

🧪 CONTRAINTES :
- Maximum 20 nœuds
- Lisible même imprimé en A4
- Respecter les noms exacts des composants (Auth, ExamenManager, etc.)
```

---

## 2. Architecture détaillée en couches

### 🎯 Objectif
Vue technique précise pour développeurs avec **tous les composants**.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un diagramme d'architecture DÉTAILLÉ en 5 couches horizontales, utilisant
la syntaxe Mermaid (flowchart TB) avec des subgraphs.

📋 EXIGENCES - Les 5 couches à représenter (de haut en bas) :

**Couche 1 - PRÉSENTATION (Frontend)**
- Pages HTML : admin/banque, admin/examens, admin/analytics, admin/monitoring,
  etudiant/passage, etudiant/correction
- Composants JSX : analytics.jsx, monitoring.jsx, etc.
- Assets : main.css, components-base.js, components-advanced.js, hooks.js
- Libs CDN : React 18, Babel Standalone, KaTeX, Recharts

**Couche 2 - ROUTING**
- index.php (router principal)
- Middleware : Rate Limiting (RoleRateLimiter)
- Middleware : CSRF check
- Routes spéciales : /health, /api/*

**Couche 3 - API**
- Modules : auth, banque, comptes, examens, passages, corrections, analytics, backups
- Response::json() / Response::error()

**Couche 4 - MÉTIER (Managers)**
- Auth, Csrf, Session, Logger
- BanqueManager, ExamenManager, PassageManager
- AnalyticsManager, BackupManager, HealthChecker
- Mailer, EmailTemplate, RateLimiter

**Couche 5 - PERSISTANCE**
- FileStorage
- Data dirs : examens/, passages/, comptes/, banque/, sessions/, backups/, logs/, _ratelimit/

📏 FORMAT DE SORTIE :
- Code Mermaid flowchart TB avec 5 subgraphs nommés
- Utiliser des couleurs par couche (ex: classDef pour chacune)
- Connexions verticales entre couches avec flèches
- Titre "## Architecture en couches - IPSSI Examens"

🧪 CONTRAINTES :
- Tous les composants listés doivent apparaître
- Utiliser des noms courts mais reconnaissables dans les boîtes
- Lisible sur un écran 1920x1080
- Si trop de nœuds, regrouper par style (ex: "8 managers" dans une seule boîte annotée)
```

---

## 3. Diagramme de déploiement OVH

### 🎯 Objectif
Topologie complète **production sur OVH VPS**.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un diagramme de DÉPLOIEMENT PRODUCTION sur OVH VPS en utilisant la
syntaxe Mermaid (flowchart LR) avec des subgraphs représentant les "zones".

📋 EXIGENCES :

**Zone 1 - INTERNET / CLIENTS**
- Utilisateurs (admin, prof, étudiants) avec icône 👤
- Navigateurs modernes (Chrome, Firefox, Safari, Edge)
- DNS OVH (zone examens-ipssi.fr)
- Connexion HTTPS obligatoire

**Zone 2 - OVH VPS (Ubuntu 22.04)**
Infrastructure :
- UFW Firewall (ports 22, 80, 443)
- Fail2ban (anti-bruteforce SSH)
- Certbot (Let's Encrypt auto-renew)

Services :
- Nginx (reverse proxy, SSL termination, headers sécurité)
- PHP-FPM 8.3 (via unix socket)
- Cron système (backup quotidien 03:00)

Application :
- /home/ipssi/maths_IA_niveau_1/examens/
- backend/public/index.php (entry point)
- data/ (storage)

**Zone 3 - OVH EMAIL**
- Plan Email Pro OVH
- Boîte noreply@examens-ipssi.fr
- SMTP ssl0.ovh.net:465 (SSL)
- Configuration SPF + DKIM

**Zone 4 - STOCKAGE EXTERNE (offsite backup)**
- OVH Object Storage (S3-compatible)
- Bucket ipssi-backups
- Endpoint : storage.gra.cloud.ovh.net
- Sync quotidien via rclone ou aws-cli

**Zone 5 - MONITORING EXTERNE**
- UptimeRobot (ping /api/health toutes les 5 min)
- Alertes email + SMS admin
- GitHub Actions (CI/CD sur push)

📊 FLUX À REPRÉSENTER :
1. Utilisateur → DNS OVH → VPS:443 (HTTPS)
2. Nginx → PHP-FPM (FastCGI socket)
3. PHP → data/ (read/write)
4. PHP → SMTP OVH (envoi emails)
5. Cron → backup.sh → data/backups/
6. rclone → OVH Object Storage (offsite)
7. UptimeRobot → /api/health (ping)
8. GitHub Actions → repo (CI)

📏 FORMAT DE SORTIE :
- Mermaid flowchart LR avec subgraphs
- Classification visuelle : zones avec fond coloré différent
- Annotations sur les flèches (protocole + port)
- Titre "## Déploiement production OVH - IPSSI Examens"

🧪 CONTRAINTES :
- Tous les composants sécurité visibles (UFW, Fail2ban, Certbot)
- Ports explicitement notés (443, 465, 22, etc.)
- Flux offsite backup obligatoire
```

---

## 4. Flux de données — Création examen

### 🎯 Objectif
Diagramme de **séquence** pour le parcours "création d'un examen par un prof".

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un diagramme de SÉQUENCE détaillé pour le workflow "Création et publication
d'un examen par un enseignant", utilisant la syntaxe Mermaid (sequenceDiagram).

📋 ACTEURS À REPRÉSENTER :
1. **Prof** (utilisateur)
2. **Navigateur** (React)
3. **index.php** (router + middleware)
4. **api/examens.php** (endpoint)
5. **Auth** (verif session)
6. **Csrf** (validation token)
7. **RoleRateLimiter** (middleware)
8. **ExamenManager** (logique métier)
9. **FileStorage** (persistance)
10. **Logger** (logs)

📋 SCÉNARIO À MODÉLISER :

**Phase 1 - Chargement de la page**
- Prof ouvre /admin/examens.html
- Navigateur charge React + fetch /api/auth/me pour vérifier session
- Navigateur charge /api/banque (liste questions)

**Phase 2 - Formulaire de création**
- Prof clique "+ Nouvel examen"
- Navigateur affiche modal avec formulaire
- Prof remplit : titre, questions sélectionnées, durée, dates, options

**Phase 3 - Soumission**
- Prof clique "Créer"
- Navigateur POST /api/examens avec JSON + X-CSRF-Token + Cookie session
- index.php :
  * Check rate limit (RoleRateLimiter.check('enseignant', 'user:ID'))
  * Ajoute headers X-RateLimit-*
  * Délègue à api/examens.php
- api/examens.php :
  * Csrf::requireValid()
  * Auth::requireRole('enseignant')
  * Parse body JSON
  * Appelle ExamenManager::create(data, profId)
- ExamenManager::create() :
  * Valide données (titre non vide, questions existent, dates cohérentes)
  * Génère EXM-XXXX-YYYY unique
  * Génère access_code ABC23K-9P
  * FileStorage::write('examens/EXM-xxxx.json', data)
  * Logger::info('Examen créé EXM-xxxx par USR-prof')
  * Retourne objet Examen créé

**Phase 4 - Publication**
- Prof clique "Publier"
- Navigateur POST /api/examens/EXM-xxxx/publish
- ExamenManager::publish() change status draft → published
- Retour avec access_code affiché

**Phase 5 - Affichage**
- Navigateur affiche "Examen publié - Code: ABC23K-9P"
- Toast de succès

📏 FORMAT DE SORTIE :
- Mermaid sequenceDiagram avec :
  * participant pour chaque acteur
  * Notes explicatives sur les étapes importantes (Note right of ...)
  * loop/alt pour les cas alternatifs si pertinent
  * activate/deactivate pour montrer les appels imbriqués
- Titre "## Séquence - Création et publication d'un examen"

🧪 CONTRAINTES :
- Inclure TOUS les acteurs listés
- Mentionner les headers HTTP clés (X-CSRF-Token, X-RateLimit-*)
- Montrer le chemin des données (JSON → fichier)
- Afficher les codes retour HTTP (200, 401, 403, 429 si applicable)
```

---

## 5. Flux de données — Passage étudiant

### 🎯 Objectif
Parcours **étudiant** complet avec anti-triche.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un diagramme de SÉQUENCE pour le workflow "Passage d'un examen par un
étudiant", de la saisie du code à la consultation de la correction, en utilisant
la syntaxe Mermaid (sequenceDiagram).

📋 ACTEURS :
1. **Étudiant**
2. **Navigateur** (passage.html + JSX)
3. **index.php**
4. **api/passages.php**
5. **api/corrections.php**
6. **ExamenManager**
7. **PassageManager**
8. **Mailer**
9. **data/** (filesystem)

📋 SCÉNARIO COMPLET :

**Phase 1 - Connexion à l'examen**
- Étudiant ouvre /etudiant/passage.html
- Saisit code d'accès "ABC23K-9P"
- POST /api/passages/access avec {access_code}
- ExamenManager::getByAccessCode() retourne l'examen si :
  * status = 'published'
  * date_ouverture <= now
  * date_cloture > now
- Retour : {titre, duree, nb_questions, instructions}

**Phase 2 - Infos personnelles et start**
- Étudiant remplit {nom, prenom, email}
- POST /api/passages/start avec infos + access_code
- PassageManager::start() :
  * Vérifie max_passages (anti-doublon par email)
  * Génère PSG-XXXX-YYYY + UUID token
  * Shuffle questions (si configuré)
  * Shuffle options A/B/C/D (si configuré)
  * Écrit data/passages/PSG-xxxx.json (status: in_progress)
  * start_time = now
- Retour : {token, question_order, questions détaillées sans réponses}

**Phase 3 - Passage de l'examen (boucle)**
- Pour chaque question répondue :
  - Étudiant clique une option
  - POST /api/passages/answer avec {token, question_id, answer_index}
  - PassageManager::saveAnswer() met à jour le JSON
- Détection focus events :
  - copy/paste/blur/devtools_open
  - POST /api/passages/focus-event avec {token, type, timestamp, duration}
  - PassageManager::logFocusEvent() ajoute à focus_events[]

**Phase 4 - Soumission**
- Étudiant clique "Soumettre" (ou timeout auto)
- POST /api/passages/submit avec {token}
- PassageManager::submit() :
  * Compare answers avec bonnes réponses
  * Calcule score_brut, score_max, score_pct
  * Calcule duration_sec
  * Génère signature_sha256 (HMAC)
  * status: in_progress → submitted
  * end_time = now
- Envoie email via Mailer :
  * EmailTemplate::etudiant_submission()
  * SMTP → email étudiant
  * Contient lien correction avec token
- Retour : {score_pct, correction_url}

**Phase 5 - Consultation correction**
- Étudiant clique lien dans email OU bouton direct
- GET /api/corrections/{token}
- PassageManager::getByToken() vérifie validité
- Retourne correction détaillée avec explications
- Option : GET /api/corrections/{token}/pdf pour télécharger PDF

📏 FORMAT DE SORTIE :
- sequenceDiagram Mermaid
- Utiliser loop pour la phase 3 (répétée)
- Notes pour expliquer les vérifications de sécurité
- Mentionner les codes d'erreur possibles (404 code invalide, 403 limite atteinte)
- Titre "## Séquence - Passage étudiant de A à Z"

🧪 CONTRAINTES :
- Les 5 phases doivent être clairement séparées (utiliser des notes "Note over")
- Inclure les champs techniques clés (token UUID, signature HMAC)
- Montrer l'auto-save
- Indiquer les focus_events
- Max 50 lignes dans le code Mermaid
```

---

## 6. Flux de données — Analytics

### 🎯 Objectif
Montrer comment les **analytics** sont calculées côté serveur.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un diagramme de FLUX (data flow diagram) pour le module Analytics,
utilisant la syntaxe Mermaid (flowchart LR).

📋 SOURCES DE DONNÉES :
- data/passages/*.json (tous les passages)
- data/examens/*.json (métadonnées examens)
- data/banque/*/*.json (questions avec bonnes réponses)

📋 PROCESSUS À MONTRER :

**Entrée**
- Prof demande /api/analytics/examen/{id}/overview

**Étape 1 - Chargement**
- AnalyticsManager::loadPassages(examenId)
  * glob data/passages/*.json
  * Filtrer par examen_id
  * Ignorer invalidated sauf si demandé
- AnalyticsManager::loadExamen(examenId)
  * FileStorage::read('examens/EXM-xxx.json')

**Étape 2 - Calculs KPIs**
- Total passages
- Taux de submission (submitted / total)
- Score moyen (AVG score_pct)
- Score médian
- Durée moyenne
- Nombre d'anomalies

**Étape 3 - Distribution scores**
- Histogramme 10 buckets (0-10%, 10-20%, ...)
- Pour chaque passage : incrémenter le bucket correspondant
- Retour : [{bucket: "0-10", count: 2, pct: 5.0}, ...]

**Étape 4 - Stats par question**
- Pour chaque question dans l'examen :
  * Compter réponses par option (A, B, C, D)
  * Calculer taux de réussite (% correct)
  * Identifier distracteur le plus efficace
  * Retour : option_analysis

**Étape 5 - Timeline**
- Grouper passages par heure
- Pour chaque heure : count + avg_score
- Sortir en format LineChart Recharts

**Étape 6 - Focus heatmap (anti-triche)**
- Agréger focus_events de tous les passages
- Compter par type : copy, paste, blur, devtools_open
- Calculer % passages avec anomalies

**Sortie**
- Format : {overview, scores_distribution, questions, timeline, focus_heatmap}
- JSON renvoyé au frontend
- Frontend React : Recharts (5 charts différents)

📏 FORMAT DE SORTIE :
- Mermaid flowchart LR
- Subgraph par étape de calcul
- Utiliser des formes différentes pour :
  * Cylindres : stockage (data/)
  * Rectangles : processus
  * Parallélogrammes : entrée/sortie
  * Losanges : décisions (filtrer invalidated ?)
- Titre "## Flux de calcul des analytics"

🧪 CONTRAINTES :
- Montrer clairement les 6 étapes de calcul
- Mentionner le format des sorties (JSON)
- Indiquer le charge potentielle (O(n) par passage)
```

---

## 7. Architecture sécurité (6 couches)

### 🎯 Objectif
Visualiser les **6 couches de sécurité** superposées.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un schéma d'architecture de SÉCURITÉ en 6 couches superposées, représentant
les défenses à traverser pour qu'une requête atteigne les données. Utilise la
syntaxe Mermaid (flowchart TB) avec style personnalisé.

📋 LES 6 COUCHES (de haut en bas - une requête les traverse dans cet ordre) :

**Couche 1 - Rate Limiting (RoleRateLimiter)**
- Position : dans index.php, middleware
- Vérifie : nb requêtes/60s par (rôle, identifier)
- Limites :
  * admin : illimité
  * enseignant : 500/min
  * étudiant : 60/min
  * anonyme : 30/min
- Action : HTTP 429 + Retry-After si dépassé
- Storage : data/_ratelimit/*.json (sliding window)

**Couche 2 - Authentication (Auth)**
- Vérifie session PHP active
- Password : bcrypt cost 12
- Sessions file-based (data/sessions/)
- `requireRole()` check hiérarchique admin > enseignant > étudiant

**Couche 3 - CSRF Protection (Csrf)**
- Token base64url 32 chars généré à la session
- Header X-CSRF-Token requis sur POST/PUT/DELETE
- Validation timing-safe (hash_equals)
- Regénération possible après login

**Couche 4 - Validation stricte (Managers)**
- Regex pour IDs :
  * EXM-[A-Z0-9]{4}-[A-Z0-9]{4}
  * PSG-[A-Z0-9]{4}-[A-Z0-9]{4}
  * USR-[a-z0-9]+
  * Questions : vec-faci-01 format
- Path traversal : InvalidArgumentException si ID invalide
- Types et valeurs : validation stricte

**Couche 5 - Signatures HMAC (PassageManager)**
- Chaque passage soumis signé avec SHA-256 + salt secret
- signature_sha256 = hash_hmac('sha256', JSON, SECRET_SALT)
- Permet de détecter modification a posteriori
- Stocké dans le passage lui-même

**Couche 6 - Échappement HTML (EmailTemplate)**
- Fonction e() : htmlspecialchars() pour tous les outputs HTML
- Protège contre XSS dans templates emails
- Double escape impossible (flag ENT_QUOTES)

📋 ÉLÉMENTS À INCLURE :
- Une "requête malveillante" en haut (icône 🚨)
- Les 6 couches empilées comme un oignon ou des barrières
- Les "données sensibles" en bas (icône 🔒)
- Flèches montrant les tentatives bloquées à chaque couche
- Légende : ce que chaque couche empêche

**Exemples à illustrer** :
- Couche 1 bloque : bruteforce, DDoS
- Couche 2 bloque : session hijacking, unauthorized
- Couche 3 bloque : CSRF (cross-site)
- Couche 4 bloque : path traversal, SQL-like injection
- Couche 5 bloque : tampering (modif données stockées)
- Couche 6 bloque : XSS, HTML injection

📏 FORMAT DE SORTIE :
- Mermaid flowchart TB avec subgraphs "couche"
- Couleurs progressives (rouge en haut → vert en bas)
- Classes CSS via classDef pour style cohérent
- Titre "## Architecture de sécurité - 6 couches défensives"

🧪 CONTRAINTES :
- Numéroter clairement les couches 1 à 6
- Inclure un exemple d'attaque bloquée par couche
- Lisible même en N&B (impression)
```

---

## 8. Topologie réseau production

### 🎯 Objectif
**Réseau** détaillé avec firewall, ports, protocoles.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un diagramme de TOPOLOGIE RÉSEAU pour le déploiement production OVH VPS,
utilisant la syntaxe Mermaid (flowchart TB) ou PlantUML (deployment diagram).

Je préfère Mermaid mais si PlantUML rend mieux, utilise-le.

📋 ÉLÉMENTS RÉSEAU À MONTRER :

**Internet**
- Utilisateurs répartis (France, Europe) avec icônes 👥
- CDNs tiers utilisés :
  * cdn.jsdelivr.net (React, Babel, KaTeX)
  * unpkg.com (Recharts, prop-types)
  * cdnjs.cloudflare.com (libs secondaires)
  * fonts.googleapis.com (Inter, JetBrains Mono)

**DNS**
- Zone DNS OVH
- Enregistrements :
  * A @ → 51.75.xxx.xxx
  * A www → 51.75.xxx.xxx
  * MX 10 mx.ovh.com
  * TXT @ "v=spf1 include:mx.ovh.com ~all"
  * TXT _dmarc "v=DMARC1; p=none; rua=mailto:admin@ipssi.fr"

**OVH VPS (51.75.xxx.xxx)**
- OS : Ubuntu 22.04 LTS
- Firewall UFW :
  * INPUT ACCEPT 22/tcp (SSH)
  * INPUT ACCEPT 80/tcp (HTTP → redirect HTTPS)
  * INPUT ACCEPT 443/tcp (HTTPS)
  * INPUT DROP tout le reste
- Fail2ban :
  * SSH : ban 1h après 5 échecs
  * HTTP : ban sur patterns anormaux

**Stack applicative sur VPS**
- Nginx :
  * Port 80 → redirect 443
  * Port 443 (SSL, TLS 1.2+, HSTS)
  * Reverse proxy vers PHP-FPM
  * Headers sécurité (X-Frame-Options, CSP, X-Content-Type-Options)
- PHP-FPM 8.3 :
  * Unix socket /var/run/php/php8.3-fpm.sock
  * Pool www, user=ipssi
- Application PHP :
  * /home/ipssi/maths_IA_niveau_1/examens/
  * entry: backend/public/index.php

**Services OVH externes**
- SMTP : ssl0.ovh.net:465 (TLS)
- Object Storage : storage.gra.cloud.ovh.net:443

**Services tiers**
- Let's Encrypt (certificats SSL) : ACME protocol
- UptimeRobot (monitoring) : ping HTTPS/api/health toutes les 5 min
- GitHub (CI/CD) : webhook sur push
- Cloudflare (optionnel, pas inclus par défaut)

📋 FLUX RÉSEAU À TRACER (avec ports) :

1. User → DNS OVH (UDP 53)
2. User → VPS:443 (TCP HTTPS, TLS 1.3)
3. Nginx → PHP-FPM (Unix socket, FastCGI)
4. PHP → /dev/sda (filesystem data/)
5. PHP → smtp.ssl0.ovh.net:465 (SMTP SSL)
6. Cron → rclone → OVH S3:443
7. UptimeRobot → VPS:443 (monitoring)
8. GitHub Actions → repo (git)
9. Certbot → acme-v02.api.letsencrypt.org:443 (ACME renewal)

📏 FORMAT DE SORTIE :
- Mermaid flowchart TB OU PlantUML deployment
- Annotations sur les liens : protocole + port (ex: "HTTPS:443")
- Boîtes avec noms, OS, IP
- Cloud shapes pour Internet et services externes
- Titre "## Topologie réseau production OVH"

🧪 CONTRAINTES :
- Ports TCP/UDP explicites
- Protocoles identifiés (HTTPS, SMTP, FastCGI, etc.)
- Firewall rules visibles
- Inclure les services tiers (GitHub, UptimeRobot, Let's Encrypt)
```

---

## 9. Diagramme de classes complet

### 🎯 Objectif
**UML classes** pour tous les composants PHP.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un diagramme de CLASSES UML complet pour toute la couche backend PHP,
utilisant la syntaxe PlantUML (@startuml). Ce diagramme va dans la documentation
technique.

📋 CLASSES À INCLURE (namespace Examens\Lib) :

**Auth**
- Attributs : $sessionUser (?array)
- Méthodes :
  + login(email: string, password: string): bool
  + logout(): void
  + isLoggedIn(): bool
  + getCurrentUser(): ?array
  + requireRole(role: string): void
  + hashPassword(password: string): string
  - verifyPassword(password: string, hash: string): bool
- Constantes : ROLE_ADMIN, ROLE_ENSEIGNANT, ROLE_ETUDIANT

**Session**
- Méthodes statiques :
  + start(): void
  + destroy(): void
  + regenerate(): void
  + get(key: string, default: mixed): mixed
  + set(key: string, value: mixed): void

**Csrf**
- Méthodes statiques :
  + generate(): string
  + validate(token: string): bool
  + requireValid(): void
  + regenerate(): void

**Logger**
- Attributs : $logFile (string), $channel (string)
- Méthodes :
  + __construct(channel: string = 'app')
  + info(message: string, context: array): void
  + warning(message: string, context: array): void
  + error(message: string, context: array): void
  + debug(message: string, context: array): void

**FileStorage**
- Attributs : $logger (Logger)
- Méthodes :
  + read(relativePath: string): ?array
  + write(relativePath: string, data: array): bool
  + delete(relativePath: string): bool
  + exists(relativePath: string): bool
  + update(relativePath: string, callback: callable): bool
  + glob(pattern: string): array

**BanqueManager**
- Attributs : $storage, $logger
- Méthodes :
  + getAll(): array
  + getById(id: string): ?array
  + getByModule(module: string): array
  + create(data: array): array
  + update(id: string, data: array): bool
  + delete(id: string): bool
  + getStats(): array
  - validateId(id: string): void

**ExamenManager**
- Méthodes :
  + create(data: array, profId: string): array
  + getById(id: string): ?array
  + getByAccessCode(code: string): ?array
  + list(profId: ?string): array
  + publish(id: string): bool
  + close(id: string): bool
  + archive(id: string): bool
  + update(id: string, data: array): bool
  + delete(id: string): bool
  - generateId(): string
  - generateAccessCode(): string

**PassageManager**
- Méthodes :
  + start(examenId: string, studentInfo: array): array
  + getByToken(token: string): ?array
  + saveAnswer(token: string, questionId: string, answer: int): bool
  + logFocusEvent(token: string, event: array): void
  + submit(token: string): array
  + getByExamen(examenId: string): array
  + invalidate(token: string, reason: string): bool
  - generateToken(): string
  - computeScore(passage: array, examen: array): array
  - computeSignature(passage: array): string

**AnalyticsManager**
- Méthodes :
  + getExamenOverview(examenId: string): array
  + getScoresDistribution(examenId: string): array
  + getQuestionsStats(examenId: string): array
  + getTimeline(examenId: string): array
  + getFocusHeatmap(examenId: string): array
  + getProfOverview(profId: string): array
  + getStudentHistory(email: string): array

**BackupManager**
- Attributs : $backupsDir, $scriptsDir, $logger
- Méthodes :
  + list(): array
  + get(id: string): ?array
  + createBackup(keep: int): array
  + verify(id: string): array
  + delete(id: string): bool
  + getStats(): array
  - sanitizeId(id: string): ?string

**HealthChecker**
- Attributs : $dataDir, seuils configurables
- Méthodes :
  + checkAll(): array
  + checkDisk(): array
  + checkMemory(): array
  + checkFilesystem(): array
  + checkCounters(): array
  + checkBackups(): array
  + checkLogs(): array
  + checkPhp(): array
  - bytesHuman(bytes: int): string
  - aggregateStatus(statuses: array): string

**RateLimiter** (existant, bruteforce login)
- Attributs : $bucket, $maxAttempts, $windowSec, $storage
- Méthodes :
  + attempt(key: string): bool
  + record(key: string): void
  + remaining(key: string): int
  + retryAfter(key: string): int
  + reset(key: string): void

**RoleRateLimiter** (nouveau, API par rôle)
- Attributs : $limiters (array)
- Méthodes :
  + check(role: string, identifier: string): array
  + headers(check: array): array
  + reset(role: string, identifier: string): void
  + getStats(): array
- Constantes : LIMITS, WINDOW_SEC

**Mailer**
- Méthodes :
  + send(to: string, subject: string, htmlBody: string): bool
  + sendWithTemplate(to: string, subject: string, template: string, vars: array): bool

**EmailTemplate**
- Méthodes statiques :
  + etudiant_submission(data: array): string
  + prof_nouveau_passage(data: array): string
  + admin_anomalie(data: array): string
  + reset_password(data: array): string
  - e(html: string): string

**Response**
- Méthodes statiques :
  + json(data: array, status: int = 200): never
  + error(message: string, status: int = 400): never
  + notFound(message: string): never
  + rateLimited(message: string): never

📋 RELATIONS À MODÉLISER :

- Auth utilise Session
- BanqueManager, ExamenManager, PassageManager : dépendent de FileStorage + Logger
- AnalyticsManager dépend de PassageManager + ExamenManager
- BackupManager utilise scripts/backup.sh via exec()
- RoleRateLimiter instancie plusieurs RateLimiter (un par rôle)
- Mailer utilise EmailTemplate
- Toutes les classes utilisent Logger pour les logs

📏 FORMAT DE SORTIE :
- PlantUML @startuml ... @enduml
- Grouper par catégorie avec `together { ... }` ou packages :
  * package "Core" { Auth, Session, Csrf, Logger, Response }
  * package "Managers" { Banque, Examen, Passage, Analytics, Backup, Health }
  * package "Security" { RateLimiter, RoleRateLimiter }
  * package "Communication" { Mailer, EmailTemplate }
  * package "Storage" { FileStorage }
- Utiliser + pour public, - pour private, # pour protected
- Utiliser <<static>> pour méthodes statiques
- Relations : -->, ..>, --|>, --*
- Titre "@title IPSSI Examens - Class Diagram (Backend)"

🧪 CONTRAINTES :
- TOUTES les classes doivent apparaître
- Attributs et méthodes clés visibles
- Relations claires entre packages
- Format PlantUML qui compile sans erreur sur plantuml.com
```

---

## 10. Diagramme de classes — Managers

### 🎯 Objectif
**Zoom** sur les managers avec détails maximum.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un diagramme de CLASSES UML détaillé pour les 7 MANAGERS principaux
(core business logic), utilisant la syntaxe PlantUML. Plus granulaire que le
diagramme global.

📋 MANAGERS À DÉTAILLER (MAXIMUM de détails) :

Pour chaque classe, montrer :
- TOUS les attributs (visibilité + type + nom)
- TOUTES les méthodes publiques (signature complète)
- Les méthodes privées importantes
- Les constantes de classe
- Les relations avec autres classes (composition, agrégation, dépendance)

**Classes concernées** :
1. BanqueManager
2. ExamenManager
3. PassageManager
4. AnalyticsManager
5. BackupManager
6. HealthChecker
7. RoleRateLimiter

📋 DÉPENDANCES ENTRE MANAGERS :
- ExamenManager ← utilisé par → PassageManager (getExamen pour start)
- ExamenManager ← utilisé par → AnalyticsManager (métadonnées)
- PassageManager ← utilisé par → AnalyticsManager (loadPassages)
- BanqueManager ← utilisé par → ExamenManager (validation questions)
- BanqueManager ← utilisé par → PassageManager (corriger réponses)
- Tous utilisent FileStorage + Logger

📋 INCLURE :
- Les types PHP 8 (avec ?nullable, array, string, int, bool, callable)
- Les constantes (ex: ROLE_ADMIN, WINDOW_SEC)
- Les formats d'ID attendus (en commentaire ou note)
- Les exceptions levées (via <<throws>> si possible)

📏 FORMAT DE SORTIE :
- PlantUML @startuml avec :
  * skinparam classAttributeIconSize 0
  * skinparam classFontSize 11
- Pas de packages cette fois (zoom)
- Notes détachées pour les algorithmes clés :
  * note right of PassageManager : "computeSignature utilise HMAC SHA-256\navec SECRET_SALT de config"
  * note right of RoleRateLimiter : "Sliding window 60s\nBucket par (role, identifier)"
- Relations avec multiplicités (ex: 1 - *)

🧪 CONTRAINTES :
- Très détaillé (pas un survol)
- Max 7 classes, mais très fouillées
- Utilisable comme référence dev
```

---

## 11. Diagramme de séquence — Passage examen

### 🎯 Objectif
**Séquence UML** complète passage étudiant (plus détaillée que prompt 5).

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un diagramme de SÉQUENCE UML très détaillé pour le workflow "Passage
d'un examen par un étudiant", utilisant la syntaxe PlantUML (@startuml).

Différent du prompt 5 : ici on veut un **diagramme UML formel** avec lifelines,
activations, alternative, loops, etc.

📋 ACTEURS ET OBJETS :
- Actor "Etudiant" as E
- Boundary "passage.html (React)" as UI
- Control "router (index.php)" as R
- Control "api/passages.php" as API
- Control "Csrf" as CSRF
- Control "Auth" as AUTH
- Control "RoleRateLimiter" as RL
- Control "ExamenManager" as EM
- Control "PassageManager" as PM
- Entity "examen (JSON)" as EX
- Entity "passage (JSON)" as PS
- Control "Mailer" as MAIL

📋 SÉQUENCE COMPLÈTE (avec tous les détails techniques) :

**Opt 1 - Connexion à l'examen**
```
E -> UI: Saisit code "ABC23K-9P"
UI -> R: POST /api/passages/access
activate R
R -> RL: check('anonyme', 'ip:x.x.x.x')
RL --> R: allowed, headers
R -> R: inject X-RateLimit-* headers
R -> API: delegate
activate API
API -> EM: getByAccessCode("ABC23K-9P")
activate EM
EM -> EX: read data/examens/*.json
EX --> EM: examen data
EM -> EM: validate status + dates
alt code valide
    EM --> API: Examen{titre, duree, nb_questions}
    API --> UI: 200 OK JSON
else code invalide
    EM --> API: null
    API --> UI: 404 {error: "code_invalide"}
end
deactivate EM
deactivate API
deactivate R
```

**Opt 2 - Start du passage**
```
E -> UI: Remplit {nom, prenom, email}
UI -> R: POST /api/passages/start + CSRF + body
R -> CSRF: requireValid()
CSRF --> R: ok
R -> API: delegate
API -> PM: start(examen_id, student_info)
activate PM
PM -> EX: read examen
PM -> PS: glob data/passages/*.json
PS --> PM: existing_passages
PM -> PM: check max_passages (anti-doublon)
alt limite atteinte
    PM --> API: error("max_passages_atteint")
    API --> UI: 403
else OK
    PM -> PM: generate PSG-XXXX-YYYY
    PM -> PM: generate UUID token
    PM -> PM: shuffle questions + options
    PM -> PS: write PSG-xxx.json (status: in_progress)
    PM --> API: {token, question_order}
    API --> UI: 200 OK
end
deactivate PM
```

**Opt 3 - Boucle de réponse**
```
loop pour chaque question
    E -> UI: Clique option
    UI -> UI: auto-save local (debounce)
    UI -> R: POST /api/passages/answer + CSRF
    R -> API: delegate
    API -> PM: saveAnswer(token, qid, idx)
    PM -> PS: update PSG-xxx.json
    PM --> API: ok
    API --> UI: 200 OK
end

par événement focus
    UI -> R: POST /api/passages/focus-event
    R -> API: delegate
    API -> PM: logFocusEvent(token, type)
    PM -> PS: append focus_events[]
end
```

**Opt 4 - Soumission**
```
E -> UI: Clique "Soumettre"
UI -> UI: confirm dialog
UI -> R: POST /api/passages/submit + CSRF
R -> API: delegate
API -> PM: submit(token)
activate PM
PM -> PS: read current passage
PM -> EX: read examen
PM -> PM: computeScore(answers, expected)
PM -> PM: computeSignature (HMAC SHA-256)
PM -> PS: write final state (status: submitted)
PM -> MAIL: send correction email
activate MAIL
MAIL -> MAIL: SMTP → étudiant
deactivate MAIL
PM --> API: {score_pct, correction_url}
API --> UI: 200 OK
deactivate PM
UI -> E: Affiche score + lien correction
```

**Opt 5 - Consultation correction (plus tard)**
```
E -> UI: Clique lien email
UI -> R: GET /api/corrections/{token}
R -> API: delegate (api/corrections.php)
API -> PM: getByToken(token)
PM -> PS: read passage
alt token valide + passage submitted
    PM -> EM: get examen metadata
    PM -> PM: build detailed_correction[]
    PM --> API: correction data
    API --> UI: 200 OK
    UI -> E: Affiche correction détaillée
else token invalide
    PM --> API: null
    API --> UI: 404
end
```

📏 FORMAT DE SORTIE :
- PlantUML @startuml
- Utiliser :
  * actor/boundary/control/entity (UML standard)
  * activate/deactivate pour les lifelines
  * alt/else pour alternatives
  * loop/par pour répétitions/parallèles
  * opt pour actions optionnelles
  * note over/right/left pour commentaires
- Diviser en blocs visibles (groupes ou dividers) pour chaque phase
- Titre : "@title Passage d'examen - Sequence Diagram UML"

🧪 CONTRAINTES :
- Tous les participants cités
- Minimum 60 lignes PlantUML (très détaillé)
- Mentionner explicitement :
  * Les vérifications CSRF
  * Le rate limiting
  * La signature HMAC
  * Les codes d'erreur HTTP
  * L'envoi email
```

---

## 12. Diagramme de séquence — Authentification

### 🎯 Objectif
**Séquence login** complète avec sécurité.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un diagramme de SÉQUENCE UML pour le workflow d'authentification
(login d'un enseignant), utilisant PlantUML. Inclure toutes les couches
sécurité.

📋 SCÉNARIO :
Un enseignant se connecte avec son email + password au dashboard /admin/examens.html

📋 PARTICIPANTS :
- Actor "Prof" as P
- Boundary "login.html (React)" as UI
- Control "router (index.php)" as R
- Control "RoleRateLimiter" as RRL
- Control "api/auth.php" as API
- Control "RateLimiter (bucket login)" as RL
- Control "Auth" as AUTH
- Control "Session" as SESS
- Control "Csrf" as CSRF
- Control "Logger" as LOG
- Entity "comptes/USR-xxx.json" as USR

📋 SÉQUENCE DÉTAILLÉE :

**Phase 1 - Chargement de la page login**
- P ouvre /admin/login.html
- UI → R: GET /admin/login.html
- R → static: serve HTML
- UI se charge (React)
- UI → R: GET /api/auth/me (check si déjà connecté)
- R → API: delegate
- API → AUTH: getCurrentUser()
- AUTH → SESS: get('user_id')
- SESS → null (pas connecté)
- AUTH → API: null
- API → UI: 401 {error: "not_authenticated"}
- UI: affiche formulaire login

**Phase 2 - Soumission**
- P: Saisit email + password
- UI → R: POST /api/auth/login + body {email, password}
- R → RRL: check('anonyme', 'ip:X.X.X.X')
- RRL → R: allowed=true (30/min OK)
- R → CSRF: vérifier X-CSRF-Token
- CSRF → R: ok (ou KO → 403)
- R → API: delegate

**Phase 3 - Vérification bruteforce**
- API → RL: attempt('ip:X.X.X.X')
- RL → RL: check remaining (5/15min)
- alt limite atteinte
  - RL → API: false
  - API → LOG: warning('login_bruteforce_blocked')
  - API → UI: 429 {error: "too_many_attempts"}

**Phase 4 - Recherche utilisateur**
- else OK
  - API → USR: glob comptes/*.json
  - USR → API: liste fichiers
  - API → API: find by email
  - alt not found
    - API → LOG: info('login_failed (not_found)')
    - API → UI: 401 {error: "invalid_credentials"}

**Phase 5 - Vérification password**
- else user trouvé
  - API → AUTH: verifyPassword(password, user.password_hash)
  - AUTH → AUTH: password_verify (bcrypt)
  - alt password invalide
    - AUTH → API: false
    - API → LOG: info('login_failed (bad_password)')
    - API → UI: 401

**Phase 6 - Succès**
- else password valide
  - AUTH → API: true
  - API → AUTH: login(email) [crée session]
  - AUTH → SESS: regenerate() [nouveau ID pour sécurité]
  - AUTH → SESS: set('user_id', USR-xxx)
  - AUTH → SESS: set('role', 'enseignant')
  - API → CSRF: regenerate()
  - CSRF → SESS: set('csrf_token', new_token)
  - API → RL: reset('ip:X.X.X.X') [reset compteur]
  - API → USR: update last_login, save
  - API → LOG: info('login_success user=xxx')
  - API → UI: 200 {ok: true, user: {id, email, role, nom}, csrf_token}
  - UI → UI: store user in state
  - UI → UI: redirect /admin/examens.html

📏 FORMAT DE SORTIE :
- PlantUML @startuml
- Titre : "@title Authentification enseignant - Workflow complet"
- Utiliser :
  * alt/else pour chaque point de décision
  * note sur les étapes sensibles (ex: "bcrypt cost 12, timing-safe")
  * activate/deactivate
- Sections clairement visibles (phases 1-6)

🧪 CONTRAINTES :
- Montrer TOUS les cas d'échec (not found, bad password, too many attempts)
- Indiquer les codes HTTP précis (200, 401, 403, 429)
- Mentionner la régénération de session + CSRF après login
- Logger toutes les tentatives (succès + échec)
```

---

## 13. Diagramme d'états — Examen

### 🎯 Objectif
**State machine** pour le cycle de vie d'un examen.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un DIAGRAMME D'ÉTATS (state diagram) UML pour le cycle de vie d'un
Examen, utilisant la syntaxe PlantUML (@startuml).

📋 ÉTATS POSSIBLES :

1. **[*]** - État initial (examen non existant)
2. **Draft** - Brouillon
   - Examen en cours de création
   - Modifiable
   - Pas accessible aux étudiants
   - Pas de code d'accès
3. **Published** - Publié
   - Code d'accès généré
   - Accessible aux étudiants si date_ouverture <= now < date_cloture
   - Modifications limitées (titre, description OK, mais pas questions)
4. **Closed** - Clôturé
   - Plus de nouveaux passages
   - Examens en cours sont submitted auto
   - Code d'accès désactivé
   - Analytics toujours disponibles
5. **Archived** - Archivé
   - Masqué de la liste principale
   - Données conservées
   - Analytics disponibles
6. **[*]** - État final (après delete, rare)

📋 TRANSITIONS (avec conditions) :

- [*] → Draft : create() par un prof
- Draft → Published : publish() [titre non vide, ≥1 question, dates valides]
- Draft → [*] : delete() [autorisé si aucun passage]
- Published → Closed : close() [manuel] OR now >= date_cloture [auto]
- Published → Draft : unpublish() [si aucun passage, rare]
- Closed → Archived : archive() [admin/prof]
- Archived → [*] : delete() [admin seulement, passages conservés ailleurs ?]

📋 ACTIONS PAR ÉTAT (entry/exit/do) :

- Draft :
  - entry: status='draft', created_at, updated_at = now
  - do: allow any modification

- Published :
  - entry: status='published', generate access_code, log audit
  - do: accept passages si dates OK
  - exit: if closing, log

- Closed :
  - entry: status='closed', closed_at = now, auto-submit passages in_progress
  - do: read-only

- Archived :
  - entry: status='archived', hidden from main list
  - do: read-only, analytics accessible

📋 ÉVÉNEMENTS EXTERNES (peuvent déclencher transitions) :

- now >= date_cloture → Closed auto (cron ou lazy check)
- Admin délete → (état final)

📏 FORMAT DE SORTIE :
- PlantUML @startuml avec state machine
- Syntaxe :
  ```
  [*] --> Draft : create()
  Draft --> Published : publish() [conditions]
  Published --> Closed : close()
  ...
  ```
- Notes pour chaque état avec entry/do/exit
- Note globale sur les transitions automatiques
- Titre "@title Cycle de vie d'un Examen"

🧪 CONTRAINTES :
- Tous les états et transitions listés doivent apparaître
- Conditions [sur les transitions] explicitement notées
- Actions entry/do/exit visibles dans les notes
- Cohérent avec le code ExamenManager::publish(), close(), archive()
```

---

## 14. Diagramme d'états — Passage

### 🎯 Objectif
**State machine** du passage étudiant.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un DIAGRAMME D'ÉTATS UML pour le cycle de vie d'un Passage (tentative
étudiant), en utilisant PlantUML.

📋 ÉTATS :

1. **[*]** - Non existant
2. **InProgress** - En cours
   - Étudiant actif
   - start_time set
   - Réponses en cours d'écriture
   - focus_events accumulés
3. **Submitted** - Soumis
   - end_time set
   - Score calculé
   - Signature HMAC générée
   - Email envoyé
4. **Expired** - Expiré
   - Timeout atteint (durée dépassée)
   - Auto-submit avec réponses actuelles
   - score_pct calculé sur ce qui est rempli
5. **Invalidated** - Invalidé
   - Détection fraude (seuil anomalies dépassé) ou action manuelle du prof
   - Ne compte pas dans les stats (sauf si demandé)
6. **[*]** - Final (rare, garder en archive)

📋 TRANSITIONS :

- [*] → InProgress : start(examenId, studentInfo) [après validation code + infos]
- InProgress → Submitted : submit() [étudiant clique soumettre]
- InProgress → Expired : timeout auto [check par cron ou lazy]
- InProgress → Invalidated : invalidate(reason) [manuel ou auto sur anomalies]
- Submitted → Invalidated : invalidate(reason) [découverte tardive]
- Expired → Submitted : impossible
- Invalidated → [*] : delete() [cleanup admin seulement]

📋 ACTIONS PAR ÉTAT :

- InProgress :
  - entry: status='in_progress', start_time=now, generate UUID token, save to JSON
  - do: 
    * accept saveAnswer pour chaque question
    * log focus_events
    * auto-save continue
  - exit: si timeout, set expired, else si submit, compute score

- Submitted :
  - entry: 
    * compute score (score_brut, score_max, score_pct)
    * compute signature_sha256 (HMAC)
    * end_time = now
    * duration_sec calculé
    * envoi email correction via Mailer
  - do: read-only, accessible par token pour correction

- Expired :
  - entry: 
    * status='expired'
    * end_time = start_time + duree_sec
    * compute score sur answers existants
    * log 'expired_timeout'
  - do: read-only

- Invalidated :
  - entry:
    * status='invalidated'
    * invalidation_reason = '...'
    * invalidated_by = userId ou 'system'
    * log
  - do: caché des stats par défaut

📋 SEUILS POUR INVALIDATION AUTOMATIQUE :

Sous-état "Watching" dans InProgress :
- focus_events count < 3 : OK
- 3-5 : warning
- > 5 : auto-invalidate (configurable)

Critères :
- 3+ events "devtools_open" → invalidated
- 5+ events "blur" > 10s chacun → invalidated
- 10+ copy/paste → invalidated

📏 FORMAT DE SORTIE :
- PlantUML @startuml state machine
- Utiliser :
  * États avec entry/do/exit en notes
  * Transitions avec conditions [...]
  * Sous-état "Watching" dans InProgress (nested state)
  * Événements externes (timer expiration)
- Titre "@title Cycle de vie d'un Passage étudiant"

🧪 CONTRAINTES :
- 5 états principaux + sous-états si pertinent
- Conditions d'invalidation auto visibles
- Actions critiques (signature HMAC, envoi email) dans entry
- Cohérent avec PassageManager::start(), submit(), invalidate()
```

---

## 15. Diagramme de cas d'utilisation

### 🎯 Objectif
**Use case diagram** UML complet.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un DIAGRAMME DE CAS D'UTILISATION (use case diagram) UML pour la
plateforme IPSSI Examens, utilisant PlantUML.

📋 ACTEURS :

**Acteurs primaires (humains)** :
- Admin
- Enseignant
- Étudiant
- Invité (non-authentifié)

**Acteurs secondaires (systèmes)** :
- SMTP Server (envoi emails)
- Cron (tâches planifiées)
- UptimeRobot (monitoring externe)
- Filesystem (stockage)

📋 CAS D'UTILISATION PAR ACTEUR :

**Admin** (inclut tout Enseignant + admin-only) :
- Gérer comptes utilisateurs
- Consulter monitoring système
- Gérer les backups (créer, vérifier, supprimer, restaurer)
- Voir tous les examens (pas seulement les siens)
- Consulter tous les logs
- Configurer cron de backup

**Enseignant** :
- Se connecter (login)
- Changer son mot de passe
- Créer un examen
  - «include» Sélectionner questions de la banque
  - «include» Configurer paramètres (durée, dates, options)
- Publier un examen
  - «include» Générer code d'accès
- Distribuer le code d'accès
- Consulter suivi temps réel (passages en cours)
- Invalider un passage (fraude détectée)
  - «extend» Consulter les anomalies
- Clôturer un examen
- Archiver un examen
- Consulter analytics
  - «include» Voir distribution des scores
  - «include» Analyser distracteurs
  - «include» Voir historique d'un étudiant
- Exporter résultats
  - «include» Format CSV
  - «include» Format Excel
  - «include» Format PDF
- Gérer la banque de questions
  - «extend» Créer une question
  - «extend» Modifier une question

**Étudiant** :
- Accéder à un examen via code
- Fournir informations (nom, prénom, email)
- Passer l'examen
  - «include» Répondre aux questions
  - «include» Marquer pour revoir
- Soumettre l'examen (manuel ou auto sur timeout)
  - «include» Recevoir email de confirmation
- Consulter sa correction
  - «include» Voir détail par question
  - «extend» Télécharger PDF correction

**Invité (anonymous)** :
- Accéder à la page login
- Consulter /api/health (public)

**Cron** (système) :
- Lancer backup.sh quotidien à 03:00
  - «include» Créer archive tar.gz
  - «include» Calculer SHA-256
  - «include» Rotation (keep N derniers)
  - «extend» Cleanup rate limit buckets > 24h
- Sync offsite backup vers OVH Object Storage

**SMTP Server** :
- Recevoir emails (correction étudiant, etc.)

**UptimeRobot** :
- Ping /api/health régulièrement
  - «extend» Alerter admin si down

**Filesystem** :
- Stocker/lire fichiers JSON
- Gérer sessions PHP

📋 RELATIONS UML :
- «include» : dépendance obligatoire (A inclut toujours B)
- «extend» : dépendance optionnelle (B peut étendre A)
- Généralisation/Hériter : Admin hérite d'Enseignant (tout ce qu'Enseignant peut, Admin peut)

📏 FORMAT DE SORTIE :
- PlantUML @startuml
- Syntaxe use case :
  ```
  left to right direction
  actor "Admin" as A
  actor "Enseignant" as E
  actor "Étudiant" as S
  ...
  rectangle "IPSSI Examens" {
    usecase "Créer examen" as UC1
    usecase "Publier examen" as UC2
    ...
  }
  A --> UC1
  E --> UC1
  UC2 .|> UC1 : extends
  ...
  ```
- Organiser en sous-rectangles/packages par groupe fonctionnel :
  * Authentification
  * Gestion examens
  * Passage étudiant
  * Analytics & Reporting
  * Administration système

🧪 CONTRAINTES :
- Tous les acteurs listés
- Tous les cas d'utilisation majeurs
- Relations include/extend utilisées correctement
- Héritage Admin hérite d'Enseignant visible
- Acteurs secondaires séparés
- Titre "@title IPSSI Examens - Use Case Diagram"
```

---

## 16. ERD — Modèle de données (fichiers JSON)

### 🎯 Objectif
**Entity-Relationship** pour les entités stockées (même si pas en SQL).

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un DIAGRAMME ENTITÉ-RELATION (ERD) pour les entités stockées, même si
elles sont en fichiers JSON (pas SQL). Utilise la syntaxe Mermaid (erDiagram).

📋 ENTITÉS À MODÉLISER :

**Examen** (data/examens/EXM-XXXX-YYYY.json)
Attributs :
- id (PK, format EXM-XXXX-YYYY, regex [A-Z0-9]{4}-[A-Z0-9]{4})
- titre (string, max 200)
- description (text, optional)
- status (enum: draft, published, closed, archived)
- created_by (FK → Compte.id)
- created_at (datetime ISO 8601)
- updated_at (datetime)
- questions (array of question IDs, 1-50)
- duree_sec (int, 60-14400)
- date_ouverture (datetime)
- date_cloture (datetime)
- max_passages (int, 1-100)
- shuffle_questions (bool)
- shuffle_options (bool)
- show_correction_after (bool)
- correction_delay_min (int)
- access_code (string, format ABC23K-9P, nullable avant publish)
- closed_at (datetime, nullable)
- archived_at (datetime, nullable)

**Passage** (data/passages/PSG-XXXX-YYYY.json)
Attributs :
- id (PK, format PSG-XXXX-YYYY)
- examen_id (FK → Examen.id)
- token (UUID v4, unique, indexé pour lookup)
- access_code_used (string)
- student_info (embedded JSON: {nom, prenom, email})
- question_order (array de question IDs, shuffle si configuré)
- option_shuffle_maps (object {question_id: [permutation]})
- answers (object {question_id: answer_index})
- start_time (datetime)
- end_time (datetime, nullable)
- duration_sec (int, nullable)
- status (enum: in_progress, submitted, expired, invalidated)
- score_brut (int, nullable)
- score_max (int, nullable)
- score_pct (float 0-100, nullable)
- signature_sha256 (string 64 chars, nullable jusqu'à submit)
- focus_events (array of objects)
- score_details (object, nullable avant submit)
- invalidation_reason (string, nullable)
- invalidated_by (string, nullable)

**Compte (User)** (data/comptes/USR-xxxx.json)
Attributs :
- id (PK, format USR-xxxxx)
- email (unique, indexé)
- nom (string)
- prenom (string)
- role (enum: admin, enseignant, etudiant)
- password_hash (bcrypt string 60 chars)
- created_at (datetime)
- last_login (datetime, nullable)
- active (bool)
- metadata (object, nullable)

**Question** (data/banque/{module}/{id}.json)
Attributs :
- id (PK, format module-difficulte-num ex: vec-faci-01)
- module (string, ex: vecteurs, matrices)
- chapitre (string)
- theme (string)
- difficulte (enum: facile, moyen, difficile, tres_difficile)
- type (enum: qcm)
- enonce (text, LaTeX KaTeX supporté)
- options (array of {text, correct})
- explication (text, optional)
- tags (array of strings)
- created_at (datetime)

**FocusEvent** (embedded dans Passage.focus_events)
Attributs :
- type (enum: copy, paste, blur, focus, devtools_open, right_click)
- timestamp (datetime)
- duration_ms (int, nullable pour blur)
- metadata (object, nullable)

📋 RELATIONS :

- Compte 1 --{0,N} Examen (created_by)
- Examen 1 --{0,N} Passage (examen_id)
- Passage 1 --{0,N} FocusEvent (embedded)
- Examen 1 --{1,N} Question (via questions[])

Note : en base SQL, on aurait une table de jointure Examen_Question pour la relation M-N car une Question peut être dans plusieurs Examens. En JSON, c'est juste un array d'IDs.

📋 CONTRAINTES À DOCUMENTER :

- Integrity : signature_sha256 permet de détecter altération d'un Passage submitted
- Cardinalité max_passages : limite de Passages par (examen_id, student_email)
- Unicité : access_code unique par Examen à un instant T (mais peut être régénéré)
- Tokens : UUID v4 unique par Passage, sert de clé d'accès publique

📏 FORMAT DE SORTIE :
- Mermaid erDiagram
- Chaque entité avec ses attributs (type + nom + PK/FK)
- Relations avec notation ||--o{ ou }o--|| etc.
- Notes sur contraintes importantes
- Titre en markdown "## Modèle de données - ERD"

Exemple structure :
```
erDiagram
    Compte ||--o{ Examen : creates
    Examen ||--o{ Passage : has
    Examen }o--|| Question : contains
    Passage ||--o{ FocusEvent : logs

    Compte {
        string id PK
        string email UK
        string password_hash
        ...
    }
    ...
```

🧪 CONTRAINTES :
- Toutes les entités listées
- Tous les attributs clés
- PK/FK marqués
- Relations avec multiplicités correctes
- Format compatible Mermaid.live
```

---

## 17. Diagramme de composants React

### 🎯 Objectif
**Frontend** : hiérarchie des composants React.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un DIAGRAMME DE COMPOSANTS pour le frontend React, montrant la
hiérarchie des composants et leurs relations. Utilise PlantUML avec la notation
component (rectangles).

📋 STRUCTURE À REPRÉSENTER :

**Design system (components-base.js)**
- Button (variants: primary, ghost, danger)
- Input, Textarea
- Box (type: info, success, warning, error)
- Card
- Spinner
- Modal
- Tabs

**Composants avancés (components-advanced.js)**
- DataTable (sort, search, pagination)
- ToastProvider + useToast
- ConfirmDialog
- DatePicker
- FileUpload

**Hooks (hooks.js)**
- useApi (fetch avec CSRF auto)
- useAuth (login state)
- useDebounce
- useLocalStorage

**Pages admin**

`admin/banque.html` :
- BanqueApp
  - BanqueTree (arborescence modules)
    - TreeNode (récursif)
  - QuestionEditor
    - LatexPreview (KaTeX)
    - OptionsEditor
  - QuestionCard

`admin/examens.html` :
- ExamensApp
  - ExamensList
    - ExamenCard (par examen)
  - CreateExamenModal
    - QuestionPicker
    - ConfigPanel

`admin/analytics.html` :
- AnalyticsApp
  - ProfOverview
  - ExamenDetail
    - KPICard (x4)
    - ScoresDistributionChart (BarChart Recharts)
    - MentionsChart (PieChart Recharts)
    - QuestionsChart (BarChart Recharts)
    - TimelineChart (LineChart Recharts)
    - FocusHeatmapChart
  - HistoryView
    - PassagesTable
  - DistractorsView
  - StudentDetail
  - ExportButtons (CSV, Excel, PDF)

`admin/monitoring.html` :
- MonitoringApp
  - StatusBanner (global status)
  - CheckCard (x7)
    - DiskCard
    - MemoryCard
    - FilesystemCard
    - CountersCard
    - BackupsCard
    - LogsCard
    - PhpCard
  - AutoRefreshToggle

**Pages étudiant**

`etudiant/passage.html` :
- PassageApp
  - AccessCodeInput
  - StudentInfoForm
  - ExamenInProgress
    - QuestionDisplay
      - LatexRenderer
    - QuestionNav (numeros)
    - Timer
    - AntiCheatGuard (détecte focus, copy, etc.)
  - SubmitConfirm
  - SubmissionSuccess

`etudiant/correction.html` :
- CorrectionApp
  - ScoreBanner
  - QuestionCorrection (par question)
    - UserAnswer
    - CorrectAnswer
    - Explanation
  - DownloadPDFButton

**Dépendances externes (via CDN)** :
- React 18
- Babel Standalone
- KaTeX 0.16.9
- Recharts 2.12.7
- Pyodide (mini-projet)

📋 RELATIONS :
- Composition : ExamenDetail contient plusieurs Charts
- Dépendances : MonitoringApp utilise useApi, useAuth
- Providers : ToastProvider wrappe toutes les apps

📏 FORMAT DE SORTIE :
- PlantUML @startuml avec component diagram
- Organisation :
  * package "Design System"
  * package "Hooks"
  * package "Admin Pages"
  * package "Etudiant Pages"
  * package "External (CDN)"
- Notation component : [ComponentName]
- Interfaces entre composants si pertinent : ()
- Relations : --> pour "uses", ..> pour dependency

🧪 CONTRAINTES :
- Tous les composants listés apparaissent
- Organisation visuelle claire par page
- Relations parent-enfant visibles
- Dépendances externes (CDN) séparées
- Titre "@title IPSSI Examens - Component Diagram (Frontend React)"
```

---

## 18. Workflow CI/CD GitHub Actions

### 🎯 Objectif
**Pipeline** CI/CD visuel.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un diagramme de WORKFLOW représentant le pipeline CI/CD GitHub Actions,
utilisant la syntaxe Mermaid (flowchart TB ou LR).

📋 WORKFLOWS À REPRÉSENTER :

**Déclencheurs (triggers)** :
- Push sur main
- Pull Request vers main

**Workflow 1 : tests.yml (principal)**

Job "tests" (matrix PHP 8.2 + 8.3, parallèle) :
1. Checkout code (actions/checkout@v4)
2. Setup PHP (shivammathur/setup-php@v2)
   - Extensions : json, mbstring, openssl, curl, zip
3. Afficher info environnement (php --version, php -m)
4. Préparer data/
   - chmod +x scripts/*.sh
   - Créer sous-dossiers data/examens, data/passages, etc.
5. Vérifier syntaxe PHP (php -l sur tous les .php)
6. Lancer harness unifié
   - php backend/tests/run_all.php --no-color
   - 389 tests attendus
7. Vérifier scripts backup
   - ./scripts/backup.sh --keep=3
   - Verify hash SHA-256
8. Tester endpoint /health live
   - Start PHP dev server
   - curl /api/health
   - curl /api/health?detailed=1
   - Kill server
9. Cleanup data/

Job "frontend-lint" (parallèle au job tests) :
1. Checkout
2. Setup Node.js 20
3. Install Babel
4. Parser tous les .jsx (validité syntaxique)

**Workflow 2 : lint.yml (rapide)**

Jobs parallèles :

Job "php-syntax" :
- Checkout
- Setup PHP 8.3
- php -l sur tous les .php

Job "markdown-check" :
- Lister tous les .md
- Afficher tailles

Job "structure-check" :
- Vérifier dossiers critiques existent
- Vérifier fichiers critiques présents
- Compter mentions CC license

**Résultats** :

Sur succès :
- ✅ Badge vert "Tests passing"
- ✅ Badge vert "Lint passing"
- Commit sur main OK

Sur échec :
- ❌ Badge rouge
- Notification (email par défaut GitHub)
- Log détaillé dans Actions tab
- Re-run possible via UI

**Durées estimées** :
- tests.yml : 3-5 min (total)
- lint.yml : < 1 min

📏 FORMAT DE SORTIE :
- Mermaid flowchart TB
- Utiliser subgraphs par workflow
- Utiliser des formes :
  * Hexagone : triggers
  * Rectangles : jobs
  * Rectangles arrondis : steps
  * Losange : décisions (pass/fail)
- Couleurs :
  * Success : vert
  * Failure : rouge
  * Neutral : gris
- Titre "## Pipeline CI/CD GitHub Actions"

🧪 CONTRAINTES :
- Les 2 workflows complets
- Matrix PHP visible
- Steps dans l'ordre
- Décisions pass/fail à la fin
- Relations entre jobs (parallèle, dépendance)
```

---

## 19. Diagramme des rôles et permissions

### 🎯 Objectif
**Matrice** de permissions par rôle.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un DIAGRAMME représentant la MATRICE DES PERMISSIONS par rôle.
Plusieurs options possibles :
- Option A : Tableau markdown + diagramme Mermaid
- Option B : Use Case diagram UML avec hiérarchie
- Option C : Mindmap Mermaid

Utilise l'**Option A** (tableau + diagramme visuel complémentaire).

📋 RÔLES :

- **Admin** : tous droits + monitoring + backups
- **Enseignant** (prof) : création examens + analytics
- **Étudiant** : passage examens par code
- **Invité** (anonyme, non connecté) : accès limité

📋 RESSOURCES / ACTIONS :

### Authentification
- Se connecter : admin ✅, prof ✅, étudiant ❌ (code seulement), invité ✅ (pour login)
- Consulter son profil : admin ✅, prof ✅, étudiant ❌, invité ❌
- Changer son password : admin ✅, prof ✅, étudiant ❌, invité ❌

### Comptes utilisateurs
- Créer : admin ✅, prof ❌, étudiant ❌, invité ❌
- Modifier (autre user) : admin ✅, prof ❌, étudiant ❌, invité ❌
- Modifier (soi-même) : admin ✅, prof ✅, étudiant N/A, invité ❌
- Désactiver : admin ✅, prof ❌
- Lister : admin ✅, prof ❌

### Banque de questions
- Consulter : admin ✅, prof ✅, étudiant ❌, invité ❌
- Créer : admin ✅, prof ✅
- Modifier : admin ✅, prof ✅ (les siennes)
- Supprimer : admin ✅, prof ⚠️ (limité)
- Exporter : admin ✅, prof ✅

### Examens
- Créer : admin ✅, prof ✅
- Lister (ses examens) : admin ✅ (tous), prof ✅ (les siens)
- Lister (tous) : admin ✅, prof ❌
- Modifier : admin ✅, prof ✅ (les siens)
- Publier : admin ✅, prof ✅ (les siens)
- Clôturer : admin ✅, prof ✅ (les siens)
- Archiver : admin ✅, prof ✅ (les siens)
- Supprimer : admin ✅, prof ✅ (si aucun passage)

### Passages
- Démarrer (avec code) : invité ✅ (via étudiant)
- Consulter correction (avec token) : invité ✅ (via étudiant)
- Lister par examen : admin ✅, prof ✅ (ses examens)
- Invalider : admin ✅, prof ✅ (ses examens)
- Supprimer : admin ✅ uniquement

### Analytics
- Consulter ses examens : admin ✅, prof ✅
- Consulter tous : admin ✅, prof ❌
- Exporter (CSV, Excel, PDF) : admin ✅, prof ✅

### Monitoring système
- Dashboard /admin/monitoring : admin ✅, prof ❌
- API /api/health : tout le monde ✅ (public)
- API /api/health?detailed=1 : admin ✅ (ou tout ? à décider)

### Backups
- Lister : admin ✅, prof ❌
- Créer : admin ✅, prof ❌
- Supprimer : admin ✅, prof ❌
- Télécharger : admin ✅, prof ❌
- Restaurer (via CLI) : admin ✅ (accès serveur)

### Rate limiting
- Limite requêtes/min :
  - admin : illimité
  - prof : 500
  - étudiant : 60
  - invité : 30

📋 HÉRARCHIE (héritage) :
- Admin HÉRITE DE Enseignant (tout ce qu'un prof peut, admin peut aussi)
- Pas d'héritage pour étudiant / invité

📏 FORMAT DE SORTIE :

**Partie 1 - Tableau markdown** (matrice complète) :

```markdown
| Action | Admin | Prof | Étudiant | Invité |
|---|:-:|:-:|:-:|:-:|
| Se connecter | ✅ | ✅ | ❌ | ✅ |
...
```

**Partie 2 - Diagramme Mermaid** (visualisation) :

Flowchart montrant :
- 4 "rôles" comme des boîtes
- Flèches vers les "ressources" accessibles
- Code couleur (vert = accès complet, orange = limité, rouge = refusé)

OU

Venn diagram simulé (approximatif en Mermaid) montrant les intersections.

🧪 CONTRAINTES :
- Tableau exhaustif (toutes les actions listées)
- Diagramme clair et lisible
- Hiérarchie admin → prof visible
- Limits rate limit mentionnés
- Titre "## Matrice des permissions par rôle"
```

---

## 20. Activity Diagram — Workflow complet examen

### 🎯 Objectif
**Activity diagram** UML pour le flux de bout en bout.

### 📋 Prompt

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Génère un DIAGRAMME D'ACTIVITÉ UML complet représentant le workflow de bout
en bout d'un examen, depuis sa création jusqu'à la consultation de la correction
par l'étudiant et l'analyse par le prof. Utilise PlantUML (@startuml avec
activity diagram beta).

📋 WORKFLOW COMPLET :

**Swimlane : Enseignant**

- Start
- "Se connecter à /admin/examens.html"
- "Cliquer + Nouvel examen"
- "Remplir formulaire (titre, questions, durée, dates)"
- "Sauver en brouillon"
- if (Prêt à publier ?) then (oui)
  - "Cliquer Publier"
  - [code d'accès généré]
  - "Communiquer code aux étudiants (email)"
- else (non)
  - "Modifier l'examen"
  - [retour sauver brouillon]
- endif

**Swimlane : Étudiant**

- "Recevoir email avec code"
- "Ouvrir /etudiant/passage.html"
- "Saisir code d'accès"
- if (Code valide + dans fenêtre ?) then (oui)
  - "Remplir infos (nom, prénom, email)"
  - "Démarrer examen [token généré]"
  - repeat
    - "Lire question"
    - "Choisir option"
    - "Réponse auto-sauvée"
  - repeat while (Questions restantes ET temps restant ?)
  - if (Temps épuisé ?) then (oui)
    - "Submit auto"
  - else
    - "Cliquer Submit"
  - endif
  - [score calculé + email envoyé + signature HMAC]
  - "Recevoir email de confirmation"
  - "Cliquer lien correction"
  - "Consulter correction détaillée"
  - "Télécharger PDF (optionnel)"
- else (non)
  - "Afficher erreur (code invalide ou fermé)"
- endif

**Swimlane : Système**

- Activités parallèles :
  - "Logger chaque action"
  - "Auto-save réponses toutes les 2s"
  - "Détecter focus events (copy, blur, devtools)"
  - "Si > seuil anomalies : invalider auto"
  - "À l'horaire date_cloture : closer l'examen auto"
  - "Cron 03:00 : backup quotidien"

**Swimlane : Enseignant (suite)**

- "Consulter /admin/analytics.html"
- "Sélectionner l'examen"
- fork
  - "Voir distribution scores"
  - "Analyser distracteurs"
  - "Consulter historique"
- endfork
- "Exporter résultats (CSV/Excel/PDF)"
- if (Anomalies détectées ?) then (oui)
  - "Review passages suspects"
  - if (Confirmer fraude ?) then (oui)
    - "Invalider passage manuellement"
  - endif
- endif
- "Clôturer examen (manuel ou auto)"
- "Archiver après quelques temps"

- End

📋 ÉVÉNEMENTS PARALLÈLES :

Représenter avec `fork`/`endfork` ou régions concurrentes :
- Étudiant répond + Système auto-save + Système logs
- Enseignant analyse + Système calcule stats à la demande

📏 FORMAT DE SORTIE :
- PlantUML @startuml avec activity diagram beta
- Syntaxe :
  ```
  @startuml
  start
  |Enseignant|
  :Créer examen;
  :Publier;
  |Étudiant|
  :Ouvrir passage;
  repeat
    :Répondre;
  repeat while (questions restantes)
  |Système|
  fork
    :Auto-save;
  fork again
    :Logger;
  end fork
  ...
  end
  @enduml
  ```
- Swimlanes (pipes |...|) pour séparer acteurs
- fork/endfork pour parallélisme
- repeat/while pour boucles
- if/else/endif pour branches
- Notes sur les activités clés

🧪 CONTRAINTES :
- 3 swimlanes minimum (Enseignant, Étudiant, Système)
- Workflow complet de A à Z (de la création à l'archivage)
- Parallélisme représenté
- Boucles et conditions visibles
- Titre "@title Workflow complet Examen - Activity Diagram"
```

---

## 🎁 Bonus : Prompt "tout-en-un"

Pour obtenir **un mega-diagramme combinant plusieurs vues** :

```
[COLLER LE BLOC DE CONTEXTE]

🎨 TÂCHE :
Je dois créer une présentation pour ma hiérarchie. Génère-moi un pack de
3 diagrammes Mermaid qui, ensemble, expliquent la plateforme :

1. **Vue utilisateur** : qui fait quoi (use case simplifié, 10 cas max)
2. **Vue technique** : architecture en couches (5 couches)
3. **Vue workflow** : flux typique création → passage → correction (séquence)

Pour chaque diagramme :
- Titre clair
- Max 15 nœuds/lignes
- Couleurs cohérentes
- Mermaid syntax valide

Présente les 3 dans l'ordre, séparés par des titres markdown ##.
Ajoute à chacun 2-3 phrases d'explication en introduction.
```

---

## 💡 Conseils pour obtenir les meilleurs résultats

### 1. **Itérer**
- Si le résultat est trop dense : demander une version "simplifiée" (moins de nœuds)
- Si trop simpliste : demander "plus de détails sur X"

### 2. **Ajuster le modèle LLM**
- **GPT-4** : excellent pour Mermaid et PlantUML
- **GPT-4o** : rapide, bon pour les itérations
- **GPT-5** : le meilleur pour la complexité et la cohérence
- **Claude 3.5/4** : excellent aussi, parfois meilleur sur les détails

### 3. **Phrases magiques**
Ajouter en fin de prompt si besoin :
- `"Réponds UNIQUEMENT avec le code, sans explications"`
- `"Code doit compiler sans erreur sur mermaid.live"`
- `"Vérifie la syntaxe avant de répondre"`
- `"Utilise des noms courts (max 15 chars) pour les nœuds"`

### 4. **Rendu des diagrammes**

#### Mermaid
- **Online** : https://mermaid.live/ (export PNG/SVG)
- **VS Code** : extension "Mermaid Preview"
- **Markdown** : GitHub/GitLab rendent nativement
- **Slides** : coller dans Excalidraw ou Draw.io

#### PlantUML
- **Online** : http://www.plantuml.com/plantuml/uml/
- **VS Code** : extension "PlantUML"
- **IntelliJ** : plugin PlantUML Integration
- **CLI** : `plantuml -tpng diagram.puml`

#### Graphviz/DOT
- **Online** : https://dreampuf.github.io/GraphvizOnline/
- **CLI** : `dot -Tpng diagram.dot -o diagram.png`

### 5. **Intégration dans votre documentation**

**Option 1 — Fichiers dans le repo** :
```
examens/docs/diagrammes/
├── 01_architecture_globale.mmd
├── 02_architecture_detaillee.mmd
├── 03_deploiement_ovh.mmd
├── 09_classes_complet.puml
├── 11_sequence_passage.puml
├── ...
```

**Option 2 — Embedded dans markdown** :
````markdown
# Mon document

## Architecture

```mermaid
flowchart LR
    A --> B
```
````

GitHub rend automatiquement les diagrammes Mermaid dans les `.md` !

**Option 3 — Images exportées** :
Export PNG/SVG depuis les outils en ligne, placer dans `docs/images/` et lier dans les `.md`.

---

## 📚 Ressources complémentaires

### Syntaxe Mermaid
- **Doc officielle** : https://mermaid.js.org/
- **Cheatsheet** : https://mermaid.js.org/syntax/classDiagram.html
- **Live editor** : https://mermaid.live/

### Syntaxe PlantUML
- **Doc officielle** : https://plantuml.com/
- **Cheatsheet UML** : https://plantuml.com/sequence-diagram
- **Themes** : https://plantuml.com/theme

### Outils complémentaires
- **Draw.io / diagrams.net** : éditeur graphique (peut importer Mermaid)
- **Excalidraw** : pour des schémas "à la main" avec import Mermaid natif
- **Lucidchart** : pro, payant
- **Miro** : collaboratif

---

## 🎯 Récapitulatif des prompts

| # | Diagramme | Format | Usage |
|:-:|---|---|---|
| 1 | Architecture globale | Mermaid flowchart | Présentation non-tech |
| 2 | Architecture détaillée | Mermaid flowchart | Dev onboarding |
| 3 | Déploiement OVH | Mermaid flowchart | DevOps |
| 4 | Séquence — Création examen | Mermaid seq | Dev, tests |
| 5 | Séquence — Passage | Mermaid seq | Dev, QA |
| 6 | Flux analytics | Mermaid flowchart | Dev, produit |
| 7 | Sécurité 6 couches | Mermaid | Security review |
| 8 | Topologie réseau | Mermaid | Ops |
| 9 | Classes complètes | PlantUML | Dev, review |
| 10 | Classes managers | PlantUML | Dev |
| 11 | Séquence passage UML | PlantUML | Formal doc |
| 12 | Séquence auth | PlantUML | Security |
| 13 | État — Examen | PlantUML | Dev, produit |
| 14 | État — Passage | PlantUML | Dev, produit |
| 15 | Use cases | PlantUML | Produit, analyst |
| 16 | ERD données | Mermaid | Data model |
| 17 | Composants React | PlantUML | Frontend dev |
| 18 | CI/CD pipeline | Mermaid | DevOps |
| 19 | Permissions | Tableau + Mermaid | Security, produit |
| 20 | Activity bout-en-bout | PlantUML | Vue globale |

---

## 🚀 Quick start : les 3 prompts ESSENTIELS

Si vous manquez de temps, **utilisez ces 3 en priorité** :

1. **Prompt #1** (Architecture globale) → compréhension générale
2. **Prompt #9** (Classes complètes) → référence technique
3. **Prompt #20** (Activity bout-en-bout) → flux métier

Ces 3 diagrammes couvrent 80% des besoins de documentation visuelle.

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
