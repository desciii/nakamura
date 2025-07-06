<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST["username"]);
  $password = $_POST["password"];

  // find user
  $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
      // login success
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['username'] = $user['username'];
      header("Location: dashboard.php"); 
      exit;
    } else {
      echo "<script>
          alert('Incorrect Password!');
          window.history.back(); // send them back to the form
        </script>";
    }
  } else {
    echo "<script>
          alert('User not Found!');
          window.history.back(); // send them back to the form
        </script>";
  }

  $stmt->close();
  $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
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

  <body
    class="bg-[#0A1128] text-white flex items-center justify-center min-h-screen"
  >
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-xs">
      <div class="flex justify-center mb-2">
        <i class="fa-solid fa-lock text-yellow-400 text-xl"></i>
      </div>
      <h1 class="text-2xl font-bold mb-2 text-center">Nakamura</h1>
      <h2 class="text-sm text-gray-400 mb-4 text-center">Login to continue</h2>

      <form action="login.php" method="POST" class="space-y-4">
        <input
          type="text"
          name="username"
          placeholder="Username"
          required
          class="w-full px-3 py-2 bg-gray-700 text-white rounded text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500"
        />
        <input
          type="password"
          name="password"
          placeholder="Password"
          required
          class="w-full px-3 py-2 bg-gray-700 text-white rounded text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500"
        />

        <label class="flex items-center gap-2 text-sm text-gray-300">
          <input
            type="checkbox"
            name="remember"
            class="accent-yellow-400 w-4 h-4 rounded focus:ring-2 focus:ring-yellow-500"
          />
          Remember Me
        </label>

        <button
          type="submit"
          class="w-full bg-yellow-400 hover:bg-yellow-500 text-black font-bold py-2 rounded text-sm transition"
        >
          Login
        </button>
      </form>

      <p class="text-center text-gray-400 text-sm mt-4">or</p>

      <a
        href="register.php"
        class="block text-center mt-2 text-yellow-400 hover:underline text-sm"
      >
        Register
      </a>
    </div>
  </body>
</html>
