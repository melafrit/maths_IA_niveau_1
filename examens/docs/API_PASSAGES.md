# 📚 API `/api/passages` — Documentation

Gestion des passages d'examen pour étudiants et profs/admins.

> **Auth** : Étudiants = validité du token suffit / Prof-Admin = session + ownership
> **Format** : `{ ok: true, data: ... }` ou `{ ok: false, error: {...} }`

---

## 📋 Index des routes (9)

### 🟢 Routes publiques (étudiants)

| # | Méthode | Route | Description |
|:-:|---|---|---|
| 1 | `POST` | `/api/passages/start` | Démarrer (retourne token) |
| 2 | `GET` | `/api/passages/{token}/progress` | Reprendre / état actuel |
| 3 | `POST` | `/api/passages/{token}/answer` | Sauvegarder une réponse |
| 4 | `POST` | `/api/passages/{token}/focus-event` | Log anti-triche |
| 5 | `POST` | `/api/passages/{token}/submit` | Finaliser |

### 🔒 Routes authentifiées (prof/admin)

| # | Méthode | Route | Auth |
|:-:|---|---|---|
| 6 | `GET` | `/api/passages` | Prof (ses) / Admin (tous) |
| 7 | `GET` | `/api/passages/stats` | Prof (avec examen_id) / Admin |
| 8 | `GET` | `/api/passages/{id}` | Owner examen / Admin |
| 9 | `POST` | `/api/passages/{id}/invalidate` | Owner examen / Admin |

---

## 🟢 ROUTES ÉTUDIANTS

### 1. `POST /api/passages/start`

Démarrer un passage à partir d'un `examen_id` (obtenu depuis `/api/examens/by-code/{code}`).

**Body** :
```json
{
  "examen_id": "EXM-ABCD-1234",
  "student_info": {
    "nom": "Dupont",
    "prenom": "Jean",
    "email": "jean.dupont@etu.ipssi.net"
  }
}
```

**Réponse 201** :
```json
{
  "ok": true,
  "data": {
    "token": "a1b2c3d4-e5f6-47a8-9012-3456789abcde",
    "passage_id": "PSG-XYZ1-2345",
    "examen": {
      "id": "EXM-ABCD-1234",
      "titre": "Contrôle Maths IA",
      "duree_sec": 3600,
      "start_time": "2026-05-01T09:00:00+02:00"
    },
    "questions": [
      {
        "id": "vec-faci-01",
        "enonce": "Soit $\\vec{u} = (1, 2)$...",
        "options": ["option A mélangée", "B mélangée", "C mélangée", "D mélangée"],
        "type": "conceptuel",
        "difficulte": "facile"
      }
    ],
    "answers": {}
  }
}
```

