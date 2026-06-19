<?php
// admin/dashboard.php
// FR-12: sentiment analytics charts per hostel
// FR-13: consolidated dashboard — listings, unclassified feedback,
//         sentiment analytics

// ─────────────────────────────────────────
// BACKEND HOOK ZONE — Michelle fills this in
// ─────────────────────────────────────────
// require_once __DIR__ . '/../includes/auth_check.php';
// requireAuth('admin');
// $userName = $_SESSION['user_name'];
//
// require_once __DIR__ . '/../includes/db.php';
// $db = getDB();
//
// // Active listings count
// $totalListings = $db->query(
//   'SELECT COUNT(*) FROM hostel_listings WHERE isActive = 1'
// )->fetchColumn();
//
// // Unclassified feedback count (isPending)
// $unclassifiedCount = $db->query(
//   'SELECT COUNT(*) FROM feedback f
//    LEFT JOIN sentiment_classifications sc
//          ON sc.feedbackId = f.feedbackId
//    WHERE sc.classificationId IS NULL'
// )->fetchColumn();
//
// // Recent unclassified feedback (for classification table)
// $pendingFeedback = $db->query(
//   'SELECT f.feedbackId, f.submissionText, f.submittedAt,
//           h.hostelName, u.admissionNumber, u.fullName
//    FROM feedback f
//    JOIN hostel_listings h ON h.hostelId  = f.hostelId
//    JOIN users           u ON u.userId    = f.studentId
//    LEFT JOIN sentiment_classifications sc
//          ON sc.feedbackId = f.feedbackId
//    WHERE sc.classificationId IS NULL
//    ORDER BY f.submittedAt DESC
//    LIMIT 10'
// )->fetchAll();
//
// // Sentiment analytics per hostel (FR-12)
// $sentimentData = $db->query(
//   'SELECT h.hostelName,
//           SUM(sc.sentiment = "positive") AS positive,
//           SUM(sc.sentiment = "negative") AS negative
//    FROM hostel_listings h
//    LEFT JOIN feedback f ON f.hostelId = h.hostelId
//    LEFT JOIN sentiment_classifications sc
//          ON sc.feedbackId = f.feedbackId
//    WHERE h.isActive = 1
//    GROUP BY h.hostelId, h.hostelName
//    HAVING positive > 0 OR negative > 0
//    ORDER BY (positive + negative) DESC'
// )->fetchAll();
// ─────────────────────────────────────────

// Frontend defaults
$pageTitle  = 'Admin Dashboard';
$activePage = 'dashboard';
$userRole   = 'admin';
$userName   = 'Dean of Students';

// Mock stats
$totalListings     = 6;
$unclassifiedCount = 3;

// Mock pending feedback
$pendingFeedback = [
  [
    'feedbackId'     => 3,
    'hostelName'     => 'Madaraka Lodge',
    'fullName'       => 'Brian Mwangi',
    'admissionNumber'=> '176844',
    'submissionText' => 'WiFi router on floor 2 has been down
      for 10 days with no update from management.',
    'submittedAt'    => '2026-04-17 08:14:00',
  ],
  [
    'feedbackId'     => 4,
    'hostelName'     => 'Westview Apartments',
    'fullName'       => 'Cynthia Otieno',
    'admissionNumber'=> '176801',
    'submissionText' => 'The listing says backup power but the
      generator has not worked in over a month.',
    'submittedAt'    => '2026-04-15 11:30:00',
  ],
  [
    'feedbackId'     => 5,
    'hostelName'     => 'Sunrise Hostel',
    'fullName'       => 'David Njoroge',
    'admissionNumber'=> '176867',
    'submissionText' => 'Main gate lock has been broken for two
      weeks. Security is compromised.',
    'submittedAt'    => '2026-04-14 16:45:00',
  ],
];

