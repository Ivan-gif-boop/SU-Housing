<?php
// admin/profile.php

session_start();

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/db.php';
$db = getDB();

$adminId = $_SESSION['adminId'];

// ── Admin info from the real `admins` table ──
$stmt = $db->prepare(
    'SELECT fullName, email FROM admins WHERE adminId = ?'
);
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

// ── Stats for avatar panel ──
$totalListings = (int) $db->query(
    'SELECT COUNT(*) FROM hostel_listings WHERE isActive = 1'
)->fetchColumn();

// "Classified" = feedback rows this admin (or any admin) has classified
$totalClassified = (int) $db->query(
    'SELECT COUNT(*) FROM feedback WHERE classification IS NOT NULL'
)->fetchColumn();

$totalFeedback = (int) $db->query(
    'SELECT COUNT(*) FROM feedback'
)->fetchColumn();

$success = null;
$error   = null;

// ── Handle profile name update ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fullName'])) {
    $newName = trim($_POST['fullName']);

    if ($newName === '') {
        $error = 'Name cannot be empty.';
    } else {
        $updateStmt = $db->prepare(
            'UPDATE admins SET fullName = ? WHERE adminId = ?'
        );
        $updateStmt->execute([$newName, $adminId]);

        $_SESSION['fullName'] = $newName;
        $admin['fullName']    = $newName;
        $success = 'Profile updated successfully.';
    }
}

