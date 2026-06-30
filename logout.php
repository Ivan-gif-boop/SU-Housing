<?php
// logout.php
session_start();
session_unset();
session_destroy();

header('Location: /SU-Housing/login.php');
exit;
?>