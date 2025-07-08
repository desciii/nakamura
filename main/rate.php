<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['spotify_access_token'])) {
    header("Location: login.php");
    exit;
}

$accessToken = $_SESSION['spotify_access_token'];
$trackId = $_GET['track_id'] ?? null;

if (!$trackId) {
    die("Invalid track.");
}

// Fetch track details from Spotify
$trackUrl = "https://api.spotify.com/v1/tracks/$trackId";
$ch = curl_init($trackUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"]
]);
$response = curl_exec($ch);
curl_close($ch);

$track = json_decode($response, true);
$trackName = $track['name'] ?? 'Unknown';
$artist = $track['artists'][0] ?? ['name' => 'Unknown', 'id' => null];
$artistName = $artist['name'];
$artistId = $artist['id'] ?? null;
$albumImage = $track['album']['images'][0]['url'] ?? 'https://via.placeholder.com/300';

// Fetch artist image
$artistImage = null;
if ($artistId) {
    $artistUrl = "https://api.spotify.com/v1/artists/$artistId";
    $ch = curl_init($artistUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"]
    ]);
    $artistResponse = curl_exec($ch);
    curl_close($ch);

    $artistData = json_decode($artistResponse, true);
    $artistImage = $artistData['images'][0]['url'] ?? null;
}

// Try to get lyrics from Lyrics.ovh
$lyrics = null;
$lyricsUrl = "https://api.lyrics.ovh/v1/" . urlencode($artistName) . "/" . urlencode($trackName);
$lyricsJson = @file_get_contents($lyricsUrl);
if ($lyricsJson) {
    $lyricsData = json_decode($lyricsJson, true);
    $lyrics = $lyricsData['lyrics'] ?? null;
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Rate Song</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="/PHP/Nakamura/nakamura/assets/logo.png" type="image/png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    .star {
      font-size: 2rem;
      color: #ccc;
      cursor: pointer;
    }
    .star.selected {
      color: #FFD700;
    }
  </style>
</head>
<body class="bg-[#0A1128] text-white min-h-screen flex flex-col items-center justify-center p-4">
  <div class="bg-gray-800 p-6 rounded-xl shadow-lg text-center max-w-md w-full">
    <a href="dashboard.php" class="flex items-center gap-2 text-sm text-yellow-400 hover:underline mt-2 w-fit">
    <i class="fas fa-arrow-left"></i>
    </a>
    <h1 class="text-2xl font-bold mb-4">Rate This Song</h1>

    <img src="<?= htmlspecialchars($albumImage) ?>" alt="Album Cover" class="w-32 h-32 mx-auto rounded mb-4 shadow-lg">
    
    <?php if ($artistImage): ?>
      <img src="<?= htmlspecialchars($artistImage) ?>" alt="Artist" class="w-20 h-20 rounded-full mx-auto mb-2 border-2 border-yellow-400">
    <?php endif; ?>

    <h2 class="text-yellow-300 text-xl font-semibold mb-1"><?= htmlspecialchars($trackName) ?></h2>
    <p class="text-gray-400 mb-4">by <?= htmlspecialchars($artistName) ?></p>

    <form method="post" action="submit_rating.php">
      <input type="hidden" name="track_id" value="<?= htmlspecialchars($trackId) ?>">
      <input type="hidden" name="track_name" value="<?= htmlspecialchars($trackName) ?>">
      <input type="hidden" name="artist_name" value="<?= htmlspecialchars($artistName) ?>">
      <input type="hidden" name="rating" id="ratingInput">

      <div id="stars" class="flex justify-center mb-4">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <i class="fas fa-star star" data-value="<?= $i ?>"></i>
        <?php endfor; ?>
      </div>

      <label for="thoughts" class="block text-left mb-1 font-medium">Your Thoughts:</label>
      <textarea name="thoughts" id="thoughts" rows="4"
        class="w-full p-2 rounded bg-gray-700 text-white mb-4"
        placeholder="Write what you felt about this track..."></textarea>

      <button type="submit" class="w-full bg-green-500 hover:bg-green-600 py-2 rounded font-semibold mb-3">
        Submit
      </button>
    </form>
  </div>

  <script>
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('ratingInput');

    stars.forEach(star => {
      star.addEventListener('click', () => {
        const rating = star.getAttribute('data-value');
        ratingInput.value = rating;

        stars.forEach(s => {
          s.classList.remove('selected');
          if (s.getAttribute('data-value') <= rating) {
            s.classList.add('selected');
          }
        });
      });
    });
  </script>
</body>
</html>
