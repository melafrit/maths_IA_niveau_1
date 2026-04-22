# 🎨 Exemples de diagrammes pré-générés

> Quelques exemples de diagrammes **directement utilisables**, générés à partir
> des prompts du fichier `PROMPTS_CHATGPT.md`. À copier-coller dans mermaid.live
> ou plantuml.com pour rendu immédiat.

---

## 1. Architecture globale (Mermaid)

**Rendu** : copier le code ci-dessous sur https://mermaid.live/

```mermaid
flowchart LR
    subgraph Client["👤 Client (Navigateur)"]
        A[Admin]
        P[Enseignant]
        E[Étudiant]
    end

    subgraph Server["⚙️ Serveur PHP 8.3"]
        R[Router<br/>index.php]
        MW[Middleware<br/>Rate Limit + CSRF]
        API[API Layer<br/>/api/*]
        M[Managers<br/>Auth, Examen, Passage,<br/>Analytics, Backup, Health]
    end

    subgraph Data["💾 Données"]
        FS[FileStorage]
        D1[(examens/)]
        D2[(passages/)]
        D3[(comptes/)]
        D4[(banque/)]
        D5[(backups/)]
    end

    subgraph External["🔄 Externe"]
        Cron[⏰ Cron 03:00<br/>backup.sh]
        SMTP[📧 SMTP OVH]
    end

    A -->|HTTPS| R
    P -->|HTTPS| R
    E -->|HTTPS| R
    R --> MW
    MW --> API
    API --> M
    M --> FS
    FS --> D1
    FS --> D2
    FS --> D3
    FS --> D4
    FS --> D5
    Cron -.->|tar.gz + SHA-256| D5
    M -.->|Email| SMTP

    classDef clientStyle fill:#dbeafe,stroke:#3b82f6,stroke-width:2px
    classDef serverStyle fill:#fed7aa,stroke:#f97316,stroke-width:2px
    classDef dataStyle fill:#bbf7d0,stroke:#22c55e,stroke-width:2px
    classDef externalStyle fill:#e5e5e5,stroke:#6b7280,stroke-width:2px

    class A,P,E clientStyle
    class R,MW,API,M serverStyle
    class FS,D1,D2,D3,D4,D5 dataStyle
    class Cron,SMTP externalStyle
```

---

## 2. Séquence — Passage étudiant (Mermaid)

**Rendu** : https://mermaid.live/

```mermaid
sequenceDiagram
    autonumber
    actor E as 🎓 Étudiant
    participant UI as 🖥️ Navigateur<br/>(passage.html)
    participant R as 🔀 index.php
    participant API as 📡 api/passages
    participant PM as 📝 PassageManager
    participant EM as 📚 ExamenManager
    participant FS as 💾 data/
    participant MAIL as 📧 Mailer

    Note over E,MAIL: Phase 1 - Connexion
    E->>UI: Saisit code "ABC23K-9P"
    UI->>R: POST /api/passages/access
    R->>API: delegate
    API->>EM: getByAccessCode("ABC23K-9P")
    EM->>FS: read examens/*.json
    FS-->>EM: data
    EM-->>API: Examen{titre, duree}
    API-->>UI: 200 OK

    Note over E,MAIL: Phase 2 - Start
    E->>UI: Remplit {nom, prenom, email}
    UI->>R: POST /api/passages/start + CSRF
    R->>API: delegate
    API->>PM: start(examen_id, student_info)
    PM->>PM: Generate PSG-XXXX + UUID token
    PM->>PM: Shuffle questions + options
    PM->>FS: write passages/PSG-xxx.json
    PM-->>API: {token, question_order}
    API-->>UI: 200 OK

    Note over E,MAIL: Phase 3 - Passage (boucle)
    loop Pour chaque question
        E->>UI: Clique option
        UI->>R: POST /api/passages/answer
        R->>API: delegate
        API->>PM: saveAnswer(token, qid, idx)
        PM->>FS: update passage
    end

    par Événements focus
        UI->>R: POST /api/passages/focus-event
        R->>API: delegate
        API->>PM: logFocusEvent()
        PM->>FS: append focus_events[]
    end

    Note over E,MAIL: Phase 4 - Soumission
    E->>UI: Clique "Soumettre"
    UI->>R: POST /api/passages/submit + CSRF
    R->>API: delegate
    API->>PM: submit(token)
    PM->>PM: Compute score
    PM->>PM: Compute HMAC SHA-256
    PM->>FS: update status=submitted
    PM->>MAIL: send correction email
    MAIL->>MAIL: SMTP → étudiant
    PM-->>API: {score_pct, correction_url}
    API-->>UI: 200 OK
    UI-->>E: Affiche score

    Note over E,MAIL: Phase 5 - Correction
    E->>UI: Click lien email
    UI->>R: GET /api/corrections/{token}
    R->>API: delegate
    API->>PM: getByToken(token)
    PM->>FS: read passage + examen
    PM-->>API: correction detaillee
    API-->>UI: 200 OK
    UI-->>E: Affiche correction
```

