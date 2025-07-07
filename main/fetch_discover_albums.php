<?php
session_start();
$accessToken = $_SESSION['spotify_access_token'] ?? $_COOKIE['spotify_token'] ?? null;
if (!$accessToken) {
    header("Location: /PHP/Nakamura/nakamura/main/authorize.php");
    exit;
}

if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    $_SESSION['username'] = $_COOKIE['username'];
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function getArtistsFromPlaylist($playlistId, $token) {
    $url = "https://api.spotify.com/v1/playlists/$playlistId/tracks?limit=50";
    $opts = ['http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer $token"
    ]];
    $json = @file_get_contents($url, false, stream_context_create($opts));
    if (!$json) return [];
    $data = json_decode($json, true);
    $artistIds = [];
    foreach ($data['items'] as $item) {
        foreach ($item['track']['artists'] as $artist) {
            $artistIds[] = $artist['id'];
        }
    }
    return array_unique($artistIds);
}

function getAlbumsByArtist($artistId, $token) {
    $url = "https://api.spotify.com/v1/artists/$artistId/albums?include_groups=album&market=PH&limit=5";
    $opts = ['http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer $token"
    ]];
    $json = @file_get_contents($url, false, stream_context_create($opts));
    if (!$json) return [];
    $data = json_decode($json, true);
    return $data['items'] ?? [];
}

$genrePlaylists = [
    'pop' => '37i9dQZF1DWUa8ZRTfalHk',
    'hip-hop' => '37i9dQZF1DX0XUsuxWHRQd',
    'rnb' => '37i9dQZF1DX4SBhb3fqCJd',
    'k-pop' => '37i9dQZF1DX9tPFwDMOaN1',
    'rock' => '37i9dQZF1DWXRqgorJj26U',
    'country' => '37i9dQZF1DX1lVhptIYRda',
    'indie' => '37i9dQZF1DX2sUQwD7tbmL',
    'dance' => '37i9dQZF1DX4dyzvuaRJ0n',
    'opm' => '37i9dQZF1DWXbttAJcbphz',
];

$genreAlbums = [];

foreach ($genrePlaylists as $genre => $playlistId) {
    $artistIds = getArtistsFromPlaylist($playlistId, $accessToken);
    $albums = [];
    foreach ($artistIds as $artistId) {
        foreach (getAlbumsByArtist($artistId, $accessToken) as $album) {
            $albums[$album['id']] = $album;
        }
    }
    $genreAlbums[$genre] = array_values($albums);
}
?>
