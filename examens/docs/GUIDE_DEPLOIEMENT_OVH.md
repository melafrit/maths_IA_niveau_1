# 🚀 Guide de déploiement OVH

> ⚠️ **Document placeholder — sera complété progressivement de P1 à P9**
>
> Chaque phase ajoutera sa contribution au déploiement.

## 📋 Contenu prévu

### Partie 1 — Prérequis (P1)
- Choisir une offre OVH mutualisée adaptée
- Vérifier PHP 7.4+ disponible
- Préparer le nom de domaine
- Configurer FTP d'accès

### Partie 2 — Configuration initiale (P1)
- Cloner le repo GitHub
- Uploader les fichiers sur OVH
- Copier `config.sample.php` en `config.php`
- Créer le premier compte admin via script CLI

### Partie 3 — Sécurité (P1 + P8)
- HTTPS obligatoire (Let's Encrypt via OVH)
- Configuration `.htaccess`
- Permissions fichiers (chmod)
- Protection des dossiers sensibles

### Partie 4 — Emails (P6)
- Configuration SMTP OVH
- Configuration SPF dans DNS
- Configuration DKIM
- Tests de délivrabilité

### Partie 5 — Cron (P8)
- Configurer les 3 tâches cron :
  - Backup quotidien 3h
  - Backup GitHub hebdo dimanche 4h
  - Nettoyage RGPD quotidien minuit

### Partie 6 — Tests post-déploiement (P9)
- Checklist de 30 points
- Tests E2E en staging
- Monitoring (Uptime Robot)

### Partie 7 — Procédure de restauration
- Depuis backup local OVH
- Depuis backup GitHub privé
- Temps de récupération estimé (RTO)

### Partie 8 — Maintenance
- Mise à jour PHP (annuelle)
- Renouvellement SSL (auto avec Let's Encrypt)
- Purge des logs anciens
- Vérifications de sécurité périodiques

---

*À compléter progressivement de P1 à P9*

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
