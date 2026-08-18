<?php
    $mpesaService = app(\App\Services\MpesaStkPushService::class);
    $mpesaTriggerReady = $mpesaTriggerReady ?? ($mpesaConfigured ?? $mpesaService->isConfigured());
    $mpesaTriggerClass = trim(($mpesaTriggerClass ?? 'btn btn-outline-success') . ' mpesa-stk-trigger');
    $mpesaTriggerAmount = $mpesaTriggerAmount ?? null;
    $mpesaTriggerTitle = $mpesaTriggerTitle ?? ($mpesaTriggerReady
        ? (($mpesaSandboxSimulate ?? $mpesaService->isSandboxSimulate()) ? 'Sandbox mode — simulated STK' : 'Send M-Pesa STK push')
        : 'M-Pesa setup required — open to see details');
?>
<button type="button"
    class="<?php echo e($mpesaTriggerClass); ?>"
    data-bs-toggle="modal"
    data-bs-target="#mpesaStkModal"
    title="<?php echo e($mpesaTriggerTitle); ?>"
    <?php if($mpesaTriggerAmount): ?> data-mpesa-amount="<?php echo e($mpesaTriggerAmount); ?>" <?php endif; ?>>
    <?php echo $mpesaTriggerLabel ?? '<i class="bi bi-phone me-1"></i>Collect via M-Pesa'; ?>

</button>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/support/partials/client-mpesa-trigger-button.blade.php ENDPATH**/ ?>