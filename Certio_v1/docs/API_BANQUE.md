# 📚 API `/api/banque` — Documentation

Gestion de la banque de questions pour la plateforme d'examens IPSSI.

> **Auth requise** : Admin uniquement (bcrypt + session).
> **Format** : `{ ok: true, data: ... }` ou `{ ok: false, error: { code, message, details } }`

---

## 📋 Index des routes

| Méthode | Route | Description |
|---|---|---|
| `GET` | `/api/banque/stats` | Stats globales |
| `GET` | `/api/banque/modules` | Liste des modules |
| `GET` | `/api/banque/{mod}/chapitres` | Chapitres d'un module |
| `GET` | `/api/banque/{mod}/{chap}/themes` | Thèmes d'un chapitre |
| `GET` | `/api/banque/{mod}/{chap}/{theme}` | Questions d'un thème |
| `GET` | `/api/banque/{mod}/{chap}/{theme}/validate` | Validation thème |
| `GET` | `/api/banque/questions` | Liste questions + filtres |
| `GET` | `/api/banque/questions/{id}` | Détail question |
| `POST` | `/api/banque/questions` | Créer question |
| `PUT` | `/api/banque/questions/{id}` | Modifier question |
| `DELETE` | `/api/banque/questions/{id}` | Supprimer question |
| `POST` | `/api/banque/search` | Recherche full-text |
| `POST` | `/api/banque/draw` | Tirage aléatoire |

---

## 🔒 Authentification

Toutes les routes nécessitent un utilisateur connecté avec le rôle `admin`.

**Login** :
```bash
curl -c cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@ipssi.net","password":"..."}' \
  http://localhost:8765/api/auth/login
```

Ensuite, inclure les cookies dans les requêtes suivantes : `-b cookies.txt`.

**Erreurs d'auth** :
- `401 unauthorized` : pas de session
- `403 forbidden` : session mais pas admin

---

## 📖 LECTURE (6 routes)

### `GET /api/banque/stats`

Stats globales de la banque.

**Réponse 200** :
```json
{
  "ok": true,
  "data": {
    "modules": [
      {
        "module": "maths-ia",
        "chapitres": [
          {
            "chapitre": "j1-representation",
            "themes": [
              { "theme": "vecteurs", "count": 20 },
              { "theme": "matrices", "count": 20 }
            ],
            "total": 80
          }
        ],
        "total": 320
      }
    ],
    "total_questions": 320,
    "by_level": {
      "facile": 80, "moyen": 80, "difficile": 80, "expert": 80
    },
    "by_type": {
      "conceptuel": 245, "calcul": 40, "code": 15, "formule": 20
    }
  }
}
```

### `GET /api/banque/modules`

Liste plate des modules disponibles.

**Réponse** :
```json
{
  "ok": true,
  "data": { "modules": ["maths-ia"], "total": 1 }
}
```

### `GET /api/banque/{module}/chapitres`

**Exemple** : `GET /api/banque/maths-ia/chapitres`

```json
{
  "ok": true,
  "data": {
    "module": "maths-ia",
    "chapitres": ["j1-representation", "j2-optimisation", "j3-classification", "j4-reseaux"],
    "total": 4
  }
}
```

### `GET /api/banque/{module}/{chapitre}/themes`

**Exemple** : `GET /api/banque/maths-ia/j1-representation/themes`

```json
{
  "ok": true,
  "data": {
    "module": "maths-ia",
    "chapitre": "j1-representation",
    "themes": ["vecteurs", "matrices", "produit-scalaire", "regression-lineaire"],
    "total": 4
  }
}
```

### `GET /api/banque/{module}/{chapitre}/{theme}`

Récupère un thème complet (20 questions + méta).

**Exemple** : `GET /api/banque/maths-ia/j1-representation/vecteurs`

