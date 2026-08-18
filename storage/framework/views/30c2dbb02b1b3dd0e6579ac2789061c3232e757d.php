
<?php
    $previewAmount = $previewAmount ?? ($suggestedPremium ?? null);
    $previewPolicy = $previewPolicy ?? ($clientPolicy ?? $policy ?? '—');
    $previewId = $previewId ?? 'mpesaPhonePreview';
?>
<div class="mpesa-phone-preview" id="<?php echo e($previewId); ?>">
    <div class="mpesa-phone-frame">
        <div class="mpesa-phone-screen">
            <div class="mpesa-phone-notch"></div>
            <div class="mpesa-phone-prompt">
                <div class="mpesa-phone-prompt-label">M-Pesa</div>
                <div class="mpesa-phone-prompt-amount" data-mpesa-preview-amount>
                    <?php if($previewAmount): ?>
                        KES <?php echo e(number_format((int) $previewAmount)); ?>

                    <?php else: ?>
                        KES —
                    <?php endif; ?>
                </div>
                <div class="mpesa-phone-prompt-hint" data-mpesa-preview-hint>
                    Enter PIN to pay · <?php echo e(Str::limit($previewPolicy, 14)); ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/support/partials/client-mpesa-phone-preview.blade.php ENDPATH**/ ?>