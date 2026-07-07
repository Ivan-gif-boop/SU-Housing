<?php
// includes/feedback_card.php
// Reusable feedback card for admin/feedback.php
// Expects: $fb (array), $isPending (bool)
?>
<div class="feedback-classify-card"
     id="fbc-<?php echo $fb['feedbackId']; ?>"
     data-hostel="<?php echo htmlspecialchars(strtolower($fb['hostelName'])); ?>"
     data-student="<?php echo htmlspecialchars(strtolower($fb['fullName'])); ?>"
     data-tab="<?php echo $isPending ? 'pending' : 'classified'; ?>"
     data-classification="<?php echo htmlspecialchars($fb['sentiment'] ?? ''); ?>"></div>
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
      <span class="badge badge-amber"> Pending</span>
    <?php endif; ?>
    <!-- Admin response -->
  <div class="fbc-response" id="fbc-response-<?php echo $fb['feedbackId']; ?>">
    <?php if (!empty($fb['adminResponse'])): ?>
      <div class="fbc-response-existing" id="fbc-response-view-<?php echo $fb['feedbackId']; ?>">
        <div class="fbc-meta" style="margin-top:10px;">
          Admin response Â· <?php echo date('j M Y', strtotime($fb['respondedAt'])); ?>
        </div>
        <p class="fbc-text" style="background:var(--gray-50); padding:10px; border-radius:8px;">
          <?php echo nl2br(htmlspecialchars($fb['adminResponse'])); ?>
        </p>
        <button class="btn btn-outline btn-sm"
                onclick="showResponseForm(<?php echo $fb['feedbackId']; ?>)">
          Edit Response
        </button>
      </div>
      <div class="fbc-response-form" id="fbc-response-form-<?php echo $fb['feedbackId']; ?>" style="display:none;">
        <textarea id="fbc-response-input-<?php echo $fb['feedbackId']; ?>"
                  class="form-control" rows="2"
                  style="margin-top:10px;"><?php echo htmlspecialchars($fb['adminResponse']); ?></textarea>
        <button class="btn btn-primary btn-sm" style="margin-top:8px;"
                onclick="submitFeedbackResponse(<?php echo $fb['feedbackId']; ?>)">
          Save Response
        </button>
      </div>
    <?php else: ?>
      <div class="fbc-response-form" id="fbc-response-form-<?php echo $fb['feedbackId']; ?>">
        <textarea id="fbc-response-input-<?php echo $fb['feedbackId']; ?>"
                  class="form-control" rows="2"
                  style="margin-top:10px;"
                  placeholder="Write a response to this student..."></textarea>
        <button class="btn btn-primary btn-sm" style="margin-top:8px;"
                onclick="submitFeedbackResponse(<?php echo $fb['feedbackId']; ?>)">
          Send Response
        </button>
      </div>
    <?php endif; ?>
  </div>

  <!-- Feedback text -->
  <p class="fbc-text">
    "<?php echo nl2br(htmlspecialchars($fb['submissionText'])); ?>"
  </p>

  <!-- Actions -->
  <?php if ($isPending): ?>
    <!-- Pending — show classify buttons -->
    <div class="fbc-actions">
      <button
        class="btn btn-success btn-sm"
        onclick="classifyFeedback(<?php echo $fb['feedbackId']; ?>, 'positive')"
      >
        ✓ Positive
      </button>
      <button
        class="btn btn-danger btn-sm"
        onclick="classifyFeedback(<?php echo $fb['feedbackId']; ?>, 'negative')"
      >
        ✗ Negative
      </button>
    </div>
  <?php else: ?>
    <!-- Classified — show remove classification button -->
    <div class="fbc-actions" style="margin-top:8px;">
      <button
        class="btn btn-outline btn-sm"
        style="font-size:12px; color:var(--gray-500);"
        onclick="removeClassification(<?php echo $fb['feedbackId']; ?>)"
      >
         Remove Classification
      </button>
    </div>
  <?php endif; ?>

</div>