<?php
session_start();

if (empty($_SESSION['studentId'])) {
    header('Location: /SU-Housing/login.php');
    exit;
}

$hostelId = (int)($_GET['id'] ?? 0);
if (!$hostelId) {
    header('Location: /SU-Housing/student/browse.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
$db = getDB();

$stmt = $db->prepare(
    'SELECT * FROM hostel_listings WHERE hostelId = ? AND isActive = 1'
);
$stmt->execute([$hostelId]);
$hostel = $stmt->fetch();

if (!$hostel) {
    header('Location: /SU-Housing/student/browse.php');
    exit;
}

$hostel['amenities']     = json_decode($hostel['amenities'], true);
$hostel['location'] = $hostel['physicalAddress'];
$hostel['landlordName']  = $hostel['landlordName'];
$hostel['landlordPhone'] = $hostel['landlordContact'];

$travelData = [];

$pageTitle  = $hostel['hostelName'];
$activePage = 'browse';
$userRole   = 'student';
$userName   = $_SESSION['fullName'] ?? 'Student';
$usesMap    = true;

$strahtmoreCoords = [
    'lat' => -1.3100,
    'lng' => 36.8126,
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

  <!-- ── Back nav + page header ── -->
  <div class="page-header">
    <div class="page-title-group">
      <div class="page-breadcrumb">
        <a href="/SU-Housing/student/browse.php"
           style="color:var(--amber);">
          ← Back to Listings
        </a>
      </div>
      <h1 class="page-title">
        <?php echo htmlspecialchars($hostel['hostelName']); ?>
      </h1>
      <div style="display:flex; align-items:center;
                  gap:10px; margin-top:6px; flex-wrap:wrap;">
        <span class="badge badge-green">Active</span>
        <span class="tag tag-blue">
          📍 <?php echo htmlspecialchars($hostel['location']); ?>
        </span>
        <span class="tag tag-gray">
          <?php echo ucfirst($hostel['roomType']); ?> Room
        </span>
      </div>
    </div>
    <div class="page-actions">
      <a href="/SU-Housing/student/feedback.php?hostelId=<?php
           echo $hostel['hostelId']; ?>"
         class="btn btn-primary">
        Submit Feedback
      </a>
    </div>
  </div>

  <div class="page-body">
    <div class="detail-layout">

      <!-- ════ LEFT: Main content ════ -->
      <div class="detail-main">

        <!-- Image placeholder -->
        <!-- Hero image -->
      <div class="detail-hero-img card mb-16">
        <div class="detail-img-main">
          <?php if (!empty($hostel['imagePath'])): ?>
            <img src="<?php echo htmlspecialchars($hostel['imagePath']); ?>"
                alt="<?php echo htmlspecialchars($hostel['hostelName']); ?>"
                style="width:100%; height:100%; object-fit:cover; border-radius:inherit;"/>
          <?php else: ?>
            <span style="font-size:72px;">🏠</span>
          <?php endif; ?>
          <div class="detail-img-overlay"></div>
        </div>
      </div>

        <!-- Description -->
        <div class="card mb-16">
          <div class="card-header">
            <span class="card-title">Overview</span>
          </div>
          <div class="card-body">
            <p style="font-size:15px; color:var(--gray-600); line-height:1.8;">
              <?php echo nl2br(htmlspecialchars($hostel['description'])); ?>
            </p>
          </div>
        </div>

        <!-- Amenities -->
        <div class="card mb-16">
          <div class="card-header">
            <span class="card-title">Amenities &amp; Features</span>
          </div>
          <div class="card-body">
            <div class="amenities-grid">
              <?php foreach ($hostel['amenities'] as $amenity): ?>
                <div class="amenity-item">
                  <span class="amenity-check">✓</span>
                  <?php echo htmlspecialchars($amenity); ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Map + Travel times (FR-07) -->
        <div class="card mb-16">
          <div class="card-header">
            <span class="card-title">
              Location &amp; Distance from Strathmore University
            </span>
          </div>
          <div class="card-body" style="padding:0;">
            <div id="hostelMap"
                 style="height:320px; width:100%; z-index:1;"
                 data-lat="<?php echo $hostel['latitude']; ?>"
                 data-lng="<?php echo $hostel['longitude']; ?>"
                 data-name="<?php echo htmlspecialchars($hostel['hostelName']); ?>">
            </div>
            <div class="travel-times" id="travelTimes">
              <div class="travel-item">
                <div class="travel-icon">🚶</div>
                <div class="travel-info">
                  <div class="travel-mode">Walking</div>
                  <div class="travel-duration" id="walkDuration">Calculating…</div>
                  <div class="travel-via" id="walkDistance">from Strathmore University</div>
                </div>
              </div>
              <div class="travel-divider"></div>
              <div class="travel-item">
                <div class="travel-icon">🚌</div>
                <div class="travel-info">
                  <div class="travel-mode">Driving</div>
                  <div class="travel-duration" id="driveDuration">Calculating…</div>
                  <div class="travel-via" id="driveDistance">from Strathmore University</div>
                </div>
              </div>
            </div>
            <p style="font-size:11px; color:var(--gray-400);
                       padding:0 20px 14px; text-align:center;">
              Travel times from Strathmore University main gate
              via OSRM. Map via Leaflet.js &amp; OpenStreetMap.
            </p>
          </div>
        </div>

        <!-- Landlord contact -->
        <div class="card mb-16">
          <div class="card-header">
            <span class="card-title">Landlord Contact</span>
          </div>
          <div class="card-body">
            <div class="contact-grid">
              <div class="contact-item">
                <div class="contact-label">Name</div>
                <div class="contact-value">
                  <?php echo htmlspecialchars($hostel['landlordName']); ?>
                </div>
              </div>
              <div class="contact-item">
                <div class="contact-label">Phone</div>
                <div class="contact-value">
                  <a href="tel:<?php echo htmlspecialchars($hostel['landlordPhone']); ?>"
                     style="color:var(--amber); font-weight:600;">
                    <?php echo htmlspecialchars($hostel['landlordPhone']); ?>
                  </a>
                </div>
              </div>
            </div>
            <div class="alert alert-info" style="margin-top:16px; font-size:13px;">
               Contact the landlord directly to arrange a viewing.
            </div>
          </div>
        </div>

        <!-- Feedback CTA -->
        <div class="card">
          <div class="card-body"
               style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
            <div style="flex:1;">
              <div style="font-family:var(--font-display); font-size:17px;
                           color:var(--navy); margin-bottom:4px;">
                Have you stayed here?
              </div>
              <div style="font-size:14px; color:var(--gray-600);">
                Share your experience to help other students and the
                Dean of Students office maintain listing quality.
              </div>
            </div>
            <a href="/SU-Housing/student/feedback.php?hostelId=<?php
                 echo $hostel['hostelId']; ?>"
               class="btn btn-primary" style="flex-shrink:0;">
              Submit Feedback
            </a>
          </div>
        </div>

      </div><!-- end detail-main -->

      <!-- RIGHT: Sticky summary panel -->
      <aside class="detail-sidebar">
        <div class="card detail-summary-card">
          <div class="card-body">
            <div class="detail-price">
              KES <?php echo number_format($hostel['priceMin']); ?>
              – <?php echo number_format($hostel['priceMax']); ?>
            </div>
            <div style="font-size:18px; color:var(--gray-600); margin-bottom:20px;">
              per month
            </div>
            <a href="/SU-Housing/student/feedback.php?hostelId=<?php
                 echo $hostel['hostelId']; ?>"
               class="btn btn-primary btn-full mb-16">
               Submit Feedback
            </a>
            <div style="font-size:12px; color:var(--gray-400);
                         text-align:center; margin-bottom:20px;">
              You won't be charged. Contact the landlord directly.
            </div>
            <hr class="divider"/>
            <div class="summary-details">
              <div class="summary-detail-item">
                <div class="summary-detail-label">Location</div>
                <div class="summary-detail-value">
                  <?php echo htmlspecialchars($hostel['location']); ?>
                </div>
              </div>
              <div class="summary-detail-item">
                <div class="summary-detail-label">Room Type</div>
                <div class="summary-detail-value">
                  <?php echo ucfirst($hostel['roomType']); ?>
                </div>
              </div>
              <div class="summary-detail-item">
                <div class="summary-detail-label">Rooms Available</div>
                <div class="summary-detail-value">
                  <?php echo $hostel['roomsAvailable']; ?>
                </div>
              </div>
              <div class="summary-detail-item">
                <div class="summary-detail-label">Status</div>
                <div class="summary-detail-value">
                  <span class="badge badge-green">Active</span>
                </div>
              </div>
              <div class="summary-detail-item">
                <div class="summary-detail-label">Address</div>
                <div class="summary-detail-value" style="font-size:13px;">
                  <?php echo htmlspecialchars($hostel['physicalAddress']); ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card" style="margin-top:16px; text-align:center; padding:16px 20px;">
          <div style="font-size:24px; margin-bottom:8px;"></div>
          <div style="font-size:13px; font-weight:600;
                       color:var(--navy); margin-bottom:4px;">
            Verified by Dean of Students
          </div>
          <div style="font-size:12px; color:var(--gray-600);">
            This listing has been physically assessed and approved
            by the Strathmore University Mentorship Office.
          </div>
        </div>
      </aside>

    </div><!-- end detail-layout -->
  </div><!-- end page-body -->

<?php
$extraScripts = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    '/SU-Housing/assets/js/detail.js',
];
include __DIR__ . '/../includes/footer.php';
?>
