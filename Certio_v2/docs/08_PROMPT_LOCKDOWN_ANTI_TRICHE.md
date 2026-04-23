# 🔐 Lockdown Anti-Triche — Mode Examen Sécurisé

> **Protection anti-triche de niveau professionnel pour Certio v2.0**  
> **Équivalent LockDown Browser mais en web natif**

| Champ | Valeur |
|---|---|
| **Livrable** | 08/N (complément Certio v2.0) |
| **Feature** | Exam Lockdown (plein écran + détection + warnings) |
| **Impact planning** | +2 jours en P6A |
| **Nouveau total v2.0** | 56 jours (vs 54 avant) |
| **Version** | 1.0 |
| **Auteur** | Mohamed EL AFRIT |
| **Licence** | CC BY-NC-SA 4.0 |

---

## Sommaire

1. [Vision et décisions validées](#1-vision-et-décisions-validées)
2. [Réalité technique (ce qui est possible ou pas)](#2-réalité-technique)
3. [Architecture multi-couches](#3-architecture-multi-couches)
4. [Modèle de données (migrations)](#4-modèle-de-données-migrations)
5. [Service backend ExamSecurityService](#5-service-backend-examsecurityservice)
6. [Composable frontend useExamLockdown](#6-composable-frontend-useexamlockdown)
7. [Notifications temps réel](#7-notifications-temps-réel)
8. [UI prof : configuration + dashboard](#8-ui-prof)
9. [Workflow de mansuétude](#9-workflow-de-mansuétude)
10. [Prompt VS Code complet](#10-prompt-vs-code-complet)

---

## 1. Vision et décisions validées

### 🎯 Objectif

Transformer Certio en plateforme d'examens **sécurisée de niveau professionnel**, équivalente à des solutions comme :
- Respondus LockDown Browser
- ProctorU
- ExamSoft
- Proctorio

Tout en restant **web natif** (pas de browser à installer).

### ✅ Décisions validées

| Question | Décision |
|---|---|
| Config par défaut | ✅ **Activé par défaut** (prof peut désactiver par examen) |
| Infractions détectées | ✅ **Toutes** + Print Screen + outils de capture |
| Notifications prof | ✅ **Temps réel** dans dashboard + email si invalidation |
| Mansuétude prof | ✅ **Autorisée** + **seuil configurable** (1 à 5 avertissements) |

### 🎯 Stratégie : Défense en profondeur

Puisqu'on ne peut pas **empêcher** 100% les captures/triche dans un navigateur, on combine plusieurs couches pour **détecter**, **dissuader** et **tracer** :

```
┌─────────────────────────────────────────┐
│  COUCHE 1 : Plein écran obligatoire      │
│  COUCHE 2 : Détection onglet/fenêtre     │
│  COUCHE 3 : Blur de la page si blur win  │ ← capture inexploitable
│  COUCHE 4 : Watermark dynamique          │ ← identification
│  COUCHE 5 : Blocage raccourcis           │
│  COUCHE 6 : Détection Print Screen       │
│  COUCHE 7 : Détection DevTools           │
│  COUCHE 8 : user-select: none            │
│  COUCHE 9 : Warnings progressifs         │
│  COUCHE 10 : Notifications temps réel    │
└─────────────────────────────────────────┘
```

---

## 2. Réalité technique

### ⚠️ Transparence sur les limites

**Ce qui est techniquement IMPOSSIBLE en navigateur web** :
- ❌ Empêcher une capture d'écran native OS (Print Screen, Cmd+Shift+3)
- ❌ Rendre une capture "noire" (le navigateur n'intercepte pas l'OS)
- ❌ Empêcher la photographie de l'écran avec un smartphone
- ❌ Empêcher l'enregistrement vidéo par logiciel tiers (OBS)
- ❌ Forcer le plein écran sans interaction utilisateur (sécurité browser)
- ❌ Empêcher Alt+Tab (contrôlé par l'OS)

### ✅ Ce qui EST faisable et sera implémenté

**Protection maximale réaliste** :

| Technique | Efficacité | Implémenté |
|---|:-:|:-:|
| Plein écran + détection sortie | 🟢 Haute | ✅ |
| Détection changement onglet | 🟢 Haute | ✅ |
| Détection perte focus fenêtre | 🟢 Haute | ✅ |
| **Blur de page si focus lost** | 🟢 **Excellent** | ✅ |
| **Watermark avec email étudiant** | 🟢 **Excellent** | ✅ |
| Détection Print Screen (touche) | 🟡 Moyenne | ✅ |
| Détection DevTools (heuristique) | 🟡 Moyenne | ✅ |
| Blocage clic droit | 🟡 Contournable | ✅ |
| Blocage copier/coller | 🟢 Haute | ✅ |
| user-select: none | 🟡 Contournable | ✅ |

### 🎯 Innovation clé : Blur sur focus loss

C'est **LA** technique qui rend les captures d'écran inutilisables :

```javascript
// Quand l'étudiant perd le focus (Alt+Tab, Snipping Tool, etc.)
window.addEventListener('blur', () => {
  document.body.classList.add('exam-blur')
})
```

```css
.exam-blur {
  filter: blur(20px) brightness(0.2);
  transition: filter 50ms;
}
```

**Résultat** :
1. Étudiant ouvre Snipping Tool → fenêtre Certio perd le focus → page devient floue et noire
2. L'étudiant capture l'écran → la capture montre **uniquement du flou**
3. L'étudiant revient sur Certio → focus retrouvé → page se décompose (warning)

Cette technique est utilisée par **Proctorio** et **ExamSoft**. Elle ne peut pas être contournée sans désactiver JavaScript, ce qui empêcherait l'examen de fonctionner.

### 💧 Innovation clé #2 : Watermark dynamique

Superposition permanente avec :
- Email de l'étudiant
- Timestamp en direct (format secondes)
- UUID du passage
- Rotation 15° opacity 10%

```css
.watermark {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 9999;
  opacity: 0.1;
  background-image: repeating-linear-gradient(
    -15deg,
    transparent,
    transparent 50px,
    rgba(0,0,0,0.05) 50px,
    rgba(0,0,0,0.05) 100px
  );
}

.watermark-text {
  position: absolute;
  transform: rotate(-15deg);
  font-size: 14px;
  color: rgba(0,0,0,0.1);
  white-space: nowrap;
  pointer-events: none;
}
```

Chaque watermark est placé en diagonale, répété sur toute la page. Si capture :
- L'étudiant est **identifié** (email visible)
- Le timestamp prouve l'heure de la capture
- Le passage UUID prouve quelle tentative

**Dissuasion maximale**.

---

## 3. Architecture multi-couches

### 🏗️ Composants à créer

```
Backend :
├── app/Services/ExamSecurityService.php     # Service principal
├── app/Actions/Passage/
│   ├── ReportSecurityEvent.php              # Log événement
│   ├── InvalidatePassage.php                # Annuler passage
│   └── RestorePassage.php                   # Mansuétude prof
├── app/Events/
│   ├── SecurityViolationDetected.php        # Broadcast temps réel
│   └── PassageInvalidated.php
├── app/Mail/
│   ├── PassageInvalidatedStudentMail.php
│   └── PassageInvalidatedProfMail.php
├── app/Http/Controllers/Student/
│   └── PassageSecurityController.php        # Endpoints
├── app/Policies/PassagePolicy.php           # + restore()
└── app/Enums/
    └── SecurityEventType.php                # Tous types d'événements

Frontend :
├── resources/js/Composables/
│   └── useExamLockdown.js                   # Composable central
├── resources/js/Components/Exam/
│   ├── LockdownStartScreen.vue              # Écran d'entrée
│   ├── FullscreenEnforcer.vue               # Force plein écran
│   ├── SecurityWarningModal.vue             # Popups warnings
│   ├── BlurOverlay.vue                      # Blur quand focus lost
│   ├── Watermark.vue                        # Filigrane dynamique
│   └── InvalidationScreen.vue               # Écran "Examen annulé"
├── resources/js/Pages/Prof/Exams/
│   └── SecurityDashboard.vue                # Dashboard temps réel
└── resources/js/Utils/
    └── securityDetector.js                  # Helpers détection
```

### 🔄 Flux complet

```
1. Prof crée examen
   └─ security_lockdown_enabled = true (défaut)
   └─ security_warning_threshold = 2 (défaut)
   └─ notify_prof_realtime = true (défaut)

2. Étudiant arrive sur passage
   └─ Affichage LockdownStartScreen
   └─ Étudiant accepte les règles + clique "Commencer"
   └─ Passage en mode lockdown activé

3. Pendant l'examen
   ├─ Watermark visible en permanence
   ├─ Event listeners actifs
   ├─ À chaque infraction détectée:
   │   ├─ POST /security-event
   │   ├─ Backend incrémente compteur
   │   ├─ Si count < threshold : warning popup
   │   ├─ Si count = threshold : dernier warning
   │   └─ Si count > threshold : invalidation auto
   └─ Broadcast WebSocket vers prof dashboard

4. En cas de violation
   └─ Prof reçoit notification temps réel
   └─ Badge "🔴 1 violation" dans dashboard
   └─ Clic → voir détails étudiant + timeline

5. Si invalidation (3e infraction par défaut)
   ├─ Passage status = 'invalidated'
   ├─ Score = 0
   ├─ Email étudiant + email prof
   ├─ Prof voit dans "Passages annulés"
   └─ Prof peut décider : maintenir ou restaurer (mansuétude)

6. Mansuétude (si prof décide)
   └─ Restore action
   └─ Score recalculé normalement
   └─ Log audit : "Restauré par {prof} le {date}"
```

---

## 4. Modèle de données (migrations)

### Migration additive : sécurité sur `exams`

```bash
php artisan make:migration add_security_lockdown_to_exams_table
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
            // Active/désactive le mode lockdown
            $table->boolean('security_lockdown_enabled')
                ->default(true)
                ->after('shuffle_options');
            
            // Seuil d'avertissements (1 à 5) avant invalidation
            $table->integer('security_warning_threshold')
                ->default(2) // 2 warnings, 3e = invalidation
                ->after('security_lockdown_enabled');
            
            // Notifications
            $table->boolean('notify_prof_on_violation')
                ->default(true)
                ->after('security_warning_threshold');
            
            $table->boolean('notify_prof_on_invalidation')
                ->default(true)
                ->after('notify_prof_on_violation');
            
            // Configuration fine
            $table->json('security_config')->nullable()
                ->after('notify_prof_on_invalidation');
            // Structure:
            // {
            //   "detect_fullscreen_exit": true,
            //   "detect_tab_switch": true,
            //   "detect_window_blur": true,
            //   "detect_copy_paste": true,
            //   "detect_print_screen": true,
            //   "detect_devtools": true,
            //   "block_context_menu": true,
            //   "block_shortcuts": true,
            //   "show_watermark": true,
            //   "blur_on_focus_loss": true
            // }
        });
    }
    
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn([
                'security_lockdown_enabled',
                'security_warning_threshold',
                'notify_prof_on_violation',
                'notify_prof_on_invalidation',
                'security_config',
            ]);
        });
    }
};
```

### Migration additive : sécurité sur `passages`

```bash
php artisan make:migration add_security_tracking_to_passages_table
```

```php
public function up(): void
{
    Schema::table('passages', function (Blueprint $table) {
        // Compteur de warnings
        $table->integer('security_warning_count')
            ->default(0)
            ->after('anti_cheat_signals');
        
        // Événements détectés (avec timestamps)
        $table->json('security_events')
            ->nullable()
            ->after('security_warning_count');
        
        // Invalidation
        $table->string('invalidation_reason')
            ->nullable()
            ->after('security_events');
        
        $table->timestamp('invalidated_at')
            ->nullable()
            ->after('invalidation_reason');
        
        // Restauration (mansuétude prof)
        $table->timestamp('restored_at')
            ->nullable()
            ->after('invalidated_at');
        
        $table->foreignId('restored_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete()
            ->after('restored_at');
        
        $table->text('restoration_reason')
            ->nullable()
            ->after('restored_by');
        
        // État du lockdown
        $table->boolean('fullscreen_was_enforced')
            ->default(false)
            ->after('restoration_reason');
    });
}

public function down(): void
{
    Schema::table('passages', function (Blueprint $table) {
        $table->dropForeign(['restored_by']);
        $table->dropColumn([
            'security_warning_count',
            'security_events',
            'invalidation_reason',
            'invalidated_at',
            'restored_at',
            'restored_by',
            'restoration_reason',
            'fullscreen_was_enforced',
        ]);
    });
}
```

### Enum SecurityEventType

```php
<?php
namespace App\Enums;

enum SecurityEventType: string
{
    case FullscreenExit = 'fullscreen_exit';
    case TabSwitch = 'tab_switch';
    case TabHidden = 'tab_hidden';
    case WindowBlur = 'window_blur';
    case CopyAttempt = 'copy_attempt';
    case PasteAttempt = 'paste_attempt';
    case ContextMenu = 'context_menu';
    case PrintScreen = 'printscreen_pressed';
    case DevToolsDetected = 'devtools_detected';
    case BlockedShortcut = 'blocked_shortcut';
    case MultiMonitorDetected = 'multi_monitor';
    case NetworkLost = 'network_lost';
    
    public function label(): string
    {
        return match($this) {
            self::FullscreenExit => '🖥️ Sortie du plein écran',
            self::TabSwitch => '📑 Changement d\'onglet',
            self::TabHidden => '👁️ Onglet masqué',
            self::WindowBlur => '🪟 Fenêtre inactive',
            self::CopyAttempt => '📋 Tentative de copie',
            self::PasteAttempt => '📋 Tentative de collage',
            self::ContextMenu => '🖱️ Clic droit',
            self::PrintScreen => '📸 Impr. écran détectée',
            self::DevToolsDetected => '🛠️ DevTools détecté',
            self::BlockedShortcut => '⌨️ Raccourci bloqué',
            self::MultiMonitorDetected => '🖥️🖥️ Multi-écrans détecté',
            self::NetworkLost => '📡 Réseau perdu',
        };
    }
    
    public function severity(): string
    {
        return match($this) {
            self::FullscreenExit, self::TabSwitch, self::WindowBlur, self::PrintScreen => 'critical',
            self::CopyAttempt, self::PasteAttempt, self::DevToolsDetected => 'high',
            self::ContextMenu, self::BlockedShortcut => 'low',
            default => 'medium',
        };
    }
    
    public function triggersWarning(): bool
    {
        return match($this) {
            self::FullscreenExit, 
            self::TabSwitch, 
            self::TabHidden,
            self::WindowBlur, 
            self::PrintScreen => true,
            
            self::CopyAttempt,
            self::PasteAttempt,
            self::ContextMenu,
            self::BlockedShortcut,
            self::DevToolsDetected,
            self::MultiMonitorDetected,
            self::NetworkLost => false, // log seulement
        };
    }
}
```

Ajouter `Invalidated` à l'enum PassageStatus :

```php
enum PassageStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Expired = 'expired';
    case Invalidated = 'invalidated'; // ← Nouveau
    // ...
}
```

---

## 5. Service backend ExamSecurityService

```php
<?php
namespace App\Services;

use App\Models\Passage;
use App\Models\User;
use App\Enums\PassageStatus;
use App\Enums\SecurityEventType;
use App\Events\SecurityViolationDetected;
use App\Events\PassageInvalidated;
use App\Mail\PassageInvalidatedStudentMail;
use App\Mail\PassageInvalidatedProfMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ExamSecurityService
{
    /**
     * Enregistre un événement silencieux (log seulement, pas de warning).
     */
    public function logSilentEvent(
        Passage $passage, 
        SecurityEventType $type, 
        array $details = []
    ): void {
        $events = $passage->security_events ?? [];
        $events[] = [
            'type' => $type->value,
            'label' => $type->label(),
            'severity' => $type->severity(),
            'timestamp' => now()->toIso8601String(),
            'details' => $details,
            'silent' => true,
        ];
        
        $passage->update(['security_events' => $events]);
    }
    
    /**
     * Enregistre une infraction et incrémente le compteur.
     * Peut déclencher une invalidation si seuil atteint.
     */
    public function reportViolation(
        Passage $passage, 
        SecurityEventType $type, 
        array $details = []
    ): array {
        return DB::transaction(function () use ($passage, $type, $details) {
            $newWarningCount = $passage->security_warning_count + 1;
            
            // Logger événement
            $events = $passage->security_events ?? [];
            $events[] = [
                'type' => $type->value,
                'label' => $type->label(),
                'severity' => $type->severity(),
                'timestamp' => now()->toIso8601String(),
                'warning_triggered' => $newWarningCount,
                'details' => $details,
            ];
            
            $passage->update([
                'security_warning_count' => $newWarningCount,
                'security_events' => $events,
            ]);
            
            // Vérifier seuil (configurable par examen)
            $threshold = $passage->exam->security_warning_threshold ?? 2;
            
            // Notification prof temps réel
            if ($passage->exam->notify_prof_on_violation) {
                broadcast(new SecurityViolationDetected($passage, $type, $newWarningCount));
            }
            
            if ($newWarningCount > $threshold) {
                // Dépassement → invalidation
                $this->invalidatePassage(
                    $passage, 
                    "security_violation_exceeded_threshold_{$threshold}"
                );
                
                return [
                    'action' => 'invalidated',
                    'warning_count' => $newWarningCount,
                    'threshold' => $threshold,
                ];
            }
            
            return [
                'action' => 'warning',
                'warning_count' => $newWarningCount,
                'threshold' => $threshold,
                'level' => $newWarningCount, // 1 ou 2
            ];
        });
    }
    
    /**
     * Invalide un passage (dépassement seuil ou décision prof).
     */
    public function invalidatePassage(
        Passage $passage, 
        string $reason
    ): void {
        $passage->update([
            'status' => PassageStatus::Invalidated,
            'invalidation_reason' => $reason,
            'invalidated_at' => now(),
            'score_raw' => 0,
            'score_max' => $passage->exam->total_points ?? 100,
            'score_percentage' => 0,
        ]);
        
        // Emails
        if ($passage->student_email) {
            Mail::to($passage->student_email)
                ->queue(new PassageInvalidatedStudentMail($passage));
        }
        
        if ($passage->exam->notify_prof_on_invalidation && $passage->exam->creator) {
            Mail::to($passage->exam->creator->email)
                ->queue(new PassageInvalidatedProfMail($passage));
        }
        
        // Broadcast temps réel
        broadcast(new PassageInvalidated($passage));
        
        // Audit log
        activity()
            ->performedOn($passage)
            ->withProperties([
                'reason' => $reason,
                'warning_count' => $passage->security_warning_count,
                'events_count' => count($passage->security_events ?? []),
            ])
            ->log('passage.invalidated_cheating');
    }
    
    /**
     * Mansuétude prof : restaure un passage annulé.
     */
    public function restorePassage(
        Passage $passage, 
        User $prof, 
        ?string $reason = null
    ): void {
        if ($passage->status !== PassageStatus::Invalidated) {
            throw new \RuntimeException('Passage is not invalidated');
        }
        
        DB::transaction(function () use ($passage, $prof, $reason) {
            $passage->update([
                'status' => PassageStatus::Submitted,
                'restored_at' => now(),
                'restored_by' => $prof->id,
                'restoration_reason' => $reason,
                // On garde invalidation_reason et invalidated_at pour historique
            ]);
            
            // Recalculer le score normalement
            app(ScoringService::class)->recomputePassage($passage);
            
            activity()
                ->causedBy($prof)
                ->performedOn($passage)
                ->withProperties([
                    'reason' => $reason,
                    'original_invalidation' => $passage->invalidation_reason,
                ])
                ->log('passage.restored_by_prof');
        });
    }
    
    /**
     * Statistiques d'un examen (pour dashboard prof).
     */
    public function getExamSecurityStats(int $examId): array
    {
        $passages = Passage::where('exam_id', $examId)
            ->select('id', 'student_email', 'student_nom', 'student_prenom',
                     'security_warning_count', 'security_events', 
                     'status', 'invalidated_at', 'restored_at')
            ->get();
        
        return [
            'total_passages' => $passages->count(),
            'with_violations' => $passages->where('security_warning_count', '>', 0)->count(),
            'invalidated' => $passages->where('status', PassageStatus::Invalidated->value)->count(),
            'restored' => $passages->whereNotNull('restored_at')->count(),
            'clean' => $passages->where('security_warning_count', 0)->count(),
            'students_at_risk' => $passages
                ->filter(fn($p) => $p->security_warning_count > 0 
                                   && $p->status !== PassageStatus::Invalidated)
                ->values()
                ->toArray(),
        ];
    }
}
```

### Event pour broadcast temps réel

```php
<?php
namespace App\Events;

use App\Models\Passage;
use App\Enums\SecurityEventType;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SecurityViolationDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public Passage $passage,
        public SecurityEventType $eventType,
        public int $warningCount,
    ) {}
    
    public function broadcastOn(): array
    {
        // Private channel par professeur
        return [
            new PrivateChannel("prof.{$this->passage->exam->creator_id}.security"),
            new PrivateChannel("exam.{$this->passage->exam_id}.security"),
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'security.violation';
    }
    
    public function broadcastWith(): array
    {
        return [
            'passage_uuid' => $this->passage->uuid,
            'student_nom' => $this->passage->student_nom,
            'student_prenom' => $this->passage->student_prenom,
            'student_email' => $this->passage->student_email,
            'event_type' => $this->eventType->value,
            'event_label' => $this->eventType->label(),
            'severity' => $this->eventType->severity(),
            'warning_count' => $this->warningCount,
            'threshold' => $this->passage->exam->security_warning_threshold,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

---

## 6. Composable frontend useExamLockdown

### Fichier complet `resources/js/Composables/useExamLockdown.js`

```javascript
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import axios from 'axios'

export function useExamLockdown(passageUuid, options = {}) {
  const {
    threshold = 2,
    warningDurations = [10000, 5000],
    onWarning = () => {},
    onInvalidation = () => {},
    config = {
      detect_fullscreen_exit: true,
      detect_tab_switch: true,
      detect_window_blur: true,
      detect_copy_paste: true,
      detect_print_screen: true,
      detect_devtools: true,
      block_context_menu: true,
      block_shortcuts: true,
      show_watermark: true,
      blur_on_focus_loss: true,
    },
  } = options
  
  // ══════════════════════════════════════════════════════════
  // STATE
  // ══════════════════════════════════════════════════════════
  const warningCount = ref(0)
  const showWarning = ref(false)
  const currentWarningLevel = ref(0)
  const warningCountdown = ref(0)
  const isInvalidated = ref(false)
  const isBlurred = ref(false)
  const isFullscreen = ref(false)
  const securityEvents = ref([])
  
  // ══════════════════════════════════════════════════════════
  // PLEIN ÉCRAN
  // ══════════════════════════════════════════════════════════
  async function enterFullscreen() {
    try {
      const elem = document.documentElement
      const request = elem.requestFullscreen 
                   || elem.webkitRequestFullscreen 
                   || elem.msRequestFullscreen
      
      if (request) {
        await request.call(elem, { navigationUI: 'hide' })
      }
      isFullscreen.value = true
      return true
    } catch (err) {
      console.error('Cannot enter fullscreen:', err)
      return false
    }
  }
  
  function exitFullscreen() {
    const exit = document.exitFullscreen 
              || document.webkitExitFullscreen 
              || document.msExitFullscreen
    if (exit && document.fullscreenElement) {
      exit.call(document).catch(() => {})
    }
  }
  
  // ══════════════════════════════════════════════════════════
  // REPORT À BACKEND
  // ══════════════════════════════════════════════════════════
  async function reportEvent(type, details = {}, silent = false) {
    if (isInvalidated.value) return
    
    const event = {
      type,
      timestamp: new Date().toISOString(),
      details,
      silent,
    }
    securityEvents.value.push(event)
    
    try {
      const res = await axios.post(
        `/student/passages/${passageUuid}/security-event`,
        event
      )
      
      if (!silent && res.data.action === 'warning') {
        warningCount.value = res.data.warning_count
        triggerWarning(res.data.level)
      }
      
      if (!silent && res.data.action === 'invalidated') {
        handleInvalidation()
      }
    } catch (err) {
      console.error('Failed to report event:', err)
    }
  }
  
  // ══════════════════════════════════════════════════════════
  // WARNINGS PROGRESSIFS
  // ══════════════════════════════════════════════════════════
  let countdownInterval = null
  
  function triggerWarning(level) {
    currentWarningLevel.value = level
    showWarning.value = true
    
    const duration = warningDurations[level - 1] || 5000
    warningCountdown.value = Math.ceil(duration / 1000)
    
    if (countdownInterval) clearInterval(countdownInterval)
    
    countdownInterval = setInterval(() => {
      warningCountdown.value--
      if (warningCountdown.value <= 0) {
        clearInterval(countdownInterval)
        closeWarning()
      }
    }, 1000)
    
    onWarning(level)
    
    setTimeout(() => closeWarning(), duration)
  }
  
  function closeWarning() {
    showWarning.value = false
    currentWarningLevel.value = 0
    
    // Re-entrer en plein écran
    if (!document.fullscreenElement && config.detect_fullscreen_exit) {
      enterFullscreen().catch(console.error)
    }
  }
  
  function handleInvalidation() {
    isInvalidated.value = true
    showWarning.value = false
    onInvalidation()
  }
  
  // ══════════════════════════════════════════════════════════
  // HANDLERS DÉTECTION
  // ══════════════════════════════════════════════════════════
  let tabSwitchTimeout = null
  let tabHiddenStart = 0
  let windowBlurTimeout = null
  
  function handleVisibilityChange() {
    if (!config.detect_tab_switch) return
    
    if (document.hidden) {
      tabHiddenStart = Date.now()
      isBlurred.value = true
      
      tabSwitchTimeout = setTimeout(() => {
        reportEvent('tab_hidden', {
          duration_ms: Date.now() - tabHiddenStart,
        })
      }, 500)
    } else {
      if (tabSwitchTimeout) clearTimeout(tabSwitchTimeout)
      isBlurred.value = false
    }
  }
  
  function handleWindowBlur() {
    if (!config.detect_window_blur) return
    
    if (config.blur_on_focus_loss) {
      isBlurred.value = true
    }
    
    windowBlurTimeout = setTimeout(() => {
      reportEvent('window_blur', {
        was_fullscreen: !!document.fullscreenElement,
      })
    }, 500)
  }
  
  function handleWindowFocus() {
    if (windowBlurTimeout) clearTimeout(windowBlurTimeout)
    isBlurred.value = false
  }
  
  function handleFullscreenChange() {
    if (!config.detect_fullscreen_exit) return
    
    const inFullscreen = !!document.fullscreenElement || !!document.webkitFullscreenElement
    isFullscreen.value = inFullscreen
    
    if (!inFullscreen && !isInvalidated.value && !showWarning.value) {
      reportEvent('fullscreen_exit', {
        method: 'unknown',
      })
    }
  }
  
  function handleKeyDown(e) {
    // Print Screen
    if (e.key === 'PrintScreen') {
      reportEvent('printscreen_pressed', {}, false) // Non silent = warning
      return
    }
    
    if (!config.block_shortcuts) return
    
    const blockedCombos = [
      { ctrl: true, key: 'c' },
      { ctrl: true, key: 'v' },
      { ctrl: true, key: 'x' },
      { ctrl: true, key: 'a' },
      { ctrl: true, key: 's' },
      { ctrl: true, key: 'p' },
      { ctrl: true, key: 'u' },
      { key: 'F12' },
      { ctrl: true, shift: true, key: 'i' },
      { ctrl: true, shift: true, key: 'j' },
      { ctrl: true, shift: true, key: 'c' },
    ]
    
    for (const combo of blockedCombos) {
      if (
        combo.key.toLowerCase() === e.key.toLowerCase() &&
        (combo.ctrl === undefined || combo.ctrl === (e.ctrlKey || e.metaKey)) &&
        (combo.shift === undefined || combo.shift === e.shiftKey)
      ) {
        e.preventDefault()
        e.stopPropagation()
        
        reportEvent('blocked_shortcut', {
          key: e.key,
          ctrl: e.ctrlKey || e.metaKey,
          shift: e.shiftKey,
        }, true) // silent
        
        return false
      }
    }
  }
  
  function handleContextMenu(e) {
    if (config.block_context_menu) {
      e.preventDefault()
      reportEvent('context_menu', {}, true)
    }
  }
  
  function handleCopy(e) {
    if (config.detect_copy_paste) {
      e.preventDefault()
      reportEvent('copy_attempt', {}, true)
    }
  }
  
  function handlePaste(e) {
    if (config.detect_copy_paste) {
      e.preventDefault()
      reportEvent('paste_attempt', {}, true)
    }
  }
  
  // ══════════════════════════════════════════════════════════
  // DETECT DEVTOOLS (HEURISTIQUE)
  // ══════════════════════════════════════════════════════════
  let devToolsInterval = null
  let devToolsDetectedOnce = false
  
  function detectDevTools() {
    if (!config.detect_devtools) return
    
    devToolsInterval = setInterval(() => {
      const widthDiff = window.outerWidth - window.innerWidth
      const heightDiff = window.outerHeight - window.innerHeight
      
      if ((widthDiff > 160 || heightDiff > 200) && !devToolsDetectedOnce) {
        devToolsDetectedOnce = true
        reportEvent('devtools_detected', {
          widthDiff,
          heightDiff,
        }, true) // silent
      }
    }, 2000)
  }
  
  // ══════════════════════════════════════════════════════════
  // DETECT MULTI-MONITOR (heuristique via Screen API)
  // ══════════════════════════════════════════════════════════
  async function detectMultiMonitor() {
    try {
      if ('getScreenDetails' in window) {
        const screens = await window.getScreenDetails()
        if (screens.screens.length > 1) {
          reportEvent('multi_monitor', {
            count: screens.screens.length,
          }, true)
        }
      }
    } catch (err) {
      // API non supportée, skip
    }
  }
  
  // ══════════════════════════════════════════════════════════
  // LIFECYCLE
  // ══════════════════════════════════════════════════════════
  function installListeners() {
    if (config.detect_tab_switch) {
      document.addEventListener('visibilitychange', handleVisibilityChange)
    }
    if (config.detect_window_blur) {
      window.addEventListener('blur', handleWindowBlur)
      window.addEventListener('focus', handleWindowFocus)
    }
    if (config.detect_fullscreen_exit) {
      document.addEventListener('fullscreenchange', handleFullscreenChange)
      document.addEventListener('webkitfullscreenchange', handleFullscreenChange)
    }
    if (config.block_shortcuts || config.detect_print_screen) {
      document.addEventListener('keydown', handleKeyDown)
      document.addEventListener('keyup', handleKeyDown)
    }
    if (config.block_context_menu) {
      document.addEventListener('contextmenu', handleContextMenu)
    }
    if (config.detect_copy_paste) {
      document.addEventListener('copy', handleCopy)
      document.addEventListener('paste', handlePaste)
    }
    
    detectDevTools()
    detectMultiMonitor()
  }
  
  function removeListeners() {
    document.removeEventListener('visibilitychange', handleVisibilityChange)
    window.removeEventListener('blur', handleWindowBlur)
    window.removeEventListener('focus', handleWindowFocus)
    document.removeEventListener('fullscreenchange', handleFullscreenChange)
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange)
    document.removeEventListener('keydown', handleKeyDown)
    document.removeEventListener('keyup', handleKeyDown)
    document.removeEventListener('contextmenu', handleContextMenu)
    document.removeEventListener('copy', handleCopy)
    document.removeEventListener('paste', handlePaste)
    
    if (devToolsInterval) clearInterval(devToolsInterval)
    if (tabSwitchTimeout) clearTimeout(tabSwitchTimeout)
    if (windowBlurTimeout) clearTimeout(windowBlurTimeout)
    if (countdownInterval) clearInterval(countdownInterval)
  }
  
  onMounted(() => {
    installListeners()
  })
  
  onBeforeUnmount(() => {
    removeListeners()
    exitFullscreen()
  })
  
  return {
    // State
    warningCount,
    showWarning,
    currentWarningLevel,
    warningCountdown,
    isInvalidated,
    isBlurred,
    isFullscreen,
    securityEvents,
    
    // Actions
    enterFullscreen,
    exitFullscreen,
    closeWarning,
  }
}
```

---

## 7. Notifications temps réel

### Setup Laravel Reverb (WebSocket)

```bash
composer require laravel/reverb
php artisan reverb:install
```

Dans `.env` :
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=1001
REVERB_APP_KEY=certio_key
REVERB_APP_SECRET=certio_secret
REVERB_HOST=localhost
REVERB_PORT=8080
```

### Frontend : écoute des événements

Dans `resources/js/Pages/Prof/Exams/Passages.vue` :

```vue
<script setup>
import { onMounted, ref } from 'vue'
import Echo from '@/echo'

const props = defineProps({
  exam: Object,
})

const liveViolations = ref([])
const violationsCount = ref(0)

onMounted(() => {
  Echo.private(`exam.${props.exam.id}.security`)
    .listen('.security.violation', (data) => {
      liveViolations.value.unshift(data)
      violationsCount.value++
      
      // Toast notification
      showToast({
        type: 'error',
        title: `⚠️ Violation détectée`,
        message: `${data.student_prenom} ${data.student_nom} - ${data.event_label}`,
        duration: 10000,
      })
    })
  
  Echo.private(`exam.${props.exam.id}.security`)
    .listen('.passage.invalidated', (data) => {
      showToast({
        type: 'error',
        title: `🚨 Passage annulé`,
        message: `${data.student_name} a été invalidé (3 violations)`,
        duration: 15000,
      })
    })
})
</script>

<template>
  <!-- Badge de notifications en temps réel -->
  <div v-if="violationsCount > 0" class="fixed top-20 right-4 z-50">
    <div class="bg-red-600 text-white px-4 py-2 rounded-full shadow-lg animate-pulse">
      🔴 {{ violationsCount }} violation(s) détectée(s)
    </div>
  </div>
  
  <!-- Dashboard passages avec temps réel -->
  <!-- ... -->
</template>
```

---

## 8. UI prof

### Configuration dans création/édition examen

```vue
<FormSection title="🔐 Mode examen sécurisé (Lockdown)">
  <div class="space-y-4">
    
    <!-- Toggle principal -->
    <label class="flex items-start gap-3 cursor-pointer">
      <input 
        type="checkbox" 
        v-model="form.security_lockdown_enabled"
        class="mt-1 h-5 w-5"
      />
      <div>
        <div class="font-semibold text-lg">Activer le mode examen sécurisé</div>
        <p class="text-sm text-gray-600">
          Plein écran obligatoire, détection de changement d'onglet, 
          filigrane, warnings progressifs.
        </p>
        <p class="text-xs text-gray-500 mt-1">
          ✅ Recommandé pour les examens formels
        </p>
      </div>
    </label>
    
    <!-- Configuration détaillée (si activé) -->
    <div v-if="form.security_lockdown_enabled" class="ml-8 space-y-4 border-l-2 pl-4 border-blue-200">
      
      <!-- Seuil d'avertissements -->
      <div>
        <label class="block text-sm font-medium mb-2">
          🎯 Seuil de tolérance (nombre d'avertissements avant annulation)
        </label>
        <select v-model="form.security_warning_threshold" class="input">
          <option :value="0">0 — Intolérance zéro (1ère infraction = annulation)</option>
          <option :value="1">1 avertissement (2e infraction = annulation)</option>
          <option :value="2">2 avertissements (3e infraction = annulation) ← Défaut</option>
          <option :value="3">3 avertissements (4e infraction = annulation)</option>
          <option :value="4">4 avertissements (5e infraction = annulation)</option>
        </select>
      </div>
      
      <!-- Notifications -->
      <div class="space-y-2">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="form.notify_prof_on_violation" />
          <span class="text-sm">📡 Me notifier en temps réel de chaque violation</span>
        </label>
        
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="form.notify_prof_on_invalidation" />
          <span class="text-sm">📧 M'envoyer un email si un passage est annulé</span>
        </label>
      </div>
      
      <!-- Info infractions -->
      <div class="bg-blue-50 p-3 rounded text-sm">
        <p class="font-semibold mb-2">🛡️ Protections activées :</p>
        <ul class="space-y-1 text-xs">
          <li>✅ Plein écran obligatoire</li>
          <li>✅ Détection changement d'onglet / fenêtre</li>
          <li>✅ Blocage copier/coller/raccourcis</li>
          <li>✅ Détection Print Screen</li>
          <li>✅ Blur automatique quand fenêtre inactive</li>
          <li>✅ Filigrane avec email étudiant</li>
          <li>✅ Détection DevTools (heuristique)</li>
        </ul>
      </div>
    </div>
  </div>
</FormSection>
```

### Dashboard sécurité temps réel

`resources/js/Pages/Prof/Exams/SecurityDashboard.vue` :

```vue
<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Echo from '@/echo'

const props = defineProps({
  exam: Object,
})

const stats = ref({
  total_passages: 0,
  with_violations: 0,
  invalidated: 0,
  students_at_risk: [],
})
const liveEvents = ref([])

async function loadStats() {
  const res = await axios.get(`/prof/exams/${props.exam.uuid}/security-stats`)
  stats.value = res.data
}

onMounted(() => {
  loadStats()
  
  // Écoute temps réel
  Echo.private(`exam.${props.exam.id}.security`)
    .listen('.security.violation', (data) => {
      liveEvents.value.unshift({
        ...data,
        id: Date.now(),
      })
      loadStats()
    })
    .listen('.passage.invalidated', () => {
      loadStats()
    })
  
  setInterval(loadStats, 30000) // Refresh toutes les 30s
})

async function restore(passage) {
  if (!confirm(`Restaurer le passage de ${passage.student_nom} ?`)) return
  
  const reason = prompt('Raison de la restauration (optionnel) :')
  
  await axios.post(`/prof/passages/${passage.uuid}/restore`, {
    reason,
  })
  
  loadStats()
}
</script>

<template>
  <div class="space-y-6">
    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-4 rounded-lg shadow">
        <div class="text-2xl font-bold">{{ stats.total_passages }}</div>
        <div class="text-sm text-gray-600">Passages total</div>
      </div>
      
      <div class="bg-green-50 p-4 rounded-lg shadow">
        <div class="text-2xl font-bold text-green-700">{{ stats.clean }}</div>
        <div class="text-sm text-green-700">Sans violation</div>
      </div>
      
      <div class="bg-orange-50 p-4 rounded-lg shadow">
        <div class="text-2xl font-bold text-orange-700">{{ stats.with_violations }}</div>
        <div class="text-sm text-orange-700">Avec violations</div>
      </div>
      
      <div class="bg-red-50 p-4 rounded-lg shadow">
        <div class="text-2xl font-bold text-red-700">{{ stats.invalidated }}</div>
        <div class="text-sm text-red-700">Annulés</div>
      </div>
    </div>
    
    <!-- Live events feed -->
    <div class="bg-white rounded-lg shadow p-4" v-if="liveEvents.length > 0">
      <h3 class="font-semibold mb-3">📡 Événements en direct</h3>
      <div class="space-y-2 max-h-60 overflow-y-auto">
        <div v-for="event in liveEvents" :key="event.id"
             class="flex items-start gap-3 p-2 border-l-4 rounded"
             :class="{
               'border-red-500 bg-red-50': event.severity === 'critical',
               'border-orange-500 bg-orange-50': event.severity === 'high',
               'border-yellow-500 bg-yellow-50': event.severity === 'medium',
             }">
          <div class="flex-1">
            <div class="font-medium">{{ event.student_prenom }} {{ event.student_nom }}</div>
            <div class="text-sm">{{ event.event_label }}</div>
            <div class="text-xs text-gray-500">
              Avertissement {{ event.warning_count }}/{{ event.threshold }}
            </div>
          </div>
          <div class="text-xs text-gray-500">
            {{ new Date(event.timestamp).toLocaleTimeString('fr-FR') }}
          </div>
        </div>
      </div>
    </div>
    
    <!-- Étudiants à risque -->
    <div v-if="stats.students_at_risk.length > 0" class="bg-white rounded-lg shadow p-4">
      <h3 class="font-semibold mb-3">⚠️ Étudiants à surveiller</h3>
      <table class="min-w-full">
        <thead>
          <tr class="text-left text-sm">
            <th>Étudiant</th>
            <th>Violations</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in stats.students_at_risk" :key="p.id">
            <td>{{ p.student_prenom }} {{ p.student_nom }}</td>
            <td>
              <span class="badge bg-orange-100 text-orange-800">
                {{ p.security_warning_count }}
              </span>
            </td>
            <td>
              <span v-if="p.status === 'invalidated'" class="badge bg-red-100 text-red-800">
                ❌ Annulé
              </span>
              <span v-else class="badge bg-yellow-100 text-yellow-800">
                ⚠️ À risque
              </span>
            </td>
            <td>
              <button 
                v-if="p.status === 'invalidated' && !p.restored_at"
                @click="restore(p)"
                class="btn-sm btn-secondary"
              >
                🛟 Restaurer
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
```

---

## 9. Workflow de mansuétude

### Cas d'usage

Un prof peut décider de restaurer un passage annulé dans ces cas :
- Étudiant a eu un vrai problème technique (coupure réseau)
- Erreur de détection (faux positif)
- Circonstances exceptionnelles (handicap, stress, etc.)
- Examen repassé avec autorisation

### Interface

```vue
<!-- Modal de restauration -->
<Modal v-if="showRestore" @close="showRestore = false">
  <template #title>🛟 Restaurer le passage</template>
  
  <div class="space-y-4">
    <div class="bg-orange-50 p-3 rounded">
      <p class="font-semibold">Étudiant : {{ passage.student_nom }}</p>
      <p class="text-sm">Passage annulé le : {{ passage.invalidated_at }}</p>
      <p class="text-sm">Raison : {{ passage.invalidation_reason }}</p>
      <p class="text-sm">Violations : {{ passage.security_warning_count }}</p>
    </div>
    
    <div>
      <label class="block text-sm font-medium mb-1">
        Raison de la restauration (optionnel)
      </label>
      <textarea 
        v-model="restorationReason"
        class="input"
        rows="3"
        placeholder="Ex: Coupure réseau confirmée, problème technique documenté..."
      />
    </div>
    
    <div class="bg-blue-50 p-3 rounded text-sm">
      <p>ℹ️ <strong>Conséquences de la restauration :</strong></p>
      <ul class="mt-1 space-y-1 text-xs">
        <li>✅ Le passage sera remis en statut "soumis"</li>
        <li>✅ Le score sera recalculé normalement</li>
        <li>✅ L'étudiant recevra un email de notification</li>
        <li>✅ L'action sera tracée dans l'audit log</li>
        <li>ℹ️ L'historique des violations reste consultable</li>
      </ul>
    </div>
  </div>
  
  <template #footer>
    <button @click="showRestore = false" class="btn-secondary">
      Annuler
    </button>
    <button @click="confirmRestore" class="btn-primary">
      🛟 Confirmer la restauration
    </button>
  </template>
</Modal>
```

---

## 10. Prompt VS Code complet

### 🎯 Prompt à utiliser

```
# CONTEXTE — CERTIO v2.0 LARAVEL — FEATURE LOCKDOWN ANTI-TRICHE

Je développe Certio v2.0 Laravel. Cette feature CRITIQUE de sécurité anti-triche 
doit être implémentée en phase P6A (Sécurité avancée).

## 📚 Documents de référence
- `Certio_v2/docs/08_PROMPT_LOCKDOWN_ANTI_TRICHE.md` (ce document)

## 🎯 Objectif (2 jours)

Implémenter un système complet de lockdown anti-triche de niveau professionnel :
1. Plein écran obligatoire avec détection sortie
2. Détection changement onglet/fenêtre avec warnings progressifs
3. Blur automatique de page quand focus perdu (rend les captures inexploitables)
4. Filigrane dynamique avec email étudiant
5. Blocage copier/coller/raccourcis
6. Détection Print Screen + DevTools
7. Notifications temps réel au prof (Laravel Reverb)
8. Seuil configurable (1 à 5 warnings)
9. Mansuétude prof possible

## 📋 TÂCHES

### Tâche 1 — Migrations BDD (30 min)
[Voir section 4 du document]
- add_security_lockdown_to_exams_table
- add_security_tracking_to_passages_table

### Tâche 2 — Enums + Models (30 min)
- SecurityEventType enum (12 cases)
- Update Exam model (fillable + casts)
- Update Passage model (fillable + casts)
- Update PassageStatus enum (+ Invalidated)

### Tâche 3 — ExamSecurityService (2h)
Créer app/Services/ExamSecurityService.php avec :
- logSilentEvent()
- reportViolation()
- invalidatePassage()
- restorePassage()
- getExamSecurityStats()

### Tâche 4 — Events + Broadcasting (1h)
- SecurityViolationDetected event
- PassageInvalidated event
- Setup Laravel Reverb
- Configurer channels privés

### Tâche 5 — Emails (45 min)
- PassageInvalidatedStudentMail
- PassageInvalidatedProfMail

### Tâche 6 — Controllers + Routes (1h)
- PassageSecurityController (reportEvent, invalidate)
- Prof routes pour restore + stats

### Tâche 7 — Composable frontend (3h)
Créer resources/js/Composables/useExamLockdown.js complet
[Voir section 6 du document]

### Tâche 8 — Composants Vue (4h)
- LockdownStartScreen.vue
- SecurityWarningModal.vue
- BlurOverlay.vue (blur filter CSS)
- Watermark.vue (filigrane dynamique)
- InvalidationScreen.vue

### Tâche 9 — UI prof (2h)
- FormSection dans Exam Create/Edit
- SecurityDashboard.vue avec temps réel
- Modal de mansuétude

### Tâche 10 — Tests Pest (2h)
- ExamSecurityServiceTest (unit)
- SecurityWorkflowTest (E2E)
- Test restoration flow

## ✅ CRITÈRES D'ACCEPTATION

- [ ] Lockdown activé par défaut sur nouveaux examens
- [ ] Plein écran obligatoire au démarrage
- [ ] Page devient floue quand focus perdu (capture inexploitable)
- [ ] Filigrane visible en permanence avec email étudiant
- [ ] 3 infractions = invalidation automatique
- [ ] Seuil configurable (1 à 5)
- [ ] Prof reçoit notification temps réel
- [ ] Prof peut restaurer un passage
- [ ] Tests > 85% coverage
- [ ] Fonctionne sur Chrome, Firefox, Safari, Edge

**Commence par Tâche 1 (migrations) et avance méthodiquement.**
```

---

## 📊 Impact sur planning

| Phase | Avant | Après | Ajout |
|:-:|:-:|:-:|:-:|
| P6A (Sécurité avancée) | 2j | **4j** | +2j |
| **Total Certio v2.0** | 54j | **56j** | +2j |

### Nouvelle répartition P6A

| Sous-tâche | Durée |
|---|:-:|
| 2FA TOTP via Fortify | 0.5j |
| Audit log Spatie | 0.5j |
| Anti-cheat basic (v2.0 prévu) | 0.5j |
| **🆕 Lockdown complet** | **2j** |
| Tests | 0.5j |
| **Total P6A** | **4j** |

---

## ⚠️ Transparence totale sur les captures d'écran

### Ce qu'on implémente

✅ **Maximum possible en web navigateur** :
- Blur automatique sur focus loss → capture montre du flou
- Filigrane visible sur capture → étudiant identifié
- Détection Print Screen → warning déclenché
- Détection focus loss → warning déclenché

### Ce qui reste possible pour l'étudiant

❌ **Captures théoriquement possibles mais inexploitables** :
- Print Screen système : image floue + watermark = inutile
- Smartphone photo : visible par prof si identification watermark
- Capture sans focus loss (rapidité) : rare et warning quand même
- Capture via DevTools : détection heuristique + warning

### Recommandation honnête

Pour **interdire totalement** les captures d'écran, il faut :
- Une app native OS (Respondus LockDown Browser)
- OU un proctoring humain/IA avec webcam
- OU un environnement contrôlé (salle d'examen physique)

**Notre solution web = protection maximale réaliste** + traçabilité.

---

## ✅ Validation finale

Cette feature transforme Certio en **plateforme d'examens de niveau professionnel**, comparable aux meilleures solutions du marché, tout en restant accessible via navigateur web standard.

**Valeur business** :
- 💎 Différenciateur majeur vs concurrents (Moodle, Wooclap)
- 💎 Argument de vente pour écoles soucieuses de l'intégrité
- 💎 Crédibilité pour examens certifiants

**Prêt à être implémenté en P6A.**

---

© 2026 Mohamed EL AFRIT — mohamed@elafrit.com  
Certio v2.0 Laravel — CC BY-NC-SA 4.0