---

## 3. Diagramme d'états — Examen (PlantUML)

**Rendu** : http://www.plantuml.com/plantuml/

```plantuml
@startuml
title Cycle de vie d'un Examen

[*] --> Draft : create()

state Draft {
  Draft : **entry** / status='draft'
  Draft : created_at = now
  Draft : updated_at = now
  Draft : **do** / allow any modification
}

state Published {
  Published : **entry** / status='published'
  Published : generate access_code
  Published : log audit
  Published : **do** / accept passages
  Published : if date_cloture <= now
}

state Closed {
  Closed : **entry** / status='closed'
  Closed : closed_at = now
  Closed : auto-submit in-progress
  Closed : **do** / read-only
}

state Archived {
  Archived : **entry** / status='archived'
  Archived : hidden from main list
  Archived : **do** / analytics accessible
}

Draft --> Published : publish()\n[titre OK\n+ ≥1 question\n+ dates valides]
Draft --> [*] : delete()\n[aucun passage]

Published --> Closed : close() [manuel]
Published --> Closed : now >= date_cloture\n[auto]
Published --> Draft : unpublish()\n[aucun passage]

Closed --> Archived : archive()

Archived --> [*] : delete() [admin only]

note right of Published
  Code d'accès actif
  Étudiants peuvent démarrer
  si dans la fenêtre temporelle
end note

note right of Closed
  Code d'accès désactivé
  Analytics accessibles
end note

@enduml
```

---

## 4. Diagramme d'états — Passage (PlantUML)

**Rendu** : http://www.plantuml.com/plantuml/

```plantuml
@startuml
title Cycle de vie d'un Passage étudiant

[*] --> InProgress : start()\n[après validation\ncode + infos]

state InProgress {
  InProgress : **entry** / status='in_progress'
  InProgress : start_time = now
  InProgress : generate UUID token
  InProgress : save JSON
  InProgress : **do** / accept saveAnswer()
  InProgress : **do** / log focus_events
  InProgress : **do** / auto-save
  
  state Watching {
    [*] --> Normal
    Normal --> Warning : 3+ events
    Warning --> AutoInvalidate : seuil dépassé
    Warning --> Normal : délai écoulé
  }
}

state Submitted {
  Submitted : **entry** / compute score
  Submitted : compute signature HMAC SHA-256
  Submitted : end_time = now
  Submitted : envoi email via Mailer
  Submitted : **do** / read-only, par token
}

state Expired {
  Expired : **entry** / status='expired'
  Expired : end_time = start_time + duree_sec
  Expired : compute score partiel
  Expired : log 'expired_timeout'
  Expired : **do** / read-only
}

state Invalidated {
  Invalidated : **entry** / status='invalidated'
  Invalidated : invalidation_reason
  Invalidated : invalidated_by
  Invalidated : **do** / caché des stats
}

InProgress --> Submitted : submit()\n[manuel]
InProgress --> Expired : timeout auto
InProgress --> Invalidated : invalidate()\n[manuel ou auto]

Submitted --> Invalidated : invalidate()\n[découverte tardive]
Expired --> [*] : end

Invalidated --> [*] : delete()\n[admin cleanup]

note right of InProgress
  Anti-triche actif :
  - 3+ devtools_open → warning
  - 5+ blur long → warning
  - 10+ copy/paste → auto-invalidate
end note

note right of Submitted
  signature_sha256 stockée
  → détection altération a posteriori
end note

@enduml
```

---

## 5. Diagramme de cas d'utilisation (PlantUML)

**Rendu** : http://www.plantuml.com/plantuml/

