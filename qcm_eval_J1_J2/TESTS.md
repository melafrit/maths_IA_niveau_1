# Plan de tests du QCM d'évaluation J1-J2

**Module :** Mathématiques appliquées à l'IA  
**Auteur :** Mohamed EL AFRIT — [m.elafrit@ecole-ipssi.net](mailto:m.elafrit@ecole-ipssi.net)  
**Licence :** CC BY-NC-SA 4.0

---

## 📋 Introduction

Ce document décrit le protocole de tests à exécuter avant chaque déploiement du QCM pour garantir que les trois pages web fonctionnent correctement et que les calculs de notes sont fiables.

Les tests s'appuient sur **10 CSV de test fictifs** fournis dans le répertoire [`tests/`](./tests/). Ces CSV représentent une classe complète avec des profils d'étudiants variés, du cas 0/20 au cas 20/20, incluant un cas de fraude simulée.

---

## 🗃️ Description du jeu de données de test

Le fichier **`tests/MANIFESTE_TESTS.json`** liste les 10 CSV avec leurs valeurs attendues. Voici un aperçu :

| # | Nom | Prénom | Note attendue | Profil | Signature |
|---|---|---|:---:|---|:---:|
| 1 | ALLEGRE | Emma | 18,48/20 | Excellent, maîtrise totale | ✅ |
| 2 | BERNARD | Louis | 17,17/20 | Bon élève équilibré | ✅ |
| 3 | CHEVALIER | Nora | 12,83/20 | J2 fort / J1 faible | ✅ |
| 4 | DURAND | Paul | 11,96/20 | Moyen, compréhension partielle | ✅ |
| 5 | FABRE | Sarah | 10,65/20 | J1 fort / J2 faible | ✅ |
| 6 | GARNIER | Tom | 9,57/20 | Juste, bases fragiles | ✅ |
| 7 | HENRY | Julie | 5,65/20 | Difficulté, 15 blanches | ✅ |
| 8 | ISAAC | Mathis | **20,00/20** | Cas limite parfait | ✅ |
| 9 | JACQUES | Lea | **0,00/20** | Cas limite zéro | ✅ |
| 10 | KAHN | Samuel | 10,22/20 | **CSV falsifié** | ❌ |

Statistiques attendues de la « classe test » :
- **Moyenne** : 11,65/20
- **Médiane** : 11,30/20
- **Min** : 0,00/20 &nbsp;·&nbsp; **Max** : 20,00/20
- **Signatures valides** : 9/10 (1 fraudeur détecté)

Les CSV sont **déterministes** : le script `tests/_generate_test_csvs.py` utilise `random.seed` fixe par profil, donc les CSV sont identiques à chaque régénération.

### Régénérer les CSV de test

```bash
cd qcm_eval_J1_J2/tests
python3 _generate_test_csvs.py
```

---

## 🧪 Protocole de tests

Les tests sont répartis en **4 niveaux**, du plus élémentaire (script) au plus complet (parcours utilisateur manuel).

### Niveau 0 — Prérequis

Avant tout test, lancer un serveur HTTP local :

```bash
cd qcm_eval_J1_J2
python3 -m http.server 8000
```

Puis ouvrir dans le navigateur :
- Page étudiant : <http://localhost:8000/qcm_etudiant.html>
- Correction perso : <http://localhost:8000/correction_personnelle.html>
- Dashboard : <http://localhost:8000/dashboard_enseignant_2026.html>

---

### Niveau 1 — Tests automatisés (Python)

Ces tests vérifient la cohérence entre les CSV générés et les valeurs attendues. Ils s'exécutent **sans navigateur**.

#### Test 1.1 — Conformité des CSV au manifeste

```bash
cd qcm_eval_J1_J2/tests
python3 _generate_test_csvs.py
```

**Critère de réussite** : Les 10 CSV sont générés avec leurs notes attendues affichées dans la sortie. Le manifeste `MANIFESTE_TESTS.json` est créé.

#### Test 1.2 — Vérification des signatures et des notes

