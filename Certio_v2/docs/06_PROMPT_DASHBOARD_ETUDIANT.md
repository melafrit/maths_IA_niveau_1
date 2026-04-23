# 🎓 Prompt VS Code — Dashboard Étudiant & Corrections détaillées

> **Prompt autosuffisant pour implémenter cette feature en Laravel**  
> **Intégration dans phase P4 (jours 4-8) du planning révisé**

| Champ | Valeur |
|---|---|
| **Document** | Prompt VS Code pour Claude Code / Cursor |
| **Durée** | 3 jours (jours 4-5-6-7-8 de P4) |
| **Version** | 1.0 |
| **Auteur** | Mohamed EL AFRIT |
| **Licence** | CC BY-NC-SA 4.0 |

---

## 🎯 À copier-coller dans Claude Code / Cursor

```
# CONTEXTE — CERTIO v2.0 LARAVEL — FEATURE DASHBOARD ÉTUDIANT

Je continue le développement de Certio v2.0 Laravel.

Les phases P-1 à P3 + début de P4 sont terminées. Tu as maintenant :
- CBM paramétrable + duplication examen
- 7 types de questions + multi-select
- Scoring service avec 3 modes
- Analytics prof (calibration, distracteurs, radar)

## 📚 Documents de référence
- `certio_v2_laravel/01_NOTE_DE_CADRAGE_LARAVEL.md`
- `certio_v2_laravel/02_PLANNING_LARAVEL_REVISE.md`
- `certio_v2_laravel/05_ADDENDUM_DASHBOARD_ETUDIANT.md` (spec complète)

## 🎯 Objectif (3 jours, en extension de P4)

Implémenter le Dashboard Étudiant complet avec :
1. Contrôle prof sur visibilité corrections (paramétrable)
2. Publication manuelle corrections avec notifications email
3. Dashboard étudiant avec KPIs + graphiques
4. Historique complet avec filtres
5. Page correction détaillée avec explications + pièges + ressources
6. Enrichissement questions (why_wrong + reference)

---

## 📋 TÂCHE 1 — Migrations BDD (30 min)

### 1.1 Migration correction_visibility sur exams

```bash
php artisan make:migration add_corrections_visibility_to_exams_table
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->enum('correction_visibility', [
                'never',
                'auto_after_submit',
                'auto_after_close',
                'manual'
            ])->default('manual')->after('passing_score');
            
            $table->timestamp('corrections_published_at')
                ->nullable()
                ->after('correction_visibility');
            
            $table->foreignId('corrections_published_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('corrections_published_at');
        });
    }
    
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['corrections_published_by']);
            $table->dropColumn([
                'correction_visibility',
                'corrections_published_at',
                'corrections_published_by',
            ]);
        });
    }
};
```

### 1.2 Migration reference sur questions

```bash
php artisan make:migration add_reference_to_questions_table
```

```php
public function up(): void
{
    Schema::table('questions', function (Blueprint $table) {
        $table->json('reference')->nullable()->after('explanation');
    });
}
```

### 1.3 Migration correction_visible sur passages (pour publication individuelle)

```bash
php artisan make:migration add_correction_visible_to_passages_table
```

```php
public function up(): void
{
    Schema::table('passages', function (Blueprint $table) {
        $table->boolean('correction_visible')->default(false);
        $table->timestamp('correction_published_at')->nullable();
        $table->foreignId('correction_published_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();
    });
}
```

Exécuter :
```bash
php artisan migrate
```

---

## 📋 TÂCHE 2 — Enum CorrectionVisibility (15 min)

Créer `app/Enums/CorrectionVisibility.php` :

```php
<?php
namespace App\Enums;

enum CorrectionVisibility: string
{
    case Never = 'never';
    case AutoAfterSubmit = 'auto_after_submit';
    case AutoAfterClose = 'auto_after_close';
    case Manual = 'manual';
    
    public function label(): string
    {
        return match($this) {
            self::Never => 'Jamais visibles',
            self::AutoAfterSubmit => 'Auto après soumission',
            self::AutoAfterClose => 'Auto après clôture',
            self::Manual => 'Publication manuelle par le prof',
        };
    }
    
    public function description(): string
    {
        return match($this) {
            self::Never => "Les étudiants ne verront jamais les corrections",
            self::AutoAfterSubmit => "Dès qu'un étudiant soumet, il voit sa correction",
            self::AutoAfterClose => "Après la date de clôture de l'examen",
            self::Manual => "Je déciderai quand publier (recommandé)",
        };
    }
    
    public function isAutomatic(): bool
    {
        return match($this) {
            self::AutoAfterSubmit, self::AutoAfterClose => true,
            default => false,
        };
    }
}
```

Mettre à jour le cast dans `app/Models/Exam.php` :

```php
protected $casts = [
    // ... autres casts ...
    'correction_visibility' => \App\Enums\CorrectionVisibility::class,
    'corrections_published_at' => 'datetime',
];
```

---

## 📋 TÂCHE 3 — PassagePolicy (20 min)

Mettre à jour `app/Policies/PassagePolicy.php` avec méthode `viewCorrection` :

```php
<?php
namespace App\Policies;

use App\Models\Passage;
use App\Models\User;
use App\Enums\CorrectionVisibility;
use App\Enums\PassageStatus;

class PassagePolicy
{
    /**
     * Un étudiant peut voir la correction d'un passage si :
     * 1. Le passage lui appartient (même user_id OU même email)
     * 2. Le passage est soumis
     * 3. Selon la config de l'examen, la correction est accessible
     */
    public function viewCorrection(User $user, Passage $passage): bool
    {
        // Vérifier appartenance
        $isOwner = $user->id === $passage->user_id 
            || $user->email === $passage->student_email;
        
        if (!$isOwner) {
            // Sauf si prof/admin du workspace
            $canSuperView = in_array($user->role, ['admin', 'super_admin', 'enseignant'])
                && $user->workspace_id === $passage->workspace_id;
            
            if (!$canSuperView) return false;
        }
        
        // Vérifier statut
        if ($passage->status !== PassageStatus::Submitted) return false;
        
        // Vérifier visibilité selon config examen
        $exam = $passage->exam;
        $visibility = $exam->correction_visibility;
        
        // Publication individuelle par le prof (cas par cas)
        if ($passage->correction_visible) return true;
        
        return match($visibility) {
            CorrectionVisibility::Never => false,
            CorrectionVisibility::AutoAfterSubmit => true,
            CorrectionVisibility::AutoAfterClose => 
                $exam->date_cloture && $exam->date_cloture->isPast(),
            CorrectionVisibility::Manual => 
                !is_null($exam->corrections_published_at),
        };
    }
}
```

---

## 📋 TÂCHE 4 — StudentDashboardService (2h)

Créer `app/Services/StudentDashboardService.php` :

```php
<?php
namespace App\Services;

use App\Models\User;
use App\Models\Passage;
use Illuminate\Support\Collection;

class StudentDashboardService
{
    public function __construct(
        private CbmScoringService $cbmService,
    ) {}
    
    /**
     * KPIs globaux de l'étudiant.
     */
    public function getGlobalKpis(User $student): array
    {
        $passages = $this->getViewablePassages($student);
        
        if ($passages->isEmpty()) {
            return [
                'total_exams' => 0,
                'average_score' => null,
                'trend_percentage' => null,
                'calibration' => null,
                'strongest_theme' => null,
                'weakest_theme' => null,
            ];
        }
        
        $avgScore = $passages->avg('score_percentage');
        $trend = $this->calculateTrend($passages);
        
        $cbmPassages = $passages->filter(fn($p) => $p->exam->cbm_enabled);
        $calibration = $cbmPassages->isNotEmpty()
            ? $this->cbmService->calculateCalibration($cbmPassages)
            : null;
        
        $themeStats = $this->calculateThemeStats($passages);
        $themes = collect($themeStats)->filter(fn($t) => $t['total'] >= 3);
        
        return [
            'total_exams' => $passages->count(),
            'average_score' => round($avgScore, 1),
            'trend_percentage' => $trend,
            'calibration' => $calibration,
            'strongest_theme' => $themes->sortByDesc('success_rate')->first(),
            'weakest_theme' => $themes->sortBy('success_rate')->first(),
        ];
    }
    
    /**
     * Données progression dans le temps (line chart).
     */
    public function getProgressData(User $student): array
    {
        return $this->getViewablePassages($student)
            ->sortBy('submitted_at')
            ->values()
            ->map(fn($p) => [
                'date' => $p->submitted_at->format('Y-m-d'),
                'date_display' => $p->submitted_at->format('d/m/Y'),
                'score' => round($p->score_percentage, 1),
                'exam_title' => $p->exam->title,
                'exam_uuid' => $p->uuid,
                'passed' => $p->score_percentage >= $p->exam->passing_score,
            ])
            ->toArray();
    }
    
    /**
     * Données radar par thème.
     */
    public function getRadarData(User $student): array
    {
        $passages = $this->getViewablePassages($student);
        return $this->calculateThemeStats($passages);
    }
    
    /**
     * Historique complet avec filtres.
     */
    public function getHistory(User $student, array $filters = []): Collection
    {
        $query = $student->passages()
            ->where('status', 'submitted')
            ->with('exam');
        
        if ($from = $filters['from'] ?? null) {
            $query->where('submitted_at', '>=', $from);
        }
        
        if ($to = $filters['to'] ?? null) {
            $query->where('submitted_at', '<=', $to);
        }
        
        if ($minScore = $filters['min_score'] ?? null) {
            $query->where('score_percentage', '>=', $minScore);
        }
        
        if ($module = $filters['module'] ?? null) {
            $query->whereHas('exam', fn($q) => $q->where('module', $module));
        }
        
        return $query->latest('submitted_at')->get()
            ->map(fn($p) => [
                'uuid' => $p->uuid,
                'exam_uuid' => $p->exam->uuid,
                'exam_title' => $p->exam->title,
                'module' => $p->exam->module ?? null,
                'submitted_at' => $p->submitted_at,
                'duration_minutes' => $p->getDurationMinutes(),
                'score_percentage' => round($p->score_percentage, 1),
                'passed' => $p->score_percentage >= $p->exam->passing_score,
                'can_view_correction' => $this->canViewCorrection($student, $p),
            ]);
    }
    
    /**
     * Détail complet d'une correction.
     */
    public function getCorrection(Passage $passage): array
    {
        $exam = $passage->exam;
        $questions = $exam->questions;
        $answers = $passage->answers ?? [];
        $corrections = [];
        
        foreach ($questions as $i => $question) {
            $answer = $answers[$question->id] ?? null;
            if (!$answer) continue;
            
            $correctIds = collect($question->options)
                ->where('is_correct', true)
                ->pluck('id')
                ->toArray();
            
            $selectedIds = $answer['selected_options'] ?? [];
            $isCorrect = $answer['is_correct'] ?? false;
            
            // Analyser les distracteurs choisis
            $distractorsChosen = [];
            foreach ($selectedIds as $selectedId) {
                $option = collect($question->options)
                    ->firstWhere('id', $selectedId);
                
                if ($option && !$option['is_correct']) {
                    $distractorsChosen[] = [
                        'option_id' => $option['id'],
                        'text' => $option['text'],
                        'why_wrong' => $option['why_wrong'] ?? null,
                    ];
                }
            }
            
            $corrections[] = [
                'question_number' => $i + 1,
                'total_questions' => $questions->count(),
                'question' => [
                    'uuid' => $question->uuid,
                    'type' => $question->type->value,
                    'type_label' => $question->type->label(),
                    'statement' => $question->statement,
                    'options' => $question->options,
                    'difficulty' => $question->difficulty,
                    'module' => $question->module,
                    'chapitre' => $question->chapitre,
                    'theme' => $question->theme,
                ],
                'student_answer' => [
                    'selected_options' => $selectedIds,
                    'cbm_level_id' => $answer['cbm_level_id'] ?? null,
                    'time_spent_seconds' => isset($answer['time_spent_ms']) 
                        ? round($answer['time_spent_ms'] / 1000, 1) 
                        : null,
                ],
                'correct_option_ids' => $correctIds,
                'is_correct' => $isCorrect,
                'score_awarded' => $answer['final_score'] ?? 0,
                'explanation' => $question->explanation,
                'distractors_chosen' => $distractorsChosen,
                'reference' => $question->reference,
            ];
        }
        
        return [
            'passage' => [
                'uuid' => $passage->uuid,
                'submitted_at' => $passage->submitted_at,
                'duration_minutes' => $passage->getDurationMinutes(),
                'score_raw' => $passage->score_raw,
                'score_max' => $passage->score_max,
                'score_percentage' => round($passage->score_percentage, 1),
                'passed' => $passage->score_percentage >= $exam->passing_score,
                'cbm_calibration' => $passage->cbm_calibration,
            ],
            'exam' => [
                'title' => $exam->title,
                'description' => $exam->description,
                'cbm_enabled' => $exam->cbm_enabled,
                'cbm_matrix' => $exam->cbm_matrix,
                'passing_score' => $exam->passing_score,
            ],
            'corrections' => $corrections,
            'summary' => [
                'correct_count' => collect($corrections)->where('is_correct', true)->count(),
                'total_count' => count($corrections),
                'success_rate' => count($corrections) > 0 
                    ? round(collect($corrections)->where('is_correct', true)->count() / count($corrections) * 100, 1) 
                    : 0,
            ],
        ];
    }
    
    /**
     * Vérifie si l'étudiant peut voir la correction.
     */
    public function canViewCorrection(User $student, Passage $passage): bool
    {
        return $student->can('viewCorrection', $passage);
    }
    
    // ─── Méthodes privées ───
    
    private function getViewablePassages(User $student): Collection
    {
        return $student->passages()
            ->where('status', 'submitted')
            ->with(['exam.questions'])
            ->get();
    }
    
    private function calculateTrend(Collection $passages): ?float
    {
        if ($passages->count() < 6) return null;
        
        $sorted = $passages->sortByDesc('submitted_at');
        $recent = $sorted->take(3);
        $previous = $sorted->slice(3, 3);
        
        $recentAvg = $recent->avg('score_percentage');
        $previousAvg = $previous->avg('score_percentage');
        
        return round($recentAvg - $previousAvg, 1);
    }
    
    private function calculateThemeStats(Collection $passages): array
    {
        $themes = [];
        
        foreach ($passages as $passage) {
            foreach (($passage->answers ?? []) as $qId => $answer) {
                $question = $passage->exam->questions->firstWhere('id', $qId);
                if (!$question || !$question->theme) continue;
                
                $theme = $question->theme;
                if (!isset($themes[$theme])) {
                    $themes[$theme] = ['total' => 0, 'correct' => 0];
                }
                
                $themes[$theme]['total']++;
                if ($answer['is_correct'] ?? false) {
                    $themes[$theme]['correct']++;
                }
            }
        }
        
        return collect($themes)
            ->map(fn($stats, $theme) => [
                'theme' => $theme,
                'total' => $stats['total'],
                'correct' => $stats['correct'],
                'success_rate' => $stats['total'] > 0 
                    ? round(($stats['correct'] / $stats['total']) * 100, 1)
                    : 0,
            ])
            ->values()
            ->toArray();
    }
}
```

Tests Pest dans `tests/Feature/Student/StudentDashboardServiceTest.php` (20+ tests).

---

## 📋 TÂCHE 5 — Action PublishCorrections (1h)

Créer `app/Actions/Exam/PublishCorrections.php` :

```php
<?php
namespace App\Actions\Exam;

use App\Models\Exam;
use App\Models\Passage;
use App\Models\User;
use App\Enums\CorrectionVisibility;
use App\Mail\CorrectionsAvailableMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PublishCorrections
{
    /**
     * Publie les corrections pour tous les étudiants d'un examen.
     */
    public function executeForExam(Exam $exam, User $publisher): int
    {
        return DB::transaction(function () use ($exam, $publisher) {
            $exam->update([
                'corrections_published_at' => now(),
                'corrections_published_by' => $publisher->id,
            ]);
            
            $passages = $exam->passages()
                ->where('status', 'submitted')
                ->get();
            
            foreach ($passages as $passage) {
                Mail::to($passage->student_email)
                    ->queue(new CorrectionsAvailableMail($passage));
            }
            
            activity()->causedBy($publisher)
                ->performedOn($exam)
                ->withProperties([
                    'passages_count' => $passages->count(),
                ])
                ->log('exam.corrections_published_all');
            
            return $passages->count();
        });
    }
    
    /**
     * Publie la correction pour un passage individuel.
     */
    public function executeForPassage(Passage $passage, User $publisher): void
    {
        DB::transaction(function () use ($passage, $publisher) {
            $passage->update([
                'correction_visible' => true,
                'correction_published_at' => now(),
                'correction_published_by' => $publisher->id,
            ]);
            
            Mail::to($passage->student_email)
                ->queue(new CorrectionsAvailableMail($passage));
            
            activity()->causedBy($publisher)
                ->performedOn($passage)
                ->log('passage.correction_published_individual');
        });
    }
    
    /**
     * Retire la publication des corrections pour un examen.
     */
    public function unpublishForExam(Exam $exam, User $publisher): void
    {
        $exam->update([
            'corrections_published_at' => null,
            'corrections_published_by' => null,
        ]);
        
        $exam->passages()->update([
            'correction_visible' => false,
        ]);
        
        activity()->causedBy($publisher)
            ->performedOn($exam)
            ->log('exam.corrections_unpublished');
    }
}
```

---

## 📋 TÂCHE 6 — Email Notification (45 min)

Créer `app/Mail/CorrectionsAvailableMail.php` :

```bash
php artisan make:mail CorrectionsAvailableMail --markdown=emails.corrections-available
```

```php
<?php
namespace App\Mail;

use App\Models\Passage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CorrectionsAvailableMail extends Mailable
{
    use Queueable, SerializesModels;
    
    public function __construct(public Passage $passage) {}
    
    public function build()
    {
        return $this->subject('Vos corrections sont disponibles 📚')
            ->markdown('emails.corrections-available', [
                'student_name' => $this->passage->student_prenom 
                    ?: $this->passage->student_nom 
                    ?: 'Étudiant',
                'exam_title' => $this->passage->exam->title,
                'score' => round($this->passage->score_percentage, 1),
                'correction_url' => route('student.passages.correction', $this->passage->uuid),
                'dashboard_url' => route('student.dashboard'),
            ]);
    }
}
```

Créer `resources/views/emails/corrections-available.blade.php` :

```blade
@component('mail::message')
# Vos corrections sont disponibles ! 📚

Bonjour {{ $student_name }},

Votre professeur vient de publier les corrections de l'examen :

## {{ $exam_title }}

**Votre score : {{ $score }}%**

@component('mail::button', ['url' => $correction_url])
Voir la correction détaillée
@endcomponent

Cette correction inclut :
- ✅ Explications détaillées pour chaque question
- ⚠️ Pièges à éviter (pourquoi les autres options étaient fausses)
- 📚 Liens vers les ressources du cours

[Voir tout mon historique]({{ $dashboard_url }})

Bonne révision !

Cordialement,<br>
L'équipe {{ config('app.name') }}
@endcomponent
```

---

## 📋 TÂCHE 7 — Controllers + Routes Étudiant (1h)

Créer `app/Http/Controllers/Student/DashboardController.php` :

```php
<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentDashboardService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private StudentDashboardService $service) {}
    
    public function index()
    {
        $student = auth()->user();
        
        return Inertia::render('Student/Dashboard', [
            'kpis' => $this->service->getGlobalKpis($student),
            'progress' => $this->service->getProgressData($student),
            'radar' => $this->service->getRadarData($student),
            'recent_passages' => $this->service->getHistory($student, [])->take(5),
        ]);
    }
}
```

Créer `app/Http/Controllers/Student/PassageController.php` :

```php
<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Passage;
use App\Services\StudentDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PassageController extends Controller
{
    public function __construct(private StudentDashboardService $service) {}
    
    public function history(Request $request)
    {
        $student = auth()->user();
        $filters = $request->only(['from', 'to', 'min_score', 'module']);
        
        return Inertia::render('Student/History', [
            'history' => $this->service->getHistory($student, $filters),
            'filters' => $filters,
        ]);
    }
    
    public function show(Passage $passage)
    {
        $this->authorize('view', $passage);
        
        return Inertia::render('Student/Passage/Show', [
            'passage' => [
                'uuid' => $passage->uuid,
                'exam_title' => $passage->exam->title,
                'score_percentage' => round($passage->score_percentage, 1),
                'submitted_at' => $passage->submitted_at,
                'duration_minutes' => $passage->getDurationMinutes(),
                'cbm_enabled' => $passage->exam->cbm_enabled,
                'cbm_calibration' => $passage->cbm_calibration,
                'can_view_correction' => auth()->user()->can('viewCorrection', $passage),
            ],
        ]);
    }
    
    public function correction(Passage $passage)
    {
        $this->authorize('viewCorrection', $passage);
        
        return Inertia::render('Student/Passage/Correction', [
            'data' => $this->service->getCorrection($passage),
        ]);
    }
}
```

Routes dans `routes/web.php` :

```php
Route::middleware(['auth', 'verified'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/history', [PassageController::class, 'history'])->name('history');
    Route::get('/passages/{passage:uuid}', [PassageController::class, 'show'])->name('passages.show');
    Route::get('/passages/{passage:uuid}/correction', [PassageController::class, 'correction'])->name('passages.correction');
});
```

---

## 📋 TÂCHE 8 — UI Vue : Page Dashboard (2h)

Créer `resources/js/Pages/Student/Dashboard.vue` :

```vue
<script setup>
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import KpiCard from '@/Components/Student/KpiCard.vue'
import ProgressChart from '@/Components/Student/ProgressChart.vue'
import ThemeRadarChart from '@/Components/Student/ThemeRadarChart.vue'

