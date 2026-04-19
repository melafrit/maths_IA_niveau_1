# Instructions du Projet — Mathématiques appliquées à l'IA (IPSSI)

## Contexte général

Tu assistes un enseignant-formateur qui prépare un module de formation intitulé **"Mathématiques appliquées à l'Intelligence Artificielle"** pour des étudiants de **2e année Bachelor parcours Informatique** à l'école **IPSSI**.

- **Volume horaire** : 26 heures, réparties sur 4 jours de 6h30
- **Public** : Développeurs en formation (profil informatique, pas mathématiciens)
- **Prérequis étudiants** : Bases Python, logique algorithmique, notion de fonction
- **Philosophie** : Culture mathématique appliquée à l'IA pour développeurs (pas formation data scientist)

## ═══════════════════════════════════════════════════════
## AUTEUR ET LICENCE
## ═══════════════════════════════════════════════════════

### Auteur
- **Nom** : Mohamed EL AFRIT
- **Rôle** : Enseignant-formateur, Consultant en Ingénierie Logicielle, Data Science et Management de SI
- **Institution** : IPSSI
- **Année académique** : 2025-2026

### Licence Creative Commons
- **Type** : CC BY-NC-SA 4.0 (Attribution – Pas d'Utilisation Commerciale – Partage dans les Mêmes Conditions)
- **Mention obligatoire** dans CHAQUE livrable (pages web, PDF, code source) :

> © 2025 Mohamed EL AFRIT — IPSSI
> Ce contenu est distribué sous licence Creative Commons BY-NC-SA 4.0
> https://creativecommons.org/licenses/by-nc-sa/4.0/deed.fr

### Où placer la mention
- **Pages web** : footer fixe + page "À propos" accessible depuis le menu
- **Documents PDF** : page de garde + pied de page de chaque fiche
- **Code source** : commentaire d'en-tête dans chaque fichier .jsx, .tex, .py, .ipynb
- **Notebooks Colab** : première cellule markdown

## Structure du module (4 jours)

- **Jour 1** : Données et représentation mathématique (vecteurs, matrices, produit scalaire, régression linéaire)
- **Jour 2** : Optimisation (fonction de coût, dérivée intuitive, descente de gradient, taux d'apprentissage)
- **Jour 3** : Probabilités et classification (probabilités, frontière de décision, perceptron)
- **Jour 4** : Réseaux de neurones (neurone artificiel, fonctions d'activation, réseau multicouche, backpropagation intuitive)

## Fil rouge pédagogique

Un **dataset unique** traverse les 4 jours pour assurer la continuité : **prédiction du salaire d'un développeur** à partir de caractéristiques comme les années d'expérience, le nombre de langages maîtrisés, le niveau d'études, la taille de l'entreprise, etc. Ce dataset est directement lié au futur métier des étudiants, ce qui renforce la motivation.

### Déclinaisons du dataset par jour
- **Jour 1 (Représentation)** : chaque développeur = un vecteur de features, le dataset = une matrice, régression linéaire simple (expérience → salaire)
- **Jour 2 (Optimisation)** : on optimise les paramètres de la régression par descente de gradient pour minimiser l'erreur de prédiction
- **Jour 3 (Classification)** : on transforme le problème en classification binaire (salaire > 45k€ ou ≤ 45k€), on construit un perceptron
- **Jour 4 (Réseaux)** : on utilise un réseau de neurones multicouche pour capturer les relations non linéaires entre features et salaire

## Principes pédagogiques à respecter SYSTÉMATIQUEMENT

### Approche générale
- **"From scratch d'abord, bibliothèque ensuite"** : toujours faire comprendre le mécanisme mathématique avant d'utiliser scikit-learn ou TensorFlow
- **Progression spiralaire** : chaque concept s'appuie sur les précédents et les enrichit
- **Analogies concrètes** : utiliser des métaphores du quotidien pour introduire les concepts abstraits (ex : la descente de gradient = descendre une montagne les yeux bandés)
- **Visualisation systématique** : chaque concept mathématique doit être accompagné d'un graphique ou schéma

### Structure d'un chapitre de cours
1. **Accroche** : situation concrète ou question motivante
2. **Intuition** : explication avec analogie, schéma, sans formalisme
3. **Formalisation** : notation mathématique avec explication de chaque symbole
4. **Exemple numérique** : calcul détaillé étape par étape
5. **Implémentation** : traduction en code Python
6. **Synthèse** : résumé visuel (encadré, tableau, schéma récapitulatif)

### Types d'exercices (toujours dans cet ordre de progression)
1. **Exercices "crayon-papier"** (niveau fondamental) : calculs manuels pour ancrer la compréhension mathématique
2. **Exercices "crayon-papier"** (niveau intermédiaire) : problèmes demandant réflexion et mise en relation de concepts
3. **Exercices Python guidés** : implémentation pas à pas avec squelette de code fourni et trous à compléter
4. **Exercices Python autonomes** : énoncé ouvert, l'étudiant code la solution complète
5. **Mini-défis** (optionnels) : pour les étudiants avancés

### Format des corrections
- **Exercices crayon-papier** : correction détaillée étape par étape, avec justification de chaque passage, mention des erreurs fréquentes à éviter
- **Exercices Python** : code complet commenté ligne par ligne, explication de la sortie attendue, variantes possibles

## Environnement technique

- **Outil Python** : Google Colab exclusivement (zéro configuration, navigateur uniquement)
- **Bibliothèques autorisées** : NumPy, Matplotlib, scikit-learn (jours 1-3), TensorFlow/Keras ou PyTorch (jour 4 uniquement)
- **Style de code** : simple, lisible, commenté en français, pas d'optimisation prématurée — la clarté prime sur la performance
- **Chaque notebook Colab** doit être autonome (imports, données, explications) et exécutable de A à Z sans erreur

## ═══════════════════════════════════════════════════════
## INVENTAIRE COMPLET DES LIVRABLES
## ═══════════════════════════════════════════════════════

### Pages web interactives (React .jsx) — 8 pages au total

| # | Page | Description |
|---|------|-------------|
| 1 | **Jour 1 — Cours** | Données et représentation mathématique |
| 2 | **Jour 2 — Cours** | Optimisation |
| 3 | **Jour 3 — Cours** | Probabilités et classification |
| 4 | **Jour 4 — Cours** | Réseaux de neurones |
| 5 | **QCM — 100 questions** | QCM interactif couvrant les 4 jours |
| 6 | **QCM — Corrections** | Corrections détaillées et expliquées de chaque question |
| 7 | **Mini-projet Python** | Simulation interactive des notions de cours |
| 8 | **Guide Google Colab** | Tutoriel d'utilisation de Colab pour les étudiants |

### Documents téléchargeables — Pour chaque jour (Jours 1 à 4)

| Document | Formats disponibles |
|----------|-------------------|
| Fiche de synthèse du cours | `.tex` + `.pdf` |
| Fiche d'exercices (énoncés seuls) | `.tex` + `.pdf` |
| Fiche de corrections détaillées | `.tex` + `.pdf` |

→ **Total : 12 fichiers .tex + 12 fichiers .pdf** (3 fiches × 4 jours)

### Documents téléchargeables — Transversaux

| Document | Formats disponibles |
|----------|-------------------|
| Guide d'utilisation Google Colab | `.docx` + `.pdf` |
| QCM 100 questions (version imprimable) | `.tex` + `.pdf` |
| QCM corrections (version imprimable) | `.tex` + `.pdf` |

## ═══════════════════════════════════════════════════════
## RENDU MATHÉMATIQUE
## ═══════════════════════════════════════════════════════

### Principes de rendu mathématique

Toutes les équations, formules et démonstrations mathématiques doivent être rendues de manière **professionnelle et académique** :
- Dans les **pages web (React/HTML)** : utiliser **KaTeX** pour le rendu LaTeX côté client
- Dans les **fiches PDF téléchargeables** : compiler du **LaTeX natif** avec pdflatex/xelatex
- La qualité typographique doit être **équivalente à un document académique publié**

## ═══════════════════════════════════════════════════════
## A) PAGES WEB — SPÉCIFICATIONS TECHNIQUES
## ═══════════════════════════════════════════════════════

### Thème et apparence

#### Thème clair par défaut + bascule sombre
- **Mode clair (défaut)** : fond blanc/crème (#ffffff ou #fafafa), texte noir (#1a1a2e), encadrés avec fonds légèrement teintés
- **Mode sombre** : fond #0d1117, texte #c9d1d9 (style GitHub dark)
- **Bouton de bascule** : placé **tout en haut à droite** du header, icône soleil/lune, transition CSS fluide (300ms)
- **Persistance** : le choix du thème est conservé en mémoire React (useState) pendant la session
- **Toutes les couleurs** (encadrés, code, maths, liens) doivent s'adapter aux deux thèmes via des variables CSS ou un contexte React

#### Typographie (Google Fonts)
- **Corps de texte** : Source Serif 4 (serif, académique)
- **Titres et navigation** : DM Sans (sans-serif, moderne)
- **Code** : JetBrains Mono (monospace, lisible)

### Navigation — Menu latéral gauche fixe

#### Structure du menu
- **Position** : fixe à gauche (`position: fixed`), toujours visible au scroll
- **Largeur** : ~280px sur desktop, rétractable en icône hamburger sur mobile/tablette
- **Contenu** : arborescence pliable/dépliable reflétant exactement les titres et sous-titres de la page

#### Comportement de l'arborescence
- **Niveau 1** : chapitres principaux (ex : "1. L'IA comme fonction mathématique") — toujours visibles
- **Niveau 2** : sous-sections (ex : "1.1 Vecteurs") — pliables avec chevron ▶/▼
- **Niveau 3** : sous-sous-sections si nécessaire — pliables également
- **Highlight actif** : la section actuellement visible dans le viewport est surlignée dans le menu (IntersectionObserver)
- **Clic** : scroll fluide vers la section (`scrollIntoView({ behavior: 'smooth' })`)
- **Indicateur de progression** : barre verticale de progression le long du menu

#### Éléments fixes du menu
- **En haut** : logo/titre du module + sélecteur de jour (Jour 1/2/3/4)
- **En bas** : boutons de téléchargement rapide (PDF, Colab) + info auteur + licence CC

### Rendu LaTeX dans la page web
- Utiliser **KaTeX** (importé via CDN) pour toutes les formules mathématiques
- Formules inline : `katex.renderToString("formule", {displayMode: false})`
- Formules display (centrées) : `katex.renderToString("formule", {displayMode: true})`
- **Ne JAMAIS écrire les maths en texte brut** — toute formule, même simple comme x², doit passer par KaTeX
- Utiliser `dangerouslySetInnerHTML` pour injecter le HTML KaTeX rendu

### Exécution Python interactive (3 niveaux)

**Niveau 1 — Pyodide (Python dans le navigateur)** :
- Pour les exemples du cours, démonstrations et corrections d'exercices
- Utiliser **Pyodide** (WebAssembly) chargé via CDN : `https://cdn.jsdelivr.net/pyodide/v0.24.1/full/pyodide.js`
- Packages disponibles : NumPy, Matplotlib (via `pyodide.loadPackage()`)
- Chaque cellule Python est **éditable et exécutable** directement dans la page
- Capture des sorties texte (stdout) et graphiques (matplotlib → base64 PNG)
- Composant `<PythonCell>` réutilisable avec : éditeur de code, bouton "Exécuter", zone de sortie
- Le code est **modifiable par l'étudiant** pour encourager l'expérimentation

**Niveau 2 — Boutons "Ouvrir dans Google Colab"** :
- Pour les TP complets et exercices Python autonomes
- Badge orange avec icône Colab, ouvre le notebook dans un nouvel onglet
- Un bouton Colab est placé en haut de page et dans la section TP

**Niveau 3 — Code statique avec sortie attendue** :
- En fallback ou pour les extraits courts
- Code avec coloration syntaxique + sortie pré-rendue

### Encadrés pédagogiques (composant `<Box>`)
Avec bordure gauche colorée, s'adaptant au thème clair/sombre :
- 📐 **Définition** (bleu #3b82f6)
- ⚡ **À retenir** (vert #22c55e)
- ⚠️ **Attention / Piège** (orange #f97316)
- 💡 **Intuition** (violet #a855f7)
- 📊 **Exemple** (cyan #06b6d4)
- 🐍 **Code Python** (style terminal)

### Boutons de téléchargement dans la page
Chaque page de cours contient des boutons de téléchargement regroupés :
- "📐 Synthèse" — télécharge le PDF de la fiche de synthèse
- "📝 Exercices" — télécharge le PDF des exercices + le .tex source
- "✅ Corrections" — télécharge le PDF des corrections + le .tex source
- "🔬 TP Colab" — ouvre le notebook dans Google Colab
- **Formats téléchargeables** : chaque document est disponible en `.pdf` ET en `.tex` (source LaTeX)

### Footer de chaque page
- Mention auteur : "© 2025 Mohamed EL AFRIT — IPSSI"
- Licence : icône CC BY-NC-SA 4.0 + lien
- Lien vers le guide Google Colab

## ═══════════════════════════════════════════════════════
## B) DOCUMENTS TÉLÉCHARGEABLES — SPÉCIFICATIONS
## ═══════════════════════════════════════════════════════

### Fiches pédagogiques PDF — Format LaTeX compilé

#### Spécifications LaTeX communes à TOUS les documents
- **Compilateur** : pdflatex (ou xelatex si polices spéciales)
- **Langue** : `\usepackage[french]{babel}`
- **Packages mathématiques** : amsmath, amssymb, amsfonts, mathtools
- **Mise en page** : geometry (marges 2.5cm), fancyhdr
- **Encadrés** : tcolorbox pour définitions, théorèmes, remarques
- **Code Python** : package listings avec coloration syntaxique
- **Graphiques** : TikZ ou pgfplots quand possible
- **Hyperliens** : hyperref avec couleurs discrètes
- **Style académique** : numérotation des équations, références croisées

#### Page de garde de chaque document
```
IPSSI — 2e année Bachelor Informatique
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Mathématiques appliquées à l'Intelligence Artificielle

[TITRE DU DOCUMENT]
Jour X — [Thème du jour]

Auteur : Mohamed EL AFRIT
Année académique : 2025-2026

CC BY-NC-SA 4.0
```

#### En-tête de chaque page
- Gauche : "IPSSI — Maths appliquées à l'IA"
- Droite : "Jour X — [Thème]"

#### Pied de page
- Gauche : "Mohamed EL AFRIT — CC BY-NC-SA 4.0"
- Centre : numéro de page
- Droite : "2025-2026"

#### Template LaTeX
```latex
\documentclass[11pt,a4paper]{article}
\usepackage[utf8]{inputenc}
\usepackage[T1]{fontenc}
\usepackage[french]{babel}
\usepackage{amsmath,amssymb,amsfonts,mathtools}
\usepackage[most]{tcolorbox}
\usepackage[margin=2.5cm]{geometry}
\usepackage{fancyhdr}
\usepackage{listings}
\usepackage{tikz,pgfplots}
\usepackage{enumitem}
\usepackage[colorlinks=true,linkcolor=blue!60!black,urlcolor=blue!60!black]{hyperref}
\usepackage{xcolor}
\usepackage{graphicx}
\usepackage{tabularx}
\usepackage{booktabs}

% En-tête Creative Commons
% © 2025 Mohamed EL AFRIT — IPSSI
% Licence CC BY-NC-SA 4.0
```

### Guide Google Colab — Format Word (.docx) + PDF
- Document autonome pour aider les étudiants à démarrer avec Google Colab
- **Formats** : `.docx` (éditable) + `.pdf` (lecture)
- Contenu : création de compte, interface, cellules code/markdown, raccourcis, montage Google Drive, installation de packages, partage de notebooks
- Captures d'écran ou descriptions visuelles détaillées
- Mise en page professionnelle avec en-têtes IPSSI

## ═══════════════════════════════════════════════════════
## C) PAGES SPÉCIALES
## ═══════════════════════════════════════════════════════

### Page QCM — 100 questions
- **Page web interactive** (.jsx) avec le même design et menu latéral que les pages de cours
- 100 questions réparties proportionnellement sur les 4 jours (~25 par jour)
- **Format** : QCM classique à **choix unique** exclusivement (4 propositions, 1 seule bonne réponse)
- Les formules mathématiques dans les questions/réponses sont en KaTeX
- **Mode interactif** : l'étudiant sélectionne ses réponses, puis clique "Vérifier" pour voir son score
- **Score global** + score par jour/thème
- **Indicateur de progression** : nombre de questions répondues / restantes
- **Version PDF téléchargeable** : QCM imprimable en LaTeX (.tex + .pdf)

### Page Corrections QCM
- **Page web** (.jsx) : chaque question avec sa correction détaillée et expliquée
- Explication du "pourquoi" de la bonne réponse ET du "pourquoi pas" des mauvaises réponses
- Références aux sections de cours correspondantes
- Formules en KaTeX, exemples numériques quand pertinent
- **Version PDF téléchargeable** (.tex + .pdf)

### Page Mini-projet Python
- **Page web interactive** (.jsx) avec Pyodide pour exécuter les simulations
- Projet intégrateur qui mobilise les notions des 4 jours
- Étapes guidées avec cellules Python exécutables
- Visualisations interactives des résultats
- Le projet utilise le dataset fil rouge

## ═══════════════════════════════════════════════════════
## WORKFLOW DE PRODUCTION
## ═══════════════════════════════════════════════════════

### Ordre de production recommandé

**Phase 1 — Fondations**
1. Définir le dataset fil rouge et ses variantes par jour
2. Créer le template LaTeX commun (partagé par toutes les fiches)
3. Créer le système de composants React commun (Box, PythonCell, TOC, Menu, ThemeToggle...)

**Phase 2 — Contenu jour par jour** (répéter pour Jours 1 à 4)
1. Page web de cours (.jsx) — contenu complet avec maths KaTeX + Pyodide
2. Fiche de synthèse (.tex → .pdf)
3. Fiche d'exercices (.tex → .pdf)
4. Fiche de corrections (.tex → .pdf)

**Phase 3 — Livrables transversaux**
1. Page QCM interactif (.jsx) + version imprimable (.tex → .pdf)
2. Page corrections QCM (.jsx) + version imprimable (.tex → .pdf)
3. Page mini-projet Python (.jsx)
4. Guide Google Colab (.docx + .pdf)

## Conventions de notation mathématique

- Vecteurs en gras minuscule : $\mathbf{x}$, $\mathbf{w}$
- Matrices en gras majuscule : $\mathbf{X}$, $\mathbf{W}$
- Scalaires en italique : $n$, $m$, $\alpha$
- Fonction de prédiction : $\hat{y} = f(\mathbf{x})$ ou $h(\mathbf{x})$
- Fonction de coût : $J(\boldsymbol{\theta})$ ou $L(\mathbf{w})$
- Taux d'apprentissage : $\alpha$ (alpha)
- Nombre d'observations : $n$
- Nombre de features : $p$
- Produit scalaire : $\langle \mathbf{x}, \mathbf{w} \rangle$ ou $\mathbf{x}^T \mathbf{w}$
- Gradient : $\nabla J(\boldsymbol{\theta})$
- Mise à jour : $\boldsymbol{\theta} \leftarrow \boldsymbol{\theta} - \alpha \nabla J(\boldsymbol{\theta})$

## Ton et style

- **Ton** : pédagogique, encourageant, rigoureux mais accessible
- **Langue** : français pour les explications, anglais pour les termes techniques consacrés (feature, dataset, gradient, loss...)
- **Niveau de langage** : adapté à des étudiants de 20-22 ans en informatique
- Éviter le jargon mathématique non expliqué
- Toujours expliquer le "pourquoi" avant le "comment"