**Sécurité** :
- Options sont **remappées** côté serveur (shuffle transparent pour l'étudiant)
- Champs `correct`, `hint`, `explanation`, `traps` **NON renvoyés** pendant le passage
- Token UUID v4 à conserver côté client pour toutes les opérations suivantes

**Erreurs** :
- `404 not_found` : examen introuvable
- `403 not_available` : status ≠ published
- `403 not_yet_open` : avant `date_ouverture`
- `403 closed` : après `date_cloture`
- `403 max_passages_reached` : déjà N passages pour cet email
- `400 validation_failed` : email invalide, nom/prénom vides

**Exemple curl** :
```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{
    "examen_id": "EXM-ABCD-1234",
    "student_info": {
      "nom": "Dupont",
      "prenom": "Jean",
      "email": "jean@test.fr"
    }
  }' \
  http://localhost:8765/api/passages/start
```

### 2. `GET /api/passages/{token}/progress`

Reprendre un passage ou vérifier son état.

**Réponse 200** :
```json
{
  "ok": true,
  "data": {
    "passage_id": "PSG-XYZ1-2345",
    "token": "...",
    "status": "in_progress",
    "examen": { "id": "...", "titre": "...", "duree_sec": 3600 },
    "student_info": { "nom": "...", "prenom": "...", "email": "..." },
    "start_time": "2026-05-01T09:00:00+02:00",
    "end_time": null,
    "time_remaining_sec": 2700,
    "questions": [ /* toutes, shuffled */ ],
    "answers": {
      "vec-faci-01": { "answer_index": 2, "timestamp": "..." }
    },
    "nb_answered": 1,
    "nb_total": 20
  }
}
```

**Important** :
- Si le passage était `in_progress` mais temps écoulé → **auto-expire** et retourne `status: "expired"`
- `time_remaining_sec` = min(start + durée, date_cloture) − now

### 3. `POST /api/passages/{token}/answer`

Sauvegarder une réponse (appelé à chaque changement de sélection).

**Body** :
```json
{
  "question_id": "vec-faci-01",
  "answer_index": 2
}
```

**Réponse 200** :
```json
{
  "ok": true,
  "data": {
    "saved": true,
    "question_id": "vec-faci-01",
    "nb_answered": 1,
    "nb_total": 20
  }
}
```

**Erreurs** :
- `404 not_found` : token invalide
- `400 validation_failed` : question hors examen, answer_index hors 0-3
- `400 expired` : temps écoulé
- `400 not_modifiable` : status ≠ in_progress

### 4. `POST /api/passages/{token}/focus-event`

Logger un événement anti-triche (blur, copy, etc.).

**Body** :
```json
{
  "type": "blur",
  "duration_ms": 5000,
  "details": { "optional": "context" }
}
```

**Types valides** :
- `blur` : perte de focus de la fenêtre
- `focus` : retour du focus
- `visibility_change` : changement d'onglet
- `copy` : 🚨 tentative de copie
- `paste` : 🚨 collage
- `rightclick` : clic droit
- `devtools` : 🚨 ouverture console dev

**Réponse** :
```json
{
  "ok": true,
  "data": { "logged": true, "type": "blur", "total_events": 3 }
}
```

Les événements `copy`, `paste`, `devtools` sont loggués en warning côté serveur.

### 5. `POST /api/passages/{token}/submit`

Finaliser le passage (calcule score + génère signature SHA-256).

**Body** : vide (pas nécessaire, toutes les réponses sont déjà sauvegardées)

**Réponse 200** :
```json
{
  "ok": true,
  "data": {
    "submitted": true,
    "passage_id": "PSG-XYZ1-2345",
    "status": "submitted",
    "score": {
      "brut": 15,
      "max": 20,
      "pct": 75
    },
    "duration_sec": 2730,
    "end_time": "2026-05-01T09:45:30+02:00",
    "signature": "a1b2c3d4e5f67890...",
    "correction_available": true,
    "correction_delay_min": 0
  }
}
```

- `signature` : preview 16 premiers chars (full en `/api/passages/{id}` pour prof)
- `correction_available` : selon `show_correction_after` + `correction_delay_min`

**Erreurs** :
- `400 already_submitted` : déjà soumis

---

## 🔒 ROUTES PROF/ADMIN

### 6. `GET /api/passages`

Liste des passages avec filtres.

**Query** :
- `examen_id` : filtrer par examen
- `email` : filtrer par étudiant
- `status` : `in_progress`, `submitted`, `expired`, `invalidated` (ou liste séparée virgule)
- `since`, `until` : dates ISO
- `limit`, `offset` : pagination

**Comportement** :
- **Prof** : voit uniquement les passages de SES examens
- **Admin** : voit tous + peut filtrer par `created_by`

**Exemple** :
```bash
curl -b cookies.txt "http://localhost:8765/api/passages?examen_id=EXM-ABCD-1234&status=submitted"
```

### 7. `GET /api/passages/stats`

Statistiques.

**Query** : `examen_id` (requis pour prof, optionnel pour admin)

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "total": 42,
    "by_status": {
      "in_progress": 3,
      "submitted": 35,
      "expired": 2,
      "invalidated": 2
    },
    "avg_score_pct": 72.5,
    "min_score_pct": 20,
    "max_score_pct": 100,
    "avg_duration_sec": 2100,
    "anomalies_count": 5,
    "anomalies_pct": 11.9
  }
}
```

### 8. `GET /api/passages/{id}`

Détail complet d'un passage (ID format `PSG-XXXX-XXXX`).

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "passage": {
      "id": "PSG-XYZ1-2345",
      /* tous les champs */
      "signature_sha256": "full-64-chars..."
    },
    "signature_valid": true,
    "is_expired": false
  }
}
```

