<?php
// admin/listings.php
// FR-02: admin creates a hostel listing
// FR-03: listing immediately visible on creation
// FR-04: admin removes a listing (soft delete — isActive = 0)

session_start();

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/db.php';
$db = getDB();

$userName = $_SESSION['fullName'] ?? 'Administrator';

// ── Fetch all listings (active + removed) from DB ──
// NOTE: landlordName and landlordContact are now both separate
// NOT NULL columns in the DB.
$listingsStmt = $db->query(
    'SELECT hostelId, hostelName, physicalAddress, description,
            priceMin, priceMax, roomType, roomsAvailable, amenities,
            landlordName, landlordContact, genderPolicy, environmentType, curfewPolicy,
            isActive
     FROM hostel_listings
     ORDER BY createdAt DESC'
);
$listings = $listingsStmt->fetchAll();

// Decode amenities JSON for each listing
foreach ($listings as &$l) {
    $l['amenities'] = json_decode($l['amenities'], true) ?? [];
}
unset($l);

// ── Page meta ──
$pageTitle  = 'Manage Listings';
$activePage = 'listings';
$userRole   = 'admin';

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
        Changes are live immediately and visible to students.
        Removed listings can be restored later.
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
                        color:var(--gray-400);
                        font-family:var(--font-body);">
            (<?php echo count($listings); ?>)
          </span>
        </span>
        <div style="display:flex; gap:10px; align-items:center;">
          <select id="statusFilter"
                  class="form-control"
                  onchange="filterListings()"
                  style="width:auto; padding:8px 12px; font-size:13px;">
            <option value="all">All Listings</option>
            <option value="active">Active Only</option>
            <option value="inactive">Removed Only</option>
          </select>
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
              <th>Location</th>
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
                  data-name="<?php echo strtolower(htmlspecialchars($l['hostelName'])); ?>"
                  data-active="<?php echo $l['isActive']; ?>">
                <td>
                  <strong><?php echo htmlspecialchars($l['hostelName']); ?></strong>
                </td>
                <td><?php echo htmlspecialchars($l['physicalAddress']); ?></td>
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
                          '<?php echo htmlspecialchars($l['hostelName'], ENT_QUOTES); ?>'
                        )"
                      >
                        Remove
                      </button>
                    <?php else: ?>
                      <button
                        class="btn btn-success btn-sm"
                        onclick="confirmRestore(
                          <?php echo $l['hostelId']; ?>,
                          '<?php echo htmlspecialchars($l['hostelName'], ENT_QUOTES); ?>'
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

        <?php if (empty($listings)): ?>
          <div class="empty-state" style="padding:48px;">
            <div class="empty-icon">🏠</div>
            <h3>No listings yet</h3>
            <p>Click "Add New Listing" to publish your first hostel.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- end page-body -->

  <!-- ════ ADD / EDIT LISTING MODAL ════ -->
  <div class="modal-overlay" id="listingModal">
    <div class="modal" style="max-width:640px;">

      <div class="modal-header">
        <span class="modal-title" id="modalTitle">Add New Listing</span>
        <button class="modal-close"
                onclick="closeModal('listingModal')">✕</button>
      </div>

      <div class="modal-body">
        <form id="listingForm" novalidate>
          <input type="hidden" id="editHostelId" name="hostelId"/>

          <div class="form-group">
            <label for="lName">Hostel Name</label>
            <input type="text" id="lName" name="hostelName"
                   class="form-control"
                   placeholder="e.g. Sunrise Hostel" required/>
            <div class="form-error" id="err-lName"></div>
          </div>

          <!-- Single physical address field — geocoded via Nominatim server-side -->
          <div class="form-group">
            <label for="lAddress">Physical Address</label>
            <input type="text" id="lAddress" name="physicalAddress"
                   class="form-control"
                   placeholder="e.g. Madaraka, next to Strathmore University, Nairobi"
                   required/>
            <div class="form-hint">
              Used to display the location and to geocode the map pin.
            </div>
            <div class="form-error" id="err-lAddress"></div>
          </div>

          <div class="form-group">
            <label for="lDesc">Description</label>
            <textarea id="lDesc" name="description"
                      class="form-control"
                      placeholder="Brief description of the hostel…"
                      rows="3" required></textarea>
            <div class="form-error" id="err-lDesc"></div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="lPriceMin">Min Price (KES/mo)</label>
              <input type="number" id="lPriceMin" name="priceMin"
                     class="form-control" placeholder="e.g. 8000"
                     min="0" required/>
              <div class="form-error" id="err-lPriceMin"></div>
            </div>
            <div class="form-group">
              <label for="lPriceMax">Max Price (KES/mo)</label>
              <input type="number" id="lPriceMax" name="priceMax"
                     class="form-control" placeholder="e.g. 12000"
                     min="0" required/>
              <div class="form-error" id="err-lPriceMax"></div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="lRoomType">Room Type</label>
              <select id="lRoomType" name="roomType"
                      class="form-control" required>
                <option value="">Select…</option>
                <option value="single">Single</option>
                <option value="shared">Shared</option>
                <option value="ensuite">Ensuite</option>
              </select>
              <div class="form-error" id="err-lRoomType"></div>
            </div>
            <div class="form-group">
              <label for="lRooms">Rooms Available</label>
              <input type="number" id="lRooms" name="roomsAvailable"
                     class="form-control" placeholder="e.g. 10"
                     min="0" required/>
              <div class="form-error" id="err-lRooms"></div>
            </div>
          </div>

          <!-- Gender policy / environment type / curfew policy — required by DB -->
          <div class="form-row">
            <div class="form-group">
              <label for="lGenderPolicy">Gender Policy</label>
              <select id="lGenderPolicy" name="genderPolicy"
                      class="form-control" required>
                <option value="">Select…</option>
                <option value="male_only">Male Only</option>
                <option value="female_only">Female Only</option>
                <option value="mixed">Mixed</option>
              </select>
              <div class="form-error" id="err-lGenderPolicy"></div>
            </div>
            <div class="form-group">
              <label for="lEnvironmentType">Environment</label>
              <select id="lEnvironmentType" name="environmentType"
                      class="form-control" required>
                <option value="">Select…</option>
                <option value="quiet">Quiet</option>
                <option value="moderate">Moderate</option>
                <option value="lively">Lively</option>
              </select>
              <div class="form-error" id="err-lEnvironmentType"></div>
            </div>
          </div>

          <div class="form-group">
            <label for="lCurfewPolicy">Curfew Policy</label>
            <select id="lCurfewPolicy" name="curfewPolicy"
                    class="form-control" required>
              <option value="">Select…</option>
              <option value="before_10pm">Before 10pm</option>
              <option value="before_midnight">Before Midnight</option>
              <option value="no_curfew">No Curfew</option>
            </select>
            <div class="form-error" id="err-lCurfewPolicy"></div>
          </div>

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

          <!-- DB has both landlordName and landlordContact as separate fields -->
          <p style="font-size:12px; font-weight:700; color:var(--gray-400);
                     text-transform:uppercase; letter-spacing:.5px;
                     margin-bottom:14px;">
            Landlord Contact
          </p>

          <div class="form-row">
            <div class="form-group">
              <label for="lLandlordName">Landlord Name</label>
              <input type="text" id="lLandlordName" name="landlordName"
                     class="form-control" placeholder="Full name"
                     required/>
              <div class="form-error" id="err-lLandlordName"></div>
            </div>
            <div class="form-group">
              <label for="lLandlordContact">Phone Number</label>
              <input type="text" id="lLandlordContact" name="landlordContact"
                     class="form-control" placeholder="+254 7XX XXX XXX"
                     required/>
              <div class="form-error" id="err-lLandlordContact"></div>
            </div>
          </div>

        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline"
                onclick="closeModal('listingModal')">Cancel</button>
        <button class="btn btn-primary"
                onclick="submitListingForm()">Save Listing →</button>
      </div>

    </div>
  </div>

  <!-- ════ CONFIRM REMOVE / RESTORE MODAL ════ -->
  <div class="modal-overlay" id="confirmModal">
    <div class="modal" style="max-width:420px;">
      <div class="modal-header">
        <span class="modal-title" id="confirmTitle">Remove Listing</span>
        <button class="modal-close"
                onclick="closeModal('confirmModal')">✕</button>
      </div>
      <div class="modal-body">
        <p style="font-size:15px; color:var(--gray-600); line-height:1.6;"
           id="confirmMessage"></p>
        <div class="alert alert-warning" style="margin-top:16px; font-size:13px;">
          ⚠️ The listing will be hidden from students but not permanently deleted.
          It can be restored later.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline"
                onclick="closeModal('confirmModal')">Cancel</button>
        <button class="btn btn-danger" id="confirmActionBtn">
          Confirm Remove
        </button>
      </div>
    </div>
  </div>

<?php
$extraScripts = ['/SU-Housing/assets/js/listings.js'];
include __DIR__ . '/../includes/footer.php';
?>