/* ============================================================================
   examens-stats.jsx — Dashboard de statistiques des examens

   Plateforme d'examens IPSSI — Phase P6.3

   Fonctionnalités :
     - KPIs : total, by status, avg questions
     - Distribution par status (bar chart)
     - Top créateurs (admin uniquement)
     - Exports CSV/JSON

   Composant exporté : window.ExamensStats

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useMemo } = React;

  const STATUS_COLORS = {
    draft: '#64748b',
    published: '#16a34a',
    closed: '#d97706',
    archived: '#9333ea',
  };

  const STATUS_LABELS = {
    draft: '📝 Brouillon',
    published: '🚀 Publié',
    closed: '🔒 Clôturé',
    archived: '📦 Archivé',
  };

  // KPI Card
  function KpiCard({ icon, value, label, color = 'var(--color-primary)' }) {
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

        <div style={{ fontSize: 32, marginBottom: 4 }}>{icon}</div>
        <div style={{
          fontSize: 'var(--text-3xl)',
          fontWeight: 800,
          color: color,
          lineHeight: 1,
          marginBottom: 6,
        }}>{value}</div>
        <div style={{
          fontSize: 'var(--text-sm)',
          color: 'var(--color-text-muted)',
          textTransform: 'uppercase',
          letterSpacing: 0.5,
          fontWeight: 600,
        }}>{label}</div>
      </div>
    );
  }

  // Bar horizontal
  function HorizontalBar({ label, value, total, color }) {
    const pct = total > 0 ? Math.round((value / total) * 100) : 0;
    return (
      <div style={{ marginBottom: 10 }}>
        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: 4,
        }}>
          <span style={{ fontSize: 'var(--text-sm)', fontWeight: 500 }}>{label}</span>
          <span style={{ fontSize: 'var(--text-sm)', fontWeight: 700, color }}>
            <strong>{value}</strong>
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

  // Section wrapper
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
          }}>{icon}{title}</h4>
          {actions}
        </div>
        {children}
      </div>
    );
  }

  // ==========================================================================
  // Composant principal
  // ==========================================================================

  function ExamensStats() {
    const { Button, Box, Spinner, useToast } = root.UI;
    const { useApi, useAuth } = root.UIHooks;
    const api = useApi();
    const { toast } = useToast();
    const { user } = useAuth();

    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const isAdmin = user?.role === 'admin';

    async function loadStats() {
      setLoading(true);
      try {
        const res = await api.request('GET', '/api/examens/stats');
        if (res.ok) {
          setStats(res.data);
        } else {
          toast({
            title: 'Erreur',
            message: res.error?.message || 'Impossible de charger les stats',
            type: 'error',
          });
        }
      } catch (err) {
        toast({ title: 'Erreur inattendue', message: err.message || 'Erreur stats', type: 'error' });
      }
      setLoading(false);
    }

    useEffect(() => { loadStats(); }, []);

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

    function exportJSON() {
      if (!stats) return;
      const payload = {
        meta: {
          generated_at: new Date().toISOString(),
          generator: 'IPSSI Examens Dashboard',
          by: user?.id,
        },
        stats,
      };
      const ts = new Date().toISOString().slice(0, 10);
      download(`examens_stats_${ts}.json`, JSON.stringify(payload, null, 2), 'application/json');
      toast({ title: 'Export JSON', message: 'Rapport téléchargé', type: 'success' });
    }

    // Top créateurs
    const topOwners = useMemo(() => {
      if (!stats?.by_owner) return [];
      return Object.entries(stats.by_owner)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 10);
    }, [stats]);

    // ==========================================================================
    // Render
    // ==========================================================================

    if (!isAdmin) {
      return (
        <Box type="info">
          📊 Les statistiques globales sont réservées aux administrateurs.
        </Box>
      );
    }

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
      return <Box type="error">Impossible de charger les statistiques.</Box>;
    }

    const total = stats.total || 0;
    const byStatus = stats.by_status || {};

    return (
      <div>
        <h3 style={{ marginTop: 0, display: 'flex', alignItems: 'center', gap: 8 }}>
          📊 Statistiques des examens
        </h3>
        <p style={{ color: 'var(--color-text-muted)', marginBottom: 'var(--space-4)' }}>
          Vue globale des examens de la plateforme (admin uniquement).
        </p>

        {/* KPIs */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))',
          gap: 'var(--space-3)',
          marginBottom: 'var(--space-4)',
        }}>
          <KpiCard
            icon="📝"
            value={total}
            label="Total examens"
            color="#3b82f6"
          />
          <KpiCard
            icon="🚀"
            value={byStatus.published || 0}
            label="Publiés"
            color="#16a34a"
          />
          <KpiCard
            icon="❓"
            value={stats.total_questions_used || 0}
            label="Questions utilisées"
            color="#8b5cf6"
          />
          <KpiCard
            icon="📈"
            value={stats.avg_questions_per_exam || 0}
            label="Moy. questions/examen"
            color="#ec4899"
          />
        </div>

        {/* Actions */}
        <div style={{
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          marginBottom: 'var(--space-4)',
          display: 'flex',
          gap: 'var(--space-2)',
        }}>
          <Button variant="ghost" size="sm" onClick={loadStats}>
            🔄 Rafraîchir
          </Button>
          <div style={{ flex: 1 }}></div>
          <Button variant="secondary" size="sm" onClick={exportJSON}>
            📋 Export rapport JSON
          </Button>
        </div>

        {/* Distribution par status */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'minmax(0, 1fr) minmax(0, 1fr)',
          gap: 'var(--space-4)',
        }}>
          <Section title="Distribution par status" icon="📊">
            {Object.entries(byStatus).map(([status, count]) => (
              <HorizontalBar
                key={status}
                label={STATUS_LABELS[status] || status}
                value={count}
                total={total}
                color={STATUS_COLORS[status] || '#64748b'}
              />
            ))}
            {total === 0 && (
              <p style={{ color: 'var(--color-text-muted)', fontSize: 13, textAlign: 'center' }}>
                Aucun examen dans la plateforme.
              </p>
            )}
          </Section>

          {/* Top créateurs */}
          <Section title="Top créateurs" icon="👥">
            {topOwners.length === 0 ? (
              <p style={{ color: 'var(--color-text-muted)', fontSize: 13, textAlign: 'center' }}>
                Aucun créateur.
              </p>
            ) : (
              <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                {topOwners.map(([owner, count], i) => {
                  const max = topOwners[0][1];
                  const pct = max > 0 ? (count / max) * 100 : 0;
                  return (
                    <div key={owner} style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
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
                          <code style={{ fontSize: 'var(--text-sm)', fontWeight: 600 }}>{owner}</code>
                          <span style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>
                            <strong style={{ color: 'var(--color-primary)' }}>{count}</strong> examen{count > 1 ? 's' : ''}
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
        </div>

        {/* Footer */}
        <div style={{
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          textAlign: 'center',
          fontSize: 11,
          color: 'var(--color-text-muted)',
          marginTop: 'var(--space-4)',
        }}>
          📅 Dernière mise à jour : {new Date().toLocaleString('fr-FR')}
          {' · '}
          {total} examens
          {' · '}
          © 2026 Mohamed EL AFRIT — CC BY-NC-SA 4.0
        </div>
      </div>
    );
  }

  root.ExamensStats = ExamensStats;

})(window);
