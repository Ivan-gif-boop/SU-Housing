<?php
require_once __DIR__ . '/../includes/headers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

session_start();
requireStudent();

$method    = $_SERVER['REQUEST_METHOD'];
$db        = getDB();
$studentId = currentStudentId();

// GET — Fetch student's preference profile (FR-08)
if ($method === 'GET') {
    $stmt = $db->prepare(
        'SELECT * FROM student_preference_profiles WHERE studentId = ?'
    );
    $stmt->execute([$studentId]);
    $profile = $stmt->fetch();

    echo json_encode([
        'profile' => $profile ?: null
    ]);

// PUT — Save or update preference profile (FR-08)
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    $budgetMin          = isset($data['budgetMin'])          ? (float)$data['budgetMin']  : null;
    $budgetMax          = isset($data['budgetMax'])          ? (float)$data['budgetMax']  : null;
    $roomTypePreference = $data['roomTypePreference']        ?? null;
    $preferredLocation  = $data['preferredLocation']         ?? null;
    $environmentType    = $data['environmentType']           ?? null;
    $genderPreference   = $data['genderPreference']          ?? null;
    $curfewPreference   = $data['curfewPreference']          ?? null;
    $studyHabits        = $data['studyHabits']               ?? null;
    $sleepSchedule      = $data['sleepSchedule']             ?? null;
    $noiseTolerance     = $data['noiseTolerance']            ?? null;

    // Check if profile already exists
    $stmt = $db->prepare(
        'SELECT profileId FROM student_preference_profiles WHERE studentId = ?'
    );
    $stmt->execute([$studentId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing profile
        $stmt = $db->prepare(
            'UPDATE student_preference_profiles SET
                budgetMin          = ?,
                budgetMax          = ?,
                roomTypePreference = ?,
                preferredLocation  = ?,
                environmentType    = ?,
                genderPreference   = ?,
                curfewPreference   = ?,
                studyHabits        = ?,
                sleepSchedule      = ?,
                noiseTolerance     = ?,
                updatedAt          = NOW()
             WHERE studentId = ?'
        );
        $stmt->execute([
            $budgetMin, $budgetMax, $roomTypePreference,
            $preferredLocation, $environmentType, $genderPreference,
            $curfewPreference, $studyHabits, $sleepSchedule,
            $noiseTolerance, $studentId
        ]);
    } else {
        // Create new profile
        $stmt = $db->prepare(
            'INSERT INTO student_preference_profiles
                (studentId, budgetMin, budgetMax, roomTypePreference,
                 preferredLocation, environmentType, genderPreference,
                 curfewPreference, studyHabits, sleepSchedule, noiseTolerance)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $studentId, $budgetMin, $budgetMax, $roomTypePreference,
            $preferredLocation, $environmentType, $genderPreference,
            $curfewPreference, $studyHabits, $sleepSchedule, $noiseTolerance
        ]);
    }

    echo json_encode(['message' => 'Profile saved successfully.']);

// DELETE — Clear preference profile
} elseif ($method === 'DELETE') {
    $stmt = $db->prepare(
        'DELETE FROM student_preference_profiles WHERE studentId = ?'
    );
    $stmt->execute([$studentId]);

    echo json_encode(['message' => 'Profile deleted successfully.']);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
