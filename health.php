<?php
// Public (the SSO leaves /health.php open), for the status page and the deploy
// check, so it says nothing beyond up or down.
const GIF_DIR = '/var/www/gifs';

$ok = is_readable(__DIR__ . '/settings.php');
if ($ok) {
  require_once __DIR__ . '/settings.php';
  $ok = defined('GIPHY_API_KEY') && GIPHY_API_KEY !== '' && GIPHY_API_KEY !== 'your-api-key'
    && defined('GIPHY_EMAIL') && GIPHY_EMAIL !== ''
    && defined('GIPHY_PASSWORD') && GIPHY_PASSWORD !== ''
    && is_dir(GIF_DIR) && is_writable(GIF_DIR);
}

http_response_code($ok ? 200 : 503);
header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(['ok' => $ok]);
