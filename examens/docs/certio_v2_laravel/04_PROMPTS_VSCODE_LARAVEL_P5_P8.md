# 🎯 Prompts VS Code Laravel — Phases P5 à P8

> **Livrable D/4 — Prompts pour finalisation, tests et déploiement**

| Champ | Valeur |
|---|---|
| **Livrable** | D/4 (Laravel) |
| **Phases couvertes** | P5 (Docs), P6 (5 sous-phases), P7 (Tests), P8 (Déploiement) |
| **Durée** | 25 jours (dernière moitié du projet) |
| **Version** | 1.0 |
| **Auteur** | Mohamed EL AFRIT |
| **Licence** | CC BY-NC-SA 4.0 |

---

## Sommaire

1. [Prompt Phase P5 — Documentation interactive](#prompt-phase-p5--documentation-interactive)
2. [Prompt Phase P6A — Sécurité avancée](#prompt-phase-p6a--sécurité-avancée)
3. [Prompt Phase P6B — Multi-tenant + SSO](#prompt-phase-p6b--multi-tenant--sso)
4. [Prompt Phase P6C — Intégrations LMS](#prompt-phase-p6c--intégrations-lms)
5. [Prompt Phase P6D — A11y + i18n + PWA](#prompt-phase-p6d--a11y--i18n--pwa)
6. [Prompt Phase P6E — Banque communautaire](#prompt-phase-p6e--banque-communautaire)
7. [Prompt Phase P7 — Tests + Admin Filament](#prompt-phase-p7--tests--admin-filament)
8. [Prompt Phase P8 — Migration + Déploiement](#prompt-phase-p8--migration--déploiement)

---

## Prompt Phase P5 — Documentation interactive

### 🎯 À copier-coller (4 jours)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P5 : DOCUMENTATION INTERACTIVE

Phases P-1 à P4 terminées. Tu as CBM + 7 types + Multi-select + Analytics.

## 🎯 Objectif Phase P5 (4 jours)

1. DocumentationService avec RBAC strict
2. Routes + contrôleurs pour docs
3. UI DocsViewer Vue 3 avec sidebar + TOC
4. 4 types de placeholders (diagram, image, video, interactive)
5. 20+ pages markdown initiales
6. Recherche full-text via Laravel Scout + FTS5

## 📋 TÂCHES

### Tâche 1 — DocumentationService (jour 1)

Créer `app/Services/DocumentationService.php` :

```php
<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class DocumentationService
{
    private string $docsPath;
    private GithubFlavoredMarkdownConverter $converter;
    
    public function __construct()
    {
        $this->docsPath = resource_path('markdown');
        $this->converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
    
    /**
     * Retourne l'arbre des docs accessibles selon le rôle.
     * RBAC rules:
     * - super_admin: admin/, prof/, student/, shared/
     * - admin: admin/, prof/, student/, shared/
     * - enseignant: prof/, student/, shared/
     * - etudiant: student/, shared/
     */
    public function getTree(string $role): array
    {
        $allowedFolders = $this->getAllowedFolders($role);
        $tree = [];
        
        foreach ($allowedFolders as $folder) {
            $path = "$this->docsPath/$folder";
            if (!is_dir($path)) continue;
            
            $tree[$folder] = $this->scanFolder($path, $folder);
        }
        
        return $tree;
    }
    
    private function getAllowedFolders(string $role): array
    {
        return match($role) {
            'super_admin', 'admin' => ['admin', 'prof', 'student', 'shared'],
            'enseignant' => ['prof', 'student', 'shared'],
            'etudiant' => ['student', 'shared'],
            default => ['shared'],
        };
    }
    
    private function scanFolder(string $path, string $rel): array
    {
        $items = [];
        $files = File::files($path);
        
        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') continue;
            $name = $file->getFilenameWithoutExtension();
            $items[] = [
                'name' => $this->prettifyName($name),
                'slug' => $name,
                'path' => "$rel/$name",
                'type' => 'file',
            ];
        }
        
        return $items;
    }
    
    private function prettifyName(string $slug): string
    {
        // "01-dashboard" → "01. Dashboard"
        return preg_replace('/^(\d+)-(.*)/', '$1. $2', 
            str_replace('-', ' ', $slug)
        );
    }
    
    /**
     * Charge le contenu d'un doc.
     */
    public function getDoc(string $path, string $role): ?array
    {
        if (!$this->checkAccess($path, $role)) {
            abort(403, 'Access denied to this documentation');
        }
        
        $filePath = "$this->docsPath/$path.md";
        if (!file_exists($filePath)) return null;
        
        $markdown = file_get_contents($filePath);
        $html = $this->converter->convert($markdown)->getContent();
        $html = $this->parsePlaceholders($html);
        
        return [
            'path' => $path,
            'content_html' => $html,
            'content_markdown' => $markdown,
            'toc' => $this->extractToc($markdown),
            'title' => $this->extractTitle($markdown),
        ];
    }
    
    public function checkAccess(string $path, string $role): bool
    {
        $folder = explode('/', $path)[0];
        return in_array($folder, $this->getAllowedFolders($role));
    }
    
    /**
     * Parse les placeholders custom.
     */
    public function parsePlaceholders(string $html): string
    {
        // Parse :::diagram ... :::
        $html = preg_replace_callback(
            '/:::diagram\s+type=(\w+)(?:\s+description="([^"]*)")?\s*\n(.*?):::/s',
            function($m) {
                [, $type, $desc, $content] = $m;
                return $this->renderDiagram($type, $content, $desc);
            },
            $html
        );
        
        // Parse :::image, :::video, :::interactive (similaire)
        // ...
        
        return $html;
    }
    
    private function renderDiagram(string $type, string $content, ?string $desc): string
    {
        if ($type === 'mermaid') {
            return "<div class=\"mermaid-diagram\" data-description=\"$desc\">
                <pre class=\"mermaid\">$content</pre>
            </div>";
        }
        return "<!-- unsupported diagram type: $type -->";
    }
    
    public function extractToc(string $markdown): array
    {
        preg_match_all('/^(#{1,3})\s+(.+)$/m', $markdown, $matches, PREG_SET_ORDER);
        
        $toc = [];
        foreach ($matches as $m) {
            $toc[] = [
                'level' => strlen($m[1]),
                'text' => trim($m[2]),
                'anchor' => $this->slugify($m[2]),
            ];
        }
        return $toc;
    }
    
    private function extractTitle(string $markdown): string
    {
        preg_match('/^#\s+(.+)$/m', $markdown, $match);
        return $match[1] ?? 'Documentation';
    }
    
    private function slugify(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-');
    }
    
    /**
     * Recherche full-text dans docs accessibles.
     */
    public function search(string $query, string $role, int $limit = 20): array
    {
        $allowedFolders = $this->getAllowedFolders($role);
        $results = [];
        
        foreach ($allowedFolders as $folder) {
            $path = "$this->docsPath/$folder";
            if (!is_dir($path)) continue;
            
            $files = File::files($path);
            foreach ($files as $file) {
                $content = file_get_contents($file->getPathname());
                if (stripos($content, $query) !== false) {
                    $results[] = [
                        'path' => "$folder/" . $file->getFilenameWithoutExtension(),
                        'title' => $this->extractTitle($content),
                        'snippet' => $this->extractSnippet($content, $query),
                        'score' => substr_count(strtolower($content), strtolower($query)),
                    ];
                }
            }
        }
        
        usort($results, fn($a, $b) => $b['score'] - $a['score']);
        return array_slice($results, 0, $limit);
    }
    
    private function extractSnippet(string $content, string $query, int $length = 200): string
    {
        $pos = stripos($content, $query);
        if ($pos === false) return substr($content, 0, $length);
        
        $start = max(0, $pos - 50);
        $snippet = substr($content, $start, $length);
        return ($start > 0 ? '...' : '') . $snippet . '...';
    }
}
```

Installer `league/commonmark` :
```bash
composer require league/commonmark
```

### Tâche 2 — Controller + Routes (jour 1)

Controller `app/Http/Controllers/DocsController.php` :

```php
<?php
namespace App\Http\Controllers;

use App\Services\DocumentationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocsController extends Controller
{
    public function __construct(private DocumentationService $docs) {}
    
    public function index(Request $request)
    {
        $role = $request->user()->role ?? 'etudiant';
        $tree = $this->docs->getTree($role);
        
        return Inertia::render('Docs/Index', [
            'tree' => $tree,
            'role' => $role,
        ]);
    }
    
    public function show(Request $request, string $path)
    {
        $role = $request->user()->role ?? 'etudiant';
        $doc = $this->docs->getDoc($path, $role);
        
        if (!$doc) abort(404);
        
        return Inertia::render('Docs/Show', [
            'doc' => $doc,
            'tree' => $this->docs->getTree($role),
        ]);
    }
    
    public function search(Request $request)
    {
        $query = $request->input('query', '');
        $role = $request->user()->role ?? 'etudiant';
        
        return response()->json([
            'results' => $this->docs->search($query, $role),
        ]);
    }
}
```

Routes :
```php
Route::middleware('auth')->group(function () {
    Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
    Route::get('/docs/{path}', [DocsController::class, 'show'])
        ->where('path', '.*')
        ->name('docs.show');
    Route::post('/docs/search', [DocsController::class, 'search'])
        ->name('docs.search');
});
```

### Tâche 3 — UI DocsViewer (jour 2)

Composants Vue dans `resources/js/Pages/Docs/` :
- `Index.vue` : arbre + introduction
- `Show.vue` : contenu + sidebar + TOC
- `Components/DocsTree.vue` : arbre navigation
- `Components/DocsSearch.vue` : recherche
- `Components/DocsToc.vue` : table des matières

```vue
<!-- Pages/Docs/Show.vue -->
<script setup>
import { ref, onMounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DocsTree from './Components/DocsTree.vue'
import DocsSearch from './Components/DocsSearch.vue'
import DocsToc from './Components/DocsToc.vue'

const props = defineProps({
  doc: Object,
  tree: Object,
})

const contentRef = ref(null)

onMounted(() => {
  // Re-render Mermaid diagrams
  if (window.mermaid) {
    window.mermaid.init(undefined, '.mermaid')
  }
  
  // Render LaTeX via KaTeX
  import('katex/contrib/auto-render/auto-render.js').then((module) => {
    module.default(contentRef.value, {
      delimiters: [
        { left: '$$', right: '$$', display: true },
        { left: '$', right: '$', display: false },
      ]
    })
  })
})
</script>

<template>
  <AppLayout>
    <div class="flex h-screen">
      <!-- Sidebar left -->
      <aside class="w-64 bg-gray-50 border-r overflow-y-auto p-4">
        <DocsSearch />
        <DocsTree :tree="tree" :current-path="doc.path" />
      </aside>
      
      <!-- Main content -->
      <main class="flex-1 overflow-y-auto p-8 max-w-4xl">
        <nav class="breadcrumbs text-sm text-gray-500 mb-4">
          <Link href="/docs">📚 Documentation</Link>
          <span class="mx-2">/</span>
          <span>{{ doc.title }}</span>
        </nav>
        
        <article 
          ref="contentRef" 
          class="prose prose-blue max-w-none"
          v-html="doc.content_html"
        ></article>
      </main>
      
      <!-- TOC right -->
      <aside class="w-64 border-l overflow-y-auto p-4 hidden lg:block">
        <h3 class="font-semibold mb-2">Sur cette page</h3>
        <DocsToc :items="doc.toc" />
      </aside>
    </div>
  </AppLayout>
</template>
```

### Tâche 4 — Contenu initial (jour 3)

Créer 20+ pages dans `resources/markdown/` :

```
resources/markdown/
├── admin/
│   ├── 01-dashboard.md
│   ├── 02-utilisateurs.md
│   ├── 03-workspaces.md
│   ├── 04-backups.md
│   ├── 05-audit-log.md
│   └── 06-monitoring.md
├── prof/
│   ├── 01-premiers-pas.md
│   ├── 02-banque-questions.md
│   ├── 03-creer-examen.md
│   ├── 04-cbm-guide.md
│   ├── 05-distribuer-examen.md
│   ├── 06-analytics.md
│   ├── 07-exports.md
│   └── 08-faq.md
├── student/
│   ├── 01-passer-examen.md
│   ├── 02-comprendre-cbm.md
│   ├── 03-voir-correction.md
│   └── 04-faq.md
└── shared/
    ├── 01-glossaire.md
    ├── 02-support.md
    ├── 03-licences.md
    └── 04-privacy-rgpd.md
```

Chaque page :
- Titre H1
- Table des matières auto-générée
- Contenu structuré en sections (H2, H3)
- Placeholders pour images/vidéos
- Liens croisés vers autres pages

Exemple `resources/markdown/prof/04-cbm-guide.md` :
```markdown
# Guide du Certainty-Based Marking (CBM)

Le CBM permet d'évaluer à la fois la connaissance ET la conscience qu'a l'étudiant de sa connaissance.

## Pourquoi utiliser le CBM ?

Le QCM classique récompense le hasard. Le CBM récompense l'honnêteté intellectuelle...

## Comment configurer le CBM

:::diagram type=mermaid description="Flux de configuration CBM"
graph LR
  A[Créer matrice] --> B[Choisir niveaux]
  B --> C[Définir scoring]
  C --> D[Activer sur examen]
:::

## Matrice par défaut

La matrice standard comporte 3 niveaux...

## Paramétrage avancé

Vous pouvez créer jusqu'à 10 niveaux...

## FAQ

**Q: Puis-je désactiver le CBM pour un seul examen ?**  
R: Oui, la case "CBM activé" est décochée par défaut à la création d'un nouvel examen.
```

### Tâche 5 — Tests + Polish (jour 4)

Tests Pest :
- `DocumentationServiceTest` (unit)
- `DocsControllerTest` (feature, RBAC)
- E2E : navigation + recherche

## ✅ CRITÈRES D'ACCEPTATION

- [ ] DocumentationService avec RBAC strict
- [ ] Admin voit tout, prof section prof+student+shared, étudiant section student+shared
- [ ] 20+ pages créées et navigables
- [ ] Placeholders Mermaid fonctionnent
- [ ] Recherche trouve dans pages accessibles
- [ ] KaTeX rend les formules LaTeX
- [ ] Responsive mobile
- [ ] Tests > 85%
- [ ] Tag `v2.0.0-beta.2`

## 📝 COMMITS ATTENDUS

- `feat(docs): add DocumentationService with RBAC`
- `feat(docs): add /docs routes and controller`
- `feat(docs): add DocsViewer Vue components`
- `feat(docs): add placeholder parsers (diagram, image, video)`
- `docs(content): add 6 admin pages`
- `docs(content): add 8 prof pages`
- `docs(content): add 4 student pages`
- `docs(content): add 4 shared pages`
- `feat(docs): add full-text search`
- `test(docs): add 20+ tests for documentation`

**Commence par Tâche 1 (DocumentationService).**
```

---

## Prompt Phase P6A — Sécurité avancée

### 🎯 À copier-coller (2 jours)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P6A : SÉCURITÉ AVANCÉE

Phase P5 terminée. Début de P6 (5 sous-phases en 10 jours).

## 🎯 Objectif Phase P6A (2 jours)

1. 2FA TOTP avec Laravel Fortify (Google Authenticator)
2. Audit log avec Spatie ActivityLog
3. Anti-cheat analyzer avec score de confiance

## 📋 TÂCHES

### Tâche 1 — 2FA avec Laravel Fortify (jour 1)

Fortify est déjà installé (P0). Activer le 2FA :

`config/fortify.php` :
```php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::updateProfileInformation(),
    Features::updatePasswords(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```

Les routes 2FA sont auto-générées :
- `POST /user/two-factor-authentication` (enable)
- `DELETE /user/two-factor-authentication` (disable)
- `POST /user/confirmed-two-factor-authentication` (confirm with code)
- `GET /user/two-factor-qr-code`
- `GET /user/two-factor-recovery-codes`
- `POST /user/two-factor-recovery-codes` (regenerate)

### Tâche 2 — UI 2FA Settings (jour 1)

Composant `resources/js/Pages/Prof/Settings/TwoFactor.vue` :

```vue
<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import axios from 'axios'

const user = computed(() => usePage().props.auth.user)
const step = ref('start') // start | qr | confirm | backup | enabled
const qrCode = ref('')
const secretKey = ref('')
const recoveryCodes = ref([])
const confirmForm = useForm({ code: '' })

async function startSetup() {
  await axios.post('/user/two-factor-authentication')
  const qrRes = await axios.get('/user/two-factor-qr-code')
  qrCode.value = qrRes.data.svg
  const secretRes = await axios.get('/user/two-factor-secret-key')
  secretKey.value = secretRes.data.secretKey
  step.value = 'qr'
}

function confirmCode() {
  confirmForm.post('/user/confirmed-two-factor-authentication', {
    onSuccess: async () => {
      const codesRes = await axios.get('/user/two-factor-recovery-codes')
      recoveryCodes.value = codesRes.data
      step.value = 'backup'
    }
  })
}

async function disable2FA() {
  if (!confirm('Désactiver le 2FA ?')) return
  await axios.delete('/user/two-factor-authentication')
  step.value = 'start'
}
</script>

<template>
  <div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-4">🔐 Authentification à deux facteurs</h2>
    
    <div v-if="user.two_factor_enabled" class="bg-green-50 p-4 rounded">
      <p>✅ 2FA activé sur votre compte</p>
      <Button @click="disable2FA" variant="danger" class="mt-2">Désactiver</Button>
    </div>
    
    <div v-else>
      <div v-if="step === 'start'">
        <p class="mb-4">Protégez votre compte avec un code à 6 chiffres généré par une app mobile.</p>
        <p class="text-sm text-gray-600 mb-4">
          Apps compatibles : Google Authenticator, Authy, Microsoft Authenticator, 1Password
        </p>
        <Button @click="startSetup">Activer 2FA</Button>
      </div>
      
      <div v-if="step === 'qr'" class="space-y-4">
        <h3 class="font-semibold">Étape 1 : Scannez le QR code</h3>
        <div v-html="qrCode" class="inline-block p-4 bg-white"></div>
        <p class="text-sm">Ou entrez manuellement : <code class="bg-gray-100 px-2 py-1">{{ secretKey }}</code></p>
        
        <h3 class="font-semibold">Étape 2 : Entrez le code à 6 chiffres</h3>
        <input 
          v-model="confirmForm.code" 
          type="text" 
          maxlength="6" 
          pattern="[0-9]{6}"
          class="input w-32 text-center text-2xl font-mono"
          placeholder="000000"
        />
        <Button @click="confirmCode" :disabled="confirmForm.processing">
          Activer
        </Button>
      </div>
      
      <div v-if="step === 'backup'" class="space-y-4">
        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded">
          <p class="font-semibold">⚠️ Conservez ces codes de backup en lieu sûr !</p>
          <p class="text-sm">Ils vous permettront de vous connecter si vous perdez votre téléphone.</p>
        </div>
        
        <div class="grid grid-cols-2 gap-2 font-mono">
          <code v-for="c in recoveryCodes" :key="c" class="bg-gray-100 p-2">{{ c }}</code>
        </div>
        
        <div class="flex gap-2">
          <Button @click="() => window.print()">🖨️ Imprimer</Button>
          <Button @click="step = 'enabled'">J'ai conservé les codes</Button>
        </div>
      </div>
    </div>
  </div>
</template>
```

### Tâche 3 — Audit log avec Spatie (jour 2)

Spatie ActivityLog est déjà installé (P0). Configurer dans `config/activitylog.php`.

Ajouter le trait `LogsActivity` sur les models importants :

```php
// app/Models/Exam.php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Exam extends Model
{
    use LogsActivity;
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'cbm_enabled'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => 
                "Examen {$eventName}"
            );
    }
}
```

Faire pareil pour : Question, Passage, User, Workspace, CommunityQuestion.

Helper pour logs manuels :
```php
activity()
    ->causedBy(auth()->user())
    ->performedOn($exam)
    ->withProperties(['old' => $oldData, 'new' => $newData])
    ->log('exam.custom_action');
```

### Tâche 4 — UI Audit log pour admin (jour 2)

Controller `app/Http/Controllers/Admin/AuditLogController.php` :

```php
public function index(Request $request)
{
    $query = Activity::query()->latest();
    
    if ($user = $request->input('user_id')) {
        $query->where('causer_id', $user);
    }
    
    if ($event = $request->input('event')) {
        $query->where('event', $event);
    }
    
    if ($from = $request->input('from')) {
        $query->where('created_at', '>=', $from);
    }
    
    return Inertia::render('Admin/Audit/Index', [
        'activities' => $query->paginate(50),
        'filters' => $request->only(['user_id', 'event', 'from']),
    ]);
}

public function export(Request $request)
{
    // Export CSV via Maatwebsite
}
```

### Tâche 5 — AntiCheatService (jour 2)

Service `app/Services/AntiCheatService.php` :

```php
<?php
namespace App\Services;

use App\Models\Passage;

class AntiCheatService
{
    public function analyzePassage(Passage $passage): array
    {
        $score = 1.0;
        $signals = [];
        
        // Focus loss analysis
        $focusEvents = $passage->focus_events ?? [];
        $longBlurs = collect($focusEvents)
            ->where('event_type', 'blur')
            ->where('duration_ms', '>', 5000)
            ->count();
        if ($longBlurs > 0) {
            $score -= 0.1 * min($longBlurs, 5);
            $signals[] = "Focus loss: $longBlurs times";
        }
        
        // Copy/paste detection
        $copyEvents = collect($focusEvents)
            ->where('event_type', 'copy_paste')
            ->count();
        if ($copyEvents > 0) {
            $score -= 0.2 * min($copyEvents, 3);
            $signals[] = "Copy/paste: $copyEvents times";
        }
        
        // DevTools
        $antiCheatSignals = $passage->anti_cheat_signals ?? [];
        if ($antiCheatSignals['devtools_opened'] ?? false) {
            $score -= 0.3;
            $signals[] = 'DevTools opened';
        }
        
        // Response time analysis
        $fastAnswers = collect($passage->answers ?? [])
            ->filter(fn($a) => ($a['time_spent_ms'] ?? 0) < 1000)
            ->count();
        if ($fastAnswers > 3) {
            $score -= 0.2;
            $signals[] = "$fastAnswers suspiciously fast answers";
        }
        
        // Pattern detection (toutes mêmes lettres)
        $selectedFirst = collect($passage->answers ?? [])
            ->pluck('selected_options.0')
            ->filter();
        if ($selectedFirst->count() > 5 && $selectedFirst->unique()->count() === 1) {
            $score -= 0.3;
            $signals[] = 'All same letter pattern detected';
        }
        
        $score = max(0, min(1, $score));
        
        return [
            'score' => round($score, 2),
            'signals' => $signals,
            'risk_level' => match(true) {
                $score >= 0.8 => 'low',
                $score >= 0.5 => 'medium',
                default => 'high',
            },
        ];
    }
}
```

Intégrer dans `SubmitPassage` action.

## ✅ CRITÈRES D'ACCEPTATION

- [ ] 2FA fonctionne avec Google Authenticator
- [ ] Backup codes single-use
- [ ] Audit log capture toutes actions Eloquent sensibles
- [ ] Page admin audit avec filtres
- [ ] Anti-cheat score calculé sur chaque passage
- [ ] Tests > 85%

## 📝 COMMITS ATTENDUS

- `feat(auth): enable Fortify 2FA TOTP`
- `feat(auth): add 2FA settings UI`
- `feat(audit): enable Spatie ActivityLog on all models`
- `feat(audit): add admin audit viewer`
- `feat(security): add AntiCheatService`
- `feat(security): integrate anti-cheat in passage submission`
- `test(security): add 30+ tests`
```

---

## Prompt Phase P6B — Multi-tenant + SSO

### 🎯 À copier-coller (2 jours)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P6B : MULTI-TENANT + SSO

Phase P6A (Sécurité) terminée.

## 🎯 Objectif (2 jours)

1. Multi-tenant avec workspaces (déjà modelé en P1)
2. Isolation stricte des données par workspace
3. SSO Google OAuth via Socialite
4. SSO Microsoft OAuth via Socialite
5. UI gestion workspaces (super-admin)

## 📋 TÂCHES

### Tâche 1 — Middleware d'isolation workspace (jour 1)

Créer `app/Http/Middleware/EnsureWorkspaceScope.php` :

```php
<?php
namespace App\Http\Middleware;

use Closure;

class EnsureWorkspaceScope
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        
        if (!$user) return $next($request);
        
        // Super admin bypass
        if ($user->role === 'super_admin') return $next($request);
        
        // Force workspace scope dans Eloquent global scope
        if (!$user->workspace_id) {
            abort(403, 'No workspace assigned');
        }
        
        app()->instance('current.workspace_id', $user->workspace_id);
        
        return $next($request);
    }
}
```

Global scope dans `app/Models/Scopes/WorkspaceScope.php` :

```php
<?php
namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class WorkspaceScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->has('current.workspace_id')) {
            $builder->where($model->getTable() . '.workspace_id', app('current.workspace_id'));
        }
    }
}
```

Appliquer sur Models `Exam`, `Question`, `Passage`, etc. :
```php
protected static function booted(): void
{
    static::addGlobalScope(new WorkspaceScope);
}
```

### Tâche 2 — WorkspaceController CRUD (jour 1)

Controller super-admin :
```bash
php artisan make:controller Admin/WorkspaceController --resource
```

Pages Inertia :
- `Admin/Workspaces/Index.vue` (liste)
- `Admin/Workspaces/Create.vue` (création)
- `Admin/Workspaces/Edit.vue` (modification)
- `Admin/Workspaces/Show.vue` (détails + stats)

### Tâche 3 — SSO Google via Socialite (jour 2)

Config dans `config/services.php` :
```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
],
'microsoft' => [
    'client_id' => env('MICROSOFT_CLIENT_ID'),
    'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
    'redirect' => env('MICROSOFT_REDIRECT_URI', env('APP_URL').'/auth/microsoft/callback'),
    'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
],
```

Installer provider Microsoft :
```bash
composer require socialiteproviders/microsoft
```

Enregistrer provider dans `AppServiceProvider` :
```php
Event::listen(function (SocialiteWasCalled $event) {
    $event->extendSocialite('microsoft', \SocialiteProviders\Microsoft\Provider::class);
});
```

Controller `app/Http/Controllers/Auth/SocialiteController.php` :

```php
<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }
    
    public function callback(string $provider)
    {
        $socialUser = Socialite::driver($provider)->user();
        
        $user = User::firstOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'uuid' => 'USR-' . strtoupper(substr(md5(uniqid()), 0, 4)),
                'name' => $socialUser->getName(),
                'workspace_id' => $this->resolveWorkspace($socialUser),
                'password' => bcrypt(str_random(32)), // Pas utilisé (SSO only)
                'email_verified_at' => now(),
            ]
        );
        
        $user->update([
            "{$provider}_id" => $socialUser->getId(),
            'last_login_at' => now(),
        ]);
        
        Auth::login($user, remember: true);
        
        return redirect()->intended('/dashboard');
    }
    
    private function resolveWorkspace($socialUser): int
    {
        // Logique : par défaut = WKS-DEFAULT
        // Si email @ecole-a.fr → workspace école A
        // etc.
        return Workspace::where('slug', 'default')->first()->id;
    }
}
```

Routes :
```php
Route::get('/auth/{provider}', [SocialiteController::class, 'redirect'])
    ->where('provider', 'google|microsoft');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
    ->where('provider', 'google|microsoft');
```

UI login avec SSO :

```vue
<!-- Pages/Auth/Login.vue -->
<script setup>
// ... form login existant
</script>

<template>
  <div>
    <form @submit.prevent="login">
      <!-- inputs email + password existants -->
    </form>
    
    <div class="my-4 flex items-center">
      <hr class="flex-1" />
      <span class="px-3 text-gray-500">ou</span>
      <hr class="flex-1" />
    </div>
    
    <div class="space-y-2">
      <a href="/auth/google" class="btn btn-outline w-full flex items-center justify-center gap-2">
        <img src="/assets/img/google.svg" class="w-5 h-5" />
        Continuer avec Google
      </a>
      <a href="/auth/microsoft" class="btn btn-outline w-full flex items-center justify-center gap-2">
        <img src="/assets/img/microsoft.svg" class="w-5 h-5" />
        Continuer avec Microsoft
      </a>
    </div>
  </div>
</template>
```

## ✅ CRITÈRES D'ACCEPTATION

- [ ] Isolation stricte : prof WKS-A ne voit pas WKS-B
- [ ] SSO Google fonctionne end-to-end
- [ ] SSO Microsoft fonctionne end-to-end
- [ ] Super-admin peut créer/gérer workspaces
- [ ] Middleware ne laisse pas fuiter

## 📝 COMMITS ATTENDUS

- `feat(workspace): add global scope for workspace isolation`
- `feat(workspace): add admin workspace CRUD`
- `feat(sso): add Socialite with Google provider`
- `feat(sso): add Microsoft provider`
- `feat(sso): add login UI with SSO buttons`
- `feat(sso): add callback handler with user creation`
- `test(workspace): add 20+ tests for isolation`
```

---

## Prompt Phase P6C — Intégrations LMS

### 🎯 À copier-coller (2 jours)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P6C : INTÉGRATIONS LMS

## 🎯 Objectif (2 jours)

1. Import Moodle XML
2. Import Word Aiken (via phpoffice/phpword)
3. Import Excel (via Maatwebsite/Excel)
4. Export SCORM 1.2 + 2004
5. Export xAPI statements
6. OpenAPI + Swagger UI

## 📋 TÂCHES RAPIDES

### Jour 1 — Imports

**Moodle XML Parser** dans `app/Services/Lms/MoodleImportService.php` :
- Parse `<quiz><question>` avec SimpleXML
- Transforme en format Certio v2
- Retourne array de questions

**Word Aiken Parser** :
```bash
composer require phpoffice/phpword
```
- Lit .docx
- Regex `/Q\d+\..*?ANSWER:\s*([A-Z])/s`
- Transforme

**Excel Import** via Maatwebsite :
```bash
composer require maatwebsite/excel
```
- Template spreadsheet avec colonnes standardisées
- Import batch

UI unifiée dans `Pages/Prof/Questions/Import.vue` avec sélecteur de source.

### Jour 2 — Exports

**SCORM 1.2** : Service `ScormExportService` qui crée un ZIP avec :
- imsmanifest.xml
- index.html (SCO avec SCORM API JS)
- questions.json
- CSS/JS

**xAPI statements** générés selon spec :
```json
{
  "actor": { "mbox": "mailto:student@..." },
  "verb": { "id": "http://adlnet.gov/expapi/verbs/completed" },
  "object": { "id": "https://certio.app/exam/..." },
  "result": { "score": { "scaled": 0.85 } }
}
```

**Swagger UI** : placer `/api-docs.html` qui charge `/api/openapi.json` généré manuellement.

## ✅ CRITÈRES D'ACCEPTATION

- [ ] Import Moodle XML OK sur sample
- [ ] Import Word Aiken OK
- [ ] Import Excel OK
- [ ] Export SCORM 1.2 importable dans Moodle test
- [ ] Export SCORM 2004 OK
- [ ] xAPI statements valides
- [ ] Swagger UI fonctionnel
```

---

## Prompt Phase P6D — A11y + i18n + PWA

### 🎯 À copier-coller (1 jour)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P6D : A11y + i18n + PWA

## 🎯 Objectif (1 jour)

1. WCAG AA complet (axe-core 0 violation)
2. i18n complet FR/EN (toutes strings)
3. PWA service worker complet avec offline

## 📋 TÂCHES

### Matin (4h) — WCAG AA

- Audit Chrome Lighthouse Accessibility → corriger violations
- `<img>` alt text partout
- Contrastes 4.5:1 minimum
- Navigation clavier 100% (tab order, focus visible)
- Raccourcis : Ctrl+S save, Esc close modal
- Skip links "Aller au contenu"
- ARIA roles corrects

### Après-midi (4h) — i18n + PWA

**i18n** :
- Externaliser toutes strings dans `resources/lang/fr.json` + `en.json` (~500 clés)
- Utiliser `$t('key')` partout
- Sélecteur de langue dans header
- Persister préférence en localStorage

**PWA** :
Étoffer `public/service-worker.js` :
```javascript
const CACHE_NAME = 'certio-v2.0.0';
const STATIC_URLS = [...]; // assets statiques
const API_CACHE = 'certio-api-v2';

self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  
  if (STATIC_URLS.includes(url.pathname)) {
    event.respondWith(
      caches.match(event.request).then(r => r || fetch(event.request))
    );
  } else if (url.pathname.startsWith('/api/passages/answer')) {
    // Network first, fallback offline queue
    event.respondWith(
      fetch(event.request).catch(() => saveOfflineAnswer(event.request))
    );
  }
});

async function saveOfflineAnswer(request) {
  // IndexedDB queue
  // ...
  return new Response(JSON.stringify({ ok: true, offline: true }));
}

// Background sync
self.addEventListener('sync', event => {
  if (event.tag === 'sync-offline') {
    event.waitUntil(syncOfflineAnswers());
  }
});
```

Prompt d'installation PWA :
```vue
<InstallPrompt v-if="showInstall" @install="installPwa" />
```

## ✅ CRITÈRES D'ACCEPTATION

- [ ] axe-core : 0 erreur critique
- [ ] Lighthouse A11y ≥ 95
- [ ] Lighthouse PWA = 100
- [ ] Toutes strings en i18n
- [ ] EN complet
- [ ] Installation PWA testée
- [ ] Mode offline passages OK
```

---

## Prompt Phase P6E — Banque communautaire

### 🎯 À copier-coller (3 jours)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P6E : BANQUE COMMUNAUTAIRE

## 🎯 Objectif (3 jours - dernière sous-phase P6)

1. CommunityBankService complet
2. Publish/unpublish workflow
3. Fork avec attribution
4. Rating + flag system
5. Modération super-admin
6. Seed 100+ questions initiales

## 📋 TÂCHES

### Jour 1 — Service + API

`app/Services/CommunityBankService.php` avec :
- `publish(Question, license, User)` : crée `CommunityQuestion`
- `unpublish(CommunityQuestion, User)`
- `fork(CommunityQuestion, User)` : crée nouvelle Question dans workspace du forker
- `rate(CommunityQuestion, User, stars, comment)`
- `flag(CommunityQuestion, User, reason)`
- `moderate(CommunityQuestion, decision, moderator)`

Controller `Admin/CommunityBankController` + `Prof/CommunityController`.

### Jour 2 — UI

Composants :
- `<CommunityQuestionCard>` : carte avec rating, forks, auteur
- `<PublishToCommunitySection>` : dans éditeur de question
- `<CommunityBrowser>` : page avec filtres + facettes + tri
- `<RatingInput>` : input 1-5 étoiles
- `<ModerationPanel>` : admin

### Jour 3 — Modération + Seed

Page admin modération :
- Liste questions pending
- Approve / Reject avec raison
- Stats globales

Seeder `CommunityQuestionsSeeder` :
- Sélectionner 100 meilleures questions du prof Mohamed
- Les publier en communauté avec licence CC-BY-SA

```bash
php artisan db:seed --class=CommunityQuestionsSeeder
```

## ✅ CRITÈRES D'ACCEPTATION

- [ ] Publish/unpublish OK
- [ ] Fork avec attribution préservée
- [ ] Rating fonctionne
- [ ] Modération super-admin OK
- [ ] 100+ questions seed disponibles
- [ ] Filtres et recherche
- [ ] Tag `v2.0.0-rc.1` 🎉

---

## 🎉 FIN DE PHASE P6 - RELEASE CANDIDATE 1

Après P6E :
1. Merge toutes PR dans `develop`
2. Tag `v2.0.0-rc.1`
3. Deploy staging pour tests pilotes
4. Communication : "Certio v2.0 Beta pour testeurs"
```

---

## Prompt Phase P7 — Tests + Admin Filament

### 🎯 À copier-coller (5 jours)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P7 : TESTS + ADMIN FILAMENT

Phase P6 terminée. Tu as `v2.0.0-rc.1` avec toutes features.

## 🎯 Objectif (5 jours)

1. Couverture tests Pest ≥ 85%
2. 5 workflows E2E
3. OWASP Top 10 audit
4. Tests de charge
5. 🎁 Admin panel avec Filament

## 📋 TÂCHES

### Jour 1-2 — Compléter couverture

Audit coverage :
```bash
./vendor/bin/pest --coverage --min=85
```

Ajouter tests manquants pour chaque service/action :
- CbmScoringService → 95%
- QuestionTypeResolver → 95%
- ScoringService → 90%
- AnalyticsService → 85%
- CommunityBankService → 85%
- DocumentationService → 85%
- Toutes Actions → 85%

### Jour 3 — E2E Workflows

Pest + Laravel Dusk pour E2E :
```bash
composer require --dev laravel/dusk pestphp/pest-plugin-laravel-dusk
php artisan dusk:install
```

5 workflows E2E dans `tests/Browser/` :

1. **FullExamWorkflow** : prof crée examen CBM → étudiant passe → résultats
2. **ImportExportWorkflow** : import Moodle → crée examen → export SCORM
3. **SsoWorkflow** : login Google → création compte → workspace
4. **CommunityWorkflow** : publish → modération → fork
5. **TwoFactorWorkflow** : setup → validation → utilisation

### Jour 4 — 🎁 Admin Filament

Installer et configurer Filament :
```bash
composer require filament/filament
php artisan filament:install --panels
php artisan make:filament-user
```

Créer resources :
```bash
php artisan make:filament-resource User
php artisan make:filament-resource Workspace
php artisan make:filament-resource Exam
php artisan make:filament-resource Question
php artisan make:filament-resource CommunityQuestion
```

Personnaliser chaque resource avec :
- Formulaires avec tous les champs
- Tables avec filtres, sort, search
- Actions (view, edit, delete, custom)
- Widgets stats sur dashboard

Admin panel accessible sur `/admin` avec rôle admin/super_admin.

### Jour 5 — OWASP + Charge + Polish

**OWASP Top 10 tests** :
- A01 Broken Access Control : tests d'autorisation
- A02 Crypto : bcrypt + HMAC + Fortify
- A03 Injection : validation inputs
- A07 Auth Failures : rate limiting login
- A08 Data Integrity : checksums passages

**Tests de charge** :
```bash
# k6 ou Apache Bench
ab -n 1000 -c 10 https://staging.certio.app/api/exams
```

Target : 100 req/s, p95 < 200ms.

**Axe-core automatisé** via Dusk + axe-core.

## ✅ CRITÈRES D'ACCEPTATION

- [ ] Coverage Pest ≥ 85%
- [ ] 5 E2E Dusk passent
- [ ] OWASP Top 10 OK
- [ ] 100 req/s tenus
- [ ] Admin Filament fonctionnel
- [ ] Larastan niveau 8 OK
- [ ] Pint --test OK
- [ ] Tag `v2.0.0-rc.2`
```

---

## Prompt Phase P8 — Migration + Déploiement

### 🎯 À copier-coller (3 jours)

```
# CONTEXTE — CERTIO v2.0 LARAVEL — PHASE P8 : DÉPLOIEMENT 🎉

🎊 DERNIÈRE PHASE ! Tu as `v2.0.0-rc.2` validée.

## 🎯 Objectif (3 jours)

1. Migration finale v1 → Laravel v2 en prod
2. Déploiement production VPS OVH
3. Switch DNS certio.app
4. Communication utilisateurs
5. Release v2.0.0

## 📋 TÂCHES

### Jour 1 — Migration data prod

```bash
# 1. Backup v1 complet
ssh user@v1-server "cd /var/www/certio-v1 && tar czf backup.tar.gz data/"
scp user@v1-server:/var/www/certio-v1/backup.tar.gz /tmp/

# 2. Extraction locale pour test
mkdir /tmp/v1-test
tar xzf /tmp/backup.tar.gz -C /tmp/v1-test

# 3. Test migration en local (dry-run)
cd /var/www/certio-laravel
php artisan certio:migrate-from-v1 --source=/tmp/v1-test/data --dry-run --verbose

# 4. Si OK : migration réelle en local pour valider
php artisan certio:migrate-from-v1 --source=/tmp/v1-test/data --verbose
php artisan test --filter MigrationTest

# 5. Vérifications
php artisan tinker
>>> User::count()
>>> Exam::count()
>>> Question::count()
>>> Passage::count()
```

### Jour 2 — Déploiement Laravel production

**Préparation VPS** (certio.app) :
```bash
# Installer dépendances
sudo apt install php8.3-fpm php8.3-sqlite3 php8.3-xml php8.3-mbstring \
  php8.3-curl php8.3-zip composer nodejs npm supervisor

# Clone + setup
cd /var/www
sudo git clone https://github.com/melafrit/certio.git
cd certio
sudo chown -R www-data:www-data .

# Checkout tag release candidate
sudo -u www-data git checkout v2.0.0-rc.2

# Install prod deps
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm ci && sudo -u www-data npm run build

# .env prod
sudo nano .env
# APP_ENV=production, APP_DEBUG=false, etc.

sudo -u www-data php artisan key:generate
sudo -u www-data php artisan storage:link
sudo -u www-data touch database/database.sqlite
sudo -u www-data php artisan migrate --force

# Migration depuis v1
sudo -u www-data php artisan certio:migrate-from-v1 --source=/path/to/v1/data

# Optimize
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Nginx config production
sudo nano /etc/nginx/sites-available/certio.app
```

Nginx config :
```nginx
server {
    listen 443 ssl http2;
    server_name certio.app;
    root /var/www/certio/public;
    
    ssl_certificate /etc/letsencrypt/live/certio.app/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/certio.app/privkey.pem;
    
    # Security headers
    add_header Strict-Transport-Security "max-age=63072000" always;
    add_header X-Frame-Options SAMEORIGIN;
    add_header X-Content-Type-Options nosniff;
    add_header Referrer-Policy strict-origin-when-cross-origin;
    
    # Gzip
    gzip on;
    gzip_types text/css application/javascript application/json;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }
}
```

**Supervisor** pour queues workers :
```ini
# /etc/supervisor/conf.d/certio-queue.conf
[program:certio-queue]
command=php /var/www/certio/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/certio-queue.log
```

**Cron** pour scheduler :
```bash
sudo crontab -u www-data -e
* * * * * cd /var/www/certio && php artisan schedule:run >> /dev/null 2>&1
```

**Redirection v1** : `v1.certio.app` pointe sur l'ancien serveur en read-only.

**Switch DNS** : `certio.app` → IP nouveau VPS Laravel.

### Jour 3 — Communication + Release

**Smoke tests** après déploiement :
```bash
curl -I https://certio.app
curl https://certio.app/api/health
```

**Monitoring** : activer Telescope (prod mode viewer) + logs Nginx.

**Tag v2.0.0** :
```bash
git tag -a v2.0.0 -m "Certio v2.0.0 — Production Release"
git push origin v2.0.0
```

**GitHub Release** : via UI, attacher release notes complètes.

**Email utilisateurs** : envoyer via Mail::send batch (template dans CHANGELOG_V2.md).

**Post LinkedIn** : voir template dans livrable 5.

## ✅ CRITÈRES D'ACCEPTATION FINAUX

- [ ] Migration prod réussie sans perte
- [ ] Smoke tests OK
- [ ] Utilisateurs v1 peuvent se connecter
- [ ] Nouveaux examens CBM fonctionnent
- [ ] Monitoring vert 48h
- [ ] Email envoyé
- [ ] Tag `v2.0.0` créé
- [ ] GitHub Release publiée
- [ ] README mis à jour
- [ ] 🎉 **RELEASE CERTIO v2.0.0** 🎉

## 📝 COMMITS ATTENDUS

- `feat(migration): finalize production migration script`
- `test(migration): validate on prod copy`
- `chore(release): prepare v2.0.0`
- `docs(changelog): add v2.0.0 release notes`
- `deploy(prod): certio v2.0.0 released`

## 🎊 CÉLÉBRATION !

Bravo, tu as livré **Certio v2.0** ! 🚀

- 5 mois de dev solo avec IA assist
- 51 jours actifs
- 20 000+ lignes de code
- 300+ tests Pest
- Un produit SaaS pro

Prochaine étape : **v2.1** avec générateur IA (voir bonus livrable).
```

---

## Conclusion Livrable D

Ces 8 prompts couvrent les **dernières 25 jours** du projet :
- P5 Docs (4j)
- P6A-E Améliorations (10j total)
- P7 Tests + Filament (5j)
- P8 Déploiement (3j)

**Total des 4 livrables (A + B + C + D)** : 51 jours pour Certio v2.0 complet.

Le **Bonus** sera la note de cadrage v2.1 (Générateur IA).

---

© 2026 Mohamed EL AFRIT — mohamed@elafrit.com  
Certio v2.0 Laravel — CC BY-NC-SA 4.0
