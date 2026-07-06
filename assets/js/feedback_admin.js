// assets/js/feedback_admin.js
// FR-11: classify feedback as positive or negative
//        → POST /SU-Housing/api/feedback.php?id={id}&action=classify

// ── Tab switching ──
function switchFeedbackTab(btn, tabId) {
  document.querySelectorAll('.tab-btn')
    .forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.feedback-tab-content')
    .forEach(c => c.style.display = 'none');

  btn.classList.add('active');
  const target = document.getElementById(tabId);
  if (target) target.style.display = 'block';

  filterFeedbackCards();
}

// ── Search + hostel filter ──
function filterFeedbackCards() {
  const query  = document.getElementById('feedbackSearch')?.value.toLowerCase() || '';
  const hostel = document.getElementById('hostelFilter')?.value.toLowerCase()   || '';

  document.querySelectorAll('.feedback-classify-card').forEach(card => {
    const cardHostel  = (card.dataset.hostel  || '').toLowerCase();
    const cardStudent = (card.dataset.student || '').toLowerCase();

    const matchesSearch =
      !query || cardHostel.includes(query) || cardStudent.includes(query);
    const matchesHostel =
      !hostel || cardHostel === hostel;

    card.style.display = matchesSearch && matchesHostel ? '' : 'none';
  });
}

// ── Classify feedback → real API (FR-11) ──
async function classifyFeedback(feedbackId, sentiment) {
  const card = document.getElementById('fbc-' + feedbackId);
  if (!card) return;

  // Disable buttons immediately to prevent double-click
  card.querySelectorAll('button').forEach(btn => {
    btn.disabled      = true;
    btn.style.opacity = '0.6';
  });

  try {
    const res = await fetch(
      `/SU-Housing/api/feedback.php?id=${feedbackId}&action=classify`,
      {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ classification: sentiment }),
      }
    );
    const data = await res.json();

    if (res.ok) {
      applyClassification(card, sentiment);
    } else {
      // Re-enable buttons on failure
      card.querySelectorAll('button').forEach(btn => {
        btn.disabled      = false;
        btn.style.opacity = '1';
      });
      showToast(data.error || 'Classification failed. Try again.', 'error');
    }
  } catch (err) {
    card.querySelectorAll('button').forEach(btn => {
      btn.disabled      = false;
      btn.style.opacity = '1';
    });
    showToast('Network error. Please try again.', 'error');
  }
}

function applyClassification(card, sentiment) {
  // Update the badge
  const badge = card.querySelector('.badge');
  if (badge) {
    badge.className   = sentiment === 'positive' ? 'badge badge-green' : 'badge badge-red';
    badge.textContent = sentiment === 'positive' ? '✓ Positive' : '✗ Negative';
  }

  // Replace action buttons with classified date
  const actions = card.querySelector('.fbc-actions');
  if (actions) {
    actions.outerHTML = `
      <div style="font-size:12px; color:var(--gray-400); margin-top:8px;">
        Classified today
      </div>`;
  }

  // Update card data attribute
  card.dataset.tab = 'classified';

  // If we're on the pending tab, fade the card out
  const activeTab = document.querySelector(
    '.feedback-tab-content[style="display: block;"]'
  );
  if (activeTab?.id === 'tab-pending') {
    setTimeout(() => {
      card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      card.style.opacity    = '0';
      card.style.transform  = 'translateY(-8px)';
      setTimeout(() => {
        card.remove();
        updatePendingCount();
      }, 400);
    }, 700);
  }

  updatePendingCount();

  showToast(
    `Feedback classified as ${sentiment}.`,
    sentiment === 'positive' ? 'success' : 'default'
  );
}

// ── Remove classification — sets classification back to NULL (pending) ──
async function removeClassification(feedbackId) {
  const card = document.getElementById('fbc-' + feedbackId);
  if (!card) return;

  // Disable the button immediately
  const btn = card.querySelector('button');
  if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }

  try {
    const res = await fetch(
      `/SU-Housing/api/feedback.php?id=${feedbackId}`,
      {
        method:  'PATCH',
        headers: { 'Content-Type': 'application/json' },
        // Send null to clear the classification
        body: JSON.stringify({ classification: null }),
      }
    );
    const data = await res.json();

    if (res.ok) {
      // Update the badge back to pending
      const badge = card.querySelector('.badge');
      if (badge) {
        badge.className   = 'badge badge-amber';
        badge.textContent = '⏳ Pending';
      }

      // Replace remove button with classify buttons
      const actions = card.querySelector('.fbc-actions');
      if (actions) {
        actions.outerHTML = `
          <div class="fbc-actions" style="margin-top:8px;">
            <button class="btn btn-success btn-sm"
              onclick="classifyFeedback(${feedbackId}, 'positive')">
              ✓ Positive
            </button>
            <button class="btn btn-danger btn-sm"
              onclick="classifyFeedback(${feedbackId}, 'negative')">
              ✗ Negative
            </button>
          </div>`;
      }

      // Update card data attribute
      card.dataset.tab = 'pending';

      // If on classified tab, fade the card out
      const activeTab = document.querySelector(
        '.feedback-tab-content[style="display: block;"]'
      );
      if (activeTab?.id === 'tab-classified') {
        setTimeout(() => {
          card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
          card.style.opacity    = '0';
          card.style.transform  = 'translateY(-8px)';
          setTimeout(() => {
            card.remove();
            updatePendingCount();
          }, 400);
        }, 700);
      }

      updatePendingCount();
      showToast('Classification removed — feedback returned to pending.', 'default');

    } else {
      if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
      showToast(data.error || 'Failed to remove classification.', 'error');
    }
  } catch (err) {
    if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
    showToast('Network error. Please try again.', 'error');
  }
}

function updatePendingCount() {
  const remaining = document.querySelectorAll(
    '.feedback-classify-card[data-tab="pending"]'
  ).length;

  const pendingTabBtn = document.querySelector('[onclick*="tab-pending"]');
  if (pendingTabBtn) {
    const countBadge = pendingTabBtn.querySelector('.tab-count.pending');
    if (countBadge) {
      remaining > 0 ? (countBadge.textContent = remaining) : countBadge.remove();
    }
  }

  // Update the "Pending Classification" stat card (second stat-num)
  const statNums = document.querySelectorAll('.stat-num');
  if (statNums[1]) statNums[1].textContent = remaining;

  // Update sidebar nav badge
  const navBadge = document.querySelector('.nav-badge');
  if (navBadge) {
    navBadge.textContent  = remaining;
    navBadge.style.display = remaining > 0 ? '' : 'none';
  }
}

// Run filter on page load
document.addEventListener('DOMContentLoaded', () => filterFeedbackCards());