# 📋 Addendum Note de Cadrage — Dashboard Étudiant + Historique

> **Ajout majeur au scope Certio v2.0 Laravel**  
> Feature "Apprentissage par la correction"

| Champ | Valeur |
|---|---|
| **Document** | Addendum à `01_NOTE_DE_CADRAGE_LARAVEL.md` |
| **Version ajout** | 2.1 de la note de cadrage |
| **Impact planning** | +3 jours sur phase P4 |
| **Nouveau total v2.0** | 54 jours (vs 51 initial) |
| **Date** | Avril 2026 |
| **Auteur** | Mohamed EL AFRIT |
| **Licence** | CC BY-NC-SA 4.0 |

---

## 🎯 Vision : "De l'évaluation à l'apprentissage"

Cette feature transforme Certio d'un **outil d'évaluation** en **véritable plateforme d'apprentissage**.

### 💡 Valeur pédagogique ajoutée

Un étudiant qui peut :
- **Revoir ses examens passés** apprend de ses erreurs
- **Comprendre pourquoi** sa réponse était fausse renforce sa mémorisation
- **Suivre sa progression** développe sa métacognition
- **Identifier ses points faibles** guide ses révisions

**Résultat** : Certio devient un outil **aimé des étudiants**, pas seulement **toléré**.

---

## 📦 Scope détaillé (ajout v2.0)

### ✅ Features du Dashboard Étudiant

#### 1. Page Dashboard principal (`/student/dashboard`)

**KPIs globaux** :
- Nombre total d'examens passés
- Score moyen sur tous les examens
- Tendance (+X% vs mois précédent)
- Calibration CBM globale (si CBM activé)
- Thème le plus fort / le plus faible

**Graphiques** :
- 📈 **Progression dans le temps** (line chart scores par date)
- 🎯 **Performance par thème** (radar chart)
- 🎲 **Calibration CBM** (scatter plot si CBM utilisé)

**Accès rapide** :
- 3 derniers examens avec score
- Lien vers historique complet
- Conseils personnalisés basés sur patterns

#### 2. Page Historique (`/student/history`)

**Tableau filtrable** :
- Colonnes : Date, Examen, Module, Score, Durée, Statut
- Filtres : date range, score min, module, statut
- Tri par colonne
- Pagination (20 par page)
- Export CSV de son historique

**Badges visuels** :
- ✅ Réussi (≥ passing_score)
- ⚠️ À revoir (entre 40% et passing_score)
- ❌ Échec (< 40%)
- 🔒 Correction non disponible (prof pas publié)
- ✅ Correction disponible

#### 3. Page Passage — Résumé (`/student/passages/{uuid}`)

Accès rapide à un passage spécifique avec :
- Résumé (score, date, durée, mode CBM si activé)
- Bouton "Voir la correction détaillée"
- Graphique "Mes réponses question par question"
- Analyse CBM personnelle (si CBM)

#### 4. Page Correction détaillée (`/student/passages/{uuid}/correction`)

**LA page clé** de cette feature. Pour chaque question :

**Section "Ta réponse"** :
- Énoncé de la question (Markdown + LaTeX)
- Ta réponse avec CBM déclaré (si CBM)
- Temps passé sur la question
- Score obtenu

**Section "La bonne réponse"** :
- Options avec marquage ✅/❌
- Ta sélection mise en évidence

**Section "💡 Explication"** :
- Explication pédagogique détaillée fournie par le prof
- Formules LaTeX rendues via KaTeX
- Visualisations si pertinent

**Section "⚠️ Pièges à éviter"** :
- Pour chaque distracteur que l'étudiant a choisi
- Explication du `why_wrong` du prof
- Pédagogie sur pourquoi c'était tentant

**Section "📚 Ressources complémentaires"** :
- Lien vers le module/chapitre du cours
- Lien vers page de documentation Certio
- Lien vers vidéo/article externe (si prof fourni)

**Navigation** :
- Question précédente / suivante
- Retour à la liste des questions
- Sommaire cliquable

### ✅ Features côté Prof

#### 1. Paramètre par examen : visibilité des corrections

Nouveau champ dans formulaire création/édition examen :

