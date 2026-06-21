<?php
// student/profile.php

// ─────────────────────────────────────────
// BACKEND HOOK ZONE — Michelle fills this in
// ─────────────────────────────────────────
// require_once __DIR__ . '/../includes/auth_check.php';
// requireAuth('student');
// $userName = $_SESSION['user_name'];
//
// require_once __DIR__ . '/../includes/db.php';
// $db = getDB();
//
// $stmt = $db->prepare(
//   'SELECT fullName, admissionNumber, programme
//    FROM users WHERE userId = ?'
// );
// $stmt->execute([$_SESSION['user_id']]);
// $student = $stmt->fetch();
//
// // Feedback count
// $stmt = $db->prepare(
//   'SELECT COUNT(*) FROM feedback WHERE studentId = ?'
// );
// $stmt->execute([$_SESSION['user_id']]);
// $feedbackCount = $stmt->fetchColumn();
//
// // Has preference profile?
// $stmt = $db->prepare(
//   'SELECT profileId FROM student_preference_profiles
//    WHERE userId = ?'
// );
// $stmt->execute([$_SESSION['user_id']]);
// $hasProfile = (bool) $stmt->fetch();
//
// $success = null;
// $error   = null;
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//   // Partner handles update logic here
// }
// ─────────────────────────────────────────

// Frontend defaults
$pageTitle  = 'My Profile';
$activePage = 'profile';
$userRole   = 'student';
$userName   = 'Ivan Wachira';

$student = [
  'fullName'        => 'Ivan Wachira',
  'admissionNumber' => '176830',
  'programme'       => 'Bachelor of Science in Informatics
    and Computer Science',
];

$feedbackCount = 2;
$hasProfile    = true;
$success       = null;
$error         = null;

$avatarLetter  = strtoupper(substr($student['fullName'], 0, 1));

