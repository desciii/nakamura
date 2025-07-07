<?php
session_start();

// Try to restore session from cookies
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    $_SESSION['username'] = $_COOKIE['username'] ?? '';
    $_SESSION['spotify_access_token'] = $_COOKIE['spotify_token'] ?? null;
    $_SESSION['spotify_refresh_token'] = $_COOKIE['spotify_refresh_token'] ?? null;
}

// Redirect based on login state
if (isset($_SESSION['user_id']) && isset($_SESSION['spotify_access_token'])) {
    header("Location: dashboard.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
