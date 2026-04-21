/* ============================================================================
   components-advanced.js — Composants React avancés
   Plateforme d'examens IPSSI — Phase P2.3

   Étend window.UI avec les composants métier-spécifiques.

   Composants exportés (11) :
     Math/Code  : KatexMath, CodeBlock
     Time       : ChronoDisplay
     Theme      : ThemeToggle
     Data       : DataTable, Pagination, Tabs, Accordion
     Feedback   : EmptyState, Stat
     Forms      : Wizard

   Dépendances :
     - Charge KaTeX en lazy load (singleton via window._katexReady)

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  if (!root.UI) {
    console.error('components-advanced.js requires components-base.js to be loaded first');
    return;
  }

  const { useState, useEffect, useRef, useCallback, useMemo, Fragment } = React;

  /* ==========================================================================
     LAZY-LOADER KATEX (singleton)
     Évite les chargements multiples si le composant est utilisé plusieurs fois
     ========================================================================== */

  function loadKatex() {
    if (root._katexReady) return root._katexReady;
    if (root.katex) {
      root._katexReady = Promise.resolve(root.katex);
      return root._katexReady;
    }
    root._katexReady = new Promise((resolve, reject) => {
      // CSS
      if (!document.querySelector('link[href*="katex"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css';
        document.head.appendChild(link);
      }
      // JS
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js';
      script.onload = () => resolve(root.katex);
      script.onerror = () => reject(new Error('KaTeX load failed'));
      document.head.appendChild(script);
    });
    return root._katexReady;
  }

  /* ==========================================================================
     1. KATEXMATH - Rendu LaTeX
     Props : math (string), inline (bool), throwOnError (bool)
     ========================================================================== */

  function KatexMath(props) {
    const { math = '', inline = false, throwOnError = false, ...rest } = props;
    const ref = useRef(null);
    const [error, setError] = useState(null);

    useEffect(() => {
      let cancelled = false;
      loadKatex().then((katex) => {
        if (cancelled || !ref.current) return;
        try {
          katex.render(math, ref.current, {
            displayMode: !inline,
            throwOnError,
            errorColor: 'var(--color-danger)',
            output: 'html',
          });
          setError(null);
        } catch (e) {
          if (!cancelled) setError(e.message);
        }
      }).catch((e) => {
        if (!cancelled) setError('Erreur chargement KaTeX');
      });
      return () => { cancelled = true; };
    }, [math, inline, throwOnError]);

    if (error) {
      return (
        <span style={{ color: 'var(--color-danger)', fontFamily: 'var(--font-mono)' }}>
          [LaTeX error: {error}]
        </span>
      );
    }

    const Tag = inline ? 'span' : 'div';
    return (
      <Tag
        ref={ref}
        style={inline ? { display: 'inline-block' } : { margin: 'var(--space-3) 0', textAlign: 'center' }}
        {...rest}
      />
    );
  }

  /* ==========================================================================
     2. CODEBLOCK - Bloc de code avec copie
     Props : code, language, showLineNumbers, copyable
     ========================================================================== */

  function CodeBlock(props) {
    const { code = '', language = '', showLineNumbers = false, copyable = true, ...rest } = props;
    const [copied, setCopied] = useState(false);

    const copy = async () => {
      try {
        await navigator.clipboard.writeText(code);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
      } catch (e) {
        // Fallback : selection
        const textarea = document.createElement('textarea');
        textarea.value = code;
        document.body.appendChild(textarea);
        textarea.select();
        try { document.execCommand('copy'); setCopied(true); setTimeout(() => setCopied(false), 2000); } catch (e2) {}
        document.body.removeChild(textarea);
      }
    };

    const lines = code.split('\n');

    return (
      <div style={{
        position: 'relative',
        background: 'var(--c-neutral-900)',
        color: 'var(--c-neutral-100)',
        borderRadius: 'var(--radius-md)',
        overflow: 'hidden',
        margin: 'var(--space-3) 0',
        fontFamily: 'var(--font-mono)',
        fontSize: 'var(--text-sm)',
        lineHeight: 'var(--leading-relaxed)',
      }} {...rest}>
        {(language || copyable) && (
          <div style={{
            display: 'flex', justifyContent: 'space-between', alignItems: 'center',
            padding: 'var(--space-2) var(--space-3)',
            background: 'rgba(255,255,255,0.05)',
            borderBottom: '1px solid rgba(255,255,255,0.1)',
            fontSize: 'var(--text-xs)',
          }}>
            <span style={{ color: 'var(--c-neutral-400)', textTransform: 'uppercase', letterSpacing: 'var(--tracking-wider)' }}>
              {language || 'code'}
            </span>
            {copyable && (
              <button onClick={copy} style={{
                background: 'transparent', border: '1px solid rgba(255,255,255,0.2)',
                color: copied ? 'var(--c-success-500)' : 'var(--c-neutral-300)',
                padding: '2px 8px', borderRadius: 'var(--radius-sm)',
                cursor: 'pointer', fontSize: 'var(--text-xs)',
                fontFamily: 'var(--font-sans)',
                transition: 'all var(--transition-fast)',
              }}>
                {copied ? '✓ Copié' : 'Copier'}
              </button>
            )}
          </div>
        )}
        <pre style={{ margin: 0, padding: 'var(--space-3) var(--space-4)', overflowX: 'auto', background: 'transparent' }}>
          {showLineNumbers ? (
            <table style={{ borderCollapse: 'collapse', width: '100%' }}>
              <tbody>
                {lines.map((line, i) => (
                  <tr key={i}>
                    <td style={{
                      color: 'var(--c-neutral-500)', userSelect: 'none',
                      paddingRight: 'var(--space-3)', textAlign: 'right',
                      fontSize: 'var(--text-xs)', verticalAlign: 'top',
                      borderRight: '1px solid rgba(255,255,255,0.1)',
                    }}>{i + 1}</td>
                    <td style={{ paddingLeft: 'var(--space-3)', whiteSpace: 'pre' }}>
                      <code style={{ background: 'transparent', padding: 0 }}>{line || ' '}</code>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <code style={{ background: 'transparent', padding: 0 }}>{code}</code>
          )}
        </pre>
      </div>
    );
  }

  /* ==========================================================================
     3. CHRONODISPLAY - Chronomètre countdown / countup
     Props : startAt (Date|ISO string), endAt (Date|ISO string|null),
             countDown (bool, default true si endAt fourni),
             alerts (array of {atSeconds, color, label}) - alertes visuelles
             onExpire (callback)
     ========================================================================== */

  function ChronoDisplay(props) {
    const {
      endAt = null,
      startAt = null,
      countDown = endAt !== null,
      alerts = [],
      onExpire = null,
      compact = false,
      ...rest
    } = props;

    const [now, setNow] = useState(Date.now());

    useEffect(() => {
      const id = setInterval(() => setNow(Date.now()), 1000);
      return () => clearInterval(id);
    }, []);

    const targetTs = endAt ? new Date(endAt).getTime() : null;
    const startTs = startAt ? new Date(startAt).getTime() : Date.now();

    let secondsRemaining = 0;
    let secondsElapsed = 0;
    let progress = 0;

    if (countDown && targetTs) {
      secondsRemaining = Math.max(0, Math.floor((targetTs - now) / 1000));
      const totalDuration = Math.max(1, (targetTs - startTs) / 1000);
      progress = 1 - (secondsRemaining / totalDuration);
    } else {
      secondsElapsed = Math.max(0, Math.floor((now - startTs) / 1000));
    }

    const displaySeconds = countDown ? secondsRemaining : secondsElapsed;
    const expired = countDown && secondsRemaining === 0;

    // Trigger onExpire une seule fois
    const expireFiredRef = useRef(false);
    useEffect(() => {
      if (expired && !expireFiredRef.current && onExpire) {
        expireFiredRef.current = true;
        onExpire();
      }
    }, [expired, onExpire]);

    // Formatage
    const h = Math.floor(displaySeconds / 3600);
    const m = Math.floor((displaySeconds % 3600) / 60);
    const s = displaySeconds % 60;
    const formatted = h > 0
      ? `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
      : `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;

    // Détermination de la couleur d'alerte
    let alertColor = 'var(--color-text)';
    let alertLabel = null;
    let pulse = false;
    if (countDown && alerts.length > 0) {
      const sortedAlerts = [...alerts].sort((a, b) => a.atSeconds - b.atSeconds);
      for (const alert of sortedAlerts) {
        if (secondsRemaining <= alert.atSeconds) {
          alertColor = alert.color || 'var(--color-warning)';
          alertLabel = alert.label || null;
          if (alert.pulse !== undefined) pulse = alert.pulse;
          else if (secondsRemaining <= 60) pulse = true;
          break;
        }
      }
    }

    if (expired) {
      alertColor = 'var(--color-danger)';
    }

    if (compact) {
      return (
        <span style={{
          fontFamily: 'var(--font-mono)', fontWeight: 'var(--font-semibold)',
          color: alertColor,
          animation: pulse ? 'pulse 1s ease-in-out infinite' : 'none',
        }} {...rest}>
          ⏱ {formatted}
        </span>
      );
    }

    return (
      <div style={{
        display: 'inline-flex', flexDirection: 'column', alignItems: 'center',
        padding: 'var(--space-3) var(--space-4)',
        background: 'var(--color-bg-card)',
        border: `2px solid ${alertColor}`,
        borderRadius: 'var(--radius-lg)',
        boxShadow: 'var(--shadow-sm)',
        animation: pulse ? 'pulse 1.5s ease-in-out infinite' : 'none',
      }} {...rest}>
        <div style={{
          fontFamily: 'var(--font-mono)',
          fontSize: 'var(--text-3xl)',
          fontWeight: 'var(--font-bold)',
          fontVariantNumeric: 'tabular-nums',
          color: alertColor,
          lineHeight: 1,
        }}>
          {formatted}
        </div>
        {alertLabel && (
          <div style={{
            fontSize: 'var(--text-xs)', fontWeight: 'var(--font-semibold)',
            color: alertColor, marginTop: 'var(--space-1)',
            textTransform: 'uppercase', letterSpacing: 'var(--tracking-wider)',
          }}>
            {expired ? 'Temps écoulé' : alertLabel}
          </div>
        )}
        {!alertLabel && (
          <div style={{ fontSize: 'var(--text-xs)', color: 'var(--color-text-muted)', marginTop: 'var(--space-1)' }}>
            {countDown ? 'Temps restant' : 'Temps écoulé'}
          </div>
        )}
      </div>
    );
  }

  /* ==========================================================================
     4. THEMETOGGLE - Bascule clair/sombre
     Props : storageKey, onChange
     ========================================================================== */

  function ThemeToggle(props) {
    const { storageKey = 'theme', onChange = null, size = 'md', ...rest } = props;

    const [theme, setTheme] = useState(() => {
      try {
        return localStorage.getItem(storageKey) || document.documentElement.getAttribute('data-theme') || 'light';
      } catch (e) {
        return 'light';
      }
    });

    useEffect(() => {
      document.documentElement.setAttribute('data-theme', theme);
      try { localStorage.setItem(storageKey, theme); } catch (e) {}
      if (onChange) onChange(theme);
    }, [theme, storageKey, onChange]);

    const toggle = () => setTheme(theme === 'dark' ? 'light' : 'dark');

    const dim = size === 'sm' ? 32 : size === 'lg' ? 48 : 40;

    return (
      <button
        onClick={toggle}
        aria-label={`Basculer en mode ${theme === 'dark' ? 'clair' : 'sombre'}`}
        title={`Mode ${theme === 'dark' ? 'clair' : 'sombre'}`}
        style={{
          width: dim, height: dim,
          display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
          background: 'var(--color-bg-card)',
          border: '1px solid var(--color-border)',
          borderRadius: 'var(--radius-full)',
          cursor: 'pointer',
          fontSize: dim * 0.4,
          transition: 'all var(--transition-fast)',
          color: 'var(--color-text)',
        }}
        {...rest}
      >
        {theme === 'dark' ? '☀️' : '🌙'}
      </button>
    );
  }

  /* ==========================================================================
     5. PAGINATION
     Props : currentPage, totalPages, onPageChange, maxVisible
     ========================================================================== */

  function Pagination(props) {
    const { currentPage = 1, totalPages = 1, onPageChange, maxVisible = 7, ...rest } = props;

    if (totalPages <= 1) return null;

    // Calculer les pages à afficher avec ... pour les grandes plages
    const pages = [];
    const half = Math.floor(maxVisible / 2);
    let start = Math.max(1, currentPage - half);
    let end = Math.min(totalPages, start + maxVisible - 1);
    if (end - start + 1 < maxVisible) {
      start = Math.max(1, end - maxVisible + 1);
    }

    if (start > 1) {
      pages.push(1);
      if (start > 2) pages.push('...');
    }
    for (let i = start; i <= end; i++) pages.push(i);
    if (end < totalPages) {
      if (end < totalPages - 1) pages.push('...');
      pages.push(totalPages);
    }

    const PageBtn = ({ page, active, disabled, children }) => (
      <button
        onClick={() => !disabled && page && onPageChange && onPageChange(page)}
        disabled={disabled || !page}
        aria-current={active ? 'page' : undefined}
        style={{
          minWidth: '36px', height: '36px',
          padding: '0 var(--space-2)',
          background: active ? 'var(--color-primary)' : 'var(--color-bg-card)',
          color: active ? 'var(--color-text-on-primary)' : 'var(--color-text)',
          border: `1px solid ${active ? 'var(--color-primary)' : 'var(--color-border)'}`,
          borderRadius: 'var(--radius-md)',
          cursor: disabled || !page ? 'default' : 'pointer',
          fontSize: 'var(--text-sm)',
          fontWeight: active ? 'var(--font-semibold)' : 'var(--font-normal)',
          opacity: disabled ? 0.4 : 1,
          fontFamily: 'inherit',
        }}
      >
        {children}
      </button>
    );

    return (
      <div style={{ display: 'flex', gap: 'var(--space-1)', alignItems: 'center', justifyContent: 'center', flexWrap: 'wrap' }} {...rest}>
        <PageBtn page={Math.max(1, currentPage - 1)} disabled={currentPage === 1}>‹</PageBtn>
        {pages.map((p, i) => (
          p === '...' ? (
            <span key={`dots-${i}`} style={{ padding: '0 var(--space-1)', color: 'var(--color-text-muted)' }}>…</span>
          ) : (
            <PageBtn key={p} page={p} active={p === currentPage}>{p}</PageBtn>
          )
        ))}
        <PageBtn page={Math.min(totalPages, currentPage + 1)} disabled={currentPage === totalPages}>›</PageBtn>
      </div>
    );
  }

  /* ==========================================================================
     6. DATATABLE - Tableau riche avec tri, filtre, pagination
     Props :
       columns: [{ key, label, sortable, width, render: (row) => ReactNode }]
       data: array d'objets
       searchable, searchPlaceholder
       pageSize (defaut 10)
       emptyMessage
       onRowClick
     ========================================================================== */

  function DataTable(props) {
    const {
      columns = [],
      data = [],
      searchable = true,
      searchPlaceholder = 'Rechercher...',
      pageSize = 10,
      emptyMessage = 'Aucune donnée',
      onRowClick = null,
      ...rest
    } = props;

    const [search, setSearch] = useState('');
    const [sortKey, setSortKey] = useState(null);
    const [sortDir, setSortDir] = useState('asc');
    const [page, setPage] = useState(1);

    // Filtrage
    const filtered = useMemo(() => {
      if (!search.trim()) return data;
      const q = search.toLowerCase();
      return data.filter(row =>
        Object.values(row).some(v => String(v ?? '').toLowerCase().includes(q))
      );
    }, [data, search]);

    // Tri
    const sorted = useMemo(() => {
      if (!sortKey) return filtered;
      return [...filtered].sort((a, b) => {
        const av = a[sortKey], bv = b[sortKey];
        if (av == null) return sortDir === 'asc' ? 1 : -1;
        if (bv == null) return sortDir === 'asc' ? -1 : 1;
        if (typeof av === 'number' && typeof bv === 'number') {
          return sortDir === 'asc' ? av - bv : bv - av;
        }
        const cmp = String(av).localeCompare(String(bv), 'fr', { numeric: true });
        return sortDir === 'asc' ? cmp : -cmp;
      });
    }, [filtered, sortKey, sortDir]);

    // Pagination
    const totalPages = Math.max(1, Math.ceil(sorted.length / pageSize));
    const safePage = Math.min(page, totalPages);
    const paged = sorted.slice((safePage - 1) * pageSize, safePage * pageSize);

    const handleSort = (key) => {
      if (sortKey === key) {
        setSortDir(sortDir === 'asc' ? 'desc' : 'asc');
      } else {
        setSortKey(key);
        setSortDir('asc');
      }
    };

    return (
      <div style={{ width: '100%' }} {...rest}>
        {/* Barre de recherche */}
        {searchable && (
          <div style={{ marginBottom: 'var(--space-3)' }}>
            <input
              type="search"
              value={search}
              onChange={(e) => { setSearch(e.target.value); setPage(1); }}
              placeholder={searchPlaceholder}
              style={{
                width: '100%', maxWidth: '300px',
                padding: '8px 12px',
                fontSize: 'var(--text-sm)', fontFamily: 'inherit',
                color: 'var(--color-text)',
                background: 'var(--color-bg-input)',
                border: '1px solid var(--color-border)',
                borderRadius: 'var(--radius-md)',
                outline: 'none',
              }}
            />
          </div>
        )}

        {/* Tableau */}
        <div style={{
          overflowX: 'auto',
          background: 'var(--color-bg-card)',
          border: '1px solid var(--color-border)',
          borderRadius: 'var(--radius-lg)',
        }}>
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ background: 'var(--color-bg-subtle)' }}>
                {columns.map(col => (
                  <th
                    key={col.key}
                    onClick={col.sortable !== false ? () => handleSort(col.key) : undefined}
                    style={{
                      padding: 'var(--space-3) var(--space-4)',
                      textAlign: 'left',
                      fontSize: 'var(--text-xs)',
                      fontWeight: 'var(--font-semibold)',
                      color: 'var(--color-text-muted)',
                      textTransform: 'uppercase',
                      letterSpacing: 'var(--tracking-wider)',
                      borderBottom: '1px solid var(--color-border)',
                      cursor: col.sortable !== false ? 'pointer' : 'default',
                      userSelect: 'none',
                      width: col.width || 'auto',
                      whiteSpace: 'nowrap',
                    }}
                  >
                    <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4 }}>
                      {col.label}
                      {col.sortable !== false && (
                        <span style={{ opacity: sortKey === col.key ? 1 : 0.3, fontSize: '0.8em' }}>
                          {sortKey === col.key ? (sortDir === 'asc' ? '▲' : '▼') : '⇅'}
                        </span>
                      )}
                    </span>
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {paged.length === 0 ? (
                <tr>
                  <td colSpan={columns.length} style={{
                    padding: 'var(--space-8)', textAlign: 'center',
                    color: 'var(--color-text-muted)', fontSize: 'var(--text-sm)',
                  }}>
                    {emptyMessage}
                  </td>
                </tr>
              ) : paged.map((row, idx) => (
                <tr
                  key={row.id || idx}
                  onClick={onRowClick ? () => onRowClick(row) : undefined}
                  style={{
                    cursor: onRowClick ? 'pointer' : 'default',
                    borderBottom: idx === paged.length - 1 ? 'none' : '1px solid var(--color-border)',
                    transition: 'background var(--transition-fast)',
                  }}
                  onMouseEnter={onRowClick ? (e) => e.currentTarget.style.background = 'var(--color-bg-subtle)' : undefined}
                  onMouseLeave={onRowClick ? (e) => e.currentTarget.style.background = 'transparent' : undefined}
                >
                  {columns.map(col => (
                    <td key={col.key} style={{
                      padding: 'var(--space-3) var(--space-4)',
                      fontSize: 'var(--text-sm)',
                      color: 'var(--color-text)',
                      verticalAlign: 'middle',
                    }}>
                      {col.render ? col.render(row) : row[col.key] ?? ''}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Footer pagination + info */}
        {sorted.length > 0 && (
          <div style={{
            display: 'flex', justifyContent: 'space-between', alignItems: 'center',
            marginTop: 'var(--space-3)', flexWrap: 'wrap', gap: 'var(--space-2)',
            fontSize: 'var(--text-sm)', color: 'var(--color-text-muted)',
          }}>
            <span>
              Affichage {(safePage - 1) * pageSize + 1}–{Math.min(safePage * pageSize, sorted.length)} sur {sorted.length}
              {filtered.length !== data.length && ` (filtré sur ${data.length})`}
            </span>
            <Pagination currentPage={safePage} totalPages={totalPages} onPageChange={setPage} />
          </div>
        )}
      </div>
    );
  }

  /* ==========================================================================
     7. TABS
     Props : tabs ([{key, label, content}]), defaultActive, onChange
     ========================================================================== */

  function Tabs(props) {
    const { tabs = [], defaultActive = null, onChange = null, ...rest } = props;
    const [active, setActive] = useState(defaultActive ?? (tabs[0]?.key));

    const select = (key) => {
      setActive(key);
      if (onChange) onChange(key);
    };

    const onKey = (e, idx) => {
      if (e.key === 'ArrowRight') {
        e.preventDefault();
        const next = tabs[(idx + 1) % tabs.length];
        if (next) select(next.key);
      } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        const prev = tabs[(idx - 1 + tabs.length) % tabs.length];
        if (prev) select(prev.key);
      }
    };

    const activeTab = tabs.find(t => t.key === active);

    return (
      <div {...rest}>
        <div role="tablist" style={{
          display: 'flex', gap: 'var(--space-1)',
          borderBottom: '1px solid var(--color-border)',
          marginBottom: 'var(--space-4)',
          overflowX: 'auto',
        }}>
          {tabs.map((tab, idx) => (
            <button
              key={tab.key}
              role="tab"
              aria-selected={tab.key === active}
              tabIndex={tab.key === active ? 0 : -1}
              onClick={() => select(tab.key)}
              onKeyDown={(e) => onKey(e, idx)}
              style={{
                padding: 'var(--space-3) var(--space-4)',
                background: 'transparent',
                color: tab.key === active ? 'var(--color-primary)' : 'var(--color-text-muted)',
                border: 'none',
                borderBottom: `2px solid ${tab.key === active ? 'var(--color-primary)' : 'transparent'}`,
                marginBottom: '-1px',
                cursor: 'pointer',
                fontWeight: tab.key === active ? 'var(--font-semibold)' : 'var(--font-medium)',
                fontSize: 'var(--text-sm)',
                fontFamily: 'inherit',
                transition: 'all var(--transition-fast)',
                whiteSpace: 'nowrap',
              }}
            >
              {tab.icon && <span style={{ marginRight: 4 }}>{tab.icon}</span>}
              {tab.label}
              {tab.badge !== undefined && (
                <span style={{
                  marginLeft: 6, fontSize: 'var(--text-xs)',
                  background: tab.key === active ? 'var(--color-primary-soft)' : 'var(--color-bg-subtle)',
                  color: 'inherit', padding: '1px 6px', borderRadius: 'var(--radius-full)',
                  fontWeight: 'var(--font-semibold)',
                }}>{tab.badge}</span>
              )}
            </button>
          ))}
        </div>
        <div role="tabpanel">
          {activeTab && activeTab.content}
        </div>
      </div>
    );
  }

  /* ==========================================================================
     8. ACCORDION
     Props : items ([{key, title, content, defaultOpen}]), allowMultiple
     ========================================================================== */

  function Accordion(props) {
    const { items = [], allowMultiple = false, ...rest } = props;
    const [openKeys, setOpenKeys] = useState(() =>
      new Set(items.filter(i => i.defaultOpen).map(i => i.key))
    );

    const toggle = (key) => {
      setOpenKeys((prev) => {
        const next = new Set(allowMultiple ? prev : []);
        if (prev.has(key)) {
          next.delete(key);
        } else {
          next.add(key);
        }
        return next;
      });
    };

    return (
      <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-2)' }} {...rest}>
        {items.map((item) => {
          const isOpen = openKeys.has(item.key);
          return (
            <div key={item.key} style={{
              background: 'var(--color-bg-card)',
              border: '1px solid var(--color-border)',
              borderRadius: 'var(--radius-md)',
              overflow: 'hidden',
            }}>
              <button
                onClick={() => toggle(item.key)}
                aria-expanded={isOpen}
                style={{
                  width: '100%',
                  display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                  padding: 'var(--space-3) var(--space-4)',
                  background: isOpen ? 'var(--color-bg-subtle)' : 'transparent',
                  border: 'none', textAlign: 'left',
                  cursor: 'pointer',
                  fontFamily: 'inherit',
                  fontSize: 'var(--text-base)',
                  fontWeight: 'var(--font-medium)',
                  color: 'var(--color-text)',
                  transition: 'background var(--transition-fast)',
                }}
              >
                <span>{item.title}</span>
                <span style={{
                  fontSize: 'var(--text-sm)',
                  color: 'var(--color-text-muted)',
                  transition: 'transform var(--transition-fast)',
                  transform: isOpen ? 'rotate(180deg)' : 'rotate(0deg)',
                }}>▼</span>
              </button>
              {isOpen && (
                <div style={{
                  padding: 'var(--space-4)',
                  borderTop: '1px solid var(--color-border)',
                  fontSize: 'var(--text-sm)',
                  color: 'var(--color-text)',
                  animation: 'fadeIn 200ms ease-out',
                }}>
                  {item.content}
                </div>
              )}
            </div>
          );
        })}
      </div>
    );
  }

  /* ==========================================================================
     9. EMPTYSTATE - État vide stylisé
     Props : icon, title, message, action
     ========================================================================== */

  function EmptyState(props) {
    const { icon = '📭', title = 'Aucune donnée', message = null, action = null, ...rest } = props;
    return (
      <div style={{
        display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
        padding: 'var(--space-12) var(--space-4)',
        textAlign: 'center',
        color: 'var(--color-text-muted)',
      }} {...rest}>
        <div style={{ fontSize: 'var(--text-6xl)', marginBottom: 'var(--space-4)', opacity: 0.6 }}>
          {icon}
        </div>
        <h3 style={{
          margin: 0, fontSize: 'var(--text-lg)', fontWeight: 'var(--font-semibold)',
          color: 'var(--color-text)',
        }}>
          {title}
        </h3>
        {message && (
          <p style={{
            margin: 'var(--space-2) 0 0 0',
            fontSize: 'var(--text-sm)',
            maxWidth: '400px',
          }}>
            {message}
          </p>
        )}
        {action && (
          <div style={{ marginTop: 'var(--space-5)' }}>
            {action}
          </div>
        )}
      </div>
    );
  }

  /* ==========================================================================
     10. STAT - KPI / Statistique avec tendance
     Props : label, value, trend (number), trendLabel, color, icon
     ========================================================================== */

  function Stat(props) {
    const { label, value, trend = null, trendLabel = null, color = 'var(--color-primary)', icon = null, ...rest } = props;

    const trendColor = trend === null ? 'var(--color-text-muted)' :
                       trend > 0 ? 'var(--color-success)' :
                       trend < 0 ? 'var(--color-danger)' : 'var(--color-text-muted)';
    const trendArrow = trend === null ? '' : trend > 0 ? '↑' : trend < 0 ? '↓' : '→';

    return (
      <div style={{
        background: 'var(--color-bg-card)',
        border: '1px solid var(--color-border)',
        borderRadius: 'var(--radius-xl)',
        padding: 'var(--space-5)',
        boxShadow: 'var(--shadow-sm)',
      }} {...rest}>
        <div style={{
          display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start',
          marginBottom: 'var(--space-2)',
        }}>
          <div style={{
            fontSize: 'var(--text-sm)', color: 'var(--color-text-muted)',
            textTransform: 'uppercase', letterSpacing: 'var(--tracking-wider)',
            fontWeight: 'var(--font-medium)',
          }}>
            {label}
          </div>
          {icon && (
            <div style={{
              width: 36, height: 36, borderRadius: 'var(--radius-md)',
              background: `color-mix(in srgb, ${color} 12%, transparent)`,
              color: color,
              display: 'flex', alignItems: 'center', justifyContent: 'center',
              fontSize: 'var(--text-lg)',
            }}>{icon}</div>
          )}
        </div>
        <div style={{
          fontSize: 'var(--text-3xl)',
          fontWeight: 'var(--font-bold)',
          color: 'var(--color-text-strong)',
          fontFamily: 'var(--font-heading)',
          fontVariantNumeric: 'tabular-nums',
          lineHeight: 1.2,
        }}>
          {value}
        </div>
        {trend !== null && (
          <div style={{
            display: 'flex', alignItems: 'center', gap: 'var(--space-1)',
            marginTop: 'var(--space-2)', fontSize: 'var(--text-sm)',
            color: trendColor, fontWeight: 'var(--font-medium)',
          }}>
            <span>{trendArrow}</span>
            <span>{Math.abs(trend)}%</span>
            {trendLabel && (
              <span style={{ color: 'var(--color-text-muted)', fontWeight: 'var(--font-normal)' }}>
                {trendLabel}
              </span>
            )}
          </div>
        )}
      </div>
    );
  }

  /* ==========================================================================
     11. WIZARD - Formulaire multi-étapes
     Props :
       steps: [{key, label, content, validate?: () => bool|string}]
       onComplete: (data) => void
       initialStep
     ========================================================================== */

  function Wizard(props) {
    const { steps = [], onComplete = null, initialStep = 0, ...rest } = props;
    const [current, setCurrent] = useState(initialStep);
    const [errors, setErrors] = useState({});

    if (steps.length === 0) return null;

    const step = steps[current];
    const isFirst = current === 0;
    const isLast = current === steps.length - 1;

    const goNext = () => {
      // Validation optionnelle
      if (step.validate) {
        const result = step.validate();
        if (result !== true && result !== undefined) {
          setErrors({ ...errors, [step.key]: typeof result === 'string' ? result : 'Étape invalide' });
          return;
        }
      }
      setErrors({ ...errors, [step.key]: null });
      if (isLast) {
        if (onComplete) onComplete();
      } else {
        setCurrent(current + 1);
      }
    };

    const goPrev = () => {
      if (!isFirst) setCurrent(current - 1);
    };

    const goTo = (idx) => {
      // On peut revenir en arrière librement, ou aller en avant si pas de validation manquante
      if (idx <= current) setCurrent(idx);
    };

    const { Button: BtnComp } = root.UI;

    return (
      <div {...rest}>
        {/* Stepper en haut */}
        <div style={{
          display: 'flex', alignItems: 'center', justifyContent: 'space-between',
          marginBottom: 'var(--space-6)', overflowX: 'auto', paddingBottom: 'var(--space-2)',
        }}>
          {steps.map((s, idx) => {
            const isActive = idx === current;
            const isDone = idx < current;
            const isClickable = idx <= current;
            return (
              <Fragment key={s.key}>
                <div
                  onClick={() => isClickable && goTo(idx)}
                  style={{
                    display: 'flex', alignItems: 'center', gap: 'var(--space-2)',
                    cursor: isClickable ? 'pointer' : 'default',
                    opacity: isClickable ? 1 : 0.5,
                    flexShrink: 0,
                  }}
                >
                  <div style={{
                    width: 32, height: 32, borderRadius: '50%',
                    background: isActive || isDone ? 'var(--color-primary)' : 'var(--color-bg-subtle)',
                    color: isActive || isDone ? 'var(--color-text-on-primary)' : 'var(--color-text-muted)',
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    fontWeight: 'var(--font-semibold)', fontSize: 'var(--text-sm)',
                    border: `2px solid ${isActive || isDone ? 'var(--color-primary)' : 'var(--color-border)'}`,
                    flexShrink: 0,
                  }}>
                    {isDone ? '✓' : idx + 1}
                  </div>
                  <div>
                    <div style={{
                      fontSize: 'var(--text-sm)',
                      fontWeight: isActive ? 'var(--font-semibold)' : 'var(--font-medium)',
                      color: isActive ? 'var(--color-text-strong)' : 'var(--color-text)',
                    }}>
                      {s.label}
                    </div>
                  </div>
                </div>
                {idx < steps.length - 1 && (
                  <div style={{
                    flex: 1, minWidth: 30, height: 2,
                    background: idx < current ? 'var(--color-primary)' : 'var(--color-border)',
                    margin: '0 var(--space-3)',
                  }} />
                )}
              </Fragment>
            );
          })}
        </div>

        {/* Contenu de l'étape courante */}
        <div style={{ minHeight: '200px', marginBottom: 'var(--space-6)' }}>
          {step.content}
          {errors[step.key] && (
            <div style={{
              marginTop: 'var(--space-3)',
              padding: 'var(--space-3)',
              background: 'var(--color-danger-soft)',
              color: 'var(--c-danger-700)',
              borderRadius: 'var(--radius-md)',
              fontSize: 'var(--text-sm)',
            }}>
              {errors[step.key]}
            </div>
          )}
        </div>

        {/* Navigation */}
        <div style={{
          display: 'flex', justifyContent: 'space-between', alignItems: 'center',
          paddingTop: 'var(--space-4)', borderTop: '1px solid var(--color-border)',
        }}>
          <BtnComp variant="ghost" onClick={goPrev} disabled={isFirst}>
            ← Précédent
          </BtnComp>
          <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-muted)' }}>
            Étape {current + 1} / {steps.length}
          </span>
          <BtnComp variant="primary" onClick={goNext}>
            {isLast ? 'Terminer' : 'Suivant →'}
          </BtnComp>
        </div>
      </div>
    );
  }

  /* ==========================================================================
     EXPORTS - étendre window.UI
     ========================================================================== */

  Object.assign(root.UI, {
    KatexMath, CodeBlock, ChronoDisplay, ThemeToggle,
    DataTable, Pagination, Tabs, Accordion,
    EmptyState, Stat, Wizard,
  });

})(window);
