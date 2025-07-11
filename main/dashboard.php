<?php
session_start();

// Restore session from cookies
if (!isset($_SESSION['username']) && isset($_COOKIE['username'])) {
    $_SESSION['username'] = $_COOKIE['username'];
    $_SESSION['user_id'] = $_COOKIE['user_id'] ?? null;
    $_SESSION['spotify_access_token'] = $_COOKIE['spotify_token'] ?? null;
}

// If still not authenticated, redirect to login
if (!isset($_SESSION['username']) || !isset($_SESSION['spotify_access_token'])) {
    header("Location: login.php");
    exit;
}

// At this point, session is valid. Assign vars if needed.
$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];
$accessToken = $_SESSION['spotify_access_token'];

// === Spotify Playlist Logic ===
function searchPlaylistIdByName($name, $token) {
    $url = 'https://api.spotify.com/v1/search?q=' . urlencode($name) . '&type=playlist&limit=1';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"]
    ]);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    return $data['playlists']['items'][0]['id'] ?? null;
}

function getPlaylistTracks($playlistId, $token) {
    $url = "https://api.spotify.com/v1/playlists/{$playlistId}/tracks?limit=50";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "<p class='text-red-400'>Spotify API error: HTTP $code</p><pre>" . htmlspecialchars($response) . "</pre>";
        return [];
    }

    $data = json_decode($response, true);
    return $data['items'] ?? [];
}

$playlistNames = ['RapCaviar', 'Billboard', 'Modern Jazz', 'IndieMusic', 'Discover Daily', 'Opium', 'Best Mumble Rap', 'AllOut2000s', 'VOLUME', 'R&B Mix', 'Alternative', 'Rock', 'Metal', 'Clsasic Emo', 'Modern Day Rap', 'Soul Classics', 'Filipino Classics', 'Shoegaze', 'Psychedelic'];

$playlistTracks = [];
foreach ($playlistNames as $name) {
    $playlistId = searchPlaylistIdByName($name, $accessToken);
    $playlistTracks[$name] = $playlistId ? getPlaylistTracks($playlistId, $accessToken) : [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="/PHP/Nakamura/nakamura/assets/logo.png" type="image/png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    .album-row { scroll-behavior: smooth; scroll-snap-type: x mandatory; }
    .album-card { scroll-snap-align: start; transition: transform 0.3s ease; }
    .album-card:hover { transform: scale(1.05); z-index: 10; }
    .nav-button { transition: all 0.3s ease; backdrop-filter: blur(10px); opacity: 0; pointer-events: none; }
    .nav-button:hover { background-color: rgba(255, 255, 255, 0.2); transform: scale(1.1); }
    .section-container { position: relative; }
    .section-container:hover .nav-button { opacity: 1; pointer-events: auto; }
    .nav-button.left { left: 10px; }
    .nav-button.right { right: 10px; }
  </style>
</head>

<body class="bg-[#0A1128] text-white">
<?php include '/xampp/htdocs/PHP/Nakamura/nakamura/views/navigation.php'; ?>

<div class="px-4 py-6 sm:px-6 md:px-8">
  <h1 class="text-3xl font-bold mb-6">Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 🎧</h1>

  <?php foreach ($playlistTracks as $playlistName => $tracks): ?>
    <?php if (!empty($tracks)): ?>
      <section class="mb-12 section-container">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl sm:text-2xl font-semibold"><?= htmlspecialchars($playlistName) ?></h2>
        </div>
        <button class="nav-button left absolute top-1/2 transform -translate-y-1/2 z-20 text-white w-12 h-12 rounded-full bg-black bg-opacity-50 hover:bg-opacity-70"
                onclick="scrollAlbums('<?= md5($playlistName) ?>', 'left')">
          <i class="fas fa-chevron-left"></i>
        </button>
        <button class="nav-button right absolute top-1/2 transform -translate-y-1/2 z-20 text-white w-12 h-12 rounded-full bg-black bg-opacity-50 hover:bg-opacity-70"
                onclick="scrollAlbums('<?= md5($playlistName) ?>', 'right')">
          <i class="fas fa-chevron-right"></i>
        </button>

        <div class="relative">
          <div id="<?= md5($playlistName) ?>-row" class="album-row overflow-x-auto scrollbar-hide w-full">
            <div class="flex flex-nowrap gap-4 w-max px-4">
              <?php foreach ($tracks as $item): ?>
                <?php
                  $track = $item['track'] ?? null;
                  if (!$track) continue;
                  $image = $track['album']['images'][1]['url'] ?? 'https://via.placeholder.com/200x200';
                  $name = $track['name'] ?? 'Unknown';
                  $artist = $track['artists'][0]['name'] ?? 'Unknown';
                ?>
                <a href="ratings.php?track_id=<?= urlencode($track['id']) ?>" class="album-card w-[160px] sm:w-[180px] md:w-[200px] flex-shrink-0 bg-gray-800 rounded-lg p-3 shadow-md hover:scale-105 transition-transform">
                  <div class="h-[160px] sm:h-[180px] md:h-[200px] w-full overflow-hidden rounded mb-3">
                    <img src="<?= htmlspecialchars($image) ?>" class="w-full h-full object-cover" alt="<?= htmlspecialchars($name) ?>" />
                  </div>
                  <p class="font-semibold truncate text-sm sm:text-base"><?= htmlspecialchars($name) ?></p>
                  <p class="text-xs sm:text-sm text-gray-400 truncate"><?= htmlspecialchars($artist) ?></p>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </section>
    <?php else: ?>
      <p class="text-red-400 mb-6">No tracks found in <strong><?= htmlspecialchars($playlistName) ?></strong>.</p>
    <?php endif; ?>
  <?php endforeach; ?>
</div>

<script>
  function scrollAlbums(id, direction) {
    const container = document.getElementById(id + '-row');
    const scrollAmount = 220;
    const itemsToScroll = 3;

    container.scrollBy({
      left: direction === 'left' ? -scrollAmount * itemsToScroll : scrollAmount * itemsToScroll,
      behavior: 'smooth'
    });
  }

  document.querySelectorAll('.album-row').forEach(row => {
    row.addEventListener('wheel', e => {
      e.preventDefault();
      row.scrollBy({ left: e.deltaY > 0 ? 220 : -220, behavior: 'smooth' });
    });
  });
</script>
</body>
</html>
