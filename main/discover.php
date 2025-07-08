<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['spotify_access_token']) && isset($_COOKIE['spotify_token'])) {
    $_SESSION['spotify_access_token'] = $_COOKIE['spotify_token'];
}
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    $_SESSION['username'] = $_COOKIE['username'];
}

if (!isset($_SESSION['spotify_access_token']) || !isset($_SESSION['user_id'])) {
    die("🔒 Not logged in. Token missing.");
}

$accessToken = $_SESSION['spotify_access_token'];
$username = $_SESSION['username'];

function fetchSpotify($url, $token) {
    $opts = ['http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer $token"
    ]];
    $res = @file_get_contents($url, false, stream_context_create($opts));
    return $res ? json_decode($res, true) : null;
}

function searchPlaylistId($playlistName, $token) {
    $query = urlencode($playlistName);
    $url = "https://api.spotify.com/v1/search?q=$query&type=playlist&limit=1";
    $result = fetchSpotify($url, $token);
    return $result['playlists']['items'][0]['id'] ?? null;
}

function getTopTracksFromPlaylist($playlistId, $token, $limit = 10) {
    $url = "https://api.spotify.com/v1/playlists/$playlistId/tracks?limit=$limit";
    $data = fetchSpotify($url, $token);
    return $data['items'] ?? [];
}

$playlistNames = ['RapCaviar', 'Discover Weekly', 'Billboard'];
$artistMap = [];

foreach ($playlistNames as $name) {
    $playlistId = searchPlaylistId($name, $accessToken);
    if (!$playlistId) continue;

    $tracks = getTopTracksFromPlaylist($playlistId, $accessToken, 15);
    foreach ($tracks as $item) {
        $track = $item['track'] ?? null;
        if (!$track || !isset($track['artists'])) continue;

        foreach ($track['artists'] as $artist) {
            $id = $artist['id'];
            if (!isset($artistMap[$id])) {
                $artistMap[$id] = [
                    'id' => $id,
                    'name' => $artist['name'],
                    'url' => $artist['external_urls']['spotify'] ?? '#'
                ];
            }
        }
    }
}

// Add artist images
$artists = array_values($artistMap);
shuffle($artists);
$selectedArtists = array_slice($artists, 0, 49); // 7x7 grid

foreach ($selectedArtists as &$artist) {
    $data = fetchSpotify("https://api.spotify.com/v1/artists/{$artist['id']}", $accessToken);
    $artist['image'] = $data['images'][0]['url'] ?? 'https://via.placeholder.com/150';
}
unset($artist);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Discover</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="/PHP/Nakamura/nakamura/assets/logo.png" type="image/png" />
  <style>
    .artist-card {
      transition: transform 0.2s ease;
    }

    .artist-card:hover {
      transform: scale(1.05);
    }
  </style>
</head>

<body class="bg-[#0A1128] text-white min-h-screen">
<?php include '/xampp/htdocs/PHP/Nakamura/nakamura/views/navigation.php'; ?>

<div class="max-w-7xl mx-auto px-6 py-12">
  <div class="text-center mb-10">
    <h1 class="text-4xl font-bold mb-2">Welcome, <?= htmlspecialchars($username) ?> 🎷</h1>
    <p class="text-gray-400 text-sm">Top Artists from RapCaviar, Discover Weekly & Billboard</p>
  </div>

  <?php if (empty($selectedArtists)): ?>
    <div class="text-center py-16">
      <p class="text-red-400">No artists found from public Spotify playlists.</p>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-7 gap-6">
      <?php foreach ($selectedArtists as $artist): ?>
        <div class="artist-card bg-gray-900 rounded-xl p-3 hover:bg-gray-800 shadow-md cursor-pointer transition-all"
             onclick="window.open('<?= htmlspecialchars($artist['url']) ?>', '_blank')">
          <div class="w-full aspect-square overflow-hidden rounded-lg mb-3 bg-gray-700">
            <img src="<?= htmlspecialchars($artist['image']) ?>"
                 alt="<?= htmlspecialchars($artist['name']) ?>"
                 class="w-full h-full object-cover" />
          </div>
          <p class="text-xs text-center font-medium truncate text-gray-200">
            <?= htmlspecialchars($artist['name']) ?>
          </p>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-10">
      <p class="text-sm text-gray-500">Showing <?= count($selectedArtists) ?> trending artists</p>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
