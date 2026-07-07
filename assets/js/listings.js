// assets/js/listings.js
// FR-02: add / edit listing  → POST/PATCH /SU-Housing/api/listings.php
// FR-04: remove listing      → DELETE /SU-Housing/api/listings.php?id={id}
//         restore listing    → PATCH  /SU-Housing/api/listings.php?id={id}&action=restore

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

// ── Listing map picker ──
// Strathmore University coords — map starts centered here
const SU_CENTER = [-1.3100, 36.8126];
let listingMap    = null;
let listingMarker = null;

function makeMarkerIcon() {
  return L.divIcon({
    className: '',
    html: `<div style="
      background:var(--amber,#e8a020);
      width:32px; height:32px;
      border-radius:50% 50% 50% 0;
      transform:rotate(-45deg);
      border:3px solid white;
      box-shadow:0 2px 8px rgba(0,0,0,.3);
    "></div>`,
    iconSize: [32, 32],
    iconAnchor: [16, 32],
  });
}

// Waits until the container's height stops changing for 5 straight
// animation frames — i.e. the modal's open animation has actually
// finished, not just "probably finished after Xms"
function waitForStableSize(el, cb) {
  let lastHeight = -1;
  let stableFrames = 0;

  function tick() {
    const h = el.offsetHeight;
    if (h > 0 && h === lastHeight) {
      stableFrames++;
      if (stableFrames >= 5) { cb(); return; }
    } else {
      stableFrames = 0;
    }
    lastHeight = h;
    requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

// Fully tears down and rebuilds the map+container, then calls
// onReady(map) once the map is created and sized correctly.
function rebuildListingMap(onReady) {
  if (listingMap) {
    listingMap.remove();
    listingMap    = null;
    listingMarker = null;
  }

  const wrapper = document.getElementById('listingMapWrapper');
  if (!wrapper) return;

  wrapper.innerHTML = '';
  const newDiv = document.createElement('div');
  newDiv.id    = 'listingMapPicker';
  newDiv.style.cssText = 'height:100%; width:100%;';
  wrapper.appendChild(newDiv);

  waitForStableSize(wrapper, () => {
    listingMap = L.map('listingMapPicker', {
      scrollWheelZoom: false,
    }).setView(SU_CENTER, 15, { animate: false });

    L.DomEvent.disableClickPropagation(newDiv);
    L.DomEvent.disableScrollPropagation(newDiv);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 19,
    }).addTo(listingMap);

    // Click handler — drop pin and reverse geocode
    listingMap.on('click', async function(e) {
      const { lat, lng } = e.latlng;

      if (listingMarker) {
        listingMarker.setLatLng([lat, lng]);
      } else {
        listingMarker = L.marker([lat, lng], { icon: makeMarkerIcon() }).addTo(listingMap);
      }

      document.getElementById('lLat').value = lat.toFixed(8);
      document.getElementById('lLng').value = lng.toFixed(8);

      try {
        const res  = await fetch(
          `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`,
          { headers: { 'Accept-Language': 'en' } }
        );
        const data = await res.json();
        if (data && data.display_name) {
          const a = data.address || {};
          const parts = [
            a.road || a.pedestrian || a.footway || '',
            a.suburb || a.neighbourhood || a.quarter || '',
            a.city || a.town || a.village || 'Nairobi',
          ].filter(Boolean);
          document.getElementById('lAddress').value =
            parts.length > 0 ? parts.join(', ') : data.display_name;
        }
      } catch (err) {
        console.warn('Reverse geocode failed:', err);
      }
    });

    // Final safety net redraw
    requestAnimationFrame(() => listingMap.invalidateSize({ animate: false }));

    if (onReady) onReady(listingMap);
  });
}

// Catch-all: if window is resized while modal is open, fix map
window.addEventListener('resize', () => {
  if (listingMap && document.getElementById('listingModal')?.classList.contains('open')) {
    listingMap.invalidateSize({ animate: false });
  }
});

// Single source of truth for opening the listing modal + building the map.
// mode: 'add' uses SU_CENTER; 'edit' takes coords and jumps there.
function openListingModalWithMap(mode, coords) {
  document.getElementById('listingModal')?.classList.add('open');
  rebuildListingMap((map) => {
    if (mode === 'edit' && coords) {
      map.setView([coords.lat, coords.lng], 16, { animate: false });
      listingMarker = L.marker([coords.lat, coords.lng], { icon: makeMarkerIcon() }).addTo(map);
    }
  });
}

