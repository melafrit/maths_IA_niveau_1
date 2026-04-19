# Prompts optimisés — Production des livrables
## Module "Mathématiques appliquées à l'IA" — IPSSI

**Auteur** : Mohamed EL AFRIT www.mohamedelafrit.com
**Dataset fil rouge** : Prédiction du salaire d'un développeur
**QCM** : 100 questions à choix unique

---

## 🗺️ PLAN DE PRODUCTION — Vue d'ensemble

```
PHASE 0 — Fondations (2 prompts)
  ├── P0.1  Dataset fil rouge complet
  └── P0.2  Template LaTeX + Composants React partagés

PHASE 1 — Jour 1 : Données et représentation (4 prompts)
  ├── P1.1  Page web cours Jour 1
  ├── P1.2  Fiche synthèse LaTeX/PDF Jour 1
  ├── P1.3  Fiche exercices LaTeX/PDF Jour 1
  └── P1.4  Fiche corrections LaTeX/PDF Jour 1

PHASE 2 — Jour 2 : Optimisation (4 prompts)
  ├── P2.1  Page web cours Jour 2
  ├── P2.2  Fiche synthèse LaTeX/PDF Jour 2
  ├── P2.3  Fiche exercices LaTeX/PDF Jour 2
  └── P2.4  Fiche corrections LaTeX/PDF Jour 2

PHASE 3 — Jour 3 : Probabilités et classification (4 prompts)
  ├── P3.1  Page web cours Jour 3
  ├── P3.2  Fiche synthèse LaTeX/PDF Jour 3
  ├── P3.3  Fiche exercices LaTeX/PDF Jour 3
  └── P3.4  Fiche corrections LaTeX/PDF Jour 3

PHASE 4 — Jour 4 : Réseaux de neurones (4 prompts)
  ├── P4.1  Page web cours Jour 4
  ├── P4.2  Fiche synthèse LaTeX/PDF Jour 4
  ├── P4.3  Fiche exercices LaTeX/PDF Jour 4
  └── P4.4  Fiche corrections LaTeX/PDF Jour 4

PHASE 5 — Livrables transversaux (4 prompts)
  ├── P5.1  Page QCM interactif (100 questions) + PDF
  ├── P5.2  Page corrections QCM + PDF
  ├── P5.3  Page mini-projet Python
  └── P5.4  Guide Google Colab (DOCX + PDF)
```

**Total : 22 prompts** à exécuter dans l'ordre.

---

## ═══════════════════════════════════════════════════════════
## PHASE 0 — FONDATIONS
## ═══════════════════════════════════════════════════════════

---

### PROMPT P0.1 — Dataset fil rouge

```
Génère le dataset fil rouge complet qui traversera les 4 jours de formation.

THÈME : Prédiction du salaire d'un développeur.

Le dataset doit contenir exactement 30 observations (développeurs fictifs) avec les colonnes suivantes :
- experience : années d'expérience (entier, 0 à 20)
- nb_langages : nombre de langages de programmation maîtrisés (entier, 1 à 8)
- niveau_etudes : niveau d'études codé numériquement (1=Bac, 2=Bac+2, 3=Bac+3, 4=Bac+5, 5=Doctorat)
- taille_entreprise : nombre d'employés (entier, 10 à 5000)
- remote : pourcentage de télétravail (0 à 100)
- salaire : salaire annuel brut en k€ (float, réaliste pour la France, entre 25 et 75)

CONTRAINTES :
1. Les données doivent être RÉALISTES et cohérentes (plus d'expérience et d'études → tendance salaire plus élevé, avec du bruit)
2. Inclure quelques cas atypiques (junior bien payé en startup, senior sous-payé)
3. Les données doivent permettre une régression linéaire visible (Jour 1-2) ET une classification binaire intéressante avec seuil 45k€ (Jour 3)
4. Prévoir une colonne binaire dérivée : salaire_eleve (1 si salaire > 45, 0 sinon) pour le Jour 3

LIVRABLES :
- Code Python qui crée le dataset en NumPy (pas de pandas, pour rester cohérent avec le cours)
- Le même dataset en format brut (listes Python) pour l'intégrer dans les pages web sans dépendance
- Un résumé statistique rapide (min, max, moyenne par colonne)
- Un graphique matplotlib : nuage de points experience vs salaire avec couleur selon salaire_eleve

Le code doit être commenté en français, prêt à copier-coller dans Google Colab.
```

---

### PROMPT P0.2 — Templates partagés

```
Génère les fondations techniques réutilisables par TOUS les livrables suivants.

PARTIE A — Template LaTeX commun
Crée un fichier template_ipssi.tex qui sera le préambule partagé de TOUS les documents LaTeX du projet.
Il doit inclure :
- Packages : inputenc, fontenc, babel french, amsmath, amssymb, mathtools, tcolorbox, geometry (marges 2.5cm), fancyhdr, listings, tikz, pgfplots, enumitem, hyperref, xcolor, booktabs, tabularx
- Environnements tcolorbox personnalisés : definitionbox (bleu), retenirbox (vert), attentionbox (orange), intuitionbox (violet), exemplebox (cyan)
- Configuration listings pour Python (coloration syntaxique, fond gris clair, police mono)
- En-tête fancyhdr : "IPSSI — Maths appliquées à l'IA" à gauche, "Jour X — [Thème]" à droite (paramétrable)
- Pied de page : "Mohamed EL AFRIT www.mohamedelafrit.com — CC BY-NC-SA 4.0" à gauche, page au centre, "2025-2026" à droite
- Commande \pagegarde{titre}{jour}{theme} pour générer une page de garde standardisée
- Niveaux d'exercice : commandes \exofondamental, \exointermediaire, \exoavance avec étoiles ★/★★/★★★
- Mention CC BY-NC-SA 4.0 en bas de la page de garde

Compile ce template avec un contenu de test pour vérifier qu'il fonctionne.

PARTIE B — Note sur les composants React
Les composants React partagés (Box, PythonCell, MathInline, MathBlock, TOC, Section, ThemeToggle, Sidebar, ColabButton, PDFButton) seront définis DANS chaque fichier .jsx (pas d'imports entre fichiers, car chaque page est un artifact autonome). Génère un fichier COMPOSANTS_REFERENCE.md qui documente chaque composant avec ses props, pour assurer la cohérence entre les pages.

Les composants doivent supporter le thème clair (défaut) ET sombre :
- ThemeToggle : bouton soleil/lune en haut à droite
- Sidebar : menu latéral gauche fixe avec arborescence pliable des titres/sous-titres
- Toutes les couleurs passent par un objet theme = { light: {...}, dark: {...} }

Auteur : Mohamed EL AFRIT www.mohamedelafrit.com — IPSSI — CC BY-NC-SA 4.0
```

