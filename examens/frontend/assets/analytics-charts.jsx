/* ============================================================================
   analytics-charts.jsx — Graphiques Recharts

   5 composants de graphiques exportes :
     - ScoreDistributionChart : BarChart histogramme 10 buckets
     - MentionsChart          : PieChart (donut) 7 mentions
     - QuestionSuccessChart   : BarChart horizontal taux par Q
     - DistractorsChart       : BarChart distracteurs d'une Q
     - TimelineChart          : LineChart passages par heure

   Dependencies : React + Recharts (CDN via window.Recharts)

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useMemo } = React;

  // Wait for Recharts to be available
  function useRecharts() {
    const [rc, setRc] = useState(window.Recharts || null);
    useEffect(() => {
      if (rc) return;
      let cancelled = false;
      const iv = setInterval(() => {
        if (window.Recharts && !cancelled) {
          setRc(window.Recharts);
          clearInterval(iv);
        }
      }, 100);
      return () => { cancelled = true; clearInterval(iv); };
    }, [rc]);
    return rc;
  }

  // Loading fallback
  function ChartLoading({ label }) {
    return (
      <div style={{
        height: 300,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        color: 'var(--color-text-muted)',
        fontSize: 13,
      }}>
        ⏳ Chargement du graphique {label || ''}...
      </div>
    );
  }

  // Wrapper card pour chaque graphique
  function ChartCard({ title, subtitle, children, actions }) {
    return (
      <div style={{
        background: 'var(--color-bg-elevated)',
        border: '1px solid var(--color-border)',
        borderRadius: 'var(--radius-md)',
        padding: 'var(--space-4)',
        marginBottom: 'var(--space-3)',
      }}>
        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'flex-start',
          marginBottom: 'var(--space-3)',
        }}>
          <div>
            <h3 style={{
              margin: 0,
              fontSize: 'var(--text-lg)',
              fontWeight: 700,
            }}>{title}</h3>
            {subtitle && (
              <p style={{
                margin: '4px 0 0 0',
                fontSize: 12,
                color: 'var(--color-text-muted)',
              }}>{subtitle}</p>
            )}
          </div>
          {actions}
        </div>
        {children}
      </div>
    );
  }

  // Couleurs buckets scores (0 = rouge, 90+ = vert)
  const BUCKET_COLORS = [
    '#991b1b', '#b91c1c', '#dc2626', '#ea580c', '#d97706',
    '#ca8a04', '#65a30d', '#16a34a', '#15803d', '#166534',
  ];

  const MENTION_COLORS = {
    excellent: '#d97706',
    tres_bien: '#16a34a',
    bien: '#22c55e',
    assez_bien: '#3b82f6',
    passable: '#6366f1',
    insuffisant: '#d97706',
    tres_insuf: '#dc2626',
  };

  const MENTION_LABELS = {
    excellent: 'Excellent (≥90%)',
    tres_bien: 'Très bien (80-89%)',
    bien: 'Bien (70-79%)',
    assez_bien: 'Assez bien (60-69%)',
    passable: 'Passable (50-59%)',
    insuffisant: 'Insuffisant (30-49%)',
    tres_insuf: 'Très insuffisant (<30%)',
  };

  // ==========================================================================
  // 1. SCORE DISTRIBUTION (BarChart vertical)
  // ==========================================================================

  function ScoreDistributionChart({ examenId }) {
    const Rc = useRecharts();
    const { useApi } = root.UIHooks;
    const api = useApi();

    const [loading, setLoading] = useState(true);
    const [data, setData] = useState(null);

    useEffect(() => {
      (async () => {
        setLoading(true);
        const res = await api.request('GET', `/api/analytics/examen/${examenId}/scores`);
        setLoading(false);
        if (res.ok) setData(res.data);
      })();
    }, [examenId]);

    const chartData = useMemo(() => {
      if (!data) return [];
      return data.histogram.map((b, i) => ({
        range: b.range,
        count: b.count,
        fill: BUCKET_COLORS[i],
      }));
    }, [data]);

    if (!Rc) return <ChartCard title="📊 Distribution des scores"><ChartLoading /></ChartCard>;
    if (loading) return <ChartCard title="📊 Distribution des scores"><ChartLoading /></ChartCard>;
    if (!data || data.total === 0) {
      return (
        <ChartCard title="📊 Distribution des scores">
          <div className="empty-state">
            <div className="empty-state-icon">📊</div>
            <p>Pas encore de passages soumis.</p>
          </div>
        </ChartCard>
      );
    }

    const { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell, LabelList } = Rc;

    return (
      <ChartCard
        title="📊 Distribution des scores"
        subtitle={`${data.total} passages · Taux de réussite (≥50%) : ${data.pass_rate_pct}%`}
      >
        <ResponsiveContainer width="100%" height={320}>
          <BarChart data={chartData} margin={{ top: 20, right: 20, left: 0, bottom: 20 }}>
            <XAxis
              dataKey="range"
              tick={{ fontSize: 11 }}
              label={{ value: 'Score (%)', position: 'insideBottom', offset: -10, fontSize: 12 }}
            />
            <YAxis
              allowDecimals={false}
              tick={{ fontSize: 11 }}
              label={{ value: 'Nombre de passages', angle: -90, position: 'insideLeft', fontSize: 12 }}
            />
            <Tooltip
              contentStyle={{ fontSize: 12, borderRadius: 6 }}
              formatter={(value) => [`${value} passages`, 'Count']}
            />
            <Bar dataKey="count" radius={[6, 6, 0, 0]}>
              {chartData.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={entry.fill} />
              ))}
              <LabelList dataKey="count" position="top" fontSize={11} />
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </ChartCard>
    );
  }

  // ==========================================================================
  // 2. MENTIONS (PieChart/Donut)
  // ==========================================================================

  function MentionsChart({ examenId }) {
    const Rc = useRecharts();
    const { useApi } = root.UIHooks;
    const api = useApi();

    const [loading, setLoading] = useState(true);
    const [data, setData] = useState(null);

    useEffect(() => {
      (async () => {
        setLoading(true);
        const res = await api.request('GET', `/api/analytics/examen/${examenId}/scores`);
        setLoading(false);
        if (res.ok) setData(res.data);
      })();
    }, [examenId]);

    const pieData = useMemo(() => {
      if (!data) return [];
      return Object.entries(data.mentions)
        .filter(([, count]) => count > 0)
        .map(([key, count]) => ({
          name: MENTION_LABELS[key],
          value: count,
          color: MENTION_COLORS[key],
          key,
        }));
    }, [data]);

    if (!Rc) return <ChartCard title="🏆 Mentions"><ChartLoading /></ChartCard>;
    if (loading) return <ChartCard title="🏆 Mentions"><ChartLoading /></ChartCard>;
    if (!data || pieData.length === 0) {
      return (
        <ChartCard title="🏆 Mentions">
          <div className="empty-state">
            <div className="empty-state-icon">🏆</div>
            <p>Aucune mention à afficher.</p>
          </div>
        </ChartCard>
      );
    }

    const { PieChart, Pie, Cell, Tooltip, Legend, ResponsiveContainer } = Rc;

    const renderLabel = ({ cx, cy, midAngle, outerRadius, value, percent }) => {
      if (percent < 0.05) return null; // Hide labels <5%
      const RADIAN = Math.PI / 180;
      const radius = outerRadius + 20;
      const x = cx + radius * Math.cos(-midAngle * RADIAN);
      const y = cy + radius * Math.sin(-midAngle * RADIAN);
      return (
        <text x={x} y={y} fill="#1a1a2e" textAnchor={x > cx ? 'start' : 'end'}
              dominantBaseline="central" fontSize={11} fontWeight={600}>
          {value} ({(percent * 100).toFixed(0)}%)
        </text>
      );
    };

    return (
      <ChartCard
        title="🏆 Répartition par mention"
        subtitle={`${data.total} passages · ${pieData.length} mention${pieData.length > 1 ? 's' : ''}`}
      >
        <ResponsiveContainer width="100%" height={320}>
          <PieChart>
            <Pie
              data={pieData}
              cx="50%"
              cy="50%"
              innerRadius={60}
              outerRadius={100}
              paddingAngle={2}
              dataKey="value"
              label={renderLabel}
              labelLine={false}
            >
              {pieData.map((entry, i) => (
                <Cell key={i} fill={entry.color} />
              ))}
            </Pie>
            <Tooltip
              contentStyle={{ fontSize: 12, borderRadius: 6 }}
              formatter={(value, name) => [`${value} passages`, name]}
            />
            <Legend
              verticalAlign="bottom"
              height={40}
              wrapperStyle={{ fontSize: 11 }}
              iconSize={10}
            />
          </PieChart>
        </ResponsiveContainer>
      </ChartCard>
    );
  }

  // ==========================================================================
  // 3. QUESTION SUCCESS (BarChart horizontal)
  // ==========================================================================

  function QuestionSuccessChart({ examenId, onQuestionClick }) {
    const Rc = useRecharts();
    const { useApi } = root.UIHooks;
    const api = useApi();

    const [loading, setLoading] = useState(true);
    const [data, setData] = useState(null);

    useEffect(() => {
      (async () => {
        setLoading(true);
        const res = await api.request('GET', `/api/analytics/examen/${examenId}/questions?with_details=false`);
        setLoading(false);
        if (res.ok) setData(res.data);
      })();
    }, [examenId]);

    const chartData = useMemo(() => {
      if (!data) return [];
      // Tri par success_rate croissant (plus difficiles en haut)
      return data.questions.map((q, i) => ({
        id: q.question_id,
        label: `Q${i + 1}`, // Short label
        fullLabel: q.question_id,
        success_rate: q.success_rate_pct,
        total: q.total,
        correct: q.correct,
        not_answered: q.not_answered,
        difficulte: q.difficulte,
        fill: q.success_rate_pct >= 75 ? '#16a34a'
           : q.success_rate_pct >= 50 ? '#d97706'
           : '#dc2626',
      }));
    }, [data]);

    if (!Rc) return <ChartCard title="❓ Taux de réussite par question"><ChartLoading /></ChartCard>;
    if (loading) return <ChartCard title="❓ Taux de réussite par question"><ChartLoading /></ChartCard>;
    if (!data || chartData.length === 0) {
      return (
        <ChartCard title="❓ Taux de réussite par question">
          <div className="empty-state">
            <div className="empty-state-icon">❓</div>
            <p>Pas encore de données.</p>
          </div>
        </ChartCard>
      );
    }

    const { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell, LabelList } = Rc;

    // Hauteur dynamique : 30px par barre
    const chartHeight = Math.max(250, chartData.length * 32 + 80);

    const CustomTooltip = ({ active, payload }) => {
      if (!active || !payload || !payload[0]) return null;
      const q = payload[0].payload;
      return (
        <div style={{
          background: '#fff',
          border: '1px solid #ccc',
          borderRadius: 6,
          padding: 10,
          fontSize: 12,
        }}>
          <div style={{ fontWeight: 700 }}>{q.fullLabel}</div>
          <div>Taux : <strong>{q.success_rate}%</strong></div>
          <div style={{ color: '#16a34a' }}>✓ {q.correct}/{q.total} corrects</div>
          {q.not_answered > 0 && (
            <div style={{ color: '#d97706' }}>⏭️ {q.not_answered} non répondue{q.not_answered > 1 ? 's' : ''}</div>
          )}
          <div style={{ color: '#64748b', fontSize: 11 }}>
            Difficulté : {q.difficulte}
          </div>
        </div>
      );
    };

    return (
      <ChartCard
        title="❓ Taux de réussite par question"
        subtitle={`${data.nb_questions} questions · ${data.nb_passages} passages · Triées par difficulté`}
      >
        <ResponsiveContainer width="100%" height={chartHeight}>
          <BarChart
            data={chartData}
            layout="vertical"
            margin={{ top: 10, right: 50, left: 40, bottom: 20 }}
          >
            <XAxis
              type="number"
              domain={[0, 100]}
              tickFormatter={(v) => `${v}%`}
              tick={{ fontSize: 11 }}
            />
            <YAxis
              type="category"
              dataKey="label"
              tick={{ fontSize: 11 }}
              width={40}
            />
            <Tooltip content={<CustomTooltip />} />
            <Bar
              dataKey="success_rate"
              radius={[0, 6, 6, 0]}
              onClick={(bar) => onQuestionClick && onQuestionClick(bar.id)}
              style={{ cursor: onQuestionClick ? 'pointer' : 'default' }}
            >
              {chartData.map((entry, i) => (
                <Cell key={i} fill={entry.fill} />
              ))}
              <LabelList
                dataKey="success_rate"
                position="right"
                formatter={(v) => `${v}%`}
                fontSize={10}
              />
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </ChartCard>
    );
  }

  // ==========================================================================
  // 4. DISTRACTORS (BarChart pour une Q selectionnee)
  // ==========================================================================

  function DistractorsChart({ examenId, questionId }) {
    const Rc = useRecharts();
    const { useApi } = root.UIHooks;
    const api = useApi();

    const [loading, setLoading] = useState(true);
    const [data, setData] = useState(null);

    useEffect(() => {
      (async () => {
        setLoading(true);
        const res = await api.request('GET', `/api/analytics/examen/${examenId}/questions?with_details=true`);
        setLoading(false);
        if (res.ok) setData(res.data);
      })();
    }, [examenId]);

    const question = useMemo(() => {
      if (!data || !questionId) return null;
      return data.questions.find(q => q.question_id === questionId);
    }, [data, questionId]);

    if (!Rc) return <ChartCard title="🎯 Analyse des distracteurs"><ChartLoading /></ChartCard>;
    if (loading) return <ChartCard title="🎯 Analyse des distracteurs"><ChartLoading /></ChartCard>;
    if (!question) {
      return (
        <ChartCard title="🎯 Analyse des distracteurs">
          <div className="empty-state">
            <div className="empty-state-icon">🎯</div>
            <p>Sélectionnez une question dans le graphique précédent pour voir l'analyse des distracteurs.</p>
          </div>
        </ChartCard>
      );
    }

    const chartData = question.option_analysis.map(opt => ({
      letter: opt.letter,
      count: opt.count,
      rate: opt.rate_pct,
      is_correct: opt.is_correct,
      fill: opt.is_correct ? '#16a34a' : '#dc2626',
      text: opt.text ? (opt.text.length > 60 ? opt.text.slice(0, 60) + '...' : opt.text) : '',
    }));

    const { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell, LabelList } = Rc;

    return (
      <ChartCard
        title="🎯 Analyse des distracteurs"
        subtitle={`Question ${question.question_id} · ${question.total} passages · Taux de réussite : ${question.success_rate_pct}%`}
      >
        {question.enonce && (
          <div style={{
            padding: 'var(--space-3)',
            background: 'var(--color-bg-subtle)',
            borderRadius: 'var(--radius-md)',
            marginBottom: 'var(--space-3)',
            fontSize: 13,
          }}>
            <div style={{ fontSize: 11, color: 'var(--color-text-muted)', marginBottom: 4 }}>ÉNONCÉ</div>
            {question.enonce}
          </div>
        )}

        <ResponsiveContainer width="100%" height={280}>
          <BarChart data={chartData} margin={{ top: 20, right: 20, left: 0, bottom: 40 }}>
            <XAxis
              dataKey="letter"
              tick={{ fontSize: 14, fontWeight: 700 }}
            />
            <YAxis
              allowDecimals={false}
              tick={{ fontSize: 11 }}
              label={{ value: 'Choix', angle: -90, position: 'insideLeft', fontSize: 12 }}
            />
            <Tooltip
              contentStyle={{ fontSize: 12, borderRadius: 6, maxWidth: 350 }}
              formatter={(value, name, props) => {
                const opt = props.payload;
                return [
                  `${value} choix (${opt.rate}%)${opt.is_correct ? ' ✓ Bonne réponse' : ''}`,
                  `Option ${opt.letter}`,
                ];
              }}
              labelFormatter={() => ''}
            />
            <Bar dataKey="count" radius={[6, 6, 0, 0]}>
              {chartData.map((entry, i) => (
                <Cell key={i} fill={entry.fill} />
              ))}
              <LabelList
                dataKey="rate"
                position="top"
                formatter={(v) => `${v}%`}
                fontSize={11}
                fontWeight={600}
              />
            </Bar>
          </BarChart>
        </ResponsiveContainer>

        {/* Légende textuelle des options */}
        {chartData.some(o => o.text) && (
          <div style={{
            marginTop: 'var(--space-3)',
            padding: 'var(--space-3)',
            background: 'var(--color-bg-subtle)',
            borderRadius: 'var(--radius-md)',
            fontSize: 12,
          }}>
            {chartData.map(o => (
              <div key={o.letter} style={{
                display: 'flex',
                gap: 8,
                alignItems: 'flex-start',
                padding: '4px 0',
                borderBottom: '1px dashed var(--color-border)',
              }}>
                <strong style={{
                  color: o.is_correct ? '#16a34a' : '#dc2626',
                  minWidth: 40,
                }}>
                  {o.is_correct ? '✓' : '✗'} {o.letter}
                </strong>
                <span style={{ flex: 1, color: 'var(--color-text)' }}>{o.text}</span>
                <span style={{
                  color: 'var(--color-text-muted)',
                  fontWeight: 600,
                  minWidth: 50,
                  textAlign: 'right',
                }}>
                  {o.count} · {o.rate}%
                </span>
              </div>
            ))}
          </div>
        )}
      </ChartCard>
    );
  }

  // ==========================================================================
  // 5. TIMELINE (LineChart passages par heure)
  // ==========================================================================

  function TimelineChart({ examenId }) {
    const Rc = useRecharts();
    const { useApi } = root.UIHooks;
    const api = useApi();

    const [loading, setLoading] = useState(true);
    const [data, setData] = useState(null);

    useEffect(() => {
      (async () => {
        setLoading(true);
        const res = await api.request('GET', `/api/analytics/examen/${examenId}/timeline`);
        setLoading(false);
        if (res.ok) setData(res.data);
      })();
    }, [examenId]);

    const chartData = useMemo(() => {
      if (!data) return [];
      return data.timeline.map(t => ({
        hour: t.hour.slice(5, 16).replace(' ', ' '), // "MM-DD HH:00"
        count: t.count,
        avg_score: t.avg_score,
      }));
    }, [data]);

    if (!Rc) return <ChartCard title="📈 Timeline des passages"><ChartLoading /></ChartCard>;
    if (loading) return <ChartCard title="📈 Timeline des passages"><ChartLoading /></ChartCard>;
    if (!data || chartData.length === 0) {
      return (
        <ChartCard title="📈 Timeline des passages">
          <div className="empty-state">
            <div className="empty-state-icon">📈</div>
            <p>Pas encore de timeline.</p>
          </div>
        </ChartCard>
      );
    }

    const { LineChart, Line, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid, Legend } = Rc;

    return (
      <ChartCard
        title="📈 Timeline des passages"
        subtitle={`${chartData.length} créneau${chartData.length > 1 ? 'x' : ''} horaire${chartData.length > 1 ? 's' : ''}`}
      >
        <ResponsiveContainer width="100%" height={300}>
          <LineChart data={chartData} margin={{ top: 10, right: 30, left: 0, bottom: 40 }}>
            <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
            <XAxis
              dataKey="hour"
              tick={{ fontSize: 10 }}
              angle={-45}
              textAnchor="end"
              height={60}
            />
            <YAxis
              yAxisId="left"
              tick={{ fontSize: 11 }}
              allowDecimals={false}
              label={{ value: 'Passages', angle: -90, position: 'insideLeft', fontSize: 12 }}
            />
            <YAxis
              yAxisId="right"
              orientation="right"
              domain={[0, 100]}
              tick={{ fontSize: 11 }}
              tickFormatter={(v) => `${v}%`}
              label={{ value: 'Score moyen', angle: 90, position: 'insideRight', fontSize: 12 }}
            />
            <Tooltip
              contentStyle={{ fontSize: 12, borderRadius: 6 }}
              formatter={(value, name) => {
                if (name === 'count') return [`${value} passages`, 'Passages'];
                if (name === 'avg_score') return [`${value}%`, 'Score moyen'];
                return [value, name];
              }}
            />
            <Legend
              wrapperStyle={{ fontSize: 12 }}
              iconSize={12}
            />
            <Line
              yAxisId="left"
              type="monotone"
              dataKey="count"
              stroke="#3b82f6"
              strokeWidth={2}
              dot={{ r: 4 }}
              activeDot={{ r: 6 }}
              name="Passages"
            />
            <Line
              yAxisId="right"
              type="monotone"
              dataKey="avg_score"
              stroke="#16a34a"
              strokeWidth={2}
              dot={{ r: 4 }}
              activeDot={{ r: 6 }}
              name="Score moyen"
            />
          </LineChart>
        </ResponsiveContainer>
      </ChartCard>
    );
  }

  // ==========================================================================
  // Exports
  // ==========================================================================

  root.ScoreDistributionChart = ScoreDistributionChart;
  root.MentionsChart = MentionsChart;
  root.QuestionSuccessChart = QuestionSuccessChart;
  root.DistractorsChart = DistractorsChart;
  root.TimelineChart = TimelineChart;

})(window);
