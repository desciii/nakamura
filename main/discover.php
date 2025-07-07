<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$accessToken = $_SESSION['spotify_access_token'] ?? $_COOKIE['spotify_token'] ?? null;

if (!$accessToken) {
    header("Location: /PHP/Nakamura/nakamura/main/authorize.php");
    exit;
}

if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    $_SESSION['username'] = $_COOKIE['username'];
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get artist IDs from a playlist
function getArtistsFromPlaylist($playlistId, $token) {
    $url = "https://api.spotify.com/v1/playlists/$playlistId/tracks?limit=50";
    $opts = ['http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer $token"
    ]];

    $json = @file_get_contents($url, false, stream_context_create($opts));
    if (!$json) return [];

    $data = json_decode($json, true);
    $artistIds = [];

    foreach ($data['items'] as $item) {
        foreach ($item['track']['artists'] as $artist) {
            $artistIds[] = $artist['id'];
        }
    }
    return array_unique($artistIds);
}

// Get albums by artist
function getAlbumsByArtist($artistId, $token) {
    $url = "https://api.spotify.com/v1/artists/$artistId/albums?include_groups=album&market=PH&limit=5";
    $opts = ['http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer $token"
    ]];
    $json = @file_get_contents($url, false, stream_context_create($opts));
    if (!$json) return [];
    $data = json_decode($json, true);
    return $data['items'] ?? [];
}

// Spotify’s trending playlists per genre
$genrePlaylists = [
    'pop' => '37i9dQZF1DWUa8ZRTfalHk',
    'hip-hop' => '37i9dQZF1DX0XUsuxWHRQd',
    'rnb' => '37i9dQZF1DX4SBhb3fqCJd',
    'k-pop' => '37i9dQZF1DX9tPFwDMOaN1',
    'rock' => '37i9dQZF1DWXRqgorJj26U',
    'country' => '37i9dQZF1DX1lVhptIYRda',
    'indie' => '37i9dQZF1DX2sUQwD7tbmL',
    'dance' => '37i9dQZF1DX4dyzvuaRJ0n',
    'opm' => '37i9dQZF1DWXbttAJcbphz',
];

$genreAlbums = [];

foreach ($genrePlaylists as $genre => $playlistId) {
    $artistIds = getArtistsFromPlaylist($playlistId, $accessToken);
    $albums = [];

    foreach ($artistIds as $artistId) {
        foreach (getAlbumsByArtist($artistId, $accessToken) as $album) {
            $albums[$album['id']] = $album;
        }
    }

    $genreAlbums[$genre] = array_values($albums);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Discover Picks for You</title>
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
        .section-container:hover .nav-button { opacity: 1; pointer-events: auto; }
    </style>
</head>

<body class="bg-[#0A1128] text-white">
<?php include '/xampp/htdocs/PHP/Nakamura/nakamura/views/navigation.php'; ?>

<div class="px-6 py-8">
    <h1 class="text-4xl font-bold mb-6">Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 🎧</h1>

    <h2 class="text-3xl font-bold mb-6">Discover Picks for You 🎶</h2>

    <?php foreach ($genreAlbums as $genre => $albums): ?>
        <?php if (!empty($albums)): ?>
        <section class="mb-12 section-container">
            <h3 class="text-2xl font-semibold mb-4"><?= ucfirst($genre) ?> Albums</h3>

            <div id="<?= $genre ?>-row" class="album-row overflow-x-auto scrollbar-hide w-full">
                <div class="flex flex-nowrap gap-4 w-max px-4">
                    <?php foreach ($albums as $album): ?>
                    <div class="album-card w-[200px] flex-shrink-0 bg-gray-800 rounded-lg p-3 shadow-md cursor-pointer"
                         onclick="window.open('<?= htmlspecialchars($album['external_urls']['spotify']) ?>', '_blank')">
                        <div class="h-[200px] w-full overflow-hidden rounded mb-3">
                            <img src="<?= htmlspecialchars($album['images'][1]['url'] ?? 'https://via.placeholder.com/200x200') ?>"
                                 class="w-full h-full object-cover"
                                 alt="<?= htmlspecialchars($album['name']) ?>" />
                        </div>
                        <p class="font-semibold truncate"><?= htmlspecialchars($album['name']) ?></p>
                        <p class="text-sm text-gray-400 truncate"><?= htmlspecialchars($album['artists'][0]['name']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<script>
function scrollAlbums(id, direction) {
    const container = document.getElementById(id + '-row');
    const scrollAmount = 220 * 3;
    container.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth'
    });
}
</script>
</body>
</html>
