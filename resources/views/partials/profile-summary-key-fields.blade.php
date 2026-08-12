@php
    $keyFieldsTitle = $keyFieldsTitle ?? 'Key Fields';
    $keyFieldsViewDetailsUrl = $keyFieldsViewDetailsUrl ?? null;
@endphp
<div class="card contact-detail-card mb-4 client-summary-personal">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            @if($keyFieldsShowIcon ?? false)
            <div class="client-details-block-icon"><i class="bi bi-person-vcard"></i></div>
            @endif
            <h6 class="text-uppercase small fw-bold mb-0" style="color:var(--agile-primary,#0E4385)">{{ $keyFieldsTitle }}</h6>
        </div>
        <div class="client-summary-personal-grid">
            @foreach($keyFields as $field)
            <div class="client-summary-field">
                <span class="client-summary-label">{{ $field['label'] }}</span>
                <span class="client-summary-value {{ $field['class'] ?? '' }}">{!! $field['value'] !!}</span>
            </div>
            @endforeach
        </div>
        @if($keyFieldsViewDetailsUrl)
        <div class="mt-3 pt-3 border-top">
            <a href="{{ $keyFieldsViewDetailsUrl }}" class="btn btn-sm btn-outline-primary">View full details</a>
        </div>
        @endif
    </div>
</div>
