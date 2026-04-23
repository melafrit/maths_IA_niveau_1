<?php
/**
 * etudiant_correction.php — Email : la correction de votre examen est disponible.
 *
 * Variables :
 *   $studentName   : 'Jean Dupont'
 *   $examTitle     : Titre
 *   $correctionUrl : URL
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

use Examens\Lib\EmailTemplate;
$e = [EmailTemplate::class, 'e'];
$tpl = new EmailTemplate();

$content = '
<p style="margin:0 0 16px;">Bonjour <strong>' . $e($studentName) . '</strong>,</p>

<p style="margin:0 0 20px;">La correction détaillée de votre examen <strong>' . $e($examTitle) . '</strong> est maintenant disponible !</p>

<div style="padding:20px;background:#dcfce7;border-left:4px solid #16a34a;border-radius:6px;margin:20px 0;">
  <div style="font-weight:700;color:#14532d;margin-bottom:4px;">✅ Votre correction est prête</div>
  <div style="color:#166534;font-size:13px;">
    Vous pouvez consulter vos bonnes réponses, vos erreurs, ainsi que les explications détaillées pour chaque question.
  </div>
</div>

<p style="margin:24px 0 16px;">Accédez à votre correction en cliquant sur le bouton ci-dessous :</p>

<div style="text-align:center;margin:32px 0;">
  <a href="' . $e($correctionUrl) . '"
     style="display:inline-block;padding:14px 32px;background:#16a34a;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;">
    📖 Voir la correction détaillée
  </a>
</div>

<p style="margin:20px 0 16px;font-size:13px;color:#64748b;">
  💡 <strong>Astuce</strong> : vous pouvez aussi télécharger un PDF depuis la page de correction pour garder une trace.
</p>

<p style="margin:16px 0 0;font-size:13px;color:#64748b;">
  Bonne révision !
</p>
';

$subject = 'Votre correction est disponible — ' . $examTitle;
$html = $tpl->renderBase(
    'Correction disponible',
    $content,
    "La correction détaillée de votre examen '$examTitle' est prête à être consultée"
);

return ['subject' => $subject, 'html' => $html];
