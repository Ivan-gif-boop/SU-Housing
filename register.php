<?php
// register.php
// Backend hook: your partner adds form processing logic here.
// $error   = "Email already registered."; — she sets on duplicate
// $success = "Account created. Please sign in."; — she sets on success

$pageTitle = 'Create Account';
$error     = $error   ?? null;
$success   = $success ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $pageTitle; ?> — StrathHousing</title>
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
      <div class="auth-logo-name">StrathHousing</div>
    </div>

    <div class="auth-hero">
      <div class="verified-badge">University Verified Accommodation</div>
      <h1>Join <em>Thousands</em> of Strathmore Students</h1>
      <p>
        Create your free account to access verified hostel listings,
        submit inquiries directly to the Dean of Students office,
        and track your accommodation search in one place.
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
    <div class="auth-card" style="max-width:460px;">

      <h2>Create your account</h2>
      <p class="auth-subtitle">Students only. Use your Strathmore email.</p>

      <!-- PHP messages -->
      <?php if ($error): ?>
        <div class="auth-alert error">⚠️ <?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="auth-alert success">✓ <?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <!-- Register form -->
      <!-- Backend: action="register.php" method="POST" — partner fills in -->
      <form action="#" method="POST">

        <div class="form-row">
          <div class="form-group">
            <label for="first_name">First Name</label>
            <input
              type="text"
              id="first_name"
              name="first_name"
              class="form-control"
              placeholder="John"
              required
            />
          </div>
          <div class="form-group">
            <label for="last_name">Last Name</label>
            <input
              type="text"
              id="last_name"
              name="last_name"
              class="form-control"
              placeholder="Doe"
              required
            />
          </div>
        </div>

        <div class="form-group">
          <label for="admission_no">Admission Number</label>
          <div class="input-wrap">
            <span class="input-icon">🎓</span>
            <input
              type="text"
              id="admission_no"
              name="admission_no"
              class="form-control"
              placeholder="176XXX"
              required
            />
          </div>
          <div class="form-hint">Your 6-digit Strathmore admission number</div>
        </div>

        <div class="form-group">
          <label for="reg_email">Email Address</label>
          <div class="input-wrap">
            <span class="input-icon">✉</span>
            <input
              type="email"
              id="reg_email"
              name="email"
              class="form-control"
              placeholder="yourname@strathmore.edu"
              required
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="reg_password">Password</label>
            <input
              type="password"
              id="reg_password"
              name="password"
              class="form-control"
              placeholder="••••••••"
              required
              minlength="8"
            />
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input
              type="password"
              id="confirm_password"
              name="confirm_password"
              class="form-control"
              placeholder="••••••••"
              required
            />
          </div>
        </div>

        <div class="form-hint" style="margin-top:-10px; margin-bottom:18px;">
          Password must be at least 8 characters.
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">
          Create Account →
        </button>

      </form>

      <div class="auth-switch">
        Already have an account? <a href="/SU-housing/login.php">Sign in</a>
      </div>

    </div>
  </div>

</div><!-- end .auth-page -->

<script>
// Client-side password match validation
document.querySelector('form').addEventListener('submit', function(e) {
  const pw  = document.getElementById('reg_password').value;
  const cpw = document.getElementById('confirm_password').value;
  if (pw !== cpw) {
    e.preventDefault();
    alert('Passwords do not match. Please try again.');
  }
});
</script>

</body>
</html>