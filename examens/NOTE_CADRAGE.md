# 📐 Note de cadrage — Plateforme d'examens IPSSI

> **Document central du projet.** Synthèse des 20 décisions structurantes
> prises lors de la phase de cadrage interactive avec l'auteur.

**Version** : 1.0
**Date** : 2026-04-21
**Auteur** : Mohamed EL AFRIT — IPSSI
**Statut** : ✅ Validé

---

## 📋 Table des matières

1. [Contexte et objectifs](#1-contexte-et-objectifs)
2. [Personas utilisateurs](#2-personas-utilisateurs)
3. [Parcours utilisateurs clés](#3-parcours-utilisateurs-clés)
4. [Les 20 décisions structurantes](#4-les-20-décisions-structurantes)
5. [Stack technique retenue](#5-stack-technique-retenue)
6. [Architecture globale](#6-architecture-globale)
7. [Fonctionnalités différenciantes](#7-fonctionnalités-différenciantes)
8. [Estimation de charge](#8-estimation-de-charge)
9. [Contraintes et hypothèses](#9-contraintes-et-hypothèses)
10. [Risques et mitigations](#10-risques-et-mitigations)

---

## 1. Contexte et objectifs

### 1.1 Contexte

Mohamed EL AFRIT, enseignant-formateur à IPSSI, a déjà développé un premier dispositif de
QCM évaluation pour son module **"Mathématiques appliquées à l'Intelligence Artificielle"**
(Bachelor 2 Informatique). Ce dispositif initial (`qcm_eval_J1_J2/`) est **standalone** :
50 questions statiques, pas de backend, CSV envoyé par email.

Le besoin a évolué vers une **vraie plateforme en ligne** multi-utilisateurs, permettant :
- La création dynamique d'examens à partir d'une banque de questions étendue
- La gestion de plusieurs promotions, années, modules
- L'automatisation complète du cycle (création → passage → correction → reporting)
- Un standard professionnel (design, tests, RGPD, accessibilité)

### 1.2 Objectifs

**Objectif 1 — Créer une plateforme d'examens moderne et professionnelle**
avec backend OVH, comptes multi-enseignants, banque de questions évolutive, génération
IA, anti-triche avancé et analytics approfondies.

**Objectif 2 — Préserver l'existant**
en migrant les 50 questions actuelles (J1-J2) dans la nouvelle banque avec amélioration
qualitative (enrichissement pédagogique, tags, corrections typos).

**Objectif 3 — Poser les bases d'une plateforme extensible**
pouvant couvrir d'autres modules (J3-J4, Machine Learning, autres matières) et
potentiellement être proposée à d'autres enseignants IPSSI.

**Objectif 4 — Garantir la qualité production**
via tests automatisés, backups, documentation et déploiement progressif (soft launch).

### 1.3 Non-objectifs (hors périmètre v1)

- ❌ Gestion de cursus complets (moyennes, bulletins, parcours) — c'est le rôle d'Yparéo
- ❌ Examens autres que QCM (rédaction, code en ligne, épreuves orales)
- ❌ Intégration SSO avec annuaire IPSSI (v2 si besoin)
- ❌ Application mobile native (responsive web suffit)
- ❌ Marketplace publique de questions entre enseignants

---

## 2. Personas utilisateurs

### 👨‍🏫 Persona 1 : Mohamed (Enseignant admin)

- **Rôle** : admin sur la plateforme, créateur principal
- **Profil** : Enseignant-formateur, consultant Data Science, a conçu tout le module Maths IA
- **Usage** : crée la banque de questions, organise les examens, surveille en temps réel,
  analyse les résultats, gère les comptes
- **Attentes** :
  - Outil rapide, fluide, professionnel
  - Analytics poussées pour améliorer son enseignement
  - Contrôle total (paramétrage fin, rôles, rétention)
  - Maintenance minimale
- **Fréquence d'usage** : hebdomadaire (création et suivi)

### 👩‍🏫 Persona 2 : Collègue enseignant

- **Rôle** : enseignant simple sur la plateforme
- **Profil** : collègue IPSSI qui enseigne un autre module (ML, Deep Learning, stats…)
- **Usage** : crée ses propres examens à partir de ses propres questions, consulte
  uniquement ses résultats
- **Attentes** :
  - Onboarding simple (pas de formation longue)
  - Interface intuitive (sans docs à lire à chaque fois)
  - Isolation de ses données (pas voir les examens des autres)
- **Fréquence d'usage** : mensuelle

### 👨‍🎓 Persona 3 : Étudiant B2 Info

- **Rôle** : utilisateur final de la plateforme (sans compte persistant)
- **Profil** : 20-22 ans, bachelier, à l'aise avec le web, passe 2-3 examens par semestre
- **Usage** : reçoit un code, passe l'examen, consulte sa correction
- **Attentes** :
  - Accès simple et rapide (pas de création de compte)
  - Interface claire, sans bug
  - Correction détaillée et pédagogique
  - Confirmation par email (preuve de passage)
  - Accessibilité (mobile, dyslexie si concerné)
- **Fréquence d'usage** : ponctuelle (quelques fois par année académique)

### 👩‍🏫 Persona 4 : Étudiant avec PPAS (tiers temps)

- **Rôle** : même que persona 3, avec aménagements
- **Besoin spécifique** : temps supplémentaire (1/3 ou 1/2), police adaptée (OpenDyslexic),
  contraste renforcé
- **Attentes** :
  - Ses aménagements sont gérés sans friction
  - Il n'a pas à demander de traitement spécial le jour J
  - Sa dignité est préservée (pas de marquage visible pour les autres)

---

## 3. Parcours utilisateurs clés

### 3.1 Parcours "Création d'un examen" (enseignant)

```
1. Se connecter à la plateforme
   └→ Email + password → Dashboard

2. Cliquer "Nouvel examen"
   └→ Formulaire en 4 étapes (wizard)

3. Étape 1 — Métadonnées
   └→ Titre, école, promotion, année, date, module

4. Étape 2 — Contenu
   └→ Filtres (chapitres, difficultés, types)
   └→ Système tire X questions automatiquement
   └→ Possibilité d'affiner (exclure/ajouter)

5. Étape 3 — Paramètres
   └→ Durée, fenêtre temporelle, barème
   └→ Sécurité (email unique, domaine, liste blanche)
   └→ Anti-triche (focus-lock, mode plein écran)
   └→ Correction (moment, détail, PDF)
   └→ Notifications (cochables)
   └→ Rétention RGPD

6. Étape 4 — Validation
   └→ Récapitulatif
   └→ Génération du code : IPSSI-B2-2026-A4F7
   └→ Lien direct + QR code
   └→ Email de confirmation

7. Partager le code aux étudiants
```

### 3.2 Parcours "Passage d'examen" (étudiant)

```
1. Recevoir le code de l'enseignant (SMS, email, tableau)

2. Ouvrir la plateforme
   └→ URL : examens.XXX.fr

3. Saisir le code + identité
   └→ Code : IPSSI-B2-2026-A4F7
   └→ Nom, Prénom, Email
   └→ Vérification serveur :
      - Code valide + dans la fenêtre
      - Email non déjà utilisé pour ce code
      - Si filtre domaine : vérifier
      - Si liste blanche : vérifier

4. Consentement RGPD
   └→ Acceptation du traitement de ses données

5. Écran "Règles de l'examen" (mis en évidence)
   └→ Durée, nombre de questions, règles focus-lock
   └→ 2 cases à cocher obligatoires
   └→ Bouton "Démarrer en plein écran"

6. Passage en plein écran + démarrage chronomètre

7. Questions (une par écran, ordre aléatoire)
   └→ Navigation libre (précédent/suivant)
   └→ Grille de navigation (25 boutons)
   └→ Auto-save toutes les 5 sec (localStorage)

8. Si sortie de page détectée :
   └→ 1ère : avertissement 5 sec
   └→ 2e : annulation + note 0/20

9. Soumission
   └→ Clic "Terminer" OU expiration du chronomètre
   └→ Génération CSV avec signature SHA-256
   └→ Sauvegarde serveur dans data/examens/XXX/passages/
   └→ Email confirmation à l'étudiant (CSV en PJ)

10. Écran post-examen
    └→ Si correction immédiate : [Voir ma correction]
    └→ Si programmée : "Disponible le XX/XX à XXh"
    └→ [Télécharger mon CSV]
    └→ [Retour à l'accueil]
```

### 3.3 Parcours "Consultation correction" (étudiant)

```
1. Cliquer sur le lien dans l'email OU saisir le token sur la page correction

2. Vérification serveur (token valide, examen expiré OK, rétention non atteinte)

3. Écran correction détaillée
   └→ Note géante + mention (Très Bien / Bien / Passable / Insuffisant)
   └→ KPI (correctes, points, %J1, %J2, durée)
   └→ Graphiques (distribution des réponses, temps par question)
   └→ Pour CHAQUE question :
      - Énoncé
      - 4 propositions (avec surlignage correct/incorrect)
      - Explication de la bonne réponse
      - Explications des mauvaises (ordonnées selon vu)
      - Piège à éviter
      - Référence cours

4. Actions
   └→ [📥 Télécharger correction en PDF]
   └→ [🔗 Partager avec mon tuteur] (email avec lien)

5. Page accessible pendant X jours (paramètre examen)
```

### 3.4 Parcours "Analyse post-examen" (enseignant)

```
1. Recevoir email "Examen clôturé — 27/30 passages"

2. Cliquer pour accéder au dashboard examen

3. Vue globale
   └→ KPI : moyenne, médiane, min, max, écart-type
   └→ Distribution des notes (histogramme)
   └→ Comparaison J1 vs J2
   └→ Grille 50 questions avec taux de réussite

4. Actions possibles
   └→ Identifier top 5 ratées → cliquer → voir répartition
   └→ Consulter tableau étudiants (tri, filtre)
   └→ Cliquer sur un étudiant → modal détail
   └→ Vérifier anomalies (signatures invalides, focus-lock)

5. Exports
   └→ CSV synthèse pour Yparéo
   └→ XLSX multi-onglets pour archivage
   └→ PDF rapport global

6. Analyses transversales (si >3 examens)
   └→ Évolution de la promo
   └→ Comparaison inter-promotions
   └→ Top questions ratées tous examens confondus
```

---

## 4. Les 20 décisions structurantes

### 📋 Tableau de synthèse

| # | Thème | Décision retenue | Alternatives écartées |
|:-:|---|---|---|
| 1 | Hébergement | OVH mutualisé + fichiers CSV/JSON | VPS+SQLite, VPS+Postgres, Supabase |
| 2 | Banque de questions | Un JSON par chapitre | Gros JSON unique, 1 fichier par question, Excel |
| 3 | Code d'examen | Hybride `IPSSI-B2-2026-A4F7` | Court aléatoire, parlant complet, mots-code |
| 4 | Auth enseignant | Comptes multiples avec rôles | Password unique, URL secrète |
| 5 | Auth étudiant | 1 email = 1 tentative + filtres | Libre, liste préchargée, password perso |
| 6 | Création examen | Formulaire COMPLET + sélection hybride B3 | Minimal, sélection manuelle |
| 7 | Chronomètre | Hybride dateDebut serveur + affichage local | 100% JS, polling, heartbeat |
| 8 | Stockage passages | Enrichi : CSV + metadata + index + audit | Minimal, intermédiaire, gros JSON |
| 9 | Correction étudiant | Paramétrable par examen | Immédiate, jamais, simple |
| 10 | Historique prof | Analyse approfondie (dashboard + détail + analytics) | Minimal, équilibré |
| 11 | Migration QCM J1-J2 | Import + amélioration qualitative | Direct, coexistence, extension J3-J4 |
| 12 | Gestion banque | CRUD + Import/Export + Génération IA | JSON direct, CRUD basique |
| 12a | Fournisseur IA | Claude + OpenAI (choix enseignant) | Claude seul, OpenAI seul, local |
| 12b | Clés API | Chaque enseignant sa propre clé | Clé centralisée admin, quota partagé |
| 13 | Notifications email | Prof + étudiant complet | Aucune, prof seul, marketing |
| 14 | Design visuel | Refonte complète avec design system pro | Continuité stricte, évolution douce |
| 15 | Accessibilité | WCAG AA + PPAS + Focus-lock anti-triche | Desktop seul, AA basique, AAA |
| 16 | RGPD | Équilibrée (traçabilité + droits + rétention) | Minimaliste, conformité max |
| 17 | Sauvegarde | Backup quotidien OVH + sync hebdo GitHub | Aucune, local seul, pro multi-sites |
| 18 | Langues | FR + architecture i18n extensible | FR hardcodé, FR+EN livrés, multilingue |
| 19 | Testing | Tests unitaires + E2E Playwright + CI/CD | Manuel, unit seul, couverture 80%+ |
| 20 | Déploiement | Soft launch (test → pilote → production) | Big bang, parallèle, feature flags |

### 📝 Détail des décisions

> Chaque décision est documentée avec : contexte, options étudiées, choix retenu,
> justification et implications.

#### Décision 1 — Hébergement

- **Contexte** : Où faire tourner le backend et stocker les données ?
- **Choix retenu** : 🅰️ **OVH mutualisé + stockage fichiers** (CSV/JSON)
- **Justification** :
  - Coût : 0 € supplémentaire (inclus dans l'offre OVH actuelle)
  - Simplicité : pas de base de données à administrer
  - Portabilité : tout le projet tient dans un ZIP
  - Debug facile : les CSV/JSON sont lisibles à l'œil
  - Cohérent avec l'existant (qcm_eval_J1_J2 utilise déjà CSV+JSON)
  - Migration future vers MySQL possible sans refonte frontend (documentée en GUIDE_MIGRATION_V2.md)
- **Limites acceptées** :
  - Scalabilité limitée (~500 examens, ~10k passages avant que les requêtes ne ralentissent)
  - Pas de requêtes SQL natives (remplacées par scripts PHP)
- **Implications techniques** :
  - Utilisation de `flock()` pour verrouiller les fichiers en écriture concurrente
  - Écriture atomique via `rename()` sur fichiers temporaires
  - Index.json maintenu dans chaque dossier d'examen pour éviter le scan complet

#### Décision 2 — Banque de questions

- **Contexte** : Format de stockage de la banque évolutive (objectif 500+ questions)
- **Choix retenu** : 🅱️ **Un JSON par chapitre** (organisation hiérarchique)
- **Justification** :
  - Évolutivité : chaque chapitre grandit sans impacter les autres
  - Sécurité : une erreur sur un fichier n'affecte pas les autres
  - Git-friendly : diff lisible, historique clair
  - Collaboratif : plusieurs profs peuvent éditer des fichiers différents
- **Structure** :
  ```
  data/banque/
  ├── index.json                    (méta globale)
  ├── maths_ia_b2/
  │   ├── _module.json              (méta module)
  │   ├── j1_representation.json    (~100 questions J1)
  │   ├── j2_optimisation.json      (~100 questions J2)
  │   ├── j3_classification.json    (~80 questions J3)
  │   └── j4_reseaux.json           (~80 questions J4)
  └── (futurs modules)
  ```

#### Décision 3 — Code d'examen

- **Contexte** : Format du code que les étudiants saisissent pour accéder
- **Choix retenu** : 🅲 **Hybride `IPSSI-B2-2026-A4F7`**
- **Justification** :
  - Lisible : le prof sait immédiatement de quel examen il s'agit
  - Sécurisé : les 4 caractères aléatoires empêchent la prédiction
  - Organisable : tri naturel par école/promo/année
  - Alphabet propre : sans ambiguïtés (pas de I, O, 0, 1)
- **Format** : `[ECOLE]-[PROMO]-[ANNEE]-[XXXX]`
  - Alphabet XXXX : `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (31 caractères)
  - 31⁴ = 923 521 combinaisons (largement suffisant)

#### Décision 4 — Authentification enseignant

- **Contexte** : Comment protéger l'accès aux pages admin
- **Choix retenu** : 🅱️ **Comptes multiples avec rôles admin/enseignant**
- **Justification** :
  - Multi-profs : plusieurs collègues IPSSI peuvent utiliser
  - Traçabilité : chaque examen a un créateur identifié
  - Rôles : admin voit tout, enseignant voit ses propres examens
  - RGPD : conformité avec la collecte de données étudiants
- **Implémentation** :
  - Hash bcrypt (10 rounds)
  - Sessions PHP sécurisées (`HttpOnly`, `SameSite=Strict`, `Secure`)
  - Rate limiting : 5 tentatives / 15 min par IP
  - CSRF token dans tous les formulaires modifiant des données

#### Décision 5 — Authentification étudiant

- **Contexte** : Comment limiter la triche sans complexifier l'accès
- **Choix retenu** : 🅱️ **1 email = 1 tentative + filtres optionnels configurables**
- **Justification** :
  - Équilibre sécurité/simplicité
  - Filtres additionnels modulables par examen :
    - Domaine email imposé (ex: `@ecole-ipssi.net`)
    - Liste blanche d'emails
    - Fenêtre temporelle stricte
  - Pas de gestion de comptes étudiants (pas de friction)
- **Compléments** :
  - Fingerprint navigateur (user-agent + résolution) pour détection d'anomalies
  - IP loguée dans audit.log

#### Décision 6 — Création d'examen

- **Contexte** : Périmètre du formulaire de création et stratégie de sélection
- **Choix retenu** : **COMPLET (~20 champs) + sélection hybride B3**
- **Paramètres couverts** :
  - Métadonnées : titre, école, promo, année, date, module, description
  - Contenu : filtres (chapitres, difficultés, types, tags) + affinage manuel
  - Durée : temps total, fenêtre temporelle, tolérance
  - Sécurité : email unique, domaine, liste blanche
  - Anti-triche : focus-lock, plein écran, copier-coller, clic droit
  - Barème : points par difficulté, note sur X, malus
  - Correction : moment (immédiate/programmée/jamais), niveau de détail, PDF
  - Notifications : checkboxes par type d'email
  - Rétention : 6 mois / 1 an / 3 ans / 5 ans / indéfinie

#### Décision 7 — Chronomètre

- **Contexte** : Timer résistant aux manipulations de l'étudiant
- **Choix retenu** : 🅱️ **Hybride : dateDebut serveur + affichage local**
- **Fonctionnement** :
  - À l'entrée : serveur enregistre `dateDebut` (UTC)
  - Le serveur envoie `dateFin = dateDebut + duree`
  - Le navigateur affiche un countdown basé sur `dateFin - maintenant()`
  - À la soumission : le serveur vérifie `now <= dateFin + tolérance`
- **Résistances** :
  - ✅ Changement d'heure système → sans effet
  - ✅ Fermeture navigateur → reprise avec le bon temps restant
  - ✅ Désactivation JS → pas de démarrage (check serveur au submit)
- **Bonus** : auto-save localStorage toutes les 5 sec

#### Décision 8 — Stockage passages

- **Contexte** : Structure fichiers pour stocker les passages d'examens
- **Choix retenu** : 🅱️ **Enrichi : CSV + metadata + index + audit**
- **Structure par examen** :
  ```
  data/examens/IPSSI-B2-2026-A4F7/
  ├── examen.json                   (config complète)
  ├── questions_snapshot.json       (copie des questions au moment T)
  ├── corrections_snapshot.json     (copie des corrections)
  ├── index.json                    (index rapide des passages)
  ├── passages/                     (CSV individuels)
  ├── passages_actifs/              (passages en cours)
  └── audit.log                     (journal JSONL chronologique)
  ```
- **Avantages** : traçabilité, performance dashboard, cohérence temporelle (snapshot)

#### Décision 9 — Correction étudiant

- **Contexte** : Quand, quoi, où afficher la correction
- **Choix retenu** : 🅱️ **Paramétrable par examen**
- **Paramètres configurables** :
  - Moment : immédiate / à la fin de la fenêtre / date précise / jamais
  - Niveau de détail : note seulement / questions ratées / détail complet
  - Durée d'accès : nombre de jours (défaut 30)
  - PDF téléchargeable : oui/non
  - Classement anonyme : oui/non
- **Défauts intelligents** :
  - Immédiate pour entraînements
  - À la fin de la fenêtre pour examens officiels
  - Détail complet par défaut (valeur pédagogique IPSSI)
  - Accès 30 jours
  - PDF activé

#### Décision 10 — Page historique prof

- **Contexte** : Richesse du dashboard enseignant
- **Choix retenu** : 🅲 **Analyse approfondie** (3 niveaux : global / par examen / transversal)
- **Niveaux** :
  - **Dashboard global** : landing après login, stats tous examens confondus
  - **Détail par examen** : réutilise le dashboard_enseignant_2026.html existant + temps réel
  - **Analytics transversales** :
    - Évolution d'une promotion
    - Top questions ratées tous examens confondus
    - Corrélations entre questions
    - Suivi par étudiant (tous ses examens)
    - Comparaison inter-promotions
    - Export Word pour conseil de classe

#### Décision 11 — Migration QCM J1-J2

- **Contexte** : Que faire des 50 questions du dispositif actuel
- **Choix retenu** : 🅳 **Import + amélioration qualitative**
- **Étapes** :
  1. Script Python `migrer_qcm_j1j2.py` (automatique)
  2. Relecture humaine de chaque question :
     - Correction typos
     - Enrichissement explications
     - Ajout tags pertinents
     - Clarification pièges
     - Homogénéisation style
  3. Rapport de migration listant les améliorations

#### Décision 12 — Gestion banque de questions

- **Contexte** : Interface de gestion de la banque
- **Choix retenu** : 🅳 **CRUD + Import/Export + Génération IA**
- **Fonctions CRUD** :
  - Liste avec filtres multi-critères
  - Création/édition avec preview KaTeX temps réel
  - Duplication, archivage, suppression
  - Validation stricte avant sauvegarde
- **Import/Export** :
  - JSON (backup, migration)
  - Excel (édition hors ligne)
  - PDF (archivage papier)
- **Génération IA** :
  - Choix du modèle à chaque génération (Claude Sonnet/Opus, GPT-4o/turbo)
  - Chaque enseignant utilise sa propre clé API (Q12b)
  - Prompt templating dans fichier éditable
  - Validation humaine obligatoire avant enregistrement
  - Journalisation complète (prompt, réponse, coût, auteur)
  - Protection confidentialité (aucune donnée étudiant envoyée)

#### Décision 13 — Notifications email

- **Contexte** : Envois automatiques par email
- **Choix retenu** : 🅲 **Prof + étudiant complet**
- **Emails envoyés** :

  *Pour le prof* :
  - Création d'examen (récap + liens)
  - Premier passage reçu
  - Examen clôturé (stats + liens rapport)
  - Signature invalide détectée
  - Anomalie anti-triche grave

  *Pour l'étudiant* :
  - Confirmation de passage (avec CSV en PJ)
  - Rappel correction disponible (si programmée)
  - Correction publiée

- **Implémentation** :
  - PHP `mail()` + SMTP OVH
  - SPF/DKIM configurés côté OVH
  - Jetons à usage unique pour accès correction
  - Opt-out RGPD dans paramètres compte

#### Décision 14 — Design visuel

- **Contexte** : Continuité ou refonte par rapport à qcm_eval_J1_J2
- **Choix retenu** : 🅲 **Refonte complète avec design system pro**
- **Fondations** :
  - Polices : Inter (UI) + Manrope (titres) + JetBrains Mono (code)
  - Palette tokens sémantiques (primary, secondary, success, warning, danger, neutral)
  - Couleur primaire : bleu par défaut (tranchée en Phase P2)
  - Mode clair + sombre raffiné
  - Échelle typographique : 12/14/16/18/24/32/48px
  - Spacing : base 4px
- **Composants premium** :
  - Boutons, inputs, modales, tooltips, toasts, skeletons
  - Animations Framer Motion
  - Command palette (Cmd+K)
  - Charts Recharts
  - Tables riches
  - Form builder (wizard)
- **Pages signature** :
  - Landing page prof avec KPIs glassmorphiques
  - Wizard création examen en 4 étapes
  - Dashboard live
  - Mode focus étudiant (plein écran immersif)
  - Mode présentation (projection au tableau)

#### Décision 15 — Accessibilité + Focus-lock anti-triche

- **Contexte** : Niveau d'inclusion + anti-triche
- **Choix retenu** : 🅲 **WCAG AA + PPAS + Focus-Lock anti-triche**

- **Responsive** :
  - Mobile-first design
  - Breakpoints : 320px / 768px / 1024px / 1440px
  - Navigation adaptative (sidebar → drawer mobile)
  - Formulaires adaptés

- **Accessibilité WCAG AA** :
  - Contraste ≥ 4.5:1
  - Navigation clavier complète (Tab, Shift+Tab, Enter, Esc, Arrows)
  - Focus visible sur tous les éléments
  - ARIA roles, landmarks, live regions
  - Labels sémantiques, erreurs proches du champ
  - Pas d'info uniquement par couleur

- **Aménagements PPAS (tiers temps)** :
  - Gestion temps supplémentaire par étudiant
  - Police OpenDyslexic en option
  - Mode haut contraste
  - Désactivation animations
  - Taille police augmentée (110%/125%/150%)

- **Focus-Lock anti-triche** ⭐ NOUVEAU :
  - Écran "Règles de l'examen" obligatoire avant démarrage
  - Plein écran obligatoire (Fullscreen API)
  - Détection sortie de page (Page Visibility API)
  - Politique à 3 niveaux configurables :
    * Strict (annulation dès la 1ère sortie)
    * Standard (1 avertissement puis annulation)
    * Tolérant (3 avertissements puis annulation)
    * Log seul (pas de sanction)
  - Mesures complémentaires (configurables) :
    * Blocage clic droit
    * Blocage copier-coller
    * Blocage Ctrl+P/S
    * Empêcher fermeture accidentelle
  - Journalisation complète dans audit.log
  - Affichage en temps réel côté prof des anomalies

#### Décision 16 — RGPD et rétention

- **Contexte** : Conformité et durée de conservation
- **Choix retenu** : 🅱️ **Équilibrée : traçabilité + droits RGPD complets**

- **Données collectées** (justifiées) :
  - Identité : nom, prénom, email (finalité : identification)
  - Passage : réponses, durée, note (finalité : correction)
  - Sécurité : IP, user-agent, événements focus-lock (finalité : anti-triche)
  - Logs : sessions, erreurs (finalité : sécurité)

- **Droits des étudiants** :
  - Page "Mes droits RGPD" accessible via email confirmation
  - Export données (JSON)
  - Demande rectification (formulaire → email au prof)
  - Demande suppression (droit à l'oubli)

- **Rétention paramétrable par examen** :
  - 6 mois / 1 an / 3 ans / 5 ans / indéfinie
  - Après expiration : suppression complète ou pseudonymisation
  - Cron quotidien de nettoyage automatique

- **Documents produits** :
  - Page "Politique de confidentialité"
  - Page "Mentions légales"
  - Consentement explicite avant 1ère utilisation
  - DPO : M. EL AFRIT + IPSSI (responsable conjoint)

#### Décision 17 — Sauvegarde

- **Contexte** : Protection contre perte de données
- **Choix retenu** : 🅲 **Backup quotidien OVH + sync hebdo GitHub privé**
- **Architecture** :
  - Script `backup_local.php` : cron OVH quotidien 3h → ZIP dans `_backups/`
  - Rotation : 30 derniers backups conservés
  - Script `backup_github.sh` : cron OVH hebdo dimanche 4h → push vers GitHub privé
  - Historique Git (ex: retour à il y a 3 mois en 1 clic)
  - Repo séparé : `melafrit/examens-backups` (privé)

#### Décision 18 — Langues

- **Contexte** : Internationalisation
- **Choix retenu** : 🅱️ **FR + architecture i18n extensible**
- **Implémentation** :
  - Textes externalisés dans `frontend/assets/i18n/fr.json`
  - Hook React `useTranslation()` : `{t('common.login')}`
  - Fallback automatique sur FR si clé manquante
  - Ajouter une langue = copier fr.json → xx.json + traduire
- **Détection auto** : basé sur `navigator.language` (avec fallback FR)

#### Décision 19 — Testing

- **Contexte** : Qualité logicielle en production
- **Choix retenu** : 🅲 **Tests unitaires + E2E Playwright + CI/CD GitHub**
- **Composants** :
  - **PHPUnit** (backend) : ~50 tests unitaires
    - ScoreCalculatorTest (calcul note, anti-fraude difficulté)
    - SignatureTest (SHA-256 validation)
    - CsvParserTest
    - ExamCodeGeneratorTest
    - QuestionFilterTest
    - FocusLockTest
  - **Playwright** (E2E) : ~15 parcours
    - student_flow.spec.js (passage nominal)
    - teacher_create_exam.spec.js
    - anti_cheat.spec.js
    - timer_expiration.spec.js
    - rgpd_rights.spec.js
    - ia_generation.spec.js
  - **GitHub Actions** : à chaque push → run tests → badge statut

#### Décision 20 — Déploiement

- **Contexte** : Stratégie de mise en production
- **Choix retenu** : 🅱️ **Soft launch en 3 phases**
- **Phase 1 — Test interne (2 semaines)** :
  - URL staging : `examens-test.XXX.fr`
  - Accès restreint (.htaccess)
  - Test perso + 2-3 collègues
  - Corrections bugs
- **Phase 2 — Pilote (3-4 semaines)** :
  - 1 vrai examen avec 1 promo volontaire
  - Sans note OU double vérification
  - Surveillance active
  - Feedback étudiants
- **Phase 3 — Production complète** :
  - Basculement de tous les examens
  - Archivage de l'ancien système

---

## 5. Stack technique retenue

### 5.1 Frontend

| Catégorie | Technologie | Justification |
|---|---|---|
| **Framework** | React 18 (via CDN + Babel in-browser) | Cohérent avec l'existant, pas de build step |
| **UI Library** | Tailwind + composants custom | Moderne, rapide |
| **Icons** | Lucide React | Cohérent, libre |
| **Math rendering** | KaTeX | Déjà utilisé dans l'existant |
| **Charts** | Recharts | Léger, déclaratif |
| **Animations** | Framer Motion | Industry standard |
| **Internationalization** | Custom hook i18n minimal | Pas de surcharge |
| **Forms** | Custom FormBuilder | Spécifique à nos besoins |

### 5.2 Backend

| Catégorie | Technologie | Justification |
|---|---|---|
| **Langage** | PHP 7.4+ (≥ 8.0 recommandé) | Supporté nativement par OVH mutualisé |
| **Stockage** | Fichiers CSV + JSON | Simple, portable, pas de DB |
| **Auth** | Sessions PHP + Bcrypt | Standard, sécurisé |
| **Chiffrement** | OpenSSL (AES-256-GCM) | Pour les clés API IA |
| **Email** | mail() + SMTP OVH | Natif, gratuit |
| **IA** | Anthropic SDK + OpenAI SDK (via CURL) | Flexibilité multi-modèle |
| **Cron** | OVH Task Manager | Gratuit, intégré |

### 5.3 Tests

| Catégorie | Technologie | Justification |
|---|---|---|
| **Tests unitaires** | PHPUnit 9.x | Standard PHP |
| **Tests E2E** | Playwright | Moderne, puissant, multi-navigateur |
| **CI/CD** | GitHub Actions | Gratuit pour repos publics/privés |
| **Coverage** | Xdebug + phpunit-coverage | Standard |

### 5.4 DevOps

| Catégorie | Technologie | Justification |
|---|---|---|
| **Versioning** | Git + GitHub | Standard, existant |
| **Backups** | ZIP + rsync + Git push | Simple, résilient |
| **Monitoring** | Uptime Robot (gratuit) | Alerte si site down |
| **Error tracking** | Sentry (free tier) ou logs custom | À décider plus tard |

---

## 6. Architecture globale

```
┌─────────────────────────────────────────────────────────────────┐
│                      UTILISATEURS                                │
│   👨‍🏫 Enseignants (admin + simples)    👨‍🎓 Étudiants           │
└─────────────────────────────┬───────────────────────────────────┘
                              │ HTTPS
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                  FRONTEND (React SPA)                            │
│  /examens/enseignant/*    /examens/etudiant/*    /examens/commun │
└─────────────────────────────┬───────────────────────────────────┘
                              │ API JSON
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                  BACKEND PHP (OVH mutualisé)                     │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                  API Layer                               │    │
│  │  auth | comptes | examens | banque | ia | email | rgpd │    │
│  └──────────────────────┬──────────────────────────────────┘    │
│  ┌──────────────────────▼──────────────────────────────────┐    │
│  │                  Service Layer                           │    │
│  │  Score | Signature | CsvWriter | Mailer | IaClient    │    │
│  │  FocusLockValidator | TokenManager | BackupManager    │    │
│  └──────────────────────┬──────────────────────────────────┘    │
│  ┌──────────────────────▼──────────────────────────────────┐    │
│  │                  Storage Layer                           │    │
│  │  FileStorage (JSON + CSV) avec flock + atomic writes    │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                 STORAGE (filesystem OVH)                         │
│  /data/banque/      /data/examens/      /data/comptes/          │
│  /data/config/      /data/_backups/                              │
└─────────────────────────────┬───────────────────────────────────┘
                              │ (cron backup)
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│           BACKUPS (GitHub privé melafrit/examens-backups)        │
└─────────────────────────────────────────────────────────────────┘
```

---

## 7. Fonctionnalités différenciantes

Ce qui rend cette plateforme unique par rapport à Moodle/Canvas/Google Forms :

### 🎯 Génération IA de questions
- Pas "juste" un formulaire : vraiment générer du contenu pédagogique
- Multi-fournisseurs (Claude + OpenAI)
- Chaque prof utilise sa propre clé
- Validation humaine obligatoire
- Prompts templates éditables par l'admin

### 🛡️ Focus-Lock anti-triche configurable
- Plein écran obligatoire
- Détection fine des sorties (onglet, fenêtre, minimisation)
- Politique escalade progressive (avertissement → annulation)
- Journalisation complète pour litiges
- Dashboard temps réel prof

### 📊 Analytics transversales
- Évolution promotion au fil des examens
- Comparaison inter-promotions
- Corrélations entre questions
- Top questions ratées tous examens
- Export Word rapport conseil de classe

### 🎨 Design system pro
- Composants modernes type Linear/Vercel
- Dark mode raffiné
- Mode présentation pour projeter au tableau
- Mode focus immersif pour étudiant

### 🔒 RGPD intégré
- Consentement explicite
- Droits étudiants par page dédiée
- Rétention paramétrable par examen
- Cron de nettoyage automatique
- Pseudonymisation pour stats

### ⚡ Pas de base de données requise
- Démarre sur OVH mutualisé inclus
- Zéro coût d'infrastructure
- Migration vers MySQL possible plus tard sans refonte

---

## 8. Estimation de charge

### 8.1 Par thème fonctionnel

| Thème | Heures |
|---|---:|
| Fondations (backend, auth, comptes) | 14 |
| Banque de questions (CRUD, Import/Export) | 14 |
| Génération IA (module complet) | 10 |
| Création d'examen (formulaire complet) | 12 |
| Passage étudiant (UI + chronomètre + focus-lock) | 14 |
| Correction étudiant (pages + PDF) | 10 |
| Historique prof (dashboard + détail + analytics) | 18 |
| Design system pro (refonte complète) | 15 |
| Accessibilité WCAG AA + responsive | 10 |
| Notifications email (prof + étudiant) | 5 |
| RGPD + rétention + pages légales | 6 |
| Sauvegardes (local + GitHub) | 5 |
| i18n (architecture FR) | 2 |
| Tests unitaires + E2E + CI/CD | 15 |
| Migration J1-J2 + amélioration | 5 |
| Documentation complète | 8 |
| **TOTAL** | **163** |

### 8.2 Par phase de livraison

Voir [ROADMAP.md](./ROADMAP.md) pour la décomposition en 9 phases.

### 8.3 Calendrier indicatif

En phase de développement continu (rythme soutenu) :
- **8 à 10 semaines** pour livrer les 9 phases
- **+5 à 6 semaines** de soft launch (test interne + pilote)
- **~15 semaines total** avant production complète

---

## 9. Contraintes et hypothèses

### 9.1 Contraintes techniques

- ✅ **Hébergement mutualisé OVH** disponible (contrainte existante)
- ✅ **PHP 7.4+** disponible sur l'offre OVH
- ✅ **Cron OVH** inclus (jusqu'à 5 tâches)
- ✅ **SMTP OVH** fonctionnel (1000 emails/jour)
- ✅ **Nom de domaine** disponible (à configurer plus tard)
- ⚠️ **Limite MySQL** : non utilisée en v1 (migration v2 plus tard)
- ⚠️ **Taille fichiers** : pas de fichier > 100Mo (pas bloquant)

### 9.2 Contraintes fonctionnelles

- ✅ Tous les examens sont des QCM (pas de rédaction)
- ✅ Questions à choix unique (4 propositions, 1 bonne réponse)
- ⚠️ **Pas de questions à choix multiples** (évolution v2 possible)
- ⚠️ **Pas d'upload d'images dans les questions** (v2)

### 9.3 Hypothèses

- **Volume** : < 100 étudiants par examen simultanément
- **Fréquence** : < 50 examens par an au total
- **Types d'accès** : majoritairement desktop en salle, minorité mobile distanciel
- **Compétences prof** : à l'aise avec outils web, pas besoin de SSH/CLI
- **Compétences étudiant** : maîtrise basique du web, mais pas toujours mobile
- **Réseau** : Wi-Fi IPSSI fiable pendant les examens

---

## 10. Risques et mitigations

| Risque | Probabilité | Impact | Mitigation |
|---|:-:|:-:|---|
| Crash OVH le jour J | Faible | Élevé | Page de statut + backup hebdo GitHub + staging |
| Bug non détecté en production | Moyen | Élevé | Tests E2E + soft launch (pilote) |
| Surcharge serveur (100+ étudiants) | Moyen | Moyen | Cache agressif + CDN pour assets + monitoring |
| Étudiant conteste sa note | Moyen | Moyen | Signature SHA-256 + audit.log complet |
| Fraude contournant focus-lock | Faible | Moyen | Multi-couches (JS + serveur + surveillance visuelle) |
| Coût clé API IA non maîtrisé | Faible | Faible | Clé par prof (il gère son budget) |
| Perte de la banque de questions | Faible | Très élevé | Backup quotidien + GitHub hebdo |
| Non-conformité RGPD | Faible | Élevé | Consentement + droits + rétention + audit |
| Prolifération mots de passe oubliés | Moyen | Faible | Système reset par email (Phase P1) |
| Adoption faible par les collègues | Moyen | Moyen | Guide + vidéos + formation informelle |

---

## 🎯 Conclusion

Ce cadrage définit une **plateforme d'examens ambitieuse mais réaliste** :

- ✅ **Simple à déployer** (OVH mutualisé, pas de DB)
- ✅ **Évolutive** (architecture prête pour MySQL v2, multi-modules)
- ✅ **Professionnelle** (design pro, tests automatisés, RGPD)
- ✅ **Innovante** (génération IA, focus-lock, analytics transversales)
- ✅ **Pérenne** (sauvegardes multi-niveaux, documentation complète)

Le prochain livrable est la **Phase P1** : fondations backend (authentification,
comptes enseignants, API base), suivie des 8 phases détaillées dans [ROADMAP.md](./ROADMAP.md).

---

**Document vivant** : ce cadrage pourra être mis à jour au fil du projet si des
ajustements sont nécessaires. Toute modification sera tracée dans
[CHANGELOG.md](./CHANGELOG.md).

---

*© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0*
