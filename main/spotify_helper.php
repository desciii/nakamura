<?php
require_once __DIR__ . '/config.php';
loadEnv(__DIR__ . '/.env');

$_SESSION['spotify_access_token'] = 'invalid_or_expired_token';

function isSpotifyTokenExpired($accessToken): bool {
    $ch = curl_init("https://api.spotify.com/v1/me");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"]
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code !== 200;
}

function refreshSpotifyToken($refreshToken) {
    $clientId = $_ENV['SPOTIFY_CLIENT_ID'];
    $clientSecret = $_ENV['SPOTIFY_CLIENT_SECRET'];

    $headers = [
        "Authorization: Basic " . base64_encode("$clientId:$clientSecret"),
        "Content-Type: application/x-www-form-urlencoded"
    ];
    $body = http_build_query([
        'grant_type'    => 'refresh_token',
        'refresh_token' => $refreshToken
    ]);

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => $body,
        ]
    ];

    $response = file_get_contents("https://accounts.spotify.com/api/token", false, stream_context_create($options));
    $data = json_decode($response, true);

    if (!isset($data['access_token'])) {
        return null;
    }

    return $data['access_token'];
}
