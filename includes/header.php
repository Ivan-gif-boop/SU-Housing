<?php
// If config exists use it, otherwise fall back to hardcoded path
// This lets your pages work even before Michelle sets up config.php
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/SU-housing/includes/config.php')) {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/SU-housing/includes/config.php';
}

if (!defined('BASE_PATH')) {
  define('BASE_PATH', '/SU-housing');
}

$pageTitle = $pageTitle ?? 'SU-Housing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle); ?> — SU-Housing</title>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/variables.css"/>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/base.css"/>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/components.css"/>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/layout.css"/>
</head>
<body>