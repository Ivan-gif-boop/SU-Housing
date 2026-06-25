<?php
// student/browse.php
// FR-05: keyword search
// FR-06: filter by location, price range, room type, amenities
// FR-09: preference match banner if student has a profile

session_start();

require_once __DIR__ . '/../includes/auth.php';
requireStudent();

require_once __DIR__ . '/../config/db.php';
$db = getDB();

$studentId = currentStudentId();
$userName  = $_SESSION['fullName'] ?? 'Student';

// ── Check if student has a preference profile (FR-09) ──
$profStmt = $db->prepare(
    'SELECT * FROM student_preference_profiles WHERE studentId = ?'
);
$profStmt->execute([$studentId]);
$profile    = $profStmt->fetch();
$hasProfile = (bool) $profile;

// ── Build filter conditions from GET params ──
$conditions = ['l.isActive = 1'];
$params     = [];

if (!empty($_GET['q'])) {
    $conditions[] = '(l.hostelName LIKE ? OR l.physicalAddress LIKE ? OR l.description LIKE ?)';
    $like         = '%' . $_GET['q'] . '%';
    $params       = array_merge($params, [$like, $like, $like]);
}
if (!empty($_GET['location'])) {
    $conditions[] = 'l.neighbourhood LIKE ?';
    $params[]     = '%' . $_GET['location'] . '%';
}
if (!empty($_GET['priceMin'])) {
    $conditions[] = 'l.priceMax >= ?';
    $params[]     = (float) $_GET['priceMin'];
}
if (!empty($_GET['priceMax'])) {
    $conditions[] = 'l.priceMin <= ?';
    $params[]     = (float) $_GET['priceMax'];
}
if (!empty($_GET['roomType'])) {
    $conditions[] = 'l.roomType = ?';
    $params[]     = $_GET['roomType'];
}
if (!empty($_GET['amenities'])) {
    foreach (explode(',', $_GET['amenities']) as $amenity) {
        $conditions[] = 'JSON_CONTAINS(l.amenities, ?)';
        $params[]     = json_encode(trim($amenity));
    }
}

$where = implode(' AND ', $conditions);
$stmt  = $db->prepare(
    "SELECT hostelId, hostelName, physicalAddress,
            priceMin, priceMax, roomType, amenities, roomsAvailable,
            latitude, longitude
     FROM hostel_listings l
     WHERE $where
     ORDER BY createdAt DESC"
);
$stmt->execute($params);
$listings = $stmt->fetchAll();

// Decode amenities + score against profile (FR-09)
foreach ($listings as &$h) {
    $h['amenities']  = json_decode($h['amenities'], true) ?? [];
    // 'physicalAddress' alias so browse.js data-attribute works
    $h['physicalAddress'] = $h['physicalAddress'];
    $h['matchScore'] = 0;

    if ($profile) {
        $score    = 0;
        $maxScore = 0;

        if ($profile['budgetMin'] !== null && $profile['budgetMax'] !== null) {
            $maxScore++;
            if ($h['priceMin'] <= $profile['budgetMax'] &&
                $h['priceMax'] >= $profile['budgetMin']) {
                $score++;
            }
        }
        if ($profile['roomTypePreference'] !== null) {
            $maxScore++;
            if ($h['roomType'] === $profile['roomTypePreference']) $score++;
        }
        if ($profile['genderPreference'] !== null && isset($h['genderPolicy'])) {
            $maxScore++;
            if ($h['genderPolicy'] === $profile['genderPreference']) $score++;
        }

        $h['matchScore'] = $maxScore > 0
            ? (int) round(($score / $maxScore) * 100)
            : 0;
    }
}
unset($h);

// Sort by match score if profile exists
if ($hasProfile) {
    usort($listings, fn($a, $b) => $b['matchScore'] <=> $a['matchScore']);
}

