<?php
// admin/feedback.php
// FR-11: admin views all feedback organised by hostel
//         and classifies each as positive or negative

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
// // All feedback with classification status
// $allFeedback = $db->query(
//   'SELECT f.feedbackId, f.submissionText, f.submittedAt,
//           h.hostelId, h.hostelName,
//           u.fullName, u.admissionNumber,
//           sc.sentiment, sc.classifiedAt
//    FROM feedback f
//    JOIN hostel_listings h ON h.hostelId  = f.hostelId
//    JOIN users           u ON u.userId    = f.studentId
//    LEFT JOIN sentiment_classifications sc
//          ON sc.feedbackId = f.feedbackId
//    ORDER BY f.submittedAt DESC'
// )->fetchAll();
// ─────────────────────────────────────────

// Frontend defaults
$pageTitle  = 'Student Feedback';
$activePage = 'feedback';
$userRole   = 'admin';
$userName   = 'Dean of Students';

// Mock feedback data
$allFeedback = [
  [
    'feedbackId'      => 1,
    'hostelId'        => 1,
    'hostelName'      => 'Keri Apartment',
    'fullName'        => 'Amina Ochieng',
    'admissionNumber' => '176821',
    'submissionText'  => 'Excellent hostel overall. WiFi is
      reliable, water is available 24 hours, and the caretaker
      is very responsive. Highly recommend to other students.',
    'submittedAt'     => '2026-04-15 14:05:00',
    'sentiment'       => 'positive',
    'classifiedAt'    => '2026-04-16 09:00:00',
  ],
  [
    'feedbackId'      => 2,
    'hostelId'        => 1,
    'hostelName'      => 'Keri Apartment',
    'fullName'        => 'Brian Kamau',
    'admissionNumber' => '176833',
    'submissionText'  => 'Main gate lock has been broken for
      two weeks. The landlord has not responded to any calls.
      Security is a serious concern.',
    'submittedAt'     => '2026-04-14 16:45:00',
    'sentiment'       => 'negative',
    'classifiedAt'    => '2026-04-15 10:00:00',
  ],
  [
    'feedbackId'      => 3,
    'hostelId'        => 2,
    'hostelName'      => 'Nyayo View Suites',
    'fullName'        => 'Cynthia Otieno',
    'admissionNumber' => '176801',
    'submissionText'  => 'Very clean and well managed. The gym
      is a great addition. Management is professional and
      responds quickly to any issues.',
    'submittedAt'     => '2026-04-13 10:20:00',
    'sentiment'       => 'positive',
    'classifiedAt'    => '2026-04-14 08:30:00',
  ],
  [
    'feedbackId'      => 4,
    'hostelId'        => 3,
    'hostelName'      => 'Green Park Residences',
    'fullName'        => 'David Njoroge',
    'admissionNumber' => '176867',
    'submissionText'  => 'Water supply was inconsistent
      throughout March with no communication from the
      landlord. The bathrooms also need maintenance.',
    'submittedAt'     => '2026-04-12 09:23:00',
    'sentiment'       => 'negative',
    'classifiedAt'    => '2026-04-13 11:00:00',
  ],
  [
    'feedbackId'      => 5,
    'hostelId'        => 4,
    'hostelName'      => 'Madaraka Lodge',
    'fullName'        => 'Esther Wanjiru',
    'admissionNumber' => '176844',
    'submissionText'  => 'WiFi router on floor 2 has been down
      for 10 days with no update from management. Affects
      academic work significantly.',
    'submittedAt'     => '2026-04-17 08:14:00',
    'sentiment'       => null,
    'classifiedAt'    => null,
  ],
  [
    'feedbackId'      => 6,
    'hostelId'        => 2,
    'hostelName'      => 'Nyayo View Suites',
    'fullName'        => 'Felix Ouma',
    'admissionNumber' => '176855',
    'submissionText'  => 'The listing says backup power but the
      generator has not worked in over a month. Very
      misleading information.',
    'submittedAt'     => '2026-04-16 11:30:00',
    'sentiment'       => null,
    'classifiedAt'    => null,
  ],
  [
    'feedbackId'      => 7,
    'hostelId'        => 5,
    'hostelName'      => "Lang'ata Court",
    'fullName'        => 'Grace Muthoni',
    'admissionNumber' => '176878',
    'submissionText'  => 'Great location, very secure compound
      with CCTV. Laundry facilities are always clean.
      Would recommend to female students in particular.',
    'submittedAt'     => '2026-04-11 15:00:00',
    'sentiment'       => 'positive',
    'classifiedAt'    => '2026-04-12 09:00:00',
  ],
];

// Separate pending and classified
$pendingFeedback    = array_filter(
  $allFeedback, fn($f) => $f['sentiment'] === null
);
$classifiedFeedback = array_filter(
  $allFeedback, fn($f) => $f['sentiment'] !== null
);

