<?php
session_start();
session_unset();
session_destroy();

// kill cookies
setcookie("user_id", "", time() - 3600, "/");
setcookie("username", "", time() - 3600, "/");

header("Location: login.php");
exit;
?>
