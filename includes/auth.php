<?php
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['studentId']) && empty($_SESSION['adminId'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorised. Please log in.']);
        exit;
    }
}

function requireAdmin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['adminId'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden. Admin access only.']);
        exit;
    }
}

function requireStudent(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['studentId'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden. Student access only.']);
        exit;
    }
}

function currentStudentId(): int {
    return (int)($_SESSION['studentId'] ?? 0);
}

function currentAdminId(): int {
    return (int)($_SESSION['adminId'] ?? 0);
}
