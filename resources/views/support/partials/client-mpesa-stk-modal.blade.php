{{-- M-Pesa STK Push modal --}}
@php
    $suggestedPremium = client_suggested_premium_amount($client ?? null);
    $defaultAmount = old('amount', $suggestedPremium ?: '');
    $mpesaService = app(\App\Services\MpesaStkPushService::class);
    $mpesaSandboxSimulate = $mpesaSandboxSimulate ?? $mpesaService->isSandboxSimulate();
    $mpesaEnv = config('mpesa.environment', 'sandbox');
    $mpesaQuickAmounts = array_values(array_unique(array_filter(array_merge(
        $suggestedPremium ? [$suggestedPremium] : [],
        [500, 1000, 2500, 5000, 10000]
    ))));
    $policyRef = $mpesaPolicyNumber ?? $clientPolicy ?? $policy ?? '—';
@endphp
<div class="modal fade mpesa-ui" id="mpesaStkModal" tabindex="-1" aria-labelledby="mpesaStkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="{{ route('support.clients.mpesa-stk') }}" id="mpesaStkForm">
                @csrf
                <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                <input type="hidden" name="policy_number" value="{{ $policyRef }}">
                <input type="hidden" name="client_name" value="{{ ($clientName ?? 'Client') !== 'Client' ? ($clientName ?? '') : '' }}">

                <div class="mpesa-modal-hero">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mpesa-modal-title" id="mpesaStkModalLabel">M-Pesa STK Push</h5>
                            <p class="mpesa-modal-sub">
                                @if($mpesaSandboxSimulate)
                                    Sandbox — simulated payment, any phone number
                                @else
                                    Payment prompt sent directly to the client's phone
                                @endif
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <div class="mpesa-modal-body">
                    @if($mpesaSandboxSimulate)
                    <div class="mpesa-notice mpesa-notice-info mb-3" style="margin:0 0 1rem;">
                        <i class="bi bi-bug"></i>
                        <div>STK is faked locally — no Safaricom credentials needed.</div>
                    </div>
                    @elseif($mpesaEnv === 'sandbox')
                    <div class="mpesa-notice mpesa-notice-info mb-3" style="margin:0 0 1rem;">
                        <i class="bi bi-info-circle"></i>
                        <div>Sandbox only works on test phone <code>254708374149</code>. Use production for real numbers.</div>
                    </div>
                    @endif

                    <div class="mpesa-policy-pill">
                        <span>Policy</span>
                        <span>{{ $policyRef }}</span>
                    </div>

                    @include('support.partials.client-mpesa-phone-preview', [
                        'previewAmount' => $defaultAmount ?: $suggestedPremium,
                        'previewPolicy' => $policyRef,
                        'previewId' => 'mpesaModalPhonePreview',
                    ])

                    <div class="mb-3 mt-3">
                        <label for="mpesa_phone" class="mpesa-field-label d-block">
                            Phone number{{ $mpesaSandboxSimulate ? '' : ' (Safaricom)' }}
                        </label>
                        <input type="text" class="form-control mpesa-field-input" name="phone" id="mpesa_phone" required
                            placeholder="{{ $mpesaSandboxSimulate ? '07xx xxx xxx' : '07xx xxx xxx' }}"
                            value="{{ old('phone', $clientPhone ?? '') }}">
                    </div>

                    <div class="mb-3">
                        <label for="mpesa_amount" class="mpesa-field-label d-block">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text mpesa-field-input" style="border-right:0;background:#f8fafc;font-weight:700;">KES</span>
                            <input type="number" class="form-control mpesa-field-input" name="amount" id="mpesa_amount" required
                                min="1" max="999999" step="1" placeholder="5000"
                                value="{{ $defaultAmount }}"
                                style="border-left:0;">
                        </div>
                        <div class="mpesa-modal-quick">
                            @foreach($mpesaQuickAmounts as $amt)
                            <button type="button" class="mpesa-quick-amt" data-amt="{{ $amt }}">{{ number_format($amt) }}</button>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="mpesa_description" class="mpesa-field-label d-block">Note <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" class="form-control mpesa-field-input" name="description" id="mpesa_description" maxlength="100"
                            placeholder="Premium payment"
                            value="{{ old('description', 'Premium — '.$policyRef) }}">
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
                        @if(empty($mpesaConfigured)) disabled title="M-Pesa unavailable" @endif>
                        @if($mpesaSandboxSimulate)
                            <i class="bi bi-play-circle me-1"></i> Simulate
                        @else
                            <i class="bi bi-send me-1"></i> Send STK Push
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
