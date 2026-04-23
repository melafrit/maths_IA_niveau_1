# 👨‍🏫 Guide Professeur — IPSSI Examens

> Manuel complet pour les **enseignants**.
> De la création d'un examen à l'analyse des résultats.

---

## 📖 Table des matières

1. [Prise en main](#1-prise-en-main)
2. [Créer votre premier examen](#2-créer-votre-premier-examen)
3. [Gérer la banque de questions](#3-gérer-la-banque-de-questions)
4. [Publication et distribution](#4-publication-et-distribution)
5. [Suivi en temps réel](#5-suivi-en-temps-réel)
6. [Analyse des résultats](#6-analyse-des-résultats)
7. [Clôture et archivage](#7-clôture-et-archivage)
8. [Exports](#8-exports)
9. [Scénarios complets](#9-scénarios-complets)
10. [Astuces et bonnes pratiques](#10-astuces-et-bonnes-pratiques)

---

## 1. Prise en main

### Accès

- **URL locale** : `http://localhost:8765/admin/examens.html`
- **URL production** : `https://examens.ipssi.fr/admin/examens.html`

### Identifiants

Votre compte enseignant est créé par l'**administrateur**. Il vous fournira :
- Un **email** de connexion (ex: `dupont@ipssi.fr`)
- Un **mot de passe temporaire** à changer à la première connexion

### Interface principale

Après connexion, 4 sections principales :

```
┌────────────────────────────────────────────┐
│  📝 Mes examens                            │ ← Création/gestion
│  📚 Banque de questions                    │ ← Consulter Qs disponibles
│  📊 Analytics                              │ ← Statistiques
│  👤 Mon profil                             │ ← Changer mdp, infos
└────────────────────────────────────────────┘
```

### Privilèges enseignant

- ✅ **Créer** vos propres examens
- ✅ **Consulter** la banque de questions (lecture)
- ✅ **Créer** de nouvelles questions (optionnel selon config)
- ✅ **Voir** vos analytics (vos examens uniquement)
- ✅ **Exporter** résultats (CSV/Excel/PDF)
- ❌ **Pas d'accès** aux examens des autres profs
- ❌ **Pas d'accès** au monitoring système / backups

---

## 2. Créer votre premier examen

### Workflow complet

```
[1] Mes examens  →  [2] + Nouvel examen  →  [3] Configuration
        ↓
[6] Publier  ←  [5] Preview  ←  [4] Sélection questions
        ↓
[7] Distribuer code d'accès aux étudiants
        ↓
[8] Suivi en temps réel  →  [9] Clôture  →  [10] Analytics
```

### Étape 1 — Configuration de base

Cliquer sur **+ Nouvel examen** et remplir :

| Champ | Description | Exemple |
|---|---|---|
| **Titre** | Nom affiché aux étudiants | "Contrôle vecteurs L1" |
| **Description** | Info additionnelle (optionnel) | "Chapitres 1-3" |
| **Durée** | Temps alloué | 60 minutes |
| **Date ouverture** | Début de la période | 2026-04-25 08:00 |
| **Date clôture** | Fin (plus de passages possibles) | 2026-04-25 18:00 |
| **Max passages** | Tentatives par étudiant | 1 (standard) |

### Étape 2 — Options avancées

```
☑ Shuffle questions      ← Ordre aléatoire par étudiant
☑ Shuffle options        ← A/B/C/D mélangées
☑ Show correction after  ← Étudiant voit correction
☐ Correction delay       ← (minutes avant accès correction)
```

**Recommandations** :
- ✅ Toujours activer **shuffle questions** (anti-triche)
- ✅ Activer **shuffle options** si QCM à choix unique
- ✅ **Show correction after** : pédagogique, mais si max_passages > 1, désactiver !
- ⚠️ Si **correction delay = 0** : les étudiants voient leur correction dès le submit — idéal pour feedback immédiat

### Étape 3 — Sélection des questions

**Arborescence module → chapitre → thème** :

```
📚 Banque (320 questions disponibles)
├── 📘 vecteurs (80 Qs)
│   ├── operations
│   │   ├── somme (15 Qs)
│   │   ├── produit-scalaire (20 Qs)
│   │   └── ...
│   └── norme (25 Qs)
├── 📙 matrices (80 Qs)
├── 📗 derivees (80 Qs)
└── 📕 integrales (80 Qs)
```

**Actions** :
- ☑ Cocher chaque question voulue
- Filtrer par **difficulté** (facile / moyen / difficile / très difficile)
- Filtrer par **tags**
- **Nombre sélectionné** affiché en temps réel
- **Durée estimée** auto-calculée (2 min/Q par défaut)

**Bonnes pratiques** :

| Type d'examen | Nombre Qs | Mix difficulté |
|---|:-:|---|
| Quiz rapide | 5-10 | 80% facile, 20% moyen |
| Contrôle | 15-25 | 30% facile, 50% moyen, 20% difficile |
| Examen final | 30-40 | 20% facile, 40% moyen, 30% difficile, 10% très difficile |

### Étape 4 — Preview

Avant publication, **Preview** vous permet de voir l'examen comme un étudiant :
- Questions dans l'ordre aléatoire (ou configuré)
- Options mélangées
- Chronomètre simulé

### Étape 5 — Sauvegarde en brouillon

L'examen est créé avec **statut `draft`** :
- ✅ Modifiable à volonté
- ❌ Non accessible aux étudiants
- ❌ Pas de code d'accès encore

Vous obtenez un **ID** : `EXM-XXXX-YYYY`

---

## 3. Gérer la banque de questions

### Consulter

`/admin/banque.html` → arbre de navigation

Pour chaque question :
- Énoncé rendu en LaTeX
- 4 options (la bonne soulignée)
- Explication
- Difficulté + tags
- Historique d'utilisation

### Créer une nouvelle question (si droits accordés)

1. Click sur un thème → **+ Nouvelle question**
2. **Éditeur avec preview LaTeX temps réel** :

```
Énoncé (LaTeX autorisé) :
┌─────────────────────────────────────┐
│ Quelle est la dérivée de $f(x) = x^2$│
│ en $x = 3$ ?                         │
└─────────────────────────────────────┘

Preview : Quelle est la dérivée de f(x) = x² en x = 3 ?
```

3. **4 options** (cocher la bonne) :
   ```
   ○ 6          ← bonne réponse (cochée)
   ○ 9
   ○ 2
   ○ x + 2
   ```

4. **Explication** (affichée en correction) :
   ```
   f'(x) = 2x, donc f'(3) = 2 × 3 = 6
   ```

5. **Métadonnées** :
   - Difficulté : `facile` / `moyen` / `difficile` / `tres_difficile`
   - Tags : mot-clés séparés par virgules
   - Type : `qcm` (seul supporté pour l'instant)

### Syntaxe LaTeX (KaTeX)

Les symboles les plus utilisés :

| Rendu | Code |
|---|---|
| $x^2$ | `$x^2$` |
| $\frac{a}{b}$ | `$\frac{a}{b}$` |
| $\vec{u}$ | `$\vec{u}$` |
| $\sqrt{x}$ | `$\sqrt{x}$` |
| $\int_0^1 x\,dx$ | `$\int_0^1 x\,dx$` |
| $\sum_{i=1}^n i$ | `$\sum_{i=1}^n i$` |
| $\alpha, \beta$ | `$\alpha, \beta$` |
| Matrice | `$\begin{pmatrix} a & b \\\\ c & d \end{pmatrix}$` |

**Inline** : `$...$`
**Displayed** : `$$...$$` (centré, grand)

### Modifier une question

- ⚠️ **Attention** : modifier une question affecte les **nouveaux passages**
- Les passages déjà soumis conservent leur version (intégrité)
- Pour tracker les changements, utiliser `version` ou les tags

### Bonnes pratiques questions

✅ **DO** :
- Formuler l'énoncé clairement, sans ambiguïté
- Proposer des **distracteurs plausibles** (erreurs classiques)
- Ajouter **toujours une explication** pour la correction
- Calibrer la difficulté après quelques passages
- Utiliser des **tags** pour retrouver plus tard

❌ **DON'T** :
- Trop long (> 3 lignes) → mauvaise lisibilité mobile
- Options très différentes en longueur (indice pour deviner)
- "Aucune des réponses précédentes" (démotivant)
- Distracteurs évidents type "1000000000" vs "3"

---

## 4. Publication et distribution

### Publier l'examen

Depuis la liste de vos examens, click sur le brouillon :

```
[Contrôle Vecteurs L1]  status: draft
  [Éditer] [Preview] [Publier] [Supprimer]
```

Click **Publier** → confirmation → status passe à **`published`**.

### Récupérer le code d'accès

Après publication, un **code d'accès** est généré :

```
┌──────────────────────────────────────┐
│  📤 Examen publié !                  │
├──────────────────────────────────────┤
│  Code d'accès : ABC23K-9P            │
│  URL directe : /etudiant/passage.html?code=ABC23K-9P │
│  [📋 Copier code] [📋 Copier URL]    │
└──────────────────────────────────────┘
```

### Distribuer aux étudiants

Plusieurs méthodes :

#### Méthode 1 : Partage du code
```
Email/Slack aux étudiants :

Bonjour,
Le contrôle de vecteurs se déroule aujourd'hui de 14h à 16h.
URL : https://examens.ipssi.fr/etudiant/passage.html
Code d'accès : ABC23K-9P
Durée : 60 minutes
```

#### Méthode 2 : URL directe (prérempli)
```
https://examens.ipssi.fr/etudiant/passage.html?code=ABC23K-9P
```

#### Méthode 3 : QR code (à générer côté prof)
```bash
# Avec qrencode (à installer)
echo "https://examens.ipssi.fr/etudiant/passage.html?code=ABC23K-9P" | qrencode -o qr.png
```

### Fenêtre d'ouverture

Même si publié, les étudiants ne peuvent passer l'examen **qu'entre `date_ouverture` et `date_cloture`**.

Avant `date_ouverture` :
```
⏳ L'examen n'est pas encore ouvert.
Ouverture : 25/04/2026 à 08:00
```

Après `date_cloture` :
```
🔒 Cet examen est clôturé.
Fermé depuis : 25/04/2026 à 18:00
```

---

## 5. Suivi en temps réel

### Dashboard examen

Depuis **Mes examens** → click sur l'examen → onglet **Suivi** :

```
╔═══════════════════════════════════════════╗
║  📊 Contrôle Vecteurs L1  [published]     ║
╠═══════════════════════════════════════════╣
║  📝 Passages : 23 (en cours: 5)          ║
║  🎯 Moyenne actuelle : 71.2%             ║
║  ⏱️ Durée moyenne : 38 min                ║
║  🔒 Anomalies détectées : 2              ║
╚═══════════════════════════════════════════╝
```

### Liste des passages en cours

Table temps réel (auto-refresh 30s) :

| Étudiant | Début | Durée | Status | Score |
|---|---|---|:-:|:-:|
| Dupont Alice | 14:02 | 23 min | 🟢 En cours | — |
| Martin Bob | 14:05 | 8 min | 🟢 En cours | — |
| Bernard Chloé | 13:58 | 45 min | ✅ Soumis | 85% |
| Petit David | 14:00 | 52 min | ⏰ Expiré | 40% |
| Durand Emma | 14:10 | 15 min | 🚫 Invalidé | — |

**Statuts** :
- 🟢 **En cours** : étudiant actif
- ✅ **Soumis** : rendu correctement
- ⏰ **Expiré** : durée dépassée, soumission auto
- 🚫 **Invalidé** : fraude détectée ou manuel

### Anomalies détectées

Chaque événement de type "anti-triche" est loggé :

```
⚠️  Anomalies pour Durand Emma :
   [14:15] copy (Ctrl+C détecté)
   [14:16] paste (Ctrl+V détecté)
   [14:17] devtools_open (F12 détecté)
   [14:20] blur (perdu focus 5.2s)
```

**Actions possibles** :
- **Invalidé automatiquement** si seuil dépassé (configurable)
- **Invalidé manuellement** : bouton 🚫 sur ligne du passage
- Les passages invalidés ne comptent pas dans les stats

---

## 6. Analyse des résultats

### Dashboard analytics

Après quelques passages, `/admin/analytics.html` :

**Vue d'ensemble (tous vos examens)** :
```
┌─────────────────────────────────────────┐
│  📊 Mes analytics                        │
├─────────────────────────────────────────┤
│  📚 Total examens : 12                  │
│  📝 Total passages : 342                │
│  🎯 Moyenne globale : 68.3%             │
│  👥 42 étudiants uniques                │
└─────────────────────────────────────────┘
```

Puis liste cliquable de vos examens.

### Détail d'un examen — 3 vues

Click sur un examen → 3 onglets :

#### 📋 Historique (priorité #1)

Table interactive avec :
- **Tri** : par date / nom / score / durée (click sur header)
- **Recherche** : par nom/email
- **Filtres** :
  - Status (submitted / expired / invalidated)
  - Plage score (min / max)
  - ☑ Anomalies uniquement
- **Pagination** : 20 par page
- **Click ligne** → détail du passage

#### 📊 Graphiques (5 charts)

1. **Distribution scores** (BarChart)
   - Histogramme 10 buckets
   - Gradient rouge → vert
   - Identifier le mode (majorité des élèves)

2. **Mentions** (Donut)
   - Excellent ≥90%, TB 80-89%, B 70-79%, AB 60-69%, Passable 50-59%, Insuf <50%
   - Pourcentages affichés

3. **Taux par question** (BarChart horizontal)
   - Trié par difficulté croissante (plus dures en haut)
   - Rouge < 50%, orange 50-75%, vert ≥ 75%
   - **Click sur une barre** → détail distracteurs

4. **Distracteurs** (sélection question)
   - 4 barres A/B/C/D
   - Verte = correcte, rouge = piège efficace
   - Identifier quelles options piègent le plus

5. **Timeline** (LineChart)
   - Passages par heure + score moyen
   - Voir les pics d'activité

#### 🎯 Distracteurs (analyse fine)

Vue dédiée question par question :

```
┌──────────────────────────────────────────┐
│  ❓ Question 3 (vec-faci-03)             │
│  Difficulté : moyen · Taux : 42%        │
│                                          │
│  Énoncé : $\vec{u} \cdot \vec{v} = ?$    │
│                                          │
│  A: "1 × 3 + 2 × 4" ─────── 48%         │
│  B: "11" ─────────────── 42% ✓ CORRECT  │
│  C: "(3, 8)" ───── 8%                   │
│  D: "Aucune" ─── 2%                     │
│                                          │
│  💡 Diagnostic :                         │
│  Le distracteur A pose problème (48%).  │
│  Les élèves confondent calcul et        │
│  résultat final.                         │
└──────────────────────────────────────────┘
```

**Filtres** :
- ☑ Problématiques uniquement (<50% réussite)
- Tri : difficulté / success rate / total

### Vue d'un étudiant

Click sur un nom dans la liste → page dédiée :

```
╔════════════════════════════════════════╗
║  👤 Dupont Alice                       ║
║  📧 alice@ipssi-etudiant.fr            ║
╠════════════════════════════════════════╣
║  📝 Passages : 8                       ║
║  🎯 Moyenne : 72.5%                   ║
║  🏆 Meilleur : 95%                     ║
║  📉 Pire : 42%                         ║
║  ⏱️ Temps total : 4h 23min              ║
║  🔒 Anomalies : 0                      ║
╠════════════════════════════════════════╣
║  📈 Évolution (LineChart)              ║
║                                        ║
║  💡 Diagnostic :                       ║
║  ⭐ Excellente élève (moyenne > 70%)   ║
╚════════════════════════════════════════╝

📋 Historique détaillé
[Date | Examen | Score | Durée | Status]
...
```

### API Analytics (pour usage avancé)

```bash
# Vue d'ensemble de mes examens
curl http://localhost:8765/api/analytics/prof/overview \
  --cookie "PHPSESSID=<session>"

# Détail examen
curl http://localhost:8765/api/analytics/examen/EXM-XXXX-YYYY/overview \
  --cookie "PHPSESSID=<session>"

# Historique étudiant
curl http://localhost:8765/api/analytics/student/alice@test.fr \
  --cookie "PHPSESSID=<session>"
```

---

## 7. Clôture et archivage

### Clôturer (avant date_cloture)

Si besoin de fermer un examen **avant** la date configurée :

Depuis **Mes examens** → **Clôturer** → confirmation

Effet :
- Status : `published` → `closed`
- Plus de nouveaux passages acceptés
- Les passages **en cours sont terminés** (soumission auto)
- Le code d'accès **ne fonctionne plus**
- Les analytics restent accessibles

### Archivage

Après clôture, vous pouvez archiver pour désencombrer la liste :

**Mes examens** → filtre "Archivés" → **Archiver**

Effet :
- Status : `closed` → `archived`
- Masqué de la liste principale (visible dans filtre "Archives")
- Analytics toujours accessibles
- Données conservées

### Suppression définitive

⚠️ **Pas recommandé** sauf cas exceptionnel.

Seuls les examens **sans passage** peuvent être supprimés.

---

## 8. Exports

### Depuis les analytics

3 formats disponibles sur chaque vue (Historique, Étudiant, Distracteurs) :

```
📤 Exporter ▼
├── 📄 CSV                 ← Données brutes
├── 📊 Excel (.xlsx)       ← Multi-feuilles
└── 📑 PDF (impression)    ← Mise en page A4
```

#### 📄 CSV
- UTF-8 avec BOM (compatible Excel Windows)
- Colonnes selon la vue :
  - **Historique** : Date, Étudiant, Email, Score, Durée, Status, Anomalies
  - **Étudiant** : historique multi-examens
  - **Distracteurs** : Q + option_analysis

#### 📊 Excel (.xlsx)
- Multi-feuilles :
  - "Passages" (historique complet)
  - "Questions" (stats par Q)
  - "Resume" (KPIs)
- Headers en **gras**
- Colonnes auto-width

#### 📑 PDF (via impression)
- Impression navigateur (Ctrl+P)
- Format A4 portrait
- En-tête : titre examen + IPSSI branding
- Masque boutons/filtres (print-only CSS)

### Exports pour admin / transmission

**Bulletin de notes** à transmettre à la scolarité :

```bash
# Via API
curl -X GET "http://localhost:8765/api/analytics/examen/EXM-XXXX-YYYY/passages?limit=500" \
  --cookie "PHPSESSID=<session>" \
  | jq '.data.passages[] | {
      nom: .student_info.nom,
      prenom: .student_info.prenom,
      email: .student_info.email,
      score: .score_pct,
      date: .start_time
    }' \
  > bulletin.json
```

---

## 9. Scénarios complets

### Scénario A — Contrôle classique en salle

**Contexte** : 30 étudiants, contrôle 1h sur vecteurs.

1. **J-3** : Créer l'examen
   - Titre : "Contrôle vecteurs L1 - 25/04/2026"
   - 20 questions mix difficulté
   - Durée 60 min
   - Ouverture 25/04 14:00, clôture 25/04 16:00
   - Max passages : 1

2. **J-1** : Publier + envoyer le code aux étudiants par email
   ```
   Objet : Contrôle vecteurs - demain
   
   Bonjour,
   Contrôle demain 25/04 à 14h.
   URL : https://examens.ipssi.fr/etudiant/passage.html
   Code : ABC23K-9P
   Durée : 60 min. Connectez-vous 5 min avant.
   ```

3. **Jour J** :
   - 13:55 : rejoindre le dashboard `/admin/analytics.html`
   - 14:00-15:00 : suivi temps réel des passages
   - 15:00 : clôture auto (date_cloture)
   - Consulter résultats

4. **J+1** : Analyser
   - Voir distribution scores
   - Identifier questions problématiques (taux < 50%)
   - Exporter bulletin en Excel pour scolarité

### Scénario B — Quiz formatif hebdomadaire

**Contexte** : quiz auto-corrigé en ligne, 2 tentatives autorisées pour progresser.

1. Créer l'examen
   - Durée 20 min
   - **Max passages : 2**
   - Ouverture lundi 8h, clôture vendredi 23h59
   - ⚠️ **Désactiver** "Show correction after" (sinon ils peuvent tricher au 2e essai)
   - Ou **correction_delay_min : 10080** (7 jours → correction à la fermeture)

2. Publier — pas besoin de communication spéciale, lien permanent sur plateforme cours.

3. Consulter analytics en fin de semaine pour adapter le cours suivant.

### Scénario C — Examen avec shuffle intensif (anti-triche)

**Contexte** : 50 étudiants dans même salle, risque copiage.

1. Créer avec :
   - ☑ Shuffle questions
   - ☑ Shuffle options
   - Banque **large** (40 questions) pour tirer **20 au hasard** par étudiant

2. Activer anti-triche client :
   - Détection copy/paste
   - Détection devtools (F12)
   - Détection perte de focus (changement onglet)
   - Chaque anomalie loggée

3. Paramétrer invalidation auto :
   - Si > 3 anomalies bloquantes → `invalidated`

4. Review manuelle après :
   - Analytics → filter "anomalies_only"
   - Décider au cas par cas (faux positif possible)

### Scénario D — Évaluation diagnostique début d'année

**Contexte** : tester le niveau des nouveaux étudiants.

1. Créer examen 40 questions très variées :
   - 50% facile (prérequis L1)
   - 30% moyen (bases solides)
   - 20% difficile (capacité à raisonner)

2. Durée 90 min, 1 seul passage.

3. Analytics → identifier :
   - **Groupe fort** (>80%) : pourront suivre programme normal
   - **Groupe moyen** (50-80%) : programme standard avec révisions
   - **Groupe faible** (<50%) : aide personnalisée

4. Exporter liste par groupe pour équipe pédagogique.

---

## 10. Astuces et bonnes pratiques

### Avant la création

- 📋 **Objectifs clairs** : quel niveau de compétence évaluer ?
- 📚 **Équilibre** : mixer difficultés pour éviter note plafonnée ou plancher
- ⏱️ **Durée réaliste** : 2 min/question pour du calcul, 3 min pour réflexion

### Pendant la création

- 🎯 Utiliser la **preview** systématiquement
- ✅ Tester vous-même l'examen avec vos propres identifiants étudiants (créer un compte test)
- 📝 Remplir **toutes les explications** (critique pour la correction)

### Pendant le passage

- 👀 Garder `/admin/analytics.html` ouvert (auto-refresh 30s)
- 💬 Répondre aux messages d'étudiants en difficulté technique (pas sur le contenu)
- 🔒 Ne pas paniquer sur les anomalies isolées (faux positifs courants)

### Après le passage

- 📊 Analyser **avant** de communiquer les notes
- 🎯 Si question très mal réussie (<30%) : la **reformuler** ou la retirer de la banque
- 💡 Utiliser distracteurs efficaces pour créer des quiz de révision

### Sauvegardes

- 💾 Exporter vos examens importants en **Excel** pour archivage hors plateforme
- 📤 Noter les **IDs** des examens importants : `EXM-ABCD-1234`
- 🔄 Demander à l'admin de faire un backup avant modifications majeures

### Accessibilité

- 🔡 Éviter les couleurs comme seul moyen d'info (daltoniens)
- 📖 Énoncés clairs, syntaxe simple
- 📱 Tester sur mobile (certains étudiants passent sur téléphone)

---

## 📞 Support

- **Pour questions pédagogiques** : équipe pédagogique IPSSI
- **Pour questions techniques** : admin de la plateforme
- **Pour bugs** : m.elafrit@ecole-ipssi.net

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
