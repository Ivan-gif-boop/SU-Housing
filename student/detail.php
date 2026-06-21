<?php
// student/detail.php
// FR-07: embedded Google Map + Distance Matrix travel times
// FR-10: feedback button links to feedback page for this hostel

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
// $hostelId = (int)($_GET['id'] ?? 0);
// if (!$hostelId) {
//   header('Location: /SU-Housing/student/browse.php');
//   exit;
// }
//
// $stmt = $db->prepare(
//   'SELECT * FROM hostel_listings
//    WHERE hostelId = ? AND isActive = 1'
// );
// $stmt->execute([$hostelId]);
// $hostel = $stmt->fetch();
//
// if (!$hostel) {
//   header('Location: /SU-Housing/student/browse.php');
//   exit;
// }
//
// $hostel['amenities'] = json_decode($hostel['amenities'], true);
//
// // Distance Matrix API called server-side here (FR-07)
// // Store result in $travelData = ['walking' => ..., 'transit' => ...]
// ─────────────────────────────────────────

// Frontend defaults
$pageTitle  = 'Keri Apartments';
$activePage = 'browse';
$userRole   = 'student';
$userName   = 'Ivan Wachira';

// Mock hostel data
$hostel = [
  'hostelId'        => 1,
  'hostelName'      => 'Keri Apartments',
  'physicalAddress' => 'Ole Shapara Avenue, Madaraka, Nairobi',
  'neighbourhood'   => 'Madaraka',
  'description'     => 'A well-maintained hostel just 5 minutes from
    Strathmore\'s main gate. Sunrise offers clean, secure single and
    double rooms with 24-hour water supply and fibre WiFi. The compound
    is fully fenced with CCTV coverage and a resident caretaker
    available Monday to Saturday. Rooms are self-contained with
    adequate natural lighting and ventilation.',
  'priceMin'        => 15500,
  'priceMax'        => 20000,
  'roomType'        => 'single',
  'roomsAvailable'  => 5,
  'amenities'       => [
    'WiFi', 'Water', 'Security', 'CCTV', 'Parking'
  ],
  'landlordName'    => 'Mr. Joseph Kariuki',
  'landlordPhone'   => '+254 712 345 678',
  'latitude'        => -1.3096,
  'longitude'       => 36.8122,
  'isActive'        => 1,
];

// Mock travel data — Michelle populates this from Distance Matrix API
$travelData = [
  'walking' => [
    'duration' => '12 mins',
    'distance' => '950 m',
    'via'      => 'Ole Shapara Ave',
  ],
  'transit' => [
    'duration' => '8 mins',
    'distance' => '1.1 km',
    'via'      => 'Matatu Route 34',
  ],
];

