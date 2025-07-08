<?php
session_start();
$_SESSION = [];
session_destroy();

setcookie('username', '', time() - 3600, '/');
setcookie('user_id', '', time() - 3600, '/');
setcookie('spotify_token', '', time() - 3600, '/');

header("Location: login.php");
exit;
