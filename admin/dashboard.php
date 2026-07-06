<?php
// admin/dashboard.php
// FR-12: sentiment analytics chart
// FR-13: live stats dashboard

session_start();

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/db.php';
$db = getDB();

$userName = $_SESSION['fullName'] ?? 'Administrator';

// ── Summary stats ──
$activeListings = (int) $db->query(
    'SELECT COUNT(*) FROM hostel_listings WHERE isActive = 1'
)->fetchColumn();

$pendingCount = (int) $db->query(
    'SELECT COUNT(*) FROM feedback WHERE classification IS NULL'
)->fetchColumn();

$studentCount = (int) $db->query(
    'SELECT COUNT(*) FROM students'
)->fetchColumn();

$totalFeedback = (int) $db->query(
    'SELECT COUNT(*) FROM feedback'
)->fetchColumn();

// ── All active listings ──
$listings = $db->query(
    'SELECT hostelId, hostelName, physicalAddress,
            priceMin, priceMax, roomType, roomsAvailable, isActive, createdAt
     FROM hostel_listings
     WHERE isActive = 1
     ORDER BY createdAt DESC'
)->fetchAll();

// ── Per-hostel feedback analytics for chart (FR-12) ──
$analyticsStmt = $db->query(
    'SELECT
        h.hostelId,
        h.hostelName,
        COUNT(f.feedbackId) AS totalFeedback,
        SUM(CASE WHEN f.classification = "positive" THEN 1 ELSE 0 END) AS positiveCount,
        SUM(CASE WHEN f.classification = "negative" THEN 1 ELSE 0 END) AS negativeCount,
        SUM(CASE WHEN f.classification IS NULL     THEN 1 ELSE 0 END) AS pendingCount
     FROM hostel_listings h
     LEFT JOIN feedback f ON h.hostelId = f.hostelId
     WHERE h.isActive = 1
     GROUP BY h.hostelId, h.hostelName
     ORDER BY totalFeedback DESC'
);
$analytics = $analyticsStmt->fetchAll();

// Add overallSentiment label per hostel
foreach ($analytics as &$row) {
    $pos = (int) $row['positiveCount'];
    $neg = (int) $row['negativeCount'];
    if ($pos + $neg === 0) {
        $row['overallSentiment'] = 'no_feedback';
    } elseif ($pos >= $neg) {
        $row['overallSentiment'] = 'positive';
    } else {
        $row['overallSentiment'] = 'negative';
    }
}
unset($row);

// ── Unreviewed (pending) feedback for admin table ──
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
     JOIN students        s ON f.studentId = s.studentId
     JOIN hostel_listings h ON f.hostelId  = h.hostelId
     WHERE f.classification IS NULL
     ORDER BY f.submittedAt ASC'
);
$unreviewedStmt->execute();
$unreviewedFeedback = $unreviewedStmt->fetchAll();

// ── Recent activity — last 5 submissions ──
$recentActivity = $db->query(
    'SELECT
        f.feedbackId,
        f.submissionText,
        f.submittedAt,
        f.classification,
        s.admissionNumber,
        h.hostelName
     FROM feedback f
     JOIN students        s ON f.studentId = s.studentId
     JOIN hostel_listings h ON f.hostelId  = h.hostelId
     ORDER BY f.submittedAt DESC
     LIMIT 5'
)->fetchAll();

// ── Preference analytics — what students actually want ──
// Only counts students who have set up a preference profile

$prefAnalytics = [];

// How many students have a preference profile
$prefAnalytics['totalWithProfile'] = (int) $db->query(
    'SELECT COUNT(*) FROM student_preference_profiles'
)->fetchColumn();

// Room type breakdown
$prefAnalytics['roomType'] = $db->query(
    'SELECT roomTypePreference AS label, COUNT(*) AS count
     FROM student_preference_profiles
     WHERE roomTypePreference IS NOT NULL
     GROUP BY roomTypePreference
     ORDER BY count DESC'
)->fetchAll();

