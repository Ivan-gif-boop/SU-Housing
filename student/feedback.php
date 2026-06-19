<?php
// student/feedback.php
// FR-10: student submits feedback on a hostel they have occupied

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
// // Load all active hostels for the dropdown
// $hostels = $db->query(
//   'SELECT hostelId, hostelName
//    FROM hostel_listings
//    WHERE isActive = 1
//    ORDER BY hostelName ASC'
// )->fetchAll();
//
// // Load this student's previously submitted feedback
// $stmt = $db->prepare(
//   'SELECT f.feedbackId, f.submissionText, f.submittedAt,
//           h.hostelName,
//           sc.sentiment
//    FROM feedback f
//    JOIN hostel_listings h ON h.hostelId = f.hostelId
//    LEFT JOIN sentiment_classifications sc
//          ON sc.feedbackId = f.feedbackId
//    WHERE f.studentId = ?
//    ORDER BY f.submittedAt DESC'
// );
// $stmt->execute([$_SESSION['user_id']]);
// $myFeedback = $stmt->fetchAll();
//
// // Handle POST — feedback submission
// $success = null;
// $error   = null;
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//   // Delegate to api/feedback/store.php
//   // verifyCsrf();
//   // ... insert feedback record
// }
// ─────────────────────────────────────────

// Frontend defaults
$pageTitle  = 'My Feedback';
$activePage = 'feedback';
$userRole   = 'student';
$userName   = 'Ivan Wachira';

// Pre-selected hostel from detail page link (?hostelId=X)
$preselectedHostelId = (int)($_GET['hostelId'] ?? 0);

$success = null;
$error   = null;

// Mock hostels for dropdown
$hostels = [
  ['hostelId' => 1, 'hostelName' => 'Keri Apartments'],
  ['hostelId' => 2, 'hostelName' => 'Nyayo View Suites'],
  ['hostelId' => 3, 'hostelName' => 'Green Park Residences'],
  ['hostelId' => 4, 'hostelName' => 'Westview Apartments'],
  ['hostelId' => 5, 'hostelName' => 'Madaraka Heights'],
  ['hostelId' => 6, 'hostelName' => "Lang'ata Court"],
];

