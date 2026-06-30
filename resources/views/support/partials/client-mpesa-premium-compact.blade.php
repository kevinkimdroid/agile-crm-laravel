@php
    $suggestedPremium = client_suggested_premium_amount($client ?? null);
    $mpesaReady = !empty($mpesaConfigured);
    $mpesaService = app(\App\Services\MpesaStkPushService::class);
    $mpesaSandboxSimulate = $mpesaSandboxSimulate ?? $mpesaService->isSandboxSimulate();
    $policyRef = $clientPolicy ?? $policy ?? '—';
    $quickAmounts = array_values(array_unique(array_filter(array_merge(
        $suggestedPremium ? [(int) $suggestedPremium] : [],
        [500, 1000, 2500, 5000, 10000]
    ))));
@endphp
<div class="premiums-mpesa-compact mpesa-ui" id="clientMpesaPayCard">
    <div class="premiums-mpesa-compact-top">
        <div class="premiums-mpesa-compact-icon"><i class="bi bi-phone-vibrate"></i></div>
        <div class="flex-grow-1">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="premiums-mpesa-compact-title">M-Pesa STK push</span>
                @if($mpesaSandboxSimulate)
                <span class="badge rounded-pill text-bg-info">Sandbox</span>
                @elseif($mpesaReady)
                <span class="badge rounded-pill text-bg-success">Live</span>
                @else
                <span class="badge rounded-pill text-bg-secondary">Unavailable</span>
                @endif
            </div>
            <p class="premiums-mpesa-compact-desc mb-0">
                Send a payment prompt to the client's phone for policy <span class="policy-ref font-monospace fw-semibold">{{ $policyRef }}</span>.
            </p>
        </div>
    </div>

    @if($mpesaSandboxSimulate)
    <div class="premiums-mpesa-compact-note"><i class="bi bi-info-circle me-1"></i>Simulated payments — no real M-Pesa prompt.</div>
    @elseif(! $mpesaReady)
    <div class="premiums-mpesa-compact-note premiums-mpesa-compact-note-muted">
        <i class="bi bi-pause-circle me-1"></i>M-Pesa collection is not available right now.
    </div>
    @elseif(empty($clientPhone))
    <div class="premiums-mpesa-compact-note"><i class="bi bi-telephone me-1"></i>You'll enter the client's number in the payment form.</div>
    @endif

    @if($mpesaReady)
    <div class="premiums-mpesa-compact-amounts">
        @foreach($quickAmounts as $amt)
        <button type="button"
            class="premiums-mpesa-amt mpesa-stk-trigger {{ $suggestedPremium && (int) $amt === (int) $suggestedPremium ? 'is-suggested' : '' }}"
            data-bs-toggle="modal" data-bs-target="#mpesaStkModal"
            data-mpesa-amount="{{ $amt }}">
            <span class="premiums-mpesa-amt-value">{{ number_format($amt) }}</span>
            <span class="premiums-mpesa-amt-currency">KES</span>
        </button>
        @endforeach
    </div>
    <button type="button" class="btn btn-success w-100 premiums-mpesa-compact-cta mpesa-stk-trigger"
        data-bs-toggle="modal" data-bs-target="#mpesaStkModal">
        <i class="bi bi-lightning-charge-fill me-2"></i>Custom amount
    </button>
    @endif

    @if($mpesaReady && ($mpesaTransactions ?? collect())->isNotEmpty())
    <div class="premiums-mpesa-compact-tx">
        <div class="premiums-mpesa-compact-tx-label">Recent STK payments</div>
        @foreach(($mpesaTransactions ?? collect())->take(3) as $tx)
        @php
            $st = $tx->status;
            $dotClass = match ($st) {
                'success' => 'text-success',
                'pending' => 'text-warning',
                'cancelled' => 'text-secondary',
                default => 'text-danger',
            };
        @endphp
        <div class="premiums-mpesa-compact-tx-row">
            <span class="premiums-mpesa-compact-tx-dot {{ $dotClass }}">●</span>
            <span class="fw-semibold">KES {{ number_format((float) $tx->amount, 0) }}</span>
            <span class="text-muted small">{{ $tx->created_at?->diffForHumans() ?? '—' }}</span>
            <span class="badge rounded-pill bg-light text-dark border ms-auto">{{ ucfirst($st) }}</span>
        </div>
        @endforeach
    </div>
    @endif
</div>
