# 💻 Guide d'installation locale — IPSSI Examens

> Installation complète sur **Windows** et **macOS** pour développement local.
> De zéro à une plateforme fonctionnelle en **15-20 minutes**.

---

## 📖 Sommaire

1. [Prérequis](#1-prérequis)
2. [Installation Windows](#2-installation-windows)
3. [Installation macOS](#3-installation-macos)
4. [Installation Linux (bonus)](#4-installation-linux-bonus)
5. [Cloner et lancer le projet](#5-cloner-et-lancer-le-projet)
6. [Configuration initiale](#6-configuration-initiale)
7. [Premier test complet](#7-premier-test-complet)
8. [Développement quotidien](#8-développement-quotidien)
9. [Dépannage](#9-dépannage)

---

## 1. Prérequis

### Logiciels requis

| Logiciel | Version min | Recommandé |
|---|:-:|:-:|
| **PHP** | 8.2 | 8.3 |
| **Git** | 2.30 | dernière |
| **Navigateur** | — | Chrome/Firefox récent |

### Logiciels optionnels

| Logiciel | Usage |
|---|---|
| **Node.js 20+** | Pour lint JSX en local (CI le fait déjà) |
| **jq** | Pour manipuler JSON en CLI |
| **curl** | Pour tester l'API |
| **VS Code** | Éditeur recommandé |

### Matériel

- **RAM** : 4 Go minimum (8 Go recommandés)
- **Disque** : 500 Mo d'espace libre
- **CPU** : n'importe quel processeur récent

---

## 2. Installation Windows

### Étape 2.1 — Installer PHP

#### Option A (recommandée) : via XAMPP

1. Télécharger **XAMPP** : https://www.apachefriends.org/download.html
2. Choisir version avec PHP 8.2+ (ex: XAMPP 8.2.x)
3. Installer dans `C:\xampp`
4. Ouvrir XAMPP Control Panel

Vérification :
```powershell
C:\xampp\php\php.exe --version
# PHP 8.2.x (cli) ...
```

#### Option B : PHP standalone

1. Télécharger : https://windows.php.net/download/
2. Choisir **VS16 x64 Thread Safe** (ZIP)
3. Extraire dans `C:\php`
4. Ajouter `C:\php` au **PATH** :
   - Menu Démarrer → "Variables d'environnement"
   - Variables système → PATH → Modifier → Nouveau
   - Ajouter `C:\php`
5. Configurer `php.ini` :
   ```powershell
   copy C:\php\php.ini-development C:\php\php.ini
   ```
6. Éditer `C:\php\php.ini` et décommenter (enlever `;`) :
   ```ini
   extension=curl
   extension=mbstring
   extension=openssl
   extension=fileinfo
   extension=zip
   ```
7. Tester :
   ```powershell
   php --version
   # PHP 8.3.x (cli) ...
   ```

### Étape 2.2 — Installer Git

1. Télécharger : https://git-scm.com/download/win
2. Installer avec options par défaut
3. Tester :
   ```powershell
   git --version
   # git version 2.40+
   ```

### Étape 2.3 — Installer Node.js (optionnel)

1. Télécharger : https://nodejs.org/ (LTS)
2. Installer avec options par défaut
3. Tester :
   ```powershell
   node --version
   # v20.x.x
   ```

### Étape 2.4 — Installer jq (optionnel)

Via **Chocolatey** (recommandé) :
```powershell
# Installer Chocolatey en PowerShell admin
Set-ExecutionPolicy Bypass -Scope Process -Force
iwr https://chocolatey.org/install.ps1 -UseBasicParsing | iex

# Installer jq
choco install jq
```

Ou téléchargement direct : https://stedolan.github.io/jq/download/

### Étape 2.5 — Configurer PowerShell

Pour utiliser les scripts bash, installer **Git Bash** (déjà inclus avec Git).

Ouvrir **Git Bash** (menu Démarrer) pour suivre les commandes `./scripts/*.sh`.

### Terminal recommandé Windows

- **Windows Terminal** (Microsoft Store)
- Combiné avec **Git Bash** ou **PowerShell 7**

---

## 3. Installation macOS

### Étape 3.1 — Installer Homebrew

Si pas déjà installé, dans Terminal :

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

Tester :
```bash
brew --version
# Homebrew 4.x.x
```

### Étape 3.2 — Installer PHP

```bash
brew install php@8.3

# Lier pour que 'php' pointe dessus
brew link php@8.3 --force --overwrite

# Vérifier
php --version
# PHP 8.3.x (cli) (built: ...)
```

### Étape 3.3 — Installer Git

Déjà présent sur macOS via Xcode Command Line Tools :

```bash
git --version
# Si demande installation Xcode : accepter

# Ou via Homebrew
brew install git
```

### Étape 3.4 — Installer Node.js (optionnel)

```bash
brew install node@20
brew link node@20 --force

node --version
# v20.x.x
```

### Étape 3.5 — Installer jq (optionnel)

```bash
brew install jq

jq --version
# jq-1.7
```

### Terminal recommandé macOS

- **Terminal.app** (natif, suffisant)
- **iTerm2** (plus de features, gratuit) : `brew install --cask iterm2`
- **Warp** (moderne, IA) : https://www.warp.dev/

---

## 4. Installation Linux (bonus)

### Ubuntu / Debian

```bash
# PHP 8.3
sudo apt update
sudo add-apt-repository ppa:ondrej/php  # PPA pour PHP récent
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip

# Git
sudo apt install -y git

# Node.js (optionnel)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# jq
sudo apt install -y jq
```

### Arch / Manjaro

```bash
sudo pacman -S php git jq nodejs npm
```

### Vérification générale

```bash
php --version
git --version
node --version  # optionnel
jq --version    # optionnel
```

---

## 5. Cloner et lancer le projet

### Étape 5.1 — Choisir un dossier de travail

**Windows (Git Bash)** :
```bash
cd /c/Users/VotreNom/Documents
# ou
cd ~/Documents
mkdir -p projets && cd projets
```

**macOS / Linux** :
```bash
cd ~
mkdir -p projets && cd projets
```

### Étape 5.2 — Cloner le repo

```bash
git clone https://github.com/melafrit/maths_IA_niveau_1.git
cd maths_IA_niveau_1
```

### Étape 5.3 — Explorer la structure

```bash
# Voir l'arborescence
ls -la

# Aller dans le projet examens
cd examens
ls -la
```

### Étape 5.4 — Vérifier permissions (macOS / Linux)

```bash
chmod -R 755 data/
chmod +x scripts/*.sh
```

**Windows** : pas nécessaire, permissions gérées par NTFS.

### Étape 5.5 — Lancer le serveur local

```bash
# Depuis le dossier examens/
php -S 127.0.0.1:8765 -t backend/public backend/public/index.php
```

Vous devriez voir :
```
[... ] PHP 8.3.x Development Server (http://127.0.0.1:8765) started
```

### Étape 5.6 — Tester dans le navigateur

Ouvrir : **http://127.0.0.1:8765**

Vous devez voir la page d'accueil avec le titre **IPSSI Examens**.

Tester l'endpoint de santé :

**URL** : http://127.0.0.1:8765/api/health

Réponse attendue :
```json
{
  "ok": true,
  "data": {
    "status": "ok",
    "version": "dev",
    "php": "8.3.x",
    "uptime_sec": 0.0012
  }
}
```

✅ Si vous voyez ça : **le serveur fonctionne !**

---

## 6. Configuration initiale

### Étape 6.1 — Copier la config exemple

```bash
# Depuis examens/
cp backend/config.sample.php backend/config.php
```

### Étape 6.2 — Personnaliser (optionnel)

Éditer `backend/config.php` :

```php
<?php
return [
    'app' => [
        'name' => 'IPSSI Examens',
        'version' => '0.8.0',
        'env' => 'dev',  // ou 'prod'
    ],
    'security' => [
        'csrf_secret' => 'CHANGE-ME-en-prod',
        'signature_salt' => 'CHANGE-ME-aussi',
    ],
    // ...
];
```

⚠️ **En production** : changer les secrets `csrf_secret` et `signature_salt` pour des valeurs aléatoires uniques.

Générer un secret sécurisé :
```bash
php -r 'echo bin2hex(random_bytes(32)) . PHP_EOL;'
```

### Étape 6.3 — Créer les comptes initiaux

#### Compte admin

```bash
php scripts/init_comptes.php \
  --role=admin \
  --email=admin@ipssi.fr \
  --nom=Admin \
  --prenom=Principal \
  --password='VotreMdpFort2026!'
```

#### Compte prof

```bash
php scripts/init_comptes.php \
  --role=enseignant \
  --email=prof@ipssi.fr \
  --nom=Dupont \
  --prenom=Jean \
  --password='PasswordProf2026!'
```

### Étape 6.4 — Premier backup

```bash
./scripts/backup.sh
```

---

## 7. Premier test complet

### Étape 7.1 — Lancer les tests backend

```bash
php backend/tests/run_all.php
```

Résultat attendu :
```
═══════════════════════════════════════════════════════════════
  🧪 IPSSI — Harness de tests unifié P8.1
═══════════════════════════════════════════════════════════════
  ...
  TOTAL        : 17 suites · 389/389 tests ✅ (100%)
═══════════════════════════════════════════════════════════════
```

✅ Si tous les tests passent : **tout fonctionne !**

### Étape 7.2 — Tester l'interface admin

1. Ouvrir : http://127.0.0.1:8765/admin/examens.html
2. Se connecter avec `prof@ipssi.fr` / `PasswordProf2026!`
3. Vous devez voir le dashboard examens (vide pour l'instant)

### Étape 7.3 — Tester la banque

1. Aller sur : http://127.0.0.1:8765/admin/banque.html
2. Voir l'arborescence des 320 questions

### Étape 7.4 — Tester le monitoring

1. Se connecter en **admin** : `admin@ipssi.fr`
2. Aller sur : http://127.0.0.1:8765/admin/monitoring.html
3. Voir le dashboard avec 7 checks

### Étape 7.5 — Workflow complet

**Créer un mini-examen** :

1. Se connecter en prof
2. `/admin/examens.html` → **+ Nouvel examen**
3. Remplir :
   - Titre : "Test install"
   - Sélectionner 3-5 questions
   - Durée 30 min
   - Dates : ouverture maintenant, clôture dans 2h
4. Sauver en brouillon → Publier
5. **Copier le code d'accès** (ex: `ABC23K-9P`)

**Simuler un passage étudiant** :

1. Ouvrir dans **onglet privé** (pour pas interférer avec session prof) :
   http://127.0.0.1:8765/etudiant/passage.html
2. Saisir le code
3. Remplir infos (nom test, email test@ipssi.fr)
4. Passer l'examen
5. Soumettre
6. Consulter la correction

**Voir les analytics** :

1. Retour sur session prof
2. `/admin/analytics.html` → votre examen
3. Voir le passage enregistré, score, temps

🎉 **Si tout fonctionne : installation réussie !**

---

## 8. Développement quotidien

### Lancer le serveur

```bash
cd ~/projets/maths_IA_niveau_1/examens
php -S 127.0.0.1:8765 -t backend/public backend/public/index.php
```

### Arrêter le serveur

`Ctrl+C` dans le terminal.

### Relancer les tests

```bash
# Complet
php backend/tests/run_all.php

# Rapide (unit only)
php backend/tests/run_all.php --quick

# Spécifique
php backend/tests/run_all.php --filter=banque
```

### Git : récupérer les dernières modifs

```bash
cd maths_IA_niveau_1
git pull origin main
cd examens
php backend/tests/run_all.php  # Vérifier non-régression
```

### Contribuer

```bash
# Créer une branche
git checkout -b feat/ma-feature

# Modifier...
# Tester...
php backend/tests/run_all.php

# Commiter
git add .
git commit -m "feat: description"
git push origin feat/ma-feature

# Ouvrir une Pull Request sur GitHub
```

### Éditeur recommandé : VS Code

Télécharger : https://code.visualstudio.com/

**Extensions utiles** :
- **PHP Intelephense** (intellisense PHP)
- **ESLint** (lint JS/JSX)
- **Prettier** (formattage auto)
- **GitLens** (amélioration Git)
- **Thunder Client** (test API, alternative Postman)

Ouvrir le projet :
```bash
code ~/projets/maths_IA_niveau_1
```

### Hot reload JSX

Le projet utilise **Babel Standalone** qui transpile le JSX directement dans le navigateur :
- Modifier un fichier `.jsx`
- Recharger la page (F5)
- Les changements sont visibles immédiatement

Pas de rebuild / bundler nécessaire.

---

## 9. Dépannage

### 🐛 "php: command not found" (macOS/Linux)

PHP n'est pas dans votre PATH.

**macOS** : Homebrew l'ajoute automatiquement. Si pas :
```bash
export PATH="/opt/homebrew/bin:$PATH"  # Apple Silicon
# ou
export PATH="/usr/local/bin:$PATH"     # Intel
```

Ajouter à `~/.zshrc` ou `~/.bashrc` pour permanence.

### 🐛 "'php' n'est pas reconnu" (Windows)

PHP n'est pas dans le PATH Windows.

**Solution** :
1. Ouvrir "Variables d'environnement" (menu Démarrer)
2. Variables système → PATH → Modifier
3. Ajouter `C:\xampp\php` ou `C:\php`
4. OK → Rouvrir PowerShell/Git Bash

### 🐛 Erreur "Permission denied" sur scripts bash

**macOS/Linux** :
```bash
chmod +x scripts/*.sh
```

**Windows** : Git Bash devrait fonctionner. Si bash introuvable, installer Git complet.

### 🐛 Extensions PHP manquantes

Message type : `Call to undefined function curl_init()`

**Solution** :
1. Localiser votre `php.ini` :
   ```bash
   php --ini
   ```
2. Ouvrir le fichier
3. Décommenter les extensions :
   ```ini
   extension=curl
   extension=mbstring
   extension=openssl
   extension=fileinfo
   extension=zip
   ```
4. Redémarrer le serveur

### 🐛 Port 8765 déjà utilisé

```bash
# Changer de port
php -S 127.0.0.1:9999 -t backend/public backend/public/index.php
```

Ou identifier le process qui l'occupe :

**macOS/Linux** :
```bash
lsof -ti:8765 | xargs kill -9
```

**Windows (PowerShell)** :
```powershell
Get-Process -Id (Get-NetTCPConnection -LocalPort 8765).OwningProcess | Stop-Process -Force
```

### 🐛 Tests échouent au premier lancement

Souvent à cause de permissions `data/` insuffisantes.

**Solution** :
```bash
# macOS/Linux
chmod -R 755 examens/data/
# S'assurer que tous les sous-dossiers existent
mkdir -p examens/data/examens examens/data/passages examens/data/comptes examens/data/banque examens/data/sessions examens/data/backups examens/data/logs examens/data/_ratelimit
```

**Windows** : clic droit → Propriétés → Sécurité → modifier permissions pour votre user.

### 🐛 "CORS error" dans le navigateur

Cela ne devrait pas arriver en local (même origine).

Si ça arrive :
- Vérifier que l'URL du navigateur correspond exactement (http://127.0.0.1:8765 vs http://localhost:8765)
- Redémarrer le serveur PHP

### 🐛 JSX ne se charge pas

**Causes** :
- **JavaScript désactivé** → activer dans le navigateur
- **Babel standalone** bloqué par adblock → désactiver pour localhost
- Fichier `.jsx` introuvable → vérifier le chemin dans `script src=`

**Debug** :
- Ouvrir **Console navigateur** (F12)
- Regarder les erreurs rouges

### 🐛 "cannot create lock file" sur backup.sh

Permissions `/tmp/` :

```bash
# Tester manuellement
touch /tmp/test && rm /tmp/test
```

**Windows Git Bash** : `/tmp` = `C:\Users\<user>\AppData\Local\Temp\`. Normalement OK.

### 🐛 Emails ne fonctionnent pas en local

Normal ! Par défaut, pas de serveur SMTP configuré en dev.

**Options** :
1. Utiliser **MailHog** pour capturer les emails localement :
   ```bash
   # macOS
   brew install mailhog
   brew services start mailhog
   # Interface : http://localhost:8025
   ```
2. Configurer un compte Gmail/SendGrid (voir `DEPLOIEMENT_OVH.md`)
3. Ignorer en dev : les emails sont dans les logs

### 🐛 Babel Standalone : "SyntaxError"

Vérifier que votre JSX est valide :
- Un seul élément root par return
- Pas de `function returnReact` (bug classique)
- Commentaires JSX : `{/* comment */}` et pas `// comment`

Tester syntaxe d'un fichier :
```bash
node -e "
const babel = require('@babel/core');
const src = require('fs').readFileSync('frontend/assets/analytics.jsx', 'utf8');
try { babel.transformSync(src, {plugins: ['@babel/plugin-transform-react-jsx']}); console.log('OK'); }
catch (e) { console.log('FAIL:', e.message); }
"
```

(Si `@babel/core` pas installé globalement, adapter les chemins.)

---

## 10. Astuces

### Alias utiles

Ajouter à votre `~/.zshrc` ou `~/.bashrc` :

```bash
alias ipssi-start='cd ~/projets/maths_IA_niveau_1/examens && php -S 127.0.0.1:8765 -t backend/public backend/public/index.php'
alias ipssi-test='cd ~/projets/maths_IA_niveau_1/examens && php backend/tests/run_all.php'
alias ipssi-backup='cd ~/projets/maths_IA_niveau_1/examens && ./scripts/backup.sh'
alias ipssi-log='tail -f ~/projets/maths_IA_niveau_1/examens/data/logs/app.log'
```

Usage :
```bash
ipssi-start   # Lance le serveur
ipssi-test    # Lance les tests
```

### Ouvrir automatiquement le navigateur

**macOS** :
```bash
php -S 127.0.0.1:8765 -t backend/public backend/public/index.php &
sleep 1 && open http://127.0.0.1:8765/admin/examens.html
```

**Linux** : remplacer `open` par `xdg-open`.

**Windows Git Bash** : remplacer `open` par `start`.

### VS Code tasks

Créer `.vscode/tasks.json` :

```json
{
  "version": "2.0.0",
  "tasks": [
    {
      "label": "IPSSI: Start Server",
      "type": "shell",
      "command": "cd examens && php -S 127.0.0.1:8765 -t backend/public backend/public/index.php",
      "presentation": { "panel": "dedicated" }
    },
    {
      "label": "IPSSI: Run Tests",
      "type": "shell",
      "command": "cd examens && php backend/tests/run_all.php",
      "group": { "kind": "test", "isDefault": true }
    },
    {
      "label": "IPSSI: Backup",
      "type": "shell",
      "command": "cd examens && ./scripts/backup.sh"
    }
  ]
}
```

Lancer via Ctrl+Shift+P → "Tasks: Run Task".

---

## 🎯 Checklist finale

Avant de considérer votre installation complète :

- [ ] `php --version` → PHP 8.2+
- [ ] `git --version` → fonctionne
- [ ] Repo cloné dans `~/projets/` (ou équivalent)
- [ ] `config.php` créé depuis `config.sample.php`
- [ ] Secrets `csrf_secret` et `signature_salt` personnalisés
- [ ] Comptes admin + prof créés
- [ ] Serveur se lance sans erreur
- [ ] `http://127.0.0.1:8765/api/health` retourne `status=ok`
- [ ] `php backend/tests/run_all.php` → **389/389** ✅
- [ ] Connexion admin fonctionne
- [ ] Création d'un examen test fonctionne
- [ ] Passage étudiant simulé fonctionne
- [ ] Analytics visibles
- [ ] Backup créé via `./scripts/backup.sh`

---

## 🎉 Prochaines étapes

Maintenant que tout fonctionne en local :

- **Utilisation** : lire `GUIDE_PROFESSEUR.md` ou `GUIDE_ADMIN.md`
- **Déploiement en ligne** : voir `DEPLOIEMENT_OVH.md`
- **Contribution** : voir `CONTRIBUTING.md`

---

## 📞 Support

En cas de blocage :

1. **Check les logs** : `tail -f data/logs/app.log`
2. **Check GitHub Issues** : https://github.com/melafrit/maths_IA_niveau_1/issues
3. **Email** : m.elafrit@ecole-ipssi.net

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
