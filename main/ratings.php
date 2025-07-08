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
  <div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-1">
        <div class="bg-gray-800 rounded-2xl p-6 shadow-2xl border border-gray-700">
          <div class="relative mb-6">
            <img src="<?= htmlspecialchars($albumImage) ?>" class="w-full rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300">
            <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent rounded-xl"></div>
          </div>
          
          <h1 class="text-3xl font-bold text-amber-300 mb-3 leading-tight"><?= htmlspecialchars($trackName) ?></h1>
          <p class="text-slate-300 mb-6 text-lg">by <span class="font-semibold text-white"><?= htmlspecialchars($artistName) ?></span></p>
          
          <?php if ($artistImage): ?>
            <div class="flex items-center gap-4 mb-6">
              <img src="<?= htmlspecialchars($artistImage) ?>" class="w-16 h-16 rounded-full shadow-lg border-2 border-slate-600">
              <div>
                <p class="text-slate-400 text-sm font-medium">Artist</p>
                <p class="text-white font-semibold"><?= htmlspecialchars($artistName) ?></p>
              </div>
            </div>
          <?php endif; ?>
          
          <div class="space-y-3 mb-6">
            <div class="flex items-center gap-2">
              <i class="fas fa-users text-slate-400"></i>
              <span class="text-slate-300 text-sm">Followers:</span>
              <span class="text-white font-semibold"><?= $artistFollowers ?></span>
            </div>
            <div class="flex items-start gap-2">
              <i class="fas fa-music text-slate-400 mt-1"></i>
              <div>
                <span class="text-slate-300 text-sm">Genres:</span>
                <div class="flex flex-wrap gap-1 mt-1">
                  <?php foreach ($artistGenres as $genre): ?>
                    <span class="bg-slate-700 text-slate-300 px-2 py-1 rounded-full text-xs"><?= htmlspecialchars($genre) ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
                <form method="post" class="mt-6">
                    <input type="hidden" name="like_toggle" value="1">
                    <button type="submit"
                        class="w-full py-2 px-4 rounded-xl shadow-lg text-white font-semibold transition-all duration-300
                        <?= $userLiked ? 'bg-pink-500 hover:bg-pink-600' : 'bg-gray-700 hover:bg-pink-500' ?>">
                        <i class="fa<?= $userLiked ? 's' : 'r' ?> fa-heart mr-2"></i>
                        <?= $userLiked ? 'Liked' : 'Like this track' ?> (<?= $likesCount ?>)
                    </button>
                </form>
          </div>
          <a href="rate.php?track_id=<?= urlencode($trackId) ?>&name=<?= urlencode($trackName) ?>&artist=<?= urlencode($artistName) ?>" 
             class="block w-full text-center bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 
                    text-white font-semibold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl 
                    transition-all duration-300 transform hover:scale-105">
            <i class="fas fa-star mr-2"></i>Rate This Track
          </a>
        </div>
      </div>

      <div class="lg:col-span-2">
        <div class="bg-gray-800 rounded-2xl p-6 shadow-2xl border border-gray-700">
          <h2 class="text-2xl font-bold text-amber-300 mb-6 flex items-center gap-2">
            <i class="fas fa-comments"></i>
            User Reviews
          </h2>
          
          <?php if ($reviews->num_rows === 0): ?>
            <div class="text-center py-12">
              <i class="fas fa-star text-6xl text-slate-600 mb-4"></i>
              <p class="text-slate-400 text-lg mb-2">No reviews yet</p>
              <p class="text-slate-500">Be the first to rate this track!</p>
            </div>
          <?php else: ?>
            <div class="space-y-4">
              <?php while ($review = $reviews->fetch_assoc()): ?>
                <div class="bg-gray-700/60 backdrop-blur-sm border border-gray-600 rounded-xl p-5 hover:bg-gray-700 transition-colors duration-200">
                  <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center">
                        <span class="text-black font-bold text-sm"><?= strtoupper(substr($review['username'], 0, 1)) ?></span>
                      </div>
                      <p class="text-amber-300 font-semibold">@<?= htmlspecialchars($review['username']) ?></p>
                    </div>
                    <div class="flex items-center gap-2">
                      <div class="text-yellow-400 text-lg">
                        <?= str_repeat('★', $review['rating']) ?>
                        <span class="text-slate-600"><?= str_repeat('★', 5 - $review['rating']) ?></span>
                      </div>
                      <span class="text-slate-400 text-sm"><?= $review['rating'] ?>/5</span>
                    </div>
                  </div>
                  
                  <blockquote class="text-white italic mb-3 pl-4 border-l-2 border-amber-400 text-lg leading-relaxed">
                    "<?= htmlspecialchars($review['thoughts']) ?>"
                  </blockquote>
                  
                  <div class="flex items-center gap-2 text-slate-500 text-sm">
                    <i class="fas fa-clock"></i>
                    <span><?= date('F j, Y \a\t g:i A', strtotime($review['created_at'])) ?></span>
                  </div>
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