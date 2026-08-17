@extends('layouts.app')

@section('title', 'ERP Messaging')

@php
    $previewCount = (int) ($previewCount ?? 0);
    $totalPending = $totalPending ?? null;
    $previewLimit = (int) ($previewLimit ?? 50);
    $canLoad = $canLoad ?? false;
    $canSendLive = $canSendLive ?? false;
    $autoSendEnabled = $autoSendEnabled ?? false;
    $summary = session('erp_sms_summary');
    $hasMoreInErp = $totalPending !== null && $totalPending > $previewCount;
    $draftTotal = $totalPending !== null ? (int) $totalPending : $previewCount;
    $defaultLimit = min(50, max(1, $draftTotal ?: 50));
    $queueOk = $canLoad && empty($loadError);
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
                <li><a class="dropdown-item active" href="{{ route('tools.erp-messaging') }}">Drafts</a></li>
                <li><a class="dropdown-item" href="{{ route('tools.erp-messaging.sent') }}">Sent</a></li>
            </ul>
        </div>
        <span class="text-muted small" id="erpVisibleCount">{{ $previewCount }} shown</span>
        <span class="visually-hidden" id="erpDraftChip">{{ number_format($draftTotal) }}</span>

        <div class="ko-grid-actions">
            <button type="button" class="ko-grid-btn" id="koFocusedBtn" title="Show preview beside the list">
                <i class="bi bi-layout-sidebar-reverse"></i> Focused view
            </button>
            <div class="dropdown">
                <button class="ko-grid-btn" type="button" data-bs-toggle="dropdown"><i class="bi bi-layout-three-columns"></i> Edit columns</button>
                <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 12rem;">
                    <label class="dropdown-item"><input type="checkbox" class="form-check-input me-2 ko-col-toggle" data-col="policy" checked> Policy</label>
                    <label class="dropdown-item"><input type="checkbox" class="form-check-input me-2 ko-col-toggle" data-col="module" checked> Module</label>
                    <label class="dropdown-item"><input type="checkbox" class="form-check-input me-2 ko-col-toggle" data-col="direction" checked> Direction</label>
                </div>
            </div>
            <form action="{{ route('tools.erp-messaging.send') }}" method="POST" id="erp-send-form" class="d-flex flex-wrap align-items-center gap-2">
                @csrf
                <input type="hidden" id="erp-send-limit" name="limit" value="{{ old('limit', $defaultLimit) }}" {{ !$canSendLive ? 'disabled' : '' }}>
                <div class="dropdown">
                    <button type="button" class="ko-grid-btn" data-bs-toggle="dropdown" {{ !$canSendLive ? 'disabled' : '' }}>Send {{ $defaultLimit }} <i class="bi bi-chevron-down"></i></button>
                    <ul class="dropdown-menu" id="erpQuickAmounts">
                        @foreach ([10, 25, 50] as $n)
                            <li><button type="button" class="dropdown-item" data-n="{{ $n }}">Next {{ $n }}</button></li>
                        @endforeach
                        <li><button type="button" class="dropdown-item" data-n="{{ min(500, max(1, $draftTotal ?: 1)) }}">All ({{ number_format($draftTotal) }})</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="px-3 py-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="dry_run" name="dry_run" {{ !$canSendLive ? 'disabled' : '' }}>
                                <label class="form-check-label small" for="dry_run">Test only</label>
                            </div>
                        </li>
                    </ul>
                </div>
                <button type="submit" class="ko-grid-primary" id="erp-send-btn" {{ !$canSendLive ? 'disabled' : '' }}>
                    <i class="bi bi-send"></i> <span id="erpSendLabel">Send {{ $defaultLimit }} SMS</span>
                </button>
            </form>
        </div>
    </div>

    @php $erpCategory = 'drafts'; @endphp
    @include('tools.partials.erp-messages-categories')

    @if (session('success') || session('warning') || session('error'))
        <div class="ko-grid-alert alert alert-{{ session('error') ? 'danger' : (session('warning') ? 'warning' : 'success') }} alert-dismissible fade show mb-0" role="alert">
            <strong>{{ session('error') ? 'Send failed' : (session('warning') ? 'Completed with issues' : 'Send completed') }}</strong>
            {{ session('success') ?? session('warning') ?? session('error') }}
            @if (is_array($summary))
                <span class="small ms-2">Processed {{ (int) ($summary['processed'] ?? 0) }} · Sent {{ (int) ($summary['sent'] ?? 0) }} · Failed {{ (int) ($summary['failed'] ?? 0) }} · Skipped {{ (int) ($summary['skipped'] ?? 0) }}</span>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (!empty($loadError))
        <div class="ko-grid-alert alert alert-warning mb-0">
            <strong>Queue not connected.</strong>
            {{ $loadHint ?? $loadError }}
            Start erp-clients-api, set <code>FINANCE_ERP_HTTP_BASE=http://127.0.0.1:5000</code>, then refresh.
        </div>
    @endif

    <div class="ko-grid-searchbar">
        <div class="ko-grid-search">
            <input type="search" id="erp-pending-search" placeholder="Try to search in your own words" autocomplete="off" {{ $queueOk && $previewCount > 0 ? '' : 'disabled' }}>
            <i class="bi bi-search ko-grid-search-ico"></i>
        </div>
        <span class="ko-grid-chip" id="koChipStatus">Status: Draft <button type="button" data-chip="status" aria-label="Remove">&times;</button></span>
        <span class="ko-grid-chip" id="koChipDir">Direction: Outgoing <button type="button" data-chip="direction" aria-label="Remove">&times;</button></span>
        @if ($hasMoreInErp)
            <span class="text-muted small">Oldest {{ $previewLimit }} of {{ number_format($totalPending) }}</span>
        @endif
    </div>

    <div class="ko-grid-body">
        <div class="ko-grid-table-wrap">
            <table class="ko-grid-table" id="koDraftTable">
                <thead>
                    <tr>
                        <th class="ko-grid-check"><input type="checkbox" class="form-check-input" id="koSelectAll" {{ $previewCount ? '' : 'disabled' }}></th>
                        <th>Configuration <i class="bi bi-chevron-down small"></i></th>
                        <th>Mobile Number <i class="bi bi-chevron-down small"></i></th>
                        <th data-col="policy">Policy <i class="bi bi-chevron-down small"></i></th>
                        <th>Message <i class="bi bi-chevron-down small"></i></th>
                        <th>Status <i class="bi bi-chevron-down small"></i></th>
                        <th data-col="direction">Direction <i class="bi bi-chevron-down small"></i></th>
                        <th>Date <i class="bi bi-arrow-down small"></i></th>
                        <th data-col="module">Module <i class="bi bi-chevron-down small"></i></th>
                    </tr>
                </thead>
                <tbody id="erp-pending-tbody">
                    @forelse(($pending ?? collect()) as $message)
                        @php
                            $searchBlob = strtolower(implode(' ', array_filter([
                                $message->message_id, $message->policy_no, $message->phone, $message->message_body, $message->sys_module,
                            ])));
                            $phone = trim((string) ($message->phone ?? ''));
                        @endphp
                        <tr class="erp-pending-row"
                            data-search="{{ e($searchBlob) }}"
                            data-phone="{{ e($phone !== '' ? $phone : 'No phone') }}"
                            data-body="{{ e((string) $message->message_body) }}"
                            data-policy="{{ e((string) ($message->policy_no ?? '')) }}"
                            data-module="{{ e((string) ($message->sys_module ?? '')) }}"
                            data-date="{{ e((string) ($message->created_date ?? '')) }}">
                            <td class="ko-grid-check"><input type="checkbox" class="form-check-input ko-row-check"></td>
                            <td><a href="#" class="ko-grid-link ko-open-preview">SMS</a></td>
                            <td><a href="#" class="ko-grid-link ko-open-preview">{{ $phone !== '' ? $phone : '—' }}</a></td>
                            <td data-col="policy">{{ $message->policy_no ?: '—' }}</td>
                            <td style="max-width: 22rem;"><div class="text-truncate">{{ $message->message_body }}</div></td>
                            <td>Draft</td>
                            <td data-col="direction">Outgoing</td>
                            <td class="text-nowrap">{{ $message->created_date ?: '—' }}</td>
                            <td data-col="module">{{ $message->sys_module ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="ko-grid-empty">
                                {{ !empty($loadError) ? 'Drafts will appear here once ERP messaging is reachable.' : 'No draft SMS in ERP right now.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <aside class="ko-grid-preview">
            <h2 id="erpPreviewTo">{{ $queueOk && $previewCount > 0 ? 'Select a message' : 'Nothing selected' }}</h2>
            <div class="small text-muted mb-2" id="erpPreviewMeta" style="{{ $queueOk && $previewCount > 0 ? '' : 'display:none;' }}">
                <span id="erpPreviewPolicy">Policy</span> · <span id="erpPreviewModule">Module</span> · <span id="erpPreviewDate">Date</span>
            </div>
            <div class="ko-grid-msg" id="erpPreviewBody">{{ $queueOk && $previewCount > 0 ? 'Turn on Focused view and click a row to read the SMS.' : 'No draft selected.' }}</div>
            @if (!$canSendLive)
                <p class="small text-muted mt-3 mb-0">Sending stays locked until drafts load and Advanta SMS is ready.</p>
            @endif
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var grid = document.getElementById('koGrid');
    var form = document.getElementById('erp-send-form');
    var dryRun = document.getElementById('dry_run');
    var btn = document.getElementById('erp-send-btn');
    var limitInput = document.getElementById('erp-send-limit');
    var sendLabel = document.getElementById('erpSendLabel');
    var previewTo = document.getElementById('erpPreviewTo');
    var previewBody = document.getElementById('erpPreviewBody');
    var previewMeta = document.getElementById('erpPreviewMeta');
    var previewPolicy = document.getElementById('erpPreviewPolicy');
    var previewModule = document.getElementById('erpPreviewModule');
    var previewDate = document.getElementById('erpPreviewDate');

    function syncSendLabel() {
        if (!sendLabel || !limitInput) return;
        var n = parseInt(limitInput.value, 10) || 0;
        sendLabel.textContent = (dryRun && dryRun.checked) ? ('Test ' + n + ' drafts') : ('Send ' + n + ' SMS');
    }
    dryRun && dryRun.addEventListener('change', syncSendLabel);

    document.querySelectorAll('#erpQuickAmounts [data-n]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (!limitInput || limitInput.disabled) return;
            limitInput.value = b.getAttribute('data-n');
            syncSendLabel();
        });
    });

    if (form && dryRun && btn) {
        form.addEventListener('submit', function (e) {
            if (!dryRun.checked) {
                var count = limitInput ? parseInt(limitInput.value, 10) || 0 : 0;
                if (!window.confirm('Send up to ' + count + ' draft SMS now? Each is sent once, then marked sent in ERP.')) {
                    e.preventDefault();
                    return;
                }
            }
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (dryRun.checked ? ' Testing…' : ' Sending…');
        });
    }

    var searchInput = document.getElementById('erp-pending-search');
    var tbody = document.getElementById('erp-pending-tbody');
    var visibleCount = document.getElementById('erpVisibleCount');
    var rows = tbody ? tbody.querySelectorAll('tr.erp-pending-row') : [];
    function filterRows() {
        var q = (searchInput && searchInput.value || '').trim().toLowerCase();
        var n = 0;
        rows.forEach(function (tr) {
            var match = !q || (tr.getAttribute('data-search') || '').indexOf(q) !== -1;
            tr.style.display = match ? '' : 'none';
            if (match) n++;
        });
        if (visibleCount) visibleCount.textContent = n + ' shown';
    }
    searchInput && searchInput.addEventListener('input', filterRows);

    document.querySelectorAll('[data-chip]').forEach(function (x) {
        x.addEventListener('click', function () {
            var chip = x.closest('.ko-grid-chip');
            if (chip) chip.style.display = 'none';
        });
    });

    function setText(el, value, fallback) {
        if (!el) return;
        el.textContent = (value || '').trim() || fallback;
    }

    function selectRow(tr) {
        rows.forEach(function (r) { r.classList.remove('table-active'); });
        tr.classList.add('table-active');
        if (previewTo) previewTo.textContent = tr.getAttribute('data-phone') || 'Preview';
        if (previewBody) previewBody.textContent = tr.getAttribute('data-body') || '';
        if (previewMeta) previewMeta.style.display = '';
        setText(previewPolicy, tr.getAttribute('data-policy'), 'Policy');
        setText(previewModule, tr.getAttribute('data-module'), 'Module');
        setText(previewDate, tr.getAttribute('data-date'), 'Date');
    }

    rows.forEach(function (tr) {
        tr.addEventListener('click', function (e) {
            if (e.target.closest('input[type="checkbox"]')) return;
            if (e.target.closest('a')) e.preventDefault();
            selectRow(tr);
        });
    });
    if (rows.length) selectRow(rows[0]);

    var focusedBtn = document.getElementById('koFocusedBtn');
    focusedBtn && focusedBtn.addEventListener('click', function () {
        grid.classList.toggle('is-focused');
        focusedBtn.classList.toggle('is-on', grid.classList.contains('is-focused'));
    });

    var selectAll = document.getElementById('koSelectAll');
    selectAll && selectAll.addEventListener('change', function () {
        document.querySelectorAll('.ko-row-check').forEach(function (c) {
            var tr = c.closest('tr');
            if (tr && tr.style.display === 'none') return;
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
});
</script>
@endpush
