/* ============================================================================
   analytics-student.jsx — Vue détaillée d'un étudiant

   Features :
     - 6 KPI cards (passages / moyenne / meilleur / pire / temps / anomalies)
     - Graphique evolution scores (LineChart)
     - Timeline detaillee des passages
     - Table comparative score vs moyenne examen
     - Badge "Position X/Y" dans chaque examen
     - Bouton voir correction
     - Support prof filtré (banniere info)

   Composant exporte : window.AnalyticsStudent

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useMemo } = React;

  function useRecharts() {
    const [rc, setRc] = useState(window.Recharts || null);
    useEffect(() => {
      if (rc) return;
      const iv = setInterval(() => {
        if (window.Recharts) {
          setRc(window.Recharts);
          clearInterval(iv);
        }
      }, 100);
      return () => clearInterval(iv);
    }, [rc]);
    return rc;
  }

  function formatDate(iso, withTime = true) {
    if (!iso) return '—';
    try {
      const opts = {
        day: '2-digit', month: '2-digit', year: '2-digit',
      };
      if (withTime) {
        opts.hour = '2-digit';
        opts.minute = '2-digit';
      }
      return new Date(iso).toLocaleString('fr-FR', opts);
    } catch { return iso; }
  }

  function formatDuration(sec) {
    if (!sec || sec < 0) return '—';
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;
    if (h > 0) return `${h}h ${String(m).padStart(2, '0')}min`;
    if (m > 0) return `${m}min ${String(s).padStart(2, '0')}s`;
    return `${s}s`;
  }

  function scoreColor(pct) {
    if (pct === null || pct === undefined) return '#64748b';
    if (pct >= 80) return '#16a34a';
    if (pct >= 60) return '#22c55e';
    if (pct >= 40) return '#d97706';
    return '#dc2626';
  }

  function scorePillClass(pct) {
    if (pct === null || pct === undefined) return '';
    if (pct >= 80) return 'excellent';
    if (pct >= 60) return 'good';
    if (pct >= 40) return 'medium';
    return 'bad';
  }

  const STATUS_LABELS = {
    'in_progress': 'En cours',
    'submitted': 'Soumis',
    'expired': 'Expiré',
    'invalidated': 'Invalidé',
  };

  // ==========================================================================
  // Graphique evolution scores
  // ==========================================================================

  function EvolutionChart({ passages }) {
    const Rc = useRecharts();

    const chartData = useMemo(() => {
      if (!passages || passages.length === 0) return [];
      // Tri chronologique asc
      const sorted = [...passages].sort((a, b) =>
        strcmp(a.start_time, b.start_time)
      );
      return sorted.map((p, i) => ({
        index: i + 1,
        date: p.start_time,
        dateShort: formatDate(p.start_time, false),
        score_pct: p.score_pct,
        examen: p.examen_titre,
      }));
    }, [passages]);

    if (!Rc || chartData.length === 0) return null;
    if (chartData.length < 2) return null; // Besoin d'au moins 2 points

    const { LineChart, Line, XAxis, YAxis, Tooltip, ResponsiveContainer,
            CartesianGrid, ReferenceLine, Dot } = Rc;

    const avg = chartData.reduce((s, p) => s + (p.score_pct || 0), 0) / chartData.length;

    // Custom dot avec couleur selon score
    const CustomDot = (props) => {
      const { cx, cy, payload } = props;
      return (
        <circle
          cx={cx} cy={cy} r={5}
          fill={scoreColor(payload.score_pct)}
          stroke="#fff"
          strokeWidth={2}
        />
      );
    };

    return (
      <div style={{
        background: 'var(--color-bg-elevated)',
        border: '1px solid var(--color-border)',
        borderRadius: 'var(--radius-md)',
        padding: 'var(--space-4)',
        marginBottom: 'var(--space-3)',
      }}>
        <h3 style={{ margin: '0 0 var(--space-3) 0', fontSize: 'var(--text-lg)' }}>
          📈 Évolution des scores
        </h3>
        <ResponsiveContainer width="100%" height={260}>
          <LineChart data={chartData} margin={{ top: 10, right: 30, left: 0, bottom: 40 }}>
            <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
            <XAxis
              dataKey="dateShort"
              tick={{ fontSize: 10 }}
              angle={-30}
              textAnchor="end"
              height={50}
            />
            <YAxis
              domain={[0, 100]}
              tick={{ fontSize: 11 }}
              tickFormatter={(v) => `${v}%`}
              label={{ value: 'Score', angle: -90, position: 'insideLeft', fontSize: 12 }}
            />
            <ReferenceLine
              y={avg}
              stroke="#8b5cf6"
              strokeDasharray="3 3"
              label={{ value: `Moy ${avg.toFixed(1)}%`, position: 'right', fontSize: 10, fill: '#8b5cf6' }}
            />
            <Tooltip
              contentStyle={{ fontSize: 12, borderRadius: 6 }}
              formatter={(value, name, props) => {
                if (name === 'score_pct') return [`${value}%`, props.payload.examen || 'Score'];
                return [value, name];
              }}
              labelFormatter={(label) => `📅 ${label}`}
            />
            <Line
              type="monotone"
              dataKey="score_pct"
              stroke="#3b82f6"
              strokeWidth={2}
              dot={<CustomDot />}
              activeDot={{ r: 7 }}
              name="Score"
            />
          </LineChart>
        </ResponsiveContainer>
      </div>
    );
  }

  function strcmp(a, b) {
    if (a < b) return -1;
    if (a > b) return 1;
    return 0;
  }

  // ==========================================================================
  // Composant principal AnalyticsStudent
  // ==========================================================================

  function AnalyticsStudent({ email, onBack }) {
    const { Button, Spinner, Box } = root.UI;
    const { useApi } = root.UIHooks;
    const api = useApi();

    const [loading, setLoading] = useState(true);
    const [data, setData] = useState(null);
    const [error, setError] = useState(null);

    useEffect(() => {
      (async () => {
        setLoading(true);
        const res = await api.request('GET', `/api/analytics/student/${encodeURIComponent(email)}`);
        setLoading(false);
        if (res.ok) {
          setData(res.data);
        } else {
          setError(res.error?.message || 'Erreur chargement');
        }
      })();
    }, [email]);

    function handleViewCorrection(passage) {
      // Ouvrir la correction dans un nouvel onglet (via passage token si disponible)
      // Note : ici on n'a que passage_id, il faudrait API pour recuperer token
      // → route alternative : /api/corrections/passage/{id} pour prof
      alert(`Correction du passage ${passage.passage_id}\nFonctionnalité à connecter en P7.6`);
    }

    if (loading) {
      return (
        <div>
          <Button variant="ghost" onClick={onBack}>← Retour</Button>
          <div className="loading-wrap"><Spinner /></div>
        </div>
      );
    }

    if (error) {
      return (
        <div>
          <Button variant="ghost" onClick={onBack}>← Retour</Button>
          <Box type="error" style={{ marginTop: 'var(--space-3)' }}>⚠️ {error}</Box>
        </div>
      );
    }

    if (!data) return null;

    const totalAnomalies = (data.passages || []).reduce(
      (sum, p) => sum + (p.anomalies_count || 0), 0
    );
    const hasMultiplePassages = data.passages && data.passages.length >= 2;

    return (
      <div>
        {/* Header */}
        <Button variant="ghost" onClick={onBack}>← Retour au dashboard</Button>

        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'flex-start',
          marginTop: 'var(--space-3)',
          gap: 16,
        }}>
          <div>
            <h2 style={{ margin: 0, fontSize: 'var(--text-2xl)' }}>
              👤 {data.student_info.prenom} {data.student_info.nom}
            </h2>
            <p style={{
              margin: '4px 0 0 0',
              color: 'var(--color-text-muted)',
              fontSize: 'var(--text-sm)',
            }}>
              📧 {data.email}
            </p>
          </div>
        </div>

        {/* Banner si vue prof filtrée */}
        {data.filtered_to_prof && (
          <div style={{
            marginTop: 'var(--space-3)',
            padding: 'var(--space-3)',
            background: 'rgba(59, 130, 246, 0.08)',
            borderLeft: '4px solid #3b82f6',
            borderRadius: 'var(--radius-md)',
            fontSize: 13,
          }}>
            ℹ️ <strong>Vue filtrée</strong> : vous ne voyez que les passages que cet étudiant a
            effectués sur <strong>vos examens</strong>. L'étudiant peut avoir d'autres passages
            ailleurs qui ne vous sont pas accessibles.
          </div>
        )}

        {/* 6 KPI cards */}
        <div className="kpi-grid" style={{ marginTop: 'var(--space-4)' }}>
          <div className="kpi-card">
            <div className="kpi-icon">📝</div>
            <div className="kpi-content">
              <div className="kpi-label">Passages</div>
              <div className="kpi-value accent-blue">{data.nb_passages}</div>
              <div className="kpi-sub">{
                (data.passages || []).filter(p => p.status === 'submitted').length
              } soumis</div>
            </div>
          </div>

          <div className="kpi-card">
            <div className="kpi-icon">🎯</div>
            <div className="kpi-content">
              <div className="kpi-label">Moyenne</div>
              <div className="kpi-value" style={{ color: scoreColor(data.avg_score_pct) }}>
                {(data.avg_score_pct || 0).toFixed(1)}%
              </div>
              <div className="kpi-sub">Tous examens confondus</div>
            </div>
          </div>

          <div className="kpi-card">
            <div className="kpi-icon">🏆</div>
            <div className="kpi-content">
              <div className="kpi-label">Meilleur score</div>
              <div className="kpi-value accent-green">
                {(data.best_score_pct || 0).toFixed(1)}%
              </div>
            </div>
          </div>

          <div className="kpi-card">
            <div className="kpi-icon">📉</div>
            <div className="kpi-content">
              <div className="kpi-label">Pire score</div>
              <div className="kpi-value accent-orange">
                {(data.worst_score_pct || 0).toFixed(1)}%
              </div>
            </div>
          </div>

          <div className="kpi-card">
            <div className="kpi-icon">⏱️</div>
            <div className="kpi-content">
              <div className="kpi-label">Temps total</div>
              <div className="kpi-value">
                {formatDuration(data.total_time_sec)}
              </div>
            </div>
          </div>

          <div className="kpi-card">
            <div className="kpi-icon">🔒</div>
            <div className="kpi-content">
              <div className="kpi-label">Anomalies</div>
              <div className={`kpi-value ${totalAnomalies > 0 ? 'accent-red' : 'accent-green'}`}>
                {totalAnomalies}
              </div>
              <div className="kpi-sub">copy/paste/devtools</div>
            </div>
          </div>
        </div>

        {/* Graph evolution (seulement si >=2 passages) */}
        {hasMultiplePassages && (
          <EvolutionChart passages={data.passages} />
        )}

        {/* Historique detaille */}
        <h3 style={{
          margin: 'var(--space-4) 0 var(--space-3) 0',
          fontSize: 'var(--text-lg)',
        }}>
          📋 Historique détaillé ({data.nb_passages})
        </h3>

        {data.passages.length === 0 ? (
          <div className="empty-state">
            <div className="empty-state-icon">📭</div>
            <h3 style={{ margin: 0 }}>Aucun passage</h3>
            <p>Cet étudiant n'a pas encore passé d'examen.</p>
          </div>
        ) : (
          <div className="passage-table-wrap">
            <table className="passage-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Examen</th>
                  <th>Score</th>
                  <th>Durée</th>
                  <th>Status</th>
                  <th>Anomalies</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {data.passages.map(p => {
                  const status = STATUS_LABELS[p.status] || p.status;

                  return (
                    <tr key={p.passage_id}>
                      <td data-label="Date">
                        <div style={{ fontSize: 12 }}>{formatDate(p.start_time)}</div>
                      </td>
                      <td data-label="Examen">
                        <div style={{ fontWeight: 600 }}>{p.examen_titre}</div>
                        <code style={{
                          fontSize: 10,
                          color: 'var(--color-text-muted)',
                        }}>{p.examen_id}</code>
                      </td>
                      <td data-label="Score">
                        {p.score_pct !== null ? (
                          <span className={`score-pill ${scorePillClass(p.score_pct)}`}>
                            {p.score_brut}/{p.score_max} ({p.score_pct.toFixed(1)}%)
                          </span>
                        ) : '—'}
                      </td>
                      <td data-label="Durée">
                        {formatDuration(p.duration_sec)}
                      </td>
                      <td data-label="Status">
                        <span style={{
                          fontSize: 12,
                          fontWeight: 600,
                          color: p.status === 'submitted' ? '#16a34a' :
                                 p.status === 'expired' ? '#d97706' :
                                 p.status === 'invalidated' ? '#dc2626' : '#3b82f6',
                        }}>
                          ● {status}
                        </span>
                      </td>
                      <td data-label="Anomalies">
                        {p.anomalies_count > 0 ? (
                          <span className="anomaly-badge">⚠️ {p.anomalies_count}</span>
                        ) : (
                          <span style={{ color: 'var(--color-text-muted)', fontSize: 12 }}>—</span>
                        )}
                      </td>
                      <td data-label="Actions">
                        <Button
                          variant="ghost"
                          onClick={() => handleViewCorrection(p)}
                          style={{ fontSize: 11, padding: '4px 8px' }}
                        >
                          🔍 Voir
                        </Button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}

        {/* Info diagnostique */}
        <div style={{
          marginTop: 'var(--space-4)',
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          fontSize: 12,
          color: 'var(--color-text-muted)',
        }}>
          💡 <strong>Aide à la décision</strong> :
          {' '}
          {hasMultiplePassages && data.best_score_pct - data.worst_score_pct > 30 && (
            <span>Variance importante entre passages — compétences à stabiliser. </span>
          )}
          {totalAnomalies > 0 && (
            <span style={{ color: '#d97706' }}>
              ⚠️ {totalAnomalies} anomalie{totalAnomalies > 1 ? 's' : ''} détectée{totalAnomalies > 1 ? 's' : ''} — à surveiller.
            </span>
          )}
          {data.avg_score_pct >= 80 && (
            <span style={{ color: '#16a34a' }}>
              ⭐ Excellent élève, moyenne supérieure à 80%.
            </span>
          )}
          {data.avg_score_pct < 50 && (
            <span style={{ color: '#dc2626' }}>
              📚 Moyenne faible ({data.avg_score_pct.toFixed(1)}%), accompagnement recommandé.
            </span>
          )}
        </div>
      </div>
    );
  }

  root.AnalyticsStudent = AnalyticsStudent;

})(window);
