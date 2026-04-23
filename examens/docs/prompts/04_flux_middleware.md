# 🔄 Prompt 04 — Flux de données et middleware

## 📖 Description et contexte

Ce prompt génère un diagramme détaillé du **pipeline de traitement d'une requête HTTP**, depuis l'arrivée sur `index.php` jusqu'à la réponse JSON, en passant par les 3 middlewares.

### Ce qui est généré
- Arrivée de la requête HTTP
- Parsing URL (regex routing)
- Middleware 1 : Rate Limiting par rôle
- Middleware 2 : Auth (session check)
- Middleware 3 : CSRF (POST/PUT/DELETE)
- Dispatch vers endpoint
- Appels Manager → FileStorage
- Réponse avec headers rate-limit
- Toutes les voies d'échec (429, 401, 403, 500, 404)

### Quand utiliser ce prompt
- Documentation **sécurité** du projet
- Formation développeurs sur le **pipeline HTTP**
- Audit de sécurité / **debug** flow
- Section "Flux de données" dans `ARCHITECTURE.md`

### Outil recommandé
**Mermaid flowchart** avec losanges pour les décisions.

---

## 🤖 Outils IA supportés

| Outil | Qualité | Remarques |
|---|:-:|---|
| **ChatGPT-4 / GPT-4o** | ⭐⭐⭐⭐⭐ | Flowchart excellent |
| **Claude Opus 4** | ⭐⭐⭐⭐⭐ | Détails précis |
| **Claude 3.5 Sonnet** | ⭐⭐⭐⭐⭐ | Structure claire |
| **Gemini 2.0 Pro** | ⭐⭐⭐⭐ | Bon |

---

## 📋 Version pour ChatGPT-4 / GPT-4o

```
Tu es un expert en architecture de systèmes distribués et pipelines HTTP.

CONTEXTE :
Plateforme IPSSI Examens avec pipeline de traitement des requêtes HTTP :
1. Requête arrive sur index.php
2. Parsing URL (regex /api/{endpoint}/...)
3. Middleware Rate Limiting (RoleRateLimiter) :
   - Identifie le rôle (admin=illimité, prof=500/min, étudiant=60/min, anonyme=30/min)
   - Identifier = user_id si connecté, IP sinon
   - Sliding window via fichiers JSON dans data/_ratelimit/
   - Si dépassé → HTTP 429 + Retry-After
4. Middleware Auth (selon endpoint) :
   - Vérifie PHPSESSID dans data/sessions/
   - Charge le compte user via FileStorage
   - Si pas auth → HTTP 401 ou redirect login
5. Middleware CSRF (POST/PUT/DELETE) :
   - Vérifie X-CSRF-Token via timing-safe hash_equals()
   - Si invalide → HTTP 403
6. Dispatch vers l'endpoint concret (backend/api/*.php)
7. L'endpoint appelle son Manager (métier)
8. Le Manager appelle FileStorage (persistance)
9. Réponse JSON renvoyée avec headers :
   - X-RateLimit-Limit / Remaining / Reset
   - Content-Type: application/json
10. Logging dans data/logs/app.log

OBJECTIF :
Génère un diagramme de flux de requête + middlewares au format Mermaid.

TYPE DE DIAGRAMME :
Flowchart top-bottom avec branches de décision (losanges) pour chaque middleware.

ÉLÉMENTS OBLIGATOIRES :
- Début : "HTTP Request" (POST /api/examens par ex.)
- Middleware RateLimit avec sortie 429 si échec
- Middleware Auth avec sortie 401 si échec
- Middleware CSRF avec sortie 403 si échec
- Dispatch API (require endpoint.php)
- Appel Manager
- FileStorage read/write
- Logger
- Réponse finale JSON (avec les headers)
- Toutes les voies d'échec (429, 401, 403, 500, 404)

ORGANISATION :
- Utiliser des swim lanes verticales si possible (Request → Middleware → API → Metier → Persistance → Response)
- Losanges pour les décisions (if rate_limited → 429, etc.)
- Boxes stylées différemment pour middlewares vs business logic
- Annotations de timing (étape rapide <1ms vs plus lente)

FORMAT :
Mermaid flowchart TB (ou LR si plus lisible).

CRITÈRES :
- Montre CLAIREMENT le fail-open en cas d'erreur middleware (comportement actuel)
- Montre les 4 voies de sortie possibles (200 OK, 401, 403, 429, 500)
- Inclut les logs / headers ajoutés à chaque étape
- Code Mermaid sans erreur, directement copiable

Génère le code Mermaid maintenant.
```

