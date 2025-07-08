<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nav-Dev</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      rel="icon"
      href="/Nakamura/nakamura/assets/logo.png"
      type="image/png"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  </head>

  <body class="bg-[#0A1128] text-white">
    <!-- Navbar -->
    <nav class="bg-gray-900 px-4 sm:px-6 py-3 shadow">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <!-- Top Row: Brand + Search (Stacks on mobile) -->
        <div class="flex flex-col sm:flex-row sm:items-center w-full gap-3">
          <!-- Brand -->
          <a href="dashboard.php" class="flex items-center gap-2">
            <img src="/PHP/Nakamura/nakamura/assets/logo.png" alt="Logo" class="h-6 w-6" />
            <span class="text-lg font-bold tracking-wide">Nakamura</span>
          </a>

          <!-- Search -->
          <form action="search.php" method="GET" class="flex items-center bg-gray-800 rounded px-2 py-1 w-full sm:w-80">
            <i class="fa fa-search text-gray-400 ml-1 mr-2"></i>
            <input
              type="text"
              name="q"
              placeholder="Search music"
              class="bg-transparent focus:outline-none text-sm w-full text-white"
            />
          </form>
        </div>

        <!-- Nav Links -->
        <ul class="flex flex-wrap justify-between sm:justify-end gap-3 text-sm items-center">
          <li>
            <a
              href="discover.php"
              class="flex items-center gap-2 text-gray-300 hover:text-yellow-400 hover:bg-gray-800 px-3 py-2 rounded transition-all duration-200"
            >
              <i class="fas fa-microphone"></i> Artists
            </a>
          </li>
          <li>
            <a
              href="albums.php"
              class="flex items-center gap-2 text-gray-300 hover:text-yellow-400 hover:bg-gray-800 px-3 py-2 rounded transition-all duration-200"
            >
              <i class="fa-solid fa-record-vinyl"></i> Albums
            </a>
          </li>
          <li>
            <a
              href="/PHP/Nakamura/nakamura/main/profile.php"
              class="flex items-center gap-2 text-gray-300 hover:text-yellow-400 hover:bg-gray-800 px-3 py-2 rounded transition-all duration-200"
            >
              <i class="fa-solid fa-user"></i> Profile
            </a>
          </li>
          <li>
            <a
              href="/PHP/Nakamura/nakamura/main/logout.php"
              class="flex items-center gap-2 text-yellow-400 hover:text-white hover:bg-gray-800 px-3 py-2 rounded transition-all duration-200"
            >
              <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </nav>
  </body>
</html>
