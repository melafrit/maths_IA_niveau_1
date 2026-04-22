/* ============================================================================
   analytics-overview.jsx — Vue d'ensemble prof (tous examens)

   Affiche :
     - 4 KPI cards (total examens, passages, moyenne, students)
     - Grille des examens (cartes cliquables)
     - Click sur examen → ouvre detail (via onExamenSelect callback)

   Composant exporte : window.AnalyticsOverview

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect } = React;

  function formatDate(iso) {
    if (!iso) return '—';
    try {
      return new Date(iso).toLocaleDateString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
      });
    } catch { return iso; }
  }

  function KPICard({ icon, label, value, sub, accent }) {
    return (
      <div className="kpi-card">
        <div className="kpi-icon">{icon}</div>
        <div className="kpi-content">
          <div className="kpi-label">{label}</div>
          <div className={`kpi-value ${accent ? 'accent-' + accent : ''}`}>{value}</div>
          {sub && <div className="kpi-sub">{sub}</div>}
        </div>
      </div>
    );
  }

  function ExamenCard({ examen, onClick }) {
    const statusClass = 'status-' + (examen.status || 'draft');
    const statusLabels = {
      draft: 'Brouillon',
      published: 'Publié',
      closed: 'Clôturé',
      archived: 'Archivé',
    };

    return (
      <div className="examen-card" onClick={() => onClick(examen)}>
        <div className="examen-card-head">
          <h3 className="examen-card-title">{examen.titre}</h3>
          <span className={`status-badge ${statusClass}`}>
            {statusLabels[examen.status] || examen.status}
          </span>
        </div>

        <div style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>
          <code style={{ fontSize: 11 }}>{examen.id}</code>
          {examen.created_at && (
            <span style={{ marginLeft: 8 }}>
              · Créé le {formatDate(examen.created_at)}
            </span>
          )}
        </div>

        <div className="examen-card-stats">
          <div className="examen-stat">
            <div className="examen-stat-val">{examen.nb_passages || 0}</div>
            <div className="examen-stat-label">Passages</div>
          </div>
          <div className="examen-stat">
            <div className="examen-stat-val" style={{
              color: (examen.avg_score_pct || 0) >= 70 ? '#16a34a'
                   : (examen.avg_score_pct || 0) >= 50 ? '#d97706'
                   : '#dc2626'
            }}>
              {examen.avg_score_pct ? examen.avg_score_pct.toFixed(1) + '%' : '—'}
            </div>
            <div className="examen-stat-label">Moyenne</div>
          </div>
        </div>
      </div>
    );
  }

  function AnalyticsOverview({ onExamenSelect, onStudentSelect }) {
    const { Button, Spinner, Input, Box } = root.UI;
    const { useApi } = root.UIHooks;
    const api = useApi();

    const [loading, setLoading] = useState(true);
    const [data, setData] = useState(null);
    const [error, setError] = useState(null);
    const [searchEmail, setSearchEmail] = useState('');

    useEffect(() => {
      (async () => {
        setLoading(true);
        const res = await api.request('GET', '/api/analytics/prof/overview');
        setLoading(false);
        if (res.ok) {
          setData(res.data);
        } else {
          setError(res.error?.message || 'Erreur chargement analytics');
        }
      })();
    }, []);

    function handleSearchStudent() {
      const email = searchEmail.trim().toLowerCase();
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;
      onStudentSelect(email);
    }

    if (loading) {
      return (
        <div className="loading-wrap">
          <Spinner />
        </div>
      );
    }

    if (error) {
      return <Box type="error">⚠️ {error}</Box>;
    }

    if (!data) return null;

    const hasExamens = data.all_examens && data.all_examens.length > 0;

    return (
      <div>
        {/* KPI Cards */}
        <div className="kpi-grid">
          <KPICard
            icon="📚"
            label="Total examens"
            value={data.total_examens}
            sub={`${data.by_status.published || 0} publiés · ${data.by_status.draft || 0} brouillons`}
            accent="blue"
          />
          <KPICard
            icon="📝"
            label="Total passages"
            value={data.total_passages}
            sub={`${data.unique_students} étudiants uniques`}
            accent="purple"
          />
          <KPICard
            icon="🎯"
            label="Moyenne globale"
            value={(data.global_avg_score_pct || 0).toFixed(1) + '%'}
            sub="Tous examens confondus"
            accent={
              data.global_avg_score_pct >= 70 ? 'green'
              : data.global_avg_score_pct >= 50 ? 'orange'
              : 'red'
            }
          />
          <KPICard
            icon="🔒"
            label="Par status"
            value={`${data.by_status.published || 0}`}
            sub={`${data.by_status.closed || 0} clôturés · ${data.by_status.archived || 0} archivés`}
            accent="green"
          />
        </div>

        {/* Recherche étudiant */}
        <div style={{
          padding: 'var(--space-3) var(--space-4)',
          background: 'var(--color-bg-elevated)',
          border: '1px solid var(--color-border)',
          borderRadius: 'var(--radius-md)',
          marginBottom: 'var(--space-4)',
          display: 'flex',
          gap: 8,
          alignItems: 'center',
        }}>
          <span style={{ fontSize: 20 }}>👤</span>
          <Input
            type="email"
            placeholder="Rechercher un étudiant par email..."
            value={searchEmail}
            onChange={e => setSearchEmail(e.target.value)}
            onKeyDown={e => {
              if (e.key === 'Enter') handleSearchStudent();
            }}
            style={{ flex: 1 }}
          />
          <Button
            variant="primary"
            onClick={handleSearchStudent}
            disabled={!searchEmail.trim()}
          >
            Voir l'historique
          </Button>
        </div>

        {/* Examens */}
        <h2 style={{
          margin: 'var(--space-4) 0 var(--space-3) 0',
          fontSize: 'var(--text-lg)',
        }}>
          📋 Mes examens ({data.all_examens?.length || 0})
        </h2>

        {hasExamens ? (
          <div className="examen-list">
            {data.all_examens.map(e => (
              <ExamenCard key={e.id} examen={e} onClick={onExamenSelect} />
            ))}
          </div>
        ) : (
          <div className="empty-state">
            <div className="empty-state-icon">📭</div>
            <h3 style={{ margin: 0 }}>Aucun examen pour l'instant</h3>
            <p>Créez votre premier examen depuis la page Examens.</p>
            <Button
              variant="primary"
              onClick={() => window.location.href = '/admin/examens.html'}
              style={{ marginTop: 8 }}
            >
              ➕ Créer un examen
            </Button>
          </div>
        )}
      </div>
    );
  }

  root.AnalyticsOverview = AnalyticsOverview;

})(window);
