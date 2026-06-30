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
<div class="card contact-detail-card client-mpesa-pay-card mb-4 mpesa-ui" id="clientMpesaPayCard">
    <div class="card-body">
        <div class="mpesa-card-hero">
            <div>
                <div class="mpesa-card-head">
                    <div class="mpesa-card-logo"><i class="bi bi-phone-vibrate"></i></div>
                    <div>
                        <h3 class="mpesa-card-title">Collect premium</h3>
                        <div class="mpesa-card-meta">
                            <span class="mpesa-tag">STK Push</span>
                            @if($mpesaSandboxSimulate)
                            <span class="mpesa-tag mpesa-tag-sandbox">Sandbox</span>
                            @elseif($mpesaReady)
                            <span class="mpesa-tag">Live</span>
                            @endif
                        </div>
                        <p class="mpesa-card-desc">
                            Send an M-Pesa prompt to the client's phone for policy
                            <span class="policy-ref">{{ $policyRef }}</span>.
                            They enter their PIN to complete payment.
                        </p>
                    </div>
                </div>
            </div>
            @include('support.partials.client-mpesa-phone-preview', [
                'previewAmount' => $suggestedPremium,
                'previewPolicy' => $policyRef,
                'previewId' => 'mpesaCardPhonePreview',
            ])
        </div>

        @if($mpesaSandboxSimulate)
        <div class="mpesa-notice mpesa-notice-info">
            <i class="bi bi-bug"></i>
            <div><strong>Sandbox mode</strong> — payments are simulated locally. Any phone number works; no real M-Pesa prompt is sent.</div>
        </div>
        @elseif(empty($clientPhone))
        <div class="mpesa-notice mpesa-notice-info">
            <i class="bi bi-telephone-plus"></i>
            <div>No phone on file — you'll enter the number in the payment form.</div>
        </div>
        @endif

        <div class="mpesa-card-actions {{ $mpesaReady ? '' : 'opacity-50' }}">
            <div class="mpesa-amt-label">Quick amounts</div>
            <div class="mpesa-amt-grid">
                @foreach($quickAmounts as $amt)
                <button type="button"
                    class="mpesa-amt-chip {{ $mpesaReady ? 'mpesa-stk-trigger' : '' }} {{ $suggestedPremium && (int) $amt === (int) $suggestedPremium ? 'mpesa-amt-chip-suggested' : '' }}"
                    @if($mpesaReady) data-bs-toggle="modal" data-bs-target="#mpesaStkModal" @endif
                    @if(! $mpesaReady) disabled title="M-Pesa unavailable" @endif
                    data-mpesa-amount="{{ $amt }}">
                    <span class="mpesa-amt-chip-currency">KES</span>
                    <span class="mpesa-amt-chip-value">{{ number_format($amt) }}</span>
                </button>
                @endforeach
            </div>
            <button type="button" class="mpesa-btn-collect {{ $mpesaReady ? 'mpesa-stk-trigger' : '' }}"
                @if($mpesaReady) data-bs-toggle="modal" data-bs-target="#mpesaStkModal" @endif
                @if(! $mpesaReady) disabled title="M-Pesa unavailable" @endif>
                <i class="bi bi-lightning-charge-fill me-2"></i>Custom amount — send STK push
            </button>
        </div>

        @if($mpesaReady && ($mpesaTransactions ?? collect())->isNotEmpty())
        <div class="mpesa-tx-list border-top pt-3">
            <div class="mpesa-tx-list-head">Recent payments</div>
            @foreach(($mpesaTransactions ?? collect())->take(5) as $tx)
            @php
                $st = $tx->status;
                $iconClass = match ($st) {
                    'success' => 'success',
                    'pending' => 'pending',
                    'cancelled' => 'cancelled',
                    default => 'failed',
                };
                $icon = match ($st) {
                    'success' => 'bi-check-lg',
                    'pending' => 'bi-hourglass-split',
                    'cancelled' => 'bi-x-lg',
                    default => 'bi-exclamation-lg',
                };
            @endphp
            <div class="mpesa-tx-item">
                <div class="mpesa-tx-icon {{ $iconClass }}"><i class="bi {{ $icon }}"></i></div>
                <div class="mpesa-tx-body">
                    <div class="mpesa-tx-amount">KES {{ number_format((float) $tx->amount, 0) }}</div>
                    <div class="mpesa-tx-meta">
                        {{ $tx->phone }} · {{ $tx->created_at?->diffForHumans() ?? '—' }}
                        · {{ ucfirst($st) }}
                    </div>
                </div>
                @if($tx->mpesa_receipt_number)
                <span class="mpesa-tx-receipt">{{ $tx->mpesa_receipt_number }}</span>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
