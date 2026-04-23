# 📋 Changelog

Tous les changements notables de la plateforme d'examens IPSSI sont documentés ici.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

---

## [Non publié]

### À venir
- Phase P3 : Banque de questions (CRUD + Import/Export)

---

## [0.2.0] — 2026-04-21

### ✨ Ajouté (Phase P2 — Design system + Frontend commun)

**P2.1 — Tokens & CSS fondations**
- `frontend/assets/tokens.css` — Variables CSS (palette bleu 50-950, 11 nuances neutres, sémantiques, 6 Box pédagogiques, 4 difficultés, typo Inter+Manrope+JetBrains Mono, espacements 4px base, rayons, ombres, z-index, containers)
- `frontend/assets/fonts.css` — Import Google Fonts (Inter 300-800, Manrope 200-800, JetBrains Mono 400-700, font-display: swap)
- `frontend/assets/reset.css` — Normalize moderne + focus-visible + scrollbars stylées + `.sr-only`
- `frontend/assets/theme.css` — Mode auto, accessibilité (PPAS : dyslexic/contrast/size/motion)
- `frontend/assets/animations.css` — 14 keyframes + 12 classes utilitaires (fadeIn, scaleIn, pulse, shake, spin, bounce, etc.)
- `frontend/assets/main.css` — Point d'entrée avec imports + helpers utilitaires + skip-link a11y
- `frontend/commun/demo_tokens.html` — Page de démo interactive (couleurs, typo, espacements, ombres, animations)

**P2.2 — Composants React de base (17 composants sur `window.UI`)**
- `frontend/assets/components-base.js`
- **Form (6)** : Button (6 variants × 3 sizes, loading, disabled, icons), Input (focus ring, error, hint, icon), Textarea, Select (chevron SVG), Checkbox, Radio
- **Layout (4)** : Card (title/subtitle/footer/hoverable), Box (6 types pédagogiques : définition/retenir/attention/intuition/exemple/python), Badge (5 variants × 3 sizes), Avatar (initiales auto, couleur hash)
- **Feedback (5)** : Modal (4 sizes, closeOnOverlay/Esc, focus trap body overflow), Tooltip (4 positions), Toast/ToastProvider/useToast (4 types, auto-dismiss, Context React), Spinner, Skeleton
- **Misc (1)** : ProgressBar (label, color, showValue %)
- `frontend/commun/demo_components.html` — Démo interactive des 17 composants

**P2.3 — Composants React avancés (11 composants, window.UI étendu)**
- `frontend/assets/components-advanced.js`
- **Math/Code (2)** : KatexMath (lazy load KaTeX CDN, inline/display), CodeBlock (header langue, copie clipboard + fallback execCommand, line numbers)
- **Time (1)** : ChronoDisplay (countdown/countup, alertes seuillées, onExpire, mode compact)
- **Theme (1)** : ThemeToggle (sync localStorage + data-theme, 3 sizes)
- **Data (4)** : DataTable (tri, recherche full-text, pagination, onRowClick, columns custom avec render), Pagination (algorithme 1…[6]…20), Tabs (ARIA + navigation clavier + badges), Accordion (single/multi-open)
- **Feedback (2)** : EmptyState (icon + title + message + action), Stat (KPI avec tendance, icon coloré)
- **Forms (1)** : Wizard (stepper visuel, validation par étape, navigation)
- `frontend/commun/demo_advanced.html` — Démo des 11 composants avancés

**P2.4 — Hooks + i18n + Layouts + Refontes**
- `frontend/assets/hooks.js` — **8 hooks custom sur `window.UIHooks`** :
  - `useTheme` (theme + accessibility PPAS + sync localStorage)
  - `useTranslation` (i18n avec cache singleton, fallback FR, substitution `{var}`)
  - `useApi` (wrapper fetch standardisé, CSRF auto sur méthodes mutantes)
  - `useAuth` (singleton _authState + listeners, login/logout/user/isAdmin)
  - `useDebounce` (valeur debouncée configurable)
  - `useLocalStorage` (state synchronisé, remove helper)
  - `useKeyboardShortcut` (parseShortcut, cmd+k compatible Mac/Win/Linux)
  - `useModal` (isOpen/open/close/toggle)
- `frontend/assets/i18n/fr.json` — **231 clés françaises** organisées en namespaces (app, common, auth, user, nav, exam, exam.create, rules, anticheat, correction, banque, ia, stats, rgpd, settings, errors, footer, phase)
- `frontend/assets/layouts.js` — **4 layouts sur `window.UILayouts`** :
  - `PublicLayout` (centré, maxWidth configurable, ThemeToggle fixe, footer)
  - `ProfLayout` (sidebar 260px/72px collapsable, mobile drawer avec overlay, header sticky avec titre+breadcrumbs+actions+user menu dropdown, items adaptés au rôle, détection route active)
  - `StudentLayout` (immersif examen : pas de sidebar, header minimal titre+chrono+progression, footer état)
  - `AdminLayout` (alias ProfLayout avec menus étendus : audit logs, sauvegardes, config)
