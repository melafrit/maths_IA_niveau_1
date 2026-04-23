# 🚀 Guide de déploiement OVH — IPSSI Examens

> Déploiement **production** complet sur OVH.
> Du domaine au site accessible publiquement, avec sécurité et monitoring.

---

## 📖 Sommaire

1. [Pré-requis](#1-pré-requis)
2. [Choix du type d'hébergement](#2-choix-du-type-dhébergement)
3. [Scénario A : OVH mutualisé](#3-scénario-a--ovh-mutualisé)
4. [Scénario B : OVH VPS](#4-scénario-b--ovh-vps)
5. [Configuration DNS](#5-configuration-dns)
6. [Certificat SSL (HTTPS)](#6-certificat-ssl-https)
7. [Sécurisation production](#7-sécurisation-production)
8. [Emails avec MX OVH](#8-emails-avec-mx-ovh)
9. [Backups offsite](#9-backups-offsite)
10. [Monitoring production](#10-monitoring-production)
11. [Mise à jour et maintenance](#11-mise-à-jour-et-maintenance)
12. [Dépannage](#12-dépannage)

---

## 1. Pré-requis

### Côté compte OVH

- [ ] Compte OVH créé
- [ ] Domaine acheté (ex: `examens-ipssi.fr`) — coût ~7€/an
- [ ] Offre d'hébergement choisie (voir section 2)

### Côté local

- [ ] Projet fonctionnel en local (voir `INSTALLATION.md`)
- [ ] Tests passent : `php backend/tests/run_all.php` → 389/389
- [ ] Secrets personnalisés dans `config.php`
- [ ] Git installé

### Outils utiles

- **SSH client** : Terminal (macOS/Linux) ou Git Bash (Windows)
- **SFTP client** : FileZilla, Cyberduck, WinSCP
- **Éditeur distant** : VS Code avec extension Remote-SSH

---

## 2. Choix du type d'hébergement

### OVH Mutualisé (recommandé pour démarrer)

**Avantages** :
- 🟢 **Pas cher** : ~4-8€/mois (offre "Perso" ou "Pro")
- 🟢 **Zéro maintenance** serveur (OVH gère)
- 🟢 **PHP configuré** d'origine
- 🟢 **Interface web** (Manager OVH) intuitive
- 🟢 **SSL gratuit** (Let's Encrypt auto-installé)

**Limites** :
- 🔴 Pas d'accès SSH root
- 🔴 Pas de cron fin (minimum 5 min d'intervalle sur Perso)
- 🔴 `exec()` PHP limité (mais suffisant pour backup.sh)
- 🔴 Ressources partagées avec autres sites

**Verdict** : ✅ **Idéal pour 50-200 utilisateurs simultanés**, usage IPSSI typique.

### OVH VPS

**Avantages** :
- 🟢 **Contrôle total** (root)
- 🟢 **Cron à la minute**
- 🟢 **Scalable** (CPU/RAM ajustables)
- 🟢 **Plus performant**

**Limites** :
- 🔴 **Plus cher** : 5-20€/mois selon gabarit
- 🔴 **Maintenance serveur** à votre charge (updates, sécurité)
- 🔴 Config PHP manuelle

**Verdict** : ✅ **Recommandé au-delà de 500 utilisateurs simultanés**.

### Comparaison rapide

| Critère | Mutualisé Pro | VPS Starter | VPS Value |
|---|:-:|:-:|:-:|
| Prix/mois | ~8€ | ~5€ | ~10€ |
| CPU | partagé | 1 vCore | 2 vCores |
| RAM | limitée | 2 Go | 4 Go |
| Disque | 500 Go | 40 Go SSD | 80 Go SSD |
| Bande passante | illimitée | 250 Mbps | 1 Gbps |
| SSH | ❌ | ✅ | ✅ |
| Cron minute | ❌ | ✅ | ✅ |
| Maintenance | OVH | Vous | Vous |

---

## 3. Scénario A : OVH mutualisé

### Étape 3.1 — Commander l'hébergement

1. Connexion OVH Manager : https://www.ovh.com/manager/
2. **Commander** → **Hébergement Web** → Offre "Perso" ou "Pro"
3. Associer à votre domaine existant ou en commander un nouveau
4. Paiement (plusieurs moyens disponibles)

### Étape 3.2 — Activer l'hébergement

Attendre quelques minutes → l'hébergement apparaît dans **Hébergements Web** du Manager.

Récupérer les infos :
- **FTP host** : `ftp.cluster0XX.hosting.ovh.net`
- **FTP login** : `votrenomXX`
- **FTP password** : généré (modifiable)
- **Chemin racine** : `/home/votrenomXX/` ou `/www/`

### Étape 3.3 — Configurer PHP 8.3

Dans le **Manager OVH** :

1. Aller dans **Hébergements** → votre hébergement
2. Onglet **Informations générales** → **Modifier la version de PHP**
3. Sélectionner **PHP 8.3**
4. Appliquer (prend quelques minutes)

Vérifier en local sur votre serveur :
```bash
# Créer un fichier de test
echo '<?php phpinfo();' > /tmp/info.php
# Upload via FTP → visible sur https://votredomaine.fr/info.php
# SUPPRIMER après vérification (infos sensibles)
```

### Étape 3.4 — Upload du projet via FTP

#### Option A : FileZilla (interface graphique)

1. Installer **FileZilla** : https://filezilla-project.org/
2. **Fichier** → **Gestionnaire de sites** → **Nouveau site**
3. Remplir :
   - Hôte : `ftp.cluster0XX.hosting.ovh.net`
   - Port : 21
   - Type : FTP sur TLS explicite
   - User : `votrenomXX`
   - Password : votre password FTP
4. Connecter
5. Côté local : naviguer vers `maths_IA_niveau_1/examens/`
6. Côté serveur : aller dans `/www/` (dossier racine web)
7. Upload **tout le contenu** de `examens/` dans `/www/`

#### Option B : Ligne de commande (lftp)

```bash
# Installer lftp si besoin
brew install lftp      # macOS
sudo apt install lftp  # Linux

# Synchroniser
lftp -u votrenomXX,VotreMdp ftp.cluster0XX.hosting.ovh.net <<EOF
mirror -R --delete --exclude data/sessions/ --exclude data/logs/ \
       examens/ /www/
quit
EOF
```

#### Option C : SSH + Git clone (si SSH disponible)

Certaines offres OVH permettent SSH :

```bash
# Connexion SSH
ssh votrenomXX@ssh.cluster0XX.hosting.ovh.net

# Cloner directement
cd ~/www/
git clone https://github.com/melafrit/maths_IA_niveau_1.git .
# Attention : cela met TOUT le repo dans /www/
# Préférer :
cd ~
git clone https://github.com/melafrit/maths_IA_niveau_1.git
# Puis configurer le docroot sur maths_IA_niveau_1/examens/backend/public/
```

### Étape 3.5 — Configurer le docroot

Le serveur doit pointer vers `examens/backend/public/` pour que les URLs fonctionnent.

Créer un `.htaccess` à la racine de `/www/` :

```apache
# /www/.htaccess

# Redirection HTTPS forcée
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Redirection vers le sous-dossier public
RewriteCond %{REQUEST_URI} !^/examens/backend/public/
RewriteRule ^(.*)$ /examens/backend/public/$1 [L]
```

Ou **mieux** : dans le Manager OVH → **Hébergements** → onglet **Multisite** :
- Domaine : `examens-ipssi.fr`
- **Dossier racine** : `examens/backend/public`

### Étape 3.6 — Permissions

Via FTP, vérifier permissions :

| Dossier | Permissions |
|---|:-:|
| `data/` | 755 |
| `data/*/` | 755 |
| `backend/public/` | 755 |
| `*.php` | 644 |
| `scripts/*.sh` | 755 (pour exécution) |

Via FileZilla : clic droit → **Permissions du fichier**.

### Étape 3.7 — Première connexion

Ouvrir : `https://examens-ipssi.fr/api/health`

Réponse attendue :
```json
{"ok":true,"data":{"status":"ok","version":"0.8.0",...}}
```

### Étape 3.8 — Créer comptes admin via SSH ou FTP

Si SSH disponible :
```bash
cd ~/
php scripts/init_comptes.php --role=admin \
  --email=admin@ipssi.fr \
  --nom=Admin --prenom=Principal \
  --password='MdpFortProdUnique2026!'
```

Sans SSH : créer localement les fichiers JSON dans `data/comptes/` et les uploader en FTP.

### Étape 3.9 — Configurer cron (mutualisé OVH)

Manager OVH → **Hébergements** → votre hébergement → onglet **Tâches CRON** :

1. **Ajouter une tâche**
2. Configuration :
   - **Commande** : `/home/votrenomXX/www/scripts/backup.sh --quiet --keep=14`
   - **Fréquence** : Quotidien à 03:00
   - **Engine** : PHP 8.3 (ou shell selon offre)

### Checklist déploiement mutualisé

- [ ] Offre achetée + activée
- [ ] PHP 8.3 configuré
- [ ] Projet uploadé dans `/www/` (ou sous-dossier)
- [ ] Docroot configuré sur `backend/public/`
- [ ] `config.php` personnalisé (secrets uniques)
- [ ] Permissions `data/` correctes (755)
- [ ] `/api/health` retourne OK
- [ ] Comptes admin créés
- [ ] Cron backup configuré
- [ ] SSL HTTPS actif (voir section 6)

---

## 4. Scénario B : OVH VPS

### Étape 4.1 — Commander un VPS

1. Manager OVH → **Serveurs** → **VPS**
2. Choisir **VPS Starter** (minimum) ou **Value** (recommandé)
3. OS : **Ubuntu 22.04 LTS** (recommandé)
4. Paiement

### Étape 4.2 — Première connexion SSH

OVH envoie par email :
- IP du VPS (ex: `51.75.xxx.xxx`)
- User : `ubuntu`
- Mot de passe initial

```bash
ssh ubuntu@51.75.xxx.xxx
# Entrer le mdp

# Passer root
sudo -i

# Changer le mot de passe ubuntu
passwd ubuntu
```

### Étape 4.3 — Mises à jour système

```bash
apt update && apt upgrade -y
apt install -y curl git vim ufw unzip jq
```

### Étape 4.4 — Installer Nginx + PHP 8.3

```bash
# Nginx
apt install -y nginx

# PHP 8.3 + extensions
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip

# Démarrer services
systemctl enable nginx php8.3-fpm
systemctl start nginx php8.3-fpm
```

### Étape 4.5 — Firewall UFW

```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp     # SSH
ufw allow 80/tcp     # HTTP
ufw allow 443/tcp    # HTTPS
ufw enable

# Vérifier
ufw status
```

### Étape 4.6 — Créer un user dédié (pas root)

```bash
# Créer un user pour l'app
adduser --disabled-password --gecos "" ipssi
usermod -aG www-data ipssi

# Ajouter votre clé SSH
su - ipssi
mkdir -p ~/.ssh && chmod 700 ~/.ssh
vim ~/.ssh/authorized_keys
# Coller votre clé publique (cat ~/.ssh/id_ed25519.pub sur votre machine)
chmod 600 ~/.ssh/authorized_keys
exit
```

Tester :
```bash
ssh ipssi@51.75.xxx.xxx
# Devrait se connecter sans mdp
```

### Étape 4.7 — Cloner le projet

```bash
su - ipssi

cd ~
git clone https://github.com/melafrit/maths_IA_niveau_1.git
cd maths_IA_niveau_1/examens

# Permissions
chmod +x scripts/*.sh
chmod -R 755 data/
```

### Étape 4.8 — Configuration Nginx

Créer `/etc/nginx/sites-available/ipssi-examens` :

```nginx
server {
    listen 80;
    server_name examens-ipssi.fr www.examens-ipssi.fr;

    root /home/ipssi/maths_IA_niveau_1/examens/backend/public;
    index index.php;

    # Logs
    access_log /var/log/nginx/ipssi-access.log;
    error_log /var/log/nginx/ipssi-error.log;

    # Taille max upload
    client_max_body_size 50M;

    # Routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Assets frontend
    location /assets/ {
        alias /home/ipssi/maths_IA_niveau_1/examens/frontend/assets/;
        try_files $uri =404;
    }

    location /admin/ {
        alias /home/ipssi/maths_IA_niveau_1/examens/frontend/admin/;
        try_files $uri $uri/ =404;
    }

    location /etudiant/ {
        alias /home/ipssi/maths_IA_niveau_1/examens/frontend/etudiant/;
        try_files $uri $uri/ =404;
    }

    # PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    # Bloquer accès aux dossiers sensibles
    location ~ ^/(data|backend/lib|backend/tests)/ {
        deny all;
        return 404;
    }

    # Bloquer accès aux .env et autres
    location ~ /\.(ht|env|git) {
        deny all;
    }

    # Headers sécurité
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options SAMEORIGIN;
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy strict-origin-when-cross-origin;
}
```

Activer :
```bash
ln -s /etc/nginx/sites-available/ipssi-examens /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default   # si pas utilisé
nginx -t    # Tester la config
systemctl reload nginx
```

### Étape 4.9 — Vérifier

```bash
curl http://examens-ipssi.fr/api/health
# {"ok":true,"data":{...}}
```

### Étape 4.10 — Configurer cron

```bash
su - ipssi
crontab -e

# Ajouter :
# Backup quotidien 03:00
0 3 * * * /home/ipssi/maths_IA_niveau_1/examens/scripts/backup.sh --quiet --keep=14

# Health check toutes les 5 min (log si error)
*/5 * * * * curl -s "http://localhost/api/health?detailed=1" | jq -r '.data.status' | grep -v "ok" && echo "Health alert at $(date)" | mail -s "IPSSI Examens Health Alert" admin@ipssi.fr
```

### Checklist déploiement VPS

- [ ] VPS commandé + OS installé
- [ ] Mises à jour système appliquées
- [ ] Nginx + PHP-FPM installés
- [ ] Firewall UFW configuré
- [ ] User dédié créé
- [ ] SSH par clé configuré (désactiver password)
- [ ] Projet cloné
- [ ] Nginx configuré
- [ ] `/api/health` répond via HTTP
- [ ] Comptes admin créés
- [ ] Cron backup configuré
- [ ] SSL HTTPS activé (section 6)

---

## 5. Configuration DNS

### Étape 5.1 — Accéder à la zone DNS

Manager OVH → **Domaines** → votre domaine → onglet **Zone DNS**.

### Étape 5.2 — Configurer les enregistrements

**Pour mutualisé** :
Les enregistrements sont **préconfigurés automatiquement** par OVH.

**Pour VPS** :

Ajouter/modifier :

| Type | Nom | Valeur |
|---|---|---|
| A | `@` | IP de votre VPS (ex: `51.75.xxx.xxx`) |
| A | `www` | IP de votre VPS |
| TXT | `@` | `v=spf1 mx ~all` (si email) |

### Étape 5.3 — Propagation

Vérifier la propagation :
```bash
# macOS/Linux
dig examens-ipssi.fr
nslookup examens-ipssi.fr

# Outil en ligne
# https://dnschecker.org/
```

Propagation : entre **quelques minutes** et **24h** max.

---

## 6. Certificat SSL (HTTPS)

### Scénario A : Mutualisé

OVH fournit **Let's Encrypt gratuit** et **automatique**.

1. Manager OVH → **Hébergement** → onglet **Multisite**
2. Votre domaine → **SSL** : activer
3. Attendre 15-30 min

Tester : `https://examens-ipssi.fr/api/health`

### Scénario B : VPS (Let's Encrypt via Certbot)

```bash
# Installer Certbot
apt install -y certbot python3-certbot-nginx

# Générer le certificat
certbot --nginx -d examens-ipssi.fr -d www.examens-ipssi.fr \
  --non-interactive --agree-tos --email admin@ipssi.fr

# Certbot ajoute automatiquement :
# - Redirection HTTP → HTTPS dans nginx
# - Renouvellement auto (timer systemd)

# Vérifier le renouvellement auto
systemctl status certbot.timer
certbot renew --dry-run   # Test
```

### Vérification

```bash
curl -I https://examens-ipssi.fr
# HTTP/2 200

# Vérifier le certificat
openssl s_client -connect examens-ipssi.fr:443 -servername examens-ipssi.fr </dev/null
```

Grade SSL : https://www.ssllabs.com/ssltest/ → viser **A** ou **A+**.

---

## 7. Sécurisation production

### Étape 7.1 — Secrets uniques

Générer de nouveaux secrets :

```bash
php -r 'echo bin2hex(random_bytes(32)) . PHP_EOL;'  # csrf_secret
php -r 'echo bin2hex(random_bytes(32)) . PHP_EOL;'  # signature_salt
```

Éditer `backend/config.php` :

```php
return [
    'app' => ['env' => 'prod'],
    'security' => [
        'csrf_secret' => '<SECRET1>',
        'signature_salt' => '<SECRET2>',
    ],
    // ...
];
```

### Étape 7.2 — Désactiver debug

S'assurer dans `php.ini` ou `.user.ini` :

```ini
display_errors = Off
log_errors = On
error_log = /home/ipssi/maths_IA_niveau_1/examens/data/logs/php_errors.log
expose_php = Off
```

### Étape 7.3 — Headers de sécurité supplémentaires

Ajouter dans Nginx (déjà inclus dans section 4.8) :

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net unpkg.com cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com; font-src 'self' fonts.gstatic.com; img-src 'self' data:; connect-src 'self';" always;
```

**Note** : le CSP doit autoriser les CDN utilisés (Recharts, KaTeX, etc.).

### Étape 7.4 — Rate limiting (déjà intégré)

Le **RoleRateLimiter** fonctionne automatiquement. Configurer seuils dans `config.php` si besoin.

### Étape 7.5 — Protection fichiers sensibles

Vérifier que `data/` et `backend/lib/` sont bloqués en HTTP :

```bash
# Doit retourner 404 (pas le contenu)
curl https://examens-ipssi.fr/data/comptes/USR-admin.json
curl https://examens-ipssi.fr/backend/lib/Auth.php
```

### Étape 7.6 — Fail2ban (VPS uniquement)

Protection contre bruteforce SSH + HTTP :

```bash
apt install -y fail2ban

cat > /etc/fail2ban/jail.local <<EOF
[DEFAULT]
bantime  = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true

[nginx-http-auth]
enabled = true
EOF

systemctl restart fail2ban
fail2ban-client status
```

---

## 8. Emails avec MX OVH

### Étape 8.1 — Commander un plan Email Pro

OVH propose des emails avec votre domaine : `noreply@examens-ipssi.fr`

Manager OVH → **Emails** → Commander plan
- **Email Pro** : 1€/mois/boîte (suffisant)

### Étape 8.2 — Créer une boîte

Manager OVH → **Emails** → votre plan → **Créer une adresse**
- Adresse : `noreply@examens-ipssi.fr`
- Mot de passe : fort (stocker au safe)

### Étape 8.3 — Configurer dans la plateforme

Éditer `backend/config.php` :

```php
return [
    // ...
    'mail' => [
        'smtp_host' => 'ssl0.ovh.net',
        'smtp_port' => 465,
        'smtp_user' => 'noreply@examens-ipssi.fr',
        'smtp_pass' => '<MdpBoite>',
        'smtp_encryption' => 'ssl',
        'from_email' => 'noreply@examens-ipssi.fr',
        'from_name' => 'IPSSI Examens',
        'reply_to' => 'admin@ipssi.fr',
    ],
];
```

### Étape 8.4 — Tester l'envoi

```bash
php -r '
require "backend/bootstrap.php";
$m = new Examens\Lib\Mailer();
$result = $m->send(
    "votre-email@test.fr",
    "Test envoi IPSSI",
    "<h1>Ça marche !</h1><p>Test depuis le serveur.</p>"
);
var_dump($result);
'
```

### Étape 8.5 — Configuration SPF/DKIM (anti-spam)

Zone DNS → ajouter :

```
Type: TXT
Nom: @
Valeur: v=spf1 include:mx.ovh.com ~all
```

Pour **DKIM**, OVH génère automatiquement. Vérifier dans Manager Emails → Configuration.

Tester : https://www.mail-tester.com/ (envoyer un email de test, score sur 10 visé > 9).

---

## 9. Backups offsite

### Important : Stocker les backups **en dehors** du serveur de production

En cas de perte totale du VPS, vos backups locaux seraient aussi perdus.

### Option A : vers serveur distant (rsync)

Ajouter au crontab :

```bash
# Après le backup local, copier vers serveur sauvegarde
30 3 * * * rsync -av --delete /home/ipssi/maths_IA_niveau_1/examens/data/backups/ backup-user@backup-server.fr:/backups/ipssi/ >> /var/log/ipssi_offsite.log 2>&1
```

### Option B : vers OVH Object Storage

```bash
# Installer awscli (compatible S3)
apt install -y awscli

# Configurer credentials OVH Cloud Archive / Object Storage
aws configure
# Endpoint: https://storage.gra.cloud.ovh.net
# Region: gra

# Script à ajouter au cron
cat > /home/ipssi/scripts/offsite-backup.sh <<'EOF'
#!/bin/bash
aws s3 sync /home/ipssi/maths_IA_niveau_1/examens/data/backups/ \
    s3://ipssi-backups/examens/ \
    --endpoint-url https://storage.gra.cloud.ovh.net
EOF

chmod +x /home/ipssi/scripts/offsite-backup.sh

# Cron : 1h après le backup local
crontab -e
# 0 4 * * * /home/ipssi/scripts/offsite-backup.sh
```

### Option C : Dropbox / Google Drive (mutualisé)

Utiliser **rclone** si SSH disponible :

```bash
# Installer rclone
curl https://rclone.org/install.sh | sudo bash

# Configurer Dropbox
rclone config
# → n) New remote
# → dropbox
# → authentification web

# Test
rclone copy /home/ipssi/maths_IA_niveau_1/examens/data/backups/ dropbox:ipssi-backups/

# Cron
crontab -e
# 45 3 * * * rclone copy /home/ipssi/.../data/backups/ dropbox:ipssi-backups/
```

### Vérification restoration

**Tester** que vos backups offsite fonctionnent, au moins 1x/mois :

1. Télécharger un backup distant
2. Sur un autre serveur (ou local) : extraire + restorer
3. Vérifier intégrité

---

## 10. Monitoring production

### Étape 10.1 — Endpoint health externe

Configurer un monitoring externe qui ping `/api/health` :

#### UptimeRobot (gratuit)

1. Créer compte : https://uptimerobot.com/
2. **New Monitor** :
   - Type : HTTP(s)
   - URL : `https://examens-ipssi.fr/api/health`
   - Interval : 5 min
   - Alert : email + SMS
3. Activer **HTTPS verification**

#### Via script maison

```bash
# /home/ipssi/scripts/health-monitor.sh
#!/bin/bash
STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://examens-ipssi.fr/api/health)
if [ "$STATUS" != "200" ]; then
    echo "IPSSI DOWN ($STATUS) at $(date)" | mail -s "🚨 IPSSI ALERTE" admin@ipssi.fr
fi
```

Cron :
```bash
*/5 * * * * /home/ipssi/scripts/health-monitor.sh
```

### Étape 10.2 — Dashboard monitoring interne

Accessible via : `https://examens-ipssi.fr/admin/monitoring.html` (admin only).

Vérifier hebdomadairement :
- Espace disque
- Âge dernier backup
- Nombre de sessions actives
- Extensions PHP

### Étape 10.3 — Logs centralisés

**VPS seulement** :

```bash
# Installer logwatch (rapport quotidien par email)
apt install -y logwatch
# Configurer email dans /etc/logwatch/conf/logwatch.conf
```

Ou utiliser un service comme **Papertrail** / **Loggly** / **Datadog**.

### Étape 10.4 — Alertes critiques

Configurer alertes email/SMS sur :
- Dashboard monitoring → status `error`
- Health endpoint → 503
- Disque > 80% plein
- CPU > 90% pendant > 10 min
- RAM > 85%

---

## 11. Mise à jour et maintenance

### Workflow mise à jour (avec Git)

```bash
# Se connecter SSH
ssh ipssi@examens-ipssi.fr
cd ~/maths_IA_niveau_1/examens

# 1. Backup avant mise à jour
./scripts/backup.sh

# 2. Pull les changements
cd ..
git pull origin main
cd examens

# 3. Tests
php backend/tests/run_all.php

# 4. Si tests OK, vérifier health
curl https://examens-ipssi.fr/api/health?detailed=1 | jq .

# 5. Recharger PHP-FPM (VPS seulement, pour flush opcache)
sudo systemctl reload php8.3-fpm
```

### Stratégie de déploiement

Pour projets en production critique :

#### Blue/Green deployment (avancé)

- Déployer sur `/home/ipssi/examens-new/`
- Tester
- Basculer Nginx sur ce nouveau chemin
- Garder l'ancien en fallback

#### Rollback rapide

```bash
# Avant mise à jour
git tag -a v-before-update -m "Avant update"

# Rollback si problème
git checkout v-before-update
sudo systemctl reload php8.3-fpm
```

### Maintenance mensuelle

- [ ] Vérifier backups (restore test 1x/mois)
- [ ] Vérifier espace disque (`df -h`)
- [ ] Review logs (erreurs récurrentes)
- [ ] Mises à jour système (VPS) : `apt update && apt upgrade`
- [ ] Rotation logs si volumineux
- [ ] Certificat SSL valide (auto-renew, mais vérifier)

### Support IPSSI

```
Support OVH :
  - Chat en ligne (Manager)
  - Email : support@ovh.com
  - Tél : 08 99 49 84 98

Support technique plateforme :
  - m.elafrit@ecole-ipssi.net
  - GitHub Issues : maths_IA_niveau_1/issues
```

---

## 12. Dépannage

### 🐛 Site inaccessible après déploiement

**Check** :
1. DNS propagé ? `dig examens-ipssi.fr`
2. Firewall OK ? `ufw status` (VPS)
3. Nginx lancé ? `systemctl status nginx`
4. PHP-FPM lancé ? `systemctl status php8.3-fpm`
5. Logs Nginx : `tail -f /var/log/nginx/error.log`

### 🐛 500 Internal Server Error

**Check** :
1. Logs PHP : `tail -f data/logs/php_errors.log`
2. Permissions : `ls -la data/` (doit être writable)
3. `config.php` existe et valide
4. Extensions PHP chargées : `php -m`

### 🐛 "Maximum execution time exceeded"

Augmenter dans `.user.ini` (mutualisé) ou `php.ini` (VPS) :
```ini
max_execution_time = 120
```

### 🐛 Upload de gros fichiers échoue

Augmenter dans `.user.ini` :
```ini
upload_max_filesize = 50M
post_max_size = 50M
```

### 🐛 Emails dans SPAM

Vérifications :
- SPF configuré dans DNS
- DKIM activé dans Manager OVH
- **DMARC** (optionnel mais recommandé) :
  ```
  TXT _dmarc.examens-ipssi.fr
  v=DMARC1; p=none; rua=mailto:admin@ipssi.fr
  ```

### 🐛 Certbot échoue

**Raisons courantes** :
- DNS pas propagé → attendre
- Port 80 bloqué → vérifier firewall
- Rate limit Let's Encrypt → attendre 1h

**Check** :
```bash
certbot certificates
journalctl -u certbot
```

### 🐛 Site lent

**Diagnostic** :
```bash
# Top
top
htop

# I/O
iotop

# Apache benchmark
ab -n 100 -c 10 https://examens-ipssi.fr/api/health
```

**Solutions** :
- Activer **OPcache** (PHP) : vérifier dans `php -i | grep opcache`
- Augmenter **memory_limit** si swap utilisé
- Upgrader VPS (plus de RAM/CPU)
- CDN Cloudflare gratuit pour les assets

---

## ✅ Checklist production complète

### Configuration

- [ ] Domaine enregistré
- [ ] DNS configurés
- [ ] Hébergement actif
- [ ] PHP 8.3 installé
- [ ] Projet déployé
- [ ] `config.php` avec secrets uniques
- [ ] Permissions `data/` correctes

### Sécurité

- [ ] HTTPS (SSL) activé
- [ ] Headers de sécurité ajoutés
- [ ] `display_errors = Off`
- [ ] Fichiers sensibles bloqués (`data/`, `.env`)
- [ ] Fail2ban actif (VPS)
- [ ] Firewall configuré (VPS)
- [ ] SSH par clé (VPS)

### Emails

- [ ] Boîte email créée
- [ ] SMTP configuré
- [ ] SPF dans DNS
- [ ] DKIM activé
- [ ] Test envoi réussi
- [ ] Score mail-tester > 9/10

### Backups

- [ ] Cron backup quotidien installé
- [ ] Rotation configurée (keep=14)
- [ ] Backup offsite configuré
- [ ] Test de restore validé

### Monitoring

- [ ] UptimeRobot (ou équivalent) configuré
- [ ] Alertes email/SMS
- [ ] Dashboard `/admin/monitoring.html` accessible
- [ ] Vérification hebdomadaire planifiée

### Tests production

- [ ] `/api/health` retourne OK
- [ ] Connexion admin fonctionne
- [ ] Création examen test fonctionne
- [ ] Passage étudiant test complet
- [ ] Email correction reçu
- [ ] Analytics visibles
- [ ] Export CSV/Excel/PDF fonctionnel

### Documentation transmise

- [ ] `GUIDE_ADMIN.md` → admin de la plateforme
- [ ] `GUIDE_PROFESSEUR.md` → profs utilisateurs
- [ ] `GUIDE_ETUDIANT.md` → étudiants
- [ ] Credentials stockés au safe

---

## 🎉 Félicitations !

Votre plateforme IPSSI Examens est **en production** ! 🚀

Pensez à :
- **Communiquer** l'URL aux profs + étudiants
- **Former** les premiers utilisateurs (session de démo)
- **Recueillir** les feedback pour améliorer
- **Monitorer** les premières semaines de très près

---

## 📞 Support

- **Technique plateforme** : m.elafrit@ecole-ipssi.net
- **OVH Support** : Manager OVH → ticket
- **Bugs / features** : https://github.com/melafrit/maths_IA_niveau_1/issues

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
