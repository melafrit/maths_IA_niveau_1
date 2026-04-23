# 🔄 Prompt 10 — Diagramme de séquence UML

## 📖 Description et contexte

Ce prompt génère **2 diagrammes de séquence UML** montrant les interactions temporelles entre composants pour les 2 workflows clés : **création d'examen par un prof** et **passage d'un étudiant**.

### Ce qui est généré
- **Workflow 1** : Création + publication d'un examen (prof)
- **Workflow 2** : Passage complet d'un étudiant (accès → soumission → email)
- Messages synchrones et asynchrones
- Activations (lifelines)
- Loops, alt, opt pour les flux alternatifs
- Notes et annotations de timing

### Quand utiliser
- Documentation **détaillée d'un workflow**
- **Debug** d'un flux complexe
- **Onboarding** : comprendre les interactions entre managers
- Tests E2E : vérifier que tous les appels sont corrects

### Outil recommandé
**Mermaid sequenceDiagram** (syntaxe très lisible).

---

## 🤖 Outils IA supportés

| Outil | Qualité | Notes |
|---|:-:|---|
| **ChatGPT-4 / GPT-4o** | ⭐⭐⭐⭐⭐ | Excellent pour sequenceDiagram |
| **Claude Opus 4** | ⭐⭐⭐⭐⭐ | Détails et activations parfaits |
| **Claude 3.5 Sonnet** | ⭐⭐⭐⭐⭐ | Très bon |
| **Gemini 2.0 Pro** | ⭐⭐⭐⭐ | Correct |

---

## 📋 Version pour ChatGPT-4 / GPT-4o

