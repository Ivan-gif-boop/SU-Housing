<?php
// admin/listings.php
// FR-02: admin creates a hostel listing
// FR-03: listing immediately visible on creation
// FR-04: admin removes a listing (soft delete — isActive = 0)

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
// $listings = $db->query(
//   'SELECT hostelId, hostelName, neighbourhood,
//           priceMin, priceMax, roomType,
//           roomsAvailable, isActive, amenities
//    FROM hostel_listings
//    ORDER BY createdAt DESC'
// )->fetchAll();
//
// foreach ($listings as &$l) {
//   $l['amenities'] = json_decode($l['amenities'], true);
// }
// ─────────────────────────────────────────

// Frontend defaults
$pageTitle  = 'Manage Listings';
$activePage = 'listings';
$userRole   = 'admin';
$userName   = 'Dean of Students';

// Mock listings
$listings = [
  [
    'hostelId'       => 1,
    'hostelName'     => 'Keri Apartments',
    'neighbourhood'  => 'Madaraka',
    'physicalAddress'=> 'Ole Shapara Avenue, Madaraka, Nairobi',
    'description'    => 'A well-maintained hostel 5 minutes from
      Strathmore main gate with 24-hour water and fibre WiFi.',
    'priceMin'       => 8000,
    'priceMax'       => 12000,
    'roomType'       => 'single',
    'roomsAvailable' => 5,
    'amenities'      => ['WiFi', 'Water', 'Security', 'CCTV'],
    'landlordName'   => 'Mr. Joseph Kariuki',
    'landlordPhone'  => '+254 712 345 678',
    'isActive'       => 1,
  ],
  [
    'hostelId'       => 2,
    'hostelName'     => 'Nyayo View Suites',
    'neighbourhood'  => 'Nairobi West',
    'physicalAddress'=> 'Nairobi West Road, Nairobi West, Nairobi',
    'description'    => 'Modern studio and double apartments with
      rooftop terrace and ample parking.',
    'priceMin'       => 10000,
    'priceMax'       => 15000,
    'roomType'       => 'studio',
    'roomsAvailable' => 8,
    'amenities'      => ['WiFi', 'Security', 'Parking', 'Gym'],
    'landlordName'   => 'Ms. Margaret Njuguna',
    'landlordPhone'  => '+254 722 987 654',
    'isActive'       => 1,
  ],
  [
    'hostelId'       => 3,
    'hostelName'     => 'Green Park Residences',
    'neighbourhood'  => "Lang'ata",
    'physicalAddress'=> 'Lang\'ata Road, Lang\'ata, Nairobi',
    'description'    => 'Affordable single rooms in a quiet,
      green compound near Lang\'ata Road.',
    'priceMin'       => 6500,
    'priceMax'       => 9000,
    'roomType'       => 'shared',
    'roomsAvailable' => 12,
    'amenities'      => ['Water', 'Security', 'Laundry'],
    'landlordName'   => 'Mr. Samuel Odhiambo',
    'landlordPhone'  => '+254 733 111 222',
    'isActive'       => 1,
  ],
  [
    'hostelId'       => 4,
    'hostelName'     => 'Westview Apartments',
    'neighbourhood'  => 'Nairobi West',
    'physicalAddress'=> 'Westlands Close, Nairobi West, Nairobi',
    'description'    => 'Premium ensuite rooms with backup
      generator power.',
    'priceMin'       => 12000,
    'priceMax'       => 18000,
    'roomType'       => 'ensuite',
    'roomsAvailable' => 3,
    'amenities'      => ['WiFi','Water','Security','Parking','Backup Power'],
    'landlordName'   => 'Ms. Alice Wambui',
    'landlordPhone'  => '+254 700 456 789',
    'isActive'       => 0, // soft-deleted example
  ],
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

  <!-- ── Page header ── -->
  <div class="page-header">
    <div class="page-title-group">
      <div class="page-breadcrumb">Admin › Listings</div>
      <h1 class="page-title">Manage Listings</h1>
      <p class="page-subtitle">
        Create, update, and remove verified hostel listings.
        Changes are live immediately (FR-03).
      </p>
    </div>
    <div class="page-actions">
      <button class="btn btn-primary"
              onclick="openModal('listingModal')">
        + Add New Listing
      </button>
    </div>
  </div>

  <div class="page-body">

    <!-- ── Listings table ── -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">
          All Listings
          <span style="font-size:14px; font-weight:400;
                        color:var(--gray-400); font-family:
                        var(--font-body);">
            (<?php echo count($listings); ?>)
          </span>
        </span>
        <div style="display:flex; gap:10px; align-items:center;">
          <!-- Status filter -->
          <select id="statusFilter"
                  class="form-control"
                  onchange="filterListings()"
                  style="width:auto; padding:8px 12px;
                         font-size:13px;">
            <option value="all">All Listings</option>
            <option value="active">Active Only</option>
            <option value="inactive">Removed Only</option>
          </select>
          <!-- Search -->
          <input
            type="text"
            id="listingSearch"
            class="form-control"
            placeholder="Search listings…"
            oninput="filterListings()"
            style="width:220px;"
          />
        </div>
      </div>

      <div class="table-wrap">
        <table id="listingsTable">
          <thead>
            <tr>
              <th>Hostel Name</th>
              <th>Neighbourhood</th>
              <th>Price Range (KES/mo)</th>
              <th>Room Type</th>
              <th>Rooms</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($listings as $l): ?>
              <tr class="listing-row"
                  data-name="<?php echo strtolower(
                    htmlspecialchars($l['hostelName'])
                  ); ?>"
                  data-active="<?php echo $l['isActive']; ?>">
                <td>
                  <strong>
                    <?php echo htmlspecialchars($l['hostelName']); ?>
                  </strong>
                  <div style="font-size:12px; color:var(--gray-400);
                               margin-top:2px;">
                    <?php echo htmlspecialchars(
                      $l['physicalAddress']
                    ); ?>
                  </div>
                </td>
                <td>
                  <?php echo htmlspecialchars($l['neighbourhood']); ?>
                </td>
                <td>
                  KES <?php echo number_format($l['priceMin']); ?>
                  – <?php echo number_format($l['priceMax']); ?>
                </td>
                <td><?php echo ucfirst($l['roomType']); ?></td>
                <td><?php echo $l['roomsAvailable']; ?></td>
                <td>
                  <?php if ($l['isActive']): ?>
                    <span class="badge badge-green">● Active</span>
                  <?php else: ?>
                    <span class="badge badge-gray">● Removed</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display:flex; gap:6px;">
                    <button
                      class="btn btn-outline btn-sm"
                      onclick='editListing(<?php echo json_encode($l); ?>)'
                    >
                      ✏️ Edit
                    </button>
                    <?php if ($l['isActive']): ?>
                      <button
                        class="btn btn-danger btn-sm"
                        onclick="confirmRemove(
                          <?php echo $l['hostelId']; ?>,
                          '<?php echo htmlspecialchars(
                            $l['hostelName'],
                            ENT_QUOTES
                          ); ?>'
                        )"
                      >
                        Remove
                      </button>
                    <?php else: ?>
                      <button
                        class="btn btn-success btn-sm"
                        onclick="confirmRestore(
                          <?php echo $l['hostelId']; ?>,
                          '<?php echo htmlspecialchars(
                            $l['hostelName'],
                            ENT_QUOTES
                          ); ?>'
                        )"
                      >
                        Restore
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- end page-body -->

  <!-- ════════════════════════════════
       ADD / EDIT LISTING MODAL
  ════════════════════════════════ -->
  <div class="modal-overlay" id="listingModal">
    <div class="modal" style="max-width:640px;">

      <div class="modal-header">
        <span class="modal-title" id="modalTitle">
          Add New Listing
        </span>
        <button class="modal-close"
                onclick="closeModal('listingModal')">
          ✕
        </button>
      </div>

      <div class="modal-body">
        <!--
          Backend hook:
          action="/SU-Housing/api/listings/store.php"
          method="POST" for new
          action="/SU-Housing/api/listings/update.php?id={id}"
          method="POST" for edit
          Michelle adds csrfField() here
        -->
        <form id="listingForm" action="#" method="POST"
              novalidate>
          <input type="hidden" id="editHostelId"
                 name="hostelId"/>

          <!-- Row 1: Name + Neighbourhood -->
          <div class="form-row">
            <div class="form-group">
              <label for="lName">Hostel Name</label>
              <input type="text" id="lName"
                     name="hostelName"
                     class="form-control"
                     placeholder="e.g. Sunrise Hostel"
                     required/>
              <div class="form-error" id="err-lName"></div>
            </div>
            <div class="form-group">
              <label for="lNeighbourhood">Neighbourhood</label>
              <select id="lNeighbourhood"
                      name="neighbourhood"
                      class="form-control" required>
                <option value="">Select…</option>
                <option value="Madaraka">Madaraka</option>
                <option value="Nairobi West">Nairobi West</option>
                <option value="Lang'ata">Lang'ata</option>
              </select>
              <div class="form-error"
                   id="err-lNeighbourhood"></div>
            </div>
          </div>

          <!-- Physical address -->
          <div class="form-group">
            <label for="lAddress">Physical Address</label>
            <input type="text" id="lAddress"
                   name="physicalAddress"
                   class="form-control"
                   placeholder="e.g. Ole Shapara Avenue,
                   Madaraka, Nairobi"
                   required/>
            <div class="form-hint">
              Used for geocoding the map location.
            </div>
            <div class="form-error" id="err-lAddress"></div>
          </div>

          <!-- Description -->
          <div class="form-group">
            <label for="lDesc">Description</label>
            <textarea id="lDesc" name="description"
                      class="form-control"
                      placeholder="Brief description of the
                      hostel and its surroundings…"
                      rows="3" required></textarea>
            <div class="form-error" id="err-lDesc"></div>
          </div>

          <!-- Row 2: Price range -->
          <div class="form-row">
            <div class="form-group">
              <label for="lPriceMin">
                Min Price (KES/mo)
              </label>
              <input type="number" id="lPriceMin"
                     name="priceMin"
                     class="form-control"
                     placeholder="e.g. 8000"
                     min="0" required/>
              <div class="form-error"
                   id="err-lPriceMin"></div>
            </div>
            <div class="form-group">
              <label for="lPriceMax">
                Max Price (KES/mo)
              </label>
              <input type="number" id="lPriceMax"
                     name="priceMax"
                     class="form-control"
                     placeholder="e.g. 12000"
                     min="0" required/>
              <div class="form-error"
                   id="err-lPriceMax"></div>
            </div>
          </div>

          <!-- Row 3: Room type + Rooms available -->
          <div class="form-row">
            <div class="form-group">
              <label for="lRoomType">Room Type</label>
              <select id="lRoomType" name="roomType"
                      class="form-control" required>
                <option value="">Select…</option>
                <option value="single">Single</option>
                <option value="shared">Shared</option>
                <option value="ensuite">Ensuite</option>
                <option value="studio">Studio</option>
              </select>
              <div class="form-error"
                   id="err-lRoomType"></div>
            </div>
            <div class="form-group">
              <label for="lRooms">Rooms Available</label>
              <input type="number" id="lRooms"
                     name="roomsAvailable"
                     class="form-control"
                     placeholder="e.g. 10"
                     min="0" required/>
              <div class="form-error"
                   id="err-lRooms"></div>
            </div>
          </div>

          <!-- Amenities checkboxes -->
          <div class="form-group">
            <label>Amenities</label>
            <div class="amenities-check-grid">
              <?php
              $amenityOptions = [
                'WiFi', 'Water', 'Security', 'Parking',
                'Laundry', 'CCTV', 'Backup Power', 'Gym',
              ];
              foreach ($amenityOptions as $a):
              ?>
                <label class="checkbox-option">
                  <input type="checkbox"
                         name="amenities[]"
                         class="modal-amenity"
                         value="<?php echo htmlspecialchars($a); ?>"/>
                  <span class="checkbox-box"></span>
                  <?php echo htmlspecialchars($a); ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <hr class="divider"/>

          <!-- Landlord details -->
          <p style="font-size:12px; font-weight:700;
                     color:var(--gray-400); text-transform:uppercase;
                     letter-spacing:.5px; margin-bottom:14px;">
            Landlord Contact
          </p>

          <div class="form-row">
            <div class="form-group">
              <label for="lLandlordName">
                Landlord Name
              </label>
              <input type="text" id="lLandlordName"
                     name="landlordName"
                     class="form-control"
                     placeholder="Full name"
                     required/>
              <div class="form-error"
                   id="err-lLandlordName"></div>
            </div>
            <div class="form-group">
              <label for="lLandlordPhone">Phone</label>
              <input type="text" id="lLandlordPhone"
                     name="landlordPhone"
                     class="form-control"
                     placeholder="+254 7XX XXX XXX"
                     required/>
              <div class="form-error"
                   id="err-lLandlordPhone"></div>
            </div>
          </div>

        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline"
                onclick="closeModal('listingModal')">
          Cancel
        </button>
        <button class="btn btn-primary"
                onclick="submitListingForm()">
          Save Listing →
        </button>
      </div>

    </div>
  </div>

  <!-- ════ CONFIRM REMOVE MODAL ════ -->
  <div class="modal-overlay" id="confirmModal">
    <div class="modal" style="max-width:420px;">
      <div class="modal-header">
        <span class="modal-title" id="confirmTitle">
          Remove Listing
        </span>
        <button class="modal-close"
                onclick="closeModal('confirmModal')">
          ✕
        </button>
      </div>
      <div class="modal-body">
        <p style="font-size:15px; color:var(--gray-600);
                   line-height:1.6;"
           id="confirmMessage">
        </p>
        <div class="alert alert-warning"
             style="margin-top:16px; font-size:13px;">
          ⚠️ The listing will be hidden from students but
          not permanently deleted. It can be restored later.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline"
                onclick="closeModal('confirmModal')">
          Cancel
        </button>
        <button class="btn btn-danger"
                id="confirmActionBtn">
          Confirm Remove
        </button>
      </div>
    </div>
  </div>

<?php
$extraScripts = [
  '/SU-Housing/assets/js/listings.js',
];
?>
<?php include __DIR__ . '/../includes/footer.php'; ?>