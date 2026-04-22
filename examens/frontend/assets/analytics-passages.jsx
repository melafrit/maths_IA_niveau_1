/* ============================================================================
   analytics-passages.jsx — Historique passages d'un examen (priorité #1)

   Features :
     - Header avec KPIs de l'examen
     - Table interactive avec :
       * Colonnes : Date, Étudiant, Score, Durée, Status, Anomalies
       * Tri cliquable sur headers
       * Recherche
       * Filtres : status, plage score, anomalies
       * Pagination
       * Click ligne → détail passage
     - Export prévu en P7.6

   Composant exporté : window.AnalyticsPassages

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useCallback, useMemo } = React;

  function formatDateTime(iso) {
    if (!iso) return '—';
    try {
      return new Date(iso).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: '2-digit',
        hour: '2-digit', minute: '2-digit',
      });
    } catch { return iso; }
  }

  function formatDuration(sec) {
    if (!sec || sec < 0) return '—';
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    if (m > 0) return `${m}m ${String(s).padStart(2, '0')}s`;
    return `${s}s`;
  }

  function scoreClass(pct) {
    if (pct === null || pct === undefined) return '';
    if (pct >= 80) return 'excellent';
    if (pct >= 60) return 'good';
    if (pct >= 40) return 'medium';
    return 'bad';
  }

  const STATUS_LABELS = {
    'in_progress': { label: 'En cours', color: '#3b82f6' },
    'submitted': { label: 'Soumis', color: '#16a34a' },
    'expired': { label: 'Expiré', color: '#d97706' },
    'invalidated': { label: 'Invalidé', color: '#dc2626' },
  };

  function KPIBar({ overview }) {
    if (!overview) return null;

    return (
      <div className="kpi-grid" style={{ marginBottom: 'var(--space-4)' }}>
        <div className="kpi-card">
          <div className="kpi-icon">📝</div>
          <div className="kpi-content">
            <div className="kpi-label">Total passages</div>
            <div className="kpi-value accent-blue">{overview.total_passages}</div>
            <div className="kpi-sub">{overview.unique_students} étudiants</div>
          </div>
        </div>
        <div className="kpi-card">
          <div className="kpi-icon">🎯</div>
          <div className="kpi-content">
            <div className="kpi-label">Moyenne</div>
            <div className={`kpi-value ${
              overview.avg_score_pct >= 70 ? 'accent-green'
              : overview.avg_score_pct >= 50 ? 'accent-orange'
              : 'accent-red'
            }`}>
              {overview.avg_score_pct}%
            </div>
            <div className="kpi-sub">Médiane : {overview.median_score_pct}%</div>
          </div>
        </div>
        <div className="kpi-card">
          <div className="kpi-icon">📊</div>
          <div className="kpi-content">
            <div className="kpi-label">Plage scores</div>
            <div className="kpi-value">
              {overview.min_score_pct}—{overview.max_score_pct}<span style={{fontSize:16,color:'var(--color-text-muted)'}}>%</span>
            </div>
            <div className="kpi-sub">Écart-type : {overview.std_dev}</div>
          </div>
        </div>
        <div className="kpi-card">
          <div className="kpi-icon">🔒</div>
          <div className="kpi-content">
            <div className="kpi-label">Anomalies</div>
            <div className={`kpi-value ${
              overview.anomaly_passages > 0 ? 'accent-red' : 'accent-green'
            }`}>
              {overview.anomaly_passages}
            </div>
            <div className="kpi-sub">{overview.anomaly_rate_pct}% du total</div>
          </div>
        </div>
      </div>
    );
  }

  function SortableHeader({ label, field, currentSort, currentOrder, onChange }) {
    const isActive = currentSort === field;
    const arrow = isActive ? (currentOrder === 'asc' ? '↑' : '↓') : '';

    function handleClick() {
      if (isActive) {
        onChange(field, currentOrder === 'asc' ? 'desc' : 'asc');
      } else {
        onChange(field, 'desc');
      }
    }

    return (
      <th onClick={handleClick}>
        {label}
        {arrow && <span className="sort-arrow">{arrow}</span>}
      </th>
    );
  }

  function PassageRow({ passage, onClick }) {
    const status = STATUS_LABELS[passage.status] || { label: passage.status, color: '#64748b' };
    const studentName = (passage.student_info?.prenom || '') + ' ' + (passage.student_info?.nom || '');

    return (
      <tr onClick={() => onClick && onClick(passage)}>
        <td data-label="Date">
          <div style={{ fontSize: 12 }}>{formatDateTime(passage.start_time)}</div>
        </td>
        <td data-label="Étudiant">
          <div style={{ fontWeight: 600 }}>{studentName.trim() || '(anonyme)'}</div>
          <div style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>
            {passage.student_info?.email}
          </div>
        </td>
        <td data-label="Score">
          {passage.score_pct !== null ? (
            <span className={`score-pill ${scoreClass(passage.score_pct)}`}>
              {passage.score_brut}/{passage.score_max} ({passage.score_pct.toFixed(1)}%)
            </span>
          ) : '—'}
        </td>
        <td data-label="Durée">
          {formatDuration(passage.duration_sec)}
        </td>
        <td data-label="Status">
          <span style={{
            color: status.color,
            fontSize: 12,
            fontWeight: 600,
          }}>● {status.label}</span>
        </td>
        <td data-label="Anomalies">
          {passage.anomalies_count > 0 ? (
            <span className="anomaly-badge">⚠️ {passage.anomalies_count}</span>
          ) : (
            <span style={{ color: 'var(--color-text-muted)', fontSize: 12 }}>—</span>
          )}
        </td>
      </tr>
    );
  }

  function AnalyticsPassages({ examenId, examenTitre, onBack, onPassageSelect }) {
    const { Button, Spinner, Box } = root.UI;
    const { useApi, useDebounce } = root.UIHooks;
    const api = useApi();

    const [loading, setLoading] = useState(true);
    const [overview, setOverview] = useState(null);
    const [data, setData] = useState(null);
    const [error, setError] = useState(null);

    // Tab actif : 'historique' | 'graphiques'
    const [activeTab, setActiveTab] = useState('historique');

    // Question sélectionnée pour distracteurs
    const [selectedQuestionId, setSelectedQuestionId] = useState(null);

    // Filtres
    const [search, setSearch] = useState('');
    const debouncedSearch = useDebounce(search, 300);
    const [statusFilter, setStatusFilter] = useState('');
    const [anomaliesOnly, setAnomaliesOnly] = useState(false);
    const [minScore, setMinScore] = useState('');
    const [maxScore, setMaxScore] = useState('');

    // Tri
    const [sort, setSort] = useState('date');
    const [order, setOrder] = useState('desc');

    // Pagination
    const [page, setPage] = useState(0);
    const PAGE_SIZE = 20;

    const loadOverview = useCallback(async () => {
      const res = await api.request('GET', `/api/analytics/examen/${examenId}/overview`);
      if (res.ok) setOverview(res.data);
    }, [examenId]);

    const loadPassages = useCallback(async () => {
      setLoading(true);

      const params = new URLSearchParams();
      if (debouncedSearch) params.set('search', debouncedSearch);
      if (statusFilter) params.set('status', statusFilter);
      if (anomaliesOnly) params.set('with_anomalies', 'true');
      if (minScore) params.set('min_score_pct', minScore);
      if (maxScore) params.set('max_score_pct', maxScore);
      params.set('sort', sort);
      params.set('order', order);
      params.set('limit', PAGE_SIZE);
      params.set('offset', page * PAGE_SIZE);

      const res = await api.request(
        'GET',
        `/api/analytics/examen/${examenId}/passages?${params.toString()}`
      );
      setLoading(false);
      if (res.ok) {
        setData(res.data);
        setError(null);
      } else {
        setError(res.error?.message || 'Erreur');
      }
    }, [examenId, debouncedSearch, statusFilter, anomaliesOnly, minScore, maxScore, sort, order, page]);

    useEffect(() => { loadOverview(); }, [loadOverview]);
    useEffect(() => { loadPassages(); }, [loadPassages]);

    // Reset page si filtres changent
    useEffect(() => { setPage(0); }, [debouncedSearch, statusFilter, anomaliesOnly, minScore, maxScore]);

    function handleSortChange(field, newOrder) {
      setSort(field);
      setOrder(newOrder);
    }

    function resetFilters() {
      setSearch('');
      setStatusFilter('');
      setAnomaliesOnly(false);
      setMinScore('');
      setMaxScore('');
    }

    const totalPages = data ? Math.ceil(data.total / PAGE_SIZE) : 0;
    const currentPage = page + 1;

    return (
      <div>
        {/* Breadcrumb / header */}
        <div style={{ marginBottom: 'var(--space-3)' }}>
          <Button variant="ghost" onClick={onBack}>
            ← Retour au dashboard
          </Button>
          <h2 style={{
            margin: 'var(--space-2) 0 0 0',
            fontSize: 'var(--text-2xl)',
          }}>
            📊 {examenTitre || 'Examen'}
          </h2>
          <div style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>
            <code>{examenId}</code>
          </div>
        </div>

        {/* KPIs */}
        <KPIBar overview={overview} />

        {/* TABS */}
        <div className="analytics-tabs">
          <button
            className={`analytics-tab ${activeTab === 'historique' ? 'active' : ''}`}
            onClick={() => setActiveTab('historique')}
          >
            📋 Historique des passages
          </button>
          <button
            className={`analytics-tab ${activeTab === 'graphiques' ? 'active' : ''}`}
            onClick={() => setActiveTab('graphiques')}
          >
            📊 Graphiques
          </button>
        </div>

        {/* Contenu selon tab */}
        {activeTab === 'graphiques' && (
          <div>
            {window.ScoreDistributionChart && (
              <window.ScoreDistributionChart examenId={examenId} />
            )}
            {window.MentionsChart && (
              <window.MentionsChart examenId={examenId} />
            )}
            {window.QuestionSuccessChart && (
              <window.QuestionSuccessChart
                examenId={examenId}
                onQuestionClick={(qid) => setSelectedQuestionId(qid)}
              />
            )}
            {window.DistractorsChart && (
              <window.DistractorsChart
                examenId={examenId}
                questionId={selectedQuestionId}
              />
            )}
            {window.TimelineChart && (
              <window.TimelineChart examenId={examenId} />
            )}
          </div>
        )}

        {activeTab === 'historique' && (
        <div className="passage-table-wrap">
          <div className="passage-filters">
            <input
              type="text"
              className="passage-search"
              placeholder="🔍 Rechercher par nom, prénom ou email..."
              value={search}
              onChange={e => setSearch(e.target.value)}
            />

            <select
              className="passage-select"
              value={statusFilter}
              onChange={e => setStatusFilter(e.target.value)}
            >
              <option value="">Tous les statuts</option>
              <option value="submitted">✅ Soumis</option>
              <option value="expired">⏰ Expirés</option>
              <option value="invalidated">🚫 Invalidés</option>
              <option value="in_progress">🔄 En cours</option>
            </select>

            <input
              type="number"
              className="passage-search"
              style={{ maxWidth: 100, minWidth: 80 }}
              placeholder="Score min %"
              value={minScore}
              onChange={e => setMinScore(e.target.value)}
              min="0" max="100"
            />
            <input
              type="number"
              className="passage-search"
              style={{ maxWidth: 100, minWidth: 80 }}
              placeholder="Score max %"
              value={maxScore}
              onChange={e => setMaxScore(e.target.value)}
              min="0" max="100"
            />

            <label className="passage-checkbox-wrap">
              <input
                type="checkbox"
                checked={anomaliesOnly}
                onChange={e => setAnomaliesOnly(e.target.checked)}
              />
              ⚠️ Anomalies seulement
            </label>

            {(search || statusFilter || anomaliesOnly || minScore || maxScore) && (
              <Button variant="ghost" onClick={resetFilters} style={{ fontSize: 12 }}>
                🗑️ Réinitialiser
              </Button>
            )}
          </div>

          {error && (
            <div style={{ padding: 'var(--space-4)' }}>
              <Box type="error">⚠️ {error}</Box>
            </div>
          )}

          {loading && !data ? (
            <div className="loading-wrap">
              <Spinner />
            </div>
          ) : data && data.passages.length === 0 ? (
            <div className="empty-state">
              <div className="empty-state-icon">🔍</div>
              <h3 style={{ margin: 0 }}>Aucun passage trouvé</h3>
              <p>Essayez de modifier vos filtres.</p>
            </div>
          ) : data ? (
            <>
              <table className="passage-table">
                <thead>
                  <tr>
                    <SortableHeader
                      label="Date" field="date"
                      currentSort={sort} currentOrder={order}
                      onChange={handleSortChange}
                    />
                    <SortableHeader
                      label="Étudiant" field="name"
                      currentSort={sort} currentOrder={order}
                      onChange={handleSortChange}
                    />
                    <SortableHeader
                      label="Score" field="score"
                      currentSort={sort} currentOrder={order}
                      onChange={handleSortChange}
                    />
                    <SortableHeader
                      label="Durée" field="duration"
                      currentSort={sort} currentOrder={order}
                      onChange={handleSortChange}
                    />
                    <th>Status</th>
                    <th>Anomalies</th>
                  </tr>
                </thead>
                <tbody>
                  {data.passages.map(p => (
                    <PassageRow
                      key={p.id}
                      passage={p}
                      onClick={onPassageSelect}
                    />
                  ))}
                </tbody>
              </table>

              {/* Pagination */}
              <div className="passage-table-footer">
                <div>
                  Affichage {data.offset + 1}—{data.offset + data.count} sur {data.total} passage{data.total > 1 ? 's' : ''}
                </div>
                {totalPages > 1 && (
                  <div className="pagination-controls">
                    <button
                      className="page-btn"
                      onClick={() => setPage(0)}
                      disabled={page === 0}
                    >
                      « Premier
                    </button>
                    <button
                      className="page-btn"
                      onClick={() => setPage(p => Math.max(0, p - 1))}
                      disabled={page === 0}
                    >
                      ← Précédent
                    </button>
                    <span style={{
                      padding: '6px 10px',
                      fontSize: 12,
                      fontWeight: 600,
                    }}>
                      Page {currentPage} / {totalPages}
                    </span>
                    <button
                      className="page-btn"
                      onClick={() => setPage(p => p + 1)}
                      disabled={page >= totalPages - 1}
                    >
                      Suivant →
                    </button>
                    <button
                      className="page-btn"
                      onClick={() => setPage(totalPages - 1)}
                      disabled={page >= totalPages - 1}
                    >
                      Dernier »
                    </button>
                  </div>
                )}
              </div>
            </>
          ) : null}
        </div>
        )}
      </div>
    );
  }

  root.AnalyticsPassages = AnalyticsPassages;

})(window);
