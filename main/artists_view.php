<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['spotify_access_token']) && isset($_COOKIE['spotify_token'])) {
    $_SESSION['spotify_access_token'] = $_COOKIE['spotify_token'];
}
if (!isset($_SESSION['username']) && isset($_COOKIE['username'])) {
    $_SESSION['username'] = $_COOKIE['username'];
    $_SESSION['user_id'] = $_COOKIE['user_id'] ?? null;
}

if (!isset($_SESSION['spotify_access_token']) || !isset($_SESSION['username'])) {
    die("🔒 Not logged in.");
}

$accessToken = $_SESSION['spotify_access_token'];
$username = $_SESSION['username'];

$artistId = $_GET['artist_id'] ?? null;
if (!$artistId) {
    die("❌ No artist ID provided.");
}

function fetchSpotify($url, $token) {
    $opts = ['http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer $token"
    ]];
    $res = @file_get_contents($url, false, stream_context_create($opts));
    return $res ? json_decode($res, true) : null;
}

// Fetch artist info
$artist = fetchSpotify("https://api.spotify.com/v1/artists/$artistId", $accessToken);
$topTracks = fetchSpotify("https://api.spotify.com/v1/artists/$artistId/top-tracks?market=PH", $accessToken)['tracks'] ?? [];
$albums = fetchSpotify("https://api.spotify.com/v1/artists/$artistId/albums?include_groups=album,single&limit=5", $accessToken)['items'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($artist['name']) ?> - View</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="/PHP/Nakamura/nakamura/assets/logo.png" />
</head>
<body class="bg-[#0A1128] text-white">
<?php include __DIR__ . '/../views/navigation.php'; ?>

<div class="max-w-6xl mx-auto px-6 py-10">
  <!-- Artist Info -->
  <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 mb-10">
    <img src="<?= $artist['images'][0]['url'] ?? 'https://via.placeholder.com/300' ?>" alt="<?= htmlspecialchars($artist['name']) ?>" class="w-40 h-40 rounded-full object-cover border-4 border-green-500 shadow-lg" />
    <div>
      <h1 class="text-4xl font-bold"><?= htmlspecialchars($artist['name']) ?></h1>
      <p class="text-gray-400 mt-2"><?= number_format($artist['followers']['total'] ?? 0) ?> followers</p>
      <p class="text-sm mt-1 text-gray-300">
        <?= implode(', ', array_slice($artist['genres'] ?? [], 0, 3)) ?>
      </p>
      <a href="<?= $artist['external_urls']['spotify'] ?>" target="_blank" class="mt-4 inline-block bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
        View on Spotify
      </a>
    </div>
  </div>

  <!-- Top Tracks -->
  <div class="mb-12">
    <h2 class="text-2xl font-semibold mb-4">Top Tracks</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-5">
      <?php foreach ($topTracks as $track): ?>
        <div class="bg-gray-800 p-4 rounded-lg shadow hover:bg-gray-700 transition cursor-pointer group relative">
          <img src="<?= $track['album']['images'][0]['url'] ?? '' ?>" class="w-full aspect-square rounded mb-3 object-cover" />
          <p class="text-sm font-semibold truncate"><?= htmlspecialchars($track['name']) ?></p>
          <a href="ratings.php?track_id=<?= urlencode($track['id']) ?>" class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
            <span class="bg-green-500 text-white px-3 py-1 rounded text-xs font-medium">Rate</span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

    <!-- Recent Albums -->
    <div>
    <h2 class="text-2xl font-semibold mb-4">Latest Releases</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-5">
        <?php foreach ($albums as $album): ?>
        <?php
            $albumId = urlencode($album['id']);
            $albumName = urlencode($album['name']);
            $artistName = urlencode($album['artists'][0]['name'] ?? 'Unknown');
        ?>
        <a href="album_tracklist.php?album_id=<?= $albumId ?>&name=<?= $albumName ?>&artist=<?= $artistName ?>" class="block">
            <div class="bg-gray-800 p-3 rounded-lg hover:bg-gray-700 transition shadow">
            <img src="<?= $album['images'][0]['url'] ?? '' ?>" class="w-full aspect-square rounded object-cover mb-2" />
            <p class="text-sm font-medium truncate"><?= htmlspecialchars($album['name']) ?></p>
            <p class="text-xs text-gray-400"><?= htmlspecialchars($album['release_date']) ?></p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    </div>
</div>
</body>
</html>
