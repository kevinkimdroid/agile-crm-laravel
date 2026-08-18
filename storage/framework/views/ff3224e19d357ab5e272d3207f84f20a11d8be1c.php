
<?php
    $mpesaService = app(\App\Services\MpesaStkPushService::class);
    $mpesaConfigured = $mpesaConfigured ?? $mpesaService->isConfigured();
    $mpesaSandboxSimulate = $mpesaSandboxSimulate ?? $mpesaService->isSandboxSimulate();
    $suggestedPremium = client_suggested_premium_amount($client ?? null);
    $defaultAmount = old('amount', $defaultAmount ?? ($suggestedPremium ?: ''));
    $mpesaEnv = config('mpesa.environment', 'sandbox');
    $mpesaQuickAmounts = array_values(array_unique(array_filter(array_merge(
        $suggestedPremium ? [$suggestedPremium] : [],
        [500, 1000, 2500, 5000, 10000]
    ))));
    $policyRef = trim((string) ($mpesaPolicyNumber ?? $clientPolicy ?? $policy ?? ''));
    if ($policyRef === '' || $policyRef === '—') {
        $policyRef = '—';
    }
?>
<div class="modal fade mpesa-ui" id="mpesaStkModal" tabindex="-1" aria-labelledby="mpesaStkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?php echo e(route('support.clients.mpesa-stk')); ?>" id="mpesaStkForm">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="return_url" value="<?php echo e(request()->fullUrl()); ?>">
                <input type="hidden" name="policy_number" value="<?php echo e($policyRef); ?>">
                <input type="hidden" name="client_name" value="<?php echo e(($clientName ?? 'Client') !== 'Client' ? ($clientName ?? '') : ''); ?>">

                <div class="mpesa-modal-hero">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mpesa-modal-title" id="mpesaStkModalLabel">M-Pesa STK Push</h5>
                            <p class="mpesa-modal-sub">
                                <?php if($mpesaSandboxSimulate): ?>
                                    Sandbox — simulated payment, any phone number
                                <?php else: ?>
                                    Payment prompt sent directly to the client's phone
                                <?php endif; ?>
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <div class="mpesa-modal-body">
                    <?php if(! $mpesaConfigured): ?>
                    <div class="mpesa-notice mpesa-notice-warning mb-3" style="margin:0 0 1rem;">
                        <i class="bi bi-exclamation-triangle"></i>
                        <div>M-Pesa is not fully configured. Enable <code>MPESA_ENABLED</code> (and sandbox simulate or Daraja credentials) to send STK pushes.</div>
                    </div>
                    <?php elseif($mpesaSandboxSimulate): ?>
                    <div class="mpesa-notice mpesa-notice-info mb-3" style="margin:0 0 1rem;">
                        <i class="bi bi-bug"></i>
                        <div>STK is faked locally — no Safaricom credentials needed.</div>
                    </div>
                    <?php elseif($mpesaEnv === 'sandbox'): ?>
                    <div class="mpesa-notice mpesa-notice-info mb-3" style="margin:0 0 1rem;">
                        <i class="bi bi-info-circle"></i>
                        <div>Sandbox only works on test phone <code>254708374149</code>. Use production for real numbers.</div>
                    </div>
                    <?php endif; ?>

                    <div class="mpesa-policy-pill">
                        <span>Policy</span>
                        <span><?php echo e($policyRef); ?></span>
                    </div>

                    <?php echo $__env->make('support.partials.client-mpesa-phone-preview', [
                        'previewAmount' => $defaultAmount ?: $suggestedPremium,
                        'previewPolicy' => $policyRef,
                        'previewId' => 'mpesaModalPhonePreview',
                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

                    <div class="mb-3 mt-3">
                        <label for="mpesa_phone" class="mpesa-field-label d-block">
                            Phone number<?php echo e($mpesaSandboxSimulate ? '' : ' (Safaricom)'); ?>

                        </label>
                        <input type="text" class="form-control mpesa-field-input" name="phone" id="mpesa_phone" required
                            placeholder="<?php echo e($mpesaSandboxSimulate ? '07xx xxx xxx' : '07xx xxx xxx'); ?>"
                            value="<?php echo e(old('phone', $clientPhone ?? '')); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="mpesa_amount" class="mpesa-field-label d-block">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text mpesa-field-input" style="border-right:0;background:#f8fafc;font-weight:700;">KES</span>
                            <input type="number" class="form-control mpesa-field-input" name="amount" id="mpesa_amount" required
                                min="1" max="999999" step="1" placeholder="5000"
                                value="<?php echo e($defaultAmount); ?>"
                                style="border-left:0;">
                        </div>
                        <div class="mpesa-modal-quick">
                            <?php $__currentLoopData = $mpesaQuickAmounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <button type="button" class="mpesa-quick-amt" data-amt="<?php echo e($amt); ?>"><?php echo e(number_format($amt)); ?></button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="mpesa_description" class="mpesa-field-label d-block">Note <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" class="form-control mpesa-field-input" name="description" id="mpesa_description" maxlength="100"
                            placeholder="Premium payment"
                            value="<?php echo e(old('description', 'Premium — '.$policyRef)); ?>">
                    </div>

                    <div id="mpesaStkStatus" class="client-mpesa-status-card d-none is-pending" role="status">
                        <div class="client-mpesa-status-icon"><i class="bi bi-phone-vibrate"></i></div>
                        <div class="client-mpesa-status-text">
                            <strong id="mpesaStkStatusTitle">Sending STK push…</strong>
                            <span id="mpesaStkStatusDetail">Ask the client to check their phone for the M-Pesa prompt.</span>
                        </div>
                    </div>
                </div>

                <div class="mpesa-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-send" id="mpesaStkSubmit"
                        <?php if(empty($mpesaConfigured)): ?> disabled title="M-Pesa unavailable" <?php endif; ?>>
                        <?php if($mpesaSandboxSimulate): ?>
                            <i class="bi bi-play-circle me-1"></i> Simulate
                        <?php else: ?>
                            <i class="bi bi-send me-1"></i> Send STK Push
                        <?php endif; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/support/partials/client-mpesa-stk-modal.blade.php ENDPATH**/ ?>