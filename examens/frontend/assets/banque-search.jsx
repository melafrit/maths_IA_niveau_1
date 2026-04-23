/* ============================================================================
   banque-search.jsx — Recherche full-text avancée dans la banque

   Plateforme d'examens IPSSI — Phase P5.4

   Fonctionnalités :
     - Search bar avec debounce 300ms
     - Filtres multi-critères (module, chapitre, difficulté, type, tags)
     - Résultats avec score de pertinence + surlignage
     - Tri : pertinence / ID / difficulté
     - Saved searches (localStorage)
     - Modal détails question
     - Actions : copier ID

   Composant exporté : window.BanqueSearch

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useMemo } = React;

  const MathText = root.MathText;
  const LevelBadge = root.LevelBadge;

  const LEVELS = ['facile', 'moyen', 'difficile', 'expert'];
  const TYPES = ['conceptuel', 'calcul', 'code', 'formule'];
  const LEVEL_COLORS = {
    facile: '#16a34a',
    moyen: '#ca8a04',
    difficile: '#ea580c',
    expert: '#dc2626',
  };
  const SAVED_SEARCHES_KEY = 'banque_saved_searches';
  const MAX_SAVED = 10;
  const LEVEL_ORDER = { facile: 1, moyen: 2, difficile: 3, expert: 4 };

  // Surlignage des matches
  function highlightMatches(text, query) {
    if (!text || !query) return text;
    const q = query.trim();
    if (q.length < 2) return text;
    const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${escaped})`, 'gi');
    return text.replace(regex, '<mark style="background: #fef08a; padding: 0 2px; border-radius: 2px; font-weight: 600;">$1</mark>');
  }

  // Filtre multi-select en chips
  function MultiSelectChips({ label, options, selected, onToggle, colors = null }) {
    return (
      <div>
        <div style={{
          fontSize: 'var(--text-xs)',
          fontWeight: 600,
          color: 'var(--color-text-muted)',
          textTransform: 'uppercase',
          letterSpacing: 0.5,
          marginBottom: 6,
        }}>{label}</div>
        <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
          {options.map(opt => {
            const active = selected.includes(opt);
            const color = colors?.[opt] || 'var(--color-primary)';
            return (
              <button
                key={opt}
                onClick={() => onToggle(opt)}
                style={{
                  padding: '3px 10px',
                  fontSize: 12,
                  background: active ? color : 'var(--color-bg-elevated)',
                  color: active ? 'white' : 'var(--color-text)',
                  border: `1px solid ${active ? color : 'var(--color-border)'}`,
                  borderRadius: 999,
                  cursor: 'pointer',
                  fontWeight: active ? 600 : 400,
                  transition: 'all 0.15s',
                }}
              >
                {active ? '✓ ' : ''}{opt}
              </button>
            );
          })}
        </div>
      </div>
    );
  }

  // Carte de résultat
  function SearchResultCard({ result, query, onView, onCopyId }) {
    const { Button } = root.UI;

    return (
      <div style={{
        padding: 'var(--space-3)',
        border: '1px solid var(--color-border)',
        borderRadius: 'var(--radius-md)',
        background: 'var(--color-bg-elevated)',
        marginBottom: 'var(--space-2)',
        position: 'relative',
      }}>
        <div style={{
          position: 'absolute',
          top: 10,
          right: 10,
          padding: '2px 8px',
          background: 'linear-gradient(135deg, var(--color-primary), #8b5cf6)',
          color: 'white',
          fontSize: 11,
          fontWeight: 700,
          borderRadius: 999,
        }}>⭐ score: {result._score}</div>

        <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 8, flexWrap: 'wrap', paddingRight: 80 }}>
          <code
            style={{ fontSize: 'var(--text-sm)', fontWeight: 700, cursor: 'pointer', color: 'var(--color-primary)' }}
            onClick={() => onCopyId(result.id)}
            title="Cliquer pour copier"
            dangerouslySetInnerHTML={{ __html: highlightMatches(result.id, query) }}
          />
          <LevelBadge level={result.difficulte} />
          <span style={{
            padding: '2px 8px', fontSize: 11,
            background: 'var(--color-bg-subtle)',
            borderRadius: 999, color: 'var(--color-text-muted)',
          }}>{result.type}</span>
        </div>

        <div style={{ fontSize: 'var(--text-sm)', lineHeight: 1.5, marginBottom: 8 }}
          dangerouslySetInnerHTML={{
            __html: highlightMatches(
              result.enonce.length > 250 ? result.enonce.substring(0, 250) + '...' : result.enonce,
              query
            ),
          }}
        />

        {result.tags && result.tags.length > 0 && (
          <div style={{ display: 'flex', gap: 4, flexWrap: 'wrap', marginBottom: 8 }}>
            {result.tags.map(tag => (
              <span key={tag}
                style={{
                  padding: '2px 8px', fontSize: 11,
                  background: 'var(--color-bg-subtle)',
                  color: 'var(--color-text-muted)',
                  borderRadius: 999,
                  border: '1px solid var(--color-border)',
                }}
                dangerouslySetInnerHTML={{ __html: highlightMatches(tag, query) }}
              />
            ))}
          </div>
        )}

        <div style={{
          display: 'flex', justifyContent: 'space-between', alignItems: 'center',
          marginTop: 8, paddingTop: 8,
          borderTop: '1px solid var(--color-border)',
          fontSize: 11,
        }}>
          <span style={{ color: 'var(--color-text-muted)' }}>
            📍 {result._module} / {result._chapitre} / {result._theme}
          </span>
          <div style={{ display: 'flex', gap: 4 }}>
            <Button variant="ghost" size="sm" onClick={() => onCopyId(result.id)}>📋 Copier ID</Button>
            <Button variant="ghost" size="sm" onClick={() => onView(result)}>👁️ Voir détails</Button>
          </div>
        </div>
      </div>
    );
  }

  // Modal détail
  function QuestionDetailModal({ question, isOpen, onClose }) {
    const { Modal, Button } = root.UI;
    if (!question) return null;
    const letters = ['A', 'B', 'C', 'D'];

    return (
      <Modal open={isOpen} onClose={onClose} title={`Question : ${question.id}`} size="lg">
        <div style={{ maxHeight: '70vh', overflow: 'auto', paddingRight: 8 }}>
          <div style={{ display: 'flex', gap: 8, marginBottom: 'var(--space-3)', flexWrap: 'wrap' }}>
            <LevelBadge level={question.difficulte} />
            <span style={{
              padding: '2px 10px', fontSize: 11,
              background: 'var(--color-primary-subtle)',
              color: 'var(--color-primary)',
              borderRadius: 999,
            }}>{question.type}</span>
          </div>

          <div style={{
            padding: 'var(--space-3)',
            background: 'var(--color-bg-subtle)',
            borderRadius: 'var(--radius-md)',
            borderLeft: '3px solid var(--color-primary)',
            marginBottom: 'var(--space-3)',
          }}>
            <div style={{
              fontSize: 'var(--text-xs)', fontWeight: 600, textTransform: 'uppercase',
              color: 'var(--color-text-muted)', marginBottom: 6,
            }}>Énoncé</div>
            <MathText text={question.enonce} as="div" />
          </div>

          <div style={{ marginBottom: 'var(--space-3)' }}>
            {question.options.map((opt, i) => (
              <div key={i} style={{
                padding: 'var(--space-2)',
                borderLeft: `3px solid ${i === question.correct ? '#16a34a' : 'var(--color-border)'}`,
                background: i === question.correct ? 'rgba(34, 197, 94, 0.08)' : 'transparent',
                marginBottom: 6, display: 'flex', gap: 8, borderRadius: 4,
              }}>
                <strong style={{ color: i === question.correct ? '#16a34a' : 'var(--color-primary)' }}>
                  {letters[i]}
                </strong>
                <MathText text={opt} as="span" style={{ flex: 1 }} />
                {i === question.correct && <span style={{ color: '#16a34a' }}>✓</span>}
              </div>
            ))}
          </div>

          <div style={{
            padding: 'var(--space-2)',
            background: 'rgba(168, 85, 247, 0.08)',
            borderLeft: '3px solid #a855f7',
            borderRadius: 4, marginBottom: 'var(--space-2)',
          }}>
            <strong style={{ fontSize: 12, color: '#7e22ce' }}>💡 HINT</strong>
            <MathText text={question.hint} as="div" style={{ fontSize: 'var(--text-sm)', marginTop: 4 }} />
          </div>

          <div style={{
            padding: 'var(--space-2)',
            background: 'rgba(34, 197, 94, 0.08)',
            borderLeft: '3px solid #16a34a',
            borderRadius: 4, marginBottom: 'var(--space-2)',
          }}>
            <strong style={{ fontSize: 12, color: '#166534' }}>📖 EXPLICATION</strong>
            <MathText text={question.explanation} as="div" style={{ fontSize: 'var(--text-sm)', marginTop: 4 }} />
          </div>

          <div style={{
            padding: 'var(--space-2)',
            background: 'rgba(249, 115, 22, 0.08)',
            borderLeft: '3px solid #f97316',
            borderRadius: 4, marginBottom: 'var(--space-3)',
          }}>
            <strong style={{ fontSize: 12, color: '#c2410c' }}>⚠️ PIÈGES</strong>
            <MathText text={question.traps} as="div" style={{ fontSize: 'var(--text-sm)', marginTop: 4 }} />
          </div>

          <div style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>
            <div>📚 {question.references}</div>
            <div style={{ marginTop: 4 }}>
              📍 {question._module} / {question._chapitre} / {question._theme}
            </div>
            {question.tags && question.tags.length > 0 && (
              <div style={{ marginTop: 4 }}>🔖 {question.tags.join(', ')}</div>
            )}
          </div>
        </div>

        <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: 'var(--space-3)' }}>
          <Button variant="ghost" onClick={onClose}>Fermer</Button>
        </div>
      </Modal>
    );
  }

  // ==========================================================================
  // Composant principal : BanqueSearch
  // ==========================================================================

  function BanqueSearch() {
    const { Button, Input, Select, useToast } = root.UI;
    const { useApi, useDebounce } = root.UIHooks;
    const api = useApi();
    const { toast } = useToast();

    const [query, setQuery] = useState('');
    const debouncedQuery = useDebounce(query, 300);

    const [filters, setFilters] = useState({
      module: '', chapitre: '', theme: '',
      difficulte: [], type: [], tags: [],
    });
    const [tagInput, setTagInput] = useState('');

    const [availableModules, setAvailableModules] = useState([]);
    const [availableChapitres, setAvailableChapitres] = useState([]);
    const [availableThemes, setAvailableThemes] = useState([]);

    const [results, setResults] = useState([]);
    const [searching, setSearching] = useState(false);
    const [searched, setSearched] = useState(false);
    const [totalResults, setTotalResults] = useState(0);

    const [sortBy, setSortBy] = useState('score');
    const [page, setPage] = useState(1);
    const PER_PAGE = 10;

    const [detailQuestion, setDetailQuestion] = useState(null);
    const [savedSearches, setSavedSearches] = useState([]);

    // Load saved searches at mount
    useEffect(() => {
      try {
        const saved = localStorage.getItem(SAVED_SEARCHES_KEY);
        if (saved) setSavedSearches(JSON.parse(saved));
      } catch (e) { /* ignore */ }
    }, []);

    // Load modules
    useEffect(() => {
      async function fetch() {
        const res = await api.request('GET', '/api/banque/modules');
        if (res.ok) setAvailableModules(res.data?.modules || []);
      }
      fetch();
    }, []);

    useEffect(() => {
      async function fetch() {
        if (!filters.module) { setAvailableChapitres([]); return; }
        const res = await api.request('GET', `/api/banque/${filters.module}/chapitres`);
        if (res.ok) setAvailableChapitres(res.data?.chapitres || []);
      }
      fetch();
    }, [filters.module]);

    useEffect(() => {
      async function fetch() {
        if (!filters.module || !filters.chapitre) { setAvailableThemes([]); return; }
        const res = await api.request('GET', `/api/banque/${filters.module}/${filters.chapitre}/themes`);
        if (res.ok) setAvailableThemes(res.data?.themes || []);
      }
      fetch();
    }, [filters.module, filters.chapitre]);

    // Auto-search
    useEffect(() => {
      if (debouncedQuery.trim().length >= 2) {
        doSearch();
      } else if (debouncedQuery === '') {
        setResults([]);
        setSearched(false);
      }
    }, [debouncedQuery]);

    // Relancer quand filters changent (si déjà cherché)
    useEffect(() => {
      if (searched && query.trim().length >= 2) {
        doSearch();
      }
    }, [
      filters.module, filters.chapitre, filters.theme,
      filters.difficulte.join(','), filters.type.join(','), filters.tags.join(','),
    ]);

    async function doSearch() {
      if (debouncedQuery.trim().length < 2) return;

      setSearching(true);
      const serverFilters = {};
      if (filters.module) serverFilters.module = filters.module;
      if (filters.chapitre) serverFilters.chapitre = filters.chapitre;
      if (filters.theme) serverFilters.theme = filters.theme;
      if (filters.difficulte.length > 0) {
        serverFilters.difficulte = filters.difficulte.length === 1
          ? filters.difficulte[0] : filters.difficulte;
      }
      if (filters.type.length > 0) {
        serverFilters.type = filters.type.length === 1
          ? filters.type[0] : filters.type;
      }
      if (filters.tags.length > 0) serverFilters.tags = filters.tags;

      const res = await api.request('POST', '/api/banque/search', {
        query: debouncedQuery.trim(),
        filters: serverFilters,
        fields: ['enonce', 'tags', 'id', 'explanation'],
        limit: 100,
      });

      if (res.ok) {
        setResults(res.data?.results || []);
        setTotalResults(res.data?.total || 0);
        setSearched(true);
        setPage(1);
      } else {
        toast({
          title: 'Erreur recherche',
          message: res.error?.message || 'Impossible de rechercher',
          type: 'error',
        });
      }
      setSearching(false);
    }

    function copyId(id) {
      navigator.clipboard?.writeText(id).then(() => {
        toast({ title: 'Copié !', message: id, type: 'success' });
      }).catch(() => {
        toast({ title: 'Erreur copie', message: id, type: 'warning' });
      });
    }

    function toggleFilter(key, value) {
      setFilters(f => ({
        ...f,
        [key]: f[key].includes(value)
          ? f[key].filter(v => v !== value)
          : [...f[key], value],
      }));
    }

    function addTagFilter() {
      const t = tagInput.trim().toLowerCase();
      if (t && !filters.tags.includes(t)) {
        setFilters(f => ({ ...f, tags: [...f.tags, t] }));
      }
      setTagInput('');
    }

    function removeTagFilter(tag) {
      setFilters(f => ({ ...f, tags: f.tags.filter(t => t !== tag) }));
    }

    function resetFilters() {
      setFilters({
        module: '', chapitre: '', theme: '',
        difficulte: [], type: [], tags: [],
      });
    }

    function resetAll() {
      setQuery('');
      resetFilters();
      setResults([]);
      setSearched(false);
    }

    function saveCurrentSearch() {
      if (!query.trim()) {
        toast({ title: 'Rien à sauver', message: 'Saisissez une recherche', type: 'warning' });
        return;
      }
      const entry = {
        id: 'search_' + Date.now(),
        query: query.trim(),
        filters: { ...filters },
        savedAt: new Date().toISOString(),
        count: totalResults,
      };
      const newList = [entry, ...savedSearches.filter(s =>
        s.query !== entry.query || JSON.stringify(s.filters) !== JSON.stringify(entry.filters)
      )].slice(0, MAX_SAVED);

      setSavedSearches(newList);
      try {
        localStorage.setItem(SAVED_SEARCHES_KEY, JSON.stringify(newList));
      } catch (e) { /* ignore */ }
      toast({ title: 'Recherche sauvée', message: entry.query, type: 'success' });
    }

    function loadSavedSearch(entry) {
      setQuery(entry.query);
      setFilters(entry.filters);
      toast({ title: 'Recherche chargée', message: entry.query, type: 'info' });
    }

    function deleteSavedSearch(entryId) {
      const newList = savedSearches.filter(s => s.id !== entryId);
      setSavedSearches(newList);
      try {
        localStorage.setItem(SAVED_SEARCHES_KEY, JSON.stringify(newList));
      } catch (e) { /* ignore */ }
    }

    const sortedResults = useMemo(() => {
      const sorted = [...results];
      switch (sortBy) {
        case 'id': sorted.sort((a, b) => a.id.localeCompare(b.id)); break;
        case 'level': sorted.sort((a, b) => (LEVEL_ORDER[a.difficulte] || 99) - (LEVEL_ORDER[b.difficulte] || 99)); break;
        case 'score':
        default: sorted.sort((a, b) => (b._score || 0) - (a._score || 0)); break;
      }
      return sorted;
    }, [results, sortBy]);

    const totalPages = Math.ceil(sortedResults.length / PER_PAGE);
    const pagedResults = sortedResults.slice((page - 1) * PER_PAGE, page * PER_PAGE);

    const activeFiltersCount = [
      filters.module, filters.chapitre, filters.theme,
      ...filters.difficulte, ...filters.type, ...filters.tags,
    ].filter(Boolean).length;

    return (
      <div>
        <h3 style={{ marginTop: 0, display: 'flex', alignItems: 'center', gap: 8 }}>
          🔎 Recherche avancée
        </h3>
        <p style={{ color: 'var(--color-text-muted)', marginBottom: 'var(--space-4)' }}>
          Recherche full-text dans les 320 questions avec filtres multi-critères et score de pertinence.
        </p>

        {/* Search bar */}
        <div style={{ marginBottom: 'var(--space-4)' }}>
          <div style={{ display: 'flex', gap: 8 }}>
            <div style={{ flex: 1, position: 'relative' }}>
              <Input
                value={query}
                onChange={val => setQuery(val)}
                placeholder="🔍 Rechercher dans les énoncés, tags, explications... (min 2 caractères)"
                autoFocus
              />
              {query && (
                <button
                  onClick={() => setQuery('')}
                  style={{
                    position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)',
                    background: 'none', border: 'none', cursor: 'pointer',
                    color: 'var(--color-text-muted)', fontSize: 16,
                  }}
                  title="Effacer"
                >✕</button>
              )}
            </div>
            <Button variant="secondary" onClick={saveCurrentSearch} disabled={!query.trim()}>
              💾 Sauver
            </Button>
            <Button variant="ghost" onClick={resetAll}>🗑️ Reset</Button>
          </div>

          {searching && (
            <div style={{ marginTop: 6, fontSize: 11, color: 'var(--color-text-muted)' }}>
              ⏳ Recherche en cours...
            </div>
          )}
        </div>

        {/* Saved searches */}
        {savedSearches.length > 0 && (
          <div style={{
            padding: 'var(--space-3)',
            background: 'var(--color-bg-subtle)',
            borderRadius: 'var(--radius-md)',
            marginBottom: 'var(--space-3)',
          }}>
            <div style={{
              fontSize: 'var(--text-xs)', fontWeight: 600,
              color: 'var(--color-text-muted)', textTransform: 'uppercase', marginBottom: 6,
            }}>💾 Recherches sauvegardées ({savedSearches.length})</div>
            <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
              {savedSearches.map(entry => (
                <div key={entry.id} style={{
                  display: 'flex', alignItems: 'center', gap: 4,
                  padding: '4px 4px 4px 10px',
                  background: 'var(--color-bg-elevated)',
                  border: '1px solid var(--color-border)',
                  borderRadius: 999, fontSize: 12,
                }}>
                  <span
                    onClick={() => loadSavedSearch(entry)}
                    style={{ cursor: 'pointer', color: 'var(--color-primary)' }}
                    title={`${entry.count} résultats le ${new Date(entry.savedAt).toLocaleString('fr-FR')}`}
                  >
                    {entry.query}
                  </span>
                  <button
                    onClick={() => deleteSavedSearch(entry.id)}
                    style={{
                      background: 'none', border: 'none', cursor: 'pointer',
                      color: 'var(--color-text-muted)', fontSize: 14,
                      padding: '0 4px', lineHeight: 1,
                    }}
                    title="Supprimer"
                  >×</button>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Filtres */}
        <div style={{
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          marginBottom: 'var(--space-4)',
          display: 'flex', flexDirection: 'column', gap: 'var(--space-3)',
        }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div style={{ fontSize: 'var(--text-sm)', fontWeight: 600 }}>
              🔧 Filtres {activeFiltersCount > 0 && (
                <span style={{
                  padding: '1px 6px',
                  background: 'var(--color-primary)',
                  color: 'white', borderRadius: 999,
                  fontSize: 10, marginLeft: 6,
                }}>{activeFiltersCount}</span>
              )}
            </div>
            {activeFiltersCount > 0 && (
              <Button variant="ghost" size="sm" onClick={resetFilters}>
                Effacer filtres
              </Button>
            )}
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 8 }}>
            <div>
              <label style={{ fontSize: 11, fontWeight: 600, display: 'block', marginBottom: 4 }}>Module</label>
              <Select
                value={filters.module}
                onChange={val => setFilters(f => ({ ...f, module: val, chapitre: '', theme: '' }))}
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
                onChange={val => setFilters(f => ({ ...f, chapitre: val, theme: '' }))}
                options={[
                  { value: '', label: '— Tous —' },
                  ...availableChapitres.map(c => ({ value: c, label: c })),
                ]}
                disabled={!filters.module}
              />
            </div>
            <div>
              <label style={{ fontSize: 11, fontWeight: 600, display: 'block', marginBottom: 4 }}>Thème</label>
              <Select
                value={filters.theme}
                onChange={val => setFilters(f => ({ ...f, theme: val }))}
                options={[
                  { value: '', label: '— Tous —' },
                  ...availableThemes.map(t => ({ value: t, label: t })),
                ]}
                disabled={!filters.chapitre}
              />
            </div>
          </div>

          <MultiSelectChips
            label="Difficulté (multi-sélection)"
            options={LEVELS}
            selected={filters.difficulte}
            onToggle={v => toggleFilter('difficulte', v)}
            colors={LEVEL_COLORS}
          />

          <MultiSelectChips
            label="Type (multi-sélection)"
            options={TYPES}
            selected={filters.type}
            onToggle={v => toggleFilter('type', v)}
          />

          <div>
            <label style={{
              fontSize: 'var(--text-xs)', fontWeight: 600,
              color: 'var(--color-text-muted)', textTransform: 'uppercase',
              letterSpacing: 0.5, display: 'block', marginBottom: 6,
            }}>Tags (AU MOINS UN doit matcher)</label>
            <div style={{ display: 'flex', gap: 6, marginBottom: 6 }}>
              <Input
                value={tagInput}
                onChange={val => setTagInput(val)}
                placeholder="ex: vecteurs, gradient, fil-rouge"
                onKeyDown={e => { if (e.key === 'Enter') { e.preventDefault(); addTagFilter(); } }}
              />
              <Button variant="secondary" size="sm" onClick={addTagFilter}>Ajouter</Button>
            </div>
            <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
              {filters.tags.map(tag => (
                <span key={tag} style={{
                  padding: '2px 6px 2px 10px',
                  background: 'var(--color-primary-subtle)',
                  color: 'var(--color-primary)',
                  borderRadius: 999, fontSize: 12,
                  display: 'inline-flex', alignItems: 'center', gap: 4,
                }}>
                  {tag}
                  <button
                    onClick={() => removeTagFilter(tag)}
                    style={{
                      background: 'none', border: 'none', cursor: 'pointer',
                      color: 'var(--color-primary)', padding: 0, lineHeight: 1, fontSize: 14,
                    }}
                  >×</button>
                </span>
              ))}
              {filters.tags.length === 0 && (
                <span style={{ color: 'var(--color-text-muted)', fontSize: 12 }}>
                  Aucun tag filtré
                </span>
              )}
            </div>
          </div>
        </div>

        {/* Résultats */}
        {!searched && !searching ? (
          <div style={{
            padding: 'var(--space-6)', textAlign: 'center',
            background: 'var(--color-bg-subtle)',
            borderRadius: 'var(--radius-md)',
            color: 'var(--color-text-muted)',
          }}>
            <div style={{ fontSize: 48, marginBottom: 12, opacity: 0.3 }}>🔎</div>
            <h4 style={{ margin: '0 0 8px 0', color: 'var(--color-text)' }}>Aucune recherche lancée</h4>
            <p style={{ margin: 0 }}>Saisissez au moins 2 caractères dans la barre ci-dessus.</p>
          </div>
        ) : results.length === 0 ? (
          <div style={{
            padding: 'var(--space-6)', textAlign: 'center',
            background: 'var(--color-bg-subtle)',
            borderRadius: 'var(--radius-md)',
            color: 'var(--color-text-muted)',
          }}>
            <div style={{ fontSize: 48, marginBottom: 12, opacity: 0.3 }}>🚫</div>
            <h4 style={{ margin: '0 0 8px 0', color: 'var(--color-text)' }}>Aucun résultat</h4>
            <p style={{ margin: 0 }}>
              Aucune question ne correspond à "<strong>{query}</strong>"
              {activeFiltersCount > 0 && ` avec ${activeFiltersCount} filtre(s) actif(s)`}.
            </p>
          </div>
        ) : (
          <>
            <div style={{
              display: 'flex', justifyContent: 'space-between', alignItems: 'center',
              marginBottom: 'var(--space-3)',
              padding: 'var(--space-2) var(--space-3)',
              background: 'var(--color-bg-elevated)',
              borderRadius: 'var(--radius-md)',
              border: '1px solid var(--color-border)',
            }}>
              <div style={{ fontWeight: 600 }}>
                ✅ <strong>{results.length}</strong> résultat{results.length > 1 ? 's' : ''}
                <span style={{ fontSize: 12, color: 'var(--color-text-muted)', marginLeft: 8 }}>
                  sur <strong>{totalResults}</strong> match{totalResults > 1 ? 's' : ''}
                </span>
              </div>
              <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                <span style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>Trier :</span>
                <Select
                  value={sortBy}
                  onChange={val => setSortBy(val)}
                  options={[
                    { value: 'score', label: '⭐ Pertinence' },
                    { value: 'id', label: '🔤 ID' },
                    { value: 'level', label: '📊 Difficulté' },
                  ]}
                  style={{ width: 150 }}
                />
              </div>
            </div>

            <div>
              {pagedResults.map(r => (
                <SearchResultCard
                  key={r.id}
                  result={r}
                  query={debouncedQuery}
                  onView={setDetailQuestion}
                  onCopyId={copyId}
                />
              ))}
            </div>

            {totalPages > 1 && (
              <div style={{
                display: 'flex', justifyContent: 'center', gap: 4,
                marginTop: 'var(--space-4)',
              }}>
                <Button variant="ghost" size="sm" disabled={page === 1}
                  onClick={() => setPage(p => Math.max(1, p - 1))}>
                  ← Précédent
                </Button>
                {Array.from({ length: Math.min(totalPages, 10) }, (_, i) => {
                  const p = i + 1;
                  return (
                    <button key={p} onClick={() => setPage(p)}
                      style={{
                        padding: '4px 10px',
                        background: page === p ? 'var(--color-primary)' : 'transparent',
                        color: page === p ? 'white' : 'var(--color-text)',
                        border: `1px solid ${page === p ? 'var(--color-primary)' : 'var(--color-border)'}`,
                        borderRadius: 'var(--radius-md)',
                        cursor: 'pointer',
                        fontSize: 12,
                        fontWeight: page === p ? 600 : 400,
                      }}
                    >{p}</button>
                  );
                })}
                {totalPages > 10 && <span style={{ padding: '4px' }}>...</span>}
                <Button variant="ghost" size="sm" disabled={page >= totalPages}
                  onClick={() => setPage(p => Math.min(totalPages, p + 1))}>
                  Suivant →
                </Button>
              </div>
            )}
          </>
        )}

        <QuestionDetailModal
          question={detailQuestion}
          isOpen={detailQuestion !== null}
          onClose={() => setDetailQuestion(null)}
        />
      </div>
    );
  }

  // EXPORT
  root.BanqueSearch = BanqueSearch;

})(window);
