# 🎨 Prompt 99 (Bonus) — Schémas visuels avec DALL-E / NanoBanana / Midjourney

## 📖 Description et contexte

Ce prompt bonus permet de générer des **schémas d'architecture visuels et stylisés** (illustrations, pas du code) pour présentations client, marketing, ou pitch decks.

Contrairement aux 12 autres prompts qui produisent du code Mermaid/PlantUML technique, ce prompt génère de **vraies images** via les modèles d'IA image.

### Ce qui est généré
- Image haute résolution (1024x1024 ou 16:9)
- Style isométrique 3D ou flat design
- Palette de couleurs professionnelle
- Prête pour présentation ou site web

### Quand utiliser
- **Pitch deck** pour investisseurs / clients
- **Page d'accueil** du site web
- **Cover** de documentation
- **Marketing** / communication
- **Présentation exécutive**

### Quand NE PAS utiliser
- Documentation technique précise (préférer Mermaid)
- Code/architecture exacte à mémoriser
- Diagrammes nécessitant précision UML

---

## 🤖 Outils IA supportés

| Outil | Qualité | Accès |
|---|:-:|---|
| **DALL-E 3** (via ChatGPT Plus) | ⭐⭐⭐⭐⭐ | ChatGPT Plus ($20/mois) |
| **Gemini 2.0 Image Gen** | ⭐⭐⭐⭐ | Google AI Studio / Gemini app |
| **Gemini NanoBanana** | ⭐⭐⭐⭐ | Gemini app |
| **Midjourney V6+** | ⭐⭐⭐⭐⭐ | Discord ($10+/mois) |
| **Ideogram** | ⭐⭐⭐⭐ | Gratuit / Premium |
| **Leonardo.ai** | ⭐⭐⭐⭐ | Gratuit avec limites |
| **Adobe Firefly** | ⭐⭐⭐⭐ | Adobe Creative Cloud |

---

## 📋 Version DALL-E 3 (via ChatGPT Plus)

```
Create a high-quality isometric 3D architecture diagram for "IPSSI Examens", a professional web-based exam platform for a French business school.

COMPOSITION (landscape 16:9):

LEFT THIRD — Users zone:
- 3 stylized character illustrations, each with distinct color accents:
  - An administrator with a small crown icon, standing near a desktop
  - A teacher holding a tablet with a book icon
  - A student with a graduation cap, using a laptop
- Dashed flow lines emanating rightward toward the server

CENTER — Main server:
- A large isometric 3D server rack with soft glow
- Labeled "IPSSI Examens Server"
- Inside the server (semi-exploded view):
  - Nginx web server block
  - PHP 8.3 FPM engine block
  - Multiple small cubes representing services (Auth, Exams API, Analytics, Backups, Monitoring)
  - A security shield icon floating on top
- Below the server: a stack of document icons labeled "JSON Database"

RIGHT THIRD — External services:
- A cloud with an envelope icon labeled "Email SMTP"
- GitHub octocat logo with "CI/CD Actions"
- A clock icon with "Cron Jobs"
- An "OVH Cloud" logo in soft orange

CONNECTIONS:
- Elegant flowing arrows between components
- Labels floating near arrows: "HTTPS", "JSON", "SMTP 465"
- Color-coded: green for data flow, red for security, blue for external APIs

STYLE:
- Modern flat design with soft 3D isometric perspective (30° angle)
- Clean corporate color palette: deep navy blue (#1a365d), mint green (#48bb78), warm orange (#ed8936), light gray background
- Subtle drop shadows for depth
- Clean sans-serif typography throughout
- Light gray grid pattern background
- All labels crisp and readable

TEXT ELEMENTS:
- Main title at top: "IPSSI Examens — Architecture Overview"
- Subtitle below: "Web Platform for Secure Online Exams · Version 1.0"
- Small footer: "© 2026 Mohamed EL AFRIT — IPSSI"

TECHNICAL:
- High resolution (1920x1080 minimum)
- All text must be clearly readable (no gibberish)
- Professional enough for a CTO presentation
- Similar in quality to AWS, Azure, or Google Cloud official architecture diagrams
```

---

## 📋 Version Gemini 2.0 / NanoBanana

```
Professional isometric cloud architecture diagram for an online exam platform called "IPSSI Examens".

LEFT: 3 user personas (administrator with crown, teacher, student) with devices

CENTER: Large isometric server with multiple service modules:
- Web server (Nginx)
- PHP application
- Security shield
- Services: Auth, Exams, Analytics, Backups, Monitoring

BELOW: JSON file database (stack of documents)

RIGHT: External services in clouds:
- Email/SMTP
- GitHub CI/CD  
- Cron jobs
- OVH Cloud

Flowing arrows with labels (HTTPS, SMTP, JSON) connect all components.

Style: Modern flat design, 30° isometric view, corporate palette (navy blue, mint green, warm orange), light gray background with subtle grid.

Title: "IPSSI Examens — Architecture Overview"
Subtitle: "Secure Online Exam Platform"

High resolution 16:9, all text readable, AWS architecture diagram quality.
```

---

## 📋 Version Midjourney V6+