Le script ci-dessous parse chaque CSV et vérifie que la note recalculée (avec le code du nouveau dashboard utilisant la difficulté authoritative) correspond à la valeur du manifeste.

```bash
cd qcm_eval_J1_J2/tests
python3 << 'EOF'
import hashlib, json, re
from pathlib import Path

ROOT = Path("..")
SALT = "IPSSI_SALT_2026"
BAREME = {"vert": 1, "jaune": 2, "orange": 3, "rouge": 4}

corrections = {c["id"]: c for c in json.loads((ROOT / "corrections.json").read_text())["corrections"]}
manifeste = json.loads(Path("MANIFESTE_TESTS.json").read_text())["csv_tests"]

ok = 0
for entry in manifeste:
    text = Path(entry["filename"]).read_text()
    # Calcul note authoritative
    # ... (voir tests.md pour le code complet)
print(f"Tests passes : {ok}/{len(manifeste)}")
EOF
```

**Critère de réussite** : `10/10` tests passés.

#### Test 1.3 — Vérification que la falsification est détectée

Vérifier que KAHN Samuel a bien une signature invalide :

```bash
grep -A 1 "KAHN" tests/MANIFESTE_TESTS.json
```

**Critère de réussite** : `"signature_valide": false` pour Samuel KAHN.

---

### Niveau 2 — Tests manuels de `qcm_etudiant.html` (parcours étudiant)

Ces tests simulent le comportement d'un étudiant passant le QCM.

#### Test 2.1 — Parcours nominal

1. Ouvrir `qcm_etudiant.html` dans un navigateur propre (pas de `localStorage` résiduel — Ctrl+Shift+Suppr pour nettoyer).
2. Vérifier l'écran d'accueil :
   - [ ] Titre « QCM d'évaluation — Jours 1 et 2 »
   - [ ] Barème visible (🟢 1pt, 🟡 2pts, 🟠 3pts, 🔴 4pts)
   - [ ] Bouton « Commencer le QCM » actif
3. Cliquer sur « Commencer le QCM ».
4. Vérifier l'écran de questions :
   - [ ] Chronomètre en haut à droite qui démarre
   - [ ] Barre de progression 1/50
   - [ ] Grille de navigation (50 boutons, celui de la question 1 surligné)
   - [ ] 4 propositions A/B/C/D pour la question 1
   - [ ] Boutons « Précédent » (désactivé) et « Suivant »
5. Répondre à quelques questions (3-4), puis cliquer « Finir le QCM ».
6. Vérifier l'écran de révision :
   - [ ] Champs Nom / Prénom / Email obligatoires
   - [ ] Bouton « Valider et télécharger mon CSV » désactivé tant que les champs sont vides
7. Remplir les 3 champs puis cliquer « Valider ».
8. Vérifier l'écran de fin :
   - [ ] Bouton de téléchargement CSV présent
   - [ ] Nom de fichier : `IPSSI_NOM_Prenom_QCM_J1J2_YYYYMMDD.csv`
   - [ ] Bouton mailto pré-rempli fonctionne
9. Télécharger le CSV et l'ouvrir dans un éditeur de texte.
10. Vérifier la structure :
    - [ ] Section `# SECTION: METADATA` avec Nom / Prénom / Email / DateDebut / DateFin / DureeSec / NbQuestions / Version / SaltVersion
    - [ ] Section `# SECTION: REPONSES` avec 50 lignes (une par question)
    - [ ] Section `# SECTION: SIGNATURE` avec `SHA256,<hex de 64 caractères>`

#### Test 2.2 — Reprise après fermeture

1. Commencer le QCM, répondre à 5 questions.
2. Fermer l'onglet sans valider.
3. Rouvrir `qcm_etudiant.html`.
4. Vérifier : **l'écran d'accueil propose « Reprendre »** avec le même ordre de questions.
5. Cliquer sur « Reprendre » et vérifier que les réponses précédentes sont conservées.

#### Test 2.3 — Randomisation inter-étudiants

