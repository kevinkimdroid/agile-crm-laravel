<?php $__env->startSection('title', 'Complaint Register'); ?>

<?php $__env->startPush('head'); ?>
<style>
    .cmp-hero {
        background: linear-gradient(135deg, var(--agile-primary-dark, #122952) 0%, var(--agile-primary, #1B3F7A) 55%, #2563eb 100%);
        border-radius: 16px;
        color: #fff;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    .cmp-hero::after {
        content: '';
        position: absolute;
        right: -2rem;
        top: -2rem;
        width: 12rem;
        height: 12rem;
        border-radius: 50%;
        background: rgba(255,255,255,0.06);
        pointer-events: none;
    }
    .cmp-hero-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 12px;
        background: rgba(255,255,255,0.15);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }
    .mat-stat-card {
        background: #fff;
        border: 1px solid var(--agile-border, #e2e8f0);
        border-radius: 14px;
        padding: 1rem 1.15rem;
        height: 100%;
        box-shadow: 0 1px 3px rgba(15,23,42,0.04);
    }
    .mat-stat-label {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--agile-text-muted, #64748b);
    }
    .mat-stat-value { font-size: 1.75rem; font-weight: 700; line-height: 1.1; }
    .mat-stat-icon {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .mat-toolbar {
        background: #fff;
        border: 1px solid var(--agile-border, #e2e8f0);
        border-radius: 14px;
        padding: 0.85rem 1rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .mat-toolbar .form-label { font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--agile-text-muted); }
    .mat-filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        background: var(--agile-primary-muted, rgba(27,63,122,0.08));
        color: var(--agile-primary, #1B3F7A);
        font-size: 0.8125rem;
        font-weight: 500;
        text-decoration: none;
    }
    .mat-filter-chip:hover { background: rgba(27,63,122,0.14); color: var(--agile-primary-dark); }
    .cmp-register-pills { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .cmp-register-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.9rem;
        border-radius: 999px;
        background: #fff;
        border: 1px solid var(--agile-border, #e2e8f0);
        color: var(--agile-text);
        text-decoration: none;
        font-size: 0.8125rem;
        font-weight: 500;
    }
    .cmp-register-pill.active { background: var(--agile-primary); border-color: var(--agile-primary); color: #fff; }
    .cmp-table-card { border-top: 3px solid var(--agile-primary, #1B3F7A); overflow: hidden; }
    .cmp-table-card .table thead th {
        background: #f8fafc;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--agile-text-muted);
        white-space: nowrap;
        padding-top: 0.85rem;
        padding-bottom: 0.85rem;
    }
    .cmp-table-card .table tbody td { vertical-align: middle; padding-top: 0.85rem; padding-bottom: 0.85rem; }
    .cmp-ref-link { font-family: ui-monospace, monospace; font-weight: 600; color: var(--agile-primary); text-decoration: none; }
    .cmp-ref-link:hover { text-decoration: underline; }
    .cmp-summary { max-width: 280px; font-size: 0.8125rem; color: #475569; line-height: 1.45; }
    .cmp-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
        font-size: 0.6875rem;
        font-weight: 600;
    }
    .cmp-type-active { background: var(--agile-primary-muted); color: var(--agile-primary); }
    .cmp-type-review { background: #fef3c7; color: #b45309; }
    .cmp-type-excluded { background: #f1f5f9; color: #64748b; }
    .cmp-status-badge { font-size: 0.6875rem; font-weight: 600; padding: 0.25rem 0.55rem; border-radius: 999px; display: inline-block; }
    .cmp-status-received, .cmp-status-pending-response { background: #fef3c7; color: #b45309; }
    .cmp-status-under-investigation { background: #dbeafe; color: #1d4ed8; }
    .cmp-status-resolved { background: var(--agile-primary-muted); color: var(--agile-primary); }
    .cmp-status-closed, .cmp-status-escalated-to-ira { background: #f1f5f9; color: #64748b; }
    .mat-empty-state { padding: 3rem 1.5rem; text-align: center; }
    .mat-empty-icon {
        width: 4rem; height: 4rem; margin: 0 auto 1rem; border-radius: 50%;
        background: var(--agile-primary-muted); color: var(--agile-primary);
        display: flex; align-items: center; justify-content: center; font-size: 1.75rem;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $registerFilter = $registerFilter ?? 'complaints';
    $stats = $stats ?? ['total' => 0, 'open' => 0, 'review' => 0, 'excluded' => 0];
    $queryBase = request()->except(['page']);
?>

<nav class="mb-3">
    <a href="<?php echo e(route('support')); ?>" class="text-muted small text-decoration-none">Support</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Complaint Register</span>
</nav>

<div class="cmp-hero">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 position-relative" style="z-index:1">
        <div class="d-flex align-items-start gap-3">
            <div class="cmp-hero-icon" aria-hidden="true"><i class="bi bi-clipboard2-check-fill"></i></div>
            <div>
                <h1 class="h3 fw-bold mb-1">Complaint Register</h1>
                <p class="mb-0 opacity-90 small" style="max-width:36rem">
                    IRA compliance register for genuine customer complaints.
                    Inbound email is classified automatically — general inquiries and automated mail are kept out of the register.
                </p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo e(route('compliance.complaints.export', request()->query())); ?>" class="btn btn-light btn-sm fw-semibold shadow-sm">
                <i class="bi bi-file-earmark-excel text-success me-1"></i>Export Excel
            </a>
            <a href="<?php echo e(route('compliance.complaints.create')); ?>" class="btn btn-light btn-sm fw-semibold shadow-sm">
                <i class="bi bi-plus-lg me-1"></i>Register Complaint
            </a>
        </div>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i><?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="mat-stat-label">In register</span>
                <span class="mat-stat-icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-clipboard2-data"></i></span>
            </div>
            <div class="mat-stat-value"><?php echo e(number_format($stats['total'])); ?></div>
            <p class="text-muted small mb-0 mt-1">Confirmed complaints</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="mat-stat-label">Open</span>
                <span class="mat-stat-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-hourglass-split"></i></span>
            </div>
            <div class="mat-stat-value"><?php echo e(number_format($stats['open'])); ?></div>
            <p class="text-muted small mb-0 mt-1">Awaiting resolution</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="mat-stat-label">Needs review</span>
                <span class="mat-stat-icon" style="background:#fef3c7;color:#b45309"><i class="bi bi-search"></i></span>
            </div>
            <div class="mat-stat-value"><?php echo e(number_format($stats['review'])); ?></div>
            <p class="text-muted small mb-0 mt-1">Confirm or exclude</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="mat-stat-label">Not complaints</span>
                <span class="mat-stat-icon" style="background:#f1f5f9;color:#64748b"><i class="bi bi-filter"></i></span>
            </div>
            <div class="mat-stat-value"><?php echo e(number_format($stats['excluded'])); ?></div>
            <p class="text-muted small mb-0 mt-1">Filtered out</p>
        </div>
    </div>
</div>

<?php if($hasRegisterColumn ?? false): ?>
<div class="cmp-register-pills mb-3">
    <a href="<?php echo e(route('compliance.complaints.index', array_merge($queryBase, ['register' => 'complaints']))); ?>" class="cmp-register-pill <?php echo e($registerFilter === 'complaints' ? 'active' : ''); ?>">
        Complaints <span class="opacity-75"><?php echo e(number_format($stats['total'])); ?></span>
    </a>
    <a href="<?php echo e(route('compliance.complaints.index', array_merge($queryBase, ['register' => 'review']))); ?>" class="cmp-register-pill <?php echo e($registerFilter === 'review' ? 'active' : ''); ?>">
        Needs review <span class="opacity-75"><?php echo e(number_format($stats['review'])); ?></span>
    </a>
    <a href="<?php echo e(route('compliance.complaints.index', array_merge($queryBase, ['register' => 'excluded']))); ?>" class="cmp-register-pill <?php echo e($registerFilter === 'excluded' ? 'active' : ''); ?>">
        Not a complaint <span class="opacity-75"><?php echo e(number_format($stats['excluded'])); ?></span>
    </a>
    <a href="<?php echo e(route('compliance.complaints.index', array_merge($queryBase, ['register' => 'all']))); ?>" class="cmp-register-pill <?php echo e($registerFilter === 'all' ? 'active' : ''); ?>">All records</a>
</div>
<?php endif; ?>

<div class="mat-toolbar mb-3">
    <div class="d-flex flex-wrap align-items-end gap-3">
        <form method="GET" action="<?php echo e(route('compliance.complaints.index')); ?>" class="d-flex flex-column gap-1 flex-grow-1" style="min-width:240px;max-width:420px">
            <?php $__currentLoopData = request()->except(['search', 'page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($v !== null && $v !== ''): ?> <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>"> <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <label class="form-label mb-0">Search</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="search" name="search" class="form-control border-start-0" placeholder="Ref, complainant, policy, email…" value="<?php echo e(request('search')); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
        <form method="GET" action="<?php echo e(route('compliance.complaints.index')); ?>" class="d-flex flex-column gap-1">
            <?php $__currentLoopData = request()->except(['status', 'page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($v !== null && $v !== ''): ?> <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>"> <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <label class="form-label mb-0">Status</label>
            <select name="status" class="form-select form-select-sm" style="min-width:10rem" onchange="this.form.submit()">
                <option value="">All statuses</option>
                <?php $__currentLoopData = \App\Models\Complaint::STATUSES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($val); ?>" <?php echo e(request('status') === $val ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </form>
        <form method="GET" action="<?php echo e(route('compliance.complaints.index')); ?>" class="d-flex flex-column gap-1">
            <?php $__currentLoopData = request()->except(['nature', 'page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($v !== null && $v !== ''): ?> <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>"> <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <label class="form-label mb-0">Nature</label>
            <select name="nature" class="form-select form-select-sm" style="min-width:11rem" onchange="this.form.submit()">
                <option value="">All natures</option>
                <?php $__currentLoopData = \App\Models\Complaint::NATURES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($val); ?>" <?php echo e(request('nature') === $val ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </form>
    </div>
    <?php if(request('search') || request('status') || request('nature')): ?>
    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-3 border-top">
        <span class="small text-muted fw-semibold">Active filters:</span>
        <?php if(request('search')): ?>
            <a href="<?php echo e(route('compliance.complaints.index', request()->except(['search', 'page']))); ?>" class="mat-filter-chip">Search: <?php echo e(\Illuminate\Support\Str::limit(request('search'), 20)); ?> <i class="bi bi-x"></i></a>
        <?php endif; ?>
        <?php if(request('status')): ?>
            <a href="<?php echo e(route('compliance.complaints.index', request()->except(['status', 'page']))); ?>" class="mat-filter-chip"><?php echo e(request('status')); ?> <i class="bi bi-x"></i></a>
        <?php endif; ?>
        <?php if(request('nature')): ?>
            <a href="<?php echo e(route('compliance.complaints.index', request()->except(['nature', 'page']))); ?>" class="mat-filter-chip"><?php echo e(\Illuminate\Support\Str::limit(request('nature'), 20)); ?> <i class="bi bi-x"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="app-card cmp-table-card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Received</th>
                    <th>Complainant</th>
                    <th>Summary</th>
                    <th>Nature</th>
                    <th>Status</th>
                    <?php if($hasRegisterColumn ?? false): ?><th>Type</th><?php endif; ?>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $complaints; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $summary = $classifier->summary($c->description);
                        $registerStatus = $c->register_status ?? 'active';
                        $typeClass = match ($registerStatus) {
                            'review' => 'cmp-type-review',
                            'excluded' => 'cmp-type-excluded',
                            default => 'cmp-type-active',
                        };
                        $typeLabel = \App\Models\Complaint::REGISTER_STATUSES[$registerStatus] ?? 'Complaint';
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo e(route('compliance.complaints.show', $c)); ?>" class="cmp-ref-link"><?php echo e($c->complaint_ref); ?></a>
                            <?php if($c->policy_number): ?>
                                <div class="small text-muted font-monospace"><?php echo e($c->policy_number); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap small text-muted"><?php echo e($c->date_received?->format('d M Y') ?? '—'); ?></td>
                        <td>
                            <div class="fw-medium"><?php echo e($c->complainant_name); ?></div>
                            <?php if($c->complainant_email): ?><div class="small text-muted"><?php echo e($c->complainant_email); ?></div><?php endif; ?>
                        </td>
                        <td><div class="cmp-summary" title="<?php echo e($summary); ?>"><?php echo e($summary ?: '—'); ?></div></td>
                        <td><span class="small text-muted"><?php echo e($c->nature ?: '—'); ?></span></td>
                        <td>
                            <span class="cmp-status-badge cmp-status-<?php echo e(Str::slug($c->status ?? '')); ?>"><?php echo e($c->status ?? '—'); ?></span>
                        </td>
                        <?php if($hasRegisterColumn ?? false): ?>
                        <td>
                            <span class="cmp-type-badge <?php echo e($typeClass); ?>"><?php echo e($typeLabel); ?></span>
                            <?php if($c->classification_score): ?>
                                <div class="small text-muted"><?php echo e($c->classification_score); ?>%</div>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td class="text-end text-nowrap">
                            <a href="<?php echo e(route('compliance.complaints.show', $c)); ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                            <?php if($hasRegisterColumn ?? false): ?>
                                <?php if($registerStatus !== 'active'): ?>
                                    <form method="POST" action="<?php echo e(route('compliance.complaints.register-status', $c)); ?>" class="d-inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="register_status" value="active">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Confirm as complaint"><i class="bi bi-check2"></i></button>
                                    </form>
                                <?php endif; ?>
                                <?php if($registerStatus !== 'excluded'): ?>
                                    <form method="POST" action="<?php echo e(route('compliance.complaints.register-status', $c)); ?>" class="d-inline" onsubmit="return confirm('Remove this from the complaint register?')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="register_status" value="excluded">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Not a complaint"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="<?php echo e(($hasRegisterColumn ?? false) ? 8 : 7); ?>" class="p-0 border-0">
                            <div class="mat-empty-state">
                                <div class="mat-empty-icon"><i class="bi bi-clipboard2-x"></i></div>
                                <h6 class="fw-semibold mb-1">
                                    <?php if($registerFilter === 'excluded'): ?>
                                        No excluded items
                                    <?php elseif($registerFilter === 'review'): ?>
                                        Nothing needs review
                                    <?php else: ?>
                                        No complaints in the register
                                    <?php endif; ?>
                                </h6>
                                <p class="text-muted small mb-3">
                                    <?php if(request('search')): ?>
                                        No matches for your search. General inquiries from email are filtered out automatically.
                                    <?php else: ?>
                                        Register a complaint manually or wait for classified inbound email.
                                    <?php endif; ?>
                                </p>
                                <a href="<?php echo e(route('compliance.complaints.create')); ?>" class="btn btn-sm btn-primary">Register Complaint</a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($complaints->hasPages() || $complaints->total() > 0): ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-3 border-top bg-light">
            <span class="small text-muted">
                Showing <?php echo e($complaints->firstItem() ?? 0); ?>–<?php echo e($complaints->lastItem() ?? 0); ?> of <?php echo e(number_format($complaints->total())); ?>

            </span>
            <?php if($complaints->hasPages()): ?>
                <?php echo e($complaints->links('pagination::bootstrap-5')); ?>

            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<details class="mt-3 mb-0">
    <summary class="small text-muted" style="cursor:pointer"><i class="bi bi-info-circle me-1"></i>How complaint detection works</summary>
    <p class="text-muted small mt-2 mb-0">
        Inbound client emails are scored for complaint language (dissatisfaction, dispute, escalation, etc.).
        Auto-replies, ticket confirmations, marketing, and general inquiries are excluded.
        Uncertain items appear under <strong>Needs review</strong> for a compliance officer to confirm or dismiss.
        Manual registrations are always treated as complaints.
    </p>
</details>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/compliance/complaints.blade.php ENDPATH**/ ?>