```
Isometric 3D architecture diagram, online exam platform "IPSSI Examens" ::3
Three user characters (admin with crown, teacher with tablet, student with cap) on left ::2
Central isometric server with glowing service cubes (auth, exams, analytics, backups) ::2
Document stack database below server ::1
External cloud services on right (email, github, cron, OVH) ::1
Flowing arrows with technical labels ::1
Modern flat design, soft shadows, 30 degree isometric perspective ::2
Corporate palette deep navy, mint green, warm orange, light gray grid background ::1
Clean sans-serif typography, title "IPSSI Examens Architecture Overview" ::1
High quality, professional, AWS architecture diagram style
--ar 16:9 --v 6 --style raw --q 2
```

---

## 📋 Version Ideogram (texte lisible garanti)

Ideogram est particulièrement bon pour les diagrammes avec du texte :

```
Isometric 3D architecture diagram showing IPSSI Examens online exam platform.

Layout: Left users (admin/teacher/student), center server with services, bottom database, right external clouds (SMTP, GitHub, Cron, OVH).

All text must be perfectly readable:
- Title: "IPSSI Examens"
- Subtitle: "Architecture Overview"
- Labels on services: "Auth", "Exams API", "Analytics", "Backups", "Monitoring"
- Connection labels: "HTTPS", "SMTP", "JSON"
- Footer: "© 2026 Mohamed EL AFRIT"

Modern flat design with isometric 3D, navy blue and mint green palette, AWS-style professional architecture diagram quality.

16:9 landscape, high resolution.
```

---

## 🎯 Variations par objectif

### 🎤 Pour présentation exécutive

```
Clean, minimalist executive-level architecture illustration for "IPSSI Examens".

Style: Apple Keynote / Google Slides aesthetic. White/light background. Elegant thin lines.

Show 3-4 key blocks only:
1. Users (3 silhouettes)
2. Cloud platform (single stylized icon)
3. Data security (shield icon)
4. Analytics (chart icon)

Minimal text, max 10 words total. Premium feel.

16:9, executive presentation quality.
```

### 🌐 Pour page web (hero image)

```
Modern hero image for a SaaS website selling "IPSSI Examens" — online exam platform.

Vibrant but professional. Diagonal composition. Abstract tech elements (dots, lines, flowing shapes) + subtle 3D isometric platform elements.

Color palette: tech gradient (cyan to purple), white accents.

Tagline space at top-left for "Secure Online Exams Made Simple".

16:9 wide, web-optimized (1920x800 recommended).
```

### 📄 Pour cover de documentation

```
Book cover style illustration for "IPSSI Examens — Architecture Documentation".

Portrait orientation (3:4). 

Minimalist tech illustration: stylized network of connected nodes in the top half, clean geometric patterns. 

Bottom half: large title text "IPSSI Examens" with subtitle "Architecture Documentation v1.0".

Navy blue and cream color palette. Classy, timeless, professional.

High resolution for print.
```

### 🎓 Pour équipe pédagogique

```
Friendly, approachable illustration of "IPSSI Examens" platform for educational context.

Show classroom-inspired elements: blackboard, books, laptops. 

3 happy characters: teacher, 2 students. 

Subtle tech integration: soft icons of shield, cloud, analytics floating around.

Warm palette: soft blues, greens, yellows. Welcoming educational feel.

16:9 for slide, readable text "IPSSI Examens" at top.
```

---

## 🎨 Post-production recommandée

Après génération de l'image :

### 1. Édition basique
- **Canva** : crop, resize, text overlay
- **Photopea** (gratuit) : alternative Photoshop
- **Figma** : retouche vectorielle, ajout d'éléments

### 2. Amélioration qualité
- **Upscale** avec Gigapixel AI ou Topaz Labs
- **Remove BG** avec remove.bg pour transparence

### 3. Adaptation format
- Export en PNG (transparence)
- Export en SVG (si possible, pour vectoriel)
- WebP pour web (plus léger)

---

## 💡 Conseils d'optimisation

### Pour DALL-E 3
- **Être très précis** sur le style
- Spécifier les couleurs en hex ou par nom
- Demander "all text must be readable"
- Itérer : "make the server bigger", "add more services"

### Pour Midjourney
- Utiliser `::N` pour pondérer
- `--ar 16:9` pour ratio
- `--v 6 --style raw` pour réalisme
- `--q 2` pour haute qualité
- `--stylize 500` (defaults 100) pour style

### Pour Ideogram
- **Meilleur pour le texte**
- Spécifier explicitement chaque label
- Choisir "Design" ou "Illustration"

### Pour Gemini Image
- Simple et direct
- Pas trop de détails techniques
- Itérations rapides

---

## 🎯 Exemples de résultats attendus

### Style "AWS Architecture"
Boxes rectangulaires, icônes standardisées, bleu dominant, flow clair.

### Style "Flat Design Moderne"
Couleurs pastels, illustrations plates, typography moderne.

### Style "Tech Startup"
Gradients, 3D isométrique, palette tech (cyan/purple).

### Style "Corporate Classique"
Monochrome ou 2-3 couleurs, sobre, professionnel.

---

## 📞 Support

- **Email** : m.elafrit@ecole-ipssi.net

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
