# 📐 Prompt 05 — Diagramme de classes UML

## 📖 Description et contexte

Ce prompt génère un **diagramme de classes UML complet** (classDiagram Mermaid) modélisant les 17 Managers PHP avec leurs attributs, méthodes, constantes et relations.

### Ce qui est généré
- 16 classes principales (namespace `Examens\Lib`)
- Attributs privés/publics avec types
- Méthodes avec signatures typées
- Constantes (ROLE_ADMIN, LIMITS, etc.)
- Relations : composition, dépendance, association
- Stéréotypes (`<<static>>`, `<<abstract>>`)

### Quand utiliser ce prompt
- **Onboarding développeur** (comprendre l'API métier)
- Documentation **UML référence**
- Audit de code ou **refactoring planning**
- Section "Classes et Managers" dans `ARCHITECTURE.md`

### Outil recommandé
**Mermaid classDiagram** — meilleur support dans GitHub/GitLab.

---

## 🤖 Outils IA supportés

| Outil | Qualité | Remarques |
|---|:-:|---|
| **ChatGPT-4 / GPT-4o** | ⭐⭐⭐⭐⭐ | Meilleure syntaxe classDiagram |
| **Claude Opus 4** | ⭐⭐⭐⭐⭐ | Relations UML parfaites |
| **Claude 3.5 Sonnet** | ⭐⭐⭐⭐⭐ | Excellent |
| **Gemini 2.0 Pro** | ⭐⭐⭐⭐ | Correct, parfois erreurs syntaxe |

---

## 📋 Version pour ChatGPT-4 / GPT-4o

```
Tu es un architecte logiciel expert en UML et modélisation objet.

CONTEXTE :
Je documente la couche métier de la plateforme IPSSI Examens (PHP 8.3 strict types).

CLASSES À MODÉLISER (namespace Examens\Lib) :

1. Auth
   - Propriétés : private FileStorage $storage, private Session $session
   - Constantes : ROLE_ADMIN = 'admin', ROLE_ENSEIGNANT = 'enseignant'
   - Méthodes : login(email, password): bool, logout(): void, isLoggedIn(): bool, getCurrentUser(): ?array, requireAuth(): void, requireRole(string $role): void, hashPassword(pwd): string, verifyPassword(pwd, hash): bool

2. Session
   - Méthodes statiques : start(), regenerate(), destroy(), set(key, value), get(key): mixed

3. Csrf
   - Méthodes statiques : generate(): string, validate(token): bool, requireValid(): void, regenerate(): string

4. Logger
   - Propriétés : private string $logDir, private string $defaultChannel
   - Méthodes : debug(msg), info(msg), warning(msg), error(msg), log(level, msg, context = [])

5. Response
   - Méthodes statiques : json(data, status = 200), error(msg, code = 400), success(data), notFound(msg), unauthorized(msg)

6. FileStorage
   - Propriétés : private Logger $logger
   - Méthodes : read(path): mixed, write(path, data): bool, update(path, callable $fn): bool, delete(path): bool, exists(path): bool, glob(pattern): array

7. ExamenManager
   - Propriétés : private FileStorage $storage, private Logger $logger
   - Méthodes : create(data): array, get(id): ?array, update(id, data): bool, delete(id): bool, list(filters = []): array, publish(id), close(id), archive(id), getByAccessCode(code): ?array, generateAccessCode(): string
   - Constantes : STATUS_DRAFT, STATUS_PUBLISHED, STATUS_CLOSED, STATUS_ARCHIVED

8. PassageManager
   - Propriétés : private FileStorage $storage, private Logger $logger, private string $signatureSalt
   - Méthodes : start(examenId, studentInfo): array, getByToken(token): ?array, saveAnswer(token, qId, answer): bool, submit(token): array, expire(token), invalidate(token, reason), logFocusEvent(token, type, data), computeScore(passage): array, signPassage(data): string, verifySignature(passage): bool

9. BanqueManager
   - Méthodes : listModules(): array, listChapitres(module): array, listThemes(module, chapitre), getQuestion(id): ?array, createQuestion(data), updateQuestion(id, data), deleteQuestion(id), searchQuestions(filters)

10. AnalyticsManager
    - Méthodes : getProfOverview(profId): array, getExamenOverview(examenId), getExamenScoresDistribution(examenId), getExamenQuestionsStats(examenId), getExamenTimeline(examenId), getExamenPassages(examenId, filters), getStudentHistory(email), getFocusHeatmap(examenId)

11. BackupManager
    - Propriétés : private string $backupsDir, private string $scriptsDir, private Logger $logger
    - Méthodes : list(): array, get(id): ?array, createBackup(keep): array, verify(id): array, delete(id): bool, getStats(): array

12. HealthChecker
    - Propriétés : private string $dataDir, private int $diskWarnBytes, private int $diskErrorBytes
    - Méthodes : checkAll(): array, checkDisk(): array, checkMemory(): array, checkFilesystem(): array, checkCounters(): array, checkBackups(): array, checkLogs(): array, checkPhp(): array

13. RateLimiter
    - Propriétés : private string $bucket, private int $maxAttempts, private int $windowSec, private FileStorage $storage
    - Méthodes : attempt(key): bool, record(key), remaining(key): int, retryAfter(key): int, isBlocked(key): bool, reset(key), cleanup(): int

14. RoleRateLimiter
    - Propriétés : private array $limiters
    - Constantes : LIMITS (array avec admin=-1, enseignant=500, etudiant=60, anonyme=30), WINDOW_SEC = 60
    - Méthodes : check(role, identifier): array, headers(check): array, reset(role, identifier), getStats(): array

15. Mailer
    - Méthodes : send(to, subject, htmlBody): bool, sendTemplate(to, template, vars): bool

16. EmailTemplate
    - Méthodes statiques : render(name, vars): string, e(text): string (escape HTML)

RELATIONS :
- Auth utilise FileStorage + Session
- ExamenManager, PassageManager, BackupManager utilisent FileStorage + Logger
- RoleRateLimiter utilise plusieurs RateLimiter (composition)
- PassageManager utilise signature HMAC via openssl_hmac
- Mailer utilise EmailTemplate

OBJECTIF :
Génère un diagramme de classes UML complet au format Mermaid (classDiagram).

SPÉCIFICATIONS :
- Utiliser la syntaxe classDiagram de Mermaid
- Visibilité : + public, - private, # protected
- Types de retour et paramètres explicites (: array, : string, : bool, etc.)
- Relations :
  - Composition (◆──) pour $storage dans les managers
  - Dépendance (..>) pour les appels de méthodes
  - Association avec multiplicité (1, *, 0..1) quand pertinent
- Stereotype <<interface>> ou <<abstract>> si applicable
- Classes statiques : <<static>> (pour Session, Csrf, Response, EmailTemplate)

ORGANISATION :
- Grouper par responsabilité (auth/security, data managers, infrastructure)
- Classes de base (FileStorage, Logger) au centre
- Managers autour
- Security (Auth, Csrf, Session) en haut

FORMAT :
Code Mermaid valide et complet.

CRITÈRES :
- Toutes les 16 classes présentes
- Relations correctes (composition vs dépendance vs association)
- Constantes affichées comme des attributs avec <<const>> ou +CONST
- Lisible sans être trop chargé (éventuellement omettre méthodes privées si trop)
- Compatible mermaid.live

Génère le code Mermaid maintenant.
```

---

## 📋 Version pour Claude

```
<role>
Architecte logiciel expert UML 2.5 et maître du classDiagram Mermaid. Tu produis des diagrammes précis, syntaxiquement corrects, pédagogiquement clairs.
</role>

<task>
Diagramme de classes UML complet en Mermaid pour les 17 managers PHP de la plateforme IPSSI Examens.
</task>

<namespace>Examens\Lib</namespace>

<classes>
  <class name="Auth">
    <dependencies>FileStorage, Session</dependencies>
    <constants>ROLE_ADMIN, ROLE_ENSEIGNANT</constants>
    <methods>
      +login(email: string, password: string): bool
      +logout(): void
      +isLoggedIn(): bool
      +getCurrentUser(): ?array
      +requireAuth(): void
      +requireRole(role: string): void
      +hashPassword(pwd: string): string
      +verifyPassword(pwd: string, hash: string): bool
    </methods>
  </class>
  
  <class name="Session" stereotype="static">
    <methods>
      +start(): void
      +regenerate(): void
      +destroy(): void
      +set(key: string, value: mixed): void
      +get(key: string): mixed
    </methods>
  </class>
  
  <class name="Csrf" stereotype="static">
    <methods>
      +generate(): string
      +validate(token: string): bool
      +requireValid(): void
      +regenerate(): string
    </methods>
  </class>
  
  <class name="Logger">
    <attributes>
      -logDir: string
      -defaultChannel: string
    </attributes>
    <methods>
      +debug(msg: string, context: array): void
      +info(msg: string, context: array): void
      +warning(msg: string, context: array): void
      +error(msg: string, context: array): void
      +log(level: string, msg: string, context: array): void
    </methods>
  </class>
  
  <class name="Response" stereotype="static">
    <methods>
      +json(data: mixed, status: int): void
      +error(msg: string, code: int): void
      +success(data: mixed): void
      +notFound(msg: string): void
      +unauthorized(msg: string): void
    </methods>
  </class>
  
  <class name="FileStorage">
    <dependencies>Logger</dependencies>
    <attributes>
      -logger: Logger
    </attributes>
    <methods>
      +read(path: string): mixed
      +write(path: string, data: mixed): bool
      +update(path: string, fn: callable): bool
      +delete(path: string): bool
      +exists(path: string): bool
      +glob(pattern: string): array
    </methods>
  </class>
  
  <class name="BanqueManager">
    <dependencies>FileStorage, Logger</dependencies>
    <methods>
      +listModules(): array
      +listChapitres(module: string): array
      +getQuestion(id: string): ?array
      +createQuestion(data: array): array
      +updateQuestion(id: string, data: array): bool
      +deleteQuestion(id: string): bool
      +searchQuestions(filters: array): array
    </methods>
  </class>
  
  <class name="ExamenManager">
    <dependencies>FileStorage, Logger</dependencies>
    <constants>STATUS_DRAFT, STATUS_PUBLISHED, STATUS_CLOSED, STATUS_ARCHIVED</constants>
    <methods>
      +create(data: array): array
      +get(id: string): ?array
      +update(id: string, data: array): bool
      +delete(id: string): bool
      +list(filters: array): array
      +publish(id: string): bool
      +close(id: string): bool
      +archive(id: string): bool
      +getByAccessCode(code: string): ?array
      -generateAccessCode(): string
    </methods>
  </class>
  
  <class name="PassageManager">
    <dependencies>FileStorage, Logger</dependencies>
    <attributes>
      -signatureSalt: string
    </attributes>
    <methods>
      +start(examenId: string, studentInfo: array): array
      +getByToken(token: string): ?array
      +saveAnswer(token: string, qId: string, answer: int): bool
      +submit(token: string): array
      +expire(token: string): void
      +invalidate(token: string, reason: string): void
      +logFocusEvent(token: string, type: string, data: array): void
      -computeScore(passage: array): array
      -signPassage(data: array): string
      -verifySignature(passage: array): bool
    </methods>
  </class>
  
  <class name="AnalyticsManager">
    <dependencies>FileStorage, Logger, ExamenManager, PassageManager</dependencies>
    <methods>
      +getProfOverview(profId: string): array
      +getExamenOverview(examenId: string): array
      +getExamenScoresDistribution(examenId: string): array
      +getExamenQuestionsStats(examenId: string): array
      +getExamenTimeline(examenId: string): array
      +getExamenPassages(examenId: string, filters: array): array
      +getStudentHistory(email: string): array
      +getFocusHeatmap(examenId: string): array
    </methods>
  </class>
  
  <class name="BackupManager">
    <dependencies>Logger</dependencies>
    <attributes>
      -backupsDir: string
      -scriptsDir: string
    </attributes>
    <methods>
      +list(): array
      +get(id: string): ?array
      +createBackup(keep: int): array
      +verify(id: string): array
      +delete(id: string): bool
      +getStats(): array
    </methods>
  </class>
  
  <class name="HealthChecker">
    <attributes>
      -dataDir: string
      -diskWarnBytes: int
      -diskErrorBytes: int
    </attributes>
    <methods>
      +checkAll(): array
      +checkDisk(): array
      +checkMemory(): array
      +checkFilesystem(): array
      +checkCounters(): array
      +checkBackups(): array
      +checkLogs(): array
      +checkPhp(): array
    </methods>
  </class>
  
  <class name="RateLimiter">
    <dependencies>FileStorage</dependencies>
    <attributes>
      -bucket: string
      -maxAttempts: int
      -windowSec: int
    </attributes>
    <methods>
      +attempt(key: string): bool
      +record(key: string): void
      +remaining(key: string): int
      +retryAfter(key: string): int
      +isBlocked(key: string): bool
      +reset(key: string): void
      +cleanup(): int
    </methods>
  </class>
  
  <class name="RoleRateLimiter">
    <dependencies>RateLimiter</dependencies>
    <constants>LIMITS (admin=-1, enseignant=500, etudiant=60, anonyme=30), WINDOW_SEC=60</constants>
    <methods>
      +check(role: string, identifier: string): array
      +headers(check: array): array
      +reset(role: string, identifier: string): void
      +getStats(): array
    </methods>
  </class>
  
  <class name="Mailer">
    <dependencies>EmailTemplate</dependencies>
    <methods>
      +send(to: string, subject: string, htmlBody: string): bool
      +sendTemplate(to: string, template: string, vars: array): bool
    </methods>
  </class>
  
  <class name="EmailTemplate" stereotype="static">
    <methods>
      +render(name: string, vars: array): string
      +e(text: string): string
    </methods>
  </class>
</classes>

<relationships>
  - Auth --* FileStorage (composition)
  - Auth --* Session (uses)
  - ExamenManager --* FileStorage
  - ExamenManager --* Logger
  - PassageManager --* FileStorage
  - PassageManager --* Logger
  - PassageManager --> ExamenManager (depends)
  - AnalyticsManager --> ExamenManager, PassageManager (depends)
  - BackupManager --* Logger
  - RoleRateLimiter --* RateLimiter (composition multiple)
  - Mailer --> EmailTemplate (uses)
  - FileStorage --* Logger
</relationships>

<requirements>
  <format>Mermaid classDiagram</format>
  <visibility>
    + public
    - private
    # protected
  </visibility>
  <syntax>
    Types explicites : method(param: type): returnType
    Relations : ..> pour dépendance, --* pour composition, --> pour association
    Stéréotypes : <<static>>, <<abstract>>, <<interface>>
  </syntax>
  <organization>
    Groupement logique par domaine :
    - Sécurité (Auth, Session, Csrf)
    - Infrastructure (Logger, Response, FileStorage)
    - Managers data (Banque, Examen, Passage)
    - Analytics & Ops (Analytics, Backup, Health)
    - Rate limit (RateLimiter, RoleRateLimiter)
    - Email (Mailer, EmailTemplate)
  </organization>
</requirements>

<o>
1. Complete Mermaid classDiagram code in a ```mermaid``` block
2. All 16 classes with complete methods/attributes
3. All relationships correctly typed
4. Constants shown as attributes with <<const>> prefix
5. Static classes marked with <<static>> stereotype
</o>
```

---

## 📋 Version pour Gemini Pro / 2.0 Flash

Version condensée et structurée :

```
Génère un classDiagram Mermaid pour IPSSI Examens (16 classes PHP).

CLASSES (namespace Examens\Lib) :

SÉCURITÉ :
- Auth : login/logout/isLoggedIn, utilise FileStorage + Session
- Session (static) : start/regenerate/set/get
- Csrf (static) : generate/validate/requireValid

INFRASTRUCTURE :
- Logger : debug/info/warning/error
- Response (static) : json/error/success
- FileStorage : read/write/update/delete/glob/exists, utilise Logger

DATA MANAGERS :
- BanqueManager : CRUD questions (listModules, getQuestion...)
- ExamenManager : CRUD examens + constantes STATUS_*, utilise FileStorage + Logger
- PassageManager : start/saveAnswer/submit + signatures HMAC, utilise FileStorage + Logger

ANALYTICS & OPS :
- AnalyticsManager : 8 méthodes de stats
- BackupManager : list/create/verify/delete, utilise Logger
- HealthChecker : 7 checks systèmes

RATE LIMIT :
- RateLimiter : attempt/record/remaining, utilise FileStorage
- RoleRateLimiter : wrapper par rôle, utilise RateLimiter (composition)

EMAIL :
- Mailer : send/sendTemplate, utilise EmailTemplate
- EmailTemplate (static) : render/e (escape)

RÈGLES :
- Format : classDiagram Mermaid
- Visibilité + - #
- Types explicites : method(param: type): returnType
- Relations : ..> pour uses, --* pour composition, --> pour association
- Stéréotypes : <<static>>, <<const>> pour constantes
- 16 classes toutes présentes
- Groupement visuel par domaine (comment-like dividers)

Produis le code Mermaid complet.
```

---

## 🎨 Rendu final

### Rendu et export

1. Code → https://mermaid.live/
2. Export SVG (haute qualité, zoomable)
3. PNG pour documents Word/PDF

### Intégration dans la doc

Dans `ARCHITECTURE.md`, section "Classes et managers" :

````markdown
## Classes et managers

### Diagramme de classes

```mermaid
[code généré]
```

### Description des managers

| Manager | Responsabilité | Dépendances |
|---|---|---|
| Auth | Authentification, bcrypt | FileStorage, Session |
| ExamenManager | CRUD examens | FileStorage, Logger |
| ... | ... | ... |
````

---

## 💡 Variations

### Version simplifiée (15 classes)
*"Simplifie en omettant les méthodes privées et en regroupant les utilitaires (Logger, Response, Csrf, Session) en un seul package."*

### Version avec interfaces
*"Ajoute des interfaces abstraites : ManagerInterface (pour tous les managers CRUD), LoggableInterface, etc."*

### Version focus sécurité
*"Produis uniquement les classes de sécurité (Auth, Csrf, Session, RateLimiter, RoleRateLimiter) avec leurs interactions détaillées."*

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