---

## ═══════════════════════════════════════════════════════════
## PHASE 1 — JOUR 1 : Données et représentation mathématique
## ═══════════════════════════════════════════════════════════

---

### PROMPT P1.1 — Page web cours Jour 1

```
Génère la page web interactive complète du JOUR 1 — "Données et représentation mathématique" (6h30).

FORMAT : Fichier React (.jsx), artifact autonome.

STRUCTURE DE LA PAGE (respecter cet ordre) :

1. L'IA COMME FONCTION MATHÉMATIQUE
   1.1 Accroche : "Comment Netflix prédit le film que vous allez aimer ?"
   1.2 Intuition : analogie machine à café → entrées/sortie
   1.3 Formalisation : ŷ = f(x), puis f_θ(x) — chaque symbole expliqué
   1.4 Notion de paramètres et d'apprentissage
   → Encadré Définition + Encadré À retenir

2. DATASET : OBSERVATIONS ET FEATURES
   2.1 Présentation du dataset fil rouge "Salaire développeur" (30 observations, 5 features + 1 cible)
   2.2 Tableau des premières lignes du dataset dans la page
   2.3 Cellule Pyodide : créer et afficher le dataset en NumPy
   → Encadré Exemple

3. VECTEURS : REPRÉSENTER UNE OBSERVATION
   3.1 Intuition : un développeur = un point dans un espace à 5 dimensions
   3.2 Formalisation : x ∈ ℝᵖ, notation colonne, transposée
   3.3 Exemple numérique : le vecteur de 3 développeurs du dataset
   3.4 Cellule Pyodide : créer des vecteurs NumPy, accéder aux composantes
   → Encadré Définition + Encadré Attention (dimension)

4. MATRICES : REPRÉSENTER UN DATASET
   4.1 Intuition : le dataset complet = un tableau = une matrice
   4.2 Formalisation : X ∈ ℝⁿˣᵖ, notation, indexation X_ij
   4.3 Exemple numérique avec la matrice du dataset
   4.4 Cellule Pyodide : créer la matrice, shape, slicing, transposée
   → Encadré Définition

5. PRODUIT SCALAIRE : COMBINER FEATURES ET POIDS
   5.1 Intuition : "pondérer" l'importance de chaque feature (comme calculer une moyenne pondérée)
   5.2 Formalisation : ⟨x, w⟩ = Σ xᵢwᵢ, notation matricielle x^T w
   5.3 Exemple numérique détaillé : prédire un salaire avec des poids w manuels
   5.4 Cellule Pyodide : calcul from scratch (boucle) puis np.dot puis @
   → Encadré Définition + Encadré Attention (dimensions compatibles)

6. MULTIPLICATION MATRICE-VECTEUR
   6.1 Intuition : prédire le salaire de TOUS les développeurs d'un coup
   6.2 Formalisation : ŷ = Xw, explication dimension par dimension
   6.3 Exemple numérique 3×2 détaillé
   6.4 Cellule Pyodide : multiplication matrice-vecteur
   → Encadré À retenir (lien produit scalaire ↔ multiplication matricielle)

7. RÉGRESSION LINÉAIRE : NOTRE PREMIER MODÈLE
   7.1 Accroche : "Peut-on tracer une droite qui prédit le salaire à partir de l'expérience ?"
   7.2 Intuition : la droite des moindres carrés
   7.3 Formalisation : ŷ = w₀ + w₁x (cas simple), puis ŷ = Xw (cas général)
   7.4 Notion d'erreur : résidu, erreur quadratique
   7.5 Formalisation MSE : J(w) = (1/n) Σ(yᵢ - ŷᵢ)²
   7.6 Cellule Pyodide : régression simple experience → salaire avec graphique
   7.7 Cellule Pyodide : calcul du MSE from scratch
   → Encadré Définition (MSE) + Encadré À retenir (plus MSE petit = meilleur modèle)

8. SYNTHÈSE DU JOUR 1
   8.1 Tableau récapitulatif : concept → notation → code NumPy
   8.2 Schéma visuel : pipeline Données → Vecteur → Matrice → Produit → Prédiction
   8.3 Boutons de téléchargement : PDF synthèse, exercices, corrections, Colab TP

SPÉCIFICATIONS TECHNIQUES :
- Thème CLAIR par défaut avec bouton bascule sombre en haut à droite
- Menu latéral gauche FIXE avec arborescence pliable des sections 1-8
- Highlight de la section active au scroll (IntersectionObserver)
- Toutes les formules en KaTeX (inline et display)
- Cellules Python Pyodide éditables et exécutables
- Encadrés pédagogiques : Définition (bleu), À retenir (vert), Attention (orange), Intuition (violet), Exemple (cyan)
- Footer : "© 2025 Mohamed EL AFRIT www.mohamedelafrit.com — IPSSI — CC BY-NC-SA 4.0"
- Google Fonts : Source Serif 4, DM Sans, JetBrains Mono
- Dataset fil rouge intégré directement dans le code (pas de fetch externe)
```

