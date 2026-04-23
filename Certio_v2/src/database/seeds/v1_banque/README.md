# 📚 Banque de questions v1 (données source)

> **Source de données pour le seeder de Certio v2.0**  
> **320 questions QCM prêtes à être importées dans la BDD Laravel**

---

## 📊 Contenu

**16 fichiers JSON** organisés par module → chapitre → thème :

```
v1_banque/
└── maths-ia/                          # Module : Mathématiques IA
    ├── j1-representation/             # Jour 1 : Représentation
    │   ├── matrices.json              # 20 questions
    │   ├── produit-scalaire.json      # 20 questions
    │   ├── regression-lineaire.json   # 20 questions
    │   └── vecteurs.json              # 20 questions
    ├── j2-optimisation/               # Jour 2 : Optimisation
    │   ├── derivee-gradient.json      # 20 questions
    │   ├── descente-gradient.json     # 20 questions
    │   ├── fonction-cout.json         # 20 questions
    │   └── taux-apprentissage.json    # 20 questions
    ├── j3-classification/             # Jour 3 : Classification
    │   ├── frontiere-decision.json    # 20 questions
    │   ├── perceptron.json            # 20 questions
    │   ├── probabilites.json          # 20 questions
    │   └── sigmoide-logistique.json   # 20 questions
    └── j4-reseaux/                    # Jour 4 : Réseaux de neurones
        ├── backpropagation.json       # 20 questions
        ├── fonctions-activation.json  # 20 questions
        ├── neurone-artificiel.json    # 20 questions
        └── reseau-multicouche.json    # 20 questions

TOTAL : 320 questions QCM
```

---

## 🔍 Structure d'un fichier JSON

```json
{
  "_meta": {
    "module": "maths-ia",
    "chapitre": "j1-representation",
    "theme": "vecteurs",
    "module_label": "Mathématiques appliquées à l'IA",
    "chapitre_label": "Jour 1 — Représentation",
    "theme_label": "Vecteurs",
    "version": "1.0.0",
    "license": "CC BY-NC-SA 4.0 — Mohamed EL AFRIT — IPSSI",
    "total_questions": 20,
    "difficulty_distribution": {
      "facile": 5,
      "moyen": 5,
      "difficile": 5,
      "expert": 5
    }
  },
  "questions": [
    {
      "id": "vec-faci-01",                    // ID unique stable
      "enonce": "Quelle est la norme...?",    // Énoncé (LaTeX supporté: $...$ et $$...$$)
      "options": ["$5$", "$7$", "$25$", "$\\sqrt{7}$"],  // 4 propositions
      "correct": 0,                            // Index 0-3 de la bonne réponse
      "difficulte": "facile",                  // facile | moyen | difficile | expert
      "type": "conceptuel",                    // conceptuel | calcul | code | formule
      "tags": ["norme", "euclidien"],          // Tags libres
      "hint": "Rappel : $\\|\\mathbf{x}\\|...$",  // Indice pédagogique
      "explanation": "La norme euclidienne...",   // Explication détaillée
      "traps": "Erreur classique : oublier...",   // Pièges à éviter
      "references": "Cours J1, section 1.2"     // Références cours
    }
  ]
}
```

---

## 🎯 Utilisation dans Certio v2.0 (Phase P1)

Cette banque sera importée via l'Artisan command `certio:migrate-from-v1` qui transformera chaque question v1 en row Eloquent v2 :

### Mapping v1 → v2

| Champ v1 | Champ v2 (Question model) | Transformation |
|---|---|---|
| `id` (string) | `legacy_id` (string) + nouveau `uuid` | Conservation pour traçabilité |
| `enonce` | `statement` | Direct |
| `options` (array strings) | `options` (array objects) | Transformation vers format v2 |
| `correct` (index) | `options[N].is_correct = true` | Marquer la bonne option |
| `difficulte` | `difficulty` | Mapping enum |
| `type` | `type` + `subtype_config` | Tous sont `mcq_single_4` |
| `tags` | `tags` | Direct (JSON) |
| `hint` | `hint` | Direct |
| `explanation` | `explanation` | Direct |
| `traps` | Conserver comme complément explanation | Concaténer ou champ dédié |
| `references` | `reference` (JSON) | Extraire module/chapitre |
| `_meta.module` | `module` | Direct |
| `_meta.chapitre` | `chapitre` | Direct |
| `_meta.theme` | `theme` | Direct |

### Exemple de transformation (v1 → v2)