```
Tu es un expert UML en modélisation comportementale.

CONTEXTE :
Je veux documenter deux workflows clés de IPSSI Examens avec des diagrammes de séquence UML.

WORKFLOW 1 — CRÉATION D'UN EXAMEN PAR UN PROF :

Acteurs : Prof, Browser (React), Router (index.php), RoleRateLimiter, Auth, Csrf, ExamenManager, FileStorage, Logger, Response

Scénario (créer puis publier) :
1. Prof clique "+ Nouvel examen" dans React app
2. Browser fait GET /api/banque pour lister questions disponibles
3. Prof remplit le formulaire (titre, durée, questions sélectionnées, dates)
4. Browser fait POST /api/examens avec body JSON + X-CSRF-Token
5. Router reçoit la requête
6. Router → RoleRateLimiter.check("enseignant", "user:prof1@ipssi.fr")
   - Retourne {allowed: true, limit: 500, remaining: 499}
   - Router ajoute headers X-RateLimit-*
7. Router → Auth.isLoggedIn() → true
8. Router → Auth.getCurrentUser() → {id:"PROF-123", role:"enseignant"}
9. Router dispatch vers backend/api/examens.php
10. examens.php → Csrf.requireValid() → OK
11. examens.php → ExamenManager.create(data)
12. ExamenManager valide les données
13. ExamenManager génère EXM-XXXX-YYYY + access_code (ex: "ABC23K-9P")
14. ExamenManager → FileStorage.write("examens/EXM-XXXX-YYYY.json", data)
15. FileStorage écrit le fichier
16. ExamenManager → Logger.info("Examen créé : EXM-XXXX-YYYY")
17. examens.php → Response.json({ok: true, data: examen})
18. Browser affiche "Examen créé en brouillon"

Puis publication :
19. Browser → POST /api/examens/EXM-XXXX-YYYY/publish
20. Middleware ... (rate limit, auth, CSRF)
21. examens.php → ExamenManager.publish(id)
22. ExamenManager change status draft → published
23. FileStorage.write() update
24. Response.json({ok: true, data: {access_code: "ABC23K-9P"}})
25. Browser affiche le code d'accès à partager

WORKFLOW 2 — PASSAGE D'UN ÉTUDIANT :

Acteurs : Étudiant, Browser, Router, RoleRateLimiter, PassageManager, ExamenManager, FileStorage, Logger, Mailer, SMTP

Scénario :
1. Étudiant ouvre /etudiant/passage.html et saisit code "ABC23K-9P"
2. Browser → POST /api/passages/access avec {code: "ABC23K-9P"}
3. Router vérifie rate limit anonyme (IP=1.2.3.4)
   - 30/min limit
4. Dispatch vers passages.php
5. passages.php → ExamenManager.getByAccessCode("ABC23K-9P") → retourne examen
6. Response → {ok: true, data: examen_info}
7. Browser affiche infos examen (titre, durée)
8. Étudiant saisit nom, prénom, email
9. Browser → POST /api/passages/start {examenId, studentInfo}
10. passages.php → PassageManager.start(examenId, info)
11. PassageManager vérifie max_passages (ExamenManager.get)
12. PassageManager génère PSG-WXYZ-5678 + token UUID
13. PassageManager shuffle questions + options (si config)
14. FileStorage.write("passages/PSG-WXYZ-5678.json", data)
15. Response → {ok: true, data: {token: "abc-123..."}}

Pendant l'examen (en boucle pour chaque réponse) :
16. Étudiant coche une option
17. Browser → POST /api/passages/answer {token, questionId, answerIndex}
18. PassageManager.saveAnswer(token, qId, index)
19. FileStorage update passage
20. Response → {ok: true}

Événement focus perdu (anti-triche) :
21. Browser détecte blur
22. Browser → POST /api/passages/focus-event {token, type: "blur"}
23. PassageManager.logFocusEvent(token, "blur")
24. FileStorage update passage.focus_events[]

Soumission finale :
25. Étudiant click "Soumettre"
26. Browser → POST /api/passages/submit {token}
27. PassageManager.submit(token)
28. PassageManager.computeScore(passage) → {score_brut, score_max, score_pct}
29. PassageManager.signPassage() → signature HMAC SHA-256
30. FileStorage update passage avec signature_sha256
31. PassageManager → Mailer.sendTemplate("etudiant_submission", vars)
32. Mailer → SMTP.send()
33. SMTP confirme livraison
34. Response → {ok: true, data: {score_pct: 75.5, correction_url: "..."}}
35. Browser redirige vers /etudiant/correction.html?token=...

OBJECTIF :
Génère 2 diagrammes de séquence UML complets au format Mermaid (sequenceDiagram).

SPÉCIFICATIONS :

Pour chaque diagramme :
- Utiliser sequenceDiagram de Mermaid
- Tous les acteurs définis en haut avec participant/actor
- Messages synchrones avec -->> ou -->
- Messages de retour avec pointillés --x
- Notes pour les points importants (note right of, note over)
- Boucles (loop) pour les actions répétées (saveAnswer)
- Alt/opt pour les branches conditionnelles

POUR LE WORKFLOW 1 :
- Inclure les 3 middlewares (rate limit, auth, CSRF) clairement
- Montrer les 2 sous-workflows (create + publish)
- Séparation visuelle entre les deux

POUR LE WORKFLOW 2 :
- Utiliser loop pour la partie saveAnswer (N questions)
- Utiliser opt pour le focus event (optionnel)
- Montrer la création UUID token
- Show the signature HMAC step

FORMAT :
Code Mermaid sequenceDiagram, complet et lisible.

BONUS :
- Ajouter des activations (activate/deactivate) pour montrer quand chaque objet "tient" le contrôle
- Annotations de timing (ex: < 50ms, < 200ms)
- Coloration pour les acteurs externes (SMTP)

CRITÈRES :
- Complétude : tous les steps listés présents
- Clarté : ordre chronologique respecté
- Style UML correct
- Compatible mermaid.live

Génère les 2 diagrammes de séquence.
```

---

## 📋 Version pour Claude

