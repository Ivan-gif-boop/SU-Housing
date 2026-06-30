<?php
// register.php
// Backend hook: partner adds POST processing logic above this line.
// $error   = "Admission number already registered.";
// $success = "Account created. Please sign in.";

$pageTitle = 'Create Account';
$error     = $error   ?? null;
$success   = $success ?? null;

$programmes = [
  'Bachelor of Science in Tourism Management',
  'Bachelor of Science in Hospitality Management',
  'Bachelor of Business Science: Financial Engineering',
  'Bachelor of Business Science: Financial Economics',
  'Bachelor of Business Science: Acturial Science',
  'Bachelor of Science in Informatics and Computer Science',
  'Bachelor of Business Information Technology',
  'Bachelor of Laws',
  'Bachelor of Arts in Communication',
  'Bachelor of Arts in International Studies',
  'Bachelor of Arts in Development Studies and Philosophy',
  'Bachelor of Science in Supply Chain and Operations Management',
  'Bachelor of Financial Services',
  'Bachelor of Science in Electrical and Electronics Engineering',
  'BSc in Statisctics and Data Science',
  'Bachelor of Commerce',
  'Diploma in International Relations',
  'Diploma in Business Management',
  'Diploma in Business Information Technology',
  'Diploma in Business Creation and Entrepreneurship',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $pageTitle; ?> — SU-Housing</title>
  <link rel="stylesheet" href="/SU-housing/assets/css/variables.css"/>
  <link rel="stylesheet" href="/SU-housing/assets/css/base.css"/>
  <link rel="stylesheet" href="/SU-housing/assets/css/components.css"/>
  <link rel="stylesheet" href="/SU-housing/assets/css/auth.css"/>
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
      <h1>Join <em>Thousands</em> of Strathmore Students</h1>
      <p>
        Create your free account to access verified hostel listings,
        get preference-matched recommendations, and submit feedback
        directly to the Dean of Students office.
      </p>
    </div>

    <div class="auth-stats">
      <div class="auth-stat">
        <div class="auth-stat-num">50+</div>
        <div class="auth-stat-label">Verified Hostels</div>
      </div>
      <div class="auth-stat">
        <div class="auth-stat-num">Free</div>
        <div class="auth-stat-label">Always Free</div>
      </div>
      <div class="auth-stat">
        <div class="auth-stat-num">100%</div>
        <div class="auth-stat-label">Institutional</div>
      </div>
      <div class="auth-stat">
        <div class="auth-stat-num">Live</div>
        <div class="auth-stat-label">Real-Time Listings</div>
      </div>
    </div>
  </div>

  <!-- ── Right: Register form ── -->
  <div class="auth-right">
    <div class="auth-card" style="max-width: 460px;">

      <h2>Create your account</h2>
      <p class="auth-subtitle">
        Students only. You will need your admission number.
      </p>

      <!-- PHP messages (server-rendered fallback, rarely used now that JS handles it) -->
      <?php if ($error): ?>
        <div class="auth-alert error">
           <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="auth-alert success">
          ✓ <?php echo htmlspecialchars($success); ?>
        </div>
      <?php endif; ?>

      <!-- API error/success alert (JS-driven) -->
      <div class="auth-alert error"
           id="registerError"
           style="display:none; margin-bottom:16px;">
      </div>
      <div class="auth-alert success"
           id="registerSuccess"
           style="display:none; margin-bottom:16px;">
      </div>

      <form id="registerForm" novalidate>

        <!-- Full Name -->
        <div class="form-group">
          <label for="full_name">Full Name</label>
          <input
            type="text"
            id="full_name"
            name="full_name"
            class="form-control"
            placeholder="Enter your full name"
            required
            autocomplete="name"
          />
          <div class="form-error" id="err-full_name"></div>
        </div>

        <!-- Admission Number -->
        <div class="form-group">
          <label for="admission_no">Admission Number</label>
          <div class="input-wrap">
            <input
              type="text"
              id="admission_no"
              name="admission_no"
              class="form-control"
              placeholder="Enter your admission number"
              required
              maxlength="10"
              autocomplete="off"
            />
          </div>
          <div class="form-hint">
            Must be a valid admission number (e.g. 6 digits)
          </div>
          <div class="form-error" id="err-admission_no"></div>
        </div>

        <!-- Programme -->
        <div class="form-group">
          <label for="programme">Programme of Study</label>
          <select
            id="programme"
            name="programme"
            class="form-control"
            required
          >
            <option value="" disabled selected>Select programme…</option>
            <?php foreach ($programmes as $prog): ?>
              <option value="<?php echo htmlspecialchars($prog); ?>">
                <?php echo htmlspecialchars($prog); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-error" id="err-programme"></div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              placeholder="Enter your password"
              required
              minlength="8"
              autocomplete="new-password"
            />
          </div>
          <div class="form-hint">Minimum 8 characters.</div>
          <div class="form-error" id="err-password"></div>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <div class="input-wrap">
            <input
              type="password"
              id="confirm_password"
              name="confirm_password"
              class="form-control"
              placeholder="Confirm your password"
              required
              autocomplete="new-password"
            />
          </div>
          <div class="form-error" id="err-confirm_password"></div>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" id="registerSubmitBtn">
          Create Account →
        </button>

      </form>

      <div class="auth-switch">
        Already have an account?
        <a href="/SU-housing/login.php">Sign in</a>
      </div>

    </div>
  </div>

</div>

<script>
// ── Client-side validation ──
// NFR-02: inline field-level error messages

const form = document.getElementById('registerForm');

function showError(fieldId, message) {
  const el = document.getElementById('err-' + fieldId);
  const input = document.getElementById(fieldId);
  if (el) el.textContent = message;
  if (input) input.classList.add('is-error');
}

function clearError(fieldId) {
  const el = document.getElementById('err-' + fieldId);
  const input = document.getElementById(fieldId);
  if (el) el.textContent = '';
  if (input) input.classList.remove('is-error');
}

// Clear errors on input
['full_name','admission_no','programme','password','confirm_password']
  .forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', () => clearError(id));
  });

