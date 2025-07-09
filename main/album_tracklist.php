<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['spotify_access_token']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$accessToken = $_SESSION['spotify_access_token'];
$username = $_SESSION['username'];
$albumId = $_GET['album_id'] ?? null;

$isLiked = false;

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT 1 FROM album_likes WHERE user_id = ? AND album_id = ?");
    if ($stmt) {
        $stmt->bind_param("is", $_SESSION['user_id'], $albumId);
        $stmt->execute();
        $stmt->store_result();
        $isLiked = $stmt->num_rows > 0;
        $stmt->close();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    if (isset($_POST['like_album'])) {
        $stmt = $conn->prepare("INSERT IGNORE INTO album_likes (user_id, album_id) VALUES (?, ?)"); 
        if ($stmt) {
            $stmt->bind_param("is", $userId, $albumId);
            $stmt->execute();
            $stmt->close();
        }
    }

    if (isset($_POST['unlike_album'])) {
        $stmt = $conn->prepare("DELETE FROM album_likes WHERE user_id = ? AND album_id = ?");
        if ($stmt) {
            $stmt->bind_param("is", $userId, $albumId);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Optional: Redirect to avoid form resubmission
    header("Location: album_tracklist.php?album_id=" . urlencode($albumId));
    exit;
}


if (!$albumId) die("Album ID not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_album']) && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT IGNORE INTO album_likes (user_id, album_id) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("is", $userId, $albumId);
        $stmt->execute();
        $stmt->close();
    }
}

$isLiked = false;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT 1 FROM album_likes WHERE user_id = ? AND album_id = ?");
    if ($stmt) {
        $stmt->bind_param("is", $userId, $albumId);
        $stmt->execute();
        $stmt->store_result();
        $isLiked = $stmt->num_rows > 0;
        $stmt->close();
    }
}

$albumData = json_decode(file_get_contents("https://api.spotify.com/v1/albums/$albumId", false, stream_context_create([
    'http' => ['header' => "Authorization: Bearer $accessToken"]
])), true);

$albumName = $albumData['name'] ?? 'Unknown Album';
$albumImage = $albumData['images'][0]['url'] ?? 'https://via.placeholder.com/300';
$artist = $albumData['artists'][0] ?? ['name' => 'Unknown', 'id' => null];
$artistName = $artist['name'];
$tracks = $albumData['tracks']['items'] ?? [];
$artistId = $artist['id'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($albumName) ?> - Album</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="/PHP/Nakamura/nakamura/assets/logo.png" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-[#0A1128] text-white min-h-screen">
<?php include __DIR__ . '/../views/navigation.php'; ?>

<div class="max-w-6xl mx-auto px-4 py-6">
  <div class="grid md:grid-cols-3 gap-6 mb-10">
    <!-- Album Info & Ratings -->
    <div class="md:col-span-1">
      <img src="<?= htmlspecialchars($albumImage) ?>" class="rounded-lg w-80 h-80 object-cover shadow-md mb-4 mt-10">
      <h1 class="text-xl font-semibold text-amber-300"><?= htmlspecialchars($albumName) ?></h1>
      <p class="text-gray-400 text-sm mb-3">
      by 
      <a href="artists_view.php?artist_id=<?= urlencode($artistId) ?>" 
        class="text-white hover:underline">
        <?= htmlspecialchars($artistName) ?>
      </a>
    </p>
      <a href="rate_album.php?album_id=<?= urlencode($albumId) ?>&name=<?= urlencode($albumName) ?>&artist=<?= urlencode($artistName) ?>"
         class="inline-block bg-green-500 hover:bg-green-600 text-white text-sm font-medium py-2 px-4 rounded transition mb-6">
         Rate This Album
      </a>

    <?php if (!$isLiked): ?>
    <form method="post" class="inline">
        <input type="hidden" name="like_album" value="1">
        <button type="submit"
        class="ml-2 bg-pink-500 hover:bg-pink-600 text-white text-sm font-medium py-2 px-4 rounded transition">
        Like Album
        </button>
    </form>
    <?php else: ?>
    <form method="post" class="inline">
        <input type="hidden" name="unlike_album" value="1">
        <button type="submit"
        class="ml-2 bg-gray-700 hover:bg-gray-600 text-white text-sm font-medium py-2 px-4 rounded transition">
        Unlike
        </button>
    </form>
    <?php endif; ?>


      <!-- Album Ratings -->
      <div class="mt-6">
        <h2 class="text-sm font-semibold text-amber-300 mb-2 flex items-center gap-2">
        Reviews
        </h2>

        <?php
        $stmt = $conn->prepare("SELECT users.username, ar.rating, ar.thoughts, ar.created_at 
                                FROM album_ratings ar
                                JOIN users ON ar.user_id = users.id
                                WHERE ar.album_id = ?
                                ORDER BY ar.created_at DESC");
        $stmt->bind_param("s", $albumId);
        $stmt->execute();
        $reviews = $stmt->get_result();
        ?>

        <?php if ($reviews->num_rows === 0): ?>
          <p class="text-sm text-gray-400 italic">No reviews yet.</p>
        <?php else: ?>
          <div class="space-y-4 max-h-60 overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-transparent">
            <?php while ($review = $reviews->fetch_assoc()): ?>
              <div class="bg-gray-800 p-3 rounded-lg">
                <div class="flex justify-between items-center mb-1">
                  <p class="text-sm text-amber-300 font-semibold truncate">@<?= htmlspecialchars($review['username']) ?></p>
                  <span class="text-yellow-400 text-sm"><?= str_repeat('★', $review['rating']) ?></span>
                </div>
                <p class="text-xs text-white italic mb-1 truncate">"<?= htmlspecialchars($review['thoughts']) ?>"</p>
                <p class="text-[10px] text-gray-500"><?= date('M j, Y', strtotime($review['created_at'])) ?></p>
              </div>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
        <?php $stmt->close(); ?>
      </div>
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
</div>
</body>
</html>
