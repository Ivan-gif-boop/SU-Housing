<?php
// includes/config.php

// ── Base path (Ivan uses this for CSS/JS links) ──
define('BASE_PATH', '/SU-Housing');
define('BASE_URL',  'http://localhost/SU-Housing');

// ── Database constants (using defined() to avoid clashes with config/db.php) ──
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'suhousing');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// ── Google APIs ──
if (!defined('GOOGLE_API_KEY')) define('GOOGLE_API_KEY', '');
