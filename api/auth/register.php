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

$fullName        = trim($data['fullName']        ?? '');
$admissionNumber = trim($data['admissionNumber'] ?? '');
$programme       = trim($data['programme']       ?? '');
$gender          = trim($data['gender']          ?? '');
$password        = $data['password']             ?? '';
$confirmPassword = $data['confirmPassword']      ?? '';

// ── Validation ──
if (!$fullName || !$admissionNumber || !$gender || !$password || !$confirmPassword) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required.']);
    exit;
}

if (!preg_match('/^\d{5,8}$/', $admissionNumber)) {
    http_response_code(400);
    echo json_encode(['error' => 'Admission number must be 5 to 8 digits.']);
    exit;
}

if (!in_array($gender, ['male', 'female'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Gender must be male or female.']);
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

// ── Check for existing admission number ──
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

// ── Insert — gender column now included ──
$stmt = $db->prepare(
    'INSERT INTO students (fullName, admissionNumber, gender, programme, passwordHash)
     VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([
    $fullName,
    $admissionNumber,
    $gender,
    $programme ?: null,
    $passwordHash,
]);

$studentId = (int) $db->lastInsertId();

// ── Set session — matches what login.php stores ──
session_regenerate_id(true);
$_SESSION['studentId']       = $studentId;
$_SESSION['fullName']        = $fullName;
$_SESSION['admissionNumber'] = $admissionNumber;
$_SESSION['role']            = 'student';

http_response_code(201);
echo json_encode([
    'message'         => 'Account created successfully.',
    'studentId'       => $studentId,
    'fullName'        => $fullName,
    'admissionNumber' => $admissionNumber,
    'role'            => 'student',
]);