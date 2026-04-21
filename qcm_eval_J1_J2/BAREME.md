# Barème d'évaluation — QCM Jours 1 et 2

> © 2025 Mohamed EL AFRIT — IPSSI | Licence CC BY-NC-SA 4.0

---

## 🎯 Principe général

Le QCM comporte **50 questions à choix unique** (4 propositions, 1 seule bonne réponse par question). Chaque question est pondérée en fonction de sa **difficulté intrinsèque** (invisible pour l'étudiant). Aucun malus n'est appliqué pour une mauvaise réponse ou une absence de réponse.

---

## 🏷️ Système de pondération

| Difficulté (interne) | Points par bonne réponse | Description |
|:---:|:---:|---|
| 🟢 **Facile** | **1 pt** | Restitution directe, définition, calcul immédiat |
| 🟡 **Moyen** | **2 pts** | Application d'une formule, lecture de code, calcul en 2 étapes |
| 🟠 **Difficile** | **3 pts** | Raisonnement, analyse, mise en relation de concepts |
| 🔴 **Expert** | **4 pts** | Synthèse, cas-limite, détection d'erreur conceptuelle |

---

## 📊 Répartition des 50 questions

### Par jour et par difficulté

| Jour | 🟢 Facile | 🟡 Moyen | 🟠 Difficile | 🔴 Expert | Total |
|:---:|:---:|:---:|:---:|:---:|:---:|
| **Jour 1** | 10 | 10 | 4 | 1 | **25** |
| **Jour 2** | 10 | 10 | 4 | 1 | **25** |
| **Total**  | 20 | 20 | 8 | 2 | **50** |

### Par type de question (sur les 50)

| Type | Nombre | Part |
|---|:---:|:---:|
| Calcul numérique sur le fil rouge | 20 | 40 % |
| Lecture/interprétation de code Python | 12 | 24 % |
| Conceptuel (compréhension) | 12 | 24 % |
| Reconnaissance de formule | 6 | 12 % |

---

## 🧮 Calcul de la note

### Points bruts maximum

Pour **un jour** :

$$
10 \times 1 + 10 \times 2 + 4 \times 3 + 1 \times 4 = 10 + 20 + 12 + 4 = 46 \text{ points bruts}
$$

Pour **les deux jours** :

$$
P_{\max} = 2 \times 46 = 92 \text{ points bruts}
$$

### Conversion sur 20

$$
\text{Note}_{/20} = \frac{P_{\text{obtenus}}}{92} \times 20
$$

### Exemples de calcul

| Points obtenus | Calcul | Note /20 |
|:---:|:---|:---:|
| 92 | 92 × 20 ÷ 92 | **20,00** |
| 83 | 83 × 20 ÷ 92 | **18,04** |
| 74 | 74 × 20 ÷ 92 | **16,09** |
| 65 | 65 × 20 ÷ 92 | **14,13** |
| 55 | 55 × 20 ÷ 92 | **11,96** |
| 46 | 46 × 20 ÷ 92 | **10,00** |
| 37 | 37 × 20 ÷ 92 | **8,04** |
| 23 | 23 × 20 ÷ 92 | **5,00** |
| 0  | 0 × 20 ÷ 92  | **0,00** |

**Note finale arrondie à deux décimales.**

---

## 📈 Scénarios-type

### Scénario « Passable » (10/20)

Obtenir ~46 points bruts peut correspondre à :

- 🟢 20 bonnes / 20 = 20 pts
- 🟡 10 bonnes / 20 = 20 pts
- 🟠 2 bonnes / 8 = 6 pts
- 🔴 0 bonne / 2 = 0 pts

→ **46 pts bruts → 10,00 / 20**

### Scénario « Excellent » (17/20)

- 🟢 20 / 20 = 20 pts
- 🟡 18 / 20 = 36 pts
- 🟠 6 / 8 = 18 pts
- 🔴 1 / 2 = 4 pts

→ **78 pts bruts → 16,96 / 20**

### Scénario « Maîtrise parfaite » (20/20)

Toutes les questions justes : **92 pts bruts → 20,00 / 20**

---

## ⚖️ Règles complémentaires

1. **Aucun malus** : une mauvaise réponse vaut 0 point (comme une non-réponse). L'étudiant est donc **encouragé à répondre à toutes les questions**, même par élimination.
2. **Choix unique obligatoire** : on ne peut sélectionner qu'une proposition par question. La validation se fait au clic sur le bouton « Soumettre mes réponses ».
3. **Navigation libre** : l'étudiant peut revenir en arrière et modifier ses réponses tant qu'il n'a pas soumis.
4. **Randomisation** : l'ordre des questions et l'ordre des 4 propositions (A/B/C/D) sont **mélangés pour chaque étudiant**. Deux étudiants voisins n'ont donc pas le même ordre.
5. **Chronomètre indicatif** : le temps passé est affiché et sauvegardé dans le CSV, mais il n'y a **aucun blocage automatique**.
6. **Signature anti-fraude** : tout CSV modifié manuellement est détecté par vérification SHA-256 dans le dashboard enseignant (badge ❌).

---

## 🧾 Affichage du barème

- **Page étudiant (avant le QCM)** : encadré 💡 visible sur l'écran d'accueil, expliquant :
  - le principe de la pondération par difficulté ;
  - le fait que la difficulté n'est **pas** visible par question ;
  - l'absence de malus ;
  - la conversion finale sur 20.
- **Page correction personnelle** : rappel en tête du rapport de correction, avec :
  - les points obtenus (bruts) ;
  - la note convertie sur 20 ;
  - le détail par question avec la difficulté révélée (🟢🟡🟠🔴) et les points obtenus.

---

## 📬 Contact

Pour toute question sur ce barème : `m.elafrit@ecole-ipssi.net`
