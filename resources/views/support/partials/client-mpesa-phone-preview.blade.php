{{-- Reusable phone mockup for M-Pesa STK preview --}}
@php
    $previewAmount = $previewAmount ?? ($suggestedPremium ?? null);
    $previewPolicy = $previewPolicy ?? ($clientPolicy ?? $policy ?? '—');
    $previewId = $previewId ?? 'mpesaPhonePreview';
@endphp
<div class="mpesa-phone-preview" id="{{ $previewId }}">
    <div class="mpesa-phone-frame">
        <div class="mpesa-phone-screen">
            <div class="mpesa-phone-notch"></div>
            <div class="mpesa-phone-prompt">
                <div class="mpesa-phone-prompt-label">M-Pesa</div>
                <div class="mpesa-phone-prompt-amount" data-mpesa-preview-amount>
                    @if($previewAmount)
                        KES {{ number_format((int) $previewAmount) }}
                    @else
                        KES —
                    @endif
                </div>
                <div class="mpesa-phone-prompt-hint" data-mpesa-preview-hint>
                    Enter PIN to pay · {{ Str::limit($previewPolicy, 14) }}
                </div>
            </div>
        </div>
    </div>
</div>
