<?php
require_once __DIR__ . '/../../includes/headers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

session_start();
requireAdmin();   

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $required = [
        'hostelName', 'physicalAddress', 'description',
        'priceMin', 'priceMax', 'roomType', 'amenities',
        'roomsAvailable', 'landlordContact',
        'genderPolicy', 'environmentType', 'curfewPolicy'
    ];

    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required."]);
            exit;
        }
    }

    if ((float)$data['priceMin'] >= (float)$data['priceMax']) {
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

   $lat     = null;
$lng     = null;

// Geocode using Nominatim (free, no API key needed)
$address = urlencode($data['physicalAddress'] . ', Nairobi, Kenya');
$geoUrl  = "https://nominatim.openstreetmap.org/search"
         . "?q=$address&format=json&limit=1";

$context = stream_context_create([
    'http' => [
        'header' => 'User-Agent: SUhousing/1.0 (student project)'
    ]
]);

$geoResp = json_decode(@file_get_contents($geoUrl, false, $context), true);

if (!empty($geoResp)) {
    $lat = (float)$geoResp[0]['lat'];
    $lng = (float)$geoResp[0]['lon'];
}

    $stmt = $db->prepare(
        'INSERT INTO hostel_listings
            (hostelName, physicalAddress, description,
             latitude, longitude, priceMin, priceMax,
             roomType, amenities, roomsAvailable,
             landlordContact, genderPolicy,
             environmentType, curfewPolicy)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        htmlspecialchars($data['hostelName'],      ENT_QUOTES, 'UTF-8'),
      $data['physicalAddress'],
        htmlspecialchars($data['description'],     ENT_QUOTES, 'UTF-8'),
        $lat, $lng,
        (float)$data['priceMin'],
        (float)$data['priceMax'],
        $data['roomType'],
        json_encode($data['amenities']),
        (int)$data['roomsAvailable'],
        htmlspecialchars($data['landlordContact'], ENT_QUOTES, 'UTF-8'),
        $data['genderPolicy'],
        $data['environmentType'],
        $data['curfewPolicy'],
    ]);

    http_response_code(201);
    echo json_encode([
        'message'  => 'Listing created successfully.',
        'hostelId' => $db->lastInsertId()
    ]);

} elseif ($method === 'PATCH') {
    $hostelId = (int)($_GET['id'] ?? 0);
    if (!$hostelId) {
        http_response_code(400);
        echo json_encode(['error' => 'Hostel ID is required.']);
        exit;
    }

    $data    = json_decode(file_get_contents('php://input'), true);
    $allowed = [
        'hostelName', 'description', 'priceMin', 'priceMax',
        'roomType', 'amenities', 'roomsAvailable', 'landlordContact',
        'genderPolicy', 'environmentType', 'curfewPolicy'
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

} elseif ($method === 'DELETE') {
    $hostelId = (int)($_GET['id'] ?? 0);
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
