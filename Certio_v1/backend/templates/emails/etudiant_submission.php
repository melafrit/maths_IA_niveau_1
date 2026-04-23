<?php
/**
 * etudiant_submission.php — Email de confirmation de soumission pour l'étudiant.
 *
 * Variables attendues :
 *   $studentName     : ex. 'Jean Dupont'
 *   $examTitle       : Titre de l'examen
 *   $scoreBrut       : ex. 15
 *   $scoreMax        : ex. 20
 *   $scorePct        : ex. 75.0
 *   $durationSec     : Durée en secondes
 *   $submittedAt     : ISO 8601 de soumission
 *   $correctionUrl   : URL vers la correction (optionnel)
 *   $correctionDelay : Délai en minutes si pas encore dispo
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

use Examens\Lib\EmailTemplate;

$e = [EmailTemplate::class, 'e'];
$tpl = new EmailTemplate();

$duration = '';
if (isset($durationSec)) {
    $h = intdiv($durationSec, 3600);
    $m = intdiv($durationSec % 3600, 60);
    $s = $durationSec % 60;
    if ($h > 0) $duration = sprintf('%dh %02dmin', $h, $m);
    elseif ($m > 0) $duration = sprintf('%dmin %02ds', $m, $s);
    else $duration = sprintf('%ds', $s);
}

$mention = 'À revoir';
$mentionColor = '#dc2626';
$mentionEmoji = '💪';
if ($scorePct >= 90) { $mention = 'Excellent'; $mentionColor = '#d97706'; $mentionEmoji = '🏆'; }
elseif ($scorePct >= 80) { $mention = 'Très bien'; $mentionColor = '#16a34a'; $mentionEmoji = '⭐'; }
elseif ($scorePct >= 70) { $mention = 'Bien'; $mentionColor = '#22c55e'; $mentionEmoji = '✨'; }
elseif ($scorePct >= 60) { $mention = 'Assez bien'; $mentionColor = '#3b82f6'; $mentionEmoji = '👍'; }
elseif ($scorePct >= 50) { $mention = 'Passable'; $mentionColor = '#6366f1'; $mentionEmoji = '📈'; }
elseif ($scorePct >= 30) { $mention = 'À améliorer'; $mentionColor = '#d97706'; $mentionEmoji = '📚'; }

$submittedFormatted = '';
if (isset($submittedAt)) {
    try {
        $submittedFormatted = (new DateTime($submittedAt))->format('d/m/Y à H:i');
    } catch (\Throwable $ex) {
        $submittedFormatted = $submittedAt;
    }
}

$content = '
<p style="margin:0 0 16px;">Bonjour <strong>' . $e($studentName) . '</strong>,</p>

<p style="margin:0 0 20px;">Votre examen <strong>' . $e($examTitle) . '</strong> a bien été enregistré.</p>

<!-- Score card -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
  <tr>
    <td style="background:#f8fafc;padding:24px;text-align:center;">
      <div style="font-size:48px;font-weight:800;color:' . $mentionColor . ';line-height:1;">
        ' . $e($scoreBrut) . '<span style="font-size:22px;color:#94a3b8;font-weight:400;"> / ' . $e($scoreMax) . '</span>
      </div>
      <div style="font-size:18px;font-weight:700;color:' . $mentionColor . ';margin-top:8px;">
        ' . $e($scorePct) . '% · ' . $mentionEmoji . ' ' . $e($mention) . '
      </div>
    </td>
  </tr>
  <tr>
    <td style="padding:16px 24px;border-top:1px solid #e2e8f0;font-size:13px;color:#64748b;">
      <strong>Durée :</strong> ' . $e($duration) . '<br>
      <strong>Soumis le :</strong> ' . $e($submittedFormatted) . '
    </td>
  </tr>
</table>
';

// Bouton correction si disponible
if (!empty($correctionUrl)) {
    $content .= '
<p style="margin:24px 0 16px;">Consultez la correction détaillée avec les explications pour chaque question :</p>

<div style="text-align:center;margin:24px 0;">
  <a href="' . $e($correctionUrl) . '"
     style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;">
    📖 Voir la correction
  </a>
</div>
';
} elseif (!empty($correctionDelay)) {
    $content .= '
<div style="padding:16px;background:#fef3c7;border-left:4px solid #f59e0b;border-radius:6px;margin:16px 0;color:#78350f;font-size:13px;">
  ⏳ La correction sera disponible dans <strong>' . $e($correctionDelay) . ' minutes</strong>.
</div>
';
}

$content .= '
<p style="margin:16px 0;font-size:13px;color:#64748b;">
  💡 Si vous avez des questions, contactez votre enseignant par email.
</p>

<p style="margin:16px 0 0;font-size:13px;color:#64748b;">
  Merci pour votre participation !
</p>
';

$subject = 'Confirmation de soumission — ' . $examTitle;
$html = $tpl->renderBase(
    'Examen soumis avec succès',
    $content,
    "Votre examen '$examTitle' a bien été enregistré — Score : $scoreBrut/$scoreMax"
);

return ['subject' => $subject, 'html' => $html];