const props = defineProps({
  kpis: Object,
  progress: Array,
  radar: Array,
  recent_passages: Array,
})
</script>

<template>
  <StudentLayout>
    <div class="max-w-7xl mx-auto p-6 space-y-8">
      <header>
        <h1 class="text-3xl font-bold">🎓 Mon espace étudiant</h1>
        <p class="text-gray-600">Bienvenue, {{ $page.props.auth.user.name }}</p>
      </header>
      
      <!-- KPIs -->
      <section>
        <h2 class="text-xl font-semibold mb-4">📊 Vue d'ensemble</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <KpiCard 
            title="Examens passés" 
            :value="kpis.total_exams" 
            icon="📝"
          />
          <KpiCard 
            v-if="kpis.average_score !== null"
            title="Score moyen" 
            :value="`${kpis.average_score}%`"
            :trend="kpis.trend_percentage"
            icon="📊"
          />
          <KpiCard 
            v-if="kpis.calibration"
            title="Calibration CBM" 
            :value="kpis.calibration.tendency"
            icon="🎯"
          />
          <KpiCard 
            v-if="kpis.strongest_theme"
            title="Thème fort" 
            :value="kpis.strongest_theme.theme"
            :subtitle="`${kpis.strongest_theme.success_rate}%`"
            icon="⭐"
          />
        </div>
      </section>
      
      <!-- Progression -->
      <section v-if="progress.length > 0">
        <h2 class="text-xl font-semibold mb-4">📈 Ma progression</h2>
        <div class="bg-white rounded-lg p-6 shadow">
          <ProgressChart :data="progress" />
        </div>
      </section>
      
      <!-- Radar par thème -->
      <section v-if="radar.length > 0">
        <h2 class="text-xl font-semibold mb-4">🎯 Performance par thème</h2>
        <div class="bg-white rounded-lg p-6 shadow">
          <ThemeRadarChart :data="radar" />
        </div>
      </section>
      
      <!-- Examens récents -->
      <section>
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-semibold">📚 Mes examens récents</h2>
          <Link :href="route('student.history')" class="text-blue-600 hover:underline">
            Voir tout l'historique →
          </Link>
        </div>
        
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div v-for="p in recent_passages" :key="p.uuid" 
               class="border-b last:border-b-0 p-4 hover:bg-gray-50">
            <div class="flex items-center justify-between">
              <div>
                <div class="flex items-center gap-2">
                  <span v-if="p.passed" class="text-green-600">✅</span>
                  <span v-else-if="p.score_percentage >= 40" class="text-yellow-600">⚠️</span>
                  <span v-else class="text-red-600">❌</span>
                  <h3 class="font-medium">{{ p.exam_title }}</h3>
                </div>
                <p class="text-sm text-gray-600">
                  {{ new Date(p.submitted_at).toLocaleDateString('fr-FR') }}
                  • {{ p.duration_minutes }} min
                </p>
              </div>
              
              <div class="flex items-center gap-4">
                <div class="text-right">
                  <div class="text-2xl font-bold" :class="{
                    'text-green-600': p.passed,
                    'text-yellow-600': !p.passed && p.score_percentage >= 40,
                    'text-red-600': p.score_percentage < 40,
                  }">
                    {{ p.score_percentage }}%
                  </div>
                </div>
                
                <Link v-if="p.can_view_correction" 
                      :href="route('student.passages.correction', p.uuid)"
                      class="btn btn-primary">
                  📖 Voir correction
                </Link>
                <span v-else class="text-sm text-gray-500">
                  🔒 Correction non publiée
                </span>
              </div>
            </div>
          </div>
          
          <div v-if="recent_passages.length === 0" class="p-8 text-center text-gray-500">
            Vous n'avez pas encore passé d'examen.
          </div>
        </div>
      </section>
    </div>
  </StudentLayout>
