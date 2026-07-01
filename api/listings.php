<?php
// api/listings.php
// GET    — anyone logged in: browse/filter listings (FR-05, FR-06, FR-09)
// POST   — admin only: create a new listing (FR-02)
// PATCH  — admin only: edit a listing (?id=X)
// DELETE — admin only: soft-delete a listing (?id=X) (FR-04)

require_once __DIR__ . '/../includes/headers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

session_start();
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ─────────────────────────────────────────
// GET — browse/filter listings (students + admins)
// ─────────────────────────────────────────
if ($method === 'GET') {

    $conditions = ['l.isActive = 1'];
    $params     = [];

    // Keyword search (FR-05)
    if (!empty($_GET['q'])) {
        $conditions[] = '(l.hostelName LIKE ? OR l.physicalAddress LIKE ? OR l.description LIKE ?)';
        $like         = '%' . $_GET['q'] . '%';
        $params       = array_merge($params, [$like, $like, $like]);
    }

    // Filter by location (FR-06) — matches against physicalAddress
    // since there is no separate neighbourhood column
    if (!empty($_GET['location'])) {
        $conditions[] = 'l.physicalAddress LIKE ?';
        $params[]     = '%' . $_GET['location'] . '%';
    }

    // Filter by price range (FR-06)
    if (!empty($_GET['priceMin'])) {
        $conditions[] = 'l.priceMax >= ?';
        $params[]     = (float) $_GET['priceMin'];
    }
    if (!empty($_GET['priceMax'])) {
        $conditions[] = 'l.priceMin <= ?';
        $params[]     = (float) $_GET['priceMax'];
    }

    // Filter by room type (FR-06)
    if (!empty($_GET['roomType'])) {
        $conditions[] = 'l.roomType = ?';
        $params[]     = $_GET['roomType'];
    }

    // Filter by gender policy (FR-06) — manual filter from the browse UI
    if (!empty($_GET['genderPolicy'])) {
        $conditions[] = 'l.genderPolicy = ?';
        $params[]     = $_GET['genderPolicy'];
    }

    // Automatic gender eligibility filter — students may only ever see
    // hostels matching their own gender, or mixed-gender hostels.
    // This is unconditional (does not depend on a preference profile)
    // and does not apply to admins, who need full visibility.
    if (!empty($_SESSION['studentId'])) {
        $genderStmt = $db->prepare('SELECT gender FROM students WHERE studentId = ?');
        $genderStmt->execute([$_SESSION['studentId']]);
        $studentGender = $genderStmt->fetchColumn();

        if ($studentGender === 'male') {
            $conditions[] = "l.genderPolicy IN ('male_only', 'mixed')";
        } elseif ($studentGender === 'female') {
            $conditions[] = "l.genderPolicy IN ('female_only', 'mixed')";
        }
    }

    // Filter by environment type (FR-06)
    if (!empty($_GET['environmentType'])) {
        $conditions[] = 'l.environmentType = ?';
        $params[]     = $_GET['environmentType'];
    }

    // Filter by curfew policy (FR-06)
    if (!empty($_GET['curfewPolicy'])) {
        $conditions[] = 'l.curfewPolicy = ?';
        $params[]     = $_GET['curfewPolicy'];
    }

    // Filter by amenities (FR-06)
    if (!empty($_GET['amenities'])) {
        foreach (explode(',', $_GET['amenities']) as $amenity) {
            $conditions[] = 'JSON_CONTAINS(l.amenities, ?)';
            $params[]     = json_encode(trim($amenity));
        }
    }

    $where = implode(' AND ', $conditions);
    $stmt  = $db->prepare(
        "SELECT l.hostelId, l.hostelName, l.physicalAddress, l.description,
                l.priceMin, l.priceMax, l.roomType, l.amenities,
                l.roomsAvailable, l.genderPolicy, l.environmentType,
                l.curfewPolicy, l.landlordName, l.landlordContact,
                l.latitude, l.longitude, l.isActive, l.createdAt
         FROM hostel_listings l
         WHERE $where
         ORDER BY l.createdAt DESC"
    );
    $stmt->execute($params);
    $listings = $stmt->fetchAll();

    foreach ($listings as &$listing) {
        $listing['amenities'] = json_decode($listing['amenities'], true);
    }
    unset($listing);

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
        unset($listing);
        usort($listings, fn($a, $b) => $b['matchScore'] <=> $a['matchScore']);
    }

    echo json_encode([
        'listings'   => $listings,
        'hasProfile' => $profile ? true : false,
        'total'      => count($listings),
    ]);

