<?php
// student/dashboard.php

session_start();

// ── Auth guard ──
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

require_once __DIR__ . '/../config/db.php';
$db = getDB();

$studentId = currentStudentId();

// ── Real data from DB ──

// Student name from session (set by login)
$userName = $_SESSION['fullName'] ?? 'Student';

// Total active listings
$totalListings = (int) $db->query(
    'SELECT COUNT(*) FROM hostel_listings WHERE isActive = 1'
)->fetchColumn();

// This student's feedback count
$myFeedbackStmt = $db->prepare(
    'SELECT COUNT(*) FROM feedback WHERE studentId = ?'
);
$myFeedbackStmt->execute([$studentId]);
$myFeedbackCount = (int) $myFeedbackStmt->fetchColumn();

// Check if student has a preference profile + fetch it for scoring
$profStmt = $db->prepare(
    'SELECT * FROM student_preference_profiles WHERE studentId = ?'
);
$profStmt->execute([$studentId]);
$profile    = $profStmt->fetch();
$hasProfile = (bool) $profile;

// Student's gender for hard-filter
$genderStmt = $db->prepare('SELECT gender FROM students WHERE studentId = ?');
$genderStmt->execute([$studentId]);
$studentGender = $genderStmt->fetchColumn();

// Build gender filter condition
$genderFilter = '';
$genderParams = [];
if ($studentGender === 'male') {
    $genderFilter = "AND genderPolicy IN ('male_only', 'mixed')";
} elseif ($studentGender === 'female') {
    $genderFilter = "AND genderPolicy IN ('female_only', 'mixed')";
}

// Top 3 recommended listings — scored against profile if exists,
// otherwise just the 3 most recently added
$recommendations = [];
if ($hasProfile) {
    // Fetch all eligible active listings and score them
    $allStmt = $db->query(
        "SELECT hostelId, hostelName, physicalAddress, priceMin, priceMax,
                roomType, amenities, genderPolicy, environmentType, curfewPolicy
         FROM hostel_listings
         WHERE isActive = 1 $genderFilter
         ORDER BY createdAt DESC"
    );
    $allListings = $allStmt->fetchAll();

    foreach ($allListings as &$l) {
        $l['amenities']  = json_decode($l['amenities'], true) ?? [];
        $l['matchScore'] = scoreListingAgainstProfile($l, $profile);
    }
    unset($l);

    usort($allListings, fn($a, $b) => $b['matchScore'] <=> $a['matchScore']);
    $recommendations = array_slice($allListings, 0, 3);
}

// Featured listings — latest 3 active (shown when no profile)
$featuredStmt = $db->query(
    "SELECT hostelId, hostelName, physicalAddress, priceMin, priceMax,
            roomType, amenities
     FROM hostel_listings
     WHERE isActive = 1 $genderFilter
     ORDER BY createdAt DESC
     LIMIT 3"
);
$featured = $featuredStmt->fetchAll();

foreach ($featured as &$h) {
    $h['amenities'] = json_decode($h['amenities'], true) ?? [];
}
unset($h);

