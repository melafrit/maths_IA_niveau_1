/* ============================================================================
   passage-welcome.jsx — Phase 1 : Accueil étudiant

   Étapes :
     1. Saisie du code d'accès (6 chars)
     2. Validation via /api/examens/by-code/{code}
     3. Affichage des infos de l'examen
     4. Saisie nom / prénom / email
     5. Acceptation du règlement
     6. Bouton "Démarrer l'examen"

   Composant exporté : window.PassageWelcome

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState } = React;

  function formatDuree(sec) {
    if (!sec) return '—';
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    if (h > 0) return `${h}h${m.toString().padStart(2, '0')}`;
    return `${m} min`;
  }

  function formatDate(iso) {
    if (!iso) return '—';
    try {
      return new Date(iso).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
      });
    } catch {
      return iso;
    }
  }

  function PassageWelcome({ onStarted }) {
    const { Button, Input, Box } = root.UI;
    const { useApi } = root.UIHooks;
    const api = useApi();

    const [step, setStep] = useState(1);          // 1=code, 2=info
    const [code, setCode] = useState('');
    const [exam, setExam] = useState(null);
    const [checking, setChecking] = useState(false);
    const [codeError, setCodeError] = useState('');

    const [studentInfo, setStudentInfo] = useState({
      nom: '',
      prenom: '',
      email: '',
    });
    const [acceptRules, setAcceptRules] = useState(false);
    const [starting, setStarting] = useState(false);
    const [infoError, setInfoError] = useState('');

    // --------------------------------------------------------------
    // Étape 1 : Vérifier le code d'accès
    // --------------------------------------------------------------
    async function checkCode() {
      const normalized = code.trim().toUpperCase();
      if (normalized.length < 4) {
        setCodeError('Code trop court');
        return;
      }

      setChecking(true);
      setCodeError('');

      const res = await api.request('GET', `/api/examens/by-code/${normalized}`);

      setChecking(false);

      if (res.ok) {
        setExam(res.data);
        setStep(2);
      } else {
        const err = res.error || {};
        let msg = err.message || 'Code invalide';

        // Messages plus conviviaux selon code erreur
        if (err.code === 'not_found') msg = 'Ce code ne correspond à aucun examen';
        else if (err.code === 'not_yet_open') {
          const sec = err.details?.opens_in_sec || 0;
          const h = Math.floor(sec / 3600);
          const m = Math.floor((sec % 3600) / 60);
          msg = `L'examen ouvre dans ${h > 0 ? `${h}h` : ''}${m}min`;
        }
        else if (err.code === 'closed') msg = 'Cet examen est fermé depuis le ' +
          formatDate(err.details?.date_cloture);
        else if (err.code === 'not_available') msg = 'Examen non disponible';

        setCodeError(msg);
      }
    }

    // --------------------------------------------------------------
    // Étape 2 : Démarrer le passage
    // --------------------------------------------------------------
    async function start() {
      setInfoError('');

      // Validation client
      const { nom, prenom, email } = studentInfo;
      if (!nom.trim() || !prenom.trim()) {
        setInfoError('Nom et prénom obligatoires');
        return;
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        setInfoError('Email invalide');
        return;
      }
      if (!acceptRules) {
        setInfoError('Vous devez accepter le règlement');
        return;
      }

      setStarting(true);

      const res = await api.request('POST', '/api/passages/start', {
        examen_id: exam.id,
        student_info: {
          nom: nom.trim(),
          prenom: prenom.trim(),
          email: email.trim().toLowerCase(),
        },
      });

      setStarting(false);

      if (res.ok) {
        // Sauvegarder token pour reprise
        try {
          localStorage.setItem('passage_token', res.data.token);
          localStorage.setItem('passage_examen_id', exam.id);
        } catch {}
        onStarted(res.data);
      } else {
        const err = res.error || {};
        let msg = err.message || 'Impossible de démarrer';
        if (err.code === 'max_passages_reached') {
          msg = `Vous avez déjà passé cet examen le nombre maximal de fois.`;
        } else if (err.code === 'validation_failed') {
          msg = err.message;
        }
        setInfoError(msg);
      }
    }

    // ==========================================================================
    // Render
    // ==========================================================================

    if (step === 1) {
      // ----- Étape 1 : Saisie du code -----
      return (
        <div className="welcome-card">
          <div className="welcome-icon">🎓</div>
          <h1 className="welcome-title">Examen en ligne</h1>
          <p className="welcome-subtitle">
            Entrez le code d'accès qui vous a été communiqué par votre enseignant.
          </p>

          <div style={{ marginBottom: 'var(--space-4)' }}>
            <label style={{
              display: 'block',
              fontSize: 'var(--text-sm)',
              fontWeight: 600,
              marginBottom: 6,
            }}>
              Code d'accès
            </label>
            <Input
              value={code}
              onChange={e => setCode(e.target.value.toUpperCase())}
              placeholder="EX. ABC123"
              style={{
                fontFamily: 'var(--font-mono)',
                fontSize: 22,
                letterSpacing: 4,
                textAlign: 'center',
                fontWeight: 700,
              }}
              maxLength={10}
              autoFocus
              onKeyDown={e => {
                if (e.key === 'Enter' && code.trim() && !checking) checkCode();
              }}
            />
            {codeError && (
              <Box type="error" style={{ marginTop: 8 }}>⚠️ {codeError}</Box>
            )}
          </div>

          <Button
            variant="primary"
            onClick={checkCode}
            disabled={checking || code.trim().length < 4}
            style={{ width: '100%', padding: '12px' }}
          >
            {checking ? '⏳ Vérification...' : '→ Continuer'}
          </Button>

          <div style={{
            marginTop: 'var(--space-5)',
            padding: 'var(--space-3)',
            background: 'var(--color-bg-subtle)',
            borderRadius: 'var(--radius-md)',
            fontSize: 12,
            color: 'var(--color-text-muted)',
          }}>
            💡 <strong>Astuce</strong> : le code est sensible à la casse et
            doit être saisi sans espace.
          </div>
        </div>
      );
    }

    // ----- Étape 2 : Saisie infos + règlement -----
    return (
      <div className="welcome-card">
        <div className="welcome-icon">📝</div>
        <h1 className="welcome-title">{exam.titre}</h1>
        {exam.description && (
          <p className="welcome-subtitle">{exam.description}</p>
        )}

        {/* Infos examen */}
        <div className="exam-info-grid">
          <div className="exam-info-item">
            <span className="exam-info-label">⏱️ Durée</span>
            <span className="exam-info-value">{formatDuree(exam.duree_sec)}</span>
          </div>
          <div className="exam-info-item">
            <span className="exam-info-label">❓ Nb questions</span>
            <span className="exam-info-value">{exam.nb_questions}</span>
          </div>
          <div className="exam-info-item">
            <span className="exam-info-label">📅 Ouverture</span>
            <span className="exam-info-value" style={{ fontSize: 13 }}>
              {formatDate(exam.date_ouverture)}
            </span>
          </div>
          <div className="exam-info-item">
            <span className="exam-info-label">🔒 Clôture</span>
            <span className="exam-info-value" style={{ fontSize: 13 }}>
              {formatDate(exam.date_cloture)}
            </span>
          </div>
        </div>

        {/* Formulaire infos étudiant */}
        <h3 style={{
          margin: 'var(--space-4) 0 var(--space-2) 0',
          fontSize: 'var(--text-base)',
        }}>Vos informations</h3>

        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
          <div>
            <label style={{ fontSize: 12, fontWeight: 600, display: 'block', marginBottom: 4 }}>
              Prénom <span style={{ color: '#dc2626' }}>*</span>
            </label>
            <Input
              value={studentInfo.prenom}
              onChange={e => setStudentInfo(s => ({ ...s, prenom: e.target.value }))}
              placeholder="Jean"
              autoFocus
            />
          </div>
          <div>
            <label style={{ fontSize: 12, fontWeight: 600, display: 'block', marginBottom: 4 }}>
              Nom <span style={{ color: '#dc2626' }}>*</span>
            </label>
            <Input
              value={studentInfo.nom}
              onChange={e => setStudentInfo(s => ({ ...s, nom: e.target.value }))}
              placeholder="Dupont"
            />
          </div>
        </div>

        <div style={{ marginTop: 12 }}>
          <label style={{ fontSize: 12, fontWeight: 600, display: 'block', marginBottom: 4 }}>
            Email <span style={{ color: '#dc2626' }}>*</span>
          </label>
          <Input
            type="email"
            value={studentInfo.email}
            onChange={e => setStudentInfo(s => ({ ...s, email: e.target.value }))}
            placeholder="jean.dupont@etu.ipssi.net"
          />
          <div style={{ fontSize: 11, color: 'var(--color-text-muted)', marginTop: 2 }}>
            Ne sera utilisé que pour la correction. Aucun spam.
          </div>
        </div>

        {/* Règlement */}
        <div style={{
          marginTop: 'var(--space-4)',
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          fontSize: 12,
          lineHeight: 1.6,
        }}>
          <strong>📋 Règles du passage</strong>
          <ul style={{ margin: '8px 0 0 20px', padding: 0 }}>
            <li>Ne pas quitter la page une fois l'examen démarré</li>
            <li>Copier/coller et clic droit sont désactivés</li>
            <li>Tout changement d'onglet est enregistré</li>
            <li>Le chronomètre ne s'arrête pas en cas d'interruption</li>
            <li>Vos réponses sont sauvegardées automatiquement</li>
            <li>Vous ne pouvez passer cet examen que <strong>{exam.max_passages}</strong> fois</li>
          </ul>
        </div>

        <label style={{
          display: 'flex',
          alignItems: 'flex-start',
          gap: 8,
          marginTop: 'var(--space-3)',
          cursor: 'pointer',
        }}>
          <input
            type="checkbox"
            checked={acceptRules}
            onChange={e => setAcceptRules(e.target.checked)}
            style={{ marginTop: 2 }}
          />
          <span style={{ fontSize: 13 }}>
            J'accepte le règlement et m'engage à passer cet examen de manière honnête.
          </span>
        </label>

        {infoError && (
          <Box type="error" style={{ marginTop: 'var(--space-3)' }}>
            ⚠️ {infoError}
          </Box>
        )}

        {/* Actions */}
        <div style={{ display: 'flex', gap: 8, marginTop: 'var(--space-4)' }}>
          <Button
            variant="ghost"
            onClick={() => { setStep(1); setExam(null); setCode(''); }}
            disabled={starting}
          >
            ← Retour
          </Button>
          <Button
            variant="primary"
            onClick={start}
            disabled={starting || !acceptRules || !studentInfo.nom || !studentInfo.prenom || !studentInfo.email}
            style={{ flex: 1, padding: '12px' }}
          >
            {starting ? '⏳ Démarrage...' : '🚀 Démarrer l\'examen'}
          </Button>
        </div>
      </div>
    );
  }

  root.PassageWelcome = PassageWelcome;

})(window);
