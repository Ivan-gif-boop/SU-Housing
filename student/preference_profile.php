<?php
// student/preference_profile.php
// FR-08: Optional preference profile — offered after registration
// FR-09: Saved profile is used to rank browse results by match %

// ─────────────────────────────────────────
// BACKEND HOOK ZONE — Michelle fills this in
// ─────────────────────────────────────────
// require_once '../includes/auth_check.php';
// requireAuth('student');
// $userName = $_SESSION['user_name'];
//
// // Load existing profile if student is editing
// require_once '../includes/db.php';
// $stmt = getDB()->prepare(
//   'SELECT * FROM student_preference_profiles WHERE userId = ?'
// );
// $stmt->execute([$_SESSION['user_id']]);
// $profile = $stmt->fetch();
// ─────────────────────────────────────────

// Frontend defaults — hardcoded for now
$pageTitle  = 'Preference Profile';
$activePage = 'profile';
$userRole   = 'student';
$userName   = 'Ivan Wachira';

// Existing profile values — Michelle replaces these with DB values
$profile = $profile ?? [];
$p = [
  'studyHabits'    => $profile['studyHabits']    ?? '',
  'sleepSchedule'  => $profile['sleepSchedule']  ?? '',
  'noiseTolerance' => $profile['noiseTolerance'] ?? '',
  'genderPref'     => $profile['genderPref']     ?? '',
  'roomType'       => $profile['roomType']       ?? '',
  'budgetMin'      => $profile['budgetMin']      ?? '',
  'budgetMax'      => $profile['budgetMax']      ?? '',
  'locationPref'   => $profile['locationPref']   ?? '',
];

// Is this being shown right after registration or as an edit?
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
          <a href="/SU-housing/student/profile.php">Account</a> › Edit Preferences
        <?php endif; ?>
      </div>
      <h1 class="page-title">Complete Your Preference Profile</h1>
      <p class="page-subtitle">
        Help us match you with the best hostel options. All fields are optional.
      </p>
    </div>
    <?php if (!$isPostRegistration): ?>
      <div class="page-actions">
        <a href="/SU-housing/student/profile.php"
           class="btn btn-outline">← Back to Profile</a>
      </div>
    <?php endif; ?>
  </div>
  <div class="page-body">

    <?php if ($isPostRegistration): ?>
      <div class="alert alert-success mb-16">
        🎉 Account created successfully! Complete your preference
        profile below so we can recommend the best hostels for you.
      </div>
    <?php endif; ?>

    <!--
  Backend hook:
  action="/SU-housing/api/profiles/me.php" method="POST"
  Michelle adds: csrfField() inside the form when integrating