// Scoring function — mirrors api/listings.php logic
function scoreListingAgainstProfile(array $listing, array $profile): int {
    $score = 0; $maxScore = 0;

    if ($profile['budgetMin'] !== null && $profile['budgetMax'] !== null) {
        $maxScore++;
        if ($listing['priceMin'] <= $profile['budgetMax'] &&
            $listing['priceMax'] >= $profile['budgetMin']) $score++;
    }
    if (!empty($profile['roomTypePreference'])) {
        $maxScore++;
        if ($listing['roomType'] === $profile['roomTypePreference']) $score++;
    }
    if (!empty($profile['genderPreference'])) {
        $maxScore++;
        if ($listing['genderPolicy'] === $profile['genderPreference']) $score++;
    }
    if (!empty($profile['preferredLocation'])) {
        $maxScore++;
        if (stripos($listing['physicalAddress'], $profile['preferredLocation']) !== false) $score++;
    }
    if (!empty($profile['noiseTolerance'])) {
        $maxScore++;
        if ($listing['environmentType'] === $profile['noiseTolerance']) $score++;
    }
    if (!empty($profile['studyHabits'])) {
        $maxScore++;
        switch ($profile['studyHabits']) {
            case 'early_riser':
                if ($listing['environmentType'] === 'quiet' &&
                    $listing['curfewPolicy'] === 'before_10pm') $score++;
                break;
            case 'night_owl':
                if ($listing['curfewPolicy'] === 'no_curfew') $score++;
                break;
            case 'flexible': $score++; break;
        }
    }
    if (!empty($profile['sleepSchedule'])) {
        $maxScore++;
        switch ($profile['sleepSchedule']) {
            case 'before_10pm':
                if ($listing['curfewPolicy'] === 'before_10pm') $score++;
                break;
            case '10pm_12am':
                if (in_array($listing['curfewPolicy'], ['before_10pm','before_midnight'])) $score++;
                break;
            case 'after_midnight':
                if ($listing['curfewPolicy'] === 'no_curfew') $score++;
                break;
        }
    }
    if (!empty($profile['curfewPreference'])) {
        $maxScore++;
        if ($listing['curfewPolicy'] === $profile['curfewPreference']) $score++;
    }
    if (!empty($profile['environmentType'])) {
        $maxScore++;
        if ($listing['environmentType'] === $profile['environmentType']) $score++;
    }
    return $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0;
}

