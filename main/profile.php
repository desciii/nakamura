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

$userData = json_decode(file_get_contents('https://api.spotify.com/v1/me', false, stream_context_create([
    'http' => ['header' => "Authorization: Bearer $accessToken"]
])), true);

$spotifyName = $userData['display_name'] ?? 'Spotify User';
$spotifyId = $userData['id'] ?? '';
$spotifyProfile = $userData['external_urls']['spotify'] ?? '#';

// FORCE Spotify profile picture ONLY
$profileImage = $userData['images'][0]['url'] ?? null;

$stmt = $conn->prepare("SELECT track_id, track_name, artist_name, rating, thoughts, created_at FROM ratings WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$ratings = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("SELECT track_id FROM track_likes WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$likesResult = $stmt->get_result();
$likedTrackIds = array_column($likesResult->fetch_all(MYSQLI_ASSOC), 'track_id');
$stmt->close();

$likedTracks = [];
foreach ($likedTrackIds as $trackId) {
    $trackInfo = json_decode(@file_get_contents("https://api.spotify.com/v1/tracks/$trackId", false, stream_context_create([
        'http' => ['header' => "Authorization: Bearer $accessToken"]
    ])), true);

    if (!empty($trackInfo['id'])) {
        $likedTracks[] = [
            'id' => $trackInfo['id'],
            'name' => $trackInfo['name'],
            'artist' => $trackInfo['artists'][0]['name'] ?? 'Unknown',
            'image' => $trackInfo['album']['images'][1]['url'] ?? null
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($spotifyName) ?> — Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="/PHP/Nakamura/nakamura/assets/logo.png" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
  </style>
</head>
<body class="bg-[#0A1128] text-white min-h-screen">
<?php include __DIR__ . '/../views/navigation.php'; ?>

<div class="max-w-7xl mx-auto px-6 py-8">
  <div class="bg-gray-800 rounded-2xl p-8 mb-8 shadow-2xl border border-gray-700">
    <div class="flex flex-col lg:flex-row lg:items-center gap-6">
      <div class="relative">
        <?php if ($profileImage): ?>
            <img src="<?= htmlspecialchars($profileImage) ?>" class="w-32 h-32 rounded-full border-4 border-amber-400 shadow-lg" />
        <?php else: ?>
            <div class="w-32 h-32 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center border-4 border-amber-400 shadow-lg">
                <span class="text-black font-bold text-4xl">
                <?= strtoupper(substr($username, 0, 1)) ?>
                </span>
            </div>
        <?php endif; ?>
        <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-green-500 rounded-full border-4 border-gray-800 flex items-center justify-center">
          <i class="fab fa-spotify text-white text-sm"></i>
        </div>
      </div>

      <div class="flex-1">
        <h1 class="text-4xl font-bold text-transparent bg-gradient-to-r from-amber-400 to-yellow-500 bg-clip-text mb-2">
          @<?= htmlspecialchars($username) ?>
        </h1>
        <div class="space-y-2 mb-4">
          <p class="text-gray-300 flex items-center gap-2">
            <i class="fab fa-spotify text-green-500"></i>
            <span>Spotify Name: <span class="text-white font-semibold"><?= htmlspecialchars($spotifyName) ?></span></span>
          </p>
          <p class="text-gray-300 flex items-center gap-2">
            <i class="fas fa-id-card text-gray-400"></i>
            <span>Spotify ID: <span class="text-white font-mono"><?= htmlspecialchars($spotifyId) ?></span></span>
          </p>
          <a href="<?= htmlspecialchars($spotifyProfile) ?>" target="_blank"
             class="inline-flex items-center gap-2 text-blue-400 hover:text-blue-300 transition-colors duration-200 group">
            <i class="fab fa-spotify"></i>
            <span>View Spotify Profile</span>
            <i class="fas fa-external-link-alt text-sm group-hover:translate-x-1 transition-transform duration-200"></i>
          </a>
        </div>

        <div class="flex items-center gap-4 text-gray-300">
          <div class="flex items-center gap-2">
            <i class="fas fa-star text-yellow-400"></i>
            <span><?= $ratings->num_rows ?> Reviews</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="fas fa-heart text-red-400"></i>
            <span><?= count($likedTracks) ?> Liked</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
    <div>
      <div class="flex items-center gap-3 mb-6">
        <i class="fas fa-star text-2xl text-amber-400"></i>
        <h2 class="text-3xl font-bold text-amber-300">Rated Tracks</h2>
      </div>

      <?php if ($ratings->num_rows === 0): ?>
        <div class="bg-gray-800 rounded-2xl p-8 text-center border border-gray-700">
          <i class="fas fa-star text-6xl text-gray-600 mb-4"></i>
          <p class="text-gray-400 text-lg mb-2">No ratings yet</p>
          <p class="text-gray-500">Start rating tracks to see them here!</p>
        </div>
      <?php else: ?>
        <div class="space-y-4 max-h-96 overflow-y-auto pr-2 scrollbar-hide">
          <?php while ($track = $ratings->fetch_assoc()): ?>
            <div class="bg-gray-800 p-5 rounded-xl shadow-lg border border-gray-700 hover:border-gray-600 transition-all duration-200 hover:shadow-xl">
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <h3 class="text-amber-300 font-bold text-lg mb-1"><?= htmlspecialchars($track['track_name']) ?></h3>
                  <p class="text-gray-400 text-sm"><?= htmlspecialchars($track['artist_name']) ?></p>
                </div>
                <div class="flex items-center gap-2">
                  <div class="text-yellow-400 text-lg">
                    <?= str_repeat('★', $track['rating']) ?>
                    <span class="text-gray-600"><?= str_repeat('★', 5 - $track['rating']) ?></span>
                  </div>
                  <span class="text-gray-400 text-sm bg-gray-700 px-2 py-1 rounded"><?= $track['rating'] ?>/5</span>
                </div>
              </div>

              <blockquote class="text-gray-300 italic mb-3 pl-4 border-l-2 border-amber-400 leading-relaxed">
                "<?= htmlspecialchars($track['thoughts']) ?>"
              </blockquote>

              <div class="flex items-center gap-2 text-gray-500 text-sm">
                <i class="fas fa-clock"></i>
                <span><?= date('F j, Y', strtotime($track['created_at'])) ?></span>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>

    <div>
      <div class="flex items-center gap-3 mb-6">
        <i class="fas fa-heart text-2xl text-red-400"></i>
        <h2 class="text-3xl font-bold text-red-300">Liked Tracks</h2>
      </div>
      
      <?php if (empty($likedTracks)): ?>
        <div class="bg-gradient-to-b from-slate-800 to-slate-900 rounded-2xl p-8 text-center border border-slate-700">
          <i class="fas fa-heart text-6xl text-slate-600 mb-4"></i>
          <p class="text-slate-400 text-lg mb-2">No liked tracks yet</p>
          <p class="text-slate-500">Heart some tracks to see them here!</p>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 max-h-96 overflow-y-auto pr-2">
          <?php foreach ($likedTracks as $track): ?>
            <div class="bg-gradient-to-b from-slate-800 to-slate-900 p-4 rounded-xl shadow-lg border border-slate-700 hover:border-slate-600 transition-all duration-200 hover:shadow-xl group">
              <div class="relative mb-3">
                <img src="<?= htmlspecialchars($track['image']) ?>" 
                     class="w-full aspect-square rounded-lg object-cover group-hover:scale-105 transition-transform duration-200">
                <div class="absolute inset-0 bg-black/20 rounded-lg group-hover:bg-black/10 transition-colors duration-200"></div>
                <div class="absolute top-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center">
                  <i class="fas fa-heart text-white text-xs"></i>
                </div>
              </div>
              
              <h4 class="text-white font-semibold text-sm mb-1 truncate"><?= htmlspecialchars($track['name']) ?></h4>
              <p class="text-slate-400 text-xs truncate mb-2"><?= htmlspecialchars($track['artist']) ?></p>
              
              <a href="ratings.php?track_id=<?= urlencode($track['id']) ?>" 
                 class="inline-flex items-center gap-1 text-xs text-green-400 hover:text-green-300 transition-colors duration-200 group">
                <i class="fas fa-eye"></i>
                <span>View Reviews</span>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>