---

### PROMPT P1.2 — Fiche synthèse LaTeX/PDF Jour 1

```
Génère la fiche de synthèse du Jour 1 — "Données et représentation mathématique".

FORMAT : Fichier LaTeX (.tex) compilable en PDF avec pdflatex.
LONGUEUR CIBLE : 3-4 pages A4.

Utilise le template LaTeX IPSSI (packages : amsmath, amssymb, mathtools, tcolorbox, geometry 2.5cm, fancyhdr, listings, tikz, hyperref, babel french).

CONTENU DE LA FICHE :

Page de garde :
- "IPSSI — 2e année Bachelor Informatique"
- "Mathématiques appliquées à l'Intelligence Artificielle"
- "FICHE DE SYNTHÈSE — Jour 1 : Données et représentation mathématique"
- "Mohamed EL AFRIT www.mohamedelafrit.com — 2025-2026 — CC BY-NC-SA 4.0"

Corps de la fiche :

1. L'IA comme fonction : encadré Définition de f_θ(x), explication des paramètres

2. Vecteurs et matrices :
   - Encadré Définition : vecteur x ∈ ℝᵖ
   - Encadré Définition : matrice X ∈ ℝⁿˣᵖ
   - Notation : conventions (gras, indices)

3. Produit scalaire :
   - Formule avec les 3 notations : ⟨x,w⟩ = x^T w = Σ xᵢwᵢ
   - Propriétés essentielles (commutativité, linéarité)
   - Lien avec la prédiction

4. Régression linéaire :
   - Modèle : ŷ = w₀ + w^T x
   - Encadré Définition : MSE
   - Encadré À retenir : "Objectif = trouver w qui minimise J(w)"

5. Tableau récapitulatif : Concept | Notation | Code NumPy (3 colonnes)

6. Formules essentielles numérotées (à retenir)

En-tête : "IPSSI — Maths appliquées à l'IA" | "Jour 1 — Représentation"
Pied : "Mohamed EL AFRIT www.mohamedelafrit.com — CC BY-NC-SA 4.0" | page | "2025-2026"

Compile le fichier .tex en .pdf et fournis les deux fichiers.
```

---

### PROMPT P1.3 — Fiche exercices Jour 1

```
Génère la fiche d'exercices (ÉNONCÉS SEULS, sans corrections) du Jour 1.

FORMAT : Fichier LaTeX (.tex) compilable en PDF.
NOMBRE D'EXERCICES : 12 exercices progressifs.

RÉPARTITION :
- 4 exercices crayon-papier ★ (fondamental) : calculs directs
- 3 exercices crayon-papier ★★ (intermédiaire) : réflexion et mise en relation
- 3 exercices Python guidés ★★ : squelette fourni avec trous à compléter
- 2 exercices Python autonomes ★★★ : énoncé ouvert

LISTE DES EXERCICES :

★ Exercice 1 — Vecteurs et dimensions
Donner la dimension de vecteurs donnés, identifier features d'un développeur.

★ Exercice 2 — Produit scalaire à la main
Calculer ⟨x,w⟩ pour 2-3 paires de vecteurs, dont un piège de dimension.

★ Exercice 3 — Multiplication matrice-vecteur
Calculer Xw à la main pour une matrice 3×2 et un vecteur de poids.

★ Exercice 4 — Calcul du MSE
Données : 5 observations avec y_réel et y_prédit. Calculer le MSE pas à pas.

★★ Exercice 5 — Interpréter les poids
Donné un modèle ŷ = 25 + 3×experience + 1.5×nb_langages, interpréter chaque poids en français.

★★ Exercice 6 — Choisir le meilleur modèle
Deux modèles avec des poids différents. Calculer le MSE de chacun, conclure.

★★ Exercice 7 — Matrice et dataset
Construire la matrice X et le vecteur y à partir d'un petit tableau de données textuelles.

★★ Exercice 8 (Python guidé) — Créer et manipuler des vecteurs NumPy
Squelette fourni avec # À COMPLÉTER. Créer vecteurs, calculer norme, produit scalaire.

★★ Exercice 9 (Python guidé) — Construire la matrice du dataset
Squelette fourni. Charger les données dans une matrice, extraire lignes/colonnes.

★★ Exercice 10 (Python guidé) — Régression linéaire from scratch
Squelette fourni. Calculer ŷ = Xw, puis MSE, puis afficher le graphique.

★★★ Exercice 11 (Python autonome) — Explorer le dataset salaire
Énoncé ouvert : charger le dataset, calculer statistiques descriptives, visualiser les corrélations.

★★★ Exercice 12 (Python autonome) — Tester plusieurs modèles
Essayer 3 jeux de poids différents, calculer le MSE de chacun, tracer les droites, conclure.

CONTEXTE : Tous les exercices utilisent le dataset fil rouge "Salaire développeur".
Chaque exercice indique clairement : numéro, titre, type (crayon-papier / Python), niveau (★/★★/★★★).
Les exercices Python guidés fournissent le squelette avec des commentaires "# À COMPLÉTER".

En-tête/pied de page standards IPSSI + CC BY-NC-SA 4.0.
Compile le .tex en .pdf et fournis les deux.
```

---

### PROMPT P1.4 — Fiche corrections Jour 1

