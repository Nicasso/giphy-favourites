<?php
// Defaults. Copy to settings.php (git-ignored, lives only on the mini) and fill
// in; settings.php loads first, so its values win over these guarded fallbacks.

defined('GIPHY_EMAIL')    || define('GIPHY_EMAIL', 'your-email-address');
defined('GIPHY_PASSWORD') || define('GIPHY_PASSWORD', 'secret-stuff');
defined('GIPHY_API_KEY')  || define('GIPHY_API_KEY', 'your-api-key');

// The SSO user (Remote-User via Caddy) let in. Empty = nobody (fails closed).
defined('SSO_USER')       || define('SSO_USER', 'nico');
