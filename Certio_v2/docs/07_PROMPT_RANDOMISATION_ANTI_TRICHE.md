# 🔐 Randomisation Anti-triche — Spécification + Prompt VS Code

> **Feature de sécurité essentielle pour l'intégrité des examens**  
> **Intégration transverse dans les phases P1, P2, P3, P4**

| Champ | Valeur |
|---|---|
| **Document** | Spécification + Prompt VS Code |
| **Feature** | Randomisation questions + options |
| **Impact planning** | 0 jour additionnel (intégré existant) |
| **Version** | 1.0 |
| **Auteur** | Mohamed EL AFRIT |
| **Licence** | CC BY-NC-SA 4.0 |

---

## Sommaire

1. [Vision et décisions validées](#1-vision-et-décisions-validées)
2. [Architecture technique](#2-architecture-technique)
3. [Modèle de données (ajouts)](#3-modèle-de-données-ajouts)
4. [Service RandomizationService](#4-service-randomizationservice)
5. [Intégration dans les phases existantes](#5-intégration-dans-les-phases-existantes)
6. [Prompt VS Code complet](#6-prompt-vs-code-complet)
7. [Tests Pest](#7-tests-pest)
8. [Checklist validation](#8-checklist-validation)

---

## 1. Vision et décisions validées

### 🎯 Objectif

Empêcher la triche entre étudiants qui passent le même examen, en garantissant que :

1. **Chaque étudiant voit les questions dans un ordre différent**
2. **Chaque étudiant voit les options dans un ordre différent**
3. **Le prof garde le contrôle** et peut désactiver si besoin

### ✅ Décisions validées

| Question | Décision |
|---|---|
| Config par défaut | ✅ **Activé par défaut** (shuffle_questions = true, shuffle_options = true) |
| Vrai/Faux | ✅ **Mélangés aussi** (même si 2 options) |
| preserve_order | ✅ **Option au niveau question** (pour séquences logiques) |
| Preview prof | ✅ **Bouton "Prévisualiser étudiant"** |

### 🎲 Principes techniques

- **Déterministe** : même passage → même ordre (pas de flip-flop si rechargement)
- **Unique par étudiant** : seed dérivé du passage UUID + email étudiant
- **Transparent** : l'étudiant ne voit aucune différence UX
- **Préserve la logique** : les IDs des options restent liés au contenu, pas à la position

---

## 2. Architecture technique

### 🏗️ Flux de données

```
Création examen (prof)
  ↓
shuffle_questions = true (défaut)
shuffle_options = true (défaut)
  ↓
Étudiant démarre passage
  ↓
RandomizationService.generateQuestionsOrder(exam, passage)
  → Seed = crc32(passage.uuid + student.email)
  → shuffle(question_ids) avec seed
  ↓
RandomizationService.generateOptionsOrder(exam, passage)
  → Pour chaque question :
    - Si preserve_options_order = true → skip
    - Seed = crc32(passage.uuid + email + question_id)
    - shuffle(option_ids) avec seed
  ↓
passage.questions_order = [5, 12, 3, 8, 1]
passage.options_order = {5: [C,A,D,B], 12: [B,D,A,C], ...}
  ↓
Étudiant voit l'ordre mélangé
  ↓
Étudiant sélectionne option ID "C" pour Q5
  ↓
Soumission : répond avec les IDs originaux
  ↓
Scoring indépendant de l'ordre visuel
```

### 🎯 Pourquoi un seed déterministe ?

**Scénario critique** : étudiant perd sa connexion, recharge la page, revient.

❌ **Sans seed déterministe** : 
- Nouvelle randomisation → ordre différent → étudiant perdu
- Ses réponses déjà données ne correspondent plus aux bonnes positions
- Catastrophe UX + données corrompues

✅ **Avec seed déterministe** :
- `crc32(passage_uuid + email)` retourne toujours la même valeur
- `mt_srand(seed)` puis `shuffle()` retourne toujours le même ordre
- Étudiant retrouve exactement ce qu'il voyait

### 🔐 Sécurité du seed

Un étudiant malveillant pourrait-il "prédire" l'ordre d'un autre étudiant ?

**Réponse : Non**, car :
1. Le `passage.uuid` est un UUID généré côté serveur (non prévisible)
2. L'email de l'autre étudiant n'est pas connu publiquement
3. `crc32` n'est pas "crackable" pour retrouver les inputs
4. Même en connaissant le seed, il faudrait implémenter exactement le même algorithme PHP

**Mesure bonus** : les IDs des questions/options ne sont pas séquentiels (UUIDs), donc connaître un ordre ne permet pas de deviner le contenu.

---

## 3. Modèle de données (ajouts)

### Migration additive sur `questions`

Ajouter le champ `preserve_options_order` pour que le prof puisse protéger certaines questions où l'ordre est essentiel (séquences, échelles de Likert, etc.).

```bash
php artisan make:migration add_preserve_options_order_to_questions_table
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->boolean('preserve_options_order')
                ->default(false)
                ->after('subtype_config')
                ->comment('Si true, les options ne sont jamais mélangées (même si exam.shuffle_options = true)');
        });
    }
    
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('preserve_options_order');
        });
    }
};
```

### Modification defaults migration `exams`

La migration existante (déjà prévue en P1) doit avoir les defaults corrects :

```php
// Dans create_exams_table migration
$table->boolean('shuffle_questions')->default(true);  // ✅ activé
$table->boolean('shuffle_options')->default(true);    // ✅ activé
```

### Structure `passages` (déjà prévue en P1)

Les champs suivants sont déjà dans la migration :
- `questions_order` (JSON) : liste ordonnée des IDs de questions
- `options_order` (JSON) : map `{question_id: [option_ids_ordered]}`

**Exemple de données** :
```json
{
  "questions_order": [5, 12, 3, 8, 1],
  "options_order": {
    "5": ["C", "A", "D", "B"],
    "12": ["B", "D", "A", "C"],
    "3": ["A", "B"],
    "8": ["D", "A", "B", "C"],
    "1": ["B", "C", "A", "D"]
  }
}
```

### Mise à jour Model `Question`

Ajouter dans `app/Models/Question.php` :
```php
protected $casts = [
    // ... casts existants
    'preserve_options_order' => 'boolean',
];

protected $fillable = [
    // ... fillables existants
    'preserve_options_order',
];
```

---

## 4. Service RandomizationService

### Service complet

Créer `app/Services/RandomizationService.php` :

```php
<?php
namespace App\Services;

use App\Models\Exam;
use App\Models\Passage;
use App\Models\Question;
use App\Enums\QuestionType;

class RandomizationService
{
    /**
     * Génère l'ordre des questions pour un passage donné.
     * L'ordre est déterministe (seed basé sur passage UUID + email).
     * 
     * @param Exam $exam
     * @param Passage $passage (doit avoir uuid et student_email)
     * @return array<int> Liste d'IDs de questions dans l'ordre à afficher
     */
    public function generateQuestionsOrder(Exam $exam, Passage $passage): array
    {
        $questionIds = $exam->questions()
            ->orderBy('exam_question.order')
            ->pluck('questions.id')
            ->toArray();
        
        if (!$exam->shuffle_questions || count($questionIds) <= 1) {
            return $questionIds;
        }
        
        // Seed déterministe : même passage → même ordre
        $seed = $this->generateSeed($passage->uuid . $passage->student_email);
        mt_srand($seed);
        shuffle($questionIds);
        
        return $questionIds;
    }
    
    /**
     * Génère l'ordre des options pour chaque question dans un passage.
     * 
     * @return array<int, array<string>> ['question_id' => ['C', 'A', 'D', 'B'], ...]
     */
    public function generateOptionsOrder(Exam $exam, Passage $passage): array
    {
        if (!$exam->shuffle_options) {
            return [];
        }
        
        $order = [];
        
        foreach ($exam->questions as $question) {
            // Exception 1 : preserve_options_order = true au niveau question
            if ($question->preserve_options_order) {
                continue;
            }
            
            // Récupérer les IDs des options
            $optionIds = collect($question->options)->pluck('id')->toArray();
            
            if (count($optionIds) <= 1) {
                continue;
            }
            
            // Seed différent pour chaque question
            $seed = $this->generateSeed(
                $passage->uuid . $passage->student_email . $question->id
            );
            mt_srand($seed);
            shuffle($optionIds);
            
            $order[$question->id] = $optionIds;
        }
        
        return $order;
    }
    
    /**
     * Applique l'ordre shuffled sur les options d'une question.
     * Retourne les options dans le nouvel ordre (pour affichage).
     * 
     * Les IDs des options ne changent pas — seul leur ordre d'affichage change.
     */
    public function applyOptionsOrder(Question $question, ?array $order = null): array
    {
        if (empty($order)) {
            return $question->options;
        }
        
        $indexed = collect($question->options)->keyBy('id');
        $result = [];
        
        foreach ($order as $optId) {
            if ($indexed->has($optId)) {
                $result[] = $indexed->get($optId);
            }
        }
        
        // Safety : ajouter options manquantes (au cas où)
        foreach ($question->options as $opt) {
            if (!in_array($opt['id'], $order)) {
                $result[] = $opt;
            }
        }
        
        return $result;
    }
    
    /**
     * Retourne les questions d'un passage dans l'ordre à afficher.
     * Charge les options avec leur ordre shuffled.
     * 
     * @return array<array{question: Question, options: array, position: int}>
     */
    public function getOrderedQuestionsForPassage(Passage $passage): array
    {
        $exam = $passage->exam;
        $questionsOrder = $passage->questions_order ?? [];
        $optionsOrder = $passage->options_order ?? [];
        
        if (empty($questionsOrder)) {
            // Fallback : ordre original
            $questionsOrder = $exam->questions->pluck('id')->toArray();
        }
        
        $questionsById = $exam->questions->keyBy('id');
        $result = [];
        
        foreach ($questionsOrder as $position => $qId) {
            $question = $questionsById->get($qId);
            if (!$question) continue;
            
            $shuffledOptions = $this->applyOptionsOrder(
                $question,
                $optionsOrder[$qId] ?? null
            );
            
            $result[] = [
                'question' => $question,
                'options' => $shuffledOptions,
                'position' => $position + 1,
            ];
        }
        
        return $result;
    }
    
    /**
     * Vérifie si une réponse est correcte indépendamment de l'ordre d'affichage.
     * L'étudiant répond avec les IDs originaux d'options (pas leur position).
     */
    public function isAnswerCorrect(
        Question $question, 
        array $selectedOptionIds
    ): bool {
        $correctIds = collect($question->options)
            ->where('is_correct', true)
            ->pluck('id')
            ->sort()
            ->values()
            ->toArray();
        
        $selected = collect($selectedOptionIds)
            ->sort()
            ->values()
            ->toArray();
        
        return $correctIds === $selected;
    }
    
    /**
     * Pour PROF uniquement : génère un preview aléatoire.
     * Utile pour "voir ce que verra un étudiant".
     * 
     * @param Exam $exam
     * @param string $seedSuffix Pour générer différents previews (random par défaut)
     * @return array Liste de questions avec options mélangées
     */
    public function generatePreview(Exam $exam, string $seedSuffix = null): array
    {
        $seedSuffix = $seedSuffix ?? uniqid('preview_');
        $fakePassageUuid = 'PREVIEW-' . $seedSuffix;
        $fakeEmail = 'preview@certio.app';
        
        $questionIds = $exam->questions
            ->sortBy(fn($q) => $q->pivot->order ?? 0)
            ->pluck('id')
            ->toArray();
        
        if ($exam->shuffle_questions && count($questionIds) > 1) {
            $seed = $this->generateSeed($fakePassageUuid . $fakeEmail);
            mt_srand($seed);
            shuffle($questionIds);
        }
        
        $questionsById = $exam->questions->keyBy('id');
        $preview = [];
        
        foreach ($questionIds as $idx => $qId) {
            $question = $questionsById->get($qId);
            $options = $question->options;
            
            // Shuffle options sauf si preserve_options_order
            if ($exam->shuffle_options 
                && !$question->preserve_options_order 
                && count($options) > 1) {
                
                $seed = $this->generateSeed(
                    $fakePassageUuid . $fakeEmail . $qId
                );
                mt_srand($seed);
                $optionIds = collect($options)->pluck('id')->toArray();
                shuffle($optionIds);
                $options = $this->applyOptionsOrder($question, $optionIds);
            }
            
            // Trouver la position originale de cette question
            $originalPosition = $exam->questions
                ->sortBy(fn($q) => $q->pivot->order ?? 0)
                ->values()
                ->search(fn($q) => $q->id === $qId) + 1;
            
            $preview[] = [
                'position' => $idx + 1,
                'original_position' => $originalPosition,
                'question' => $question,
                'options' => $options,
                'preserve_order' => $question->preserve_options_order,
            ];
        }
        
        return $preview;
    }
    
    /**
     * Génère un seed déterministe à partir d'une chaîne.
     */
    private function generateSeed(string $input): int
    {
        // crc32 retourne un int 32 bits qui convient pour mt_srand
        return crc32($input);
    }
}
```

### Intégration dans `StartPassage` Action

Mettre à jour `app/Actions/Passage/StartPassage.php` :

```php
<?php
namespace App\Actions\Passage;

use App\Models\Exam;
use App\Models\Passage;
use App\Services\RandomizationService;
use App\Enums\PassageStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StartPassage
{
    public function __construct(
        private RandomizationService $randomizer,
    ) {}
    
    public function execute(
        Exam $exam, 
        string $studentEmail,
        string $studentNom,
        string $studentPrenom,
        ?array $customFields = null
    ): Passage {
        return DB::transaction(function () use (
            $exam, $studentEmail, $studentNom, $studentPrenom, $customFields
        ) {
            // Vérifier que l'examen accepte de nouveaux passages
            if (!$exam->canAcceptNewPassages()) {
                throw new \RuntimeException('Cet examen n\'accepte plus de passages');
            }
            
            // Créer le passage
            $passage = Passage::create([
                'uuid' => 'PSG-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'token' => Str::uuid(),
                'workspace_id' => $exam->workspace_id,
                'exam_id' => $exam->id,
                'student_email' => $studentEmail,
                'student_nom' => $studentNom,
                'student_prenom' => $studentPrenom,
                'student_custom_fields' => $customFields,
                'status' => PassageStatus::InProgress,
                'started_at' => now(),
                'expires_at' => now()->addMinutes($exam->duration_minutes),
            ]);
            
            // 🎲 Générer les ordres aléatoires (seed stable basé sur passage UUID + email)
            $questionsOrder = $this->randomizer->generateQuestionsOrder($exam, $passage);
            $optionsOrder = $this->randomizer->generateOptionsOrder($exam, $passage);
            
            // Sauvegarder
            $passage->update([
                'questions_order' => $questionsOrder,
                'options_order' => $optionsOrder,
            ]);
            
            // Audit log
            activity()
                ->performedOn($passage)
                ->withProperties([
                    'shuffle_questions' => $exam->shuffle_questions,
                    'shuffle_options' => $exam->shuffle_options,
                    'questions_count' => count($questionsOrder),
                ])
                ->log('passage.started');
            
            return $passage;
        });
    }
}
```

### Controller pour preview prof

Ajouter méthode dans `app/Http/Controllers/Prof/ExamController.php` :

```php
public function preview(Exam $exam, Request $request, RandomizationService $randomizer)
{
    Gate::authorize('view', $exam);
    
    $seedSuffix = $request->input('seed', null);
    $preview = $randomizer->generatePreview($exam, $seedSuffix);
    
    return response()->json([
        'preview' => collect($preview)->map(fn($item) => [
            'position' => $item['position'],
            'original_position' => $item['original_position'],
            'preserve_order' => $item['preserve_order'],
            'question' => [
                'uuid' => $item['question']->uuid,
                'type' => $item['question']->type->value,
                'statement' => $item['question']->statement,
            ],
            'options' => $item['options'],
        ]),
        'shuffle_questions' => $exam->shuffle_questions,
        'shuffle_options' => $exam->shuffle_options,
    ]);
}
```

Route :
```php
// routes/web.php
Route::get('/prof/exams/{exam}/preview', [ExamController::class, 'preview'])
    ->name('prof.exams.preview');
```

---

## 5. Intégration dans les phases existantes

### Phase P1 (Migration données v1 → v2)

**Déjà prévu** : colonnes `shuffle_questions` et `shuffle_options` dans `exams`.

**À ajouter** :
- ✅ Valeur par défaut `true` pour les 2 booleans
- ✅ Migration additive `preserve_options_order` sur `questions`

### Phase P2 (CBM Core)

**À ajouter** :
- ✅ Créer `RandomizationService` (2h)
- ✅ Intégrer dans `StartPassage` action (1h)
- ✅ Tests Pest du service (2h)

**Impact durée** : +0.5 jour → à absorber dans P2 (5 jours)

### Phase P3 (Types questions + Multi-select)

**À ajouter** :
- ✅ Éditeur question : toggle `preserve_options_order` (1h)
- ✅ Controller `preview` endpoint (30min)
- ✅ Composant `StudentPreviewModal` Vue (2h)
- ✅ Toggles dans formulaire création examen (30min)

**Impact durée** : +0.5 jour → à absorber dans P3 (5 jours)

### Phase P4 (Scoring & Analytics)

**À ajouter** :
- ✅ Utiliser `RandomizationService::isAnswerCorrect` dans scoring (1h)
- ✅ Dans analytics, afficher "position vue par étudiant" vs "position originale" (1h)

**Impact durée** : négligeable

**TOTAL** : 0 jour additionnel (intégré dans phases existantes)

---

## 6. Prompt VS Code complet

### 🎯 À copier-coller dans Claude Code / Cursor

```
# CONTEXTE — CERTIO v2.0 LARAVEL — FEATURE RANDOMISATION ANTI-TRICHE

Je développe Certio v2.0 Laravel. Cette feature de sécurité doit être implémentée 
de manière transverse dans les phases P1, P2, P3, P4.

## 📚 Documents de référence
- `Certio_v2/docs/01_NOTE_DE_CADRAGE_LARAVEL.md`
- `Certio_v2/docs/07_PROMPT_RANDOMISATION_ANTI_TRICHE.md` (ce document)

## 🎯 Objectif

Implémenter un système robuste de randomisation anti-triche :
1. Chaque étudiant voit les questions dans un ordre différent
2. Chaque étudiant voit les options dans un ordre différent
3. L'ordre est déterministe (seed stable) par passage
4. Le prof peut désactiver (activé par défaut)
5. Les V/F sont mélangés aussi (par défaut)
6. Les questions peuvent avoir preserve_options_order = true (séquences logiques)
7. Le prof peut prévisualiser ce que verra un étudiant

## 📋 TÂCHES

### Tâche 1 — Migration preserve_options_order (15 min)

```bash
php artisan make:migration add_preserve_options_order_to_questions_table
```

```php
public function up(): void
{
    Schema::table('questions', function (Blueprint $table) {
        $table->boolean('preserve_options_order')
            ->default(false)
            ->after('subtype_config');
    });
}
```

Mettre à jour Model Question :
```php
protected $fillable = [
    // ... fillables existants
    'preserve_options_order',
];

protected $casts = [
    // ... casts existants
    'preserve_options_order' => 'boolean',
];
```

### Tâche 2 — Defaults shuffle dans migration exams (10 min)

Vérifier que dans la migration `create_exams_table` :
```php
$table->boolean('shuffle_questions')->default(true);   // ← activé par défaut
$table->boolean('shuffle_options')->default(true);     // ← activé par défaut
```

Si la migration existe déjà avec default `false`, créer une migration de modification :

```bash
php artisan make:migration change_default_shuffle_on_exams_table
```

```php
public function up(): void
{
    // Laravel ne permet pas de changer defaults avec Schema builder
    // sur SQLite - on fait via raw SQL
    DB::statement("UPDATE exams SET shuffle_questions = 1 WHERE shuffle_questions = 0 AND created_at > NOW()");
    DB::statement("UPDATE exams SET shuffle_options = 1 WHERE shuffle_options = 0 AND created_at > NOW()");
    
    // Pour les nouveaux records, modifier le default au niveau model
    // (ajouter dans Exam.php : 'shuffle_questions' => true dans $attributes)
}
```

Dans `app/Models/Exam.php` :
```php
protected $attributes = [
    'shuffle_questions' => true,
    'shuffle_options' => true,
    // ... autres defaults
];
```

### Tâche 3 — Service RandomizationService (2h)

Créer `app/Services/RandomizationService.php` avec le code complet fourni dans la section 4 du document.

### Tâche 4 — Tests Pest du service (1h30)

Créer `tests/Feature/Services/RandomizationServiceTest.php` :

```php
<?php
use App\Services\RandomizationService;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Passage;

beforeEach(function () {
    $this->service = app(RandomizationService::class);
    $this->exam = Exam::factory()
        ->has(Question::factory()->count(5), 'questions')
        ->create([
            'shuffle_questions' => true,
            'shuffle_options' => true,
        ]);
});

test('questions order is deterministic for same passage', function () {
    $passage = Passage::factory()->create([
        'exam_id' => $this->exam->id,
        'uuid' => 'PSG-TEST-001',
        'student_email' => 'student@test.com',
    ]);
    
    $order1 = $this->service->generateQuestionsOrder($this->exam, $passage);
    $order2 = $this->service->generateQuestionsOrder($this->exam, $passage);
    
    expect($order1)->toBe($order2);
});

test('questions order differs between passages', function () {
    $passage1 = Passage::factory()->create([
        'exam_id' => $this->exam->id,
        'uuid' => 'PSG-TEST-001',
        'student_email' => 'student1@test.com',
    ]);
    $passage2 = Passage::factory()->create([
        'exam_id' => $this->exam->id,
        'uuid' => 'PSG-TEST-002',
        'student_email' => 'student2@test.com',
    ]);
    
    $order1 = $this->service->generateQuestionsOrder($this->exam, $passage1);
    $order2 = $this->service->generateQuestionsOrder($this->exam, $passage2);
    
    // Highly unlikely to be same (5! = 120 possibilities)
    expect($order1)->not->toBe($order2);
});

test('questions order preserved when shuffle_questions is false', function () {
    $this->exam->update(['shuffle_questions' => false]);
    $passage = Passage::factory()->create(['exam_id' => $this->exam->id]);
    
    $order = $this->service->generateQuestionsOrder($this->exam, $passage);
    $originalOrder = $this->exam->questions->pluck('id')->toArray();
    
    expect($order)->toBe($originalOrder);
});

test('options order respects preserve_options_order flag', function () {
    $question = Question::factory()->create([
        'preserve_options_order' => true,
        'options' => [
            ['id' => 'A', 'text' => 'Jamais', 'is_correct' => false],
            ['id' => 'B', 'text' => 'Parfois', 'is_correct' => false],
            ['id' => 'C', 'text' => 'Souvent', 'is_correct' => false],
            ['id' => 'D', 'text' => 'Toujours', 'is_correct' => true],
        ],
    ]);
    $this->exam->questions()->attach($question);
    
    $passage = Passage::factory()->create([
        'exam_id' => $this->exam->id,
        'uuid' => 'PSG-TEST-003',
    ]);
    
    $order = $this->service->generateOptionsOrder($this->exam, $passage);
    
    // Cette question ne doit PAS être dans l'ordre (skipped)
    expect($order)->not->toHaveKey($question->id);
});

test('true/false questions are also shuffled by default', function () {
    $tfQuestion = Question::factory()->create([
        'type' => 'true_false',
        'preserve_options_order' => false, // explicite
        'options' => [
            ['id' => 'A', 'text' => 'Vrai', 'is_correct' => true],
            ['id' => 'B', 'text' => 'Faux', 'is_correct' => false],
        ],
    ]);
    $this->exam->questions()->attach($tfQuestion);
    
    $passage = Passage::factory()->create(['exam_id' => $this->exam->id]);
    
    $order = $this->service->generateOptionsOrder($this->exam, $passage);
    
    // Doit avoir un ordre (pas skipped)
    expect($order)->toHaveKey($tfQuestion->id);
});

test('answer correctness is independent of display order', function () {
    $question = Question::factory()->create([
        'options' => [
            ['id' => 'A', 'text' => 'Option A', 'is_correct' => false],
            ['id' => 'B', 'text' => 'Option B', 'is_correct' => true],
            ['id' => 'C', 'text' => 'Option C', 'is_correct' => false],
            ['id' => 'D', 'text' => 'Option D', 'is_correct' => false],
        ],
    ]);
    
    // Student selects "B" (original ID)
    $result = $this->service->isAnswerCorrect($question, ['B']);
    expect($result)->toBeTrue();
    
    // Even though "B" might appear in position 1, 2, 3, or 4 visually
});

test('preview generates different orders with different seeds', function () {
    $preview1 = $this->service->generatePreview($this->exam, 'seed1');
    $preview2 = $this->service->generatePreview($this->exam, 'seed2');
    
    $order1 = collect($preview1)->pluck('question.id')->toArray();
    $order2 = collect($preview2)->pluck('question.id')->toArray();
    
    expect($order1)->not->toBe($order2);
});

test('preview includes original position for teacher visibility', function () {
    $preview = $this->service->generatePreview($this->exam, 'test');
    
    foreach ($preview as $item) {
        expect($item)->toHaveKeys(['position', 'original_position', 'question', 'options']);
    }
});

// ... 10+ autres tests
```

### Tâche 5 — Intégration dans StartPassage (30 min)

Mettre à jour `app/Actions/Passage/StartPassage.php` avec le code fourni en section 4.

### Tâche 6 — Controller preview pour prof (30 min)

Dans `app/Http/Controllers/Prof/ExamController.php`, ajouter la méthode `preview` fournie en section 4.

Ajouter la route :
```php
Route::get('/prof/exams/{exam}/preview', [ExamController::class, 'preview'])
    ->name('prof.exams.preview');
```

### Tâche 7 — UI prof : Toggles + preserve_order (1h)

**7A. Dans `resources/js/Pages/Prof/Exams/Create.vue` et `Edit.vue`** :

Remplacer la section anti-triche existante par :

```vue
<FormSection title="🔐 Anti-triche — Randomisation" icon="🎲">
  <div class="space-y-4">
    
    <!-- Shuffle questions -->
    <label class="flex items-start gap-3 cursor-pointer p-3 rounded hover:bg-gray-50">
      <input 
        type="checkbox" 
        v-model="form.shuffle_questions"
        class="mt-1 h-4 w-4"
      />
      <div class="flex-1">
        <div class="font-medium">Mélanger l'ordre des questions</div>
        <p class="text-sm text-gray-600 mt-1">
          Chaque étudiant verra les questions dans un ordre différent 
          (mais stable pour lui durant tout le passage).
        </p>
      </div>
    </label>
    
    <!-- Shuffle options -->
    <label class="flex items-start gap-3 cursor-pointer p-3 rounded hover:bg-gray-50">
      <input 
        type="checkbox" 
        v-model="form.shuffle_options"
        class="mt-1 h-4 w-4"
      />
      <div class="flex-1">
        <div class="font-medium">Mélanger les positions des réponses</div>
        <p class="text-sm text-gray-600 mt-1">
          Pour chaque question, les options apparaîtront dans un ordre différent 
          pour chaque étudiant (y compris V/F).
        </p>
        <p class="text-xs text-gray-500 mt-1">
          💡 Pour certaines questions (échelles, séquences), activez 
          "Préserver l'ordre des options" au niveau de la question.
        </p>
      </div>
    </label>
    
    <!-- Tip -->
    <div class="bg-green-50 border-l-4 border-green-400 p-3 rounded">
      <p class="text-sm">
        ✅ <strong>Protection activée par défaut.</strong> Désactivez uniquement 
        si vous avez une raison pédagogique (ex: examen en classe avec correction 
        projetée).
      </p>
    </div>
    
    <!-- Preview button -->
    <div v-if="exam.id && (form.shuffle_questions || form.shuffle_options)" class="pt-2">
      <button
        @click="showPreview = true"
        type="button"
        class="btn-secondary inline-flex items-center gap-2"
      >
        👁️ Prévisualiser ce que verra un étudiant
      </button>
    </div>
    
    <StudentPreviewModal 
      v-if="showPreview" 
      :exam-id="exam.id" 
      :exam-uuid="exam.uuid"
      @close="showPreview = false" 
    />
  </div>
</FormSection>
```

**7B. Dans `resources/js/Components/QuestionEditor.vue`** :

Ajouter toggle `preserve_options_order` :

```vue
<!-- Après le bloc options, avant le bloc explanation -->
<div v-if="form.type !== 'true_false'" class="mt-4">
  <label class="flex items-start gap-3 cursor-pointer">
    <input 
      type="checkbox" 
      v-model="form.preserve_options_order"
      class="mt-1"
    />
    <div>
      <div class="font-medium text-sm">Préserver l'ordre des options</div>
      <p class="text-xs text-gray-600">
        Utile pour les séquences logiques (ex: "Jamais / Parfois / Souvent / Toujours") 
        ou les échelles ordonnées. Les options de cette question ne seront jamais 
        mélangées, même si l'examen a "Mélanger les positions" activé.
      </p>
    </div>
  </label>
</div>
```

### Tâche 8 — Composant StudentPreviewModal.vue (1h30)

Créer `resources/js/Components/StudentPreviewModal.vue` :

```vue
<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  examId: Number,
  examUuid: String,
})
const emit = defineEmits(['close'])

const currentPreview = ref(null)
const previewIndex = ref(0)
const loading = ref(false)
const metadata = ref(null)

async function loadPreview(seed = null) {
  loading.value = true
  try {
    const params = seed ? { seed } : {}
    const res = await axios.get(
      `/prof/exams/${props.examUuid}/preview`,
      { params }
    )
    currentPreview.value = res.data.preview
    metadata.value = {
      shuffle_questions: res.data.shuffle_questions,
      shuffle_options: res.data.shuffle_options,
    }
    previewIndex.value = 0
  } catch (err) {
    console.error('Preview error:', err)
    alert('Erreur lors du chargement de la prévisualisation')
  } finally {
    loading.value = false
  }
}

function generateNewPreview() {
  const suffix = Math.random().toString(36).substring(7)
  loadPreview(suffix)
}

function previous() {
  if (previewIndex.value > 0) previewIndex.value--
}

function next() {
  if (previewIndex.value < currentPreview.value.length - 1) previewIndex.value++
}

onMounted(() => loadPreview())
</script>

<template>
  <Modal @close="emit('close')" size="lg">
    <template #title>
      👁️ Prévisualisation étudiant
    </template>
    
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-sm text-gray-600">Génération de la prévisualisation...</p>
    </div>
    
    <div v-else-if="currentPreview && currentPreview.length">
      <!-- Metadata -->
      <div class="bg-blue-50 p-3 rounded mb-4 text-sm">
        <p>
          <strong>Configuration actuelle :</strong>
          <span v-if="metadata.shuffle_questions">✅ Questions mélangées</span>
          <span v-else>❌ Questions dans l'ordre original</span>
          |
          <span v-if="metadata.shuffle_options">✅ Options mélangées</span>
          <span v-else>❌ Options dans l'ordre original</span>
        </p>
      </div>
      
      <p class="text-sm text-gray-600 mb-4">
        💡 Voici un exemple de ce que verrait un étudiant. Les bonnes réponses 
        sont marquées ✓ (non visibles par les étudiants).
      </p>
      
      <!-- Navigation -->
      <div class="flex items-center justify-between mb-4 p-2 bg-gray-50 rounded">
        <button 
          @click="previous" 
          :disabled="previewIndex === 0"
          class="btn-sm btn-secondary"
        >
          ← Précédente
        </button>
        <span class="text-sm font-medium">
          Question {{ previewIndex + 1 }} sur {{ currentPreview.length }}
        </span>
        <button 
          @click="next" 
          :disabled="previewIndex === currentPreview.length - 1"
          class="btn-sm btn-secondary"
        >
          Suivante →
        </button>
      </div>
      
      <!-- Question preview -->
      <div v-if="currentPreview[previewIndex]" class="border rounded-lg p-4 bg-white">
        <div class="flex items-center gap-2 mb-3 text-xs text-gray-500">
          <span>📋 Position dans le passage : {{ currentPreview[previewIndex].position }}</span>
          <span>|</span>
          <span>🎯 Position originale : {{ currentPreview[previewIndex].original_position }}</span>
          <span 
            v-if="currentPreview[previewIndex].preserve_order" 
            class="text-purple-600 font-medium"
          >
            🔒 Ordre préservé
          </span>
        </div>
        
        <h3 class="font-medium mb-4 text-lg" 
            v-html="currentPreview[previewIndex].question.statement">
        </h3>
        
        <div class="space-y-2">
          <div 
            v-for="(opt, i) in currentPreview[previewIndex].options" 
            :key="opt.id"
            class="flex items-center gap-3 p-3 border-2 rounded"
            :class="{ 
              'border-green-400 bg-green-50': opt.is_correct,
              'border-gray-200': !opt.is_correct 
            }"
          >
            <span class="font-mono text-sm font-bold w-6">
              {{ String.fromCharCode(65 + i) }}.
            </span>
            <span class="flex-1">{{ opt.text }}</span>
            <span v-if="opt.is_correct" class="text-green-600 text-sm font-medium">
              ✓ Bonne réponse
            </span>
          </div>
        </div>
      </div>
      
      <!-- Generate new -->
      <div class="mt-6 flex justify-center">
        <button 
          @click="generateNewPreview" 
          class="btn-secondary inline-flex items-center gap-2"
        >
          🔄 Générer un autre exemple aléatoire
        </button>
      </div>
    </div>
    
    <template #footer>
      <button @click="emit('close')" class="btn-primary">
        Fermer
      </button>
    </template>
  </Modal>
</template>
```

### Tâche 9 — Intégration côté étudiant (déjà prévue en P3)

Dans `resources/js/Pages/Student/Passage/Take.vue`, s'assurer que :
- On utilise `passage.questions_order` pour afficher les questions
- On utilise `passage.options_order[question_id]` pour afficher les options
- Les IDs originaux des options sont soumis (pas les positions)

Utiliser la méthode `RandomizationService::getOrderedQuestionsForPassage($passage)` 
depuis le contrôleur pour préparer les données.

### Tâche 10 — Tests E2E (1h)

Créer `tests/Feature/E2E/AntiCheatWorkflowTest.php` :

```php
test('two students see different question orders', function () {
    $exam = Exam::factory()
        ->has(Question::factory()->count(10), 'questions')
        ->create([
            'shuffle_questions' => true,
            'shuffle_options' => true,
        ]);
    
    $passage1 = app(StartPassage::class)->execute(
        $exam, 'alice@test.com', 'Alice', 'Wonderland'
    );
    $passage2 = app(StartPassage::class)->execute(
        $exam, 'bob@test.com', 'Bob', 'Builder'
    );
    
    expect($passage1->questions_order)->not->toBe($passage2->questions_order);
    expect($passage1->options_order)->not->toBe($passage2->options_order);
});

test('student sees same order on reload', function () {
    $exam = Exam::factory()
        ->has(Question::factory()->count(5), 'questions')
        ->create(['shuffle_questions' => true]);
    
    $passage = app(StartPassage::class)->execute(
        $exam, 'alice@test.com', 'Alice', 'Test'
    );
    
    $order1 = $passage->questions_order;
    
    // Simuler reload : re-fetch le passage
    $passage->refresh();
    
    expect($passage->questions_order)->toBe($order1);
});

test('answer is correct regardless of display order', function () {
    $question = Question::factory()->create([
        'type' => 'mcq_single_4',
        'options' => [
            ['id' => 'A', 'text' => 'Wrong', 'is_correct' => false],
            ['id' => 'B', 'text' => 'Correct', 'is_correct' => true],
            ['id' => 'C', 'text' => 'Wrong', 'is_correct' => false],
            ['id' => 'D', 'text' => 'Wrong', 'is_correct' => false],
        ],
    ]);
    
    $randomizer = app(RandomizationService::class);
    
    // Student answers with original ID "B"
    expect($randomizer->isAnswerCorrect($question, ['B']))->toBeTrue();
    
    // Student answers with original ID "A" (wrong)
    expect($randomizer->isAnswerCorrect($question, ['A']))->toBeFalse();
});
```

## ✅ CRITÈRES D'ACCEPTATION

- [ ] Migration `preserve_options_order` appliquée
- [ ] Defaults shuffle_questions/options à `true` dans Exam model
- [ ] RandomizationService avec 5+ méthodes
- [ ] Tests Pest > 20 tests passent
- [ ] StartPassage génère questions_order et options_order
- [ ] Controller preview endpoint fonctionne
- [ ] UI prof : toggles avec descriptions claires
- [ ] UI prof : preserve_options_order dans QuestionEditor
- [ ] Composant StudentPreviewModal fonctionnel
- [ ] Preview génère différents ordres avec différents seeds
- [ ] V/F sont mélangés par défaut
- [ ] Tests E2E : 2 étudiants ont des ordres différents
- [ ] Tests E2E : même étudiant a le même ordre après reload

## 📝 COMMITS ATTENDUS

- `feat(question): add preserve_options_order field`
- `chore(exam): set shuffle_questions and shuffle_options to true by default`
- `feat(service): add RandomizationService with deterministic seed`
- `test(service): add 20+ tests for RandomizationService`
- `feat(passage): integrate randomization in StartPassage action`
- `feat(prof): add preview endpoint for student view`
- `feat(prof): add shuffle toggles and preserve_order option in UI`
- `feat(prof): add StudentPreviewModal component`
- `test(e2e): add anti-cheat workflow tests`

**Commence par Tâche 1 (migration) et avance méthodiquement.**
```

---

## 7. Tests Pest

Suite de tests complète à maintenir :

### Tests unitaires `RandomizationService`

- ✅ Ordre questions déterministe pour même passage
- ✅ Ordre différent entre passages
- ✅ Respecte `shuffle_questions = false`
- ✅ Options order respecte `preserve_options_order`
- ✅ V/F mélangés par défaut
- ✅ isAnswerCorrect indépendant de l'ordre
- ✅ Preview génère ordres différents
- ✅ Preview inclut `original_position`
- ✅ Seed stable sur reload
- ✅ Gère questions sans options (edge case)

### Tests E2E

- ✅ 2 étudiants voient ordres différents
- ✅ Même étudiant voit même ordre après reload
- ✅ Réponse correcte indépendante de position affichée
- ✅ Preview prof fonctionne
- ✅ Désactivation shuffle = ordre original

---

## 8. Checklist validation

### Backend
- [ ] Migration `preserve_options_order` appliquée
- [ ] Model Question mis à jour (fillable + casts)
- [ ] Model Exam avec defaults `true` pour shuffle
- [ ] RandomizationService créé et testé (>90% coverage)
- [ ] StartPassage génère les ordres
- [ ] Controller preview avec Policy
- [ ] Route `prof.exams.preview` déclarée
- [ ] isAnswerCorrect utilisé dans scoring

### Frontend
- [ ] UI prof : toggles anti-triche clairs
- [ ] UI prof : explications pédagogiques
- [ ] UI prof : bouton preview visible si shuffle activé
- [ ] QuestionEditor : option preserve_options_order
- [ ] StudentPreviewModal : navigation questions
- [ ] StudentPreviewModal : bouton "générer autre"
- [ ] StudentPreviewModal : position vue + originale

### Sécurité
- [ ] Seed non prévisible (UUID côté serveur)
- [ ] Pas de leak d'ordre d'un étudiant à un autre
- [ ] Préservation sur reload (même seed)
- [ ] Preview prof : droit seulement pour propriétaire examen

### Tests
- [ ] Tests unitaires RandomizationService > 20 tests
- [ ] Tests E2E anti-triche
- [ ] Coverage > 85%
- [ ] Tous passent en CI

### Documentation
- [ ] Ajouter section anti-triche dans `docs/prof/05-distribuer-examen.md`
- [ ] Ajouter FAQ "Comment ça marche la randomisation"
- [ ] Ajouter dans i18n les nouvelles strings

---

## Conclusion

Cette feature est **essentielle** pour la crédibilité de Certio comme plateforme 
d'évaluation sérieuse. Elle est :

- ✅ **Activée par défaut** (pas de config nécessaire)
- ✅ **Transparente** pour l'étudiant (UX identique)
- ✅ **Robuste** (seed déterministe, reproductible)
- ✅ **Flexible** (preserve_order pour cas spéciaux)
- ✅ **Vérifiable** (preview prof)
- ✅ **Testée** (unit + E2E)

**Impact planning** : 0 jour additionnel (réparti sur P1, P2, P3).

**Valeur ajoutée** : protection anti-triche **industry-standard** qui rassurera 
les écoles soucieuses de l'intégrité des évaluations.

---

© 2026 Mohamed EL AFRIT — mohamed@elafrit.com  
Certio v2.0 Laravel — CC BY-NC-SA 4.0
