/* ============================================================================
   examens.jsx — Application principale d'administration des examens

   Plateforme d'examens IPSSI — Phase P6.3

   Responsabilités :
     - Auth guard (redirect si non auth ou non prof/admin)
     - AdminLayout avec sidebar
     - 3 onglets : Liste / Créer / Stats (admin only)
     - Handlers onEdit/onCreate/onSaved pour coordination entre composants

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect } = React;
  const { ExamensList, ExamensCreate, ExamensStats } = window;

  // ==========================================================================
  // Auth Guard
  // ==========================================================================

  function AuthGuard({ children }) {
    const { user, loading } = root.UIHooks.useAuth();

    useEffect(() => {
      if (loading) return;
      if (!user) {
        window.location.href = '/login.html';
        return;
      }
      const role = user.role;
      if (role !== 'admin' && role !== 'enseignant') {
        window.location.href = '/admin/dashboard.html';
      }
    }, [user, loading]);

    if (loading) {
      return (
        <div style={{ padding: 'var(--space-6)', textAlign: 'center' }}>
          <root.UI.Spinner />
          <p style={{ marginTop: 'var(--space-3)', color: 'var(--color-text-muted)' }}>
            Vérification de l'authentification...
          </p>
        </div>
      );
    }

    if (!user || (user.role !== 'admin' && user.role !== 'enseignant')) {
      return null;
    }

    return children;
  }

  // ==========================================================================
  // Contenu principal avec tabs
  // ==========================================================================

  function ExamensContent() {
    const { useAuth } = root.UIHooks;
    const { user } = useAuth();
    const { ProfLayout } = root.UILayouts;
    const [activeTab, setActiveTab] = useState('list');
    const [editingExamen, setEditingExamen] = useState(null);

    const isAdmin = user?.role === 'admin';

    const tabs = [
      { id: 'list', label: '📋 Liste', component: ExamensList, enabled: true },
      { id: 'create', label: editingExamen ? '✏️ Modifier' : '➕ Créer', component: ExamensCreate, enabled: true },
      { id: 'stats', label: '📊 Statistiques', component: ExamensStats, enabled: isAdmin, tooltip: isAdmin ? null : 'Admin uniquement' },
    ];

    function handleEdit(examen) {
      setEditingExamen(examen);
      setActiveTab('create');
    }

    function handleCreate() {
      setEditingExamen(null);
      setActiveTab('create');
    }

    function handleSaved(examen) {
      setEditingExamen(null);
      setActiveTab('list');
    }

    function handleCancel() {
      setEditingExamen(null);
      setActiveTab('list');
    }

    // Component props
    const componentProps = {
      list: { onEdit: handleEdit, onCreate: handleCreate },
      create: { editingExamen, onSaved: handleSaved, onCancel: handleCancel },
      stats: {},
    };

    const currentTab = tabs.find(t => t.id === activeTab);
    const ActiveComponent = currentTab?.component;

    return (
      <ProfLayout
        user={user}
        activeRoute="/admin/examens.html"
        title="Gestion des examens"
        subtitle="Créer, publier, et suivre les examens de la plateforme IPSSI."
      >
        <div className="examens-container">
          {/* Tab navigation */}
          <div className="examens-tab-nav">
            {tabs.map(tab => (
              <button
                key={tab.id}
                className={`examens-tab-btn ${activeTab === tab.id ? 'active' : ''}`}
                onClick={() => tab.enabled && setActiveTab(tab.id)}
                disabled={!tab.enabled}
                title={tab.tooltip}
                style={{
                  opacity: tab.enabled ? 1 : 0.4,
                  cursor: tab.enabled ? 'pointer' : 'not-allowed',
                }}
              >
                {tab.label}
              </button>
            ))}
          </div>

          {/* Active tab content */}
          {ActiveComponent && (
            <ActiveComponent {...(componentProps[activeTab] || {})} />
          )}
        </div>
      </ProfLayout>
    );
  }

  // ==========================================================================
  // Root app
  // ==========================================================================

  function ExamensApp() {
    const { ToastProvider, ErrorBoundary } = root.UI;
    return (
      <ToastProvider>
        <ErrorBoundary>
          <AuthGuard>
            <ExamensContent />
          </AuthGuard>
        </ErrorBoundary>
      </ToastProvider>
    );
  }

  // Mount (immediate — Babel Standalone runs after DOMContentLoaded)
  const rootElement = document.getElementById('root');
  if (rootElement) {
    ReactDOM.createRoot(rootElement).render(<ExamensApp />);
  }

})(window);
