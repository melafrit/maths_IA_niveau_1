# 📚 API `/api/examens` — Documentation

Gestion des examens pour la plateforme IPSSI.

> **Auth** : différenciée selon route (prof/admin + ownership)
> **Format** : `{ ok: true, data: ... }` ou `{ ok: false, error: {...} }`

---

## 📋 Index des routes

| # | Méthode | Route | Auth |
|:-:|---|---|---|
| 1 | `GET` | `/api/examens` | Auth (prof = ses / admin = tous) |
| 2 | `GET` | `/api/examens/stats` | Admin |
| 3 | `GET` | `/api/examens/by-code/{code}` | Public limité |
| 4 | `GET` | `/api/examens/{id}` | Créateur/Admin |
| 5 | `POST` | `/api/examens` | Auth |
| 6 | `PUT` | `/api/examens/{id}` | Créateur/Admin |
| 7 | `DELETE` | `/api/examens/{id}` | Créateur/Admin (draft only) |
| 8 | `POST` | `/api/examens/{id}/publish` | Créateur/Admin |
| 9 | `POST` | `/api/examens/{id}/close` | Créateur/Admin |
| 10 | `POST` | `/api/examens/{id}/archive` | Créateur/Admin |

---

## 🔒 Règles d'authentification

| Rôle | Peut voir | Peut modifier |
|---|---|---|
| **Prof** | Ses propres examens | Ses propres examens |
| **Admin** | Tous les examens | Tous les examens |
| **Anonyme** | `by-code/{code}` si examen published et ouvert | Rien |

---

## 📖 LECTURE

### `GET /api/examens`

Liste des examens avec filtres optionnels.

**Query params** :
- `status` : `draft`, `published`, `closed`, `archived` (ou liste)
- `after` : date ISO (ouverture après)
- `before` : date ISO (ouverture avant)
- `created_by` : (admin uniquement) filtre par créateur
- `limit`, `offset` : pagination

**Exemple** :
```bash
curl -b cookies.txt "http://localhost:8765/api/examens?status=published,closed&limit=20"
```

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "examens": [
      {
        "id": "EXM-ABCD-1234",
        "titre": "Contrôle Maths IA",
        "status": "published",
        "created_by": "PROF-XXX",
        "questions": ["vec-faci-01", ...],
        "duree_sec": 3600,
        "date_ouverture": "2026-05-01T09:00:00+02:00",
        "date_cloture": "2026-05-01T11:00:00+02:00",
        "access_code": "ABC123"
      }
    ],
    "total": 5,
    "count": 5,
    "filters": {"status": ["published", "closed"]}
  }
}
```

### `GET /api/examens/stats`

Statistiques globales (admin uniquement).

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "total": 42,
    "by_status": { "draft": 10, "published": 5, "closed": 20, "archived": 7 },
    "by_owner": { "PROF-XXX": 15, "PROF-YYY": 27 },
    "total_questions_used": 840,
    "avg_questions_per_exam": 20
  }
}
```

### `GET /api/examens/by-code/{code}`