// ── Open modal (generic) ──
// listingModal gets special handling so the map builds correctly;
// all other modals (confirmModal, etc.) behave as before.
function openModal(id) {
  if (id === 'listingModal') {
    openListingModalWithMap('add');
  } else {
    document.getElementById(id)?.classList.add('open');
  }
}
function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.classList.remove('open');

    // Clean up the map when closing the listing modal
    if (id === 'listingModal' && listingMap) {
        listingMap.remove();
        listingMap = null;
        listingMarker = null;
    }
}

// ── Edit listing buttons — use event delegation ──
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.edit-listing-btn');
  if (!btn) return;
  try {
    const listing = JSON.parse(btn.dataset.listing);
    editListing(listing);
  } catch (err) {
    console.error('Failed to parse listing data:', err);
    showToast('Failed to open listing editor.', 'error');
  }
});

// ── Open edit modal — pre-fill form ──
function editListing(listing) {
  document.getElementById('modalTitle').textContent       = 'Edit Listing';
  document.getElementById('editHostelId').value            = listing.hostelId;
  document.getElementById('lName').value                   = listing.hostelName;
  document.getElementById('lAddress').value                = listing.physicalAddress;
  document.getElementById('lDesc').value                   = listing.description;
  document.getElementById('lPriceMin').value               = listing.priceMin;
  document.getElementById('lPriceMax').value               = listing.priceMax;
  document.getElementById('lRoomType').value               = listing.roomType;
  document.getElementById('lRooms').value                  = listing.roomsAvailable;
  document.getElementById('lGenderPolicy').value           = listing.genderPolicy    || '';
  document.getElementById('lEnvironmentType').value        = listing.environmentType || '';
  document.getElementById('lCurfewPolicy').value           = listing.curfewPolicy    || '';
  document.getElementById('lLandlordName').value           = listing.landlordName;
  document.getElementById('lLandlordContact').value        = listing.landlordContact;

  // Pre-fill hidden lat/lng fields
  document.getElementById('lLat').value = listing.latitude  || '';
  document.getElementById('lLng').value = listing.longitude || '';

  // Show current image preview if the listing has one
  const preview = document.getElementById('currentImagePreview');
  const thumb   = document.getElementById('currentImageThumb');
  if (listing.imagePath && preview && thumb) {
    thumb.src             = listing.imagePath;
    preview.style.display = 'block';
  } else if (preview) {
    preview.style.display = 'none';
  }

  // Clear the file input so old selection doesn't carry over
  const imageInput = document.getElementById('lImage');
  if (imageInput) imageInput.value = '';

  // Tick the right amenity checkboxes
  const amenities = Array.isArray(listing.amenities) ? listing.amenities : [];
  document.querySelectorAll('.modal-amenity').forEach(cb => {
    cb.checked = amenities.includes(cb.value);
  });

  if (listing.latitude && listing.longitude) {
    openListingModalWithMap('edit', {
      lat: parseFloat(listing.latitude),
      lng: parseFloat(listing.longitude),
    });
  } else {
    openListingModalWithMap('add');
  }
}

