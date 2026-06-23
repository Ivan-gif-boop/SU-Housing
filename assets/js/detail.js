// assets/js/detail.js
// Leaflet map + OSRM travel times for hostel detail page

// Strathmore University main gate coordinates
const STRATHMORE = { lat: -1.3100, lng: 36.8126 };

// Hostel coordinates — injected from PHP via data attributes
// on the #hostelMap div
const mapEl  = document.getElementById('hostelMap');
const hostelLat = parseFloat(mapEl?.dataset.lat);
const hostelLng = parseFloat(mapEl?.dataset.lng);
const hostelName = mapEl?.dataset.name || 'Hostel';

// ── Initialise Leaflet map ──
if (mapEl && !isNaN(hostelLat) && !isNaN(hostelLng)) {

  const map = L.map('hostelMap').setView(
    [hostelLat, hostelLng], 15
  );

  // OpenStreetMap tile layer (free, no API key)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">' +
                 'OpenStreetMap</a> contributors',
    maxZoom: 19,
  }).addTo(map);

  // ── Custom marker icons ──
  const hostelIcon = L.divIcon({
    className: '',
    html: `<div style="
      background:var(--amber,#e8a020);
      width:36px; height:36px;
      border-radius:50% 50% 50% 0;
      transform:rotate(-45deg);
      border:3px solid white;
      box-shadow:0 2px 8px rgba(0,0,0,0.3);
    "></div>`,
    iconSize: [36, 36],
    iconAnchor: [18, 36],
    popupAnchor: [0, -36],
  });

  const uniIcon = L.divIcon({
    className: '',
    html: `<div style="
      background:#0f1f3d;
      width:32px; height:32px;
      border-radius:50%;
      border:3px solid white;
      box-shadow:0 2px 8px rgba(0,0,0,0.3);
      display:flex; align-items:center;
      justify-content:center;
      color:white; font-size:14px;
      font-weight:bold;
    ">S</div>`,
    iconSize: [32, 32],
    iconAnchor: [16, 16],
    popupAnchor: [0, -16],
  });

  // ── Markers ──
  L.marker([hostelLat, hostelLng], { icon: hostelIcon })
    .addTo(map)
    .bindPopup(`<strong>${hostelName}</strong>`)
    .openPopup();

  L.marker([STRATHMORE.lat, STRATHMORE.lng], { icon: uniIcon })
    .addTo(map)
    .bindPopup('<strong>Strathmore University</strong>');

  // ── Draw a line between the two points ──
  L.polyline(
    [
      [STRATHMORE.lat, STRATHMORE.lng],
      [hostelLat, hostelLng],
    ],
    { color: '#e8a020', weight: 2, dashArray: '6,6', opacity: 0.7 }
  ).addTo(map);

  // ── Fit map to show both markers ──
  map.fitBounds([
    [STRATHMORE.lat, STRATHMORE.lng],
    [hostelLat, hostelLng],
  ], { padding: [40, 40] });

  // ── OSRM travel times ──
  fetchTravelTimes(hostelLat, hostelLng);

} else {
  // No coordinates available yet — show placeholder message
  if (mapEl) {
    mapEl.innerHTML = `
      <div style="height:320px; display:flex; align-items:center;
                  justify-content:center; background:var(--gray-100);
                  flex-direction:column; gap:8px;">
        <span style="font-size:36px;">🗺️</span>
        <p style="font-size:14px; color:var(--gray-600);">
          Map coordinates not yet available for this listing.
        </p>
      </div>`;
  }
}

// ── OSRM API: fetch walking + driving durations ──
async function fetchTravelTimes(lat, lng) {
  const walkEl  = document.getElementById('walkDuration');
  const walkDEl = document.getElementById('walkDistance');
  const driveEl = document.getElementById('driveDuration');
  const driveDEl= document.getElementById('driveDistance');

  try {
    // Walking route
    const walkUrl =
      `https://router.project-osrm.org/route/v1/walking/` +
      `${STRATHMORE.lng},${STRATHMORE.lat};${lng},${lat}` +
      `?overview=false`;

    const walkRes  = await fetch(walkUrl);
    const walkData = await walkRes.json();

    if (walkData.code === 'Ok' && walkData.routes.length) {
      const route    = walkData.routes[0];
      const mins     = Math.round(route.duration / 60);
      const distKm   = (route.distance / 1000).toFixed(1);

      if (walkEl)  walkEl.textContent  = `${mins} min`;
      if (walkDEl) walkDEl.textContent = `${distKm} km on foot`;
    }

    // Driving route (closest to matatu/transit)
    const driveUrl =
      `https://router.project-osrm.org/route/v1/driving/` +
      `${STRATHMORE.lng},${STRATHMORE.lat};${lng},${lat}` +
      `?overview=false`;

    const driveRes  = await fetch(driveUrl);
    const driveData = await driveRes.json();

    if (driveData.code === 'Ok' && driveData.routes.length) {
      const route    = driveData.routes[0];
      const mins     = Math.round(route.duration / 60);
      const distKm   = (route.distance / 1000).toFixed(1);

      if (driveEl)  driveEl.textContent  = `${mins} min`;
      if (driveDEl) driveDEl.textContent = `${distKm} km by road`;
    }

  } catch (err) {
    // OSRM unavailable — show fallback
    if (walkEl)  walkEl.textContent  = 'Unavailable';
    if (driveEl) driveEl.textContent = 'Unavailable';
    console.warn('OSRM travel time fetch failed:', err);
  }
}