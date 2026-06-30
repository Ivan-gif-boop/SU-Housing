<?php
// includes/auth.php

// Detect whether this request is hitting an API endpoint (under /api/)
// or a regular HTML page. API requests get JSON error responses;
// page requests get redirected to login instead of showing raw JSON.
function isApiRequest(): bool {
    return strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false;
}

function denyAccess(int $statusCode, string $message): void {
    if (isApiRequest()) {
        http_response_code($statusCode);
        echo json_encode(['error' => $message]);
        exit;
    }

    // Page request — redirect instead of showing raw JSON
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['studentId']) && empty($_SESSION['adminId'])) {
        // Not logged in at all
        header('Location: /SU-Housing/login.php');
        exit;
    }

    // Logged in, but wrong role — send them to their own dashboard
    $redirect = !empty($_SESSION['adminId'])
        ? '/SU-Housing/admin/dashboard.php'
        : '/SU-Housing/student/dashboard.php';
    header('Location: ' . $redirect);
    exit;
}

function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['studentId']) && empty($_SESSION['adminId'])) {
        denyAccess(401, 'Unauthorised. Please log in.');
    }
}

function requireAdmin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['adminId'])) {
        denyAccess(403, 'Forbidden. Admin access only.');
    }
}

function requireStudent(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['studentId'])) {
        denyAccess(403, 'Forbidden. Student access only.');
    }
}

function currentStudentId(): int {
    return (int) ($_SESSION['studentId'] ?? 0);
}

function currentAdminId(): int {
    return (int) ($_SESSION['adminId'] ?? 0);
}