```
🔓 Visibilité des corrections

☐ Jamais (corrections non accessibles aux étudiants)
☐ Auto après soumission (immédiatement visible)
☐ Auto après clôture de l'examen
☑ Manuel (je publierai quand je serai prêt) [par défaut]
```

**Default = "manual"** → le prof garde le contrôle par défaut.

#### 2. Bouton "Publier corrections" dans suivi passages

Dans la page de suivi d'un examen (`/prof/exams/{uuid}/passages`) :

```
📊 Suivi des passages : "TP Maths Jour 1"

Status : 15 étudiants ont terminé
Corrections : 🔒 Non publiées

[📢 Publier les corrections à TOUS] ← Bouton principal

Ou par étudiant individuel dans le tableau :
| Étudiant     | Score | Date         | Actions           |
| Jean Dupont  | 85%   | 15 avr 2026  | [Publier correction] |
| Marie Martin | 72%   | 15 avr 2026  | [Publier correction] |
| ...          | ...   | ...          | ...               |
```

#### 3. Enrichissement questions : champ "why_wrong"

Dans l'éditeur de question, pour chaque option **fausse** :

```
Option A : [x² + 3]  ☐ Correcte
  Pourquoi c'est faux (optionnel mais recommandé) :
  [C'est f(x) moins une constante, pas la dérivée. 
   Pour dériver, applique la règle (xⁿ)' = n·xⁿ⁻¹]
```

C'est ce qui permet les **pièges à éviter** pédagogiques dans la correction.

#### 4. Enrichissement questions : champ "reference"

Nouveau bloc dans l'éditeur de question :

```
📚 Référence (optionnel)
├── Module :       [Maths pour l'IA]
├── Chapitre :     [Chapitre 2 - Calcul différentiel]
├── Section :      [2.3 Dérivées des polynômes]
├── Doc Certio :   [student/maths-derivees-polynomes ▼]
└── URL externe :  [https://youtube.com/...]
```

Cela permet de **lier la question au cours** — l'étudiant peut cliquer pour réviser.

---

## 🏗️ Architecture technique (ajouts)

### Nouveaux modèles

Pas de nouveaux modèles — extensions de `Exam` et `Question` + nouveau service.

### Nouvelles migrations (2)

#### Migration `add_corrections_visibility_to_exams_table.php`

```php
Schema::table('exams', function (Blueprint $table) {
    $table->enum('correction_visibility', [
        'never',
        'auto_after_submit',
        'auto_after_close',
        'manual'
    ])->default('manual')->after('passing_score');
    
    $table->timestamp('corrections_published_at')->nullable()
        ->after('correction_visibility');
    
    $table->foreignId('corrections_published_by')->nullable()
        ->constrained('users')
        ->after('corrections_published_at');
});
```

#### Migration `add_reference_to_questions_table.php`

```php
Schema::table('questions', function (Blueprint $table) {
    $table->json('reference')->nullable()->after('explanation');
});
```

### Enum ajouté

```php
// app/Enums/CorrectionVisibility.php
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
}
```

### Nouveau service

`app/Services/StudentDashboardService.php` avec :
- `getGlobalKpis(User $student): array`
- `getProgressData(User $student): array`
- `getRadarData(User $student): array`
- `getHistory(User $student, array $filters): Collection`
- `getCorrection(Passage $passage): array`
- `canViewCorrection(User $student, Passage $passage): bool`

### Nouvelle action

`app/Actions/Exam/PublishCorrections.php` :

```php
class PublishCorrections
{
    public function executeForExam(Exam $exam, User $publisher): int
    {
        return DB::transaction(function () use ($exam, $publisher) {
            $exam->update([
                'correction_visibility' => CorrectionVisibility::Manual,
                'corrections_published_at' => now(),
                'corrections_published_by' => $publisher->id,
            ]);
            
            // Notifier tous les étudiants qui ont passé
            $passages = $exam->passages()->where('status', 'submitted')->get();
            
            foreach ($passages as $passage) {
                // Notification par email
                Mail::to($passage->student_email)
                    ->queue(new CorrectionsAvailableMail($passage));
            }
            
            activity()->causedBy($publisher)
                ->performedOn($exam)
                ->log('exam.corrections_published');
            
            return $passages->count();
        });
    }
    
    public function executeForPassage(Passage $passage, User $publisher): void
    {
        // Publication au cas par cas (marqueur sur passage)
        $passage->update([
            'correction_visible' => true,
            'correction_published_at' => now(),
            'correction_published_by' => $publisher->id,
        ]);
        
        Mail::to($passage->student_email)
            ->queue(new CorrectionsAvailableMail($passage));
    }
}
```

