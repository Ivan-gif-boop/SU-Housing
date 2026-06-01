<?php
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
$userRole   = 'student';
$userName   = 'Ivan Wachira';
$userEmail  = 'ivan@strathmore.edu';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

  <div class="page-header">
    <div>
      <div class="page-breadcrumb">Home</div>
      <h1 class="page-title">Step 2 Complete ✓</h1>
      <p class="page-subtitle">Shared shell is working. CSS and sidebar loaded correctly.</p>
    </div>
  </div>

  <div class="page-body">
    <div class="stats-grid">
      <div class="stat-card animate-fade-up delay-1">
        <div class="stat-icon amber">🏠</div>
        <div><div class="stat-num">12</div><div class="stat-label">Available Hostels</div></div>
      </div>
      <div class="stat-card animate-fade-up delay-2">
        <div class="stat-icon blue">💬</div>
        <div><div class="stat-num">3</div><div class="stat-label">My Inquiries</div></div>
      </div>
      <div class="stat-card animate-fade-up delay-3">
        <div class="stat-icon green">📝</div>
        <div><div class="stat-num">1</div><div class="stat-label">My Feedback</div></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <span class="card-title">Component Test</span>
      </div>
      <div class="card-body">
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px;">
          <button class="btn btn-primary">Primary</button>
          <button class="btn btn-navy">Navy</button>
          <button class="btn btn-outline">Outline</button>
          <button class="btn btn-danger btn-sm">Danger</button>
          <button class="btn btn-success btn-sm" onclick="showToast('Everything is working!', 'success')">Test Toast</button>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <span class="badge badge-green">Available</span>
          <span class="badge badge-amber">Limited</span>
          <span class="badge badge-red">Full</span>
          <span class="badge badge-blue">Admin</span>
          <span class="tag tag-blue">WiFi</span>
          <span class="tag tag-green">Water</span>
        </div>
      </div>
    </div>
  </div>

<?php include 'includes/footer.php'; ?>