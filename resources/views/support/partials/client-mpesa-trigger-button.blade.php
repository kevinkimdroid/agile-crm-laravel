@php
    $mpesaService = app(\App\Services\MpesaStkPushService::class);
    $mpesaTriggerReady = $mpesaTriggerReady ?? ($mpesaConfigured ?? $mpesaService->isConfigured());
    $mpesaTriggerClass = trim(($mpesaTriggerClass ?? 'btn btn-outline-success') . ' mpesa-stk-trigger');
    $mpesaTriggerAmount = $mpesaTriggerAmount ?? null;
    $mpesaTriggerTitle = $mpesaTriggerTitle ?? ($mpesaTriggerReady
        ? (($mpesaSandboxSimulate ?? $mpesaService->isSandboxSimulate()) ? 'Sandbox mode — simulated STK' : 'Send M-Pesa STK push')
        : 'M-Pesa setup required — open to see details');
@endphp
<button type="button"
    class="{{ $mpesaTriggerClass }}"
    data-bs-toggle="modal"
    data-bs-target="#mpesaStkModal"
    title="{{ $mpesaTriggerTitle }}"
    @if($mpesaTriggerAmount) data-mpesa-amount="{{ $mpesaTriggerAmount }}" @endif>
    {!! $mpesaTriggerLabel ?? '<i class="bi bi-phone me-1"></i>Collect via M-Pesa' !!}
</button>