</template>
```

---

## 📋 TÂCHE 9 — UI Vue : Page Correction détaillée (3h)

Créer `resources/js/Pages/Student/Passage/Correction.vue` :

```vue
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import QuestionCorrection from '@/Components/Student/QuestionCorrection.vue'

const props = defineProps({
  data: Object, // Retour de StudentDashboardService::getCorrection
})

const currentIndex = ref(0)
const currentCorrection = computed(() => props.data.corrections[currentIndex.value])

function previousQuestion() {
  if (currentIndex.value > 0) currentIndex.value--
}

function nextQuestion() {
  if (currentIndex.value < props.data.corrections.length - 1) currentIndex.value++
}

function goToQuestion(index) {
  currentIndex.value = index
}

// Raccourcis clavier
onMounted(() => {
  window.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') previousQuestion()
    if (e.key === 'ArrowRight') nextQuestion()
  })
})
</script>

<template>
  <StudentLayout>
    <div class="max-w-5xl mx-auto p-6">
      <!-- Header -->
      <header class="mb-6">
        <Link :href="route('student.history')" class="text-blue-600 hover:underline mb-2 inline-block">
          ← Retour à l'historique
        </Link>
        <h1 class="text-3xl font-bold">📖 Correction : {{ data.exam.title }}</h1>
        <p class="text-gray-600">
          Passé le {{ new Date(data.passage.submitted_at).toLocaleDateString('fr-FR', { 
            day: 'numeric', month: 'long', year: 'numeric' 
          }) }}
          — Score : <strong>{{ data.passage.score_percentage }}%</strong>
          ({{ data.summary.correct_count }}/{{ data.summary.total_count }})
        </p>
      </header>
      
      <!-- Résumé -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h2 class="font-semibold mb-2">📊 Résumé</h2>
        <div class="grid grid-cols-3 gap-4">
          <div>
            <div class="text-sm text-gray-600">Bonnes réponses</div>
            <div class="text-xl font-bold">
              {{ data.summary.correct_count }} / {{ data.summary.total_count }}
            </div>
          </div>
          <div>
            <div class="text-sm text-gray-600">Taux de réussite</div>
            <div class="text-xl font-bold">{{ data.summary.success_rate }}%</div>
          </div>
          <div v-if="data.exam.cbm_enabled">
            <div class="text-sm text-gray-600">Calibration CBM</div>
            <div class="text-xl font-bold capitalize">
              {{ data.passage.cbm_calibration?.tendency || 'N/A' }}
            </div>
          </div>
        </div>
      </div>
      
      <!-- Navigation questions (pastilles) -->
      <div class="flex flex-wrap gap-2 mb-6">
        <button
          v-for="(c, i) in data.corrections"
          :key="i"
          @click="goToQuestion(i)"
          :class="[
            'w-10 h-10 rounded-full font-bold border-2 transition',
            i === currentIndex 
              ? 'border-blue-600 bg-blue-600 text-white' 
              : c.is_correct
                ? 'border-green-400 bg-green-50 text-green-700 hover:bg-green-100'
                : 'border-red-400 bg-red-50 text-red-700 hover:bg-red-100'
          ]"
          :aria-label="`Question ${i + 1}`"
        >
          {{ i + 1 }}
        </button>
      </div>
      
      <!-- Correction de la question courante -->
      <QuestionCorrection 
        :correction="currentCorrection" 
        :cbm-enabled="data.exam.cbm_enabled"
        :cbm-matrix="data.exam.cbm_matrix"
      />
      
      <!-- Navigation prev/next -->
      <div class="flex justify-between mt-6">
        <button 
          @click="previousQuestion" 
          :disabled="currentIndex === 0"
          class="btn btn-secondary disabled:opacity-50"
        >
          ← Question précédente
        </button>
        
        <span class="text-gray-600 self-center">
          Question {{ currentIndex + 1 }} / {{ data.corrections.length }}
        </span>
        
        <button 
          @click="nextQuestion" 
          :disabled="currentIndex === data.corrections.length - 1"
          class="btn btn-primary disabled:opacity-50"
        >
          Question suivante →
        </button>
      </div>
      
      <!-- Raccourcis clavier info -->
      <p class="text-sm text-gray-500 text-center mt-4">
        💡 Utilisez <kbd>←</kbd> et <kbd>→</kbd> pour naviguer
      </p>
    </div>
  </StudentLayout>
