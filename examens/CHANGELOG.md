# 📋 Changelog

Tous les changements notables de la plateforme d'examens IPSSI sont documentés ici.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

---

## [Non publié]

### À venir
- Phase P1 : Fondations backend (auth, comptes, API base)

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
| 0.0.1 | P0 — Cadrage | ✅ **Actuelle** | 2026-04-21 |
| 0.1.0 | P1 — Fondations backend | 🔴 À venir | +1-2 sem |
| 0.2.0 | P2 — Design system | 🔴 | +3-4 sem |
| 0.3.0 | P3 — Banque de questions | 🔴 | +5 sem |
| 0.4.0 | P4 — IA + Migration J1-J2 | 🔴 | +6 sem |
| 0.5.0 | P5 — Création examen + Passage + Focus-lock | 🔴 | +8-9 sem |
| 0.6.0 | P6 — Correction + Emails | 🔴 | +10 sem |
| 0.7.0 | P7 — Historique + Analytics | 🔴 | +12 sem |
| 0.8.0 | P8 — Tests + CI/CD + Backups | 🔴 | +14 sem |
| 0.9.0 | P9 — Documentation + Soft launch prep | 🔴 | +15 sem |
| 1.0.0 | Production stable (post soft launch) | 🔴 | ~Juillet 2026 |

---

*© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0*
