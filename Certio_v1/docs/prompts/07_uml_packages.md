# 📐 Prompt 07 — Diagramme de packages UML

## 📖 Description et contexte

Ce prompt génère un **diagramme de packages** montrant l'organisation modulaire du projet (dossiers / namespaces) et les dépendances entre packages.

### Ce qui est généré
- Structure hiérarchique des dossiers
- Namespaces PHP (`Examens\Lib`)
- Dépendances inter-packages avec stéréotypes
- Nombre de fichiers par package
- Icônes par type de contenu

### Quand utiliser
- **Découverte** du projet par un nouveau dev
- **Refactoring** : identifier dépendances circulaires
- Documentation de **modularité** architecturale
- Planning de **migration** (ex: vers MySQL)

### Outil recommandé
**Mermaid** avec subgraphs nested.

---

## 🤖 Outils IA supportés

| Outil | Qualité | Notes |
|---|:-:|---|
| **ChatGPT-4 / GPT-4o** | ⭐⭐⭐⭐⭐ | Excellent structure |
| **Claude Opus 4** | ⭐⭐⭐⭐⭐ | Très clair |
| **Gemini 2.0 Pro** | ⭐⭐⭐⭐ | Bon |

---

## 📋 Version pour ChatGPT-4 / GPT-4o

```
Tu es un architecte logiciel expert en UML et organisation modulaire.

CONTEXTE :
Structure de packages (namespaces / dossiers) de la plateforme IPSSI Examens :

PACKAGE ROOT : examens/

PACKAGE backend/
  PACKAGE backend/lib/ (namespace Examens\Lib)
    - Auth, Session, Csrf, Logger, Response, FileStorage
    - BanqueManager, ExamenManager, PassageManager
    - AnalyticsManager, BackupManager, HealthChecker
    - RateLimiter, RoleRateLimiter
    - Mailer, EmailTemplate
  PACKAGE backend/api/ (endpoints REST)
    - auth.php, banque.php, comptes.php, examens.php
    - passages.php, corrections.php, analytics.php, backups.php
  PACKAGE backend/public/
    - index.php (router)
  PACKAGE backend/tests/
    - run_all.php (harness)
    - test_*.php (suites par module)
    - security/ (CSRF, auth, XSS, injection)
    - e2e/ (workflow complet)
  PACKAGE backend/templates/emails/
    - HTML templates pour Mailer

PACKAGE frontend/
  PACKAGE frontend/admin/
    - banque.html, examens.html, analytics.html, monitoring.html
  PACKAGE frontend/etudiant/
    - passage.html, correction.html
  PACKAGE frontend/assets/
    - main.css (design system)
    - components-base.js (Button, Input, Box)
    - components-advanced.js (Modal, Table, Toast)
    - hooks.js (useApi, useAuth, useDebounce)
    - analytics.jsx, monitoring.jsx, ... (apps principales)

PACKAGE data/
  - examens/, passages/, comptes/, banque/
  - sessions/, backups/, logs/, _ratelimit/
  - config/

PACKAGE scripts/
  - backup.sh, restore.sh, install-cron.sh
  - init_comptes.php, reset_password.php

PACKAGE docs/
  - ARCHITECTURE.md, GUIDE_ADMIN.md, GUIDE_PROFESSEUR.md
  - GUIDE_ETUDIANT.md, INSTALLATION.md, DEPLOIEMENT_OVH.md
  - BACKUPS.md, RATE_LIMITING.md, TESTING.md, CI_CD.md, EMAILS.md

PACKAGE .github/workflows/
  - tests.yml, lint.yml

DÉPENDANCES ENTRE PACKAGES :
- frontend/* → backend/api/* (HTTP REST)
- backend/api/* → backend/lib/* (PHP require + use)
- backend/lib/* → data/* (I/O fichiers)
- backend/public/index.php → backend/api/*, backend/lib/Auth, RoleRateLimiter
- backend/tests/* → backend/lib/* (pour tester)
- scripts/*.sh → data/* (backup ops)
- .github/workflows/* → backend/tests/* (CI)

OBJECTIF :
Génère un diagramme de packages UML au format Mermaid.

TYPE :
Utiliser graph TB avec des subgraphs nommés pour chaque package.

FONCTIONNALITÉS À INCLURE :
- Hiérarchie claire (backend, frontend, data, scripts, docs, .github)
- Flèches de dépendance avec stéréotypes UML :
  - <<use>> pour utilisation directe
  - <<http>> pour API REST call
  - <<fs>> pour accès fichier
  - <<require>> pour PHP require
- Nombre de fichiers par package indiqué
- Icône emoji par type de package (🐘 PHP, ⚛️ React, 💾 data, 📜 scripts, 📚 docs)

ORGANISATION :
- Haut : frontend (UI)
- Centre : backend (logique)
- Bas : data + scripts (infrastructure)
- Côté : docs + .github (transverse)

FORMAT :
Code Mermaid avec :
- Subgraphs imbriqués si nécessaire (backend > lib, api, tests)
- Couleurs par type
- Annotations sur les flèches

CRITÈRES :
- Montre l'architecture modulaire claire
- Dépendances bien typées
- Pas de dépendances circulaires visibles
- Lisible et compact

Génère le code Mermaid complet maintenant.
```

---

## 📋 Version pour Claude / Gemini

Claude et Gemini peuvent utiliser la même version directe (structure déjà optimisée).

```
Génère un diagramme de packages Mermaid pour IPSSI Examens.

STRUCTURE :

📁 examens/
├── 🐘 backend/
│   ├── lib/ (16 managers PHP - namespace Examens\Lib)
│   ├── api/ (9 endpoints REST)
│   ├── public/ (index.php router)
│   ├── tests/ (389 tests, 4 catégories)
│   └── templates/emails/
├── ⚛️ frontend/
│   ├── admin/ (4 pages HTML)
│   ├── etudiant/ (2 pages HTML)
│   └── assets/ (50+ composants React)
├── 💾 data/
│   └── [examens, passages, comptes, banque, sessions, backups, logs, _ratelimit, config]
├── 📜 scripts/
│   └── [backup.sh, restore.sh, install-cron.sh + 2 PHP scripts]
├── 📚 docs/ (12+ markdown)
└── 🤖 .github/workflows/ (tests.yml, lint.yml)

DÉPENDANCES :
- frontend → backend/api (HTTP REST)
- backend/api → backend/lib (require + use)
- backend/lib → data (file I/O)
- backend/public → backend/api + backend/lib (dispatch + middleware)
- backend/tests → backend/lib (testing)
- scripts → data (bash ops)
- .github/workflows → backend/tests (CI)

RÈGLES :
- Format : Mermaid graph TB
- Subgraphs imbriqués pour la hiérarchie
- Stéréotypes UML sur flèches : <<http>>, <<require>>, <<fs>>, <<use>>
- Emojis par type de package
- Nombre de fichiers entre parenthèses
- Couleurs : jaune pour data, vert pour backend, bleu pour frontend, violet pour docs/CI

Génère le code Mermaid.
```

---

## 🎨 Rendu

### Outils

- https://mermaid.live/ (Mermaid)
- Export SVG/PNG

### Intégration

Section "Structure" dans README ou ARCHITECTURE.md.

---

## 💡 Variations

### Vue "Dependency Matrix"
*"Transforme ce diagramme en matrice de dépendances (tableau) avec X et Y = packages, et case = type de dépendance."*

### Focus sur les tests
*"Mets en évidence uniquement les dépendances liées au testing et CI/CD."*

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