</template>
```

---

## 📋 TÂCHE 10 — Composant QuestionCorrection (2h)

Créer `resources/js/Components/Student/QuestionCorrection.vue` :

```vue
<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  correction: Object,
  cbmEnabled: Boolean,
  cbmMatrix: Object,
})

const studentCbmLevel = computed(() => {
  if (!props.cbmMatrix || !props.correction.student_answer.cbm_level_id) return null
  return props.cbmMatrix.levels.find(
    l => l.id === props.correction.student_answer.cbm_level_id
  )
})

function isSelected(optionId) {
  return props.correction.student_answer.selected_options.includes(optionId)
}

function isCorrectOption(optionId) {
  return props.correction.correct_option_ids.includes(optionId)
}
</script>

<template>
  <article class="bg-white rounded-lg shadow p-6 space-y-6">
    <!-- Header question -->
    <div class="border-b pb-4">
      <div class="flex items-center gap-2 mb-2">
        <span class="text-sm text-gray-600">
          Question {{ correction.question_number }} / {{ correction.total_questions }}
        </span>
        <span v-if="correction.question.module" 
              class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">
          {{ correction.question.module }}
        </span>
        <span class="text-xs bg-gray-100 px-2 py-1 rounded">
          Difficulté : {{ correction.question.difficulty }}
        </span>
      </div>
      
      <h2 class="text-lg font-medium" v-html="correction.question.statement"></h2>
    </div>
    
    <!-- Réponses -->
    <div>
      <h3 class="font-semibold mb-3">Les options :</h3>
      <div class="space-y-2">
        <div 
          v-for="opt in correction.question.options" 
          :key="opt.id"
          :class="[
            'p-3 rounded-lg border-2',
            isSelected(opt.id) && isCorrectOption(opt.id) 
              ? 'border-green-500 bg-green-50' 
              : isSelected(opt.id) && !isCorrectOption(opt.id)
                ? 'border-red-500 bg-red-50'
                : isCorrectOption(opt.id)
                  ? 'border-green-400 bg-green-50/50'
                  : 'border-gray-200'
          ]"
        >
          <div class="flex items-start gap-2">
            <span class="font-bold">{{ opt.id }}.</span>
            <span class="flex-1" v-html="opt.text"></span>
            <span v-if="isSelected(opt.id)" class="text-sm font-semibold">
              👤 Ta réponse
            </span>
            <span v-if="isCorrectOption(opt.id)" class="text-green-600 text-xl">✓</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- CBM info -->
    <div v-if="cbmEnabled && studentCbmLevel" class="bg-purple-50 p-3 rounded">
      <p class="text-sm">
        🎯 Ta certitude : <strong>{{ studentCbmLevel.label }}</strong>
        ({{ studentCbmLevel.value }}%)
        — Score CBM obtenu : <strong>{{ correction.score_awarded }}</strong>
      </p>
    </div>
    
    <!-- Explication -->
    <div v-if="correction.explanation" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <h3 class="font-semibold mb-2 flex items-center gap-2">
        💡 <span>Explication</span>
      </h3>
      <div class="prose prose-sm max-w-none" v-html="correction.explanation"></div>
    </div>
    
    <!-- Pièges à éviter -->
    <div v-if="correction.distractors_chosen.length > 0" 
         class="bg-orange-50 border border-orange-200 rounded-lg p-4">
      <h3 class="font-semibold mb-2 flex items-center gap-2">
        ⚠️ <span>Pièges à éviter (tes réponses fausses)</span>
      </h3>
      <div class="space-y-2">
        <div v-for="d in correction.distractors_chosen" :key="d.option_id" class="bg-white p-3 rounded">
          <p class="font-medium mb-1">Option {{ d.option_id }} : {{ d.text }}</p>
          <p v-if="d.why_wrong" class="text-sm text-gray-700" v-html="d.why_wrong"></p>
          <p v-else class="text-sm text-gray-500 italic">
            Pas d'explication fournie par le professeur
          </p>
        </div>
      </div>
    </div>
    
    <!-- Ressources -->
    <div v-if="correction.reference" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
      <h3 class="font-semibold mb-2 flex items-center gap-2">
        📚 <span>Pour réviser</span>
      </h3>
      <div class="space-y-1 text-sm">
        <p v-if="correction.reference.module">
          <strong>Module :</strong> {{ correction.reference.module }}
        </p>
        <p v-if="correction.reference.chapitre">
          <strong>Chapitre :</strong> {{ correction.reference.chapitre }}
        </p>
        <p v-if="correction.reference.section">
          <strong>Section :</strong> {{ correction.reference.section }}
        </p>
        <p v-if="correction.reference.doc_path">
          <Link :href="`/docs/${correction.reference.doc_path}`" 
                class="text-blue-600 hover:underline">
            → Consulter le cours dans la documentation
          </Link>
        </p>
        <p v-if="correction.reference.external_url">
          <a :href="correction.reference.external_url" 
             target="_blank" rel="noopener"
             class="text-blue-600 hover:underline">
            → Ressource externe ↗
          </a>
        </p>
      </div>
    </div>
  </article>