// ── Reset modal to add mode ──
document.querySelector('[onclick="openModal(\'listingModal\')"]')
  ?.addEventListener('click', () => {
  document.getElementById('modalTitle').textContent = 'Add New Listing';
  document.getElementById('listingForm')?.reset();
  document.getElementById('editHostelId').value = '';
  document.getElementById('lLat').value = '';
  document.getElementById('lLng').value = '';
  document.querySelectorAll('.modal-amenity').forEach(cb => cb.checked = false);
  const preview = document.getElementById('currentImagePreview');
  if (preview) preview.style.display = 'none';
  clearAllErrors();
  // Map init is handled by openModal() -> openListingModalWithMap('add')
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

// ── Submit listing form → real API (multipart/form-data for image support) ──
async function submitListingForm() {
  clearAllErrors();
  let valid = true;

  const required = [
    { id: 'lName',             msg: 'Hostel name is required.'        },
    { id: 'lAddress',          msg: 'Physical address is required.'   },
    { id: 'lDesc',             msg: 'Description is required.'        },
    { id: 'lPriceMin',         msg: 'Minimum price is required.'      },
    { id: 'lPriceMax',         msg: 'Maximum price is required.'      },
    { id: 'lRoomType',         msg: 'Room type is required.'          },
    { id: 'lRooms',            msg: 'Rooms available is required.'    },
    { id: 'lGenderPolicy',     msg: 'Gender policy is required.'      },
    { id: 'lEnvironmentType',  msg: 'Environment type is required.'   },
    { id: 'lCurfewPolicy',     msg: 'Curfew policy is required.'      },
    { id: 'lLandlordName',     msg: 'Landlord name is required.'      },
    { id: 'lLandlordContact',  msg: 'Landlord contact is required.'   },
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

  // Validate image if provided
  const imageInput = document.getElementById('lImage');
  if (imageInput?.files.length > 0) {
    const file     = imageInput.files[0];
    const allowed  = ['image/jpeg', 'image/png', 'image/webp'];
    const maxBytes = 2 * 1024 * 1024;
    if (!allowed.includes(file.type)) {
      showError('lImage', 'Image must be JPG, PNG, or WebP.');
      valid = false;
    } else if (file.size > maxBytes) {
      showError('lImage', 'Image must be under 2MB.');
      valid = false;
    }
  }

  if (!valid) return;

  const hostelId = document.getElementById('editHostelId').value;
  const isEdit   = !!hostelId;

  const amenities = Array.from(
    document.querySelectorAll('.modal-amenity:checked')
  ).map(cb => cb.value);

  // Use FormData so the image file can be included in the request
  const formData = new FormData();
  formData.append('hostelName',      document.getElementById('lName').value.trim());
  formData.append('physicalAddress', document.getElementById('lAddress').value.trim());
  formData.append('description',     document.getElementById('lDesc').value.trim());
  formData.append('priceMin',        document.getElementById('lPriceMin').value);
  formData.append('priceMax',        document.getElementById('lPriceMax').value);
  formData.append('roomType',        document.getElementById('lRoomType').value);
  formData.append('roomsAvailable',  document.getElementById('lRooms').value);
  formData.append('genderPolicy',    document.getElementById('lGenderPolicy').value);
  formData.append('environmentType', document.getElementById('lEnvironmentType').value);
  formData.append('curfewPolicy',    document.getElementById('lCurfewPolicy').value);
  formData.append('landlordName',    document.getElementById('lLandlordName').value.trim());
  formData.append('landlordContact', document.getElementById('lLandlordContact').value.trim());
  // Amenities sent as JSON string since FormData can't send arrays natively
  formData.append('amenities',       JSON.stringify(amenities));

  // Include lat/lng from map picker if set
  const lat = document.getElementById('lLat').value;
  const lng = document.getElementById('lLng').value;
  if (lat) formData.append('latitude',  lat);
  if (lng) formData.append('longitude', lng);

  // Attach image file if selected
  if (imageInput?.files.length > 0) {
    formData.append('image', imageInput.files[0]);
  }

  const url = '/SU-Housing/api/listings.php' + (isEdit ? `?id=${hostelId}&_method=PATCH` : '');

  // For edits, we send as POST with _method=PATCH in the FormData
  // because PHP does not populate $_POST for multipart PATCH requests.
  // For new listings, POST is correct as-is.
  const method = 'POST';

  if (isEdit) {
    formData.append('_method', 'PATCH');
  }

  try {
    // Do NOT set Content-Type header — browser sets it automatically
    // with the correct multipart boundary when using FormData
    const res  = await fetch(url, { method, body: formData });
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
    `Are you sure you want to remove "${name}" from the live database?
     Students will no longer be able to see this listing.`;
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
      `/SU-Housing/api/listings.php?id=${pendingRemoveId}`,
      { method: 'DELETE' }
    );
    const data = await res.json();

    if (res.ok) {
      closeModal('confirmModal');
      showToast('Listing removed from live database.', 'default');
      setTimeout(() => location.reload(), 1200);
    } else {
      closeModal('confirmModal');
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
      `/SU-Housing/api/listings.php?id=${pendingRemoveId}&action=restore`,
      { method: 'PATCH' }
    );
    const data = await res.json();

    if (res.ok) {
      closeModal('confirmModal');
      showToast('Listing restored and is now live.', 'success');
      setTimeout(() => location.reload(), 1200);
    } else {
      closeModal('confirmModal');
      showToast(data.error || 'Failed to restore listing.', 'error');
    }
  } catch (err) {
    closeModal('confirmModal');
    showToast('Network error. Please try again.', 'error');
  }
}

// ── Amenity checkbox grid style ──
const style = document.createElement('style');
style.textContent = `
  .amenities-check-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 8px;
  }
`;
document.head.appendChild(style);