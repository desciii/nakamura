<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['spotify_access_token']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$accessToken = $_SESSION['spotify_access_token'];
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Helper for API call
function fetchSpotify($url, $token) {
    $res = @file_get_contents($url, false, stream_context_create([
        'http' => ['header' => "Authorization: Bearer $token"]
    ]));
    return $res ? json_decode($res, true) : null;
}

// Fetch profile
$userData = fetchSpotify('https://api.spotify.com/v1/me', $accessToken);
if (!$userData || isset($userData['error'])) {
    header("Location: login.php");
    exit;
}

$spotifyName = $userData['display_name'] ?? 'Spotify User';
$spotifyId = $userData['id'] ?? '';
$spotifyProfile = $userData['external_urls']['spotify'] ?? '#';
$profileImage = $userData['images'][0]['url'] ?? null;

// Fetch Rated Tracks
$ratings = [];
$stmt = $conn->prepare("SELECT track_id, track_name, artist_name, rating, thoughts, created_at FROM ratings WHERE user_id = ? ORDER BY created_at DESC");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $ratings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch Liked Tracks
$likedTracks = [];
$stmt = $conn->prepare("SELECT track_id FROM track_likes WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $trackIds = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'track_id');
    $stmt->close();

    foreach ($trackIds as $trackId) {
        $track = fetchSpotify("https://api.spotify.com/v1/tracks/$trackId", $accessToken);
        if ($track && isset($track['id'])) {
            $likedTracks[] = [
                'id' => $track['id'],
                'name' => $track['name'],
                'artist' => $track['artists'][0]['name'] ?? 'Unknown',
                'image' => $track['album']['images'][1]['url'] ?? null
            ];
        }
    }
}

// Fetch Liked Albums
$likedAlbums = [];
$stmt = $conn->prepare("SELECT album_id FROM album_likes WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $albumIds = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'album_id');
    $stmt->close();

    foreach ($albumIds as $albumId) {
        $album = fetchSpotify("https://api.spotify.com/v1/albums/$albumId", $accessToken);
        if ($album && isset($album['id'])) {
            $likedAlbums[] = [
                'id' => $album['id'],
                'name' => $album['name'],
                'artist' => $album['artists'][0]['name'] ?? 'Unknown',
                'image' => $album['images'][0]['url'] ?? null
            ];
        }
    }
}

// Fetch Rated Albums
$ratedAlbums = [];
$stmt = $conn->prepare("SELECT album_id, album_name, artist_name, rating, thoughts, created_at FROM album_ratings WHERE user_id = ? ORDER BY created_at DESC");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $ratedAlbums = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($spotifyName) ?> — Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="/PHP/Nakamura/nakamura/assets/logo.png">
  <style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { scrollbar-width: none; -ms-overflow-style: none; }
  </style>
</head>
<body class="bg-[#0A1128] text-white">
<?php include __DIR__ . '/../views/navigation.php'; ?>

