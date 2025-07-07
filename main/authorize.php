<?php

require_once __DIR__ . '/config.php';
loadEnv(__DIR__ . '/.env');

$client_id = $_ENV['SPOTIFY_CLIENT_ID'];
$redirectUri = $_ENV['SPOTIFY_REDIRECT_URI'];

$scope = 'playlist-read-private playlist-read-collaborative user-read-email user-read-private';

$url = 'https://accounts.spotify.com/authorize' .
    '?response_type=code' .
    '&client_id=' . $client_id .
    '&scope=' . urlencode($scope) .
    '&redirect_uri=' . urlencode($redirectUri) .
    '&show_dialog=true';

header("Location: $url");
exit;
