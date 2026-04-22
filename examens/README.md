# 🎓 Plateforme d'examens IPSSI

> Plateforme web complète d'examens en ligne avec banque de questions évolutive,
> génération IA, anti-triche avancé, analytics approfondies et design professionnel.

[![Tests](https://github.com/melafrit/maths_IA_niveau_1/actions/workflows/tests.yml/badge.svg)](https://github.com/melafrit/maths_IA_niveau_1/actions/workflows/tests.yml)
[![Lint](https://github.com/melafrit/maths_IA_niveau_1/actions/workflows/lint.yml/badge.svg)](https://github.com/melafrit/maths_IA_niveau_1/actions/workflows/lint.yml)
[![License: CC BY-NC-SA 4.0](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-lightgrey.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Status](https://img.shields.io/badge/status-Phase%20P8%20%E2%9C%85%20%E2%80%94%20CI%2FCD%20livr%C3%A9-success)]()
[![Tests](https://img.shields.io/badge/tests-389%2F389%20%E2%9C%85-success)]()
[![Version](https://img.shields.io/badge/version-0.8.0-blue)]()

---

## 🎯 Vision

Fournir aux enseignants d'IPSSI — et potentiellement à d'autres écoles — un outil
**professionnel, scalable et pédagogique** pour créer, diffuser, corriger et analyser
des examens QCM en ligne.

Cette plateforme succède au dispositif initial `qcm_eval_J1_J2/` (50 questions statiques
en mode standalone) et l'élargit à une **vraie plateforme multi-utilisateurs**.

---

## ✨ Fonctionnalités principales

### Côté enseignant

- 🔐 **Comptes multi-enseignants** (rôles admin / enseignant)
- 📚 **Banque de questions évolutive** organisée par module/chapitre/thème
- 🤖 **Génération IA** de questions (Claude + OpenAI, clé par enseignant)
- ➕ **CRUD complet** : création, édition, duplication, archivage, import/export
- 🎨 **Preview KaTeX en temps réel** pour les formules mathématiques
- 📝 **Création d'examens** avec formulaire complet (~20 paramètres)
- 🎯 **Sélection hybride** : filtres automatiques + affinage manuel
- ⏱️ **Chronomètre configurable** + fenêtre temporelle
- 🛡️ **Anti-triche focus-lock** (détection sortie de page, plein écran obligatoire)
- 📊 **Dashboard temps réel** pendant les examens actifs
- 📈 **Analytics approfondies** : stats par examen + analyses transversales multi-examens
- 📥 **Exports** CSV (Yparéo) + XLSX multi-onglets + PDF
- 📧 **Notifications email** automatiques (création, soumission, clôture)

### Côté étudiant

- 🎫 **Accès par code** : `IPSSI-B2-2026-A4F7` (format hybride parlant + aléatoire)
- 📱 **Responsive** (desktop, tablette, smartphone)
- ⏱️ **Chronomètre visible** avec alertes progressives (10 min, 5 min, 1 min)
- 🎲 **Ordre des questions et propositions aléatoire** (anti-triche)
- 🛡️ **Focus-lock anti-triche** (plein écran, détection sortie)
- 💾 **Auto-sauvegarde** des réponses (résilient au crash navigateur)
- ✅ **Signature SHA-256** du CSV pour intégrité
- 📖 **Correction détaillée** (paramétrable par examen)
- 📥 **Export CSV + PDF** de sa correction
- ♿ **Accessibilité WCAG AA** (dyslexie, haut contraste, temps supplémentaire PPAS)
- 📧 **Confirmation email** avec CSV en pièce jointe

---

## 🏗️ Architecture

```
┌────────────────────────────────────────────────────────────┐
│           FRONTEND (React + Babel in-browser)              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  Enseignant  │  │   Étudiant   │  │    Commun    │     │
│  │              │  │              │  │              │     │
│  │ • Dashboard  │  │ • Accès code │  │ • Login      │     │
│  │ • Banque     │  │ • QCM        │  │ • Mentions   │     │
│  │ • Création   │  │ • Correction │  │ • Profil     │     │
│  │ • Historique │  │ • Export     │  │              │     │
│  │ • Analytics  │  │              │  │              │     │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘     │
└─────────┼─────────────────┼─────────────────┼──────────────┘
          │                 │                 │
          └─────────────────┼─────────────────┘
                            │ JSON / HTTPS
                            ▼
┌────────────────────────────────────────────────────────────┐
│              BACKEND (PHP 7.4+ sur OVH mutualisé)          │
│  ┌──────────────────────────────────────────────────┐      │
│  │  API REST (endpoints JSON)                       │      │
│  │  • auth.php   • examens.php   • banque.php      │      │
│  │  • ia.php     • backup.php    • rgpd.php        │      │
│  └──────────────────────────────────────────────────┘      │
│  ┌──────────────────────────────────────────────────┐      │
│  │  Lib (utilitaires)                               │      │
│  │  • CsvWriter  • Signature  • TokenManager        │      │
│  │  • Mailer     • Logger     • ScoreCalculator     │      │
│  └──────────────────────────────────────────────────┘      │
└──────────────────────┬─────────────────────────────────────┘
                       │
                       ▼
┌────────────────────────────────────────────────────────────┐
│              STORAGE (fichiers sur OVH)                    │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │ banque/  │  │ examens/ │  │ comptes/ │  │ config/  │   │
│  │  JSON    │  │ CSV+JSON │  │  JSON    │  │ JSON+PHP │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└────────────────────────────────────────────────────────────┘
                       │
                       │ (cron quotidien + hebdo)
                       ▼
┌────────────────────────────────────────────────────────────┐
│   BACKUPS (ZIP local sur OVH + GitHub privé hebdo)         │
└────────────────────────────────────────────────────────────┘
```

---

## 🛠️ Stack technique

| Couche | Technologies |
|---|---|
| **Frontend** | React 18 (CDN + Babel in-browser), KaTeX, Recharts, Lucide icons |
| **Backend** | PHP 7.4+ sur OVH mutualisé, fichiers JSON + CSV (pas de DB en v1) |
| **Polices** | Inter (UI), Manrope (titres), JetBrains Mono (code) |
| **Authentification** | Bcrypt + sessions PHP sécurisées |
| **Chiffrement** | AES-256 pour les clés API IA |
| **Email** | PHP `mail()` + SMTP OVH |
| **Tests** | PHPUnit (backend) + Playwright (E2E) + GitHub Actions (CI/CD) |
| **Backups** | Cron OVH local + Git push GitHub privé |
| **i18n** | Architecture prête, FR livré (EN ajoutable) |
| **IA** | Anthropic Claude (Sonnet/Opus) + OpenAI (GPT-4o) — clé par enseignant |

---

## 📂 Structure du projet

```
examens/
├── README.md                      # Ce fichier
├── NOTE_CADRAGE.md                # Cadrage détaillé (20 décisions)
├── ROADMAP.md                     # Plan de livraison en 9 phases
├── CONVENTIONS.md                 # Conventions de code et de commit
├── CHANGELOG.md                   # Historique des versions
├── LICENSE                        # CC BY-NC-SA 4.0
│
├── backend/                       # Code PHP
│   ├── api/                       # Endpoints REST
│   ├── lib/                       # Utilitaires
│   └── public/                    # Point d'entrée web
│
├── frontend/                      # Code React/HTML
│   ├── assets/                    # Design system, i18n, composants
│   ├── enseignant/                # Pages prof
│   ├── etudiant/                  # Pages étudiant
│   └── commun/                    # Pages partagées
│
├── data/                          # Données (vide initialement)
│   ├── banque/                    # JSON des questions par chapitre
│   ├── examens/                   # Passages d'examens (1 dossier/examen)
│   ├── comptes/                   # Comptes enseignants
│   └── config/                    # Configuration globale
│
├── scripts/                       # Utilitaires (migration, backup, cron)
├── tests/                         # Tests automatisés (PHPUnit + Playwright)
├── scenarios_tests/               # Scénarios de tests manuels
├── docs/                          # Guides utilisateur et déploiement
└── .github/                       # Templates Issues, PR, CI workflows
```

Voir [`docs/`](./docs/) pour les guides détaillés.

---

## 🚀 Démarrage rapide (à venir — Phase P1+)

Une fois la Phase P1 livrée :

```bash
# 1. Cloner le projet
git clone https://github.com/melafrit/maths_IA_niveau_1.git
cd maths_IA_niveau_1/examens

# 2. Configurer l'environnement
cp backend/config.sample.php backend/config.php
# (éditer backend/config.php avec vos paramètres)

# 3. Lancer en local
php -S localhost:8000 -t backend/public

# 4. Créer le premier compte admin
php scripts/init_comptes.php
```

**Déploiement OVH** : voir [`docs/GUIDE_DEPLOIEMENT_OVH.md`](./docs/GUIDE_DEPLOIEMENT_OVH.md) (en Phase P9).

---

## 📚 Documentation

| Document | Description |
|---|---|
| [NOTE_CADRAGE.md](./NOTE_CADRAGE.md) | **Cadrage détaillé** (20 décisions + justifications) |
| [ROADMAP.md](./ROADMAP.md) | Plan de livraison en 9 phases |
| [CONVENTIONS.md](./CONVENTIONS.md) | Conventions de code, commit, branches |
| [CHANGELOG.md](./CHANGELOG.md) | Historique des versions |
| [docs/GUIDE_UTILISATION_PROF.md](./docs/GUIDE_UTILISATION_PROF.md) | Guide enseignant *(Phase P9)* |
| [docs/GUIDE_UTILISATION_ETUDIANT.md](./docs/GUIDE_UTILISATION_ETUDIANT.md) | Guide étudiant *(Phase P9)* |
| [docs/GUIDE_DEPLOIEMENT_OVH.md](./docs/GUIDE_DEPLOIEMENT_OVH.md) | Installation OVH pas-à-pas *(Phase P9)* |
| [docs/GUIDE_MIGRATION_V2.md](./docs/GUIDE_MIGRATION_V2.md) | Migration fichiers → MySQL *(v2)* |

---

## 📅 Statut du projet

**Phase actuelle : P2 — Design system** ✅ **Terminée**

| Phase | Description | Statut |
|:-:|---|:-:|
| P0 | Cadrage + structure repo | ✅ Livrée |
| P1 | Fondations backend (auth, comptes, API) | ✅ Livrée |
| P2 | Design system + frontend commun | ✅ **Livrée** |
| P3 | Banque de questions (CRUD + Import/Export) | 🟡 À venir |
| P4 | Génération IA + Migration J1-J2 | ⏳ |
| P5 | Création examen + Passage étudiant + Focus-lock | ⏳ |
| P6 | Correction étudiant + PDF + Notifications | ⏳ |
| P7 | Historique prof + Analytics approfondies | ⏳ |
| P8 | Tests + CI/CD + Backups | ⏳ |
| P9 | Documentation finale + Soft launch | ⏳ |

Voir [ROADMAP.md](./ROADMAP.md) pour le détail.

---

## 🔗 Projets liés

Ce projet s'inscrit dans la suite des livrables pédagogiques IPSSI — Maths IA :

- 📘 **[`cours/`](../cours/)** — Cours web interactifs (Jour 1 à 4)
- 🧠 **[`qcm/`](../qcm/)** — QCM d'entraînement par jour
- 🎯 **[`qcm_eval_J1_J2/`](../qcm_eval_J1_J2/)** — Dispositif QCM évaluation standalone (V1, origine de ce projet)
- 📄 **[`livrables_jour_par_jour/`](../livrables_jour_par_jour/)** — PDF de cours, exercices, corrections

---

## 👤 Auteur

**Mohamed EL AFRIT**
Enseignant-formateur, Consultant en Ingénierie Logicielle, Data Science et Management de SI
📧 m.elafrit@ecole-ipssi.net
🏫 IPSSI — 2025-2026

---

## 📜 Licence

Ce projet est distribué sous licence [Creative Commons BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/deed.fr) :

- ✅ **BY** : Attribution — vous devez créditer l'auteur
- ❌ **NC** : Non commercial — pas d'usage commercial sans autorisation
- 🔄 **SA** : Partage dans les mêmes conditions

© 2025-2026 Mohamed EL AFRIT — IPSSI

---

## 🤝 Contribution

Ce projet est principalement maintenu par son auteur dans le cadre de l'enseignement IPSSI.
Les contributions externes sont possibles via Pull Request après discussion.

Pour signaler un bug ou proposer une amélioration :
- Ouvrir une [Issue GitHub](https://github.com/melafrit/maths_IA_niveau_1/issues/new/choose)
- Ou envoyer un email à l'auteur

Voir [CONVENTIONS.md](./CONVENTIONS.md) pour les conventions de code.