<div class="max-w-5xl mx-auto px-4 py-8 space-y-10">
  <!-- Profile -->
  <div class="flex gap-6 items-center bg-gray-800 p-6 rounded-xl">
    <div class="relative">
      <?php if ($profileImage): ?>
        <img src="<?= $profileImage ?>" class="w-24 h-24 rounded-full border-4 border-amber-400 object-cover">
      <?php else: ?>
        <div class="w-24 h-24 bg-yellow-400 rounded-full flex items-center justify-center text-black font-bold text-3xl">
          <?= strtoupper($username[0]) ?>
        </div>
      <?php endif; ?>
      <div class="absolute -bottom-2 -right-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
        <i class="fab fa-spotify text-white text-xs"></i>
      </div>
    </div>
    <div>
      <h1 class="text-2xl font-bold text-amber-300">@<?= htmlspecialchars($username) ?></h1>
      <p class="text-gray-300">Name: <span class="text-white"><?= htmlspecialchars($spotifyName) ?></span></p>
      <p class="text-gray-300 text-sm">ID: <span class="font-mono"><?= htmlspecialchars($spotifyId) ?></span></p>
      <a href="<?= $spotifyProfile ?>" target="_blank" class="text-blue-400 text-sm hover:underline">Spotify Profile</a>
    </div>
  </div>

    <!-- Liked Albums -->
    <div>
    <h2 class="text-xl font-semibold text-yellow-300 mb-3">Liked Albums</h2>
    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 max-h-96 overflow-y-auto scrollbar-hide pr-1">
        <?php foreach ($likedAlbums as $album): ?>
        <div class="bg-gray-800 p-2 rounded-lg text-sm">
            <img src="<?= $album['image'] ?>" class="w-full aspect-square object-cover mb-2 rounded">
            <div class="font-semibold truncate text-xs"><?= $album['name'] ?></div>
            <div class="text-gray-400 text-xs truncate"><?= $album['artist'] ?></div>
            <a href="album_tracklist.php?album_id=<?= $album['id'] ?>" class="text-green-400 text-xs hover:underline">View Album</a>
        </div>
        <?php endforeach; ?>
        <?php if (empty($likedAlbums)): ?><p class="text-gray-400">No liked albums.</p><?php endif; ?>
    </div>
    </div>

    <!-- Liked Tracks -->
    <div>
    <h2 class="text-xl font-semibold text-yellow-300 mb-3">Liked Tracks</h2>
    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 max-h-96 overflow-y-auto scrollbar-hide pr-1">
        <?php foreach ($likedTracks as $track): ?>
        <div class="bg-gray-800 p-2 rounded-lg text-sm">
            <img src="<?= $track['image'] ?>" class="w-full aspect-square object-cover mb-2 rounded">
            <div class="font-semibold truncate text-xs"><?= $track['name'] ?></div>
            <div class="text-gray-400 text-xs truncate"><?= $track['artist'] ?></div>
            <a href="ratings.php?track_id=<?= $track['id'] ?>" class="text-green-400 text-xs hover:underline">View Review</a>
        </div>
        <?php endforeach; ?>
        <?php if (empty($likedTracks)): ?><p class="text-gray-400">No liked tracks.</p><?php endif; ?>
    </div>
    </div>

  <!-- Rated Tracks -->
  <div>
    <h2 class="text-xl font-semibold text-yellow-300 mb-3">Rated Tracks</h2>
    <div class="space-y-3 max-h-96 overflow-y-auto scrollbar-hide pr-1">
      <?php foreach ($ratings as $track): ?>
        <a href="ratings.php?track_id=<?= urlencode($track['track_id']) ?>" class="block group bg-gray-800 hover:bg-gray-900 transition rounded-lg">
          <div class="p-4">
            <div class="flex justify-between">
              <div>
                <div class="font-semibold"><?= $track['track_name'] ?></div>
                <div class="text-sm text-gray-400"><?= $track['artist_name'] ?></div>
              </div>
              <div class="text-yellow-400"><?= str_repeat('★', $track['rating']) ?></div>
            </div>
            <p class="text-sm text-gray-300 italic mt-2">"<?= htmlspecialchars($track['thoughts']) ?>"</p>
            <p class="text-xs text-gray-500 mt-1"><?= date('M j, Y', strtotime($track['created_at'])) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
      <?php if (empty($ratings)): ?><p class="text-gray-400">No track ratings.</p><?php endif; ?>
    </div>
  </div>

  <!-- Rated Albums -->
  <div>
    <h2 class="text-xl font-semibold text-yellow-400 mb-3">Rated Albums</h2>
    <div class="space-y-3 max-h-96 overflow-y-auto scrollbar-hide pr-1">
      <?php foreach ($ratedAlbums as $album): ?>
        <a href="album_tracklist.php?album_id=<?= urlencode($album['album_id']) ?>" class="block group bg-gray-800 hover:bg-gray-900 transition rounded-lg">
          <div class="p-4">
            <div class="flex justify-between">
              <div>
                <div class="font-semibold"><?= $album['album_name'] ?></div>
                <div class="text-sm text-gray-400"><?= $album['artist_name'] ?></div>
              </div>
              <div class="text-yellow-400"><?= str_repeat('★', $album['rating']) ?></div>
            </div>
            <p class="text-sm text-gray-300 italic mt-2">"<?= htmlspecialchars($album['thoughts']) ?>"</p>
            <p class="text-xs text-gray-500 mt-1"><?= date('M j, Y', strtotime($album['created_at'])) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
      <?php if (empty($ratedAlbums)): ?><p class="text-gray-400">No album ratings.</p><?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