// Location breakdown
$prefAnalytics['location'] = $db->query(
    'SELECT preferredLocation AS label, COUNT(*) AS count
     FROM student_preference_profiles
     WHERE preferredLocation IS NOT NULL AND preferredLocation != ""
     GROUP BY preferredLocation
     ORDER BY count DESC'
)->fetchAll();

// Environment type breakdown
$prefAnalytics['environment'] = $db->query(
    'SELECT environmentType AS label, COUNT(*) AS count
     FROM student_preference_profiles
     WHERE environmentType IS NOT NULL
     GROUP BY environmentType
     ORDER BY count DESC'
)->fetchAll();

// Budget: average min and max across all profiles
$prefAnalytics['budget'] = $db->query(
    'SELECT
        ROUND(AVG(budgetMin)) AS avgMin,
        ROUND(AVG(budgetMax)) AS avgMax,
        ROUND(MIN(budgetMin)) AS lowestMin,
        ROUND(MAX(budgetMax)) AS highestMax
     FROM student_preference_profiles
     WHERE budgetMin IS NOT NULL AND budgetMax IS NOT NULL'
)->fetch();

// Gender preference breakdown
$prefAnalytics['gender'] = $db->query(
    'SELECT genderPreference AS label, COUNT(*) AS count
     FROM student_preference_profiles
     WHERE genderPreference IS NOT NULL
     GROUP BY genderPreference
     ORDER BY count DESC'
)->fetchAll();

// Study habits breakdown
$prefAnalytics['studyHabits'] = $db->query(
    'SELECT studyHabits AS label, COUNT(*) AS count
     FROM student_preference_profiles
     WHERE studyHabits IS NOT NULL
     GROUP BY studyHabits
     ORDER BY count DESC'
)->fetchAll();