---

## 📋 Version pour Claude

```
<role>
Expert en pipelines HTTP, middleware chains et sécurité web. Spécialiste de la représentation UML/Mermaid de flux de contrôle complexes.
</role>

<context>
Pipeline HTTP de la plateforme IPSSI Examens (backend PHP 8.3).

Ordre d'exécution : Request → parsing → 3 middlewares → endpoint → manager → storage → response.
</context>

<pipeline_steps>
  <step n="1" name="HTTP Request">
    Example: POST /api/examens
    Headers: Content-Type: application/json, X-CSRF-Token, Cookie: PHPSESSID
  </step>
  
  <step n="2" name="Route Parsing">
    File: backend/public/index.php
    Regex: /api/([a-z0-9_-]+)(?:/.*)?/i
    Special routes: /api/health (exempt from middleware)
  </step>
  
  <step n="3" name="Rate Limit Middleware">
    Class: RoleRateLimiter
    Limits:
      - admin: -1 (unlimited)
      - enseignant: 500/min
      - etudiant: 60/min
      - anonyme: 30/min (by IP)
    Storage: data/_ratelimit/api_{role}_{hash16}.json
    Algorithm: sliding window (last 60s)
    Exit if exceeded: HTTP 429 + Retry-After header
    Failure mode: fail-open (if middleware errors, request passes with error log)
  </step>
  
  <step n="4" name="Auth Middleware">
    Class: Auth::isLoggedIn()
    Checks: PHPSESSID cookie validity
    Session storage: data/sessions/ (PHP native)
    Identifier: user ID if logged in, IP otherwise
    Exit if required and not logged: HTTP 401 (or redirect /admin/login.html for browser)
  </step>
  
  <step n="5" name="CSRF Middleware">
    Class: Csrf::requireValid()
    Applies to: POST, PUT, PATCH, DELETE methods
    Checks: X-CSRF-Token header vs session token
    Algorithm: hash_equals() (timing-safe)
    Exit if invalid: HTTP 403 + message
  </step>
  
  <step n="6" name="Endpoint Dispatch">
    require $endpointFile (backend/api/{endpoint}.php)
    Example: backend/api/examens.php
  </step>
  
  <step n="7" name="Manager Call">
    Business logic in backend/lib/{Manager}.php
    Example: $em = new ExamenManager(); $em->create($data);
  </step>
  
  <step n="8" name="FileStorage">
    Read/write JSON in data/examens/EXM-xxx.json
    Concurrency: LOCK_EX for writes
  </step>
  
  <step n="9" name="Response">
    Class: Response::json($data)
    Headers added:
      - X-RateLimit-Limit: {N}
      - X-RateLimit-Remaining: {N}
      - X-RateLimit-Reset: {timestamp}
      - Content-Type: application/json
    HTTP 200 for success
  </step>
  
  <step n="10" name="Logging">
    Logger::info() in data/logs/app.log
    Format: [timestamp] [channel] message
  </step>
</pipeline_steps>

<error_exits>
  <exit code="404" trigger="Endpoint not found" />
  <exit code="429" trigger="Rate limit exceeded" header="Retry-After" />
  <exit code="401" trigger="Not authenticated" />
  <exit code="403" trigger="CSRF invalid OR role forbidden" />
  <exit code="500" trigger="Server error (caught exception)" />
</error_exits>

<requirements>
  <format>Mermaid flowchart TB</format>
  <structure>
    - Linear main flow top-down
    - Diamond shapes for middleware decisions
    - Rectangular boxes for actions
    - Red-tinted boxes for error exits
    - Green-tinted for success
  </structure>
  <annotations>
    - Each step has a short label
    - Middleware decisions show condition (if X then Y)
    - Arrows labeled with return codes or actions
  </annotations>
</requirements>

<o>
Provide:
1. Complete Mermaid flowchart TB code
2. Explanation of fail-open behavior (important for middleware)
3. Security implications (what prevents typical attacks : bruteforce, CSRF, XSS)
</o>
```

