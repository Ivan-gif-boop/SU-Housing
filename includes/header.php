<?php
// includes/header.php
$pageTitle = $pageTitle ?? 'StrathHousing';
require_once $_SERVER['DOCUMENT_ROOT'] . '/SU-housing/includes/config.php';
$pageTitle = $pageTitle ?? 'StrathHousing';
// Base path — update this if your folder name ever changes
define('BASE_PATH', '/SU-housing');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle); ?> — StrathHousing</title>

  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/variables.css"/>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/base.css"/>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/components.css"/>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/layout.css"/>
</head>
<body>