```
Génère la fiche de CORRECTIONS DÉTAILLÉES des exercices du Jour 1.

FORMAT : Fichier LaTeX (.tex) compilable en PDF.

Pour CHAQUE exercice (1 à 12) :

EXERCICES CRAYON-PAPIER (1-7) :
- Réécrire l'énoncé en résumé (1 ligne)
- Correction étape par étape avec TOUTES les étapes intermédiaires
- Justification de chaque passage mathématique
- Encadré "Erreurs fréquentes" : les 2-3 erreurs les plus courantes sur cet exercice
- Encadré "À retenir" quand pertinent
- Résultat final encadré

EXERCICES PYTHON (8-12) :
- Code complet fonctionnel commenté ligne par ligne en français
- Explication de la sortie attendue (texte et/ou graphique décrit)
- Encadré "Variantes" : une façon alternative de résoudre le problème
- Encadré "Erreurs fréquentes" en Python (IndexError, shape mismatch, etc.)

IMPORTANT :
- Les corrections doivent être TRÈS détaillées — un étudiant qui n'a pas réussi l'exercice doit pouvoir comprendre chaque étape
- Chaque correction commence par un rappel de la formule/concept utilisé
- Utiliser le formalisme LaTeX académique pour toutes les équations
- Le code Python est dans des environnements lstlisting avec coloration syntaxique

En-tête/pied de page standards IPSSI + CC BY-NC-SA 4.0.
Compile le .tex en .pdf et fournis les deux.
```

---

## ═══════════════════════════════════════════════════════════
## PHASE 2 — JOUR 2 : Optimisation
## ═══════════════════════════════════════════════════════════

---

### PROMPT P2.1 — Page web cours Jour 2

```
Génère la page web interactive complète du JOUR 2 — "Optimisation" (6h30).

FORMAT : Fichier React (.jsx), artifact autonome. Mêmes composants et design que Jour 1 (thème clair défaut, bascule sombre, menu latéral, KaTeX, Pyodide).

STRUCTURE DE LA PAGE :

1. RAPPEL ET TRANSITION
   1.1 "Au Jour 1, on a trouvé des poids w manuellement. Mais comment TROUVER les meilleurs poids automatiquement ?"
   1.2 Cellule Pyodide : rappel du MSE du Jour 1 avec les poids manuels

2. LA FONCTION DE COÛT
   2.1 Intuition : "Le GPS de l'apprentissage" — mesurer à quel point on se trompe
   2.2 Formalisation : J(w) = (1/n) Σ(yᵢ - ŷᵢ)²
   2.3 Interprétation graphique : J comme une courbe (cas 1 paramètre) ou surface (2 paramètres)
   2.4 Cellule Pyodide : tracer J(w₁) pour w₁ variant de -5 à 10 (fixer w₀)
   → Encadré Définition + Encadré Intuition

3. NOTION DE MINIMUM
   3.1 Intuition : "le creux de la vallée" — le point le plus bas de la courbe
   3.2 Formalisation : w* = argmin J(w)
   3.3 Exemple graphique : identifier visuellement le minimum sur la courbe
   → Encadré À retenir

4. INTRODUCTION À LA DÉRIVÉE
   4.1 Intuition : "la pente de la route sous vos pieds"
   4.2 Formalisation progressive : taux de variation → limite → dérivée
   4.3 Dérivées utiles : dérivée de x², de ax+b, règle de la chaîne (intuitive)
   4.4 Lien dérivée-minimum : dérivée = 0 au minimum
   4.5 Exemple numérique : dérivée de J(w) = (w-3)²
   4.6 Cellule Pyodide : tracer une fonction et sa dérivée, montrer le point dérivée=0
   → Encadré Définition (dérivée) + Encadré Attention (maximum vs minimum)

5. LA DESCENTE DE GRADIENT
   5.1 Accroche : "Descendre une montagne les yeux bandés"
   5.2 Intuition : on tâte la pente, on fait un pas dans la direction descendante, on recommence
   5.3 Formalisation : w ← w - α · ∂J/∂w
   5.4 Algorithme pas à pas (pseudo-code puis Python)
   5.5 Exemple numérique COMPLET : 5 itérations à la main sur J(w) = (w-3)²
   5.6 Cellule Pyodide : descente de gradient animée (afficher w et J(w) à chaque itération)
   → Encadré Définition + Encadré À retenir

6. LE TAUX D'APPRENTISSAGE α
   6.1 Intuition : "la taille de vos pas" — trop petit = trop lent, trop grand = on dépasse
   6.2 Visualisation : 3 graphiques côte à côte (α trop petit, α bon, α trop grand)
   6.3 Cellule Pyodide : comparer convergence pour α = 0.001, 0.01, 0.1, 1.0
   6.4 Notion de divergence
   → Encadré Attention (choix de α) + Encadré À retenir

7. DESCENTE DE GRADIENT SUR LE DATASET SALAIRE
   7.1 Application complète : régression linéaire simple (experience → salaire) optimisée par gradient descent
   7.2 Formalisation des dérivées partielles ∂J/∂w₀ et ∂J/∂w₁
   7.3 Cellule Pyodide : implémentation complète from scratch + animation de la convergence
   7.4 Cellule Pyodide : comparaison avec scikit-learn LinearRegression
   → Encadré Exemple

8. SYNTHÈSE DU JOUR 2
   8.1 Schéma récapitulatif : boucle Prédiction → Erreur → Gradient → Mise à jour
   8.2 Tableau : Concept | Formule | Rôle
   8.3 Boutons de téléchargement

Footer : "© 2025 Mohamed EL AFRIT www.mohamedelafrit.com — IPSSI — CC BY-NC-SA 4.0"
```

---

### PROMPT P2.2 — Fiche synthèse LaTeX/PDF Jour 2

