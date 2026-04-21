# QCM d'évaluation — Jours 1 et 2

> **Mathématiques appliquées à l'Intelligence Artificielle**
> Module 2e année Bachelor Informatique — IPSSI — Année 2025-2026
>
> © 2025 Mohamed EL AFRIT — IPSSI | [www.mohamedelafrit.com](https://www.mohamedelafrit.com)
> Licence [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/deed.fr)

---

## 📋 Objet du livrable

Dispositif d'évaluation sommative couvrant les **deux premiers jours** du module :

- **Jour 1** — Données et représentation mathématique (vecteurs, matrices, produit scalaire, régression linéaire, MSE)
- **Jour 2** — Optimisation (fonction de coût, dérivée intuitive, descente de gradient, taux d'apprentissage)

Le dispositif est composé de **3 pages web HTML standalone** et de **2 fichiers JSON** (énoncés et corrections), avec une architecture de déploiement en deux temps garantissant l'impossibilité pour un étudiant d'accéder aux corrections pendant l'évaluation.

---

## 🎯 Cahier des charges validé

### 1. Contenu & structure

| Paramètre | Choix retenu |
|---|---|
| **Nombre de questions** | 50 questions — choix unique (1 bonne réponse parmi 4) |
| **Répartition** | 25 questions Jour 1 / 25 questions Jour 2 (équilibré) |
| **Difficulté (interne)** | 🟢 10 / 🟡 10 / 🟠 4 / 🔴 1 par jour — **invisible côté étudiant** |
| **Types de questions** | 25 % conceptuel / **40 % calcul numérique** / 25 % lecture de code / 10 % reconnaissance de formule |
| **Fil rouge** | Systématique : 30 développeurs fictifs, `w = 2.07`, `b = 31.27`, `MSE = 20.46` (k€)² |

### 2. Conditions de passage (page étudiant)

| Paramètre | Choix retenu |
|---|---|
| **Chronomètre** | Visible mais **sans blocage** (indicatif) |
| **Randomisation** | Questions **ET** propositions de réponses mélangées **par étudiant** (anti-triche) |
| **Navigation** | Libre (retour arrière autorisé) + barre de progression `X/50 répondues` en permanence |
| **Identification** | `NOM` + `Prénom` + `Email` (validation regex) obligatoires **au moment de l'export** — pas avant le QCM |

### 3. Notation

| Paramètre | Choix retenu |
|---|---|
| **Barème** | Pondéré par difficulté : 🟢 = 1 pt / 🟡 = 2 pts / 🟠 = 3 pts / 🔴 = 4 pts |
| **Total brut** | 2 × (10×1 + 10×2 + 4×3 + 1×4) = **2 × 46 = 92 points** bruts |
| **Conversion** | Ramené sur **/20** (note_finale = points_obtenus × 20 / 92) |
| **Malus** | Aucun malus en cas de mauvaise réponse |
| **Affichage barème** | Expliqué dans la page énoncé **et** rappelé en tête de la page correction |
| **Note affichée à l'étudiant** | `/20` uniquement (pas de pourcentage ni de mention) |

### 4. Export CSV côté étudiant

| Paramètre | Choix retenu |
|---|---|
| **Contenu** | Identité complète + date/heure + temps passé + ordre des questions + ordre des propositions + énoncé tronqué + réponse choisie + **signature SHA-256** |
| **Nom de fichier** | `IPSSI_NOM_Prenom_QCM_J1J2_YYYYMMDD.csv` |
| **Envoi** | Téléchargement local + instructions à l'écran pour envoi manuel à `m.elafrit@ecole-ipssi.net` |
| **Anti-triche** | Hash cryptographique SHA-256 sur la concaténation des champs clés → détection automatique de toute modification manuelle |

### 5. Page 2 — Correction personnelle étudiant

| Paramètre | Choix retenu |
|---|---|
| **Accès** | Page blanche tant qu'aucun CSV n'est chargé (pas d'accès au contenu sans CSV) |
| **Affichage** | **Linéaire** : résumé en haut (note/20, X/50 bonnes réponses) puis déroulé complet |
| **Contenu** | Toutes les questions dans **l'ordre vu par l'étudiant**, avec ses réponses, la bonne réponse, explication du « pourquoi » et du « pourquoi pas » des autres |

### 6. Page 3 — Dashboard enseignant

| Paramètre | Choix retenu |
|---|---|
| **Accès** | **URL secrète** non référencée depuis le site public |
| **Chargement CSVs** | Drag-and-drop **ET** bouton file input classique (max flexibilité) |
| **Tableau** | Nom, Prénom, Email, Classe, Date, Durée, Note /20, % J1, % J2, Rang, Badge signature (✅/❌) |
| **Fonctionnalités** | Tri par colonne, filtre par note, recherche par nom, pagination (>30 étudiants), couleurs vert/orange/rouge, modal détail par étudiant |
| **Statistiques** | Moyenne, médiane, min, max, écart-type, histogramme de distribution, taux par question, taux J1 vs J2, top 5 questions ratées, camemberts des réponses pour chaque question ratée |
| **Exports** | CSV (simple, pour Yparéo) **et** XLSX (multi-onglets avec mise en forme conditionnelle) |

### 7. Architecture de déploiement (sécurité)

Le déploiement se fait **en deux phases distinctes** pour garantir que les étudiants n'aient aucun accès aux bonnes réponses pendant l'évaluation :

#### Phase A — Avant l'évaluation (jour J)

```
/serveur/qcm_eval_J1_J2/
├── qcm_etudiant.html      ← page d'évaluation
└── questions.json         ← 50 énoncés randomisables (AUCUNE bonne réponse présente)
```

→ Les étudiants reçoivent uniquement le lien vers `qcm_etudiant.html`.
→ Le fichier `corrections.json` **n'existe pas** sur le serveur à ce stade.

#### Phase B — Après la date limite de rendu

```
/serveur/qcm_eval_J1_J2/
├── qcm_etudiant.html
├── questions.json
├── correction_personnelle.html    ← ajouté
├── dashboard_enseignant_XXXX.html ← ajouté (URL secrète)
└── corrections.json               ← ajouté
```

→ Les étudiants peuvent charger leur CSV dans `correction_personnelle.html` pour voir leur copie corrigée.
→ L'enseignant charge tous les CSVs dans le dashboard pour obtenir notes et statistiques.

**Conséquence sécurité** : pendant toute la durée de l'évaluation, les bonnes réponses sont **physiquement absentes** du serveur. Aucun étudiant ne peut les trouver, même en inspectant le code source.

### 8. Charte graphique

Identique aux pages de cours existantes :

- **Thème** : clair par défaut, bascule sombre en haut à droite, persistance via `localStorage`
- **Polices** : Source Serif 4 (corps), DM Sans (titres), JetBrains Mono (code)
- **Rendu mathématique** : KaTeX (CDN) pour toutes les formules
- **Palette** : bleu/violet pour J1, vert pour J2, couleurs des encadrés identiques aux pages cours
- **Licence** : bandeau CC BY-NC-SA 4.0 en footer de chaque page

---

## 🏗️ Architecture des fichiers livrés

```
qcm_eval_J1_J2/
├── README.md                            ← ce document (cadrage)
├── BAREME.md                            ← détail du barème et conversion sur 20
│
├── questions.json                       ← 50 énoncés + propositions (sans bonnes réponses)
├── corrections.json                     ← bonnes réponses + explications détaillées
│
├── qcm_etudiant.html                    ← Phase A — page de passage
├── correction_personnelle.html          ← Phase B — page de correction individuelle
├── dashboard_enseignant_2026.html       ← Phase B — URL secrète, tableau de bord
│
├── GUIDE_DEPLOIEMENT.md                 ← procédure de mise en ligne en 2 phases
├── TESTS.md                             ← plan de tests et CSV de référence
└── tests/                               ← CSVs de test fictifs pour valider le dashboard
    ├── IPSSI_MARTIN_Jean_QCM_J1J2_20260420.csv
    ├── IPSSI_DUPONT_Marie_QCM_J1J2_20260420.csv
    └── IPSSI_DURAND_Paul_QCM_J1J2_20260420.csv
```

### Structure des fichiers JSON

**`questions.json`** (déployé en Phase A) :

```json
{
  "metadata": {
    "titre": "QCM d'évaluation Jours 1-2",
    "version": "1.0",
    "date_creation": "2026-04-20",
    "bareme": {"vert": 1, "jaune": 2, "orange": 3, "rouge": 4},
    "note_max_brute": 92,
    "note_sur": 20
  },
  "questions": [
    {
      "id": "Q01",
      "jour": 1,
      "difficulte": "vert",
      "type": "conceptuel",
      "enonce": "Un vecteur en 3 dimensions contient...",
      "propositions": [
        "3 composantes réelles",
        "3 composantes entières uniquement",
        "un seul nombre",
        "une matrice 3x3"
      ]
    }
    // ... 49 autres questions
  ]
}
```

Note : la clé `bonne_reponse` **n'apparaît pas** dans ce fichier. Elle est uniquement dans `corrections.json`.

**`corrections.json`** (déployé en Phase B uniquement) :

```json
{
  "corrections": [
    {
      "id": "Q01",
      "bonne_reponse_index": 0,
      "explication_bonne": "Un vecteur en dimension 3 est par définition...",
      "explications_mauvaises": [
        null,
        "Faux : les composantes peuvent être réelles, pas seulement entières",
        "Faux : un seul nombre est un scalaire, pas un vecteur",
        "Faux : une matrice 3x3 a 9 composantes, pas 3"
      ],
      "reference_cours": "Jour 1, section 1.1 — Vecteurs",
      "piege_a_eviter": "Ne pas confondre dimension et nature des composantes"
    }
    // ... 49 autres corrections
  ]
}
```

---

## 🔐 Mécanisme de signature anti-fraude

Chaque CSV exporté contient un champ `signature` calculé comme suit :

```
donnees = NOM | Prenom | Email | date_iso | duree_sec | [id_q|reponse_index, ...] | ordre_questions | ordre_propositions
signature = SHA-256(donnees + "IPSSI_SALT_2026")
```

Le sel `IPSSI_SALT_2026` est codé en dur dans les deux pages (étudiant et correction). Toute modification manuelle d'un champ invalide la signature. Le dashboard enseignant vérifie chaque signature et affiche :

- ✅ **Valide** — badge vert : le CSV n'a pas été modifié
- ❌ **Falsifiée** — badge rouge : modification détectée, note à examiner manuellement

---

## 🚀 Plan de livraison (5 phases avec validation par commit)

Chaque phase fait l'objet d'un commit séparé avec push vers `main`. À chaque push, **l'enseignant valide** avant que la phase suivante soit engagée.

| Phase | Livrable | Fichiers | État |
|---|---|---|---|
| **1** | Cadrage + JSON | `README.md`, `BAREME.md`, `questions.json`, `corrections.json` | 🚧 En cours |
| **2** | Page étudiant | `qcm_etudiant.html` | ⏳ À venir |
| **3** | Page correction individuelle | `correction_personnelle.html` | ⏳ À venir |
| **4** | Dashboard enseignant | `dashboard_enseignant_2026.html` | ⏳ À venir |
| **5** | Documentation + tests | `GUIDE_DEPLOIEMENT.md`, `TESTS.md`, CSVs de test | ⏳ À venir |

### Ce commit (Phase 1) contient

- Le présent `README.md` (cadrage complet)
- `BAREME.md` (détail du système de notation, à commiter dans ce même commit ou en sous-commit de Phase 1)
- `questions.json` (à générer — 50 énoncés)
- `corrections.json` (à générer — 50 corrections détaillées)

---

## 📐 Conventions mathématiques (rappel)

Les formules utilisent la notation du cours :

- Vecteurs : $\mathbf{x}$, $\mathbf{w}$ (gras minuscule)
- Matrices : $\mathbf{X}$, $\mathbf{W}$ (gras majuscule)
- Scalaires : $n$, $m$, $\alpha$ (italique)
- Prédiction : $\hat{y} = \mathbf{w}^T \mathbf{x} + b$
- Coût MSE : $J(w, b) = \frac{1}{n} \sum_{i=1}^{n} (y_i - \hat{y}_i)^2$
- Gradient : $\nabla J(\boldsymbol{\theta})$
- Mise à jour : $\boldsymbol{\theta} \leftarrow \boldsymbol{\theta} - \alpha \nabla J(\boldsymbol{\theta})$

Rendu KaTeX côté web, LaTeX natif côté PDF (si version imprimable générée ultérieurement).

---

## 📬 Contact

**Auteur et responsable pédagogique** : Mohamed EL AFRIT
**Email de contact** : `m.elafrit@ecole-ipssi.net`
**Institution** : IPSSI — Bachelor Informatique 2e année
**Année académique** : 2025-2026

---

## 📄 Licence

Ce contenu est distribué sous licence **Creative Commons BY-NC-SA 4.0** :

- ✅ **BY** — Attribution obligatoire à l'auteur
- ⛔ **NC** — Pas d'utilisation commerciale
- 🔁 **SA** — Partage dans les mêmes conditions

Texte complet : [https://creativecommons.org/licenses/by-nc-sa/4.0/deed.fr](https://creativecommons.org/licenses/by-nc-sa/4.0/deed.fr)
