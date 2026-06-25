<?php
session_start();

if (empty($_SESSION['adminId'])) {
    header('Location: /SU-Housing/login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
$db      = getDB();
$adminId = (int)$_SESSION['adminId'];

// Active listings count
$totalListings = (int)$db->query(
    'SELECT COUNT(*) FROM hostel_listings WHERE isActive = 1'
)->fetchColumn();

// Unclassified feedback count
$unclassifiedCount = (int)$db->query(
    'SELECT COUNT(*) FROM feedback WHERE classification IS NULL'
)->fetchColumn();

// Pending feedback list
$pendingFeedback = $db->query(
    'SELECT f.feedbackId, f.submissionText, f.submittedAt,
            f.suggestedClassification,
            h.hostelName,
            s.fullName, s.admissionNumber
     FROM feedback f
     JOIN hostel_listings h ON f.hostelId  = h.hostelId
     JOIN students        s ON f.studentId = s.studentId
     WHERE f.classification IS NULL
     ORDER BY f.submittedAt DESC
     LIMIT 10'
)->fetchAll();

// Sentiment analytics per hostel (FR-12)
$sentimentData = $db->query(
    'SELECT h.hostelName,
            SUM(CASE WHEN f.classification = "positive" THEN 1 ELSE 0 END) AS positive,
            SUM(CASE WHEN f.classification = "negative" THEN 1 ELSE 0 END) AS negative
     FROM hostel_listings h
     LEFT JOIN feedback f ON f.hostelId = h.hostelId
     WHERE h.isActive = 1
     GROUP BY h.hostelId, h.hostelName
     HAVING positive > 0 OR negative > 0
     ORDER BY (positive + negative) DESC'
)->fetchAll();

$totalClassified = array_sum(
    array_map(fn($r) => $r['positive'] + $r['negative'], $sentimentData)
);

$pageTitle  = 'Admin Dashboard';
$activePage = 'dashboard';
$userRole   = 'admin';
$userName   = $_SESSION['fullName'] ?? 'Admin';

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
      <a href="/SU-Housing/admin/listings.php" class="btn btn-primary">
        + Add New Listing
      </a>
    </div>
  </div>

  <div class="page-body">

    <!-- Stats row (FR-13) -->
    <div class="stats-grid">
      <div class="stat-card animate-fade-up delay-1">
        <div class="stat-icon amber">🏠</div>
        <div>
          <div class="stat-num"><?php echo $totalListings; ?></div>
          <div class="stat-label">Active Listings</div>
        </div>
      </div>
      <div class="stat-card animate-fade-up delay-2">
        <div class="stat-icon red">📋</div>
        <div>
          <div class="stat-num"><?php echo $unclassifiedCount; ?></div>
          <div class="stat-label">Unclassified Feedback</div>
        </div>
      </div>
      <div class="stat-card animate-fade-up delay-3">
        <div class="stat-icon green">✓</div>
        <div>
          <div class="stat-num"><?php echo $totalClassified; ?></div>
          <div class="stat-label">Classified Feedback</div>
        </div>
      </div>
    </div>

    <!-- Sentiment Analytics (FR-12) -->
    <div class="section-header">
      <div>
        <h2 class="section-title">Sentiment Analytics</h2>
        <p class="section-subtitle">
          Positive vs negative feedback per hostel listing
        </p>
      </div>
      <a href="/SU-Housing/admin/feedback.php" class="btn btn-ghost">
        View all feedback →
      </a>
    </div>

    <div class="card mb-16">
      <div class="card-body">
        <?php if (empty($sentimentData)): ?>
          <div class="empty-state" style="padding:40px 24px;">
            <div class="empty-icon">📊</div>
            <h3>No classified feedback yet</h3>
            <p>Classify student feedback below to generate sentiment analytics.</p>
          </div>
        <?php else: ?>
          <div style="position:relative; height:320px;">
            <canvas id="sentimentChart"></canvas>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Unclassified Feedback (FR-11, FR-13) -->
    <div class="section-header">
      <div>
        <h2 class="section-title">
          Unclassified Feedback
          <?php if ($unclassifiedCount > 0): ?>
            <span class="nav-badge" style="font-size:13px; padding:3px 10px;
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
        <div class="feedback-classify-card" id="fb-<?php echo $fb['feedbackId']; ?>">
          <div class="fbc-header">
            <div>
              <div class="fbc-hostel">
                <?php echo htmlspecialchars($fb['hostelName']); ?>
              </div>
              <div class="fbc-meta">
                <?php echo htmlspecialchars($fb['fullName']); ?>
                · Admission: <?php echo htmlspecialchars($fb['admissionNumber']); ?>
                · <?php echo date('j M Y', strtotime($fb['submittedAt'])); ?>
              </div>
            </div>
            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:4px;">
              <span class="badge badge-amber">Pending</span>
              <?php if (!empty($fb['suggestedClassification'])): ?>
                <span class="badge <?php echo $fb['suggestedClassification'] === 'positive' ? 'badge-green' : ($fb['suggestedClassification'] === 'negative' ? 'badge-red' : 'badge-gray'); ?>"
                      style="font-size:11px;">
                  AI: <?php echo ucfirst($fb['suggestedClassification']); ?>
                </span>
              <?php endif; ?>
            </div>
          </div>

          <p class="fbc-text">
            "<?php echo nl2br(htmlspecialchars($fb['submissionText'])); ?>"
          </p>

          <div class="fbc-actions">
            <button class="btn btn-success btn-sm"
              onclick="classifyFeedback(<?php echo $fb['feedbackId']; ?>, 'positive')">
              ✓ Positive
            </button>
            <button class="btn btn-danger btn-sm"
              onclick="classifyFeedback(<?php echo $fb['feedbackId']; ?>, 'negative')">
              ✗ Negative
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>

<?php
$sentimentJson = json_encode($sentimentData);
$extraScripts  = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
    '/SU-Housing/assets/js/dashboard.js',
];
?>
<script>
window.SENTIMENT_DATA = <?php echo $sentimentJson; ?>;

async function classifyFeedback(feedbackId, classification) {
    try {
        const response = await fetch(
            `/SU-Housing/api/admin/feedback.php?id=${feedbackId}`, {
            method: 'PATCH',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ classification })
        });

        const data = await response.json();

        if (response.ok) {
            // Remove the card from the UI
            const card = document.getElementById('fb-' + feedbackId);
            if (card) {
                card.style.opacity = '0';
                card.style.transition = 'opacity 0.3s';
                setTimeout(() => {
                    card.remove();
                    // Reload to update counts and chart
                    window.location.reload();
                }, 300);
            }
        } else {
            alert(data.error || 'Failed to classify feedback.');
        }
    } catch (err) {
        alert('Network error. Please try again.');
        console.error('Classify error:', err);
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