```
Génère la fiche de synthèse du Jour 2 — "Optimisation" (3-4 pages A4).

FORMAT : Fichier LaTeX compilable en PDF. Template IPSSI standard.

CONTENU :
1. Fonction de coût MSE : formule encadrée, interprétation graphique (schéma TikZ de la parabole J(w))
2. Dérivée — résumé : définition, dérivées utiles (tableau), lien dérivée-minimum
3. Descente de gradient : algorithme en 4 étapes, formule de mise à jour encadrée, schéma TikZ illustrant les itérations sur la courbe
4. Taux d'apprentissage : tableau des 3 cas (trop petit, bon, trop grand) avec comportement
5. Application à la régression : dérivées partielles ∂J/∂w₀ et ∂J/∂w₁ complètes
6. Formules essentielles numérotées
7. Tableau récapitulatif : Concept | Formule | Code Python

En-tête/pied de page standards. Mohamed EL AFRIT www.mohamedelafrit.com — CC BY-NC-SA 4.0.
Compile .tex → .pdf, fournis les deux.
```

---

### PROMPT P2.3 — Fiche exercices Jour 2

```
Génère la fiche d'exercices (ÉNONCÉS SEULS) du Jour 2 — "Optimisation".
12 exercices progressifs, même structure que Jour 1 : 4★ + 3★★ + 3★★ Python guidés + 2★★★ Python autonomes.

EXERCICES :
★1 — Calculer le MSE pour 4 observations données
★2 — Calculer la dérivée de fonctions simples (3x², x²-6x+9, 2x+5)
★3 — Une itération de descente de gradient à la main sur J(w) = (w-4)²
★4 — Trois itérations de descente de gradient avec α=0.1 sur J(w) = w²
★★5 — Dérivées partielles de J(w₀,w₁) pour la régression linéaire
★★6 — Diagnostiquer un problème : α trop grand (données d'itérations divergentes fournies)
★★7 — Comparer deux taux d'apprentissage sur un même problème
★★8 (Python guidé) — Implémenter la descente de gradient 1D from scratch
★★9 (Python guidé) — Visualiser la convergence (tracer J(w) à chaque itération)
★★10 (Python guidé) — Descente de gradient pour la régression sur le dataset salaire
★★★11 (Python autonome) — Comparer 4 valeurs de α et tracer les courbes de convergence
★★★12 (Python autonome) — Implémenter et comparer : gradient descent from scratch vs scikit-learn

Contexte : dataset salaire développeur.
En-tête/pied standards. Compile .tex → .pdf.
```

---

### PROMPT P2.4 — Fiche corrections Jour 2

```
Génère les corrections DÉTAILLÉES des 12 exercices du Jour 2.
Même format que les corrections Jour 1 : étape par étape, erreurs fréquentes, code commenté ligne par ligne.
Compile .tex → .pdf, fournis les deux.
```

---

## ═══════════════════════════════════════════════════════════
## PHASE 3 — JOUR 3 : Probabilités et classification
## ═══════════════════════════════════════════════════════════

---

### PROMPT P3.1 — Page web cours Jour 3

```
Génère la page web interactive complète du JOUR 3 — "Probabilités et classification" (6h30).

FORMAT : React (.jsx), mêmes composants et design que Jours 1-2.

STRUCTURE :

1. TRANSITION : DE LA RÉGRESSION À LA CLASSIFICATION
   1.1 "Au lieu de prédire un salaire exact, peut-on prédire si un développeur gagne plus de 45k€ ?"
   1.2 Passage du continu (régression) au discret (classification binaire)
   1.3 Cellule Pyodide : ajouter la colonne salaire_eleve au dataset, visualiser les 2 classes

2. RAPPELS DE PROBABILITÉS UTILES
   2.1 Probabilité d'un événement : P(A) ∈ [0,1]
   2.2 Variable aléatoire discrète (intuition : le résultat d'un dé)
   2.3 Espérance et variance (intuition seulement)
   2.4 Probabilité conditionnelle : P(salaire_eleve | experience > 5)
   2.5 Cellule Pyodide : calculer des probabilités empiriques sur le dataset
   → Encadrés Définition pour chaque notion

3. CLASSIFICATION LINÉAIRE
   3.1 Intuition : "tracer une frontière" qui sépare les deux classes
   3.2 Formalisation : la fonction de décision g(x) = w^T x + b
   3.3 Règle de décision : si g(x) > 0 alors classe 1, sinon classe 0
   3.4 Cellule Pyodide : visualiser le dataset 2D (experience, nb_langages) coloré par classe
   → Encadré Définition (classifieur linéaire)

4. FRONTIÈRE DE DÉCISION ET HYPERPLAN
   4.1 Intuition : la frontière = une droite (2D), un plan (3D), un hyperplan (nD)
   4.2 Formalisation : w^T x + b = 0 définit la frontière
   4.3 Cellule Pyodide : tracer la frontière de décision sur le nuage de points
   → Encadré Définition (hyperplan) + Encadré À retenir

5. LE PERCEPTRON
   5.1 Accroche : "Le premier neurone artificiel (1957)"
   5.2 Intuition : un neurone qui prend une décision binaire
   5.3 Formalisation : ŷ = signe(w^T x + b), fonction signe
   5.4 Algorithme d'apprentissage du perceptron (pseudo-code)
   5.5 Règle de mise à jour : w ← w + α(y - ŷ)x
   5.6 Exemple numérique : 3 itérations à la main
   5.7 Cellule Pyodide : perceptron from scratch sur le dataset salaire
   5.8 Cellule Pyodide : animation de l'apprentissage (frontière qui bouge)
   → Encadrés Définition + Intuition + À retenir

6. LIMITES DU PERCEPTRON : LE PROBLÈME XOR
   6.1 Présentation du problème XOR
   6.2 Cellule Pyodide : montrer que le perceptron échoue sur XOR
   6.3 "Il faut plus qu'un seul neurone..." → teaser Jour 4
   → Encadré Attention

7. COMPARAISON AVEC SCIKIT-LEARN
   7.1 Cellule Pyodide : Perceptron de sklearn sur le dataset salaire
   7.2 Comparaison des résultats from scratch vs sklearn
   → Encadré À retenir

8. SYNTHÈSE DU JOUR 3
   8.1 Tableau : Régression vs Classification
   8.2 Schéma : pipeline complet du perceptron
   8.3 Boutons de téléchargement

Footer : Mohamed EL AFRIT www.mohamedelafrit.com — CC BY-NC-SA 4.0
```

