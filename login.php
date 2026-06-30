<?php
$pageTitle = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $pageTitle; ?> — SU-Housing</title>
  <link rel="stylesheet" href="/SU-Housing/assets/css/variables.css"/>
  <link rel="stylesheet" href="/SU-Housing/assets/css/base.css"/>
  <link rel="stylesheet" href="/SU-Housing/assets/css/components.css"/>
  <link rel="stylesheet" href="/SU-Housing/assets/css/auth.css"/>
</head>
<body>

<div class="auth-page">

  <!-- ── Left: Branding panel ── -->
  <div class="auth-left">
    <div class="auth-logo">
      <div class="auth-logo-mark"><span>S</span></div>
      <div class="auth-logo-name">SU-Housing</div>
    </div>

    <div class="auth-hero">
      <div class="verified-badge">
        University Verified Accommodation
      </div>
      <h1>Find Verified Student Housing <em>Near Campus</em></h1>
      <p>
        The official Strathmore University portal for
        discovering and accessing approved hostels and
        apartments around the Madaraka campus.
      </p>
    </div>

    <div class="auth-stats">
      <div class="auth-stat">
        <div class="auth-stat-num">50+</div>
        <div class="auth-stat-label">Verified Hostels</div>
      </div>
      <div class="auth-stat">
        <div class="auth-stat-num">45+</div>
        <div class="auth-stat-label">Available Now</div>
      </div>
      <div class="auth-stat">
        <div class="auth-stat-num">2,000+</div>
        <div class="auth-stat-label">Students Housed</div>
      </div>
      <div class="auth-stat">
        <div class="auth-stat-num">&lt;15m</div>
        <div class="auth-stat-label">Walking Distance</div>
      </div>
    </div>
  </div>

  <!-- ── Right: Login form ── -->
  <div class="auth-right">
    <div class="auth-card">

      <h2>Welcome back</h2>
      <p class="auth-subtitle">
        Sign in to your SU-Housing account.
      </p>

      <!-- ── Role toggle ── -->
      <div class="role-toggle">
        <button
          class="role-toggle-btn active"
          id="studentToggle"
          onclick="switchLoginRole('student')"
          type="button"
        >
          Student
        </button>
        <button
          class="role-toggle-btn"
          id="adminToggle"
          onclick="switchLoginRole('admin')"
          type="button"
        >
          Administrator
        </button>
      </div>

      <!-- API error alert -->
      <div class="auth-alert error"
           id="loginError"
           style="display:none; margin-bottom:16px;">
      </div>

      <!-- ════ STUDENT LOGIN FORM ════ -->
      <div id="studentForm">

        <form id="studentLoginForm" novalidate>

          <div class="form-group">
            <label for="admission_no">
              Admission Number
            </label>
            <div class="input-wrap">
              <input
                type="text"
                id="admission_no"
                class="form-control"
                placeholder= "Enter your admission number"
                autocomplete="username"
              />
            </div>
            <div class="form-error"
                 id="err-admission_no"></div>
          </div>

          <div class="form-group">
            <label for="student_password">Password</label>
            <div class="input-wrap">
              <input
                type="password"
                id="student_password"
                class="form-control"
                autocomplete="current-password"
                placeholder="Enter your password"
              />
            </div>
            <div class="form-error"
                 id="err-student_password"></div>
          </div>

          <button
            type="submit"
            class="btn btn-primary btn-full btn-lg"
            id="studentSubmitBtn"
          >
            Sign In →
          </button>

        </form>

        <div class="auth-switch">
          New student?
          <a href="/SU-Housing/register.php">
            Create an account
          </a>
        </div>

      </div><!-- end studentForm -->

      <!-- ════ ADMIN LOGIN FORM ════ -->
      <div id="adminForm" style="display:none;">

        <div class="alert alert-info"
             style="margin-bottom:20px; font-size:13px;">
          🔑 Administrator access only. Contact the Dean of
          Students office if you need assistance.
        </div>

        <form id="adminLoginForm" novalidate>

          <div class="form-group">
            <label for="admin_email">
              Email Address
            </label>
            <div class="input-wrap">
              <span class="input-icon">✉</span>
              <input
                type="email"
                id="admin_email"
                class="form-control"
                placeholder="Enter your email"
                autocomplete="email"
              />
            </div>
            <div class="form-error"
                 id="err-admin_email"></div>
          </div>

          <div class="form-group">
            <label for="admin_password">Password</label>
            <div class="input-wrap">
              <input
                type="password"
                id="admin_password"
                class="form-control"
                placeholder="Enter your password"
                autocomplete="current-password"
              />
            </div>
            <div class="form-error"
                 id="err-admin_password"></div>
          </div>

          <button
            type="submit"
            class="btn btn-navy btn-full btn-lg"
            id="adminSubmitBtn"
          >
            Sign In as Administrator →
          </button>

        </form>

      </div><!-- end adminForm -->

    </div>
  </div>

