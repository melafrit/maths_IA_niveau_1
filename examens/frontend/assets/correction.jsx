/* ============================================================================
   correction.jsx — App de correction étudiant

   Récupère token depuis URL (?token=...) ou localStorage
   Charge GET /api/corrections/{token}
   Affiche :
     - Header avec score + mention
     - Stats (% correct, difficultés, types)
     - Filtres (Toutes/Correctes/Incorrectes/Non répondues)
     - Cards par question avec :
       * Énoncé + options avec surlignage correct/wrong-chosen
       * Hint, Explication, Pièges
     - Bouton PDF (window.print)

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useMemo } = React;
  const MathText = root.MathText;

  function formatDuration(sec) {
    if (!sec) return '—';
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;
    const pad = n => String(n).padStart(2, '0');
    if (h > 0) return `${h}h ${pad(m)}min`;
    if (m > 0) return `${m}min ${pad(s)}s`;
    return `${s}s`;
  }

  function formatDate(iso) {
    if (!iso) return '—';
    try {
      return new Date(iso).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
      });
    } catch { return iso; }
  }

  function getMention(pct) {
    if (pct >= 90) return { label: 'Excellent', emoji: '🏆', color: '#d97706' };
    if (pct >= 80) return { label: 'Très bien', emoji: '⭐', color: '#16a34a' };
    if (pct >= 70) return { label: 'Bien', emoji: '✨', color: '#22c55e' };
    if (pct >= 60) return { label: 'Assez bien', emoji: '👍', color: '#3b82f6' };
    if (pct >= 50) return { label: 'Passable', emoji: '📈', color: '#6366f1' };
    if (pct >= 30) return { label: 'À améliorer', emoji: '📚', color: '#d97706' };
    return { label: 'À revoir', emoji: '💪', color: '#dc2626' };
  }

  // ==========================================================================
  // Question card
  // ==========================================================================

  function QuestionCard({ question, index }) {
    const letters = ['A', 'B', 'C', 'D'];

    let cardClass = 'correction-question';
    let numClass = 'question-num';
    let badge = null;

    if (!question.was_answered) {
      cardClass += ' skipped';
      numClass += ' skipped';
      badge = <span className="question-badge badge-skipped">⏭️ Non répondue</span>;
    } else if (question.is_correct) {
      cardClass += ' correct';
      numClass += ' correct';
      badge = <span className="question-badge badge-correct">✅ Correct</span>;
    } else {
      cardClass += ' incorrect';
      numClass += ' incorrect';
      badge = <span className="question-badge badge-incorrect">❌ Incorrect</span>;
    }

    return (
      <div className={cardClass}>
        <div className="question-head">
          <div className={numClass}>{index + 1}</div>
          <div style={{ flex: 1 }}>
            <div style={{ display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>
              {badge}
              <span style={{
                fontSize: 11,
                color: 'var(--color-text-muted)',
              }}>
                {question.difficulte} · {question.type}
              </span>
            </div>
            <code style={{
              fontSize: 10,
              color: 'var(--color-text-muted)',
              display: 'block',
              marginTop: 2,
            }}>{question.id}</code>
          </div>
        </div>

        <div className="question-enonce">
          <MathText text={question.enonce} as="div" />
        </div>

        {/* Options */}
        <div>
          {question.options.map((opt, i) => {
            const isCorrect = i === question.correct_answer_index;
            const isChosen = i === question.user_answer_index;
            const isWrongChosen = isChosen && !isCorrect;

            let optClass = 'option-corr';
            let markerText = null;
            let markerClass = null;

            if (isCorrect) {
              optClass += ' is-correct';
              markerText = '✓ Bonne réponse';
              markerClass = 'correct';
            }
            if (isWrongChosen) {
              optClass += ' is-wrong-chosen';
              markerText = '✗ Votre choix';
              markerClass = 'wrong';
            }
            if (isChosen && isCorrect) {
              markerText = '✓ Votre réponse (correcte)';
              markerClass = 'correct';
            }

            return (
              <div key={i} className={optClass}>
                <div className="opt-letter">{letters[i]}</div>
                <div style={{ flex: 1 }}>
                  <MathText text={opt} as="span" />
                </div>
                {markerText && (
                  <span className={`opt-marker ${markerClass}`}>{markerText}</span>
                )}
              </div>
            );
          })}
        </div>

        {/* Hint */}
        {question.hint && (
          <div className="info-box info-box-hint">
            <div className="info-box-label">💡 Hint</div>
            <MathText text={question.hint} as="div" />
          </div>
        )}

        {/* Explication */}
        {question.explanation && (
          <div className="info-box info-box-explanation">
            <div className="info-box-label">📖 Explication</div>
            <MathText text={question.explanation} as="div" />
          </div>
        )}

        {/* Pièges */}
        {question.traps && (
          <div className="info-box info-box-traps">
            <div className="info-box-label">⚠️ Pièges à éviter</div>
            <MathText text={question.traps} as="div" />
          </div>
        )}

        {question.references && (
          <div style={{
            marginTop: 10,
            fontSize: 11,
            color: 'var(--color-text-muted)',
          }}>
            📚 {question.references}
          </div>
        )}
      </div>
    );
  }

  // ==========================================================================
  // Composant principal
  // ==========================================================================

  function CorrectionApp() {
    const { ToastProvider } = root.UI;
    return (
      <ToastProvider>
        <CorrectionAppInner />
      </ToastProvider>
    );
  }

  function CorrectionAppInner() {
    const { Button, Spinner, useToast } = root.UI;
    const { useApi } = root.UIHooks;
    const api = useApi();
    const { toast } = useToast();

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [data, setData] = useState(null);
    const [filter, setFilter] = useState('all'); // all | correct | incorrect | skipped

    // Charger au mount
    useEffect(() => {
      (async () => {
        // Récupérer token depuis URL ou localStorage
        const urlParams = new URLSearchParams(window.location.search);
        let token = urlParams.get('token');

        if (!token) {
          // Fallback : dernier token connu
          try {
            token = localStorage.getItem('last_submitted_token');
          } catch {}
        }

        if (!token) {
          setError({
            code: 'no_token',
            message: 'Aucun token de correction fourni. Utilisez le lien envoyé par email ou fourni après votre passage.',
          });
          setLoading(false);
          return;
        }

        const res = await api.request('GET', `/api/corrections/${token}`);

        if (res.ok) {
          setData(res.data);
        } else {
          const err = res.error || {};
          let msg = err.message || 'Impossible de charger la correction';

          if (err.code === 'not_found') {
            msg = 'Correction introuvable. Vérifiez le token.';
          } else if (err.code === 'not_ready') {
            msg = 'Passage pas encore soumis.';
          } else if (err.code === 'correction_disabled') {
            msg = 'La correction n\'est pas activée pour cet examen.';
          } else if (err.code === 'delay_not_elapsed') {
            const secLeft = err.details?.available_in_sec || 0;
            const minLeft = Math.ceil(secLeft / 60);
            msg = `La correction sera disponible dans ${minLeft} minute(s).`;
          }

          setError({ code: err.code, message: msg, details: err.details });
        }

        setLoading(false);
      })();
    }, []);

    // Filtrage
    const filteredQuestions = useMemo(() => {
      if (!data) return [];
      const questions = data.questions || [];
      switch (filter) {
        case 'correct':
          return questions.filter(q => q.is_correct);
        case 'incorrect':
          return questions.filter(q => q.was_answered && !q.is_correct);
        case 'skipped':
          return questions.filter(q => !q.was_answered);
        default:
          return questions;
      }
    }, [data, filter]);

    const counts = useMemo(() => {
      if (!data) return { all: 0, correct: 0, incorrect: 0, skipped: 0 };
      const questions = data.questions || [];
      return {
        all: questions.length,
        correct: questions.filter(q => q.is_correct).length,
        incorrect: questions.filter(q => q.was_answered && !q.is_correct).length,
        skipped: questions.filter(q => !q.was_answered).length,
      };
    }, [data]);

    // ----- Render -----

    if (loading) {
      return (
        <div style={{
          minHeight: '100vh',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
        }}>
          <div style={{ textAlign: 'center' }}>
            <Spinner />
            <div style={{ marginTop: 'var(--space-3)', color: 'var(--color-text-muted)' }}>
              Chargement de la correction...
            </div>
          </div>
        </div>
      );
    }

    if (error) {
      return (
        <div className="error-card">
          <div style={{ fontSize: 48, marginBottom: 16 }}>⚠️</div>
          <h2 style={{ margin: '0 0 12px 0' }}>Correction indisponible</h2>
          <p style={{ color: 'var(--color-text-muted)' }}>{error.message}</p>
          <Button
            variant="primary"
            onClick={() => window.location.href = '/etudiant/passage.html'}
            style={{ marginTop: 16 }}
          >
            ← Retour à l'accueil
          </Button>
        </div>
      );
    }

    if (!data) return null;

    const { passage, examen, questions, stats_by_difficulte, stats_by_type } = data;
    const pct = passage.score_pct || 0;
    const mention = getMention(pct);

    return (
      <div className="correction-container">
        {/* Header */}
        <div className="correction-header">
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: 16 }}>
            <div>
              <div style={{
                fontSize: 12,
                textTransform: 'uppercase',
                letterSpacing: 0.5,
                color: 'var(--color-text-muted)',
                fontWeight: 600,
                marginBottom: 4,
              }}>📝 Correction</div>
              <h1 style={{ margin: 0, fontSize: 'var(--text-2xl)' }}>
                {examen.titre}
              </h1>
              <p style={{
                margin: '6px 0 0 0',
                color: 'var(--color-text-muted)',
                fontSize: 'var(--text-sm)',
              }}>
                {passage.student_info.prenom} {passage.student_info.nom} · {passage.student_info.email}
              </p>
            </div>

            <div className="print-hidden">
              <Button
                variant="primary"
                onClick={() => window.print()}
                style={{ padding: '8px 16px' }}
              >
                🖨️ Télécharger PDF
              </Button>
            </div>
          </div>

          {/* Score */}
          <div style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            gap: 16,
            marginTop: 'var(--space-4)',
            paddingTop: 'var(--space-4)',
            borderTop: '1px solid var(--color-border)',
          }}>
            <div>
              <div style={{ display: 'flex', alignItems: 'baseline', gap: 8 }}>
                <span className="correction-score-big">{passage.score_brut}</span>
                <span style={{
                  fontSize: 24,
                  color: 'var(--color-text-muted)',
                  fontWeight: 300,
                }}>/ {passage.score_max}</span>
              </div>
              <div style={{
                fontSize: 18,
                fontWeight: 700,
                color: mention.color,
                marginTop: 4,
              }}>
                {pct.toFixed(1)}% · {mention.emoji} {mention.label}
              </div>
            </div>

            <div style={{
              textAlign: 'right',
              fontSize: 13,
              color: 'var(--color-text-muted)',
            }}>
              <div>⏱️ Durée : {formatDuration(passage.duration_sec)}</div>
              <div>📅 Soumis : {formatDate(passage.end_time)}</div>
            </div>
          </div>
        </div>

        {/* Stats par difficulté + type */}
        {(Object.keys(stats_by_difficulte || {}).length > 0) && (
          <div className="stats-grid">
            <div className="stats-item">
              <div className="stats-value" style={{ color: '#16a34a' }}>{counts.correct}</div>
              <div className="stats-label">Correctes</div>
            </div>
            <div className="stats-item">
              <div className="stats-value" style={{ color: '#dc2626' }}>{counts.incorrect}</div>
              <div className="stats-label">Incorrectes</div>
            </div>
            <div className="stats-item">
              <div className="stats-value" style={{ color: '#d97706' }}>{counts.skipped}</div>
              <div className="stats-label">Non répondues</div>
            </div>
            {Object.entries(stats_by_difficulte).map(([diff, s]) => (
              <div key={diff} className="stats-item">
                <div className="stats-value" style={{ fontSize: 18 }}>
                  {s.correct}/{s.total}
                </div>
                <div className="stats-label">{diff}</div>
              </div>
            ))}
          </div>
        )}

        {/* Filtres */}
        <div className="filter-tabs print-hidden">
          <button
            className={`filter-tab ${filter === 'all' ? 'active' : ''}`}
            onClick={() => setFilter('all')}
          >
            📋 Toutes ({counts.all})
          </button>
          <button
            className={`filter-tab ${filter === 'correct' ? 'active' : ''}`}
            onClick={() => setFilter('correct')}
          >
            ✅ Correctes ({counts.correct})
          </button>
          <button
            className={`filter-tab ${filter === 'incorrect' ? 'active' : ''}`}
            onClick={() => setFilter('incorrect')}
          >
            ❌ Incorrectes ({counts.incorrect})
          </button>
          {counts.skipped > 0 && (
            <button
              className={`filter-tab ${filter === 'skipped' ? 'active' : ''}`}
              onClick={() => setFilter('skipped')}
            >
              ⏭️ Non répondues ({counts.skipped})
            </button>
          )}
        </div>

        {/* Liste des questions */}
        {filteredQuestions.length === 0 ? (
          <div className="empty-state">
            <div style={{ fontSize: 48, marginBottom: 12 }}>🎯</div>
            <h3 style={{ margin: 0 }}>Aucune question dans ce filtre</h3>
            <p style={{ margin: '4px 0 0 0' }}>
              {filter === 'correct' && 'Aucune réponse correcte.'}
              {filter === 'incorrect' && 'Aucune réponse incorrecte — bravo !'}
              {filter === 'skipped' && 'Toutes les questions ont été répondues.'}
            </p>
          </div>
        ) : (
          filteredQuestions.map((q, i) => {
            // Retrouver l'index original dans questions pour numérotation stable
            const origIdx = questions.findIndex(qq => qq.id === q.id);
            return <QuestionCard key={q.id} question={q} index={origIdx} />;
          })
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
          🏫 IPSSI — Correction générée le {formatDate(data.generated_at)}
          {' · '}
          © 2026 Mohamed EL AFRIT — CC BY-NC-SA 4.0
        </div>

        <div className="print-hidden" style={{
          textAlign: 'center',
          marginTop: 'var(--space-4)',
        }}>
          <Button
            variant="ghost"
            onClick={() => window.location.href = '/etudiant/passage.html'}
          >
            ← Retour à l'accueil
          </Button>
        </div>
      </div>
    );
  }

  // Mount (immediate — Babel Standalone runs after DOMContentLoaded)
  const rootElement = document.getElementById('root');
  if (rootElement) {
    ReactDOM.createRoot(rootElement).render(<CorrectionApp />);
  }

})(window);
