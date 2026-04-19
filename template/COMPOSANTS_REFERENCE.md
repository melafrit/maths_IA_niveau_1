# COMPOSANTS\_REFERENCE.md — Bibliothèque de composants React partagés

> © 2025 Mohamed EL AFRIT — IPSSI | [www.mohamedelafrit.com](https://www.mohamedelafrit.com)
> Licence CC BY-NC-SA 4.0

---

## Architecture générale

Chaque page `.jsx` est un **artifact autonome** : tous les composants sont définis
**dans le même fichier**, sans imports croisés. Ce document sert de **référence de
cohérence** pour que chaque page utilise exactement les mêmes composants, les mêmes
props, les mêmes couleurs et le même comportement.

**Dépendances externes** (chargées via CDN dans chaque page) :

| Bibliothèque | Version | CDN | Usage |
|---|---|---|---|
| KaTeX | 0.16.9 | `cdn.jsdelivr.net/npm/katex@0.16.9` | Rendu LaTeX |
| Pyodide | 0.24.1 | `cdn.jsdelivr.net/pyodide/v0.24.1/full/pyodide.js` | Python WASM |
| Google Fonts | — | `fonts.googleapis.com` | Source Serif 4, DM Sans, JetBrains Mono |

---

## 1. Système de thème — `ThemeProvider` + `useTheme`

### Objet de thème

```jsx
const THEMES = {
  light: {
    // Fond et texte
    bg: '#ffffff',
    bgAlt: '#fafafa',
    bgCode: '#f3f4f6',
    text: '#1a1a2e',
    textSecondary: '#4b5563',
    textMuted: '#9ca3af',

    // Bordures
    border: '#e5e7eb',
    borderLight: '#f3f4f6',

    // Encadrés pédagogiques
    definitionBg: '#eff6ff',    definitionBorder: '#3b82f6',  definitionTitle: '#1e40af',
    retenirBg:    '#f0fdf4',    retenirBorder:    '#22c55e',  retenirTitle:    '#166534',
    attentionBg:  '#fff7ed',    attentionBorder:  '#f97316',  attentionTitle:  '#c2410c',
    intuitionBg:  '#faf5ff',    intuitionBorder:  '#a855f7',  intuitionTitle:  '#7e22ce',
    exempleBg:    '#ecfeff',    exempleBorder:    '#06b6d4',  exempleTitle:    '#0e7490',
    codeBg:       '#f8f9fa',    codeBorder:       '#d1d5db',  codeTitle:       '#1f2937',

    // Sidebar
    sidebarBg: '#f8f9fa',
    sidebarText: '#374151',
    sidebarActive: '#3b82f6',
    sidebarActiveBg: '#eff6ff',
    sidebarHover: '#f3f4f6',

    // Liens et accents
    link: '#3b82f6',
    accent: '#3b82f6',

    // Données (graphiques)
    classe0: '#e74c3c',    // salaire ≤ 45k€
    classe1: '#2ecc71',    // salaire > 45k€
    regression: '#3498db', // droite de régression
  },

  dark: {
    bg: '#0d1117',
    bgAlt: '#161b22',
    bgCode: '#1c2128',
    text: '#c9d1d9',
    textSecondary: '#8b949e',
    textMuted: '#6e7681',

    border: '#30363d',
    borderLight: '#21262d',

    definitionBg: '#0d1f3c',    definitionBorder: '#3b82f6',  definitionTitle: '#93bbfc',
    retenirBg:    '#0a1f0d',    retenirBorder:    '#22c55e',  retenirTitle:    '#86efac',
    attentionBg:  '#1f1206',    attentionBorder:  '#f97316',  attentionTitle:  '#fdba74',
    intuitionBg:  '#1a0d2e',    intuitionBorder:  '#a855f7',  intuitionTitle:  '#d8b4fe',
    exempleBg:    '#0a1a1f',    exempleBorder:    '#06b6d4',  exempleTitle:    '#67e8f9',
    codeBg:       '#161b22',    codeBorder:       '#30363d',  codeTitle:       '#c9d1d9',

    sidebarBg: '#161b22',
    sidebarText: '#8b949e',
    sidebarActive: '#58a6ff',
    sidebarActiveBg: '#1c2541',
    sidebarHover: '#1c2128',

    link: '#58a6ff',
    accent: '#58a6ff',

    classe0: '#ff6b6b',
    classe1: '#51cf66',
    regression: '#74c0fc',
  },
};
```

### ThemeProvider (React Context)

```jsx
const ThemeContext = React.createContext();

function ThemeProvider({ children }) {
  const [mode, setMode] = React.useState('light'); // 'light' | 'dark'
  const theme = THEMES[mode];
  const toggle = () => setMode(m => m === 'light' ? 'dark' : 'light');
  return (
    <ThemeContext.Provider value={{ mode, theme, toggle }}>
      {children}
    </ThemeContext.Provider>
  );
}

function useTheme() {
  return React.useContext(ThemeContext);
}
```

---

## 2. ThemeToggle

Bouton soleil/lune pour basculer clair ↔ sombre.

| Prop | Type | Défaut | Description |
|---|---|---|---|
| — | — | — | Aucune prop — lit le contexte `useTheme()` |

```jsx
function ThemeToggle() {
  const { mode, toggle } = useTheme();
  return (
    <button
      onClick={toggle}
      style={{
        background: 'none', border: 'none', cursor: 'pointer',
        fontSize: '1.4rem', padding: '6px 10px',
        borderRadius: '8px', transition: 'background 0.3s',
      }}
      title={mode === 'light' ? 'Passer en mode sombre' : 'Passer en mode clair'}
      aria-label="Basculer le thème"
    >
      {mode === 'light' ? '🌙' : '☀️'}
    </button>
  );
}
```

**Placement** : tout en haut à droite du header, toujours accessible.

---

## 3. Sidebar — Menu latéral gauche fixe

| Prop | Type | Défaut | Description |
|---|---|---|---|
| `sections` | `Section[]` | requis | Arborescence des titres de la page |
| `jourActif` | `number` | `1` | Numéro du jour affiché (1–4) |
| `onJourChange` | `(n) => void` | — | Callback quand l'utilisateur change de jour |

### Structure de `sections`

```jsx
// Section = { id, title, level, children? }
const sections = [
  {
    id: 'ia-fonction',
    title: "1. L'IA comme fonction mathématique",
    level: 1,
    children: [
      { id: 'vecteurs', title: '1.1 Vecteurs', level: 2 },
      { id: 'matrices', title: '1.2 Matrices', level: 2 },
    ],
  },
  // ...
];
```

### Comportement

- **Position** : `position: fixed; left: 0; top: 0; height: 100vh; width: 280px;`
- **Mobile** (< 768px) : rétractable via icône hamburger
- **Arborescence pliable** : niveaux 2+ pliables avec chevron ▶ / ▼
- **Highlight actif** : la section visible dans le viewport est surlignée
  via `IntersectionObserver` sur les éléments `<section id="...">`.
- **Scroll fluide** : clic → `element.scrollIntoView({ behavior: 'smooth' })`
- **Barre de progression** : barre verticale le long du côté gauche indiquant
  le pourcentage de page scrollée

### Éléments fixes

- **En haut** : titre « Maths IA — IPSSI » + sélecteur de jour (onglets 1/2/3/4)
- **En bas** : boutons téléchargement PDF/Colab + mention auteur + licence CC

---

## 4. Box — Encadrés pédagogiques

| Prop | Type | Défaut | Description |
|---|---|---|---|
| `type` | `string` | requis | `'definition'` \| `'retenir'` \| `'attention'` \| `'intuition'` \| `'exemple'` \| `'code'` |
| `title` | `string` | auto | Titre affiché (défaut = nom du type) |
| `children` | `ReactNode` | — | Contenu de l'encadré |

```jsx
function Box({ type, title, children }) {
  const { theme } = useTheme();

  const CONFIG = {
    definition: { icon: '📐', label: 'Définition',  bgKey: 'definitionBg', borderKey: 'definitionBorder', titleKey: 'definitionTitle' },
    retenir:    { icon: '⚡', label: 'À retenir',    bgKey: 'retenirBg',    borderKey: 'retenirBorder',    titleKey: 'retenirTitle'    },
    attention:  { icon: '⚠️', label: 'Attention',    bgKey: 'attentionBg',  borderKey: 'attentionBorder',  titleKey: 'attentionTitle'  },
    intuition:  { icon: '💡', label: 'Intuition',    bgKey: 'intuitionBg',  borderKey: 'intuitionBorder',  titleKey: 'intuitionTitle'  },
    exemple:    { icon: '📊', label: 'Exemple',      bgKey: 'exempleBg',    borderKey: 'exempleBorder',    titleKey: 'exempleTitle'    },
    code:       { icon: '🐍', label: 'Code Python',  bgKey: 'codeBg',       borderKey: 'codeBorder',       titleKey: 'codeTitle'       },
  };

  const cfg = CONFIG[type];
  const displayTitle = title || cfg.label;

  return (
    <div style={{
      background: theme[cfg.bgKey],
      borderLeft: `4px solid ${theme[cfg.borderKey]}`,
      borderRadius: '6px',
      padding: '16px 20px',
      margin: '20px 0',
    }}>
      <div style={{
        fontFamily: "'DM Sans', sans-serif",
        fontWeight: 700,
        color: theme[cfg.titleKey],
        marginBottom: '10px',
        fontSize: '0.95rem',
      }}>
        {cfg.icon} {displayTitle}
      </div>
      <div style={{ color: theme.text, lineHeight: 1.7 }}>
        {children}
      </div>
    </div>
  );
}
```

### Correspondance LaTeX ↔ React

| LaTeX | React |
|---|---|
| `\begin{definitionbox}[titre]` | `<Box type="definition" title="titre">` |
| `\begin{retenirbox}` | `<Box type="retenir">` |
| `\begin{attentionbox}` | `<Box type="attention">` |
| `\begin{intuitionbox}` | `<Box type="intuition">` |
| `\begin{exemplebox}` | `<Box type="exemple">` |
| `\begin{pythonbox}` | `<Box type="code">` |

---

## 5. MathInline / MathBlock — Rendu LaTeX (KaTeX)

### MathInline — formule inline

| Prop | Type | Défaut | Description |
|---|---|---|---|
| `tex` | `string` | requis | Expression LaTeX (sans `$...$`) |

```jsx
function MathInline({ tex }) {
  const html = katex.renderToString(tex, {
    throwOnError: false,
    displayMode: false,
  });
  return <span dangerouslySetInnerHTML={{ __html: html }} />;
}
```

**Usage** : `Le vecteur <MathInline tex="\\mathbf{x}" /> contient les features.`

### MathBlock — formule display (centrée)

| Prop | Type | Défaut | Description |
|---|---|---|---|
| `tex` | `string` | requis | Expression LaTeX |

```jsx
function MathBlock({ tex }) {
  const html = katex.renderToString(tex, {
    throwOnError: false,
    displayMode: true,
  });
  return (
    <div style={{ margin: '20px 0', textAlign: 'center', overflowX: 'auto' }}
         dangerouslySetInnerHTML={{ __html: html }} />
  );
}
```

**Usage** : `<MathBlock tex="\\hat{y} = \\mathbf{w}^T \\mathbf{x} + b" />`

### Conventions de notation (identiques au LaTeX)

| Concept | KaTeX | Rendu |
|---|---|---|
| Vecteur | `\mathbf{x}` | **x** |
| Matrice | `\mathbf{X}` | **X** |
| Prédiction | `\hat{y}` | ŷ |
| Coût | `J(\boldsymbol{\theta})` | J(**θ**) |
| Gradient | `\nabla J(\boldsymbol{\theta})` | ∇J(**θ**) |
| Produit scalaire | `\langle \mathbf{x}, \mathbf{w} \rangle` | ⟨**x**,**w**⟩ |
| Taux d'apprentissage | `\alpha` | α |

---

## 6. PythonCell — Cellule Python exécutable (Pyodide)

| Prop | Type | Défaut | Description |
|---|---|---|---|
| `code` | `string` | `''` | Code Python initial |
| `readOnly` | `boolean` | `false` | Si `true`, l'éditeur n'est pas modifiable |
| `title` | `string` | `'Code Python'` | Titre affiché au-dessus de la cellule |
| `expectedOutput` | `string` | `null` | Sortie pré-rendue affichée en fallback |

### Comportement

1. **Chargement de Pyodide** : un singleton partagé par toutes les cellules de la page.
   Pyodide est chargé au premier clic sur "Exécuter" (lazy loading).
2. **Éditeur** : `<textarea>` stylisé en monospace (JetBrains Mono), fond gris clair/sombre.
3. **Bouton « ▶ Exécuter »** : lance le code dans le worker Pyodide, capture stdout.
4. **Sortie** : texte stdout affiché sous l'éditeur, images matplotlib converties en base64 PNG.
5. **Indicateur de chargement** : spinner pendant le chargement de Pyodide et l'exécution.

```jsx
// Singleton Pyodide
let pyodideReady = null;
async function getPyodide() {
  if (!pyodideReady) {
    pyodideReady = loadPyodide({ indexURL: 'https://cdn.jsdelivr.net/pyodide/v0.24.1/full/' });
    const py = await pyodideReady;
    await py.loadPackage(['numpy', 'matplotlib']);
  }
  return pyodideReady;
}

function PythonCell({ code: initialCode, readOnly = false, title = 'Code Python', expectedOutput = null }) {
  const { theme } = useTheme();
  const [code, setCode] = React.useState(initialCode);
  const [output, setOutput] = React.useState(expectedOutput || '');
  const [imgSrc, setImgSrc] = React.useState(null);
  const [running, setRunning] = React.useState(false);
  const [pyLoaded, setPyLoaded] = React.useState(false);

  const run = async () => {
    setRunning(true);
    setOutput('');
    setImgSrc(null);
    try {
      const py = await getPyodide();
      setPyLoaded(true);
      // Redirect matplotlib to base64
      py.runPython(`
import io, base64
import matplotlib
matplotlib.use('AGG')
import matplotlib.pyplot as plt
`);
      // Capture stdout
      py.runPython(`
import sys, io
_capture = io.StringIO()
sys.stdout = _capture
`);
      py.runPython(code);
      const stdout = py.runPython('_capture.getvalue()');
      setOutput(stdout);

      // Check for matplotlib figure
      const hasPlot = py.runPython(`
buf = io.BytesIO()
fig = plt.gcf()
has = len(fig.get_axes()) > 0
if has:
    fig.savefig(buf, format='png', dpi=120, bbox_inches='tight', facecolor='white')
    buf.seek(0)
plt.close('all')
has
`);
      if (hasPlot) {
        const b64 = py.runPython("base64.b64encode(buf.getvalue()).decode()");
        setImgSrc(`data:image/png;base64,${b64}`);
      }
    } catch (err) {
      setOutput(`Erreur : ${err.message}`);
    }
    setRunning(false);
  };

  return (
    <div style={{
      border: `1px solid ${theme.codeBorder}`,
      borderRadius: '8px',
      overflow: 'hidden',
      margin: '20px 0',
    }}>
      {/* Header */}
      <div style={{
        background: theme.codeBg,
        padding: '8px 16px',
        display: 'flex', justifyContent: 'space-between', alignItems: 'center',
        borderBottom: `1px solid ${theme.codeBorder}`,
        fontFamily: "'DM Sans', sans-serif",
        fontSize: '0.85rem',
        color: theme.codeTitle,
        fontWeight: 600,
      }}>
        <span>🐍 {title}</span>
        <button onClick={run} disabled={running} style={{
          background: '#22c55e', color: 'white', border: 'none',
          padding: '4px 14px', borderRadius: '4px', cursor: 'pointer',
          fontWeight: 600, fontSize: '0.85rem',
        }}>
          {running ? '⏳ Exécution...' : '▶ Exécuter'}
        </button>
      </div>

      {/* Éditeur */}
      <textarea
        value={code}
        onChange={e => !readOnly && setCode(e.target.value)}
        readOnly={readOnly}
        spellCheck={false}
        style={{
          width: '100%', minHeight: '120px',
          fontFamily: "'JetBrains Mono', monospace",
          fontSize: '0.85rem', lineHeight: 1.6,
          background: theme.bgCode, color: theme.text,
          border: 'none', padding: '16px', resize: 'vertical',
          outline: 'none', boxSizing: 'border-box',
        }}
      />

      {/* Sortie */}
      {(output || imgSrc) && (
        <div style={{
          borderTop: `1px solid ${theme.codeBorder}`,
          padding: '12px 16px',
          background: theme.bgAlt,
          fontFamily: "'JetBrains Mono', monospace",
          fontSize: '0.8rem', color: theme.textSecondary,
          whiteSpace: 'pre-wrap',
        }}>
          {output && <pre style={{ margin: 0 }}>{output}</pre>}
          {imgSrc && <img src={imgSrc} alt="Graphique matplotlib"
            style={{ maxWidth: '100%', marginTop: '8px', borderRadius: '4px' }} />}
        </div>
      )}
    </div>
  );
}
```

---

## 7. Section — Wrapper de section avec ancre

| Prop | Type | Défaut | Description |
|---|---|---|---|
| `id` | `string` | requis | Identifiant unique (ancre HTML) |
| `title` | `string` | requis | Titre de la section |
| `level` | `number` | `1` | Niveau du titre (1 = h2, 2 = h3, 3 = h4) |
| `children` | `ReactNode` | — | Contenu |

```jsx
function Section({ id, title, level = 1, children }) {
  const { theme } = useTheme();
  const Tag = `h${level + 1}`;  // level 1 → h2, level 2 → h3
  const sizes = { 1: '1.8rem', 2: '1.4rem', 3: '1.15rem' };

  return (
    <section id={id} style={{ scrollMarginTop: '80px' }}>
      <Tag style={{
        fontFamily: "'DM Sans', sans-serif",
        fontSize: sizes[level],
        color: theme.text,
        fontWeight: 700,
        borderBottom: level === 1 ? `2px solid ${theme.accent}` : 'none',
        paddingBottom: level === 1 ? '8px' : '0',
        marginTop: level === 1 ? '48px' : '32px',
        marginBottom: '16px',
      }}>
        {title}
      </Tag>
      {children}
    </section>
  );
}
```

---

## 8. ColabButton — Bouton « Ouvrir dans Google Colab »

| Prop | Type | Défaut | Description |
|---|---|---|---|
| `notebookUrl` | `string` | requis | URL complète du notebook Colab |
| `label` | `string` | `'Ouvrir dans Colab'` | Texte du bouton |

```jsx
function ColabButton({ notebookUrl, label = 'Ouvrir dans Colab' }) {
  return (
    <a href={notebookUrl} target="_blank" rel="noopener noreferrer"
       style={{
         display: 'inline-flex', alignItems: 'center', gap: '8px',
         background: '#F9AB00', color: '#1a1a2e',
         padding: '8px 18px', borderRadius: '6px',
         textDecoration: 'none', fontWeight: 600,
         fontFamily: "'DM Sans', sans-serif", fontSize: '0.9rem',
         transition: 'opacity 0.2s',
       }}
       onMouseOver={e => e.target.style.opacity = '0.85'}
       onMouseOut={e => e.target.style.opacity = '1'}
    >
      <img src="https://colab.research.google.com/img/colab_favicon_256px.png"
           alt="Colab" style={{ width: 20, height: 20 }} />
      {label}
    </a>
  );
}
```

---

## 9. PDFButton — Bouton de téléchargement PDF/TEX

| Prop | Type | Défaut | Description |
|---|---|---|---|
| `href` | `string` | requis | URL ou chemin du fichier à télécharger |
| `label` | `string` | requis | Texte du bouton |
| `icon` | `string` | `'📄'` | Emoji / icône affiché |
| `variant` | `string` | `'primary'` | `'primary'` (bleu) \| `'secondary'` (gris) |

```jsx
function PDFButton({ href, label, icon = '📄', variant = 'primary' }) {
  const { theme } = useTheme();
  const bg = variant === 'primary' ? theme.accent : theme.border;
  const fg = variant === 'primary' ? '#ffffff' : theme.text;
  return (
    <a href={href} download style={{
      display: 'inline-flex', alignItems: 'center', gap: '6px',
      background: bg, color: fg,
      padding: '8px 16px', borderRadius: '6px',
      textDecoration: 'none', fontWeight: 600,
      fontFamily: "'DM Sans', sans-serif", fontSize: '0.85rem',
      transition: 'opacity 0.2s',
    }}>
      {icon} {label}
    </a>
  );
}
```

### Groupe de boutons de téléchargement

```jsx
function DownloadGroup({ jour }) {
  return (
    <div style={{ display: 'flex', gap: '10px', flexWrap: 'wrap', margin: '20px 0' }}>
      <PDFButton href={`/docs/jour${jour}_synthese.pdf`} label="Synthèse" icon="📐" />
      <PDFButton href={`/docs/jour${jour}_exercices.pdf`} label="Exercices" icon="📝" />
      <PDFButton href={`/docs/jour${jour}_corrections.pdf`} label="Corrections" icon="✅" />
      <ColabButton notebookUrl={`https://colab.research.google.com/...jour${jour}`} />
    </div>
  );
}
```

---

## 10. Footer — Pied de page

| Prop | Type | Défaut | Description |
|---|---|---|---|
| — | — | — | Aucune prop — utilise le contexte thème |

```jsx
function Footer() {
  const { theme } = useTheme();
  return (
    <footer style={{
      marginTop: '60px',
      padding: '24px 0',
      borderTop: `1px solid ${theme.border}`,
      textAlign: 'center',
      fontFamily: "'DM Sans', sans-serif",
      fontSize: '0.8rem',
      color: theme.textMuted,
    }}>
      <p>© 2025 Mohamed EL AFRIT — IPSSI |{' '}
         <a href="https://www.mohamedelafrit.com" style={{ color: theme.link }}>
           www.mohamedelafrit.com
         </a>
      </p>
      <p>
        <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.fr"
           style={{ color: theme.link }}>
          CC BY-NC-SA 4.0
        </a>
        {' | '}
        <a href="/guide-colab" style={{ color: theme.link }}>
          Guide Google Colab
        </a>
      </p>
    </footer>
  );
}
```

---

## 11. ExerciceLevel — Indicateur de niveau

| Prop | Type | Défaut | Description |
|---|---|---|---|
| `level` | `number` | requis | `1` = fondamental, `2` = intermédiaire, `3` = avancé |

```jsx
function ExerciceLevel({ level }) {
  const CONFIG = {
    1: { label: 'Fondamental',   color: '#22c55e', stars: 1 },
    2: { label: 'Intermédiaire', color: '#f97316', stars: 2 },
    3: { label: 'Avancé',        color: '#e74c3c', stars: 3 },
  };
  const cfg = CONFIG[level];
  const filled = '★'.repeat(cfg.stars);
  const empty  = '★'.repeat(3 - cfg.stars);
  return (
    <span style={{ fontFamily: "'DM Sans', sans-serif", fontSize: '0.9rem' }}>
      <span style={{ color: cfg.color }}>{filled}</span>
      <span style={{ color: '#d1d5db' }}>{empty}</span>
      {' '}
      <span style={{ color: cfg.color, fontWeight: 700 }}>{cfg.label}</span>
    </span>
  );
}
```

### Correspondance LaTeX ↔ React

| LaTeX | React |
|---|---|
| `\exofondamental` | `<ExerciceLevel level={1} />` |
| `\exointermediaire` | `<ExerciceLevel level={2} />` |
| `\exoavance` | `<ExerciceLevel level={3} />` |

---

## 12. Dataset fil rouge — Constantes partagées

Chaque page qui a besoin du dataset doit inclure cette constante :

```jsx
const DATASET = {
  colonnes: ["experience", "nb_langages", "niveau_etudes",
             "taille_entreprise", "remote", "salaire", "salaire_eleve"],
  donnees: [
    [ 0, 2, 3,  150,  80, 28.0, 0], [ 1, 1, 2,   50, 100, 26.5, 0],
    [ 1, 3, 4,  800,  40, 35.0, 0], [ 2, 2, 3,  200,  60, 32.0, 0],
    [ 2, 4, 4, 3000,  20, 38.0, 0], [ 0, 1, 1,   30,  50, 25.0, 0],
    [ 3, 3, 3,  100,  80, 34.5, 0], [ 1, 5, 4,   15, 100, 42.0, 0],
    [ 4, 3, 3,  500,  60, 38.5, 0], [ 5, 4, 4, 1200,  40, 44.0, 0],
    [ 5, 3, 3,  300,  80, 41.0, 0], [ 6, 5, 4, 2000,  30, 48.0, 1],
    [ 6, 4, 3,  150,  90, 43.5, 0], [ 7, 5, 4,  800,  50, 50.0, 1],
    [ 7, 3, 2,   80,  70, 39.0, 0], [ 4, 6, 5,  400, 100, 47.0, 1],
    [ 8, 4, 4, 1500,  40, 51.0, 1], [ 5, 2, 3, 4000,  10, 40.0, 0],
    [ 9, 5, 4,  600,  60, 52.0, 1], [10, 6, 4, 2500,  30, 55.0, 1],
    [10, 4, 3,  200,  80, 48.5, 1], [11, 5, 5, 1000,  50, 58.0, 1],
    [12, 7, 4, 3500,  20, 60.0, 1], [ 9, 3, 3,   60,  90, 42.0, 0],
    [13, 6, 4,  800,  70, 62.0, 1], [11, 4, 4, 5000,  10, 54.0, 1],
    [15, 8, 4, 1200,  50, 65.0, 1], [17, 6, 5, 2000,  40, 68.0, 1],
    [20, 7, 4,  500,  60, 72.0, 1], [18, 5, 3,  100,  80, 58.0, 1],
  ],
  stats: {
    regression: { w: 2.07, b: 31.27, mse: 20.46 },
    correlations: { exp_sal: 0.927, etu_sal: 0.667, lang_sal: 0.897 },
    classification: { seuil: 45, classe0: 15, classe1: 15 },
  },
};
```

---

## 13. Typographie — Feuille de style de base

```jsx
const BASE_STYLES = `
  @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,700;1,400&family=JetBrains+Mono:wght@400;500;700&family=Source+Serif+4:ital,wght@0,400;0,600;0,700;1,400&display=swap');

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Source Serif 4', Georgia, serif;
    font-size: 17px;
    line-height: 1.8;
    transition: background 0.3s, color 0.3s;
  }

  h1, h2, h3, h4, h5, h6, nav, button, .sidebar {
    font-family: 'DM Sans', -apple-system, sans-serif;
  }

  code, pre, textarea, .code-cell {
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
  }

  .main-content {
    margin-left: 280px;  /* espace pour la sidebar */
    max-width: 820px;
    padding: 40px 48px;
  }

  @media (max-width: 768px) {
    .main-content { margin-left: 0; padding: 20px; }
  }
`;
```

---

## 14. Layout de page complet — Structure type

```jsx
export default function JourXPage() {
  return (
    <ThemeProvider>
      <style>{BASE_STYLES}</style>
      <link rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css" />

      <PageLayout>
        <Sidebar sections={SECTIONS} jourActif={1} />

        <main className="main-content">
          <Header />
          <DownloadGroup jour={1} />

          <Section id="intro" title="1. L'IA comme fonction mathématique" level={1}>
            <Box type="definition" title="Fonction de prédiction">
              Une <strong>fonction de prédiction</strong>{' '}
              <MathInline tex="f : \\mathbb{R}^p \\to \\mathbb{R}" /> ...
            </Box>
            <MathBlock tex="\\hat{y} = \\mathbf{w}^T \\mathbf{x} + b" />
          </Section>

          {/* ... autres sections ... */}

          <Footer />
        </main>
      </PageLayout>
    </ThemeProvider>
  );
}
```

---

## Checklist de cohérence inter-pages

Avant de livrer une page `.jsx`, vérifier :

- [ ] `ThemeProvider` englobe tout le rendu
- [ ] `THEMES` light/dark sont identiques à cette référence
- [ ] Tous les `Box` utilisent les mêmes 6 types et mêmes couleurs
- [ ] `MathInline` / `MathBlock` utilisent KaTeX (jamais de texte brut pour les maths)
- [ ] `PythonCell` charge Pyodide en singleton
- [ ] `Sidebar` reflète exactement les `Section` de la page
- [ ] `Footer` contient auteur + licence CC + lien Colab
- [ ] Toutes les couleurs viennent de `theme` (pas de couleurs en dur)
- [ ] Google Fonts chargées : Source Serif 4, DM Sans, JetBrains Mono
- [ ] Mention « © 2025 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0 » présente
- [ ] Le `DATASET` embarqué est identique à cette référence (30 lignes, 7 colonnes)

---

*© 2025 Mohamed EL AFRIT — IPSSI | [www.mohamedelafrit.com](https://www.mohamedelafrit.com) | CC BY-NC-SA 4.0*
