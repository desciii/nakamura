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
    .tracklist-scroll::-webkit-scrollbar {
    display: none;
    }
    .tracklist-scroll {
    -ms-overflow-style: none;  
    scrollbar-width: none;   
    }
  </style>
</head>
<body class="bg-[#0A1128] text-white min-h-screen">
  <?php include __DIR__ . '/../views/navigation.php'; ?>

  <div class="max-w-6xl mx-auto px-4 py-8 grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- Album Info & Rating -->
    <div class="md:col-span-2 space-y-6">
      <div class="flex gap-5 items-center bg-gray-800 p-4 rounded-lg border border-gray-700">
        <img src="<?= htmlspecialchars($cover) ?>" class="w-32 h-32 rounded-lg object-cover" />
        <div>
          <h1 class="text-2xl font-bold text-amber-300"><?= htmlspecialchars($albumName) ?></h1>
          <p class="text-gray-400 text-sm mt-1">by <span class="text-white font-semibold"><?= htmlspecialchars($artist) ?></span></p>
          <p class="text-gray-500 text-xs mt-1"><?= count($tracks) ?> track<?= count($tracks) === 1 ? '' : 's' ?></p>
        </div>
      </div>

      <!-- Rating Form -->
      <div class="bg-gray-800 p-4 rounded-lg border border-gray-700">
        <form method="post" action="submit_album_rating.php">
          <input type="hidden" name="album_id" value="<?= htmlspecialchars($albumId) ?>">
          <input type="hidden" name="album_name" value="<?= htmlspecialchars($albumName) ?>">
          <input type="hidden" name="artist_name" value="<?= htmlspecialchars($artist) ?>">
          <input type="hidden" name="rating" id="ratingInput">

          <label class="block text-sm font-medium text-yellow-400 mb-1">Your Rating</label>
          <div id="stars" class="flex gap-2 mb-3">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="fas fa-star star" data-value="<?= $i ?>"></i>
            <?php endfor; ?>
          </div>

          <label for="thoughts" class="text-sm text-gray-300">Thoughts</label>
          <textarea name="thoughts" id="thoughts" rows="3" placeholder="Share your thoughts..."
            class="w-full bg-gray-700 p-2 rounded-md border border-gray-600 text-sm text-white mb-3 resize-none"></textarea>

          <button type="submit" class="w-full bg-green-600 hover:bg-green-700 py-2 rounded-md text-sm font-semibold transition">
            Submit Rating
          </button>
        </form>
      </div>
    </div>

    <!-- Tracklist -->
    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700 max-h-[480px] overflow-y-auto tracklist-scroll">
    <h2 class="text-lg font-semibold text-amber-300 mb-3"><i class="fas fa-music"></i> Tracklist</h2>
    <div class="space-y-2 text-sm">
        <?php foreach ($tracks as $index => $track): ?>
        <div class="flex justify-between items-center bg-gray-700 hover:bg-gray-600 p-2 rounded-md">
            <span class="truncate"><?= $index + 1 ?>. <?= htmlspecialchars($track['name']) ?></span>
            <a href="ratings.php?track_id=<?= urlencode($track['id']) ?>" class="text-green-400 hover:underline">Rate</a>
        </div>
        <?php endforeach; ?>
    </div>
    </div>
  </div>

  <script>
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('ratingInput');
    stars.forEach(star => {
      star.addEventListener('click', () => {
        const rating = parseInt(star.getAttribute('data-value'));
        ratingInput.value = rating;
        stars.forEach(s => {
          s.classList.remove('selected');
          if (parseInt(s.getAttribute('data-value')) <= rating) s.classList.add('selected');
        });
      });
    });
  </script>
</body>
</html>
