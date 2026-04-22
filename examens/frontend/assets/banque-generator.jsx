/* ============================================================================
   banque-generator.jsx — Générateur d'examens aléatoires

   Plateforme d'examens IPSSI — Phase P5.3

   Fonctionnalités :
     - Scope hiérarchique : module/chapitre/thèmes (multi-sélection)
     - Stratégie : Custom (quotas manuels) ou Équitable (N réparti)
     - Quota sliders par niveau (facile/moyen/difficile/expert)
     - Seed pour reproductibilité
     - Preview des questions tirées
     - Export JSON / CSV / TXT imprimable

   Composant exporté : window.BanqueGenerator

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useCallback, useMemo } = React;

  // Réutilisation des helpers du Browser
  const MathText = root.MathText;
  const LevelBadge = root.LevelBadge;

  const LEVELS = ['facile', 'moyen', 'difficile', 'expert'];
  const LEVEL_LABELS = {
    facile: 'Facile',
    moyen: 'Moyen',
    difficile: 'Difficile',
    expert: 'Expert',
  };
  const LEVEL_COLORS = {
    facile: '#16a34a',
    moyen: '#ca8a04',
    difficile: '#ea580c',
    expert: '#dc2626',
  };

  // ==========================================================================
  // Composant : Slider coloré par niveau
  // ==========================================================================

  function QuotaSlider({ level, value, max, onChange, available }) {
    const color = LEVEL_COLORS[level];
    const label = LEVEL_LABELS[level];
    const clampedMax = Math.min(max, available);

    return (
      <div style={{
        padding: 'var(--space-3)',
        background: 'var(--color-bg-subtle)',
        borderRadius: 'var(--radius-md)',
        borderLeft: `3px solid ${color}`,
      }}>
        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: 'var(--space-2)',
        }}>
          <span style={{ fontWeight: 600, color: color }}>
            {level === 'facile' && '🟢'}
            {level === 'moyen' && '🟡'}
            {level === 'difficile' && '🟠'}
            {level === 'expert' && '🔴'}
            {' '}{label}
          </span>
          <span style={{
            fontFamily: 'var(--font-mono)',
            fontSize: 'var(--text-sm)',
            color: 'var(--color-text-muted)',
          }}>
            <strong style={{ color: color, fontSize: 'var(--text-lg)' }}>{value}</strong>
            {' / '}{available} dispo.
          </span>
        </div>

        <input
          type="range"
          min={0}
          max={clampedMax}
          value={Math.min(value, clampedMax)}
          onChange={e => onChange(parseInt(e.target.value, 10))}
          style={{
            width: '100%',
            accentColor: color,
            cursor: clampedMax > 0 ? 'pointer' : 'not-allowed',
          }}
          disabled={clampedMax === 0}
        />

        <div style={{
          display: 'flex',
          gap: 4,
          marginTop: 6,
        }}>
          {[0, 1, 2, 5, 10, clampedMax].filter((v, i, a) => v <= clampedMax && a.indexOf(v) === i).map(v => (
            <button
              key={v}
              onClick={() => onChange(v)}
              style={{
                padding: '2px 8px',
                fontSize: 11,
                background: value === v ? color : 'transparent',
                color: value === v ? 'white' : color,
                border: `1px solid ${color}`,
                borderRadius: 4,
                cursor: 'pointer',
                fontWeight: 600,
              }}
            >
              {v === clampedMax ? 'Max' : v}
            </button>
          ))}
        </div>
      </div>
    );
  }

  // ==========================================================================
  // Composant : Scope selector (module/chapitre/thèmes)
  // ==========================================================================

  function ScopeSelector({ scope, setScope, availableModules, availableChapitres, availableThemes, loading }) {
    const { Select, Checkbox } = root.UI;

    return (
      <div style={{
        padding: 'var(--space-3)',
        background: 'var(--color-bg-subtle)',
        borderRadius: 'var(--radius-md)',
        marginBottom: 'var(--space-4)',
      }}>
        <div style={{
          fontSize: 'var(--text-sm)',
          fontWeight: 600,
          marginBottom: 'var(--space-2)',
        }}>📍 Portée du tirage</div>

        {/* Module */}
        <div style={{ marginBottom: 'var(--space-2)' }}>
          <label style={{ fontSize: 'var(--text-xs)', fontWeight: 600, display: 'block', marginBottom: 4 }}>
            Module
          </label>
          <Select
            value={scope.module || ''}
            onChange={e => setScope({ module: e.target.value, chapitre: null, themes: [] })}
            options={[
              { value: '', label: '— Tous les modules —' },
              ...availableModules.map(m => ({ value: m, label: m })),
            ]}
          />
        </div>

        {/* Chapitre */}
        {scope.module && (
          <div style={{ marginBottom: 'var(--space-2)' }}>
            <label style={{ fontSize: 'var(--text-xs)', fontWeight: 600, display: 'block', marginBottom: 4 }}>
              Chapitre
            </label>
            <Select
              value={scope.chapitre || ''}
              onChange={e => setScope({ ...scope, chapitre: e.target.value, themes: [] })}
              options={[
                { value: '', label: '— Tous les chapitres —' },
                ...availableChapitres.map(c => ({ value: c, label: c })),
              ]}
            />
          </div>
        )}

        {/* Thèmes (multi) */}
        {scope.chapitre && availableThemes.length > 0 && (
          <div>
            <label style={{ fontSize: 'var(--text-xs)', fontWeight: 600, display: 'block', marginBottom: 4 }}>
              Thèmes (multi-sélection, vide = tous)
            </label>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
              {availableThemes.map(theme => {
                const selected = scope.themes.includes(theme);
                return (
                  <button
                    key={theme}
                    onClick={() => {
                      const newThemes = selected
                        ? scope.themes.filter(t => t !== theme)
                        : [...scope.themes, theme];
                      setScope({ ...scope, themes: newThemes });
                    }}
                    style={{
                      padding: '4px 10px',
                      fontSize: 12,
                      background: selected ? 'var(--color-primary)' : 'var(--color-bg-elevated)',
                      color: selected ? 'white' : 'var(--color-text)',
                      border: `1px solid ${selected ? 'var(--color-primary)' : 'var(--color-border)'}`,
                      borderRadius: 'var(--radius-md)',
                      cursor: 'pointer',
                      transition: 'all 0.15s',
                      fontWeight: selected ? 600 : 400,
                    }}
                  >
                    {selected ? '✓ ' : ''}{theme}
                  </button>
                );
              })}
            </div>
            {scope.themes.length > 0 && (
              <div style={{ marginTop: 6, fontSize: 11, color: 'var(--color-text-muted)' }}>
                {scope.themes.length} thème(s) sélectionné(s)
              </div>
            )}
          </div>
        )}

        {loading && (
          <div style={{ marginTop: 8, fontSize: 11, color: 'var(--color-text-muted)' }}>
            ⏳ Chargement...
          </div>
        )}
      </div>
    );
  }

  // ==========================================================================
  // Composant : Question card compacte (preview)
  // ==========================================================================

  function QuestionCard({ question, index }) {
    const [expanded, setExpanded] = useState(false);
    const letters = ['A', 'B', 'C', 'D'];

    return (
      <div style={{
        padding: 'var(--space-3)',
        border: '1px solid var(--color-border)',
        borderRadius: 'var(--radius-md)',
        background: 'var(--color-bg-elevated)',
        marginBottom: 'var(--space-2)',
      }}>
        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'flex-start',
          gap: 'var(--space-2)',
          marginBottom: 8,
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
            <span style={{
              background: 'var(--color-primary)',
              color: 'white',
              fontSize: 11,
              fontWeight: 700,
              padding: '2px 8px',
              borderRadius: 999,
            }}>#{index + 1}</span>
            <code style={{ fontSize: 'var(--text-sm)', fontWeight: 600 }}>{question.id}</code>
            <LevelBadge level={question.difficulte} />
            <span style={{
              padding: '2px 8px',
              fontSize: 11,
              background: 'var(--color-bg-subtle)',
              borderRadius: 999,
              color: 'var(--color-text-muted)',
            }}>{question.type}</span>
          </div>
          <button
            onClick={() => setExpanded(!expanded)}
            style={{
              background: 'none',
              border: 'none',
              cursor: 'pointer',
              fontSize: 'var(--text-sm)',
              color: 'var(--color-primary)',
            }}
          >
            {expanded ? '▲ Replier' : '▼ Détails'}
          </button>
        </div>

        <MathText
          text={question.enonce.length > 200 && !expanded
            ? question.enonce.substring(0, 200) + '...'
            : question.enonce}
          as="div"
          style={{ fontSize: 'var(--text-sm)', lineHeight: 1.5 }}
        />

        {expanded && (
          <div style={{ marginTop: 'var(--space-2)' }}>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
              {question.options.map((opt, i) => (
                <div
                  key={i}
                  style={{
                    padding: '6px 10px',
                    borderLeft: `3px solid ${i === question.correct ? '#16a34a' : 'var(--color-border)'}`,
                    background: i === question.correct ? 'rgba(34, 197, 94, 0.05)' : 'transparent',
                    fontSize: 'var(--text-sm)',
                    display: 'flex',
                    gap: 8,
                  }}
                >
                  <strong style={{ color: i === question.correct ? '#16a34a' : 'var(--color-primary)' }}>
                    {letters[i]}
                  </strong>
                  <MathText text={opt} as="span" style={{ flex: 1 }} />
                  {i === question.correct && (
                    <span style={{ color: '#16a34a' }}>✓</span>
                  )}
                </div>
              ))}
            </div>
            <div style={{
              marginTop: 8,
              padding: 8,
              background: 'var(--color-bg-subtle)',
              borderRadius: 4,
              fontSize: 12,
              color: 'var(--color-text-muted)',
            }}>
              📍 {question._module} / {question._chapitre} / {question._theme}
            </div>
          </div>
        )}
      </div>
    );
  }

  // ==========================================================================
  // Composant principal : BanqueGenerator
  // ==========================================================================

  function BanqueGenerator() {
    const { Button, Input, Box, Select, useToast } = root.UI;
    const { useApi } = root.UIHooks;
    const api = useApi();
    const { toast } = useToast();

    // Scope
    const [scope, setScope] = useState({ module: 'maths-ia', chapitre: null, themes: [] });
    const [availableModules, setAvailableModules] = useState([]);
    const [availableChapitres, setAvailableChapitres] = useState([]);
    const [availableThemes, setAvailableThemes] = useState([]);
    const [loadingScope, setLoadingScope] = useState(false);

    // Counts par niveau selon scope actuel
    const [levelCounts, setLevelCounts] = useState({ facile: 0, moyen: 0, difficile: 0, expert: 0 });

    // Strategy + quotas
    const [strategy, setStrategy] = useState('custom');
    const [quotas, setQuotas] = useState({ facile: 2, moyen: 3, difficile: 3, expert: 2 });
    const [nEquitable, setNEquitable] = useState(10);
    const [seed, setSeed] = useState('');

    // Résultats
    const [drawing, setDrawing] = useState(false);
    const [drawnQuestions, setDrawnQuestions] = useState([]);
    const [lastDrawConfig, setLastDrawConfig] = useState(null);

    const totalQuotas = Object.values(quotas).reduce((a, b) => a + b, 0);

    // ==========================================================================
    // Chargement initial des modules
    // ==========================================================================

    useEffect(() => {
      async function fetch() {
        const res = await api.request('GET', '/api/banque/modules');
        if (res.ok) setAvailableModules(res.data?.modules || []);
      }
      fetch();
    }, []);

    // Chargement des chapitres quand le module change
    useEffect(() => {
      async function fetch() {
        if (!scope.module) {
          setAvailableChapitres([]);
          return;
        }
        setLoadingScope(true);
        const res = await api.request('GET', `/api/banque/${scope.module}/chapitres`);
        if (res.ok) setAvailableChapitres(res.data?.chapitres || []);
        setLoadingScope(false);
      }
      fetch();
    }, [scope.module]);

    // Chargement des thèmes quand le chapitre change
    useEffect(() => {
      async function fetch() {
        if (!scope.module || !scope.chapitre) {
          setAvailableThemes([]);
          return;
        }
        setLoadingScope(true);
        const res = await api.request('GET', `/api/banque/${scope.module}/${scope.chapitre}/themes`);
        if (res.ok) setAvailableThemes(res.data?.themes || []);
        setLoadingScope(false);
      }
      fetch();
    }, [scope.module, scope.chapitre]);

    // Recomptage par niveau quand le scope change
    useEffect(() => {
      async function fetchCounts() {
        const filters = {};
        if (scope.module) filters.module = scope.module;
        if (scope.chapitre) filters.chapitre = scope.chapitre;

        const query = new URLSearchParams();
        Object.entries(filters).forEach(([k, v]) => query.set(k, v));

        const counts = { facile: 0, moyen: 0, difficile: 0, expert: 0 };

        for (const level of LEVELS) {
          const q = new URLSearchParams(query);
          q.set('difficulte', level);
          const res = await api.request('GET', `/api/banque/questions?${q.toString()}&limit=1`);
          if (res.ok) {
            let total = res.data?.total || 0;
            // Si thèmes sélectionnés, filtrer plus finement
            if (scope.themes.length > 0) {
              const themeTotals = await Promise.all(scope.themes.map(async (theme) => {
                const qt = new URLSearchParams(query);
                qt.set('difficulte', level);
                qt.set('theme', theme);
                const r2 = await api.request('GET', `/api/banque/questions?${qt.toString()}&limit=1`);
                return r2.ok ? (r2.data?.total || 0) : 0;
              }));
              total = themeTotals.reduce((a, b) => a + b, 0);
            }
            counts[level] = total;
          }
        }
        setLevelCounts(counts);
      }
      fetchCounts();
    }, [scope.module, scope.chapitre, scope.themes.join(',')]);

    // ==========================================================================
    // Actions : tirer un examen
    // ==========================================================================

    function buildScopePayload() {
      const payload = {};
      if (scope.module) payload.module = scope.module;
      if (scope.chapitre) payload.chapitre = scope.chapitre;
      // Note : l'API actuelle ne supporte qu'un seul theme ; on tire puis filtre côté client
      if (scope.themes.length === 1) payload.theme = scope.themes[0];
      return payload;
    }

    async function drawExam() {
      // Validation pre-tirage
      if (strategy === 'custom' && totalQuotas === 0) {
        toast({ title: 'Quotas vides', message: 'Augmentez au moins un quota', type: 'warning' });
        return;
      }
      if (strategy === 'equitable' && nEquitable <= 0) {
        toast({ title: 'N invalide', message: 'Choisissez un nombre > 0', type: 'warning' });
        return;
      }

      setDrawing(true);
      const scopePayload = buildScopePayload();

      // Si multi-thèmes, on fait N tirages séparés et on combine
      const needsMultiPass = scope.themes.length > 1;

      try {
        let questions = [];

        if (needsMultiPass) {
          // Répartir les quotas entre thèmes (approximatif)
          // Simple : tirer chaque niveau séparément sur chaque thème, en prenant le premier jusqu'à atteindre le quota
          const nThemes = scope.themes.length;
          const perThemeQuotas = {};
          for (const level of LEVELS) {
            perThemeQuotas[level] = Math.floor((strategy === 'custom' ? quotas[level] : Math.floor(nEquitable / 4)) / nThemes);
          }
          // On fait plusieurs tirages et on combine
          for (const theme of scope.themes) {
            const themeScope = { ...scopePayload, theme };
            const body = strategy === 'custom'
              ? { scope: themeScope, quotas: perThemeQuotas, seed: seed ? parseInt(seed, 10) : undefined }
              : { strategy: 'equitable', scope: themeScope, n: Math.floor(nEquitable / nThemes), seed: seed ? parseInt(seed, 10) : undefined };
            const res = await api.request('POST', '/api/banque/draw', body);
            if (res.ok) {
              questions = questions.concat(res.data?.questions || []);
            }
          }
          // Shuffle final
          questions = shuffle(questions);
        } else {
          // Tirage simple
          const body = strategy === 'custom'
            ? { scope: scopePayload, quotas, seed: seed ? parseInt(seed, 10) : undefined }
            : { strategy: 'equitable', scope: scopePayload, n: nEquitable, seed: seed ? parseInt(seed, 10) : undefined };

          const res = await api.request('POST', '/api/banque/draw', body);
          if (res.ok) {
            questions = res.data?.questions || [];
          } else {
            toast({
              title: 'Erreur tirage',
              message: res.error?.message || 'Impossible de tirer',
              type: 'error',
            });
            setDrawing(false);
            return;
          }
        }

        setDrawnQuestions(questions);
        setLastDrawConfig({ scope: scopePayload, strategy, quotas, nEquitable, seed });
        toast({
          title: 'Examen généré !',
          message: `${questions.length} question(s) tirée(s)`,
          type: 'success',
        });
      } catch (e) {
        toast({ title: 'Erreur', message: e.message, type: 'error' });
      }
      setDrawing(false);
    }

    function shuffle(arr) {
      const a = [...arr];
      for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
      }
      return a;
    }

    function redraw() {
      // Re-tirage avec config actuelle mais nouveau seed
      setSeed('');
      drawExam();
    }

    // ==========================================================================
    // Exports
    // ==========================================================================

    function download(filename, content, mimeType = 'text/plain') {
      const blob = new Blob([content], { type: mimeType + ';charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }

    function exportJSON() {
      const data = {
        meta: {
          generated_at: new Date().toISOString(),
          count: drawnQuestions.length,
          config: lastDrawConfig,
        },
        questions: drawnQuestions,
      };
      const ts = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
      download(`examen_${ts}.json`, JSON.stringify(data, null, 2), 'application/json');
      toast({ title: 'Export JSON', message: 'Fichier téléchargé', type: 'success' });
    }

    function exportCSV() {
      const escape = (s) => `"${String(s || '').replace(/"/g, '""').replace(/\n/g, ' ')}"`;
      const headers = ['index', 'id', 'difficulte', 'type', 'enonce', 'optA', 'optB', 'optC', 'optD', 'correct', 'tags'];
      const rows = drawnQuestions.map((q, i) => [
        i + 1,
        q.id,
        q.difficulte,
        q.type,
        escape(q.enonce),
        escape(q.options[0]),
        escape(q.options[1]),
        escape(q.options[2]),
        escape(q.options[3]),
        ['A', 'B', 'C', 'D'][q.correct],
        (q.tags || []).join(';'),
      ]);
      const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
      const ts = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
      download(`examen_${ts}.csv`, csv, 'text/csv');
      toast({ title: 'Export CSV', message: 'Fichier téléchargé', type: 'success' });
    }

    function exportTXT() {
      const letters = ['A', 'B', 'C', 'D'];
      let txt = `EXAMEN — IPSSI\n`;
      txt += `Généré le ${new Date().toLocaleString('fr-FR')}\n`;
      txt += `${drawnQuestions.length} questions\n`;
      txt += '═'.repeat(70) + '\n\n';

      drawnQuestions.forEach((q, i) => {
        txt += `Question ${i + 1} / ${drawnQuestions.length}`;
        txt += `  [${q.difficulte}]  [${q.type}]  ${q.id}\n\n`;
        txt += q.enonce + '\n\n';
        q.options.forEach((opt, j) => {
          txt += `  ${letters[j]}. ${opt}\n`;
        });
        txt += '\n' + '─'.repeat(70) + '\n\n';
      });

      txt += '═'.repeat(70) + '\n';
      txt += 'CORRIGÉ\n';
      txt += '═'.repeat(70) + '\n\n';
      drawnQuestions.forEach((q, i) => {
        txt += `Q${i + 1} [${q.id}] → Réponse : ${letters[q.correct]}\n`;
      });

      const ts = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
      download(`examen_${ts}.txt`, txt, 'text/plain');
      toast({ title: 'Export TXT', message: 'Fichier imprimable téléchargé', type: 'success' });
    }

    // ==========================================================================
    // Stats du tirage
    // ==========================================================================

    const drawStats = useMemo(() => {
      const stats = { facile: 0, moyen: 0, difficile: 0, expert: 0 };
      drawnQuestions.forEach(q => {
        if (stats[q.difficulte] !== undefined) stats[q.difficulte]++;
      });
      return stats;
    }, [drawnQuestions]);

    // ==========================================================================
    // Render
    // ==========================================================================

    return (
      <div>
        {/* Header */}
        <h3 style={{ marginTop: 0, display: 'flex', alignItems: 'center', gap: 8 }}>
          🎲 Générateur d'examens aléatoires
        </h3>
        <p style={{ color: 'var(--color-text-muted)', marginBottom: 'var(--space-4)' }}>
          Tirez un ensemble de questions selon vos critères pour créer un examen.
        </p>

        <div style={{
          display: 'grid',
          gridTemplateColumns: 'minmax(0, 1fr) minmax(0, 1.5fr)',
          gap: 'var(--space-5)',
          alignItems: 'flex-start',
        }}>
          {/* === Colonne gauche : Configuration === */}
          <div>
            {/* Scope */}
            <ScopeSelector
              scope={scope}
              setScope={setScope}
              availableModules={availableModules}
              availableChapitres={availableChapitres}
              availableThemes={availableThemes}
              loading={loadingScope}
            />

            {/* Stratégie */}
            <div style={{
              padding: 'var(--space-3)',
              background: 'var(--color-bg-subtle)',
              borderRadius: 'var(--radius-md)',
              marginBottom: 'var(--space-4)',
            }}>
              <div style={{
                fontSize: 'var(--text-sm)',
                fontWeight: 600,
                marginBottom: 'var(--space-2)',
              }}>⚙️ Stratégie de tirage</div>

              <div style={{ display: 'flex', gap: 8 }}>
                <button
                  onClick={() => setStrategy('custom')}
                  style={{
                    flex: 1,
                    padding: '10px 12px',
                    border: `2px solid ${strategy === 'custom' ? 'var(--color-primary)' : 'var(--color-border)'}`,
                    background: strategy === 'custom' ? 'var(--color-primary-subtle)' : 'var(--color-bg-elevated)',
                    borderRadius: 'var(--radius-md)',
                    cursor: 'pointer',
                    textAlign: 'left',
                    transition: 'all 0.15s',
                  }}
                >
                  <div style={{ fontWeight: 600, fontSize: 'var(--text-sm)', marginBottom: 2 }}>
                    🎯 Custom
                  </div>
                  <div style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>
                    Quotas manuels par niveau
                  </div>
                </button>
                <button
                  onClick={() => setStrategy('equitable')}
                  style={{
                    flex: 1,
                    padding: '10px 12px',
                    border: `2px solid ${strategy === 'equitable' ? 'var(--color-primary)' : 'var(--color-border)'}`,
                    background: strategy === 'equitable' ? 'var(--color-primary-subtle)' : 'var(--color-bg-elevated)',
                    borderRadius: 'var(--radius-md)',
                    cursor: 'pointer',
                    textAlign: 'left',
                    transition: 'all 0.15s',
                  }}
                >
                  <div style={{ fontWeight: 600, fontSize: 'var(--text-sm)', marginBottom: 2 }}>
                    ⚖️ Équitable
                  </div>
                  <div style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>
                    N réparti automatiquement
                  </div>
                </button>
              </div>
            </div>

            {/* Mode Custom : sliders */}
            {strategy === 'custom' && (
              <div style={{ marginBottom: 'var(--space-4)' }}>
                <div style={{
                  fontSize: 'var(--text-sm)',
                  fontWeight: 600,
                  marginBottom: 'var(--space-2)',
                  display: 'flex',
                  justifyContent: 'space-between',
                }}>
                  <span>📊 Quotas par niveau</span>
                  <span style={{ color: 'var(--color-primary)', fontSize: 'var(--text-lg)' }}>
                    Total : {totalQuotas}
                  </span>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-2)' }}>
                  {LEVELS.map(level => (
                    <QuotaSlider
                      key={level}
                      level={level}
                      value={quotas[level]}
                      max={levelCounts[level]}
                      available={levelCounts[level]}
                      onChange={v => setQuotas(q => ({ ...q, [level]: v }))}
                    />
                  ))}
                </div>
              </div>
            )}

            {/* Mode Équitable : N total */}
            {strategy === 'equitable' && (
              <div style={{
                padding: 'var(--space-3)',
                background: 'var(--color-bg-subtle)',
                borderRadius: 'var(--radius-md)',
                marginBottom: 'var(--space-4)',
              }}>
                <label style={{
                  fontSize: 'var(--text-sm)',
                  fontWeight: 600,
                  display: 'block',
                  marginBottom: 'var(--space-2)',
                }}>
                  📊 Nombre total de questions
                </label>
                <Input
                  type="number"
                  min={1}
                  max={100}
                  value={nEquitable}
                  onChange={e => setNEquitable(Math.max(1, Math.min(100, parseInt(e.target.value) || 1)))}
                />
                <div style={{ marginTop: 8, fontSize: 11, color: 'var(--color-text-muted)' }}>
                  Répartition auto : {Math.floor(nEquitable / 4)} par niveau (+ arrondis si %4 ≠ 0)
                </div>
              </div>
            )}

            {/* Seed */}
            <div style={{ marginBottom: 'var(--space-4)' }}>
              <label style={{
                fontSize: 'var(--text-sm)',
                fontWeight: 600,
                display: 'block',
                marginBottom: 4,
              }}>
                🎲 Seed (optionnel)
              </label>
              <Input
                type="number"
                value={seed}
                onChange={e => setSeed(e.target.value)}
                placeholder="Ex: 42 (pour reproductibilité)"
              />
              <div style={{ marginTop: 4, fontSize: 11, color: 'var(--color-text-muted)' }}>
                Même seed = même tirage. Vide = nouveau tirage à chaque fois.
              </div>
            </div>

            {/* Actions */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-2)' }}>
              <Button
                variant="primary"
                onClick={drawExam}
                disabled={drawing || (strategy === 'custom' && totalQuotas === 0)}
                style={{ width: '100%' }}
              >
                {drawing ? '⏳ Tirage en cours...' : '🎲 Générer l\'examen'}
              </Button>

              {drawnQuestions.length > 0 && (
                <Button variant="ghost" onClick={redraw} disabled={drawing} style={{ width: '100%' }}>
                  🔄 Retirer (nouveau shuffle)
                </Button>
              )}
            </div>
          </div>

          {/* === Colonne droite : Résultats === */}
          <div>
            {drawnQuestions.length === 0 ? (
              <div style={{
                padding: 'var(--space-6)',
                textAlign: 'center',
                background: 'var(--color-bg-subtle)',
                borderRadius: 'var(--radius-md)',
                color: 'var(--color-text-muted)',
              }}>
                <div style={{ fontSize: 48, marginBottom: 12, opacity: 0.3 }}>🎲</div>
                <h4 style={{ margin: '0 0 8px 0', color: 'var(--color-text)' }}>Aucun examen généré</h4>
                <p style={{ margin: 0 }}>Configurez votre tirage à gauche puis cliquez sur "Générer".</p>
              </div>
            ) : (
              <>
                {/* Stats du tirage */}
                <div style={{
                  padding: 'var(--space-3)',
                  background: 'var(--color-bg-subtle)',
                  borderRadius: 'var(--radius-md)',
                  marginBottom: 'var(--space-3)',
                }}>
                  <div style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    marginBottom: 8,
                  }}>
                    <div style={{ fontSize: 'var(--text-lg)', fontWeight: 700 }}>
                      ✅ {drawnQuestions.length} questions tirées
                    </div>
                    <div style={{ display: 'flex', gap: 4 }}>
                      <Button variant="ghost" size="sm" onClick={exportJSON}>📋 JSON</Button>
                      <Button variant="ghost" size="sm" onClick={exportCSV}>📊 CSV</Button>
                      <Button variant="ghost" size="sm" onClick={exportTXT}>🖨️ TXT</Button>
                    </div>
                  </div>

                  <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                    {LEVELS.map(level => (
                      drawStats[level] > 0 && (
                        <div key={level} style={{
                          padding: '4px 10px',
                          background: 'var(--color-bg-elevated)',
                          borderRadius: 999,
                          fontSize: 12,
                          display: 'flex',
                          alignItems: 'center',
                          gap: 6,
                        }}>
                          <span style={{
                            width: 8,
                            height: 8,
                            borderRadius: '50%',
                            background: LEVEL_COLORS[level],
                          }}></span>
                          <strong style={{ color: LEVEL_COLORS[level] }}>{drawStats[level]}</strong>
                          <span style={{ color: 'var(--color-text-muted)' }}>{LEVEL_LABELS[level]}</span>
                        </div>
                      )
                    ))}
                  </div>
                </div>

                {/* Liste des questions */}
                <div style={{ maxHeight: '70vh', overflow: 'auto', paddingRight: 4 }}>
                  {drawnQuestions.map((q, i) => (
                    <QuestionCard key={q.id + i} question={q} index={i} />
                  ))}
                </div>
              </>
            )}
          </div>
        </div>
      </div>
    );
  }

  // EXPORT
  root.BanqueGenerator = BanqueGenerator;

})(window);
