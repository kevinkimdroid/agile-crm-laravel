@php
    $variant = $variant ?? 'full'; // full | compact
    $tagline = $showTagline ?? ($variant === 'full');
@endphp
<div class="ko-brand ko-brand--{{ $variant }}" aria-label="{{ config('branding.client_name') }}">
    <div class="ko-brand-mark" aria-hidden="true">
        @include('partials.client-brand-mark')
    </div>
    <div class="ko-brand-copy">
        <div class="ko-brand-line1">KENYA ORIENT</div>
        <div class="ko-brand-line2">INSURANCE LIMITED</div>
        @if($tagline)
            <div class="ko-brand-tagline">{{ config('branding.tagline') }}</div>
        @endif
    </div>
</div>