// ── Page meta ──
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
$userRole   = 'admin';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

  <!-- ── Page header ── -->
  <div class="page-header">
    <div class="page-title-group">
      <div class="page-breadcrumb">Admin</div>
      <h1 class="page-title">
        Welcome, <?php echo htmlspecialchars(
          explode(' ', $userName)[0]
        ); ?> 
      </h1>
      <p class="page-subtitle">
        SU-Housing administration dashboard.
      </p>
    </div>
    <div class="page-actions">
      <a href="/SU-Housing/admin/listings.php" class="btn btn-primary">
        + Add Listing
      </a>
    </div>
  </div>

  <div class="page-body">

    <!-- ── Stats row ── -->
    <div class="stats-grid">

      <div class="stat-card animate-fade-up delay-1">
        <div>
          <div class="stat-num"><?php echo $activeListings; ?></div>
          <div class="stat-label">Active Listings</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-2">
        <div>
          <div class="stat-num"><?php echo $pendingCount; ?></div>
          <div class="stat-label">Pending Feedback</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-3">
        <div>
          <div class="stat-num"><?php echo $studentCount; ?></div>
          <div class="stat-label">Registered Students</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-4">
        <div>
          <div class="stat-num"><?php echo $totalFeedback; ?></div>
          <div class="stat-label">Total Feedback</div>
        </div>
      </div>

    </div>

    <!-- ── Sentiment Analytics Chart (FR-12) ── -->
    <div class="section-header">
      <div>
        <h2 class="section-title">Feedback Sentiment by Hostel</h2>
        <p class="section-subtitle">
          Positive vs negative classifications per listing
        </p>
      </div>
    </div>

    <div class="card mb-24">
      <div class="card-body" style="height:320px; position:relative;">
        <?php if (empty($analytics) || array_sum(array_column($analytics, 'totalFeedback')) === 0): ?>
          <div style="display:flex; align-items:center; justify-content:center;
                      height:100%; flex-direction:column; gap:8px;
                      color:var(--gray-400);">
            <span style="font-size:36px;"></span>
            <p style="font-size:14px;">No feedback submitted yet.</p>
          </div>
        <?php else: ?>
          <canvas id="sentimentChart"></canvas>
        <?php endif; ?>
      </div>
    </div>

    <!-- Pass analytics data to JS (FR-12) -->
    <script>
      window.SENTIMENT_DATA = <?php echo json_encode(array_map(fn($r) => [
          'hostelName' => $r['hostelName'],
          'positive'   => (int) $r['positiveCount'],
          'negative'   => (int) $r['negativeCount'],
      ], $analytics)); ?>;
    </script>

    <!-- ── Pending feedback table (FR-13) ── -->
    <?php if (!empty($unreviewedFeedback)): ?>
      <div class="section-header">
        <div>
          <h2 class="section-title">
            Pending Classification
            <span class="nav-badge"><?php echo $pendingCount; ?></span>
          </h2>
          <p class="section-subtitle">
            Feedback awaiting your positive / negative classification
          </p>
        </div>
        <a href="/SU-Housing/admin/feedback.php" class="btn btn-ghost">
          View all →
        </a>
      </div>

      <?php foreach ($unreviewedFeedback as $fb):
        $isPending = true;
        $fb['sentiment'] = null; // ensure card renders pending state
      ?>
        <?php include __DIR__ . '/../includes/feedback_card.php'; ?>
      <?php endforeach; ?>
    <?php endif; ?>

    <!-- ── Recent activity ── -->
    <div class="section-header" style="margin-top:32px;">
      <div>
        <h2 class="section-title">Recent Activity</h2>
        <p class="section-subtitle">Last 5 feedback submissions</p>
      </div>
    </div>

    <div class="card">
      <div class="table-wrap">
        <?php if (empty($recentActivity)): ?>
          <div class="empty-state" style="padding:40px;">
            <div class="empty-icon"></div>
            <h3>No activity yet</h3>
            <p>Recent feedback submissions will appear here.</p>
          </div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Student</th>
                <th>Hostel</th>
                <th>Submitted</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentActivity as $a): ?>
                <tr>
                  <td><?php echo htmlspecialchars($a['admissionNumber']); ?></td>
                  <td><?php echo htmlspecialchars($a['hostelName']); ?></td>
                  <td style="font-size:13px; color:var(--gray-500);">
                    <?php echo date('d M Y, H:i',
                      strtotime($a['submittedAt'])); ?>
                  </td>
                  <td>
                    <?php if ($a['classification'] === 'positive'): ?>
                      <span class="badge badge-green">✓ Positive</span>
                    <?php elseif ($a['classification'] === 'negative'): ?>
                      <span class="badge badge-red">✗ Negative</span>
                    <?php else: ?>
                      <span class="badge badge-gray">Pending</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── Listings overview ── -->
    <div class="section-header" style="margin-top:32px;">
      <div>
        <h2 class="section-title">Active Listings</h2>
        <p class="section-subtitle">
          <?php echo $activeListings; ?> hostels currently live
        </p>
      </div>
      <a href="/SU-Housing/admin/listings.php" class="btn btn-ghost">
        Manage →
      </a>
    </div>

    <div class="card">
      <div class="table-wrap">
        <?php if (empty($listings)): ?>
          <div class="empty-state" style="padding:40px;">
            <div class="empty-icon"></div>
            <h3>No active listings</h3>
            <p>
              <a href="/SU-Housing/admin/listings.php"
                 class="btn btn-primary mt-16">
                + Add First Listing
              </a>
            </p>
          </div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Hostel Name</th>
                <th>Location</th>
                <th>Price Range</th>
                <th>Room Type</th>
                <th>Rooms</th>
                <th>Sentiment</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Build a sentiment map from analytics
              $sentimentMap = [];
              foreach ($analytics as $a) {
                  $sentimentMap[$a['hostelId']] = $a['overallSentiment'];
              }
              foreach ($listings as $l):
                  $sentiment = $sentimentMap[$l['hostelId']] ?? 'no_feedback';
              ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($l['hostelName']); ?></strong></td>
                  <td><?php echo htmlspecialchars($l['physicalAddress']); ?></td>
                  <td>
                    KES <?php echo number_format($l['priceMin']); ?>
                    – <?php echo number_format($l['priceMax']); ?>
                  </td>
                  <td><?php echo ucfirst($l['roomType']); ?></td>
                  <td><?php echo $l['roomsAvailable']; ?></td>
                  <td>
                    <?php if ($sentiment === 'positive'): ?>
                      <span class="badge badge-green">✓ Positive</span>
                    <?php elseif ($sentiment === 'negative'): ?>
                      <span class="badge badge-red">✗ Negative</span>
                    <?php else: ?>
                      <span class="badge badge-gray">— No feedback</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- ════ STUDENT PREFERENCE ANALYTICS ════ -->
    <div class="section-header" style="margin-top:32px;">
      <div>
        <h2 class="section-title">Student Preference Analytics</h2>
        <p class="section-subtitle">
          What students are looking for —
          based on <?php echo $prefAnalytics['totalWithProfile']; ?>
          preference profile<?php echo $prefAnalytics['totalWithProfile'] !== 1 ? 's' : ''; ?>
          submitted
        </p>
      </div>
      <a href="/SU-Housing/admin/feedback.php" class="btn btn-ghost">
        Manage assignments →
      </a>
    </div>

    <?php if ($prefAnalytics['totalWithProfile'] === 0): ?>
      <div class="card">
        <div class="empty-state" style="padding:40px;">
          <div class="empty-icon"></div>
          <h3>No preference profiles yet</h3>
          <p>Analytics will appear here once students set up their preference profiles.</p>
        </div>
      </div>
    <?php else: ?>

      <!-- Analytics grid -->
      <div class="stats-grid" style="margin-bottom:16px;">

        <!-- Budget -->
        <?php if ($prefAnalytics['budget'] && $prefAnalytics['budget']['avgMin']): ?>
          <div class="stat-card">
            <div>
              <div class="stat-num" style="font-size:18px;">
                KES <?php echo number_format($prefAnalytics['budget']['avgMin']); ?>
                – <?php echo number_format($prefAnalytics['budget']['avgMax']); ?>
              </div>
              <div class="stat-label">Average Budget Range</div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Profiles count -->
        <div class="stat-card">
          <div>
            <div class="stat-num"><?php echo $prefAnalytics['totalWithProfile']; ?></div>
            <div class="stat-label">Students with Profiles</div>
          </div>
        </div>

      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">

        <!-- Room type breakdown -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Room Type Preferences</span>
          </div>
          <div class="card-body">
            <?php if (empty($prefAnalytics['roomType'])): ?>
              <p style="font-size:13px; color:var(--gray-400);">No data yet.</p>
            <?php else: ?>
              <?php foreach ($prefAnalytics['roomType'] as $row): ?>
                <?php
                  $pct = $prefAnalytics['totalWithProfile'] > 0
                    ? round(($row['count'] / $prefAnalytics['totalWithProfile']) * 100)
                    : 0;
                ?>
                <div style="margin-bottom:12px;">
                  <div style="display:flex; justify-content:space-between;
                               font-size:13px; margin-bottom:4px;">
                    <span><?php echo ucfirst($row['label']); ?></span>
                    <span style="color:var(--gray-500);">
                      <?php echo $row['count']; ?> student<?php echo $row['count'] != 1 ? 's' : ''; ?>
                      (<?php echo $pct; ?>%)
                    </span>
                  </div>
                  <div style="height:6px; background:var(--gray-100);
                               border-radius:3px; overflow:hidden;">
                    <div style="height:100%; width:<?php echo $pct; ?>%;
                                 background:var(--amber); border-radius:3px;
                                 transition:width 0.3s;"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Location breakdown -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Location Preferences</span>
          </div>
          <div class="card-body">
            <?php if (empty($prefAnalytics['location'])): ?>
              <p style="font-size:13px; color:var(--gray-400);">No data yet.</p>
            <?php else: ?>
              <?php foreach ($prefAnalytics['location'] as $row): ?>
                <?php
                  $pct = $prefAnalytics['totalWithProfile'] > 0
                    ? round(($row['count'] / $prefAnalytics['totalWithProfile']) * 100)
                    : 0;
                ?>
                <div style="margin-bottom:12px;">
                  <div style="display:flex; justify-content:space-between;
                               font-size:13px; margin-bottom:4px;">
                    <span><?php echo htmlspecialchars($row['label']); ?></span>
                    <span style="color:var(--gray-500);">
                      <?php echo $row['count']; ?> student<?php echo $row['count'] != 1 ? 's' : ''; ?>
                      (<?php echo $pct; ?>%)
                    </span>
                  </div>
                  <div style="height:6px; background:var(--gray-100);
                               border-radius:3px; overflow:hidden;">
                    <div style="height:100%; width:<?php echo $pct; ?>%;
                                 background:var(--navy); border-radius:3px;"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Environment type -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Environment Preferences</span>
          </div>
          <div class="card-body">
            <?php if (empty($prefAnalytics['environment'])): ?>
              <p style="font-size:13px; color:var(--gray-400);">No data yet.</p>
            <?php else: ?>
              <?php foreach ($prefAnalytics['environment'] as $row): ?>
                <?php
                  $pct = $prefAnalytics['totalWithProfile'] > 0
                    ? round(($row['count'] / $prefAnalytics['totalWithProfile']) * 100)
                    : 0;
                ?>
                <div style="margin-bottom:12px;">
                  <div style="display:flex; justify-content:space-between;
                               font-size:13px; margin-bottom:4px;">
                    <span><?php echo ucfirst($row['label']); ?></span>
                    <span style="color:var(--gray-500);">
                      <?php echo $row['count']; ?> student<?php echo $row['count'] != 1 ? 's' : ''; ?>
                      (<?php echo $pct; ?>%)
                    </span>
                  </div>
                  <div style="height:6px; background:var(--gray-100);
                               border-radius:3px; overflow:hidden;">
                    <div style="height:100%; width:<?php echo $pct; ?>%;
                                 background:var(--green); border-radius:3px;"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Study habits -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Study Habits</span>
          </div>
          <div class="card-body">
            <?php if (empty($prefAnalytics['studyHabits'])): ?>
              <p style="font-size:13px; color:var(--gray-400);">No data yet.</p>
            <?php else: ?>
              <?php
              $habitLabels = [
                'early_riser' => 'Early Riser',
                'night_owl'   => 'Night Owl',
                'flexible'    => 'Flexible',
              ];
              foreach ($prefAnalytics['studyHabits'] as $row):
                $pct = $prefAnalytics['totalWithProfile'] > 0
                  ? round(($row['count'] / $prefAnalytics['totalWithProfile']) * 100)
                  : 0;
                $label = $habitLabels[$row['label']] ?? ucfirst($row['label']);
              ?>
                <div style="margin-bottom:12px;">
                  <div style="display:flex; justify-content:space-between;
                               font-size:13px; margin-bottom:4px;">
                    <span><?php echo $label; ?></span>
                    <span style="color:var(--gray-500);">
                      <?php echo $row['count']; ?> student<?php echo $row['count'] != 1 ? 's' : ''; ?>
                      (<?php echo $pct; ?>%)
                    </span>
                  </div>
                  <div style="height:6px; background:var(--gray-100);
                               border-radius:3px; overflow:hidden;">
                    <div style="height:100%; width:<?php echo $pct; ?>%;
                                 background:var(--amber); border-radius:3px;"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>
    <?php endif; ?>

  </div><!-- end page-body -->

<?php
$extraScripts = [
    'https://cdn.jsdelivr.net/npm/chart.js',
    '/SU-Housing/assets/js/dashboard.js',
    '/SU-Housing/assets/js/feedback_admin.js',
];
include __DIR__ . '/../includes/footer.php';
?>