<?php
    $keyFieldsTitle = $keyFieldsTitle ?? 'Key Fields';
    $keyFieldsViewDetailsUrl = $keyFieldsViewDetailsUrl ?? null;
?>
<div class="card contact-detail-card mb-4 client-summary-personal">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <?php if($keyFieldsShowIcon ?? false): ?>
            <div class="client-details-block-icon"><i class="bi bi-person-vcard"></i></div>
            <?php endif; ?>
            <h6 class="text-uppercase small fw-bold mb-0" style="color:var(--agile-primary,#0E4385)"><?php echo e($keyFieldsTitle); ?></h6>
        </div>
        <div class="client-summary-personal-grid">
            <?php $__currentLoopData = $keyFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="client-summary-field">
                <span class="client-summary-label"><?php echo e($field['label']); ?></span>
                <span class="client-summary-value <?php echo e($field['class'] ?? ''); ?>"><?php echo $field['value']; ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php if($keyFieldsViewDetailsUrl): ?>
        <div class="mt-3 pt-3 border-top">
            <a href="<?php echo e($keyFieldsViewDetailsUrl); ?>" class="btn btn-sm btn-outline-primary">View full details</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/partials/profile-summary-key-fields.blade.php ENDPATH**/ ?>