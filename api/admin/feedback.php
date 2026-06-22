<?php
require_once __DIR__ . '/../../includes/headers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

session_start();
requireAdmin();

$method  = $_SERVER['REQUEST_METHOD'];
$db      = getDB();
$adminId = currentAdminId();

// GET — View all feedback (FR-11)
if ($method === 'GET') {

    $hostelId = (int)($_GET['hostelId'] ?? 0);

    if ($hostelId) {
        $stmt = $db->prepare(
            'SELECT f.feedbackId, f.submissionText, f.submittedAt,
                    f.hostelAccuracy, f.propertyCondition, f.issuesEncountered,
                    f.classification, f.adminResponse, f.respondedAt,
                    s.admissionNumber, s.fullName,
                    h.hostelName
             FROM feedback f
             JOIN students s ON f.studentId = s.studentId
             JOIN hostel_listings h ON f.hostelId = h.hostelId
             WHERE f.hostelId = ?
             ORDER BY f.submittedAt DESC'
        );
        $stmt->execute([$hostelId]);
    } else {
        $stmt = $db->prepare(
            'SELECT f.feedbackId, f.submissionText, f.submittedAt,
                    f.hostelAccuracy, f.propertyCondition, f.issuesEncountered,
                    f.classification, f.adminResponse, f.respondedAt,
                    s.admissionNumber, s.fullName,
                    h.hostelName, h.hostelId
             FROM feedback f
             JOIN students s ON f.studentId = s.studentId
             JOIN hostel_listings h ON f.hostelId = h.hostelId
             ORDER BY f.submittedAt DESC'
        );
        $stmt->execute();
    }

    $feedbackList = $stmt->fetchAll();

    $unreviewed = array_values(array_filter(
        $feedbackList,
        fn($f) => $f['classification'] === null
    ));
    $reviewed = array_values(array_filter(
        $feedbackList,
        fn($f) => $f['classification'] !== null
    ));

    echo json_encode([
        'allFeedback'     => $feedbackList,
        'unreviewed'      => $unreviewed,
        'reviewed'        => $reviewed,
        'total'           => count($feedbackList),
        'unreviewedCount' => count($unreviewed)
    ]);

// PATCH — Classify and respond to feedback (FR-11)
} elseif ($method === 'PATCH') {
    $feedbackId = (int)($_GET['id'] ?? 0);

    if (!$feedbackId) {
        http_response_code(400);
        echo json_encode(['error' => 'Feedback ID is required.']);
        exit;
    }

    $data           = json_decode(file_get_contents('php://input'), true);
    $classification = $data['classification'] ?? null;
    $adminResponse  = trim($data['adminResponse'] ?? '') ?: null;

    if ($classification !== null && !in_array($classification, ['positive', 'negative'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Classification must be positive or negative.']);
        exit;
    }

    $checkStmt = $db->prepare(
        'SELECT feedbackId FROM feedback WHERE feedbackId = ?'
    );
    $checkStmt->execute([$feedbackId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Feedback not found.']);
        exit;
    }

    $stmt = $db->prepare(
        'UPDATE feedback SET
            classification = ?,
            adminResponse  = ?,
            respondedBy    = ?,
            respondedAt    = NOW()
         WHERE feedbackId  = ?'
    );
    $stmt->execute([
        $classification,
        $adminResponse,
        $adminId,
        $feedbackId
    ]);

    echo json_encode(['message' => 'Feedback classified and response saved.']);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
