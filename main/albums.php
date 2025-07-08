<?php
session_start();

if (!isset($_SESSION['spotify_access_token']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
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

function getTopTracksFromPlaylist($playlistId, $token, $limit = 15) {
    $url = "https://api.spotify.com/v1/playlists/$playlistId/tracks?limit=$limit";
    $data = fetchSpotify($url, $token);
    return $data['items'] ?? [];
}

$playlistNames = ['RapCaviar', 'Discover Weekly', 'Billboard', 'R&B Mix', 'Opium'];
$artistMap = [];

foreach ($playlistNames as $name) {
    $playlistId = searchPlaylistId($name, $accessToken);
    if (!$playlistId) continue;

    $tracks = getTopTracksFromPlaylist($playlistId, $accessToken);
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

$albums = [];

foreach ($artistMap as $artist) {
    $artistId = $artist['id'];
    $albumsData = fetchSpotify("https://api.spotify.com/v1/artists/$artistId/albums?include_groups=album&limit=3", $accessToken);
    foreach ($albumsData['items'] ?? [] as $album) {
        $albums[] = [
            'id' => $album['id'],
            'name' => $album['name'],
            'image' => $album['images'][0]['url'] ?? 'https://via.placeholder.com/200',
            'artist' => $album['artists'][0]['name'] ?? 'Unknown'
        ];
    }
}

shuffle($albums);
$albums = array_slice($albums, 0, 60);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Albums</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="/PHP/Nakamura/nakamura/assets/logo.png" type="image/png" />
  <style>
    .album-card {
      transition: all 0.2s ease;
    }
    
    .album-card:hover {
      transform: translateY(-2px);
    }
    
    .album-image {
      transition: transform 0.2s ease;
    }
    
    .album-card:hover .album-image {
      transform: scale(1.02);
    }
  </style>
</head>

<body class="bg-[#0A1128] text-white min-h-screen">
<?php include __DIR__ . '/../views/navigation.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-8">
  <!-- Header -->
  <div class="mb-8">
    <h1 class="text-3xl font-bold mb-2">Recent Albums from Trending Playlists</h1>
    <p class="text-gray-400">Discover the latest releases from your favorite artists</p>
  </div>

  <?php if (empty($albums)): ?>
    <div class="text-center py-16">
      <div class="bg-gray-800 rounded-xl p-8 max-w-md mx-auto">
        <div class="w-16 h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2zm12-3c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2z"></path>
          </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">No Albums Found</h3>
        <p class="text-gray-400">No albums found from trending playlists.</p>
      </div>
    </div>
  <?php else: ?>
    <!-- Albums Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 sm:gap-6">
      <?php foreach ($albums as $album): ?>
        <div class="album-card bg-gray-800 hover:bg-gray-700 rounded-xl p-4 cursor-pointer group shadow-lg"
            onclick="window.location.href='album_tracklist.php?album_id=<?= urlencode($album['id']) ?>'">
          <!-- Album Cover -->
          <div class="relative overflow-hidden rounded-lg mb-3 bg-gray-700">
            <img src="<?= htmlspecialchars($album['image']) ?>" 
                 alt="<?= htmlspecialchars($album['name']) ?>"
                 class="album-image w-full aspect-square object-cover" />
            
            <!-- Hover Play Button -->
            <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
              <div class="bg-green-500 rounded-full p-2 transform scale-90 group-hover:scale-100 transition-transform duration-200">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z"/>
                </svg>
              </div>
            </div>
          </div>
          
          <!-- Album Info -->
          <div class="space-y-1">
            <h3 class="text-sm font-semibold truncate group-hover:text-green-400 transition-colors duration-200">
              <?= htmlspecialchars($album['name']) ?>
            </h3>
            <p class="text-xs text-gray-400 truncate">
              <?= htmlspecialchars($album['artist']) ?>
            </p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Footer Stats -->
    <div class="text-center mt-12">
      <div class="bg-gray-800 rounded-lg p-4 inline-block">
        <p class="text-sm text-gray-300">
          Showing <span class="text-green-400 font-semibold"><?= count($albums) ?></span> albums from trending playlists
        </p>
      </div>
    </div>
  <?php endif; ?>
</div>
</body>
</html>