-->
    <form
      action="#"
      method="POST"
      id="preferenceForm"
    >

      <!-- ══ SECTION 1: Study & Living Preferences ══ -->
      <div class="pref-section card mb-16">
        <div class="card-header">
          <span class="card-title">Study &amp; Living Preferences</span>
        </div>
        <div class="card-body">

          <!-- Study habits -->
          <div class="pref-field">
            <div class="pref-label">Study habits</div>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="studyHabits"
                       value="early_riser"
                       <?php echo $p['studyHabits'] === 'early_riser' ? 'checked' : ''; ?>/>
                <span class="radio-box">
                  <span class="radio-dot"></span>
                </span>
                Early riser
              </label>
              <label class="radio-option">
                <input type="radio" name="studyHabits"
                       value="night_owl"
                       <?php echo $p['studyHabits'] === 'night_owl' ? 'checked' : ''; ?>/>
                <span class="radio-box">
                  <span class="radio-dot"></span>
                </span>
                Night owl
              </label>
              <label class="radio-option">
                <input type="radio" name="studyHabits"
                       value="flexible"
                       <?php echo $p['studyHabits'] === 'flexible' ? 'checked' : ''; ?>/>
                <span class="radio-box">
                  <span class="radio-dot"></span>
                </span>
                Flexible
              </label>
            </div>
          </div>

          <!-- Sleep schedule -->
          <div class="pref-field">
            <div class="pref-label">Sleep schedule</div>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="sleepSchedule"
                       value="before_10pm"
                       <?php echo $p['sleepSchedule'] === 'before_10pm' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                Before 10 pm
              </label>
              <label class="radio-option">
                <input type="radio" name="sleepSchedule"
                       value="10pm_to_12am"
                       <?php echo $p['sleepSchedule'] === '10pm_to_12am' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                10 pm – 12 am
              </label>
              <label class="radio-option">
                <input type="radio" name="sleepSchedule"
                       value="after_midnight"
                       <?php echo $p['sleepSchedule'] === 'after_midnight' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                After midnight
              </label>
            </div>
          </div>

          <!-- Noise tolerance -->
          <div class="pref-field">
            <div class="pref-label">Noise tolerance</div>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="noiseTolerance"
                       value="quiet"
                       <?php echo $p['noiseTolerance'] === 'quiet' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                Quiet
              </label>
              <label class="radio-option">
                <input type="radio" name="noiseTolerance"
                       value="moderate"
                       <?php echo $p['noiseTolerance'] === 'moderate' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                Moderate
              </label>
              <label class="radio-option">
                <input type="radio" name="noiseTolerance"
                       value="lively"
                       <?php echo $p['noiseTolerance'] === 'lively' ? 'checked' : ''; ?>/>
                <span class="radio-box"><span class="radio-dot"></span></span>
                Lively
              </label>
            </div>
          </div>

          <!-- Gender preference -->
          <div class="pref-field">
            <div class="pref-label">Gender preference</div>
            <select name="genderPref" class="form-control pref-select">
              <option value=""
                <?php echo $p['genderPref'] === '' ? 'selected' : ''; ?>>
                No preference
              </option>
              <option value="male_only"
                <?php echo $p['genderPref'] === 'male_only' ? 'selected' : ''; ?>>
                Male only
              </option>
              <option value="female_only"
                <?php echo $p['genderPref'] === 'female_only' ? 'selected' : ''; ?>>
                Female only
              </option>
              <option value="mixed"
                <?php echo $p['genderPref'] === 'mixed' ? 'selected' : ''; ?>>
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
            <select name="roomType" class="form-control pref-select">
              <option value=""
                <?php echo $p['roomType'] === '' ? 'selected' : ''; ?>>
                No preference
              </option>
              <option value="single"
                <?php echo $p['roomType'] === 'single' ? 'selected' : ''; ?>>
                Single
              </option>
              <option value="shared"
                <?php echo $p['roomType'] === 'shared' ? 'selected' : ''; ?>>
                Shared
              </option>
              <option value="ensuite"
                <?php echo $p['roomType'] === 'ensuite' ? 'selected' : ''; ?>>
                Ensuite
              </option>
              <option value="studio"
                <?php echo $p['roomType'] === 'studio' ? 'selected' : ''; ?>>
                Studio
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
              <input
                type="number"
                id="budgetMin"
                name="budgetMin"
                class="form-control"
                placeholder="e.g. 5000"
                min="0"
                value="<?php echo htmlspecialchars($p['budgetMin']); ?>"
              />
            </div>
            <div class="form-group">
              <label for="budgetMax">Maximum (KES/month)</label>
              <input
                type="number"
                id="budgetMax"
                name="budgetMax"
                class="form-control"
                placeholder="e.g. 15000"
                min="0"
                value="<?php echo htmlspecialchars($p['budgetMax']); ?>"
              />
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
            <select name="locationPref" class="form-control pref-select">
              <option value=""
                <?php echo $p['locationPref'] === '' ? 'selected' : ''; ?>>
                No preference
              </option>
              <option value="Madaraka"
                <?php echo $p['locationPref'] === 'Madaraka' ? 'selected' : ''; ?>>
                Madaraka
              </option>
              <option value="Nairobi West"
                <?php echo $p['locationPref'] === 'Nairobi West' ? 'selected' : ''; ?>>
                Nairobi West
              </option>
              <option value="Lang'ata"
                <?php echo $p['locationPref'] === "Lang'ata" ? 'selected' : ''; ?>>
                Lang'ata
              </option>
            </select>
          </div>

        </div>
      </div>

      <!-- ══ Form Actions ══ -->
      <div class="pref-actions">
        <button type="submit" class="btn btn-primary btn-lg">
          Save Profile
        </button>
        
          <a href="/SU-housing/student/browse.php"
          class="btn btn-ghost btn-lg"
          id="skipBtn" >
          Skip for Now
        </a>
      </div>

    </form>

  </div><!-- end page-body -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
document.getElementById('preferenceForm')
  .addEventListener('submit', function(e) {

  // Budget validation — max must be >= min if both entered
  const min = parseFloat(document.getElementById('budgetMin').value) || 0;
  const max = parseFloat(document.getElementById('budgetMax').value) || 0;
  const errBudget = document.getElementById('err-budget');

  if (min && max && max < min) {
    errBudget.textContent =
      'Maximum budget must be greater than minimum budget.';
    e.preventDefault();
    return;
  }

  errBudget.textContent = '';
});
</script>