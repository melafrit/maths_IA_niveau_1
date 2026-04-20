# Guide de déploiement du QCM d'évaluation J1-J2

**Module :** Mathématiques appliquées à l'IA  
**Public :** 2ᵉ année Bachelor Informatique — IPSSI — Année 2025-2026  
**Auteur :** Mohamed EL AFRIT — [m.elafrit@ecole-ipssi.net](mailto:m.elafrit@ecole-ipssi.net)  
**Licence :** CC BY-NC-SA 4.0

---

## 🎯 Vue d'ensemble du dispositif

Ce dossier contient un dispositif complet d'évaluation en ligne qui couvre les **Jours 1 (Représentation des données et régression) et Jour 2 (Optimisation et descente de gradient)** du module. Il se compose de **trois pages web standalone** (sans backend) et de **deux fichiers JSON de données** :

| Fichier | Rôle | Public |
|---|---|---|
| `qcm_etudiant.html` | Passage du QCM (50 questions) + export CSV signé | Étudiants |
| `correction_personnelle.html` | Affichage du corrigé individuel après upload CSV | Étudiants |
| `dashboard_enseignant_2026.html` | Tableau de bord complet multi-étudiants + exports | **Enseignant uniquement** |
| `questions.json` | Les 50 énoncés + 4 propositions (aucune bonne réponse) | Publié en Phase A |
| `corrections.json` | Les 50 bonnes réponses + explications + pièges | **Publié UNIQUEMENT en Phase B** |

> 🔒 **Point critique** : `corrections.json` contient les bonnes réponses. **Il ne doit JAMAIS être publié avant la clôture de l'évaluation.**

---

## 🗓️ Déploiement en 2 phases

Le déploiement est volontairement séparé en deux temps pour **empêcher qu'un étudiant ne consulte les corrections pendant la période d'évaluation**.

### 📅 Phase A — Avant l'évaluation (semaine du QCM)

**Fichiers à mettre en ligne :**

```
qcm_etudiant.html
questions.json
```

**Fichiers à NE PAS déployer :**
- ❌ `correction_personnelle.html` (nécessite `corrections.json`)
- ❌ `dashboard_enseignant_2026.html` (nécessite `corrections.json`)
- ❌ `corrections.json` (CONFIDENTIEL)

**Communication aux étudiants :**

Diffuser l'URL de `qcm_etudiant.html` via Yparéo / e-mail / Teams, accompagnée des consignes suivantes :

> Bonjour,
>
> Dans le cadre du module « Mathématiques appliquées à l'IA », vous allez passer un QCM couvrant les Jours 1 et 2.
>
> **Accès** : [lien vers qcm_etudiant.html]
>
> **Consignes**
> - Le QCM comporte **50 questions à choix unique** (4 propositions par question).
> - Date limite : **dimanche [DATE] avant minuit**.
> - Durée estimée : **60 minutes**. Un chronomètre s'affiche mais ne bloque pas la soumission.
> - À la fin, vous téléchargerez **un fichier CSV** à envoyer par e-mail à <m.elafrit@ecole-ipssi.net> en respectant strictement le nommage `IPSSI_NOM_Prenom_QCM_J1J2_YYYYMMDD.csv`.
> - **Conservez votre CSV** : vous en aurez besoin la semaine suivante pour consulter votre correction détaillée.
>
> Bon courage,  
> M. EL AFRIT

### 📅 Phase B — Après la clôture (semaine suivante)

Une fois la date limite dépassée et les notes consolidées, on publie les outils de correction.

**Fichiers à ajouter :**

```
correction_personnelle.html
dashboard_enseignant_2026.html   ← URL secrète, ne pas la diffuser aux étudiants
corrections.json
```

**Communication aux étudiants (Phase B) :**

> Bonjour,
>
> Vous pouvez désormais consulter votre correction détaillée sur :  
> [lien vers correction_personnelle.html]
>
> Glissez-déposez simplement votre fichier CSV pour voir :
> - votre note finale sur 20,
> - les explications détaillées pour chacune des 50 questions,
> - les pièges à éviter et les références au cours.
>
> M. EL AFRIT

---

## 🖥️ Options d'hébergement

Les trois pages sont **100 % statiques** (HTML + JS + CSS dans le navigateur, aucun backend requis). Les options d'hébergement sont :

### Option 1 — GitHub Pages (recommandé)

