<?php

header("Content-Type: text/html; charset=UTF-8");

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "  <meta charset='UTF-8'>";
echo "  <title>PHP on Vercel</title>";
echo "  <style>
            body {
                font-family: Arial, sans-serif;
                background: #0f172a;
                color: #e5e7eb;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                margin: 0;
            }
            .box {
                text-align: center;
                background: #020617;
                padding: 2rem 3rem;
                border-radius: 12px;
            }
        </style>";
echo "</head>";
echo "<body>";
echo "  <div class='box'>";
echo "      <h1>PHP deployed on Vercel</h1>";
echo "      <p>If you see this, it works.</p>";
echo "  </div>";
echo "</body>";
echo "</html>";
