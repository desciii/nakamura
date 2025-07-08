<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$albumId = $_POST['album_id'] ?? null;
$albumName = $_POST['album_name'] ?? '';
$artistName = $_POST['artist_name'] ?? '';
$rating = $_POST['rating'] ?? 0;
$thoughts = trim($_POST['thoughts'] ?? '');

if (!$albumId || $rating < 1 || $rating > 5) {
    die("Invalid rating or album data.");
}

// Optional: Prevent duplicate rating from the same user
$check = $conn->prepare("SELECT id FROM album_ratings WHERE user_id = ? AND album_id = ?");
$check->bind_param("is", $userId, $albumId);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // If you want to update the existing review instead of inserting a new one:
    $update = $conn->prepare("UPDATE album_ratings SET rating = ?, thoughts = ?, created_at = NOW() WHERE user_id = ? AND album_id = ?");
    $update->bind_param("isis", $rating, $thoughts, $userId, $albumId);
    $update->execute();
    $update->close();
} else {
    $stmt = $conn->prepare("INSERT INTO album_ratings (user_id, album_id, album_name, artist_name, rating, thoughts) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssis", $userId, $albumId, $albumName, $artistName, $rating, $thoughts);
    $stmt->execute();
    $stmt->close();
}

$check->close();

// Redirect back to album page
header("Location: rate_album.php?album_id=" . urlencode($albumId));
exit;
