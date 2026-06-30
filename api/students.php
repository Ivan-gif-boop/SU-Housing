<?php
// api/students.php
// GET   — admin: list all students with their current hostel assignment
// PATCH — admin: assign/change a student's currentHostelId (?id=X)

require_once __DIR__ . '/../includes/headers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

session_start();
requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ─────────────────────────────────────────
// GET — list students + their current hostel
// ─────────────────────────────────────────
if ($method === 'GET') {

    $stmt = $db->query(
        'SELECT s.studentId, s.fullName, s.admissionNumber, s.programme,
                s.currentHostelId, h.hostelName AS currentHostelName
         FROM students s
         LEFT JOIN hostel_listings h ON s.currentHostelId = h.hostelId
         ORDER BY s.fullName ASC'
    );
    $students = $stmt->fetchAll();

    echo json_encode(['students' => $students]);

// ─────────────────────────────────────────
// PATCH — assign a student's current hostel (?id=X)
// ─────────────────────────────────────────
} elseif ($method === 'PATCH') {
    $studentId = (int) ($_GET['id'] ?? 0);

    if (!$studentId) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID is required.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    // hostelId may be null to un-assign a student (e.g. they moved out)
    $hostelId = array_key_exists('hostelId', $data) ? $data['hostelId'] : null;

    if ($hostelId !== null) {
        $hostelId = (int) $hostelId;
        $checkStmt = $db->prepare(
            'SELECT hostelId FROM hostel_listings WHERE hostelId = ? AND isActive = 1'
        );
        $checkStmt->execute([$hostelId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Hostel not found or inactive.']);
            exit;
        }
    }

    $studentCheck = $db->prepare('SELECT studentId FROM students WHERE studentId = ?');
    $studentCheck->execute([$studentId]);
    if (!$studentCheck->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Student not found.']);
        exit;
    }

    $updateStmt = $db->prepare(
        'UPDATE students SET currentHostelId = ? WHERE studentId = ?'
    );
    $updateStmt->execute([$hostelId, $studentId]);

    echo json_encode([
        'message'   => $hostelId
            ? 'Student assigned to hostel successfully.'
            : 'Student unassigned from hostel.',
    ]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}