```plantuml
@startuml
title IPSSI Examens - Use Case Diagram

left to right direction

actor "👑 Admin" as A
actor "👨‍🏫 Enseignant" as P
actor "🎓 Étudiant" as S
actor "👤 Invité" as I
actor "⏰ Cron" as CR
actor "📧 SMTP Server" as SM
actor "🔔 UptimeRobot" as U

A --|> P : hérite

rectangle "IPSSI Examens" {

  package "Authentification" {
    usecase "Se connecter" as UC1
    usecase "Logout" as UC2
    usecase "Changer password" as UC3
  }

  package "Gestion Examens" {
    usecase "Créer examen" as UC10
    usecase "Sélectionner\nquestions" as UC11
    usecase "Configurer\nparamètres" as UC12
    usecase "Publier examen" as UC13
    usecase "Générer code\nd'accès" as UC14
    usecase "Clôturer" as UC15
    usecase "Archiver" as UC16
  }

  package "Banque Questions" {
    usecase "Créer question" as UC20
    usecase "Modifier question" as UC21
    usecase "Consulter banque" as UC22
  }

  package "Passage Étudiant" {
    usecase "Accéder par code" as UC30
    usecase "Passer examen" as UC31
    usecase "Soumettre" as UC32
    usecase "Recevoir email" as UC33
    usecase "Consulter\ncorrection" as UC34
    usecase "Télécharger PDF" as UC35
  }

  package "Analytics" {
    usecase "Dashboard analytics" as UC40
    usecase "Distribution scores" as UC41
    usecase "Analyse distracteurs" as UC42
    usecase "Historique étudiant" as UC43
    usecase "Export CSV/Excel/PDF" as UC44
  }

  package "Administration" {
    usecase "Gérer comptes" as UC50
    usecase "Monitoring système" as UC51
    usecase "Gérer backups" as UC52
    usecase "Restaurer backup" as UC53
  }

  package "Système" {
    usecase "Backup quotidien" as UC60
    usecase "Envoyer email" as UC61
    usecase "Ping health" as UC62
  }
}

P --> UC1
P --> UC2
P --> UC3

P --> UC10
UC10 ..> UC11 : <<include>>
UC10 ..> UC12 : <<include>>
P --> UC13
UC13 ..> UC14 : <<include>>
P --> UC15
P --> UC16

P --> UC20
P --> UC21
P --> UC22

I --> UC30
UC30 <.. UC31 : <<extend>>
S --> UC31
S --> UC32
UC32 ..> UC33 : <<include>>
S --> UC34
UC34 <.. UC35 : <<extend>>

P --> UC40
UC40 ..> UC41 : <<include>>
UC40 ..> UC42 : <<include>>
UC40 ..> UC43 : <<include>>
P --> UC44

A --> UC50
A --> UC51
A --> UC52
A --> UC53

CR --> UC60
SM --> UC61
U --> UC62

@enduml
```

---

## 6. ERD — Modèle de données (Mermaid)

**Rendu** : https://mermaid.live/

```mermaid
erDiagram
    COMPTE ||--o{ EXAMEN : "creates"
    EXAMEN ||--o{ PASSAGE : "has"
    EXAMEN }o--|| QUESTION : "contains"
    PASSAGE ||--o{ FOCUS_EVENT : "logs"

    COMPTE {
        string id PK "USR-xxxxx"
        string email UK
        string nom
        string prenom
        enum role "admin|enseignant|etudiant"
        string password_hash "bcrypt cost 12"
        datetime created_at
        datetime last_login
        bool active
    }

    EXAMEN {
        string id PK "EXM-XXXX-YYYY"
        string titre
        text description
        enum status "draft|published|closed|archived"
        string created_by FK
        datetime created_at
        datetime updated_at
        array questions "1-50 question IDs"
        int duree_sec
        datetime date_ouverture
        datetime date_cloture
        int max_passages
        bool shuffle_questions
        bool shuffle_options
        bool show_correction_after
        int correction_delay_min
        string access_code UK "ABC23K-9P"
    }

    PASSAGE {
        string id PK "PSG-XXXX-YYYY"
        string examen_id FK
        string token UK "UUID v4"
        object student_info "nom, prenom, email"
        array question_order "shuffled"
        object option_shuffle_maps
        object answers
        datetime start_time
        datetime end_time
        int duration_sec
        enum status "in_progress|submitted|expired|invalidated"
        int score_brut
        int score_max
        float score_pct "0-100"
        string signature_sha256 "HMAC 64 chars"
        array focus_events
        object score_details
    }

    QUESTION {
        string id PK "module-difficulte-num"
        string module "vecteurs, matrices"
        string chapitre
        string theme
        enum difficulte "facile|moyen|difficile|tres_difficile"
        enum type "qcm"
        text enonce "LaTeX KaTeX"
        array options "4 items with correct flag"
        text explication
        array tags
        datetime created_at
    }

    FOCUS_EVENT {
        enum type "copy|paste|blur|focus|devtools_open|right_click"
        datetime timestamp
        int duration_ms
        object metadata
    }
```

