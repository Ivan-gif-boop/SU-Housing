// assets/js/listings.js
// FR-02: add / edit listing  → POST   /SU-Housing/api/admin/listings.php
// FR-04: remove listing      → DELETE /SU-Housing/api/admin/listings.php?id={id}
//         restore listing    → PATCH  /SU-Housing/api/admin/listings.php?id={id}

// ── Filter table ──
function filterListings() {
  const query  = document.getElementById('listingSearch')?.value.toLowerCase() || '';
  const status = document.getElementById('statusFilter')?.value || 'all';

  document.querySelectorAll('.listing-row').forEach(row => {
    const name   = row.dataset.name   || '';
    const active = row.dataset.active || '1';

    const matchesSearch = !query || name.includes(query);
    const matchesStatus =
      status === 'all'      ? true :
      status === 'active'   ? active === '1' :
      status === 'inactive' ? active === '0' :
      true;

    row.style.display = matchesSearch && matchesStatus ? '' : 'none';
  });
}

// ── Modal helpers ──
function openModal(id) {
  document.getElementById(id)?.classList.add('open');
}

function closeModal(id) {
  document.getElementById(id)?.classList.remove('open');
}

// ── Open edit modal — pre-fill form ──
function editListing(listing) {
  document.getElementById('modalTitle').textContent    = 'Edit Listing';
  document.getElementById('editHostelId').value        = listing.hostelId;
  document.getElementById('lName').value               = listing.hostelName;
  document.getElementById('lAddress').value            = listing.physicalAddress;
  document.getElementById('lDesc').value               = listing.description ?? '';
  document.getElementById('lPriceMin').value           = listing.priceMin;
  document.getElementById('lPriceMax').value           = listing.priceMax;
  document.getElementById('lRoomType').value           = listing.roomType;
  document.getElementById('lRooms').value              = listing.roomsAvailable;
  document.getElementById('lLandlordContact').value    = listing.landlordContact ?? '';
  document.getElementById('lGenderPolicy').value       = listing.genderPolicy ?? '';
  document.getElementById('lEnvironmentType').value    = listing.environmentType ?? '';
  document.getElementById('lCurfewPolicy').value       = listing.curfewPolicy ?? '';

  const amenities = Array.isArray(listing.amenities) ? listing.amenities : [];
  document.querySelectorAll('.modal-amenity').forEach(cb => {
    cb.checked = amenities.includes(cb.value);
  });

  openModal('listingModal');
}

// ── Reset modal to add mode ──
document.querySelector('[onclick="openModal(\'listingModal\')"]')
  ?.addEventListener('click', () => {
    document.getElementById('modalTitle').textContent = 'Add New Listing';
    document.getElementById('listingForm')?.reset();
    document.getElementById('editHostelId').value = '';
    document.querySelectorAll('.modal-amenity').forEach(cb => cb.checked = false);
    clearAllErrors();
  });

// ── Form validation helpers ──
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
  document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
  document.querySelectorAll('.is-error').forEach(el => el.classList.remove('is-error'));
}

