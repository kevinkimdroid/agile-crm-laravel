<?php
    $variant = $variant ?? 'full'; // full | compact
    $tagline = $showTagline ?? ($variant === 'full');
?>
<div class="ko-brand ko-brand--<?php echo e($variant); ?>" aria-label="<?php echo e(config('branding.client_name')); ?>">
    <div class="ko-brand-mark" aria-hidden="true">
        <?php echo $__env->make('partials.client-brand-mark', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
    <div class="ko-brand-copy">
        <div class="ko-brand-line1">KENYA ORIENT</div>
        <div class="ko-brand-line2">INSURANCE LIMITED</div>
        <?php if($tagline): ?>
            <div class="ko-brand-tagline"><?php echo e(config('branding.tagline')); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/partials/client-brand.blade.php ENDPATH**/ ?>