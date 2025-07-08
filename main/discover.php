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

$playlistNames = ['RapCaviar', 'Discover Weekly', 'Billboard', 'R&B Mix'];
$artistMap = [];

foreach ($playlistNames as $name) {
    $playlistId = searchPlaylistId($name, $accessToken);
    if (!$playlistId) continue;

    $tracks = getTopTracksFromPlaylist($playlistId, $accessToken, 20);
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

$artists = array_values($artistMap);
shuffle($artists);
$selectedArtists = array_slice($artists, 0, 49);

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
      transition: all 0.2s ease;
    }

    .artist-card:hover {
      transform: translateY(-2px);
    }

    .artist-image {
      transition: transform 0.2s ease;
    }

    .artist-card:hover .artist-image {
      transform: scale(1.02);
    }
  </style>
</head>

<body class="bg-[#0A1128] text-white min-h-screen">
<?php include '/xampp/htdocs/PHP/Nakamura/nakamura/views/navigation.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-8">
  <div class="mb-8">
    <h1 class="text-3xl font-bold mb-2">Welcome, <?= htmlspecialchars($username) ?> 🎷</h1>
    <p class="text-gray-400">Top Artists from RapCaviar, Discover Weekly, R&B Mix & Billboard</p>
  </div>

  <?php if (empty($selectedArtists)): ?>
    <div class="text-center py-16">
      <div class="bg-gray-800 rounded-xl p-8 max-w-md mx-auto">
        <div class="w-16 h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2zm12-3c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2z"></path>
          </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">No Artists Found</h3>
        <p class="text-gray-400">No artists found from public Spotify playlists.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-7 gap-4 sm:gap-6">
      <?php foreach ($selectedArtists as $artist): ?>
        <a href="artists_view.php?artist_id=<?= urlencode($artist['id']) ?>" class="artist-card bg-gray-800 hover:bg-gray-700 rounded-xl p-4 group shadow-lg block">
          <div class="relative overflow-hidden rounded-lg mb-3 bg-gray-700">
            <img src="<?= htmlspecialchars($artist['image']) ?>" alt="<?= htmlspecialchars($artist['name']) ?>" class="artist-image w-full aspect-square object-cover" />
            <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
              <span class="bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">View</span>
            </div>
          </div>
          <p class="text-xs text-center font-semibold truncate group-hover:text-green-400 transition-colors duration-200">
            <?= htmlspecialchars($artist['name']) ?>
          </p>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-12">
      <div class="bg-gray-800 rounded-lg p-4 inline-block">
        <p class="text-sm text-gray-300">
          Showing <span class="text-green-400 font-semibold"><?= count($selectedArtists) ?></span> trending artists
        </p>
      </div>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
