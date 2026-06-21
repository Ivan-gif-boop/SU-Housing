<?php
require_once __DIR__ . '/../includes/headers.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Destroy the session completely
session_unset();
session_destroy();

echo json_encode(['message' => 'Logged out successfully.']);