```
<role>
Expert UML en Sequence Diagrams. Tu maîtrises :
- Messages synchrones (-->>)
- Messages asynchrones (->>)
- Returns (pointillés)
- Activations (activate/deactivate)
- Combined fragments : loop, alt, opt, par, critical
- Notes (note left/right/over)
</role>

<task>
Générer 2 diagrammes de séquence Mermaid pour IPSSI Examens :
1. Création + publication d'un examen (Prof)
2. Passage complet d'un étudiant
</task>

<workflow_1>
  <n>Création et publication d'un examen</n>
  
  <participants>
    - actor Prof
    - participant Browser (React)
    - participant Router (index.php)
    - participant RateLimit (RoleRateLimiter)
    - participant Auth
    - participant Csrf
    - participant ExamenAPI (examens.php)
    - participant ExamenMgr (ExamenManager)
    - participant FS (FileStorage)
    - participant Logger
  </participants>
  
  <phases>
    <phase name="Listage banque">
      Prof click → Browser GET /api/banque → ... → liste questions
    </phase>
    
    <phase name="Création">
      POST /api/examens →
        RateLimit.check("enseignant", "user:...") → {allowed, remaining:499}
        Auth.isLoggedIn() → true
        Auth.getCurrentUser() → {id:"PROF-123"}
        Csrf.requireValid() → OK
        ExamenMgr.create(data)
          → validate
          → generateAccessCode() → "ABC23K-9P"
          → FS.write("examens/EXM-xxx.json")
          → Logger.info()
        Response.json({ok, data})
    </phase>
    
    <phase name="Publication">
      POST /api/examens/{id}/publish → [middlewares] →
        ExamenMgr.publish(id)
          → status: draft → published
          → FS.write(update)
        Response.json({ok, access_code})
    </phase>
  </phases>
</workflow_1>

<workflow_2>
  <n>Passage étudiant complet</n>
  
  <participants>
    - actor Étudiant
    - participant Browser
    - participant Router
    - participant RateLimit
    - participant PassageAPI
    - participant PassageMgr
    - participant ExamenMgr
    - participant FS
    - participant Mailer
    - participant SMTP (external)
  </participants>
  
  <phases>
    <phase name="Accès par code">
      POST /api/passages/access {code} →
        RateLimit.check("anonyme", "ip:1.2.3.4") → {allowed, 30/min}
        ExamenMgr.getByAccessCode(code) → examen
        Response → {examen_info}
    </phase>
    
    <phase name="Démarrage">
      POST /api/passages/start {examenId, studentInfo} →
        PassageMgr.start()
          → ExamenMgr.get() : check max_passages
          → generate PSG-xxx + UUID token
          → shuffle questions (if config)
          → FS.write("passages/PSG-xxx.json")
        Response → {token}
    </phase>
    
    <phase name="Réponses (loop)">
      loop "pour chaque question répondue"
        POST /api/passages/answer {token, qId, answer} →
          PassageMgr.saveAnswer()
          FS.update()
          Response → {ok}
      end
    </phase>
    
    <phase name="Focus event (opt)">
      opt "si blur/focus perdu"
        POST /api/passages/focus-event {token, type:"blur"} →
          PassageMgr.logFocusEvent()
          FS.update(focus_events[])
      end
    </phase>
    
    <phase name="Soumission">
      POST /api/passages/submit {token} →
        PassageMgr.submit()
          → computeScore() → {score_brut, score_max, score_pct}
          → signPassage() → signature HMAC SHA-256
          → FS.update(with signature)
          → Mailer.sendTemplate("etudiant_submission")
            → SMTP.send() [external]
            → SMTP returns OK
        Response → {score_pct, correction_url}
      Browser redirects to /etudiant/correction.html
    </phase>
  </phases>
</workflow_2>

<requirements>
  <format>Mermaid sequenceDiagram</format>
  
  <style>
    - Use activate/deactivate for lifeline activations
    - Notes for key points (note over Participant : ...)
    - Timing annotations (< 50ms, < 200ms) where relevant
    - Highlight external participants (SMTP) with different color/note
  </style>
  
  <fragments>
    - loop for repeated saveAnswer
    - opt for optional focus events
    - alt for error paths (brief)
  </fragments>
</requirements>

<o>
2 complete Mermaid sequenceDiagram code blocks, each with:
- Title at top
- All participants declared
- Complete message flow
- Activations
- Notes at key steps
- Proper fragments

Also provide 1-2 lines explaining how each diagram should be read.
</o>
```

