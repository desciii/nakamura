<?php
session_start();
require_once __DIR__ . '/config.php';
include 'db.php';

loadEnv(__DIR__ . '/.env');

$clientId = $_ENV['SPOTIFY_CLIENT_ID'];
$clientSecret = $_ENV['SPOTIFY_CLIENT_SECRET'];
$redirectUri = $_ENV['SPOTIFY_REDIRECT_URI'];

// STEP 1: Check for auth code
if (!isset($_GET['code'])) {
    die('❌ Authorization failed. No code received.');
}
$code = $_GET['code'];

// STEP 2: Exchange code for access token
$tokenUrl = 'https://accounts.spotify.com/api/token';
$headers = [
    "Authorization: Basic " . base64_encode("$clientId:$clientSecret"),
    "Content-Type: application/x-www-form-urlencoded"
];
$body = http_build_query([
    'grant_type'   => 'authorization_code',
    'code'         => $code,
    'redirect_uri' => $redirectUri
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

// STEP 3: Get Spotify user profile
$userProfile = file_get_contents('https://api.spotify.com/v1/me', false, stream_context_create([
    'http' => [
        'header' => "Authorization: Bearer $accessToken"
    ]
]));
$userData = json_decode($userProfile, true);

$spotifyId = $userData['id'] ?? null;
$displayName = $userData['display_name'] ?? 'Spotify User';

if (!$spotifyId) {
    die('❌ Spotify user ID not found.');
}

// STEP 4: Insert or update user in DB
$stmt = $conn->prepare("SELECT id FROM users WHERE spotify_id = ?");
$stmt->bind_param("s", $spotifyId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $insert = $conn->prepare("INSERT INTO users (username, spotify_id) VALUES (?, ?)");
    $insert->bind_param("ss", $displayName, $spotifyId);
    $insert->execute();
    $userId = $insert->insert_id;
    $insert->close();
} else {
    $userId = $user['id'];
}

$stmt->close();
$conn->close();

// STEP 5: Save session and cookie
$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $displayName;
$_SESSION['spotify_access_token'] = $accessToken;
$_SESSION['spotify_user'] = $userData;

$expiry = time() + (86400 * 7);
setcookie('spotify_token', $accessToken, time() + 604800, '/'); // 7 days
setcookie('user_id', $userId, time() + 604800, '/');
setcookie('username', $displayName, time() + 604800, '/');

// ✅ Save and redirect
session_write_close();
header("Location: dashboard.php");
exit;
