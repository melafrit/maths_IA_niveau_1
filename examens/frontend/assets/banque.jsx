/* ============================================================================
   banque.jsx — Page principale de la banque de questions

   Plateforme d'examens IPSSI — Phase P5

   Assemble les composants de gestion de la banque :
     ✅ Browser (Phase P5.1 - livré)
     ⏳ Editor (Phase P5.2 - à venir)
     ⏳ Exam Generator (Phase P5.3 - à venir)
     ⏳ Search & Filters (Phase P5.4 - à venir)
     ⏳ Dashboard Stats (Phase P5.5 - à venir)

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function () {
  'use strict';

  const { useState, useEffect } = React;

  // Composants UI du design system P2
  const {
    Button, Card, Box, Badge,
    Stat, EmptyState,
    ToastProvider, useToast,
  } = window.UI;

  // Hooks
  const { useAuth, useApi, useTranslation, useTheme } = window.UIHooks;

  // Layouts
  const { AdminLayout } = window.UILayouts;

  // Composant Browser (phase P5.1)
  const { BanqueBrowser } = window;

  // ==========================================================================
  // Contenu principal (tabs)
  // ==========================================================================

  function BanqueContent() {
    const [activeTab, setActiveTab] = useState('browser');

    const tabs = [
      { id: 'browser', label: '🔍 Navigateur', component: BanqueBrowser, enabled: true },
      { id: 'editor', label: '✏️ Éditeur', enabled: false, tooltip: 'Phase P5.2' },
      { id: 'generator', label: '🎲 Générateur d\'examens', enabled: false, tooltip: 'Phase P5.3' },
      { id: 'search', label: '🔎 Recherche avancée', enabled: false, tooltip: 'Phase P5.4' },
      { id: 'stats', label: '📊 Statistiques', enabled: false, tooltip: 'Phase P5.5' },
    ];

    const currentTab = tabs.find(t => t.id === activeTab);
    const ActiveComponent = currentTab?.component;

    return (
      <div className="banque-container">
        <div className="banque-header">
          <div>
            <h1 style={{ margin: 0, fontSize: 'var(--text-2xl)', display: 'flex', alignItems: 'center', gap: 'var(--space-2)' }}>
              📚 Banque de questions
            </h1>
            <p style={{ margin: 'var(--space-1) 0 0 0', color: 'var(--color-text-muted)' }}>
              Gérer les questions de la plateforme d'examens
            </p>
          </div>
        </div>

        {/* Tabs navigation */}
        <div className="banque-tab-nav" style={{ marginBottom: 'var(--space-4)' }}>
          {tabs.map(tab => (
            <button
              key={tab.id}
              className={`banque-tab-btn ${activeTab === tab.id ? 'active' : ''}`}
              onClick={() => tab.enabled && setActiveTab(tab.id)}
              disabled={!tab.enabled}
              title={tab.tooltip || ''}
              style={{
                opacity: tab.enabled ? 1 : 0.4,
                cursor: tab.enabled ? 'pointer' : 'not-allowed',
              }}
            >
              {tab.label}
              {!tab.enabled && (
                <span style={{
                  marginLeft: 6,
                  fontSize: 10,
                  padding: '1px 6px',
                  background: 'var(--color-text-muted)',
                  color: 'white',
                  borderRadius: 3,
                }}>BIENTÔT</span>
              )}
            </button>
          ))}
        </div>

        {/* Contenu de l'onglet actif */}
        {ActiveComponent ? (
          <ActiveComponent />
        ) : (
          <EmptyState
            icon="🚧"
            title="Composant en construction"
            message={`L'onglet "${currentTab?.label}" sera disponible dans la ${currentTab?.tooltip || 'phase suivante'}.`}
          />
        )}
      </div>
    );
  }

  // ==========================================================================
  // Root App avec Layout + Toast
  // ==========================================================================

  function BanquePage() {
    const { t } = useTranslation();
    const { user, isAuthenticated, loading } = useAuth();

    // Redirect si pas authentifié ou pas admin
    useEffect(() => {
      if (!loading) {
        if (!isAuthenticated) {
          window.location.href = '/login.html';
        } else if (user && user.role !== 'admin') {
          window.location.href = '/dashboard_temp.html';
        }
      }
    }, [loading, isAuthenticated, user]);

    if (loading || !user) {
      return (
        <div style={{
          minHeight: '100vh',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          background: 'var(--color-bg)',
        }}>
          <div style={{ textAlign: 'center' }}>
            <div style={{ fontSize: 'var(--text-2xl)', marginBottom: 'var(--space-3)' }}>📚</div>
            <p style={{ color: 'var(--color-text-muted)' }}>Chargement de la banque...</p>
          </div>
        </div>
      );
    }

    if (user.role !== 'admin') {
      return (
        <div style={{ padding: 'var(--space-6)', textAlign: 'center' }}>
          <h2>⛔ Accès refusé</h2>
          <p>Cette page est réservée aux administrateurs.</p>
        </div>
      );
    }

    return (
      <AdminLayout user={user} activeRoute="/admin/banque.html">
        <BanqueContent />
      </AdminLayout>
    );
  }

  // ==========================================================================
  // Mount
  // ==========================================================================

  function App() {
    return (
      <ToastProvider>
        <BanquePage />
      </ToastProvider>
    );
  }

  const rootEl = document.getElementById('root');
  if (rootEl) {
    ReactDOM.createRoot(rootEl).render(<App />);
  }

})();