### Policy

`app/Policies/PassagePolicy.php` :

```php
public function viewCorrection(User $user, Passage $passage): bool
{
    // L'étudiant ne peut voir que ses propres passages
    if ($user->id !== $passage->user_id && $user->email !== $passage->student_email) {
        return false;
    }
    
    $exam = $passage->exam;
    
    return match($exam->correction_visibility) {
        CorrectionVisibility::Never => false,
        CorrectionVisibility::AutoAfterSubmit => 
            $passage->status === PassageStatus::Submitted,
        CorrectionVisibility::AutoAfterClose => 
            $exam->date_cloture && $exam->date_cloture->isPast(),
        CorrectionVisibility::Manual => 
            !is_null($exam->corrections_published_at) 
            || $passage->correction_visible === true,
    };
}
```

### Nouveau composant Vue

`resources/js/Components/Question/QuestionCorrection.vue` — composant clé avec :
- Toggle sections (réponse, explication, pièges, ressources)
- Rendu LaTeX via KaTeX
- Rendu Markdown via marked
- Animations fluides
- Mode print-friendly

### Email template

`resources/views/emails/corrections-available.blade.php` :

```markdown
# Vos corrections sont disponibles ! 📚

Bonjour {{ $student_name }},

Votre professeur vient de publier les corrections de l'examen :
**{{ $exam_title }}**

Votre score : **{{ $score }}%**

[**Voir la correction détaillée →**]({{ $correction_url }})

Cette correction inclut :
- Explications détaillées pour chaque question
- Pièges à éviter
- Liens vers les ressources de cours

Bonne révision !

L'équipe Certio
```

---

## 🔄 Workflow complet

### Côté prof (workflow type)

```
1. Prof crée un examen
   ├── Choix : correction_visibility = 'manual' [défaut]
   └── Publie l'examen

2. Étudiants passent l'examen
   └── Scores calculés automatiquement
   └── Corrections NON visibles

3. Prof consulte les résultats
   ├── Analyse les performances
   ├── Ajuste éventuellement les explications
   └── Décide de publier

4. Prof clique "📢 Publier corrections pour tous"
   ├── Email automatique envoyé aux étudiants
   └── Corrections visibles dans leur dashboard

5. Prof peut aussi publier cas par cas
   └── Utile pour rattrapages ou examens individualisés
```

### Côté étudiant (workflow type)

```
1. Étudiant reçoit email : "Vos corrections sont disponibles"

2. Clic → Login Certio → redirigé vers /student/passages/{uuid}

3. Vue d'ensemble du passage
   ├── Score : 85%
   ├── Temps : 42min
   ├── Calibration CBM : Bien calibré
   └── Bouton "Voir correction détaillée"

4. Page correction détaillée
   ├── Navigation question par question
   ├── Voit ses réponses + bonnes réponses
   ├── Lit les explications du prof
   ├── Comprend les pièges (why_wrong)
   └── Clique sur liens vers cours pour réviser

5. Dashboard global
   └── Voit sa progression globale
   └── Identifie ses points faibles par thème
   └── Motivé à s'améliorer
```

---

## 📊 Impact sur planning v2.0

### Phase P4 révisée : Scoring & Analytics + Dashboard Étudiant

**Durée** : 5 jours → **8 jours** (+3 jours)