</template>
```

---

## 📋 TÂCHE 11 — UI Prof : Paramètre visibility + bouton publier (2h)

Dans `resources/js/Pages/Prof/Exams/Create.vue` (et Edit.vue), ajouter section :

```vue
<!-- Dans le formulaire, section Configuration -->
<FormSection title="🔓 Visibilité des corrections">
  <RadioGroup v-model="form.correction_visibility" :options="[
    { value: 'never', label: 'Jamais', description: 'Les étudiants ne verront jamais les corrections' },
    { value: 'auto_after_submit', label: 'Auto après soumission', description: 'Dès qu\'un étudiant soumet, il voit sa correction' },
    { value: 'auto_after_close', label: 'Auto après clôture', description: 'Après la date de clôture de l\'examen' },
    { value: 'manual', label: 'Manuel (recommandé)', description: 'Je déciderai quand publier' },
  ]" />
</FormSection>
```

Dans `resources/js/Pages/Prof/Exams/Passages.vue` (suivi des passages), ajouter en haut :

```vue
<section v-if="exam.correction_visibility === 'manual'" class="mb-4">
  <div v-if="!exam.corrections_published_at" class="bg-orange-50 p-4 rounded border border-orange-200">
    <div class="flex justify-between items-center">
      <div>
        <h3 class="font-semibold">🔒 Corrections non publiées</h3>
        <p class="text-sm">Les étudiants ne voient pas leurs corrections pour le moment.</p>
      </div>
      <Button @click="publishAll" :loading="publishing" class="btn-primary">
        📢 Publier les corrections à tous
      </Button>
    </div>
  </div>
  <div v-else class="bg-green-50 p-4 rounded border border-green-200">
    <h3 class="font-semibold">✅ Corrections publiées</h3>
    <p class="text-sm">
      Publiées le {{ new Date(exam.corrections_published_at).toLocaleDateString('fr-FR') }}
    </p>
    <Button @click="unpublishAll" variant="secondary" size="sm">
      Retirer la publication
    </Button>
  </div>
