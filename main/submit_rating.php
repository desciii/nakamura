<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$trackId = $_POST['track_id'] ?? null;
$trackName = $_POST['track_name'] ?? '';
$artistName = $_POST['artist_name'] ?? '';
$rating = intval($_POST['rating']);
$thoughts = trim($_POST['thoughts'] ?? '');

// Basic validation
if (!$trackId || $rating < 1 || $rating > 5) {
    die("Invalid input.");
}

// Check if already rated
$check = $conn->prepare("SELECT id FROM ratings WHERE user_id = ? AND track_id = ?");
$check->bind_param("is", $userId, $trackId);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // Update existing
    $update = $conn->prepare("UPDATE ratings SET rating = ?, thoughts = ?, created_at = NOW() WHERE user_id = ? AND track_id = ?");
    $update->bind_param("ssis", $rating, $thoughts, $userId, $trackId);
    $update->execute();
    $update->close();
} else {
    // Insert new
    $insert = $conn->prepare("INSERT INTO ratings (user_id, track_id, track_name, artist_name, rating, thoughts) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->bind_param("isssis", $userId, $trackId, $trackName, $artistName, $rating, $thoughts);
    $insert->execute();
    $insert->close();
}

$check->close();
$conn->close();

header("Location: ratings.php?track_id=" . urlencode($trackId));
exit;
