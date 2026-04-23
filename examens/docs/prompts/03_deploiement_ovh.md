# 🚀 Prompt 03 — Infrastructure de déploiement OVH

## 📖 Description et contexte

Ce prompt génère un diagramme d'**infrastructure de déploiement** montrant où et comment chaque composant est hébergé sur OVH, avec les 2 scénarios possibles (mutualisé et VPS).

### Ce qui est généré
- Scénario A : **OVH Mutualisé** (Perso/Pro, ~8€/mois)
- Scénario B : **OVH VPS** (Ubuntu 22.04, ~10€/mois)
- Configuration DNS, SSL Let's Encrypt
- Services externes (SMTP, Object Storage, GitHub Actions)
- Ports, protocoles, firewall

### Quand utiliser ce prompt
- Documentation **DevOps / SRE**
- Guide de **déploiement production**
- Présentation au **client/équipe infra**
- Section "Infrastructure" dans pitch technique

### Outil recommandé
**Mermaid** pour le code technique, **DALL-E/Gemini Image** pour schéma visuel commercial.

---

## 🤖 Outils IA supportés

| Outil | Qualité | Remarques |
|---|:-:|---|
| **ChatGPT-4 / GPT-4o** | ⭐⭐⭐⭐⭐ | Excellent, connaît OVH |
| **Claude Opus 4** | ⭐⭐⭐⭐⭐ | Détails techniques précis |
| **Gemini 2.0 Pro** | ⭐⭐⭐⭐ | Bon, parfois moins spécifique OVH |
| **DALL-E 3** | ⭐⭐⭐⭐ | Pour version commerciale |

---

## 📋 Version pour ChatGPT-4 / GPT-4o

```
Tu es un ingénieur DevOps expert en infrastructure web, spécialisé OVH.

CONTEXTE :
Je déploie la plateforme IPSSI Examens sur OVH en production.
2 scénarios possibles :
- Scénario A : OVH Mutualisé (Perso ou Pro, ~8€/mois)
- Scénario B : OVH VPS (Ubuntu 22.04, Nginx + PHP-FPM, ~10€/mois)

STACK COMPLÈTE :
- Domaine : examens-ipssi.fr (OVH Domains)
- DNS : zone OVH avec enregistrement A vers serveur
- HTTPS : Let's Encrypt (automatique mutualisé, ou Certbot sur VPS)
- Serveur web : Apache (mutualisé) ou Nginx (VPS)
- PHP 8.3 (FPM sur VPS)
- Stockage : fichiers JSON dans /data/
- Email : OVH Email Pro (ssl0.ovh.net:465) avec SPF/DKIM
- Backups : cron local + offsite vers S3/OVH Object Storage ou rsync
- Monitoring externe : UptimeRobot ping /api/health
- Monitoring interne : /admin/monitoring.html avec HealthChecker
- Sécurité VPS : UFW firewall (22/80/443), fail2ban, SSH par clé
- CI/CD : GitHub Actions (tests + lint automatiques sur push)

OBJECTIF :
Génère un diagramme d'infrastructure de déploiement complet au format Mermaid.

SPÉCIFICATIONS :
- Format : flowchart LR (left-right) ou TB
- Montrer LES DEUX scénarios dans deux subgraphs séparés OU utiliser des annotations pour les différences

ÉLÉMENTS À INCLURE :

1. INTERNET (gauche) :
   - Users (Admin, Prof, Étudiant) avec leurs device (laptop, phone)
   - UptimeRobot (service monitoring externe)
   - Cloudflare (optionnel, CDN)

2. OVH DNS :
   - Zone DNS examens-ipssi.fr
   - Enregistrements A, MX, TXT (SPF, DKIM, DMARC)

3. SERVEUR WEB (centre) — 2 options :
   a) MUTUALISÉ :
      - Apache avec .htaccess
      - PHP 8.3
      - Let's Encrypt SSL auto
      - Tâches cron (via Manager OVH)
   
   b) VPS :
      - UFW Firewall (22/80/443)
      - Fail2ban
      - Nginx (reverse proxy)
      - PHP 8.3 FPM (unix socket)
      - Certbot (cert auto-renew)
      - Cron quotidien (backup.sh)

4. APPLICATION (dans le serveur) :
   - backend/public/index.php (entry point)
   - backend/lib/ (17 managers)
   - backend/api/ (8 endpoints)
   - frontend/ (assets statiques)
   - data/ (JSON + backups)
   - scripts/ (bash : backup.sh, restore.sh)

5. SERVICES EXTERNES :
   - OVH Email Pro SMTP (ssl0.ovh.net:465)
   - OVH Object Storage / S3 (backups offsite)
   - GitHub (repo + Actions CI)

6. FLUX PRINCIPAUX (flèches étiquetées) :
   - User → DNS → Serveur (HTTPS 443)
   - Serveur → SMTP (TLS 465)
   - Cron → backup.sh → Object Storage (S3 rsync)
   - UptimeRobot → /api/health (HTTPS 443)
   - GitHub Actions → tests → merge

FORMAT DE SORTIE :
Code Mermaid avec :
- Icônes emoji pour chaque composant (☁️ 🔒 🌐 🖥️ 💾 📧 🤖)
- Subgraphs pour grouper (Internet, OVH Cloud, Serveur, App, Externes)
- Couleurs : rouge (sécurité), vert (app), bleu (external services), gris (infra)
- Annotations sur les flèches avec protocole + port

CRITÈRES :
- Montre clairement les zones de confiance (DMZ, intranet simulé)
- Facile à comprendre pour un admin débutant
- Titre : "IPSSI Examens — Déploiement OVH (Mutualisé et VPS)"

Génère le code Mermaid.
```

