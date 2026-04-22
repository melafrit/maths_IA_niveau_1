/* ============================================================================
   passage.jsx — App principale étudiant

   State machine :
     init → welcome → pre_exam → exam → result

   Responsabilités :
     - Gère les transitions entre les 3 phases
     - Récupération de token en localStorage pour reprise de session
     - Mount React

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect } = React;
  const { PassageWelcome, PassageExam, PassageResult } = window;

  function PassageApp() {
    const { ToastProvider, useToast, Button, Spinner } = root.UI;

    return (
      <ToastProvider>
        <PassageAppInner />
      </ToastProvider>
    );
  }

  function PassageAppInner() {
    const { useApi } = root.UIHooks;
    const { Button, Spinner } = root.UI;
    const api = useApi();

    // Phase : 'loading' | 'welcome' | 'exam' | 'result' | 'resume_prompt'
    const [phase, setPhase] = useState('loading');
    const [passageData, setPassageData] = useState(null);
    const [resultData, setResultData] = useState(null);
    const [resumeToken, setResumeToken] = useState(null);

    // ----- Au mount : détecter token en localStorage -----
    useEffect(() => {
      (async () => {
        try {
          const saved = localStorage.getItem('passage_token');
          if (!saved) {
            setPhase('welcome');
            return;
          }

          // Vérifier si le passage est toujours in_progress
          const res = await api.request('GET', `/api/passages/${saved}/progress`);
          if (res.ok && res.data?.status === 'in_progress') {
            setResumeToken(saved);
            setPhase('resume_prompt');
          } else {
            // Token expiré / invalide / déjà soumis → cleanup
            try {
              localStorage.removeItem('passage_token');
              localStorage.removeItem('passage_examen_id');
            } catch {}
            setPhase('welcome');
          }
        } catch (e) {
          setPhase('welcome');
        }
      })();
    }, []);

    // ----- Handlers transitions -----
    function handleStarted(data) {
      setPassageData(data);
      setPhase('exam');
    }

    async function handleResume() {
      const res = await api.request('GET', `/api/passages/${resumeToken}/progress`);
      if (res.ok && res.data?.status === 'in_progress') {
        setPassageData({
          token: resumeToken,
          examen: res.data.examen,
          questions: res.data.questions,
          answers: res.data.answers,
        });
        setPhase('exam');
      } else {
        try {
          localStorage.removeItem('passage_token');
          localStorage.removeItem('passage_examen_id');
        } catch {}
        setPhase('welcome');
      }
    }

    function handleNewExam() {
      try {
        localStorage.removeItem('passage_token');
        localStorage.removeItem('passage_examen_id');
      } catch {}
      setResumeToken(null);
      setPhase('welcome');
    }

    function handleSubmitted(data) {
      setResultData(data);
      setPhase('result');
    }

    function handleClose() {
      setPassageData(null);
      setResultData(null);
      setPhase('welcome');
    }

    // ----- Render -----

    if (phase === 'loading') {
      return (
        <div style={{
          minHeight: '100vh',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
        }}>
          <div style={{ textAlign: 'center' }}>
            <Spinner />
            <div style={{
              marginTop: 'var(--space-3)',
              color: 'var(--color-text-muted)',
            }}>
              Chargement...
            </div>
          </div>
        </div>
      );
    }

    if (phase === 'resume_prompt') {
      return (
        <div className="welcome-card">
          <div className="welcome-icon">🔄</div>
          <h1 className="welcome-title">Examen en cours détecté</h1>
          <p className="welcome-subtitle">
            Un passage d'examen était en cours sur ce navigateur. Souhaitez-vous
            le reprendre là où vous l'aviez laissé ?
          </p>

          <div style={{
            padding: 'var(--space-3)',
            background: 'var(--color-bg-subtle)',
            borderRadius: 'var(--radius-md)',
            margin: 'var(--space-3) 0',
            fontSize: 13,
            color: 'var(--color-text-muted)',
          }}>
            💡 Le chronomètre continue à tourner même pendant que cette page
            était fermée. Reprenez rapidement si vous choisissez de continuer.
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
            <Button
              variant="primary"
              onClick={handleResume}
              style={{ width: '100%', padding: '12px' }}
            >
              ↻ Reprendre le passage
            </Button>
            <Button
              variant="ghost"
              onClick={handleNewExam}
              style={{ width: '100%' }}
            >
              Démarrer un nouvel examen
            </Button>
          </div>
        </div>
      );
    }

    if (phase === 'welcome') {
      return (
        <div className="passage-container">
          <PassageWelcome onStarted={handleStarted} />
        </div>
      );
    }

    if (phase === 'exam' && passageData) {
      return (
        <PassageExam
          passageData={passageData}
          onSubmitted={handleSubmitted}
        />
      );
    }

    if (phase === 'result' && resultData) {
      return (
        <div className="passage-container">
          <PassageResult
            resultData={resultData}
            onClose={handleClose}
          />
        </div>
      );
    }

    // Fallback
    return (
      <div className="welcome-card">
        <p>État invalide. <Button variant="primary" onClick={handleClose}>Recharger</Button></p>
      </div>
    );
  }

  // Mount
  document.addEventListener('DOMContentLoaded', () => {
    const rootElement = document.getElementById('root');
    if (rootElement) {
      const reactRoot = ReactDOM.createRoot(rootElement);
      reactRoot.render(<PassageApp />);
    }
  });

})(window);
