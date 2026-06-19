// assets/js/dashboard.js
// FR-12: sentiment analytics bar chart
// FR-11: classify feedback — updates chart on response

// ── Sentiment Chart ──
document.addEventListener('DOMContentLoaded', () => {
  const canvas = document.getElementById('sentimentChart');
  if (!canvas || !window.SENTIMENT_DATA?.length) return;

  const data     = window.SENTIMENT_DATA;
  const labels   = data.map(d => d.hostelName);
  const positive = data.map(d => parseInt(d.positive) || 0);
  const negative = data.map(d => parseInt(d.negative) || 0);

  new Chart(canvas, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Positive',
          data: positive,
          backgroundColor: '#1e8a5e',
          borderRadius: 6,
          borderSkipped: false,
        },
        {
          label: 'Negative',
          data: negative,
          backgroundColor: '#c23b22',
          borderRadius: 6,
          borderSkipped: false,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            font: { family: 'DM Sans', size: 13 },
            padding: 20,
            usePointStyle: true,
            pointStyleWidth: 10,
          },
        },
        tooltip: {
          callbacks: {
            label: ctx =>
              ` ${ctx.dataset.label}: ${ctx.parsed.y} submission${
                ctx.parsed.y !== 1 ? 's' : ''
              }`,
          },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            font: { family: 'DM Sans', size: 12 },
            color: '#5a6080',
          },
        },
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1,
            font: { family: 'DM Sans', size: 12 },
            color: '#5a6080',
          },
          grid: {
            color: '#eef0f5',
          },
        },
      },
    },
  });
});

// ── Classify feedback (FR-11) ──
// Frontend side: remove card and update count
// Backend side: Michelle wires the fetch() call to
// PATCH /api/feedback/{id}/sentiment

function classifyFeedback(feedbackId, sentiment) {
  const card = document.getElementById('fb-' + feedbackId);
  if (!card) return;

  // Disable buttons immediately to prevent double-click
  card.querySelectorAll('button').forEach(btn => {
    btn.disabled = true;
    btn.style.opacity = '0.6';
  });

  // ── Backend hook ──
  // When Michelle's API is ready, replace the block below with:
  //
  // fetch(`/SU-Housing/api/feedback/classify.php?id=${feedbackId}`, {
  //   method: 'POST',
  //   headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  //   body: `sentiment=${sentiment}&csrf_token=${CSRF_TOKEN}`
  // })
  // .then(res => res.json())
  // .then(data => {
  //   if (data.status === 'success') {
  //     removeCard(card, sentiment);
  //     updateUnclassifiedCount();
  //   }
  // })
  // .catch(() => showToast('Failed to classify. Try again.', 'error'));

  // ── Frontend-only simulation for now ──
  removeCard(card, sentiment);
  updateUnclassifiedCount();
}

function removeCard(card, sentiment) {
  const label    = sentiment === 'positive' ? 'Positive ✓' : 'Negative ✗';
  const badgeClass = sentiment === 'positive'
    ? 'badge-green' : 'badge-red';

  // Swap pending badge to classified badge
  const badge = card.querySelector('.badge');
  if (badge) {
    badge.className = `badge ${badgeClass}`;
    badge.textContent = label;
  }

  // Hide action buttons
  const actions = card.querySelector('.fbc-actions');
  if (actions) actions.style.display = 'none';

  // Fade card out after short delay
  setTimeout(() => {
    card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
    card.style.opacity    = '0';
    card.style.transform  = 'translateY(-8px)';
    setTimeout(() => card.remove(), 400);
  }, 800);

  showToast(
    `Feedback classified as ${sentiment}.`,
    sentiment === 'positive' ? 'success' : 'default'
  );
}

function updateUnclassifiedCount() {
  // Update the badge count in the section header
  const badge = document.querySelector('.nav-badge');
  if (!badge) return;

  const current = parseInt(badge.textContent) || 0;
  const updated = Math.max(0, current - 1);

  badge.textContent = updated;

  if (updated === 0) {
    badge.style.display = 'none';
  }
}