| Jour | Contenu | Statut |
|:-:|---|:-:|
| 1 | ScoringService avec 3 modes | 🔵 existant |
| 2 | Combinaison CBM + multi | 🔵 existant |
| 3 | Analytics prof (calibration, distracteurs) | 🔵 existant |
| 4 | 🆕 **Migrations : correction_visibility + reference** | 🟢 nouveau |
| 5 | 🆕 **StudentDashboardService complet** | 🟢 nouveau |
| 6 | 🆕 **Pages Student/Dashboard + History** | 🟢 nouveau |
| 7 | 🆕 **Page Student/Correction détaillée** | 🟢 nouveau |
| 8 | 🆕 **UI prof : paramètre visibility + bouton publier + tests** | 🟢 nouveau |

### 📅 Nouveau total Certio v2.0

| Stack | Avant | Après |
|---|:-:|:-:|
| Durée totale | 51 jours | **54 jours** |
| Timeline | Mai-Sept 2026 | **Mai-Octobre 2026** |

**+3 jours** pour une feature à **très forte valeur pédagogique**. Excellent ratio.

---

## 💎 Différenciateur marketing

Cette feature renforce Certio face à la concurrence :

| Plateforme | Historique étudiant | Corrections riches | Contrôle prof |
|---|:-:|:-:|:-:|
| **Certio v2.0** | ✅ Compte complet | ✅ + pièges + refs | ✅ Paramétrable |
| Moodle | ✅ Basique | ⚠️ Limité | ✅ |
| Wooclap | ❌ Limité | ⚠️ Basique | ⚠️ |
| Google Forms | ❌ | ❌ | ❌ |
| Kahoot | ❌ | ❌ | ❌ |

**Argumentaire de vente aux écoles** :
> "Certio ne se contente pas d'évaluer vos étudiants. Il les aide à progresser grâce à un système de corrections pédagogiques détaillées et un historique personnel qui valorise chaque examen comme une occasion d'apprendre."

---

## ✅ Definition of Done — Feature Dashboard Étudiant

Avant de considérer la feature livrée :

- [ ] Migration `correction_visibility` appliquée
- [ ] Migration `reference` questions appliquée
- [ ] Enum `CorrectionVisibility` créé
- [ ] StudentDashboardService avec 10+ méthodes testées
- [ ] Action `PublishCorrections` + tests
- [ ] Policy `PassagePolicy::viewCorrection` + tests
- [ ] Email `CorrectionsAvailableMail` + template
- [ ] 4 pages Vue créées : Dashboard, History, Passage/Show, Passage/Correction
- [ ] Composant QuestionCorrection.vue réutilisable
- [ ] UI prof : paramètre visibility dans Create/Edit exam
- [ ] UI prof : bouton "Publier corrections" dans suivi passages
- [ ] UI prof : champ why_wrong pour chaque option fausse
- [ ] UI prof : champ reference pour chaque question
- [ ] Notifications email fonctionnelles
- [ ] Tests Pest > 85%
- [ ] Accessible en clavier (keyboard navigation)
- [ ] Responsive mobile
- [ ] Documentation `/docs/student/utiliser-son-dashboard.md`

---

## 🎯 Critères de succès post-release

6 mois après release v2.0, on mesure :

| KPI | Cible |
|---|---|
| % étudiants qui se connectent à leur dashboard | ≥ 60% |
| Nombre moyen de consultations correction par passage | ≥ 1.5 |
| NPS étudiants (sur dashboard) | ≥ 50 |
| Profs qui publient manuellement les corrections | ≥ 70% |
| Feedback prof "aide à la pédagogie" | ≥ 80% |

---

## Conclusion

Cette feature **Dashboard Étudiant + Historique + Corrections détaillées** est l'une des plus **impactantes** de Certio v2.0.

**Pour 3 jours supplémentaires**, tu gagnes :
- 🎓 **Valeur pédagogique** énorme
- 💎 **Différenciateur** face aux concurrents
- 😊 **Satisfaction étudiants** (Certio devient "utile")
- 📈 **Argument de vente** clé pour écoles

**C'est une évidence : cette feature DOIT être en v2.0.**

Les 3 livrables suivants (note de cadrage, planning, prompt VS Code) seront mis à jour pour intégrer cette feature.

---

© 2026 Mohamed EL AFRIT — mohamed@elafrit.com  
Certio v2.0 Laravel — CC BY-NC-SA 4.0
