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

require_once __DIR__ . '/config.php';
loadEnv(__DIR__ . '/.env');

$apiKey = $_ENV['LASTFM_API_KEY'];

function fetchAlbums($url) {
    $json = @file_get_contents($url);
    if (!$json) {
        error_log("Failed to fetch albums from URL: $url");
        return [];
    }
    $data = json_decode($json, true);
    return $data['albums']['album'] ?? [];
}

function getTopAlbumsByGenre($genre, $apiKey) {
    $url = "http://ws.audioscrobbler.com/2.0/?method=tag.gettopalbums&tag=" . urlencode($genre) . "&api_key={$apiKey}&format=json&limit=50";
    return fetchAlbums($url);
}

$genres = ['pop', 'hip-hop', 'rnb', 'k-pop', 'opm', 'rock', 'jazz', 'electronic', 'metal', 'country', 'classical', 'indie', 'reggae', 'blues', 'dance', 'house', 'techno', 'trap', 'folk', 'alternative'];

$genreAlbums = [];

foreach ($genres as $genre) {
    $genreAlbums[$genre] = getTopAlbumsByGenre($genre, $apiKey);
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
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .album-row { scroll-behavior: smooth; scroll-snap-type: x mandatory; }
        .album-card { scroll-snap-align: start; transition: transform 0.3s ease; }
        .album-card:hover { transform: scale(1.05); z-index: 10; }
        .nav-button { transition: all 0.3s ease; backdrop-filter: blur(10px); opacity: 0; pointer-events: none; }
        .nav-button:hover { background-color: rgba(255, 255, 255, 0.2); transform: scale(1.1); }
        .section-container { position: relative; }
        .section-container:hover .nav-button { opacity: 1; pointer-events: auto; }
        .nav-button.left { left: 10px; }
        .nav-button.right { right: 10px; }
    </style>
</head>

<body class="bg-[#0A1128] text-white">
    <?php include '/xampp/htdocs/PHP/Nakamura/nakamura/views/navigation.php'; ?>

    <div class="p-6">
        <h1 class="text-3xl font-bold mb-6">Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 🎧</h1>
        
        <!-- GENRE ALBUM SECTIONS -->
        <?php foreach ($genreAlbums as $genre => $albums): ?>
            <?php if (!empty($albums)): ?>
                <section class="mb-12 section-container">
                    <h2 class="text-2xl font-semibold mb-4"><?= ucfirst($genre) ?> Albums</h2>

                    <button class="nav-button left absolute top-1/2 transform -translate-y-1/2 z-20 text-white w-12 h-12 rounded-full bg-black bg-opacity-50 hover:bg-opacity-70"
                            onclick="scrollAlbums('<?= $genre ?>', 'left')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="nav-button right absolute top-1/2 transform -translate-y-1/2 z-20 text-white w-12 h-12 rounded-full bg-black bg-opacity-50 hover:bg-opacity-70"
                            onclick="scrollAlbums('<?= $genre ?>', 'right')">
                        <i class="fas fa-chevron-right"></i>
                    </button>

                    <div class="relative">
                        <div id="<?= $genre ?>-row" class="album-row overflow-x-auto scrollbar-hide w-full">
                            <div class="flex flex-nowrap gap-4 w-max px-4">
                                <?php foreach ($albums as $album): ?>
                                    <div class="album-card w-[200px] flex-shrink-0 bg-gray-800 rounded-lg p-3 shadow-md cursor-pointer">
                                        <div class="h-[200px] w-full overflow-hidden rounded mb-3">
                                            <img src="<?= htmlspecialchars($album['image'][2]['#text'] ?? 'https://via.placeholder.com/200x200') ?>"
                                                 class="w-full h-full object-cover"
                                                 alt="<?= htmlspecialchars($album['name']) ?>"
                                                 onerror="this.src='https://via.placeholder.com/200x200?text=<?= urlencode($album['name']) ?>';" />
                                        </div>
                                        <p class="font-semibold truncate"><?= htmlspecialchars($album['name']) ?></p>
                                        <p class="text-sm text-gray-400 truncate"><?= htmlspecialchars($album['artist']['name']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <script>
        function scrollAlbums(id, direction) {
            const container = document.getElementById(id + '-row');
            const scrollAmount = 220;
            const itemsToScroll = 3;

            container.scrollBy({
                left: direction === 'left' ? -scrollAmount * itemsToScroll : scrollAmount * itemsToScroll,
                behavior: 'smooth'
            });
        }

        document.querySelectorAll('.album-row').forEach(row => {
            row.addEventListener('wheel', (e) => {
                e.preventDefault();
                row.scrollBy({
                    left: e.deltaY > 0 ? 220 : -220,
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>