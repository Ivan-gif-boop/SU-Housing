<?php
$pageTitle = 'Sign In';
$error     = $error ?? null;
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
      <div class="verified-badge">University Verified Accommodation</div>
      <h1>Find Verified Student Housing <em>Near Campus</em></h1>
      <p>
        The official Strathmore University portal for discovering
        and accessing approved hostels and apartments around the
        Madaraka campus.
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
        Sign in with your Strathmore admission number.
      </p>

      <?php if ($error): ?>
        <div class="auth-alert error">
          ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form action="/SU-Housing/api/auth/login.php" method="POST">

        <div class="form-group">
          <label for="admission_no">Admission Number</label>
          <div class="input-wrap">
            <span class="input-icon">🎓</span>
            <input
              type="text"
              id="admission_no"
              name="admission_no"
              class="form-control"
              placeholder="e.g. 176830"
              required
              autocomplete="username"
            />
          </div>
          <div class="form-error" id="err-admission_no"></div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              placeholder="••••••••"
              required
              autocomplete="current-password"
            />
          </div>
          <div class="form-error" id="err-password"></div>
        </div>

        <div style="display:flex; justify-content:flex-end;
                    margin-top:-8px; margin-bottom:20px;">
          <a href="#"
             style="font-size:13px;color:var(--amber);font-weight:500;">
            Forgot password?
          </a>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">
          Sign In →
        </button>

      </form>

      <div class="auth-switch">
        New student?
      <a href="/SU-Housing/register.php">Create an account</a>
      </div>

    </div>
  </div>

</div>

<script>
const loginForm = document.getElementById('loginForm');

function showError(fieldId, msg) {
  const el  = document.getElementById('err-' + fieldId);
  const inp = document.getElementById(fieldId);
  if (el)  el.textContent = msg;
  if (inp) inp.classList.add('is-error');
}

function clearError(fieldId) {
  const el  = document.getElementById('err-' + fieldId);
  const inp = document.getElementById(fieldId);
  if (el)  el.textContent = '';
  if (inp) inp.classList.remove('is-error');
}

['admission_no', 'password'].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('input', () => clearError(id));
});

loginForm.addEventListener('submit', async function(e) {
  e.preventDefault();
  let valid = true;

  const adm = document.getElementById('admission_no').value.trim();
  if (!adm) {
    showError('admission_no', 'Admission number is required.');
    valid = false;
  }

  const pw = document.getElementById('password').value;
  if (!pw) {
    showError('password', 'Password is required.');
    valid = false;
  }

  if (!valid) return;

  try {
    const response = await fetch('/SU-Housing/api/auth/login.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        admissionNumber: adm,
        password: pw
      })
    });

    const data = await response.json();

    if (response.ok) {
      if (data.role === 'admin') {
        window.location.href = '/SU-Housing/admin/dashboard.php';
      } else {
        window.location.href = '/SU-Housing/student/dashboard.php';
      }
    } else {
      showError('admission_no', data.error || 'Login failed. Please try again.');
    }

  } catch (err) {
    showError('admission_no', 'Network error. Please try again.');
    console.error('Login error:', err);
  }
});
</script>

</body>
</html>
