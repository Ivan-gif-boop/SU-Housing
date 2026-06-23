<?php
// includes/config.php

// ── Base path ──
if (!defined('BASE_PATH')) {
  define('BASE_PATH', '/SU-Housing');
}

if (!defined('BASE_URL')) {
  define('BASE_URL', 'http://localhost/SU-Housing');
}

// ── Database ──
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'suhousing');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// ── Google APIs (not used — replaced by Leaflet/OSRM) ──
if (!defined('GOOGLE_API_KEY')) define('GOOGLE_API_KEY', '');