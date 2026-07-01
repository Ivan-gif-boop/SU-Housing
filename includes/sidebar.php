<?php
// includes/sidebar.php

$activePage      = $activePage      ?? '';
$userRole        = $userRole        ?? 'student';
$userName        = $userName        ?? 'Student User';
$userEmail       = $userEmail       ?? null;
$admissionNumber = $admissionNumber ?? null;

$avatarLetter = strtoupper(substr($userName, 0, 1));
$roleLabel    = $userRole === 'admin' ? 'Administrator' : 'Student';

// Subtext under the name in the footer pill:
// admins show their email, students show their admission number
$footerSubtext = $userRole === 'admin'
    ? ($userEmail ?? '')
    : ($admissionNumber ? 'Admission: ' . $admissionNumber : '');

// ── Nav structure ──
$studentNav = [
  'Overview' => [
    ['label' => 'Dashboard', 'href' => BASE_PATH . '/student/dashboard.php', 'key' => 'dashboard'],
  ],
  'Accommodation' => [
    ['label' => 'Browse Hostels', 'href' => BASE_PATH . '/student/browse.php', 'key' => 'browse'],
  ],
  'My Activity' => [
    ['label' => 'My Feedback', 'href' => BASE_PATH . '/student/feedback.php', 'key' => 'feedback'],
  ],
  'Account' => [
    ['label' => 'My Preferences', 'href' => BASE_PATH . '/student/preference_profile.php', 'key' => 'preferences'],
    ['label' => 'My Profile', 'href' => BASE_PATH . '/student/profile.php', 'key' => 'profile'],
  ],
];

$adminNav = [
  'Overview' => [
    ['label' => 'Dashboard', 'href' => BASE_PATH . '/admin/dashboard.php', 'key' => 'dashboard'],
  ],
  'Management' => [
    ['label' => 'Manage Listings', 'href' => BASE_PATH . '/admin/listings.php', 'key' => 'listings'],
    ['label' => 'Feedback', 'href' => BASE_PATH . '/admin/feedback.php', 'key' => 'feedback'],
  ],
  'Account' => [
    ['label' => 'Profile', 'href' => BASE_PATH . '/admin/profile.php', 'key' => 'profile'],
  ],
];

$nav = $userRole === 'admin' ? $adminNav : $studentNav;
?>

<!-- Mobile menu button -->
<button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div id="app">
  <aside id="sidebar">

    <!-- Logo -->
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <div class="sidebar-logo-mark"><span>S</span></div>
        <div class="sidebar-logo-text">
          SU-Housing
          <small>Accommodation Portal</small>
        </div>
      </div>
      <div class="sidebar-role">
        <div class="role-dot"></div>
        <div class="role-label"><?php echo $roleLabel; ?></div>
      </div>
    </div>

    <!-- Nav items -->
    <nav class="sidebar-nav">
      <?php foreach ($nav as $section => $items): ?>
        <div class="nav-section-label"><?php echo $section; ?></div>
        <?php foreach ($items as $item):
          $isActive = ($activePage === $item['key']);
        ?>
          <a href="<?php echo $item['href']; ?>"
             class="nav-item <?php echo $isActive ? 'active' : ''; ?>">
            <?php echo $item['label']; ?>
            <?php if (!empty($item['badge'])): ?>
              <span class="nav-badge"><?php echo $item['badge']; ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <!-- User footer -->
    <div class="sidebar-footer">
      <a href="<?php echo BASE_PATH; ?>/<?php echo $userRole === 'admin' ? 'admin' : 'student'; ?>/profile.php"
         class="user-pill">
        <div class="user-avatar"><?php echo $avatarLetter; ?></div>
        <div class="user-info">
          <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
          <?php if ($footerSubtext): ?>
            <div class="user-email"><?php echo htmlspecialchars($footerSubtext); ?></div>
          <?php endif; ?>
        </div>
        <span class="logout-icon">⇥</span>
      </a>
    </div>

  </aside>

  <!-- Main content wrapper opens here — closed in footer.php -->
  <div id="main-content">