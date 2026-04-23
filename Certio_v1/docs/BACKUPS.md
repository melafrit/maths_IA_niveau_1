# 💾 Documentation Backups — IPSSI Examens

Guide complet pour sauvegarder et restaurer les données de la plateforme.

> **Scope** : données `data/` (examens, passages, comptes, banque, sessions, config)
> **Format** : archives tar.gz avec hash SHA-256
> **Rotation** : automatique (par défaut 14 derniers)

---

## 🎯 Concepts

### Ce qui est sauvegardé

Les backups incluent tous les sous-dossiers critiques de `data/` :

| Dossier | Contenu |
|---|---|
| `examens/` | Tous les examens (draft, published, closed, archived) |
| `passages/` | Tous les passages étudiants (avec réponses + signatures) |
| `comptes/` | Utilisateurs (admin, profs) avec hashes bcrypt |
| `banque/` | Questions de la banque (modules/chapitres/thèmes) |
| `sessions/` | Sessions PHP actives |
| `config/` | Configuration application |

### Ce qui n'est PAS sauvegardé

- `data/logs/` : les logs applicatifs (trop volumineux, pas critique)
- `data/backups/` : évite la récursion
- `data/tmp/` : fichiers temporaires

---

## 📦 Backup manuel

### Via script bash

```bash
# Backup standard (garde 14 derniers)
./scripts/backup.sh

# Personnaliser la rotation
./scripts/backup.sh --keep=30

# Mode silencieux (pour cron)
./scripts/backup.sh --quiet

# Verifier un backup existant
./scripts/backup.sh --verify-only=data/backups/backup_2026-04-22_030000.tar.gz
```

### Via API REST (admin dashboard)

```bash
# Declencher un backup
curl -X POST http://localhost/api/backups \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: xxx" \
  --cookie "PHPSESSID=xxx" \
  -d '{"keep": 14}'

# Reponse
{
  "ok": true,
  "data": {
    "backup_id": "backup_2026-04-22_143000",
    "duration_sec": 0.84,
    "info": { ... }
  }
}
```

---

## ♻️ Backup automatique (cron)

### Installation

```bash
# Voir la ligne cron à ajouter
./scripts/install-cron.sh

# Installer automatiquement (03:00 du matin par defaut)
./scripts/install-cron.sh --install

# Personnaliser l'heure
./scripts/install-cron.sh --install --time=02:30

# Desinstaller
./scripts/install-cron.sh --remove
```

### Vérifier

```bash
# Lister le crontab
crontab -l

# Tester le script manuellement
./scripts/backup.sh

# Voir les logs
tail -f data/logs/backups.log
```

### Exemple de ligne cron générée

```
# IPSSI_EXAMENS_BACKUP
0 3 * * * /path/to/examens/scripts/backup.sh --quiet --keep=14
```

---

## 🔄 Restauration

### Restore interactif

```bash
# Liste + choix interactif
./scripts/restore.sh

# Affiche :
# === Backups disponibles ===
# #   Fichier                             Taille    Date
# 1   backup_2026-04-22_030000.tar.gz    2.3M      2026-04-22 03:00:00
# 2   backup_2026-04-21_030000.tar.gz    2.1M      2026-04-21 03:00:00
# ...
# Numéro du backup à restaurer (ou 'q' pour quitter) :
```

### Restore direct

```bash
# Restore du plus recent
./scripts/restore.sh --latest

# Restore d'un fichier precis
./scripts/restore.sh data/backups/backup_2026-04-22_030000.tar.gz

# Restore sans confirmation (scripting)
./scripts/restore.sh --latest --yes

# Restore partiel (examens uniquement)
./scripts/restore.sh --latest --only=examens

# Lister sans restaurer
./scripts/restore.sh --list
```

### Options avancées

```bash
# Sans backup de securite (risque : pas de rollback possible)
./scripts/restore.sh --latest --no-safety-backup

# Avec tout : non-interactif + restore partiel
./scripts/restore.sh --latest --only=banque --yes
```

### Sécurité : backup automatique avant restore

Par défaut, `restore.sh` crée un **backup de sécurité** avant de remplacer les données :

```
data/backups/safety_before_restore_2026-04-22_144500.tar.gz
```

Cela permet de rollback en cas de problème :

```bash
./scripts/restore.sh data/backups/safety_before_restore_2026-04-22_144500.tar.gz
```

---

## 🔐 Vérification d'intégrité

Chaque backup est accompagné d'un fichier `.sha256` contenant son hash :

