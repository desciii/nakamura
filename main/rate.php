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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rate Song</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="/PHP/Nakamura/nakamura/assets/logo.png" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    .star {
      font-size: 2rem;
      color: #9CA3AF;
      cursor: pointer;
      transition: color 0.2s;
    }
    .star.selected {
      color: #FFD700;
    }
    .star:hover {
      color: #FBBF24;
    }
  </style>
</head>
<body class="bg-[#0A1128] text-white min-h-screen flex items-center justify-center px-4 py-6">

  <div class="bg-gray-800 p-5 sm:p-6 rounded-xl shadow-lg w-full max-w-sm space-y-5">

    <a href="javascript:history.back()" class="text-sm text-yellow-400 hover:underline flex items-center gap-1 w-fit">
      <i class="fas fa-arrow-left"></i> Back
    </a>

    <div class="space-y-3 text-center">
      <h1 class="text-xl sm:text-2xl font-bold">Rate This Song</h1>

      <img src="<?= htmlspecialchars($albumImage) ?>" alt="Album Cover"
           class="w-28 h-28 mx-auto rounded-md shadow-lg">

      <?php if ($artistImage): ?>
        <img src="<?= htmlspecialchars($artistImage) ?>" alt="Artist"
             class="w-16 h-16 mx-auto rounded-full border-2 border-yellow-400">
      <?php endif; ?>

      <h2 class="text-lg sm:text-xl font-semibold text-yellow-300">
        <?= htmlspecialchars($trackName) ?>
      </h2>
      <p class="text-sm text-gray-400">by <?= htmlspecialchars($artistName) ?></p>
    </div>

    <form method="post" action="submit_rating.php" class="space-y-4">
      <input type="hidden" name="track_id" value="<?= htmlspecialchars($trackId) ?>">
      <input type="hidden" name="track_name" value="<?= htmlspecialchars($trackName) ?>">
      <input type="hidden" name="artist_name" value="<?= htmlspecialchars($artistName) ?>">
      <input type="hidden" name="rating" id="ratingInput">

      <div id="stars" class="flex justify-center gap-2">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <i class="fas fa-star star" data-value="<?= $i ?>"></i>
        <?php endfor; ?>
      </div>

      <div>
        <label for="thoughts" class="block text-sm font-medium text-gray-300 mb-1">Your Thoughts</label>
        <textarea name="thoughts" id="thoughts" rows="4"
          class="w-full p-2 text-sm bg-gray-800 rounded resize-none border border-white"
          placeholder="What did you feel about this track?"></textarea>
      </div>

      <button type="submit"
        class="w-full bg-green-600 hover:bg-green-700 py-2 rounded-md font-semibold text-sm transition">
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