---

## 📋 Version pour Gemini Pro

```
Génère 2 diagrammes de séquence Mermaid pour IPSSI Examens.

DIAGRAMME 1 : Création examen par Prof

Participants :
- actor Prof
- Browser, Router, RateLimit, Auth, Csrf
- ExamenAPI, ExamenManager, FileStorage, Logger

Séquence :
1. Prof → Browser : click "Nouvel examen"
2. Browser → Router : GET /api/banque (liste questions)
3. Browser → Router : POST /api/examens {data, X-CSRF-Token}
4. Router → RateLimit : check("enseignant", id)
5. RateLimit → Router : {allowed, remaining:499}
6. Router → Auth : isLoggedIn() → true
7. Router → Auth : getCurrentUser() → {id, role}
8. Router → ExamenAPI : dispatch
9. ExamenAPI → Csrf : requireValid()
10. ExamenAPI → ExamenManager : create(data)
11. ExamenManager → FileStorage : write("examens/EXM-xxx.json")
12. ExamenManager → Logger : info()
13. ExamenAPI → Browser : Response {ok, examen}

Puis Publication (lignes 14-18) : POST /publish → ExamenManager.publish() → status change → FS.update() → Response

DIAGRAMME 2 : Passage étudiant

Participants :
- actor Étudiant
- Browser, Router, RateLimit
- PassageAPI, PassageManager, ExamenManager, FileStorage
- Mailer, SMTP (external)

Séquence :
1. Étudiant saisit code "ABC23K-9P"
2. Browser → POST /api/passages/access {code}
3. Router → RateLimit : check("anonyme", ip) → 30/min
4. PassageAPI → ExamenManager : getByAccessCode()
5. Response → {examen_info}
6. Étudiant saisit nom/email
7. Browser → POST /api/passages/start
8. PassageManager.start() : génère PSG-xxx + UUID token
9. FS.write passage
10. Response → {token}

loop "pour chaque réponse" :
11. POST /api/passages/answer {token, qId, idx}
12. PassageManager.saveAnswer()
13. FS update
14. Response {ok}
end loop

opt "focus event" :
15. POST /api/passages/focus-event
16. PassageManager.logFocusEvent()
end opt

Soumission :
17. Browser → POST /api/passages/submit
18. PassageManager.submit()
19. computeScore() → {score_pct}
20. signPassage() → HMAC SHA-256
21. FS update avec signature
22. Mailer.sendTemplate()
23. Mailer → SMTP : send()
24. SMTP → Mailer : OK
25. Response → {score_pct, correction_url}

RÈGLES :
- Format : sequenceDiagram Mermaid
- 2 diagrammes séparés
- activate/deactivate pour lifelines
- loop/opt pour fragments
- notes sur points clés
- SMTP en external (couleur distincte)

Génère les 2 codes complets.
```

---

## 🎨 Rendu final

### Rendu

- https://mermaid.live/
- Export SVG pour zoom

### Intégration

````markdown
## Flux de création d'examen

```mermaid
[diagramme 1]
```

## Flux de passage étudiant

```mermaid
[diagramme 2]
```
````

---

## 💡 Variations

### Version timing réel
*"Ajoute des timestamps réels : T+0ms, T+15ms, T+50ms... pour chaque étape."*

### Version avec erreurs
*"Ajoute des branches alt avec les cas d'erreur : rate limit dépassé, CSRF invalide, token expiré."*

### Version simplifiée
*"Simplifie en regroupant les middlewares en 1 participant 'Middleware Pipeline'."*

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
