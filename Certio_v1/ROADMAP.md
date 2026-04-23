# 🗺️ Roadmap — Plateforme d'examens IPSSI

> Plan de livraison détaillé en **9 phases**, de la fondation backend
> jusqu'au soft launch en production.

**Version** : 1.0
**Date de création** : 2026-04-21
**Auteur** : Mohamed EL AFRIT — IPSSI

---

## 📋 Vue d'ensemble

| Phase | Nom | Statut | Durée estimée | Commits prévus |
|:-:|---|:-:|:-:|:-:|
| **P0** | Cadrage + Structure repo | 🟢 **En cours** | 3h | 2-3 |
| **P1** | Fondations backend | 🔴 À venir | 16h | 5-6 |
| **P2** | Design system + frontend commun | 🔴 | 15h | 4-5 |
| **P3** | Banque de questions (CRUD + I/O) | 🔴 | 14h | 4-5 |
| **P4** | Génération IA + Migration J1-J2 | 🔴 | 11h | 3-4 |
| **P5** | Création examen + Passage étudiant + Focus-lock | 🔴 | 26h | 8-10 |
| **P6** | Correction étudiant + PDF + Notifications | 🔴 | 15h | 4-5 |
| **P7** | Historique prof + Analytics approfondies | 🔴 | 18h | 5-6 |
| **P8** | Tests + CI/CD + Backups | 🔴 | 18h | 5-6 |
| **P9** | Documentation finale + Soft launch | 🔴 | 8h | 3-4 |
| **TOTAL** | | | **~144h** | **~45 commits** |

⚠️ **Les durées sont indicatives** (dev continu intensif). En réalité, prévoir 2× si
intercalé avec des pauses, validations utilisateur, corrections d'anomalies.

---

## 🧭 Principes de la roadmap

### Chaque phase est :
- ✅ **Autonome** : livrable testable indépendamment
- ✅ **Validée** avant passage à la suivante (retour utilisateur)
- ✅ **Documentée** dans le CHANGELOG
- ✅ **Commitée** progressivement (pas de "big commit" en fin de phase)
- ✅ **Non-bloquante** : si une phase prend du retard, les autres s'enchaînent

### Règles de transition entre phases
- ✅ La phase doit être poussée sur `main`
- ✅ Le CHANGELOG doit être à jour
- ✅ L'utilisateur doit avoir testé et donné son feu vert
- ✅ Les tests de cette phase doivent passer (à partir de P8)

---

## 📦 Phase P0 — Cadrage + Structure repo

**Objectif** : Poser les fondations documentaires et la structure du projet.

### Livrables
- [x] `README.md` — Vue d'ensemble (310 lignes)
- [x] `NOTE_CADRAGE.md` — Cadrage des 20 décisions (870 lignes)
- [ ] `ROADMAP.md` — Ce document
- [ ] `CONVENTIONS.md` — Conventions code/commit
- [ ] `CHANGELOG.md` — Historique versions (commence à 0.0.1)
- [ ] `LICENSE` — CC BY-NC-SA 4.0
- [ ] `.gitignore` — Exclusions Git (PHP, Node, Python, IDE)
- [ ] Structure des sous-dossiers complète avec `.gitkeep`
- [ ] `docs/` — Placeholders des guides (complétés en P9)
- [ ] `.github/` — Templates Issues et PR + workflows CI placeholder

### Critères d'acceptation
- ✅ Structure du dossier `examens/` visible sur GitHub
- ✅ Tous les documents de cadrage lisibles en ligne
- ✅ Template d'Issue GitHub fonctionnel

### Durée : 3h
### Fin prévue : validation utilisateur fin P0

---

## 🏗️ Phase P1 — Fondations backend

**Objectif** : Mettre en place le backend PHP minimal avec authentification et
comptes enseignants.

### Contenu