---

### PROMPT P3.2 — Fiche synthèse LaTeX/PDF Jour 3

```
Génère la fiche de synthèse du Jour 3 — "Probabilités et classification" (3-4 pages A4).
Inclure : rappels probabilités, classification binaire, frontière de décision (schéma TikZ), perceptron (algorithme + formule mise à jour), limites (XOR), tableau comparatif régression/classification.
Template IPSSI, Mohamed EL AFRIT www.mohamedelafrit.com — CC BY-NC-SA 4.0. Compile .tex → .pdf.
```

---

### PROMPT P3.3 — Fiche exercices Jour 3

```
Génère la fiche d'exercices (ÉNONCÉS SEULS) du Jour 3.
12 exercices : 4★ + 3★★ + 3★★ Python guidés + 2★★★ Python autonomes.

★1 — Calculer des probabilités simples sur un mini-dataset
★2 — Calculer P(salaire_eleve | experience > 5) à la main
★3 — Appliquer la règle de décision g(x) = w^Tx + b sur 3 observations
★4 — Tracer une frontière de décision donnée sur un graphique papier
★★5 — Exécuter 3 itérations de l'algorithme du perceptron à la main
★★6 — Expliquer pourquoi XOR n'est pas linéairement séparable
★★7 — Calculer l'accuracy d'un classifieur sur un mini-dataset
★★8 (Python) — Calculer les probabilités empiriques sur le dataset salaire
★★9 (Python) — Implémenter la règle de décision et visualiser la frontière
★★10 (Python) — Perceptron from scratch avec boucle d'apprentissage
★★★11 (Python) — Comparer perceptron from scratch et sklearn, afficher les 2 frontières
★★★12 (Python) — Tester le perceptron sur XOR, visualiser l'échec, proposer une solution

Dataset salaire. Template IPSSI. Compile .tex → .pdf.
```

---

### PROMPT P3.4 — Fiche corrections Jour 3

```
Corrections DÉTAILLÉES des 12 exercices du Jour 3. Même format détaillé que Jours 1-2.
Compile .tex → .pdf.
```

---

## ═══════════════════════════════════════════════════════════
## PHASE 4 — JOUR 4 : Réseaux de neurones
## ═══════════════════════════════════════════════════════════

---

### PROMPT P4.1 — Page web cours Jour 4

```
Génère la page web interactive complète du JOUR 4 — "Réseaux de neurones" (6h30).

FORMAT : React (.jsx), mêmes composants et design.

STRUCTURE :

1. TRANSITION : DU PERCEPTRON AU RÉSEAU
   1.1 Rappel : le perceptron simple ne résout pas XOR
   1.2 "Et si on combinait plusieurs neurones ?"

2. LE NEURONE ARTIFICIEL (version enrichie du perceptron)
   2.1 Somme pondérée : z = w^T x + b
   2.2 Fonction d'activation : a = σ(z) ou ReLU(z)
   2.3 Cellule Pyodide : calculer la sortie d'un neurone

3. FONCTIONS D'ACTIVATION
   3.1 Sigmoïde : σ(z) = 1/(1+e^(-z)) — intuition "interrupteur progressif"
   3.2 ReLU : max(0, z) — intuition "seuil"
   3.3 Pourquoi les fonctions d'activation sont indispensables (sans elles, tout reste linéaire)
   3.4 Cellule Pyodide : tracer sigmoïde et ReLU, comparer
   → Encadrés Définition + Intuition

4. COMPOSITION DE FONCTIONS
   4.1 Intuition : "une chaîne de montage" — la sortie d'un neurone devient l'entrée du suivant
   4.2 Formalisation : f∘g(x) = f(g(x))
   4.3 Exemple numérique : 2 neurones en série

5. RÉSEAU MULTICOUCHE (MLP)
   5.1 Architecture : couche d'entrée → couche(s) cachée(s) → couche de sortie
   5.2 Schéma interactif du réseau (dessiné en code)
   5.3 Forward pass : calcul de la sortie couche par couche
   5.4 Exemple numérique COMPLET : réseau 2-2-1 avec calcul de chaque neurone
   5.5 Cellule Pyodide : forward pass from scratch (NumPy)
   → Encadrés Définition + Exemple

6. FONCTION DE PERTE ET APPRENTISSAGE
   6.1 Rappel de la MSE, introduction de la cross-entropy (intuition seulement)
   6.2 Rôle du gradient dans l'apprentissage (rappel Jour 2, maintenant appliqué à chaque couche)
   6.3 Backpropagation : intuition "propager l'erreur en arrière" (PAS de calcul complet, juste l'idée)
   6.4 Cellule Pyodide : entraîner un mini réseau sur le dataset salaire
   → Encadrés Intuition + À retenir

7. RÉSEAU DE NEURONES SUR LE DATASET SALAIRE
   7.1 Cellule Pyodide : réseau NumPy from scratch sur classification salaire_eleve
   7.2 Cellule Pyodide : même chose avec TensorFlow/Keras (ou sklearn MLPClassifier)
   7.3 Comparaison des résultats : perceptron (Jour 3) vs réseau (Jour 4)
   7.4 Cellule Pyodide : résoudre XOR avec un réseau 2 couches !

8. LIMITES ET RISQUES
   8.1 Surapprentissage (overfitting) : intuition "apprendre par cœur"
   8.2 Biais dans les données → biais dans le modèle
   8.3 Encadré Attention : les réseaux ne sont pas magiques

9. SYNTHÈSE DU JOUR 4 + SYNTHÈSE GLOBALE DES 4 JOURS
   9.1 Schéma récapitulatif : perceptron → réseau multicouche
   9.2 Tableau des 4 jours : ce qu'on a appris, les formules clés
   9.3 "Vous avez maintenant les fondations mathématiques pour comprendre l'IA"

Footer : Mohamed EL AFRIT www.mohamedelafrit.com — CC BY-NC-SA 4.0
```

