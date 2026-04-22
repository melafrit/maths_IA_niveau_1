/* ============================================================================
   banque-search.jsx — Recherche avancée et filtres multi-critères

   Plateforme d'examens IPSSI — Phase P5.4

   Fonctionnalités :
     - Recherche full-text avec debounce 300ms
     - Filtres combinables : module/chapitre/niveaux/types/tags
     - Surlignage des occurrences dans les résultats
     - Pagination 20 par page
     - Modal de détail complet (réutilise MathText)
     - Sauvegarde de recherches fréquentes (localStorage)
     - Score de pertinence affiché

   Composant exporté : window.BanqueSearch

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useCallback, useMemo, useRef } = React;

  // Réutilisation
  const MathText = root.MathText;
  const LevelBadge = root.LevelBadge;

  const LEVELS = ['facile', 'moyen', 'difficile', 'expert'];
  const TYPES = ['conceptuel', 'calcul', 'code', 'formule'];
  const SAVED_SEARCHES_KEY = 'banque_saved_searches';
  const PAGE_SIZE = 20;

  // ==========================================================================
  // Helper : surlignage des matches
  // ==========================================================================

  function highlightText(text, query) {
    if (!text || !query || query.trim() === '') return text;
    try {
      const escaped = query.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const regex = new RegExp(`(${escaped})`, 'gi');
      return text.replace(regex, '<mark style="background: rgba(234, 179, 8, 0.3); padding: 1px 3px; border-radius: 3px;">$1</mark>');
    } catch (e) {
      return text;
    }
  }

  // ==========================================================================
  // Composant : chips de filtres multi-sélect
  // ==========================================================================

  function MultiSelectChips({ options, selected, onChange, getLabel, getColor, icon }) {
    return (
      <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
        {options.map(opt => {
          const isSelected = selected.includes(opt);
          const color = getColor ? getColor(opt) : 'var(--color-primary)';
          return (
            <button
              key={opt}
              onClick={() => {
                onChange(
                  isSelected
                    ? selected.filter(x => x !== opt)
                    : [...selected, opt]
                );
              }}
              style={{
                padding: '4px 10px',
                fontSize: 12,
                background: isSelected ? color : 'var(--color-bg-elevated)',
                color: isSelected ? 'white' : 'var(--color-text)',
                border: `1px solid ${isSelected ? color : 'var(--color-border)'}`,
                borderRadius: 'var(--radius-md)',
                cursor: 'pointer',
                transition: 'all 0.15s',
                fontWeight: isSelected ? 600 : 400,
              }}
            >
              {isSelected ? '✓ ' : ''}{icon || ''}{getLabel ? getLabel(opt) : opt}
            </button>
          );
        })}
      </div>
    );
  }

  // ==========================================================================
  // Composant : Modal détail question
  // ==========================================================================

  function QuestionDetailModal({ question, onClose, query }) {
    if (!question) return null;
    const { Modal, Button } = root.UI;
    const letters = ['A', 'B', 'C', 'D'];

    return (
      <Modal
        isOpen={!!question}
        onClose={onClose}
        title={`Question ${question.id}`}
        size="lg"
      >
        <div style={{ marginBottom: 'var(--space-3)', display: 'flex', gap: 8, flexWrap: 'wrap' }}>
          <LevelBadge level={question.difficulte} />
          <span style={{
            padding: '2px 8px',
            background: 'var(--color-bg-subtle)',
            borderRadius: 999,
            fontSize: 12,
          }}>{question.type}</span>
          <span style={{
            padding: '2px 8px',
            background: 'var(--color-primary-subtle)',
            color: 'var(--color-primary)',
            borderRadius: 999,
            fontSize: 11,
          }}>
            📍 {question._module} / {question._chapitre} / {question._theme}
          </span>
        </div>

        <div style={{
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          marginBottom: 'var(--space-3)',
          borderLeft: '3px solid var(--color-primary)',
        }}>
          <div style={{ fontSize: 11, textTransform: 'uppercase', fontWeight: 600, color: 'var(--color-text-muted)', marginBottom: 6 }}>
            Énoncé
          </div>
          <MathText text={question.enonce} as="div" />
        </div>

        <div style={{ display: 'flex', flexDirection: 'column', gap: 8, marginBottom: 'var(--space-3)' }}>
          {question.options.map((opt, i) => (
            <div
              key={i}
              style={{
                padding: '8px 12px',
                borderLeft: `3px solid ${i === question.correct ? '#16a34a' : 'var(--color-border)'}`,
                background: i === question.correct ? 'rgba(34, 197, 94, 0.08)' : 'var(--color-bg-elevated)',
                borderRadius: 4,
                display: 'flex',
                gap: 8,
              }}
            >
              <strong style={{ color: i === question.correct ? '#16a34a' : 'var(--color-primary)', minWidth: 20 }}>
                {letters[i]}
              </strong>
              <MathText text={opt} as="span" style={{ flex: 1 }} />
              {i === question.correct && <span style={{ color: '#16a34a' }}>✓</span>}
            </div>
          ))}
        </div>

        {/* Hint, explanation, traps (accordéon simple) */}
        <details style={{ marginBottom: 'var(--space-2)' }}>
          <summary style={{ cursor: 'pointer', fontWeight: 600, padding: 8 }}>💡 Indice (hint)</summary>
          <div style={{ padding: 8, fontSize: 'var(--text-sm)' }}>
            <MathText text={question.hint} as="div" />
          </div>
        </details>

        <details style={{ marginBottom: 'var(--space-2)' }}>
          <summary style={{ cursor: 'pointer', fontWeight: 600, padding: 8 }}>📖 Explication détaillée</summary>
          <div style={{ padding: 8, fontSize: 'var(--text-sm)' }}>
            <MathText text={question.explanation} as="div" />
          </div>
        </details>

        <details style={{ marginBottom: 'var(--space-2)' }}>
          <summary style={{ cursor: 'pointer', fontWeight: 600, padding: 8 }}>⚠️ Pièges à éviter</summary>
          <div style={{ padding: 8, fontSize: 'var(--text-sm)' }}>
            <MathText text={question.traps} as="div" />
          </div>
        </details>

        <div style={{
          marginTop: 'var(--space-3)',
          padding: 8,
          background: 'var(--color-bg-subtle)',
          borderRadius: 4,
          fontSize: 11,
        }}>
          <strong>Tags :</strong> {(question.tags || []).join(', ') || '—'}<br />
          <strong>Référence :</strong> {question.references}
        </div>

        <div style={{ marginTop: 'var(--space-3)', display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
          <Button variant="ghost" onClick={() => {
            navigator.clipboard?.writeText(question.id);
          }}>📋 Copier ID</Button>
          <Button variant="primary" onClick={onClose}>Fermer</Button>
        </div>
      </Modal>
    );
  }

  // ==========================================================================
  // Composant principal : BanqueSearch
  // ==========================================================================

  function BanqueSearch() {
    const { Button, Input, Select, Box, useToast } = root.UI;
    const { useApi, useDebounce } = root.UIHooks;
    const api = useApi();
    const { toast } = useToast();

    // État recherche
    const [query, setQuery] = useState('');
    const debouncedQuery = useDebounce(query, 300);

    // État filtres
    const [filters, setFilters] = useState({
      module: '',
      chapitre: '',
      levels: [],
      types: [],
      tags: [],
    });

    // Lookups
    const [availableModules, setAvailableModules] = useState([]);
    const [availableChapitres, setAvailableChapitres] = useState([]);
    const [availableTags, setAvailableTags] = useState([]);

    // Résultats
    const [results, setResults] = useState([]);
    const [totalResults, setTotalResults] = useState(0);
    const [searching, setSearching] = useState(false);
    const [page, setPage] = useState(0);
    const [selectedQuestion, setSelectedQuestion] = useState(null);

    // Recherches sauvegardées
    const [savedSearches, setSavedSearches] = useState([]);
    const [showSaved, setShowSaved] = useState(false);

    // Charger lookups
    useEffect(() => {
      async function fetch() {
        const res = await api.request('GET', '/api/banque/modules');
        if (res.ok) setAvailableModules(res.data?.modules || []);
      }
      fetch();
      // Charger recherches sauvegardées
      try {
        const saved = localStorage.getItem(SAVED_SEARCHES_KEY);
        if (saved) setSavedSearches(JSON.parse(saved));
      } catch (e) {}
    }, []);

    useEffect(() => {
      async function fetch() {
        if (!filters.module) {
          setAvailableChapitres([]);
          return;
        }
        const res = await api.request('GET', `/api/banque/${filters.module}/chapitres`);
        if (res.ok) setAvailableChapitres(res.data?.chapitres || []);
      }
      fetch();
    }, [filters.module]);

    // Charger tous les tags existants (une fois)
    useEffect(() => {
      async function fetchAllTags() {
        const res = await api.request('GET', '/api/banque/questions?limit=1000');
        if (res.ok) {
          const allTags = new Set();
          (res.data?.questions || []).forEach(q => {
            (q.tags || []).forEach(t => allTags.add(t));
          });
          setAvailableTags(Array.from(allTags).sort());
        }
      }
      fetchAllTags();
    }, []);

    // ==========================================================================
    // Exécution recherche
    // ==========================================================================

    const hasActiveFilters = useMemo(() => {
      return filters.module || filters.chapitre || filters.levels.length > 0 || filters.types.length > 0 || filters.tags.length > 0;
    }, [filters]);

    const hasSearch = debouncedQuery.trim().length > 0;

    useEffect(() => {
      async function doSearch() {
        if (!hasSearch && !hasActiveFilters) {
          setResults([]);
          setTotalResults(0);
          return;
        }

        setSearching(true);

        try {
          let res;
          if (hasSearch) {
            // Recherche full-text via POST /search
            const body = {
              query: debouncedQuery,
              filters: {},
              fields: ['enonce', 'tags', 'id', 'explanation'],
              limit: 500,
            };
            if (filters.module) body.filters.module = filters.module;
            if (filters.chapitre) body.filters.chapitre = filters.chapitre;
            if (filters.levels.length > 0) body.filters.difficulte = filters.levels;
            if (filters.types.length > 0) body.filters.type = filters.types;
            if (filters.tags.length > 0) body.filters.tags = filters.tags;

            res = await api.request('POST', '/api/banque/search', body);
            if (res.ok) {
              const allResults = res.data?.results || [];
              setResults(allResults);
              setTotalResults(allResults.length);
            }
          } else {
            // Filtres seulement via GET /questions
            const qs = new URLSearchParams();
            if (filters.module) qs.set('module', filters.module);
            if (filters.chapitre) qs.set('chapitre', filters.chapitre);
            if (filters.levels.length > 0) qs.set('difficulte', filters.levels.join(','));
            if (filters.types.length > 0) qs.set('type', filters.types.join(','));
            if (filters.tags.length > 0) qs.set('tags', filters.tags.join(','));
            qs.set('limit', '500');

            res = await api.request('GET', `/api/banque/questions?${qs.toString()}`);
            if (res.ok) {
              setResults(res.data?.questions || []);
              setTotalResults(res.data?.total || 0);
            }
          }
          setPage(0);
        } catch (e) {
          toast({ title: 'Erreur recherche', message: e.message, type: 'error' });
        }
        setSearching(false);
      }
      doSearch();
    }, [debouncedQuery, filters.module, filters.chapitre, filters.levels.join(','), filters.types.join(','), filters.tags.join(',')]);

    // ==========================================================================
    // Pagination
    // ==========================================================================

    const paginated = useMemo(() => {
      const start = page * PAGE_SIZE;
      return results.slice(start, start + PAGE_SIZE);
    }, [results, page]);

    const totalPages = Math.ceil(results.length / PAGE_SIZE);

    // ==========================================================================
    // Actions : reset / save / load
    // ==========================================================================

    function resetAll() {
      setQuery('');
      setFilters({ module: '', chapitre: '', levels: [], types: [], tags: [] });
      setResults([]);
      setPage(0);
    }

    function saveCurrentSearch() {
      if (!hasSearch && !hasActiveFilters) {
        toast({ title: 'Rien à sauvegarder', message: 'Définissez au moins un critère', type: 'warning' });
        return;
      }
      const name = prompt('Nom de cette recherche ?');
      if (!name) return;

      const entry = {
        name: name.trim(),
        query,
        filters,
        savedAt: new Date().toISOString(),
      };
      const updated = [entry, ...savedSearches.filter(s => s.name !== entry.name)].slice(0, 20);
      setSavedSearches(updated);
      try {
        localStorage.setItem(SAVED_SEARCHES_KEY, JSON.stringify(updated));
        toast({ title: 'Recherche sauvegardée', message: name, type: 'success' });
      } catch (e) {
        toast({ title: 'Erreur sauvegarde', message: 'localStorage non disponible', type: 'error' });
      }
    }

    function loadSavedSearch(entry) {
      setQuery(entry.query || '');
      setFilters(entry.filters || { module: '', chapitre: '', levels: [], types: [], tags: [] });
      setShowSaved(false);
      toast({ title: 'Recherche chargée', message: entry.name, type: 'success' });
    }

    function deleteSavedSearch(name) {
      const updated = savedSearches.filter(s => s.name !== name);
      setSavedSearches(updated);
      try { localStorage.setItem(SAVED_SEARCHES_KEY, JSON.stringify(updated)); } catch (e) {}
    }

    const levelColors = {
      facile: '#16a34a',
      moyen: '#ca8a04',
      difficile: '#ea580c',
      expert: '#dc2626',
    };

    // ==========================================================================
    // Render
    // ==========================================================================

    return (
      <div>
        <h3 style={{ marginTop: 0, display: 'flex', alignItems: 'center', gap: 8 }}>
          🔎 Recherche avancée & filtres
        </h3>
        <p style={{ color: 'var(--color-text-muted)', marginBottom: 'var(--space-4)' }}>
          Retrouvez rapidement des questions dans la banque grâce à la recherche full-text et aux filtres combinables.
        </p>

        {/* Barre de recherche */}
        <div style={{
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          marginBottom: 'var(--space-3)',
        }}>
          <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
            <div style={{ flex: 1, position: 'relative' }}>
              <span style={{
                position: 'absolute',
                left: 12,
                top: '50%',
                transform: 'translateY(-50%)',
                color: 'var(--color-text-muted)',
                pointerEvents: 'none',
              }}>🔍</span>
              <Input
                placeholder="Rechercher dans les énoncés, tags, IDs, explications..."
                value={query}
                onChange={e => setQuery(e.target.value)}
                style={{ paddingLeft: 36 }}
              />
            </div>
            {(hasSearch || hasActiveFilters) && (
              <Button variant="ghost" onClick={resetAll}>✕ Réinitialiser</Button>
            )}
            <Button variant="ghost" onClick={saveCurrentSearch} disabled={!hasSearch && !hasActiveFilters}>
              ⭐ Sauver
            </Button>
            <Button
              variant={showSaved ? 'primary' : 'ghost'}
              onClick={() => setShowSaved(!showSaved)}
            >
              📚 Sauvées ({savedSearches.length})
            </Button>
          </div>

          {/* Recherches sauvegardées */}
          {showSaved && savedSearches.length > 0 && (
            <div style={{ marginTop: 'var(--space-3)' }}>
              <div style={{ fontSize: 'var(--text-sm)', fontWeight: 600, marginBottom: 6 }}>
                Mes recherches sauvegardées :
              </div>
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
                {savedSearches.map(s => (
                  <div
                    key={s.name}
                    style={{
                      padding: '4px 10px 4px 12px',
                      background: 'var(--color-bg-elevated)',
                      border: '1px solid var(--color-border)',
                      borderRadius: 999,
                      fontSize: 12,
                      display: 'flex',
                      alignItems: 'center',
                      gap: 6,
                    }}
                  >
                    <span
                      onClick={() => loadSavedSearch(s)}
                      style={{ cursor: 'pointer', fontWeight: 500 }}
                      title={`Query: "${s.query}" | ${new Date(s.savedAt).toLocaleDateString()}`}
                    >⭐ {s.name}</span>
                    <button
                      onClick={() => deleteSavedSearch(s.name)}
                      style={{
                        background: 'none',
                        border: 'none',
                        cursor: 'pointer',
                        color: '#dc2626',
                        padding: 0,
                        fontSize: 14,
                        lineHeight: 1,
                      }}
                    >×</button>
                  </div>
                ))}
              </div>
            </div>
          )}
          {showSaved && savedSearches.length === 0 && (
            <div style={{ marginTop: 12, fontSize: 12, color: 'var(--color-text-muted)' }}>
              Aucune recherche sauvegardée. Utilisez ⭐ Sauver pour enregistrer la recherche actuelle.
            </div>
          )}
        </div>

        {/* Filtres multi-critères */}
        <div style={{
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          marginBottom: 'var(--space-4)',
        }}>
          <div style={{ fontSize: 'var(--text-sm)', fontWeight: 600, marginBottom: 'var(--space-2)' }}>
            🎛️ Filtres
            {hasActiveFilters && (
              <span style={{
                marginLeft: 8,
                padding: '1px 8px',
                background: 'var(--color-primary)',
                color: 'white',
                borderRadius: 999,
                fontSize: 10,
              }}>
                {[
                  filters.module && 'module',
                  filters.chapitre && 'chapitre',
                  filters.levels.length > 0 && `${filters.levels.length} niveau(x)`,
                  filters.types.length > 0 && `${filters.types.length} type(s)`,
                  filters.tags.length > 0 && `${filters.tags.length} tag(s)`,
                ].filter(Boolean).join(' · ')}
              </span>
            )}
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--space-2)', marginBottom: 'var(--space-2)' }}>
            <div>
              <label style={{ fontSize: 11, fontWeight: 600, display: 'block', marginBottom: 4 }}>Module</label>
              <Select
                value={filters.module}
                onChange={e => setFilters(f => ({ ...f, module: e.target.value, chapitre: '' }))}
                options={[
                  { value: '', label: '— Tous —' },
                  ...availableModules.map(m => ({ value: m, label: m })),
                ]}
              />
            </div>
            <div>
              <label style={{ fontSize: 11, fontWeight: 600, display: 'block', marginBottom: 4 }}>Chapitre</label>
              <Select
                value={filters.chapitre}
                onChange={e => setFilters(f => ({ ...f, chapitre: e.target.value }))}
                options={[
                  { value: '', label: '— Tous —' },
                  ...availableChapitres.map(c => ({ value: c, label: c })),
                ]}
                disabled={!filters.module}
              />
            </div>
          </div>

          <div style={{ marginBottom: 'var(--space-2)' }}>
            <label style={{ fontSize: 11, fontWeight: 600, display: 'block', marginBottom: 4 }}>
              Niveaux (multi)
            </label>
            <MultiSelectChips
              options={LEVELS}
              selected={filters.levels}
              onChange={v => setFilters(f => ({ ...f, levels: v }))}
              getColor={l => levelColors[l]}
              getLabel={l => l}
              icon=""
            />
          </div>

          <div style={{ marginBottom: 'var(--space-2)' }}>
            <label style={{ fontSize: 11, fontWeight: 600, display: 'block', marginBottom: 4 }}>
              Types (multi)
            </label>
            <MultiSelectChips
              options={TYPES}
              selected={filters.types}
              onChange={v => setFilters(f => ({ ...f, types: v }))}
              getLabel={t => t}
            />
          </div>

          {availableTags.length > 0 && (
            <div>
              <label style={{ fontSize: 11, fontWeight: 600, display: 'block', marginBottom: 4 }}>
                Tags (multi — {availableTags.length} disponibles)
              </label>
              <div style={{ maxHeight: 110, overflow: 'auto', padding: 4, border: '1px solid var(--color-border)', borderRadius: 'var(--radius-md)', background: 'var(--color-bg-elevated)' }}>
                <MultiSelectChips
                  options={availableTags}
                  selected={filters.tags}
                  onChange={v => setFilters(f => ({ ...f, tags: v }))}
                  getLabel={t => t}
                />
              </div>
            </div>
          )}
        </div>

        {/* Résultats */}
        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: 'var(--space-3)',
        }}>
          <div style={{ fontSize: 'var(--text-lg)', fontWeight: 700 }}>
            {searching ? (
              <span style={{ color: 'var(--color-text-muted)' }}>⏳ Recherche...</span>
            ) : hasSearch || hasActiveFilters ? (
              <>
                {results.length === 0 ? '❌ Aucun résultat' : `✅ ${results.length} résultat${results.length > 1 ? 's' : ''}`}
                {hasSearch && query.trim() && ` pour « ${debouncedQuery} »`}
              </>
            ) : (
              <span style={{ color: 'var(--color-text-muted)' }}>
                Commencez à taper ou cochez des filtres
              </span>
            )}
          </div>

          {totalPages > 1 && (
            <div style={{ display: 'flex', gap: 6, alignItems: 'center' }}>
              <Button variant="ghost" size="sm" onClick={() => setPage(p => Math.max(0, p - 1))} disabled={page === 0}>
                ← Précédent
              </Button>
              <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-muted)' }}>
                Page {page + 1} / {totalPages}
              </span>
              <Button variant="ghost" size="sm" onClick={() => setPage(p => Math.min(totalPages - 1, p + 1))} disabled={page >= totalPages - 1}>
                Suivant →
              </Button>
            </div>
          )}
        </div>

        {/* Liste des résultats */}
        {paginated.length > 0 ? (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-2)' }}>
            {paginated.map(q => (
              <ResultCard
                key={q.id}
                question={q}
                query={debouncedQuery}
                onSelect={() => setSelectedQuestion(q)}
              />
            ))}
          </div>
        ) : (!searching && (hasSearch || hasActiveFilters)) ? (
          <div style={{
            padding: 'var(--space-5)',
            textAlign: 'center',
            background: 'var(--color-bg-subtle)',
            borderRadius: 'var(--radius-md)',
            color: 'var(--color-text-muted)',
          }}>
            <div style={{ fontSize: 48, opacity: 0.3, marginBottom: 8 }}>🔎</div>
            <h4 style={{ margin: '0 0 4px 0', color: 'var(--color-text)' }}>Aucune question trouvée</h4>
            <p style={{ margin: 0, fontSize: 'var(--text-sm)' }}>
              Essayez des mots-clés différents ou élargissez vos filtres.
            </p>
          </div>
        ) : (!searching && (
          <div style={{
            padding: 'var(--space-5)',
            textAlign: 'center',
            background: 'var(--color-bg-subtle)',
            borderRadius: 'var(--radius-md)',
            color: 'var(--color-text-muted)',
          }}>
            <div style={{ fontSize: 48, opacity: 0.3, marginBottom: 8 }}>🔍</div>
            <h4 style={{ margin: '0 0 4px 0', color: 'var(--color-text)' }}>Prêt à chercher ?</h4>
            <p style={{ margin: 0, fontSize: 'var(--text-sm)' }}>
              Tapez un terme ou appliquez des filtres pour voir les questions.
            </p>
          </div>
        ))}

        {/* Pagination bas de page (redondante) */}
        {totalPages > 1 && (
          <div style={{ marginTop: 'var(--space-3)', display: 'flex', justifyContent: 'center', gap: 6, alignItems: 'center' }}>
            <Button variant="ghost" size="sm" onClick={() => setPage(0)} disabled={page === 0}>⏮️</Button>
            <Button variant="ghost" size="sm" onClick={() => setPage(p => Math.max(0, p - 1))} disabled={page === 0}>←</Button>
            <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-muted)', padding: '0 12px' }}>
              {page + 1} / {totalPages}
            </span>
            <Button variant="ghost" size="sm" onClick={() => setPage(p => Math.min(totalPages - 1, p + 1))} disabled={page >= totalPages - 1}>→</Button>
            <Button variant="ghost" size="sm" onClick={() => setPage(totalPages - 1)} disabled={page >= totalPages - 1}>⏭️</Button>
          </div>
        )}

        {/* Modal détail */}
        <QuestionDetailModal
          question={selectedQuestion}
          onClose={() => setSelectedQuestion(null)}
          query={debouncedQuery}
        />
      </div>
    );
  }

  // ==========================================================================
  // Sous-composant : carte de résultat
  // ==========================================================================

  function ResultCard({ question, query, onSelect }) {
    const { useToast } = root.UIHooks || root.UI;

    // Extraire un snippet autour du match si présent
    const snippet = useMemo(() => {
      if (!query || !query.trim()) return question.enonce.substring(0, 200);
      const text = question.enonce;
      const idx = text.toLowerCase().indexOf(query.toLowerCase());
      if (idx === -1) return text.substring(0, 200);
      const start = Math.max(0, idx - 50);
      const end = Math.min(text.length, idx + query.length + 150);
      return (start > 0 ? '...' : '') + text.substring(start, end) + (end < text.length ? '...' : '');
    }, [question.enonce, query]);

    const highlightedSnippet = useMemo(() => highlightText(snippet, query), [snippet, query]);

    function copyId(e) {
      e.stopPropagation();
      try {
        navigator.clipboard.writeText(question.id);
        // feedback visuel léger
        e.currentTarget.textContent = '✓ Copié';
        setTimeout(() => { if (e.currentTarget) e.currentTarget.textContent = '📋 ID'; }, 1200);
      } catch (err) {}
    }

    return (
      <div
        onClick={onSelect}
        style={{
          padding: 'var(--space-3)',
          border: '1px solid var(--color-border)',
          borderRadius: 'var(--radius-md)',
          background: 'var(--color-bg-elevated)',
          cursor: 'pointer',
          transition: 'all 0.15s',
        }}
        onMouseEnter={e => {
          e.currentTarget.style.borderColor = 'var(--color-primary)';
          e.currentTarget.style.transform = 'translateY(-1px)';
          e.currentTarget.style.boxShadow = 'var(--shadow-sm)';
        }}
        onMouseLeave={e => {
          e.currentTarget.style.borderColor = 'var(--color-border)';
          e.currentTarget.style.transform = 'none';
          e.currentTarget.style.boxShadow = 'none';
        }}
      >
        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'flex-start',
          gap: 8,
          marginBottom: 6,
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
            <code
              style={{ fontSize: 'var(--text-sm)', fontWeight: 600 }}
              dangerouslySetInnerHTML={{ __html: highlightText(question.id, query) }}
            />
            <LevelBadge level={question.difficulte} />
            <span style={{
              padding: '2px 8px',
              background: 'var(--color-bg-subtle)',
              borderRadius: 999,
              fontSize: 11,
            }}>{question.type}</span>
            {typeof question._score === 'number' && question._score > 0 && (
              <span style={{
                padding: '2px 8px',
                background: 'rgba(234, 179, 8, 0.15)',
                color: '#ca8a04',
                borderRadius: 999,
                fontSize: 11,
                fontWeight: 600,
              }}>
                ⭐ Score {question._score}
              </span>
            )}
          </div>
          <button
            onClick={copyId}
            style={{
              padding: '2px 10px',
              fontSize: 11,
              background: 'transparent',
              border: '1px solid var(--color-border)',
              borderRadius: 999,
              cursor: 'pointer',
            }}
          >📋 ID</button>
        </div>

        <div
          style={{
            fontSize: 'var(--text-sm)',
            lineHeight: 1.5,
            color: 'var(--color-text)',
          }}
          dangerouslySetInnerHTML={{ __html: highlightedSnippet }}
        />

        <div style={{
          marginTop: 6,
          fontSize: 11,
          color: 'var(--color-text-muted)',
          display: 'flex',
          gap: 8,
          flexWrap: 'wrap',
          alignItems: 'center',
        }}>
          <span>📍 {question._module} / {question._chapitre} / {question._theme}</span>
          {(question.tags || []).length > 0 && (
            <>
              <span>·</span>
              <span>🏷️ {question.tags.slice(0, 5).join(', ')}{question.tags.length > 5 ? '...' : ''}</span>
            </>
          )}
        </div>
      </div>
    );
  }

  // EXPORT
  root.BanqueSearch = BanqueSearch;

})(window);
