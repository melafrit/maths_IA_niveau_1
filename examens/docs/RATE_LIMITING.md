# 🚦 Rate Limiting — IPSSI Examens

Système de limitation de débit par rôle pour protéger l'API.

---

## 🎯 Limites par rôle

| Rôle | Limite | Application |
|---|---|---|
| **admin** | Illimité | Aucune vérification |
| **enseignant** | 500/min | Usage intensif autorisé |
| **étudiant** | 60/min | Usage normal pendant examen |
| **anonyme** | 30/min | Protection contre scraping |

Fenêtre glissante : **60 secondes**

---

## 🔧 Fonctionnement

### Middleware dans `index.php`

Pour chaque requête `/api/*` (sauf `/health`) :

1. Récupération du rôle depuis la session (ou `anonyme` si déconnecté)
2. Identifier = `user:<id>` si connecté, `ip:<IP>` sinon
3. Check du bucket sliding window
4. Si OK → requête passe + headers ajoutés
5. Si bloqué → 429 + `Retry-After`

### Headers HTTP retournés

**En mode normal (OK)** :
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 47
X-RateLimit-Reset: 1714751234
```

**Admin (illimité)** :
```
X-RateLimit-Limit: unlimited
```

**Quand bloqué (429)** :
```
HTTP/1.1 429 Too Many Requests
Retry-After: 42
Content-Type: application/json

{
  "ok": false,
  "error": {
    "code": "rate_limit_exceeded",
    "message": "Trop de requetes. Reessayez dans 42s.",
    "retry_after_sec": 42,
    "limit": 60
  }
}
```

---

## 🚫 Endpoints exemptés

- `/health`, `/api/health` : **toujours accessible** pour monitoring

---

## 📂 Storage

Buckets stockés dans `data/_ratelimit/` :
- Un fichier JSON par (rôle, identifier)
- Format : `api_{role}_{hash16}.json`
- Contenu : `{"attempts": [1714751200, 1714751210, ...], "updated_at": 1714751210}`
- Cleanup automatique via `RateLimiter::cleanup()`

---

## 🛡️ Sécurité (fail-open)

Si le middleware plante (disque plein, erreur), **la requête passe** avec un log d'erreur. Cela évite de bloquer toute l'application sur un problème du rate limiter.

---

## 🧪 Tests

22 tests couvrant :
- Limites par rôle (admin/prof/étudiant/anonyme)
- Isolation entre identifiers
- Isolation entre rôles
- Headers HTTP
- Dépassement (429 + Retry-After)
- Reset

```bash
php backend/test_rate_limiter.php
```

---

## 🔧 Cas spéciaux

### Login bruteforce

Le `RateLimiter` existant (bucket `login`) est **séparé** et reste actif sur le login :
- 5 tentatives / 15 min par IP
- Protection anti-bruteforce dédiée

### Passage d'examen

Un étudiant en examen fait typiquement :
- 1 start + N `saveAnswer` + 1 submit + qq focus_event
- Pour 40 questions : environ 45 requêtes = largement sous 60/min ✅

Si besoin de hausser ponctuellement : l'étudiant peut être temporairement passé en rôle `enseignant` depuis le dashboard admin (non implémenté actuellement).

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