// Mock previously submitted feedback
$myFeedback = [
  [
    'feedbackId'     => 1,
    'hostelName'     => 'Green Park Residences',
    'submissionText' => 'Water supply was inconsistent throughout
      March. The landlord was unresponsive when contacted. The
      compound security is good but the common bathrooms need
      maintenance.',
    'submittedAt'    => '2026-04-10 09:23:00',
    'sentiment'      => null, // not yet classified by admin
  ],
  [
    'feedbackId'     => 2,
    'hostelName'     => 'Keri Apartments',
    'submissionText' => 'Excellent hostel overall. WiFi is reliable,
      water is available 24 hours, and the caretaker is very
      responsive. Highly recommend to other students.',
    'submittedAt'    => '2026-03-15 14:05:00',
    'sentiment'      => 'positive',
  ],
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

  <!-- ── Page header ── -->
  <div class="page-header">
    <div class="page-title-group">
      <div class="page-breadcrumb">Home › My Feedback</div>
      <h1 class="page-title">My Feedback</h1>
      <p class="page-subtitle">
        Submit reports on hostels you have occupied to help the
        Dean of Students office maintain listing quality.
      </p>
    </div>
  </div>

  <div class="page-body">

    <div class="feedback-layout">

      <!-- ════ LEFT: Submission form ════ -->
      <div class="feedback-form-col">

        <div class="card">
          <div class="card-header">
            <span class="card-title">Submit New Feedback</span>
          </div>
          <div class="card-body">

            <!-- PHP success / error messages -->
            <?php if ($success): ?>
              <div class="alert alert-success mb-16">
                ✓ <?php echo htmlspecialchars($success); ?>
              </div>
            <?php endif; ?>
            <?php if ($error): ?>
              <div class="alert alert-error mb-16">
                ⚠️ <?php echo htmlspecialchars($error); ?>
              </div>
            <?php endif; ?>

            <div class="alert alert-info mb-16">
              ℹ️ Only submit feedback about a hostel you have
              personally occupied. Your report will be reviewed
              by the Office of the Dean of Students.
            </div>

            <!--
              Backend hook:
              action="/SU-Housing/api/feedback/store.php"
              method="POST"
              Michelle adds csrfField() here
            -->
            <form
              action="#"
              method="POST"
              id="feedbackForm"
              novalidate
            >

              <!-- Hostel selector -->
              <div class="form-group">
                <label for="hostelId">Hostel</label>
                <select
                  id="hostelId"
                  name="hostelId"
                  class="form-control"
                  required
                >
                  <option value="" disabled
                    <?php echo !$preselectedHostelId
                      ? 'selected' : ''; ?>>
                    Select a hostel…
                  </option>
                  <?php foreach ($hostels as $h): ?>
                    <option
                      value="<?php echo $h['hostelId']; ?>"
                      <?php echo $h['hostelId'] === $preselectedHostelId
                        ? 'selected' : ''; ?>
                    >
                      <?php echo htmlspecialchars($h['hostelName']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-error" id="err-hostelId"></div>
              </div>

              <!-- Feedback text -->
              <div class="form-group">
                <label for="submissionText">Your Feedback</label>
                <textarea
                  id="submissionText"
                  name="submissionText"
                  class="form-control"
                  placeholder="Describe your experience — the
accuracy of the listing, the condition of the property,
any maintenance issues, safety concerns, or general
observations that would help other students or the
Dean of Students office…"
                  rows="7"
                  required
                  minlength="30"
                ></textarea>
                <div class="form-hint">
                  Minimum 30 characters. Be specific and factual.
                </div>
                <div class="form-error" id="err-submissionText"></div>
                <!-- Character counter -->
                <div class="char-counter" id="charCounter">
                  0 / 30 minimum
                </div>
              </div>

              <button
                type="submit"
                class="btn btn-primary btn-full"
              >
                Submit Feedback →
              </button>

            </form>

          </div>
        </div>

      </div><!-- end feedback-form-col -->

      <!-- ════ RIGHT: Past submissions ════ -->
      <div class="feedback-history-col">

        <div class="section-header">
          <div>
            <h2 class="section-title">My Submissions</h2>
            <p class="section-subtitle">
              Feedback you have previously submitted
            </p>
          </div>
          <span class="badge badge-blue">
            <?php echo count($myFeedback); ?>
          </span>
        </div>

        <?php if (empty($myFeedback)): ?>
          <div class="empty-state">
            <div class="empty-icon">📝</div>
            <h3>No feedback yet</h3>
            <p>
              Submit your first feedback report using
              the form on the left.
            </p>
          </div>

        <?php else: ?>
          <?php foreach ($myFeedback as $fb): ?>
            <div class="feedback-item">

              <div class="feedback-item-header">
                <div>
                  <div class="feedback-hostel-name">
                    <?php echo htmlspecialchars($fb['hostelName']); ?>
                  </div>
                  <div class="feedback-date">
                    Submitted
                    <?php echo date(
                      'j M Y',
                      strtotime($fb['submittedAt'])
                    ); ?>
                  </div>
                </div>
                <!-- Sentiment badge from admin classification -->
                <?php if ($fb['sentiment'] === 'positive'): ?>
                  <span class="badge badge-green">
                    ✓ Positive
                  </span>
                <?php elseif ($fb['sentiment'] === 'negative'): ?>
                  <span class="badge badge-red">
                    ✗ Negative
                  </span>
                <?php else: ?>
                  <span class="badge badge-gray">
                    Pending Review
                  </span>
                <?php endif; ?>
              </div>

              <p class="feedback-text">
                <?php echo nl2br(
                  htmlspecialchars($fb['submissionText'])
                ); ?>
              </p>

            </div>
          <?php endforeach; ?>

        <?php endif; ?>

      </div><!-- end feedback-history-col -->

    </div><!-- end feedback-layout -->

  </div><!-- end page-body -->

<script>
// ── Client-side validation (NFR-02) ──

const form          = document.getElementById('feedbackForm');
const textArea      = document.getElementById('submissionText');
const charCounter   = document.getElementById('charCounter');
const MIN_LENGTH    = 30;

// Live character counter
textArea?.addEventListener('input', () => {
  const len = textArea.value.trim().length;
  charCounter.textContent =
    `${len} / ${MIN_LENGTH} minimum`;
  charCounter.style.color =
    len >= MIN_LENGTH ? 'var(--green)' : 'var(--gray-400)';
});

function showError(fieldId, msg) {
  const el  = document.getElementById('err-' + fieldId);
  const inp = document.getElementById(fieldId);
  if (el)  el.textContent = msg;
  if (inp) inp.classList.add('is-error');
}

function clearError(fieldId) {
  const el  = document.getElementById('err-' + fieldId);
  const inp = document.getElementById(fieldId);
  if (el)  el.textContent = '';
  if (inp) inp.classList.remove('is-error');
}

['hostelId', 'submissionText'].forEach(id => {
  document.getElementById(id)
    ?.addEventListener('input', () => clearError(id));
  document.getElementById(id)
    ?.addEventListener('change', () => clearError(id));
});

form?.addEventListener('submit', function(e) {
  let valid = true;

  // Hostel required
  const hostelVal = document.getElementById('hostelId').value;
  if (!hostelVal) {
    showError('hostelId', 'Please select a hostel.');
    valid = false;
  }

  // Feedback text minimum length
  const text = textArea.value.trim();
  if (!text) {
    showError('submissionText', 'Feedback text is required.');
    valid = false;
  } else if (text.length < MIN_LENGTH) {
    showError(
      'submissionText',
      `Please write at least ${MIN_LENGTH} characters.`
    );
    valid = false;
  }

  if (!valid) e.preventDefault();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>