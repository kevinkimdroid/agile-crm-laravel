<?php
    $notifyScreen = $notifyScreen ?? 'maturities';
    $notifyEventType = $notifyEventType ?? 'maturity';
    $notifyPolicy = $notifyPolicy ?? '';
    $notifyEventDate = $notifyEventDate ?? '';
    $notifyClientName = $notifyClientName ?? '';
    $notifyProduct = $notifyProduct ?? '';
    $notifyEmail = $notifyEmail ?? '';
    $notifyPhone = $notifyPhone ?? '';
    $notifySubject = $notifySubject ?? '';
    $notifyMessage = $notifyMessage ?? '';
    $emailSent = ! empty($emailSent);
    $smsSent = ! empty($smsSent);
?>
<div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
    <button type="button"
        class="btn btn-sm <?php echo e($emailSent ? 'btn-success' : 'btn-outline-primary'); ?>"
        title="<?php echo e($emailSent ? 'Maturity email sent' : 'Email client'); ?>"
        data-bs-toggle="modal" data-bs-target="#maturityNotifyModal"
        data-channel="email"
        data-screen="<?php echo e($notifyScreen); ?>"
        data-event-type="<?php echo e($notifyEventType); ?>"
        data-policy="<?php echo e($notifyPolicy); ?>"
        data-event-date="<?php echo e($notifyEventDate); ?>"
        data-client-name="<?php echo e($notifyClientName); ?>"
        data-product="<?php echo e($notifyProduct); ?>"
        data-email="<?php echo e($notifyEmail); ?>"
        data-phone="<?php echo e($notifyPhone); ?>"
        data-subject="<?php echo e($notifySubject); ?>"
        data-message="<?php echo e($notifyMessage); ?>">
        <i class="bi bi-envelope-at"></i>
    </button>
    <button type="button"
        class="btn btn-sm <?php echo e($smsSent ? 'btn-success' : 'btn-outline-secondary'); ?>"
        title="<?php echo e($smsSent ? 'Maturity SMS sent' : 'SMS client'); ?>"
        data-bs-toggle="modal" data-bs-target="#maturityNotifyModal"
        data-channel="sms"
        data-screen="<?php echo e($notifyScreen); ?>"
        data-event-type="<?php echo e($notifyEventType); ?>"
        data-policy="<?php echo e($notifyPolicy); ?>"
        data-event-date="<?php echo e($notifyEventDate); ?>"
        data-client-name="<?php echo e($notifyClientName); ?>"
        data-product="<?php echo e($notifyProduct); ?>"
        data-email="<?php echo e($notifyEmail); ?>"
        data-phone="<?php echo e($notifyPhone); ?>"
        data-subject=""
        data-message="">
        <i class="bi bi-chat-dots"></i>
    </button>
</div>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/support/partials/maturity-notify-buttons.blade.php ENDPATH**/ ?>