# Données de la plateforme

Ce dossier contient toutes les données persistantes.

## Structure

- **`banque/`** — Banque de questions (JSON par chapitre)
- **`examens/`** — Passages d'examens (1 dossier par code examen)
- **`comptes/`** — Comptes enseignants (hashes bcrypt, JAMAIS en clair)
- **`config/`** — Configuration globale (paramètres admin)

## ⚠️ Important

- **Ce dossier ne doit PAS être commité en production** (voir `.gitignore`)
- Les données sont **personnelles** et relèvent du RGPD
- Backups quotidiens (cf. `scripts/backup_local.php`)
- Sync hebdomadaire GitHub privé (cf. `scripts/backup_github.sh`)

## Permissions OVH

```bash
chmod 750 data/
chmod 750 data/*/
chmod 640 data/*/*.json
```

*À compléter de P1 à P9*