</div><!-- end auth-page -->

<script>
// ── Role toggle ──
function switchLoginRole(role) {
  const studentForm   = document.getElementById('studentForm');
  const adminForm     = document.getElementById('adminForm');
  const studentToggle = document.getElementById('studentToggle');
  const adminToggle   = document.getElementById('adminToggle');
  const errorEl       = document.getElementById('loginError');

  // Hide error on switch
  errorEl.style.display = 'none';

  if (role === 'student') {
    studentForm.style.display   = 'block';
    adminForm.style.display     = 'none';
    studentToggle.classList.add('active');
    adminToggle.classList.remove('active');
  } else {
    adminForm.style.display     = 'block';
    studentForm.style.display   = 'none';
    adminToggle.classList.add('active');
    studentToggle.classList.remove('active');
  }
}

// ── Show / clear field errors ──
function showFieldError(fieldId, msg) {
  const el  = document.getElementById('err-' + fieldId);
  const inp = document.getElementById(fieldId);
  if (el)  el.textContent = msg;
  if (inp) inp.classList.add('is-error');
}

function clearFieldError(fieldId) {
  const el  = document.getElementById('err-' + fieldId);
  const inp = document.getElementById(fieldId);
  if (el)  el.textContent = '';
  if (inp) inp.classList.remove('is-error');
}

// Clear errors on input
[
  'admission_no',
  'student_password',
  'admin_email',
  'admin_password'
].forEach(id => {
  document.getElementById(id)
    ?.addEventListener('input', () => clearFieldError(id));
});

// ── Shared API call ──
async function submitLogin(payload, btnId) {
  const errorEl = document.getElementById('loginError');
  const btn     = document.getElementById(btnId);

  btn.disabled    = true;
  btn.textContent = 'Signing in…';
  errorEl.style.display = 'none';

  try {
    const response = await fetch(
      '/SU-Housing/api/auth/login.php',
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      }
    );

    const data = await response.json();

    if (response.ok) {
      // Redirect based on role
      if (data.role === 'admin') {
        window.location.href =
          '/SU-Housing/admin/dashboard.php';
      } else {
        window.location.href =
          '/SU-Housing/student/dashboard.php';
      }
    } else {
      errorEl.textContent   = data.error || 'Login failed.';
      errorEl.style.display = 'flex';
      btn.disabled          = false;
      btn.textContent       = btnId === 'studentSubmitBtn'
        ? 'Sign In →'
        : 'Sign In as Administrator →';
    }

  } catch (err) {
    errorEl.textContent   =
      'Connection error. Please try again.';
    errorEl.style.display = 'flex';
    btn.disabled          = false;
    btn.textContent       = btnId === 'studentSubmitBtn'
      ? 'Sign In →'
      : 'Sign In as Administrator →';
  }
}

// ── Student form submit ──
document.getElementById('studentLoginForm')
  .addEventListener('submit', function(e) {
  e.preventDefault();
  let valid = true;

  const admNo = document.getElementById('admission_no')
                  .value.trim();
  if (!admNo) {
    showFieldError('admission_no',
      'Admission number is required.');
    valid = false;
  }

  const pw = document.getElementById('student_password')
               .value;
  if (!pw) {
    showFieldError('student_password',
      'Password is required.');
    valid = false;
  }

  if (!valid) return;

  submitLogin(
    { admissionNumber: admNo, password: pw },
    'studentSubmitBtn'
  );
});

// ── Admin form submit ──
document.getElementById('adminLoginForm')
  .addEventListener('submit', function(e) {
  e.preventDefault();
  let valid = true;

  const email = document.getElementById('admin_email')
                  .value.trim();
  if (!email) {
    showFieldError('admin_email', 'Email is required.');
    valid = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showFieldError('admin_email',
      'Please enter a valid email address.');
    valid = false;
  }

  const pw = document.getElementById('admin_password')
               .value;
  if (!pw) {
    showFieldError('admin_password',
      'Password is required.');
    valid = false;
  }

  if (!valid) return;

  submitLogin(
    { email: email, password: pw },
    'adminSubmitBtn'
  );
});
</script>

</body>
</html>