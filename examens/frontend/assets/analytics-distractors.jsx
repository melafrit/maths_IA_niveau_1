/* ============================================================================
   analytics-distractors.jsx — Vue dédiée "Distracteurs"

   Affiche toutes les questions de l'examen avec :
     - Badge difficulté
     - Taux de réussite (barre colorée)
     - 4 options A/B/C/D avec :
       * Badge ✓ (correcte) ou ✗ (incorrecte)
       * Barre horizontale proportionnelle
       * Texte complet
       * Count + %
       * Highlight "🎯 Piège efficace" sur le distracteur le + choisi
     - Filtres :
       * "Problématiques" (taux <50%)
       * Recherche par enonce
       * Tri par : difficulté / taux / total

   Composant exporte : window.AnalyticsDistractors

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useMemo } = React;

  function successColor(pct) {
    if (pct === null || pct === undefined) return '#64748b';
    if (pct >= 75) return '#16a34a';
    if (pct >= 50) return '#d97706';
    return '#dc2626';
  }

  function difficulteColor(diff) {
    switch (diff) {
      case 'facile': return '#16a34a';
      case 'moyen': return '#d97706';
      case 'difficile': return '#dc2626';
      case 'tres_difficile': return '#7c2d12';
      default: return '#64748b';
    }
  }

  // ==========================================================================
  // Card pour une question
  // ==========================================================================

  function QuestionDistractorCard({ question, index, isExpanded, onToggle }) {
    const { MathText } = root;

    // Trouver l'option incorrecte la plus choisie (piège efficace)
    const incorrectOptions = question.option_analysis.filter(o => !o.is_correct);
    const mostChosenWrong = incorrectOptions.reduce((max, opt) =>
      opt.count > (max?.count || 0) ? opt : max, null
    );

    const maxCount = Math.max(...question.option_analysis.map(o => o.count), 1);

    const difficultyColor = difficulteColor(question.difficulte);

    return (
      <div style={{
        background: 'var(--color-bg-elevated)',
        border: '1px solid var(--color-border)',
        borderLeft: `4px solid ${successColor(question.success_rate_pct)}`,
        borderRadius: 'var(--radius-md)',
        padding: 'var(--space-4)',
        marginBottom: 'var(--space-3)',
      }}>
        {/* Header */}
        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'flex-start',
            gap: 16,
            cursor: 'pointer',
          }}
          onClick={onToggle}
        >
          <div style={{ flex: 1 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 8 }}>
              <span style={{
                width: 32, height: 32, borderRadius: 8,
                background: successColor(question.success_rate_pct),
                color: 'white',
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontWeight: 700,
                fontSize: 13,
              }}>
                {index + 1}
              </span>
              <code style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>
                {question.question_id}
              </code>
              <span style={{
                padding: '3px 10px',
                borderRadius: 999,
                fontSize: 10,
                fontWeight: 600,
                textTransform: 'uppercase',
                background: difficultyColor + '22',
                color: difficultyColor,
              }}>
                {question.difficulte || '—'}
              </span>
              {question.type && (
                <span style={{
                  padding: '3px 10px',
                  borderRadius: 999,
                  fontSize: 10,
                  background: 'var(--color-bg-subtle)',
                  color: 'var(--color-text-muted)',
                }}>
                  {question.type}
                </span>
              )}
            </div>

            {question.enonce && MathText && (
              <div style={{ fontSize: 13, lineHeight: 1.5, color: 'var(--color-text)' }}>
                <MathText text={question.enonce} as="div" />
              </div>
            )}
          </div>

          <div style={{ textAlign: 'right', minWidth: 120 }}>
            <div style={{
              fontSize: 24,
              fontWeight: 800,
              color: successColor(question.success_rate_pct),
              lineHeight: 1,
            }}>
              {question.success_rate_pct}%
            </div>
            <div style={{
              fontSize: 11,
              color: 'var(--color-text-muted)',
              marginTop: 2,
            }}>
              {question.correct}/{question.total} corrects
            </div>
            {question.not_answered > 0 && (
              <div style={{
                fontSize: 11,
                color: '#d97706',
                marginTop: 2,
              }}>
                ⏭️ {question.not_answered} non rép.
              </div>
            )}
          </div>
        </div>

        {/* Barres des options */}
        <div style={{ marginTop: 'var(--space-3)' }}>
          {question.option_analysis.map((opt, i) => {
            const barWidth = maxCount > 0 ? (opt.count / maxCount) * 100 : 0;
            const isMostChosenWrong =
              mostChosenWrong && opt.index === mostChosenWrong.index && opt.count > 0;

            return (
              <div key={i} style={{
                display: 'flex',
                alignItems: 'center',
                gap: 12,
                padding: '8px 0',
                borderBottom: i < 3 ? '1px dashed var(--color-border)' : 'none',
              }}>
                {/* Letter badge */}
                <div style={{
                  width: 28, height: 28,
                  borderRadius: '50%',
                  background: opt.is_correct ? '#16a34a' : (isMostChosenWrong ? '#dc2626' : '#e2e8f0'),
                  color: opt.is_correct || isMostChosenWrong ? 'white' : 'var(--color-text)',
                  display: 'inline-flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  fontWeight: 700,
                  fontSize: 13,
                  flexShrink: 0,
                }}>
                  {opt.letter}
                </div>

                {/* Texte option */}
                <div style={{
                  flex: 1,
                  fontSize: 13,
                  color: opt.is_correct ? '#14532d' : (isMostChosenWrong ? '#7f1d1d' : 'var(--color-text)'),
                  fontWeight: opt.is_correct || isMostChosenWrong ? 500 : 400,
                  minWidth: 0,
                }}>
                  {opt.text || <span style={{ color: 'var(--color-text-muted)' }}>(pas de texte)</span>}
                  {' '}
                  {opt.is_correct && (
                    <span style={{
                      fontSize: 10,
                      fontWeight: 700,
                      padding: '2px 6px',
                      borderRadius: 4,
                      background: '#16a34a',
                      color: 'white',
                      marginLeft: 4,
                    }}>✓ BONNE RÉPONSE</span>
                  )}
                  {isMostChosenWrong && (
                    <span style={{
                      fontSize: 10,
                      fontWeight: 700,
                      padding: '2px 6px',
                      borderRadius: 4,
                      background: '#dc2626',
                      color: 'white',
                      marginLeft: 4,
                    }}>🎯 PIÈGE EFFICACE</span>
                  )}
                </div>

                {/* Barre + stat */}
                <div style={{
                  minWidth: 160,
                  display: 'flex',
                  alignItems: 'center',
                  gap: 8,
                }}>
                  <div style={{
                    flex: 1,
                    height: 10,
                    background: 'var(--color-bg-subtle)',
                    borderRadius: 5,
                    overflow: 'hidden',
                    position: 'relative',
                  }}>
                    <div style={{
                      position: 'absolute',
                      top: 0, left: 0, bottom: 0,
                      width: barWidth + '%',
                      background: opt.is_correct ? '#16a34a' :
                                  isMostChosenWrong ? '#dc2626' : '#94a3b8',
                      transition: 'width 0.3s ease',
                    }} />
                  </div>
                  <span style={{
                    fontSize: 12,
                    fontWeight: 600,
                    minWidth: 70,
                    textAlign: 'right',
                    color: opt.is_correct ? '#16a34a' :
                           isMostChosenWrong ? '#dc2626' : 'var(--color-text)',
                  }}>
                    {opt.count} ({opt.rate_pct}%)
                  </span>
                </div>
              </div>
            );
          })}
        </div>

        {/* Diagnostique pédagogique */}
        {(question.success_rate_pct < 50 || (mostChosenWrong && mostChosenWrong.rate_pct > 40)) && (
          <div style={{
            marginTop: 'var(--space-3)',
            padding: 'var(--space-2) var(--space-3)',
            background: 'rgba(249, 115, 22, 0.08)',
            borderLeft: '3px solid #f97316',
            borderRadius: 6,
            fontSize: 12,
            color: '#7c2d12',
          }}>
            <strong>💡 Diagnostic :</strong>{' '}
            {question.success_rate_pct < 30 && 'Question très mal réussie — revoir ou reformuler.'}
            {question.success_rate_pct >= 30 && question.success_rate_pct < 50 &&
              'Question difficile pour les étudiants.'}
            {mostChosenWrong && mostChosenWrong.rate_pct > 40 && question.success_rate_pct >= 50 &&
              ` Le distracteur ${mostChosenWrong.letter} (${mostChosenWrong.rate_pct}%) pose problème.`}
          </div>
        )}
      </div>
    );
  }

  // ==========================================================================
  // Composant principal AnalyticsDistractors
  // ==========================================================================

  function AnalyticsDistractors({ examenId }) {
    const { Spinner, Box, Button } = root.UI;
    const { useApi } = root.UIHooks;
    const api = useApi();

    const [loading, setLoading] = useState(true);
    const [data, setData] = useState(null);
    const [error, setError] = useState(null);

    // Filtres
    const [problematicOnly, setProblematicOnly] = useState(false);
    const [search, setSearch] = useState('');
    const [sort, setSort] = useState('difficulty'); // difficulty | success | total

    useEffect(() => {
      (async () => {
        setLoading(true);
        const res = await api.request('GET', `/api/analytics/examen/${examenId}/questions?with_details=true`);
        setLoading(false);
        if (res.ok) {
          setData(res.data);
        } else {
          setError(res.error?.message || 'Erreur');
        }
      })();
    }, [examenId]);

    const filteredQuestions = useMemo(() => {
      if (!data) return [];
      let arr = [...data.questions];

      if (problematicOnly) {
        arr = arr.filter(q => q.success_rate_pct < 50);
      }

      if (search) {
        const q = search.toLowerCase();
        arr = arr.filter(question =>
          (question.enonce || '').toLowerCase().includes(q) ||
          (question.question_id || '').toLowerCase().includes(q)
        );
      }

      // Tri
      switch (sort) {
        case 'success':
          arr.sort((a, b) => a.success_rate_pct - b.success_rate_pct);
          break;
        case 'total':
          arr.sort((a, b) => b.total - a.total);
          break;
        case 'difficulty':
        default:
          // Difficulté = plus difficile d'abord
          arr.sort((a, b) => a.success_rate_pct - b.success_rate_pct);
          break;
      }

      return arr;
    }, [data, problematicOnly, search, sort]);

    // Statistiques globales
    const stats = useMemo(() => {
      if (!data || !data.questions) return null;
      const total = data.questions.length;
      const easy = data.questions.filter(q => q.success_rate_pct >= 75).length;
      const medium = data.questions.filter(q => q.success_rate_pct >= 50 && q.success_rate_pct < 75).length;
      const hard = data.questions.filter(q => q.success_rate_pct < 50).length;
      return { total, easy, medium, hard };
    }, [data]);

    if (loading) return <div className="loading-wrap"><Spinner /></div>;
    if (error) return <Box type="error">⚠️ {error}</Box>;
    if (!data) return null;

    if (data.nb_questions === 0) {
      return (
        <div className="empty-state">
          <div className="empty-state-icon">🎯</div>
          <h3 style={{ margin: 0 }}>Pas encore de données</h3>
          <p>Les analyses des distracteurs seront disponibles après les premiers passages.</p>
        </div>
      );
    }

    return (
      <div>
        {/* Overview stats */}
        {stats && (
          <div className="kpi-grid" style={{ marginBottom: 'var(--space-3)' }}>
            <div className="kpi-card">
              <div className="kpi-icon">📝</div>
              <div className="kpi-content">
                <div className="kpi-label">Total questions</div>
                <div className="kpi-value accent-blue">{stats.total}</div>
                <div className="kpi-sub">{data.nb_passages} passages</div>
              </div>
            </div>
            <div className="kpi-card">
              <div className="kpi-icon">🟢</div>
              <div className="kpi-content">
                <div className="kpi-label">Faciles (≥75%)</div>
                <div className="kpi-value accent-green">{stats.easy}</div>
              </div>
            </div>
            <div className="kpi-card">
              <div className="kpi-icon">🟠</div>
              <div className="kpi-content">
                <div className="kpi-label">Moyennes (50-75%)</div>
                <div className="kpi-value accent-orange">{stats.medium}</div>
              </div>
            </div>
            <div className="kpi-card">
              <div className="kpi-icon">🔴</div>
              <div className="kpi-content">
                <div className="kpi-label">Difficiles (&lt;50%)</div>
                <div className="kpi-value accent-red">{stats.hard}</div>
                <div className="kpi-sub">À revoir</div>
              </div>
            </div>
          </div>
        )}

        {/* Filtres */}
        <div style={{
          display: 'flex',
          gap: 12,
          alignItems: 'center',
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          marginBottom: 'var(--space-3)',
          flexWrap: 'wrap',
        }}>
          <input
            type="text"
            className="passage-search"
            style={{ flex: 1, minWidth: 200 }}
            placeholder="🔍 Rechercher par énoncé ou ID..."
            value={search}
            onChange={e => setSearch(e.target.value)}
          />

          <select
            className="passage-select"
            value={sort}
            onChange={e => setSort(e.target.value)}
          >
            <option value="difficulty">Trier par difficulté (↓)</option>
            <option value="success">Trier par taux de réussite</option>
            <option value="total">Trier par nb passages</option>
          </select>

          <label className="passage-checkbox-wrap">
            <input
              type="checkbox"
              checked={problematicOnly}
              onChange={e => setProblematicOnly(e.target.checked)}
            />
            🔴 Problématiques uniquement
          </label>

          <div style={{
            fontSize: 12,
            color: 'var(--color-text-muted)',
            marginLeft: 'auto',
          }}>
            {filteredQuestions.length} / {data.nb_questions} question{data.nb_questions > 1 ? 's' : ''}
          </div>
        </div>

        {/* Liste des questions */}
        {filteredQuestions.length === 0 ? (
          <div className="empty-state">
            <div className="empty-state-icon">🔍</div>
            <h3 style={{ margin: 0 }}>Aucune question avec ces filtres</h3>
          </div>
        ) : (
          filteredQuestions.map((q, i) => (
            <QuestionDistractorCard
              key={q.question_id}
              question={q}
              index={i}
              isExpanded={true}
            />
          ))
        )}

        {/* Info aide */}
        <div style={{
          marginTop: 'var(--space-4)',
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          fontSize: 12,
          color: 'var(--color-text-muted)',
        }}>
          💡 <strong>Comprendre les distracteurs</strong> :<br/>
          ✓ <strong>BONNE RÉPONSE</strong> (vert) : l'option correcte<br/>
          🎯 <strong>PIÈGE EFFICACE</strong> (rouge) : le distracteur le plus souvent choisi.
          Si &gt;40% des étudiants le choisissent, il est probablement <em>trop séduisant</em> —
          à reformuler pour clarifier la distinction avec la bonne réponse.
        </div>
      </div>
    );
  }

  root.AnalyticsDistractors = AnalyticsDistractors;

})(window);