---

## 📋 Version pour Claude (3.5/4 Sonnet, Opus)

```
<role>
Tu es un ingénieur DevOps senior expert en :
- Infrastructure OVH (mutualisé, VPS, dédié)
- Linux (Ubuntu) + Nginx + PHP-FPM
- Sécurité système (firewall, SSH, fail2ban)
- Let's Encrypt / Certbot
- Backups offsite (S3, rsync)
- Diagrammes d'infrastructure avec Mermaid
</role>

<project>
  <n>IPSSI Examens Production Deployment</n>
  <domain>examens-ipssi.fr</domain>
</project>

<deployment_scenarios>
  <scenario id="A" name="OVH Mutualisé">
    <plan>Perso ou Pro</plan>
    <cost>~8€/mois</cost>
    <features>
      - Apache avec .htaccess
      - PHP 8.3 géré par OVH
      - SSL Let's Encrypt automatique
      - Tâches cron via Manager OVH Web
      - FTP only (pas de SSH)
    </features>
  </scenario>
  
  <scenario id="B" name="OVH VPS">
    <plan>Starter ou Value</plan>
    <os>Ubuntu 22.04 LTS</os>
    <cost>~5-10€/mois</cost>
    <components>
      - Nginx 1.18 (reverse proxy)
      - PHP 8.3 FPM (unix socket)
      - UFW Firewall (22/80/443)
      - Fail2ban
      - Certbot (auto-renew SSL)
      - Cron native (crontab)
      - SSH avec key authentication
    </components>
  </scenario>
</deployment_scenarios>

<components_to_diagram>
  <zone name="Internet">
    - Users: Admin, Prof, Étudiant (with devices)
    - UptimeRobot (external monitoring)
    - (Optional) Cloudflare CDN
  </zone>
  
  <zone name="OVH Cloud">
    <service>DNS Zone (A, MX, TXT SPF/DKIM/DMARC)</service>
    <service>Email Pro (ssl0.ovh.net:465)</service>
    <service>Object Storage (s3://ipssi-backups/)</service>
  </zone>
  
  <zone name="Server">
    Infrastructure components depending on scenario
  </zone>
  
  <zone name="Application">
    - backend/public/index.php (entry)
    - backend/lib/ (17 managers)
    - backend/api/ (9 endpoints)
    - frontend/ (static)
    - data/ (JSON + backups)
    - scripts/ (backup.sh, restore.sh)
  </zone>
  
  <zone name="External Services">
    - GitHub (repo + Actions CI)
    - Let's Encrypt
    - UptimeRobot
  </zone>
</components_to_diagram>

<network_flows>
  <flow from="User" to="DNS" protocol="UDP" port="53" />
  <flow from="User" to="Server" protocol="HTTPS" port="443" />
  <flow from="Nginx" to="PHP-FPM" protocol="Unix Socket" />
  <flow from="App" to="SMTP" protocol="TLS" port="465" />
  <flow from="Cron" to="Object Storage" protocol="HTTPS" port="443" />
  <flow from="Certbot" to="Let's Encrypt" protocol="HTTPS" port="443" />
  <flow from="UptimeRobot" to="Server" protocol="HTTPS" port="443" />
  <flow from="Developer SSH" to="VPS" protocol="SSH" port="22" />
  <flow from="GitHub Actions" to="Repo" protocol="HTTPS" />
</network_flows>

<requirements>
  <format>Mermaid flowchart LR</format>
  
  <structure>
    - Separate subgraphs per zone
    - Subgraphs for each scenario (A et B)
    - Clear visual separation between mutualisé and VPS paths
  </structure>
  
  <styling>
    <emojis>
      - 💻 Client devices
      - 🌐 DNS/Web
      - 🖥️ Server
      - 🔒 Security (firewall, fail2ban)
      - 💾 Storage
      - 📧 Email
      - 🤖 CI/CD
      - ☁️ Cloud services
    </emojis>
    
    <colors>
      - red: security components
      - green: application
      - blue: external services
      - gray: infrastructure
      - yellow: data/storage
    </colors>
    
    <annotations>
      All arrows must have protocol + port labels
    </annotations>
  </styling>
</requirements>

<o>
Provide:
1. Title: "IPSSI Examens — Déploiement OVH (Mutualisé et VPS)"
2. Complete Mermaid code with BOTH scenarios
3. Brief comparison (3-5 lines): when to use which scenario
4. Security zones annotation (DMZ, trusted zone)
</o>
```

