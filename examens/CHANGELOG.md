# 📋 Changelog

Tous les changements notables de la plateforme d'examens IPSSI sont documentés ici.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

---

## [Non publié]

### À venir
- Phase P2 : Design system + frontend commun (composants React, tokens, i18n, layouts)

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
| 0.1.0 | P1 — Fondations backend | ✅ **Livré** | 2026-04-21 |
| 0.2.0 | P2 — Design system | 🔴 À venir | +1-2 sem |
| 0.3.0 | P3 — Banque de questions | 🔴 | +3 sem |
| 0.4.0 | P4 — IA + Migration J1-J2 | 🔴 | +4 sem |
| 0.5.0 | P5 — Création examen + Passage + Focus-lock | 🔴 | +6-7 sem |
| 0.6.0 | P6 — Correction + Emails | 🔴 | +8 sem |
| 0.7.0 | P7 — Historique + Analytics | 🔴 | +10 sem |
| 0.8.0 | P8 — Tests + CI/CD + Backups | 🔴 | +12 sem |
| 0.9.0 | P9 — Documentation + Soft launch prep | 🔴 | +13 sem |
| 1.0.0 | Production stable (post soft launch) | 🔴 | ~Juillet 2026 |

---

*© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0*
