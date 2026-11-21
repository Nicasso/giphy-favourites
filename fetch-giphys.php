<?php

require_once 'settings.php';

session_start();

function login() {
  $email = urlencode(GIPHY_EMAIL);
  $password = urlencode(GIPHY_PASSWORD);
  $api_key = urlencode(GIPHY_API_KEY);

  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api.giphy.com/v1/user/login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => "email={$email}&password={$password}&api_key=$api_key",
    CURLOPT_HTTPHEADER => array(
      'Host: api.giphy.com',
      'Accept: */*',
      'X-GIPHY-SDK-VERSION: 2.3.22',
      'Connection: keep-alive',
      'X-GIPHY-SDK-NAME: CoreSDK',
      'User-Agent: Giphy Core SDK v2.3.22 (iOS)',
      'Accept-Language: nl-NL,nl;q=0.9',
      'X-GIPHY-SDK-PLATFORM: iOS',
      'Content-Type: application/x-www-form-urlencoded'
    ),
  ));


  $response = curl_exec($curl);

  if ($response === false) {
    http_response_code(400);
    die();
  }

  $data = json_decode($response);

  $_SESSION['access_token'] = $data->data->access_token;
  $_SESSION['user_id'] = $data->data->user->id;

  return $data->data->access_token;
}

// Local copies live outside the docroot, in a volume nginx serves at /gifs/.
// A file that is not there yet is a 302 to Giphy (nginx.conf), so these paths
// are valid from the first fetch on, and a link once shared keeps working.
const GIF_DIR = '/var/www/gifs';
const GIF_MAX_BYTES = 100 * 1024 * 1024;

// Giphy ids are alphanumeric. Anything else never becomes a path on disk.
function gif_id(array $giphy) {
  $id = $giphy['id'] ?? '';
  return preg_match('/^[A-Za-z0-9]{1,64}$/', $id) ? $id : null;
}

// Tracking params (cid/ep/rid/ct) stripped; only Giphy's own media hosts.
function giphy_media_url($url) {
  $url = explode('?', (string) $url, 2)[0];
  $host = parse_url($url, PHP_URL_HOST) ?? '';
  $ok = parse_url($url, PHP_URL_SCHEME) === 'https' && preg_match('/(^|\.)giphy\.com$/', $host);
  return $ok ? $url : null;
}

function download_gif($url, $dest) {
  $tmp = $dest . '.part';
  $fh = fopen($tmp, 'wb');
  if ($fh === false) {
    error_log("giphy: cannot write $tmp");
    return false;
  }

  $curl = curl_init($url);
  curl_setopt_array($curl, [
    CURLOPT_FILE => $fh,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_NOPROGRESS => false,
    CURLOPT_XFERINFOFUNCTION => fn($c, $total, $done) => (int) ($total > GIF_MAX_BYTES || $done > GIF_MAX_BYTES),
  ]);
  $ok = curl_exec($curl);
  $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
  $type = (string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
  $error = curl_error($curl);
  fclose($fh);

  if ($ok === false || $status !== 200 || !str_starts_with($type, 'image/')) {
    error_log("giphy: download failed ($status $type $error): $url");
    unlink($tmp);
    return false;
  }
  // Atomic: nginx never serves a half-written file.
  return rename($tmp, $dest);
}

// Runs after the response is sent. One sync at a time; a second request while
// one runs just returns, the next fetch picks up whatever is still missing.
function sync_gifs(array $sources) {
  $lock = fopen(GIF_DIR . '/.sync.lock', 'c');
  if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    return;
  }
  set_time_limit(0);
  ignore_user_abort(true);
  foreach (['still', 'play'] as $sub) {
    if (!is_dir(GIF_DIR . "/$sub") && !mkdir(GIF_DIR . "/$sub", 0755, true)) {
      error_log('giphy: cannot create ' . GIF_DIR . "/$sub");
      return;
    }
  }

  foreach ($sources as $path => $url) {
    $dest = GIF_DIR . $path;
    if (!is_file($dest)) {
      download_gif($url, $dest);
    }
  }
  flock($lock, LOCK_UN);
}