</section>
```

---

## 📋 TÂCHE 12 — Éditeur question : why_wrong + reference (1h)

Enrichir `resources/js/Components/QuestionEditor.vue` pour ajouter :

1. Champ `why_wrong` par option **fausse** :

```vue
<div v-for="(opt, i) in form.options" :key="i" class="option-editor">
  <input v-model="opt.text" placeholder="Texte de l'option" />
  <label>
    <input type="radio/checkbox" v-model="opt.is_correct" />
    Correcte
  </label>
  
  <!-- Nouveau champ : why_wrong (seulement si FAUSSE) -->
  <textarea v-if="!opt.is_correct" v-model="opt.why_wrong"
            placeholder="Pourquoi c'est faux ? (pour aider les étudiants)"
            class="mt-2 text-sm" />
</div>
```

2. Section `reference` :

```vue
<FormSection title="📚 Référence pédagogique (optionnel)">
  <input v-model="form.reference.module" placeholder="Module (ex: Maths pour l'IA)" />
  <input v-model="form.reference.chapitre" placeholder="Chapitre (ex: Chapitre 2)" />
  <input v-model="form.reference.section" placeholder="Section (ex: 2.3 Dérivées)" />
  <input v-model="form.reference.doc_path" placeholder="Doc Certio (ex: student/maths-derivees)" />
  <input v-model="form.reference.external_url" placeholder="URL externe (YouTube, article...)" />
