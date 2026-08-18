<?php
    $erpMsgTab = $erpMsgTab ?? 'drafts';
?>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?php echo e($erpMsgTab === 'drafts' ? 'active' : ''); ?>" href="<?php echo e(route('tools.erp-messaging')); ?>">Drafts</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo e($erpMsgTab === 'sent' ? 'active' : ''); ?>" href="<?php echo e(route('tools.erp-messaging.sent')); ?>">Sent &amp; delivery</a>
    </li>
</ul>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/tools/partials/erp-messaging-tabs.blade.php ENDPATH**/ ?>