<?php
require_once __DIR__ . '/../../includes/headers.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

if (!$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Password is required.']);
    exit;
}

$db = getDB();

// Admin login — uses email
if (!empty($data['email'])) {
    $email = trim($data['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format.']);
        exit;
    }

    $stmt = $db->prepare(
        'SELECT adminId, fullName, email, passwordHash FROM admins WHERE email = ?'
    );
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['passwordHash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password.']);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['adminId']  = $admin['adminId'];
    $_SESSION['fullName'] = $admin['fullName'];
    $_SESSION['email']    = $admin['email'];
    $_SESSION['role']     = 'admin';

    echo json_encode([
        'message'  => 'Login successful.',
        'adminId'  => $admin['adminId'],
        'fullName' => $admin['fullName'],
        'email'    => $admin['email'],
        'role'     => 'admin'
    ]);

// Student login — uses admission number
} elseif (!empty($data['admissionNumber'])) {
    $admissionNumber = trim($data['admissionNumber']);

    $stmt = $db->prepare(
        'SELECT studentId, fullName, admissionNumber, passwordHash FROM students WHERE admissionNumber = ?'
    );
    $stmt->execute([$admissionNumber]);
    $student = $stmt->fetch();

    if (!$student || !password_verify($password, $student['passwordHash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid admission number or password.']);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['studentId']       = $student['studentId'];
    $_SESSION['fullName']        = $student['fullName'];
    $_SESSION['admissionNumber'] = $student['admissionNumber'];
    $_SESSION['role']            = 'student';

    echo json_encode([
        'message'         => 'Login successful.',
        'studentId'       => $student['studentId'],
        'fullName'        => $student['fullName'],
        'admissionNumber' => $student['admissionNumber'],
        'role'            => 'student'
    ]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Email or admission number is required.']);
}