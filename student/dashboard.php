<?php
// student/dashboard.php

// ─────────────────────────────────────────
// BACKEND HOOK ZONE — Michelle fills this in
// ─────────────────────────────────────────
// require_once __DIR__ . '/../includes/auth_check.php';
// requireAuth('student');
// $userName = $_SESSION['user_name'];
//
// require_once __DIR__ . '/../includes/db.php';
// $db = getDB();
//
// // Stats
// $totalListings = $db->query(
//   'SELECT COUNT(*) FROM hostel_listings WHERE isActive = 1'
// )->fetchColumn();
//
// $myFeedbackCount = $db->prepare(
//   'SELECT COUNT(*) FROM feedback WHERE studentId = ?'
// );
// $myFeedbackCount->execute([$_SESSION['user_id']]);
// $myFeedbackCount = $myFeedbackCount->fetchColumn();
//
// // Check if student has a preference profile
// $hasProfile = $db->prepare(
//   'SELECT profileId FROM student_preference_profiles WHERE userId = ?'
// );
// $hasProfile->execute([$_SESSION['user_id']]);
// $hasProfile = (bool) $hasProfile->fetch();
//
// // Featured listings (latest 3)
// $featured = $db->query(
//   'SELECT hostelId, hostelName, neighbourhood, priceMin, priceMax,
//           roomType, amenities
//    FROM hostel_listings
//    WHERE isActive = 1
//    ORDER BY createdAt DESC
//    LIMIT 3'
// )->fetchAll();
// ─────────────────────────────────────────

// Frontend defaults — hardcoded for now
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
$userRole   = 'student';
$userName   = 'Ivan Wachira';

// Mock stats
$totalListings   = 12;
$myFeedbackCount = 1;
$hasProfile      = false;

// Mock featured listings
$featured = [
  [
    'hostelId'      => 1,
    'hostelName'    => 'Keri Apartments',
    'neighbourhood' => 'Madaraka',
    'priceMin'      => 15500,
    'priceMax'      => 20000,
    'roomType'      => 'single',
    'amenities'     => ['WiFi', 'Water', 'Security'],
  ],
  [
    'hostelId'      => 2,
    'hostelName'    => 'Campus View Apartments',
    'neighbourhood' => 'Nairobi West',
    'priceMin'      => 10000,
    'priceMax'      => 15000,
    'roomType'      => 'studio',
    'amenities'     => ['WiFi', 'Security', 'Parking'],
  ],
  [
    'hostelId'      => 3,
    'hostelName'    => 'Green Park Residences',
    'neighbourhood' => "Lang'ata",
    'priceMin'      => 6500,
    'priceMax'      => 9000,
    'roomType'      => 'shared',
    'amenities'     => ['Water', 'Security', 'Laundry'],
  ],
];

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
          <div class="stat-num">
            <?php echo $totalListings; ?>
          </div>
          <div class="stat-label">Verified Hostels</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-2">
        <div class="stat-icon green">📝</div>
        <div>
          <div class="stat-num">
            <?php echo $myFeedbackCount; ?>
          </div>
          <div class="stat-label">My Feedback</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-3">
        <div class="stat-icon blue">⚙️</div>
        <div>
          <div class="stat-num">
            <?php echo $hasProfile ? '✓' : '—'; ?>
          </div>
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
      <a href="/SU-Housing/student/browse.php"
         class="btn btn-ghost">
        View all →
      </a>
    </div>

    <div class="hostel-grid">
      <?php foreach ($featured as $h): ?>
        <div class="hostel-card animate-fade-up">

          <!-- Card image area -->
          <div class="hostel-card-img">
            <div class="hostel-card-img-inner">
              <span class="hostel-card-emoji">🏠</span>
            </div>
            <span class="hostel-price-badge">
              KES <?php echo number_format($h['priceMin']); ?>
              – <?php echo number_format($h['priceMax']); ?>/mo
            </span>
          </div>

          <!-- Card body -->
          <div class="hostel-card-body">
            <h3 class="hostel-name">
              <?php echo htmlspecialchars($h['hostelName']); ?>
            </h3>
            <div class="hostel-location">
              📍 <?php echo htmlspecialchars($h['neighbourhood']); ?>
            </div>
            <div class="hostel-amenities">
              <?php
              // Decode if JSON string (from DB), use as-is if already array
              $amenities = is_string($h['amenities'])
                ? json_decode($h['amenities'], true)
                : $h['amenities'];
              foreach (array_slice($amenities, 0, 3) as $amenity):
              ?>
                <span class="tag tag-blue">
                  <?php echo htmlspecialchars($amenity); ?>
                </span>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Card footer -->
          <div class="hostel-card-footer">
            <span class="tag tag-gray">
              <?php echo ucfirst($h['roomType']); ?>
            </span>
            
              <a href="/SU-Housing/student/detail.php?id=<?php echo $h['hostelId']; ?>"
              class="btn btn-primary btn-sm"
            >
              View Details →
            </a>
          </div>

        </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Quick actions ── -->
    <div class="section-header" style="margin-top:32px;">
      <div>
        <h2 class="section-title">Quick Actions</h2>
        <p class="section-subtitle">Jump to what you need</p>
      </div>
    </div>

    <div class="quick-actions-grid">

      <a href="/SU-Housing/student/browse.php"
         class="quick-action-card">
        <div class="qa-icon amber">🔍</div>
        <div class="qa-label">Browse Hostels</div>
        <div class="qa-desc">
          Search and filter all verified listings
        </div>
      </a>

      <a href="/SU-Housing/student/feedback.php"
         class="quick-action-card">
        <div class="qa-icon green">📝</div>
        <div class="qa-label">My Feedback</div>
        <div class="qa-desc">
          View feedback you've submitted
        </div>
      </a>

      <a href="/SU-Housing/student/preference_profile.php"
         class="quick-action-card">
        <div class="qa-icon blue">⚙️</div>
        <div class="qa-label">My Preferences</div>
        <div class="qa-desc">
          Update your accommodation preferences
        </div>
      </a>

      <a href="/SU-Housing/student/profile.php"
         class="quick-action-card">
        <div class="qa-icon navy">👤</div>
        <div class="qa-label">My Profile</div>
        <div class="qa-desc">
          View and edit your account details
        </div>
      </a>

    </div>

  </div><!-- end page-body -->

<?php include __DIR__ . '/../includes/footer.php'; ?>