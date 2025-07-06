<?php
session_start();

if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
  $_SESSION['user_id'] = $_COOKIE['user_id'];
  $_SESSION['username'] = $_COOKIE['username'];
}

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

// Genre name => genre ID (from Deezer)
$genres = [
  'Hip-Hop' => 116,
  'Pop' => 132,
  'Rock' => 152,
  'R&B' => 165,
  'Electronic' => 106
];

// fetch albums by genre
function getAlbumsByGenre($genre_id, $limit = 25) {
  // Get artists in that genre
  $artistRes = file_get_contents("https://api.deezer.com/genre/$genre_id/artists");
  $artistData = json_decode($artistRes, true);

  if (!isset($artistData['data'])) {
    return [];
  }

  $albums = [];

  foreach ($artistData['data'] as $artist) {
    $artistId = $artist['id'];
    $artistName = $artist['name'];

    $albumRes = file_get_contents("https://api.deezer.com/artist/$artistId/albums");
    $albumData = json_decode($albumRes, true);

    if (isset($albumData['data'][0])) {
      $album = $albumData['data'][0];
      $album['artist'] = ['name' => $artistName]; // add artist name manually
      $albums[] = $album;
    }

    if (count($albums) >= $limit) break;
  }

  return $albums;
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
      .scrollbar::-webkit-scrollbar {
        height: 4px;
      }

      .scrollbar::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
      }

      .scrollbar::-webkit-scrollbar-track {
        background: transparent;
      }
    </style>
</head>

<body class="bg-[#0A1128] text-white">
  <?php include '/xampp/htdocs/PHP/Nakamura/nakamura/views/navigation.php'; ?>

  <div class="p-6">
    <h1 class="text-3xl font-bold mb-6">Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 🎧</h1>

    <?php foreach ($genres as $genreName => $genreId): 
      $albums = getAlbumsByGenre($genreId);
      if (empty($albums)) continue;
    ?>
      <section class="mb-10">
        <h2 class="text-xl font-semibold mb-3"><?= $genreName ?> Hits</h2>

        <div class="overflow-x-auto scroll-smooth pb-2 scrollbar">
          <div class="flex gap-5 min-w-full">
            <?php foreach ($albums as $album): ?>
              <div class="w-[220px] flex-shrink-0 bg-gray-800 rounded-lg p-4 shadow-md hover:scale-105 transition-transform duration-200">
                <div class="h-[220px] w-full overflow-hidden rounded-md mb-3">
                  <img src="<?= $album['cover_medium'] ?>" alt="Album Cover" class="w-full h-full object-cover" />
                </div>
                <p class="text-base font-semibold truncate"><?= htmlspecialchars($album['title']) ?></p>
                <p class="text-sm text-gray-400 truncate"><?= htmlspecialchars($album['artist']['name']) ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endforeach; ?>
  </div>
</body>
</html>