// Strathmore University coordinates (origin for Distance Matrix)
$strahtmoreCoords = [
  'lat' => -1.3096,
  'lng' => 36.8120,
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
        <span class="badge badge-green">● Active</span>
        <span class="tag tag-blue">
          📍 <?php echo htmlspecialchars($hostel['neighbourhood']); ?>
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
        📝 Submit Feedback
      </a>
    </div>
  </div>

  <div class="page-body">

    <div class="detail-layout">

      <!-- ════ LEFT: Main content ════ -->
      <div class="detail-main">

        <!-- ── Image placeholder ── -->
        <div class="detail-hero-img card mb-16">
          <div class="detail-img-main">
            <span style="font-size:72px;">🏠</span>
            <div class="detail-img-overlay"></div>
          </div>
        </div>

        <!-- ── Description ── -->
        <div class="card mb-16">
          <div class="card-header">
            <span class="card-title">Overview</span>
          </div>
          <div class="card-body">
            <p style="font-size:15px; color:var(--gray-600);
                      line-height:1.8;">
              <?php echo nl2br(htmlspecialchars($hostel['description'])); ?>
            </p>
          </div>
        </div>

        <!-- ── Amenities ── -->
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

        <!-- ── Map + Travel times (FR-07) ── -->
        <div class="card mb-16">
          <div class="card-header">
            <span class="card-title">
              Location &amp; Distance from Strathmore University
            </span>
          </div>
          <div class="card-body" style="padding:0;">

        <!-- Leaflet Map (FR-07) -->
        <!-- Leaflet renders here — JS below initialises it -->
        <div id="hostelMap"
            style="height:320px; width:100%; z-index:1;"
            data-lat="<?php echo $hostel['latitude']; ?>"
            data-lng="<?php echo $hostel['longitude']; ?>"
            data-name="<?php echo htmlspecialchars($hostel['hostelName']); ?>">
        </div>
            <!-- Travel times — populated by OSRM API call in detail.js -->
        <div class="travel-times" id="travelTimes">

        <div class="travel-item">
          <div class="travel-icon">🚶</div>
          <div class="travel-info">
            <div class="travel-mode">Walking</div>
            <div class="travel-duration" id="walkDuration">
          Calculating…
        </div>
        <div class="travel-via" id="walkDistance">
          from Strathmore University
        </div>
      </div>
    </div>

    <div class="travel-divider"></div>

    <div class="travel-item">
      <div class="travel-icon">🚌</div>
      <div class="travel-info">
        <div class="travel-mode">Driving</div>
        <div class="travel-duration" id="driveDuration">
          Calculating…
        </div>
        <div class="travel-via" id="driveDistance">
          from Strathmore University
        </div>
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

        <!-- ── Landlord contact ── -->
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
                  <a href="tel:<?php echo htmlspecialchars(
                    $hostel['landlordPhone']
                  ); ?>"
                     style="color:var(--amber); font-weight:600;">
                    <?php echo htmlspecialchars($hostel['landlordPhone']); ?>
                  </a>
                </div>
              </div>
            </div>
            <div class="alert alert-info"
                 style="margin-top:16px; font-size:13px;">
              ℹ️ Contact the landlord directly to arrange a viewing.
              Final accommodation arrangements are made offline.
            </div>
          </div>
        </div>

        <!-- ── Feedback CTA ── -->
        <div class="card">
          <div class="card-body"
               style="display:flex; align-items:center;
                      gap:16px; flex-wrap:wrap;">
            <div style="flex:1;">
              <div style="font-family:var(--font-display);
                           font-size:17px; color:var(--navy);
                           margin-bottom:4px;">
                Have you stayed here?
              </div>
              <div style="font-size:14px; color:var(--gray-600);">
                Share your experience to help other students and the
                Dean of Students office maintain listing quality.
              </div>
            </div>
            <a href="/SU-Housing/student/feedback.php?hostelId=<?php
                 echo $hostel['hostelId']; ?>"
               class="btn btn-primary"
               style="flex-shrink:0;">
              📝 Submit Feedback
            </a>
          </div>
        </div>

      </div><!-- end detail-main -->

      <!-- ════ RIGHT: Sticky summary panel ════ -->
      <aside class="detail-sidebar">

        <!-- Price + key details -->
        <div class="card detail-summary-card">
          <div class="card-body">

            <div class="detail-price">
              KES <?php echo number_format($hostel['priceMin']); ?>
                – <?php echo number_format($hostel['priceMax']); ?>
              
            </div>
            <div style="font-size:18px; color:var(--gray-600);
                         margin-bottom:20px;">
              per month
            </div>

            <a href="/SU-Housing/student/feedback.php?hostelId=<?php
                 echo $hostel['hostelId']; ?>"
               class="btn btn-primary btn-full mb-16">
              📝 Submit Feedback
            </a>

            <div style="font-size:12px; color:var(--gray-400);
                         text-align:center; margin-bottom:20px;">
              You won't be charged. Contact the landlord directly.
            </div>

            <hr class="divider"/>

            <!-- Key details -->
            <div class="summary-details">
              <div class="summary-detail-item">
                <div class="summary-detail-label">Neighbourhood</div>
                <div class="summary-detail-value">
                  <?php echo htmlspecialchars($hostel['neighbourhood']); ?>
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
                  <span class="badge badge-green">● Active</span>
                </div>
              </div>
              <div class="summary-detail-item">
                <div class="summary-detail-label">Address</div>
                <div class="summary-detail-value"
                     style="font-size:13px;">
                  <?php echo htmlspecialchars(
                    $hostel['physicalAddress']
                  ); ?>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Verified badge -->
        <div class="card"
             style="margin-top:16px; text-align:center;
                    padding:16px 20px;">
          <div style="font-size:24px; margin-bottom:8px;">🏛️</div>
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
?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