Le dépôt `maths_IA_niveau_1` peut être publié via GitHub Pages. Les étudiants accèdent directement aux URLs.

1. Dans les paramètres du dépôt → **Pages** → Source : branche `main`, dossier `/`
2. L'URL de production sera : `https://melafrit.github.io/maths_IA_niveau_1/qcm_eval_J1_J2/qcm_etudiant.html`
3. Pour la Phase A, **supprimer temporairement** `correction_personnelle.html`, `dashboard_enseignant_2026.html` et `corrections.json` du dépôt (voir la commande `git rm` ci-dessous).

**Procédure Git pour Phase A (retirer les fichiers sensibles) :**

```bash
cd maths_IA_niveau_1
# Sauvegarde locale avant suppression
cp qcm_eval_J1_J2/corrections.json ~/corrections_BACKUP.json
cp qcm_eval_J1_J2/correction_personnelle.html ~/correction_BACKUP.html
cp qcm_eval_J1_J2/dashboard_enseignant_2026.html ~/dashboard_BACKUP.html

# Suppression sur la branche de production (ici 'gh-pages' ou 'main')
git rm qcm_eval_J1_J2/corrections.json
git rm qcm_eval_J1_J2/correction_personnelle.html
git rm qcm_eval_J1_J2/dashboard_enseignant_2026.html
git commit -m "chore(qcm-eval): phase A - retrait temporaire des outils de correction"
git push origin main
```

**Procédure Git pour Phase B (remettre les fichiers) :**

```bash
cp ~/corrections_BACKUP.json           qcm_eval_J1_J2/corrections.json
cp ~/correction_BACKUP.html            qcm_eval_J1_J2/correction_personnelle.html
cp ~/dashboard_BACKUP.html             qcm_eval_J1_J2/dashboard_enseignant_2026.html

git add qcm_eval_J1_J2/
git commit -m "chore(qcm-eval): phase B - ouverture des correctifs apres cloture"
git push origin main
```

### Option 2 — Serveur HTTP local (démo / test)

Depuis un terminal :

```bash
cd qcm_eval_J1_J2
python3 -m http.server 8000
```

Puis accéder dans le navigateur à : `http://localhost:8000/qcm_etudiant.html`

> ⚠️ Ne pas ouvrir les fichiers HTML via un double-clic (`file:///...`) : les appels `fetch('questions.json')` échouent pour cause de sécurité CORS. Un serveur HTTP est obligatoire.

### Option 3 — Serveur de l'école

Copier uniquement les fichiers de la Phase A dans le répertoire web de l'école (ex: `/var/www/html/qcm-maths-ia/`), puis en Phase B ajouter les fichiers manquants.

---

## 🔐 Sécurité et vérification d'intégrité

Chaque CSV produit par `qcm_etudiant.html` contient une signature **SHA-256** calculée à partir :

1. de l'identité de l'étudiant (nom, prénom, email),
2. des timestamps de début et de fin,
3. de la durée et du nombre de questions,
4. de **l'ordre des questions, des permutations et des réponses**,
5. d'un salt codé dans les pages (`IPSSI_SALT_2026`).

### Qu'est-ce qui est protégé ?

Toute modification manuelle du CSV suivante sera **automatiquement détectée** par `correction_personnelle.html` et `dashboard_enseignant_2026.html` :

- ✅ Modification du nom/prénom/email
- ✅ Modification d'une réponse (`IndexOriginalChoisi`)
- ✅ Modification des dates ou de la durée
- ✅ Ajout ou suppression de lignes de réponses
- ✅ Forgerie d'une signature fictive

### Qu'est-ce qui est également protégé par le fix du 20/04/2026 ?

Depuis la version actuelle, la difficulté, le jour et le type de chaque question sont **recalculés depuis `corrections.json`** (source authoritative) et non lus depuis le CSV. Cela protège contre une fraude consistant à modifier la colonne `Difficulte` du CSV pour transformer des questions faciles (1 pt) en questions expert (4 pts). Cette fraude ne casserait pas la signature (qui ne couvre pas ce champ) mais est désormais sans effet sur la note.

### Limite connue

Le salt `IPSSI_SALT_2026` est codé en dur dans le JavaScript, donc visible dans le code source de la page. Un étudiant ayant de solides compétences en développement web pourrait théoriquement forger un CSV valide. **Contre-mesure** : vérifier visuellement les CSV suspects (durée trop courte, horaire incohérent, etc.) et, en cas de doute, demander le passage en conditions surveillées.

