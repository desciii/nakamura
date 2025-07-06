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
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link 
      rel="icon" 
      href="/PHP/Nakamura/nakamura/assets/logo.png" 
      type="image/png" 
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />  
  </head>

  <body class="bg-[#0A1128] text-white">
    <?php include '/xampp/htdocs/PHP/Nakamura/nakamura/views/navigation.php'; ?>

    <!-- main -->
    <div class="p-6">
      <h1 class="text-2xl font-bold">Welcome to Nakamura</h1>
      <p class="mt-2 text-gray-300">yo..... gurt.</p>
    </div>
  </body>
</html>