</FormSection>
```

---

## ✅ CRITÈRES D'ACCEPTATION

- [ ] 3 migrations appliquées (correction_visibility, reference, correction_visible)
- [ ] Enum CorrectionVisibility avec label/description
- [ ] PassagePolicy::viewCorrection complète avec tous les cas
- [ ] StudentDashboardService avec 10+ méthodes testées
- [ ] Action PublishCorrections (exam et passage)
- [ ] CorrectionsAvailableMail envoyé correctement
- [ ] 4 pages Vue étudiant créées et fonctionnelles
- [ ] Composant QuestionCorrection réutilisable
- [ ] UI prof : paramètre visibility dans Create/Edit
- [ ] UI prof : bouton "Publier corrections" fonctionnel
- [ ] UI prof : champs why_wrong et reference dans éditeur
- [ ] Tests Pest > 85%
- [ ] Navigation clavier (←/→) sur correction
- [ ] Responsive mobile
- [ ] Email fonctionne en queue

## 📝 COMMITS ATTENDUS

- `feat(student): add correction_visibility migration`
- `feat(student): add reference field to questions`
- `feat(student): add CorrectionVisibility enum`
- `feat(student): add PassagePolicy::viewCorrection`
- `feat(student): add StudentDashboardService with 10+ methods`
- `test(student): add 25+ tests for StudentDashboardService`
- `feat(student): add PublishCorrections action`
- `feat(student): add CorrectionsAvailableMail notification`
- `feat(student): add Student dashboard page`
- `feat(student): add Student history page`
- `feat(student): add Student correction page`
- `feat(student): add QuestionCorrection component`
- `feat(prof): add correction_visibility form option`
- `feat(prof): add publish corrections UI`
- `feat(prof): add why_wrong and reference fields in QuestionEditor`

**Commence par Tâche 1 (migrations) et avance méthodiquement.**
```

---

© 2026 Mohamed EL AFRIT — mohamed@elafrit.com  
Certio v2.0 Laravel — CC BY-NC-SA 4.0
