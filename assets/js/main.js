// ── Mobile sidebar toggle ──
const mobileMenuBtn   = document.getElementById('mobileMenuBtn');
const sidebar         = document.getElementById('sidebar');
const sidebarOverlay  = document.getElementById('sidebarOverlay');

if (mobileMenuBtn) {
  mobileMenuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    sidebarOverlay.classList.toggle('open');
  });
}

if (sidebarOverlay) {
  sidebarOverlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    sidebarOverlay.classList.remove('open');
  });
}

// ── Modal helpers ──
function openModal(id)  { document.getElementById(id).classList.add('open');    }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.classList.remove('open');
  });
});

// ── Toast ──
let toastTimer;
function showToast(message, type = 'default') {
  const toast = document.getElementById('toast');
  if (!toast) return;

  const icons = { success: '✅', error: '❌', warning: '⚠️', default: 'ℹ️' };
  toast.innerHTML = `<span>${icons[type] || icons.default}</span> ${message}`;
  toast.style.display = 'flex';

  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { toast.style.display = 'none'; }, 3500);
}

// ── Tab switcher ──
function switchTab(btn, targetId) {
  const pageBody = btn.closest('.page-body, .card-body');
  if (pageBody) {
    pageBody.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  }
  btn.closest('.tabs').querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  const target = document.getElementById(targetId);
  if (target) target.classList.add('active');
}