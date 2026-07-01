<?php
session_start();

if (empty($_SESSION['studentId'])) {
    header('Location: /SU-Housing/login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
$db        = getDB();
$studentId = (int)$_SESSION['studentId'];

// Load existing profile if student already has one
$stmt = $db->prepare(
    'SELECT * FROM student_preference_profiles WHERE studentId = ?'
);
$stmt->execute([$studentId]);
$profile    = $stmt->fetch() ?: [];
$hasProfile = !empty($profile);

$p = [
    'studyHabits'         => $profile['studyHabits']        ?? '',
    'sleepSchedule'       => $profile['sleepSchedule']      ?? '',
    'noiseTolerance'      => $profile['noiseTolerance']     ?? '',
    'genderPreference'    => $profile['genderPreference']   ?? '',
    'roomTypePreference'  => $profile['roomTypePreference'] ?? '',
    'budgetMin'           => $profile['budgetMin']          ?? '',
    'budgetMax'           => $profile['budgetMax']          ?? '',
    'preferredLocation'   => $profile['preferredLocation']  ?? '',
    'environmentType'     => $profile['environmentType']    ?? '',
    'curfewPreference'    => $profile['curfewPreference']   ?? '',
];

$pageTitle       = 'Preference Profile';
$activePage      = 'preferences';
$userRole        = 'student';
$userName        = $_SESSION['fullName'] ?? 'Student';
$admissionNumber = $_SESSION['admissionNumber'] ?? null;

$isPostRegistration = isset($_GET['new']) && $_GET['new'] === '1';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

  <!-- ── Page header ── -->
  <div class="page-header">
    <div class="page-title-group">
      <div class="page-breadcrumb">
        <?php if ($isPostRegistration): ?>
          Registration Complete
        <?php else: ?>
          <a href="/SU-Housing/student/profile.php">Account</a> › Edit Preferences
        <?php endif; ?>
      </div>
      <h1 class="page-title">
        <?php echo $hasProfile ? 'Your Preference Profile' : 'Set Up Your Preference Profile'; ?>
      </h1>
      <p class="page-subtitle">
        Help us match you with the best hostel options. All fields are optional.
      </p>
    </div>
    <?php if (!$isPostRegistration): ?>
      <div class="page-actions">
        <a href="/SU-Housing/student/profile.php"
           class="btn btn-outline">← Back to Profile</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="page-body">

    <?php if ($isPostRegistration): ?>
      <div class="alert alert-success mb-16">
        Account created successfully! Complete your preference
        profile below so we can recommend the best hostels for you.
      </div>
    <?php endif; ?>

    <!-- ── Profile status banner ── -->
    <?php if ($hasProfile): ?>
      <div class="alert alert-success mb-24" style="align-items:center;">
        <span style="font-size:18px;">✓</span>
        <div style="flex:1;">
          <strong>Preference profile is active.</strong>
          Hostel listings on your dashboard and browse page are currently
          ranked by how well they match your preferences.
          Your profile was last updated
          <?php echo date('j M Y', strtotime($profile['updatedAt'])); ?>.
        </div>
        <button
          class="btn btn-danger btn-sm"
          style="flex-shrink:0;"
          onclick="confirmResetProfile()"
        >
          Reset Profile
        </button>
      </div>
    <?php else: ?>
      <div class="alert alert-info mb-24" style="align-items:center;">
        <span style="font-size:18px;"></span>
        <div style="flex:1;">
          <strong>No preference profile set.</strong>
          Hostels are currently shown in default order (most recently added first).
          Fill in the form below to get personalised recommendations ranked
          by match percentage.
        </div>
      </div>
    <?php endif; ?>

    <!-- Reset confirmation modal -->
    <div class="modal-overlay" id="resetModal">
      <div class="modal" style="max-width:420px;">
        <div class="modal-header">
          <span class="modal-title">Reset Preference Profile</span>
          <button class="modal-close" onclick="closeModal('resetModal')">✕</button>
        </div>
        <div class="modal-body">
          <p style="font-size:15px; color:var(--gray-600); line-height:1.6;">
            This will delete your preference profile. Hostel listings
            will return to default order (most recently added first)
            and match percentages will no longer appear.
          </p>
          <div class="alert alert-warning" style="margin-top:16px; font-size:13px;">
            You can set up a new profile at any time by filling in
            the form below again.
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" onclick="closeModal('resetModal')">
            Cancel
          </button>
          <button class="btn btn-danger" id="confirmResetBtn" onclick="executeReset()">
            Yes, Reset Profile
          </button>
        </div>
      </div>
    </div>

    <form action="#" method="POST" id="preferenceForm">

      <!-- ══ SECTION 1: Study & Living Preferences ══ -->
      <div class="pref-section card mb-16">
        <div class="card-header">
          <span class="card-title">Study &amp; Living Preferences</span>
        </div>
        <div class="card-body">

          <div class="pref-field">
            <div class="pref-label">Study habits</div>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="studyHabits" value="early_riser"
                  <?php echo $p['studyHabits'] === 'early_riser' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                Early riser
              </label>
              <label class="radio-option">
                <input type="radio" name="studyHabits" value="night_owl"
                  <?php echo $p['studyHabits'] === 'night_owl' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                Night owl
              </label>
              <label class="radio-option">
                <input type="radio" name="studyHabits" value="flexible"
                  <?php echo $p['studyHabits'] === 'flexible' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                Flexible
              </label>
            </div>
          </div>

          <div class="pref-field">
            <div class="pref-label">Sleep schedule</div>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="sleepSchedule" value="before_10pm"
                  <?php echo $p['sleepSchedule'] === 'before_10pm' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                Before 10 pm
              </label>
              <label class="radio-option">
                <input type="radio" name="sleepSchedule" value="10pm_12am"
                  <?php echo $p['sleepSchedule'] === '10pm_12am' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                10 pm – 12 am
              </label>
              <label class="radio-option">
                <input type="radio" name="sleepSchedule" value="after_midnight"
                  <?php echo $p['sleepSchedule'] === 'after_midnight' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                After midnight
              </label>
            </div>
          </div>

          <div class="pref-field">
            <div class="pref-label">Noise tolerance</div>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="noiseTolerance" value="quiet"
                  <?php echo $p['noiseTolerance'] === 'quiet' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                Quiet
              </label>
              <label class="radio-option">
                <input type="radio" name="noiseTolerance" value="moderate"
                  <?php echo $p['noiseTolerance'] === 'moderate' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                Moderate
              </label>
              <label class="radio-option">
                <input type="radio" name="noiseTolerance" value="lively"
                  <?php echo $p['noiseTolerance'] === 'lively' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                Lively
              </label>
            </div>
          </div>

          <div class="pref-field">
            <div class="pref-label">Gender preference</div>
            <select name="genderPreference" class="form-control pref-select">
              <option value="" <?php echo $p['genderPreference'] === '' ? 'selected' : ''; ?>>
                No preference
              </option>
              <option value="male_only" <?php echo $p['genderPreference'] === 'male_only' ? 'selected' : ''; ?>>
                Male only
              </option>
              <option value="female_only" <?php echo $p['genderPreference'] === 'female_only' ? 'selected' : ''; ?>>
                Female only
              </option>
              <option value="mixed" <?php echo $p['genderPreference'] === 'mixed' ? 'selected' : ''; ?>>
                Mixed
              </option>
            </select>
          </div>

        </div>
      </div>

      <!-- ══ SECTION 2: Room Preferences ══ -->
      <div class="pref-section card mb-16">
        <div class="card-header">
          <span class="card-title">Room Preferences</span>
        </div>
        <div class="card-body">
          <div class="pref-field">
            <div class="pref-label">Room type</div>
            <select name="roomTypePreference" class="form-control pref-select">
              <option value="" <?php echo $p['roomTypePreference'] === '' ? 'selected' : ''; ?>>
                No preference
              </option>
              <option value="single" <?php echo $p['roomTypePreference'] === 'single' ? 'selected' : ''; ?>>
                Single
              </option>
              <option value="shared" <?php echo $p['roomTypePreference'] === 'shared' ? 'selected' : ''; ?>>
                Shared
              </option>
              <option value="ensuite" <?php echo $p['roomTypePreference'] === 'ensuite' ? 'selected' : ''; ?>>
                Ensuite
              </option>
            </select>
          </div>

          <div class="pref-field" style="margin-top:16px;">
            <div class="pref-label">Environment type</div>
            <select name="environmentType" class="form-control pref-select">
              <option value="" <?php echo $p['environmentType'] === '' ? 'selected' : ''; ?>>
                No preference
              </option>
              <option value="quiet" <?php echo $p['environmentType'] === 'quiet' ? 'selected' : ''; ?>>
                Quiet
              </option>
              <option value="moderate" <?php echo $p['environmentType'] === 'moderate' ? 'selected' : ''; ?>>
                Moderate
              </option>
              <option value="lively" <?php echo $p['environmentType'] === 'lively' ? 'selected' : ''; ?>>
                Lively
              </option>
            </select>
          </div>

          <div class="pref-field" style="margin-top:16px;">
            <div class="pref-label">Curfew preference</div>
            <select name="curfewPreference" class="form-control pref-select">
              <option value="" <?php echo $p['curfewPreference'] === '' ? 'selected' : ''; ?>>
                No preference
              </option>
              <option value="before_10pm" <?php echo $p['curfewPreference'] === 'before_10pm' ? 'selected' : ''; ?>>
                Before 10pm
              </option>
              <option value="before_midnight" <?php echo $p['curfewPreference'] === 'before_midnight' ? 'selected' : ''; ?>>
                Before Midnight
              </option>
              <option value="no_curfew" <?php echo $p['curfewPreference'] === 'no_curfew' ? 'selected' : ''; ?>>
                No Curfew
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- ══ SECTION 3: Budget ══ -->
      <div class="pref-section card mb-16">
        <div class="card-header">
          <span class="card-title">Budget</span>
        </div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label for="budgetMin">Minimum (KES/month)</label>
              <input type="number" id="budgetMin" name="budgetMin"
                class="form-control" placeholder="e.g. 5000" min="0"
                value="<?php echo htmlspecialchars($p['budgetMin']); ?>"/>
            </div>
            <div class="form-group">
              <label for="budgetMax">Maximum (KES/month)</label>
              <input type="number" id="budgetMax" name="budgetMax"
                class="form-control" placeholder="e.g. 15000" min="0"
                value="<?php echo htmlspecialchars($p['budgetMax']); ?>"/>
            </div>
          </div>
          <div class="form-error" id="err-budget"></div>
        </div>
      </div>

      <!-- ══ SECTION 4: Location Preference ══ -->
      <div class="pref-section card mb-16">
        <div class="card-header">
          <span class="card-title">Location Preference</span>
        </div>
        <div class="card-body">
          <div class="pref-field">
            <div class="pref-label">Preferred Location</div>
            <select name="preferredLocation" class="form-control pref-select">
              <option value="" <?php echo $p['preferredLocation'] === '' ? 'selected' : ''; ?>>
                No preference
              </option>
              <option value="Madaraka" <?php echo $p['preferredLocation'] === 'Madaraka' ? 'selected' : ''; ?>>
                Madaraka
              </option>
              <option value="South C" <?php echo $p['preferredLocation'] === 'South C' ? 'selected' : ''; ?>>
                South C
              </option>
              <option value="Nairobi West" <?php echo $p['preferredLocation'] === 'Nairobi West' ? 'selected' : ''; ?>>
                Nairobi West
              </option>
              <option value="Lang'ata" <?php echo $p['preferredLocation'] === "Lang'ata" ? 'selected' : ''; ?>>
                Lang'ata
              </option>
              <option value="South B" <?php echo $p['preferredLocation'] === 'South B' ? 'selected' : ''; ?>>
                South B
              </option>
              <option value="Siwaka" <?php echo $p['preferredLocation'] === 'Siwaka' ? 'selected' : ''; ?>>
                Siwaka
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- ══ Form Actions ══ -->
      <div class="pref-actions">
        <button type="submit" class="btn btn-primary btn-lg">
          <?php echo $hasProfile ? 'Update Profile' : 'Save Profile'; ?>
        </button>
        <a href="/SU-Housing/student/browse.php"
           class="btn btn-ghost btn-lg">
          <?php echo $hasProfile ? 'View Listings' : 'Skip for Now'; ?>
        </a>
      </div>

    </form>

  </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// ── Save / update profile ──
document.getElementById('preferenceForm')
  .addEventListener('submit', async function(e) {
    e.preventDefault();

    const min = parseFloat(document.getElementById('budgetMin').value) || 0;
    const max = parseFloat(document.getElementById('budgetMax').value) || 0;
    const errBudget = document.getElementById('err-budget');

    if (min && max && max < min) {
      errBudget.textContent = 'Maximum budget must be greater than minimum budget.';
      return;
    }
    errBudget.textContent = '';

    const form = new FormData(this);

    const payload = {
      studyHabits:        form.get('studyHabits')        || null,
      sleepSchedule:      form.get('sleepSchedule')      || null,
      noiseTolerance:     form.get('noiseTolerance')     || null,
      genderPreference:   form.get('genderPreference')   || null,
      roomTypePreference: form.get('roomTypePreference') || null,
      environmentType:    form.get('environmentType')    || null,
      curfewPreference:   form.get('curfewPreference')   || null,
      budgetMin:          form.get('budgetMin') ? parseFloat(form.get('budgetMin')) : null,
      budgetMax:          form.get('budgetMax') ? parseFloat(form.get('budgetMax')) : null,
      preferredLocation:  form.get('preferredLocation')  || null,
    };

    try {
      const response = await fetch('/SU-Housing/api/profiles.php', {
        method:      'PUT',
        credentials: 'include',
        headers:     { 'Content-Type': 'application/json' },
        body:        JSON.stringify(payload),
      });

      const data = await response.json();

      if (response.ok) {
        window.location.href = '/SU-Housing/student/browse.php';
      } else {
        errBudget.textContent = data.error || 'Failed to save profile. Please try again.';
      }

    } catch (err) {
      errBudget.textContent = 'Network error. Please try again.';
    }
  });

// ── Reset profile ──
function confirmResetProfile() {
  document.getElementById('resetModal').classList.add('open');
}

async function executeReset() {
  const btn = document.getElementById('confirmResetBtn');
  btn.disabled    = true;
  btn.textContent = 'Resetting…';

  try {
    const response = await fetch('/SU-Housing/api/profiles.php', {
      method:      'DELETE',
      credentials: 'include',
    });

    const data = await response.json();

    if (response.ok) {
      // Redirect back to same page — will now show "no profile" state
      window.location.href = '/SU-Housing/student/preference_profile.php';
    } else {
      alert(data.error || 'Failed to reset profile.');
      btn.disabled    = false;
      btn.textContent = 'Yes, Reset Profile';
    }

  } catch (err) {
    alert('Network error. Please try again.');
    btn.disabled    = false;
    btn.textContent = 'Yes, Reset Profile';
  }
}

function closeModal(id) {
  document.getElementById(id)?.classList.remove('open');
}
</script>