1. Ouvrir `qcm_etudiant.html` dans un navigateur « A » (Firefox).
2. Ouvrir `qcm_etudiant.html` dans un navigateur « B » (Chrome incognito).
3. Démarrer le QCM dans les 2 navigateurs.
4. Comparer la question 1 affichée dans A et dans B.
5. **Critère** : L'ordre des questions ET l'ordre des propositions A/B/C/D doivent être différents dans les 2 navigateurs. Si c'est identique, c'est un bug majeur.

#### Test 2.4 — Navigation libre

1. Pendant le QCM, utiliser la grille de navigation pour revenir en arrière.
2. Modifier une réponse déjà donnée.
3. Vérifier : la nouvelle réponse est bien prise en compte (icône ✔ toujours présente, autre proposition surlignée).

#### Test 2.5 — Non-réponses autorisées

1. Pendant le QCM, laisser quelques questions blanches (ne pas cliquer sur une proposition).
2. Finir le QCM et télécharger le CSV.
3. Vérifier dans le CSV : les lignes correspondantes ont `LettreChoisie` vide et `IndexOriginalChoisi` vide.

---

### Niveau 3 — Tests manuels de `correction_personnelle.html` (parcours étudiant)

#### Test 3.1 — Upload d'un CSV valide

1. Ouvrir `correction_personnelle.html`.
2. Glisser-déposer `tests/IPSSI_ALLEGRE_Emma_QCM_J1J2_20260426.csv` sur la zone.
3. Vérifier :
   - [ ] Redirection automatique vers l'écran rapport
   - [ ] Note affichée : **18,48/20** (mention « Très Bien » en vert)
   - [ ] Badge signature : **✅ Valide**
   - [ ] 47/50 bonnes réponses
   - [ ] Durée : 62m 00s
   - [ ] Réussite J1 : proche de 95%, Réussite J2 : proche de 95%
4. Dérouler une question :
   - [ ] Énoncé complet affiché (avec KaTeX si formule)
   - [ ] 4 propositions dans l'ordre vu (pas forcément l'ordre A/B/C/D du JSON)
   - [ ] La proposition choisie est verte (✅) ou rouge (❌)
   - [ ] La bonne réponse (si différente) est orange (⭕)
   - [ ] Encadré « À retenir » avec explication
   - [ ] Explications numérotées des mauvaises propositions
   - [ ] Encadré « Piège à éviter »
5. Tester le toggle 🌙 / ☀️ : vérifier que le thème change correctement.

#### Test 3.2 — Upload du CSV falsifié

1. Ouvrir `correction_personnelle.html`.
2. Glisser-déposer `tests/IPSSI_KAHN_Samuel_QCM_J1J2_20260426.csv`.
3. Vérifier :
   - [ ] Badge signature : **❌ Invalide**
   - [ ] Encadré d'avertissement rouge visible
   - [ ] Note quand même calculée (sans bloquer)

#### Test 3.3 — Cas 0/20

1. Uploader `tests/IPSSI_JACQUES_Lea_QCM_J1J2_20260426.csv`.
2. Vérifier :
   - [ ] Note : **0,00/20**
   - [ ] Mention « Insuffisant » en rouge
   - [ ] 0/50 bonnes réponses
   - [ ] Toutes les questions affichent le badge ❌ rouge

#### Test 3.4 — Cas 20/20

1. Uploader `tests/IPSSI_ISAAC_Mathis_QCM_J1J2_20260426.csv`.
2. Vérifier :
   - [ ] Note : **20,00/20**
   - [ ] Mention « Très Bien » en vert
   - [ ] 50/50 bonnes réponses
   - [ ] Toutes les questions affichent le badge ✅ vert

#### Test 3.5 — Cas avec beaucoup de blanches (HENRY)

1. Uploader `tests/IPSSI_HENRY_Julie_QCM_J1J2_20260426.csv`.
2. Vérifier :
   - [ ] Environ 15 questions avec badge ❓ orange (« Non répondue »)
   - [ ] Note : **5,65/20**

#### Test 3.6 — Test de fraude manuelle

