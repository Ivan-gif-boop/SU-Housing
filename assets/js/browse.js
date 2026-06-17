// assets/js/browse.js
// FR-05: keyword search
// FR-06: filter by neighbourhood, price, room type, amenities
// FR-09: sort by match score if profile exists

function applyFilters() {
  const query     = document.getElementById('filterSearch')
                      ?.value.toLowerCase().trim() || '';
  const nbh       = document.getElementById('filterNeighbourhood')
                      ?.value || '';
  const priceMin  = parseFloat(
                      document.getElementById('filterPriceMin')?.value
                    ) || 0;
  const priceMax  = parseFloat(
                      document.getElementById('filterPriceMax')?.value
                    ) || Infinity;
  const roomType  = document.getElementById('filterRoomType')
                      ?.value || '';
  const sortBy    = document.getElementById('sortSelect')
                      ?.value || 'match';

  // Collect checked amenities
  const checkedAmenities = Array.from(
    document.querySelectorAll('.amenity-filter:checked')
  ).map(el => el.value);

  const cards = Array.from(
    document.querySelectorAll('#hostelGrid .hostel-card')
  );

  let visibleCount = 0;

  cards.forEach(card => {
    const name       = card.dataset.name        || '';
    const cardNbh    = card.dataset.neighbourhood|| '';
    const cardPMin   = parseFloat(card.dataset.priceMin) || 0;
    const cardPMax   = parseFloat(card.dataset.priceMax) || 0;
    const cardRoom   = card.dataset.roomType    || '';
    const cardAmenities = (card.dataset.amenities || '')
                            .split(',')
                            .map(a => a.trim());

    // Test each filter
    const matchesSearch = !query || name.includes(query);
    const matchesNbh    = !nbh   || cardNbh === nbh;
    const matchesPrice  = cardPMin <= priceMax && cardPMax >= priceMin;
    const matchesRoom   = !roomType || cardRoom === roomType;
    const matchesAmenities = checkedAmenities.every(
      a => cardAmenities.includes(a)
    );

    const visible = matchesSearch && matchesNbh
                 && matchesPrice  && matchesRoom
                 && matchesAmenities;

    card.style.display = visible ? '' : 'none';
    if (visible) visibleCount++;
  });

  // Update results count
  const countEl = document.getElementById('resultsCount');
  if (countEl) {
    countEl.innerHTML =
      `Showing <strong>${visibleCount}</strong> result${visibleCount !== 1 ? 's' : ''}`;
  }

  // Show/hide empty state
  const emptyState = document.getElementById('emptyState');
  const grid       = document.getElementById('hostelGrid');
  if (emptyState && grid) {
    emptyState.style.display = visibleCount === 0 ? 'flex' : 'none';
    grid.style.display       = visibleCount === 0 ? 'none' : '';
  }

  // Sort visible cards
  sortCards(sortBy);
}

function sortCards(sortBy) {
  const grid  = document.getElementById('hostelGrid');
  if (!grid) return;

  const cards = Array.from(grid.querySelectorAll('.hostel-card'));

  cards.sort((a, b) => {
    if (sortBy === 'match') {
      return (parseFloat(b.dataset.match) || 0)
           - (parseFloat(a.dataset.match) || 0);
    }
    if (sortBy === 'price_asc') {
      return parseFloat(a.dataset.priceMin)
           - parseFloat(b.dataset.priceMin);
    }
    if (sortBy === 'price_desc') {
      return parseFloat(b.dataset.priceMax)
           - parseFloat(a.dataset.priceMax);
    }
    if (sortBy === 'name') {
      return (a.dataset.name || '').localeCompare(b.dataset.name || '');
    }
    return 0;
  });

  // Re-append in sorted order
  cards.forEach(card => grid.appendChild(card));
}

function clearFilters() {
  const ids = [
    'filterSearch', 'filterNeighbourhood',
    'filterPriceMin', 'filterPriceMax', 'filterRoomType',
  ];
  ids.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });

  document.querySelectorAll('.amenity-filter:checked')
    .forEach(el => el.checked = false);

  applyFilters();
}

// Run on page load to set initial sort
document.addEventListener('DOMContentLoaded', () => applyFilters());