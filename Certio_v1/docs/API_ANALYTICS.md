# 📊 API `/api/analytics` — Documentation

API REST pour les analytics de la plateforme IPSSI.

> **Auth** : Session requise pour toutes les routes
> **Format** : `{ ok: true, data: ... }` ou `{ ok: false, error: {...} }`
> **Méthode** : `GET` uniquement (analytics en lecture)

---

## 📋 Index des 8 routes

| # | Route | Auth | Description |
|:-:|---|---|---|
| 1 | `GET /api/analytics/examen/{id}/overview` | Owner/Admin | KPIs globaux d'un examen |
| 2 | `GET /api/analytics/examen/{id}/scores` | Owner/Admin | Distribution des scores (10 buckets) |
| 3 | `GET /api/analytics/examen/{id}/questions` | Owner/Admin | Stats par question + distracteurs |
| 4 | `GET /api/analytics/examen/{id}/timeline` | Owner/Admin | Passages dans le temps |
| 5 | `GET /api/analytics/examen/{id}/focus-heatmap` | Owner/Admin | Anti-triche agrégation |
| 6 | `GET /api/analytics/examen/{id}/passages` | Owner/Admin | Historique enrichi |
| 7 | `GET /api/analytics/prof/overview` | Auth | Dashboard prof (ses examens) |
| 8 | `GET /api/analytics/student/{email}` | Admin/Prof avec passage commun | Historique multi-examens |

---

## 1. `GET /api/analytics/examen/{id}/overview`

KPIs globaux d'un examen.

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "examen": {
      "id": "EXM-ABCD-1234",
      "titre": "Contrôle Maths",
      "status": "published",
      "nb_questions": 20,
      "duree_sec": 3600
    },
    "total_passages": 42,
    "unique_students": 38,
    "avg_score_pct": 72.5,
    "median_score_pct": 75.0,
    "min_score_pct": 15.0,
    "max_score_pct": 100.0,
    "std_dev": 18.3,
    "avg_duration_sec": 2730,
    "avg_not_answered_pct": 5.2,
    "anomaly_passages": 3,
    "anomaly_rate_pct": 7.14
  }
}
```

**Champs notables** :
- `unique_students` : nb d'emails distincts
- `median_score_pct` : médiane (calcul manuel)
- `std_dev` : écart-type formule Bessel (n-1)
- `anomaly_passages` : nb passages avec copy/paste/devtools

**Exemple** :
```bash
curl -b cookies.txt http://localhost:8765/api/analytics/examen/EXM-ABCD-1234/overview
```

---

## 2. `GET /api/analytics/examen/{id}/scores`

Distribution des scores en 10 tranches + mentions.

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "total": 42,
    "histogram": [
      { "range": "0-9", "count": 1 },
      { "range": "10-19", "count": 0 },
      { "range": "20-29", "count": 2 },
      { "range": "30-39", "count": 3 },
      { "range": "40-49", "count": 5 },
      { "range": "50-59", "count": 8 },
      { "range": "60-69", "count": 9 },
      { "range": "70-79", "count": 7 },
      { "range": "80-89", "count": 5 },
      { "range": "90-100", "count": 2 }
    ],
    "mentions": {
      "excellent": 2,
      "tres_bien": 5,
      "bien": 7,
      "assez_bien": 9,
      "passable": 8,
      "insuffisant": 8,
      "tres_insuf": 3
    },
    "pass_rate_pct": 73.81
  }
}
```

**Mentions** :
- `excellent` : ≥90%
- `tres_bien` : 80-89%
- `bien` : 70-79%
- `assez_bien` : 60-69%
- `passable` : 50-59%
- `insuffisant` : 30-49%
- `tres_insuf` : <30%

`pass_rate_pct` = % de passages avec score ≥ 50%.

---

## 3. `GET /api/analytics/examen/{id}/questions`

Stats par question avec analyse des distracteurs.

