<?php
/**
 * index.php — Point d'entrée web de la plateforme d'examens IPSSI
 *
 * Rôle :
 *   - Charger le bootstrap (autoload, config, session)
 *   - Router les requêtes vers l'endpoint API approprié
 *   - Servir la page d'accueil (temporaire en P1)
 *
 * Routage :
 *   - GET  /                   → page d'accueil HTML (redirection vers login ou dashboard)
 *   - *    /api/{endpoint}     → déléguer à backend/api/{endpoint}.php
 *   - *    /health             → ping de monitoring
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Examens\Lib\Response;

// ============================================================================
// Parsing de l'URL
// ============================================================================

$requestUri  = $_SERVER['REQUEST_URI']  ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH) ?? '/';

// Si la plateforme est servie depuis un sous-chemin (ex: /examens/...),
// on s'adapte. Pour l'instant on suppose racine.
$basePath = '';
if (str_starts_with($requestPath, $basePath)) {
    $requestPath = substr($requestPath, strlen($basePath));
}

// Nettoyer les slashes finaux
$requestPath = '/' . trim($requestPath, '/');

// ============================================================================
// Routes spéciales
// ============================================================================

// Health check (monitoring)
if ($requestPath === '/health' || $requestPath === '/api/health') {
    Response::json([
        'status'     => 'ok',
        'version'    => $GLOBALS['CONFIG']['app']['version']
                       ?? $GLOBALS['CONFIG']['version']
                       ?? 'dev',
        'timestamp'  => date('c'),
        'php'        => PHP_VERSION,
        'uptime_sec' => round(microtime(true) - EXAMENS_START_TIME, 4),
    ]);
}

// ============================================================================
// Routage API : /api/{endpoint} → backend/api/{endpoint}.php
// ============================================================================

if (preg_match('#^/api/([a-z0-9_-]+)(?:/.*)?$#i', $requestPath, $matches)) {
    $endpoint = $matches[1];
    $endpointFile = EXAMENS_ROOT . '/api/' . $endpoint . '.php';

    // Sécurité : vérifier que le fichier existe et est bien dans api/
    $realBase = realpath(EXAMENS_ROOT . '/api/');
    $realFile = realpath($endpointFile);

    if ($realFile === false || !str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR)) {
        Response::notFound('Endpoint API introuvable');
    }

    // Déléguer à l'endpoint
    require $endpointFile;
    exit;
}

// ============================================================================
// Servir les assets statiques frontend (CSS, JS, images, fonts)
// /assets/main.css → frontend/assets/main.css
// ============================================================================

$assetsDir = realpath(EXAMENS_ROOT . '/../frontend/assets');
if ($assetsDir !== false && preg_match('#^/assets/(.+)$#', $requestPath, $m)) {
    $relPath = $m[1];
    // Sécurité : pas de .., pas de chemins absolus
    if (str_contains($relPath, '..') || str_starts_with($relPath, '/')) {
        Response::notFound('Asset introuvable');
    }
    $candidate = realpath($assetsDir . '/' . $relPath);
    if ($candidate !== false && str_starts_with($candidate, $assetsDir . DIRECTORY_SEPARATOR)) {
        // Détection MIME type basique
        $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
        $mimes = [
            'css'  => 'text/css; charset=utf-8',
            'js'   => 'application/javascript; charset=utf-8',
            'json' => 'application/json',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'webp' => 'image/webp',
            'woff' => 'font/woff', 'woff2' => 'font/woff2',
            'ttf'  => 'font/ttf', 'otf' => 'font/otf',
            'ico'  => 'image/x-icon',
        ];
        $mime = $mimes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        // Cache 1h en dev, à augmenter en prod via .htaccess
        header('Cache-Control: public, max-age=3600');
        readfile($candidate);
        exit;
    }
}

// ============================================================================
// Servir les pages frontend HTML (login.html, dashboard_temp.html, etc.)
// ============================================================================

$frontendDir = realpath(EXAMENS_ROOT . '/../frontend/commun');
if ($frontendDir !== false && preg_match('#^/([a-z0-9_-]+\.html)$#i', $requestPath, $m)) {
    $candidate = realpath($frontendDir . '/' . $m[1]);
    if ($candidate !== false && str_starts_with($candidate, $frontendDir . DIRECTORY_SEPARATOR)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($candidate);
        exit;
    }
}

// Redirection : / → /login.html (si pas connecté) ou page d'accueil placeholder
if ($requestPath === '/' && file_exists(EXAMENS_ROOT . '/../frontend/commun/login.html')) {
    header('Location: /login.html', true, 302);
    exit;
}

// ============================================================================
// Page d'accueil (placeholder en P1 — enrichie en P2)
// ============================================================================

if ($requestPath === '/' || $requestPath === '/index.html') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Plateforme d'examens IPSSI</title>
  <style>
    body {
      margin: 0;
      padding: 40px 24px;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #f8fafc;
      color: #1a1a2e;
    }
    .container { max-width: 640px; margin: 0 auto; }
    h1 { margin: 0 0 8px 0; font-size: 2rem; }
    .subtitle { color: #64748b; margin-bottom: 32px; }
    .card {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 16px;
    }
    .badge {
      display: inline-block;
      padding: 2px 10px;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
      background: #fef3c7;
      color: #a16207;
    }
    a { color: #3b82f6; }
    code {
      background: #f1f5f9;
      padding: 2px 6px;
      border-radius: 4px;
      font-family: 'JetBrains Mono', Consolas, monospace;
      font-size: 0.9em;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>🎓 Plateforme d'examens IPSSI</h1>
    <div class="subtitle">
      <span class="badge">Phase P1 — Fondations backend</span>
    </div>

    <div class="card">
      <h2 style="margin-top:0;">✅ Backend opérationnel</h2>
      <p>Le bootstrap PHP est chargé correctement. Cette page sera remplacée par le
      vrai portail en Phase P2.</p>
      <p>Test de l'API : <a href="/api/health">/api/health</a></p>
    </div>

    <div class="card">
      <h2 style="margin-top:0;">📖 Documentation</h2>
      <ul>
        <li><a href="https://github.com/melafrit/maths_IA_niveau_1/tree/main/examens">Dépôt GitHub du projet</a></li>
        <li><a href="https://github.com/melafrit/maths_IA_niveau_1/blob/main/examens/README.md">README</a></li>
        <li><a href="https://github.com/melafrit/maths_IA_niveau_1/blob/main/examens/ROADMAP.md">Roadmap des 9 phases</a></li>
      </ul>
    </div>

    <div style="text-align:center; color:#94a3b8; font-size:0.85rem; margin-top:40px;">
      © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
    </div>
  </div>
</body>
</html>
    <?php
    exit;
}

// ============================================================================
// 404
// ============================================================================

Response::notFound('Page ou endpoint introuvable');
