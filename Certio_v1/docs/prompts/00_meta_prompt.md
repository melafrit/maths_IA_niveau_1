# 🎯 Méta-prompt — Contexte initial à donner à l'IA

## 📖 Description et contexte

Ce **méta-prompt** est à utiliser **en tout début de conversation** avec votre IA avant de demander des diagrammes. Il permet de :
- **Économiser des tokens** : le contexte est donné une seule fois
- **Garantir la cohérence** entre tous les diagrammes générés
- **Accélérer** les demandes ultérieures (plus besoin de re-expliquer)

### Quand l'utiliser
- Si vous prévoyez de demander **plusieurs diagrammes** dans une même session
- Pour documenter **tout le projet** en série
- Quand vous voulez garder une **conversation longue** avec l'IA

### Quand NE PAS l'utiliser
- Pour une demande **ponctuelle** d'un seul diagramme → utiliser directement le prompt spécifique
- Si vous changez de conversation/session → refaire le méta-prompt

---

## 🤖 Outils IA supportés

| Outil | Recommandation | Taille contexte supportée |
|---|---|---|
| **ChatGPT-4 / GPT-4o** | ✅ Excellent | ~128k tokens |
| **Claude 3.5/4 Sonnet** | ✅ Excellent | ~200k tokens |
| **Claude Opus 4** | ✅ Parfait | ~200k tokens |
| **Gemini 2.0 Pro** | ✅ Très bon | ~1M tokens |
| **Gemini 2.0 Flash** | ⚠️ Correct | ~1M tokens |
| **NanoBanana (Gemini)** | ⚠️ Modèle visuel | Variable |

---

## 📋 Version pour ChatGPT-4 / GPT-4o / Claude

```
Tu es un architecte logiciel senior expert en UML 2.5 et documentation technique.

Dans cette conversation, je vais te demander de générer plusieurs diagrammes (architecture, UML structurels, UML comportementaux) pour documenter une plateforme web éducative.

# CONTEXTE DU PROJET (à mémoriser pour TOUTE la conversation)

## Nom
IPSSI Examens — plateforme web d'examens QCM en ligne

## Organisation
École IPSSI (France), module "Mathématiques appliquées à l'IA"
- 26h de formation sur 4 jours
- Bachelor 2 Informatique
- Auteur : Mohamed EL AFRIT

## Stack technique
- **Backend** : PHP 8.3 strict types, **AUCUNE dépendance Composer** (natif pur)
- **Frontend** : React 18 via CDN UMD + Babel Standalone (pas de bundler, pas de build)
- **Persistance** : fichiers JSON dans data/ (pas de SQL, pas de base de données)
- **Libs frontend** : KaTeX (maths), Recharts (graphs), SheetJS (Excel), Pyodide (Python navigateur)
- **Hébergement** : OVH mutualisé (~8€/mois) OU OVH VPS Ubuntu (~10€/mois)
- **CI/CD** : GitHub Actions (tests.yml + lint.yml)

## Composants backend (17 managers PHP)
Namespace : Examens\Lib\

1. **Auth** : authentification (bcrypt cost 12, sessions)
2. **Session** : wrapper sessions PHP
3. **Csrf** : génération/validation tokens CSRF (timing-safe)
4. **Logger** : logging multi-niveaux (debug/info/warning/error)
5. **Response** : helpers JSON responses (ok, error, json)
6. **FileStorage** : lecture/écriture JSON thread-safe
7. **BanqueManager** : CRUD 320 questions (module/chapitre/thème)
8. **ExamenManager** : CRUD examens (status: draft/published/closed/archived)
9. **PassageManager** : gestion passages étudiants + signature HMAC SHA-256
10. **AnalyticsManager** : statistiques multi-vues (scores, distracteurs, timeline)
11. **BackupManager** : API PHP pour backups (wraps bash scripts)
12. **HealthChecker** : 7 checks systèmes (disk/memory/fs/counters/backups/logs/php)
13. **RateLimiter** : rate limit par bucket (existant, utilisé pour login)
14. **RoleRateLimiter** : middleware rate limit par rôle (admin illim, prof 500/min, étudiant 60/min, anonyme 30/min)
15. **Mailer** : envoi SMTP
16. **EmailTemplate** : templates HTML emails avec escape

## API REST (8 modules)
Dans backend/api/ :
- /api/auth (login/logout/me)
- /api/banque (questions CRUD)
- /api/comptes (users CRUD, admin only)
- /api/examens (CRUD + publish/close)
- /api/passages (start/saveAnswer/submit/focus-event)
- /api/corrections (consultation par token)
- /api/analytics (8 routes stats)
- /api/backups (liste/create/verify/download)
- /api/health (monitoring, exempt rate limit)

## Middleware (dans backend/public/index.php)
1. Rate limiting par rôle (RoleRateLimiter)
2. Auth check (session + role)
3. CSRF check (POST/PUT/DELETE)
4. Dispatch vers endpoint
5. Fail-open en cas d'erreur middleware

## Frontend
- **Pages HTML** : admin/banque, admin/examens, admin/analytics, admin/monitoring, etudiant/passage, etudiant/correction
- **50+ composants React** : Button, Modal, DataTable, Toast, Box, Card, Spinner, ThemeProvider, etc.
- **Hooks** : useApi, useAuth, useDebounce, useToast

## Data structure
```
data/
├── examens/EXM-XXXX-YYYY.json
├── passages/PSG-WXYZ-5678.json
├── comptes/USR-xxxx.json
├── banque/{module}/{chapitre}/{id}.json
├── sessions/ (PHP native)
├── backups/backup_YYYY-MM-DD_HHMMSS.tar.gz (+.sha256)
├── logs/app.log, auth.log, backups.log
└── _ratelimit/api_{role}_{hash16}.json
```

## Acteurs
1. **Administrateur** : droits complets (tous examens, tous users, monitoring, backups)
2. **Enseignant (prof)** : ses propres examens, banque (CRUD), analytics
3. **Étudiant** : accès par code + infos perso, passage, consultation correction
4. **Système Cron** : backups auto 03:00
5. **Service SMTP** : envoi emails confirmation

## Sécurité
6 couches :
1. Rate limiting par rôle
2. Auth (bcrypt + sessions)
3. CSRF tokens (timing-safe hash_equals)
4. Validation stricte IDs (regex EXM-[A-Z0-9]{4}-[A-Z0-9]{4})
5. Signatures HMAC SHA-256 (passages)
6. Escape HTML output (EmailTemplate::e)

## Tests
- 389/389 tests ✅ (100%)
- 17 suites en 4 catégories : UNIT (199), INTEGRATION (65), SECURITY (85), E2E (40)
- Harness unifié : backend/tests/run_all.php

## Phases livrées (P0 → P9)
Toutes terminées ✅, projet en version 1.0.0, production-ready.

# RÈGLES POUR CETTE CONVERSATION

1. Quand je demande un diagramme, **fournis TOUJOURS** :
   - Le code Mermaid **complet et valide** (prêt à copier sur mermaid.live)
   - Si pertinent : aussi la version PlantUML
   - Une courte **explication** du choix de représentation

2. **Style attendu** :
   - Emoji pour identifier les composants (🐘 PHP, ⚛️ React, 💾 data, 🔒 security)
   - Couleurs sémantiques (rouge=sécurité, bleu=API, vert=métier, jaune=data)
   - Subgraphs pour grouper logiquement
   - Labels sur les flèches (protocoles, méthodes)

3. **Qualité** :
   - Code directement utilisable (pas de pseudo-code)
   - Diagrammes lisibles (pas plus de 30 nœuds par vue)
   - Respect strict de la syntaxe UML 2.5

4. **Format de réponse** :
   - Code dans un bloc markdown ```mermaid ou ```plantuml
   - Précéder du titre du diagramme
   - Suivi de notes d'utilisation

5. Si je demande "simplifie" ou "ajoute des détails", itère sur le même diagramme sans tout recommencer.

# CONFIRMATION

Confirme que tu as bien compris le contexte, puis attends mes demandes de diagrammes. Ne génère aucun diagramme avant que je te le demande explicitement.
```

