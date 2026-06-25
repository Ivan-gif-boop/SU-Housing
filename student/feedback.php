<?php
session_start();

if (empty($_SESSION['studentId'])) {
    header('Location: /SU-Housing/login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
$db        = getDB();
$studentId = (int)$_SESSION['studentId'];

// Pre-selected hostel from detail page link (?hostelId=X)
$preselectedHostelId = (int)($_GET['hostelId'] ?? 0);

// Load all active hostels for dropdown
$hostels = $db->query(
    'SELECT hostelId, hostelName FROM hostel_listings
     WHERE isActive = 1 ORDER BY hostelName ASC'
)->fetchAll();

// Load student's previously submitted feedback
$stmt = $db->prepare(
    'SELECT f.feedbackId, f.submissionText, f.submittedAt,
            f.classification as sentiment, f.adminResponse,
            h.hostelName
     FROM feedback f
     JOIN hostel_listings h ON f.hostelId = h.hostelId
     WHERE f.studentId = ?
     ORDER BY f.submittedAt DESC'
);
$stmt->execute([$studentId]);
$myFeedback = $stmt->fetchAll();

$pageTitle  = 'My Feedback';
$activePage = 'feedback';
$userRole   = 'student';
$userName   = $_SESSION['fullName'] ?? 'Student';

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

            <div class="alert alert-info mb-16" id="formAlert" style="display:none;"></div>

            <div class="alert alert-info mb-16">
              ℹ️ Only submit feedback about a hostel you have
              personally occupied. Your report will be reviewed
              by the Office of the Dean of Students.
            </div>

            <form action="#" method="POST" id="feedbackForm" novalidate>

              <div class="form-group">
                <label for="hostelId">Hostel</label>
                <select id="hostelId" name="hostelId"
                  class="form-control" required>
                  <option value="" disabled
                    <?php echo !$preselectedHostelId ? 'selected' : ''; ?>>
                    Select a hostel…
                  </option>
                  <?php foreach ($hostels as $h): ?>
                    <option value="<?php echo $h['hostelId']; ?>"
                      <?php echo $h['hostelId'] === $preselectedHostelId ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($h['hostelName']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-error" id="err-hostelId"></div>
              </div>

              <div class="form-group">
                <label for="submissionText">Your Feedback</label>
                <textarea id="submissionText" name="submissionText"
                  class="form-control"
                  placeholder="Describe your experience — the accuracy of the listing, the condition of the property, any maintenance issues, safety concerns, or general observations…"
                  rows="7" required minlength="30"></textarea>
                <div class="form-hint">Minimum 30 characters. Be specific and factual.</div>
                <div class="form-error" id="err-submissionText"></div>
                <div class="char-counter" id="charCounter">0 / 30 minimum</div>
              </div>

              <button type="submit" class="btn btn-primary btn-full">
                Submit Feedback →
              </button>

            </form>

          </div>
        </div>
      </div>

      <!-- ════ RIGHT: Past submissions ════ -->
      <div class="feedback-history-col">

        <div class="section-header">
          <div>
            <h2 class="section-title">My Submissions</h2>
            <p class="section-subtitle">Feedback you have previously submitted</p>
          </div>
          <span class="badge badge-blue"><?php echo count($myFeedback); ?></span>
        </div>

        <?php if (empty($myFeedback)): ?>
          <div class="empty-state">
            <div class="empty-icon">📝</div>
            <h3>No feedback yet</h3>
            <p>Submit your first feedback report using the form on the left.</p>
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
                    Submitted <?php echo date('j M Y', strtotime($fb['submittedAt'])); ?>
                  </div>
                </div>
                <?php if ($fb['sentiment'] === 'positive'): ?>
                  <span class="badge badge-green">✓ Positive</span>
                <?php elseif ($fb['sentiment'] === 'negative'): ?>
                  <span class="badge badge-red">✗ Negative</span>
                <?php else: ?>
                  <span class="badge badge-gray">Pending Review</span>
                <?php endif; ?>
              </div>
              <p class="feedback-text">
                <?php echo nl2br(htmlspecialchars($fb['submissionText'])); ?>
              </p>
              <?php if (!empty($fb['adminResponse'])): ?>
                <div class="alert alert-info" style="margin-top:12px; font-size:13px;">
                  <strong>Dean of Students Response:</strong><br>
                  <?php echo nl2br(htmlspecialchars($fb['adminResponse'])); ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>
    </div>
  </div>

<script>
const form        = document.getElementById('feedbackForm');
const textArea    = document.getElementById('submissionText');
const charCounter = document.getElementById('charCounter');
const formAlert   = document.getElementById('formAlert');
const MIN_LENGTH  = 30;

textArea?.addEventListener('input', () => {
    const len = textArea.value.trim().length;
    charCounter.textContent = `${len} / ${MIN_LENGTH} minimum`;
    charCounter.style.color = len >= MIN_LENGTH ? 'var(--green)' : 'var(--gray-400)';
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
    document.getElementById(id)?.addEventListener('input',  () => clearError(id));
    document.getElementById(id)?.addEventListener('change', () => clearError(id));
});

form?.addEventListener('submit', async function(e) {
    e.preventDefault();
    let valid = true;

    const hostelVal = document.getElementById('hostelId').value;
    if (!hostelVal) {
        showError('hostelId', 'Please select a hostel.');
        valid = false;
    }

    const text = textArea.value.trim();
    if (!text) {
        showError('submissionText', 'Feedback text is required.');
        valid = false;
    } else if (text.length < MIN_LENGTH) {
        showError('submissionText', `Please write at least ${MIN_LENGTH} characters.`);
        valid = false;
    }

    if (!valid) return;

    try {
        const response = await fetch('/SU-Housing/api/feedback.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                hostelId:       parseInt(hostelVal),
                submissionText: text
            })
        });

        const data = await response.json();

        if (response.ok) {
            // Show success and reload to show new feedback in list
            formAlert.style.display = 'flex';
            formAlert.className     = 'alert alert-success mb-16';
            formAlert.textContent   = '✓ Feedback submitted successfully!';
            form.reset();
            charCounter.textContent = '0 / 30 minimum';
            // Reload after 1.5 seconds to show updated list
            setTimeout(() => window.location.reload(), 1500);
        } else {
            formAlert.style.display = 'flex';
            formAlert.className     = 'alert alert-error mb-16';
            formAlert.textContent   = '⚠️ ' + (data.error || 'Failed to submit feedback.');
        }

    } catch (err) {
        formAlert.style.display = 'flex';
        formAlert.className     = 'alert alert-error mb-16';
        formAlert.textContent   = '⚠️ Network error. Please try again.';
        console.error('Feedback error:', err);
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
