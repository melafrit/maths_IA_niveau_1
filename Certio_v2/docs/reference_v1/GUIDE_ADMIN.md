# 👑 Guide Administrateur — IPSSI Examens

> Manuel complet pour les **administrateurs** de la plateforme.
> Dashboard, gestion utilisateurs, monitoring, backups, maintenance.

---

## 📖 Table des matières

1. [Première connexion](#1-première-connexion)
2. [Interface admin](#2-interface-admin)
3. [Gestion des utilisateurs](#3-gestion-des-utilisateurs)
4. [Banque de questions](#4-banque-de-questions)
5. [Monitoring système](#5-monitoring-système)
6. [Gestion des backups](#6-gestion-des-backups)
7. [Maintenance](#7-maintenance)
8. [Dépannage](#8-dépannage)
9. [Scénarios complets](#9-scénarios-complets)

---

## 1. Première connexion

### Accès

Une fois le système installé (voir `INSTALLATION.md`), accédez au dashboard admin :

- **Local** : `http://localhost:8765/admin/examens.html`
- **Production** : `https://examens.ipssi.fr/admin/examens.html`

### Identifiants par défaut

Créés par le script `scripts/init_comptes.php` :

| Rôle | Email | Password par défaut |
|---|---|---|
| Admin | `admin@ipssi.fr` | `ChangeMe2026!` |

⚠️ **Changez ce mot de passe immédiatement après la première connexion** via le script :

```bash
php scripts/reset_password.php admin@ipssi.fr
# Demande interactive du nouveau mot de passe
```

### Première action recommandée

1. Vérifier santé système : `/admin/monitoring.html`
2. Créer au moins un compte **enseignant** (section 3)
3. Configurer backups quotidiens (section 6)

---

## 2. Interface admin

### Menu principal

```
┌────────────────────────────────────────────┐
│  🎓 IPSSI Examens                          │
├────────────────────────────────────────────┤
│  📚 Banque de questions  /admin/banque     │
│  📝 Examens              /admin/examens    │
│  📊 Analytics            /admin/analytics  │
│  🩺 Monitoring           /admin/monitoring │
│  🔧 Maintenance          (scripts CLI)     │
└────────────────────────────────────────────┘
```

### Privilèges admin

L'administrateur a **tous les droits** :
- ✅ Voir les examens de tous les profs
- ✅ Accéder aux analytics de tous les examens
- ✅ Gérer tous les comptes (créer, désactiver, reset password)
- ✅ Accéder au monitoring système
- ✅ Gérer les backups (créer, supprimer, restaurer)
- ✅ Rate limit **illimité** (pas de throttling)

---

## 3. Gestion des utilisateurs

### Via script CLI

**Créer un compte enseignant** :

```bash
cd /path/to/examens

# Mode interactif
php scripts/init_comptes.php

# Avec paramètres
php scripts/init_comptes.php --role=enseignant \
  --email=prof@ipssi.fr \
  --nom=Dupont --prenom=Jean \
  --password=MotDePasseInit2026
```

**Réinitialiser un mot de passe** :

```bash
php scripts/reset_password.php prof@ipssi.fr
# Demande le nouveau mot de passe interactivement
```

**Lister les comptes** :

```bash
ls -la data/comptes/
# USR-xxxx.json (un par user)

# Voir un compte
cat data/comptes/USR-xxxx.json | jq
```

**Désactiver un compte** :

Éditer directement le fichier JSON :
```bash
# Dans data/comptes/USR-xxxx.json
{
  "active": false   // Passer à false
}
```

### Via API REST

**Créer un compte** (admin requis + CSRF) :

```bash
curl -X POST http://localhost:8765/api/comptes \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: <token>" \
  --cookie "PHPSESSID=<session>" \
  -d '{
    "email": "prof@ipssi.fr",
    "nom": "Dupont",
    "prenom": "Jean",
    "role": "enseignant",
    "password": "PasswordInit2026"
  }'
```

**Lister** :
```bash
curl http://localhost:8765/api/comptes \
  --cookie "PHPSESSID=<session>"
```

---

## 4. Banque de questions

### Accès

`/admin/banque.html` → arborescence interactive

### Structure

```
Module
├── Chapitre
│   └── Thème
│       ├── Question 1 (QCM)
│       ├── Question 2 (QCM)
│       └── ...
```

Exemple :
```
vecteurs
├── operations
│   ├── somme
│   │   ├── vec-faci-01
│   │   └── vec-faci-02
│   └── produit-scalaire
└── norme
```

### Actions disponibles

#### Créer une question

1. Click sur un thème → **+ Nouvelle question**
2. Remplir :
   - Énoncé (support **LaTeX KaTeX** : `$\vec{u}$`, `$\frac{a}{b}$`)
   - 4 options (A, B, C, D)
   - Marquer la bonne réponse
   - Explication (optionnelle mais recommandée)
   - Difficulté : facile / moyen / difficile / très difficile
   - Tags

3. Preview LaTeX en temps réel

4. Sauvegarder → génère un ID unique (ex: `vec-faci-05`)

#### Modifier une question

- Click sur question → bouton ✏️ **Éditer**
- Les changements sont **immédiatement effectifs** sur les nouveaux passages
- Les passages déjà soumis conservent leur version (intégrité)

#### Supprimer une question

- ⚠️ Non recommandé si la question est utilisée dans un examen publié
- Préférer marquer comme `archived: true` dans le JSON

### Format JSON

Chaque question est un fichier JSON :

```json
{
  "id": "vec-faci-01",
  "module": "vecteurs",
  "chapitre": "operations",
  "theme": "somme",
  "difficulte": "facile",
  "type": "qcm",
  "enonce": "Quelle est la somme de $\\vec{u}=(1,2)$ et $\\vec{v}=(3,4)$ ?",
  "options": [
    {"text": "(2, 6)", "correct": false},
    {"text": "(4, 6)", "correct": true},
    {"text": "(4, 8)", "correct": false},
    {"text": "(3, 8)", "correct": false}
  ],
  "explication": "On additionne composante par composante : (1+3, 2+4) = (4, 6)",
  "tags": ["base", "calcul"]
}
```

### Bonnes pratiques

- **Rédigez toujours l'explication** — elle aide les étudiants en correction
- **Varier les distracteurs** — éviter les options trop évidentes
- **Difficulté progressive** : `facile` (> 75% taux réussite attendu), `moyen` (50-75%), `difficile` (25-50%), `tres_difficile` (<25%)
- **IDs explicites** : `{module3}-{difficulte4}-{num02}` (ex: `vec-faci-01`)

---

## 5. Monitoring système

### Dashboard monitoring

`/admin/monitoring.html` → vue temps réel de la santé système.

### Ce qui est affiché

```
╔══════════════════════════════════════════╗
║  🩺 Status global (banner color-coded)  ║
║   ✅ OK  |  ⚠️ Warning  |  🚨 Error     ║
╚══════════════════════════════════════════╝

┌─────────┐  ┌─────────┐  ┌─────────┐
│  💾     │  │  🧠     │  │  📁     │
│  Disque │  │ Mémoire │  │ Fichiers│
└─────────┘  └─────────┘  └─────────┘

┌─────────┐  ┌─────────┐  ┌─────────┐
│  📊     │  │  💾     │  │  📝     │
│ Compteurs│  │ Backups │  │  Logs   │
└─────────┘  └─────────┘  └─────────┘

┌─────────┐
│  🐘     │
│   PHP   │
└─────────┘
```

### 7 checks détaillés

#### 💾 Disque
- Espace libre/total du filesystem
- Usage % avec barre de progression
- Taille du dossier `data/`
- **Alertes** : warning < 500MB libres, error < 100MB

#### 🧠 Mémoire
- Current (usage actuel)
- Peak (pic maximum)
- Limit (`memory_limit` PHP)
- Usage %
- **Alertes** : warning > 75%, error > 90%

#### 📁 Filesystem
- Check R/W sur 6 dossiers critiques :
  - examens, passages, comptes, banque, logs, sessions
- Affiche ✅ ou ❌ pour chaque

#### 📊 Compteurs
- Total examens
- Total passages
- Total comptes
- Sessions actives
- Total backups

#### 💾 Backups
- Nombre total
- Dernier backup (date + age)
- Taille totale
- **Alertes** : warning si > 48h, error si > 96h

#### 📝 Logs
- Taille totale
- Nombre de fichiers
- **Alertes** : warning > 100MB

#### 🐘 PHP
- Version
- SAPI (cli, fpm, etc.)
- Timezone
- OPCache enabled
- Extensions actives (json, mbstring, openssl, curl, zip)

### Auto-refresh

Checkbox en haut-droite : refresh automatique toutes les 30 secondes.

### API monitoring

Utilisable en CLI ou par outils externes :

```bash
# Check basique
curl http://localhost:8765/api/health

# Check détaillé (JSON complet)
curl http://localhost:8765/api/health?detailed=1
```

### Intégration monitoring externe

Exemple avec **UptimeRobot** / **Nagios** / **Datadog** :

```bash
# Simple ping
curl -f http://examens.ipssi.fr/api/health || alert

# Check détaillé + status code
STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
  "http://examens.ipssi.fr/api/health?detailed=1")
# 200 = OK ou warning
# 503 = error critique
```

---

## 6. Gestion des backups

### Stratégie recommandée

| Usage | Fréquence | Rétention |
|---|---|---|
| Production normale | Quotidien 03:00 | 14 derniers |
| Production critique | Quotidien + avant déploiements | 30 derniers + offsite |
| Développement | Manuel | 3-5 derniers |

### Installation cron (production)

```bash
# Voir la ligne à ajouter
./scripts/install-cron.sh

# Installer automatiquement (03:00 par défaut)
./scripts/install-cron.sh --install

# Personnaliser heure
./scripts/install-cron.sh --install --time=02:30

# Désinstaller
./scripts/install-cron.sh --remove
```

Vérifier :
```bash
crontab -l
# Devrait afficher :
# # IPSSI_EXAMENS_BACKUP
# 0 3 * * * /path/to/examens/scripts/backup.sh --quiet --keep=14
```

### Backup manuel

**Via script** :
```bash
./scripts/backup.sh
# ✅ Backup créé : 2.3M en 1s
# Hash SHA-256 : abc123...
```

**Via dashboard admin** (quand UI disponible) :
- `/admin/backups.html` → bouton **+ Nouveau backup**

**Via API** :
```bash
curl -X POST http://localhost:8765/api/backups \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: <token>" \
  --cookie "PHPSESSID=<session>" \
  -d '{"keep": 14}'
```

### Consulter les backups

```bash
# Liste
./scripts/restore.sh --list

# === Backups disponibles ===
# #   Fichier                             Taille    Date
# 1   backup_2026-04-22_030000.tar.gz    2.3M      2026-04-22 03:00:00
# 2   backup_2026-04-21_030000.tar.gz    2.1M      2026-04-21 03:00:00
```

### Restaurer

**Mode interactif** (recommandé) :
```bash
./scripts/restore.sh

# Liste affichée
# Numéro du backup à restaurer : 1
# Confirmer : yes
# ✅ Restore terminé
```

**Mode direct** :
```bash
./scripts/restore.sh --latest
./scripts/restore.sh data/backups/backup_2026-04-22_030000.tar.gz
```

**Restore partiel** (seulement une partie) :
```bash
./scripts/restore.sh --latest --only=examens
./scripts/restore.sh --latest --only=banque
```

### Rollback d'un restore

`restore.sh` crée automatiquement un **safety backup** avant de remplacer :

```bash
# Liste des safety backups
ls -la data/backups/safety_*

# Restore ce safety si problème
./scripts/restore.sh data/backups/safety_before_restore_2026-04-22_144500.tar.gz
```

### Vérifier intégrité

Chaque backup a un fichier `.sha256` associé :

```bash
./scripts/backup.sh --verify-only=data/backups/backup_2026-04-22_030000.tar.gz
# ✅ Hash OK
```

### Offsite backup (production)

Pour protection supplémentaire, copier les backups sur un stockage externe :

```bash
# Via rsync (serveur distant)
rsync -av data/backups/ user@backup-server:/backups/ipssi/

# Via S3 (AWS CLI configuré)
aws s3 sync data/backups/ s3://ipssi-backups/examens/

# Via scp
scp data/backups/backup_*.tar.gz backup-server:/backups/ipssi/
```

Ajouter au cron :
```cron
# IPSSI_EXAMENS_BACKUP_OFFSITE
30 3 * * * rsync -av /path/to/data/backups/ user@backup-server:/backups/ipssi/ >> /var/log/ipssi_offsite.log 2>&1
```

---

## 7. Maintenance

### Logs

Tous les logs sont dans `data/logs/` :

| Fichier | Contenu |
|---|---|
| `app.log` | Logs applicatifs généraux |
| `auth.log` | Tentatives login (réussies + échecs) |
| `backups.log` | Historique backups |

**Consulter** :
```bash
tail -f data/logs/app.log

# Filtrer par niveau
grep ERROR data/logs/app.log | tail -20

# Logs d'aujourd'hui
grep "$(date '+%Y-%m-%d')" data/logs/app.log
```

**Rotation** : pas de rotation automatique built-in. Utiliser `logrotate` en production :

```bash
# /etc/logrotate.d/ipssi-examens
/path/to/examens/data/logs/*.log {
    weekly
    rotate 8
    compress
    missingok
    notifempty
    create 0644 www-data www-data
}
```

### Nettoyage manuel

**Sessions expirées** (si pas auto-cleanup) :
```bash
# Supprimer sessions > 7 jours
find data/sessions -name 'sess_*' -mtime +7 -delete
```

**Buckets rate limit** (auto-cleaned, mais si besoin) :
```bash
# Nettoyer > 24h
php -r '
require "backend/bootstrap.php";
$rl = new Examens\Lib\RateLimiter("api_anonyme");
echo "Deleted: " . $rl->cleanup() . PHP_EOL;
'
```

**Logs volumineux** :
```bash
# Archiver logs > 30 jours
find data/logs -name '*.log' -mtime +30 -exec gzip {} \;

# Supprimer archives > 90 jours
find data/logs -name '*.log.gz' -mtime +90 -delete
```

### Mise à jour du système

```bash
cd /path/to/examens

# 1. Backup avant toute mise à jour
./scripts/backup.sh

# 2. Pull latest
git pull origin main

# 3. Relancer tests
php backend/tests/run_all.php

# 4. Vérifier health
curl http://localhost:8765/api/health?detailed=1
```

### Exécution des tests

```bash
# Complet
php backend/tests/run_all.php

# Rapide (unit seulement)
php backend/tests/run_all.php --quick

# Security
php backend/tests/run_all.php --security

# Filter
php backend/tests/run_all.php --filter=banque --verbose
```

---

## 8. Dépannage

### Problème : 500 Internal Server Error

**Diagnostic** :
```bash
# Logs PHP
tail -f /var/log/apache2/error.log   # Apache
tail -f /var/log/nginx/error.log     # Nginx

# Logs app
tail -f data/logs/app.log
```

**Causes courantes** :
- Permissions `data/` insuffisantes → `chmod -R 755 data/`
- `memory_limit` trop bas → monter à 256M dans `php.ini`
- Fichier JSON corrompu → restaurer depuis backup

### Problème : 429 Too Many Requests

L'utilisateur a dépassé son quota rate limit.

**Voir les headers** :
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
Retry-After: 42
```

**Solutions** :
- Attendre `Retry-After` secondes
- Si légitime : reset bucket manuellement :
  ```bash
  rm -f data/_ratelimit/api_*_<hash>.json
  ```

### Problème : Examen inaccessible (404 par code)

**Diagnostic** :
```bash
# Vérifier que l'examen existe
ls data/examens/ | grep EXM-

# Voir un examen
cat data/examens/EXM-XXXX-YYYY.json | jq .status
```

**Causes** :
- Status = `draft` → le prof doit **publier** l'examen
- Status = `closed` ou `archived` → code d'accès désactivé
- Code d'accès erroné → vérifier `access_code` dans le fichier

### Problème : Emails non envoyés

**Diagnostic** :
```bash
# Vérifier configuration SMTP dans data/config/
cat data/config/mailer.json  # si existe

# Logs mailer
grep -i mail data/logs/app.log | tail -20

# Tester depuis CLI
php -r '
require "backend/bootstrap.php";
$m = new Examens\Lib\Mailer();
$m->send("test@example.com", "Test", "<p>Hello</p>");
'
```

**Causes** :
- Server sans relais SMTP → installer `msmtp` ou utiliser Gmail/SendGrid
- Mot de passe SMTP incorrect
- Port bloqué par firewall

### Problème : Tests CI échouent

**Voir logs GitHub Actions** :
- https://github.com/melafrit/maths_IA_niveau_1/actions

**Reproduire en local** :
```bash
# Simuler environnement CI
php backend/tests/run_all.php --no-color
```

**Nettoyage si tests pollutés** :
```bash
# Supprimer données test résiduelles
rm -rf data/examens/EXM-TEST-* data/examens/EXM-E2E-*
rm -rf data/passages/PSG-TEST-* data/passages/PSG-E2E-*
rm -rf data/backups/backup_* data/backups/safety_*
rm -rf data/_ratelimit/*
```

### Problème : Disque plein

**Identifier** :
```bash
du -sh data/*  # Taille par dossier
df -h          # Filesystem global
```

**Solutions** :
- Nettoyer logs (cf. section Maintenance)
- Réduire rotation backups (`--keep=7`)
- Archiver vieux passages (> 1 an) ailleurs

---

## 9. Scénarios complets

### Scénario A — Déploiement initial

1. **Installation** (voir `INSTALLATION.md`)
2. **Créer compte admin** :
   ```bash
   php scripts/init_comptes.php --role=admin \
     --email=admin@ipssi.fr \
     --nom=Admin --prenom=Principal \
     --password='MdpFort123!'
   ```
3. **Créer compte prof** :
   ```bash
   php scripts/init_comptes.php --role=enseignant \
     --email=dupont@ipssi.fr \
     --nom=Dupont --prenom=Jean \
     --password='MdpProf2026!'
   ```
4. **Backup initial** :
   ```bash
   ./scripts/backup.sh
   ```
5. **Installer cron** :
   ```bash
   ./scripts/install-cron.sh --install --time=03:00
   ```
6. **Vérifier health** :
   ```bash
   curl http://localhost:8765/api/health?detailed=1
   ```

### Scénario B — Restore après incident

**Contexte** : examens disparus après manipulation erronée.

1. **Stopper le serveur** (pour éviter écritures concurrentes) :
   ```bash
   # Si PHP dev server
   pkill -f "php -S"
   ```

2. **Lister backups disponibles** :
   ```bash
   ./scripts/restore.sh --list
   ```

3. **Restore** :
   ```bash
   ./scripts/restore.sh --latest
   # Safety backup créé automatiquement
   # Confirmer : yes
   ```

4. **Vérifier** :
   ```bash
   ls data/examens/ | wc -l
   php backend/tests/run_all.php --quick
   ```

5. **Redémarrer serveur**

### Scénario C — Nouvelle session d'examens (début année)

1. **Backup des données actuelles** :
   ```bash
   ./scripts/backup.sh
   # Garder ce backup au safe comme "année 2025-2026"
   cp data/backups/backup_*.tar.gz ~/archives/ipssi_2025-2026.tar.gz
   ```

2. **Archiver examens de l'année** :
   ```bash
   # Via API (exemple pour chaque examen)
   # Status draft → closed → archived
   ```

3. **Nettoyer (optionnel)** :
   ```bash
   # Supprimer les passages > 1 an
   find data/passages -name '*.json' -mtime +365 -ls
   # Après validation :
   find data/passages -name '*.json' -mtime +365 -delete
   ```

4. **Créer nouveaux profs** pour la nouvelle année

### Scénario D — Migration vers nouveau serveur

1. **Sur ancien serveur** :
   ```bash
   ./scripts/backup.sh
   # Copier le backup
   scp data/backups/backup_*.tar.gz nouveau-serveur:/tmp/
   ```

2. **Sur nouveau serveur** (après installation vierge) :
   ```bash
   cd /path/to/examens
   # Placer le backup
   cp /tmp/backup_*.tar.gz data/backups/
   # Restaurer
   ./scripts/restore.sh --latest --yes
   ```

3. **Vérifier** :
   ```bash
   curl http://localhost:8765/api/health?detailed=1
   php backend/tests/run_all.php --quick
   ```

### Scénario E — Incident sécurité (user compromis)

1. **Désactiver immédiatement le compte** :
   ```bash
   # Éditer data/comptes/USR-xxxx.json
   # "active": false
   ```

2. **Invalider toutes les sessions du user** :
   ```bash
   # Pas d'index session → user, donc purger toutes les sessions
   rm data/sessions/sess_*
   # Les users devront se reconnecter
   ```

3. **Reset password** :
   ```bash
   php scripts/reset_password.php user@compromis.fr
   ```

4. **Audit logs** :
   ```bash
   grep "user@compromis.fr" data/logs/auth.log | tail -50
   ```

5. **Vérifier passages suspects** (dernières 24h) :
   ```bash
   grep -l "compromis" data/passages/*.json | while read f; do
     jq '.student_info.email, .anomalies_count, .start_time' "$f"
   done
   ```

---

## 📞 Contact support

- **Email** : m.elafrit@ecole-ipssi.net
- **Repo** : https://github.com/melafrit/maths_IA_niveau_1
- **Issues** : https://github.com/melafrit/maths_IA_niveau_1/issues

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
