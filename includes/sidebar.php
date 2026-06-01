<?php
// includes/sidebar.php
// Requires: $activePage (string matching nav item key), $userRole ('student' or 'admin')
// These will come from $_SESSION once backend is wired up.
// For now we use defaults for frontend testing.

$activePage = $activePage ?? '';
$userRole   = $userRole   ?? 'student';
$userName   = $userName   ?? 'Student User';
$userEmail  = $userEmail  ?? 'student@strathmore.edu';

$avatarLetter = strtoupper(substr($userName, 0, 1));
$roleLabel    = $userRole === 'admin' ? 'Administrator' : 'Student';

// ── Nav structure ──
$studentNav = [
  'Overview' => [
    ['icon' => '⊞', 'label' => 'Dashboard', 'href' => BASE_PATH . '/student/dashboard.php', 'key' => 'dashboard'],
  ],
  'Accommodation' => [
    ['icon' => '🔍', 'label' => 'Browse Hostels','href' => BASE_PATH . '/student/browse.php',     'key' => 'browse'],
  ],
  'My Activity' => [
    ['icon' => '💬', 'label' => 'My Inquiries',  'href' => BASE_PATH . '/student/inquiries.php',  'key' => 'inquiries', 'badge' => ''],
    ['icon' => '📝', 'label' => 'My Feedback',   'href' => BASE_PATH . '/student/feedback.php',   'key' => 'feedback'],
  ],
  'Account' => [
    ['icon' => '👤', 'label' => 'My Profile',    'href' => BASE_PATH . '/student/profile.php',    'key' => 'profile'],
  ],
];

$adminNav = [
  'Overview' => [
    ['icon' => '⊞',  'label' => 'Dashboard',      'href' => BASE_PATH . '/admin/dashboard.php',  'key' => 'dashboard'],
  ],
  'Management' => [
    ['icon' => '🏠', 'label' => 'Manage Listings', 'href' => BASE_PATH . '/admin/listings.php',   'key' => 'listings'],
    ['icon' => '💬', 'label' => 'Inquiries',        'href' => BASE_PATH . '/admin/inquiries.php',  'key' => 'inquiries', 'badge' => ''],
    ['icon' => '📋', 'label' => 'Feedback',         'href' => BASE_PATH . '/admin/feedback.php',   'key' => 'feedback',  'badge' => ''],
  ],
  'Account' => [
    ['icon' => '👤', 'label' => 'Profile',          'href' => BASE_PATH . '/admin/profile.php',    'key' => 'profile'],
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
          <?php echo BASE_PATH; ?>
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
            <span class="nav-icon"><?php echo $item['icon']; ?></span>
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
          <div class="user-email"><?php echo htmlspecialchars($userEmail); ?></div>
        </div>
        <span class="logout-icon">⇥</span>
      </a>
    </div>

  </aside>

  <!-- Main content wrapper opens here — closed in footer.php -->
  <div id="main-content">