---

### PROMPT P4.2 — Fiche synthèse LaTeX/PDF Jour 4

```
Fiche de synthèse Jour 4 — "Réseaux de neurones" (3-4 pages).
Inclure : neurone artificiel (schéma TikZ), fonctions d'activation (formules + graphes), architecture MLP (schéma TikZ), forward pass, backpropagation (intuition), tableau comparatif perceptron/MLP, limites.
Template IPSSI, Mohamed EL AFRIT www.mohamedelafrit.com — CC BY-NC-SA 4.0. Compile .tex → .pdf.
```

---

### PROMPT P4.3 — Fiche exercices Jour 4

```
Fiche d'exercices (ÉNONCÉS SEULS) Jour 4.
12 exercices : 4★ + 3★★ + 3★★ Python guidés + 2★★★ Python autonomes.

★1 — Calculer la sortie d'un neurone (somme pondérée + activation sigmoïde)
★2 — Appliquer ReLU et sigmoïde à des valeurs données
★3 — Forward pass d'un réseau 2-2-1 à la main
★4 — Identifier les dimensions des matrices de poids d'un réseau donné
★★5 — Composer deux fonctions et calculer le résultat
★★6 — Expliquer pourquoi un réseau sans activation est équivalent à un modèle linéaire
★★7 — Analyser un cas de surapprentissage (courbes train/test fournies)
★★8 (Python) — Implémenter sigmoïde et ReLU, tracer les courbes
★★9 (Python) — Forward pass d'un réseau 2-2-1 en NumPy
★★10 (Python) — Mini réseau qui apprend XOR
★★★11 (Python) — Réseau from scratch pour classifier salaire_eleve
★★★12 (Python) — Comparer : réseau NumPy vs TensorFlow/Keras sur le dataset salaire

Dataset salaire. Template IPSSI. Compile .tex → .pdf.
```

---

### PROMPT P4.4 — Fiche corrections Jour 4

```
Corrections DÉTAILLÉES des 12 exercices du Jour 4. Même format.
Compile .tex → .pdf.
```

---

## ═══════════════════════════════════════════════════════════
## PHASE 5 — LIVRABLES TRANSVERSAUX
## ═══════════════════════════════════════════════════════════

---

### PROMPT P5.1 — Page QCM interactif + PDF

```
Génère la page web du QCM de 100 questions couvrant les 4 jours.

FORMAT : React (.jsx), même design (thème clair/sombre, menu latéral).

STRUCTURE DU QCM :
- 25 questions Jour 1 (Q1-Q25) : vecteurs, matrices, produit scalaire, régression, MSE
- 25 questions Jour 2 (Q26-Q50) : fonction de coût, dérivée, descente de gradient, learning rate
- 25 questions Jour 3 (Q51-Q75) : probabilités, classification, frontière de décision, perceptron
- 25 questions Jour 4 (Q76-Q100) : neurone, activation, réseau multicouche, backpropagation, limites

FORMAT DE CHAQUE QUESTION :
- Numéro + badge du jour (couleur)
- Énoncé (avec formules KaTeX si nécessaire)
- 4 propositions (A, B, C, D) — choix unique
- Les propositions contiennent des formules KaTeX quand c'est pertinent

FONCTIONNALITÉS INTERACTIVES :
- Sélection d'une réponse par question (radio buttons stylisés)
- Bouton "Vérifier mes réponses" en bas de page
- Après vérification : chaque question s'affiche en vert (correct) ou rouge (incorrect)
- Score global : X/100
- Score par jour : X/25 pour chaque jour
- Bouton "Réinitialiser"
- Possibilité de filtrer par jour (onglets ou filtre dans le menu)

TYPES DE QUESTIONS (variété au sein du choix unique) :
- Questions de définition : "Qu'est-ce que...?"
- Questions de calcul : "Quel est le résultat de...?"
- Questions de compréhension : "Pourquoi...?"
- Questions de diagnostic : "Que se passe-t-il si...?"
- Questions formule : "Quelle formule correspond à...?"

IMPORTANT : ne PAS afficher les explications — elles sont dans la page Corrections (P5.2).
Stocker la bonne réponse dans le code mais ne l'afficher qu'après le clic "Vérifier".

En parallèle, génère aussi le fichier LaTeX du QCM imprimable (.tex → .pdf) :
- Page de garde IPSSI standard
- Questions numérotées avec cases à cocher □ A □ B □ C □ D
- Grille de réponses en dernière page

Footer : Mohamed EL AFRIT www.mohamedelafrit.com — CC BY-NC-SA 4.0
```

---

### PROMPT P5.2 — Page corrections QCM + PDF

```
Génère la page web des corrections détaillées du QCM de 100 questions.

FORMAT : React (.jsx), même design.

POUR CHAQUE QUESTION (Q1 à Q100) :
1. Rappel de la question et des 4 propositions
2. Bonne réponse mise en évidence (encadré vert)
3. Explication DÉTAILLÉE de pourquoi c'est la bonne réponse (2-5 phrases)
4. Pourquoi les autres réponses sont fausses (1-2 phrases par mauvaise réponse)
5. Référence à la section du cours : "→ Voir Jour X, Section Y"
6. Si pertinent : formule mathématique en KaTeX ou exemple numérique rapide

NAVIGATION :
- Menu latéral avec les 100 questions groupées par jour
- Possibilité de sauter à une question spécifique
- Onglets par jour

Générer aussi le PDF des corrections (.tex → .pdf).

Footer : Mohamed EL AFRIT www.mohamedelafrit.com — CC BY-NC-SA 4.0
```

---