Accès étudiant via code (pas d'auth requise, mais restrictions strictes).

**Règles** :
- L'examen doit être en status `published`
- Il doit être dans la fenêtre d'ouverture (`date_ouverture ≤ now ≤ date_cloture`)

**Exemple** :
```bash
curl "http://localhost:8765/api/examens/by-code/ABC123"
```

**Réponse 200 (examen accessible)** :
```json
{
  "ok": true,
  "data": {
    "id": "EXM-ABCD-1234",
    "titre": "Contrôle Maths IA",
    "description": "...",
    "duree_sec": 3600,
    "nb_questions": 20,
    "date_ouverture": "...",
    "date_cloture": "...",
    "max_passages": 1,
    "access_code": "ABC123"
  }
}
```

**NB** : la liste complète des questions n'est PAS renvoyée ici (sécurité) — elle sera fournie à la création d'un passage.

**Erreurs** :
- `404 not_found` : code inconnu
- `403 not_available` : examen pas en status published
- `403 not_yet_open` : pas encore ouvert (contient `opens_in_sec`)
- `403 closed` : période terminée

### `GET /api/examens/{id}`

Détail d'un examen (créateur ou admin).

**Réponse 200** :
```json
{
  "ok": true,
  "data": {
    "examen": { /* examen complet */ }
  }
}
```

**Erreurs** :
- `404 not_found`
- `403 forbidden` (pas créateur ni admin)

---

## ✏️ ÉCRITURE

### `POST /api/examens`

Créer un nouvel examen (status : draft).

**Body** :
```json
{
  "titre": "Contrôle Maths IA",
  "description": "Contrôle continu J1-J2",
  "questions": ["vec-faci-01", "mat-moye-05", "der-diff-03"],
  "duree_sec": 3600,
  "date_ouverture": "2026-05-01T09:00:00+02:00",
  "date_cloture": "2026-05-01T11:00:00+02:00",
  "max_passages": 1,
  "shuffle_questions": true,
  "shuffle_options": true,
  "show_correction_after": true,
  "correction_delay_min": 0
}
```

**Réponse 201** :
```json
{
  "ok": true,
  "data": {
    "examen": { /* ... avec id et access_code générés ... */ }
  }
}
```

**Erreurs** :
- `400 validation_failed` : champs invalides
- `400` : questions inexistantes dans la banque

### `PUT /api/examens/{id}`

Modifier un examen. Champs modifiables selon status :
- **draft** : tout sauf id/created_by/access_code
- **published** : titre, description, date_cloture
- **closed** : status (pour archiver)
- **archived** : rien

**Body** :
```json
{
  "updates": {
    "titre": "Nouveau titre",
    "description": "Nouvelle description"
  }
}
```

### `DELETE /api/examens/{id}`

Supprimer un examen (**draft uniquement**).

**Réponse 200** :
```json
{
  "ok": true,
  "data": { "deleted": true, "id": "EXM-ABCD-1234" }
}
```

---

## 🔄 CYCLE DE VIE

### `POST /api/examens/{id}/publish`

Transition draft → published.

**Réponse 200** :
```json
{
  "ok": true,
  "data": {
    "examen": { ... },
    "action": "publish",
    "new_status": "published"
  }
}
```

**Erreurs** :
- `400 invalid_transition` : status actuel ≠ draft

### `POST /api/examens/{id}/close`

Transition published → closed.

### `POST /api/examens/{id}/archive`

Transition closed/draft → archived.

---

## ⚠️ Codes d'erreur

| Code | HTTP | Description |
|---|:-:|---|
| `unauthorized` | 401 | Pas de session |
| `forbidden` | 403 | Pas propriétaire / pas admin |
| `not_available` | 403 | Examen pas published |
| `not_yet_open` | 403 | Avant date_ouverture |
| `closed` | 403 | Après date_cloture |
| `bad_request` | 400 | Paramètres invalides |
| `validation_failed` | 400 | Structure invalide |
| `invalid_transition` | 400 | Transition de status invalide |
| `not_allowed` | 400 | Action non autorisée (ex: delete non-draft) |
| `not_found` | 404 | Examen/route introuvable |
| `method_not_allowed` | 405 | Mauvaise méthode HTTP |
| `server_error` | 500 | Erreur interne |

---

## 🧪 Tests

```bash
# Tests backend
php backend/test_examen_manager.php   # 26/26
php backend/test_api_examens.php      # 11/11
```

---

## 🎬 Scénario complet (workflow prof + étudiant)

### 1. Prof crée un examen
```bash
# Login prof
curl -c cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -d '{"email":"prof@ipssi.net","password":"..."}' \
  http://localhost:8765/api/auth/login

# Créer examen
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $CSRF" \
  -d '{
    "titre": "Contrôle Maths IA",
    "questions": ["vec-faci-01", "mat-faci-01", "..."],
    "duree_sec": 3600,
    "date_ouverture": "2026-05-01T09:00:00+02:00",
    "date_cloture": "2026-05-01T11:00:00+02:00"
  }' \
  http://localhost:8765/api/examens
# => { ok:true, data:{examen:{id:"EXM-ABCD-1234", access_code:"XYZ789", ...}} }
```

### 2. Prof publie l'examen
```bash
curl -b cookies.txt -X POST \
  -H "X-CSRF-Token: $CSRF" \
  http://localhost:8765/api/examens/EXM-ABCD-1234/publish
# => { ok:true, data:{examen:{status:"published"}, action:"publish"} }
```

### 3. Prof partage le code aux étudiants
```
Code d'accès : XYZ789
```

### 4. Étudiant accède via le code
```bash
# Pas de login nécessaire
curl http://localhost:8765/api/examens/by-code/XYZ789
# => { ok:true, data:{titre, duree_sec, nb_questions, ...} }
```

### 5. Prof clôture après la date limite
```bash
curl -b cookies.txt -X POST \
  -H "X-CSRF-Token: $CSRF" \
  http://localhost:8765/api/examens/EXM-ABCD-1234/close
# => { ok:true, data:{examen:{status:"closed"}} }
```

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
