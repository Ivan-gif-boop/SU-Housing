// assets/js/feedback_admin.js
// FR-11: classify feedback as positive or negative

// ── Tab switching ──
function switchFeedbackTab(btn, tabId) {
  // Deactivate all tabs
  document.querySelectorAll('.tab-btn')
    .forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.feedback-tab-content')
    .forEach(c => c.style.display = 'none');

  // Activate selected
  btn.classList.add('active');
  const target = document.getElementById(tabId);
  if (target) target.style.display = 'block';

  // Re-apply search filter to new tab
  filterFeedbackCards();
}

// ── Search + hostel filter ──
function filterFeedbackCards() {
  const query  = document.getElementById('feedbackSearch')
                   ?.value.toLowerCase() || '';
  const hostel = document.getElementById('hostelFilter')
                   ?.value.toLowerCase() || '';

  document.querySelectorAll('.feedback-classify-card')
    .forEach(card => {
      const cardHostel  = card.dataset.hostel  || '';
      const cardStudent = card.dataset.student || '';

      const matchesSearch =
        !query ||
        cardHostel.includes(query) ||
        cardStudent.includes(query);

      const matchesHostel =
        !hostel || cardHostel === hostel;

      card.style.display =
        matchesSearch && matchesHostel ? '' : 'none';
    });
}

// ── Classify feedback (FR-11) ──
function classifyFeedback(feedbackId, sentiment) {
  const card = document.getElementById(
    'fbc-' + feedbackId
  );
  if (!card) return;

  // Disable buttons immediately
  card.querySelectorAll('button').forEach(btn => {
    btn.disabled      = true;
    btn.style.opacity = '0.6';
  });

  // ── Backend hook ──
  // When Michelle's API is ready replace simulation below with:
  //
  // fetch(
  //   `/SU-Housing/api/feedback/classify.php?id=${feedbackId}`,
  //   {
  //     method: 'POST',
  //     headers: {
  //       'Content-Type':
  //         'application/x-www-form-urlencoded'
  //     },
  //     body: `sentiment=${sentiment}&csrf_token=${CSRF_TOKEN}`
  //   }
  // )
  // .then(res => res.json())
  // .then(data => {
  //   if (data.status === 'success') {
  //     applyClassification(card, sentiment);
  //   } else {
  //     showToast('Classification failed.', 'error');
  //   }
  // })
  // .catch(() => showToast('Network error.', 'error'));

  // ── Frontend simulation ──
  applyClassification(card, sentiment);
}

function applyClassification(card, sentiment) {
  // Update the badge
  const badge = card.querySelector('.badge');
  if (badge) {
    badge.className = sentiment === 'positive'
      ? 'badge badge-green'
      : 'badge badge-red';
    badge.textContent = sentiment === 'positive'
      ? '✓ Positive'
      : '✗ Negative';
  }

  // Replace buttons with classified date
  const actions = card.querySelector('.fbc-actions');
  if (actions) {
    actions.outerHTML = `
      <div style="font-size:12px;
                  color:var(--gray-400);
                  margin-top:8px;">
        Classified today
      </div>`;
  }

  // Update card tab data attribute
  card.dataset.tab = 'classified';

  // Move card out of pending tab if visible
  const pendingTab = document.getElementById('tab-pending');
  const activeTab  = document.querySelector(
    '.feedback-tab-content[style="display: block;"]'
  ) || document.getElementById('tab-all');

  if (activeTab?.id === 'tab-pending') {
    // Fade and remove from pending tab
    setTimeout(() => {
      card.style.transition =
        'opacity 0.4s ease, transform 0.4s ease';
      card.style.opacity    = '0';
      card.style.transform  = 'translateY(-8px)';
      setTimeout(() => {
        card.remove();
        updatePendingCount();
      }, 400);
    }, 700);
  }

  // Update stats
  updatePendingCount();

  showToast(
    `Feedback classified as ${sentiment}.`,
    sentiment === 'positive' ? 'success' : 'default'
  );
}

function updatePendingCount() {
  // Count remaining pending cards
  const remaining = document.querySelectorAll(
    '.feedback-classify-card[data-tab="pending"]'
  ).length;

  // Update pending tab badge
  const pendingTabBtn = document.querySelector(
    '[onclick*="tab-pending"]'
  );
  if (pendingTabBtn) {
    const countBadge =
      pendingTabBtn.querySelector('.tab-count.pending');
    if (countBadge) {
      if (remaining > 0) {
        countBadge.textContent = remaining;
      } else {
        countBadge.remove();
      }
    }
  }

  // Update pending stat card
  const statCards = document.querySelectorAll('.stat-num');
  // Second stat card is pending count
  if (statCards[1]) {
    statCards[1].textContent = remaining;
  }
}

// Run filter on page load
document.addEventListener('DOMContentLoaded',
  () => filterFeedbackCards()
);