$programmes = [
  'Bachelor of Business Information Technology',
  'Bachelor of Science in Informatics and Computer Science',
  'Bachelor of Commerce',
  'Bachelor of Laws',
  'Bachelor of Arts in Communication',
  'Bachelor of Science in Actuarial Science',
  'Bachelor of Science in Mathematics',
  'Bachelor of Arts in Journalism',
  'Bachelor of Science in Electrical Engineering',
  'Bachelor of Science in Civil Engineering',
  'Diploma in Business Information Technology',
  'Diploma in Supply Chain Management',
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

  <!-- ── Page header ── -->
  <div class="page-header">
    <div class="page-title-group">
      <div class="page-breadcrumb">Home › My Profile</div>
      <h1 class="page-title">My Profile</h1>
      <p class="page-subtitle">
        Manage your account details and preferences.
      </p>
    </div>
  </div>

  <div class="page-body">

    <div class="profile-layout">

      <!-- ════ LEFT: Avatar panel ════ -->
      <div class="profile-sidebar-col">

        <!-- Avatar card -->
        <div class="card profile-avatar-card">
          <div class="card-body" style="text-align:center;">

            <div class="profile-avatar">
              <?php echo $avatarLetter; ?>
            </div>

            <div class="profile-name">
              <?php echo htmlspecialchars(
                $student['fullName']
              ); ?>
            </div>
            <div class="profile-role-tag">Student</div>

            <div class="profile-stat-row">
              <div class="profile-stat">
                <div class="profile-stat-num">
                  <?php echo $feedbackCount; ?>
                </div>
                <div class="profile-stat-label">
                  Feedback
                </div>
              </div>
              <div class="profile-stat-divider"></div>
              <div class="profile-stat">
                <div class="profile-stat-num">
                  <?php echo $hasProfile ? '✓' : '—'; ?>
                </div>
                <div class="profile-stat-label">
                  Preferences
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Quick links -->
        <div class="card" style="margin-top:16px;">
          <div class="card-header">
            <span class="card-title"
                  style="font-size:15px;">
              Quick Links
            </span>
          </div>
          <div class="card-body"
               style="display:flex;
                      flex-direction:column;
                      gap:8px; padding:16px;">
            <a href="/SU-Housing/student/browse.php"
               class="btn btn-outline btn-full">
              🔍 Browse Hostels
            </a>
            <a href="/SU-Housing/student/preference_profile.php"
               class="btn btn-outline btn-full">
              ⚙️ Edit Preferences
            </a>
            <a href="/SU-Housing/student/feedback.php"
               class="btn btn-outline btn-full">
              📝 My Feedback
            </a>
            <hr class="divider" style="margin:4px 0;"/>
            <a href="/SU-Housing/logout.php"
               class="btn btn-danger btn-full">
              Sign Out
            </a>
          </div>
        </div>

      </div><!-- end profile-sidebar-col -->

      <!-- ════ RIGHT: Forms ════ -->
      <div class="profile-main-col">

        <!-- PHP messages -->
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

        <!-- ── Personal information ── -->
        <div class="card mb-16">
          <div class="card-header">
            <span class="card-title">
              Personal Information
            </span>
          </div>
          <div class="card-body">
            <!--
              Backend hook:
              action="/SU-Housing/api/auth/update_profile.php"
              method="POST"
              Michelle adds csrfField() here
            -->
            <form action="#" method="POST"
                  id="profileForm" novalidate>

              <div class="form-group">
                <label for="fullName">Full Name</label>
                <input
                  type="text"
                  id="fullName"
                  name="fullName"
                  class="form-control"
                  value="<?php echo htmlspecialchars(
                    $student['fullName']
                  ); ?>"
                  required
                />
                <div class="form-error"
                     id="err-fullName"></div>
              </div>

              <div class="form-group">
                <label for="admissionNumber">
                  Admission Number
                </label>
                <input
                  type="text"
                  id="admissionNumber"
                  class="form-control"
                  value="<?php echo htmlspecialchars(
                    $student['admissionNumber']
                  ); ?>"
                  readonly
                />
                <div class="form-hint">
                  Admission number cannot be changed.
                </div>
              </div>

              <div class="form-group">
                <label for="programme">
                  Programme of Study
                </label>
                <select id="programme"
                        name="programme"
                        class="form-control">
                  <?php foreach ($programmes as $prog): ?>
                    <option
                      value="<?php echo htmlspecialchars($prog); ?>"
                      <?php echo trim($student['programme']) ===
                        trim($prog) ? 'selected' : ''; ?>
                    >
                      <?php echo htmlspecialchars($prog); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <button type="submit"
                      class="btn btn-primary"
                      onclick="return validateProfile()">
                Save Changes
              </button>

            </form>
          </div>
        </div>

        <!-- ── Change password ── -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Change Password</span>
          </div>
          <div class="card-body">
            <!--
              Backend hook:
              action="/SU-Housing/api/auth/change_password.php"
              method="POST"
              Michelle adds csrfField() here
            -->
            <form action="#" method="POST"
                  id="passwordForm" novalidate>

              <div class="form-group">
                <label for="currentPassword">
                  Current Password
                </label>
                <input
                  type="password"
                  id="currentPassword"
                  name="currentPassword"
                  class="form-control"
                  placeholder="••••••••"
                  required
                />
                <div class="form-error"
                     id="err-currentPassword"></div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="newPassword">
                    New Password
                  </label>
                  <input
                    type="password"
                    id="newPassword"
                    name="newPassword"
                    class="form-control"
                    placeholder="••••••••"
                    minlength="8"
                    required
                  />
                  <div class="form-error"
                       id="err-newPassword"></div>
                </div>
                <div class="form-group">
                  <label for="confirmNewPassword">
                    Confirm New Password
                  </label>
                  <input
                    type="password"
                    id="confirmNewPassword"
                    name="confirmNewPassword"
                    class="form-control"
                    placeholder="••••••••"
                    required
                  />
                  <div class="form-error"
                       id="err-confirmNewPassword"></div>
                </div>
              </div>

              <div class="form-hint" style="margin-top:-8px;
                           margin-bottom:16px;">
                Minimum 8 characters.
              </div>

              <button type="submit"
                      class="btn btn-navy"
                      onclick="return validatePassword()">
                Update Password
              </button>

            </form>
          </div>
        </div>

      </div><!-- end profile-main-col -->

    </div><!-- end profile-layout -->

  </div><!-- end page-body -->

<script>
function validateProfile() {
  const name = document.getElementById('fullName').value.trim();
  const err  = document.getElementById('err-fullName');
  const inp  = document.getElementById('fullName');

  if (!name) {
    err.textContent = 'Full name is required.';
    inp.classList.add('is-error');
    return false;
  }

  err.textContent = '';
  inp.classList.remove('is-error');
  return true;
}

function validatePassword() {
  let valid = true;

  const fields = [
    'currentPassword', 'newPassword', 'confirmNewPassword'
  ];
  fields.forEach(id => {
    const el  = document.getElementById(id);
    const err = document.getElementById('err-' + id);
    err.textContent = '';
    el.classList.remove('is-error');
  });

  const current = document.getElementById(
    'currentPassword'
  ).value;
  if (!current) {
    document.getElementById('err-currentPassword')
      .textContent = 'Current password is required.';
    document.getElementById('currentPassword')
      .classList.add('is-error');
    valid = false;
  }

  const newPw = document.getElementById('newPassword').value;
  if (newPw.length < 8) {
    document.getElementById('err-newPassword')
      .textContent = 'Minimum 8 characters.';
    document.getElementById('newPassword')
      .classList.add('is-error');
    valid = false;
  }

  const confirm = document.getElementById(
    'confirmNewPassword'
  ).value;
  if (newPw !== confirm) {
    document.getElementById('err-confirmNewPassword')
      .textContent = 'Passwords do not match.';
    document.getElementById('confirmNewPassword')
      .classList.add('is-error');
    valid = false;
  }

  return valid;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>