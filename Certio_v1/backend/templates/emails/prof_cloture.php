<?php
/**
 * prof_cloture.php — Email prof : examen cloture + stats finales.
 *
 * Variables :
 *   $profName
 *   $examTitle
 *   $nbPassages
 *   $avgScorePct
 *   $minScorePct / $maxScorePct
 *   $anomaliesCount
 *   $adminUrl
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

use Examens\Lib\EmailTemplate;
$e = [EmailTemplate::class, 'e'];
$tpl = new EmailTemplate();

$avgColor = '#dc2626';
if (($avgScorePct ?? 0) >= 70) $avgColor = '#16a34a';
elseif (($avgScorePct ?? 0) >= 50) $avgColor = '#d97706';

$content = '
<p style="margin:0 0 16px;">Bonjour <strong>' . $e($profName) . '</strong>,</p>

<p style="margin:0 0 20px;">L\'examen <strong>' . $e($examTitle) . '</strong> est clôturé. Voici le récapitulatif final.</p>

<!-- Stats principales -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">
  <tr>
    <td width="33%" style="padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;text-align:center;vertical-align:top;">
      <div style="font-size:32px;font-weight:800;color:#3b82f6;line-height:1;">' . $e($nbPassages) . '</div>
      <div style="font-size:11px;text-transform:uppercase;color:#64748b;font-weight:600;margin-top:4px;">Passages</div>
    </td>
    <td width="33%" style="padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;text-align:center;vertical-align:top;margin-left:4px;">
      <div style="font-size:32px;font-weight:800;color:' . $avgColor . ';line-height:1;">' . $e($avgScorePct) . '%</div>
      <div style="font-size:11px;text-transform:uppercase;color:#64748b;font-weight:600;margin-top:4px;">Moyenne</div>
    </td>
    <td width="33%" style="padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;text-align:center;vertical-align:top;">
      <div style="font-size:32px;font-weight:800;color:' . (($anomaliesCount ?? 0) > 0 ? '#dc2626' : '#16a34a') . ';line-height:1;">' . $e($anomaliesCount) . '</div>
      <div style="font-size:11px;text-transform:uppercase;color:#64748b;font-weight:600;margin-top:4px;">Anomalies</div>
    </td>
  </tr>
</table>

<!-- Detail scores -->
<div style="padding:16px 20px;background:#f8fafc;border-radius:8px;margin:16px 0;font-size:13px;">
  <div style="margin-bottom:6px;"><strong>📉 Score min :</strong> ' . $e($minScorePct) . '%</div>
  <div style="margin-bottom:6px;"><strong>📈 Score max :</strong> ' . $e($maxScorePct) . '%</div>
</div>
';

if (($anomaliesCount ?? 0) > 0) {
    $content .= '
<div style="padding:16px;background:#fef3c7;border-left:4px solid #f59e0b;border-radius:6px;margin:20px 0;font-size:13px;color:#78350f;">
  ⚠️ <strong>' . $e($anomaliesCount) . ' passage(s) ont été signalés</strong> pour des comportements suspects
  (copier/coller, changement d\'onglet répété, etc.). Consultez le détail pour décider de leur validation.
</div>
';
}

if (!empty($adminUrl)) {
    $content .= '
<div style="text-align:center;margin:24px 0;">
  <a href="' . $e($adminUrl) . '"
     style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;">
    📊 Voir les résultats détaillés
  </a>
</div>
';
}

$content .= '
<p style="margin:16px 0;color:#64748b;font-size:13px;">
  💡 Vous pouvez à tout moment consulter les statistiques détaillées, par question et par étudiant, depuis votre tableau de bord.
</p>
';

$subject = '🔒 Examen clôturé : ' . $examTitle . ' (' . $nbPassages . ' passages)';
$html = $tpl->renderBase(
    'Examen clôturé',
    $content,
    "Examen '$examTitle' clôturé — $nbPassages passages — Moyenne $avgScorePct%"
);

return ['subject' => $subject, 'html' => $html];