```json
{
  "ok": true,
  "data": {
    "module": "maths-ia",
    "chapitre": "j1-representation",
    "theme": "vecteurs",
    "meta": {
      "total_questions": 20,
      "difficulty_distribution": { "facile": 5, "moyen": 5, "difficile": 5, "expert": 5 }
    },
    "questions": [ /* ... 20 questions ... */ ],
    "count": 20
  }
}
```

### `GET /api/banque/{module}/{chapitre}/{theme}/validate`

Rapport de validation d'un thème.

**Exemple** : `GET /api/banque/maths-ia/j1-representation/vecteurs/validate`

```json
{
  "ok": true,
  "data": {
    "report": {
      "valid": true,
      "errors": [],
      "warnings": [],
      "stats": {
        "total": 20,
        "by_level": { "facile": 5, "moyen": 5, "difficile": 5, "expert": 5 },
        "by_type": { "conceptuel": 14, "calcul": 4, "code": 1, "formule": 1 },
        "unique_ids": 20
      }
    }
  }
}
```

---

## 📝 CRUD QUESTIONS (5 routes)

### `GET /api/banque/questions`

Liste avec filtres query string.

**Query params (tous optionnels)** :
- `module` : filtrer par module (ex: `maths-ia`)
- `chapitre` : filtrer par chapitre
- `theme` : filtrer par thème
- `difficulte` : `facile`, `moyen`, `difficile`, `expert` ou liste (`facile,moyen`)
- `type` : `conceptuel`, `calcul`, `code`, `formule` ou liste
- `tags` : liste séparée par virgule (AU MOINS UN tag doit matcher)
- `limit` : max 1000 (défaut : tout)
- `offset` : pagination (défaut 0)

