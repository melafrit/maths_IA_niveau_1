# 🎨 Documentation visuelle — Schémas & Diagrammes

> Tout ce qu'il faut pour générer et utiliser les **schémas d'architecture** et
> **diagrammes UML** de la plateforme IPSSI Examens.

---

## 📂 Contenu du dossier

| Fichier | Description | Usage |
|---|---|---|
| [`PROMPTS_CHATGPT.md`](./PROMPTS_CHATGPT.md) | **20 prompts optimisés** pour ChatGPT (+ bonus) | Générer vos propres diagrammes |
| [`EXEMPLES_RENDUS.md`](./EXEMPLES_RENDUS.md) | **10 exemples pré-générés** prêts à l'emploi | Copier-coller direct |
| `README.md` *(ce fichier)* | Index + guide d'utilisation | Commencer ici |

---

## 🚀 Quick start

### Option A : Je veux un diagramme tout de suite

👉 **Ouvrir [`EXEMPLES_RENDUS.md`](./EXEMPLES_RENDUS.md)**, copier un exemple, coller dans https://mermaid.live/

### Option B : Je veux personnaliser avec ChatGPT

👉 **Ouvrir [`PROMPTS_CHATGPT.md`](./PROMPTS_CHATGPT.md)**, choisir un prompt, copier-coller dans ChatGPT

### Option C : Je veux tous les diagrammes pour ma doc

👉 Suivre le **workflow complet** ci-dessous

---

## 📋 Workflow complet de génération

### 1. Choisir les diagrammes nécessaires

Pour une documentation **minimale** (recommandé) :

