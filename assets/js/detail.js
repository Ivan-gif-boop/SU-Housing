// assets/js/detail.js
// Leaflet map + travel times for hostel detail page
//
// NOTE: the public OSRM demo server (router.project-osrm.org) only
// reliably serves the DRIVING profile. Requests to /route/v1/walking/
// against it get routed through the same car engine, which is why
// walking and driving times used to come back identical. Walking
// times below use OpenRouteService instead, which has a real foot
// profile on its free tier. Sign up for a key at openrouteservice.org
// and put it in ORS_API_KEY.

const ORS_API_KEY = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6ImFhYTMzMWIzMWE5OTQ4YzJhNjlhZWE0MDFmNGYxZmY4IiwiaCI6Im11cm11cjY0In0='; // TODO: set this

// Strathmore University main gate coordinates
const STRATHMORE = { lat: -1.3100, lng: 36.8126 };

// Hostel coordinates — injected from PHP via data attributes
// on the #hostelMap div
const mapEl  = document.getElementById('hostelMap');
const hostelLat = parseFloat(mapEl?.dataset.lat);
const hostelLng = parseFloat(mapEl?.dataset.lng);
const hostelName = mapEl?.dataset.name || 'Hostel';

let map; // module-level so fetchTravelTimes can add route layers to it

// ── Initialise Leaflet map ──
if (mapEl && !isNaN(hostelLat) && !isNaN(hostelLng)) {

  map = L.map('hostelMap').setView(
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

  // NOTE: the old straight dashed line between the two points has
  // been removed from here. The actual route polyline is now drawn
  // inside fetchTravelTimes() once we have real route geometry back
  // from the routing APIs, so the line follows roads/paths instead
  // of cutting straight through buildings.

  // ── Fit map to show both markers (route fit happens later) ──
  map.fitBounds([
    [STRATHMORE.lat, STRATHMORE.lng],
    [hostelLat, hostelLng],
  ], { padding: [40, 40] });

  // ── Travel times + route geometry ──
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

// Draws a real route line on the map from a GeoJSON LineString's
// coordinate array (note: GeoJSON is [lng, lat], Leaflet wants [lat, lng])
function drawRouteLine(geojsonCoords, color) {
  if (!map || !geojsonCoords?.length) return;
  const latLngs = geojsonCoords.map(([lng, lat]) => [lat, lng]);
  L.polyline(latLngs, { color, weight: 4, opacity: 0.8 }).addTo(map);
}

// ── Driving route via OSRM (public demo server supports this profile) ──
async function fetchDrivingRoute(lat, lng) {
  const driveEl  = document.getElementById('driveDuration');
  const driveDEl = document.getElementById('driveDistance');

  const driveUrl =
    `https://router.project-osrm.org/route/v1/driving/` +
    `${STRATHMORE.lng},${STRATHMORE.lat};${lng},${lat}` +
    `?overview=full&geometries=geojson`;

  const driveRes  = await fetch(driveUrl);
  const driveData = await driveRes.json();

  if (driveData.code === 'Ok' && driveData.routes.length) {
    const route  = driveData.routes[0];
    const mins   = Math.round(route.duration / 60);
    const distKm = (route.distance / 1000).toFixed(1);

    if (driveEl)  driveEl.textContent  = `${mins} min`;
    if (driveDEl) driveDEl.textContent = `${distKm} km by road`;

    drawRouteLine(route.geometry.coordinates, '#0f1f3d');
  } else {
    if (driveEl) driveEl.textContent = 'Unavailable';
  }
}

// ── Walking route via OpenRouteService ──
// (the public OSRM demo doesn't have a real foot profile, so we use
// a different provider here instead of pretending OSRM supports it)
async function fetchWalkingRoute(lat, lng) {
  const walkEl  = document.getElementById('walkDuration');
  const walkDEl = document.getElementById('walkDistance');

  if (!ORS_API_KEY || ORS_API_KEY === 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6ImFhYTMzMWIzMWE5OTQ4YzJhNjlhZWE0MDFmNGYxZmY4IiwiaCI6Im11cm11cjY0In0=') {
    if (walkEl) walkEl.textContent = 'Unavailable';
    console.warn('OpenRouteService API key not configured — walking times disabled.');
    return;
  }

  const walkUrl = 'https://api.openrouteservice.org/v2/directions/foot-walking/geojson';

  const walkRes = await fetch(walkUrl, {
    method: 'POST',
    headers: {
      'Authorization': ORS_API_KEY,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      coordinates: [
        [STRATHMORE.lng, STRATHMORE.lat],
        [lng, lat],
      ],
    }),
  });
  const walkData = await walkRes.json();

  const feature = walkData?.features?.[0];
  if (feature) {
    const summary = feature.properties.summary;
    const mins    = Math.round(summary.duration / 60);
    const distKm  = (summary.distance / 1000).toFixed(1);

    if (walkEl)  walkEl.textContent  = `${mins} min`;
    if (walkDEl) walkDEl.textContent = `${distKm} km on foot`;

    drawRouteLine(feature.geometry.coordinates, '#e8a020');
  } else {
    if (walkEl) walkEl.textContent = 'Unavailable';
  }
}

// ── Fetch both travel modes ──
async function fetchTravelTimes(lat, lng) {
  try {
    await Promise.all([
      fetchWalkingRoute(lat, lng),
      fetchDrivingRoute(lat, lng),
    ]);
  } catch (err) {
    const walkEl  = document.getElementById('walkDuration');
    const driveEl = document.getElementById('driveDuration');
    if (walkEl)  walkEl.textContent  = 'Unavailable';
    if (driveEl) driveEl.textContent = 'Unavailable';
    console.warn('Travel time fetch failed:', err);
  }
}