- `frontend/commun/login.html` — **Refonte React complète** utilisant PublicLayout + useAuth + useTranslation + useToast, validation client (email regex + mdp ≥ 8), redirect auto si déjà connecté, branding + badge version, legacy sauvegardée
- `frontend/commun/dashboard_temp.html` — **Refonte React** avec ProfLayout + Stat cards "à venir" + cartes 6 phases + liens démos, legacy sauvegardée
- `frontend/commun/demo_p2_complete.html` — Démo combinée de tous les hooks + 3 layouts (public/prof/student)

### 🏗️ Architecture frontend (sans bundler)

- React 18 via CDN unpkg (production build)
- Babel standalone pour transpilation in-browser (JSX + modern JS)
- Pattern UMD-like : tous les modules exposent via `window.UI`, `window.UIHooks`, `window.UILayouts`
- Aucune dépendance npm, aucun build step
- Compatible OVH mutualisé (serveur static files + PHP)

### 🧪 Tests HTTP effectués

- **13/13 fichiers P2 servis correctement** (HTTP 200) : 6 CSS, 4 JS + 1 JSON i18n + 2 HTML (login + dashboard)
- MIME types corrects (text/css, application/javascript, application/json, text/html)
- Cache-Control: public, max-age=3600
- Path traversal bloqué sur `/assets/*`

### 📊 Statistiques Phase P2

- **Fichiers créés** : 13 nouveaux fichiers (6 CSS + 4 JS + 1 JSON + 5 HTML dont 2 refontes)
- **Lignes de code** : ~5 700 lignes
- **Composants React totaux** : **28** (17 base + 11 avancés)
- **Hooks custom** : **8**
- **Layouts** : **4**
- **Traductions FR** : **231 clés**
- **Commits Phase P2** : 4 (P2.1 → P2.4)

### 🎨 Design tokens utilisés

- Couleur primaire : **bleu** `#3b82f6` (validée en début de P2)
- Typographie : Inter (UI), Manrope (titres), JetBrains Mono (code)
- 8-pt grid pour espacements
- Ratios d'échelle modulaire 1.2 pour typographie
- Mode clair par défaut + sombre via `data-theme="dark"` sur `<html>`

---

## [0.1.0] — 2026-04-21

### ✨ Ajouté (Phase P1 — Fondations backend)

**Architecture backend (P1.1)**
- `backend/config.sample.php` — Template de configuration exhaustif (app, paths, security, logging, email, IA, backup, RGPD, dev)
- `backend/bootstrap.php` — Chargement initial : autoloader PSR-4, config, timezone, error handling, configuration session sécurisée
- `backend/lib/FileStorage.php` — Lecture/écriture JSON atomique avec `flock()` (LOCK_SH lecture, LOCK_EX écriture, écriture via fichier temp + rename atomique)
- `backend/lib/Logger.php` — Logs JSONL avec rotation par jour, 4 niveaux (debug/info/warning/error), channels multiples
- `backend/lib/Response.php` — Helpers JSON API standardisés (`{ok:true, data}` / `{ok:false, error:{code,message,details}}`), 8 méthodes de raccourci HTTP
- `backend/lib/Validator.php` — Validation fluent (required, email, minLength, maxLength, in, matches, boolean, sanitize)
- `backend/lib/Utils.php` — Helpers : codes examen (alphabet sans I/O/0/1), tokens, normalisation noms/emails, formatage durée, **chiffrement AES-256-GCM** (clés API IA), signatures SHA-256
- `backend/public/index.php` — Front controller avec routing : `/api/{endpoint}` → délégation, `/health`, pages HTML frontend, page d'accueil placeholder
- `backend/public/.htaccess` — Apache mod_rewrite, headers sécurité (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy), blocage fichiers sensibles, cache assets statiques, gzip
- `backend/.htaccess` — Protection totale du dossier (Require all denied, seul `public/` exposé)

**Authentification & sécurité (P1.2)**
- `backend/lib/Session.php` — Sessions PHP sécurisées : démarrage paresseux, régénération périodique de l'ID (anti-fixation, défaut 5 min), fingerprint (UA + 2 octets IP) anti-hijacking, helpers get/set/has/delete, flash one-shot
- `backend/lib/Csrf.php` — Tokens CSRF : génération + vérification timing-safe (`hash_equals`), TTL configurable (défaut 2h), récupération auto depuis header `X-CSRF-Token` / body JSON `_csrf` / POST `_csrf`
- `backend/lib/RateLimiter.php` — Rate limiting fichier (sliding window, sans Redis ni DB, compatible OVH mutualisé), bucket configurable (login, api_ia, submit), méthodes attempt/record/remaining/retryAfter/isBlocked/reset, cleanup automatique
- `backend/lib/Auth.php` — Authentification + CRUD comptes enseignants : login (bcrypt + re-hash auto si cost change), logout, 2 rôles (admin/enseignant), middleware `requireAuth()` (401) et `requireAdmin()` (403), CRUD complet avec garde-fous (anti-doublon, anti-suppression dernier admin)

