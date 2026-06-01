<?php
// index.php — entry point, redirects to login
// When backend is ready, your partner will add session check here:
// if (isset($_SESSION['user_id'])) { header('Location: /SU-housing/student/dashboard.php'); exit; }

header('Location: /SU-housing/login.php');
exit;
?>