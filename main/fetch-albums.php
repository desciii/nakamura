<?php
$apiKey = '11927d42c87d3b41b8c6ff4f5ba30fe2';
$currentYear = date('Y');

$genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
if ($genre === '') exit;

function getJSON($url) {
  $json = @file_get_contents($url);
  return $json ? json_decode($json, true) : null;
}

function getTopAlbums($genre, $apiKey) {
  $url = "http://ws.audioscrobbler.com/2.0/?method=tag.gettopalbums&tag=" . urlencode($genre) . "&api_key=$apiKey&format=json&limit=100";
  $data = getJSON($url);
  return $data['albums']['album'] ?? [];
}

function getAlbumReleaseYear($artist, $album, $apiKey) {
  $url = "http://ws.audioscrobbler.com/2.0/?method=album.getInfo&api_key=$apiKey&artist=" . urlencode($artist) . "&album=" . urlencode($album) . "&format=json";
  $info = getJSON($url);
  if (!isset($info['album']['wiki']['published'])) return null;
  $published = strtotime($info['album']['wiki']['published']);
  return $published ? date('Y', $published) : null;
}

function filterAlbumsByYear($albums, $apiKey, $year, $limit = 30) {
  $filtered = [];
  foreach ($albums as $album) {
    $artist = $album['artist']['name'] ?? '';
    $name = $album['name'] ?? '';
    if (!$artist || !$name) continue;

    $releaseYear = getAlbumReleaseYear($artist, $name, $apiKey);
    if ($releaseYear === strval($year)) {
      $filtered[] = $album;
    }

    if (count($filtered) >= $limit) break;
  }
  return $filtered;
}

// Get and filter albums
$rawAlbums = getTopAlbums($genre, $apiKey);
$albums = filterAlbumsByYear($rawAlbums, $apiKey, $currentYear);

// Output album cards
foreach ($albums as $album) {
  $img = $album['image'][2]['#text'] ?? 'https://via.placeholder.com/200';
  $title = $album['name'] ?? 'Untitled';
  $artist = $album['artist']['name'] ?? 'Unknown Artist';

  echo <<<HTML
    <div class="w-[200px] flex-shrink-0 bg-gray-800 rounded-lg p-3 shadow-md hover:scale-105 transition-transform duration-200">
      <div class="h-[200px] w-full overflow-hidden rounded mb-3">
        <img src="{$img}" class="w-full h-full object-cover" onerror="this.src='https://via.placeholder.com/200x200/1f2937/ffffff?text=No+Image'" />
      </div>
      <p class="font-semibold truncate">{$title}</p>
      <p class="text-sm text-gray-400 truncate">{$artist}</p>
    </div>
  HTML;
}
