<?php
// admin/feedback.php
// FR-11: admin views all feedback organised by hostel
//         and classifies each as positive or negative

session_start();

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/db.php';
$db = getDB();

$userName = $_SESSION['fullName'] ?? 'Administrator';

// ── Fetch all feedback with student + hostel info ──
// NOTE: the DB column is `classification`, not `sentiment`
$allFeedback = $db->query(
    'SELECT f.feedbackId,
            f.submissionText,
            f.submittedAt,
            f.classification  AS sentiment,
            f.hostelAccuracy,
            f.propertyCondition,
            f.issuesEncountered,
            h.hostelId,
            h.hostelName,
            s.fullName,
            s.admissionNumber
     FROM feedback f
     JOIN hostel_listings h ON h.hostelId  = f.hostelId
     JOIN students        s ON s.studentId = f.studentId
     ORDER BY f.submittedAt DESC'
)->fetchAll();

// Separate pending and classified
$pendingFeedback    = array_values(array_filter(
    $allFeedback, fn($f) => $f['sentiment'] === null
));
$classifiedFeedback = array_values(array_filter(
    $allFeedback, fn($f) => $f['sentiment'] !== null
));

// Count per sentiment
$positiveCount = count(array_filter(
    $allFeedback, fn($f) => $f['sentiment'] === 'positive'
));
$negativeCount = count(array_filter(
    $allFeedback, fn($f) => $f['sentiment'] === 'negative'
));
$pendingCount  = count($pendingFeedback);

// Active hostels — used to populate the student assignment dropdown
$activeHostels = $db->query(
    'SELECT hostelId, hostelName FROM hostel_listings WHERE isActive = 1 ORDER BY hostelName ASC'
)->fetchAll();

// ── Page meta ──
$pageTitle  = 'Student Feedback';
$activePage = 'feedback';
$userRole   = 'admin';

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
        who have occupied listed hostels.
      </p>
    </div>
  </div>

  <div class="page-body">

    <!-- ── Student hostel assignments (occupancy) ── -->
    <div class="card mb-24">
      <div class="card-header">
        <span class="card-title">Student Hostel Assignments</span>
      </div>
      <div class="card-body">
        <p style="font-size:13px; color:var(--gray-500); margin-bottom:16px;">
          Assign each student to the hostel they currently occupy.
          Students can only submit feedback for the hostel they are
          assigned to here.
        </p>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Student</th>
                <th>Admission No.</th>
                <th>Current Hostel</th>
                <th>Assign / Change</th>
              </tr>
            </thead>
            <tbody id="studentAssignTableBody">
              <tr><td colspan="4">Loading students…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── Stats row ── -->
    <div class="stats-grid">

      <div class="stat-card animate-fade-up delay-1">
        <div>
          <div class="stat-num"><?php echo count($allFeedback); ?></div>
          <div class="stat-label">Total Submissions</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-2">
        <div>
          <div class="stat-num"><?php echo $pendingCount; ?></div>
          <div class="stat-label">Pending Classification</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-3">
        <div class="stat-icon green">✓</div>
        <div>
          <div class="stat-num"><?php echo $positiveCount; ?></div>
          <div class="stat-label">Positive</div>
        </div>
      </div>

      <div class="stat-card animate-fade-up delay-4">
        <div class="stat-icon red">✗</div>
        <div>
          <div class="stat-num"><?php echo $negativeCount; ?></div>
          <div class="stat-label">Negative</div>
        </div>
      </div>

    </div>

    <!-- ── Tabs ── -->
    <div class="tabs" id="feedbackTabs">
      <button class="tab-btn active"
              onclick="switchFeedbackTab(this, 'tab-all')">
        All
        <span class="tab-count"><?php echo count($allFeedback); ?></span>
      </button>
      <button class="tab-btn"
              onclick="switchFeedbackTab(this, 'tab-pending')">
        Pending
        <?php if ($pendingCount > 0): ?>
          <span class="tab-count pending"><?php echo $pendingCount; ?></span>
        <?php endif; ?>
      </button>
      <button class="tab-btn"
              onclick="switchFeedbackTab(this, 'tab-classified')">
        Classified
        <span class="tab-count"><?php echo count($classifiedFeedback); ?></span>
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
        $uniqueHostels = array_unique(array_column($allFeedback, 'hostelName'));
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
          <h3>No feedback submitted yet</h3>
          <p>Feedback from students will appear here.</p>
        </div>
      <?php else: ?>
        <?php foreach ($allFeedback as $fb):
          $isPending = $fb['sentiment'] === null;
        ?>
          <?php include __DIR__ . '/../includes/feedback_card.php'; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- ════ TAB: PENDING ════ -->
    <div id="tab-pending" class="feedback-tab-content" style="display:none;">
      <?php if (empty($pendingFeedback)): ?>
        <div class="empty-state">
          <h3>All feedback classified</h3>
          <p>No pending submissions to review.</p>
        </div>
      <?php else: ?>
        <?php foreach ($pendingFeedback as $fb):
          $isPending = true;
        ?>
          <?php include __DIR__ . '/../includes/feedback_card.php'; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- ════ TAB: CLASSIFIED ════ -->
    <div id="tab-classified" class="feedback-tab-content" style="display:none;">
      <?php if (empty($classifiedFeedback)): ?>
        <div class="empty-state">
          <h3>No classified feedback yet</h3>
          <p>Classify pending submissions to see them here.</p>
        </div>
      <?php else: ?>
        <?php foreach ($classifiedFeedback as $fb):
          $isPending = false;
        ?>
          <?php include __DIR__ . '/../includes/feedback_card.php'; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div><!-- end page-body -->

<?php
$extraScripts = ['/SU-Housing/assets/js/feedback_admin.js', '/SU-Housing/assets/js/student_assignment.js'];
?>
<script>
  window.HOSTEL_OPTIONS = <?php echo json_encode($activeHostels); ?>;
</script>
<?php
include __DIR__ . '/../includes/footer.php';
?>