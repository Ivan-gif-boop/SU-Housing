<?php
require_once __DIR__ . '/../includes/headers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

session_start();
requireStudent();

$method    = $_SERVER['REQUEST_METHOD'];
$db        = getDB();
$studentId = currentStudentId();

// POST — Student submits feedback (FR-10)
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $hostelId       = (int)($data['hostelId'] ?? 0);
    $submissionText = trim($data['submissionText'] ?? '');

    if (!$hostelId || !$submissionText) {
        http_response_code(400);
        echo json_encode(['error' => 'Hostel ID and feedback text are required.']);
        exit;
    }

    $hostelStmt = $db->prepare(
        'SELECT hostelId FROM hostel_listings WHERE hostelId = ? AND isActive = 1'
    );
    $hostelStmt->execute([$hostelId]);
    if (!$hostelStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Hostel not found.']);
        exit;
    }

    $dupStmt = $db->prepare(
        'SELECT feedbackId FROM feedback WHERE hostelId = ? AND studentId = ?'
    );
    $dupStmt->execute([$hostelId, $studentId]);
    if ($dupStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'You have already submitted feedback for this hostel.']);
        exit;
    }

    $hostelAccuracy    = isset($data['hostelAccuracy'])    ? (int)$data['hostelAccuracy']    : null;
    $propertyCondition = isset($data['propertyCondition']) ? (int)$data['propertyCondition'] : null;
    $issuesEncountered = trim($data['issuesEncountered'] ?? '') ?: null;

    $submissionText = htmlspecialchars($submissionText, ENT_QUOTES, 'UTF-8');

    $suggestedClassification = suggestSentiment($submissionText . ' ' . ($issuesEncountered ?? ''));

    $stmt = $db->prepare(
        'INSERT INTO feedback
            (hostelId, studentId, submissionText,
             hostelAccuracy, propertyCondition, issuesEncountered,
             suggestedClassification)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $hostelId, $studentId, $submissionText,
        $hostelAccuracy, $propertyCondition, $issuesEncountered,
        $suggestedClassification
    ]);

    http_response_code(201);
    echo json_encode(['message' => 'Feedback submitted successfully.']);

} elseif ($method === 'GET') {
    $stmt = $db->prepare(
        'SELECT f.feedbackId, f.submissionText, f.submittedAt,
                f.hostelAccuracy, f.propertyCondition, f.issuesEncountered,
                f.classification, f.adminResponse, f.respondedAt,
                h.hostelName
         FROM feedback f
         JOIN hostel_listings h ON f.hostelId = h.hostelId
         WHERE f.studentId = ?
         ORDER BY f.submittedAt DESC'
    );
    $stmt->execute([$studentId]);

    echo json_encode(['feedback' => $stmt->fetchAll()]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

function suggestSentiment(string $text): string {
    $text = strtolower($text);

    $positiveWords = [
        'great', 'good', 'excellent', 'clean', 'comfortable', 'secure', 'safe',
        'responsive', 'reliable', 'spacious', 'quiet', 'friendly', 'helpful',
        'affordable', 'convenient', 'satisfied', 'happy', 'recommend', 'love',
        'amazing', 'best', 'perfect', 'nice', 'cozy'
    ];

    $negativeWords = [
        'bad', 'broken', 'dirty', 'unsafe', 'noisy', 'slow', 'unresponsive',
        'leak', 'leaking', 'mold', 'mould', 'rude', 'expensive', 'overpriced',
        'unreliable', 'inconsistent', 'poor', 'terrible', 'worst', 'horrible',
        'disappointed', 'issue', 'issues', 'problem', 'problems', 'faulty',
        'theft', 'stolen', 'mosquitoes', 'smell', 'smelly'
    ];

    $positiveCount = 0;
    $negativeCount = 0;

    foreach ($positiveWords as $word) {
        $positiveCount += substr_count($text, $word);
    }
    foreach ($negativeWords as $word) {
        $negativeCount += substr_count($text, $word);
    }

    if ($positiveCount > $negativeCount) {
        return 'positive';
    } elseif ($negativeCount > $positiveCount) {
        return 'negative';
    } else {
        return 'neutral';
    }
}