---

## 📋 Version pour Gemini Pro / 2.0 Flash

```
Tâche : diagramme infrastructure déploiement OVH en Mermaid.

Projet : IPSSI Examens sur OVH

2 SCÉNARIOS À MONTRER :

SCÉNARIO A - MUTUALISÉ (~8€/mois)
- Apache + .htaccess
- PHP 8.3 (géré OVH)
- SSL auto Let's Encrypt
- Cron via Manager OVH

SCÉNARIO B - VPS Ubuntu 22.04 (~10€/mois)
- Nginx + PHP-FPM
- UFW Firewall (22, 80, 443)
- Fail2ban
- Certbot (auto-renew)
- Cron natif
- SSH par clé

COMPOSANTS DANS LES 2 CAS :
- backend/public/index.php
- backend/lib/ (17 managers)
- backend/api/ (9 endpoints)
- frontend/ (statiques)
- data/ (JSON + backups)
- scripts/ (bash)

ACTEURS/SERVICES :
- Users (Admin, Prof, Étudiant)
- UptimeRobot (monitoring externe)
- OVH DNS (A, MX, TXT)
- OVH Email Pro SMTP ssl0.ovh.net:465
- OVH Object Storage (backups offsite)
- GitHub (Actions CI)
- Let's Encrypt

FLUX PRINCIPAUX (avec port) :
- User → DNS (UDP 53)
- User → Server (HTTPS 443)
- Nginx → PHP-FPM (unix socket, VPS seulement)
- App → SMTP (TLS 465)
- Cron → Object Storage (HTTPS 443)
- UptimeRobot → /api/health (HTTPS 443)
- Dev SSH → VPS (SSH 22)
- Certbot → Let's Encrypt (HTTPS 443)

RÈGLES :
1. Format : Mermaid flowchart LR
2. Subgraphs : Internet, OVH Cloud, Serveur (A ou B), Application, Externes
3. Emojis : 💻 🌐 🖥️ 🔒 💾 📧 🤖 ☁️
4. Couleurs : rouge=sécu, vert=app, bleu=external, gris=infra
5. Labels sur flèches : protocole + port
6. Titre : "IPSSI Examens — Déploiement OVH"

Génère uniquement le code Mermaid.
```