function fetch_streams($page = 0, $second_attempt = false) {
    if (isset($_SESSION['access_token']) && isset($_SESSION['user_id'])) {
      $access_token = $_SESSION['access_token'];
    } else {
      $access_token = login();
    }

    $user_id = $_SESSION['user_id'];
    $api_key = urlencode(GIPHY_API_KEY);
    $limit = 100;

    if ($page == 0) {
      $offset = 0;
    } else {
      $offset = $page * $limit;
    }

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.giphy.com/v1/user/{$user_id}/favorites/gifs?access_token={$access_token}&offset={$offset}&limit={$limit}&api_key={$api_key}",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Host: api.giphy.com',
        'X-GIPHY-SDK-NAME: CoreSDK',
        'Connection: keep-alive',
        'X-GIPHY-SDK-VERSION: 2.3.22',
        'Accept: */*',
        'User-Agent: Giphy Core SDK v2.3.22 (iOS)',
        'Accept-Language: nl-NL,nl;q=0.9',
        'X-GIPHY-SDK-PLATFORM: iOS',
        'Content-Type: application/json'
      ),
    ));


    $response = curl_exec($curl);

    if (curl_errno($curl) && $second_attempt == false) {
      // Maybe we need to login again, so try that once.
      unset($_SESSION['access_token']);
      fetch_streams($page, true);
    }

    $json = json_decode($response, TRUE);

    global $gif_sources;
    $response = [];
    foreach ($json['data'] as $giphy) {
      $id = gif_id($giphy);
      $images = $giphy['images'];
      $original = giphy_media_url($images['original']['url'] ?? '');
      $still = giphy_media_url($images['original_still']['url'] ?? '');
      if ($id === null || $original === null || $still === null) {
        error_log('giphy: skipped favourite without usable id or original: ' . ($giphy['id'] ?? '?'));
        continue;
      }
      $gif_sources["/$id.gif"] = $original;
      $gif_sources["/still/$id.gif"] = $still;

      // Playback in the grid uses downsized_medium (max 5 MB) so a click starts
      // fast; the copied link stays the original. Only when it is really smaller.
      $play = "/gifs/$id.gif";
      $medium = giphy_media_url($images['downsized_medium']['url'] ?? '');
      $medium_size = (int) ($images['downsized_medium']['size'] ?? 0);
      $original_size = (int) ($images['original']['size'] ?? 0);
      if ($medium !== null && $medium_size > 0 && $medium_size < $original_size) {
        $gif_sources["/play/$id.gif"] = $medium;
        $play = "/gifs/play/$id.gif";
      }

      $response[$giphy['index_id']] = [
        'url_preview' => "/gifs/still/$id.gif",
        'url' => $play,
        'url_original' => "/gifs/$id.gif",
        'width' => $images['original']['width'],
        'height' => $images['original']['height'],
        'tags' => implode(",", $giphy['tags']),
        'title' => $giphy['title'],
        'create_datetime' => $giphy['create_datetime'],
      ];
    }

    $_SESSION['last_response'] = time();
    $_SESSION['total_giphy_count'] = $json['pagination']['total_count'];

    return $response;
}

if (isset($_SESSION['last_response'])) {
  $now = time();
  $last_response = $_SESSION['last_response'];

  $ten_minutes = 60 * 10;
  if ($now - $last_response < $ten_minutes) {
    echo json_encode(['data' => []]);
    die();
  }
}

$gif_sources = [];
$all_giphys = [];
if (isset($_SESSION['total_giphy_count'])) {
  $total_giphy_count = $_SESSION['total_giphy_count'];
  $start = 0;
} else {
  $all_giphys = fetch_streams(0);
  $start = 1;
}

$total_giphy_count = $_SESSION['total_giphy_count'];
$total_requests_needed = ceil($total_giphy_count / 100);

for ($i = $start; $i < $total_requests_needed; $i++) {
  $all_giphys = array_merge($all_giphys, fetch_streams($i));
}

echo json_encode(['data' => $all_giphys]);

session_write_close();
fastcgi_finish_request();
sync_gifs($gif_sources);
