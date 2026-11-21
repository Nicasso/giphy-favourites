<?php
// Login is the SSO's job: Caddy's `import sso` admits a request only with a
// session and sets Remote-User (stripping any the client sent). Checking it here
// too means losing `import sso` shuts the credentialed proxy (SCS §6.4).

// settings.php (mini-only) loads first so its values win over the guarded defaults.
(function () {
    $here = __DIR__ . '/..';
    if (is_file("$here/settings.php") && empty($GLOBALS['__skip_settings'])) {
        require_once "$here/settings.php";
    }
    require_once "$here/default.settings.php";
})();

/** True only when $remoteUser is exactly $expected, and $expected is configured. */
function sso_allows(string $expected, ?string $remoteUser): bool
{
    return $expected !== '' && $remoteUser !== null && hash_equals($expected, $remoteUser);
}

/** Ends the request with a 401 unless the SSO user is the configured one. */
function require_sso(): void
{
    $user = $_SERVER['HTTP_REMOTE_USER'] ?? null;
    if (sso_allows(SSO_USER, is_string($user) ? $user : null)) {
        return;
    }
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    exit("Not logged in.\n");
}
