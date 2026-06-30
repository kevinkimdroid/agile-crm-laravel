@if(config('branding.logo_vector', true))
    @include('partials.client-brand', ['variant' => $variant ?? 'full', 'showTagline' => $showTagline ?? null])
@else
    <img
        src="{{ asset(config('branding.logo_display', config('branding.logo'))) }}?v=6"
        alt="{{ config('branding.client_name') }}"
        class="{{ $class ?? 'client-logo' }}"
        loading="eager"
        decoding="async"
        style="width:100%;height:auto;display:block;"
    >
@endif
