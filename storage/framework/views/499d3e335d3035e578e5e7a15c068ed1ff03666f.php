<?php $__env->startSection('title', 'ERP Messaging — Sent'); ?>

<?php
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
?>

<?php $__env->startPush('head'); ?>
    <?php echo $__env->make('tools.partials.erp-messages-grid-styles', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="ko-grid" id="koGrid">
    <div class="ko-grid-cmd">
        <a href="<?php echo e(route('tools')); ?>" class="ko-grid-back" title="Back to Tools"><i class="bi bi-arrow-left"></i></a>
        <div class="dropdown ko-grid-view">
            <button class="ko-grid-view-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                My Messages <i class="bi bi-chevron-down" style="font-size:0.85rem"></i>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo e(route('tools.erp-messaging')); ?>">Drafts</a></li>
                <li><a class="dropdown-item active" href="<?php echo e(route('tools.erp-messaging.sent')); ?>">Sent</a></li>
            </ul>
        </div>
        <span class="text-muted small">(<span id="erp-sent-visible-count"><?php echo e($rows->count()); ?></span> of <?php echo e($rows->count()); ?>)</span>

        <div class="ko-grid-actions">
            <button type="button" class="ko-grid-btn" id="koFocusedBtn"><i class="bi bi-layout-sidebar-reverse"></i> Focused view</button>
            <div class="dropdown">
                <button class="ko-grid-btn" type="button" data-bs-toggle="dropdown"><i class="bi bi-funnel"></i> Edit filters</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php $__currentLoopData = $filterLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <a class="dropdown-item <?php echo e($filter === $key ? 'active' : ''); ?>" href="<?php echo e(route('tools.erp-messaging.sent', ['filter' => $key])); ?>">
                                <?php echo e($meta['label']); ?> (<?php echo e(number_format($meta['count'])); ?>)
                            </a>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
            <a href="<?php echo e(route('tools.erp-messaging')); ?>" class="ko-grid-primary"><i class="bi bi-send"></i> Drafts</a>
        </div>
    </div>

    <?php $erpCategory = 'sent'; ?>
    <?php echo $__env->make('tools.partials.erp-messages-categories', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php if(!empty($loadError)): ?>
        <div class="ko-grid-alert alert alert-danger mb-0">
            <strong>Could not load sent messages.</strong> <?php echo e($loadError); ?>

        </div>
    <?php else: ?>
        <div class="ko-grid-searchbar">
            <div class="ko-grid-search">
                <input type="search" id="erp-sent-search" placeholder="Try to search in your own words" autocomplete="off" aria-label="Search table">
                <i class="bi bi-search ko-grid-search-ico"></i>
            </div>
            <button type="button" class="ko-grid-btn" id="erp-sent-search-clear">Clear</button>
            <span class="ko-grid-chip">Status: <?php echo e($filterLabel); ?> <a href="<?php echo e(route('tools.erp-messaging.sent', ['filter' => 'all'])); ?>" class="text-decoration-none text-muted">&times;</a></span>
            <span class="ko-grid-chip">Direction: Outgoing <button type="button" onclick="this.parentElement.style.display='none'" aria-label="Remove">&times;</button></span>
            <?php if($transport === 'http'): ?>
                <span class="ko-grid-chip">Transport: HTTP</span>
            <?php elseif($transport === 'oracle'): ?>
                <span class="ko-grid-chip">Transport: Oracle</span>
            <?php endif; ?>
            <div class="d-lg-none w-100">
                <label for="erp-sent-filter-select" class="form-label small text-muted mb-1">Jump to filter</label>
                <select id="erp-sent-filter-select" class="form-select form-select-sm">
                    <?php $__currentLoopData = $filterLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e(route('tools.erp-messaging.sent', ['filter' => $key])); ?>" <?php echo e($filter === $key ? 'selected' : ''); ?>>
                            <?php echo e($meta['label']); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                        <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
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
                        ?>
                        <tr class="erp-sent-row"
                            data-search="<?php echo e(e($searchBlob)); ?>"
                            data-phone="<?php echo e(e($phone !== '' ? $phone : '—')); ?>"
                            data-body="<?php echo e(e((string) $message->message_body)); ?>">
                            <td class="ko-grid-check"><input type="checkbox" class="form-check-input ko-row-check"></td>
                            <td><a href="#" class="ko-grid-link ko-open-preview">SMS</a></td>
                            <td><a href="#" class="ko-grid-link ko-open-preview"><?php echo e($phone !== '' ? $phone : '—'); ?></a></td>
                            <td data-col="policy"><?php echo e($message->policy_no ?: '—'); ?></td>
                            <td style="max-width: 22rem;">
                                <div class="erp-sent-msg-preview text-truncate" title="<?php echo e(e($message->message_body)); ?>"><?php echo e($message->message_body); ?></div>
                                <?php if(strlen((string) $message->message_body) > 80): ?>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none erp-sent-toggle-msg">Show full</button>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($displayLabel); ?></td>
                            <td>Outgoing</td>
                            <td class="text-nowrap"><?php echo e($message->sent_date ?: ($message->crm_sent_at ? $message->crm_sent_at->format('n/j/Y g:i A') : '—')); ?></td>
                            <td><?php echo e($readLabel); ?></td>
                            <td data-col="crmsent" class="text-nowrap"><?php echo e($message->crm_sent_at ? $message->crm_sent_at->format('n/j/Y g:i A') : '—'); ?></td>
                            <td data-col="module"><?php echo e($message->sys_module ?: '—'); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="11" class="ko-grid-empty">
                                No messages for this filter. Try <a href="<?php echo e(route('tools.erp-messaging.sent', ['filter' => 'all'])); ?>">All</a>.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <aside class="ko-grid-preview">
                <h2 id="erpPreviewTo">Select a message</h2>
                <div class="ko-grid-msg" id="erpPreviewBody">Turn on Focused view and click a row to read the SMS.</div>
            </aside>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/tools/erp-messaging-sent.blade.php ENDPATH**/ ?>