**Query** : `with_details=true|false` (défaut `true`)

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "examen_id": "EXM-...",
    "nb_passages": 42,
    "nb_questions": 20,
    "questions": [
      {
        "question_id": "vec-faci-01",
        "total": 42,
        "correct": 12,
        "not_answered": 3,
        "success_rate_pct": 28.57,
        "not_answered_rate_pct": 7.14,
        "correct_index": 2,
        "option_analysis": [
          {
            "index": 0, "letter": "A",
            "is_correct": false,
            "count": 15, "rate_pct": 35.71,
            "text": "Option A enoncé"
          },
          {
            "index": 1, "letter": "B",
            "is_correct": false,
            "count": 8, "rate_pct": 19.05,
            "text": "Option B"
          },
          {
            "index": 2, "letter": "C",
            "is_correct": true,
            "count": 12, "rate_pct": 28.57,
            "text": "Bonne réponse"
          },
          {
            "index": 3, "letter": "D",
            "is_correct": false,
            "count": 4, "rate_pct": 9.52,
            "text": "Option D"
          }
        ],
        "difficulte": "moyen",
        "type": "calcul",
        "enonce": "Soit u = (1, 2)...",
        "options": [...]
      }
    ]
  }
}
```

**Notes** :
- Les questions sont **triées par `success_rate_pct` croissant** (les plus difficiles en premier)
- `option_analysis` est dans l'ordre **original** (pas shuffled)
- `is_correct=true` identifie LA bonne réponse
- Le distracteur le plus efficace = celui avec le `count` le plus élevé après la bonne réponse

---

## 4. `GET /api/analytics/examen/{id}/timeline`

Évolution des passages dans le temps (par heure).

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "examen_id": "EXM-...",
    "timeline": [
      { "hour": "2026-05-01 09:00", "count": 12, "avg_score": 68.5 },
      { "hour": "2026-05-01 10:00", "count": 15, "avg_score": 72.3 },
      { "hour": "2026-05-01 11:00", "count": 8, "avg_score": 80.0 },
      { "hour": "2026-05-01 14:00", "count": 7, "avg_score": 65.2 }
    ]
  }
}
```

Trié chronologiquement.

---

## 5. `GET /api/analytics/examen/{id}/focus-heatmap`

Agrégation des événements anti-triche.

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "examen_id": "EXM-...",
    "total_events": 156,
    "by_type": {
      "blur": 45,
      "focus": 45,
      "visibility_change": 38,
      "copy": 5,
      "paste": 2,
      "rightclick": 18,
      "devtools": 3
    },
    "passages_with_events": [
      {
        "passage_id": "PSG-...",
        "student_name": "Jean Dupont",
        "email": "jean@test.fr",
        "score_pct": 95.0,
        "total_events": 12,
        "events_by_type": {
          "blur": 4, "focus": 4, "visibility_change": 2,
          "copy": 1, "paste": 1, "rightclick": 0, "devtools": 0
        }
      }
    ],
    "anomaly_threshold": {
      "copy": 10
    }
  }
}
```

Trié par `total_events` décroissant.

---

## 6. `GET /api/analytics/examen/{id}/passages`

Historique enrichi des passages avec tri + filtres + recherche.

**Query params** (tous optionnels) :
- `status` : `submitted`, `expired`, `invalidated` (séparés par virgule)
- `search` : texte (recherche dans nom/prénom/email)
- `email` : email exact
- `since`, `until` : dates ISO
- `min_score_pct`, `max_score_pct` : plage de score
- `with_anomalies` : `true|false` (filtrer passages avec copy/paste/devtools)
- `sort` : `date | score | name | duration` (défaut `date`)
- `order` : `asc | desc` (défaut `desc`)
- `limit` : 1-500 (défaut illimité)
- `offset` : ≥ 0 (défaut 0)

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "passages": [
      {
        "id": "PSG-...",
        "examen_id": "EXM-...",
        "student_info": { "nom": "...", "prenom": "...", "email": "..." },
        "status": "submitted",
        "start_time": "2026-05-01T09:00:00+02:00",
        "end_time": "2026-05-01T09:45:30+02:00",
        "duration_sec": 2730,
        "score_brut": 15,
        "score_max": 20,
        "score_pct": 75.0,
        "anomalies_count": 0,
        "focus_events_count": 2,
        "nb_answered": 18
      }
    ],
    "total": 42,
    "count": 20,
    "offset": 0,
    "limit": 20,
    "applied": {
      "sort": "score",
      "order": "desc",
      "search": null,
      "with_anomalies": false,
      "min_score_pct": null,
      "max_score_pct": null
    }
  }
}
```

