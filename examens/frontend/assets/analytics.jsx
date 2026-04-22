/* ============================================================================
   analytics.jsx — App principale Analytics

   State machine simple :
     - 'overview'  : vue d'ensemble prof (landing)
     - 'examen'    : detail d'un examen (historique passages)
     - 'student'   : historique etudiant (prepare pour P7.5)

   URL sync via hash :
     #/                     → overview
     #/examen/EXM-XXX       → detail examen
     #/student/alice@...    → detail etudiant

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect } = React;
  const { AnalyticsOverview, AnalyticsPassages } = window;

  function AnalyticsApp() {
    const { ToastProvider } = root.UI;
    return (
      <ToastProvider>
        <AnalyticsAppInner />
      </ToastProvider>
    );
  }

  function AnalyticsAppInner() {
    const { Button, Spinner, Box } = root.UI;
    const { useAuth } = root.UIHooks;
    const auth = useAuth();

    const [view, setView] = useState('overview');
    const [selectedExamen, setSelectedExamen] = useState(null);
    const [selectedStudent, setSelectedStudent] = useState(null);

    // ----- Auth check -----
    useEffect(() => {
      auth.fetchMe();
    }, []);

    // ----- URL hash routing -----
    useEffect(() => {
      function syncFromHash() {
        const hash = window.location.hash.slice(1) || '/';
        const parts = hash.split('/').filter(Boolean);

        if (parts.length === 0) {
          setView('overview');
        } else if (parts[0] === 'examen' && parts[1]) {
          setSelectedExamen({ id: parts[1], titre: parts[2] ? decodeURIComponent(parts[2]) : '' });
          setView('examen');
        } else if (parts[0] === 'student' && parts[1]) {
          setSelectedStudent(decodeURIComponent(parts[1]));
          setView('student');
        } else {
          setView('overview');
        }
      }

      syncFromHash();
      window.addEventListener('hashchange', syncFromHash);
      return () => window.removeEventListener('hashchange', syncFromHash);
    }, []);

    // ----- Handlers -----
    function handleExamenSelect(examen) {
      setSelectedExamen(examen);
      window.location.hash = `#/examen/${examen.id}/${encodeURIComponent(examen.titre || '')}`;
    }

    function handleStudentSelect(email) {
      setSelectedStudent(email);
      window.location.hash = `#/student/${encodeURIComponent(email)}`;
    }

    function handleBackToOverview() {
      setSelectedExamen(null);
      setSelectedStudent(null);
      window.location.hash = '#/';
    }

    function handlePassageSelect(passage) {
      // TODO : en P7.5, ouvrir modal detail passage / correction
      console.log('Passage selected:', passage);
    }

    // ----- Auth states -----
    if (auth.loading) {
      return (
        <div className="loading-wrap" style={{ minHeight: '100vh' }}>
          <Spinner />
        </div>
      );
    }

    if (!auth.user) {
      return (
        <div className="analytics-container">
          <Box type="error" style={{ maxWidth: 500, margin: '80px auto', textAlign: 'center' }}>
            <h2>🔒 Connexion requise</h2>
            <p>Vous devez être connecté pour accéder à cette page.</p>
            <Button
              variant="primary"
              onClick={() => window.location.href = '/admin/login.html'}
            >
              Se connecter
            </Button>
          </Box>
        </div>
      );
    }

    if (!['admin', 'enseignant'].includes(auth.user.role)) {
      return (
        <div className="analytics-container">
          <Box type="error" style={{ maxWidth: 500, margin: '80px auto', textAlign: 'center' }}>
            <h2>🚫 Accès refusé</h2>
            <p>Cette page est réservée aux enseignants et administrateurs.</p>
          </Box>
        </div>
      );
    }

    // ----- Breadcrumb -----
    const breadcrumb = (
      <div className="analytics-breadcrumb">
        {view === 'overview' ? (
          <span>📊 Analytics</span>
        ) : (
          <>
            <a onClick={handleBackToOverview}>📊 Analytics</a>
            <span className="analytics-breadcrumb-sep">›</span>
            {view === 'examen' && (
              <span>{selectedExamen?.titre || selectedExamen?.id}</span>
            )}
            {view === 'student' && (
              <span>👤 {selectedStudent}</span>
            )}
          </>
        )}
      </div>
    );

    // ----- Render -----
    return (
      <div className="analytics-container">
        {/* Top nav */}
        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: 'var(--space-3)',
        }}>
          <div>
            <h1 style={{ margin: 0, fontSize: 'var(--text-xl)' }}>
              📊 Dashboard Analytics
            </h1>
            <div style={{ fontSize: 13, color: 'var(--color-text-muted)' }}>
              {auth.user.nom || auth.user.email} · {auth.user.role}
            </div>
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            <Button
              variant="ghost"
              onClick={() => window.location.href = '/admin/examens.html'}
            >
              ← Examens
            </Button>
            <Button
              variant="ghost"
              onClick={() => window.location.href = '/admin/banque.html'}
            >
              📚 Banque
            </Button>
          </div>
        </div>

        {breadcrumb}

        {/* Content */}
        {view === 'overview' && (
          <AnalyticsOverview
            onExamenSelect={handleExamenSelect}
            onStudentSelect={handleStudentSelect}
          />
        )}

        {view === 'examen' && selectedExamen && (
          <AnalyticsPassages
            examenId={selectedExamen.id}
            examenTitre={selectedExamen.titre}
            onBack={handleBackToOverview}
            onPassageSelect={handlePassageSelect}
          />
        )}

        {view === 'student' && selectedStudent && (
          window.AnalyticsStudent ? (
            <window.AnalyticsStudent
              email={selectedStudent}
              onBack={handleBackToOverview}
            />
          ) : (
            <StudentPlaceholder
              email={selectedStudent}
              onBack={handleBackToOverview}
            />
          )
        )}

        {/* Footer */}
        <div style={{
          textAlign: 'center',
          marginTop: 'var(--space-5)',
          paddingTop: 'var(--space-3)',
          borderTop: '1px solid var(--color-border)',
          fontSize: 11,
          color: 'var(--color-text-muted)',
        }}>
          © 2026 Mohamed EL AFRIT — IPSSI · CC BY-NC-SA 4.0
        </div>

        {/* Print footer (visible uniquement en impression) */}
        <div className="print-footer print-only">
          © 2026 Mohamed EL AFRIT — IPSSI · CC BY-NC-SA 4.0 · {new Date().toLocaleString('fr-FR')}
        </div>
      </div>
    );
  }

  // Placeholder pour la vue etudiant (sera remplace en P7.5)
  function StudentPlaceholder({ email, onBack }) {
    const { Button, Box, Spinner } = root.UI;
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
        if (res.ok) setData(res.data);
        else setError(res.error?.message || 'Erreur');
      })();
    }, [email]);

    if (loading) return <div className="loading-wrap"><Spinner /></div>;
    if (error) return <Box type="error">⚠️ {error}</Box>;
    if (!data) return null;

    return (
      <div>
        <Button variant="ghost" onClick={onBack}>← Retour</Button>
        <h2 style={{ marginTop: 'var(--space-3)' }}>
          👤 {data.student_info.prenom} {data.student_info.nom}
        </h2>
        <p style={{ color: 'var(--color-text-muted)' }}>{data.email}</p>

        <div className="kpi-grid" style={{ marginTop: 'var(--space-3)' }}>
          <div className="kpi-card">
            <div className="kpi-icon">📝</div>
            <div className="kpi-content">
              <div className="kpi-label">Passages</div>
              <div className="kpi-value accent-blue">{data.nb_passages}</div>
            </div>
          </div>
          <div className="kpi-card">
            <div className="kpi-icon">🎯</div>
            <div className="kpi-content">
              <div className="kpi-label">Moyenne</div>
              <div className="kpi-value accent-purple">{(data.avg_score_pct || 0).toFixed(1)}%</div>
            </div>
          </div>
          <div className="kpi-card">
            <div className="kpi-icon">🏆</div>
            <div className="kpi-content">
              <div className="kpi-label">Meilleur</div>
              <div className="kpi-value accent-green">{(data.best_score_pct || 0).toFixed(1)}%</div>
            </div>
          </div>
          <div className="kpi-card">
            <div className="kpi-icon">📉</div>
            <div className="kpi-content">
              <div className="kpi-label">Pire</div>
              <div className="kpi-value accent-orange">{(data.worst_score_pct || 0).toFixed(1)}%</div>
            </div>
          </div>
        </div>

        <h3 style={{ marginTop: 'var(--space-4)' }}>📋 Historique des passages</h3>

        <div className="passage-table-wrap">
          <table className="passage-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Examen</th>
                <th>Score</th>
                <th>Durée</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              {data.passages.map(p => (
                <tr key={p.passage_id}>
                  <td>
                    <div style={{ fontSize: 12 }}>
                      {new Date(p.start_time).toLocaleString('fr-FR', {
                        day: '2-digit', month: '2-digit', year: '2-digit',
                        hour: '2-digit', minute: '2-digit',
                      })}
                    </div>
                  </td>
                  <td>
                    <div style={{ fontWeight: 600 }}>{p.examen_titre}</div>
                    <code style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>{p.examen_id}</code>
                  </td>
                  <td>
                    {p.score_pct !== null ? (
                      <span className={`score-pill ${
                        p.score_pct >= 80 ? 'excellent'
                        : p.score_pct >= 60 ? 'good'
                        : p.score_pct >= 40 ? 'medium'
                        : 'bad'
                      }`}>
                        {p.score_brut}/{p.score_max} ({p.score_pct.toFixed(1)}%)
                      </span>
                    ) : '—'}
                  </td>
                  <td>
                    {p.duration_sec ? `${Math.floor(p.duration_sec/60)}m ${String(p.duration_sec%60).padStart(2,'0')}s` : '—'}
                  </td>
                  <td style={{ fontSize: 12, fontWeight: 600 }}>
                    {p.status}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {data.passages.length === 0 && (
            <div className="empty-state">
              <div className="empty-state-icon">📭</div>
              <h3 style={{ margin: 0 }}>Aucun passage</h3>
            </div>
          )}
        </div>

        {data.filtered_to_prof && (
          <div style={{
            marginTop: 'var(--space-3)',
            padding: 'var(--space-3)',
            background: 'rgba(59, 130, 246, 0.08)',
            border: '1px solid #3b82f6',
            borderRadius: 'var(--radius-md)',
            fontSize: 12,
          }}>
            ℹ️ Vue filtrée : seuls les passages sur VOS examens sont affichés.
          </div>
        )}
      </div>
    );
  }

  // Mount (immediate — Babel Standalone runs after DOMContentLoaded)
  const rootElement = document.getElementById('root');
  if (rootElement) {
    ReactDOM.createRoot(rootElement).render(<AnalyticsApp />);
  }

})(window);