- ✅ Architecture globale (Prompt #1)
- ✅ Diagramme de classes (Prompt #9)
- ✅ Séquence passage étudiant (Prompt #5 ou #11)
- ✅ Use cases (Prompt #15)
- ✅ États examen + passage (Prompts #13 et #14)

Pour une documentation **complète** :

- Tous les 20 prompts du fichier `PROMPTS_CHATGPT.md`

### 2. Générer via ChatGPT

```
1. Ouvrir https://chat.openai.com/
2. Nouveau chat avec GPT-4 ou GPT-4o
3. Ouvrir PROMPTS_CHATGPT.md
4. Copier le bloc de CONTEXTE (section en haut)
5. Coller dans ChatGPT
6. Envoyer ET ATTENDRE (ChatGPT acquiesce)
7. Copier un prompt (ex: Prompt #1)
8. Coller dans ChatGPT
9. ChatGPT génère le code Mermaid/PlantUML
10. Copier le code généré
```

### 3. Rendre le diagramme

**Pour Mermaid** :
1. https://mermaid.live/
2. Coller le code
3. Vérifier le rendu à droite
4. Actions → Download PNG/SVG

**Pour PlantUML** :
1. http://www.plantuml.com/plantuml/
2. Coller le code
3. Rendu automatique
4. Clic droit image → Enregistrer

### 4. Intégrer dans votre doc

**Option 1 - Markdown avec code** (recommandé pour GitHub) :

````markdown
## Architecture

```mermaid
flowchart LR
    A --> B
```
````

**Option 2 - Images exportées** :

```markdown
## Architecture

![Architecture globale](./images/architecture.png)
```

**Option 3 - Fichiers sources** :

Sauvegarder dans `examens/docs/diagrammes/sources/` :
- `01_architecture.mmd` (Mermaid)
- `09_classes.puml` (PlantUML)

---

## 📊 Pack de prompts disponibles

### 🏛️ Architecture (#1-8)

| # | Nom | Format | Niveau détail |
|:-:|---|---|---|
| 1 | Architecture globale | Mermaid | ⭐ Simple |
| 2 | Architecture en couches | Mermaid | ⭐⭐ Moyen |
| 3 | Déploiement OVH | Mermaid | ⭐⭐⭐ Détaillé |
| 4 | Flux création examen | Mermaid seq | ⭐⭐ |
| 5 | Flux passage étudiant | Mermaid seq | ⭐⭐ |
| 6 | Flux analytics | Mermaid | ⭐⭐ |
| 7 | Sécurité 6 couches | Mermaid | ⭐⭐ |
| 8 | Topologie réseau | Mermaid | ⭐⭐⭐ |

### 📐 UML (#9-15)

| # | Nom | Format | Niveau détail |
|:-:|---|---|---|
| 9 | Classes complètes | PlantUML | ⭐⭐⭐ |
| 10 | Classes managers | PlantUML | ⭐⭐⭐ |
| 11 | Séquence passage UML | PlantUML | ⭐⭐⭐ |
| 12 | Séquence auth | PlantUML | ⭐⭐⭐ |
| 13 | État examen | PlantUML | ⭐⭐ |
| 14 | État passage | PlantUML | ⭐⭐ |
| 15 | Use cases | PlantUML | ⭐⭐ |

### 🎁 Bonus (#16-20)

| # | Nom | Format | Niveau détail |
|:-:|---|---|---|
| 16 | ERD données | Mermaid | ⭐⭐ |
| 17 | Composants React | PlantUML | ⭐⭐⭐ |
| 18 | CI/CD pipeline | Mermaid | ⭐⭐ |
| 19 | Permissions | Tableau + Mermaid | ⭐ |
| 20 | Activity bout-en-bout | PlantUML | ⭐⭐⭐ |

---

## 🎯 Recommandations par cas d'usage

### Documentation interne développeurs

**Priorité** : #9 (classes), #10 (managers), #11 (séquence UML), #18 (CI/CD)

### Présentation commerciale / hiérarchie

**Priorité** : #1 (globale), #3 (déploiement), #15 (use cases simplifié)

### Audit sécurité

**Priorité** : #7 (6 couches), #12 (auth), #8 (réseau), #19 (permissions)

### Onboarding nouveau prof

**Priorité** : #15 (use cases), #4 (création examen), #6 (analytics)

### Documentation complète 360°

**Priorité** : Les 20 prompts, par ordre logique

---

## 🛠️ Outils utiles

### Éditeurs en ligne (gratuits, pas de compte)

- **[Mermaid Live Editor](https://mermaid.live/)** - Éditeur Mermaid avec preview
- **[PlantUML Online](http://www.plantuml.com/plantuml/)** - Éditeur PlantUML
- **[Draw.io](https://app.diagrams.net/)** - Éditeur graphique (import Mermaid)
- **[Excalidraw](https://excalidraw.com/)** - Style main levée, import Mermaid natif
- **[Graphviz Online](https://dreampuf.github.io/GraphvizOnline/)** - Pour DOT

### Extensions VS Code

- **Mermaid Preview** (Matt Bierner)
- **PlantUML** (jebbs)
- **Mermaid Markdown Syntax Highlighting**

### CLI

```bash
# PlantUML local
sudo apt install plantuml  # Linux
brew install plantuml      # macOS

plantuml diagram.puml      # Génère diagram.png

# Mermaid CLI
npm install -g @mermaid-js/mermaid-cli

mmdc -i input.mmd -o output.png
```

---

## 📝 Bonnes pratiques

### 1. Versionner les sources
Sauvegarder les fichiers `.mmd` et `.puml` dans le repo :
```
examens/docs/diagrammes/sources/
├── 01_architecture.mmd
├── 09_classes.puml
└── ...
```

### 2. Régénérer périodiquement
Quand le code évolue, régénérer les diagrammes en modifiant le prompt avec les nouveautés.

### 3. Intégrer dans les guides existants
Insérer des diagrammes dans :
- `ARCHITECTURE.md` → Prompt #1, #2, #9
- `GUIDE_ADMIN.md` → Prompt #3, #15, #19
- `GUIDE_PROFESSEUR.md` → Prompt #4, #20
- `GUIDE_ETUDIANT.md` → Prompt #5 (simplifié)
- `DEPLOIEMENT_OVH.md` → Prompt #3, #8

### 4. Noms cohérents
Utiliser une convention :
- `01_architecture_globale.mmd`
- `02_architecture_couches.mmd`
- etc.

### 5. Compacter quand possible
Un bon diagramme tient sur **un seul écran** (ou une page A4).
Si ChatGPT produit un diagramme trop grand, demander une version "simplifiée" ou découpée en plusieurs.

---

## ❓ FAQ

### 💬 Quel modèle LLM utiliser ?

| Modèle | Recommandation |
|---|---|
| **GPT-4** | ⭐⭐⭐⭐⭐ Excellent |
| **GPT-4o** | ⭐⭐⭐⭐ Très bon, plus rapide |
| **GPT-5** | ⭐⭐⭐⭐⭐ Le meilleur (si disponible) |
| **Claude Sonnet** | ⭐⭐⭐⭐⭐ Excellent, parfois meilleur |
| **Claude Opus** | ⭐⭐⭐⭐⭐ Le plus détaillé |
| **Gemini** | ⭐⭐⭐ Correct |

### 💬 Si ChatGPT génère un diagramme invalide ?

1. Copier le message d'erreur de mermaid.live / plantuml.com
2. Revenir dans ChatGPT
3. Dire : "Le diagramme génère cette erreur : [erreur]. Corrige la syntaxe."
4. ChatGPT corrige généralement du premier coup

### 💬 Comment obtenir des diagrammes plus détaillés ?

Ajouter à la fin du prompt :
- `"Sois TRÈS détaillé, minimum 30 éléments"`
- `"Inclus TOUS les composants mentionnés dans le contexte"`
- `"N'hésite pas à utiliser des sous-graphs imbriqués"`

### 💬 Comment simplifier un diagramme trop chargé ?

Demander à ChatGPT :
- `"Simplifie ce diagramme à 10 éléments maximum"`
- `"Regroupe les composants similaires"`
- `"Fais une version 'vue oiseau' pour présentation"`

### 💬 Les diagrammes Mermaid ne s'affichent pas sur GitHub ?

- Mermaid nécessite **```mermaid** comme langage du code block
- Vérifier la syntaxe sur mermaid.live avant commit
- Certaines vieilles versions GitHub Enterprise ne supportent pas Mermaid

---

## 🎓 Exemples de cas concrets

### "Je veux une slide pour présenter la plateforme au Directeur IPSSI"

1. Prompt #1 (Architecture globale) → slide 1
2. Prompt #15 (Use cases) → slide 2
3. Prompt #3 (Déploiement OVH) → slide 3
4. Prompt #19 (Permissions) → slide 4

**Export** : PNG depuis mermaid.live, insertion dans PowerPoint/Keynote.

### "Un nouveau développeur rejoint le projet"

1. Lire `ARCHITECTURE.md`
2. Regarder Prompt #9 (classes complètes) rendu
3. Regarder Prompt #2 (architecture en couches)
4. Regarder Prompt #11 (séquence passage UML)

Temps d'onboarding : **1h** au lieu de 1 journée.

### "Audit sécurité externe"

1. Générer Prompt #7 (6 couches sécurité)
2. Générer Prompt #8 (topologie réseau)
3. Générer Prompt #12 (auth)
4. Générer Prompt #19 (permissions)
5. Fournir avec `docs/DEPLOIEMENT_OVH.md`

---

## 📞 Support

Pour questions sur l'utilisation des prompts :
- **Email** : m.elafrit@ecole-ipssi.net
- **Issues** : https://github.com/melafrit/maths_IA_niveau_1/issues

---

## 📜 Licence

Tous les prompts et exemples : **CC BY-NC-SA 4.0**

© 2026 Mohamed EL AFRIT — IPSSI

Vous êtes libres de :
- ✅ Utiliser pour vos propres projets (non commerciaux)
- ✅ Adapter et modifier
- ✅ Partager avec attribution

Vous devez :
- ⚠️ Citer l'auteur
- ⚠️ Partager sous la même licence
- ❌ Pas d'usage commercial sans accord
