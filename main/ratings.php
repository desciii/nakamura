<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['spotify_access_token'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';
$accessToken = $_SESSION['spotify_access_token'];
$username = $_SESSION['username'];

$trackId = $_GET['track_id'] ?? null;
if (!$trackId) {
    die("Track not found.");
}

$trackData = json_decode(file_get_contents("https://api.spotify.com/v1/tracks/$trackId", false, stream_context_create([
    'http' => [
        'header' => "Authorization: Bearer $accessToken"
    ]
])), true);

$trackName = $trackData['name'] ?? 'Unknown';
$albumImage = $trackData['album']['images'][0]['url'] ?? 'https://via.placeholder.com/300';
$artist = $trackData['artists'][0] ?? ['name' => 'Unknown', 'id' => null];
$artistName = $artist['name'];
$artistId = $artist['id'];

$artistData = json_decode(file_get_contents("https://api.spotify.com/v1/artists/$artistId", false, stream_context_create([
    'http' => [
        'header' => "Authorization: Bearer $accessToken"
    ]
])), true);

// Like / Unlike handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_toggle'])) {
    $checkLike = $conn->prepare("SELECT id FROM track_likes WHERE user_id = ? AND track_id = ?");
    $checkLike->bind_param("is", $_SESSION['user_id'], $trackId);
    $checkLike->execute();
    $checkLike->store_result();

    if ($checkLike->num_rows > 0) {
        // Unlike
        $del = $conn->prepare("DELETE FROM track_likes WHERE user_id = ? AND track_id = ?");
        $del->bind_param("is", $_SESSION['user_id'], $trackId);
        $del->execute();
        $del->close();
    } else {
        // Like
        $ins = $conn->prepare("INSERT INTO track_likes (user_id, track_id) VALUES (?, ?)");
        $ins->bind_param("is", $_SESSION['user_id'], $trackId);
        $ins->execute();
        $ins->close();
    }

    $checkLike->close();
    header("Location: ratings.php?track_id=" . urlencode($trackId));
    exit;
}

// Count total likes and check if this user liked the track
$likesResult = $conn->prepare("SELECT COUNT(*) as total FROM track_likes WHERE track_id = ?");
$likesResult->bind_param("s", $trackId);
$likesResult->execute();
$likesCount = $likesResult->get_result()->fetch_assoc()['total'] ?? 0;
$likesResult->close();

$userLiked = false;
$checkLiked = $conn->prepare("SELECT 1 FROM track_likes WHERE user_id = ? AND track_id = ?");
$checkLiked->bind_param("is", $_SESSION['user_id'], $trackId);
$checkLiked->execute();
$checkLiked->store_result();
$userLiked = $checkLiked->num_rows > 0;
$checkLiked->close();

$artistImage = $artistData['images'][0]['url'] ?? null;
$artistGenres = $artistData['genres'] ?? [];
$artistFollowers = number_format($artistData['followers']['total'] ?? 0);

