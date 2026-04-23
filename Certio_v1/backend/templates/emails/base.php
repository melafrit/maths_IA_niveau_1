<?php
/**
 * base.php — Layout HTML commun pour tous les emails IPSSI.
 *
 * Variables attendues :
 *   $title     : Titre du mail (h1)
 *   $content   : Contenu HTML (deja rendu)
 *   $preheader : Texte preheader (affiche par certains clients)
 *
 * Design : responsive, compatible Outlook/Gmail/Apple Mail.
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

$title = $title ?? 'IPSSI Examens';
$content = $content ?? '';
$preheader = $preheader ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?></title>
</head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;line-height:1.6;color:#1a1a2e;">

<!-- Preheader (masque mais apparait dans l'apercu) -->
<?php if ($preheader): ?>
<div style="display:none;max-height:0;overflow:hidden;font-size:1px;line-height:1px;color:#f0f4f8;">
  <?= htmlspecialchars($preheader) ?>
</div>
<?php endif; ?>

<!-- Wrapper -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f0f4f8;padding:20px 0;">
  <tr>
    <td align="center">

      <!-- Container -->
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.05);">

        <!-- Header avec gradient -->
        <tr>
          <td style="background:linear-gradient(135deg,#3b82f6 0%,#8b5cf6 100%);padding:32px 40px;border-radius:12px 12px 0 0;text-align:center;">
            <div style="color:#ffffff;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
              🎓 IPSSI
            </div>
            <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">
              <?= htmlspecialchars($title) ?>
            </h1>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:32px 40px;color:#1a1a2e;font-size:15px;line-height:1.6;">
            <?= $content ?>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f8fafc;padding:24px 40px;border-top:1px solid #e2e8f0;border-radius:0 0 12px 12px;text-align:center;font-size:12px;color:#64748b;">
            <div style="margin-bottom:8px;">
              <strong>🏫 IPSSI</strong> — Plateforme d'examens
            </div>
            <div style="margin-bottom:4px;">
              Mohamed EL AFRIT · <a href="mailto:m.elafrit@ecole-ipssi.net" style="color:#3b82f6;text-decoration:none;">m.elafrit@ecole-ipssi.net</a>
            </div>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid #e2e8f0;font-size:11px;">
              © 2026 Mohamed EL AFRIT — <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.fr" style="color:#64748b;text-decoration:none;">CC BY-NC-SA 4.0</a>
            </div>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
