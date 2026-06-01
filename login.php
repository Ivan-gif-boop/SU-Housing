<?php
// login.php
// Backend hook: your partner will add session/auth logic here.
// $error = "Invalid email or password."; — she sets this on failed login.

$pageTitle = 'Sign In';
$error     = $error ?? null;
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
      <h1>Find Verified Student Housing <em>Near Campus</em></h1>
      <p>
        The official Strathmore University portal for discovering and
        accessing approved hostels and apartments around the Madaraka campus.
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
      <p class="auth-subtitle">Sign in to your StrathHousing account.</p>

      <!-- PHP error message hook -->
      <?php if ($error): ?>
        <div class="auth-alert error">⚠️ <?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <!-- Login form -->
      <!-- Backend: action="login.php" method="POST" — your partner fills this in -->
      <form action="#" method="POST">

        <div class="form-group">
          <label for="email">Email Address</label>
          <div class="input-wrap">
            <span class="input-icon">✉</span>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control"
              placeholder="yourname@strathmore.edu"
              required
              autocomplete="email"
            />
          </div>
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
        </div>

        <div style="display:flex; justify-content:flex-end; margin-top:-10px; margin-bottom:20px;">
          <a href="#" style="font-size:13px; color:var(--amber); font-weight:500;">
            Forgot password?
          </a>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">
          Sign In →
        </button>

      </form>

      <div class="auth-switch">
        New student? <a href="/SU-housing/register.php">Create an account</a>
      </div>

    </div>
  </div>

</div><!-- end .auth-page -->

</body>
</html>