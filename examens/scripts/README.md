# Scripts utilitaires

## Scripts prévus

### Migration
- **`migrer_qcm_j1j2.py`** — Importe les 50 questions J1-J2 existantes dans la banque (Phase P4)

### Administration
- **`init_comptes.php`** — Crée le premier compte admin en ligne de commande (Phase P1)
- **`reset_password.php`** — Reset mot de passe en CLI (Phase P1)

### Sauvegardes
- **`backup_local.php`** — Cron OVH quotidien 3h (Phase P8)
- **`backup_github.sh`** — Cron OVH hebdo dimanche 4h (Phase P8)
- **`restore.php`** — Restauration depuis backup (Phase P8)

### Maintenance RGPD
- **`cleanup_retention.php`** — Cron quotidien nettoyage données expirées (Phase P6)

### Déploiement
- **`deploy_ovh.sh`** — Script de déploiement automatisé vers OVH (Phase P9)

## Usage

Scripts PHP :
```bash
php scripts/init_comptes.php
```

Scripts Shell (OVH cron) :
```bash
bash scripts/backup_github.sh
```

*À compléter de P1 à P9*