---

## 🎓 Barème et calcul de la note

### Points par question (selon la difficulté)

| Difficulté | Emoji | Points |
|---|:---:|:---:|
| Facile | 🟢 | 1 |
| Moyen | 🟡 | 2 |
| Difficile | 🟠 | 3 |
| Expert | 🔴 | 4 |

### Répartition

- **Jour 1** : 10 🟢 + 10 🟡 + 4 🟠 + 1 🔴 = 46 points (25 questions)
- **Jour 2** : 10 🟢 + 10 🟡 + 4 🟠 + 1 🔴 = 46 points (25 questions)
- **Total** : **92 points bruts**, 50 questions

### Conversion sur 20

$$\text{Note}_{/20} = \frac{\text{Points obtenus}}{92} \times 20$$

- Aucun malus en cas de mauvaise réponse.
- Aucun point pour une non-réponse.

### Interprétation

| Note | Mention | Couleur |
|---|---|---|
| ≥ 16 | Très Bien | 🟢 Vert |
| 14 – 15,99 | Bien | 🔵 Bleu |
| 12 – 13,99 | Assez Bien | 🔵 Bleu |
| 10 – 11,99 | Passable | 🟠 Orange |
| < 10 | Insuffisant | 🔴 Rouge |

---

## 🧪 Tests avant mise en production

Avant chaque déploiement, vérifier que les pages fonctionnent correctement avec les CSV de test fournis. Voir le fichier dédié : **[TESTS.md](TESTS.md)**.

Le répertoire `tests/` contient **10 CSV fictifs** représentant une classe complète (notes étalées de 0 à 20, un CSV falsifié) avec leurs valeurs attendues dans `MANIFESTE_TESTS.json`.

---

## 📞 En cas de problème

- **Un étudiant ne retrouve pas son CSV** → lui demander de refaire le QCM si possible, sinon le noter sur la base d'un QCM manuel corrigé à la main. Ne pas faire d'exception sur la signature car cela créerait un précédent.
- **Un étudiant signale une signature invalide alors qu'il n'a pas modifié son fichier** → vérifier qu'il a utilisé la bonne version de `qcm_etudiant.html` (salt `IPSSI_SALT_2026`). Les versions des années précédentes ont des salts différents.
- **Le dashboard ne charge pas** → vérifier que `questions.json` et `corrections.json` sont présents dans le même répertoire et accessibles (test : `curl http://…/corrections.json`).
- **Export XLSX échoue** → la librairie SheetJS est chargée depuis `cdn.jsdelivr.net`. Si ce CDN est bloqué par le pare-feu de l'école, l'export CSV fonctionne toujours comme fallback.

---

## ✅ Checklist avant de lancer l'évaluation

- [ ] `qcm_etudiant.html` accessible via le lien prévu
- [ ] `questions.json` accessible (test : `curl URL/questions.json` retourne le JSON)
- [ ] **`corrections.json` NON accessible** en Phase A (test : retourne 404)
- [ ] **`correction_personnelle.html` NON accessible** en Phase A (test : retourne 404)
- [ ] **`dashboard_enseignant_2026.html` NON accessible** en Phase A (test : retourne 404)
- [ ] Test du QCM de bout en bout sur un appareil personnel (téléchargement CSV, ouverture dans un tableur pour vérifier la structure)
- [ ] Communication envoyée aux étudiants (URL, date limite, consignes CSV)
- [ ] Rappel J-1 envoyé

## ✅ Checklist avant d'ouvrir la correction (Phase B)

- [ ] Tous les CSV des étudiants ont été reçus et sauvegardés
- [ ] Test du dashboard sur un batch complet des CSV reçus (voir `TESTS.md`)
- [ ] Toutes les signatures sont OK ou ont été tracées pour enquête
- [ ] Ajout de `correction_personnelle.html` + `corrections.json` + `dashboard_enseignant_2026.html` au site
- [ ] Test rapide de la page `correction_personnelle.html` avec un CSV réel
- [ ] Communication envoyée aux étudiants avec le lien vers la correction
- [ ] Export CSV Yparéo effectué et sauvegardé

---

*Document maintenu par Mohamed EL AFRIT — IPSSI — Licence CC BY-NC-SA 4.0*
