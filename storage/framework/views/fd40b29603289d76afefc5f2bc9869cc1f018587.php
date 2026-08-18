<?php
    $smsDeskTab = $smsDeskTab ?? 'drafts';
?>
<div class="sms-desk-bar">
    <div class="sms-desk-brand">
        <i class="bi bi-chat-dots-fill"></i>
        SMS Desk
    </div>
    <div class="sms-dots">
        <?php if($smsDeskTab === 'drafts'): ?>
            <span class="sms-dot <?php echo e(!empty($canSendLive) ? 'on' : ''); ?>"><?php echo e(!empty($canSendLive) ? 'SMS ready' : 'SMS locked'); ?></span>
            <span class="sms-dot <?php echo e(!empty($autoSendEnabled) ? 'on' : ''); ?>"><?php echo e(!empty($autoSendEnabled) ? 'Auto-send' : 'Manual'); ?></span>
            <span class="sms-count"><strong id="erpDraftChip"><?php echo e(number_format((int) ($draftTotal ?? 0))); ?></strong> waiting</span>
        <?php else: ?>
            <?php if(($erpSmsTransport ?? '') === 'http'): ?>
                <span class="sms-dot on">HTTP API</span>
            <?php elseif(($erpSmsTransport ?? '') === 'oracle'): ?>
                <span class="sms-dot on">Oracle</span>
            <?php else: ?>
                <span class="sms-dot">No transport</span>
            <?php endif; ?>
            <span class="sms-count"><?php echo e(number_format((int) ($counts['total'] ?? 0))); ?> in snapshot</span>
        <?php endif; ?>
    </div>
    <nav class="sms-seg" aria-label="SMS desk">
        <a href="<?php echo e(route('tools.erp-messaging')); ?>" class="<?php echo e($smsDeskTab === 'drafts' ? 'is-on' : ''); ?>">Drafts</a>
        <a href="<?php echo e(route('tools.erp-messaging.sent')); ?>" class="<?php echo e($smsDeskTab === 'sent' ? 'is-on' : ''); ?>">Sent</a>
    </nav>
</div>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/tools/partials/sms-desk-bar.blade.php ENDPATH**/ ?>