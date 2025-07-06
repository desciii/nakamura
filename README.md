# Nakamura

A Side Project named Nakamura - In Production

# Nakamura

A web-based music dashboard that fetches real-time album data from LastFM using their API. Users can search albums, view detailed info like cover art, artists, and producers. And overall rate Music and Albums. A fun side project to learn API integration and futher enhance Tailwind CSS knowledge.

## Features - In Production

- User Authentication (Login & Register)
- Dark Themed Dashboard
- Responsive Navigation Bar with Font Awesome Icons
- Album Search via Spotify API
- Album Info Display (Cover, Artist, Release Date, etc.)
- Custom Tailwind UI Styling
- Logout & Session Handling
- Reusable PHP components (e.g., navbar)
- Clean project file structure

## Technologies Used

- **Frontend:**

  - HTML5 / Tailwind CSS
  - Font Awesome
  - Google Fonts (VT323, Inter)

- **Backend:**

  - PHP (Vanilla PHP)
  - Spotify Web API (for album data)
  - SQLite or MySQL not yet sure

- **Other:**
  - XAMPP for local development
  - Session-based login system
  - REST API integration with cURL

## Setup Instructions

1. **Clone or Download this repository** into your XAMPP `htdocs` directory.

2. **Spotify API Setup:**

   - Go to [Spotify Developer Dashboard](https://developer.spotify.com/dashboard)
   - Create an App and get your **Client ID** and **Client Secret**
   - Store them securely in `spotify-config.php`

3. **Update Config Files:**

   - Make sure your database and API keys are set up correctly.

4. **Run the Project:**
   - Start Apache via XAMPP.
   - Visit `http://localhost/Nakamura/login.php`

## Credits

Developed by **@desciii**

---
