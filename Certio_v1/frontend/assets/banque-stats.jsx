/* ============================================================================
   banque-stats.jsx — Dashboard de statistiques de la banque

   Plateforme d'examens IPSSI — Phase P5.5 (DERNIER composant P5 !)

   Fonctionnalités :
     - KPIs : total questions, modules, chapitres, thèmes
     - Distribution par niveau (bar chart horizontal coloré)
     - Distribution par type (bar chart)
     - Répartition par chapitre (bar chart)
     - Top 10 tags (liste avec compteurs)
     - Health indicators : validation de chaque thème
     - Exports CSV / JSON complets

   Composant exporté : window.BanqueStats

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useMemo } = React;

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
  const TYPE_COLORS = {
    conceptuel: '#3b82f6',
    calcul: '#8b5cf6',
    code: '#ec4899',
    formule: '#14b8a6',
  };
  const LEVEL_ICONS = {
    facile: '🟢',
    moyen: '🟡',
    difficile: '🟠',
    expert: '🔴',
  };

  // ==========================================================================
  // Composant : KPI Card
  // ==========================================================================

  function KpiCard({ icon, value, label, color = 'var(--color-primary)', trend = null }) {
    return (
      <div style={{
        padding: 'var(--space-4)',
        background: 'var(--color-bg-elevated)',
        border: '1px solid var(--color-border)',
        borderRadius: 'var(--radius-md)',
        position: 'relative',
        overflow: 'hidden',
      }}>
        <div style={{
          position: 'absolute',
          top: -20,
          right: -20,
          width: 100,
          height: 100,
          borderRadius: '50%',
          background: `${color}15`,
          pointerEvents: 'none',
        }}></div>

        <div style={{ fontSize: 32, marginBottom: 4, filter: 'grayscale(0.2)' }}>
          {icon}
        </div>
        <div style={{
          fontSize: 'var(--text-3xl)',
          fontWeight: 800,
          color: color,
          lineHeight: 1,
          marginBottom: 6,
        }}>
          {value}
        </div>
        <div style={{
          fontSize: 'var(--text-sm)',
          color: 'var(--color-text-muted)',
          textTransform: 'uppercase',
          letterSpacing: 0.5,
          fontWeight: 600,
        }}>
          {label}
        </div>
        {trend && (
          <div style={{
            fontSize: 11,
            color: trend.up ? '#16a34a' : '#dc2626',
            marginTop: 4,
          }}>
            {trend.up ? '↑' : '↓'} {trend.value}
          </div>
        )}
      </div>
    );
  }

  // ==========================================================================
  // Composant : Bar horizontal avec label et valeur
  // ==========================================================================

  function HorizontalBar({ label, value, total, color, icon = null }) {
    const pct = total > 0 ? Math.round((value / total) * 100) : 0;

    return (
      <div style={{ marginBottom: 10 }}>
        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: 4,
        }}>
          <span style={{
            fontSize: 'var(--text-sm)',
            fontWeight: 500,
            color: 'var(--color-text)',
            display: 'flex',
            alignItems: 'center',
            gap: 6,
          }}>
            {icon}
            {label}
          </span>
          <span style={{
            fontSize: 'var(--text-sm)',
            fontWeight: 700,
            color: color,
          }}>
            <strong style={{ fontSize: 'var(--text-base)' }}>{value}</strong>
            <span style={{ color: 'var(--color-text-muted)', fontWeight: 400, fontSize: 11, marginLeft: 4 }}>
              ({pct}%)
            </span>
          </span>
        </div>
        <div style={{
          width: '100%',
          height: 10,
          background: 'var(--color-bg-subtle)',
          borderRadius: 5,
          overflow: 'hidden',
        }}>
          <div style={{
            width: `${pct}%`,
            height: '100%',
            background: `linear-gradient(90deg, ${color}, ${color}cc)`,
            borderRadius: 5,
            transition: 'width 0.5s ease',
          }}></div>
        </div>
      </div>
    );
  }

  // ==========================================================================
  // Composant : Donut chart CSS pur (pour distribution type)
  // ==========================================================================

  function DonutChart({ data, size = 180 }) {
    const total = data.reduce((sum, d) => sum + d.value, 0);
    if (total === 0) return null;

    let cumulativeAngle = 0;
    const radius = size / 2;
    const strokeWidth = 24;
    const normalizedRadius = radius - strokeWidth / 2;
    const circumference = normalizedRadius * 2 * Math.PI;

    return (
      <div style={{
        position: 'relative',
        width: size,
        height: size,
        margin: '0 auto',
      }}>
        <svg width={size} height={size} style={{ transform: 'rotate(-90deg)' }}>
          <circle
            cx={radius}
            cy={radius}
            r={normalizedRadius}
            fill="transparent"
            stroke="var(--color-bg-subtle)"
            strokeWidth={strokeWidth}
          />
          {data.map((d, i) => {
            const pct = d.value / total;
            const strokeDashoffset = circumference - (pct * circumference);
            const rotation = cumulativeAngle;
            cumulativeAngle += pct * 360;

            return (
              <circle
                key={d.label}
                cx={radius}
                cy={radius}
                r={normalizedRadius}
                fill="transparent"
                stroke={d.color}
                strokeWidth={strokeWidth}
                strokeDasharray={`${pct * circumference} ${circumference}`}
                strokeDashoffset={0}
                style={{
                  transform: `rotate(${rotation}deg)`,
                  transformOrigin: `${radius}px ${radius}px`,
                  transition: 'all 0.5s ease',
                }}
              />
            );
          })}
        </svg>
        <div style={{
          position: 'absolute',
          top: '50%',
          left: '50%',
          transform: 'translate(-50%, -50%)',
          textAlign: 'center',
        }}>
          <div style={{ fontSize: 'var(--text-2xl)', fontWeight: 800 }}>{total}</div>
          <div style={{
            fontSize: 'var(--text-xs)',
            color: 'var(--color-text-muted)',
            textTransform: 'uppercase',
            letterSpacing: 0.5,
          }}>Total</div>
        </div>
      </div>
    );
  }

  // ==========================================================================
  // Composant : Legend pour donut
  // ==========================================================================

  function DonutLegend({ data }) {
    const total = data.reduce((sum, d) => sum + d.value, 0);
    return (
      <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
        {data.map(d => (
          <div key={d.label} style={{
            display: 'flex',
            alignItems: 'center',
            gap: 10,
            padding: '8px 12px',
            background: 'var(--color-bg-subtle)',
            borderRadius: 6,
          }}>
            <div style={{
              width: 14,
              height: 14,
              borderRadius: 3,
              background: d.color,
              flexShrink: 0,
            }}></div>
            <div style={{ flex: 1 }}>
              <div style={{ fontSize: 'var(--text-sm)', fontWeight: 600 }}>{d.label}</div>
              <div style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>
                {d.value} ({total > 0 ? Math.round(d.value / total * 100) : 0}%)
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  // ==========================================================================
  // Composant : Card section
  // ==========================================================================

  function Section({ title, icon, children, actions = null }) {
    return (
      <div style={{
        padding: 'var(--space-4)',
        background: 'var(--color-bg-elevated)',
        border: '1px solid var(--color-border)',
        borderRadius: 'var(--radius-md)',
        marginBottom: 'var(--space-4)',
      }}>
        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: 'var(--space-3)',
          paddingBottom: 'var(--space-2)',
          borderBottom: '1px solid var(--color-border)',
        }}>
          <h4 style={{
            margin: 0,
            fontSize: 'var(--text-lg)',
            display: 'flex',
            alignItems: 'center',
            gap: 8,
          }}>
            {icon}
            {title}
          </h4>
          {actions}
        </div>
        {children}
      </div>
    );
  }

  // ==========================================================================
  // Composant principal : BanqueStats
  // ==========================================================================

  function BanqueStats() {
    const { Button, Box, Spinner, useToast } = root.UI;
    const { useApi } = root.UIHooks;
    const api = useApi();
    const { toast } = useToast();

    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [validationResults, setValidationResults] = useState({}); // {theme_path: {valid, errors, warnings}}
    const [validating, setValidating] = useState(false);
    const [allQuestions, setAllQuestions] = useState([]);

    // ==========================================================================
    // Chargement des stats
    // ==========================================================================

    async function loadStats() {
      setLoading(true);
      const res = await api.request('GET', '/api/banque/stats');
      if (res.ok) {
        setStats(res.data);
      } else {
        toast({ title: 'Erreur', message: 'Impossible de charger les stats', type: 'error' });
      }
      setLoading(false);
    }

    async function loadAllQuestions() {
      // Pour le top tags + calculs détaillés
      const res = await api.request('GET', '/api/banque/questions?limit=1000');
      if (res.ok) {
        setAllQuestions(res.data?.questions || []);
      }
    }

    useEffect(() => {
      loadStats();
      loadAllQuestions();
    }, []);

    // ==========================================================================
    // Validation de tous les thèmes
    // ==========================================================================

    async function validateAll() {
      if (!stats) return;
      setValidating(true);
      const results = {};

      for (const module of stats.modules) {
        for (const chap of module.chapitres) {
          for (const themeData of chap.themes) {
            const key = `${module.module}/${chap.chapitre}/${themeData.theme}`;
            const res = await api.request(
              'GET',
              `/api/banque/${module.module}/${chap.chapitre}/${themeData.theme}/validate`
            );
            if (res.ok) {
              results[key] = res.data?.report || { valid: false, errors: ['Unknown'], warnings: [] };
            }
          }
        }
      }

      setValidationResults(results);
      setValidating(false);
      toast({ title: 'Validation terminée', message: `${Object.keys(results).length} thèmes validés`, type: 'success' });
    }

    // ==========================================================================
    // Top tags (calculés depuis allQuestions)
    // ==========================================================================

    const topTags = useMemo(() => {
      const tagCounts = {};
      allQuestions.forEach(q => {
        (q.tags || []).forEach(tag => {
          tagCounts[tag] = (tagCounts[tag] || 0) + 1;
        });
      });
      return Object.entries(tagCounts)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 10);
    }, [allQuestions]);

    // ==========================================================================
    // Distribution par chapitre (pour le bar chart)
    // ==========================================================================

    const chapterDistribution = useMemo(() => {
      if (!stats) return [];
      const result = [];
      for (const module of stats.modules || []) {
        for (const chap of module.chapitres || []) {
          result.push({
            label: chap.chapitre,
            value: chap.total,
            color: 'var(--color-primary)',
          });
        }
      }
      return result.sort((a, b) => b.value - a.value);
    }, [stats]);

    // ==========================================================================
    // Thèmes avec status de validation
    // ==========================================================================

    const themesWithStatus = useMemo(() => {
      if (!stats) return [];
      const result = [];
      for (const module of stats.modules || []) {
        for (const chap of module.chapitres || []) {
          for (const themeData of chap.themes || []) {
            const key = `${module.module}/${chap.chapitre}/${themeData.theme}`;
            const report = validationResults[key];
            result.push({
              path: key,
              theme: themeData.theme,
              count: themeData.count,
              valid: report?.valid ?? null,
              errors: report?.errors?.length ?? 0,
              warnings: report?.warnings?.length ?? 0,
            });
          }
        }
      }
      return result;
    }, [stats, validationResults]);

    const validationSummary = useMemo(() => {
      const sum = { valid: 0, warnings: 0, errors: 0, unchecked: 0 };
      themesWithStatus.forEach(t => {
        if (t.valid === null) sum.unchecked++;
        else if (t.errors > 0) sum.errors++;
        else if (t.warnings > 0) sum.warnings++;
        else sum.valid++;
      });
      return sum;
    }, [themesWithStatus]);

    // ==========================================================================
    // Exports
    // ==========================================================================

    function download(filename, content, mime = 'text/plain') {
      const blob = new Blob([content], { type: mime + ';charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }

    function exportAllCSV() {
      if (allQuestions.length === 0) {
        toast({ title: 'Aucune donnée', message: 'Attendez le chargement', type: 'warning' });
        return;
      }
      const escape = (s) => `"${String(s || '').replace(/"/g, '""').replace(/\n/g, ' ')}"`;
      const headers = ['id', 'module', 'chapitre', 'theme', 'difficulte', 'type', 'enonce', 'optA', 'optB', 'optC', 'optD', 'correct', 'hint', 'explanation', 'traps', 'references', 'tags'];
      const rows = allQuestions.map(q => [
        q.id,
        q._module, q._chapitre, q._theme,
        q.difficulte, q.type,
        escape(q.enonce),
        escape(q.options[0]), escape(q.options[1]), escape(q.options[2]), escape(q.options[3]),
        ['A', 'B', 'C', 'D'][q.correct],
        escape(q.hint),
        escape(q.explanation),
        escape(q.traps),
        escape(q.references),
        (q.tags || []).join(';'),
      ]);
      const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
      const ts = new Date().toISOString().slice(0, 10);
      download(`banque_complete_${ts}.csv`, csv, 'text/csv');
      toast({ title: 'Export CSV', message: `${allQuestions.length} questions exportées`, type: 'success' });
    }

    function exportStatsJSON() {
      if (!stats) return;
      const payload = {
        meta: {
          generated_at: new Date().toISOString(),
          generator: 'IPSSI Banque Dashboard',
        },
        stats: stats,
        top_tags: topTags.map(([tag, count]) => ({ tag, count })),
        validation: validationSummary,
        themes: themesWithStatus,
      };
      const ts = new Date().toISOString().slice(0, 10);
      download(`banque_stats_${ts}.json`, JSON.stringify(payload, null, 2), 'application/json');
      toast({ title: 'Export JSON', message: 'Rapport téléchargé', type: 'success' });
    }

    // ==========================================================================
    // Render
    // ==========================================================================

    if (loading) {
      return (
        <div style={{ padding: 'var(--space-6)', textAlign: 'center' }}>
          <Spinner />
          <p style={{ marginTop: 'var(--space-3)', color: 'var(--color-text-muted)' }}>
            Chargement des statistiques...
          </p>
        </div>
      );
    }

    if (!stats) {
      return (
        <Box type="error">
          Impossible de charger les statistiques. Réessayez.
        </Box>
      );
    }

    // Construction des données pour les charts
    const totalQuestions = stats.total_questions || 0;
    const totalModules = (stats.modules || []).length;
    const totalChapitres = (stats.modules || []).reduce((n, m) => n + (m.chapitres || []).length, 0);
    const totalThemes = (stats.modules || []).reduce(
      (n, m) => n + (m.chapitres || []).reduce((nn, c) => nn + (c.themes || []).length, 0),
      0
    );

    const levelData = LEVELS.map(level => ({
      label: LEVEL_LABELS[level],
      value: stats.by_level?.[level] || 0,
      color: LEVEL_COLORS[level],
      icon: LEVEL_ICONS[level],
    }));

    const typeData = Object.entries(stats.by_type || {}).map(([type, value]) => ({
      label: type,
      value,
      color: TYPE_COLORS[type] || 'var(--color-text-muted)',
    }));

    return (
      <div>
        <h3 style={{ marginTop: 0, display: 'flex', alignItems: 'center', gap: 8 }}>
          📊 Dashboard statistiques
        </h3>
        <p style={{ color: 'var(--color-text-muted)', marginBottom: 'var(--space-4)' }}>
          Vue globale de la banque de questions — statistiques, distribution, santé.
        </p>

        {/* KPIs */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))',
          gap: 'var(--space-3)',
          marginBottom: 'var(--space-4)',
        }}>
          <KpiCard
            icon="❓"
            value={totalQuestions}
            label="Questions totales"
            color="#3b82f6"
          />
          <KpiCard
            icon="📚"
            value={totalModules}
            label="Modules"
            color="#8b5cf6"
          />
          <KpiCard
            icon="📖"
            value={totalChapitres}
            label="Chapitres"
            color="#ec4899"
          />
          <KpiCard
            icon="🎯"
            value={totalThemes}
            label="Thèmes"
            color="#14b8a6"
          />
        </div>

        {/* Actions bar */}
        <div style={{
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          marginBottom: 'var(--space-4)',
          display: 'flex',
          gap: 'var(--space-2)',
          flexWrap: 'wrap',
        }}>
          <Button variant="primary" size="sm" onClick={validateAll} disabled={validating}>
            {validating ? '⏳ Validation...' : '✔️ Valider tous les thèmes'}
          </Button>
          <Button variant="ghost" size="sm" onClick={loadStats}>
            🔄 Rafraîchir
          </Button>
          <div style={{ flex: 1 }}></div>
          <Button variant="secondary" size="sm" onClick={exportStatsJSON}>
            📋 Export rapport JSON
          </Button>
          <Button variant="secondary" size="sm" onClick={exportAllCSV}>
            📊 Export banque CSV
          </Button>
        </div>

        {/* Grid : Niveaux + Types */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'minmax(0, 1fr) minmax(0, 1fr)',
          gap: 'var(--space-4)',
          marginBottom: 'var(--space-4)',
        }}>
          {/* Distribution par niveau */}
          <Section title="Distribution par difficulté" icon="📊">
            {levelData.map(d => (
              <HorizontalBar
                key={d.label}
                label={d.label}
                value={d.value}
                total={totalQuestions}
                color={d.color}
                icon={d.icon}
              />
            ))}
          </Section>

          {/* Distribution par type (donut) */}
          <Section title="Distribution par type" icon="🎨">
            <div style={{
              display: 'grid',
              gridTemplateColumns: 'auto 1fr',
              gap: 'var(--space-3)',
              alignItems: 'center',
            }}>
              <DonutChart data={typeData} size={180} />
              <DonutLegend data={typeData} />
            </div>
          </Section>
        </div>

        {/* Répartition par chapitre */}
        <Section title="Répartition par chapitre" icon="📖">
          {chapterDistribution.map(d => (
            <HorizontalBar
              key={d.label}
              label={d.label}
              value={d.value}
              total={totalQuestions}
              color={d.color}
            />
          ))}
        </Section>

        {/* Grid : Top tags + Health */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'minmax(0, 1fr) minmax(0, 1.2fr)',
          gap: 'var(--space-4)',
          marginBottom: 'var(--space-4)',
        }}>
          {/* Top tags */}
          <Section title="Top 10 tags" icon="🔖">
            {topTags.length === 0 ? (
              <p style={{ color: 'var(--color-text-muted)', fontSize: 12 }}>
                {allQuestions.length === 0 ? '⏳ Chargement...' : 'Aucun tag'}
              </p>
            ) : (
              <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                {topTags.map(([tag, count], i) => {
                  const max = topTags[0][1];
                  const pct = max > 0 ? (count / max) * 100 : 0;
                  return (
                    <div key={tag} style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                      <span style={{
                        width: 24,
                        height: 24,
                        borderRadius: '50%',
                        background: i < 3 ? 'linear-gradient(135deg, #fbbf24, #f59e0b)' : 'var(--color-bg-subtle)',
                        color: i < 3 ? 'white' : 'var(--color-text-muted)',
                        display: 'inline-flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        fontSize: 11,
                        fontWeight: 700,
                        flexShrink: 0,
                      }}>{i + 1}</span>
                      <div style={{ flex: 1 }}>
                        <div style={{
                          display: 'flex',
                          justifyContent: 'space-between',
                          alignItems: 'center',
                          marginBottom: 3,
                        }}>
                          <code style={{ fontSize: 'var(--text-sm)', fontWeight: 600 }}>{tag}</code>
                          <span style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>
                            <strong style={{ color: 'var(--color-primary)' }}>{count}</strong> question{count > 1 ? 's' : ''}
                          </span>
                        </div>
                        <div style={{
                          height: 4,
                          background: 'var(--color-bg-subtle)',
                          borderRadius: 2,
                          overflow: 'hidden',
                        }}>
                          <div style={{
                            width: `${pct}%`,
                            height: '100%',
                            background: 'linear-gradient(90deg, var(--color-primary), #8b5cf6)',
                            borderRadius: 2,
                            transition: 'width 0.5s',
                          }}></div>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </Section>

          {/* Health indicators */}
          <Section
            title="Santé des thèmes"
            icon="🩺"
            actions={
              !validating && Object.keys(validationResults).length === 0 && (
                <Button variant="ghost" size="sm" onClick={validateAll}>
                  Lancer validation
                </Button>
              )
            }
          >
            {Object.keys(validationResults).length === 0 ? (
              <div style={{
                padding: 'var(--space-3)',
                textAlign: 'center',
                color: 'var(--color-text-muted)',
                fontSize: 'var(--text-sm)',
              }}>
                {validating ? '⏳ Validation en cours...' : '💡 Cliquez sur "Valider tous les thèmes" pour voir la santé.'}
              </div>
            ) : (
              <div>
                {/* Résumé */}
                <div style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(3, 1fr)',
                  gap: 8,
                  marginBottom: 'var(--space-3)',
                }}>
                  <div style={{
                    padding: 'var(--space-2)',
                    background: 'rgba(34, 197, 94, 0.1)',
                    borderRadius: 6,
                    textAlign: 'center',
                    border: '1px solid #16a34a',
                  }}>
                    <div style={{ fontSize: 'var(--text-xl)', fontWeight: 800, color: '#16a34a' }}>
                      {validationSummary.valid}
                    </div>
                    <div style={{ fontSize: 11, color: 'var(--color-text-muted)', fontWeight: 600 }}>
                      🟢 Validés
                    </div>
                  </div>
                  <div style={{
                    padding: 'var(--space-2)',
                    background: 'rgba(234, 179, 8, 0.1)',
                    borderRadius: 6,
                    textAlign: 'center',
                    border: '1px solid #ca8a04',
                  }}>
                    <div style={{ fontSize: 'var(--text-xl)', fontWeight: 800, color: '#ca8a04' }}>
                      {validationSummary.warnings}
                    </div>
                    <div style={{ fontSize: 11, color: 'var(--color-text-muted)', fontWeight: 600 }}>
                      🟡 Warnings
                    </div>
                  </div>
                  <div style={{
                    padding: 'var(--space-2)',
                    background: 'rgba(220, 38, 38, 0.1)',
                    borderRadius: 6,
                    textAlign: 'center',
                    border: '1px solid #dc2626',
                  }}>
                    <div style={{ fontSize: 'var(--text-xl)', fontWeight: 800, color: '#dc2626' }}>
                      {validationSummary.errors}
                    </div>
                    <div style={{ fontSize: 11, color: 'var(--color-text-muted)', fontWeight: 600 }}>
                      🔴 Erreurs
                    </div>
                  </div>
                </div>

                {/* Liste */}
                <div style={{ maxHeight: 300, overflow: 'auto' }}>
                  {themesWithStatus.map(t => {
                    let status, color, icon;
                    if (t.valid === null) { status = 'Non validé'; color = 'var(--color-text-muted)'; icon = '⚪'; }
                    else if (t.errors > 0) { status = `${t.errors} erreur(s)`; color = '#dc2626'; icon = '🔴'; }
                    else if (t.warnings > 0) { status = `${t.warnings} warning(s)`; color = '#ca8a04'; icon = '🟡'; }
                    else { status = 'Valide'; color = '#16a34a'; icon = '🟢'; }

                    return (
                      <div key={t.path} style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        padding: '6px 10px',
                        borderBottom: '1px solid var(--color-border)',
                        fontSize: 'var(--text-sm)',
                      }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                          <span>{icon}</span>
                          <code style={{ fontSize: 11 }}>{t.theme}</code>
                          <span style={{ color: 'var(--color-text-muted)', fontSize: 11 }}>
                            ({t.count}q)
                          </span>
                        </div>
                        <span style={{ color, fontSize: 11, fontWeight: 500 }}>{status}</span>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}
          </Section>
        </div>

        {/* Footer */}
        <div style={{
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          textAlign: 'center',
          fontSize: 11,
          color: 'var(--color-text-muted)',
        }}>
          📅 Dernière mise à jour : {new Date().toLocaleString('fr-FR')}
          {' · '}
          Banque IPSSI {totalQuestions} questions
          {' · '}
          © 2026 Mohamed EL AFRIT — CC BY-NC-SA 4.0
        </div>
      </div>
    );
  }

  // EXPORT
  root.BanqueStats = BanqueStats;

})(window);
