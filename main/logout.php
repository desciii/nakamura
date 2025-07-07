<?php
session_start();

// Clear all session data
$_SESSION = [];
session_unset();
session_destroy();

// Expire access token cookie
setcookie('spotify_token', '', time() - 3600, '/');
setcookie('user_id', '', time() - 3600, '/');
setcookie('username', '', time() - 3600, '/');

header("Location: login.php");
exit;
