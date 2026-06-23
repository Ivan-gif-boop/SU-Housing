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

$data = json_decode(file_get_contents('php://input'), true);

$fullName        = trim($data['fullName'] ?? '');
$admissionNumber = trim($data['admissionNumber'] ?? '');
$programme       = trim($data['programme'] ?? '');
$password        = $data['password'] ?? '';
$confirmPassword = $data['confirmPassword'] ?? '';

if (!$fullName || !$admissionNumber || !$password || !$confirmPassword) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required.']);
    exit;
}

if (!preg_match('/^\d{5,8}$/', $admissionNumber)) {
    http_response_code(400);
    echo json_encode(['error' => 'Admission number must be 5 to 8 digits.']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 8 characters.']);
    exit;
}

if ($password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords do not match.']);
    exit;
}

$db = getDB();

$stmt = $db->prepare(
    'SELECT studentId FROM students WHERE admissionNumber = ?'
);
$stmt->execute([$admissionNumber]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'An account with this admission number already exists.']);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $db->prepare(
    'INSERT INTO students (fullName, admissionNumber, programme, passwordHash)
     VALUES (?, ?, ?, ?)'
);
$stmt->execute([
    $fullName,
    $admissionNumber,
    $programme ?: null,
    $passwordHash
]);

$studentId = $db->lastInsertId();

$_SESSION['studentId'] = $studentId;
$_SESSION['fullName']  = $fullName;
$_SESSION['role']      = 'student';

http_response_code(201);
echo json_encode([
    'message'   => 'Account created successfully.',
    'studentId' => $studentId,
    'fullName'  => $fullName,
    'role'      => 'student'
]);
