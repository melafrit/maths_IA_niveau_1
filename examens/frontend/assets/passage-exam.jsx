/* ============================================================================
   passage-exam.jsx — Phase 2 : Passage examen

   Fonctionnalités :
     - Timer fixe en haut de page (countdown safe/warning/danger)
     - Navigation question par question
     - Options radio avec KaTeX
     - Auto-save sur chaque changement (POST /answer)
     - Focus-lock detection (blur, visibility, copy, paste, rightclick)
     - Progress bar
     - Modal de confirmation soumission
     - Bouton Précédent/Suivant + grille numérotée

   Composant exporté : window.PassageExam

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useRef, useCallback } = React;

  const MathText = root.MathText;

  function formatTime(sec) {
    if (sec < 0) sec = 0;
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;
    const pad = n => String(n).padStart(2, '0');
    if (h > 0) return `${pad(h)}:${pad(m)}:${pad(s)}`;
    return `${pad(m)}:${pad(s)}`;
  }

  // ==========================================================================
  // Composant Timer
  // ==========================================================================

  function Timer({ remainingSec, totalSec }) {
    const ratio = totalSec > 0 ? remainingSec / totalSec : 0;
    let cls = 'timer-safe';
    if (ratio < 0.1) cls = 'timer-danger';
    else if (ratio < 0.25) cls = 'timer-warning';

    return (
      <div className="passage-timer">
        <div>
          <div style={{
            fontSize: 11,
            color: 'var(--color-text-muted)',
            textTransform: 'uppercase',
            letterSpacing: 0.5,
            fontWeight: 600,
          }}>⏱️ Temps restant</div>
          <div className={`timer-value ${cls}`}>
            {formatTime(remainingSec)}
          </div>
        </div>
        <div style={{ textAlign: 'right' }}>
          <div style={{
            fontSize: 11,
            color: 'var(--color-text-muted)',
            textTransform: 'uppercase',
            letterSpacing: 0.5,
            fontWeight: 600,
          }}>Total</div>
          <div style={{
            fontFamily: 'var(--font-mono)',
            fontSize: 16,
            color: 'var(--color-text-muted)',
          }}>{formatTime(totalSec)}</div>
        </div>
      </div>
    );
  }

  // ==========================================================================
  // Composant NavGrid (grille de navigation questions)
  // ==========================================================================

  function NavGrid({ questions, currentIdx, answers, onGoTo }) {
    return (
      <div className="passage-nav">
        {questions.map((q, i) => {
          const isCurrent = i === currentIdx;
          const isAnswered = answers[q.id] !== undefined;
          let cls = 'nav-dot';
          if (isAnswered) cls += ' answered';
          if (isCurrent) cls += ' current';
          return (
            <button
              key={q.id}
              className={cls}
              onClick={() => onGoTo(i)}
              title={`Question ${i + 1}${isAnswered ? ' (répondue)' : ''}`}
            >
              {i + 1}
            </button>
          );
        })}
      </div>
    );
  }

  // ==========================================================================
  // Composant Question Card
  // ==========================================================================

  function QuestionCard({ question, index, total, selected, onSelect }) {
    const letters = ['A', 'B', 'C', 'D'];

    return (
      <div className="question-card">
        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          <div className="question-number">{index + 1}</div>
          <div style={{ flex: 1 }}>
            <div style={{
              fontSize: 11,
              textTransform: 'uppercase',
              letterSpacing: 0.5,
              color: 'var(--color-text-muted)',
              fontWeight: 600,
            }}>Question {index + 1} / {total}</div>
            <div style={{
              display: 'flex',
              gap: 8,
              marginTop: 2,
              fontSize: 12,
              color: 'var(--color-text-muted)',
            }}>
              <span>📊 {question.difficulte}</span>
              <span>·</span>
              <span>🎯 {question.type}</span>
            </div>
          </div>
        </div>

        <div className="question-enonce no-select">
          <MathText text={question.enonce} as="div" />
        </div>

        <div>
          {question.options.map((opt, i) => (
            <div
              key={i}
              className={`option-item ${selected === i ? 'selected' : ''}`}
              onClick={() => onSelect(i)}
            >
              <div className="option-letter">{letters[i]}</div>
              <div className="option-text no-select">
                <MathText text={opt} as="span" />
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  // ==========================================================================
  // Composant principal PassageExam
  // ==========================================================================

  function PassageExam({ passageData, onSubmitted }) {
    const { Button, Modal, useToast } = root.UI;
    const { useApi } = root.UIHooks;
    const api = useApi();
    const { toast } = useToast();

    // État principal
    const [questions] = useState(passageData.questions || []);
    const [token] = useState(passageData.token);
    const [examInfo] = useState(passageData.examen || {});
    const [answers, setAnswers] = useState(passageData.answers || {});
    const [currentIdx, setCurrentIdx] = useState(0);
    const [remainingSec, setRemainingSec] = useState(examInfo.duree_sec || 3600);
    const [submitting, setSubmitting] = useState(false);
    const [confirmSubmit, setConfirmSubmit] = useState(false);
    const [focusWarningCount, setFocusWarningCount] = useState(0);
    const [showBanner, setShowBanner] = useState(null);

    // Refs pour focus tracking
    const blurStartRef = useRef(null);
    const submittedRef = useRef(false);

    const totalQuestions = questions.length;
    const answeredCount = Object.keys(answers).length;
    const currentQuestion = questions[currentIdx];
    const currentAnswer = currentQuestion ? answers[currentQuestion.id]?.answer_index ?? null : null;

    // --------------------------------------------------------------
    // Timer tick + auto-submit si fini
    // --------------------------------------------------------------
    useEffect(() => {
      if (submittedRef.current) return;
      const interval = setInterval(() => {
        setRemainingSec(s => {
          if (s <= 1) {
            clearInterval(interval);
            if (!submittedRef.current) {
              toast({
                title: '⏰ Temps écoulé',
                message: 'Soumission automatique en cours...',
                type: 'warning',
              });
              doSubmit();
            }
            return 0;
          }
          return s - 1;
        });
      }, 1000);
      return () => clearInterval(interval);
    }, []);

    // --------------------------------------------------------------
    // Focus-lock : blur / focus / visibility_change
    // --------------------------------------------------------------
    useEffect(() => {
      if (!token) return;

      function logEvent(type, details = {}) {
        if (submittedRef.current) return;
        api.request('POST', `/api/passages/${token}/focus-event`, {
          type,
          duration_ms: details.duration_ms || 0,
          details: details.details || null,
        });
      }

      function handleBlur() {
        if (submittedRef.current) return;
        blurStartRef.current = Date.now();
        setFocusWarningCount(c => c + 1);
        setShowBanner({
          type: 'warning',
          message: '⚠️ Attention : vous avez quitté l\'onglet de l\'examen (enregistré)',
        });
      }

      function handleFocus() {
        if (submittedRef.current) return;
        const duration = blurStartRef.current ? Date.now() - blurStartRef.current : 0;
        if (blurStartRef.current) {
          logEvent('blur', { duration_ms: duration });
          blurStartRef.current = null;
        }
        logEvent('focus');

        // Masquer banner après 3s
        setTimeout(() => setShowBanner(null), 3000);
      }

      function handleVisibilityChange() {
        if (submittedRef.current) return;
        if (document.hidden) {
          logEvent('visibility_change', { details: 'tab_hidden' });
        } else {
          logEvent('visibility_change', { details: 'tab_visible' });
        }
      }

      function handleCopy(e) {
        if (submittedRef.current) return;
        e.preventDefault();
        logEvent('copy');
        toast({
          title: '🚫 Copie bloquée',
          message: 'La copie du contenu est interdite pendant l\'examen.',
          type: 'error',
        });
      }

      function handlePaste(e) {
        if (submittedRef.current) return;
        e.preventDefault();
        logEvent('paste');
        toast({
          title: '🚫 Collage bloqué',
          message: 'Le collage est interdit pendant l\'examen.',
          type: 'error',
        });
      }

      function handleContextMenu(e) {
        if (submittedRef.current) return;
        e.preventDefault();
        logEvent('rightclick');
      }

      function handleBeforeUnload(e) {
        if (submittedRef.current) return;
        e.preventDefault();
        e.returnValue = 'Examen en cours. Quitter la page perdra votre progression ?';
        return e.returnValue;
      }

      window.addEventListener('blur', handleBlur);
      window.addEventListener('focus', handleFocus);
      document.addEventListener('visibilitychange', handleVisibilityChange);
      document.addEventListener('copy', handleCopy);
      document.addEventListener('paste', handlePaste);
      document.addEventListener('contextmenu', handleContextMenu);
      window.addEventListener('beforeunload', handleBeforeUnload);

      return () => {
        window.removeEventListener('blur', handleBlur);
        window.removeEventListener('focus', handleFocus);
        document.removeEventListener('visibilitychange', handleVisibilityChange);
        document.removeEventListener('copy', handleCopy);
        document.removeEventListener('paste', handlePaste);
        document.removeEventListener('contextmenu', handleContextMenu);
        window.removeEventListener('beforeunload', handleBeforeUnload);
      };
    }, [token]);

    // --------------------------------------------------------------
    // Sauvegarder une réponse
    // --------------------------------------------------------------
    const saveAnswer = useCallback(async (questionId, answerIndex) => {
      // Update local immediately
      setAnswers(a => ({
        ...a,
        [questionId]: {
          answer_index: answerIndex,
          timestamp: new Date().toISOString(),
        },
      }));

      // Save remote (fire and forget)
      try {
        const res = await api.request('POST', `/api/passages/${token}/answer`, {
          question_id: questionId,
          answer_index: answerIndex,
        });
        if (!res.ok) {
          const err = res.error || {};
          if (err.code === 'expired') {
            toast({
              title: '⏰ Temps écoulé',
              message: 'Votre passage a expiré.',
              type: 'error',
            });
            await doSubmit();
          }
        }
      } catch (e) {
        toast({
          title: '⚠️ Problème réseau',
          message: 'La réponse est sauvegardée localement. Réessai en cours.',
          type: 'warning',
        });
      }
    }, [token]);

    // --------------------------------------------------------------
    // Navigation
    // --------------------------------------------------------------
    function goNext() {
      if (currentIdx < totalQuestions - 1) setCurrentIdx(i => i + 1);
    }
    function goPrev() {
      if (currentIdx > 0) setCurrentIdx(i => i - 1);
    }

    // --------------------------------------------------------------
    // Soumission
    // --------------------------------------------------------------
    async function doSubmit() {
      if (submittedRef.current) return;
      submittedRef.current = true;

      setSubmitting(true);
      const res = await api.request('POST', `/api/passages/${token}/submit`);
      setSubmitting(false);
      setConfirmSubmit(false);

      if (res.ok) {
        // Sauvegarder le token pour acceder a la correction plus tard
        try {
          localStorage.setItem('last_submitted_token', token);
          localStorage.removeItem('passage_token');
          localStorage.removeItem('passage_examen_id');
        } catch {}
        onSubmitted(res.data);
      } else {
        toast({
          title: 'Erreur soumission',
          message: res.error?.message || 'Impossible de soumettre',
          type: 'error',
        });
        submittedRef.current = false;
      }
    }

    // --------------------------------------------------------------
    // Render
    // --------------------------------------------------------------

    if (totalQuestions === 0) {
      return (
        <div style={{ padding: 'var(--space-6)', textAlign: 'center' }}>
          Aucune question disponible.
        </div>
      );
    }

    const progressPct = totalQuestions > 0 ? (answeredCount / totalQuestions) * 100 : 0;

    return (
      <div className="passage-container">
        {/* Timer fixe */}
        <Timer remainingSec={remainingSec} totalSec={examInfo.duree_sec || 3600} />

        {/* Banner anti-triche */}
        {showBanner && (
          <div className={`warning-banner ${showBanner.type === 'danger' ? 'danger' : ''}`}>
            {showBanner.message}
          </div>
        )}

        {/* Titre examen */}
        <h2 style={{ margin: '0 0 var(--space-2) 0' }}>
          📝 {examInfo.titre}
        </h2>
        <div style={{
          fontSize: 13,
          color: 'var(--color-text-muted)',
          marginBottom: 'var(--space-3)',
        }}>
          <strong>{answeredCount}</strong> / {totalQuestions} répondues
          {focusWarningCount > 0 && (
            <span style={{
              marginLeft: 12,
              color: '#d97706',
              fontWeight: 600,
            }}>
              ⚠️ {focusWarningCount} changement{focusWarningCount > 1 ? 's' : ''} d'onglet
            </span>
          )}
        </div>

        {/* Progress bar */}
        <div className="passage-progress">
          <div className="passage-progress-fill" style={{ width: `${progressPct}%` }}></div>
        </div>

        {/* Navigation grid */}
        <NavGrid
          questions={questions}
          currentIdx={currentIdx}
          answers={answers}
          onGoTo={setCurrentIdx}
        />

        {/* Question courante */}
        <QuestionCard
          question={currentQuestion}
          index={currentIdx}
          total={totalQuestions}
          selected={currentAnswer}
          onSelect={(i) => saveAnswer(currentQuestion.id, i)}
        />

        {/* Navigation bas */}
        <div style={{
          display: 'flex',
          gap: 8,
          justifyContent: 'space-between',
          alignItems: 'center',
          marginTop: 'var(--space-3)',
          padding: 'var(--space-3)',
          background: 'var(--color-bg-elevated)',
          border: '1px solid var(--color-border)',
          borderRadius: 'var(--radius-md)',
        }}>
          <Button
            variant="ghost"
            onClick={goPrev}
            disabled={currentIdx === 0}
          >
            ← Précédent
          </Button>

          <div style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>
            {currentIdx + 1} / {totalQuestions}
          </div>

          {currentIdx < totalQuestions - 1 ? (
            <Button variant="primary" onClick={goNext}>
              Suivant →
            </Button>
          ) : (
            <Button variant="primary" onClick={() => setConfirmSubmit(true)}>
              ✅ Terminer l'examen
            </Button>
          )}
        </div>

        {/* Bouton soumettre flottant toujours visible */}
        {answeredCount > 0 && currentIdx < totalQuestions - 1 && (
          <div style={{ textAlign: 'center', marginTop: 'var(--space-3)' }}>
            <Button
              variant="secondary"
              onClick={() => setConfirmSubmit(true)}
            >
              ✅ Terminer maintenant ({answeredCount}/{totalQuestions} répondues)
            </Button>
          </div>
        )}

        {/* Modal confirmation soumission */}
        <Modal
          isOpen={confirmSubmit}
          onClose={() => !submitting && setConfirmSubmit(false)}
          title="🎯 Confirmer la soumission"
        >
          <div>
            <p>Vous êtes sur le point de soumettre votre examen.</p>

            <div style={{
              padding: 'var(--space-3)',
              background: 'var(--color-bg-subtle)',
              borderRadius: 'var(--radius-md)',
              margin: 'var(--space-3) 0',
            }}>
              <div style={{ marginBottom: 8 }}>
                <strong>Réponses données :</strong> {answeredCount} / {totalQuestions}
              </div>
              {answeredCount < totalQuestions && (
                <div style={{ color: '#d97706', fontSize: 13 }}>
                  ⚠️ {totalQuestions - answeredCount} question{totalQuestions - answeredCount > 1 ? 's' : ''} sans réponse (compteront comme incorrect{totalQuestions - answeredCount > 1 ? 'es' : ''})
                </div>
              )}
            </div>

            <p style={{ color: '#dc2626', fontWeight: 500 }}>
              ⚠️ Cette action est définitive. Vous ne pourrez plus modifier vos réponses.
            </p>

            <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end', marginTop: 'var(--space-4)' }}>
              <Button
                variant="ghost"
                onClick={() => setConfirmSubmit(false)}
                disabled={submitting}
              >
                Annuler
              </Button>
              <Button
                variant="primary"
                onClick={doSubmit}
                disabled={submitting}
              >
                {submitting ? '⏳ Soumission...' : '✅ Confirmer et soumettre'}
              </Button>
            </div>
          </div>
        </Modal>
      </div>
    );
  }

  root.PassageExam = PassageExam;

})(window);