---

## 7. Architecture sécurité 6 couches (Mermaid)

**Rendu** : https://mermaid.live/

```mermaid
flowchart TB
    Attack[🚨 Requête malveillante]

    subgraph L1["Couche 1 - Rate Limiting (RoleRateLimiter)"]
        L1A[Sliding window 60s]
        L1B[Bloque bruteforce + DDoS]
        L1Out[HTTP 429 + Retry-After]
    end

    subgraph L2["Couche 2 - Authentication (Auth)"]
        L2A[Bcrypt cost 12]
        L2B[Session file-based]
        L2C[requireRole hiérarchique]
        L2Out[HTTP 401 si non auth]
    end

    subgraph L3["Couche 3 - CSRF (Csrf)"]
        L3A[Token base64url 32 chars]
        L3B[hash_equals timing-safe]
        L3Out[HTTP 403 si token invalide]
    end

    subgraph L4["Couche 4 - Validation (Managers)"]
        L4A[Regex stricts IDs]
        L4B[EXM-XXXX-YYYY]
        L4C[Path traversal refusé]
        L4Out[InvalidArgumentException]
    end

    subgraph L5["Couche 5 - Signatures HMAC"]
        L5A[HMAC SHA-256 + salt]
        L5B[Détection altération]
        L5Out[Invalidation silencieuse]
    end

    subgraph L6["Couche 6 - Escape HTML"]
        L6A[htmlspecialchars ENT_QUOTES]
        L6B[Protection XSS]
        L6Out[Output safe dans emails]
    end

    Data[🔒 Données sensibles]

    Attack -->|requête| L1
    L1 -->|si passe| L2
    L2 -->|si passe| L3
    L3 -->|si passe| L4
    L4 -->|si passe| L5
    L5 -->|si passe| L6
    L6 -->|accepté| Data

    L1 -.->|bloque| X1[❌ Bruteforce/DDoS bloqué]
    L2 -.->|bloque| X2[❌ Session hijacking bloqué]
    L3 -.->|bloque| X3[❌ CSRF bloqué]
    L4 -.->|bloque| X4[❌ Path traversal bloqué]
    L5 -.->|bloque| X5[❌ Tampering détecté]
    L6 -.->|bloque| X6[❌ XSS bloqué]

    classDef attackStyle fill:#fee2e2,stroke:#dc2626,stroke-width:3px,color:#991b1b
    classDef layerStyle fill:#fef3c7,stroke:#f59e0b,stroke-width:2px
    classDef dataStyle fill:#dcfce7,stroke:#16a34a,stroke-width:3px
    classDef blockedStyle fill:#fecaca,stroke:#991b1b,color:#7f1d1d

    class Attack attackStyle
    class L1,L2,L3,L4,L5,L6 layerStyle
    class Data dataStyle
    class X1,X2,X3,X4,X5,X6 blockedStyle
```

---

## 8. Architecture en couches (Mermaid) - Simplifiée

**Rendu** : https://mermaid.live/

```mermaid
flowchart TB
    subgraph Presentation["🎨 PRÉSENTATION - Frontend"]
        PA[Pages HTML admin/etudiant]
        PB[Composants React JSX]
        PC[CDN: React 18, Babel,<br/>KaTeX, Recharts]
    end

    subgraph Routing["🔀 ROUTING"]
        RA[index.php]
        RB[Middleware: Rate Limit]
        RC[Middleware: CSRF]
        RD[/health endpoint/]
    end

    subgraph API_Layer["📡 API REST"]
        API1[auth]
        API2[examens]
        API3[passages]
        API4[corrections]
        API5[analytics]
        API6[backups]
        API7[banque]
        API8[comptes]
    end

    subgraph Business["💼 MÉTIER - Managers"]
        M1[Auth, Session, CSRF]
        M2[ExamenManager]
        M3[PassageManager]
        M4[AnalyticsManager]
        M5[BackupManager]
        M6[HealthChecker]
        M7[RateLimiter, Mailer, Logger]
    end

    subgraph Persistence["💾 PERSISTANCE"]
        FS[FileStorage]
        D[Data: examens, passages,<br/>comptes, banque, backups,<br/>sessions, logs]
    end

    Presentation -->|HTTPS| Routing
    Routing --> API_Layer
    API_Layer --> Business
    Business --> Persistence
    FS --> D

    classDef pres fill:#dbeafe,stroke:#2563eb
    classDef route fill:#fae8ff,stroke:#a855f7
    classDef api fill:#fed7aa,stroke:#f97316
    classDef biz fill:#fef3c7,stroke:#f59e0b
    classDef data fill:#dcfce7,stroke:#16a34a

    class Presentation,PA,PB,PC pres
    class Routing,RA,RB,RC,RD route
    class API_Layer,API1,API2,API3,API4,API5,API6,API7,API8 api
    class Business,M1,M2,M3,M4,M5,M6,M7 biz
    class Persistence,FS,D data
```

