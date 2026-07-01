<?php
// includes/feedback_card.php
// Reusable feedback card for admin/feedback.php
// Expects: $fb (array), $isPending (bool)
?>
<div class="feedback-classify-card"
     id="fbc-<?php echo $fb['feedbackId']; ?>"
     data-hostel="<?php echo htmlspecialchars(
       strtolower($fb['hostelName'])
     ); ?>"
     data-student="<?php echo htmlspecialchars(
       strtolower($fb['fullName'])
     ); ?>"
     data-tab="<?php echo $isPending
       ? 'pending' : 'classified'; ?>">

  <div class="fbc-header">
    <div>
      <div class="fbc-hostel">
        <?php echo htmlspecialchars($fb['hostelName']); ?>
      </div>
      <div class="fbc-meta">
        <?php echo htmlspecialchars($fb['fullName']); ?>
        · Admission:
        <?php echo htmlspecialchars($fb['admissionNumber']); ?>
        ·
        <?php echo date('j M Y', strtotime($fb['submittedAt'])); ?>
      </div>
    </div>

    <!-- Sentiment badge -->
    <?php if ($fb['sentiment'] === 'positive'): ?>
      <span class="badge badge-green">✓ Positive</span>
    <?php elseif ($fb['sentiment'] === 'negative'): ?>
      <span class="badge badge-red">✗ Negative</span>
    <?php else: ?>
      <span class="badge badge-amber">⏳ Pending</span>
    <?php endif; ?>
  </div>

  <!-- Feedback text -->
  <p class="fbc-text">
    "<?php echo nl2br(htmlspecialchars($fb['submissionText'])); ?>"
  </p>

  <!-- Classify actions — only shown for pending -->
  <?php if ($isPending): ?>
    <div class="fbc-actions">
      <button
        class="btn btn-success btn-sm"
        onclick="classifyFeedback(
          <?php echo $fb['feedbackId']; ?>,
          'positive'
        )"
      >
        ✓ Positive
      </button>
      <button
        class="btn btn-danger btn-sm"
        onclick="classifyFeedback(
          <?php echo $fb['feedbackId']; ?>,
          'negative'
        )"
      >
        ✗ Negative
      </button>
    </div>
  <?php else: ?>
    <div style="font-size:12px; color:var(--gray-400);
                 margin-top:8px;">
    </div>
  <?php endif; ?>

</div>