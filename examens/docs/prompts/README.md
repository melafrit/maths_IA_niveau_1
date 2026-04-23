# 🎨 Bibliothèque de prompts — IPSSI Examens

> Collection de prompts optimisés pour générer des schémas d'architecture et diagrammes UML de la plateforme IPSSI Examens avec différents outils d'IA.

**Version** : 1.0.0 · **Licence** : CC BY-NC-SA 4.0

---

## 📖 Qu'est-ce que ce dossier ?

Ce dossier contient **12 prompts professionnels** organisés en 3 catégories pour générer la documentation visuelle complète de la plateforme IPSSI Examens.

Chaque prompt :
- Est **prêt-à-copier-coller** dans l'IA de votre choix
- Contient **plusieurs versions optimisées** selon l'outil (ChatGPT, Gemini, Claude, etc.)
- Produit du code Mermaid/PlantUML ou des descriptions visuelles
- Inclut un **contexte de description** et des **conseils d'usage**

---

## 🗂️ Index des prompts

### 🏛️ Catégorie 1 — Schémas d'architecture

| # | Fichier | Diagramme | Format principal |
|:-:|---|---|---|
| 01 | [01_architecture_globale.md](./01_architecture_globale.md) | Architecture globale haut niveau | Mermaid flowchart |
| 02 | [02_architecture_couches.md](./02_architecture_couches.md) | Architecture en couches (5 layers) | Mermaid flowchart |
| 03 | [03_deploiement_ovh.md](./03_deploiement_ovh.md) | Infrastructure OVH (mutualisé + VPS) | Mermaid flowchart |
| 04 | [04_flux_middleware.md](./04_flux_middleware.md) | Flux HTTP + middlewares | Mermaid flowchart |

### 📐 Catégorie 2 — Diagrammes UML structurels

| # | Fichier | Diagramme | Format principal |
|:-:|---|---|---|
| 05 | [05_uml_classes.md](./05_uml_classes.md) | Classes (17 managers PHP) | Mermaid classDiagram |
| 06 | [06_uml_composants.md](./06_uml_composants.md) | Composants (30 components) | Mermaid + PlantUML |
| 07 | [07_uml_packages.md](./07_uml_packages.md) | Packages/namespaces | Mermaid |
| 08 | [08_uml_deploiement.md](./08_uml_deploiement.md) | Déploiement physique | Mermaid + PlantUML |

### 🔄 Catégorie 3 — Diagrammes UML comportementaux

| # | Fichier | Diagramme | Format principal |
|:-:|---|---|---|
| 09 | [09_uml_cas_utilisation.md](./09_uml_cas_utilisation.md) | Use cases (28 UC, 5 acteurs) | PlantUML |
| 10 | [10_uml_sequence.md](./10_uml_sequence.md) | Séquence (create + passage) | Mermaid sequenceDiagram |
| 11 | [11_uml_activite.md](./11_uml_activite.md) | Activité (workflow complet) | PlantUML |
| 12 | [12_uml_etats.md](./12_uml_etats.md) | États (examen + passage) | Mermaid stateDiagram |

### 🎨 Bonus

| Fichier | Usage |
|---|---|
| [00_meta_prompt.md](./00_meta_prompt.md) | Méta-prompt à utiliser en début de conversation |
| [99_bonus_visuel_dalle.md](./99_bonus_visuel_dalle.md) | Prompt pour générer image avec DALL-E/MidJourney |

---

## 🤖 Outils IA supportés

Chaque prompt est optimisé pour plusieurs plateformes :

| Outil | Usage optimal | Notes |
|---|---|---|
| **ChatGPT-4** / **GPT-4o** | Mermaid + PlantUML | Meilleure génération de code technique |
| **Claude 3.5/4 Sonnet** | Mermaid + PlantUML | Excellente qualité structurelle |
| **Gemini 2.0 Flash / Pro** | Mermaid | Rapide, contexte large |
| **Gemini NanoBanana** | Schémas visuels | Via Gemini Image Generation |
| **DALL-E 3** (via ChatGPT) | Images visuelles | Schémas isométriques/flat design |
| **Midjourney** | Images visuelles artistiques | V6+ recommandé |

---

## 🛠️ Outils de rendu (à utiliser après génération du code)

