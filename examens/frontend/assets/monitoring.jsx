/* ============================================================================
   monitoring.jsx — Dashboard monitoring admin

   Features :
     - Appel /api/health?detailed=1 avec auto-refresh configurable
     - Banner status global color-coded
     - Card par check (disk, memory, filesystem, counters, backups, logs, php)
     - Progress bars pour les usages %
     - Refresh manuel + auto 30s

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useRef } = React;

  // ========================================================================
  // Helpers
  // ========================================================================

  function usageClass(pct) {
    if (pct === null || pct === undefined) return 'low';
    if (pct >= 90) return 'high';
    if (pct >= 75) return 'medium';
    return 'low';
  }

  function statusIcon(status) {
    return { ok: '✅', warning: '⚠️', error: '🚨' }[status] || '❓';
  }

  function statusMessage(status) {
    return { ok: 'Tout va bien', warning: 'Avertissements', error: 'Erreurs critiques' }[status] || status;
  }

  function fmtDate(iso) {
    if (!iso) return '—';
    try { return new Date(iso).toLocaleString('fr-FR'); }
    catch { return iso; }
  }

  // ========================================================================
  // CHECK CARDS (une par type de check)
  // ========================================================================

  function DiskCard({ data }) {
    return (
      <CheckCard icon="💾" title="Disque" data={data}>
        <div className="usage-bar">
          <div
            className={`usage-fill ${usageClass(data.usage_pct)}`}
            style={{ width: (data.usage_pct || 0) + '%' }}
          />
        </div>
        <div className="check-details">
          <DetailRow k="Libre" v={data.free_human} />
          <DetailRow k="Total" v={data.total_human} />
          <DetailRow k="Utilisé" v={data.usage_pct + '%'} />
          <DetailRow k="data/" v={data.data_size_human} />
        </div>
      </CheckCard>
    );
  }

  function MemoryCard({ data }) {
    return (
      <CheckCard icon="🧠" title="Mémoire" data={data}>
        <div className="usage-bar">
          <div
            className={`usage-fill ${usageClass(data.usage_pct)}`}
            style={{ width: (data.usage_pct || 0) + '%' }}
          />
        </div>
        <div className="check-details">
          <DetailRow k="Courante" v={data.current_human} />
          <DetailRow k="Peak" v={data.peak_human} />
          <DetailRow k="Limite" v={data.limit_human} />
          <DetailRow k="Usage" v={data.usage_pct + '%'} />
        </div>
      </CheckCard>
    );
  }

  function FilesystemCard({ data }) {
    return (
      <CheckCard icon="📁" title="Filesystem" data={data}>
        <div className="check-details">
          {Object.entries(data.directories || {}).map(([name, info]) => (
            <DetailRow
              key={name}
              k={name}
              v={info.ok ? '✅ OK' : '❌ KO'}
            />
          ))}
        </div>
      </CheckCard>
    );
  }

  function CountersCard({ data }) {
    return (
      <CheckCard icon="📊" title="Compteurs" data={data}>
        <div className="check-details">
          {Object.entries(data.counts || {}).map(([name, value]) => (
            <DetailRow
              key={name}
              k={name.replace('_total', '').replace('_active', ' (actifs)')}
              v={value}
            />
          ))}
        </div>
      </CheckCard>
    );
  }

  function BackupsCard({ data }) {
    return (
      <CheckCard icon="💾" title="Backups" data={data}>
        <div className="check-details">
          <DetailRow k="Total" v={data.total || 0} />
          {data.last_backup && (
            <>
              <DetailRow k="Dernier" v={data.last_backup} />
              <DetailRow k="Âge" v={data.last_backup_age_human} />
              <DetailRow k="Taille totale" v={data.total_size_human} />
            </>
          )}
          {!data.last_backup && (
            <DetailRow k="État" v="Aucun backup" />
          )}
        </div>
      </CheckCard>
    );
  }

  function LogsCard({ data }) {
    return (
      <CheckCard icon="📝" title="Logs" data={data}>
        <div className="check-details">
          <DetailRow k="Taille" v={data.size_human} />
          <DetailRow k="Fichiers" v={data.files_count} />
        </div>
      </CheckCard>
    );
  }

  function PhpCard({ data }) {
    return (
      <CheckCard icon="🐘" title="PHP" data={data}>
        <div className="check-details">
          <DetailRow k="Version" v={data.version} />
          <DetailRow k="SAPI" v={data.sapi} />
          <DetailRow k="Timezone" v={data.timezone} />
          <DetailRow k="OPCache" v={data.opcache_enabled ? '✅' : '❌'} />
          <DetailRow k="Extensions" v={Object.entries(data.extensions || {}).filter(([, v]) => v).length + ' actives'} />
        </div>
      </CheckCard>
    );
  }

  // ========================================================================
  // Wrappers generiques
  // ========================================================================

  function CheckCard({ icon, title, data, children }) {
    return (
      <div className={`check-card ${data.status}`}>
        <div className="check-card-head">
          <span className="check-icon">{icon}</span>
          <span className="check-title">{title}</span>
          <span className={`status-pill ${data.status}`}>{data.status}</span>
        </div>
        <p className="check-message">{data.message}</p>
        {children}
      </div>
    );
  }

  function DetailRow({ k, v }) {
    return (
      <div className="detail-row">
        <span className="detail-key">{k}</span>
        <span className="detail-value">{v ?? '—'}</span>
      </div>
    );
  }

  // ========================================================================
  // App principale
  // ========================================================================

  function MonitoringApp() {
    const { Button, Spinner, Box, ToastProvider, ErrorBoundary } = root.UI;
    return (
      <ToastProvider>
        <ErrorBoundary>
          <MonitoringInner />
        </ErrorBoundary>
      </ToastProvider>
    );
  }

  function MonitoringInner() {
    const { Button, Spinner, Box } = root.UI;
    const { useApi, useAuth } = root.UIHooks;
    const { AdminLayout } = root.UILayouts;
    const api = useApi();
    const auth = useAuth();

    const [loading, setLoading] = useState(true);
    const [report, setReport] = useState(null);
    const [error, setError] = useState(null);
    const [autoRefresh, setAutoRefresh] = useState(false);
    const [lastUpdate, setLastUpdate] = useState(null);
    const intervalRef = useRef(null);

    // Auth check is automatic via useAuth() — no manual fetchMe needed

    async function fetchHealth() {
      setError(null);
      try {
        const res = await api.request('GET', '/api/health?detailed=1');
        if (res.ok) {
          setReport(res.data || res);
          setLastUpdate(new Date());
        } else {
          setError(res.error?.message || 'Erreur inconnue');
        }
      } catch (e) {
        setError(e.message);
      } finally {
        setLoading(false);
      }
    }

    useEffect(() => {
      fetchHealth();
    }, []);

    useEffect(() => {
      if (autoRefresh) {
        intervalRef.current = setInterval(fetchHealth, 30000); // 30s
      } else if (intervalRef.current) {
        clearInterval(intervalRef.current);
        intervalRef.current = null;
      }
      return () => {
        if (intervalRef.current) clearInterval(intervalRef.current);
      };
    }, [autoRefresh]);

    if (auth.loading || (loading && !report)) {
      return (
        <div className="monitoring-container">
          <div className="loading-wrap">
            <Spinner />
            <div style={{ color: 'var(--color-text-muted)', fontSize: 13 }}>
              Chargement du rapport de santé...
            </div>
          </div>
        </div>
      );
    }

    if (auth.user && !['admin'].includes(auth.user.role)) {
      return (
        <div className="monitoring-container">
          <Box type="error" style={{ maxWidth: 500, margin: '80px auto', textAlign: 'center' }}>
            <h2>🚫 Accès refusé</h2>
            <p>Cette page est réservée aux administrateurs.</p>
          </Box>
        </div>
      );
    }

    if (!auth.user) {
      return (
        <div className="monitoring-container">
          <Box type="error" style={{ maxWidth: 500, margin: '80px auto', textAlign: 'center' }}>
            <h2>🔒 Connexion requise</h2>
            <Button variant="primary" onClick={() => window.location.href = '/login.html'}>
              Se connecter
            </Button>
          </Box>
        </div>
      );
    }

    if (error && !report) {
      return (
        <div className="monitoring-container">
          <Box type="error">⚠️ {error}</Box>
          <Button variant="primary" onClick={fetchHealth} style={{ marginTop: 16 }}>
            Réessayer
          </Button>
        </div>
      );
    }

    if (!report) return null;

    const status = report.status || 'ok';
    const checks = report.checks || {};

    return (
      <AdminLayout
        user={auth.user}
        activeRoute="/admin/monitoring.html"
        title="Monitoring système"
        subtitle={'Version ' + (report.version || '?') + ' · PHP ' + report.php}
      >
        <div className="monitoring-container">
          {/* Top controls */}
          <div className="monitoring-top">
            <div>
              <div style={{ fontSize: 13, color: 'var(--color-text-muted)' }}>
                Dernière MAJ : {lastUpdate ? lastUpdate.toLocaleTimeString('fr-FR') : '—'}
              </div>
            </div>
            <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
              <label className="auto-refresh">
                <input
                  type="checkbox"
                  checked={autoRefresh}
                  onChange={e => setAutoRefresh(e.target.checked)}
                />
                Auto-refresh 30s
              </label>
              <button
                className="refresh-btn"
                onClick={fetchHealth}
                disabled={loading}
              >
                🔄 {loading ? 'Actualisation...' : 'Actualiser'}
              </button>
            </div>
          </div>

          {/* Status banner */}
          <div className={`status-banner ${status}`}>
            <div className="status-banner-icon">{statusIcon(status)}</div>
            <div className="status-banner-text">
              <h2 className="status-banner-title">{statusMessage(status)}</h2>
              <div className="status-banner-meta">
                {Object.keys(checks).length} vérifications ·
                Durée : {report.check_duration_ms || report.duration_ms || 0}ms ·
                Uptime : {Math.round(report.uptime_sec || 0)}s
              </div>
            </div>
          </div>

          {/* Checks grid */}
          <div className="checks-grid">
            {checks.disk && <DiskCard data={checks.disk} />}
            {checks.memory && <MemoryCard data={checks.memory} />}
            {checks.filesystem && <FilesystemCard data={checks.filesystem} />}
            {checks.counters && <CountersCard data={checks.counters} />}
            {checks.backups && <BackupsCard data={checks.backups} />}
            {checks.logs && <LogsCard data={checks.logs} />}
            {checks.php && <PhpCard data={checks.php} />}
          </div>
        </div>
      </AdminLayout>
    );
  }

  // ========================================================================
  // Mount (immediate — Babel Standalone runs after DOMContentLoaded)
  // ========================================================================

  const rootElement = document.getElementById('root');
  if (rootElement) {
    ReactDOM.createRoot(rootElement).render(<MonitoringApp />);
  }

})(window);
