<?php
/**
 * prof_premier_passage.php — Email prof : premier etudiant a soumis.
 *
 * Variables :
 *   $profName
 *   $examTitle
 *   $studentName
 *   $scoreBrut / $scoreMax / $scorePct
 *   $adminUrl
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

use Examens\Lib\EmailTemplate;
$e = [EmailTemplate::class, 'e'];
$tpl = new EmailTemplate();

$content = '
<p style="margin:0 0 16px;">Bonjour <strong>' . $e($profName) . '</strong>,</p>

<p style="margin:0 0 20px;">🎉 Le premier étudiant vient de soumettre son examen <strong>' . $e($examTitle) . '</strong>.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
  <tr>
    <td style="padding:16px 20px;background:#f8fafc;font-weight:600;color:#1a1a2e;">
      👤 ' . $e($studentName) . '
    </td>
    <td style="padding:16px 20px;background:#f8fafc;text-align:right;">
      <span style="font-size:20px;font-weight:700;color:#3b82f6;">' . $e($scoreBrut) . '/' . $e($scoreMax) . '</span>
      <span style="color:#64748b;font-size:13px;margin-left:8px;">(' . $e($scorePct) . '%)</span>
    </td>
  </tr>
</table>

<p style="margin:16px 0;color:#64748b;font-size:13px;">
  💡 Vous recevez cet email parce qu\'il s\'agit du <strong>premier passage</strong>.
  Les soumissions suivantes ne déclencheront pas de notification (vous pouvez consulter toutes les stats dans le tableau de bord).
</p>
';

if (!empty($adminUrl)) {
    $content .= '
<div style="text-align:center;margin:24px 0;">
  <a href="' . $e($adminUrl) . '"
     style="display:inline-block;padding:12px 24px;background:#3b82f6;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">
    📊 Voir les résultats
  </a>
</div>
';
}

$subject = '🎉 Premier passage : ' . $examTitle;
$html = $tpl->renderBase(
    'Premier passage reçu',
    $content,
    "Premier étudiant a soumis l'examen '$examTitle'"
);

return ['subject' => $subject, 'html' => $html];
