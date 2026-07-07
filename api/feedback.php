<?php
// api/feedback.php
// GET   — student: own feedback only
// GET   — admin (?all=1): all feedback, optional &hostelId=X filter
// POST  — student: submit new feedback (FR-10)
// PATCH — admin: classify + respond to feedback (?id=X) (FR-11)

require_once __DIR__ . '/../includes/headers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

session_start();
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ─────────────────────────────────────────
// GET — student's own feedback, or admin's view of all feedback
// ─────────────────────────────────────────
if ($method === 'GET') {

    // Admin view — all feedback, optionally filtered by hostel
    if (!empty($_GET['all'])) {
        requireAdmin();

        $hostelId = (int) ($_GET['hostelId'] ?? 0);

        if ($hostelId) {
            $stmt = $db->prepare(
                'SELECT f.feedbackId, f.submissionText, f.submittedAt,
                        f.hostelAccuracy, f.propertyCondition, f.issuesEncountered,
                        f.classification, f.suggestedClassification,
                        f.adminResponse, f.respondedAt,
                        s.admissionNumber, s.fullName,
                        h.hostelName, h.hostelId
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
                        f.classification, f.suggestedClassification,
                        f.adminResponse, f.respondedAt,
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
            $feedbackList, fn($f) => $f['classification'] === null
        ));
        $reviewed = array_values(array_filter(
            $feedbackList, fn($f) => $f['classification'] !== null
        ));

        echo json_encode([
            'allFeedback'     => $feedbackList,
            'unreviewed'      => $unreviewed,
            'reviewed'        => $reviewed,
            'total'           => count($feedbackList),
            'unreviewedCount' => count($unreviewed),
        ]);
        exit;
    }

    // Student view — own feedback only
    requireStudent();
    $studentId = currentStudentId();

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

// ─────────────────────────────────────────
// POST — student submits feedback (FR-10)
// ─────────────────────────────────────────
} elseif ($method === 'POST') {
    requireStudent();
    $studentId = currentStudentId();

    $data = json_decode(file_get_contents('php://input'), true);

    $hostelId       = (int) ($data['hostelId'] ?? 0);
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

    // Occupancy check — students may only review the hostel they are
    // currently assigned to (set by the admin via students.currentHostelId)
    $occupancyStmt = $db->prepare(
        'SELECT currentHostelId FROM students WHERE studentId = ?'
    );
    $occupancyStmt->execute([$studentId]);
    $currentHostelId = $occupancyStmt->fetchColumn();

    if (!$currentHostelId) {
        http_response_code(403);
        echo json_encode(['error' => 'You are not currently assigned to a hostel. Contact the Dean of Students office to confirm your accommodation before submitting feedback.']);
        exit;
    }

    if ((int) $currentHostelId !== $hostelId) {
        http_response_code(403);
        echo json_encode(['error' => 'You can only submit feedback for the hostel you are currently occupying.']);
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

    $hostelAccuracy    = isset($data['hostelAccuracy'])    ? (int) $data['hostelAccuracy']    : null;
    $propertyCondition = isset($data['propertyCondition']) ? (int) $data['propertyCondition'] : null;
    $issuesEncountered = trim($data['issuesEncountered'] ?? '') ?: null;

    $submissionText = htmlspecialchars($submissionText, ENT_QUOTES, 'UTF-8');

    $suggestedClassification = suggestSentiment(
        $submissionText . ' ' . ($issuesEncountered ?? '')
    );

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
        $suggestedClassification,
    ]);

    http_response_code(201);
    echo json_encode(['message' => 'Feedback submitted successfully.']);

// ─────────────────────────────────────────
// PATCH — admin classifies and/or responds to feedback (?id=X) (FR-11)
//
// Only columns actually present in the JSON body are updated. This
// prevents a classify-only call from wiping out an existing
// adminResponse, and vice versa.
// ─────────────────────────────────────────
} elseif ($method === 'PATCH') {
    requireAdmin();
    $adminId = currentAdminId();

    $feedbackId = (int) ($_GET['id'] ?? 0);

    if (!$feedbackId) {
        http_response_code(400);
        echo json_encode(['error' => 'Feedback ID is required.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $checkStmt = $db->prepare(
        'SELECT feedbackId FROM feedback WHERE feedbackId = ?'
    );
    $checkStmt->execute([$feedbackId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Feedback not found.']);
        exit;
    }

    $setClauses = [];
    $params     = [];

    // Only touch classification if the client explicitly sent it
    if (array_key_exists('classification', $data)) {
        $classification = $data['classification'];
        if ($classification !== null && !in_array($classification, ['positive', 'negative'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Classification must be positive or negative.']);
            exit;
        }
        $setClauses[] = 'classification = ?';
        $params[]     = $classification;
    }

    // Only touch adminResponse if the client explicitly sent it
    if (array_key_exists('adminResponse', $data)) {
        $adminResponse = trim($data['adminResponse'] ?? '') ?: null;
        $setClauses[]  = 'adminResponse = ?';
        $params[]      = $adminResponse;
        $setClauses[]  = 'respondedBy = ?';
        $params[]      = $adminId;
        $setClauses[]  = 'respondedAt = NOW()';
    }

    if (empty($setClauses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nothing to update.']);
        exit;
    }

    $params[] = $feedbackId;

    $stmt = $db->prepare(
        'UPDATE feedback SET ' . implode(', ', $setClauses) . ' WHERE feedbackId = ?'
    );
    $stmt->execute($params);

    echo json_encode(['message' => 'Feedback updated successfully.']);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

// AI-suggested sentiment helper (used at submission time)
function suggestSentiment(string $text): string {
    $text = strtolower($text);

    $positiveWords = [
        'great', 'good', 'excellent', 'clean', 'comfortable', 'secure', 'safe',
        'responsive', 'reliable', 'spacious', 'quiet', 'friendly', 'helpful',
        'affordable', 'convenient', 'satisfied', 'happy', 'recommend', 'love',
        'amazing', 'best', 'perfect', 'nice', 'cozy',
    ];

    $negativeWords = [
        'bad', 'broken', 'dirty', 'unsafe', 'noisy', 'slow', 'unresponsive',
        'leak', 'leaking', 'mold', 'mould', 'rude', 'expensive', 'overpriced',
        'unreliable', 'inconsistent', 'poor', 'terrible', 'worst', 'horrible',
        'disappointed', 'issue', 'issues', 'problem', 'problems', 'faulty',
        'theft', 'stolen', 'mosquitoes', 'smell', 'smelly',
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