### 9. `POST /api/passages/{id}/invalidate`

Invalider un passage (fraude détectée).

**Body** :
```json
{
  "reason": "Détection de devtools + changements d'onglet répétés"
}
```

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "invalidated": true,
    "passage": { "status": "invalidated", "invalidation_reason": "..." }
  }
}
```

---

## ⚠️ Codes d'erreur

| Code | HTTP | Description |
|---|:-:|---|
| `not_found` | 404 | Token ou passage introuvable |
| `not_available` | 403 | Examen pas published |
| `not_yet_open` | 403 | Avant `date_ouverture` |
| `closed` | 403 | Après `date_cloture` |
| `max_passages_reached` | 403 | Nombre max de passages atteint pour cet email |
| `validation_failed` | 400 | Format invalide |
| `expired` | 400 | Temps écoulé |
| `not_modifiable` | 400 | Status ≠ in_progress |
| `already_submitted` | 400 | Déjà soumis |
| `save_failed` | 500 | Erreur technique sauvegarde |

---

## 🎬 Workflow étudiant complet

```bash
# 1. L'étudiant saisit le code d'accès et obtient l'examen
curl http://localhost:8765/api/examens/by-code/XYZ789
# => { id: "EXM-ABCD-1234", titre: "...", ... }

# 2. Étudiant saisit nom/prénom/email et démarre
curl -X POST -H "Content-Type: application/json" \
  -d '{
    "examen_id": "EXM-ABCD-1234",
    "student_info": {"nom":"Dupont","prenom":"Jean","email":"jean@test.fr"}
  }' \
  http://localhost:8765/api/passages/start
# => { token: "abc-def-...", questions: [...] }
TOKEN="abc-def-..."

# 3. Étudiant répond aux questions (AJAX à chaque click)
curl -X POST -H "Content-Type: application/json" \
  -d '{"question_id":"vec-faci-01","answer_index":2}' \
  http://localhost:8765/api/passages/$TOKEN/answer

# 4. Frontend log les focus events en continu
curl -X POST -H "Content-Type: application/json" \
  -d '{"type":"blur","duration_ms":3500}' \
  http://localhost:8765/api/passages/$TOKEN/focus-event

# 5. Reprendre si navigateur fermé accidentellement
curl http://localhost:8765/api/passages/$TOKEN/progress
# => { answers: {...}, time_remaining_sec: 1850, status: "in_progress" }

# 6. Étudiant clique "Soumettre"
curl -X POST http://localhost:8765/api/passages/$TOKEN/submit
# => { score: {brut:15, max:20, pct:75}, signature: "...", correction_available: true }
```

## 🎬 Workflow prof

```bash
# Prof voit tous les passages de son examen
curl -b cookies.txt "http://localhost:8765/api/passages?examen_id=EXM-ABCD-1234"

# Stats de cet examen
curl -b cookies.txt "http://localhost:8765/api/passages/stats?examen_id=EXM-ABCD-1234"
# => { total: 42, avg_score_pct: 72.5, anomalies_pct: 11.9 }

# Détail d'un passage + vérification signature
curl -b cookies.txt http://localhost:8765/api/passages/PSG-XYZ1-2345
# => { passage: {...}, signature_valid: true }

# Invalider un passage (fraude)
curl -b cookies.txt -X POST -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $CSRF" \
  -d '{"reason":"Détection de devtools répétée"}' \
  http://localhost:8765/api/passages/PSG-XYZ1-2345/invalidate
```

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