// ── Page meta ──
$pageTitle  = 'Browse Hostels';
$activePage = 'browse';
$userRole   = 'student';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

  <!-- ── Page header ── -->
  <div class="page-header">
    <div class="page-title-group">
      <div class="page-breadcrumb">Home › Browse</div>
      <h1 class="page-title">Browse Hostels</h1>
      <p class="page-subtitle">
        <?php echo count($listings); ?> verified listings
        from the Office of the Dean of Students
      </p>
    </div>
  </div>

  <div class="page-body">

    <!-- ── Preference match banner (FR-09) ── -->
    <?php if ($hasProfile): ?>
      <div class="alert alert-success mb-24" style="align-items:center;">
        <span style="font-size:18px;">✓</span>
        <div style="flex:1;">
          Results are ranked by match with your
          <strong>preference profile</strong>.
        </div>
        <a href="/SU-Housing/student/preference_profile.php"
           class="btn btn-ghost btn-sm"
           style="flex-shrink:0; color:var(--green);">
          Edit profile
        </a>
      </div>
    <?php else: ?>
      <div class="alert alert-info mb-24" style="align-items:center;">
        <span style="font-size:18px;">💡</span>
        <div style="flex:1;">
          <strong>Get personalised recommendations.</strong>
          Set up your preference profile to see match percentages
          on each listing.
        </div>
        <a href="/SU-Housing/student/preference_profile.php?new=0"
           class="btn btn-navy btn-sm" style="flex-shrink:0;">
          Set Up Profile
        </a>
      </div>
    <?php endif; ?>

    <!-- ── Browse layout: filter panel + grid ── -->
    <div class="browse-layout">

      <!-- ════ LEFT: Filter panel ════ -->
      <aside class="filter-panel" id="filterPanel">

        <div class="filter-panel-header">
          <span class="filter-panel-title">Filters</span>
          <button class="btn btn-ghost btn-sm"
                  id="clearFiltersBtn"
                  onclick="clearFilters()">
            Clear all
          </button>
        </div>

        <!-- Search -->
        <div class="filter-group">
          <label class="filter-label">Search</label>
          <div class="input-wrap">
            <span class="input-icon">🔍</span>
            <input
              type="text"
              id="filterSearch"
              class="form-control"
              placeholder="Search by name…"
              oninput="applyFilters()"
            />
          </div>
        </div>

        <!-- Neighbourhood / Location -->
        <div class="filter-group">
          <label class="filter-label">Neighbourhood</label>
          <select id="filterNeighbourhood"
                  class="form-control"
                  onchange="applyFilters()">
            <option value="">All Neighbourhoods</option>
            <?php
            // Build neighbourhood options dynamically from DB results
            $neighbourhoods = array_unique(
                array_column($listings, 'neighbourhood')
            );
            sort($neighbourhoods);
            foreach ($neighbourhoods as $nbh):
            ?>
              <option value="<?php echo htmlspecialchars($nbh); ?>">
                <?php echo htmlspecialchars($nbh); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Price range -->
        <div class="filter-group">
          <label class="filter-label">Price Range (KES/month)</label>
          <div style="display:flex; gap:8px; align-items:center;">
            <input
              type="number"
              id="filterPriceMin"
              class="form-control"
              placeholder="Min"
              min="0"
              oninput="applyFilters()"
              style="flex:1;"
            />
            <span style="color:var(--gray-400);font-size:13px;">to</span>
            <input
              type="number"
              id="filterPriceMax"
              class="form-control"
              placeholder="Max"
              min="0"
              oninput="applyFilters()"
              style="flex:1;"
            />
          </div>
        </div>

        <!-- Room type -->
        <div class="filter-group">
          <label class="filter-label">Room Type</label>
          <select id="filterRoomType"
                  class="form-control"
                  onchange="applyFilters()">
            <option value="">Any Room Type</option>
            <option value="single">Single</option>
            <option value="shared">Shared</option>
            <option value="ensuite">Ensuite</option>
            <option value="studio">Studio</option>
          </select>
        </div>

        <!-- Amenities -->
        <div class="filter-group">
          <label class="filter-label">Amenities</label>
          <div class="checkbox-list">
            <?php
            $amenityOptions = [
              'WiFi', 'Water', 'Security',
              'Parking', 'Laundry',
              'Backup Power', 'Gym', 'CCTV',
            ];
            foreach ($amenityOptions as $a):
            ?>
              <label class="checkbox-option">
                <input
                  type="checkbox"
                  class="amenity-filter"
                  value="<?php echo htmlspecialchars($a); ?>"
                  onchange="applyFilters()"
                />
                <span class="checkbox-box"></span>
                <?php echo htmlspecialchars($a); ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <button class="btn btn-outline btn-full" onclick="clearFilters()">
          Clear Filters
        </button>

      </aside>

      <!-- ════ RIGHT: Results ════ -->
      <div class="browse-results">

        <!-- Results count + sort -->
        <div class="results-bar">
          <span class="results-count" id="resultsCount">
            Showing <strong><?php echo count($listings); ?></strong> results
          </span>
          <div style="display:flex; align-items:center; gap:8px;">
            <label style="font-size:13px;color:var(--gray-600);">Sort by</label>
            <select id="sortSelect"
                    class="form-control"
                    onchange="applyFilters()"
                    style="width:auto; padding:7px 12px; font-size:13px;">
              <?php if ($hasProfile): ?>
                <option value="match">Best Match</option>
              <?php endif; ?>
              <option value="price_asc">Price: Low to High</option>
              <option value="price_desc">Price: High to Low</option>
              <option value="name">Name A–Z</option>
            </select>
          </div>
        </div>

        <!-- Hostel grid -->
        <div class="hostel-grid" id="hostelGrid">
          <?php foreach ($listings as $h): ?>
            <div class="hostel-card animate-fade-up"
                 data-name="<?php echo strtolower(htmlspecialchars($h['hostelName'])); ?>"
                 data-physical-address="<?php echo htmlspecialchars($h['physicalAddress']); ?>"
                 data-price-min="<?php echo $h['priceMin']; ?>"
                 data-price-max="<?php echo $h['priceMax']; ?>"
                 data-room-type="<?php echo $h['roomType']; ?>"
                 data-amenities="<?php echo htmlspecialchars(implode(',', $h['amenities'])); ?>"
                 data-match="<?php echo $h['matchScore']; ?>"
            >

              <div class="hostel-card-img">
                <div class="hostel-card-img-inner">
                  <span class="hostel-card-emoji">🏠</span>
                </div>
                <?php if ($hasProfile): ?>
                  <span class="match-badge">
                    <?php echo $h['matchScore']; ?>% match
                  </span>
                <?php endif; ?>
                <span class="hostel-price-badge">
                  KES <?php echo number_format($h['priceMin']); ?>
                  – <?php echo number_format($h['priceMax']); ?>/mo
                </span>
              </div>

              <div class="hostel-card-body">
                <h3 class="hostel-name">
                  <?php echo htmlspecialchars($h['hostelName']); ?>
                </h3>
                <div class="hostel-location">
                  📍 <?php echo htmlspecialchars($h['physicalAddress']); ?>
                  <span class="hostel-rooms-pill">
                    · <?php echo $h['roomsAvailable']; ?> rooms
                  </span>
                </div>
                <div class="hostel-amenities">
                  <?php foreach (array_slice($h['amenities'], 0, 3) as $a): ?>
                    <span class="tag tag-blue">
                      <?php echo htmlspecialchars($a); ?>
                    </span>
                  <?php endforeach; ?>
                  <?php if (count($h['amenities']) > 3): ?>
                    <span class="tag tag-gray">
                      +<?php echo count($h['amenities']) - 3; ?> more
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="hostel-card-footer">
                <span class="tag tag-gray">
                  <?php echo ucfirst($h['roomType']); ?>
                </span>
                <a href="/SU-Housing/student/detail.php?id=<?php echo $h['hostelId']; ?>"
                   class="btn btn-primary btn-sm">
                  View →
                </a>
              </div>

            </div>
          <?php endforeach; ?>
        </div>

        <!-- Empty state -->
        <div class="empty-state" id="emptyState"
             style="display:<?php echo empty($listings) ? 'flex' : 'none'; ?>;">
          <div class="empty-icon">🔍</div>
          <h3>No hostels found</h3>
          <p>Try adjusting your filters or search term.</p>
          <button class="btn btn-outline mt-16" onclick="clearFilters()">
            Clear Filters
          </button>
        </div>

      </div><!-- end browse-results -->

    </div><!-- end browse-layout -->

  </div><!-- end page-body -->

<?php
$extraScripts = ['/SU-Housing/assets/js/browse.js'];
include __DIR__ . '/../includes/footer.php';
?>