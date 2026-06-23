<?php
// includes/auth_check.php
// Drop this at the top of any protected page.
// Usage:
//   require_once '../includes/auth_check.php';
//   requireAuth();          — any logged-in user
//   requireAuth('admin');   — admin only
//   requireAuth('student'); — student only

function requireAuth(string $role = ''): void {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  if (empty($_SESSION['user_id'])) {
    header('Location: /SU-housing/login.php');
    exit;
  }

  if ($role && $_SESSION['user_role'] !== $role) {
    http_response_code(403);
    // Redirect to their own dashboard instead of showing a raw 403
    $redirect = $_SESSION['user_role'] === 'admin'
      ? '/SU-housing/admin/dashboard.php'
      : '/SU-housing/student/dashboard.php';
    header('Location: ' . $redirect);
    exit;
  }
}