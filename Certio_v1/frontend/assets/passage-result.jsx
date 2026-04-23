/* ============================================================================
   passage-result.jsx — Phase 3 : Affichage du résultat après soumission

   Affiche :
     - Score géant (ex: 15/20)
     - Pourcentage et mention
     - Message personnalisé selon score
     - Durée totale
     - Signature (16 premiers chars)
     - Bouton "Voir la correction" si disponible
     - Bouton "Terminer" (retour accueil)

   Composant exporté : window.PassageResult

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState } = React;

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

  function getMention(pct) {
    if (pct >= 90) return { label: 'Excellent', emoji: '🏆', color: '#facc15' };
    if (pct >= 80) return { label: 'Très bien', emoji: '⭐', color: '#16a34a' };
    if (pct >= 70) return { label: 'Bien', emoji: '✨', color: '#22c55e' };
    if (pct >= 60) return { label: 'Assez bien', emoji: '👍', color: '#3b82f6' };
    if (pct >= 50) return { label: 'Passable', emoji: '📈', color: '#6366f1' };
    if (pct >= 30) return { label: 'À améliorer', emoji: '📚', color: '#d97706' };
    return { label: 'À revoir', emoji: '💪', color: '#dc2626' };
  }

  function getMessage(pct) {
    if (pct >= 90) return 'Performance exceptionnelle ! Vous maîtrisez parfaitement ce sujet.';
    if (pct >= 80) return 'Très bon travail ! Vous avez bien compris les concepts.';
    if (pct >= 70) return 'Bonne performance. Quelques points à consolider.';
    if (pct >= 60) return 'Résultat correct. Continuez à travailler les notions incertaines.';
    if (pct >= 50) return 'Vous avez les bases. Un peu plus de pratique sera bénéfique.';
    if (pct >= 30) return 'Il reste du chemin. Reprenez les notions fondamentales.';
    return 'Concentrez-vous sur la révision des bases avant de retenter.';
  }

  function PassageResult({ resultData, onClose }) {
    const { Button } = root.UI;
    const [viewingDetails, setViewingDetails] = useState(false);

    const score = resultData.score || {};
    const pct = score.pct || 0;
    const mention = getMention(pct);
    const message = getMessage(pct);

    return (
      <div className="welcome-card" style={{ maxWidth: 700 }}>
        {/* Header celebratoire */}
        <div style={{ textAlign: 'center', marginBottom: 'var(--space-4)' }}>
          <div style={{ fontSize: 72, marginBottom: 8 }}>{mention.emoji}</div>
          <h1 style={{
            margin: 0,
            fontSize: 'var(--text-2xl)',
            color: mention.color,
          }}>
            {mention.label}
          </h1>
          <p style={{
            margin: '8px 0 0 0',
            color: 'var(--color-text-muted)',
            fontSize: 'var(--text-sm)',
          }}>
            {message}
          </p>
        </div>

        {/* Score géant */}
        <div className="result-score">
          <div>
            <span className="score-number">{score.brut ?? 0}</span>
            <span className="score-max"> / {score.max ?? 0}</span>
          </div>
          <div className="score-pct" style={{ color: mention.color }}>
            {pct.toFixed(1)}%
          </div>
        </div>

        {/* Détails */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(2, 1fr)',
          gap: 12,
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          marginBottom: 'var(--space-3)',
        }}>
          <div>
            <div style={{ fontSize: 11, color: 'var(--color-text-muted)', textTransform: 'uppercase', letterSpacing: 0.5, fontWeight: 600 }}>⏱️ Durée</div>
            <div style={{ fontSize: 'var(--text-base)', fontWeight: 600 }}>
              {formatDuration(resultData.duration_sec)}
            </div>
          </div>
          <div>
            <div style={{ fontSize: 11, color: 'var(--color-text-muted)', textTransform: 'uppercase', letterSpacing: 0.5, fontWeight: 600 }}>📊 Status</div>
            <div style={{ fontSize: 'var(--text-base)', fontWeight: 600, color: '#16a34a' }}>
              ✅ Soumis
            </div>
          </div>
          <div>
            <div style={{ fontSize: 11, color: 'var(--color-text-muted)', textTransform: 'uppercase', letterSpacing: 0.5, fontWeight: 600 }}>📅 Soumis le</div>
            <div style={{ fontSize: 13, fontWeight: 500 }}>
              {new Date(resultData.end_time).toLocaleString('fr-FR', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
              })}
            </div>
          </div>
          <div>
            <div style={{ fontSize: 11, color: 'var(--color-text-muted)', textTransform: 'uppercase', letterSpacing: 0.5, fontWeight: 600 }}>🔐 Signature</div>
            <div style={{ fontSize: 11, fontFamily: 'var(--font-mono)', color: 'var(--color-text-muted)' }}>
              {resultData.signature}
            </div>
          </div>
        </div>

        {/* Correction */}
        {resultData.correction_available ? (
          <div style={{
            padding: 'var(--space-3)',
            background: 'rgba(34, 197, 94, 0.08)',
            border: '1px solid #16a34a',
            borderRadius: 'var(--radius-md)',
            marginBottom: 'var(--space-3)',
          }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 4 }}>
              <span style={{ fontSize: 18 }}>✅</span>
              <strong>Correction détaillée disponible</strong>
            </div>
            <p style={{ margin: '0 0 12px 0', fontSize: 13, color: 'var(--color-text-muted)' }}>
              Consultez la correction avec les bonnes réponses et les explications pour chaque question.
            </p>
            <Button
              variant="primary"
              onClick={() => {
                const token = (function() {
                  try { return localStorage.getItem('last_submitted_token'); } catch { return null; }
                })();
                const url = token
                  ? `/etudiant/correction.html?token=${encodeURIComponent(token)}`
                  : '/etudiant/correction.html';
                window.location.href = url;
              }}
              style={{ padding: '8px 16px' }}
            >
              📖 Voir la correction détaillée
            </Button>
          </div>
        ) : (
          <div style={{
            padding: 'var(--space-3)',
            background: 'rgba(234, 179, 8, 0.08)',
            border: '1px solid #ca8a04',
            borderRadius: 'var(--radius-md)',
            marginBottom: 'var(--space-3)',
            fontSize: 13,
          }}>
            <strong>⏳ Correction différée</strong>
            {resultData.correction_delay_min > 0 && (
              <div style={{ marginTop: 4, color: 'var(--color-text-muted)' }}>
                La correction sera disponible dans {resultData.correction_delay_min} minutes.
              </div>
            )}
          </div>
        )}

        {/* Message de remerciement */}
        <div style={{
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          marginBottom: 'var(--space-3)',
          textAlign: 'center',
          fontSize: 'var(--text-sm)',
        }}>
          <div style={{ marginBottom: 8 }}>
            🎓 Merci d'avoir passé cet examen.
          </div>
          <div style={{ color: 'var(--color-text-muted)' }}>
            Votre résultat a été enregistré et sera communiqué à votre enseignant.
          </div>
        </div>

        {/* Actions */}
        <div style={{ display: 'flex', gap: 8, justifyContent: 'center' }}>
          <Button
            variant="primary"
            onClick={onClose}
            style={{ padding: '10px 32px' }}
          >
            ✅ Terminer
          </Button>
        </div>

        {/* Footer */}
        <div style={{
          textAlign: 'center',
          marginTop: 'var(--space-4)',
          paddingTop: 'var(--space-3)',
          borderTop: '1px solid var(--color-border)',
          fontSize: 11,
          color: 'var(--color-text-muted)',
        }}>
          🏫 IPSSI — Plateforme d'examens
          {' · '}
          © 2026 Mohamed EL AFRIT
        </div>
      </div>
    );
  }

  root.PassageResult = PassageResult;

})(window);
