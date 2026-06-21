<?php
require_once __DIR__ . '/../includes/headers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

session_start();
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

if ($method === 'GET') {

    $conditions = ['l.isActive = 1'];
    $params     = [];

    // Keyword search (FR-05)
    if (!empty($_GET['q'])) {
        $conditions[] = '(l.hostelName LIKE ? OR l.physicalAddress LIKE ? OR l.description LIKE ?)';
        $like         = '%' . $_GET['q'] . '%';
        $params       = array_merge($params, [$like, $like, $like]);
    }

    // Filter by location (FR-06)
    if (!empty($_GET['location'])) {
        $conditions[] = 'l.physicalAddress LIKE ?';
        $params[]     = '%' . $_GET['location'] . '%';
    }

    // Filter by price range (FR-06)
    if (!empty($_GET['priceMin'])) {
        $conditions[] = 'l.priceMax >= ?';
        $params[]     = (float)$_GET['priceMin'];
    }
    if (!empty($_GET['priceMax'])) {
        $conditions[] = 'l.priceMin <= ?';
        $params[]     = (float)$_GET['priceMax'];
    }

    // Filter by room type (FR-06)
    if (!empty($_GET['roomType'])) {
        $conditions[] = 'l.roomType = ?';
        $params[]     = $_GET['roomType'];
    }

    // Filter by gender policy (FR-06)
    if (!empty($_GET['genderPolicy'])) {
        $conditions[] = 'l.genderPolicy = ?';
        $params[]     = $_GET['genderPolicy'];
    }

    // Filter by environment type (FR-06)
    if (!empty($_GET['environmentType'])) {
        $conditions[] = 'l.environmentType = ?';
        $params[]     = $_GET['environmentType'];
    }

    // Filter by amenities (FR-06)
    if (!empty($_GET['amenities'])) {
        $amenities = explode(',', $_GET['amenities']);
        foreach ($amenities as $amenity) {
            $conditions[] = 'JSON_CONTAINS(l.amenities, ?)';
            $params[]     = json_encode(trim($amenity));
        }
    }

    $where = implode(' AND ', $conditions);
    $stmt  = $db->prepare(
        "SELECT l.hostelId, l.hostelName, l.physicalAddress,
                l.priceMin, l.priceMax, l.roomType, l.amenities,
                l.roomsAvailable, l.genderPolicy, l.environmentType,
                l.curfewPolicy, l.latitude, l.longitude
         FROM hostel_listings l
         WHERE $where
         ORDER BY l.createdAt DESC"
    );
    $stmt->execute($params);
    $listings = $stmt->fetchAll();

    foreach ($listings as &$listing) {
        $listing['amenities'] = json_decode($listing['amenities'], true);
    }

    // Check if student has a preference profile (FR-09)
    $profile = null;
    if (!empty($_SESSION['studentId'])) {
        $studentId = currentStudentId();
        $profStmt  = $db->prepare(
            'SELECT * FROM student_preference_profiles WHERE studentId = ?'
        );
        $profStmt->execute([$studentId]);
        $profile = $profStmt->fetch();
    }

    // Score and rank listings if profile exists
    if ($profile) {
        foreach ($listings as &$listing) {
            $listing['matchScore'] = scoreListingAgainstProfile($listing, $profile);
        }
        usort($listings, fn($a, $b) => $b['matchScore'] <=> $a['matchScore']);
    }

    echo json_encode([
        'listings'   => $listings,
        'hasProfile' => $profile ? true : false,
        'total'      => count($listings)
    ]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

// Preference scoring algorithm (FR-09)
function scoreListingAgainstProfile(array $listing, array $profile): int {
    $score    = 0;
    $maxScore = 0;

    // Budget fit
    if ($profile['budgetMin'] !== null && $profile['budgetMax'] !== null) {
        $maxScore++;
        if (
            $listing['priceMin'] <= $profile['budgetMax'] &&
            $listing['priceMax'] >= $profile['budgetMin']
        ) {
            $score++;
        }
    }

    // Room type match
    if ($profile['roomTypePreference'] !== null) {
        $maxScore++;
        if ($listing['roomType'] === $profile['roomTypePreference']) {
            $score++;
        }
    }

    // Environment type match
    if ($profile['environmentType'] !== null) {
        $maxScore++;
        if ($listing['environmentType'] === $profile['environmentType']) {
            $score++;
        }
    }

    // Gender policy match
    if ($profile['genderPreference'] !== null) {
        $maxScore++;
        if ($listing['genderPolicy'] === $profile['genderPreference']) {
            $score++;
        }
    }

    // Curfew policy match
    if ($profile['curfewPreference'] !== null) {
        $maxScore++;
        if ($listing['curfewPolicy'] === $profile['curfewPreference']) {
            $score++;
        }
    }

    return $maxScore > 0 ? (int)round(($score / $maxScore) * 100) : 0;
}
