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

  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

  $response = curl_exec($curl);
  curl_close($curl);

  if ($response === false) {
    http_response_code(400);
    die();
  }

  $data = json_decode($response);

  $_SESSION['access_token'] = $data->data->access_token;
  $_SESSION['user_id'] = $data->data->user->id;

  return $data->data->access_token;
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

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

    $response = curl_exec($curl);

    if (curl_errno($curl) && $second_attempt == false) {
      // Maybe we need to login again, so try that once.
      unset($_SESSION['access_token']);
      fetch_streams($page, true);
    }

    curl_close($curl);

    $json = json_decode($response, TRUE);

    $response = [];
    foreach ($json['data'] as $giphy) {
      $tags = implode(",", $giphy['tags']);

      $response[$giphy['index_id']] = [
        'url_preview' => $giphy['images']['fixed_height_still']['url'],
        'url' => $giphy['images']['fixed_height']['url'],
        'tags' => $tags,
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
