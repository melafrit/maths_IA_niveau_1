<?php
/**
 * prof_examen_cree.php — Email prof : confirmation creation d'examen (apres publish).
 *
 * Variables :
 *   $profName       : Nom prof
 *   $examTitle      : Titre
 *   $examId         : ID
 *   $accessCode     : Code d'acces
 *   $nbQuestions    : Nb questions
 *   $dureeSec       : Duree en sec
 *   $dateOuverture  : ISO
 *   $dateCloture    : ISO
 *   $maxPassages    : 1-10
 *   $adminUrl       : URL vers /admin/examens.html
 *   $studentUrl     : URL de partage étudiant (optionnel)
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

use Examens\Lib\EmailTemplate;
$e = [EmailTemplate::class, 'e'];
$tpl = new EmailTemplate();

// Duree en h/min
$h = intdiv($dureeSec ?? 3600, 3600);
$m = intdiv(($dureeSec ?? 3600) % 3600, 60);
$duree = $h > 0 ? sprintf('%dh%02d', $h, $m) : sprintf('%d min', $m);

$dateOuvFormatted = $dateClotFormatted = '';
try {
    if (!empty($dateOuverture)) $dateOuvFormatted = (new DateTime($dateOuverture))->format('d/m/Y à H:i');
    if (!empty($dateCloture)) $dateClotFormatted = (new DateTime($dateCloture))->format('d/m/Y à H:i');
} catch (\Throwable $ex) {}

$content = '
<p style="margin:0 0 16px;">Bonjour <strong>' . $e($profName) . '</strong>,</p>

<p style="margin:0 0 20px;">Votre examen <strong>' . $e($examTitle) . '</strong> est publié et prêt à être partagé avec vos étudiants.</p>

<!-- Access code card -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;border:2px solid #3b82f6;border-radius:12px;overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#3b82f6,#8b5cf6);padding:24px;text-align:center;">
      <div style="font-size:12px;color:#ffffff;text-transform:uppercase;letter-spacing:1px;font-weight:600;margin-bottom:8px;opacity:0.9;">
        🎟️ Code d\'accès
      </div>
      <div style="font-family:monospace;font-size:36px;font-weight:800;color:#ffffff;letter-spacing:6px;">
        ' . $e($accessCode) . '
      </div>
      <div style="margin-top:8px;font-size:12px;color:#ffffff;opacity:0.8;">
        À communiquer à vos étudiants
      </div>
    </td>
  </tr>
</table>

<!-- Recap -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">
  <tr>
    <td style="padding:8px 0;color:#64748b;font-size:13px;width:40%;">📋 Identifiant :</td>
    <td style="padding:8px 0;font-family:monospace;font-size:12px;color:#1a1a2e;"><code>' . $e($examId) . '</code></td>
  </tr>
  <tr>
    <td style="padding:8px 0;color:#64748b;font-size:13px;">❓ Nombre de questions :</td>
    <td style="padding:8px 0;font-weight:600;color:#1a1a2e;">' . $e($nbQuestions) . '</td>
  </tr>
  <tr>
    <td style="padding:8px 0;color:#64748b;font-size:13px;">⏱️ Durée :</td>
    <td style="padding:8px 0;font-weight:600;color:#1a1a2e;">' . $e($duree) . '</td>
  </tr>
  <tr>
    <td style="padding:8px 0;color:#64748b;font-size:13px;">🎯 Tentatives max :</td>
    <td style="padding:8px 0;font-weight:600;color:#1a1a2e;">' . $e($maxPassages) . ' par étudiant</td>
  </tr>
  <tr>
    <td style="padding:8px 0;color:#64748b;font-size:13px;">📅 Ouverture :</td>
    <td style="padding:8px 0;font-weight:500;color:#1a1a2e;">' . $e($dateOuvFormatted) . '</td>
  </tr>
  <tr>
    <td style="padding:8px 0;color:#64748b;font-size:13px;">🔒 Clôture :</td>
    <td style="padding:8px 0;font-weight:500;color:#1a1a2e;">' . $e($dateClotFormatted) . '</td>
  </tr>
</table>
';

// Message pour partage aux etudiants
$content .= '
<div style="padding:16px;background:#f0f9ff;border-left:4px solid #0ea5e9;border-radius:6px;margin:20px 0;font-size:13px;color:#075985;">
  <strong>📢 Instructions pour vos étudiants :</strong><br>
  1. Rendez-vous sur la plateforme d\'examens<br>
  2. Saisissez le code d\'accès <strong>' . $e($accessCode) . '</strong><br>
  3. Suivez les instructions à l\'écran
</div>
';

if (!empty($adminUrl)) {
    $content .= '
<div style="text-align:center;margin:24px 0;">
  <a href="' . $e($adminUrl) . '"
     style="display:inline-block;padding:12px 24px;background:#3b82f6;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">
    ⚙️ Gérer cet examen
  </a>
</div>
';
}

$content .= '
<p style="margin:16px 0;font-size:13px;color:#64748b;">
  Vous recevrez un email lorsque le premier étudiant soumettra son examen.
</p>
';

$subject = '✅ Examen publié : ' . $examTitle;
$html = $tpl->renderBase(
    'Examen publié avec succès',
    $content,
    "Votre examen '$examTitle' est publié. Code d'accès : $accessCode"
);

return ['subject' => $subject, 'html' => $html];
