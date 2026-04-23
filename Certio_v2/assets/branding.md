# 🎨 Guide de Branding Certio v2.0

> Identité visuelle de référence pour tous les livrables Certio

---

## 🎯 Identité

| Élément | Valeur |
|---|---|
| **Nom produit** | Certio |
| **Tagline** | Certainty-Based Assessment Platform |
| **Description courte** | Plateforme d'évaluation avec Certainty-Based Marking |
| **Description longue** | Plateforme SaaS multi-écoles pour créer, distribuer et corriger des examens QCM avec le système Certainty-Based Marking (CBM) |
| **Domaine** | certio.app |
| **Email principal** | mohamed@elafrit.com |
| **Slogan pédagogique** | "La vraie mesure d'une connaissance, c'est la conscience qu'on en a." |

---

## 🎨 Palette de couleurs

### Primaires

| Usage | Couleur | HEX | RGB |
|---|---|---|---|
| 🔵 **Primary** | Deep Navy | `#1a365d` | `26, 54, 93` |
| 🟢 **Secondary** | Mint Green | `#48bb78` | `72, 187, 120` |
| 🟠 **Accent** | Warm Orange | `#ed8936` | `237, 137, 54` |

### Variantes Primary

| Variante | HEX |
|---|---|
| Primary Light | `#2c5282` |
| Primary Dark | `#0f2547` |
| Primary 50 | `#ebf8ff` |
| Primary 100 | `#bee3f8` |
| Primary 500 | `#3182ce` |
| Primary 900 | `#1a365d` |

### États

| État | HEX | Usage |
|---|---|---|
| 🟢 Success | `#38a169` | Succès, réussi, validé |
| 🟡 Warning | `#d69e2e` | Attention, vigilance |
| 🔴 Danger | `#e53e3e` | Erreur, danger, échec |
| 🔵 Info | `#3182ce` | Information, neutre |

### Neutres

**Mode clair** :
| Usage | HEX |
|---|---|
| Background | `#ffffff` |
| Background Secondary | `#f7fafc` |
| Text Primary | `#1a202c` |
| Text Muted | `#718096` |
| Border | `#e2e8f0` |

**Mode sombre** :
| Usage | HEX |
|---|---|
| Background | `#0d1117` |
| Background Secondary | `#161b22` |
| Text Primary | `#c9d1d9` |
| Border | `#30363d` |

---

## 🔤 Typographie

### Polices principales

**UI & Body** : **Inter** (Google Fonts)
- Lisible, moderne, sans-serif
- Variantes : 400, 500, 600, 700
- Import : `https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap`

**Code** : **JetBrains Mono** (Google Fonts)
- Monospace dev-friendly
- Variantes : 400, 500
- Import : `https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap`

### Hiérarchie typographique

```css
/* H1 — Titre principal de page */
font-size: 2rem;       /* 32px */
font-weight: 700;
line-height: 1.2;
letter-spacing: -0.02em;

/* H2 — Section */
font-size: 1.5rem;     /* 24px */
font-weight: 600;
line-height: 1.3;

/* H3 — Sous-section */
font-size: 1.25rem;    /* 20px */
font-weight: 600;
line-height: 1.4;

/* Body — Texte courant */
font-size: 1rem;       /* 16px */
font-weight: 400;
line-height: 1.6;

/* Small — Texte secondaire */
font-size: 0.875rem;   /* 14px */
font-weight: 400;
line-height: 1.5;
```

---

## 🖼️ Logo

### Variantes disponibles

Le logo est dans `logo.svg` dans ce dossier.

| Variante | Format | Usage |
|---|---|---|
| Logo complet (symbole + texte) | SVG | En-têtes, documents |
| Icône seule (shield + check) | À créer | Favicons, badges |
| Version monochrome | À créer | Impression N&B |

### Règles d'utilisation

✅ **À faire** :
- Utiliser la version SVG pour toujours avoir une qualité optimale
- Respecter les proportions originales
- Garder une marge minimale de 20px autour du logo
- Utiliser sur fond clair ou sombre selon la variante

❌ **À ne pas faire** :
- Ne pas déformer (étirer/compresser)
- Ne pas changer les couleurs
- Ne pas ajouter d'effets (ombre, outline, glow)
- Ne pas utiliser sur un fond coloré qui réduit la lisibilité

### Zone de protection

Le logo doit avoir une zone de protection minimale équivalente à la hauteur de la lettre "C" dans "Certio" tout autour.

---

## 📐 Espacement et grille

### Système d'espacement (Tailwind-based)

```
0    → 0px
1    → 4px
2    → 8px
3    → 12px
4    → 16px    ← Base
6    → 24px
8    → 32px
12   → 48px
16   → 64px
24   → 96px
```

### Grille de mise en page

- **Container max-width** : 1280px (7xl)
- **Columns** : système flexbox + CSS grid
- **Gutter** : 16px (gap-4) ou 24px (gap-6)