1. Ouvrir `tests/IPSSI_DURAND_Paul_QCM_J1J2_20260426.csv` dans un éditeur de texte.
2. Modifier **une réponse** (par exemple : changer `,A,1` en `,D,3` sur la ligne 1 de la section REPONSES).
3. Sauvegarder et uploader ce CSV dans `correction_personnelle.html`.
4. **Critère** : Badge signature **❌ Invalide**, avertissement rouge visible.

---

### Niveau 4 — Tests manuels de `dashboard_enseignant_2026.html` (parcours enseignant)

#### Test 4.1 — Upload multi-CSV

1. Ouvrir `dashboard_enseignant_2026.html`.
2. Dans l'explorateur, sélectionner **tous les CSV** du dossier `tests/` (Ctrl+A).
3. Les glisser-déposer sur la zone d'upload.
4. Vérifier :
   - [ ] Passage automatique à l'écran dashboard
   - [ ] KPI « Étudiants » : **10**
   - [ ] KPI « Moyenne /20 » : **11,65**
   - [ ] KPI « Médiane /20 » : **11,30**
   - [ ] KPI « Min /20 » : **0,00** (rouge)
   - [ ] KPI « Max /20 » : **20,00** (vert)
   - [ ] KPI « Signatures ❌ » : **1** (rouge)

#### Test 4.2 — Distribution des notes

1. Vérifier l'histogramme :
   - [ ] 10 tranches (0-2, 2-4, ..., 18-20)
   - [ ] Une barre dans la tranche 0-2 (JACQUES)
   - [ ] Une barre dans la tranche 18-20 (ISAAC et ALLEGRE)
   - [ ] Barres colorées selon la tranche (rouge pour < 10, orange pour 10-12, etc.)

#### Test 4.3 — Comparaison J1 vs J2

1. Vérifier le bar chart horizontal :
   - [ ] Deux barres (Jour 1 et Jour 2)
   - [ ] Pourcentages affichés à l'extrémité
   - [ ] Proche de 60% pour chaque (varie selon la réussite moyenne)

#### Test 4.4 — Grille par question

1. Vérifier la grille 10×5 :
   - [ ] 50 cellules avec ID de question (Q01 à Q50)
   - [ ] % de réussite affiché dans chaque cellule
   - [ ] Couleur selon la tranche (rouge/orange/bleu/vert)
2. Cliquer sur une cellule de question :
   - [ ] Modal s'ouvre avec détail de la question
   - [ ] Énoncé complet
   - [ ] 4 propositions listées (bonne en vert)
   - [ ] Répartition des réponses de la classe

#### Test 4.5 — Top 5 questions ratées

1. Vérifier que 5 cartes sont affichées.
2. Chaque carte comporte :
   - [ ] Un camembert SVG coloré
   - [ ] Le % de réussite (≤ 50% typiquement)
   - [ ] La légende A/B/C/D + non répondu
   - [ ] L'énoncé tronqué

#### Test 4.6 — Tableau des étudiants — tri et filtres

1. Vérifier le tri par défaut : note décroissante.
2. **ISAAC Mathis** doit être rang #1 avec 20,00/20.
3. **JACQUES Lea** doit être dernière avec 0,00/20.
4. Cliquer sur l'en-tête « Nom » :
   - [ ] Tri alphabétique, rangs recalculés mais gardés
5. Taper `CHE` dans la recherche :
   - [ ] Seul CHEVALIER Nora apparaît
6. Sélectionner le filtre « ❌ Moins de 10 » :
   - [ ] 3 lignes visibles : GARNIER, HENRY, JACQUES
7. Sélectionner « ⭐ 16 et plus » :
   - [ ] 3 lignes visibles : ALLEGRE, BERNARD, ISAAC

#### Test 4.7 — Modal détail étudiant

1. Cliquer sur une ligne du tableau (par ex. DURAND Paul).
2. Vérifier la modal :
   - [ ] 7 KPI en haut (Note, Points, Correctes, %J1, %J2, Durée, Signature)
   - [ ] Liste compacte des 50 réponses avec statut coloré
   - [ ] Chaque ligne : statut + ID + difficulté + énoncé tronqué + « Choisi / Bonne / pts »
