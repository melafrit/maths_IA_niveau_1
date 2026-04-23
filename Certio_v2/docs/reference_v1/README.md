# 📚 Documentation de référence v1

> **Documentation technique de Certio v1 conservée pour référence**  
> **Utile pour comprendre la logique métier et les décisions de design**

---

## 🎯 Pourquoi conserver cette documentation ?

Certio v1 était une **plateforme fonctionnelle en production** (PHP natif + React CDN). Bien que le code soit remplacé par Laravel en v2, la **logique métier** et les **règles de gestion** restent valides et doivent être respectées en v2.

Cette documentation sert de **référence pour le développement v2** :
- Comprendre les endpoints API existants
- Respecter les règles de gestion (workflow, validations)
- S'inspirer de l'architecture éprouvée
- Ne pas "réinventer la roue" pour la logique métier

---

## 📖 Documents inclus

### 🏗️ Architecture

#### `ARCHITECTURE.md` (29 KB)
Architecture globale de la plateforme v1 :
- Vue d'ensemble des composants
- Flux de données
- Décisions techniques
- Patterns utilisés

**À consulter en Phase P0 (Bootstrap) et P1 (Migration)**

---

### 🔌 APIs REST v1

#### `API_EXAMENS.md` (8 KB)
Documentation de l'API examens :
- Endpoints : GET, POST, PUT, DELETE /api/examens
- Payload attendus
- Codes de retour
- Exemples curl

#### `API_BANQUE.md` (10 KB)
Documentation de l'API banque de questions :
- CRUD questions
- Recherche full-text
- Tirage aléatoire
- Import/Export

#### `API_PASSAGES.md` (10 KB)
Documentation de l'API passages étudiants :
- Création passage (par code d'accès)
- Soumission réponses
- Consultation correction

#### `API_ANALYTICS.md` (11 KB)
Documentation de l'API analytics :
- Stats par examen
- Calibration CBM
- Analyse distracteurs
- Exports CSV/XLSX

**À consulter en Phase P4 (Analytics)**

---

### 👥 Guides utilisateur

#### `GUIDE_PROFESSEUR.md` (23 KB)
Guide complet enseignant :
- Création de compte
- Gestion banque de questions
- Création d'examen (20 paramètres)
- Distribution (codes d'accès)
- Suivi temps réel
- Analyse post-examen

**Utile pour** : comprendre les workflows utilisateur, reprendre les écrans/wordings en v2

#### `GUIDE_ETUDIANT.md` (19 KB)
Guide étudiant :
- Accès par code (IPSSI-B2-2026-A4F7)
- Passage d'examen
- Anti-triche focus-lock
- Consultation correction

#### `GUIDE_ADMIN.md` (20 KB)
Guide admin/super-admin :
- Gestion comptes enseignants
- Configuration système
- Backups + restauration
- Monitoring
- Audit logs

---

## 🎯 Comment utiliser cette documentation ?

### Pour l'équipe dev

**⚠️ Ne PAS** copier le code PHP/React de v1. C'est une référence **conceptuelle**, pas technique.

**✅ DOIT** :
1. Lire ARCHITECTURE.md avant Phase P0
2. S'inspirer des endpoints API v1 pour designer les endpoints Laravel
3. Respecter les règles de gestion documentées
4. Reprendre les wordings utilisateur (français naturel)

### Pour la migration

**En Phase P1** :
- Lire les structures de données v1 (dans fixtures)
- Respecter le mapping v1 → v2 documenté
- Vérifier que tous les champs essentiels sont migrés

### Pour la QA

**En Phase P7** :
- Vérifier que les fonctionnalités v1 fonctionnent en v2
- Comparer les workflows avec les guides
- S'assurer de la **régression zéro**

---

## 🚨 Règles critiques à respecter en v2

### 1. Codes d'examens

Format v1 : `IPSSI-B2-2026-A4F7`
- Préfixe parlant (école + promo + année)
- Suffixe aléatoire 4 chars (anti-brute-force)
- Alphabet sans I/O/0/1 (anti-confusion)

**En v2** : conserver ce format ou proposer variante.

### 2. IDs des entités

Format v1 : `PREFIX-XXXX-YYYY`
- Examens : `EXM-XXXX-YYYY`
- Passages : `PSG-XXXX-YYYY`
- Comptes : `USR-XXXX-YYYY`

**En v2** : conserver + ajouter UUID Laravel auto-généré pour routes.

### 3. Shuffle déterministe

En v1, l'ordre aléatoire était **stable par passage** (même étudiant → même ordre).

**En v2** : implémenté via `RandomizationService` avec seed `crc32(passage.uuid + email)`.

### 4. Anti-triche

V1 avait :
- Focus-lock (plein écran forcé)
- Détection sortie de page
- Randomisation questions + options

**En v2** : amélioré avec lockdown complet (voir livrable 08).

### 5. Correction paramétrable

V1 : `show_correction_after` (boolean)
**V2** : `correction_visibility` enum (4 modes) - plus flexible.

---

## 🔄 Différences v1 ↔ v2 importantes

### Changements structurels

| Aspect | v1 (PHP natif) | v2 (Laravel) |
|---|---|---|
| Stockage | JSON files | SQLite (BDD relationnelle) |
| Auth | Sessions PHP natives | Laravel Fortify |
| Queries | Code custom | Eloquent ORM |
| Validation | Validator custom | FormRequest Laravel |
| Routes | `public/index.php` custom | Laravel routing |
| Frontend | React CDN + Babel | Vue 3 + Inertia |
| Tests | PHPUnit custom | Pest PHP |

### Nouveautés v2

- ✨ CBM 100% paramétrable (nouveau)
- ✨ 7 types de questions (v1 = 4 options unique choice seulement)
- ✨ Multi-tenant workspaces (v1 = mono-tenant)
- ✨ SSO Google/Microsoft (v1 = comptes locaux seulement)
- ✨ Dashboard étudiant (v1 = accès par code)
- ✨ Banque communautaire
- ✨ Lockdown anti-triche avancé

### Conservé de v1

- 🔒 Format des codes d'accès
- 🔒 Logique de shuffle déterministe
- 🔒 Structure des questions (hint, explanation, traps)
- 🔒 Workflow prof (création → distribution → analytics)
- 🔒 Wordings français
- 🔒 Branding IPSSI

---

## 📜 Mention légale

© 2026 Mohamed EL AFRIT — IPSSI  
Licence CC BY-NC-SA 4.0

Cette documentation de référence v1 est conservée pour aider au développement v2 et assurer la continuité pédagogique pour les utilisateurs.