// ── Submit listing form → admin API ──
async function submitListingForm() {
  clearAllErrors();
  let valid = true;

  const required = [
    { id: 'lName',            msg: 'Hostel name is required.'      },
    { id: 'lAddress',         msg: 'Physical address is required.' },
    { id: 'lDesc',            msg: 'Description is required.'      },
    { id: 'lPriceMin',        msg: 'Minimum price is required.'    },
    { id: 'lPriceMax',        msg: 'Maximum price is required.'    },
    { id: 'lRoomType',        msg: 'Room type is required.'        },
    { id: 'lRooms',           msg: 'Rooms available is required.'  },
    { id: 'lLandlordContact', msg: 'Landlord contact is required.' },
    { id: 'lGenderPolicy',    msg: 'Gender policy is required.'    },
    { id: 'lEnvironmentType', msg: 'Environment type is required.' },
    { id: 'lCurfewPolicy',    msg: 'Curfew policy is required.'    },
  ];

  required.forEach(({ id, msg }) => {
    if (!document.getElementById(id)?.value.trim()) {
      showError(id, msg);
      valid = false;
    }
  });

  const min = parseFloat(document.getElementById('lPriceMin').value);
  const max = parseFloat(document.getElementById('lPriceMax').value);
  if (min && max && max < min) {
    showError('lPriceMax', 'Max price must be greater than min price.');
    valid = false;
  }

  if (!valid) return;

  const hostelId = document.getElementById('editHostelId').value;
  const isEdit   = !!hostelId;

  const amenities = Array.from(
    document.querySelectorAll('.modal-amenity:checked')
  ).map(cb => cb.value);

  const payload = {
    hostelName:      document.getElementById('lName').value.trim(),
    physicalAddress: document.getElementById('lAddress').value.trim(),
    description:     document.getElementById('lDesc').value.trim(),
    priceMin:        parseFloat(document.getElementById('lPriceMin').value),
    priceMax:        parseFloat(document.getElementById('lPriceMax').value),
    roomType:        document.getElementById('lRoomType').value,
    roomsAvailable:  parseInt(document.getElementById('lRooms').value, 10),
    landlordContact: document.getElementById('lLandlordContact').value.trim(),
    genderPolicy:    document.getElementById('lGenderPolicy').value,
    environmentType: document.getElementById('lEnvironmentType').value,
    curfewPolicy:    document.getElementById('lCurfewPolicy').value,
    amenities,
  };

  const url    = isEdit
    ? `/SU-Housing/api/admin/listings.php?id=${hostelId}`
    : '/SU-Housing/api/admin/listings.php';
  const method = isEdit ? 'PATCH' : 'POST';

  try {
    const res  = await fetch(url, {
      method,
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();

    if (res.ok) {
      closeModal('listingModal');
      showToast(
        isEdit ? 'Listing updated successfully.' : 'New listing added and is now live.',
        'success'
      );
      setTimeout(() => location.reload(), 1200);
    } else {
      showToast(data.error || 'Failed to save listing.', 'error');
    }
  } catch (err) {
    showToast('Network error. Please try again.', 'error');
  }
}

// ── Confirm remove ──
let pendingRemoveId = null;

function confirmRemove(id, name) {
  pendingRemoveId = id;
  document.getElementById('confirmTitle').textContent   = 'Remove Listing';
  document.getElementById('confirmMessage').textContent =
    `Are you sure you want to remove "${name}" from the live database? Students will no longer see this listing.`;
  const btn = document.getElementById('confirmActionBtn');
  btn.textContent = 'Confirm Remove';
  btn.className   = 'btn btn-danger';
  btn.onclick     = executeRemove;
  openModal('confirmModal');
}

function confirmRestore(id, name) {
  pendingRemoveId = id;
  document.getElementById('confirmTitle').textContent   = 'Restore Listing';
  document.getElementById('confirmMessage').textContent =
    `Restore "${name}"? It will become visible to students immediately.`;
  const btn = document.getElementById('confirmActionBtn');
  btn.textContent = 'Confirm Restore';
  btn.className   = 'btn btn-success';
  btn.onclick     = executeRestore;
  openModal('confirmModal');
}

async function executeRemove() {
  try {
    const res  = await fetch(
      `/SU-Housing/api/admin/listings.php?id=${pendingRemoveId}`,
      { method: 'DELETE', credentials: 'include' }
    );
    const data = await res.json();
    closeModal('confirmModal');
    if (res.ok) {
      showToast('Listing removed from live database.', 'default');
      setTimeout(() => location.reload(), 1200);
    } else {
      showToast(data.error || 'Failed to remove listing.', 'error');
    }
  } catch (err) {
    closeModal('confirmModal');
    showToast('Network error. Please try again.', 'error');
  }
}

async function executeRestore() {
  try {
    const res  = await fetch(
      `/SU-Housing/api/admin/listings.php?id=${pendingRemoveId}`,
      {
        method: 'PATCH',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ isActive: 1 })
      }
    );
    const data = await res.json();
    closeModal('confirmModal');
    if (res.ok) {
      showToast('Listing restored and is now live.', 'success');
      setTimeout(() => location.reload(), 1200);
    } else {
      showToast(data.error || 'Failed to restore listing.', 'error');
    }
  } catch (err) {
    closeModal('confirmModal');
    showToast('Network error. Please try again.', 'error');
  }
}

// ── Toast notification ──
function showToast(message, type = 'default') {
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = message;
  toast.style.cssText = `
    position:fixed; bottom:24px; right:24px; z-index:9999;
    background:${type === 'success' ? '#16a34a' : type === 'error' ? '#dc2626' : '#374151'};
    color:white; padding:12px 20px; border-radius:8px;
    font-size:14px; box-shadow:0 4px 12px rgba(0,0,0,0.15);
    transition:opacity 0.3s;
  `;
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}