$stmt = $conn->prepare("SELECT users.username, ratings.rating, ratings.thoughts, ratings.created_at FROM ratings JOIN users ON ratings.user_id = users.id WHERE ratings.track_id = ? ORDER BY ratings.created_at DESC");
$stmt->bind_param("s", $trackId);
$stmt->execute();
$reviews = $stmt->get_result();
$stmt->close();


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($trackName) ?> - Reviews</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="/PHP/Nakamura/nakamura/assets/logo.png" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-[#0A1128] min-h-screen text-white">
  <?php include __DIR__ . '/../views/navigation.php'; ?>
  <div class="max-w-6xl mx-auto px-4 py-6">
  <div class="grid md:grid-cols-3 gap-6">
    <!-- Track Sidebar -->
    <div class="bg-gray-800 p-4 rounded-lg shadow md:col-span-1">
      <img src="<?= htmlspecialchars($albumImage) ?>" class="w-full rounded-md mb-4">
      <h1 class="text-xl font-semibold text-amber-300"><?= htmlspecialchars($trackName) ?></h1>
      <p class="text-sm text-slate-400 mb-4">by <span class="text-white font-medium"><?= htmlspecialchars($artistName) ?></span></p>

      <?php if ($artistImage): ?>
      <div class="flex items-center gap-3 mb-4">
        <img src="<?= htmlspecialchars($artistImage) ?>" class="w-12 h-12 rounded-full border border-slate-600">
        <div>
          <p class="text-xs text-slate-400">Artist</p>
          <p class="text-sm font-medium text-white"><?= htmlspecialchars($artistName) ?></p>
        </div>
      </div>
      <?php endif; ?>

      <div class="text-sm space-y-2 mb-4">
        <div class="flex items-center gap-2 text-slate-300">
          <i class="fas fa-users text-xs"></i>
          <span><?= $artistFollowers ?> followers</span>
        </div>
        <?php if (!empty($artistGenres)): ?>
        <div>
          <p class="text-xs text-slate-400 mb-1">Genres:</p>
          <div class="flex flex-wrap gap-1">
            <?php foreach ($artistGenres as $genre): ?>
              <span class="bg-slate-700 text-xs text-slate-300 px-2 py-1 rounded-full"><?= htmlspecialchars($genre) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Like Button -->
      <form method="post" class="mb-4">
        <input type="hidden" name="like_toggle" value="1">
        <button type="submit"
          class="w-full py-2 text-sm rounded bg-gray-700 hover:bg-pink-500 transition font-semibold <?= $userLiked ? 'bg-pink-500' : '' ?>">
          <i class="fa<?= $userLiked ? 's' : 'r' ?> fa-heart mr-2"></i>
          <?= $userLiked ? 'Liked' : 'Like this track' ?> (<?= $likesCount ?>)
        </button>
      </form>

      <!-- Rate Button -->
      <a href="rate.php?track_id=<?= urlencode($trackId) ?>&name=<?= urlencode($trackName) ?>&artist=<?= urlencode($artistName) ?>"
         class="block w-full text-center text-sm py-2 rounded bg-green-500 hover:bg-green-600 transition font-semibold">
         <i class="fas fa-star mr-1"></i> Rate This Track
      </a>
    </div>

    <!-- Reviews Section -->
    <div class="md:col-span-2">
      <div class="bg-gray-800 p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold text-amber-300 mb-4 flex items-center gap-2">
          <i class="fas fa-comments text-base"></i> User Reviews
        </h2>

        <?php if ($reviews->num_rows === 0): ?>
        <div class="text-center text-slate-400 py-10">
          <i class="fas fa-star text-4xl text-slate-600 mb-2"></i>
          <p class="mb-1">No reviews yet</p>
          <p class="text-sm">Be the first to rate this track!</p>
        </div>
        <?php else: ?>
        <div class="space-y-3">
          <?php while ($review = $reviews->fetch_assoc()): ?>
          <div class="bg-gray-700 rounded-md p-4">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-amber-500 text-black font-bold text-xs flex items-center justify-center">
                  <?= strtoupper(substr($review['username'], 0, 1)) ?>
                </div>
                <p class="text-sm font-medium text-white">@<?= htmlspecialchars($review['username']) ?></p>
              </div>
              <div class="text-yellow-400 text-sm font-semibold">
                <?= str_repeat('★', $review['rating']) ?><span class="text-slate-600"><?= str_repeat('★', 5 - $review['rating']) ?></span>
              </div>
            </div>
            <p class="text-sm italic text-white mb-2">"<?= htmlspecialchars($review['thoughts']) ?>"</p>
            <p class="text-xs text-slate-400"><i class="fas fa-clock mr-1"></i><?= date('M j, Y \a\t g:i A', strtotime($review['created_at'])) ?></p>
          </div>
          <?php endwhile; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>