**Exemples** :
```bash
# Toutes les questions faciles
GET /api/banque/questions?difficulte=facile

# 10 questions de code sur le jour 4
GET /api/banque/questions?chapitre=j4-reseaux&type=code&limit=10

# Questions avec tag "fil-rouge" ou "bayes"
GET /api/banque/questions?tags=fil-rouge,bayes
```

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "questions": [ /* ... */ ],
    "total": 80,
    "offset": 0,
    "limit": 10,
    "count": 10,
    "filters": { "difficulte": "facile" }
  }
}
```

### `GET /api/banque/questions/{id}`

**Exemple** : `GET /api/banque/questions/vec-faci-01`

```json
{
  "ok": true,
  "data": {
    "question": {
      "id": "vec-faci-01",
      "enonce": "...",
      "options": ["...", "...", "...", "..."],
      "correct": 0,
      "difficulte": "facile",
      "type": "conceptuel",
      "tags": [...],
      "hint": "...",
      "explanation": "...",
      "traps": "...",
      "references": "...",
      "_module": "maths-ia",
      "_chapitre": "j1-representation",
      "_theme": "vecteurs"
    }
  }
}
```

### `POST /api/banque/questions`

Créer une question dans un thème existant.

**Body** :
```json
{
  "module": "maths-ia",
  "chapitre": "j1-representation",
  "theme": "vecteurs",
  "question": {
    "id": "vec-faci-99",
    "enonce": "Énoncé de la question",
    "options": ["A", "B", "C", "D"],
    "correct": 0,
    "difficulte": "facile",
    "type": "conceptuel",
    "tags": ["test"],
    "hint": "Indice",
    "explanation": "Explication complète",
    "traps": "Pièges à éviter",
    "references": "Référence au cours"
  }
}
```

**Réponse 201** (créée) :
```json
{
  "ok": true,
  "data": {
    "question": { /* la question créée */ },
    "location": { "module": "...", "chapitre": "...", "theme": "..." }
  }
}
```

**Erreurs** :
- `400 validation_failed` : structure invalide
- `409 conflict` : ID déjà existant

### `PUT /api/banque/questions/{id}`

Modifier une question existante (sauf l'ID qui est immuable).

**Body** :
```json
{
  "updates": {
    "hint": "Nouveau hint",
    "explanation": "Nouvelle explication"
  }
}
```

**Réponse 200** :
```json
{ "ok": true, "data": { "question": { /* mise à jour */ } } }
```

**Erreurs** :
- `404 not_found` : question introuvable
- `400` : essai de modifier l'ID

### `DELETE /api/banque/questions/{id}`

**Réponse 200** :
```json
{ "ok": true, "data": { "deleted": true, "id": "vec-faci-99" } }
```

---

## 🔍 RECHERCHE (1 route)

### `POST /api/banque/search`

Recherche full-text pondérée dans les questions.

**Body** :
```json
{
  "query": "gradient",
  "filters": { "difficulte": "facile" },
  "fields": ["enonce", "tags", "id"],
  "limit": 20
}
```

**Pondération** :
- `id` : ×10
- `tags` : ×5
- `enonce` : ×3
- Autres : ×1

**Réponse** :
```json
{
  "ok": true,
  "data": {
    "results": [
      {
        "id": "der-moye-01",
        "_score": 18,
        "enonce": "...",
        ...
      }
    ],
    "total": 25,
    "count": 20,
    "query": "gradient"
  }
}
```

---

## 🎲 TIRAGE ALÉATOIRE (1 route)

### `POST /api/banque/draw`

Tirage aléatoire personnalisable pour générer un examen.

**Stratégie `custom` (défaut)** : quotas explicites par niveau.

**Body** :
```json
{
  "strategy": "custom",
  "scope": {
    "module": "maths-ia",
    "chapitre": "j1-representation"
  },
  "quotas": {
    "facile": 3,
    "moyen": 3,
    "difficile": 2,
    "expert": 2
  },
  "seed": 42
}
```

**Stratégie `equitable`** : répartition automatique.

**Body** :
```json
{
  "strategy": "equitable",
  "scope": { "module": "maths-ia" },
  "n": 20,
  "seed": 42
}
```

**Réponse 200** :
```json
{
  "ok": true,
  "data": {
    "questions": [ /* 10 questions melangees */ ],
    "count": 10,
    "strategy": "custom",
    "scope": { ... },
    "quotas": { ... },
    "seed": 42
  }
}
```

**Erreurs** :
- `400` : quotas impossibles (pas assez de questions)
- `400` : scope vide ou niveau invalide

---

## ⚠️ Format d'erreur standardisé

Toutes les erreurs suivent le format :

```json
{
  "ok": false,
  "error": {
    "code": "validation_failed",
    "message": "Question invalide : [...]",
    "details": {}
  }
}
```

**Codes d'erreur utilisés** :
| Code | HTTP | Description |
|---|:-:|---|
| `unauthorized` | 401 | Pas de session |
| `forbidden` | 403 | Pas admin |
| `bad_request` | 400 | Paramètres invalides |
| `validation_failed` | 400 | Structure invalide |
| `not_found` | 404 | Ressource introuvable |
| `method_not_allowed` | 405 | Mauvaise méthode HTTP |
| `conflict` | 409 | ID dupliqué |
| `server_error` | 500 | Erreur interne |

---

## 🧪 Tests

### Tests unitaires BanqueManager
```bash
php backend/test_banque_manager.php
# → 28/28 tests passants
```

### Tests intégration API
```bash
php backend/test_api_banque.php
# → 10/10 tests passants
```

### Tests HTTP manuels (via curl)
Voir la section "Exemples" ci-dessus pour chaque route.

---

## 🏗️ Architecture

```
/api/banque (router dans banque.php)
  ├─ Auth::requireAdmin()           ← gatekeeper
  ├─ Route parsing (regex)
  ├─ Body JSON parsing (Response::getJsonBody)
  ├─ CSRF check (pour POST/PUT/DELETE)
  └─ BanqueManager (lib)
       └─ FileStorage (lib)
            └─ Atomic write (flock + rename)
            └─ JSON files in data/banque/
```

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
