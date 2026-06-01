<?php
// includes/header.php
// Usage: include this at the top of every page.
// $pageTitle should be set before including, e.g. $pageTitle = "Dashboard";

$pageTitle = $pageTitle ?? 'StrathHousing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle); ?> — StrathHousing</title>

  <!-- CSS load order matters: variables first, then base, then components, then layout -->
  <link rel="stylesheet" href="/strathhousing/assets/css/variables.css"/>
  <link rel="stylesheet" href="/strathhousing/assets/css/base.css"/>
  <link rel="stylesheet" href="/strathhousing/assets/css/components.css"/>
  <link rel="stylesheet" href="/strathhousing/assets/css/layout.css"/>
</head>
<body>