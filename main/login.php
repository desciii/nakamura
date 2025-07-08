<?php
session_start();
include('db.php');

// If already logged in, redirect to dashboard
if (isset($_SESSION['username']) || isset($_COOKIE['username'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="/PHP/Nakamura/nakamura/assets/logo.png" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="bg-[#0A1128] flex items-center justify-center min-h-screen text-white font-sans">
  <div class="bg-gray-900 shadow-xl rounded-2xl p-8 w-full max-w-sm">
    <div class="flex flex-col items-center space-y-4">
      <img src="/PHP/Nakamura/nakamura/assets/logo.png" alt="Nakamura Logo" class="h-12 w-12 rounded-full">
      <h1 class="text-2xl font-bold text-yellow-400">Welcome to Nakamura</h1>
      <p class="text-sm text-gray-400">Login using your Spotify account</p>
      <a
        href="authorize.php"
        class="flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded-md transition w-full text-sm"
      >
        <i class="fab fa-spotify text-lg"></i> Continue with Spotify
      </a>
    </div>
  </div>
</body>
</html>