// ─────────────────────────────────────────
// POST — admin creates a new listing (FR-02)
// ─────────────────────────────────────────
} elseif ($method === 'POST') {
    requireAdmin();

    $data = json_decode(file_get_contents('php://input'), true);

    $required = [
        'hostelName', 'physicalAddress', 'description',
        'priceMin', 'priceMax', 'roomType', 'amenities',
        'roomsAvailable', 'landlordName', 'landlordContact',
        'genderPolicy', 'environmentType', 'curfewPolicy',
    ];

    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required."]);
            exit;
        }
    }

    if ((float) $data['priceMin'] >= (float) $data['priceMax']) {
        http_response_code(400);
        echo json_encode(['error' => 'priceMin must be less than priceMax.']);
        exit;
    }

    $validRoomTypes   = ['single', 'shared', 'ensuite'];
    $validGender      = ['male_only', 'female_only', 'mixed'];
    $validEnvironment = ['quiet', 'moderate', 'lively'];
    $validCurfew      = ['before_10pm', 'before_midnight', 'no_curfew'];

    if (!in_array($data['roomType'], $validRoomTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid room type.']);
        exit;
    }
    if (!in_array($data['genderPolicy'], $validGender)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid gender policy.']);
        exit;
    }
    if (!in_array($data['environmentType'], $validEnvironment)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid environment type.']);
        exit;
    }
    if (!in_array($data['curfewPolicy'], $validCurfew)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid curfew policy.']);
        exit;
    }

    // Geocode using Nominatim (free, no API key needed)
    $lat = null;
    $lng = null;

    $address = urlencode($data['physicalAddress'] . ', Nairobi, Kenya');
    $geoUrl  = "https://nominatim.openstreetmap.org/search"
             . "?q=$address&format=json&limit=1";

    $context = stream_context_create([
        'http' => [
            'header' => 'User-Agent: SUhousing/1.0 (student project)',
        ],
    ]);

    $geoResp = json_decode(@file_get_contents($geoUrl, false, $context), true);

    if (!empty($geoResp)) {
        $lat = (float) $geoResp[0]['lat'];
        $lng = (float) $geoResp[0]['lon'];
    }

    $stmt = $db->prepare(
        'INSERT INTO hostel_listings
            (hostelName, physicalAddress, description,
             latitude, longitude, priceMin, priceMax,
             roomType, amenities, roomsAvailable,
             landlordName, landlordContact, genderPolicy,
             environmentType, curfewPolicy)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        htmlspecialchars($data['hostelName'], ENT_QUOTES, 'UTF-8'),
        $data['physicalAddress'],
        htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8'),
        $lat, $lng,
        (float) $data['priceMin'],
        (float) $data['priceMax'],
        $data['roomType'],
        json_encode($data['amenities']),
        (int) $data['roomsAvailable'],
        htmlspecialchars($data['landlordName'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($data['landlordContact'], ENT_QUOTES, 'UTF-8'),
        $data['genderPolicy'],
        $data['environmentType'],
        $data['curfewPolicy'],
    ]);

    http_response_code(201);
    echo json_encode([
        'message'  => 'Listing created successfully.',
        'hostelId' => $db->lastInsertId(),
    ]);

// ─────────────────────────────────────────
// PATCH — admin edits a listing (?id=X)
// ─────────────────────────────────────────
} elseif ($method === 'PATCH') {
    requireAdmin();

    $hostelId = (int) ($_GET['id'] ?? 0);
    if (!$hostelId) {
        http_response_code(400);
        echo json_encode(['error' => 'Hostel ID is required.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    // Restore action — re-activates a soft-deleted listing
    if (($_GET['action'] ?? '') === 'restore') {
        $stmt = $db->prepare(
            'UPDATE hostel_listings SET isActive = 1 WHERE hostelId = ?'
        );
        $stmt->execute([$hostelId]);
        echo json_encode(['message' => 'Listing restored successfully.']);
        exit;
    }

    $allowed = [
        'hostelName', 'physicalAddress', 'description', 'priceMin', 'priceMax',
        'roomType', 'amenities', 'roomsAvailable', 'landlordName', 'landlordContact',
        'genderPolicy', 'environmentType', 'curfewPolicy',
    ];

    $sets   = [];
    $params = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $data)) {
            $sets[]   = "$field = ?";
            $params[] = $field === 'amenities'
                ? json_encode($data[$field])
                : $data[$field];
        }
    }

    if (empty($sets)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid fields to update.']);
        exit;
    }

    $params[] = $hostelId;
    $stmt = $db->prepare(
        'UPDATE hostel_listings SET ' . implode(', ', $sets) . ' WHERE hostelId = ?'
    );
    $stmt->execute($params);
    echo json_encode(['message' => 'Listing updated successfully.']);

// ─────────────────────────────────────────
// DELETE — admin soft-deletes a listing (?id=X) (FR-04)
// ─────────────────────────────────────────
} elseif ($method === 'DELETE') {
    requireAdmin();

    $hostelId = (int) ($_GET['id'] ?? 0);
    if (!$hostelId) {
        http_response_code(400);
        echo json_encode(['error' => 'Hostel ID is required.']);
        exit;
    }

    $stmt = $db->prepare(
        'UPDATE hostel_listings SET isActive = 0 WHERE hostelId = ?'
    );
    $stmt->execute([$hostelId]);
    echo json_encode(['message' => 'Listing removed successfully.']);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

// Preference scoring algorithm (FR-09)
// Maps all student lifestyle preferences against hostel attributes.
// Each criterion that is set contributes equally to the final percentage.
function scoreListingAgainstProfile(array $listing, array $profile): int {
    $score    = 0;
    $maxScore = 0;

    // ── Budget fit ──
    // Passes if the hostel's price range overlaps with the student's budget
    if ($profile['budgetMin'] !== null && $profile['budgetMax'] !== null) {
        $maxScore++;
        if (
            $listing['priceMin'] <= $profile['budgetMax'] &&
            $listing['priceMax'] >= $profile['budgetMin']
        ) {
            $score++;
        }
    }

    // ── Room type match ──
    if (!empty($profile['roomTypePreference'])) {
        $maxScore++;
        if ($listing['roomType'] === $profile['roomTypePreference']) {
            $score++;
        }
    }

    // ── Gender policy match ──
    if (!empty($profile['genderPreference'])) {
        $maxScore++;
        if ($listing['genderPolicy'] === $profile['genderPreference']) {
            $score++;
        }
    }

    // ── Location match ──
    // preferredLocation (e.g. "Madaraka") is matched against physicalAddress
    if (!empty($profile['preferredLocation'])) {
        $maxScore++;
        if (stripos($listing['physicalAddress'], $profile['preferredLocation']) !== false) {
            $score++;
        }
    }

    // ── Noise tolerance → environment type ──
    // A student's noise tolerance maps directly to the hostel's environment type
    // quiet/moderate/lively are shared enum values on both sides
    if (!empty($profile['noiseTolerance'])) {
        $maxScore++;
        if ($listing['environmentType'] === $profile['noiseTolerance']) {
            $score++;
        }
    }

    // ── Study habits → environment type + curfew policy ──
    // early_riser: prefers quiet environment AND early curfew (before_10pm)
    // night_owl:   needs no curfew to study late
    // flexible:    any environment or curfew is fine (always scores the point)
    if (!empty($profile['studyHabits'])) {
        $maxScore++;
        switch ($profile['studyHabits']) {
            case 'early_riser':
                if (
                    $listing['environmentType'] === 'quiet' &&
                    $listing['curfewPolicy']    === 'before_10pm'
                ) {
                    $score++;
                }
                break;
            case 'night_owl':
                if ($listing['curfewPolicy'] === 'no_curfew') {
                    $score++;
                }
                break;
            case 'flexible':
                $score++; // flexible students are compatible with any hostel
                break;
        }
    }

    // ── Sleep schedule → curfew policy ──
    // Maps the student's sleep time to compatible curfew policies:
    // before_10pm  → compatible with before_10pm curfew
    // 10pm_12am    → compatible with before_midnight or before_10pm
    // after_midnight → needs no_curfew
    if (!empty($profile['sleepSchedule'])) {
        $maxScore++;
        switch ($profile['sleepSchedule']) {
            case 'before_10pm':
                if ($listing['curfewPolicy'] === 'before_10pm') {
                    $score++;
                }
                break;
            case '10pm_12am':
                if (in_array($listing['curfewPolicy'], ['before_10pm', 'before_midnight'])) {
                    $score++;
                }
                break;
            case 'after_midnight':
                if ($listing['curfewPolicy'] === 'no_curfew') {
                    $score++;
                }
                break;
        }
    }

    // ── Curfew preference (explicit) ──
    // Direct match between student's stated curfew preference
    // and the hostel's curfew policy
    if (!empty($profile['curfewPreference'])) {
        $maxScore++;
        if ($listing['curfewPolicy'] === $profile['curfewPreference']) {
            $score++;
        }
    }

    // ── Environment type (explicit) ──
    // Direct match between student's stated environment preference
    // and the hostel's environment type
    if (!empty($profile['environmentType'])) {
        $maxScore++;
        if ($listing['environmentType'] === $profile['environmentType']) {
            $score++;
        }
    }

    return $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0;
}