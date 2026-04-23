# 🧪 Fixtures v1 — Données de test réelles

> **Exemples de données réelles de Certio v1 pour les tests unitaires et E2E**  
> **À utiliser en tests Pest pour valider la migration v1 → v2**

---

## 📂 Contenu

### `examens/`
**2 examens réels** créés et distribués en production Certio v1 :

| Fichier | Titre | Questions | Statut |
|---|---|:-:|:-:|
| `EXM-EPX9-NFK7.json` | examen2 | 11 | published |
| `EXM-P99D-FWGC.json` | examen1 | 20 | published |

### `passages/`
**3 passages réels** effectués par des étudiants :

| Fichier | Examen | Étudiant | Statut |
|---|---|---|:-:|
| `PSG-ALPY-R5AX.json` | EXM-EPX9-NFK7 | Mohamed Amine | submitted |
| `PSG-WVWF-N6D6.json` | EXM-EPX9-NFK7 | Test | submitted |
| `PSG-Z9V4-8ANG.json` | EXM-P99D-FWGC | Test | submitted |

---

## 🔍 Structure d'un examen v1

```json
{
  "id": "EXM-EPX9-NFK7",
  "titre": "examen2",
  "description": "",
  "created_by": "FCB7-A7DN-VW46",         // ID du compte créateur (enseignant)
  "status": "published",                    // draft | published | closed
  "questions": ["mat-faci-02", ...],        // IDs des questions (référence)
  "duree_sec": 3600,                        // Durée en secondes
  "date_ouverture": "2026-04-23T16:00:00.000Z",
  "date_cloture": "2026-04-25T09:00:00.000Z",
  "max_passages": 1,                        // 1 seule tentative par étudiant
  "shuffle_questions": true,                // Randomisation questions
  "shuffle_options": true,                  // Randomisation options
  "show_correction_after": true,            // Afficher correction après
  "correction_delay_min": 0,                // Délai avant correction
  "access_code": "W328Y5",                  // Code d'accès étudiant
  "created_at": "2026-04-24T00:21:02+02:00",
  "updated_at": "2026-04-24T00:21:15+02:00"
}
```

## 🔍 Structure d'un passage v1

```json
{
  "id": "PSG-ALPY-R5AX",
  "examen_id": "EXM-EPX9-NFK7",
  "token": "df0f0657-4448-4245-a6a7-d4202a5bba9a",
  "access_code_used": "W328Y5",
  "student_info": {
    "nom": "Amine",
    "prenom": "Mohamed",
    "email": "afrit_mohamed@yahoo.fr"
  },
  "question_order": [                       // Ordre randomisé vu par l'étudiant
    "mat-diff-02", "mat-faci-05", ...
  ],
  "option_shuffle_maps": {                  // Mapping shuffle options par question
    "mat-diff-02": [3, 0, 2, 1],            // Position 0 → option 3, etc.
    ...
  },
  "reponses": { ... },                      // Réponses données
  "score": { ... },                         // Score calculé
  "timings": { ... },                       // Timings détaillés
  "status": "submitted",
  "started_at": "...",
  "submitted_at": "..."
}
```

---

## 🎯 Utilisation dans les tests Pest

### Test de migration v1 → v2

```php
// tests/Feature/Migration/V1MigrationTest.php

test('can migrate v1 exam structure to v2', function () {
    $v1ExamJson = base_path('tests/fixtures/v1/examens/EXM-EPX9-NFK7.json');
    $v1Data = json_decode(file_get_contents($v1ExamJson), true);
    
    // Run migration command
    $this->artisan('certio:migrate-from-v1', [
        '--source' => base_path('tests/fixtures/v1'),
        '--dry-run' => false,
    ])->assertSuccessful();
    
    // Verify v2 structure
    $v2Exam = Exam::where('legacy_id', 'EXM-EPX9-NFK7')->first();
    
    expect($v2Exam)
        ->not->toBeNull()
        ->title->toBe('examen2')
        ->duration_minutes->toBe(60)           // 3600 sec → 60 min
        ->max_passages->toBe(1)
        ->shuffle_questions->toBeTrue()
        ->shuffle_options->toBeTrue()
        ->access_code->toBe('W328Y5');
});

test('v1 passage ordering is preserved after migration', function () {
    // Migration
    $this->artisan('certio:migrate-from-v1', [
        '--source' => base_path('tests/fixtures/v1'),
    ])->assertSuccessful();
    
    // Verify passage
    $v2Passage = Passage::where('legacy_id', 'PSG-ALPY-R5AX')->first();
    
    expect($v2Passage)
        ->not->toBeNull()
        ->student_email->toBe('afrit_mohamed@yahoo.fr')
        ->questions_order->toBeArray()->toHaveCount(11);
});
```

### Test de structure

```php
test('all v1 fixtures have required fields', function () {
    $fixturesPath = base_path('tests/fixtures/v1');
    
    // Check examens
    foreach (glob("$fixturesPath/examens/*.json") as $file) {
        $data = json_decode(file_get_contents($file), true);
        expect($data)->toHaveKeys([
            'id', 'titre', 'status', 'questions', 'duree_sec',
            'shuffle_questions', 'shuffle_options', 'access_code',
        ]);
    }
    
    // Check passages
    foreach (glob("$fixturesPath/passages/*.json") as $file) {
        $data = json_decode(file_get_contents($file), true);
        expect($data)->toHaveKeys([
            'id', 'examen_id', 'token', 'student_info',
            'question_order', 'option_shuffle_maps',
        ]);
    }
});
```

---

## ⚠️ Données sensibles

**Les emails des fixtures sont anonymisés** pour les tests :
- Email réel : `afrit_mohamed@yahoo.fr` → utilisé uniquement en dev local
- En CI/CD : remplacer par `test@example.com` via seeders

---

## 🔄 Correspondance champs v1 ↔ v2

### Examen

| Champ v1 | Champ v2 | Transformation |
|---|---|---|
| `id` | `legacy_id` + `uuid` | Génération UUID nouveau |
| `titre` | `title` | Direct |
| `description` | `description` | Direct |
| `created_by` | `creator_id` | Lookup User |
| `status` | `status` (enum) | Direct avec enum |
| `questions` (array IDs) | Relation `exam_question` | Lookup + attach |
| `duree_sec` | `duration_minutes` | /60 |
| `max_passages` | `max_passages` | Direct |
| `shuffle_questions` | `shuffle_questions` | Direct |
| `shuffle_options` | `shuffle_options` | Direct |
| `show_correction_after` | `correction_visibility` | `true` → `auto_after_submit`, `false` → `manual` |
| `access_code` | `access_code` | Direct |

### Passage

| Champ v1 | Champ v2 | Transformation |
|---|---|---|
| `id` | `legacy_id` + `uuid` | Génération UUID nouveau |
| `examen_id` | `exam_id` | Lookup Exam |
| `token` | `token` | Direct |
| `student_info.nom` | `student_nom` | Direct |
| `student_info.prenom` | `student_prenom` | Direct |
| `student_info.email` | `student_email` | Direct |
| `question_order` | `questions_order` | Lookup IDs → convert |
| `option_shuffle_maps` | `options_order` | Transformation format |

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