// ── Handle password change ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newPassword'])) {
    $currentPw = $_POST['currentPassword'] ?? '';
    $newPw     = $_POST['newPassword']     ?? '';
    $confirmPw = $_POST['confirmNewPassword'] ?? '';

    $hashStmt = $db->prepare('SELECT passwordHash FROM admins WHERE adminId = ?');
    $hashStmt->execute([$adminId]);
    $row = $hashStmt->fetch();

    if (!$row || !password_verify($currentPw, $row['passwordHash'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPw) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($newPw !== $confirmPw) {
        $error = 'New passwords do not match.';
    } else {
        $newHash = password_hash($newPw, PASSWORD_BCRYPT);
        $updateStmt = $db->prepare(
            'UPDATE admins SET passwordHash = ? WHERE adminId = ?'
        );
        $updateStmt->execute([$newHash, $adminId]);
        $success = 'Password updated successfully.';
    }
}

$userName = $admin['fullName'] ?? ($_SESSION['fullName'] ?? 'Administrator');
$userEmail = $admin['email'] ?? ($_SESSION['email'] ?? null);

$avatarLetter = strtoupper(substr($userName, 0, 1));

// ── Page meta ──
$pageTitle  = 'Admin Profile';
$activePage = 'profile';
$userRole   = 'admin';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

  <!-- ── Page header ── -->
  <div class="page-header">
    <div class="page-title-group">
      <div class="page-breadcrumb">Admin › Profile</div>
      <h1 class="page-title">Admin Profile</h1>
      <p class="page-subtitle">
        Office of the Dean of Students account settings.
      </p>
    </div>
  </div>

  <div class="page-body">

    <div class="profile-layout">

      <!-- ════ LEFT: Avatar panel ════ -->
      <div class="profile-sidebar-col">

        <div class="card profile-avatar-card">
          <div class="card-body" style="text-align:center;">

            <div class="profile-avatar">
              <?php echo $avatarLetter; ?>
            </div>

            <div class="profile-name">
              <?php echo htmlspecialchars($userName); ?>
            </div>
            <div class="profile-role-tag admin-role-tag">
              Administrator
            </div>

            <div class="profile-stat-row">
              <div class="profile-stat">
                <div class="profile-stat-num"><?php echo $totalListings; ?></div>
                <div class="profile-stat-label">Listings</div>
              </div>
              <div class="profile-stat-divider"></div>
              <div class="profile-stat">
                <div class="profile-stat-num"><?php echo $totalFeedback; ?></div>
                <div class="profile-stat-label">Feedback</div>
              </div>
              <div class="profile-stat-divider"></div>
              <div class="profile-stat">
                <div class="profile-stat-num"><?php echo $totalClassified; ?></div>
                <div class="profile-stat-label">Classified</div>
              </div>
            </div>

          </div>
        </div>

        <!-- Quick links -->
        <div class="card" style="margin-top:16px;">
          <div class="card-header">
            <span class="card-title" style="font-size:15px;">Quick Links</span>
          </div>
          <div class="card-body"
               style="display:flex; flex-direction:column; gap:8px; padding:16px;">
            <a href="/SU-Housing/admin/listings.php" class="btn btn-outline btn-full">
              🏠 Manage Listings
            </a>
            <a href="/SU-Housing/admin/feedback.php" class="btn btn-outline btn-full">
              📋 Review Feedback
            </a>
            <a href="/SU-Housing/admin/dashboard.php" class="btn btn-outline btn-full">
              📊 View Analytics
            </a>
            <hr class="divider" style="margin:4px 0;"/>
            <a href="/SU-Housing/logout.php" class="btn btn-danger btn-full">
              Sign Out
            </a>
          </div>
        </div>

      </div><!-- end profile-sidebar-col -->

      <!-- ════ RIGHT: Forms ════ -->
      <div class="profile-main-col">

        <?php if ($success): ?>
          <div class="alert alert-success mb-16">
            ✓ <?php echo htmlspecialchars($success); ?>
          </div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-error mb-16">
            ⚠️ <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <!-- ── Account information ── -->
        <div class="card mb-16">
          <div class="card-header">
            <span class="card-title">Account Information</span>
          </div>
          <div class="card-body">
            <form action="" method="POST" id="adminProfileForm" novalidate>

              <div class="form-group">
                <label for="adminFullName">Full Name / Office Title</label>
                <input
                  type="text"
                  id="adminFullName"
                  name="fullName"
                  class="form-control"
                  value="<?php echo htmlspecialchars($userName); ?>"
                  required
                />
                <div class="form-error" id="err-adminFullName"></div>
              </div>

              <div class="form-group">
                <label>Email</label>
                <input
                  type="text"
                  class="form-control"
                  value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>"
                  readonly
                />
                <div class="form-hint">
                  Email is fixed and cannot be changed here.
                </div>
              </div>

              <div class="form-group">
                <label>Role</label>
                <input
                  type="text"
                  class="form-control"
                  value="Administrator — Mentorship Office"
                  readonly
                />
                <div class="form-hint">
                  Role is assigned by the system and cannot be changed here.
                </div>
              </div>

              <button
                type="submit"
                class="btn btn-primary"
                onclick="return validateAdminProfile()"
              >
                Save Changes
              </button>

            </form>
          </div>
        </div>

        <!-- ── Change password ── -->
        <div class="card mb-16">
          <div class="card-header">
            <span class="card-title">Change Password</span>
          </div>
          <div class="card-body">
            <form action="" method="POST" id="adminPasswordForm" novalidate>

              <div class="form-group">
                <label for="adminCurrentPw">Current Password</label>
                <input
                  type="password"
                  id="adminCurrentPw"
                  name="currentPassword"
                  class="form-control"
                  placeholder="••••••••"
                  required
                />
                <div class="form-error" id="err-adminCurrentPw"></div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="adminNewPw">New Password</label>
                  <input
                    type="password"
                    id="adminNewPw"
                    name="newPassword"
                    class="form-control"
                    placeholder="••••••••"
                    minlength="8"
                    required
                  />
                  <div class="form-error" id="err-adminNewPw"></div>
                </div>
                <div class="form-group">
                  <label for="adminConfirmPw">Confirm New Password</label>
                  <input
                    type="password"
                    id="adminConfirmPw"
                    name="confirmNewPassword"
                    class="form-control"
                    placeholder="••••••••"
                    required
                  />
                  <div class="form-error" id="err-adminConfirmPw"></div>
                </div>
              </div>

              <div class="form-hint" style="margin-top:-8px; margin-bottom:16px;">
                Minimum 8 characters.
              </div>

              <button
                type="submit"
                class="btn btn-navy"
                onclick="return validateAdminPassword()"
              >
                Update Password
              </button>

            </form>
          </div>
        </div>

        <!-- ── System info ── -->
        <div class="card" style="border-color: var(--red-light);">
          <div class="card-header" style="border-color: var(--red-light);">
            <span class="card-title" style="color:var(--red);">System Information</span>
          </div>
          <div class="card-body">
            <div class="detail-grid" style="grid-template-columns:1fr 1fr; gap:16px; display:grid;">
              <div>
                <div class="summary-detail-label">System</div>
                <div class="summary-detail-value">SU-Housing v1.0</div>
              </div>
              <div>
                <div class="summary-detail-label">Environment</div>
                <div class="summary-detail-value">XAMPP / Apache</div>
              </div>
              <div>
                <div class="summary-detail-label">Database</div>
                <div class="summary-detail-value">MySQL (MariaDB)</div>
              </div>
              <div>
                <div class="summary-detail-label">Map Provider</div>
                <div class="summary-detail-value">Leaflet + OpenStreetMap</div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- end profile-main-col -->

    </div><!-- end profile-layout -->

  </div><!-- end page-body -->

<script>
function validateAdminProfile() {
  const name = document.getElementById('adminFullName').value.trim();
  const err  = document.getElementById('err-adminFullName');
  const inp  = document.getElementById('adminFullName');

  if (!name) {
    err.textContent = 'Name is required.';
    inp.classList.add('is-error');
    return false;
  }

  err.textContent = '';
  inp.classList.remove('is-error');
  return true;
}

function validateAdminPassword() {
  let valid = true;

  ['adminCurrentPw', 'adminNewPw', 'adminConfirmPw'].forEach(id => {
    document.getElementById(id).classList.remove('is-error');
    document.getElementById('err-' + id).textContent = '';
  });

  if (!document.getElementById('adminCurrentPw').value) {
    document.getElementById('err-adminCurrentPw').textContent = 'Current password is required.';
    document.getElementById('adminCurrentPw').classList.add('is-error');
    valid = false;
  }

  const newPw = document.getElementById('adminNewPw').value;
  if (newPw.length < 8) {
    document.getElementById('err-adminNewPw').textContent = 'Minimum 8 characters.';
    document.getElementById('adminNewPw').classList.add('is-error');
    valid = false;
  }

  const confirm = document.getElementById('adminConfirmPw').value;
  if (newPw !== confirm) {
    document.getElementById('err-adminConfirmPw').textContent = 'Passwords do not match.';
    document.getElementById('adminConfirmPw').classList.add('is-error');
    valid = false;
  }

  return valid;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>