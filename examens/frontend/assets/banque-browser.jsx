/* ============================================================================
   banque-browser.jsx — Navigation hiérarchique dans la banque de questions

   Plateforme d'examens IPSSI — Phase P5.1

   Fonctionnalités :
     - Navigation : Module → Chapitre → Thème → Question
     - Breadcrumb cliquable
     - Badges de niveau colorés (facile/moyen/difficile/expert)
     - Preview de question complet avec KaTeX
     - Tabs : Énoncé/Options, Métadonnées (hint, explanation, traps, refs)

   Composant exporté : window.BanqueBrowser

   Dépendances :
     - React 18
     - window.UI (components-base + advanced)
     - window.UIHooks (useApi)
     - window.UI (useToast)
     - KaTeX (pour rendu formules)

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useCallback, useMemo } = React;

  // Helper : rendu KaTeX inline pour un texte contenant $...$ et $$...$$
  function renderMath(text) {
    if (!text || typeof text !== 'string') return text;
    if (typeof window.katex === 'undefined') return text;

    try {
      // Remplacer les blocs display math $$...$$
      let html = text.replace(/\$\$([^$]+)\$\$/g, (_, expr) => {
        try {
          return window.katex.renderToString(expr, { displayMode: true, throwOnError: false });
        } catch (e) {
          return `<code>${expr}</code>`;
        }
      });
      // Remplacer les maths inline $...$
      html = html.replace(/\$([^$]+)\$/g, (_, expr) => {
        try {
          return window.katex.renderToString(expr, { displayMode: false, throwOnError: false });
        } catch (e) {
          return `<code>${expr}</code>`;
        }
      });
      // Remplacer **gras** en <strong>
      html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
      // Remplacer `code` en <code>
      html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
      return html;
    } catch (e) {
      console.warn('renderMath error', e);
      return text;
    }
  }

  // Composant : rendu d'un texte avec formules KaTeX
  function MathText({ text, as = 'div', className, style }) {
    const html = useMemo(() => renderMath(text), [text]);
    const Tag = as;
    return (
      <Tag
        className={className}
        style={style}
        dangerouslySetInnerHTML={{ __html: html }}
      />
    );
  }

  // Composant : badge de difficulté coloré
  function LevelBadge({ level, count = null }) {
    const icons = {
      facile: '🟢',
      moyen: '🟡',
      difficile: '🟠',
      expert: '🔴',
    };
    const labels = {
      facile: 'Facile',
      moyen: 'Moyen',
      difficile: 'Difficile',
      expert: 'Expert',
    };
    return (
      <span className={`level-badge level-badge--${level}`}>
        {icons[level]} {labels[level]}
        {count !== null && ` · ${count}`}
      </span>
    );
  }

  // Composant : breadcrumb cliquable
  function Breadcrumb({ path, onNavigate }) {
    const levels = ['Banque', path.module, path.chapitre, path.theme, path.questionId];
    const activeIdx = levels.findIndex((l, i) => i > 0 && !l) - 1;
    const finalIdx = activeIdx === -2 ? levels.length - 1 : activeIdx;

    return (
      <div className="banque-breadcrumb">
        {levels.map((label, i) => {
          if (!label) return null;
          const isLast = i === finalIdx;
          return (
            <React.Fragment key={i}>
              {i > 0 && <span className="banque-breadcrumb-separator">›</span>}
              {isLast ? (
                <span className="banque-breadcrumb-current">{label}</span>
              ) : (
                <span
                  className="banque-breadcrumb-item"
                  onClick={() => onNavigate(i)}
                >
                  {label}
                </span>
              )}
            </React.Fragment>
          );
        })}
      </div>
    );
  }

  // Niveau 0 : liste des modules
  function ModulesList({ modules, onSelect, loading }) {
    const { Card, Box, EmptyState } = window.UI;

    if (loading) return <Box type="info">Chargement des modules...</Box>;
    if (!modules.length) {
      return (
        <EmptyState
          icon="📚"
          title="Aucun module"
          message="La banque de questions est vide."
        />
      );
    }

    return (
      <div className="banque-grid">
        {modules.map(mod => (
          <div
            key={mod}
            className="banque-item-card"
            onClick={() => onSelect(mod)}
          >
            <h3 className="banque-item-title">📚 {mod}</h3>
            <p className="banque-item-subtitle">Module · Cliquer pour explorer</p>
          </div>
        ))}
      </div>
    );
  }

  // Niveau 1 : liste des chapitres d'un module
  function ChapitresList({ chapitres, onSelect, loading }) {
    const { Box, EmptyState } = window.UI;

    if (loading) return <Box type="info">Chargement des chapitres...</Box>;
    if (!chapitres.length) {
      return <EmptyState icon="📖" title="Aucun chapitre" message="Ce module ne contient pas de chapitre." />;
    }

    return (
      <div className="banque-grid">
        {chapitres.map(chap => (
          <div
            key={chap}
            className="banque-item-card"
            onClick={() => onSelect(chap)}
          >
            <h3 className="banque-item-title">📖 {chap}</h3>
            <p className="banque-item-subtitle">Chapitre · Cliquer pour voir les thèmes</p>
          </div>
        ))}
      </div>
    );
  }

  // Niveau 2 : liste des thèmes d'un chapitre
  function ThemesList({ themes, onSelect, loading, themeCounts }) {
    const { Box, EmptyState } = window.UI;

    if (loading) return <Box type="info">Chargement des thèmes...</Box>;
    if (!themes.length) {
      return <EmptyState icon="🎯" title="Aucun thème" message="Ce chapitre ne contient pas de thème." />;
    }

    return (
      <div className="banque-grid">
        {themes.map(theme => {
          const count = themeCounts?.[theme] ?? null;
          return (
            <div
              key={theme}
              className="banque-item-card"
              onClick={() => onSelect(theme)}
            >
              <h3 className="banque-item-title">🎯 {theme}</h3>
              <p className="banque-item-subtitle">
                Thème{count !== null && ` · ${count} question${count > 1 ? 's' : ''}`}
              </p>
            </div>
          );
        })}
      </div>
    );
  }

  // Niveau 3 : liste des questions d'un thème
  function QuestionsList({ questions, onSelect, loading }) {
    const { Box, EmptyState, Badge } = window.UI;
    const [filterLevel, setFilterLevel] = useState('all');

    if (loading) return <Box type="info">Chargement des questions...</Box>;
    if (!questions.length) {
      return <EmptyState icon="❓" title="Aucune question" message="Ce thème est vide." />;
    }

    const levels = ['all', 'facile', 'moyen', 'difficile', 'expert'];
    const filtered = filterLevel === 'all'
      ? questions
      : questions.filter(q => q.difficulte === filterLevel);

    const countsByLevel = questions.reduce((acc, q) => {
      acc[q.difficulte] = (acc[q.difficulte] || 0) + 1;
      return acc;
    }, {});

    return (
      <div>
        {/* Filtres par niveau */}
        <div style={{ marginBottom: 'var(--space-4)', display: 'flex', gap: 'var(--space-2)', flexWrap: 'wrap' }}>
          {levels.map(lvl => {
            const active = filterLevel === lvl;
            const count = lvl === 'all' ? questions.length : (countsByLevel[lvl] || 0);
            return (
              <button
                key={lvl}
                onClick={() => setFilterLevel(lvl)}
                style={{
                  padding: '6px 14px',
                  borderRadius: 'var(--radius-md)',
                  border: `1px solid ${active ? 'var(--color-primary)' : 'var(--color-border)'}`,
                  background: active ? 'var(--color-primary)' : 'var(--color-bg-elevated)',
                  color: active ? 'white' : 'var(--color-text)',
                  fontSize: 'var(--text-sm)',
                  fontWeight: 500,
                  cursor: 'pointer',
                  transition: 'all 0.15s',
                }}
              >
                {lvl === 'all' ? `Tous (${count})` : `${lvl} (${count})`}
              </button>
            );
          })}
        </div>

        {/* Liste */}
        {filtered.length === 0 ? (
          <EmptyState icon="🔍" title="Aucune question" message={`Aucune question de niveau ${filterLevel}.`} />
        ) : (
          <div className="banque-grid">
            {filtered.map(q => (
              <div
                key={q.id}
                className="banque-item-card"
                onClick={() => onSelect(q)}
                style={{ position: 'relative' }}
              >
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: 'var(--space-2)' }}>
                  <code style={{ fontSize: 'var(--text-xs)', color: 'var(--color-text-muted)' }}>{q.id}</code>
                  <LevelBadge level={q.difficulte} />
                </div>
                <MathText
                  text={q.enonce.length > 120 ? q.enonce.substring(0, 120) + '...' : q.enonce}
                  as="p"
                  className="banque-item-subtitle"
                  style={{ margin: 0, lineHeight: 1.5 }}
                />
                <div className="tags-list">
                  <span className="tag-chip">{q.type}</span>
                  {(q.tags || []).slice(0, 3).map(tag => (
                    <span key={tag} className="tag-chip">{tag}</span>
                  ))}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    );
  }

  // Niveau 4 : detail d'une question
  function QuestionDetail({ question, onBack }) {
    const { Button, Box, Badge } = window.UI;
    const [activeTab, setActiveTab] = useState('question');

    if (!question) return null;

    const letters = ['A', 'B', 'C', 'D'];

    return (
      <div>
        <div style={{ marginBottom: 'var(--space-4)' }}>
          <Button variant="ghost" size="sm" onClick={onBack}>← Retour aux questions</Button>
        </div>

        {/* Header avec badges */}
        <div style={{ marginBottom: 'var(--space-4)', display: 'flex', alignItems: 'center', gap: 'var(--space-3)', flexWrap: 'wrap' }}>
          <code style={{ fontSize: 'var(--text-lg)', fontWeight: 600 }}>{question.id}</code>
          <LevelBadge level={question.difficulte} />
          <span className="tag-chip" style={{ background: 'var(--color-primary-subtle)', color: 'var(--color-primary)' }}>
            {question.type}
          </span>
        </div>

        {/* Tabs */}
        <div className="tabs-container">
          <div className="banque-tab-nav">
            <button
              className={`banque-tab-btn ${activeTab === 'question' ? 'active' : ''}`}
              onClick={() => setActiveTab('question')}
            >
              📝 Question & options
            </button>
            <button
              className={`banque-tab-btn ${activeTab === 'meta' ? 'active' : ''}`}
              onClick={() => setActiveTab('meta')}
            >
              💡 Hint, explication, pièges
            </button>
            <button
              className={`banque-tab-btn ${activeTab === 'refs' ? 'active' : ''}`}
              onClick={() => setActiveTab('refs')}
            >
              🔖 Tags & références
            </button>
          </div>

          {/* Tab 1 : Question */}
          {activeTab === 'question' && (
            <div>
              <div className="question-meta-section">
                <div className="question-meta-title">Énoncé</div>
                <MathText text={question.enonce} as="div" className="question-enonce" />
              </div>

              <div className="question-options">
                {(question.options || []).map((opt, i) => (
                  <div
                    key={i}
                    className={`question-option ${i === question.correct ? 'question-option--correct' : ''}`}
                  >
                    <span className="question-option-letter">{letters[i]}</span>
                    <MathText text={opt} as="div" style={{ flex: 1 }} />
                    {i === question.correct && (
                      <span style={{ color: '#16a34a', fontWeight: 600, fontSize: '1.2em' }}>✓</span>
                    )}
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Tab 2 : Meta */}
          {activeTab === 'meta' && (
            <div>
              <div className="question-meta-section">
                <div className="question-meta-title">💡 Indice (hint)</div>
                <MathText text={question.hint} as="div" className="question-meta-content" />
              </div>

              <div className="question-meta-section" style={{ borderLeftColor: '#22c55e' }}>
                <div className="question-meta-title">📖 Explication détaillée</div>
                <MathText text={question.explanation} as="div" className="question-meta-content" />
              </div>

              <div className="question-meta-section" style={{ borderLeftColor: '#f97316' }}>
                <div className="question-meta-title">⚠️ Pièges à éviter</div>
                <MathText text={question.traps} as="div" className="question-meta-content" />
              </div>
            </div>
          )}

          {/* Tab 3 : Refs & tags */}
          {activeTab === 'refs' && (
            <div>
              <div className="question-meta-section">
                <div className="question-meta-title">🔖 Tags</div>
                <div className="tags-list">
                  {(question.tags || []).map(tag => (
                    <span key={tag} className="tag-chip">{tag}</span>
                  ))}
                  {(!question.tags || question.tags.length === 0) && (
                    <span style={{ color: 'var(--color-text-muted)' }}>Aucun tag</span>
                  )}
                </div>
              </div>

              <div className="question-meta-section">
                <div className="question-meta-title">📚 Référence au cours</div>
                <div className="question-meta-content">{question.references}</div>
              </div>

              <div className="question-meta-section">
                <div className="question-meta-title">📍 Localisation</div>
                <div className="question-meta-content">
                  <strong>Module</strong> : {question._module}<br />
                  <strong>Chapitre</strong> : {question._chapitre}<br />
                  <strong>Thème</strong> : {question._theme}
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    );
  }

  // ==========================================================================
  // Composant principal : BanqueBrowser
  // ==========================================================================

  function BanqueBrowser() {
    const { useApi } = window.UIHooks;
    const { useToast } = window.UI;
    const api = useApi();
    const { toast } = useToast();

    // État de navigation
    const [path, setPath] = useState({
      module: null,
      chapitre: null,
      theme: null,
      questionId: null,
    });

    // Données chargées
    const [modules, setModules] = useState([]);
    const [chapitres, setChapitres] = useState([]);
    const [themes, setThemes] = useState([]);
    const [questions, setQuestions] = useState([]);
    const [currentQuestion, setCurrentQuestion] = useState(null);
    const [themeCounts, setThemeCounts] = useState({});

    // États de chargement
    const [loading, setLoading] = useState({
      modules: false,
      chapitres: false,
      themes: false,
      questions: false,
      question: false,
    });

    // Charger les modules au mount
    useEffect(() => {
      loadModules();
    }, []);

    async function loadModules() {
      setLoading(l => ({ ...l, modules: true }));
      const res = await api.request('GET', '/api/banque/modules');
      if (res.ok) {
        setModules(res.data?.modules || []);
      } else {
        toast({ title: 'Erreur', message: res.error?.message || 'Impossible de charger les modules', type: 'error' });
      }
      setLoading(l => ({ ...l, modules: false }));
    }

    async function loadChapitres(module) {
      setLoading(l => ({ ...l, chapitres: true }));
      const res = await api.request('GET', `/api/banque/${module}/chapitres`);
      if (res.ok) {
        setChapitres(res.data?.chapitres || []);
      } else {
        toast({ title: 'Erreur', message: 'Impossible de charger les chapitres', type: 'error' });
      }
      setLoading(l => ({ ...l, chapitres: false }));
    }

    async function loadThemes(module, chapitre) {
      setLoading(l => ({ ...l, themes: true }));
      const res = await api.request('GET', `/api/banque/${module}/${chapitre}/themes`);
      if (res.ok) {
        setThemes(res.data?.themes || []);
        // Charger les counts par thème en parallèle
        const counts = {};
        await Promise.all((res.data?.themes || []).map(async (theme) => {
          const qRes = await api.request('GET', `/api/banque/${module}/${chapitre}/${theme}`);
          if (qRes.ok) {
            counts[theme] = qRes.data?.count || 0;
          }
        }));
        setThemeCounts(counts);
      } else {
        toast({ title: 'Erreur', message: 'Impossible de charger les thèmes', type: 'error' });
      }
      setLoading(l => ({ ...l, themes: false }));
    }

    async function loadQuestions(module, chapitre, theme) {
      setLoading(l => ({ ...l, questions: true }));
      const res = await api.request('GET', `/api/banque/${module}/${chapitre}/${theme}`);
      if (res.ok) {
        setQuestions(res.data?.questions || []);
      } else {
        toast({ title: 'Erreur', message: 'Impossible de charger les questions', type: 'error' });
      }
      setLoading(l => ({ ...l, questions: false }));
    }

    // Navigation : descendre dans la hiérarchie
    function selectModule(mod) {
      setPath({ module: mod, chapitre: null, theme: null, questionId: null });
      loadChapitres(mod);
    }

    function selectChapitre(chap) {
      setPath(p => ({ ...p, chapitre: chap, theme: null, questionId: null }));
      loadThemes(path.module, chap);
    }

    function selectTheme(theme) {
      setPath(p => ({ ...p, theme, questionId: null }));
      loadQuestions(path.module, path.chapitre, theme);
    }

    function selectQuestion(q) {
      setPath(p => ({ ...p, questionId: q.id }));
      setCurrentQuestion(q);
      // Scroll to top pour voir la question
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Navigation : retour arrière via breadcrumb
    function navigateTo(level) {
      // level 0 = Banque, 1 = module, 2 = chapitre, 3 = theme, 4 = question
      if (level === 0) {
        setPath({ module: null, chapitre: null, theme: null, questionId: null });
      } else if (level === 1) {
        setPath(p => ({ ...p, chapitre: null, theme: null, questionId: null }));
      } else if (level === 2) {
        setPath(p => ({ ...p, theme: null, questionId: null }));
      } else if (level === 3) {
        setPath(p => ({ ...p, questionId: null }));
        setCurrentQuestion(null);
      }
    }

    // Render selon le niveau courant
    let content;
    if (path.questionId && currentQuestion) {
      content = <QuestionDetail question={currentQuestion} onBack={() => navigateTo(3)} />;
    } else if (path.theme) {
      content = <QuestionsList questions={questions} onSelect={selectQuestion} loading={loading.questions} />;
    } else if (path.chapitre) {
      content = <ThemesList themes={themes} onSelect={selectTheme} loading={loading.themes} themeCounts={themeCounts} />;
    } else if (path.module) {
      content = <ChapitresList chapitres={chapitres} onSelect={selectChapitre} loading={loading.chapitres} />;
    } else {
      content = <ModulesList modules={modules} onSelect={selectModule} loading={loading.modules} />;
    }

    return (
      <div>
        <Breadcrumb path={path} onNavigate={navigateTo} />
        {content}
      </div>
    );
  }

  // ==========================================================================
  // EXPORT
  // ==========================================================================

  root.BanqueBrowser = BanqueBrowser;
  root.MathText = MathText;
  root.LevelBadge = LevelBadge;

})(window);