**API REST (P1.3)**
- `backend/api/health.php` — `GET /api/health` : monitoring (PHP version, écriture data/, extensions OpenSSL/JSON/mbstring), HTTP 200 ou 503
- `backend/api/auth.php` — Endpoints d'authentification :
  - `GET /api/auth/csrf-token` — Récupère le token CSRF courant
  - `GET /api/auth/me` — Compte courant ou `{authenticated: false}`
  - `POST /api/auth/login` — Login + rate limiting par IP (5 tentatives / 15 min)
  - `POST /api/auth/logout` — Déconnexion + destruction session
  - `POST /api/auth/change-password` — Changement de mot de passe (CSRF + ancien mdp requis)
- `backend/api/comptes.php` — CRUD comptes (admin only sauf consultation soi-même) :
  - `GET /api/comptes` — Liste tous les comptes (admin)
  - `POST /api/comptes` — Créer un compte (admin + CSRF + Validator)
  - `GET /api/comptes/{id}` — Détail (admin OU soi-même)
  - `PUT /api/comptes/{id}` — Update (admin OU soi-même limité, garde-fous anti-suicide)
  - `DELETE /api/comptes/{id}` — Désactiver (soft delete, admin)
  - `POST /api/comptes/{id}/enable` — Réactiver (admin)
  - `POST /api/comptes/{id}/destroy` — Suppression définitive (admin)

**Frontend & Scripts CLI (P1.4)**
- `scripts/init_comptes.php` — Script CLI interactif pour créer le 1er admin (vérification doublon, prompts colorisés ANSI, mot de passe sans écho terminal, confirmation explicite)
- `scripts/reset_password.php` — Reset password en CLI (par email, vérification existence, prompt nouveau mdp avec confirmation)
- `frontend/commun/login.html` — Page de connexion stylée (vanilla JS, fetch API, pas de dépendance externe, dark mode auto)
- `frontend/commun/dashboard_temp.html` — Dashboard temporaire P1 (cards "à venir", logout, vérification auth)
- `frontend/commun/404.html` — Page d'erreur 404
- `frontend/commun/500.html` — Page d'erreur 500
- Routing dans `index.php` étendu pour servir les pages HTML frontend depuis `/`

### 🧪 Tests effectués

**Tests unitaires PHP (CLI)**
- P1.1 : 8/8 tests passés (config, helpers, autoloader, Utils, FileStorage, Validator, chiffrement AES-256-GCM round-trip)
- P1.2 : 11/11 tests passés (création admin, refus doublon, refus mdp court, password_verify, création enseignant, disable/enable, change password, listComptes, RateLimiter, deleteCompte)

**Tests fonctionnels HTTP (curl)**
- P1.3 : 13/13 tests passés (health, csrf-token, me, login mauvais/bon mdp, comptes CRUD avec/sans CSRF, logout, parcours complet)
- P1.4 : Tests parcours complet (login → dashboard → logout) + sécurité path traversal OK

### 🔒 Sécurité implémentée

- Bcrypt cost 12 (configurable) + re-hash auto à la connexion si le cost change
- Sessions HttpOnly + SameSite=Strict + régénération périodique d'ID
- Fingerprint session (UA + 2 octets IP) — anti session hijacking
- CSRF tokens avec `hash_equals` (timing-safe)
- Rate limiting login (5 tentatives / 15 min par IP, sliding window fichier)
- Path traversal bloqué (realpath + str_starts_with check)
- Headers sécurité Apache (.htaccess) : X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy
- Chiffrement AES-256-GCM pour les futures clés API IA (Phase P4)
- Garde-fous anti-suicide : impossible de désactiver/supprimer son propre compte ou le dernier admin actif

### 📊 Statistiques Phase P1

- **Fichiers PHP créés** : 14 (4 lib P1.1 + 1 Utils + 4 lib P1.2 + 3 api + 2 scripts CLI)
- **Fichiers HTML créés** : 4 (login, dashboard_temp, 404, 500)
- **Lignes de code total** : ~3 200 lignes
- **Commits Phase P1** : 5 (P1.1×2, P1.2, P1.3, P1.4 à venir)
- **Tests passés** : 32/32 (8 + 11 + 13 + parcours P1.4)
- **Durée** : ~6h de travail effectif

---

## [0.0.1] — 2026-04-21

### ✨ Ajouté (Phase P0 — Cadrage)

