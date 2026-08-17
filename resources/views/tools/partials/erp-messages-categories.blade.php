@php
    $erpCategory = $erpCategory ?? 'drafts';
    $draftN = $draftTotal ?? $previewCount ?? null;
    $sentN = $sentTotal ?? ($counts['total'] ?? null);
@endphp
<nav class="ko-cats" aria-label="Message categories">
    <a class="ko-cat {{ $erpCategory === 'drafts' ? 'is-on' : '' }}" href="{{ route('tools.erp-messaging') }}">
        Drafts
        @if ($draftN !== null && $draftN !== '')
            <em>{{ number_format((int) $draftN) }}</em>
        @endif
    </a>
    <a class="ko-cat {{ $erpCategory === 'sent' ? 'is-on' : '' }}" href="{{ route('tools.erp-messaging.sent') }}">
        Sent
        @if ($sentN !== null && $sentN !== '')
            <em>{{ number_format((int) $sentN) }}</em>
        @endif
    </a>
</nav>
@if ($erpCategory === 'sent' && !empty($filterLinks))
    <nav class="ko-cats ko-cats-sub" aria-label="Sent status">
        @foreach ($filterLinks as $key => $meta)
            <a class="ko-cat {{ ($filter ?? 'all') === $key ? 'is-on' : '' }}"
               href="{{ route('tools.erp-messaging.sent', ['filter' => $key]) }}">
                {{ $meta['label'] }}
                <em>{{ number_format((int) ($meta['count'] ?? 0)) }}</em>
            </a>
        @endforeach
    </nav>
@endif