**Exemple** :
```bash
# Top 10 meilleurs scores
curl -b cookies.txt "http://localhost:8765/api/analytics/examen/EXM-ABCD-1234/passages?sort=score&order=desc&limit=10"

# Recherche "dupont" avec anomalies
curl -b cookies.txt "http://localhost:8765/api/analytics/examen/EXM-ABCD-1234/passages?search=dupont&with_anomalies=true"

# Plage de score 60-80%
curl -b cookies.txt "http://localhost:8765/api/analytics/examen/EXM-ABCD-1234/passages?min_score_pct=60&max_score_pct=80"
```

---

## 7. `GET /api/analytics/prof/overview`

Dashboard prof (vue d'ensemble de tous ses examens).

**Query** (admin uniquement) : `prof_id` pour consulter un autre prof

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "prof_id": "PROF-...",
    "total_examens": 8,
    "by_status": {
      "draft": 2,
      "published": 4,
      "closed": 1,
      "archived": 1
    },
    "total_passages": 156,
    "unique_students": 42,
    "global_avg_score_pct": 71.2,
    "recent_examens": [
      {
        "id": "EXM-...",
        "titre": "Contrôle Maths IA",
        "status": "published",
        "created_at": "2026-04-15T10:00:00+02:00",
        "nb_passages": 38,
        "avg_score_pct": 73.5
      }
    ],
    "all_examens": [...]
  }
}
```

`recent_examens` : 10 derniers, triés par date de création desc.

---

## 8. `GET /api/analytics/student/{email}`

Historique d'un étudiant (multi-examens).

**Auth** :
- **Admin** : voit tous les passages
- **Prof** : voit uniquement les passages sur SES examens
  - Si l'étudiant n'a aucun passage commun → 403

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "email": "alice@test.fr",
    "student_info": {
      "nom": "Smith",
      "prenom": "Alice",
      "email": "alice@test.fr"
    },
    "nb_passages": 5,
    "avg_score_pct": 78.0,
    "best_score_pct": 95.0,
    "worst_score_pct": 60.0,
    "total_time_sec": 12345,
    "passages": [
      {
        "passage_id": "PSG-...",
        "examen_id": "EXM-...",
        "examen_titre": "Contrôle Maths IA",
        "status": "submitted",
        "start_time": "2026-05-01T09:00:00+02:00",
        "end_time": "2026-05-01T09:45:30+02:00",
        "duration_sec": 2730,
        "score_brut": 18,
        "score_max": 20,
        "score_pct": 90.0,
        "anomalies_count": 0
      }
    ],
    "filtered_to_prof": "PROF-..."
  }
}
```

`filtered_to_prof` est présent uniquement quand un prof consulte (pas pour admin).

---

## ⚠️ Codes d'erreur

| Code | HTTP | Description |
|---|:-:|---|
| `not_found` | 404 | Examen ou route introuvable |
| `forbidden` | 403 | Pas d'accès aux données demandées |
| `unauthorized` | 401 | Pas connecté |
| `bad_request` | 400 | Email invalide, paramètres malformés |
| `method_not_allowed` | 405 | Autre méthode que GET |
| `server_error` | 500 | Erreur calcul agrégation |

---

## 🎬 Workflow typique frontend

```bash
# 1. Charger dashboard prof (page d'accueil analytics)
curl -b cookies.txt http://localhost:8765/api/analytics/prof/overview
# → liste examens avec stats résumées

# 2. Cliquer sur un examen → 4 appels en parallèle
curl -b cookies.txt http://localhost:8765/api/analytics/examen/EXM-XXX/overview
curl -b cookies.txt http://localhost:8765/api/analytics/examen/EXM-XXX/scores
curl -b cookies.txt http://localhost:8765/api/analytics/examen/EXM-XXX/questions
curl -b cookies.txt http://localhost:8765/api/analytics/examen/EXM-XXX/passages?limit=20

# 3. Cliquer sur "Anti-triche" tab
curl -b cookies.txt http://localhost:8765/api/analytics/examen/EXM-XXX/focus-heatmap

# 4. Cliquer sur "Détail étudiant" pour alice
curl -b cookies.txt http://localhost:8765/api/analytics/student/alice@test.fr

# 5. Filtrer par anomalies + score
curl -b cookies.txt "http://localhost:8765/api/analytics/examen/EXM-XXX/passages?with_anomalies=true&min_score_pct=50"
```

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
