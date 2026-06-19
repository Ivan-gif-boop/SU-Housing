// assets/js/listings.js
// FR-02: add listing
// FR-04: remove listing (soft delete)

// ── Filter table ──
function filterListings() {
  const query  = document.getElementById('listingSearch')
                   ?.value.toLowerCase() || '';
  const status = document.getElementById('statusFilter')
                   ?.value || 'all';

  document.querySelectorAll('.listing-row').forEach(row => {
    const name   = row.dataset.name    || '';
    const active = row.dataset.active  || '1';

    const matchesSearch = !query || name.includes(query);
    const matchesStatus =
      status === 'all'      ? true :
      status === 'active'   ? active === '1' :
      status === 'inactive' ? active === '0' :
      true;

    row.style.display =
      matchesSearch && matchesStatus ? '' : 'none';
  });
}

// ── Open add modal ──
function openModal(id) {
  document.getElementById(id)?.classList.add('open');
}

// ── Open edit modal — pre-fill form ──
function editListing(listing) {
  document.getElementById('modalTitle').textContent =
    'Edit Listing';
  document.getElementById('editHostelId').value =
    listing.hostelId;
  document.getElementById('lName').value =
    listing.hostelName;
  document.getElementById('lNeighbourhood').value =
    listing.neighbourhood;
  document.getElementById('lAddress').value =
    listing.physicalAddress;
  document.getElementById('lDesc').value =
    listing.description;
  document.getElementById('lPriceMin').value =
    listing.priceMin;
  document.getElementById('lPriceMax').value =
    listing.priceMax;
  document.getElementById('lRoomType').value =
    listing.roomType;
  document.getElementById('lRooms').value =
    listing.roomsAvailable;
  document.getElementById('lLandlordName').value =
    listing.landlordName;
  document.getElementById('lLandlordPhone').value =
    listing.landlordPhone;

  // Tick the right amenity checkboxes
  const amenities = Array.isArray(listing.amenities)
    ? listing.amenities
    : [];
  document.querySelectorAll('.modal-amenity').forEach(cb => {
    cb.checked = amenities.includes(cb.value);
  });

  openModal('listingModal');
}

// ── Reset modal to add mode ──
document.getElementById('listingModal')
  ?.addEventListener('click', function(e) {
  if (e.target === this) closeModal('listingModal');
});

// Override the + Add New Listing button to reset form
document.querySelector('[onclick="openModal(\'listingModal\')"]')
  ?.addEventListener('click', () => {
  document.getElementById('modalTitle').textContent =
    'Add New Listing';
  document.getElementById('listingForm')?.reset();
  document.getElementById('editHostelId').value = '';
  document.querySelectorAll('.modal-amenity')
    .forEach(cb => cb.checked = false);
  clearAllErrors();
});

// ── Form validation ──
function showError(id, msg) {
  const el  = document.getElementById('err-' + id);
  const inp = document.getElementById(id);
  if (el)  el.textContent = msg;
  if (inp) inp.classList.add('is-error');
}

function clearError(id) {
  const el  = document.getElementById('err-' + id);
  const inp = document.getElementById(id);
  if (el)  el.textContent = '';
  if (inp) inp.classList.remove('is-error');
}

function clearAllErrors() {
  document.querySelectorAll('.form-error')
    .forEach(el => el.textContent = '');
  document.querySelectorAll('.is-error')
    .forEach(el => el.classList.remove('is-error'));
}