### PROMPT P5.3 — Page mini-projet Python

```
Génère la page web du mini-projet intégrateur Python.

FORMAT : React (.jsx), même design, cellules Pyodide exécutables.

TITRE DU PROJET : "Prédire le salaire d'un développeur : du vecteur au réseau de neurones"

CONCEPT : Un projet guidé en 6 étapes qui mobilise les notions des 4 jours sur le dataset fil rouge.

STRUCTURE :

Étape 1 — Exploration des données (Jour 1)
- Charger le dataset
- Statistiques descriptives
- Visualiser les corrélations (heatmap simplifiée)
- Identifier les features les plus corrélées au salaire

Étape 2 — Régression linéaire from scratch (Jour 1)
- Choisir 2 features, construire X et y
- Calculer ŷ = Xw avec des poids manuels
- Calculer le MSE

Étape 3 — Optimiser par descente de gradient (Jour 2)
- Implémenter la descente de gradient
- Trouver les poids optimaux
- Tracer la courbe de convergence
- Comparer avec scikit-learn

Étape 4 — Classifier : salaire élevé ou non ? (Jour 3)
- Créer la variable binaire salaire_eleve
- Implémenter un perceptron from scratch
- Visualiser la frontière de décision
- Calculer l'accuracy

Étape 5 — Réseau de neurones (Jour 4)
- Construire un réseau 2 couches en NumPy
- Entraîner sur le problème de classification
- Comparer avec le perceptron simple

Étape 6 — Synthèse et conclusions
- Tableau comparatif des 4 approches (MSE, accuracy)
- Graphiques récapitulatifs
- "Ce que vous avez accompli" — bilan pédagogique

Chaque étape contient :
- Objectif clair
- Rappel du concept mathématique (encadré)
- Cellule Pyodide avec code guidé (certaines parties à compléter, marquées # À COMPLÉTER)
- Questions de réflexion

Footer : Mohamed EL AFRIT www.mohamedelafrit.com — CC BY-NC-SA 4.0
```

---

### PROMPT P5.4 — Guide Google Colab (DOCX + PDF)

```
Génère le guide d'utilisation de Google Colab pour les étudiants.

FORMATS : fichier Word (.docx) avec mise en page professionnelle + compilation PDF.

CONTENU DU GUIDE (8-10 pages) :

1. Introduction
   - Qu'est-ce que Google Colab ?
   - Pourquoi on l'utilise dans ce module (gratuit, rien à installer, Python prêt)

2. Premiers pas
   - Créer un compte Google (si nécessaire)
   - Accéder à colab.research.google.com
   - Créer un nouveau notebook
   - Interface : barre de menu, cellules, boutons

3. Les cellules
   - Cellule de code : écrire et exécuter du Python
   - Cellule de texte (Markdown) : écrire des explications
   - Ajouter, supprimer, déplacer des cellules
   - Raccourcis clavier essentiels (Ctrl+Enter, Shift+Enter, Ctrl+M B)

4. Exécuter du code
   - Le bouton Play
   - L'ordre d'exécution des cellules (piège fréquent !)
   - Le runtime : connexion, déconnexion, redémarrage
   - "Exécuter tout" (Runtime > Run all)

5. Bibliothèques Python
   - NumPy, Matplotlib, scikit-learn : déjà installés
   - Comment installer un package supplémentaire : !pip install ...
   - Importer une bibliothèque

6. Gérer ses fichiers
   - Le système de fichiers Colab (temporaire !)
   - Monter Google Drive : from google.colab import drive
   - Importer/exporter des fichiers

7. Astuces pour le module
   - Écrire des maths en Markdown : $formule$ et $$formule$$
   - Afficher des graphiques inline
   - Bonnes pratiques : commenter son code, cellules courtes, exécuter dans l'ordre

8. Résolution de problèmes
   - "Mon code ne s'exécute pas" → vérifier le runtime
   - "Variable non définie" → exécuter les cellules dans l'ordre
   - "Le graphique ne s'affiche pas" → plt.show()
   - Redémarrer le runtime en cas de bug

Mise en page DOCX professionnelle :
- En-tête : "IPSSI — Mathématiques appliquées à l'IA"
- Pied de page : "Mohamed EL AFRIT www.mohamedelafrit.com — CC BY-NC-SA 4.0 — 2025-2026"
- Table des matières
- Numérotation des sections
- Encadrés "Astuce" et "Attention"
- Police professionnelle, marges standard

Fournir le .docx et le .pdf.
```

---

## ═══════════════════════════════════════════════════════════
## NOTES D'UTILISATION DES PROMPTS
## ═══════════════════════════════════════════════════════════

### Ordre d'exécution
Respecter l'ordre P0 → P1 → P2 → P3 → P4 → P5. Chaque phase s'appuie sur les précédentes.

### Cohérence entre livrables
- Le dataset fil rouge (P0.1) est réutilisé PARTOUT
- Le template LaTeX (P0.2) est réutilisé par TOUTES les fiches PDF
- Les composants React (P0.2) assurent la cohérence visuelle entre les pages
- Les exercices des fiches correspondent aux notions du cours web

### Vérification après chaque prompt
Après génération de chaque livrable, vérifier :
- [ ] Les formules KaTeX/LaTeX se rendent correctement
- [ ] Le thème clair/sombre fonctionne
- [ ] Le menu latéral reflète la structure
- [ ] Le footer contient "Mohamed EL AFRIT www.mohamedelafrit.com — CC BY-NC-SA 4.0"
- [ ] Le dataset salaire est cohérent avec les autres livrables
- [ ] Les fichiers .tex compilent sans erreur

### Adaptation
Si un prompt produit un résultat trop long ou trop court :
- Ajouter "Limite-toi aux sections 1-4" pour raccourcir
- Ajouter "Développe davantage la section X avec plus d'exemples" pour approfondir
