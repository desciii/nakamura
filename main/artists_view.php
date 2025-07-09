<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/db.php';

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

$userId = $_SESSION['user_id'];

// Like/Unlike handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_artist'])) {
    $stmt = $conn->prepare("SELECT id FROM artist_likes WHERE user_id = ? AND artist_id = ?");
    $stmt->bind_param("is", $userId, $artistId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Already liked → Unlike
        $del = $conn->prepare("DELETE FROM artist_likes WHERE user_id = ? AND artist_id = ?");
        $del->bind_param("is", $userId, $artistId);
        $del->execute();
        $del->close();
    } else {
        // Not liked → Like
        $ins = $conn->prepare("INSERT INTO artist_likes (user_id, artist_id) VALUES (?, ?)");
        $ins->bind_param("is", $userId, $artistId);
        $ins->execute();
        $ins->close();
    }

    $stmt->close();
    header("Location: artists_view.php?artist_id=" . urlencode($artistId));
    exit;
}

// Check if user liked the artist
$userLiked = false;
$check = $conn->prepare("SELECT 1 FROM artist_likes WHERE user_id = ? AND artist_id = ?");
$check->bind_param("is", $userId, $artistId);
$check->execute();
$check->store_result();
$userLiked = $check->num_rows > 0;
$check->close();

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
function fetchAllAlbums($artistId, $accessToken) {
    $albums = [];
    $url = "https://api.spotify.com/v1/artists/$artistId/albums?include_groups=album&limit=50&market=PH"; // ← only "album"

    while ($url) {
        $response = fetchSpotify($url, $accessToken);
        if (!$response || !isset($response['items'])) break;

        $albums = array_merge($albums, $response['items']);
        $url = $response['next'] ?? null;
    }

    return $albums;
}

$albums = fetchAllAlbums($artistId, $accessToken);

$uniqueAlbums = [];
$seen = [];

foreach ($albums as $album) {
    $name = strtolower(trim($album['name']));
    if (!in_array($name, $seen)) {
        $seen[] = $name;
        $uniqueAlbums[] = $album;
    }
}

$albums = $uniqueAlbums;

// ✅ Only include albums with full release date precision
$albums = array_filter($albums, function ($album) {
    return isset($album['release_date_precision']) && $album['release_date_precision'] === 'day';
});

// ✅ Sort albums by release date descending
usort($albums, function ($a, $b) {
    return strtotime($b['release_date']) <=> strtotime($a['release_date']);
});

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
      <form method="post" class="inline-block ml-2">
        <input type="hidden" name="like_artist" value="1">
        <button type="submit" class="bg-pink-500 hover:bg-pink-600 text-white px-4 py-2 rounded">
          <?= $userLiked ? 'Unlike Artist' : 'Like Artist' ?>
        </button>
      </form>
    </div>
  </div>

  <!-- Top Tracks -->
  <div class="mb-12">
    <h2 class="text-2xl font-semibold mb-4">Top Tracks</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-5">
      <?php foreach (array_slice($topTracks, 0, 8) as $track): ?>
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

  <!-- New Tracks -->
<?php
  function fetchAllAlbumsAndSingles($artistId, $accessToken) {
      $albums = [];
      $url = "https://api.spotify.com/v1/artists/$artistId/albums?include_groups=album,single&limit=50&market=PH";

      while ($url) {
          $response = fetchSpotify($url, $accessToken);
          if (!$response || !isset($response['items'])) break;

          $albums = array_merge($albums, $response['items']);
          $url = $response['next'] ?? null;
      }

      return $albums;
  }

  $albumsRaw = fetchAllAlbumsAndSingles($artistId, $accessToken);
  $albumsRaw = array_filter($albumsRaw, function ($a) {
      return $a['release_date_precision'] === 'day';
  });

  $seen = [];
  $filteredAlbums = [];

  foreach ($albumsRaw as $album) {
      $name = strtolower(trim($album['name']));
      if (!in_array($name, $seen)) {
          $seen[] = $name;
          $filteredAlbums[] = $album;
      }
  }

  usort($filteredAlbums, function ($a, $b) {
      return strtotime($b['release_date']) <=> strtotime($a['release_date']);
  });

  $newTracks = [];
  $addedTrackIds = [];

  foreach ($filteredAlbums as $release) {
      if (count($newTracks) >= 8) break;

      $tracksData = fetchSpotify("https://api.spotify.com/v1/albums/{$release['id']}/tracks?limit=10", $accessToken);
      foreach ($tracksData['items'] ?? [] as $t) {
          if (count($newTracks) >= 8) break;

          $trackId = $t['id'];
          if (in_array($trackId, $addedTrackIds)) continue;

          $newTracks[] = [
              'id' => $trackId,
              'name' => $t['name'],
              'album_image' => $release['images'][0]['url'] ?? null,
          ];
          $addedTrackIds[] = $trackId;
      }
  }
  ?>
  <?php if (!empty($newTracks)): ?>
    <div class="mb-12">
      <h2 class="text-2xl font-semibold mb-4">New Tracks</h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-5">
        <?php foreach ($newTracks as $track): ?>
          <div class="bg-gray-800 p-4 rounded-lg shadow hover:bg-gray-700 transition cursor-pointer group relative">
            <img src="<?= htmlspecialchars($track['album_image']) ?>" class="w-full aspect-square rounded mb-3 object-cover" />
            <p class="text-sm font-semibold truncate"><?= htmlspecialchars($track['name']) ?></p>
            <a href="ratings.php?track_id=<?= urlencode($track['id']) ?>" class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
              <span class="bg-green-500 text-white px-3 py-1 rounded text-xs font-medium">Rate</span>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

 <?php
  usort($albums, function ($a, $b) {
      return strtotime($b['release_date']) <=> strtotime($a['release_date']);
  });
  ?>

  <div>
    <h2 class="text-2xl font-semibold mb-4">Albums</h2>
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
