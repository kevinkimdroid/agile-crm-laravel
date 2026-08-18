<?php $__env->startSection('title', 'ERP Messaging'); ?>

<?php
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
                <li><a class="dropdown-item active" href="<?php echo e(route('tools.erp-messaging')); ?>">Drafts</a></li>
                <li><a class="dropdown-item" href="<?php echo e(route('tools.erp-messaging.sent')); ?>">Sent</a></li>
            </ul>
        </div>
        <span class="text-muted small" id="erpVisibleCount"><?php echo e($previewCount); ?> shown</span>
        <span class="visually-hidden" id="erpDraftChip"><?php echo e(number_format($draftTotal)); ?></span>

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
            <form action="<?php echo e(route('tools.erp-messaging.send')); ?>" method="POST" id="erp-send-form" class="d-flex flex-wrap align-items-center gap-2">
                <?php echo csrf_field(); ?>
                <input type="hidden" id="erp-send-limit" name="limit" value="<?php echo e(old('limit', $defaultLimit)); ?>" <?php echo e(!$canSendLive ? 'disabled' : ''); ?>>
                <div class="dropdown">
                    <button type="button" class="ko-grid-btn" data-bs-toggle="dropdown" <?php echo e(!$canSendLive ? 'disabled' : ''); ?>>Send <?php echo e($defaultLimit); ?> <i class="bi bi-chevron-down"></i></button>
                    <ul class="dropdown-menu" id="erpQuickAmounts">
                        <?php $__currentLoopData = [10, 25, 50]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><button type="button" class="dropdown-item" data-n="<?php echo e($n); ?>">Next <?php echo e($n); ?></button></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <li><button type="button" class="dropdown-item" data-n="<?php echo e(min(500, max(1, $draftTotal ?: 1))); ?>">All (<?php echo e(number_format($draftTotal)); ?>)</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="px-3 py-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="dry_run" name="dry_run" <?php echo e(!$canSendLive ? 'disabled' : ''); ?>>
                                <label class="form-check-label small" for="dry_run">Test only</label>
                            </div>
                        </li>
                    </ul>
                </div>
                <button type="submit" class="ko-grid-primary" id="erp-send-btn" <?php echo e(!$canSendLive ? 'disabled' : ''); ?>>
                    <i class="bi bi-send"></i> <span id="erpSendLabel">Send <?php echo e($defaultLimit); ?> SMS</span>
                </button>
            </form>
        </div>
    </div>

    <?php $erpCategory = 'drafts'; ?>
    <?php echo $__env->make('tools.partials.erp-messages-categories', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php if(session('success') || session('warning') || session('error')): ?>
        <div class="ko-grid-alert alert alert-<?php echo e(session('error') ? 'danger' : (session('warning') ? 'warning' : 'success')); ?> alert-dismissible fade show mb-0" role="alert">
            <strong><?php echo e(session('error') ? 'Send failed' : (session('warning') ? 'Completed with issues' : 'Send completed')); ?></strong>
            <?php echo e(session('success') ?? session('warning') ?? session('error')); ?>

            <?php if(is_array($summary)): ?>
                <span class="small ms-2">Processed <?php echo e((int) ($summary['processed'] ?? 0)); ?> · Sent <?php echo e((int) ($summary['sent'] ?? 0)); ?> · Failed <?php echo e((int) ($summary['failed'] ?? 0)); ?> · Skipped <?php echo e((int) ($summary['skipped'] ?? 0)); ?></span>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(!empty($loadError)): ?>
        <div class="ko-grid-alert alert alert-warning mb-0">
            <strong>Queue not connected.</strong>
            <?php echo e($loadHint ?? $loadError); ?>

            Start erp-clients-api, set <code>FINANCE_ERP_HTTP_BASE=http://127.0.0.1:5000</code>, then refresh.
        </div>
    <?php endif; ?>

    <div class="ko-grid-searchbar">
        <div class="ko-grid-search">
            <input type="search" id="erp-pending-search" placeholder="Try to search in your own words" autocomplete="off" <?php echo e($queueOk && $previewCount > 0 ? '' : 'disabled'); ?>>
            <i class="bi bi-search ko-grid-search-ico"></i>
        </div>
        <span class="ko-grid-chip" id="koChipStatus">Status: Draft <button type="button" data-chip="status" aria-label="Remove">&times;</button></span>
        <span class="ko-grid-chip" id="koChipDir">Direction: Outgoing <button type="button" data-chip="direction" aria-label="Remove">&times;</button></span>
        <?php if($hasMoreInErp): ?>
            <span class="text-muted small">Oldest <?php echo e($previewLimit); ?> of <?php echo e(number_format($totalPending)); ?></span>
        <?php endif; ?>
    </div>

    <div class="ko-grid-body">
        <div class="ko-grid-table-wrap">
            <table class="ko-grid-table" id="koDraftTable">
                <thead>
                    <tr>
                        <th class="ko-grid-check"><input type="checkbox" class="form-check-input" id="koSelectAll" <?php echo e($previewCount ? '' : 'disabled'); ?>></th>
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
                    <?php $__empty_1 = true; $__currentLoopData = ($pending ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $searchBlob = strtolower(implode(' ', array_filter([
                                $message->message_id, $message->policy_no, $message->phone, $message->message_body, $message->sys_module,
                            ])));
                            $phone = trim((string) ($message->phone ?? ''));
                        ?>
                        <tr class="erp-pending-row"
                            data-search="<?php echo e(e($searchBlob)); ?>"
                            data-phone="<?php echo e(e($phone !== '' ? $phone : 'No phone')); ?>"
                            data-body="<?php echo e(e((string) $message->message_body)); ?>"
                            data-policy="<?php echo e(e((string) ($message->policy_no ?? ''))); ?>"
                            data-module="<?php echo e(e((string) ($message->sys_module ?? ''))); ?>"
                            data-date="<?php echo e(e((string) ($message->created_date ?? ''))); ?>">
                            <td class="ko-grid-check"><input type="checkbox" class="form-check-input ko-row-check"></td>
                            <td><a href="#" class="ko-grid-link ko-open-preview">SMS</a></td>
                            <td><a href="#" class="ko-grid-link ko-open-preview"><?php echo e($phone !== '' ? $phone : '—'); ?></a></td>
                            <td data-col="policy"><?php echo e($message->policy_no ?: '—'); ?></td>
                            <td style="max-width: 22rem;"><div class="text-truncate"><?php echo e($message->message_body); ?></div></td>
                            <td>Draft</td>
                            <td data-col="direction">Outgoing</td>
                            <td class="text-nowrap"><?php echo e($message->created_date ?: '—'); ?></td>
                            <td data-col="module"><?php echo e($message->sys_module ?: '—'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="9" class="ko-grid-empty">
                                <?php echo e(!empty($loadError) ? 'Drafts will appear here once ERP messaging is reachable.' : 'No draft SMS in ERP right now.'); ?>

                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <aside class="ko-grid-preview">
            <h2 id="erpPreviewTo"><?php echo e($queueOk && $previewCount > 0 ? 'Select a message' : 'Nothing selected'); ?></h2>
            <div class="small text-muted mb-2" id="erpPreviewMeta" style="<?php echo e($queueOk && $previewCount > 0 ? '' : 'display:none;'); ?>">
                <span id="erpPreviewPolicy">Policy</span> · <span id="erpPreviewModule">Module</span> · <span id="erpPreviewDate">Date</span>
            </div>
            <div class="ko-grid-msg" id="erpPreviewBody"><?php echo e($queueOk && $previewCount > 0 ? 'Turn on Focused view and click a row to read the SMS.' : 'No draft selected.'); ?></div>
            <?php if(!$canSendLive): ?>
                <p class="small text-muted mt-3 mb-0">Sending stays locked until drafts load and Advanta SMS is ready.</p>
            <?php endif; ?>
        </aside>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/tools/erp-messaging.blade.php ENDPATH**/ ?>