---

## 📋 Version pour Gemini Pro / 2.0 Flash

```
Diagramme Mermaid : pipeline HTTP middleware.

Projet : IPSSI Examens (PHP 8.3)

SÉQUENCE DÉTAILLÉE :

1. HTTP Request arrive (ex: POST /api/examens)

2. Parse URL dans index.php (regex /api/xxx)

3. [DÉCISION] Route /health ? 
   - OUI → skip middleware, retourne health JSON
   - NON → continuer

4. MIDDLEWARE RATE LIMIT (RoleRateLimiter)
   Limites par rôle :
   - admin: illimité
   - prof: 500/min
   - etudiant: 60/min
   - anonyme: 30/min
   
   Algorithme : sliding window 60s
   Storage : data/_ratelimit/
   
   [DÉCISION] dépassé ?
   - OUI → HTTP 429 + Retry-After (FIN)
   - NON → continuer avec headers X-RateLimit-*

5. MIDDLEWARE AUTH (Auth::isLoggedIn)
   Check session PHPSESSID
   [DÉCISION] authentifié (si requis) ?
   - NON → HTTP 401 (FIN)
   - OUI → continuer

6. MIDDLEWARE CSRF (si POST/PUT/DELETE)
   Check X-CSRF-Token avec hash_equals()
   [DÉCISION] valide ?
   - NON → HTTP 403 (FIN)
   - OUI → continuer

7. Dispatch : require backend/api/{endpoint}.php

8. Appel Manager (ex: $em->create())

9. Manager → FileStorage read/write JSON dans data/

10. Logger::info()

11. Response::json() avec headers
    - X-RateLimit-Limit
    - X-RateLimit-Remaining  
    - X-RateLimit-Reset
    - HTTP 200

SORTIES POSSIBLES : 200, 401, 403, 404, 429, 500

RÈGLES :
- Format : flowchart TB Mermaid
- Losanges pour décisions
- Rectangles pour actions
- Couleurs : rouge pour erreurs, vert pour succès
- Labels sur flèches
- Fail-open : si middleware plante, requête passe (log error)
- 200 = succès final

Génère le code Mermaid complet.
```

---

## 🎨 Rendu final

### Rendu dans la doc

Parfait pour :
- `docs/ARCHITECTURE.md` section "Flux de données"
- `docs/RATE_LIMITING.md` - visualiser le middleware
- Formation équipe développement

### Intégration suggérée

```markdown
## Flux HTTP et middlewares

Chaque requête traverse 3 middlewares obligatoires :

\`\`\`mermaid
[code généré]
\`\`\`

**Fail-open** : en cas d'erreur d'un middleware, la requête passe
avec un log d'erreur, pour ne jamais bloquer l'application.
```

---

## 💡 Variations

### Version détaillée avec exceptions
*"Ajoute des paths pour chaque type d'exception : network timeout, disk full, JSON parse error, etc."*

### Version avec métriques
*"Ajoute des annotations de timing typique pour chaque étape (< 1ms, 5ms, 50ms)."*

### Version sécurité
*"Mets en évidence visuellement les vecteurs d'attaque bloqués par chaque middleware."*

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
