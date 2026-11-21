<?php
// SSO gate check, no dependencies: php tests/sso_check.php (exit 0 = pass).
// Kept in the wrapper, outside the image and the web root.
// curl is disabled in the child processes so an ungated fetch-giphys.php can
// never log into Giphy from here.

$html = realpath(__DIR__ . '/../html');
$failures = 0;

function check(string $name, bool $ok): void
{
    global $failures;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n";
    if (!$ok) {
        $failures++;
    }
}

// Runs one entry point in a child PHP and returns its HTTP status (200 when none was set).
function status_of(string $html, string $script, ?string $remoteUser, ?string $ssoUser = null): int
{
    $code = '$GLOBALS["__skip_settings"] = true;'
        . ($ssoUser !== null ? 'define("SSO_USER", ' . var_export($ssoUser, true) . ');' : '')
        . ($remoteUser !== null ? '$_SERVER["HTTP_REMOTE_USER"] = ' . var_export($remoteUser, true) . ';' : '')
        . 'register_shutdown_function(function () { echo "\n@@STATUS:" . (http_response_code() ?: 200); });'
        . 'chdir(' . var_export($html, true) . ');'
        . 'require ' . var_export("$html/$script", true) . ';';
    $cmd = escapeshellarg(PHP_BINARY)
        . ' -d disable_functions=curl_init,curl_exec,curl_multi_exec -d display_errors=stderr -d log_errors=0'
        . ' -r ' . escapeshellarg($code) . ' 2>/dev/null';
    $out = shell_exec($cmd) ?? '';
    return preg_match('/@@STATUS:(\d+)\s*$/', $out, $m) ? (int) $m[1] : 0;
}

// Unit: the comparison itself.
if (is_file("$html/lib/auth.php")) {
    $GLOBALS['__skip_settings'] = true;
    require "$html/lib/auth.php";
    check('sso_allows: header absent', sso_allows('nico', null) === false);
    check('sso_allows: wrong user', sso_allows('nico', 'mallory') === false);
    check('sso_allows: empty header', sso_allows('nico', '') === false);
    check('sso_allows: right user', sso_allows('nico', 'nico') === true);
    check('sso_allows: empty SSO_USER refuses all', sso_allows('', '') === false && sso_allows('', 'nico') === false);
    check('SSO_USER defaults to nico', SSO_USER === 'nico');
} else {
    check('html/lib/auth.php exists', false);
}

// End to end: each entry point, as the browser would reach it without the edge.
check('index.php: header absent -> 401', status_of($html, 'index.php', null) === 401);
check('index.php: wrong user -> 401', status_of($html, 'index.php', 'mallory') === 401);
check('index.php: right user -> 200', status_of($html, 'index.php', 'nico') === 200);
check('index.php: empty SSO_USER -> 401', status_of($html, 'index.php', 'nico', '') === 401);
check('fetch-giphys.php: header absent -> 401', status_of($html, 'fetch-giphys.php', null) === 401);
check('fetch-giphys.php: wrong user -> 401', status_of($html, 'fetch-giphys.php', 'mallory') === 401);
check('fetch-giphys.php: empty SSO_USER -> 401', status_of($html, 'fetch-giphys.php', 'nico', '') === 401);
// fetch-giphys.php with the right user is not run: it would log into Giphy.

echo $failures === 0 ? "PASS\n" : "$failures FAILED\n";
exit($failures === 0 ? 0 : 1);