---

## 9. Pipeline CI/CD (Mermaid)

**Rendu** : https://mermaid.live/

```mermaid
flowchart TB
    Trigger{{Push / PR sur main}}

    Trigger --> W1[Workflow tests.yml]
    Trigger --> W2[Workflow lint.yml]

    subgraph tests["🧪 tests.yml (3-5 min)"]
        direction TB
        subgraph matrix["Matrix PHP 8.2 + 8.3"]
            T1[Checkout]
            T2[Setup PHP + extensions]
            T3[Préparer data/]
            T4[Syntaxe PHP]
            T5[Harness complet<br/>389 tests]
            T6[Test backup.sh]
            T7[Test /health endpoint]
            T8[Cleanup]
            T1 --> T2 --> T3 --> T4 --> T5 --> T6 --> T7 --> T8
        end

        subgraph frontend["Frontend Lint"]
            F1[Setup Node 20]
            F2[Install Babel]
            F3[Parse tous les .jsx]
            F1 --> F2 --> F3
        end
    end

    subgraph lint["⚡ lint.yml (< 1 min)"]
        direction TB
        L1[php-syntax]
        L2[markdown-check]
        L3[structure-check]
    end

    W1 --> tests
    W2 --> lint

    tests --> R1{Tous PASS ?}
    lint --> R2{Tous PASS ?}

    R1 -->|✅ Oui| OK[✅ Badges verts]
    R1 -->|❌ Non| FAIL[❌ Badge rouge<br/>Notification]
    R2 -->|✅ Oui| OK
    R2 -->|❌ Non| FAIL

    OK --> Commit[Commit accepté sur main]
    FAIL --> Rerun[Re-run possible<br/>via GitHub UI]

    classDef triggerStyle fill:#c7d2fe,stroke:#4f46e5
    classDef workflowStyle fill:#fed7aa,stroke:#ea580c
    classDef jobStyle fill:#fef3c7,stroke:#d97706
    classDef successStyle fill:#bbf7d0,stroke:#16a34a
    classDef failStyle fill:#fecaca,stroke:#dc2626

    class Trigger triggerStyle
    class W1,W2 workflowStyle
    class T1,T2,T3,T4,T5,T6,T7,T8,F1,F2,F3,L1,L2,L3 jobStyle
    class OK,Commit successStyle
    class FAIL,Rerun failStyle
```

---

## 10. Diagramme de classes simplifié (PlantUML)

**Rendu** : http://www.plantuml.com/plantuml/