function submitListingForm() {
  clearAllErrors();
  let valid = true;

  const required = [
    { id: 'lName',          msg: 'Hostel name is required.' },
    { id: 'lNeighbourhood', msg: 'Neighbourhood is required.' },
    { id: 'lAddress',       msg: 'Physical address is required.' },
    { id: 'lDesc',          msg: 'Description is required.' },
    { id: 'lPriceMin',      msg: 'Minimum price is required.' },
    { id: 'lPriceMax',      msg: 'Maximum price is required.' },
    { id: 'lRoomType',      msg: 'Room type is required.' },
    { id: 'lRooms',         msg: 'Rooms available is required.' },
    { id: 'lLandlordName',  msg: 'Landlord name is required.' },
    { id: 'lLandlordPhone', msg: 'Landlord phone is required.' },
  ];

  required.forEach(({ id, msg }) => {
    const el = document.getElementById(id);
    if (!el?.value.trim()) {
      showError(id, msg);
      valid = false;
    }
  });

  // Price range check
  const min = parseFloat(
    document.getElementById('lPriceMin').value
  );
  const max = parseFloat(
    document.getElementById('lPriceMax').value
  );
  if (min && max && max < min) {
    showError('lPriceMax',
      'Max price must be greater than min price.');
    valid = false;
  }

  if (!valid) return;

  // ── Backend hook ──
  // When Michelle's API is ready:
  // document.getElementById('listingForm').submit();
  //
  // For now — show success toast and close modal
  const isEdit = !!document.getElementById('editHostelId').value;
  closeModal('listingModal');
  showToast(
    isEdit
      ? 'Listing updated successfully.'
      : 'New listing added and is now live.',
    'success'
  );
}

// ── Confirm remove ──
let pendingRemoveId = null;

function confirmRemove(id, name) {
  pendingRemoveId = id;
  document.getElementById('confirmTitle').textContent =
    'Remove Listing';
  document.getElementById('confirmMessage').textContent =
    `Are you sure you want to remove "${name}" from the
    live database? Students will no longer be able to
    see this listing.`;
  const btn = document.getElementById('confirmActionBtn');
  btn.textContent  = 'Confirm Remove';
  btn.className    = 'btn btn-danger';
  btn.onclick      = executeRemove;
  openModal('confirmModal');
}

function confirmRestore(id, name) {
  pendingRemoveId = id;
  document.getElementById('confirmTitle').textContent =
    'Restore Listing';
  document.getElementById('confirmMessage').textContent =
    `Restore "${name}"? It will become visible to
    students immediately.`;
  const btn = document.getElementById('confirmActionBtn');
  btn.textContent = 'Confirm Restore';
  btn.className   = 'btn btn-success';
  btn.onclick     = executeRestore;
  openModal('confirmModal');
}

function executeRemove() {
  // ── Backend hook ──
  // fetch(`/SU-Housing/api/listings/destroy.php?id=${pendingRemoveId}`, {
  //   method: 'POST',
  //   body: `csrf_token=${CSRF_TOKEN}`
  // }).then(...);

  closeModal('confirmModal');
  showToast('Listing removed from live database.', 'default');

  // Update row status badge in the table (frontend simulation)
  updateRowStatus(pendingRemoveId, false);
}

function executeRestore() {
  closeModal('confirmModal');
  showToast('Listing restored and is now live.', 'success');
  updateRowStatus(pendingRemoveId, true);
}

function updateRowStatus(id, isActive) {
  // Find the row by searching cells — works with mock data
  document.querySelectorAll('.listing-row').forEach(row => {
    const editBtn = row.querySelector('button');
    if (!editBtn) return;

    // Update the status badge
    const badge = row.querySelector('.badge');
    if (badge) {
      badge.className  = isActive
        ? 'badge badge-green'
        : 'badge badge-gray';
      badge.textContent = isActive ? '● Active' : '● Removed';
    }

    // Swap remove/restore button
    const actionDiv = row.querySelector(
      'div[style*="display:flex"]'
    );
    if (actionDiv) {
      const removeBtn = actionDiv.querySelector('.btn-danger');
      const restoreBtn = actionDiv.querySelector('.btn-success');
      if (isActive && restoreBtn) {
        restoreBtn.className = 'btn btn-danger btn-sm';
        restoreBtn.textContent = 'Remove';
      }
      if (!isActive && removeBtn) {
        removeBtn.className = 'btn btn-success btn-sm';
        removeBtn.textContent = 'Restore';
      }
    }
  });
}

// ── Add CSS for amenities checkboxes in modal ──
// (already defined in components.css — just needs grid layout)
const style = document.createElement('style');
style.textContent = `
  .amenities-check-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 8px;
  }
`;
document.head.appendChild(style);