- Structure initiale du projet `examens/` dans le repo `maths_IA_niveau_1`
- `README.md` — Vue d'ensemble complète de la plateforme
- `NOTE_CADRAGE.md` — Cadrage détaillé des 20 décisions structurantes
- `ROADMAP.md` — Plan de livraison en 9 phases
- `CONVENTIONS.md` — Conventions code, commit, branches
- `CHANGELOG.md` — Ce fichier
- `LICENSE` — CC BY-NC-SA 4.0
- `.gitignore` — Exclusions Git
- Structure des sous-dossiers :
  - `backend/` (api/, lib/, public/)
  - `frontend/` (assets/, enseignant/, etudiant/, commun/)
  - `data/` (banque/, examens/, comptes/, config/)
  - `scripts/`
  - `tests/` (backend/, e2e/, fixtures/)
  - `scenarios_tests/`
  - `docs/`
  - `.github/` (ISSUE_TEMPLATE/, workflows/)
- Templates GitHub : Issues, Pull Requests, CI workflow placeholder
- Placeholders des guides dans `docs/` :
  - `GUIDE_UTILISATION_PROF.md`
  - `GUIDE_UTILISATION_ETUDIANT.md`
  - `GUIDE_DEPLOIEMENT_OVH.md`
  - `GUIDE_MIGRATION_V2.md`

### 🎯 Décisions de cadrage (20/20 validées)

1. **Hébergement** : OVH mutualisé + fichiers CSV/JSON
2. **Banque de questions** : un JSON par chapitre
3. **Code d'examen** : hybride `IPSSI-B2-2026-A4F7`
4. **Authentification prof** : comptes multiples avec rôles
5. **Authentification étudiant** : 1 email = 1 tentative + filtres
6. **Création d'examen** : formulaire COMPLET + sélection hybride
7. **Chronomètre** : dateDebut serveur + affichage local
8. **Stockage passages** : CSV + metadata + index + audit
9. **Correction étudiant** : paramétrable par examen
10. **Historique prof** : analyse approfondie (3 niveaux)
11. **Migration QCM J1-J2** : import + amélioration qualitative
12. **Gestion banque** : CRUD + Import/Export + **Génération IA**
    - 12a : Claude + OpenAI (choix par enseignant)
    - 12b : clé API par enseignant
13. **Notifications email** : prof + étudiant complet
14. **Design visuel** : refonte complète design system pro
15. **Accessibilité** : WCAG AA + PPAS + **Focus-Lock anti-triche**
16. **RGPD** : équilibré (traçabilité + droits + rétention)
17. **Sauvegarde** : quotidien OVH + hebdo GitHub privé
18. **Langues** : FR + architecture i18n extensible
19. **Testing** : PHPUnit + Playwright + CI/CD GitHub
20. **Déploiement** : soft launch (test → pilote → production)

### 📊 Statistiques Phase P0

- **Documents produits** : 6 documents Markdown principaux (~2400 lignes total)
- **Commits** : 2 (à ce stade, sera mis à jour en fin de P0)
- **Durée** : 3h de travail

---

## Format des entrées futures

Chaque version suit cette structure :

```markdown
## [X.Y.Z] — YYYY-MM-DD

### ✨ Ajouté
- Nouvelles fonctionnalités

### 🔄 Modifié
- Changements dans les fonctionnalités existantes

### ⚠️ Déprécié
- Fonctionnalités qui seront supprimées

### 🗑️ Supprimé
- Fonctionnalités supprimées

### 🐛 Corrigé
- Corrections de bugs

### 🔒 Sécurité
- Corrections de vulnérabilités
```

---

## Versions prévues

| Version | Phase | Statut | Date prévue |
|:-:|---|:-:|:-:|
| 0.0.1 | P0 — Cadrage | ✅ Livré | 2026-04-21 |
| 0.1.0 | P1 — Fondations backend | ✅ Livré | 2026-04-21 |
| 0.2.0 | P2 — Design system | ✅ **Livré** | 2026-04-21 |
| 0.3.0 | P3 — Banque de questions | 🔴 À venir | +2 sem |
| 0.4.0 | P4 — IA + Migration J1-J2 | 🔴 | +3 sem |
| 0.5.0 | P5 — Création examen + Passage + Focus-lock | 🔴 | +5-6 sem |
| 0.6.0 | P6 — Correction + Emails | 🔴 | +7 sem |
| 0.7.0 | P7 — Historique + Analytics | 🔴 | +9 sem |
| 0.8.0 | P8 — Tests + CI/CD + Backups | 🔴 | +11 sem |
| 0.9.0 | P9 — Documentation + Soft launch prep | 🔴 | +12 sem |
| 1.0.0 | Production stable (post soft launch) | 🔴 | ~Juillet 2026 |

---

*© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0*
