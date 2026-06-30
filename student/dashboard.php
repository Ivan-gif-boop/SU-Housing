<?php
// student/dashboard.php

session_start();

// ── Auth guard ──
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

require_once __DIR__ . '/../config/db.php';
$db = getDB();

$studentId = currentStudentId();


// Student name from session (set by login)
$userName = $_SESSION['fullName'] ?? 'Student';
$admissionNumber = $_SESSION['admissionNumber'] ?? null;

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

// Check if student has a preference profile
$profStmt = $db->prepare(
    'SELECT profileId FROM student_preference_profiles WHERE studentId = ?'
);
$profStmt->execute([$studentId]);
$hasProfile = (bool) $profStmt->fetch();

// Featured listings — latest 3 active
$featuredStmt = $db->query(
    'SELECT hostelId, hostelName, physicalAddress, priceMin, priceMax,
            roomType, amenities
     FROM hostel_listings
     WHERE isActive = 1
     ORDER BY createdAt DESC
     LIMIT 3'
);
$featured = $featuredStmt->fetchAll();

// Decode amenities JSON for each listing
foreach ($featured as &$h) {
    $h['amenities'] = json_decode($h['amenities'], true) ?? [];
}
unset($h);

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
        🔍 Browse Hostels
      </a>
    </div>
  </div>

  <div class="page-body">

    <!-- ── Preference profile nudge banner ── -->
    <?php if (!$hasProfile): ?>
      <div class="alert alert-info mb-24" style="align-items:center;">
        <span style="font-size:20px;">⚙️</span>
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
        <div class="stat-icon green">📝</div>
        <div>
          <div class="stat-num"><?php echo $myFeedbackCount; ?></div>
          <div class="stat-label">My Feedback</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-3">
        <div class="stat-icon blue">⚙️</div>
        <div>
          <div class="stat-num"><?php echo $hasProfile ? '✓' : '—'; ?></div>
          <div class="stat-label">Preference Profile</div>
        </div>
      </div>

    </div>

    <!-- ── Featured listings ── -->
    <div class="section-header">
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
        <div class="qa-icon amber">🔍</div>
        <div class="qa-label">Browse Hostels</div>
        <div class="qa-desc">Search and filter all verified listings</div>
      </a>

      <a href="/SU-Housing/student/feedback.php" class="quick-action-card">
        <div class="qa-icon green">📝</div>
        <div class="qa-label">My Feedback</div>
        <div class="qa-desc">View feedback you've submitted</div>
      </a>

      <a href="/SU-Housing/student/preference_profile.php" class="quick-action-card">
        <div class="qa-icon blue">⚙️</div>
        <div class="qa-label">My Preferences</div>
        <div class="qa-desc">Update your accommodation preferences</div>
      </a>

      <a href="/SU-Housing/student/profile.php" class="quick-action-card">
        <div class="qa-icon navy">👤</div>
        <div class="qa-label">My Profile</div>
        <div class="qa-desc">View and edit your account details</div>
      </a>

    </div>

  </div><!-- end page-body -->

<?php include __DIR__ . '/../includes/footer.php'; ?>