```
backup_2026-04-22_030000.tar.gz
backup_2026-04-22_030000.tar.gz.sha256
```

### Vérifier manuellement

```bash
# Via script
./scripts/backup.sh --verify-only=data/backups/backup_2026-04-22_030000.tar.gz

# Via sha256sum
sha256sum -c data/backups/backup_2026-04-22_030000.tar.gz.sha256
```

### Vérification automatique

- `backup.sh` : calcule et stocke le hash à chaque création
- `restore.sh` : vérifie le hash avant restauration (refuse si mismatch)

---

## 🌐 API REST

Toutes les routes nécessitent une authentification **admin**.

### `GET /api/backups`

Liste tous les backups disponibles.

```bash
curl -X GET http://localhost/api/backups \
  --cookie "PHPSESSID=xxx"
```

Réponse :
```json
{
  "ok": true,
  "data": {
    "backups": [
      {
        "id": "backup_2026-04-22_030000",
        "filename": "backup_2026-04-22_030000.tar.gz",
        "path": "/path/to/data/backups/backup_2026-04-22_030000.tar.gz",
        "size": 2347823,
        "size_human": "2.2M",
        "created_at": "2026-04-22T03:00:00+02:00",
        "hash": "abc123...def456",
        "verified": true
      }
    ]
  }
}
```

### `GET /api/backups/stats`

```json
{
  "ok": true,
  "data": {
    "total_backups": 14,
    "total_size_bytes": 32870123,
    "total_size_human": "31.3M",
    "oldest": "2026-04-08T03:00:00+02:00",
    "newest": "2026-04-22T03:00:00+02:00"
  }
}
```

### `POST /api/backups`

Crée un nouveau backup (body optionnel : `{"keep": 30}`).

### `GET /api/backups/{id}`

Infos détaillées d'un backup spécifique.

### `GET /api/backups/{id}/verify`

Vérifie le hash SHA-256.

```json
{
  "ok": true,
  "data": {
    "valid": true,
    "expected": "abc123...",
    "actual": "abc123..."
  }
}
```

### `GET /api/backups/{id}/download`

Télécharge l'archive (content-type: application/gzip).

### `DELETE /api/backups/{id}`

Supprime un backup (archive + fichier hash).

---

## 🗓️ Stratégie recommandée

### Production classique

- **Fréquence** : backup quotidien à 03:00
- **Rotation** : 14 derniers (2 semaines)
- **Surveillance** : check `data/logs/backups.log` hebdomadaire

### Production critique

- **Fréquence** : backup quotidien + avant chaque déploiement
- **Rotation** : 30 derniers (1 mois)
- **Offsite** : copier les backups sur stockage externe (rsync, S3, etc.)

### Développement

- Backup manuel avant opérations risquées
- Garder 3-5 backups max

---

## 🔧 Dépannage

### Le backup échoue avec "Lock file present"

Un autre backup est en cours. Attendre ou supprimer le lock :
```bash
rm -f /tmp/ipssi_examens_backup.lock
```

### "Verification hash : mismatch"

Le fichier a été corrompu ou modifié. Ne pas restaurer ce backup.

### Permissions refusées

```bash
chmod +x scripts/*.sh
chmod -R u+w data/backups/
```

### Le cron ne se lance pas

Vérifier que le script est exécutable :
```bash
ls -la scripts/backup.sh
# Doit montrer -rwxr-xr-x
```

Vérifier les logs système du cron :
```bash
grep CRON /var/log/syslog | tail -20
```

---

## 📁 Structure des fichiers

```
examens/
├── scripts/
│   ├── backup.sh           # Creation backup
│   ├── restore.sh          # Restauration
│   └── install-cron.sh     # Helper cron
├── backend/
│   ├── lib/
│   │   └── BackupManager.php    # API PHP
│   └── api/
│       └── backups.php          # Routes REST
└── data/
    ├── backups/
    │   ├── backup_*.tar.gz      # Archives
    │   ├── backup_*.tar.gz.sha256  # Hashes
    │   └── safety_before_restore_*.tar.gz  # Backups de securite
    └── logs/
        └── backups.log          # Journal
```

---

## 🧪 Tests

Les backups sont testés avec 19 tests unitaires :

```bash
# Tests BackupManager
php backend/test_backup_manager.php

# Via harness complet
php backend/tests/run_all.php --filter=backup
```

Couverture :
- ✅ Création et format
- ✅ Liste et tri
- ✅ Hash SHA-256
- ✅ Verify / invalidation
- ✅ Path traversal refusé
- ✅ Stats globales
- ✅ Delete propre

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
