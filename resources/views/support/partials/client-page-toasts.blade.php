{{-- Flash + M-Pesa status toasts --}}
@php
    $mpesaPendingId = session('mpesa_stk_transaction_id');
    $isMpesaFlash = $mpesaPendingId || (session('success') && str_contains(strtolower((string) session('success')), 'm-pesa'))
        || (session('success') && str_contains(strtolower((string) session('success')), 'stk'));
@endphp

<div class="client-toast-stack mpesa-ui" id="clientToastStack" aria-live="polite" aria-atomic="true">
    @if (session('error'))
    <div class="client-toast client-toast-error show" role="alert">
        <div class="client-toast-icon"><i class="bi bi-exclamation-octagon-fill"></i></div>
        <div class="client-toast-body">
            <strong>Could not complete</strong>
            <p>{{ session('error') }}</p>
        </div>
        <button type="button" class="client-toast-close" data-dismiss-toast aria-label="Close">&times;</button>
    </div>
    @endif

    @if (session('success') && ! $isMpesaFlash)
    <div class="client-toast client-toast-success show" role="alert">
        <div class="client-toast-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="client-toast-body">
            <strong>Success</strong>
            <p>{{ session('success') }}</p>
        </div>
        <button type="button" class="client-toast-close" data-dismiss-toast aria-label="Close">&times;</button>
    </div>
    @endif

    @if (session('success') && $isMpesaFlash)
    <div class="client-toast client-toast-mpesa client-toast-mpesa-sent show" id="mpesaPageToast" role="status">
        <div class="client-toast-mpesa-logo">M</div>
        <div class="client-toast-body">
            <strong id="mpesaPageToastTitle">STK push sent</strong>
            <p id="mpesaPageToastMessage">{{ session('success') }}</p>
            @if($mpesaPendingId)
            <div class="client-toast-mpesa-progress" id="mpesaPageToastProgress">
                <span class="client-toast-mpesa-pulse"></span>
                Waiting for PIN on phone…
            </div>
            @endif
        </div>
        <button type="button" class="client-toast-close" data-dismiss-toast aria-label="Close">&times;</button>
    </div>
    @endif
</div>
