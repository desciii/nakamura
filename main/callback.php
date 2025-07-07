<?php
session_start();

require_once __DIR__ . '/config.php';
loadEnv(__DIR__ . '/.env');

// Spotify app credentials from .env
$clientId = $_ENV['SPOTIFY_CLIENT_ID'];
$clientSecret = $_ENV['SPOTIFY_CLIENT_SECRET'];
$redirectUri = $_ENV['SPOTIFY_REDIRECT_URI'];

// 1. Check for code
if (!isset($_GET['code'])) {
    die('❌ Authorization failed. No code received.');
}

$code = $_GET['code'];

// 2. Request access token
$tokenUrl = 'https://accounts.spotify.com/api/token';
$headers = [
    "Authorization: Basic " . base64_encode("$clientId:$clientSecret"),
    "Content-Type: application/x-www-form-urlencoded"
];
$body = http_build_query([
    'grant_type'    => 'authorization_code',
    'code'          => $code,
    'redirect_uri'  => $redirectUri
]);

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => implode("\r\n", $headers),
        'content' => $body,
    ]
];

$response = file_get_contents($tokenUrl, false, stream_context_create($options));
$data = json_decode($response, true);

if (!isset($data['access_token'])) {
    die('❌ Failed to get access token.<br><pre>' . var_export($data, true) . '</pre>');
}

$accessToken = $data['access_token'];

// 3. Store token
$_SESSION['spotify_access_token'] = $accessToken;
setcookie('spotify_token', $accessToken, time() + 3600, '/');

// 4. Fetch user profile
$userProfile = file_get_contents('https://api.spotify.com/v1/me', false, stream_context_create([
    'http' => [
        'header' => "Authorization: Bearer $accessToken"
    ]
]));
$userData = json_decode($userProfile, true);

$_SESSION['spotify_user'] = $userData;
$_SESSION['user_id'] = $userData['id'] ?? uniqid('guest_');
$_SESSION['username'] = $userData['display_name'] ?? 'Spotify User';

// Optional: Log for debugging
file_put_contents(__DIR__ . "/callback_log.txt", 
    "✅ Callback hit at: " . date('Y-m-d H:i:s') . "\n" .
    "Access Token: " . $accessToken . "\n" .
    "User: " . ($userData['display_name'] ?? 'N/A') . "\n" .
    "User ID: " . ($userData['id'] ?? 'N/A') . "\n" .
    str_repeat("-", 40) . "\n",
    FILE_APPEND
);

// 5. Redirect to dashboard
header("Location: /PHP/Nakamura/nakamura/main/dashboard.php");
exit;
