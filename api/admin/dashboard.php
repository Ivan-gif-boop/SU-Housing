<?php
require_once __DIR__ . '/../../includes/headers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

session_start();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$db = getDB();

// Total active listings (FR-13)
$activeListings = $db->query(
    'SELECT COUNT(*) FROM hostel_listings WHERE isActive = 1'
)->fetchColumn();

// Total unreviewed feedback count for badge (FR-13)
$unreviewedCount = $db->query(
    'SELECT COUNT(*) FROM feedback WHERE classification IS NULL'
)->fetchColumn();

// Total registered students
$studentCount = $db->query(
    'SELECT COUNT(*) FROM students'
)->fetchColumn();

// All active listings (FR-13)
$listings = $db->query(
    'SELECT hostelId, hostelName, physicalAddress,
            priceMin, priceMax, roomType,
            roomsAvailable, isActive, createdAt
     FROM hostel_listings
     WHERE isActive = 1
     ORDER BY createdAt DESC'
)->fetchAll();

// Per hostel feedback analytics for charts (FR-12)
$analytics = $db->query(
    'SELECT
        h.hostelId,
        h.hostelName,
        COUNT(f.feedbackId) as totalFeedback,
        SUM(CASE WHEN f.classification = "positive" THEN 1 ELSE 0 END) as positiveCount,
        SUM(CASE WHEN f.classification = "negative" THEN 1 ELSE 0 END) as negativeCount,
        SUM(CASE WHEN f.classification IS NULL THEN 1 ELSE 0 END) as pendingCount
     FROM hostel_listings h
     LEFT JOIN feedback f ON h.hostelId = f.hostelId
     WHERE h.isActive = 1
     GROUP BY h.hostelId, h.hostelName
     ORDER BY totalFeedback DESC'
)->fetchAll();

// Add overall sentiment per hostel
foreach ($analytics as &$hostel) {
    $pos = (int)$hostel['positiveCount'];
    $neg = (int)$hostel['negativeCount'];

    if ($pos + $neg === 0) {
        $hostel['overallSentiment'] = 'no_feedback';
    } elseif ($pos >= $neg) {
        $hostel['overallSentiment'] = 'positive';
    } else {
        $hostel['overallSentiment'] = 'negative';
    }
}

// Unreviewed feedback list for admin table (FR-13)
$unreviewedStmt = $db->prepare(
    'SELECT
        f.feedbackId,
        f.submissionText,
        f.submittedAt,
        f.hostelAccuracy,
        f.propertyCondition,
        f.issuesEncountered,
        s.admissionNumber,
        s.fullName,
        h.hostelName,
        h.hostelId
     FROM feedback f
     JOIN students s ON f.studentId = s.studentId
     JOIN hostel_listings h ON f.hostelId = h.hostelId
     WHERE f.classification IS NULL
     ORDER BY f.submittedAt ASC'
);
$unreviewedStmt->execute();
$unreviewedFeedback = $unreviewedStmt->fetchAll();

// Recent activity — last 5 feedback submissions
$recentActivity = $db->query(
    'SELECT
        f.feedbackId,
        f.submissionText,
        f.submittedAt,
        f.classification,
        s.admissionNumber,
        h.hostelName
     FROM feedback f
     JOIN students s ON f.studentId = s.studentId
     JOIN hostel_listings h ON f.hostelId = h.hostelId
     ORDER BY f.submittedAt DESC
     LIMIT 5'
)->fetchAll();

echo json_encode([
    'summary' => [
        'activeListings'  => (int)$activeListings,
        'unreviewedCount' => (int)$unreviewedCount,
        'studentCount'    => (int)$studentCount,
    ],
    'listings'           => $listings,
    'feedbackAnalytics'  => $analytics,
    'unreviewedFeedback' => $unreviewedFeedback,
    'recentActivity'     => $recentActivity,
]);
