<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST["username"]);
  $email = trim($_POST["email"]);
  $password = password_hash($_POST["password"], PASSWORD_DEFAULT); // hashed

  $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
  $check->bind_param("s", $username);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
  echo "<script>
          alert('Username already taken!');
          window.history.back(); // send them back to the form
        </script>";
  $check->close();
  $conn->close();
  exit;
}

  $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $username, $email, $password);

  if ($stmt->execute()) {
    header("Location: login.html"); 
    exit;
  } else {
    echo "Registration failed: " . $conn->error;
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
    <title>Register</title>
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
      <a href="login.php" class="text-yellow-400 hover:underline text-lg">
        <i class="fa-solid fa-user"></i>
      </a>
      <h1 class="text-2xl font-bold mb-2 text-center">Nakamura</h1>
      <h2 class="text-sm text-gray-400 mb-4 text-center">
        Register to Continue
      </h2>

      <form action="register.php" method="POST" class="space-y-4">
        <!-- Username -->
        <input
          type="text"
          name="username"
          placeholder="Username"
          required
          class="w-full px-3 py-2 bg-gray-700 text-white rounded text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500"
        />
        <!-- Email -->
        <input
          type="email"
          name="email"
          placeholder="Email"
          required
          class="w-full px-3 py-2 bg-gray-700 text-white rounded text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500"
        />

        <!-- Password -->
        <input
          type="password"
          name="password"
          placeholder="Password"
          required
          class="w-full px-3 py-2 bg-gray-700 text-white rounded text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500"
        />

        <!-- Submit Button -->
        <button
          type="submit"
          class="w-full bg-yellow-400 hover:bg-yellow-500 text-black font-bold py-2 rounded text-sm transition"
        >
          Register
        </button>
      </form>
    </div>
  </body>
</html>