```plantuml
@startuml
title IPSSI Examens - Class Diagram (Simplifié)
skinparam classAttributeIconSize 0

package "Security" {
  class Auth {
    - sessionUser: ?array
    + login(email, password): bool
    + logout(): void
    + isLoggedIn(): bool
    + requireRole(role): void
  }

  class Csrf {
    + {static} generate(): string
    + {static} validate(token): bool
    + {static} requireValid(): void
  }

  class Session {
    + {static} start(): void
    + {static} get(key): mixed
    + {static} set(key, value): void
  }

  class RoleRateLimiter {
    + check(role, id): array
    + headers(check): array
    + reset(role, id): void
  }
}

package "Managers" {
  class BanqueManager {
    + getAll(): array
    + getById(id): ?array
    + create(data): array
    + update(id, data): bool
  }

  class ExamenManager {
    + create(data, profId): array
    + getById(id): ?array
    + getByAccessCode(code): ?array
    + publish(id): bool
    + close(id): bool
  }

  class PassageManager {
    + start(examenId, info): array
    + saveAnswer(token, qid, idx): bool
    + logFocusEvent(token, event): void
    + submit(token): array
    - computeSignature(passage): string
  }

  class AnalyticsManager {
    + getExamenOverview(id): array
    + getScoresDistribution(id): array
    + getQuestionsStats(id): array
    + getFocusHeatmap(id): array
  }

  class BackupManager {
    + list(): array
    + createBackup(keep): array
    + verify(id): array
    + delete(id): bool
  }

  class HealthChecker {
    + checkAll(): array
    + checkDisk(): array
    + checkMemory(): array
    + checkBackups(): array
  }
}

package "Infrastructure" {
  class FileStorage {
    + read(path): ?array
    + write(path, data): bool
    + delete(path): bool
    + glob(pattern): array
  }

  class Logger {
    - logFile: string
    - channel: string
    + info(msg, context): void
    + error(msg, context): void
  }

  class Mailer {
    + send(to, subject, html): bool
    + sendWithTemplate(to, subj, tpl, vars): bool
  }

  class EmailTemplate {
    + {static} etudiant_submission(data): string
    + {static} reset_password(data): string
  }
}

Auth --> Session : uses
BanqueManager ..> FileStorage
BanqueManager ..> Logger
ExamenManager ..> FileStorage
ExamenManager ..> Logger
PassageManager ..> FileStorage
PassageManager ..> Logger
PassageManager ..> Mailer
PassageManager ..> ExamenManager : validates
AnalyticsManager ..> PassageManager
AnalyticsManager ..> ExamenManager
BackupManager ..> FileStorage
BackupManager ..> Logger
HealthChecker ..> FileStorage
Mailer ..> EmailTemplate

note right of PassageManager
  Signature HMAC SHA-256
  avec SECRET_SALT
end note

note right of RoleRateLimiter
  admin: illimité
  enseignant: 500/min
  étudiant: 60/min
  anonyme: 30/min
end note

@enduml
```

---

## 🔧 Comment utiliser ces exemples

### Méthode 1 — Rendu en ligne (le plus simple)

1. **Mermaid** :
   - Ouvrir https://mermaid.live/
   - Copier le code Mermaid (entre les ```` ```mermaid ```` et ```` ``` ````)
   - Coller dans l'éditeur de gauche
   - Voir le rendu à droite
   - Export PNG/SVG via le bouton "Actions" en haut

2. **PlantUML** :
   - Ouvrir http://www.plantuml.com/plantuml/
   - Copier le code (entre `@startuml` et `@enduml`)
   - Coller dans l'éditeur
   - Le rendu apparaît à droite
   - URL permanente partageable

### Méthode 2 — Dans votre repo GitHub

GitHub **rend nativement** les diagrammes Mermaid dans les fichiers `.md` !

Exemple dans votre `README.md` :
````markdown
## Architecture

```mermaid
flowchart LR
    A --> B
```
````

### Méthode 3 — Dans VS Code

Installer les extensions :
- **Mermaid Preview** (par Matt Bierner)
- **PlantUML** (par jebbs)

Puis :
- Ouvrir un fichier `.md` avec code Mermaid → voir le preview en temps réel
- Pour PlantUML : créer un `.puml`, Alt+D pour preview

### Méthode 4 — Export images

Pour intégrer dans des slides ou PDF :

**Mermaid** :
- https://mermaid.live/ → bouton "Actions" → "Download PNG" ou "Download SVG"

**PlantUML** :
- http://www.plantuml.com/plantuml/ → le lien URL contient le diagramme encodé
- Clic droit sur l'image → "Enregistrer l'image"

---

## 📚 Utilisation recommandée

Chaque exemple ci-dessus correspond à **un prompt spécifique** du fichier
`PROMPTS_CHATGPT.md`. Vous pouvez :

1. **Utiliser directement** ces exemples tels quels (les modifier pour votre contexte)
2. **Régénérer via ChatGPT** avec les prompts pour des variantes personnalisées
3. **Mixer** : commencer par ces exemples et les enrichir avec ChatGPT

---

## 🎯 Ressources

- **Mermaid Live Editor** : https://mermaid.live/
- **PlantUML Online** : http://www.plantuml.com/plantuml/
- **Draw.io / diagrams.net** : https://app.diagrams.net/ (pour des schémas complexes)
- **Excalidraw** : https://excalidraw.com/ (style dessin à main levée, import Mermaid)

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
