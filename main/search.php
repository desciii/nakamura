<?php
session_start();
if (!isset($_SESSION['spotify_access_token'])) {
    header("Location: login.php");
    exit;
}

$accessToken = $_SESSION['spotify_access_token'];
$query = $_GET['q'] ?? '';
$results = [];

if ($query) {
    $url = "https://api.spotify.com/v1/search?q=" . urlencode($query) . "&type=track&limit=10";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"]
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $results = $data['tracks']['items'] ?? [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Search Results - <?= htmlspecialchars($query) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-[#0A1128] text-white">
<?php include __DIR__ . '/../views/navigation.php'; ?>

<div class="p-6">
  <h1 class="text-2xl font-bold mb-4">Search Results for “<?= htmlspecialchars($query) ?>”</h1>

  <?php if (empty($results)): ?>
    <p class="text-gray-400">No results found.</p>
  <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-x-4 gap-y-6">
        <?php foreach ($results as $track): ?>
            <?php
            $trackId = $track['id'];
            $name = $track['name'];
            $artist = $track['artists'][0]['name'] ?? 'Unknown';
            $image = $track['album']['images'][1]['url'] ?? 'https://via.placeholder.com/200';
            ?>
            <div class="bg-gray-800 rounded-lg shadow p-4 text-center w-56 mx-auto">
            <img src="<?= htmlspecialchars($image) ?>" class="w-32 h-32 mx-auto object-cover rounded mb-3 shadow">
            <h2 class="text-base font-semibold truncate"><?= htmlspecialchars($name) ?></h2>
            <p class="text-sm text-gray-400 truncate"><?= htmlspecialchars($artist) ?></p>
            <a href="ratings.php?track_id=<?= urlencode($trackId) ?>"
                class="text-sm bg-green-500 hover:bg-green-600 text-white px-3 py-1 mt-3 rounded inline-block">
                Rate →
            </a>
            </div>
        <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