---

## 📋 Version pour Gemini Pro / 2.0 Flash

```
Contexte pour cette conversation : Je veux documenter une plateforme web avec des diagrammes.

PROJET : IPSSI Examens (plateforme d'examens en ligne pour école IPSSI)

STACK :
- Backend : PHP 8.3 natif, 17 managers, 8 APIs REST
- Frontend : React 18 CDN + Babel Standalone
- Data : fichiers JSON (pas de SQL)
- Hébergement : OVH mutualisé/VPS
- 389 tests auto
- CI/CD GitHub Actions

COMPOSANTS CLÉS :
- Managers : Auth, Csrf, Session, Logger, FileStorage, ExamenManager, PassageManager, BanqueManager, AnalyticsManager, BackupManager, HealthChecker, RateLimiter, RoleRateLimiter, Mailer, EmailTemplate, Response
- API : /api/auth, /api/examens, /api/passages, /api/banque, /api/corrections, /api/analytics, /api/backups, /api/comptes, /api/health
- Middleware : RateLimit par rôle (admin illim, prof 500/min, etudiant 60/min, anonyme 30/min), Auth, CSRF
- Acteurs : Admin, Prof, Étudiant + Cron + SMTP

Je te donnerai ensuite des demandes précises de diagrammes (architecture, UML classes, séquence, etc.). Pour chaque demande :
- Produis le code Mermaid valide (copiable sur mermaid.live)
- Ou PlantUML si mieux adapté
- Avec emojis et couleurs pour lisibilité
- Pas plus de 30 nœuds par diagramme

Confirme le contexte puis attends mes questions.
```

---

## 📋 Version pour Claude Opus (avec réflexion étendue)

Claude Opus peut bénéficier d'un prompt plus structuré exploitant sa capacité de raisonnement :