---

## 🎭 Iconographie

### Librairie officielle

**Heroicons 2** (https://heroicons.com)
- Cohérent avec Tailwind
- 24x24 par défaut
- Outline + Solid versions

### Emojis fréquents

Pour illustrer les sections dans la doc :

| Emoji | Usage |
|---|---|
| 🎯 | Objectif, cible |
| 🎲 | CBM, certitude |
| 📝 | Examen, création |
| 📊 | Analytics, graphique |
| 🎓 | Étudiant, apprentissage |
| 👨‍🏫 | Prof |
| 🏫 | École, workspace |
| 🔐 | Sécurité |
| 🌐 | Community, partage |
| 💡 | Idée, astuce |
| ⚠️ | Attention |
| ✅ | OK, validé |
| ❌ | Erreur, refus |
| 🚀 | Lancement |
| 🎉 | Célébration |
| 📚 | Documentation |

---

## 📧 Email signature

Pour les emails transactionnels Certio :

```
--
[Logo Certio - 120px width]

{{ user.name }}  
{{ user.role }}

Certio - Certainty-Based Assessment Platform
📧 mohamed@elafrit.com
🌐 https://certio.app
📄 CC BY-NC-SA 4.0

"La vraie mesure d'une connaissance, c'est la conscience qu'on en a."
```

---

## 🗣️ Voix et ton

### Principes de communication

Certio s'adresse à 3 audiences, avec des tons adaptés :

#### 👨‍🏫 Pour les profs
- **Ton** : Professionnel, respectueux, clair
- **Style** : Concis, factuel
- **Exemple** : "Configurez la visibilité des corrections selon votre pédagogie."

#### 👨‍🎓 Pour les étudiants
- **Ton** : Bienveillant, encourageant, explicatif
- **Style** : Accessible, motivant
- **Exemple** : "Consultez vos corrections détaillées pour progresser !"

#### 🏫 Pour les admins d'école
- **Ton** : Business, sérieux, rassurant
- **Style** : Précis, data-driven
- **Exemple** : "Reporting complet sur l'activité de votre établissement."

### Guidelines générales

- ✅ **Français impeccable** : pas de fautes, accords corrects
- ✅ **Éviter jargon technique** sauf pour docs techniques
- ✅ **Actif plutôt que passif** : "Vous créez" > "Il est possible de créer"
- ✅ **Positif** : "Réussi" > "Non échoué"
- ✅ **Clair et bref** : 1 idée par phrase

### Mots à éviter

❌ "Notre plateforme" → Dire "Certio"  
❌ "Nous vous proposons" → "Certio vous permet"  
❌ "Hey utilisateur" → "Bonjour {prénom}"  
❌ "Échec" → "Non réussi" ou "À revoir"  

---

## 📱 Responsive breakpoints

Breakpoints Tailwind standards :

| Nom | Width | Usage |
|---|---|---|
| `sm` | ≥ 640px | Smartphones paysage |
| `md` | ≥ 768px | Tablettes |
| `lg` | ≥ 1024px | Laptops |
| `xl` | ≥ 1280px | Desktop |
| `2xl` | ≥ 1536px | Grand desktop |

Design **mobile-first** : commencer par mobile, étendre vers desktop.

---

## ♿ Accessibilité

### Règles WCAG AA à respecter

- **Contraste texte** : ≥ 4.5:1 (normal) et 3:1 (large)
- **Zones cliquables** : ≥ 44x44px (pouce)
- **Focus visible** : ring de 2px min
- **Alt text** : sur toutes images
- **ARIA labels** : sur boutons avec icônes seules
- **Hiérarchie titres** : H1 → H2 → H3 sans saut

### Palette accessible

Les couleurs primaires de Certio respectent WCAG AA :
- `#1a365d` sur blanc : contraste **11.5:1** ✅
- `#48bb78` sur blanc : contraste **3.1:1** (OK pour large text)
- `#ed8936` sur blanc : contraste **3.2:1** (OK pour large text)

---

## 🎯 Assets à produire

### Priorité haute (Phase P0)
- [x] Logo SVG (fait, dans `logo.svg`)
- [ ] Favicon ICO (16x16, 32x32)
- [ ] Icons PNG PWA (192x192, 512x512)
- [ ] Open Graph image (1200x630 pour partages sociaux)

### Priorité moyenne
- [ ] Illustrations vectorielles pour pages vides (empty states)
- [ ] Iconographie custom pour features spécifiques (CBM, community...)
- [ ] Screenshots produit pour landing page

### Priorité basse
- [ ] Templates email HTML
- [ ] Éléments graphiques pour réseaux sociaux
- [ ] Print templates (certificats, attestations)

---

## 📞 Contact designer

Pour toute question de branding :
**Mohamed EL AFRIT**  
📧 mohamed@elafrit.com

---

© 2026 Mohamed EL AFRIT — [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)
