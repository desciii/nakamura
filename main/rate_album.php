<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['spotify_access_token'])) {
    header("Location: login.php");
    exit;
}

$accessToken = $_SESSION['spotify_access_token'];
$username = $_SESSION['username'];

$albumId = $_GET['album_id'] ?? null;
if (!$albumId) {
    die("Album ID missing.");
}

// Fetch album details
$ch = curl_init("https://api.spotify.com/v1/albums/$albumId");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"]
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if (!$data || isset($data['error'])) {
    die("Failed to fetch album.");
}

$albumName = $data['name'] ?? 'Unknown';
$artist = $data['artists'][0]['name'] ?? 'Unknown';
$tracks = $data['tracks']['items'] ?? [];
$cover = $data['images'][0]['url'] ?? 'https://via.placeholder.com/300';
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($albumName) ?> - Rate Album</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="/PHP/Nakamura/nakamura/assets/logo.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    .star { font-size: 1.5rem; color: #4B5563; cursor: pointer; }
    .star.selected { color: #FFD700; }
    .star:hover { color: #FBBF24; }
    .tracklist-scroll::-webkit-scrollbar { display: none; }
    .tracklist-scroll { scrollbar-width: none; -ms-overflow-style: none; }
  </style>
</head>
<body class="bg-[#0A1128] text-white min-h-screen">
  <?php include __DIR__ . '/../views/navigation.php'; ?>

  <div class="max-w-6xl mx-auto px-4 py-6 grid md:grid-cols-3 gap-4">
    <!-- Album Info + Rating -->
    <div class="md:col-span-2 space-y-4">
      <div class="flex gap-4 bg-gray-800 p-4 rounded-lg">
        <img src="<?= htmlspecialchars($cover) ?>" class="w-28 h-28 rounded object-cover">
        <div>
          <a href="album_tracklist.php?album_id=<?= urlencode($albumId) ?>" class="text-xl font-bold text-amber-300 hover:underline hover:text-yellow-400 transition">
            <?= htmlspecialchars($albumName) ?>
          </a>
          <p class="text-sm text-gray-400 mt-1">
            by <span class="text-white font-semibold"><?= htmlspecialchars($artist) ?></span>
          </p>
          <p class="text-xs text-gray-500 mt-1"><?= count($tracks) ?> track<?= count($tracks) === 1 ? '' : 's' ?></p>
        </div>
      </div>

      <!-- Rating Form -->
      <form method="post" action="submit_album_rating.php" class="bg-gray-800 p-4 rounded-lg space-y-3">
        <input type="hidden" name="album_id" value="<?= htmlspecialchars($albumId) ?>">
        <input type="hidden" name="album_name" value="<?= htmlspecialchars($albumName) ?>">
        <input type="hidden" name="artist_name" value="<?= htmlspecialchars($artist) ?>">
        <input type="hidden" name="rating" id="ratingInput">

        <label class="text-sm font-medium text-yellow-400">Your Rating</label>
        <div id="stars" class="flex gap-1">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="fas fa-star star" data-value="<?= $i ?>"></i>
          <?php endfor; ?>
        </div>

        <textarea name="thoughts" rows="3" placeholder="Share your thoughts..."
          class="w-full bg-gray-700 p-2 rounded text-sm resize-none text-white"
        ></textarea>

        <button type="submit"
          class="w-full bg-green-600 hover:bg-green-700 py-2 rounded text-sm font-semibold transition">
          Submit Rating
        </button>
      </form>
    </div>

    <!-- Tracklist -->
    <div class="bg-gray-800 p-4 rounded-lg max-h-[480px] overflow-y-auto tracklist-scroll">
      <h2 class="text-lg text-amber-300 mb-3">Tracklist</h2>
      <div class="space-y-2 text-sm">
        <?php foreach ($tracks as $index => $track): ?>
        <div class="flex justify-between items-center bg-gray-700 hover:bg-gray-600 p-2 rounded">
          <span class="truncate"><?= $index + 1 ?>. <?= htmlspecialchars($track['name']) ?></span>
          <a href="ratings.php?track_id=<?= urlencode($track['id']) ?>" class="text-green-400 hover:underline">Rate</a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <script>
    document.querySelectorAll('.star').forEach(star => {
      star.addEventListener('click', () => {
        const value = parseInt(star.dataset.value);
        document.getElementById('ratingInput').value = value;
        document.querySelectorAll('.star').forEach(s => {
          s.classList.toggle('selected', parseInt(s.dataset.value) <= value);
        });
      });
    });
  </script>
</body>
</html>