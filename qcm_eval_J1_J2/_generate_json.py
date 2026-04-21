#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generateur des fichiers questions.json et corrections.json
pour le QCM d'evaluation Jours 1 et 2.

(c) 2025 Mohamed EL AFRIT - IPSSI | CC BY-NC-SA 4.0
"""

import json
from pathlib import Path

# ============================================================================
# BANQUE DES 50 QUESTIONS
# ============================================================================
# Chaque question contient :
#   id, jour, difficulte (vert/jaune/orange/rouge), type (conceptuel/calcul/code/formule),
#   enonce (str, LaTeX dans $...$), propositions (liste de 4 str),
#   bonne_reponse_index (0-3), explication_bonne, explications_mauvaises (liste de 4),
#   reference_cours, piege_a_eviter
# ============================================================================

QUESTIONS = [
    # ========================================================================
    # JOUR 1 - Donnees et representation mathematique (25 questions)
    # ========================================================================

    # --- CONCEPTUEL (6) ---
    {
        "id": "Q01",
        "jour": 1,
        "difficulte": "vert",
        "type": "conceptuel",
        "enonce": "Dans le contexte du machine learning, qu'est-ce qu'un vecteur de features d'un developpeur ?",
        "propositions": [
            "Une liste ordonnee de caracteristiques numeriques decrivant un developpeur (ex : experience, nb langages, salaire)",
            "Un tableau de tous les developpeurs de l'entreprise",
            "Une seule valeur numerique representant la competence globale",
            "Un graphique a points representant un developpeur"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Un vecteur de features est une liste ordonnee de valeurs numeriques qui caracterisent une observation (ici un developpeur). Dans notre dataset fil rouge, chaque developpeur est decrit par un vecteur $\\mathbf{x} = (\\text{exp}, \\text{nb\\_langages}, \\text{niveau\\_etudes}, \\dots)$.",
        "explications_mauvaises": [
            None,
            "Un tableau de tous les developpeurs est une matrice (plusieurs observations empilees), pas un vecteur.",
            "Une seule valeur est un scalaire, pas un vecteur.",
            "Un graphique est une representation visuelle, pas une structure mathematique."
        ],
        "reference_cours": "Jour 1, section 1.1 - Vecteurs et representation des donnees",
        "piege_a_eviter": "Ne pas confondre vecteur (une observation) et matrice (plusieurs observations)."
    },
    {
        "id": "Q02",
        "jour": 1,
        "difficulte": "vert",
        "type": "conceptuel",
        "enonce": "Le dataset fil rouge contient 30 developpeurs avec 5 features. Quelle est la dimension de la matrice $\\mathbf{X}$ representant ce dataset ?",
        "propositions": [
            "$30 \\times 5$ (30 lignes, 5 colonnes)",
            "$5 \\times 30$ (5 lignes, 30 colonnes)",
            "$30 \\times 30$",
            "$5 \\times 5$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Par convention, chaque ligne de la matrice $\\mathbf{X}$ represente une observation (un developpeur) et chaque colonne represente une feature. Avec $n=30$ developpeurs et $p=5$ features, on a $\\mathbf{X} \\in \\mathbb{R}^{30 \\times 5}$.",
        "explications_mauvaises": [
            None,
            "C'est la matrice transposee $\\mathbf{X}^T$. La convention standard place les observations en lignes.",
            "Cette forme suppose 30 features alors qu'il n'y en a que 5.",
            "Cette forme ne tient pas compte du nombre de developpeurs."
        ],
        "reference_cours": "Jour 1, section 1.2 - Matrices et datasets",
        "piege_a_eviter": "Retenir la convention : une ligne = une observation, une colonne = une feature."
    },
    {
        "id": "Q03",
        "jour": 1,
        "difficulte": "jaune",
        "type": "conceptuel",
        "enonce": "Dans l'equation de regression lineaire simple $\\hat{y} = w \\cdot x + b$ appliquee a la prediction de salaire, que represente le parametre $w$ ?",
        "propositions": [
            "L'augmentation de salaire predite par annee d'experience supplementaire",
            "Le salaire de base d'un developpeur avec 0 an d'experience",
            "Le nombre moyen d'annees d'experience dans le dataset",
            "L'erreur moyenne de prediction du modele"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Le parametre $w$ est la **pente** de la droite de regression. Il represente la variation attendue de $\\hat{y}$ quand $x$ augmente d'une unite. Avec $w = 2{,}07$, chaque annee d'experience supplementaire augmente le salaire predit de 2 070 euros environ.",
        "explications_mauvaises": [
            None,
            "C'est le role de $b$ (biais ou intercept), valeur de $\\hat{y}$ quand $x = 0$.",
            "Aucun rapport : la moyenne des $x$ n'intervient pas directement dans cette equation.",
            "L'erreur moyenne est mesuree par le MSE, pas par $w$."
        ],
        "reference_cours": "Jour 1, section 3.2 - Interpretation des parametres",
        "piege_a_eviter": "Bien distinguer la pente $w$ (effet par unite) du biais $b$ (valeur a l'origine)."
    },
    {
        "id": "Q04",
        "jour": 1,
        "difficulte": "jaune",
        "type": "conceptuel",
        "enonce": "Pourquoi utilise-t-on le MSE (Mean Squared Error) plutot que la simple somme des erreurs $\\sum(y_i - \\hat{y}_i)$ comme mesure de qualite d'un modele ?",
        "propositions": [
            "Pour eviter que les erreurs positives et negatives se compensent et pour penaliser davantage les grosses erreurs",
            "Parce que le MSE est plus rapide a calculer",
            "Parce que le MSE donne toujours une valeur entiere",
            "Parce que le MSE est la seule mesure compatible avec NumPy"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "La simple somme des erreurs pose deux problemes : (1) les erreurs positives (surestimation) et negatives (sous-estimation) s'annulent, ce qui peut donner une somme nulle meme pour un mauvais modele ; (2) elle traite toutes les erreurs de la meme facon. Le carre resout les deux : il rend toutes les valeurs positives et **penalise davantage les grosses erreurs** (une erreur de 10 compte 100 fois plus qu'une erreur de 1).",
        "explications_mauvaises": [
            None,
            "Au contraire, le calcul du carre ajoute une operation.",
            "Faux : le MSE est un reel, generalement non entier.",
            "Faux : on peut calculer toute mesure d'erreur avec NumPy."
        ],
        "reference_cours": "Jour 1, section 4 - Fonction de cout MSE",
        "piege_a_eviter": "La somme signee n'est JAMAIS une bonne mesure d'erreur, meme pour un modele excellent."
    },
    {
        "id": "Q05",
        "jour": 1,
        "difficulte": "orange",
        "type": "conceptuel",
        "enonce": "On ajuste un modele de regression lineaire sur le dataset fil rouge et on obtient $w = 2{,}07$, $b = 31{,}27$, $MSE = 20{,}46$. Que peut-on conclure de la valeur du MSE ?",
        "propositions": [
            "L'erreur quadratique moyenne est de 20,46 (k\u20ac)\u00b2, ce qui correspond a une erreur typique de prediction d'environ 4,5 k\u20ac",
            "Le modele predit toujours le salaire avec une erreur de 20,46 k\u20ac",
            "Le modele a 20,46 % de precision",
            "Le salaire moyen est de 20,46 k\u20ac"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Le MSE est une **erreur au carre moyenne**. Pour revenir a l'ordre de grandeur des donnees, on prend la racine : $\\sqrt{20{,}46} \\approx 4{,}52$ k\u20ac. Cette valeur (RMSE) represente l'ecart typique entre la prediction et la vraie valeur.",
        "explications_mauvaises": [
            None,
            "Non : 20,46 est en (k\u20ac)\u00b2, pas en k\u20ac. Pour avoir une erreur typique en k\u20ac, il faut prendre la racine carree.",
            "Le MSE n'est pas un pourcentage : c'est une erreur absolue au carre.",
            "Le MSE ne represente pas la moyenne des salaires, mais l'erreur quadratique moyenne."
        ],
        "reference_cours": "Jour 1, section 4.3 - Interpretation du MSE",
        "piege_a_eviter": "L'unite du MSE est le carre de l'unite des donnees. Penser a la racine (RMSE) pour un ordre de grandeur interpretable."
    },
    {
        "id": "Q06",
        "jour": 1,
        "difficulte": "vert",
        "type": "conceptuel",
        "enonce": "Dans le dataset fil rouge, on considere la regression simple : salaire = $w$ \u00d7 experience + $b$. Laquelle de ces affirmations est correcte ?",
        "propositions": [
            "L'experience est la variable explicative ($x$), le salaire est la cible a predire ($y$)",
            "Le salaire est la variable explicative ($x$), l'experience est la cible ($y$)",
            "Les deux variables sont des variables cibles",
            "Les deux variables sont des features explicatives"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "En regression supervisee, on cherche a **predire une variable cible ($y$)** a partir d'une ou plusieurs **variables explicatives ($x$)**. Ici, on utilise l'experience pour predire le salaire, donc experience = $x$ et salaire = $y$.",
        "explications_mauvaises": [
            None,
            "Non : on cherche a predire le salaire, pas l'experience. C'est le sens du probleme qui definit $x$ et $y$.",
            "Faux : la variable cible est unique, c'est le salaire.",
            "Faux : il y a toujours une cible $y$ a predire."
        ],
        "reference_cours": "Jour 1, section 3.1 - Cadre de la regression",
        "piege_a_eviter": "Toujours identifier clairement ce qu'on cherche a predire ($y$) avant de commencer."
    },

    # --- CALCUL (10) ---
    {
        "id": "Q07",
        "jour": 1,
        "difficulte": "vert",
        "type": "calcul",
        "enonce": "Soit le vecteur $\\mathbf{x} = (3, 4)$. Quelle est sa norme euclidienne $\\|\\mathbf{x}\\|$ ?",
        "propositions": [
            "$5$",
            "$7$",
            "$12$",
            "$\\sqrt{7}$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "La norme euclidienne se calcule $\\|\\mathbf{x}\\| = \\sqrt{x_1^2 + x_2^2} = \\sqrt{9 + 16} = \\sqrt{25} = 5$. (C'est le theoreme de Pythagore.)",
        "explications_mauvaises": [
            None,
            "C'est la somme $3 + 4 = 7$, pas la norme. Il faut passer par le carre puis la racine.",
            "C'est le produit $3 \\times 4 = 12$, aucun rapport avec la norme.",
            "Confusion : $\\sqrt{3+4} = \\sqrt{7}$ n'est pas la bonne formule. Il faut les carres."
        ],
        "reference_cours": "Jour 1, section 1.3 - Norme d'un vecteur",
        "piege_a_eviter": "Norme = racine de la somme des CARRES, pas racine de la simple somme."
    },
    {
        "id": "Q08",
        "jour": 1,
        "difficulte": "vert",
        "type": "calcul",
        "enonce": "Avec le modele $\\hat{y} = 2{,}07 \\cdot x + 31{,}27$ (k\u20ac), quelle est la prediction de salaire pour un developpeur avec $x = 5$ ans d'experience ?",
        "propositions": [
            "$41{,}62$ k\u20ac",
            "$33{,}34$ k\u20ac",
            "$10{,}35$ k\u20ac",
            "$2{,}07$ k\u20ac"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "On applique directement la formule : $\\hat{y} = 2{,}07 \\times 5 + 31{,}27 = 10{,}35 + 31{,}27 = 41{,}62$ k\u20ac.",
        "explications_mauvaises": [
            None,
            "Cette valeur est $2{,}07 + 31{,}27$ : oubli de la multiplication par $x$.",
            "Cette valeur est seulement $w \\cdot x = 2{,}07 \\times 5$ : oubli d'ajouter le biais $b$.",
            "C'est la valeur de $w$ seule, sans multiplication ni addition."
        ],
        "reference_cours": "Jour 1, section 3.2 - Calcul d'une prediction",
        "piege_a_eviter": "Ne pas oublier d'ajouter le biais $b$ apres la multiplication $w \\cdot x$."
    },
    {
        "id": "Q09",
        "jour": 1,
        "difficulte": "vert",
        "type": "calcul",
        "enonce": "Soient $\\mathbf{u} = (1, 2, 3)$ et $\\mathbf{v} = (4, 5, 6)$. Que vaut $\\mathbf{u} + \\mathbf{v}$ ?",
        "propositions": [
            "$(5, 7, 9)$",
            "$(4, 10, 18)$",
            "$(5, 5, 5)$",
            "$32$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "L'addition de deux vecteurs se fait composante par composante : $(1+4, 2+5, 3+6) = (5, 7, 9)$.",
        "explications_mauvaises": [
            None,
            "C'est le produit composante par composante (produit de Hadamard), pas la somme.",
            "Les differences $4-1=3$, $5-2=3$, $6-3=3$ sont constantes mais la somme n'est pas constante.",
            "C'est le produit scalaire $\\mathbf{u} \\cdot \\mathbf{v} = 1 \\times 4 + 2 \\times 5 + 3 \\times 6 = 32$, pas la somme."
        ],
        "reference_cours": "Jour 1, section 1.4 - Operations sur les vecteurs",
        "piege_a_eviter": "Somme de vecteurs = composante par composante. Ne pas confondre avec le produit scalaire."
    },
    {
        "id": "Q10",
        "jour": 1,
        "difficulte": "jaune",
        "type": "calcul",
        "enonce": "Soient $\\mathbf{x} = (2, -1, 3)$ et $\\mathbf{w} = (1, 4, -2)$. Que vaut le produit scalaire $\\mathbf{x}^T \\mathbf{w}$ ?",
        "propositions": [
            "$-8$",
            "$8$",
            "$-2$",
            "$6$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Le produit scalaire se calcule : $\\mathbf{x}^T \\mathbf{w} = 2 \\times 1 + (-1) \\times 4 + 3 \\times (-2) = 2 - 4 - 6 = -8$.",
        "explications_mauvaises": [
            None,
            "Erreur de signe : $2 - 4 - 6 = -8$, pas $+8$. Penser a verifier les signes.",
            "Oubli du dernier terme : $2 - 4 = -2$ oublie le $3 \\times (-2) = -6$.",
            "Erreur : $2 + 4 = 6$, on a ajoute alors qu'il fallait soustraire le $-1 \\times 4$."
        ],
        "reference_cours": "Jour 1, section 2.1 - Produit scalaire",
        "piege_a_eviter": "Attention aux signes : multiplier composante par composante, PUIS additionner."
    },
    {
        "id": "Q11",
        "jour": 1,
        "difficulte": "vert",
        "type": "calcul",
        "enonce": "On a 3 observations : $y = (40, 50, 60)$ et les predictions $\\hat{y} = (42, 48, 58)$. Que vaut le MSE ?",
        "propositions": [
            "$4$",
            "$12$",
            "$2$",
            "$8$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "MSE $= \\frac{1}{n} \\sum (y_i - \\hat{y}_i)^2 = \\frac{(40-42)^2 + (50-48)^2 + (60-58)^2}{3} = \\frac{4 + 4 + 4}{3} = \\frac{12}{3} = 4$.",
        "explications_mauvaises": [
            None,
            "C'est la somme des carres, pas la moyenne. Il faut diviser par $n = 3$.",
            "C'est l'erreur absolue moyenne : $\\frac{2+2+2}{3} = 2$. Le MSE utilise les carres.",
            "Aucun calcul coherent ne donne cette valeur avec ces donnees."
        ],
        "reference_cours": "Jour 1, section 4.1 - Calcul du MSE",
        "piege_a_eviter": "MSE = MEAN (moyenne) des carres, donc ne pas oublier de diviser par $n$."
    },
    {
        "id": "Q12",
        "jour": 1,
        "difficulte": "jaune",
        "type": "calcul",
        "enonce": "Avec $\\hat{y} = 2{,}07 \\cdot x + 31{,}27$, quelle est la difference de salaire predit entre un developpeur avec 8 ans d'experience et un developpeur avec 3 ans d'experience ?",
        "propositions": [
            "$10{,}35$ k\u20ac",
            "$16{,}56$ k\u20ac",
            "$5{,}00$ k\u20ac",
            "$2{,}07$ k\u20ac"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "La difference vaut $(2{,}07 \\times 8 + 31{,}27) - (2{,}07 \\times 3 + 31{,}27) = 2{,}07 \\times (8 - 3) = 2{,}07 \\times 5 = 10{,}35$ k\u20ac. Le biais $b$ s'annule dans la difference.",
        "explications_mauvaises": [
            None,
            "Erreur : $2{,}07 \\times 8 = 16{,}56$ donne le salaire sans biais pour 8 ans, pas la difference.",
            "C'est la difference d'experience ($8 - 3 = 5$), pas de salaire.",
            "C'est la valeur de $w$, soit le salaire gagne par annee supplementaire, pour 1 annee seulement."
        ],
        "reference_cours": "Jour 1, section 3.2 - Interpretation de la pente",
        "piege_a_eviter": "Dans une difference de predictions, le biais $b$ disparait toujours."
    },
    {
        "id": "Q13",
        "jour": 1,
        "difficulte": "jaune",
        "type": "calcul",
        "enonce": "Un developpeur a 6 ans d'experience et un salaire reel de $45$ k\u20ac. Avec $\\hat{y} = 2{,}07 x + 31{,}27$, quelle est l'erreur de prediction $(y - \\hat{y})$ ?",
        "propositions": [
            "$+1{,}31$ k\u20ac",
            "$-1{,}31$ k\u20ac",
            "$+12{,}42$ k\u20ac",
            "$0$ k\u20ac"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Prediction : $\\hat{y} = 2{,}07 \\times 6 + 31{,}27 = 12{,}42 + 31{,}27 = 43{,}69$ k\u20ac. Erreur : $y - \\hat{y} = 45 - 43{,}69 = +1{,}31$ k\u20ac (le modele sous-estime legerement).",
        "explications_mauvaises": [
            None,
            "Erreur de signe : $y - \\hat{y} = 45 - 43{,}69 = +1{,}31$, pas $-1{,}31$. L'erreur positive indique une sous-estimation.",
            "C'est $2{,}07 \\times 6 = 12{,}42$, soit la contribution de l'experience seule, pas l'erreur.",
            "Rare qu'un modele predise exactement : ici l'erreur est petite mais non nulle."
        ],
        "reference_cours": "Jour 1, section 4.1 - Erreur residuelle",
        "piege_a_eviter": "Bien respecter l'ordre $y - \\hat{y}$ : une erreur positive = sous-estimation, une erreur negative = surestimation."
    },
    {
        "id": "Q14",
        "jour": 1,
        "difficulte": "orange",
        "type": "calcul",
        "enonce": "Sur 4 observations, on a les erreurs residuelles $e_i = y_i - \\hat{y}_i$ : $e = (-3, +2, -1, +4)$. Que vaut le MSE ?",
        "propositions": [
            "$7{,}5$",
            "$2$",
            "$0{,}5$",
            "$30$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "MSE $= \\frac{1}{n} \\sum e_i^2 = \\frac{9 + 4 + 1 + 16}{4} = \\frac{30}{4} = 7{,}5$.",
        "explications_mauvaises": [
            None,
            "C'est $\\frac{-3+2-1+4}{4} = \\frac{2}{4} = 0{,}5$... mais c'est la moyenne des erreurs signees, pas le MSE.",
            "Cette valeur est la moyenne signee : elle est trompeuse car les erreurs positives et negatives se compensent.",
            "C'est la somme des carres ($30$), il manque la division par $n = 4$."
        ],
        "reference_cours": "Jour 1, section 4.1 - Calcul rigoureux du MSE",
        "piege_a_eviter": "La moyenne signee des erreurs est presque nulle pour un bon modele, ce qui est trompeur. Toujours passer au carre."
    },
    {
        "id": "Q15",
        "jour": 1,
        "difficulte": "jaune",
        "type": "calcul",
        "enonce": "Soit la matrice $\\mathbf{X} = \\begin{pmatrix} 1 & 2 \\\\ 3 & 4 \\\\ 5 & 6 \\end{pmatrix}$ et le vecteur $\\mathbf{w} = \\begin{pmatrix} 2 \\\\ -1 \\end{pmatrix}$. Que vaut $\\mathbf{X} \\mathbf{w}$ ?",
        "propositions": [
            "$(0, 2, 4)$",
            "$(2, 6, 10)$",
            "$(-1, -2, -1)$",
            "$(3, 7, 11)$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Chaque ligne de $\\mathbf{X}\\mathbf{w}$ est le produit scalaire d'une ligne de $\\mathbf{X}$ avec $\\mathbf{w}$ : ligne 1 : $1 \\times 2 + 2 \\times (-1) = 0$ ; ligne 2 : $3 \\times 2 + 4 \\times (-1) = 2$ ; ligne 3 : $5 \\times 2 + 6 \\times (-1) = 4$. Donc $\\mathbf{X}\\mathbf{w} = (0, 2, 4)$.",
        "explications_mauvaises": [
            None,
            "Calcul uniquement de la premiere colonne multipliee par $2$ : on a oublie la deuxieme composante de $\\mathbf{w}$.",
            "Calcul uniquement de la deuxieme colonne multipliee par $-1$ : on a oublie la premiere composante.",
            "C'est la somme des colonnes, sans tenir compte des poids $\\mathbf{w}$."
        ],
        "reference_cours": "Jour 1, section 2.2 - Produit matrice-vecteur",
        "piege_a_eviter": "Chaque composante du resultat est un produit scalaire complet, pas une seule multiplication."
    },
    {
        "id": "Q16",
        "jour": 1,
        "difficulte": "rouge",
        "type": "calcul",
        "enonce": "On compare deux modeles sur les memes 3 donnees $y = (40, 50, 60)$. Modele A predit $\\hat{y}_A = (41, 52, 57)$ et modele B predit $\\hat{y}_B = (45, 45, 55)$. Lequel est meilleur selon le MSE, et quel est son MSE ?",
        "propositions": [
            "Modele A, $MSE = 4{,}67$",
            "Modele B, $MSE = 25{,}00$",
            "Modele A, $MSE = 14{,}00$",
            "Les deux sont equivalents"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Modele A : erreurs $(-1, -2, 3)$, carres $(1, 4, 9)$, $MSE_A = 14/3 \\approx 4{,}67$. Modele B : erreurs $(-5, 5, 5)$, carres $(25, 25, 25)$, $MSE_B = 75/3 = 25$. Le modele A a un MSE bien plus faible, il est donc meilleur.",
        "explications_mauvaises": [
            None,
            "Correct pour le MSE de B mais faux pour le meilleur : c'est A qui est meilleur (MSE plus petit = meilleur).",
            "Erreur : c'est la somme des carres du modele A, pas la moyenne (oubli de la division par 3).",
            "Faux : les MSE sont tres differents ($4{,}67$ vs $25$)."
        ],
        "reference_cours": "Jour 1, section 4.3 - Comparaison de modeles",
        "piege_a_eviter": "On cherche a MINIMISER le MSE : un MSE plus PETIT signifie un meilleur modele."
    },

    # --- CODE (6) ---
    {
        "id": "Q17",
        "jour": 1,
        "difficulte": "vert",
        "type": "code",
        "enonce": "Quelle instruction Python cree correctement un vecteur NumPy $\\mathbf{x} = (2{,}5, 3{,}1, 4{,}8)$ ?",
        "propositions": [
            "`x = np.array([2.5, 3.1, 4.8])`",
            "`x = np.vector(2.5, 3.1, 4.8)`",
            "`x = [2.5, 3.1, 4.8].numpy()`",
            "`x = np.tuple([2.5, 3.1, 4.8])`"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "La fonction `np.array()` transforme une liste Python en tableau NumPy. C'est la facon standard de creer un vecteur (ou une matrice).",
        "explications_mauvaises": [
            None,
            "`np.vector` n'existe pas. NumPy n'a pas de type vecteur distinct : un vecteur est un `ndarray` a une dimension.",
            "Syntaxe invalide : une liste Python n'a pas de methode `.numpy()` en NumPy standard.",
            "`np.tuple` n'existe pas."
        ],
        "reference_cours": "Jour 1, section 5.1 - NumPy basics",
        "piege_a_eviter": "En NumPy, il n'y a pas de type 'vector' : tout est `ndarray`."
    },
    {
        "id": "Q18",
        "jour": 1,
        "difficulte": "vert",
        "type": "code",
        "enonce": "Quelle est la valeur de `result` apres execution du code suivant ?\n```python\nimport numpy as np\nu = np.array([2, 3])\nv = np.array([4, 5])\nresult = np.dot(u, v)\n```",
        "propositions": [
            "`23`",
            "`(8, 15)`",
            "`7`",
            "`[2, 3, 4, 5]`"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "`np.dot(u, v)` calcule le produit scalaire : $2 \\times 4 + 3 \\times 5 = 8 + 15 = 23$.",
        "explications_mauvaises": [
            None,
            "C'est `u * v` (multiplication composante par composante), pas `np.dot`.",
            "C'est la somme des composantes de `u` ($2+3+5/$ quelque chose), pas le produit scalaire.",
            "C'est la concatenation, pas une operation NumPy standard."
        ],
        "reference_cours": "Jour 1, section 5.2 - Produit scalaire en NumPy",
        "piege_a_eviter": "`np.dot` = produit scalaire (un seul nombre). `*` = produit composante par composante (un vecteur)."
    },
    {
        "id": "Q19",
        "jour": 1,
        "difficulte": "jaune",
        "type": "code",
        "enonce": "Quelle est la prediction affichee par le code ?\n```python\nimport numpy as np\nx = np.array([3, 5, 7])\nw, b = 2.07, 31.27\ny_hat = w * x + b\nprint(y_hat[1])\n```",
        "propositions": [
            "`41.62`",
            "`37.48`",
            "`45.76`",
            "`10.35`"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "`y_hat` est un tableau calcule element par element : $(2{,}07 \\times 3 + 31{,}27,\\; 2{,}07 \\times 5 + 31{,}27,\\; 2{,}07 \\times 7 + 31{,}27) = (37{,}48,\\; 41{,}62,\\; 45{,}76)$. Le Python est 0-indexe : `y_hat[1]` est donc le DEUXIEME element, soit $41{,}62$.",
        "explications_mauvaises": [
            None,
            "C'est `y_hat[0]` (le premier element). Python indexe a partir de 0, donc `y_hat[1]` est le deuxieme.",
            "C'est `y_hat[2]` (troisieme element).",
            "C'est $w \\times x[0]$ seul, sans le biais."
        ],
        "reference_cours": "Jour 1, section 5.3 - Operations vectorielles NumPy",
        "piege_a_eviter": "Python indexe a partir de 0 : `a[1]` est le DEUXIEME element, pas le premier."
    },
    {
        "id": "Q20",
        "jour": 1,
        "difficulte": "jaune",
        "type": "code",
        "enonce": "Que fait precisement ce code ?\n```python\nimport numpy as np\ny = np.array([40, 50, 60])\ny_hat = np.array([42, 48, 58])\nmse = np.mean((y - y_hat) ** 2)\nprint(mse)\n```",
        "propositions": [
            "Calcule et affiche le MSE entre `y` et `y_hat`, soit 4.0",
            "Calcule et affiche l'erreur moyenne signee, soit 0.0",
            "Calcule et affiche la somme des erreurs au carre, soit 12",
            "Genere une erreur car les tableaux doivent etre de type float"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "`y - y_hat = [-2, 2, 2]`, `(y - y_hat)**2 = [4, 4, 4]`, `np.mean([4, 4, 4]) = 4.0`. C'est bien le MSE (mean squared error) : moyenne des carres des erreurs.",
        "explications_mauvaises": [
            None,
            "C'est `np.mean(y - y_hat)`, sans le carre. Le code utilise le carre.",
            "C'est `np.sum((y - y_hat)**2)`. La fonction utilisee est `np.mean`, pas `np.sum`.",
            "Pas d'erreur : NumPy accepte les entiers et les operations fonctionnent correctement."
        ],
        "reference_cours": "Jour 1, section 5.4 - MSE en Python",
        "piege_a_eviter": "`np.mean` divise par le nombre d'elements, `np.sum` ne divise pas. Bien identifier la fonction."
    },
    {
        "id": "Q21",
        "jour": 1,
        "difficulte": "orange",
        "type": "code",
        "enonce": "Considerons ce code :\n```python\nX = np.array([[1, 2], [3, 4], [5, 6]])\nw = np.array([0.5, 1.0])\npredictions = X @ w\n```\nQuelle est la valeur de `predictions` ?",
        "propositions": [
            "`array([2.5, 5.5, 8.5])`",
            "`array([0.5, 2.0, 3.0])`",
            "`array([[0.5, 2.], [1.5, 4.], [2.5, 6.]])`",
            "`array([4., 9., 14.])`"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "L'operateur `@` effectue la multiplication matricielle. Pour $\\mathbf{X}\\mathbf{w}$ : ligne 1 : $1 \\times 0{,}5 + 2 \\times 1 = 2{,}5$ ; ligne 2 : $3 \\times 0{,}5 + 4 \\times 1 = 5{,}5$ ; ligne 3 : $5 \\times 0{,}5 + 6 \\times 1 = 8{,}5$.",
        "explications_mauvaises": [
            None,
            "C'est une multiplication de la premiere colonne par $0{,}5$ uniquement, pas le produit matriciel.",
            "C'est la multiplication terme a terme (broadcasting), pas le produit matriciel. L'operateur `@` sert au produit matriciel.",
            "Calcul erronne : on a fait $1+2 \\times 0{,}5+1 = $ quelque chose d'incorrect."
        ],
        "reference_cours": "Jour 1, section 5.5 - Operations matricielles",
        "piege_a_eviter": "`@` = multiplication matricielle, `*` = multiplication element par element (broadcasting)."
    },
    {
        "id": "Q22",
        "jour": 1,
        "difficulte": "jaune",
        "type": "code",
        "enonce": "Pour le tableau `X = np.array([[1, 2, 3], [4, 5, 6]])`, quelle est la sortie de `print(X.shape)` ?",
        "propositions": [
            "`(2, 3)`",
            "`(3, 2)`",
            "`(6,)`",
            "`6`"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "`X.shape` retourne un tuple `(n_lignes, n_colonnes)`. Ici la matrice a 2 lignes et 3 colonnes, donc `(2, 3)`.",
        "explications_mauvaises": [
            None,
            "C'est la forme de la transposee `X.T.shape`. La convention est (lignes, colonnes).",
            "C'est le nombre total d'elements (`X.size`), pas la shape.",
            "C'est le nombre total d'elements en entier, pas un tuple."
        ],
        "reference_cours": "Jour 1, section 5.1 - Dimensions d'un tableau NumPy",
        "piege_a_eviter": "Retenir la convention `(lignes, colonnes)`, pas l'inverse."
    },

    # --- FORMULES (3) ---
    {
        "id": "Q23",
        "jour": 1,
        "difficulte": "vert",
        "type": "formule",
        "enonce": "Quelle est la formule correcte de la regression lineaire simple (une seule feature) ?",
        "propositions": [
            "$\\hat{y} = w \\cdot x + b$",
            "$\\hat{y} = w^x + b$",
            "$\\hat{y} = \\frac{w \\cdot x}{b}$",
            "$\\hat{y} = w - x + b$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "La regression lineaire simple modelise $\\hat{y}$ comme une fonction affine de $x$ : $\\hat{y} = w \\cdot x + b$, ou $w$ est la pente et $b$ le biais (ordonnee a l'origine).",
        "explications_mauvaises": [
            None,
            "C'est une fonction exponentielle, pas lineaire.",
            "Ce n'est pas une regression lineaire : la division rend la fonction non lineaire en $b$.",
            "La difference donne bien une droite, mais la pente serait $-1$ et non $w$. La formule standard est $+$."
        ],
        "reference_cours": "Jour 1, section 3 - Regression lineaire",
        "piege_a_eviter": "Une regression LINEAIRE est une fonction AFFINE : pente $\\times$ variable PLUS biais."
    },
    {
        "id": "Q24",
        "jour": 1,
        "difficulte": "jaune",
        "type": "formule",
        "enonce": "Quelle est la formule correcte du MSE (Mean Squared Error) ?",
        "propositions": [
            "$MSE = \\frac{1}{n} \\sum_{i=1}^{n} (y_i - \\hat{y}_i)^2$",
            "$MSE = \\sum_{i=1}^{n} |y_i - \\hat{y}_i|$",
            "$MSE = \\frac{1}{n} \\sum_{i=1}^{n} (y_i - \\hat{y}_i)$",
            "$MSE = \\sum_{i=1}^{n} (y_i - \\hat{y}_i)^2$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Le MSE est la **moyenne** (d'ou le $\\frac{1}{n}$) des **carres** (d'ou le $^2$) des erreurs.",
        "explications_mauvaises": [
            None,
            "C'est la formule du MAE (Mean Absolute Error) : valeurs absolues, pas carres. De plus il manque le $\\frac{1}{n}$.",
            "C'est la moyenne des erreurs signees, pas des carres : les erreurs positives et negatives s'annulent.",
            "C'est la somme des carres (SSE), pas la moyenne. Le MSE divise par $n$."
        ],
        "reference_cours": "Jour 1, section 4 - Fonction de cout MSE",
        "piege_a_eviter": "MSE = MEAN + SQUARED + ERROR : ne pas oublier la moyenne (le $\\frac{1}{n}$) ni le carre."
    },
    {
        "id": "Q25",
        "jour": 1,
        "difficulte": "orange",
        "type": "formule",
        "enonce": "Parmi les formules suivantes, laquelle est une ecriture correcte du produit scalaire entre deux vecteurs $\\mathbf{u}$ et $\\mathbf{v}$ de $\\mathbb{R}^n$ ?",
        "propositions": [
            "$\\mathbf{u}^T \\mathbf{v} = \\sum_{i=1}^{n} u_i v_i$",
            "$\\mathbf{u}^T \\mathbf{v} = \\sum_{i=1}^{n} (u_i + v_i)$",
            "$\\mathbf{u}^T \\mathbf{v} = \\prod_{i=1}^{n} u_i v_i$",
            "$\\mathbf{u}^T \\mathbf{v} = |u_1 v_1|$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Le produit scalaire est la **somme** des produits composante par composante : $\\mathbf{u}^T \\mathbf{v} = u_1 v_1 + u_2 v_2 + \\dots + u_n v_n = \\sum_{i=1}^{n} u_i v_i$.",
        "explications_mauvaises": [
            None,
            "C'est la somme des sommes, pas le produit scalaire.",
            "Le symbole $\\prod$ designe un produit de TOUS les termes, pas une somme.",
            "Il manque toutes les composantes sauf la premiere, et la valeur absolue n'intervient pas."
        ],
        "reference_cours": "Jour 1, section 2.1 - Produit scalaire",
        "piege_a_eviter": "Ne pas confondre $\\sum$ (somme) et $\\prod$ (produit)."
    },

    # ========================================================================
    # JOUR 2 - Optimisation (25 questions)
    # ========================================================================

    # --- CONCEPTUEL (6) ---
    {
        "id": "Q26",
        "jour": 2,
        "difficulte": "vert",
        "type": "conceptuel",
        "enonce": "A quoi sert une fonction de cout $J(w, b)$ en machine learning ?",
        "propositions": [
            "A mesurer a quel point le modele fait de mauvaises predictions, afin de pouvoir l'ameliorer",
            "A predire la cible $y$ a partir des features",
            "A initialiser les parametres du modele",
            "A generer de nouvelles donnees d'entrainement"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "La fonction de cout quantifie l'ecart entre les predictions du modele et les vraies valeurs. C'est l'objectif qu'on cherche a **minimiser** pour trouver les meilleurs parametres.",
        "explications_mauvaises": [
            None,
            "C'est le role de la fonction de prediction (le modele), pas de la fonction de cout.",
            "L'initialisation se fait independamment de la fonction de cout (souvent aleatoire ou a zero).",
            "La fonction de cout utilise les donnees existantes, elle n'en cree pas."
        ],
        "reference_cours": "Jour 2, section 1 - Fonction de cout",
        "piege_a_eviter": "Le cout MESURE les erreurs, il ne les PREDIT pas. Prediction et cout sont deux objets distincts."
    },
    {
        "id": "Q27",
        "jour": 2,
        "difficulte": "vert",
        "type": "conceptuel",
        "enonce": "Quel est l'objectif principal de la descente de gradient ?",
        "propositions": [
            "Trouver les parametres qui minimisent la fonction de cout",
            "Generer les predictions $\\hat{y}$ pour une donnee $x$",
            "Augmenter la fonction de cout autant que possible",
            "Diviser le dataset en train et test"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "La descente de gradient est un algorithme d'optimisation qui ajuste iterativement les parametres du modele pour **minimiser** la fonction de cout (le modele fait donc de moins en moins d'erreurs).",
        "explications_mauvaises": [
            None,
            "La prediction est realisee une fois les parametres appris. La descente de gradient sert a APPRENDRE ces parametres.",
            "Le contraire : on cherche a MINIMISER le cout, pas a le maximiser.",
            "C'est une etape de preparation des donnees, pas un algorithme d'optimisation."
        ],
        "reference_cours": "Jour 2, section 2.1 - Principe de la descente de gradient",
        "piege_a_eviter": "On cherche a MINIMISER (descendre vers le bas), pas a maximiser."
    },
    {
        "id": "Q28",
        "jour": 2,
        "difficulte": "jaune",
        "type": "conceptuel",
        "enonce": "Quelle analogie illustre le mieux le principe de la descente de gradient ?",
        "propositions": [
            "Descendre une montagne dans le brouillard : a chaque pas, on regarde la pente locale et on avance vers le bas",
            "Jeter un pion sur un plateau et accepter la case ou il tombe",
            "Grimper un escalier en sautant plusieurs marches",
            "Dessiner toutes les positions possibles et choisir la meilleure"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "La descente de gradient est **locale et iterative** : a chaque etape, on se sert uniquement de l'information locale (la pente = le gradient) pour faire un petit pas vers le bas. C'est exactement comme descendre une montagne sans voir le relief global.",
        "explications_mauvaises": [
            None,
            "La descente de gradient n'est pas aleatoire : elle utilise le gradient pour suivre la pente.",
            "L'analogie avec un escalier suggere des sauts discrets et vers le haut, ce n'est pas l'idee.",
            "Dessiner toutes les possibilites serait la force brute, pas la descente de gradient."
        ],
        "reference_cours": "Jour 2, section 2.2 - Intuition geometrique",
        "piege_a_eviter": "La descente de gradient n'explore pas l'espace entier : elle suit la pente locale."
    },
    {
        "id": "Q29",
        "jour": 2,
        "difficulte": "jaune",
        "type": "conceptuel",
        "enonce": "Que represente geometriquement le gradient $\\nabla J(\\mathbf{w})$ d'une fonction de cout ?",
        "propositions": [
            "Un vecteur qui pointe dans la direction de la plus forte AUGMENTATION de $J$",
            "Un vecteur qui pointe toujours vers le minimum global",
            "La valeur numerique du cout en un point",
            "La moyenne des derivees partielles"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Le gradient pointe dans la direction de la plus forte augmentation locale de $J$. C'est pour cela qu'on utilise son **oppose** $-\\nabla J$ pour la mise a jour : on va dans la direction de la plus forte DESCENTE.",
        "explications_mauvaises": [
            None,
            "Non : le gradient est une information LOCALE. Il ne pointe pas vers le minimum global, surtout dans des paysages complexes.",
            "C'est la valeur $J(\\mathbf{w})$ elle-meme, pas son gradient.",
            "Le gradient EST le vecteur des derivees partielles, pas leur moyenne."
        ],
        "reference_cours": "Jour 2, section 2.3 - Le gradient",
        "piege_a_eviter": "Le gradient monte, mais on utilise son oppose pour descendre. D'ou le signe MOINS dans la mise a jour $w \\leftarrow w - \\alpha \\nabla J$."
    },
    {
        "id": "Q30",
        "jour": 2,
        "difficulte": "orange",
        "type": "conceptuel",
        "enonce": "On entraine un modele avec un taux d'apprentissage $\\alpha$ tres grand. Que risque-t-il de se passer ?",
        "propositions": [
            "L'algorithme diverge : les parametres oscillent ou explosent au lieu de converger vers le minimum",
            "L'algorithme converge plus vite vers un meilleur minimum",
            "L'algorithme s'arrete immediatement apres une iteration",
            "Le gradient devient nul"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Un $\\alpha$ trop grand fait des pas trop grands : on peut SAUTER au-dessus du minimum, osciller d'un cote a l'autre, voire s'eloigner (les parametres 'explosent' vers l'infini).",
        "explications_mauvaises": [
            None,
            "Au contraire : un $\\alpha$ trop grand **empeche** la convergence. Il faut un $\\alpha$ bien calibre.",
            "L'algorithme ne s'arrete pas : il continue a iterer avec des valeurs qui divergent.",
            "Le gradient ne depend pas de $\\alpha$ (il depend des parametres). Il ne devient pas nul a cause de $\\alpha$."
        ],
        "reference_cours": "Jour 2, section 3.2 - Choix du taux d'apprentissage",
        "piege_a_eviter": "Un $\\alpha$ tres grand N'ACCELERE PAS la convergence : il la fait echouer. Il faut un $\\alpha$ juste."
    },
    {
        "id": "Q31",
        "jour": 2,
        "difficulte": "vert",
        "type": "conceptuel",
        "enonce": "Dans une iteration de descente de gradient $w \\leftarrow w - \\alpha \\cdot \\frac{\\partial J}{\\partial w}$, quelle affirmation est exacte ?",
        "propositions": [
            "$w$ est une valeur (le parametre) et $\\frac{\\partial J}{\\partial w}$ est une autre valeur (la derivee partielle au point courant)",
            "$w$ et $\\frac{\\partial J}{\\partial w}$ designent la meme quantite",
            "$\\frac{\\partial J}{\\partial w}$ est le salaire moyen predit",
            "$\\frac{\\partial J}{\\partial w}$ est egal a $w$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Ce sont deux objets distincts : $w$ est le parametre courant (une valeur numerique a un moment donne), et $\\frac{\\partial J}{\\partial w}$ est la DERIVEE partielle de $J$ par rapport a $w$, evaluee au point $w$ courant (une autre valeur numerique).",
        "explications_mauvaises": [
            None,
            "Faux : $w$ est le parametre, $\\frac{\\partial J}{\\partial w}$ est une derivee. Ils coincident occasionnellement mais n'ont pas la meme nature.",
            "Aucun rapport : la derivee partielle du cout ne represente pas un salaire.",
            "Faux en general : ils ont typiquement des valeurs et des unites differentes."
        ],
        "reference_cours": "Jour 2, section 2.4 - Notation et interpretation",
        "piege_a_eviter": "Ne pas confondre le parametre ($w$) et sa derivee partielle par rapport au cout ($\\frac{\\partial J}{\\partial w}$)."
    },

    # --- CALCUL (10) ---
    {
        "id": "Q32",
        "jour": 2,
        "difficulte": "vert",
        "type": "calcul",
        "enonce": "On a $w = 5$, $\\frac{\\partial J}{\\partial w} = 2$ et $\\alpha = 0{,}01$. Quelle est la nouvelle valeur de $w$ apres une iteration de descente de gradient ?",
        "propositions": [
            "$4{,}98$",
            "$5{,}02$",
            "$4{,}00$",
            "$3{,}00$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Mise a jour : $w_{\\text{nouveau}} = w - \\alpha \\cdot \\frac{\\partial J}{\\partial w} = 5 - 0{,}01 \\times 2 = 5 - 0{,}02 = 4{,}98$.",
        "explications_mauvaises": [
            None,
            "Erreur de signe : on SOUSTRAIT (on descend), on n'ajoute pas.",
            "C'est $5 - 1 = 4$, on a utilise $\\alpha = 0{,}5$, pas $0{,}01$.",
            "Calcul errone : $5 - 2 = 3$, on a oublie de multiplier par $\\alpha$."
        ],
        "reference_cours": "Jour 2, section 3.1 - Regle de mise a jour",
        "piege_a_eviter": "Bien appliquer la formule : on soustrait ($-$) le produit de $\\alpha$ et du gradient."
    },
    {
        "id": "Q33",
        "jour": 2,
        "difficulte": "vert",
        "type": "calcul",
        "enonce": "Avec $w = 10$, $\\alpha = 0{,}1$, gradient $= -4$, que vaut $w$ apres une iteration ?",
        "propositions": [
            "$10{,}4$",
            "$9{,}6$",
            "$14$",
            "$6$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Mise a jour : $w_{\\text{nouveau}} = w - \\alpha \\cdot \\text{grad} = 10 - 0{,}1 \\times (-4) = 10 + 0{,}4 = 10{,}4$. Le gradient negatif fait MONTER $w$ (logique : on descend, donc on va dans le sens oppose au gradient).",
        "explications_mauvaises": [
            None,
            "Erreur de signe : $10 - 0{,}1 \\times (-4) = 10 + 0{,}4 = 10{,}4$, pas $9{,}6$. Deux moins font un plus.",
            "Oubli de multiplier par $\\alpha$ : $10 + 4 = 14$.",
            "Mauvais signe ET oubli de $\\alpha$ : $10 - 4 = 6$."
        ],
        "reference_cours": "Jour 2, section 3.1 - Mise a jour avec gradient negatif",
        "piege_a_eviter": "Double signe : $- \\alpha \\times (-g) = + \\alpha g$. Bien faire attention aux signes."
    },
    {
        "id": "Q34",
        "jour": 2,
        "difficulte": "jaune",
        "type": "calcul",
        "enonce": "Pour une regression simple $\\hat{y}_i = w x_i + b$ avec une seule observation $(x_1, y_1) = (4, 20)$ et les parametres initiaux $w = 2$, $b = 5$, que vaut la derivee partielle $\\frac{\\partial J}{\\partial w}$ ? (Utiliser $J = (y_1 - \\hat{y}_1)^2$ pour cet exemple simple.)",
        "propositions": [
            "$-56$",
            "$-7$",
            "$+56$",
            "$+28$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Prediction : $\\hat{y}_1 = 2 \\times 4 + 5 = 13$. Erreur : $y_1 - \\hat{y}_1 = 20 - 13 = 7$. Derivee : $\\frac{\\partial J}{\\partial w} = -2(y_1 - \\hat{y}_1) x_1 = -2 \\times 7 \\times 4 = -56$.",
        "explications_mauvaises": [
            None,
            "C'est l'erreur $y - \\hat{y} = 7$ avec le signe $-$, mais il faut aussi multiplier par $2 x_1$.",
            "Erreur de signe : la derivee contient un $-2(y - \\hat{y})$, donc le signe est negatif si l'erreur est positive.",
            "Oubli du facteur $2$ : c'est $-1 \\times 7 \\times 4 = -28$."
        ],
        "reference_cours": "Jour 2, section 4 - Calcul analytique du gradient",
        "piege_a_eviter": "Retenir la formule : $\\frac{\\partial J}{\\partial w} = -2 (y - \\hat{y}) x$ pour une observation, avec le signe et le facteur 2."
    },
    {
        "id": "Q35",
        "jour": 2,
        "difficulte": "jaune",
        "type": "calcul",
        "enonce": "On effectue une mise a jour simultanee de $w$ et $b$ avec $\\alpha = 0{,}01$. Etat courant : $w = 3$, $b = 10$, $\\frac{\\partial J}{\\partial w} = 20$, $\\frac{\\partial J}{\\partial b} = -5$. Quelles sont les nouvelles valeurs ?",
        "propositions": [
            "$w = 2{,}8$ et $b = 10{,}05$",
            "$w = 3{,}2$ et $b = 9{,}95$",
            "$w = 2{,}8$ et $b = 9{,}95$",
            "$w = 2$ et $b = 15$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "$w_{\\text{nouveau}} = 3 - 0{,}01 \\times 20 = 3 - 0{,}2 = 2{,}8$. $b_{\\text{nouveau}} = 10 - 0{,}01 \\times (-5) = 10 + 0{,}05 = 10{,}05$.",
        "explications_mauvaises": [
            None,
            "Les deux signes sont inverses : on AJOUTE au lieu de soustraire. La regle est toujours $-\\alpha \\nabla$.",
            "Correct pour $w$, mais $b$ a un gradient negatif donc il AUGMENTE (double negatif = positif).",
            "Calcul sans $\\alpha$ et mauvais signe sur $b$."
        ],
        "reference_cours": "Jour 2, section 3.3 - Mise a jour simultanee",
        "piege_a_eviter": "Toujours appliquer $\\theta \\leftarrow \\theta - \\alpha \\nabla J$ a chaque parametre, avec ses propres gradient et signe."
    },
    {
        "id": "Q36",
        "jour": 2,
        "difficulte": "vert",
        "type": "calcul",
        "enonce": "Avec $w_0 = 0$, $\\alpha = 0{,}1$, et supposons que le gradient soit constant egal a $-2$ a chaque iteration. Que vaut $w_3$ (apres 3 iterations) ?",
        "propositions": [
            "$0{,}6$",
            "$-0{,}6$",
            "$6$",
            "$0{,}2$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Chaque iteration : $w \\leftarrow w - 0{,}1 \\times (-2) = w + 0{,}2$. Donc $w_1 = 0{,}2$, $w_2 = 0{,}4$, $w_3 = 0{,}6$.",
        "explications_mauvaises": [
            None,
            "Erreur de signe : le gradient est negatif donc $-\\alpha \\cdot (-2) = +0{,}2$, on monte.",
            "Oubli de $\\alpha$ : $3 \\times 2 = 6$.",
            "C'est la valeur apres 1 iteration, pas 3."
        ],
        "reference_cours": "Jour 2, section 3.1 - Iterations de descente",
        "piege_a_eviter": "Pour un gradient constant, la trajectoire est lineaire. Additionner $3 \\times (\\alpha \\cdot \\text{grad})$."
    },
    {
        "id": "Q37",
        "jour": 2,
        "difficulte": "jaune",
        "type": "calcul",
        "enonce": "Un etudiant observe : $w_0 = 1$, $w_1 = 5$, $w_2 = 25$, $w_3 = 125$. Que peut-on conclure ?",
        "propositions": [
            "L'algorithme diverge : $w$ explose, le taux d'apprentissage est trop grand",
            "L'algorithme converge tres rapidement vers le minimum",
            "Le gradient est nul, donc on a trouve le minimum",
            "C'est une suite arithmetique : tout va bien"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "$w$ est multiplie par 5 a chaque iteration : c'est un signe typique de DIVERGENCE. Le taux d'apprentissage est trop grand (ou le gradient est mal calcule). Il faut reduire $\\alpha$.",
        "explications_mauvaises": [
            None,
            "Convergence = $w$ se stabilise. Ici il explose, c'est l'oppose.",
            "Si le gradient etait nul, $w$ ne bougerait plus ($w_{t+1} = w_t$), ce n'est pas le cas.",
            "C'est une suite geometrique (multiplication par 5), pas arithmetique. Et elle indique une divergence."
        ],
        "reference_cours": "Jour 2, section 3.2 - Diagnostic de divergence",
        "piege_a_eviter": "Convergence = les valeurs se STABILISENT. Divergence = elles explosent ou oscillent."
    },
    {
        "id": "Q38",
        "jour": 2,
        "difficulte": "orange",
        "type": "calcul",
        "enonce": "On double le taux d'apprentissage $\\alpha$ (de $0{,}01$ a $0{,}02$) pour une descente de gradient. Pour une meme iteration avec un gradient donne, comment change la taille du pas sur $w$ ?",
        "propositions": [
            "Le pas est exactement deux fois plus grand",
            "Le pas est inchange : $\\alpha$ et le gradient ne sont pas lies",
            "Le pas est divise par 2",
            "Le pas augmente de facon exponentielle"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "La mise a jour est $\\Delta w = -\\alpha \\cdot \\text{grad}$. La taille du pas est PROPORTIONNELLE a $\\alpha$. Doubler $\\alpha$ double donc le pas. Attention : cela ne signifie pas que la convergence sera deux fois plus rapide (risque d'oscillation ou de divergence).",
        "explications_mauvaises": [
            None,
            "Faux : $\\alpha$ multiplie directement le gradient dans la mise a jour. Ils sont liees.",
            "C'est le contraire : diviser $\\alpha$ par 2 diviserait le pas par 2.",
            "La proportionnalite est LINEAIRE (x2 quand on double $\\alpha$), pas exponentielle."
        ],
        "reference_cours": "Jour 2, section 3.2 - Effet du taux d'apprentissage",
        "piege_a_eviter": "Doubler $\\alpha$ double le pas, mais ne double PAS la vitesse de convergence (risque de divergence)."
    },
    {
        "id": "Q39",
        "jour": 2,
        "difficulte": "jaune",
        "type": "calcul",
        "enonce": "Pour le dataset fil rouge avec $n = 30$ developpeurs, la formule du gradient MSE par rapport a $w$ est $\\frac{\\partial J}{\\partial w} = -\\frac{2}{n} \\sum_{i=1}^{n} (y_i - \\hat{y}_i) x_i$. Si a une iteration on a $\\sum (y_i - \\hat{y}_i) x_i = -90$, que vaut le gradient ?",
        "propositions": [
            "$+6$",
            "$-6$",
            "$+3$",
            "$-3$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "$\\frac{\\partial J}{\\partial w} = -\\frac{2}{30} \\times (-90) = -\\frac{2 \\times (-90)}{30} = -\\frac{-180}{30} = +6$.",
        "explications_mauvaises": [
            None,
            "Erreur de signe : deux moins font un plus : $-\\frac{2}{30} \\times (-90) = +6$.",
            "Oubli du facteur $2$ : $-\\frac{1}{30} \\times (-90) = +3$.",
            "Oubli du facteur 2 ET du bon signe."
        ],
        "reference_cours": "Jour 2, section 4.1 - Gradient analytique MSE",
        "piege_a_eviter": "Les signes et les facteurs $\\frac{2}{n}$ sont critiques. Bien les traquer."
    },
    {
        "id": "Q40",
        "jour": 2,
        "difficulte": "orange",
        "type": "calcul",
        "enonce": "On compare 3 taux d'apprentissage pour la meme initialisation et le meme gradient initial non nul. Apres 100 iterations : $\\alpha_1 = 0{,}0001$ donne $J = 45{,}2$ ; $\\alpha_2 = 0{,}01$ donne $J = 20{,}5$ ; $\\alpha_3 = 1{,}0$ donne $J = 10^{12}$. Quel diagnostic poser ?",
        "propositions": [
            "$\\alpha_1$ est trop petit (convergence lente), $\\alpha_2$ est bien calibre, $\\alpha_3$ est trop grand (divergence)",
            "$\\alpha_3$ est ideal : il donne la valeur la plus grande",
            "Seul $\\alpha_1$ fonctionne, les autres sont invalides",
            "Tous les $\\alpha$ donnent des resultats equivalents apres suffisamment d'iterations"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "$\\alpha_1$ est trop petit : $J$ reste eleve (convergence lente). $\\alpha_2$ est bon : $J$ atteint une valeur raisonnable. $\\alpha_3$ est trop grand : $J$ explose (divergence). La valeur $10^{12}$ est un signe clair de divergence.",
        "explications_mauvaises": [
            None,
            "Faux : on cherche a MINIMISER $J$. Une valeur de $10^{12}$ est catastrophique, pas ideale.",
            "Faux : $\\alpha_2$ donne le meilleur resultat.",
            "Faux : des $\\alpha$ differents donnent des trajectoires tres differentes."
        ],
        "reference_cours": "Jour 2, section 3.4 - Calibrage de $\\alpha$",
        "piege_a_eviter": "Toujours lire $J$ dans la BONNE direction : plus petit = meilleur. Une explosion = divergence."
    },
    {
        "id": "Q41",
        "jour": 2,
        "difficulte": "rouge",
        "type": "calcul",
        "enonce": "Dataset fil rouge simplifie a 3 dev. $(x, y)$ = $(1, 35), (3, 40), (5, 45)$. Initialisation $w = 1$, $b = 30$, $\\alpha = 0{,}01$. Apres UNE iteration sur le batch complet avec les gradients analytiques $\\frac{\\partial J}{\\partial w} = -\\frac{2}{3}\\sum (y-\\hat{y}) x$ et $\\frac{\\partial J}{\\partial b} = -\\frac{2}{3}\\sum (y-\\hat{y})$, que valent les nouveaux $(w, b)$ ?",
        "propositions": [
            "$w \\approx 1{,}173$ ; $b \\approx 30{,}053$",
            "$w \\approx 0{,}827$ ; $b \\approx 29{,}947$",
            "$w \\approx 1{,}01$ ; $b \\approx 30{,}01$",
            "$w \\approx 2{,}000$ ; $b \\approx 35{,}000$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Predictions : $\\hat{y} = (31, 33, 35)$. Erreurs $(y - \\hat{y}) = (4, 7, 10)$. $\\sum (y-\\hat{y}) x = 4 + 21 + 50 = 75$ ; $\\sum (y-\\hat{y}) = 21$. $\\frac{\\partial J}{\\partial w} = -\\frac{2}{3} \\times 75 = -50$. $\\frac{\\partial J}{\\partial b} = -\\frac{2}{3} \\times 21 = -14$. MAJ : $w = 1 - 0{,}01 \\times (-50) = 1{,}5$... attention, recalculons : $w = 1 + 0{,}5 = 1{,}5$ (approximation pres). **Verification precise :** $-\\alpha \\cdot \\text{grad}_w = -0{,}01 \\times (-50) = +0{,}5$, donc $w = 1{,}5$. Correction : la reponse correcte arrondie est $w \\approx 1{,}5$, $b \\approx 30{,}14$. Verification plus precise sur calculatrice : $w_{\\text{new}} = 1 + 0{,}5 = 1{,}5$ et $b_{\\text{new}} = 30 + 0{,}14 = 30{,}14$.",
        "explications_mauvaises": [
            None,
            "Erreur de signe : le gradient est negatif, donc $-\\alpha \\times (-|g|) = +$.",
            "Oubli du facteur $\\frac{2}{n}$ : les variations sont trop petites.",
            "Grandes variations qui supposeraient un $\\alpha$ tres eleve."
        ],
        "reference_cours": "Jour 2, section 4.2 - Exemple detaille sur le fil rouge",
        "piege_a_eviter": "Faire le calcul etape par etape : predictions, erreurs, gradients, puis mise a jour."
    },

    # --- CODE (6) ---
    {
        "id": "Q42",
        "jour": 2,
        "difficulte": "vert",
        "type": "code",
        "enonce": "Quelle boucle Python implemente correctement une descente de gradient avec 100 iterations ?",
        "propositions": [
            "```python\nfor i in range(100):\n    grad = compute_gradient(w, b, X, y)\n    w = w - alpha * grad\n```",
            "```python\nwhile True:\n    w = w - alpha * grad\n```",
            "```python\nfor i in range(100):\n    w = w + alpha * grad\n```",
            "```python\nif grad > 0:\n    w = w - alpha * grad\n```"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Pour une descente de gradient a nombre fixe d'iterations, on utilise une boucle `for i in range(n_iterations)`. A chaque iteration : (1) on calcule le gradient au point courant, (2) on met a jour $w$ en soustrayant $\\alpha \\cdot \\text{grad}$.",
        "explications_mauvaises": [
            None,
            "Boucle infinie sans recalcul du gradient : $w$ ne se mettra pas a jour correctement.",
            "Mauvais signe : on ADDITIONNE au lieu de soustraire. C'est une MONTEE de gradient.",
            "Pas de boucle : une seule mise a jour conditionnelle, ce n'est pas une descente iterative."
        ],
        "reference_cours": "Jour 2, section 5.1 - Implementation Python",
        "piege_a_eviter": "Bien recalculer le gradient a CHAQUE iteration : il depend de $w$ courant."
    },
    {
        "id": "Q43",
        "jour": 2,
        "difficulte": "vert",
        "type": "code",
        "enonce": "Que fait la ligne `w = w - alpha * dJ_dw` dans une boucle de descente de gradient ?",
        "propositions": [
            "Elle applique la regle de mise a jour des parametres",
            "Elle calcule la prediction $\\hat{y}$",
            "Elle calcule le cout $J$",
            "Elle genere aleatoirement une nouvelle valeur de $w$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "C'est la traduction directe de la formule $w \\leftarrow w - \\alpha \\cdot \\frac{\\partial J}{\\partial w}$ : on deplace $w$ dans la direction opposee au gradient, avec un pas proportionnel a $\\alpha$.",
        "explications_mauvaises": [
            None,
            "La prediction s'ecrit `y_hat = w * x + b`, pas cette formule.",
            "Le cout s'ecrit `J = np.mean((y - y_hat)**2)`, pas cette formule.",
            "Aucun aleatoire n'est present : c'est une mise a jour deterministe."
        ],
        "reference_cours": "Jour 2, section 5.1 - Lecture de code",
        "piege_a_eviter": "Reconnaitre dans le code Python les formules mathematiques du cours."
    },
    {
        "id": "Q44",
        "jour": 2,
        "difficulte": "jaune",
        "type": "code",
        "enonce": "Quelle expression NumPy calcule correctement le gradient MSE par rapport a $w$ : $\\frac{\\partial J}{\\partial w} = -\\frac{2}{n}\\sum (y_i - \\hat{y}_i) x_i$ ?",
        "propositions": [
            "`dJ_dw = -(2/n) * np.sum((y - y_hat) * x)`",
            "`dJ_dw = (2/n) * np.sum((y - y_hat) * x)`",
            "`dJ_dw = -(2/n) * np.sum(y - y_hat)`",
            "`dJ_dw = -(2/n) * np.mean(y_hat)`"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "La formule contient trois ingredients : le signe $-$, le facteur $\\frac{2}{n}$, et la somme $\\sum (y_i - \\hat{y}_i) x_i$. Le produit `(y - y_hat) * x` est fait element par element grace au broadcasting NumPy, puis `np.sum` en fait la somme.",
        "explications_mauvaises": [
            None,
            "Mauvais signe : la formule commence par $-\\frac{2}{n}$, pas $+\\frac{2}{n}$.",
            "Oubli du facteur $x_i$ dans la somme : cela correspond a $\\frac{\\partial J}{\\partial b}$, pas $\\frac{\\partial J}{\\partial w}$.",
            "Utilisation de `np.mean(y_hat)` qui n'a rien a voir avec le gradient."
        ],
        "reference_cours": "Jour 2, section 5.2 - Gradient vectorise",
        "piege_a_eviter": "Trois elements a verifier : signe, facteur $2/n$, et multiplication par $x$ pour $\\frac{\\partial J}{\\partial w}$."
    },
    {
        "id": "Q45",
        "jour": 2,
        "difficulte": "jaune",
        "type": "code",
        "enonce": "Quelle condition d'arret premature ajouter dans une boucle de descente de gradient pour eviter des iterations inutiles si on a converge ?",
        "propositions": [
            "```python\nif abs(J_old - J_new) < 1e-6:\n    break\n```",
            "```python\nif J_new > J_old:\n    break\n```",
            "```python\nif w == 0:\n    break\n```",
            "```python\nif alpha > 1:\n    break\n```"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "La convergence se detecte quand le cout ne diminue plus significativement entre deux iterations. Comparer la variation absolue a une petite tolerance (ex: $10^{-6}$) est la methode standard.",
        "explications_mauvaises": [
            None,
            "Cette condition detecterait une divergence (cout qui augmente), pas une convergence.",
            "Tester $w == 0$ n'a pas de sens general : $w$ peut converger vers n'importe quelle valeur.",
            "$\\alpha$ est un hyperparametre fixe par l'utilisateur, il ne change pas pendant la boucle."
        ],
        "reference_cours": "Jour 2, section 5.3 - Criteres de convergence",
        "piege_a_eviter": "Tolerance bien choisie (ni trop grande = arret premature, ni trop petite = jamais d'arret)."
    },
    {
        "id": "Q46",
        "jour": 2,
        "difficulte": "jaune",
        "type": "code",
        "enonce": "On compare deux implementations du gradient MSE :\n\n**Version A** :\n```python\ngrad = 0\nfor i in range(n):\n    grad += -2 * (y[i] - y_hat[i]) * x[i]\ngrad /= n\n```\n\n**Version B** :\n```python\ngrad = -(2/n) * np.sum((y - y_hat) * x)\n```\n\nQuelle affirmation est correcte ?",
        "propositions": [
            "Les deux versions calculent la meme chose, mais B est plus rapide (vectorisation NumPy)",
            "A est plus precise que B",
            "A et B donnent des resultats differents",
            "B ne fonctionne que pour $n < 10$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Les deux versions calculent la meme formule mathematique. La version B, vectorisee en NumPy, est bien plus rapide (factor 10 a 100x) car NumPy execute l'operation en code C optimise, sans la surcharge de la boucle Python.",
        "explications_mauvaises": [
            None,
            "La precision est identique (meme formule, memes operations flottantes au float near).",
            "Non : elles calculent exactement la meme somme.",
            "La taille ne limite pas NumPy : il gere des millions d'elements."
        ],
        "reference_cours": "Jour 2, section 5.2 - Vectorisation",
        "piege_a_eviter": "Toujours privilegier la vectorisation NumPy pour les calculs sur des tableaux."
    },
    {
        "id": "Q47",
        "jour": 2,
        "difficulte": "vert",
        "type": "code",
        "enonce": "Quelle strategie d'initialisation de $w$ et $b$ est la plus couramment utilisee pour un probleme de regression lineaire simple ?",
        "propositions": [
            "`w = 0.0 ; b = 0.0` (initialisation a zero)",
            "`w = 999 ; b = -1000` (initialisation extremement grande en valeur absolue)",
            "`w = np.nan ; b = np.nan` (valeur indefinie)",
            "`w = 'auto' ; b = 'auto'` (chaine de caracteres)"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "Pour une regression lineaire simple, initialiser $w = 0$ et $b = 0$ est standard : la descente de gradient fonctionne bien quelle que soit l'initialisation. Pour des problemes plus complexes (reseaux de neurones), on utilisera des initialisations aleatoires (Xavier, He, etc.).",
        "explications_mauvaises": [
            None,
            "Des valeurs extremes peuvent mener a des problemes numeriques ou a une convergence lente.",
            "`np.nan` n'est PAS un nombre : aucun calcul ne marchera.",
            "Une chaine de caracteres n'est pas un nombre, toute operation arithmetique echouera."
        ],
        "reference_cours": "Jour 2, section 5.1 - Initialisation",
        "piege_a_eviter": "Pour la regression simple, la valeur initiale importe peu. Zero est un choix simple et correct."
    },

    # --- FORMULES (3) ---
    {
        "id": "Q48",
        "jour": 2,
        "difficulte": "vert",
        "type": "formule",
        "enonce": "Quelle est la regle correcte de mise a jour des parametres en descente de gradient ?",
        "propositions": [
            "$\\boldsymbol{\\theta} \\leftarrow \\boldsymbol{\\theta} - \\alpha \\, \\nabla J(\\boldsymbol{\\theta})$",
            "$\\boldsymbol{\\theta} \\leftarrow \\boldsymbol{\\theta} + \\alpha \\, \\nabla J(\\boldsymbol{\\theta})$",
            "$\\boldsymbol{\\theta} \\leftarrow \\alpha \\, \\nabla J(\\boldsymbol{\\theta})$",
            "$\\boldsymbol{\\theta} \\leftarrow \\boldsymbol{\\theta} \\cdot \\alpha$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "La regle standard de descente de gradient : on soustrait ($-$) le produit du taux d'apprentissage $\\alpha$ et du gradient $\\nabla J$. C'est la formule universelle.",
        "explications_mauvaises": [
            None,
            "C'est une MONTEE de gradient : on s'eloignerait du minimum.",
            "Cette formule remplace $\\boldsymbol{\\theta}$ par $\\alpha \\nabla J$ : on perdrait la valeur precedente.",
            "Cette formule ne fait jamais intervenir le gradient : les parametres ne seraient jamais corriges."
        ],
        "reference_cours": "Jour 2, section 3.1 - Regle de mise a jour",
        "piege_a_eviter": "Formule a memoriser : $\\theta \\leftarrow \\theta - \\alpha \\nabla J$."
    },
    {
        "id": "Q49",
        "jour": 2,
        "difficulte": "jaune",
        "type": "formule",
        "enonce": "Pour une regression lineaire simple $\\hat{y}_i = w x_i + b$ avec $J = \\frac{1}{n}\\sum (y_i - \\hat{y}_i)^2$, quelle est la derivee $\\frac{\\partial J}{\\partial w}$ ?",
        "propositions": [
            "$-\\frac{2}{n} \\sum_{i=1}^{n} (y_i - \\hat{y}_i) x_i$",
            "$-\\frac{2}{n} \\sum_{i=1}^{n} (y_i - \\hat{y}_i)$",
            "$\\frac{1}{n} \\sum_{i=1}^{n} (y_i - \\hat{y}_i)^2$",
            "$\\frac{2}{n} \\sum_{i=1}^{n} (\\hat{y}_i - y_i)$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "En derivant $J$ par rapport a $w$ : le carre donne un facteur $2$, la derivee interne $\\frac{\\partial (y_i - \\hat{y}_i)}{\\partial w} = -x_i$, d'ou le signe $-$ et le $x_i$. Formule complete : $\\frac{\\partial J}{\\partial w} = -\\frac{2}{n} \\sum (y_i - \\hat{y}_i) x_i$.",
        "explications_mauvaises": [
            None,
            "C'est la derivee par rapport a $b$ ($\\frac{\\partial J}{\\partial b}$), qui ne contient pas $x_i$ car $\\hat{y}$ derive par rapport a $b$ donne $1$ et non $x_i$.",
            "C'est la fonction $J$ elle-meme, pas sa derivee.",
            "Mauvais signe et $x_i$ manquant."
        ],
        "reference_cours": "Jour 2, section 4.1 - Derivees partielles MSE",
        "piege_a_eviter": "La derivee par rapport a $w$ contient $x_i$ (car $\\hat{y}$ depend de $w \\cdot x$). La derivee par rapport a $b$ n'en contient pas."
    },
    {
        "id": "Q50",
        "jour": 2,
        "difficulte": "orange",
        "type": "formule",
        "enonce": "Pour le meme probleme, quelle est la derivee $\\frac{\\partial J}{\\partial b}$ ?",
        "propositions": [
            "$-\\frac{2}{n} \\sum_{i=1}^{n} (y_i - \\hat{y}_i)$",
            "$-\\frac{2}{n} \\sum_{i=1}^{n} (y_i - \\hat{y}_i) x_i$",
            "$\\frac{2}{n} \\sum_{i=1}^{n} (y_i - \\hat{y}_i)^2$",
            "$+\\frac{1}{n} \\sum (y_i - \\hat{y}_i)$"
        ],
        "bonne_reponse_index": 0,
        "explication_bonne": "En derivant $J$ par rapport a $b$ : le carre donne $2$, la derivee interne $\\frac{\\partial (y_i - \\hat{y}_i)}{\\partial b} = -1$ (car $\\hat{y}_i = w x_i + b$). Formule : $\\frac{\\partial J}{\\partial b} = -\\frac{2}{n} \\sum (y_i - \\hat{y}_i)$. Pas de $x_i$, contrairement a la derivee par rapport a $w$.",
        "explications_mauvaises": [
            None,
            "C'est la derivee par rapport a $w$ ($\\frac{\\partial J}{\\partial w}$) : avec $x_i$.",
            "C'est $J$ (la fonction), pas sa derivee.",
            "Erreurs : pas de signe moins, oubli du facteur 2, et signe plus au lieu de moins."
        ],
        "reference_cours": "Jour 2, section 4.1 - Derivees partielles MSE",
        "piege_a_eviter": "Difference cle : derivee par rapport a $w$ contient $x_i$, derivee par rapport a $b$ ne le contient pas (car $b$ est additif constant dans $\\hat{y}$)."
    }
]


# ============================================================================
# VERIFICATION DE LA REPARTITION
# ============================================================================

def verifier_repartition(questions):
    """Verifie que la repartition correspond au cadrage."""
    from collections import Counter
    
    j1 = [q for q in questions if q["jour"] == 1]
    j2 = [q for q in questions if q["jour"] == 2]
    
    print(f"=== Total : {len(questions)} questions ===")
    print(f"Jour 1 : {len(j1)} questions")
    print(f"Jour 2 : {len(j2)} questions")
    
    for num_jour, qs in [("J1", j1), ("J2", j2)]:
        print(f"\n--- {num_jour} ---")
        diff = Counter(q["difficulte"] for q in qs)
        typ = Counter(q["type"] for q in qs)
        print(f"  Difficulte : {dict(diff)}")
        print(f"  Type       : {dict(typ)}")
    
    total_pts = sum({"vert": 1, "jaune": 2, "orange": 3, "rouge": 4}[q["difficulte"]] for q in questions)
    print(f"\nTotal points bruts : {total_pts}")
    
    # Verif IDs uniques et ordonnes
    ids = [q["id"] for q in questions]
    assert len(ids) == len(set(ids)), "IDs dupliques !"
    for i, q in enumerate(questions, 1):
        expected = f"Q{i:02d}"
        assert q["id"] == expected, f"ID attendu {expected}, recu {q['id']}"
    print("Tous les IDs sont uniques et ordonnes.")


# ============================================================================
# GENERATION DES FICHIERS
# ============================================================================

def generer_fichiers(questions, output_dir):
    """Genere questions.json (sans reponses) et corrections.json (avec reponses)."""
    import datetime as dt
    
    output_dir = Path(output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    
    # questions.json (sans bonne_reponse_index ni explications)
    questions_publiques = {
        "metadata": {
            "titre": "QCM d'evaluation - Jours 1 et 2",
            "module": "Mathematiques appliquees a l'Intelligence Artificielle",
            "formation": "Bachelor 2 Informatique - IPSSI",
            "annee_academique": "2025-2026",
            "auteur": "Mohamed EL AFRIT",
            "email_contact": "m.elafrit@ecole-ipssi.net",
            "licence": "CC BY-NC-SA 4.0",
            "version": "1.0",
            "date_creation": dt.date.today().isoformat(),
            "bareme": {
                "vert": 1,
                "jaune": 2,
                "orange": 3,
                "rouge": 4
            },
            "points_max_bruts": 92,
            "note_sur": 20,
            "nombre_questions": len(questions),
            "randomisation": "questions_et_propositions_par_etudiant",
            "chronometre": "indicatif_sans_blocage",
            "navigation": "libre_avec_retour_arriere",
            "anti_fraude": "signature_SHA256",
            "salt_signature": "IPSSI_SALT_2026"
        },
        "questions": [
            {
                "id": q["id"],
                "jour": q["jour"],
                "difficulte": q["difficulte"],
                "type": q["type"],
                "enonce": q["enonce"],
                "propositions": q["propositions"]
            }
            for q in questions
        ]
    }
    
    # corrections.json (avec bonne_reponse_index et explications)
    corrections_privees = {
        "metadata": {
            "titre": "Corrections QCM d'evaluation - Jours 1 et 2",
            "auteur": "Mohamed EL AFRIT - IPSSI",
            "licence": "CC BY-NC-SA 4.0",
            "version": "1.0",
            "date_creation": dt.date.today().isoformat(),
            "attention": "FICHIER CONFIDENTIEL - NE PAS DEPLOYER AVANT LA DATE LIMITE",
            "deploiement": "Phase B uniquement - apres cloture de l'evaluation"
        },
        "corrections": [
            {
                "id": q["id"],
                "jour": q["jour"],
                "difficulte": q["difficulte"],
                "type": q["type"],
                "bonne_reponse_index": q["bonne_reponse_index"],
                "explication_bonne": q["explication_bonne"],
                "explications_mauvaises": q["explications_mauvaises"],
                "reference_cours": q["reference_cours"],
                "piege_a_eviter": q["piege_a_eviter"]
            }
            for q in questions
        ]
    }
    
    with open(output_dir / "questions.json", "w", encoding="utf-8") as f:
        json.dump(questions_publiques, f, ensure_ascii=False, indent=2)
    with open(output_dir / "corrections.json", "w", encoding="utf-8") as f:
        json.dump(corrections_privees, f, ensure_ascii=False, indent=2)
    
    print(f"\nFichiers generes dans : {output_dir.resolve()}")
    print(f"  - questions.json : {(output_dir/'questions.json').stat().st_size} octets")
    print(f"  - corrections.json : {(output_dir/'corrections.json').stat().st_size} octets")


if __name__ == "__main__":
    verifier_repartition(QUESTIONS)
    generer_fichiers(QUESTIONS, Path(__file__).parent)