**v1 (format source)** :
```json
{
  "id": "vec-faci-01",
  "enonce": "Quelle est la norme de $(3,4)$ ?",
  "options": ["$5$", "$7$", "$25$", "$\\sqrt{7}$"],
  "correct": 0,
  "difficulte": "facile",
  "type": "conceptuel",
  "tags": ["norme"],
  "hint": "Rappel...",
  "explanation": "La norme...",
  "traps": "Erreur...",
  "references": "Cours J1, section 1.2"
}
```

**v2 (format cible Laravel)** :
```json
{
  "uuid": "QST-XXXX-YYYY",
  "legacy_id": "vec-faci-01",
  "workspace_id": 1,
  "type": "mcq_single_4",
  "statement": "Quelle est la norme de $(3,4)$ ?",
  "options": [
    {"id": "A", "text": "$5$", "is_correct": true, "why_wrong": null},
    {"id": "B", "text": "$7$", "is_correct": false, "why_wrong": null},
    {"id": "C", "text": "$25$", "is_correct": false, "why_wrong": null},
    {"id": "D", "text": "$\\sqrt{7}$", "is_correct": false, "why_wrong": null}
  ],
  "explanation": "La norme...\n\n**Piège à éviter :** Erreur...",
  "hint": "Rappel...",
  "difficulty": "easy",
  "tags": ["norme"],
  "module": "maths-ia",
  "chapitre": "j1-representation",
  "theme": "vecteurs",
  "reference": {
    "source": "Cours J1, section 1.2"
  },
  "visibility": "private",
  "license": "CC-BY-NC-SA",
  "locale": "fr"
}
```

---

## 📝 Code d'import (à implémenter en P1)

L'Artisan command lira ces fichiers via :

```php
// app/Console/Commands/MigrateFromV1.php

private function migrateBanqueQuestions(): void
{
    $banquePath = base_path('database/seeds/v1_banque');
    $modules = glob("$banquePath/*", GLOB_ONLYDIR);
    
    foreach ($modules as $modulePath) {
        $moduleName = basename($modulePath);
        $chapitres = glob("$modulePath/*", GLOB_ONLYDIR);
        
        foreach ($chapitres as $chapitrePath) {
            $chapitreName = basename($chapitrePath);
            $themes = glob("$chapitrePath/*.json");
            
            foreach ($themes as $themeFile) {
                $data = json_decode(file_get_contents($themeFile), true);
                $meta = $data['_meta'];
                $themeName = $meta['theme'];
                
                foreach ($data['questions'] as $q) {
                    Question::create([
                        'uuid' => $this->generateUuid('QST'),
                        'legacy_id' => $q['id'],
                        'workspace_id' => $this->defaultWorkspaceId,
                        'type' => QuestionType::McqSingle4,
                        'statement' => $q['enonce'],
                        'options' => $this->transformOptions($q['options'], $q['correct']),
                        'explanation' => $this->mergeExplanationAndTraps($q),
                        'hint' => $q['hint'],
                        'difficulty' => $this->mapDifficulty($q['difficulte']),
                        'tags' => $q['tags'],
                        'module' => $meta['module'],
                        'chapitre' => $meta['chapitre'],
                        'theme' => $meta['theme'],
                        'reference' => ['source' => $q['references']],
                        'visibility' => 'private',
                        'license' => 'CC-BY-NC-SA',
                        'locale' => 'fr',
                    ]);
                }
            }
        }
    }
}

private function transformOptions(array $options, int $correctIndex): array
{
    $result = [];
    foreach ($options as $i => $text) {
        $result[] = [
            'id' => chr(65 + $i), // A, B, C, D
            'text' => $text,
            'is_correct' => ($i === $correctIndex),
            'why_wrong' => null, // À enrichir plus tard
        ];
    }
    return $result;
}

private function mapDifficulty(string $v1Difficulty): string
{
    return match($v1Difficulty) {
        'facile' => 'easy',
        'moyen' => 'medium',
        'difficile' => 'hard',
        'expert' => 'expert',
    };
}
```

---

## ⚖️ Licence

Toutes ces questions sont sous licence **CC BY-NC-SA 4.0** :
- **Auteur** : Mohamed EL AFRIT — IPSSI
- **Utilisation** : pédagogique non-commerciale uniquement
- **Partage** : obligatoire sous la même licence

---

## ✅ Validation qualité

Chaque question a été :
- ✅ Vérifiée pédagogiquement
- ✅ Testée en production (v1)
- ✅ Enrichie avec hint + explanation + traps + references
- ✅ Taggée pour recherche
- ✅ Classifiée par difficulté (5 par niveau × 4 niveaux = 20)

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