---

## 📋 Version DALL-E 3 / Gemini NanoBanana (schéma visuel)

Pour un rendu plus visuel et professionnel pour présentation :

```
Create a professional isometric cloud infrastructure diagram showing OVH hosting for a web application called "IPSSI Examens".

Composition (landscape 16:9):

LEFT SIDE (Internet / Users):
- 3 devices: laptop (admin with crown), tablet (teacher), smartphone (student)
- Dashed lines flowing right
- "UptimeRobot" service icon (eye symbol) floating above

CENTER LEFT (OVH Cloud):
- Large isometric cloud outline with "OVH" logo
- Inside: DNS icon with "examens-ipssi.fr" label
- Connected to SSL padlock (Let's Encrypt)

CENTER (Server):
- 3D isometric server rack with "VPS Ubuntu 22.04" label
- Visual firewall shield (red) protecting inbound
- Inside the server (exploded view):
  - Nginx container
  - PHP-FPM process
  - Application layer with service cubes
  - Data storage (stacked documents)
- Fail2ban protection badge

CENTER RIGHT (External Services):
- Email icon with "OVH Email Pro" label
- S3-style bucket with "Object Storage - Backups"
- GitHub octocat logo with "Actions CI/CD"

Flow indicators:
- Flowing arrows with labels (HTTPS 443, SSH 22, TLS 465)
- Color-coded: green for safe, red for secured, blue for external

Style:
- Isometric 3D perspective (30° angle)
- Modern flat design with subtle shadows
- Corporate color palette: OVH orange, soft blues, greens
- Light gray gradient background
- Clean typography (sans-serif)

Labels all readable, professional look.

Title at top: "IPSSI Examens — OVH Production Infrastructure"
Subtitle: "VPS Ubuntu · Nginx · PHP 8.3 · Let's Encrypt SSL"

Make it worthy of a technical presentation to a CTO.
```

---

## 🎨 Rendu final

### Workflow Mermaid

1. Générer le code avec ChatGPT/Claude/Gemini
2. Coller sur https://mermaid.live/
3. Exporter SVG pour haute qualité

### Workflow visuel (DALL-E/NanoBanana)

1. Coller le prompt visuel
2. Itérer sur le résultat (variantes)
3. Éditer si besoin avec Figma
4. Utiliser pour présentations clients

### Intégration doc

Dans `DEPLOIEMENT_OVH.md`, juste après l'introduction :

````markdown
## 🏗️ Infrastructure de déploiement

Vue d'ensemble de l'infrastructure en production :

```mermaid
[code généré]
```

**Notre choix** : VPS Starter (Ubuntu 22.04) avec Nginx + PHP-FPM
car il offre le meilleur rapport performance/contrôle/prix.
````

---

## 💡 Variations

### Version simplifiée (sans VPS)
*"Génère uniquement le scénario mutualisé, simplifié, 15 nœuds maximum."*

### Version avec métriques
*"Ajoute pour chaque composant : RAM utilisée, trafic moyen, latence."*

### Version multi-région (future)
*"Anticipe une architecture multi-région : Paris (principal) + Gravelines (réplique), avec load balancer."*

---

## 📞 Support

- **Email** : m.elafrit@ecole-ipssi.net
- **Issues** : https://github.com/melafrit/maths_IA_niveau_1/issues

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
