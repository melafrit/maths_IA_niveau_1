# Mathematiques appliquees a l'Intelligence Artificielle

Site pedagogique interactif pour le module **Mathematiques appliquees a l'IA** destine aux etudiants de 2e annee Bachelor Informatique (IPSSI).

**Auteur** : Mohamed EL AFRIT — [www.mohamedelafrit.com](https://www.mohamedelafrit.com)

**Licence** : [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/deed.fr)

---

## Presentation

Ce projet est une plateforme educative complete couvrant les fondements mathematiques de l'IA/ML en **23 heures reparties sur 4 jours** (5h45/jour). L'approche pedagogique cible des developpeurs (et non des data scientists) et suit le principe **"from scratch d'abord, librairie ensuite"**.

Un **dataset fil rouge** de 30 developpeurs (prediction de salaire) est utilise tout au long des 4 jours, permettant une progression coherente des concepts.

### Chiffres cles

- 23 heures de formation
- 4 jours de cours interactifs
- 30+ exercices interactifs
- 14 notebooks Jupyter
- 13 documents PDF (syntheses, exercices, corrections)

---

## Programme des 4 jours

| Jour | Theme | Couleur | Concepts cles |
|------|-------|---------|---------------|
| **Jour 1** | Donnees et representation | Bleu | Vecteurs, matrices, produit scalaire, regression lineaire, MSE |
| **Jour 2** | Optimisation | Vert | Fonction de cout, derivees, descente de gradient, taux d'apprentissage |
| **Jour 3** | Probabilites et classification | Orange | Classification binaire, frontiere de decision, perceptron, probleme XOR |
| **Jour 4** | Reseaux de neurones | Violet | Neurone artificiel, fonctions d'activation, MLP, backpropagation |

Un **mini-projet** de synthese en 6 etapes integre l'ensemble des concepts des 4 jours.

---

## Structure du projet

```
.
├── index.html                  # Page d'accueil
├── jour1.html                  # Jour 1 — Donnees et representation
├── jour2.html                  # Jour 2 — Optimisation
├── jour3.html                  # Jour 3 — Probabilites et classification
├── jour4.html                  # Jour 4 — Reseaux de neurones
├── mini_projet.html            # Mini-projet de synthese (6 etapes)
│
├── notebook/                   # Notebooks Jupyter (14 fichiers)
│   ├── Jour1_Cours.ipynb       #   Cours du jour 1
│   ├── Jour1_TP_Enonce.ipynb   #   TP enonce du jour 1
│   ├── Jour1_TP_Corrige.ipynb  #   TP corrige du jour 1
│   ├── ...                     #   (idem pour Jours 2, 3, 4)
│   └── Mini_Projet.ipynb       #   Notebook du mini-projet
│
├── pdf/                        # Ressources PDF (13 fichiers)
│   ├── guide_colab.pdf         #   Guide de prise en main Google Colab
│   ├── jour1_synthese.pdf      #   Synthese du jour 1
│   ├── jour1_exercices.pdf     #   Exercices du jour 1
│   ├── jour1_corrections.pdf   #   Corrections du jour 1
│   └── ...                     #   (idem pour Jours 2, 3, 4)
│
├── tex/                        # Sources LaTeX des PDF
│   ├── template_ipssi.tex      #   Template LaTeX IPSSI
│   └── jourN_*.tex             #   Sources syntheses, exercices, corrections
│
├── template/                   # Templates et references
│   ├── COMPOSANTS_REFERENCE.md #   Documentation des composants React
│   ├── dataset_fil_rouge.py    #   Definition du dataset (30 developpeurs)
│   └── template_ipssi.tex      #   Template LaTeX
│
├── prompts/                    # Instructions de conception du projet
│
└── ypareo/                     # Plannings Ypareo (gestion de classe)
```

---

## Stack technique

### Pages interactives

| Technologie | Version | Usage |
|-------------|---------|-------|
| **React** | 18 | Framework UI (charge via CDN) |
| **Babel Standalone** | — | Transpilation JSX dans le navigateur |
| **KaTeX** | 0.16.9 | Rendu des formules mathematiques LaTeX |
| **Pyodide** | 0.24.1 | Execution Python (WASM) dans le navigateur |

### Polices

- **DM Sans** — Titres et interface
- **Source Serif 4** — Corps de texte
- **JetBrains Mono** — Blocs de code

### Environnement Python (Pyodide)

- Chargement paresseux au premier clic sur "Executer"
- Packages pre-charges : **NumPy**, **Matplotlib**
- Capture stdout et figures Matplotlib encodees en base64

### Documents PDF

- Generes a partir de sources **LaTeX** (`tex/`)
- Template IPSSI avec boites pedagogiques colorees

---

## Fonctionnalites interactives

- **Mode clair / sombre** : bascule via bouton dans chaque page
- **Cellules Python executables** : code Python directement dans le navigateur (Pyodide)
- **Rendu mathematique** : formules LaTeX via KaTeX
- **Navigation laterale** : sommaire avec suivi du scroll
- **Design responsive** : adapte mobile, tablette et desktop
- **Boites pedagogiques** : 6 types (definition, intuition, exemple, attention, retenir, code)

---

## Approche pedagogique

1. **Du concret a l'abstrait** : chaque concept part d'une analogie concrete avant la formalisation
2. **From scratch d'abord** : implementation manuelle des algorithmes avant l'utilisation de scikit-learn
3. **Execution interactive** : pas besoin d'installer Python, tout s'execute dans le navigateur
4. **Apprentissage visuel** : graphiques Matplotlib integres aux explications
5. **Curriculum en spirale** : chaque jour s'appuie sur les acquis precedents
6. **Dataset fil rouge** : les memes 30 developpeurs du jour 1 au jour 4

---

## Utilisation

### Consultation en ligne

Ouvrir `index.html` dans un navigateur moderne (Chrome, Firefox, Edge). Aucune installation requise — toutes les dependances sont chargees via CDN.

### Notebooks Jupyter

Les notebooks du dossier `notebook/` peuvent etre ouverts :
- Sur **Google Colab** (liens fournis dans les pages de cours)
- En local avec `jupyter notebook` (necessite Python + NumPy + Matplotlib)

### Compilation des PDF

Les sources LaTeX (`tex/`) necessitent une distribution TeX (TeX Live, MiKTeX) pour recompiler les PDF.

---

## Licence

Ce projet est distribue sous licence [Creative Commons Attribution - Pas d'Utilisation Commerciale - Partage dans les Memes Conditions 4.0 International (CC BY-NC-SA 4.0)](https://creativecommons.org/licenses/by-nc-sa/4.0/deed.fr).

Voir le fichier [LICENSE](LICENSE) pour le texte complet.