3. Appuyer sur **Échap** : la modal se ferme.

#### Test 4.8 — Export CSV Yparéo

1. Cliquer sur « Export CSV (Yparéo) ».
2. Vérifier que le fichier `IPSSI_QCM_J1J2_Resultats_YYYYMMDD.csv` est téléchargé.
3. Ouvrir dans Excel ou LibreOffice :
   - [ ] 10 lignes + 1 ligne d'en-tête
   - [ ] Colonnes : Nom, Prénom, Email, Note, PointsObtenus, PointsMax, PctJ1, PctJ2, DureeSec, DateFin, SignatureValide
   - [ ] Tri par note décroissante
   - [ ] ISAAC en premier avec note 20,00
   - [ ] Les chiffres utilisent la virgule française (pas le point)

#### Test 4.9 — Export XLSX multi-onglets

1. Cliquer sur « Export XLSX multi-onglets ».
2. Vérifier téléchargement de `IPSSI_QCM_J1J2_Rapport_YYYYMMDD.xlsx`.
3. Ouvrir dans Excel/LibreOffice :
   - [ ] **Onglet 1 : Résultats** — 10 lignes, 12 colonnes
   - [ ] **Onglet 2 : Stats classe** — Moyenne, médiane, min, max, écart-type, distribution
   - [ ] **Onglet 3 : Détail par question** — 50 lignes, 16 colonnes (ID, Jour, Difficulté, Type, Énoncé, %Réussite, NbCorrectes, Choix A/B/C/D/NR)

#### Test 4.10 — Détection de fraude

1. Dans le tableau, trouver la ligne KAHN Samuel.
2. Vérifier :
   - [ ] Icône **❌** dans la colonne Signature
   - [ ] Cliquer sur la ligne → la modal affiche « ⚠️ Signature invalide »

---

## 🔁 Tests de régression à re-lancer après chaque modification du code

Après toute modification de `qcm_etudiant.html`, `correction_personnelle.html` ou `dashboard_enseignant_2026.html`, relancer **au minimum** :

| Test | Description | Durée |
|---|---|:---:|
| 1.1 | Régénération des CSV | 10 s |
| 1.2 | Vérification automatique des notes | 30 s |
| 2.1 | Parcours étudiant nominal | 15 min |
| 3.1 | Correction personnelle sur 1 CSV valide | 2 min |
| 3.2 | Correction personnelle sur CSV falsifié | 1 min |
| 4.1 | Dashboard — upload des 10 CSV | 1 min |
| 4.6 | Tri et filtres du tableau | 3 min |
| 4.8 | Export CSV | 1 min |
| 4.9 | Export XLSX | 1 min |

**Durée totale estimée** : ~25 minutes.

---

## 🐛 Symptômes connus et diagnostics

### « Le CSV est valide mais la signature est marquée invalide »

- Vérifier que le salt dans les pages HTML correspond (`CONFIG.SALT = 'IPSSI_SALT_2026'`). Si une page a un salt différent, elle rejettera tous les CSV produits par l'autre.

### « Le dashboard affiche 0 étudiants alors que j'ai bien uploadé des CSV »

- Vérifier que les CSV contiennent bien les 3 sections (`METADATA`, `REPONSES`, `SIGNATURE`). Si une section manque, le parsing ignore le fichier silencieusement. Ouvrir la console navigateur (F12) pour voir les messages d'erreur.

### « Les formules mathématiques s'affichent en texte brut plutôt qu'en formule »

- Vérifier que le CDN KaTeX est accessible (<https://cdn.jsdelivr.net/npm/katex@0.16.9/>). Si le CDN est bloqué, les formules tombent en fallback `<code>` mais restent lisibles.

### « L'export XLSX produit un fichier corrompu »

- Vérifier que le CDN SheetJS est accessible. Fallback : utiliser uniquement l'export CSV.

---

## 📞 Contact

Pour toute remontée de bug ou amélioration : <m.elafrit@ecole-ipssi.net>

---

*Document maintenu par Mohamed EL AFRIT — IPSSI — Licence CC BY-NC-SA 4.0*
