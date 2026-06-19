<?php
// student/browse.php
// FR-05: keyword search
// FR-06: filter by neighbourhood, price range, room type, amenities
// FR-09: preference match banner if student has a profile

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
// // Check if student has preference profile (FR-09)
// $stmt = $db->prepare(
//   'SELECT profileId FROM student_preference_profiles WHERE userId = ?'
// );
// $stmt->execute([$_SESSION['user_id']]);
// $hasProfile = (bool) $stmt->fetch();
//
// // Fetch listings — search + filter applied server side
// // See api/listings/index.php for full query logic
// // $listings comes from the API response
// ─────────────────────────────────────────

// Frontend defaults
$pageTitle  = 'Browse Hostels';
$activePage = 'browse';
$userRole   = 'student';
$userName   = 'Ivan Wachira';
$hasProfile = true; // toggle to false to test no-profile state

// Mock listings
$listings = [
  [
    'hostelId'      => 1,
    'hostelName'    => 'Keri Apartments',
    'neighbourhood' => 'Madaraka',
    'priceMin'      => 15500,
    'priceMax'      => 20000,
    'roomType'      => 'single',
    'roomsAvailable'=> 5,
    'amenities'     => ['WiFi', 'Water', 'Security', 'CCTV'],
    'isActive'      => 1,
    'matchScore'    => 92,
  ],
  [
    'hostelId'      => 2,
    'hostelName'    => 'Nyaya Lodge',
    'neighbourhood' => 'Nairobi West',
    'priceMin'      => 10000,
    'priceMax'      => 15000,
    'roomType'      => 'studio',
    'roomsAvailable'=> 8,
    'amenities'     => ['WiFi', 'Security', 'Parking', 'Gym'],
    'isActive'      => 1,
    'matchScore'    => 85,
  ],
  [
    'hostelId'      => 3,
    'hostelName'    => 'Green Park Residences',
    'neighbourhood' => "Lang'ata",
    'priceMin'      => 6500,
    'priceMax'      => 9000,
    'roomType'      => 'shared',
    'roomsAvailable'=> 12,
    'amenities'     => ['Water', 'Security', 'Laundry'],
    'isActive'      => 1,
    'matchScore'    => 78,
  ],
  [
    'hostelId'      => 4,
    'hostelName'    => 'Westview Apartments',
    'neighbourhood' => 'Nairobi West',
    'priceMin'      => 12000,
    'priceMax'      => 18000,
    'roomType'      => 'ensuite',
    'roomsAvailable'=> 3,
    'amenities'     => ['WiFi', 'Water', 'Security', 'Parking', 'Backup Power'],
    'isActive'      => 1,
    'matchScore'    => 71,
  ],
  [
    'hostelId'      => 5,
    'hostelName'    => 'Madaraka Heights',
    'neighbourhood' => 'Madaraka',
    'priceMin'      => 5000,
    'priceMax'      => 7500,
    'roomType'      => 'single',
    'roomsAvailable'=> 20,
    'amenities'     => ['Water', 'Security', 'WiFi'],
    'isActive'      => 1,
    'matchScore'    => 65,
  ],
  [
    'hostelId'      => 6,
    'hostelName'    => "Lang'ata Court",
    'neighbourhood' => "Lang'ata",
    'priceMin'      => 9000,
    'priceMax'      => 13000,
    'roomType'      => 'ensuite',
    'roomsAvailable'=> 6,
    'amenities'     => ['WiFi', 'Water', 'Laundry', 'Security', 'CCTV'],
    'isActive'      => 1,
    'matchScore'    => 60,
  ],
];

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
      <div class="alert alert-success mb-24"
           style="align-items:center;">
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
      <div class="alert alert-info mb-24"
           style="align-items:center;">
        <span style="font-size:18px;">💡</span>
        <div style="flex:1;">
          <strong>Get personalised recommendations.</strong>
          Set up your preference profile to see match percentages
          on each listing.
        </div>
        <a href="/SU-Housing/student/preference_profile.php?new=0"
           class="btn btn-navy btn-sm"
           style="flex-shrink:0;">
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

        <!-- Neighbourhood -->
        <div class="filter-group">
          <label class="filter-label">Neighbourhood</label>
          <select id="filterNeighbourhood"
                  class="form-control"
                  onchange="applyFilters()">
            <option value="">All Neighbourhoods</option>
            <option value="Madaraka">Madaraka</option>
            <option value="Nairobi West">Nairobi West</option>
            <option value="Lang'ata">Lang'ata</option>
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
              'Backup Power', 'Gym',
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

        <button class="btn btn-outline btn-full"
                onclick="clearFilters()">
          Clear Filters
        </button>

      </aside>

      <!-- ════ RIGHT: Results ════ -->
      <div class="browse-results">

        <!-- Results count + sort -->
        <div class="results-bar">
          <span class="results-count" id="resultsCount">
            Showing
            <strong><?php echo count($listings); ?></strong>
            results
          </span>
          <div style="display:flex; align-items:center; gap:8px;">
            <label style="font-size:13px;color:var(--gray-600);">
              Sort by
            </label>
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
            <?php
            $amenities = is_string($h['amenities'])
              ? json_decode($h['amenities'], true)
              : $h['amenities'];
            ?>
            <div class="hostel-card animate-fade-up"
                 data-name="<?php echo strtolower(htmlspecialchars($h['hostelName'])); ?>"
                 data-neighbourhood="<?php echo htmlspecialchars($h['neighbourhood']); ?>"
                 data-price-min="<?php echo $h['priceMin']; ?>"
                 data-price-max="<?php echo $h['priceMax']; ?>"
                 data-room-type="<?php echo $h['roomType']; ?>"
                 data-amenities="<?php echo htmlspecialchars(
                   implode(',', $amenities)
                 ); ?>"
                 data-match="<?php echo $h['matchScore']; ?>"
            >

              <!-- Image -->
              <div class="hostel-card-img">
                <div class="hostel-card-img-inner">
                  <span class="hostel-card-emoji">🏠</span>
                </div>
                <!-- Match badge (FR-09) -->
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

              <!-- Body -->
              <div class="hostel-card-body">
                <h3 class="hostel-name">
                  <?php echo htmlspecialchars($h['hostelName']); ?>
                </h3>
                <div class="hostel-location">
                  📍 <?php echo htmlspecialchars($h['neighbourhood']); ?>
                  <span class="hostel-rooms-pill">
                    · <?php echo $h['roomsAvailable']; ?> rooms
                  </span>
                </div>
                <div class="hostel-amenities">
                  <?php foreach (array_slice($amenities, 0, 3) as $a): ?>
                    <span class="tag tag-blue">
                      <?php echo htmlspecialchars($a); ?>
                    </span>
                  <?php endforeach; ?>
                  <?php if (count($amenities) > 3): ?>
                    <span class="tag tag-gray">
                      +<?php echo count($amenities) - 3; ?> more
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Footer -->
              <div class="hostel-card-footer">
                <span class="tag tag-gray">
                  <?php echo ucfirst($h['roomType']); ?>
                </span>
                <a href="/SU-Housing/student/detail.php?id=<?php
                     echo $h['hostelId']; ?>"
                   class="btn btn-primary btn-sm">
                  View →
                </a>
              </div>

            </div>
          <?php endforeach; ?>
        </div>

        <!-- Empty state -->
        <div class="empty-state" id="emptyState" style="display:none;">
          <div class="empty-icon">🔍</div>
          <h3>No hostels found</h3>
          <p>Try adjusting your filters or search term.</p>
          <button class="btn btn-outline mt-16"
                  onclick="clearFilters()">
            Clear Filters
          </button>
        </div>

      </div><!-- end browse-results -->

    </div><!-- end browse-layout -->

  </div><!-- end page-body -->
  <?php
// Extra scripts for this page
$extraScripts = ['/SU-Housing/assets/js/browse.js'];
?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