// Count per sentiment
$positiveCount = count(array_filter(
  $allFeedback, fn($f) => $f['sentiment'] === 'positive'
));
$negativeCount = count(array_filter(
  $allFeedback, fn($f) => $f['sentiment'] === 'negative'
));
$pendingCount  = count($pendingFeedback);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

  <!-- ── Page header ── -->
  <div class="page-header">
    <div class="page-title-group">
      <div class="page-breadcrumb">Admin › Feedback</div>
      <h1 class="page-title">Student Feedback</h1>
      <p class="page-subtitle">
        Review and classify feedback submitted by students
        who have occupied listed hostels (FR-11).
      </p>
    </div>
  </div>

  <div class="page-body">

    <!-- ── Stats row ── -->
    <div class="stats-grid">

      <div class="stat-card animate-fade-up delay-1">
        <div class="stat-icon amber">📋</div>
        <div>
          <div class="stat-num">
            <?php echo count($allFeedback); ?>
          </div>
          <div class="stat-label">Total Submissions</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-2">
        <div class="stat-icon red">⏳</div>
        <div>
          <div class="stat-num">
            <?php echo $pendingCount; ?>
          </div>
          <div class="stat-label">Pending Classification</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-3">
        <div class="stat-icon green">✓</div>
        <div>
          <div class="stat-num">
            <?php echo $positiveCount; ?>
          </div>
          <div class="stat-label">Positive</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-4">
        <div class="stat-icon red">✗</div>
        <div>
          <div class="stat-num">
            <?php echo $negativeCount; ?>
          </div>
          <div class="stat-label">Negative</div>
        </div>
      </div>

    </div>

    <!-- ── Tabs ── -->
    <div class="tabs" id="feedbackTabs">
      <button class="tab-btn active"
              onclick="switchFeedbackTab(this, 'tab-all')">
        All
        <span class="tab-count">
          <?php echo count($allFeedback); ?>
        </span>
      </button>
      <button class="tab-btn"
              onclick="switchFeedbackTab(this, 'tab-pending')">
        Pending
        <?php if ($pendingCount > 0): ?>
          <span class="tab-count pending">
            <?php echo $pendingCount; ?>
          </span>
        <?php endif; ?>
      </button>
      <button class="tab-btn"
              onclick="switchFeedbackTab(this, 'tab-classified')">
        Classified
        <span class="tab-count">
          <?php echo count($classifiedFeedback); ?>
        </span>
      </button>
    </div>

    <!-- ── Filter bar ── -->
    <div style="display:flex; gap:12px; margin-bottom:20px;
                flex-wrap:wrap; align-items:center;">
      <input
        type="text"
        id="feedbackSearch"
        class="form-control"
        placeholder="Search by hostel or student name…"
        oninput="filterFeedbackCards()"
        style="max-width:300px;"
      />
      <select id="hostelFilter"
              class="form-control"
              onchange="filterFeedbackCards()"
              style="max-width:220px;">
        <option value="">All Hostels</option>
        <?php
        $uniqueHostels = array_unique(
          array_column($allFeedback, 'hostelName')
        );
        sort($uniqueHostels);
        foreach ($uniqueHostels as $hn):
        ?>
          <option value="<?php echo htmlspecialchars($hn); ?>">
            <?php echo htmlspecialchars($hn); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- ════ TAB: ALL ════ -->
    <div id="tab-all" class="feedback-tab-content active">
      <?php if (empty($allFeedback)): ?>
        <div class="empty-state">
          <div class="empty-icon">📋</div>
          <h3>No feedback submitted yet</h3>
          <p>Feedback from students will appear here.</p>
        </div>
      <?php else: ?>
        <?php foreach ($allFeedback as $fb):
          $isPending = $fb['sentiment'] === null;
        ?>
          <?php include __DIR__ .
            '/../includes/feedback_card.php'; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- ════ TAB: PENDING ════ -->
    <div id="tab-pending"
         class="feedback-tab-content"
         style="display:none;">
      <?php if (empty($pendingFeedback)): ?>
        <div class="empty-state">
          <div class="empty-icon">✅</div>
          <h3>All feedback classified</h3>
          <p>No pending submissions to review.</p>
        </div>
      <?php else: ?>
        <?php foreach ($pendingFeedback as $fb):
          $isPending = true;
        ?>
          <?php include __DIR__ .
            '/../includes/feedback_card.php'; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- ════ TAB: CLASSIFIED ════ -->
    <div id="tab-classified"
         class="feedback-tab-content"
         style="display:none;">
      <?php if (empty($classifiedFeedback)): ?>
        <div class="empty-state">
          <div class="empty-icon">📋</div>
          <h3>No classified feedback yet</h3>
          <p>Classify pending submissions to see them here.</p>
        </div>
      <?php else: ?>
        <?php foreach ($classifiedFeedback as $fb):
          $isPending = false;
        ?>
          <?php include __DIR__ .
            '/../includes/feedback_card.php'; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div><!-- end page-body -->

<?php
$extraScripts = [
  '/SU-Housing/assets/js/feedback_admin.js',
];
?>
<?php include __DIR__ . '/../includes/footer.php'; ?>