| Outil | URL | Formats supportés |
|---|---|---|
| **Mermaid Live Editor** | https://mermaid.live/ | Tous diagrammes Mermaid |
| **PlantText** | https://www.planttext.com/ | Tous diagrammes PlantUML |
| **Kroki** | https://kroki.io/ | Mermaid, PlantUML, D2, etc. (API) |
| **Draw.io** | https://app.diagrams.net/ | Éditer manuellement après import |
| **Excalidraw** | https://excalidraw.com/ | Style "hand-drawn" |
| **tldraw** | https://www.tldraw.com/ | Collaboration temps réel |

---

## 📝 Structure d'un fichier prompt

Chaque fichier suit cette structure type :

```markdown
# 🎯 [Titre du prompt]

## 📖 Description et contexte
- Quel diagramme est généré
- Quand utiliser ce prompt
- Outil recommandé

## 🤖 Outils IA supportés
- Meilleur : ChatGPT
- Alternatives : Claude, Gemini...
- Version optimisée pour chaque

## 📋 Version pour ChatGPT-4 / GPT-4o
[Prompt complet]

## 📋 Version pour Claude (3.5/4 Sonnet, Opus)
[Version adaptée si différente]

## 📋 Version pour Gemini Pro / NanoBanana
[Version adaptée]

## 🎨 Rendu final
- URLs d'outils de rendu
- Tips d'utilisation
```

---

## 🚀 Utilisation rapide

### Scénario A : Je veux UN diagramme spécifique

1. Choisir le fichier souhaité (ex: `05_uml_classes.md`)
2. Identifier votre outil IA (ChatGPT, Claude, Gemini)
3. Copier la **version optimisée** correspondante
4. Coller dans votre IA
5. Copier le code généré
6. Coller sur https://mermaid.live/ ou équivalent
7. Exporter l'image (PNG/SVG)

### Scénario B : Je veux TOUS les diagrammes en série

1. Commencer par [00_meta_prompt.md](./00_meta_prompt.md) — donne le contexte une fois
2. Puis demander un diagramme à la fois
3. L'IA gardera le contexte et produira des diagrammes cohérents

### Scénario C : Je veux une image visuelle stylisée

Utiliser [99_bonus_visuel_dalle.md](./99_bonus_visuel_dalle.md) avec :
- DALL-E 3 (ChatGPT Plus)
- Gemini Image
- Midjourney

---

## 📚 Ressources complémentaires

- **Syntaxe Mermaid** : https://mermaid.js.org/intro/
- **Syntaxe PlantUML** : https://plantuml.com/fr/
- **C4 Model** (architecture) : https://c4model.com/
- **UML 2.5 Reference** : https://www.omg.org/spec/UML/2.5.1/

---

## 💡 Tips avancés

### Enchaîner les prompts dans une même conversation

Commencer par le [meta-prompt](./00_meta_prompt.md), puis demander :
1. "Génère le diagramme d'architecture globale"
2. "Maintenant le diagramme de classes"
3. "Et le diagramme de séquence du passage étudiant"

L'IA garde le contexte entre les messages.

### Itérer et affiner

Si le premier résultat n'est pas optimal :
- "Simplifie ce diagramme en gardant seulement les composants critiques"
- "Ajoute plus de détails sur la sécurité"
- "Produis une version landscape 16:9"

### Combiner outils

- Génère le code avec **ChatGPT/Claude/Gemini**
- Rends visuellement avec **mermaid.live**
- Exporte en SVG pour édition avec **Figma** ou **Illustrator**
- Utilise **DALL-E/Midjourney** pour version marketing

---

## 🎯 Mapping prompt → doc

Ces diagrammes enrichissent naturellement ces sections de la documentation :

| Diagramme | Section doc enrichie |
|---|---|
| Architecture globale | `ARCHITECTURE.md` § 2 |
| Architecture couches | `ARCHITECTURE.md` § 2 |
| Déploiement OVH | `DEPLOIEMENT_OVH.md` § 2-4 |
| Classes | `ARCHITECTURE.md` § 9 |
| Séquence | `ARCHITECTURE.md` § 8 (flux) |
| Use cases | `GUIDE_ADMIN.md`, `GUIDE_PROFESSEUR.md`, `GUIDE_ETUDIANT.md` |
| États | `ARCHITECTURE.md` § 5 (modèle données) |

---

## 🤝 Contribution

Pour ajouter ou améliorer un prompt :
1. Créer/éditer le fichier markdown correspondant
2. Conserver la structure type (contexte, versions, outils)
3. Tester avec au moins 2 IA différentes
4. Documenter les résultats

---

## 📞 Contact

- **Auteur** : Mohamed EL AFRIT
- **Email** : m.elafrit@ecole-ipssi.net
- **Repo** : https://github.com/melafrit/maths_IA_niveau_1

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