form.addEventListener('submit', async function(e) {
  e.preventDefault();

  const errorEl   = document.getElementById('registerError');
  const successEl = document.getElementById('registerSuccess');
  const btn       = document.getElementById('registerSubmitBtn');

  errorEl.style.display   = 'none';
  successEl.style.display = 'none';

  let valid = true;

  // Full name
  const name = document.getElementById('full_name').value.trim();
  if (!name) {
    showError('full_name', 'Full name is required.');
    valid = false;
  }

  // Admission number — must be digits only, 5-8 chars
  const admNo = document.getElementById('admission_no').value.trim();
  if (!admNo) {
    showError('admission_no', 'Admission number is required.');
    valid = false;
  } else if (!/^\d{5,8}$/.test(admNo)) {
    showError('admission_no', 'Must be a valid admission number (e.g. 6 digits).');
    valid = false;
  }

  // Programme
  const prog = document.getElementById('programme').value;
  if (!prog) {
    showError('programme', 'Please select your programme.');
    valid = false;
  }

  // Password length
  const pw = document.getElementById('password').value;
  if (pw.length < 8) {
    showError('password', 'Password must be at least 8 characters.');
    valid = false;
  }

  // Confirm password match
  const cpw = document.getElementById('confirm_password').value;
  if (pw !== cpw) {
    showError('confirm_password', 'Passwords do not match.');
    valid = false;
  }

  if (!valid) return;

  btn.disabled    = true;
  btn.textContent = 'Creating account…';

  try {
    // NOTE: keys below are camelCase to match api/auth/register.php exactly
    // (fullName, admissionNumber, confirmPassword) — do not change to snake_case.
    const response = await fetch('/SU-Housing/api/auth/register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        fullName: name,
        admissionNumber: admNo,
        programme: prog,
        password: pw,
        confirmPassword: cpw,
      }),
    });

    const data = await response.json();

    if (response.ok) {
      successEl.textContent   = data.message || 'Account created. Redirecting…';
      successEl.style.display = 'flex';
      setTimeout(() => {
        window.location.href = '/SU-Housing/student/dashboard.php';
      }, 1200);
    } else {
      errorEl.textContent   = data.error || 'Registration failed.';
      errorEl.style.display = 'flex';
      btn.disabled          = false;
      btn.textContent       = 'Create Account →';
    }

  } catch (err) {
    errorEl.textContent   = 'Connection error. Please try again.';
    errorEl.style.display = 'flex';
    btn.disabled          = false;
    btn.textContent       = 'Create Account →';
  }
});
</script>

</body>
</html>
