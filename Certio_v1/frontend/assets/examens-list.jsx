/* ============================================================================
   examens-list.jsx — Liste des examens avec actions

   Plateforme d'examens IPSSI — Phase P6.3

   Fonctionnalités :
     - Table des examens avec tri et filtres
     - Badges de status colorés
     - Actions rapides : publish/close/archive/edit/delete
     - Access code cliquable (copie clipboard)
     - Indicateur fenêtre d'ouverture (en cours / à venir / terminé)

   Composant exporté : window.ExamensList

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useMemo } = React;

  const STATUS_LABELS = {
    draft: '📝 Brouillon',
    published: '🚀 Publié',
    closed: '🔒 Clôturé',
    archived: '📦 Archivé',
  };

  function StatusBadge({ status }) {
    return (
      <span className={`status-badge status-badge--${status}`}>
        {STATUS_LABELS[status] || status}
      </span>
    );
  }

  function formatDate(iso) {
    if (!iso) return '—';
    try {
      const d = new Date(iso);
      return d.toLocaleString('fr-FR', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit',
      });
    } catch {
      return iso;
    }
  }

  function formatDuree(sec) {
    if (!sec) return '—';
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    if (h > 0) return `${h}h${m.toString().padStart(2, '0')}`;
    return `${m} min`;
  }

  function getWindowStatus(examen) {
    const now = Date.now();
    const open = new Date(examen.date_ouverture).getTime();
    const close = new Date(examen.date_cloture).getTime();

    if (now < open) {
      const diffMin = Math.floor((open - now) / 60000);
      if (diffMin < 60) return { label: `Ouvre dans ${diffMin}min`, color: '#d97706' };
      const diffH = Math.floor(diffMin / 60);
      if (diffH < 24) return { label: `Ouvre dans ${diffH}h`, color: '#d97706' };
      const diffD = Math.floor(diffH / 24);
      return { label: `Ouvre dans ${diffD}j`, color: '#d97706' };
    }
    if (now > close) return { label: 'Fermé', color: '#dc2626' };
    const diffMin = Math.floor((close - now) / 60000);
    if (diffMin < 60) return { label: `Ferme dans ${diffMin}min`, color: '#16a34a' };
    const diffH = Math.floor(diffMin / 60);
    return { label: `Ferme dans ${diffH}h`, color: '#16a34a' };
  }

  function ExamensList({ onEdit, onCreate }) {
    const { Button, Input, Select, Modal, useToast } = root.UI;
    const { useApi, useAuth } = root.UIHooks;
    const api = useApi();
    const { toast } = useToast();
    const { user } = useAuth();

    const [examens, setExamens] = useState([]);
    const [loading, setLoading] = useState(false);
    const [filters, setFilters] = useState({ status: '', search: '' });
    const [sortBy, setSortBy] = useState('date_ouverture_desc');
    const [confirmDelete, setConfirmDelete] = useState(null);
    const [confirmTransition, setConfirmTransition] = useState(null);
    const [transitioning, setTransitioning] = useState(false);

    const isAdmin = user?.role === 'admin';

    async function loadExamens() {
      setLoading(true);
      try {
        const qs = new URLSearchParams();
        if (filters.status) qs.set('status', filters.status);
        const res = await api.request('GET', `/api/examens?${qs.toString()}`);
        if (res.ok) {
          setExamens(res.data?.examens || []);
        } else {
          toast({ title: 'Erreur', message: res.error?.message || 'Chargement impossible', type: 'error' });
        }
      } catch (err) {
        toast({ title: 'Erreur inattendue', message: err.message || 'Chargement impossible', type: 'error' });
      }
      setLoading(false);
    }

    useEffect(() => { loadExamens(); }, [filters.status]);

    // Filtrage client (search) + tri
    const filtered = useMemo(() => {
      let result = [...examens];
      if (filters.search) {
        const q = filters.search.toLowerCase();
        result = result.filter(e =>
          (e.titre || '').toLowerCase().includes(q) ||
          (e.description || '').toLowerCase().includes(q) ||
          (e.id || '').toLowerCase().includes(q) ||
          (e.access_code || '').toLowerCase().includes(q)
        );
      }

      // Tri
      switch (sortBy) {
        case 'date_ouverture_desc':
          result.sort((a, b) => (b.date_ouverture || '').localeCompare(a.date_ouverture || ''));
          break;
        case 'date_ouverture_asc':
          result.sort((a, b) => (a.date_ouverture || '').localeCompare(b.date_ouverture || ''));
          break;
        case 'titre':
          result.sort((a, b) => (a.titre || '').localeCompare(b.titre || ''));
          break;
        case 'status':
          const order = { draft: 0, published: 1, closed: 2, archived: 3 };
          result.sort((a, b) => (order[a.status] ?? 9) - (order[b.status] ?? 9));
          break;
      }
      return result;
    }, [examens, filters.search, sortBy]);

    async function doTransition(examen, action) {
      setTransitioning(true);
      try {
        const res = await api.request('POST', `/api/examens/${examen.id}/${action}`);
        setTransitioning(false);
        setConfirmTransition(null);

        if (res.ok) {
          toast({
            title: 'Transition OK',
            message: `${examen.titre} → ${res.data?.new_status}`,
            type: 'success',
          });
          loadExamens();
        } else {
          toast({
            title: 'Erreur transition',
            message: res.error?.message || 'Impossible',
            type: 'error',
          });
        }
      } catch (err) {
        setTransitioning(false);
        setConfirmTransition(null);
        toast({ title: 'Erreur inattendue', message: err.message || 'Erreur transition', type: 'error' });
      }
    }

    async function doDelete(examen) {
      setConfirmDelete(null);
      try {
        const res = await api.request('DELETE', `/api/examens/${examen.id}`);
        if (res.ok) {
          toast({ title: 'Supprimé', message: examen.titre, type: 'success' });
          loadExamens();
        } else {
          toast({
            title: 'Erreur suppression',
            message: res.error?.message || 'Impossible',
            type: 'error',
          });
        }
      } catch (err) {
        toast({ title: 'Erreur inattendue', message: err.message || 'Erreur suppression', type: 'error' });
      }
    }

    function copyCode(code) {
      navigator.clipboard?.writeText(code).then(() => {
        toast({ title: 'Code copié', message: code, type: 'success' });
      });
    }

    // ==========================================================================
    // Render
    // ==========================================================================

    return (
      <div>
        {/* Toolbar */}
        <div style={{
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          marginBottom: 'var(--space-3)',
          display: 'flex',
          gap: 'var(--space-2)',
          alignItems: 'center',
          flexWrap: 'wrap',
        }}>
          <div style={{ flex: 1, minWidth: 200 }}>
            <Input
              value={filters.search}
              onChange={val => setFilters(f => ({ ...f, search: val }))}
              placeholder="🔍 Rechercher par titre, ID, code..."
            />
          </div>

          <Select
            value={filters.status}
            onChange={val => setFilters(f => ({ ...f, status: val }))}
            options={[
              { value: '', label: '— Tous les status —' },
              { value: 'draft', label: '📝 Brouillon' },
              { value: 'published', label: '🚀 Publié' },
              { value: 'closed', label: '🔒 Clôturé' },
              { value: 'archived', label: '📦 Archivé' },
            ]}
            style={{ width: 180 }}
          />

          <Select
            value={sortBy}
            onChange={val => setSortBy(val)}
            options={[
              { value: 'date_ouverture_desc', label: '📅 Date ↓' },
              { value: 'date_ouverture_asc', label: '📅 Date ↑' },
              { value: 'titre', label: '🔤 Titre' },
              { value: 'status', label: '📊 Status' },
            ]}
            style={{ width: 140 }}
          />

          <Button variant="ghost" size="sm" onClick={loadExamens}>
            🔄 Rafraîchir
          </Button>

          <Button variant="primary" onClick={onCreate}>
            ➕ Nouvel examen
          </Button>
        </div>

        {/* Info count */}
        <div style={{
          fontSize: 12,
          color: 'var(--color-text-muted)',
          marginBottom: 'var(--space-2)',
          padding: '0 4px',
        }}>
          {loading ? '⏳ Chargement...' : (
            <>
              <strong>{filtered.length}</strong> examen{filtered.length > 1 ? 's' : ''}
              {filtered.length !== examens.length && ` (sur ${examens.length})`}
              {isAdmin && ' · Admin : vous voyez TOUS les examens'}
            </>
          )}
        </div>

        {/* Table */}
        {filtered.length === 0 && !loading ? (
          <div style={{
            padding: 'var(--space-6)',
            textAlign: 'center',
            background: 'var(--color-bg-subtle)',
            borderRadius: 'var(--radius-md)',
            color: 'var(--color-text-muted)',
          }}>
            <div style={{ fontSize: 48, marginBottom: 12, opacity: 0.3 }}>📋</div>
            <h4 style={{ margin: '0 0 8px 0', color: 'var(--color-text)' }}>
              {examens.length === 0 ? 'Aucun examen' : 'Aucun résultat'}
            </h4>
            <p style={{ margin: 0 }}>
              {examens.length === 0
                ? 'Créez votre premier examen avec le bouton ci-dessus.'
                : 'Aucun examen ne correspond à vos filtres.'}
            </p>
          </div>
        ) : (
          <div style={{ overflowX: 'auto' }}>
            <table className="examens-table">
              <thead>
                <tr>
                  <th>Titre</th>
                  <th>Status</th>
                  <th>Code</th>
                  <th>Questions</th>
                  <th>Durée</th>
                  <th>Ouverture → Clôture</th>
                  <th>Fenêtre</th>
                  {isAdmin && <th>Créateur</th>}
                  <th style={{ textAlign: 'right' }}>Actions</th>
                </tr>
              </thead>
              <tbody>
                {filtered.map(e => {
                  const win = getWindowStatus(e);
                  const canPublish = e.status === 'draft';
                  const canClose = e.status === 'published';
                  const canArchive = ['closed', 'draft'].includes(e.status);
                  const canEdit = e.status !== 'archived';
                  const canDelete = e.status === 'draft';

                  return (
                    <tr key={e.id}>
                      <td>
                        <div style={{ fontWeight: 600 }}>{e.titre}</div>
                        <code style={{ fontSize: 10, color: 'var(--color-text-muted)' }}>{e.id}</code>
                      </td>
                      <td><StatusBadge status={e.status} /></td>
                      <td>
                        <span
                          className="access-code"
                          onClick={() => copyCode(e.access_code)}
                          title="Cliquer pour copier"
                        >
                          {e.access_code}
                        </span>
                      </td>
                      <td style={{ textAlign: 'center' }}>{e.questions?.length || 0}</td>
                      <td>{formatDuree(e.duree_sec)}</td>
                      <td style={{ fontSize: 11 }}>
                        <div>{formatDate(e.date_ouverture)}</div>
                        <div style={{ color: 'var(--color-text-muted)' }}>
                          → {formatDate(e.date_cloture)}
                        </div>
                      </td>
                      <td>
                        <span style={{
                          padding: '2px 8px',
                          fontSize: 10,
                          borderRadius: 4,
                          background: win.color + '20',
                          color: win.color,
                          fontWeight: 600,
                        }}>{win.label}</span>
                      </td>
                      {isAdmin && (
                        <td style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>
                          {e.created_by}
                        </td>
                      )}
                      <td style={{ textAlign: 'right' }}>
                        <div className="table-actions" style={{ justifyContent: 'flex-end' }}>
                          {canEdit && (
                            <button
                              className="table-action-btn"
                              onClick={() => onEdit(e)}
                              title="Éditer"
                            >✏️</button>
                          )}
                          {canPublish && (
                            <button
                              className="table-action-btn primary"
                              onClick={() => setConfirmTransition({ examen: e, action: 'publish' })}
                              title="Publier"
                            >🚀</button>
                          )}
                          {canClose && (
                            <button
                              className="table-action-btn"
                              onClick={() => setConfirmTransition({ examen: e, action: 'close' })}
                              title="Clôturer"
                            >🔒</button>
                          )}
                          {canArchive && (
                            <button
                              className="table-action-btn"
                              onClick={() => setConfirmTransition({ examen: e, action: 'archive' })}
                              title="Archiver"
                            >📦</button>
                          )}
                          {canDelete && (
                            <button
                              className="table-action-btn danger"
                              onClick={() => setConfirmDelete(e)}
                              title="Supprimer"
                            >🗑️</button>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}

        {/* Modal confirmation delete */}
        <Modal
          open={!!confirmDelete}
          onClose={() => setConfirmDelete(null)}
          title="⚠️ Supprimer cet examen ?"
        >
          {confirmDelete && (
            <>
              <p>Vous êtes sur le point de supprimer :</p>
              <div style={{
                padding: 'var(--space-3)',
                background: 'var(--color-bg-subtle)',
                borderRadius: 'var(--radius-md)',
                margin: 'var(--space-2) 0',
              }}>
                <strong>{confirmDelete.titre}</strong><br />
                <code style={{ fontSize: 11 }}>{confirmDelete.id}</code>
              </div>
              <p style={{ color: '#dc2626' }}>
                ⚠️ Cette action est irréversible. Seuls les examens en status <strong>draft</strong> peuvent être supprimés.
              </p>
              <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end', marginTop: 'var(--space-3)' }}>
                <Button variant="ghost" onClick={() => setConfirmDelete(null)}>Annuler</Button>
                <Button variant="danger" onClick={() => doDelete(confirmDelete)}>
                  🗑️ Supprimer définitivement
                </Button>
              </div>
            </>
          )}
        </Modal>

        {/* Modal confirmation transition */}
        <Modal
          open={!!confirmTransition}
          onClose={() => setConfirmTransition(null)}
          title={
            confirmTransition?.action === 'publish' ? '🚀 Publier cet examen ?' :
            confirmTransition?.action === 'close' ? '🔒 Clôturer cet examen ?' :
            '📦 Archiver cet examen ?'
          }
        >
          {confirmTransition && (
            <>
              <div style={{
                padding: 'var(--space-3)',
                background: 'var(--color-bg-subtle)',
                borderRadius: 'var(--radius-md)',
                margin: 'var(--space-2) 0',
              }}>
                <strong>{confirmTransition.examen.titre}</strong>
              </div>

              {confirmTransition.action === 'publish' && (
                <div>
                  <p>L'examen sera <strong>visible aux étudiants</strong> via son code d'accès pendant la fenêtre d'ouverture.</p>
                  <p style={{ fontSize: 13, color: 'var(--color-text-muted)' }}>
                    Une fois publié, seuls le titre, la description et la date de clôture restent modifiables.
                  </p>
                </div>
              )}

              {confirmTransition.action === 'close' && (
                <div>
                  <p>L'examen ne sera <strong>plus accessible aux étudiants</strong>. Les passages en cours continueront.</p>
                </div>
              )}

              {confirmTransition.action === 'archive' && (
                <div>
                  <p>L'examen sera <strong>archivé</strong>. Il ne pourra plus être modifié. Les données restent conservées pour l'historique.</p>
                </div>
              )}

              <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end', marginTop: 'var(--space-3)' }}>
                <Button variant="ghost" onClick={() => setConfirmTransition(null)}>
                  Annuler
                </Button>
                <Button
                  variant="primary"
                  onClick={() => doTransition(confirmTransition.examen, confirmTransition.action)}
                  disabled={transitioning}
                >
                  {transitioning ? '⏳...' : 'Confirmer'}
                </Button>
              </div>
            </>
          )}
        </Modal>
      </div>
    );
  }

  root.ExamensList = ExamensList;

})(window);