// ── Page meta ──
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
$userRole   = 'student';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

  <!-- ── Page header ── -->
  <div class="page-header">
    <div class="page-title-group">
      <div class="page-breadcrumb">Home</div>
      <h1 class="page-title">
        Welcome back, <?php echo htmlspecialchars(
          explode(' ', $userName)[0]
        ); ?> 👋
      </h1>
      <p class="page-subtitle">
        Here's what's happening with your accommodation search.
      </p>
    </div>
    <div class="page-actions">
      <a href="/SU-Housing/student/browse.php"
         class="btn btn-primary">
        Browse Hostels
      </a>
    </div>
  </div>

  <div class="page-body">

    <!-- ── Preference profile nudge banner ── -->
    <?php if (!$hasProfile): ?>
      <div class="alert alert-info mb-24" style="align-items:center;">
        <div style="flex:1;">
          <strong>Set up your preference profile</strong> to get
          personalised hostel recommendations ranked by match percentage.
        </div>
        <a href="/SU-Housing/student/preference_profile.php?new=0"
           class="btn btn-navy btn-sm" style="flex-shrink:0;">
          Set Up Now
        </a>
      </div>
    <?php endif; ?>

    <!-- ── Stats row ── -->
    <div class="stats-grid">

      <div class="stat-card animate-fade-up delay-1">
        <div class="stat-icon amber">🏠</div>
        <div>
          <div class="stat-num"><?php echo $totalListings; ?></div>
          <div class="stat-label">Verified Hostels</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-2">
        <div>
          <div class="stat-num"><?php echo $myFeedbackCount; ?></div>
          <div class="stat-label">My Feedback</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-3">
        <div>
          <div class="stat-num"><?php echo $hasProfile ? '✓' : '—'; ?></div>
          <div class="stat-label">Preference Profile</div>
        </div>
      </div>

    </div>

    <!-- ── Top Recommendations (shown only when profile exists) ── -->
    <?php if ($hasProfile && !empty($recommendations)): ?>
      <div class="section-header">
        <div>
          <h2 class="section-title">Top Recommendations For You</h2>
          <p class="section-subtitle">
            Ranked by how well they match your preference profile
          </p>
        </div>
        <a href="/SU-Housing/student/browse.php" class="btn btn-ghost">
          See all →
        </a>
      </div>

      <div class="hostel-grid">
        <?php foreach ($recommendations as $h): ?>
          <div class="hostel-card animate-fade-up">

            <div class="hostel-card-img">
              <div class="hostel-card-img-inner">
                <span class="hostel-card-emoji">🏠</span>
              </div>
              <span class="match-badge"><?php echo $h['matchScore']; ?>% match</span>
              <span class="hostel-price-badge">
                KES <?php echo number_format($h['priceMin']); ?>
                – <?php echo number_format($h['priceMax']); ?>/mo
              </span>
            </div>

            <div class="hostel-card-body">
              <h3 class="hostel-name">
                <?php echo htmlspecialchars($h['hostelName']); ?>
              </h3>
              <div class="hostel-location">
                📍 <?php echo htmlspecialchars($h['physicalAddress']); ?>
              </div>
              <div class="hostel-amenities">
                <?php foreach (array_slice($h['amenities'], 0, 3) as $amenity): ?>
                  <span class="tag tag-blue">
                    <?php echo htmlspecialchars($amenity); ?>
                  </span>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="hostel-card-footer">
              <span class="tag tag-gray">
                <?php echo ucfirst($h['roomType']); ?>
              </span>
              <a href="/SU-Housing/student/detail.php?id=<?php echo $h['hostelId']; ?>"
                 class="btn btn-primary btn-sm">
                View Details →
              </a>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- ── Recently Added (always shown) ── -->
    <div class="section-header" style="margin-top:<?php echo $hasProfile ? '32px' : '0'; ?>;">
      <div>
        <h2 class="section-title">Recently Added Hostels</h2>
        <p class="section-subtitle">
          Latest verified listings from the Dean of Students office
        </p>
      </div>
      <a href="/SU-Housing/student/browse.php" class="btn btn-ghost">
        View all →
      </a>
    </div>

    <?php if (empty($featured)): ?>
      <div class="empty-state">
        <div class="empty-icon">🏠</div>
        <h3>No listings yet</h3>
        <p>The Dean of Students office hasn't published any hostels yet. Check back soon.</p>
      </div>
    <?php else: ?>
      <div class="hostel-grid">
        <?php foreach ($featured as $h): ?>
          <div class="hostel-card animate-fade-up">

            <div class="hostel-card-img">
              <div class="hostel-card-img-inner">
                <span class="hostel-card-emoji">🏠</span>
              </div>
              <span class="hostel-price-badge">
                KES <?php echo number_format($h['priceMin']); ?>
                – <?php echo number_format($h['priceMax']); ?>/mo
              </span>
            </div>

            <div class="hostel-card-body">
              <h3 class="hostel-name">
                <?php echo htmlspecialchars($h['hostelName']); ?>
              </h3>
              <div class="hostel-location">
                📍 <?php echo htmlspecialchars($h['physicalAddress']); ?>
              </div>
              <div class="hostel-amenities">
                <?php foreach (array_slice($h['amenities'], 0, 3) as $amenity): ?>
                  <span class="tag tag-blue">
                    <?php echo htmlspecialchars($amenity); ?>
                  </span>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="hostel-card-footer">
              <span class="tag tag-gray">
                <?php echo ucfirst($h['roomType']); ?>
              </span>
              <a href="/SU-Housing/student/detail.php?id=<?php echo $h['hostelId']; ?>"
                 class="btn btn-primary btn-sm">
                View Details →
              </a>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- ── Quick actions ── -->
    <div class="section-header" style="margin-top:32px;">
      <div>
        <h2 class="section-title">Quick Actions</h2>
        <p class="section-subtitle">Jump to what you need</p>
      </div>
    </div>

    <div class="quick-actions-grid">

      <a href="/SU-Housing/student/browse.php" class="quick-action-card">
        <div class="qa-label">Browse Hostels</div>
        <div class="qa-desc">Search and filter all verified listings</div>
      </a>

      <a href="/SU-Housing/student/feedback.php" class="quick-action-card">
        <div class="qa-label">My Feedback</div>
        <div class="qa-desc">View feedback you've submitted</div>
      </a>

      <a href="/SU-Housing/student/preference_profile.php" class="quick-action-card">
        <div class="qa-label">My Preferences</div>
        <div class="qa-desc">Update your accommodation preferences</div>
      </a>

      <a href="/SU-Housing/student/profile.php" class="quick-action-card">
        <div class="qa-label">My Profile</div>
        <div class="qa-desc">View and edit your account details</div>
      </a>

    </div>

  </div><!-- end page-body -->

<?php include __DIR__ . '/../includes/footer.php'; ?>