#### Backend PHP structuré
```
backend/
├── api/
│   ├── auth.php              # Login, logout, check-session
│   ├── comptes.php           # CRUD comptes enseignants (admin only)
│   └── health.php            # Endpoint de diagnostic
├── lib/
│   ├── Auth.php              # Classe d'authentification (bcrypt)
│   ├── Session.php           # Gestion sessions sécurisées
│   ├── Logger.php            # Logs applicatifs
│   ├── Response.php          # Helpers JSON response
│   ├── Validator.php         # Validation des entrées
│   └── FileStorage.php       # Lecture/écriture fichiers avec flock
├── public/
│   ├── index.php             # Point d'entrée (routing)
│   └── .htaccess             # Redirect + sécurité
├── config.sample.php         # Template de configuration
└── bootstrap.php             # Chargement initial (autoload, session)
```

#### Pages de base
- `/examens/login.html` — Page de connexion enseignant
- `/examens/logout.html` — Page de déconnexion
- `/examens/404.html` — Erreur 404
- `/examens/500.html` — Erreur serveur

#### Scripts utilitaires
- `scripts/init_comptes.php` — Créer le premier compte admin
- `scripts/reset_password.php` — Reset mot de passe en ligne de commande

### Fonctionnalités implémentées
- [x] Création du compte admin (via script CLI)
- [x] Login email + password avec bcrypt
- [x] Session PHP sécurisée (HttpOnly, SameSite, Secure)
- [x] Rate limiting (5 tentatives / 15 min par IP)
- [x] CSRF token dans tous les formulaires
- [x] Logs de sécurité (login success/fail)
- [x] CRUD enseignants (admin peut ajouter/supprimer/désactiver)
- [x] Changement mot de passe (par l'enseignant lui-même)
- [x] API `/api/health` pour monitoring

### Livrables
- [ ] Code backend complet
- [ ] Page login fonctionnelle
- [ ] Documentation d'installation dans `docs/GUIDE_DEPLOIEMENT_OVH.md`
- [ ] Premier test E2E (login basique)

### Critères d'acceptation
- ✅ L'admin peut se connecter
- ✅ L'admin peut créer un compte enseignant
- ✅ L'enseignant peut se connecter
- ✅ Sessions sécurisées (cookies HttpOnly)
- ✅ Tests manuels de sécurité basiques OK

### Durée : 16h
### Commits prévus : 5-6

---

## 🎨 Phase P2 — Design system + frontend commun

**Objectif** : Créer la base visuelle professionnelle réutilisée partout.

### Contenu

#### Design system
```
frontend/assets/
├── tokens.css                # Variables CSS (couleurs, spacing, typos)
├── fonts.css                 # Import Inter, Manrope, JetBrains Mono
├── reset.css                 # Normalize moderne
├── animations.css            # Keyframes (fadeIn, slideDown, spin)
└── theme.css                 # Thèmes clair/sombre
```

#### Composants React réutilisables
```
frontend/assets/components/
├── Button.js              # 5 variants (primary/secondary/success/danger/ghost)
├── Input.js               # Text/email/password avec validation
├── Select.js              # Dropdown avec recherche
├── Checkbox.js
├── Radio.js
├── TextArea.js
├── Modal.js               # Avec overlay flouté, fermeture ESC
├── Toast.js               # Notifications in-app (success/info/warning/error)
├── Tooltip.js
├── Popover.js
├── Dropdown.js            # Menu déroulant
├── Card.js                # Conteneur générique
├── Box.js                 # 6 types pédagogiques (définition, retenir, etc.)
├── Badge.js
├── Avatar.js
├── Skeleton.js            # Chargement progressif
├── Tabs.js
├── Accordion.js
├── DataTable.js           # Tableau riche (tri, filtre, pagination)
├── FormBuilder.js         # Générateur de formulaires (wizard)
├── ChronoDisplay.js       # Affichage chronomètre stylé
├── KatexMath.js           # Helper KaTeX
├── RichText.js            # Rendu texte enrichi (code + KaTeX)
├── CodeBlock.js           # Bloc de code coloré
├── ThemeToggle.js         # 🌙/☀️
├── LanguageSelector.js    # Sélecteur langue (pour i18n)
└── CommandPalette.js      # Cmd+K (recherche globale)
```

#### Hooks custom
```
frontend/assets/hooks/
├── useTheme.js            # Gestion thème clair/sombre
├── useTranslation.js      # i18n avec fallback
├── useLocalStorage.js
├── useApi.js              # Fetch + gestion erreurs + auth
├── useToast.js
├── useModal.js
├── useKeyboardShortcut.js # Cmd+K, Cmd+N, etc.
├── useDebounce.js
└── useAuth.js             # État de session
```

#### i18n
```
frontend/assets/i18n/
├── fr.json                # ~200 clés en français
├── en.json                # Vide (à traduire plus tard)
└── config.js              # Détection auto + fallback
```

#### Layout & Navigation
```
frontend/assets/layouts/
├── PublicLayout.js        # Pages sans auth (login, 404)
├── ProfLayout.js          # Sidebar + header + contenu
├── StudentLayout.js       # Plein écran immersif
└── AdminLayout.js         # Admin avec menus spéciaux
```

### Livrables
- [ ] Design system documenté (`docs/DESIGN_SYSTEM.md`)
- [ ] Composants testés visuellement
- [ ] Page de démonstration `/examens/frontend/commun/demo.html`
- [ ] i18n fonctionnel avec FR

### Critères d'acceptation
- ✅ La page login de P1 utilise les nouveaux composants
- ✅ Thème clair/sombre opérationnel partout
- ✅ Tous les composants responsive (mobile/desktop)
- ✅ Accessibilité clavier de base OK
- ✅ Tous les textes en français via i18n

### Durée : 15h
### Commits prévus : 4-5

---

## 📚 Phase P3 — Banque de questions (CRUD + Import/Export)

**Objectif** : Interface complète de gestion de la banque de questions.

### Contenu

#### API backend
```
backend/api/banque.php
├── GET    /api/banque/questions             # Liste filtrée
├── GET    /api/banque/questions/{id}        # Détail
├── POST   /api/banque/questions             # Créer
├── PUT    /api/banque/questions/{id}        # Modifier
├── DELETE /api/banque/questions/{id}        # Supprimer (soft delete)
├── POST   /api/banque/duplicate/{id}        # Dupliquer
├── POST   /api/banque/import                # Import JSON/Excel
├── GET    /api/banque/export                # Export JSON/Excel/PDF
└── GET    /api/banque/modules               # Liste modules/chapitres
```

#### Lib services
```
backend/lib/
├── BanqueManager.php         # CRUD sur les JSON de banque
├── QuestionValidator.php     # Validation avant sauvegarde
├── ImportExporter.php        # Conversions JSON/Excel
└── IndexRebuilder.php        # Maintenance de l'index.json
```

#### Pages enseignant
```
frontend/enseignant/
├── banque_liste.html         # Liste + filtres + recherche
├── banque_editer.html        # Formulaire création/édition
└── banque_preview.html       # Prévisualisation question
```

### Fonctionnalités
- [x] Liste paginée avec filtres multi-critères
- [x] Recherche textuelle dans les énoncés
- [x] Filtres : module / chapitre / thème / difficulté / type / tags
- [x] Création de question avec preview KaTeX temps réel
- [x] Édition d'une question existante
- [x] Duplication (pour variantes)
- [x] Archivage (soft delete, garde l'historique)
- [x] Import Excel (template fourni)
- [x] Import JSON (reprise d'une autre banque)
- [x] Export JSON / Excel / PDF
- [x] Actions en masse (sélectionner plusieurs → archiver/exporter)
- [x] Historique des modifications (date, auteur)
- [x] Stats d'utilisation (dans combien d'examens utilisée)

### Livrables
- [ ] Backend complet avec API REST
- [ ] 3 pages frontend complètes
- [ ] Template Excel d'import
- [ ] Tests unitaires PHPUnit des validations

### Critères d'acceptation
- ✅ Créer, éditer, supprimer une question
- ✅ Preview KaTeX fonctionne en temps réel
- ✅ Import Excel avec validation des erreurs
- ✅ Export JSON peut être ré-importé (round-trip OK)
- ✅ Recherche + filtres fluides
- ✅ Accessibilité WCAG AA validée

### Durée : 14h
### Commits prévus : 4-5

---

## 🤖 Phase P4 — Génération IA + Migration J1-J2

**Objectif** : Intégrer la génération IA dans la banque + importer les 50 questions
existantes avec amélioration qualitative.

### Contenu

#### Module IA
```
backend/lib/
├── IaClient.php              # Classe abstraite
├── AnthropicClient.php       # Implémentation Claude
├── OpenAiClient.php          # Implémentation OpenAI
├── IaPromptBuilder.php       # Construction des prompts
└── IaQuestionValidator.php   # Validation spécifique IA
```

#### API backend
```
backend/api/ia.php
├── POST /api/ia/generate         # Génération de N questions
├── GET  /api/ia/models           # Liste modèles disponibles
├── POST /api/ia/estimate-cost    # Estimation coût avant génération
└── GET  /api/ia/history          # Historique des générations
```

#### Config enseignant
```
frontend/enseignant/profil.html
└── Section "Clés API IA" :
    ├── Clé Anthropic (chiffrée AES-256 serveur)
    ├── Clé OpenAI (chiffrée)
    ├── Bouton test validité
    └── Stats consommation mensuelle
```

#### Page de génération IA
```
frontend/enseignant/banque_generer_ia.html
├── Choix du modèle (avec prix indicatif)
├── Formulaire descriptif :
│   ├── Module/Chapitre/Thème cibles
│   ├── Difficulté souhaitée
│   ├── Type (conceptuel/calcul/code)
│   ├── Nombre de questions
│   ├── Instructions spéciales (contexte pédago, fil rouge...)
│   └── Langue (FR par défaut)
├── Prévisualisation des questions générées
├── Validation humaine obligatoire par question
└── Enregistrement dans la banque
```

#### Migration J1-J2
```
scripts/migrer_qcm_j1j2.py
└── Lit qcm_eval_J1_J2/questions.json + corrections.json
    └── Fusion + catégorisation automatique
    └── Enrichissement IA (amélioration qualitative)
    └── Écriture dans examens/data/banque/maths_ia_b2/
    └── Rapport de migration (md)
```

### Fonctionnalités IA
- [x] Génération 1-10 questions par appel
- [x] Choix du modèle (4 options : Claude Sonnet/Opus, GPT-4o/turbo)
- [x] Prompt templating (fichier éditable par admin)
- [x] Validation humaine systématique avant enregistrement
- [x] Bouton "Régénérer" si mauvais résultat
- [x] Journalisation complète (prompt, réponse, coût, auteur)
- [x] Protection confidentialité (jamais de données étudiants envoyées)
- [x] Estimation de coût avant génération
- [x] Quota par enseignant (configurable, défaut illimité)

### Migration J1-J2
- [x] Script Python automatique
- [x] Import des 50 questions
- [x] Amélioration qualitative :
  - Correction typos
  - Enrichissement explications (+IA si possible)
  - Ajout tags pertinents
  - Clarification pièges
  - Homogénéisation style
- [x] Rapport `migration_J1_J2_rapport.md` listant les améliorations

### Livrables
- [ ] Module IA backend (AnthropicClient + OpenAiClient)
- [ ] Page génération IA frontend
- [ ] Page profil avec gestion clés API
- [ ] Script migration J1-J2
- [ ] Rapport de migration commité
- [ ] 50 questions migrées visibles dans la banque

### Critères d'acceptation
- ✅ L'enseignant peut configurer ses clés API
- ✅ Clés API chiffrées AES-256 en base (fichier JSON)
- ✅ Génération IA fonctionne avec les 2 providers
- ✅ Validation humaine obligatoire avant enregistrement
- ✅ 50 questions historiques visibles dans la banque
- ✅ Rapport de migration complet et vérifié

### Durée : 11h
### Commits prévus : 3-4

---

## 📝 Phase P5 — Création examen + Passage étudiant + Focus-lock

**Phase la plus volumineuse** — cœur de la plateforme.

**Objectif** : Permettre au prof de créer un examen complet, et à l'étudiant de le passer
avec toutes les protections anti-triche.

### Contenu

#### Création d'examen (prof)
```
frontend/enseignant/examen_creer.html
└── Wizard en 4 étapes :
    ├── Étape 1 — Métadonnées (titre, école, promo, année, date...)
    ├── Étape 2 — Contenu (filtres + sélection hybride B3)
    ├── Étape 3 — Paramètres (durée, sécurité, correction, notifications)
    └── Étape 4 — Validation + génération code
```

#### API backend
```
backend/api/examens.php
├── POST   /api/examens/create        # Créer un examen
├── GET    /api/examens/{code}        # Détail
├── PUT    /api/examens/{code}        # Modifier (si aucun passage)
├── DELETE /api/examens/{code}        # Supprimer
├── POST   /api/examens/{code}/pause  # Mettre en pause
├── POST   /api/examens/{code}/close  # Clôturer manuellement
├── POST   /api/examens/{code}/duplicate # Dupliquer

backend/api/passages.php
├── POST   /api/passages/start        # Démarrer un passage
├── POST   /api/passages/heartbeat    # Signaler activité (option)
├── POST   /api/passages/submit       # Soumettre
├── POST   /api/passages/save-draft   # Sauvegarder partiellement
├── POST   /api/passages/cheat-event  # Signaler événement anti-triche
└── POST   /api/passages/cancel       # Annuler par triche
```

#### Passage étudiant
```
frontend/etudiant/
├── acces.html                # Saisie code + identité
├── regles.html               # Écran règles (anti-triche)
├── qcm.html                  # Interface QCM avec chronomètre
└── soumis.html               # Écran post-soumission
```

#### Composants anti-triche
```
frontend/assets/components/
├── AntiCheatGuard.js         # Wrapper qui encapsule le QCM
├── FocusLockHook.js          # Hook useFocusLock()
├── CheatWarningModal.js      # Alerte 5 sec
├── CheatCancelScreen.js      # Écran annulation
├── ExamRulesScreen.js        # Règles avant démarrage
└── FullscreenManager.js      # Gestion plein écran
```

### Fonctionnalités

#### Côté prof
- [x] Wizard création en 4 étapes
- [x] Sélection questions hybride (filtres + affinage manuel)
- [x] Preview des questions sélectionnées
- [x] Génération automatique du code IPSSI-B2-2026-A4F7
- [x] QR code + lien direct pour étudiants
- [x] Email de confirmation au prof créateur
- [x] Modification tant qu'aucun passage n'a eu lieu
- [x] Clôture manuelle / pause

#### Côté étudiant
- [x] Saisie code + identité (validation serveur)
- [x] Consentement RGPD
- [x] Écran règles de l'examen (mis en évidence, 2 cases à cocher)
- [x] Passage en plein écran obligatoire (Fullscreen API)
- [x] Chronomètre visible (hybride dateDebut serveur + affichage local)
- [x] Questions dans ordre aléatoire
- [x] Propositions dans ordre aléatoire (Fisher-Yates)
- [x] Navigation libre (suivant / précédent / grille)
- [x] Auto-save toutes les 5 sec (localStorage)
- [x] Soumission manuelle OU auto à expiration
- [x] Génération CSV signé SHA-256
- [x] Sauvegarde serveur dans data/examens/XXX/passages/
- [x] Email confirmation avec CSV en PJ

#### Focus-Lock anti-triche
- [x] Détection sortie de page (Page Visibility API)
- [x] Détection perte focus (window blur)
- [x] Politique escalade configurable :
  - Strict (annulation dès 1ère sortie)
  - Standard (1 avertissement puis annulation)
  - Tolérant (3 avertissements)
  - Log seul (pas de sanction)
- [x] Durée avertissement configurable (3-10 sec, défaut 5)
- [x] Blocage clic droit (configurable)
- [x] Blocage copier-coller (configurable)
- [x] Blocage Ctrl+P, Ctrl+S, F12 (configurable)
- [x] Pop-up empêcher fermeture accidentelle
- [x] Journalisation complète dans audit.log

### Livrables
- [ ] Wizard création examen (4 étapes)
- [ ] Parcours étudiant complet (4 pages)
- [ ] Module anti-triche fonctionnel
- [ ] Chronomètre hybride testé
- [ ] Tests E2E basiques
- [ ] Audit.log opérationnel

### Critères d'acceptation
- ✅ Prof crée un examen et reçoit le code
- ✅ Étudiant se connecte avec le code et passe l'examen
- ✅ Chronomètre fonctionne correctement (test changement horloge)
- ✅ Focus-lock détecte les sorties et applique les sanctions
- ✅ CSV signé est généré et sauvé côté serveur
- ✅ Soumission auto à expiration fonctionne
- ✅ Mode plein écran obligatoire fonctionne
- ✅ Accessibilité mobile validée (iOS + Android)

### Durée : 26h (la plus grosse phase)
### Commits prévus : 8-10

---

## 📖 Phase P6 — Correction étudiant + PDF + Notifications

**Objectif** : L'étudiant voit sa correction détaillée, reçoit les emails, télécharge son PDF.

### Contenu

#### Pages correction étudiant
```
frontend/etudiant/
├── correction.html           # Via token depuis email
├── correction_csv.html       # Via upload CSV (compatibilité v1)
└── correction_pdf.html       # Version imprimable
```

#### API backend
```
backend/api/corrections.php
├── GET  /api/corrections/{token}       # Accès par token
├── GET  /api/corrections/my-rights     # Accès RGPD étudiant
└── POST /api/corrections/rectify       # Demande rectification
```

#### Génération PDF
```
backend/lib/PdfGenerator.php
└── Utilise la fonction window.print() du navigateur
    OU jsPDF côté client
```

#### Emails
```
backend/lib/
├── Mailer.php                # Classe wrapping PHP mail() + SMTP OVH
├── EmailTemplate.php         # Gestionnaire de templates
└── templates/emails/
    ├── prof_examen_cree.php
    ├── prof_premier_passage.php
    ├── prof_cloture_examen.php
    ├── etudiant_confirmation.php
    ├── etudiant_correction_disponible.php
    └── base.php              # Layout commun
```

### Fonctionnalités

#### Correction étudiant
- [x] Accès via token sécurisé (email) ou upload CSV (bascule v1)
- [x] Note géante avec mention
- [x] KPI : correctes, points, %J1, %J2, durée, rang
- [x] Graphique : temps par question, distribution difficulté
- [x] Détail par question :
  - Énoncé avec KaTeX
  - 4 propositions (surlignage correct/incorrect/manqué)
  - Explication bonne réponse
  - Explications mauvaises (ordonnées selon vu)
  - Piège à éviter
  - Référence cours
- [x] Bouton télécharger PDF
- [x] Accès pendant durée configurée (défaut 30 jours)

#### Notifications email
- [x] Prof — Création examen (récap + liens)
- [x] Prof — Premier passage reçu
- [x] Prof — Clôture examen (stats)
- [x] Prof — Signature invalide détectée
- [x] Prof — Anomalie focus-lock grave
- [x] Étudiant — Confirmation passage (CSV en PJ)
- [x] Étudiant — Correction disponible (si programmée)

#### Templates emails
- HTML responsive
- Texte alternatif pour email texte pur
- Tokens à usage unique
- Tracking d'ouverture (optionnel, désactivé par défaut pour RGPD)

### Livrables
- [ ] Pages correction étudiant
- [ ] PDF téléchargeable fonctionnel
- [ ] 7 templates emails complets
- [ ] Système de tokens sécurisés
- [ ] Configuration SPF/DKIM documentée

### Critères d'acceptation
- ✅ Étudiant reçoit email confirmation avec CSV en PJ
- ✅ Clic sur lien email ouvre la correction
- ✅ Token expire après durée configurée
- ✅ PDF généré est imprimable et lisible
- ✅ Tous les emails testés (envoi + rendu)
- ✅ Délivrabilité email OK (pas de spam)

### Durée : 15h
### Commits prévus : 4-5

---

## 📊 Phase P7 — Historique prof + Analytics approfondies

**Objectif** : Dashboard prof complet + analyses transversales.

### Contenu

#### 3 niveaux de pages
```
frontend/enseignant/
├── dashboard.html              # Niveau 1 : landing global
├── examens_liste.html          # Niveau 2 : liste avec filtres
├── examens_detail.html         # Niveau 3 : détail examen (temps réel)
├── analytics_global.html       # Analyses transversales
├── analytics_promo.html        # Analyse par promo
├── analytics_etudiant.html     # Suivi par étudiant
└── analytics_questions.html    # Top questions ratées
```

#### API backend
```
backend/api/analytics.php
├── GET /api/analytics/global
├── GET /api/analytics/examen/{code}
├── GET /api/analytics/promo/{id}
├── GET /api/analytics/etudiant/{email}
├── GET /api/analytics/questions-top-rated
└── GET /api/analytics/compare-promos
```

### Fonctionnalités

#### Dashboard global (niveau 1)
- [x] Bienvenue + notifications
- [x] 5 KPI principaux (examens, passages, moyenne, durée, top ratée)
- [x] Section "Examens actifs" (en cours ou à venir)
- [x] Section "Examens récents" (10 derniers)
- [x] Boutons rapides : nouvel examen, tous examens

#### Liste examens (niveau 2)
- [x] Tableau complet avec tri/filtre/recherche/pagination
- [x] Filtres : année, promotion, module, statut
- [x] Actions : voir, éditer, dupliquer, archiver, supprimer
- [x] Export CSV de la liste

#### Détail examen (niveau 3)
- [x] Réutilise le dashboard existant (`qcm_eval_J1_J2/dashboard_enseignant_2026.html`)
- [x] + Suivi temps réel si examen actif
- [x] + Actions (pause, clôture, reset passage, etc.)
- [x] + Graphique temps par question

#### Analytics transversales ⭐
- [x] Évolution d'une promotion (graph temporel)
- [x] Top 20 questions les plus ratées (toutes années)
- [x] Corrélations entre questions (matrice)
- [x] Suivi par étudiant (historique + évolution)
- [x] Comparaison inter-promos (B2 2025 vs 2026)
- [x] Export Word du rapport conseil de classe

### Livrables
- [ ] 7 pages enseignant
- [ ] Toutes les API analytics
- [ ] Export Word (utilise DOCX template)
- [ ] Temps réel via polling (rafraîchissement 30 sec)

### Critères d'acceptation
- ✅ Dashboard affiche toutes les stats globales correctement
- ✅ Détail examen fonctionne en temps réel
- ✅ Analytics calculées sur au moins 3 examens
- ✅ Export Word généré et lisible dans Microsoft Word

### Durée : 18h
### Commits prévus : 5-6

---

## 🧪 Phase P8 — Tests + CI/CD + Backups

**Objectif** : Qualité logicielle et protection des données.

### Contenu

#### Tests unitaires PHPUnit
```
tests/backend/
├── ScoreCalculatorTest.php
├── SignatureTest.php
├── CsvParserTest.php
├── ExamCodeGeneratorTest.php
├── QuestionFilterTest.php
├── AuthTest.php
├── IaClientTest.php (avec mocks)
├── BanqueManagerTest.php
├── FocusLockValidatorTest.php
└── ... (~50 tests)
```

#### Tests E2E Playwright
```
tests/e2e/
├── login.spec.js
├── create_exam.spec.js
├── student_complete_flow.spec.js
├── anti_cheat_focus_lock.spec.js
├── anti_cheat_timer.spec.js
├── correction_via_email.spec.js
├── correction_via_csv_upload.spec.js
├── ia_generation.spec.js
├── rgpd_rights.spec.js
├── analytics_dashboard.spec.js
└── ... (~15 parcours)
```

#### CI/CD GitHub Actions
```
.github/workflows/
├── ci.yml                    # Tests sur push/PR
├── backup.yml                # Déclenche backup manuellement
└── deploy.yml                # Déploiement OVH (à configurer)
```

#### Backups
```
scripts/
├── backup_local.php          # Cron OVH quotidien 3h
├── backup_github.sh          # Cron OVH hebdo dimanche 4h
├── restore.php               # Script de restauration
└── cleanup_retention.php     # Cron quotidien RGPD
```

### Livrables
- [ ] ~50 tests PHPUnit
- [ ] ~15 tests E2E Playwright
- [ ] GitHub Actions fonctionnel
- [ ] Scripts backup + cron OVH documentés
- [ ] Badges CI dans le README
- [ ] Procédure de restauration documentée

### Critères d'acceptation
- ✅ `composer test` passe 100%
- ✅ `npx playwright test` passe 100%
- ✅ CI GitHub Actions vert sur main
- ✅ Un backup manuel fonctionne
- ✅ Restauration testée (simulation)

### Durée : 18h
### Commits prévus : 5-6

---

## 📚 Phase P9 — Documentation finale + Soft launch

**Objectif** : Tout est prêt pour le soft launch.

### Contenu

#### Documentation utilisateur
```
docs/
├── GUIDE_UTILISATION_PROF.md          # 20+ pages avec captures
├── GUIDE_UTILISATION_ETUDIANT.md      # 5 pages, illustré
├── GUIDE_DEPLOIEMENT_OVH.md           # 15 pages, pas à pas
├── GUIDE_MIGRATION_V2.md              # Migration vers MySQL si besoin
├── FAQ.md                             # Questions fréquentes
├── TROUBLESHOOTING.md                 # Résolution de problèmes
└── SECURITE.md                        # Bonnes pratiques sécurité
```

#### Checklists
```
docs/
├── CHECKLIST_PRE_PROD.md              # 30 points avant déploiement
├── CHECKLIST_POST_EXAMEN.md           # Actions post-examen prof
└── CHECKLIST_SECURITE.md              # Audit sécurité
```

#### Vidéos de démo (optionnel, +5h)
- 3 min côté prof : création d'examen
- 2 min côté étudiant : parcours complet
- 2 min côté admin : gestion comptes

### Soft launch

#### Phase 1 — Test interne (2 semaines)
- Déploiement staging
- 2-3 examens simulés par l'auteur
- Feedback 2-3 collègues
- Corrections bugs remontés

#### Phase 2 — Pilote (3-4 semaines)
- 1 vrai examen avec 1 promo volontaire
- Sans note OU double vérification
- Surveillance active
- Feedback étudiants

#### Phase 3 — Production
- Basculement de tous les examens
- Archivage qcm_eval_J1_J2

### Livrables
- [ ] Tous les guides utilisateur complets
- [ ] Checklists de validation
- [ ] Vidéos de démo (optionnel)
- [ ] Plateforme déployée en staging
- [ ] Rapport pilote

### Critères d'acceptation
- ✅ Un prof découvre et crée un examen en < 15 min sans aide
- ✅ Un étudiant passe l'examen sans friction
- ✅ Pilote réussi sans incident majeur
- ✅ Documentation complète

### Durée : 8h (hors temps de soft launch)
### Commits prévus : 3-4

---

## 🎯 Critères de succès du projet

Le projet sera considéré comme réussi si :

1. ✅ **Fonctionnel** : toutes les fonctionnalités des 20 décisions sont livrées
2. ✅ **Stable** : zéro incident majeur pendant le pilote
3. ✅ **Documenté** : un prof peut démarrer sans aide
4. ✅ **Testé** : tests automatisés passent 100%
5. ✅ **Déployé** : en production sur OVH
6. ✅ **Utilisé** : au moins 1 vrai examen noté passé avec succès
7. ✅ **Sauvegardé** : backups fonctionnels
8. ✅ **Conforme** : RGPD respecté, accessibilité WCAG AA validée

---

## 📅 Timeline indicative

En supposant un rythme de dev soutenu (1 phase / 1-2 semaines) :

```
Semaine 1    : P0 + début P1 ✅ (en cours)
Semaine 2-3  : P1 Fondations backend
Semaine 4-5  : P2 Design system
Semaine 6    : P3 Banque de questions (suite)
Semaine 7    : P4 IA + Migration
Semaine 8-10 : P5 Création examen + Passage (la plus grosse)
Semaine 11   : P6 Correction + Emails
Semaine 12-13: P7 Analytics
Semaine 14   : P8 Tests + CI/CD
Semaine 15   : P9 Doc + soft launch
                 ↓
Semaines 16-17 : Test interne
Semaines 18-21 : Pilote avec 1 promo
Semaine 22+   : Production complète
```

**Livraison finale estimée** : ~Juillet 2026 pour production complète.

---

## 🔄 Gestion des changements

Si, en cours de route, une décision de cadrage doit être modifiée :

1. **Discussion** : pourquoi ce changement ?
2. **Impact** : sur quelles phases ?
3. **Mise à jour** : `NOTE_CADRAGE.md` + `CHANGELOG.md`
4. **Re-validation** : l'utilisateur confirme
5. **Continuation** : phase adaptée

---

*© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0*
