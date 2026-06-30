<?php
// includes/header.php

if (!defined('BASE_PATH')) {
  define('BASE_PATH', '/SU-Housing');
}

if (!defined('BASE_URL')) {
  define('BASE_URL', 'http://localhost/SU-Housing');
}

$pageTitle = $pageTitle ?? 'SU-Housing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle); ?> SU-Housing</title>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/variables.css"/>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/base.css"/>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/components.css"/>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/layout.css"/>
  <?php if (!empty($usesMap)): ?>
    <link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <?php endif; ?>
</head>
<body>