```
<role>
Tu es un architecte logiciel senior avec 15 ans d'expérience en documentation UML et systèmes distribués. Tu excelles particulièrement en :
- UML 2.5 complet (tous les 14 diagrammes)
- Architectures de microservices et monolithes modulaires
- Mermaid.js et PlantUML (syntaxes maîtrisées)
- Pédagogie : expliquer des concepts techniques clairement
</role>

<project>
<name>IPSSI Examens</name>
<purpose>Plateforme web d'examens QCM pour l'école IPSSI</purpose>

<stack>
  <backend>
    <language>PHP 8.3 (strict types)</language>
    <dependencies>AUCUNE (pas de Composer)</dependencies>
    <architecture>Managers pattern + file storage</architecture>
  </backend>
  <frontend>
    <framework>React 18 via CDN UMD</framework>
    <transpiler>Babel Standalone (runtime navigateur)</transpiler>
    <libs>KaTeX, Recharts, SheetJS, Pyodide</libs>
  </frontend>
  <persistence>JSON files in data/</persistence>
  <hosting>OVH (mutualisé ou VPS Ubuntu)</hosting>
</stack>

<components>
  <managers count="17">
    Auth, Session, Csrf, Logger, Response, FileStorage,
    BanqueManager, ExamenManager, PassageManager, AnalyticsManager,
    BackupManager, HealthChecker, RateLimiter, RoleRateLimiter,
    Mailer, EmailTemplate
  </managers>
  <apis count="9">auth, banque, comptes, examens, passages, corrections, analytics, backups, health</apis>
  <middleware>RateLimit par rôle, Auth, CSRF</middleware>
</components>

<actors>
  <primary>Admin, Enseignant, Étudiant</primary>
  <secondary>Cron, SMTP</secondary>
</actors>

<quality>
  <tests>389/389 ✅</tests>
  <categories>UNIT (199), INTEGRATION (65), SECURITY (85), E2E (40)</categories>
</quality>
</project>

<instructions>
Tu vas recevoir des demandes de diagrammes. Pour chacune :

1. **Analyse** brièvement ce qui est demandé
2. **Choisis** le type de diagramme UML le plus adapté
3. **Génère** le code Mermaid complet et valide
4. **Explique** en 2-3 lignes les choix de représentation
5. **Propose** éventuellement une alternative (PlantUML, C4, etc.)

Format de réponse :
- Titre du diagramme
- Code dans bloc ```mermaid```
- Notes d'utilisation
- Si pertinent : version PlantUML dans ```plantuml```

Style :
- Emojis pour typage visuel
- Couleurs sémantiques
- Subgraphs pour grouper
- Max 30 nœuds par vue
</instructions>

<wait_for_request>
Confirme que le contexte est clair, puis attends ma première demande de diagramme.
</wait_for_request>
```

---

## 🎯 Exemples de commandes post-contexte

Une fois le méta-prompt validé, utilisez ces formulations courtes :

### Simples
- "Génère le diagramme d'architecture globale"
- "Produis le diagramme de classes UML"
- "Fais le diagramme de séquence pour la création d'un examen"

### Avec contraintes
- "Diagramme d'architecture en couches, max 20 nœuds, couleurs pastel"
- "Diagramme de séquence du passage étudiant, inclure les 3 middlewares explicitement"
- "Use case diagram PlantUML format, avec héritage Admin → Prof"

### Itératives
- "Simplifie ce diagramme"
- "Ajoute les détails de sécurité"
- "Produis une version 16:9 landscape"
- "Sépare en 2 diagrammes : haut niveau + détaillé"

---

## 🎨 Rendu final

Une fois le code généré par l'IA :

| Format | Outil | URL |
|---|---|---|
| Mermaid | Mermaid Live | https://mermaid.live/ |
| PlantUML | PlantText | https://www.planttext.com/ |
| Multi | Kroki | https://kroki.io/ |
| Édition | Draw.io | https://app.diagrams.net/ |

**Workflow recommandé** :
1. Code généré par IA → copier
2. Coller sur mermaid.live → voir aperçu
3. Export SVG → édition Figma/Illustrator si besoin
4. Export PNG haute résolution pour présentation

---

## 💡 Astuces pour optimiser la sortie

### Pour ChatGPT
- Demander explicitement **"pas de pseudo-code"** pour éviter les ...
- Préciser **"complet et fonctionnel"**

### Pour Claude
- Utiliser les balises XML (comme dans l'exemple Opus)
- Demander de **justifier les choix architecturaux**

### Pour Gemini
- Être **direct et concis** (Gemini préfère des prompts structurés)
- Utiliser des **listes à puces** plutôt que du texte narratif

### Pour tous
- **Itérer** : si le premier résultat est trop dense, demander simplification
- **Valider** : toujours tester le code sur mermaid.live avant de l'intégrer

---

## 📞 Contact

- **Auteur** : Mohamed EL AFRIT — IPSSI
- **Repo** : https://github.com/melafrit/maths_IA_niveau_1

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
