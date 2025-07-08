<?php
session_start();
if (!isset($_SESSION['spotify_access_token'])) {
    header("Location: login.php");
    exit;
}

$accessToken = $_SESSION['spotify_access_token'];
$query = $_GET['q'] ?? '';
$trackResults = [];
$artistResults = [];
$artistAlbums = [];

function fetchSpotify($url, $token) {
    $opts = ['http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer $token"
    ]];
    $res = @file_get_contents($url, false, stream_context_create($opts));
    return $res ? json_decode($res, true) : null;
}

if ($query) {
    $url = "https://api.spotify.com/v1/search?q=" . urlencode($query) . "&type=track,artist&limit=10";
    $data = fetchSpotify($url, $accessToken);
    $trackResults = $data['tracks']['items'] ?? [];
    $artistResults = $data['artists']['items'] ?? [];

    foreach ($artistResults as $artist) {
        $artistId = $artist['id'];
        $albumsUrl = "https://api.spotify.com/v1/artists/$artistId/albums?include_groups=album,single&limit=5";
        $albumsData = fetchSpotify($albumsUrl, $accessToken);

        foreach ($albumsData['items'] ?? [] as $album) {
            $artistAlbums[] = [
                'id' => $album['id'],
                'name' => $album['name'],
                'image' => $album['images'][0]['url'] ?? 'https://via.placeholder.com/200',
                'artist' => $album['artists'][0]['name'] ?? 'Unknown'
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Search Results - <?= htmlspecialchars($query) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="icon" href="/PHP/Nakamura/nakamura/assets/logo.png" type="image/png" />
</head>
<body class="bg-[#0A1128] text-white">
<?php include __DIR__ . '/../views/navigation.php'; ?>

<div class="p-6">
  <h1 class="text-2xl font-bold mb-4">Search Results for “<?= htmlspecialchars($query) ?>”</h1>

  <?php if (empty($trackResults) && empty($artistResults)): ?>
    <p class="text-gray-400">No results found.</p>
  <?php else: ?>

    <?php if (!empty($trackResults)): ?>
      <h2 class="text-xl font-semibold mb-3">Tracks</h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-10">
        <?php foreach ($trackResults as $track): ?>
          <?php
          $trackId = $track['id'];
          $name = $track['name'];
          $artist = $track['artists'][0]['name'] ?? 'Unknown';
          $image = $track['album']['images'][1]['url'] ?? 'https://via.placeholder.com/200';
          ?>
          <div class="bg-gray-800 rounded-lg shadow p-4 text-center hover:bg-gray-700">
            <img src="<?= htmlspecialchars($image) ?>" class="w-32 h-32 mx-auto object-cover rounded mb-3">
            <h2 class="text-base font-semibold truncate"><?= htmlspecialchars($name) ?></h2>
            <p class="text-sm text-gray-400 truncate"><?= htmlspecialchars($artist) ?></p>
            <a href="ratings.php?track_id=<?= urlencode($trackId) ?>"
               class="text-sm bg-green-500 hover:bg-green-600 text-white px-3 py-1 mt-3 rounded inline-block">
              Rate →
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($artistResults)): ?>
      <h2 class="text-xl font-semibold mb-3">Artists</h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-10">
        <?php foreach ($artistResults as $artist): ?>
          <?php
          $id = $artist['id'];
          $name = $artist['name'];
          $image = $artist['images'][0]['url'] ?? 'https://via.placeholder.com/200';
          $url = $artist['external_urls']['spotify'] ?? '#';
          ?>
            <div class="bg-gray-800 rounded-lg shadow p-4 text-center cursor-pointer hover:bg-gray-700"
                onclick="window.location.href='artists_view.php?artist_id=<?= urlencode($id) ?>'">
            <img src="<?= htmlspecialchars($image) ?>" class="w-32 h-32 mx-auto object-cover rounded-full mb-3">
            <h2 class="text-base font-semibold truncate"><?= htmlspecialchars($name) ?></h2>
            <p class="text-sm text-gray-400">Artist</p>
            </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($artistAlbums)): ?>
      <h2 class="text-xl font-semibold mb-3">Recent Albums</h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <?php foreach ($artistAlbums as $album): ?>
            <div class="bg-gray-900 rounded-lg shadow p-3 hover:bg-gray-800 cursor-pointer"
                onclick="window.location.href='album_tracklist.php?album_id=<?= urlencode($album['id']) ?>'">
            <img src="<?= htmlspecialchars($album['image']) ?>" class="w-full aspect-square object-cover rounded mb-2">
            <h3 class="text-sm font-medium truncate"><?= htmlspecialchars($album['name']) ?></h3>
            <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($album['artist']) ?></p>
            </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>
</body>
</html>