// Mock sentiment analytics data (FR-12)
$sentimentData = [
  [
    'hostelName' => 'Keri Apartments',
    'positive'   => 8,
    'negative'   => 2,
  ],
  [
    'hostelName' => 'Nairobi West',
    'positive'   => 5,
    'negative'   => 3,
  ],
  [
    'hostelName' => "Lang'ata Court",
    'positive'   => 6,
    'negative'   => 1,
  ],
  [
    'hostelName' => 'Green Park Residences',
    'positive'   => 2,
    'negative'   => 5,
  ],
  [
    'hostelName' => 'Madaraka Lodge',
    'positive'   => 3,
    'negative'   => 4,
  ],
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

  <!-- ── Page header ── -->
  <div class="page-header">
    <div class="page-title-group">
      <div class="page-breadcrumb">Admin › Home</div>
      <h1 class="page-title">Administrator Dashboard</h1>
      <p class="page-subtitle">
        Office of the Dean of Students — Accommodation Management
      </p>
    </div>
    <div class="page-actions">
      <a href="/SU-Housing/admin/listings.php"
         class="btn btn-primary">
        + Add New Listing
      </a>
    </div>
  </div>

  <div class="page-body">

    <!-- ── Stats row (FR-13) ── -->
    <div class="stats-grid">

      <div class="stat-card animate-fade-up delay-1">
        <div class="stat-icon amber">🏠</div>
        <div>
          <div class="stat-num">
            <?php echo $totalListings; ?>
          </div>
          <div class="stat-label">Active Listings</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-2">
        <div class="stat-icon red">📋</div>
        <div>
          <div class="stat-num">
            <?php echo $unclassifiedCount; ?>
          </div>
          <div class="stat-label">Unclassified Feedback</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-3">
        <div class="stat-icon green">✓</div>
        <div>
          <div class="stat-num">
            <?php
            $totalClassified = array_sum(
              array_map(
                fn($r) => $r['positive'] + $r['negative'],
                $sentimentData
              )
            );
            echo $totalClassified;
            ?>
          </div>
          <div class="stat-label">Classified Feedback</div>
        </div>
      </div>

    </div>

    <!-- ── Sentiment Analytics (FR-12) ── -->
    <div class="section-header">
      <div>
        <h2 class="section-title">Sentiment Analytics</h2>
        <p class="section-subtitle">
          Positive vs negative feedback per hostel listing
        </p>
      </div>
      <a href="/SU-Housing/admin/feedback.php"
         class="btn btn-ghost">
        View all feedback →
      </a>
    </div>

    <div class="card mb-16">
      <div class="card-body">

        <?php if (empty($sentimentData)): ?>
          <div class="empty-state" style="padding:40px 24px;">
            <div class="empty-icon">📊</div>
            <h3>No classified feedback yet</h3>
            <p>
              Classify student feedback below to generate
              sentiment analytics.
            </p>
          </div>

        <?php else: ?>
          <!-- Chart canvas — Chart.js renders here -->
          <div style="position:relative; height:320px;">
            <canvas id="sentimentChart"></canvas>
          </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- ── Unclassified Feedback (FR-11, FR-13) ── -->
    <div class="section-header">
      <div>
        <h2 class="section-title">
          Unclassified Feedback
          <?php if ($unclassifiedCount > 0): ?>
            <span class="nav-badge" style="
              font-size:13px; padding:3px 10px;
              vertical-align:middle; margin-left:6px;">
              <?php echo $unclassifiedCount; ?>
            </span>
          <?php endif; ?>
        </h2>
        <p class="section-subtitle">
          Classify each submission as positive or negative
          to update the analytics dashboard
        </p>
      </div>
    </div>

    <?php if (empty($pendingFeedback)): ?>
      <div class="card">
        <div class="empty-state" style="padding:40px 24px;">
          <div class="empty-icon">✅</div>
          <h3>All feedback classified</h3>
          <p>No pending submissions to review.</p>
        </div>
      </div>

    <?php else: ?>
      <?php foreach ($pendingFeedback as $fb): ?>
        <div class="feedback-classify-card" id="fb-<?php
             echo $fb['feedbackId']; ?>">

          <div class="fbc-header">
            <div>
              <div class="fbc-hostel">
                <?php echo htmlspecialchars($fb['hostelName']); ?>
              </div>
              <div class="fbc-meta">
                <?php echo htmlspecialchars($fb['fullName']); ?>
                · Admission:
                <?php echo htmlspecialchars($fb['admissionNumber']); ?>
                ·
                <?php echo date(
                  'j M Y',
                  strtotime($fb['submittedAt'])
                ); ?>
              </div>
            </div>
            <span class="badge badge-amber">Pending</span>
          </div>

          <p class="fbc-text">
            "<?php echo nl2br(
              htmlspecialchars($fb['submissionText'])
            ); ?>"
          </p>

          <!--
            Backend hook:
            These buttons POST to
            /SU-Housing/api/feedback/classify.php?id={feedbackId}
            with sentiment=positive or sentiment=negative
            Michelle wires up the fetch() call in the JS below
          -->
          <div class="fbc-actions">
            <button
              class="btn btn-success btn-sm"
              onclick="classifyFeedback(
                <?php echo $fb['feedbackId']; ?>,
                'positive'
              )"
            >
              ✓ Positive
            </button>
            <button
              class="btn btn-danger btn-sm"
              onclick="classifyFeedback(
                <?php echo $fb['feedbackId']; ?>,
                'negative'
              )"
            >
              ✗ Negative
            </button>
          </div>

        </div>
      <?php endforeach; ?>

    <?php endif; ?>

  </div><!-- end page-body -->

<?php
// Pass sentiment data to JS
$sentimentJson = json_encode($sentimentData);

$extraScripts = [
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
  '/SU-Housing/assets/js/dashboard.js',
];
?>
<script>
  // Sentiment data from PHP — available to dashboard.js
  window.SENTIMENT_DATA = <?php echo $sentimentJson; ?>;
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>