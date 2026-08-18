<?php
    $erpCategory = $erpCategory ?? 'drafts';
    $draftN = $draftTotal ?? $previewCount ?? null;
    $sentN = $sentTotal ?? ($counts['total'] ?? null);
?>
<nav class="ko-cats" aria-label="Message categories">
    <a class="ko-cat <?php echo e($erpCategory === 'drafts' ? 'is-on' : ''); ?>" href="<?php echo e(route('tools.erp-messaging')); ?>">
        Drafts
        <?php if($draftN !== null && $draftN !== ''): ?>
            <em><?php echo e(number_format((int) $draftN)); ?></em>
        <?php endif; ?>
    </a>
    <a class="ko-cat <?php echo e($erpCategory === 'sent' ? 'is-on' : ''); ?>" href="<?php echo e(route('tools.erp-messaging.sent')); ?>">
        Sent
        <?php if($sentN !== null && $sentN !== ''): ?>
            <em><?php echo e(number_format((int) $sentN)); ?></em>
        <?php endif; ?>
    </a>
</nav>
<?php if($erpCategory === 'sent' && !empty($filterLinks)): ?>
    <nav class="ko-cats ko-cats-sub" aria-label="Sent status">
        <?php $__currentLoopData = $filterLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a class="ko-cat <?php echo e(($filter ?? 'all') === $key ? 'is-on' : ''); ?>"
               href="<?php echo e(route('tools.erp-messaging.sent', ['filter' => $key])); ?>">
                <?php echo e($meta['label']); ?>

                <em><?php echo e(number_format((int) ($meta['count'] ?? 0))); ?></em>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </nav>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/tools/partials/erp-messages-categories.blade.php ENDPATH**/ ?>