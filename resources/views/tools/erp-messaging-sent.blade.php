@extends('layouts.app')

@section('title', 'ERP Messaging — Sent')

@php
    $counts = $counts ?? [];
    $filter = $filter ?? 'all';
    $rows = $rows ?? collect();
    $filterLinks = [
        'all' => ['label' => 'All', 'count' => (int) ($counts['total'] ?? 0)],
        'pending_delivery' => ['label' => 'Awaiting delivery', 'count' => (int) ($counts['pending_delivery'] ?? 0)],
        'delivered' => ['label' => 'Delivered', 'count' => (int) ($counts['delivered'] ?? 0)],
        'read' => ['label' => 'Read', 'count' => (int) ($counts['read'] ?? 0)],
        'not_read' => ['label' => 'Not read', 'count' => (int) ($counts['not_read'] ?? 0)],
    ];
    if ($filter === 'sent') {
        $filter = 'all';
    }
    $transport = $erpSmsTransport ?? '';
    $filterLabel = $filterLinks[$filter]['label'] ?? 'All';
@endphp

@push('head')
    @include('tools.partials.erp-messages-grid-styles')
@endpush

@section('content')
<div class="ko-grid" id="koGrid">
    <div class="ko-grid-cmd">
        <a href="{{ route('tools') }}" class="ko-grid-back" title="Back to Tools"><i class="bi bi-arrow-left"></i></a>
        <div class="dropdown ko-grid-view">
            <button class="ko-grid-view-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                My Messages <i class="bi bi-chevron-down" style="font-size:0.85rem"></i>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('tools.erp-messaging') }}">Drafts</a></li>
                <li><a class="dropdown-item active" href="{{ route('tools.erp-messaging.sent') }}">Sent</a></li>
            </ul>
        </div>
        <span class="text-muted small">(<span id="erp-sent-visible-count">{{ $rows->count() }}</span> of {{ $rows->count() }})</span>

        <div class="ko-grid-actions">
            <button type="button" class="ko-grid-btn" id="koFocusedBtn"><i class="bi bi-layout-sidebar-reverse"></i> Focused view</button>
            <div class="dropdown">
                <button class="ko-grid-btn" type="button" data-bs-toggle="dropdown"><i class="bi bi-funnel"></i> Edit filters</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach ($filterLinks as $key => $meta)
                        <li>
                            <a class="dropdown-item {{ $filter === $key ? 'active' : '' }}" href="{{ route('tools.erp-messaging.sent', ['filter' => $key]) }}">
                                {{ $meta['label'] }} ({{ number_format($meta['count']) }})
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="dropdown">
                <button class="ko-grid-btn" type="button" data-bs-toggle="dropdown"><i class="bi bi-layout-three-columns"></i> Edit columns</button>
                <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 12rem;">
                    <label class="dropdown-item"><input type="checkbox" class="form-check-input me-2 ko-col-toggle" data-col="policy" checked> Policy</label>
                    <label class="dropdown-item"><input type="checkbox" class="form-check-input me-2 ko-col-toggle" data-col="module" checked> Module</label>
                    <label class="dropdown-item"><input type="checkbox" class="form-check-input me-2 ko-col-toggle" data-col="crmsent" checked> CRM sent</label>
                </div>
            </div>
            <a href="{{ route('tools.erp-messaging') }}" class="ko-grid-primary"><i class="bi bi-send"></i> Drafts</a>
        </div>
    </div>

    @php $erpCategory = 'sent'; @endphp
    @include('tools.partials.erp-messages-categories')

    @if (!empty($loadError))
        <div class="ko-grid-alert alert alert-danger mb-0">
            <strong>Could not load sent messages.</strong> {{ $loadError }}
        </div>
    @else
        <div class="ko-grid-searchbar">
            <div class="ko-grid-search">
                <input type="search" id="erp-sent-search" placeholder="Try to search in your own words" autocomplete="off" aria-label="Search table">
                <i class="bi bi-search ko-grid-search-ico"></i>
            </div>
            <button type="button" class="ko-grid-btn" id="erp-sent-search-clear">Clear</button>
            <span class="ko-grid-chip">Status: {{ $filterLabel }} <a href="{{ route('tools.erp-messaging.sent', ['filter' => 'all']) }}" class="text-decoration-none text-muted">&times;</a></span>
            <span class="ko-grid-chip">Direction: Outgoing <button type="button" onclick="this.parentElement.style.display='none'" aria-label="Remove">&times;</button></span>
            @if ($transport === 'http')
                <span class="ko-grid-chip">Transport: HTTP</span>
            @elseif ($transport === 'oracle')
                <span class="ko-grid-chip">Transport: Oracle</span>
            @endif
            <div class="d-lg-none w-100">
                <label for="erp-sent-filter-select" class="form-label small text-muted mb-1">Jump to filter</label>
                <select id="erp-sent-filter-select" class="form-select form-select-sm">
                    @foreach ($filterLinks as $key => $meta)
                        <option value="{{ route('tools.erp-messaging.sent', ['filter' => $key]) }}" {{ $filter === $key ? 'selected' : '' }}>
                            {{ $meta['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="ko-grid-body">
            <div class="ko-grid-table-wrap">
                <table class="ko-grid-table">
                    <thead>
                        <tr>
                            <th class="ko-grid-check"><input type="checkbox" class="form-check-input" id="koSelectAll"></th>
                            <th>Configuration <i class="bi bi-chevron-down small"></i></th>
                            <th>Mobile Number <i class="bi bi-chevron-down small"></i></th>
                            <th data-col="policy">Policy <i class="bi bi-chevron-down small"></i></th>
                            <th>Message <i class="bi bi-chevron-down small"></i></th>
                            <th>Status <i class="bi bi-chevron-down small"></i></th>
                            <th>Direction <i class="bi bi-chevron-down small"></i></th>
                            <th>Date <i class="bi bi-arrow-down small"></i></th>
                            <th>Read <i class="bi bi-chevron-down small"></i></th>
                            <th data-col="crmsent">CRM sent <i class="bi bi-chevron-down small"></i></th>
                            <th data-col="module">Module <i class="bi bi-chevron-down small"></i></th>
                        </tr>
                    </thead>
                    <tbody id="erp-sent-tbody">
                        @forelse($rows as $message)
                        @php
                            $deliveryState = $message->delivery_state ?? 'unknown';
                            $readState = $message->read_state ?? 'unknown';
                            $deliveryLabel = $deliveryState === 'delivered' ? 'Delivered' : ($deliveryState === 'failed' ? 'Failed' : ($deliveryState === 'pending' ? 'Submitted' : 'Unknown'));
                            $displayLabel = trim((string) ($message->advanta_status ?? '')) !== ''
                                ? $message->advanta_status
                                : $deliveryLabel;
                            $readLabel = $readState === 'read' ? 'Read' : ($readState === 'not_read' ? 'Not read' : 'N/A');
                            $searchBlob = strtolower(implode(' ', array_filter([
                                $message->message_id,
                                $message->policy_no,
                                $message->phone,
                                $message->message_body,
                                $message->sys_module,
                                $displayLabel,
                            ])));
                            $phone = trim((string) ($message->phone ?? ''));
                        @endphp
                        <tr class="erp-sent-row"
                            data-search="{{ e($searchBlob) }}"
                            data-phone="{{ e($phone !== '' ? $phone : '—') }}"
                            data-body="{{ e((string) $message->message_body) }}">
                            <td class="ko-grid-check"><input type="checkbox" class="form-check-input ko-row-check"></td>
                            <td><a href="#" class="ko-grid-link ko-open-preview">SMS</a></td>
                            <td><a href="#" class="ko-grid-link ko-open-preview">{{ $phone !== '' ? $phone : '—' }}</a></td>
                            <td data-col="policy">{{ $message->policy_no ?: '—' }}</td>
                            <td style="max-width: 22rem;">
                                <div class="erp-sent-msg-preview text-truncate" title="{{ e($message->message_body) }}">{{ $message->message_body }}</div>
                                @if (strlen((string) $message->message_body) > 80)
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none erp-sent-toggle-msg">Show full</button>
                                @endif
                            </td>
                            <td>{{ $displayLabel }}</td>
                            <td>Outgoing</td>
                            <td class="text-nowrap">{{ $message->sent_date ?: ($message->crm_sent_at ? $message->crm_sent_at->format('n/j/Y g:i A') : '—') }}</td>
                            <td>{{ $readLabel }}</td>
                            <td data-col="crmsent" class="text-nowrap">{{ $message->crm_sent_at ? $message->crm_sent_at->format('n/j/Y g:i A') : '—' }}</td>
                            <td data-col="module">{{ $message->sys_module ?: '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="ko-grid-empty">
                                No messages for this filter. Try <a href="{{ route('tools.erp-messaging.sent', ['filter' => 'all']) }}">All</a>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <aside class="ko-grid-preview">
                <h2 id="erpPreviewTo">Select a message</h2>
                <div class="ko-grid-msg" id="erpPreviewBody">Turn on Focused view and click a row to read the SMS.</div>
            </aside>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var grid = document.getElementById('koGrid');
    var focusedBtn = document.getElementById('koFocusedBtn');
    focusedBtn && focusedBtn.addEventListener('click', function () {
        grid.classList.toggle('is-focused');
        focusedBtn.classList.toggle('is-on', grid.classList.contains('is-focused'));
    });

    var sel = document.getElementById('erp-sent-filter-select');
    if (sel) {
        sel.addEventListener('change', function () {
            if (this.value) window.location.href = this.value;
        });
    }

    var searchInput = document.getElementById('erp-sent-search');
    var clearBtn = document.getElementById('erp-sent-search-clear');
    var tbody = document.getElementById('erp-sent-tbody');
    var visibleEl = document.getElementById('erp-sent-visible-count');
    if (!searchInput || !tbody) return;

    var rows = tbody.querySelectorAll('tr.erp-sent-row');
    var previewTo = document.getElementById('erpPreviewTo');
    var previewBody = document.getElementById('erpPreviewBody');

    function runFilter() {
        var q = (searchInput.value || '').trim().toLowerCase();
        var n = 0;
        rows.forEach(function (tr) {
            var hay = tr.getAttribute('data-search') || '';
            var show = !q || hay.indexOf(q) !== -1;
            tr.classList.toggle('filtered-out', !show);
            if (show) n++;
        });
        if (visibleEl) visibleEl.textContent = String(n);
    }

    searchInput.addEventListener('input', runFilter);
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            runFilter();
            searchInput.focus();
        });
    }

    function selectRow(tr) {
        rows.forEach(function (r) { r.classList.remove('table-active'); });
        tr.classList.add('table-active');
        if (previewTo) previewTo.textContent = tr.getAttribute('data-phone') || 'Preview';
        if (previewBody) previewBody.textContent = tr.getAttribute('data-body') || '';
    }

    rows.forEach(function (tr) {
        tr.addEventListener('click', function (e) {
            if (e.target.closest('input[type="checkbox"]') || e.target.closest('.erp-sent-toggle-msg')) return;
            e.preventDefault();
            selectRow(tr);
        });
    });
    if (rows.length) selectRow(rows[0]);

    var selectAll = document.getElementById('koSelectAll');
    selectAll && selectAll.addEventListener('change', function () {
        document.querySelectorAll('.ko-row-check').forEach(function (c) {
            var tr = c.closest('tr');
            if (tr && tr.classList.contains('filtered-out')) return;
            c.checked = selectAll.checked;
        });
    });

    document.querySelectorAll('.ko-col-toggle').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var col = cb.getAttribute('data-col');
            document.querySelectorAll('[data-col="' + col + '"]').forEach(function (el) {
                el.style.display = cb.checked ? '' : 'none';
            });
        });
    });

    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.erp-sent-toggle-msg');
        if (!btn) return;
        var cell = btn.closest('td');
        if (!cell) return;
        var preview = cell.querySelector('.erp-sent-msg-preview');
        if (!preview) return;
        var full = preview.classList.toggle('text-truncate') === false;
        preview.style.whiteSpace = full ? 'normal' : '';
        preview.style.maxWidth = full ? 'none' : '';
        btn.textContent = full ? 'Show less' : 'Show full';
    });
});
</script>
@endpush
