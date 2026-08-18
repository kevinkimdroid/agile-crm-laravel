<?php $__env->startSection('title', 'Deals'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $stageSummary = $stageSummary ?? [];
    $currentStage = $currentStage ?? '';
    $search = $search ?? '';
    $closingSoon = $closingSoon ?? collect();
    $closedStages = ['Closed Won', 'Closed Lost', 'Dead', 'Closed'];
    $stageOrder = ['Prospecting', 'Qualification', 'Proposal', 'Negotiation', 'Closed Won', 'Closed Lost'];
    foreach (array_keys($stageSummary) as $name) {
        if (! in_array($name, $stageOrder, true)) {
            $stageOrder[] = $name;
        }
    }
    $allCount = collect($stageSummary)->sum('count');
    $today = now()->startOfDay();
?>

<div class="deals-page">
    <div class="deals-hero mb-4">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <div class="deals-hero-icon mb-3"><i class="bi bi-briefcase-fill"></i></div>
                <h1 class="deals-hero-title mb-1">Deals</h1>
                <p class="deals-hero-desc mb-0">Track opportunities through the pipeline — from prospecting to closed won.</p>
            </div>
            <a href="<?php echo e(route('deals.create')); ?>" class="btn btn-light fw-semibold">
                <i class="bi bi-plus-lg me-1"></i> New Deal
            </a>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e(session('error')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(session('info')): ?>
        <div class="alert alert-info alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i><?php echo e(session('info')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="deals-kpi deals-kpi-featured">
                <span class="deals-kpi-label">Open pipeline</span>
                <span class="deals-kpi-value">KES <?php echo e(number_format($pipelineValue ?? 0, 0)); ?></span>
                <span class="deals-kpi-meta"><?php echo e(number_format($openCount ?? 0)); ?> open <?php echo e(($openCount ?? 0) === 1 ? 'deal' : 'deals'); ?></span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="deals-kpi">
                <span class="deals-kpi-label">All deals</span>
                <span class="deals-kpi-value"><?php echo e(number_format($allCount)); ?></span>
                <span class="deals-kpi-meta">In your pipeline</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="deals-kpi">
                <span class="deals-kpi-label">Closing in 30 days</span>
                <span class="deals-kpi-value"><?php echo e(number_format($closingSoon->count())); ?></span>
                <span class="deals-kpi-meta">Need follow-up</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="deals-kpi">
                <span class="deals-kpi-label">Closed won</span>
                <span class="deals-kpi-value">KES <?php echo e(number_format($wonValue ?? 0, 0)); ?></span>
                <span class="deals-kpi-meta"><?php echo e(number_format($wonCount ?? 0)); ?> won</span>
            </div>
        </div>
    </div>

    <div class="deals-pills mb-4">
        <a href="<?php echo e(route('deals.index', array_filter(['q' => $search ?: null]))); ?>" class="deals-pill <?php echo e($currentStage === '' ? 'active' : ''); ?>">
            All <span class="deals-pill-count"><?php echo e(number_format($allCount)); ?></span>
        </a>
        <?php $__currentLoopData = $stageOrder; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $row = $stageSummary[$name] ?? ['count' => 0, 'amount' => 0]; ?>
            <?php if($row['count'] > 0 || in_array($name, ['Prospecting', 'Qualification', 'Proposal', 'Negotiation', 'Closed Won', 'Closed Lost'], true)): ?>
            <a href="<?php echo e(route('deals.index', array_filter(['stage' => $name, 'q' => $search ?: null]))); ?>"
               class="deals-pill deals-pill-<?php echo e(Str::slug($name)); ?> <?php echo e($currentStage === $name ? 'active' : ''); ?>">
                <?php echo e($name); ?> <span class="deals-pill-count"><?php echo e(number_format($row['count'])); ?></span>
            </a>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="app-card deals-table-card overflow-hidden">
        <form method="GET" action="<?php echo e(route('deals.index')); ?>" class="deals-toolbar">
            <?php if($currentStage !== ''): ?>
                <input type="hidden" name="stage" value="<?php echo e($currentStage); ?>">
            <?php endif; ?>
            <div class="deals-search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" class="form-control border-0 bg-transparent" placeholder="Search deal name, number, or stage…" value="<?php echo e($search); ?>" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-sm deals-search-btn">Search</button>
            <?php if($search !== '' || $currentStage !== ''): ?>
                <a href="<?php echo e(route('deals.index')); ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
            <?php endif; ?>
            <a href="<?php echo e(route('deals.create')); ?>" class="btn btn-sm app-topbar-add ms-auto">Add Deal</a>
        </form>

        <div class="table-responsive">
            <table class="table table-hover deals-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Deal</th>
                        <th class="text-end">Amount</th>
                        <th>Stage</th>
                        <th>Closing</th>
                        <th class="text-end" width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $deals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deal): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $name = $deal->potentialname ?? 'Untitled';
                            $stageName = trim((string) ($deal->sales_stage ?? '')) ?: 'Unknown';
                            $close = $deal->closingdate ? \Carbon\Carbon::parse($deal->closingdate)->startOfDay() : null;
                            $isClosed = in_array($stageName, $closedStages, true);
                            $isOverdue = $close && ! $isClosed && $close->lt($today);
                            $isSoon = $close && ! $isClosed && ! $isOverdue && $close->lte($today->copy()->addDays(14));
                            $initials = strtoupper(substr(preg_replace('/\s+/', '', $name), 0, 2));
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo e(route('deals.show', $deal->potentialid)); ?>" class="deals-name-link">
                                    <span class="deals-avatar"><?php echo e($initials); ?></span>
                                    <span>
                                        <span class="fw-semibold d-block"><?php echo e($name); ?></span>
                                        <?php if(!empty($deal->potential_no)): ?>
                                            <span class="deals-meta"><?php echo e($deal->potential_no); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </td>
                            <td class="text-end deals-amount">KES <?php echo e(number_format($deal->amount ?? 0, 0)); ?></td>
                            <td>
                                <span class="deals-stage deals-stage-<?php echo e(Str::slug($stageName)); ?>"><?php echo e($stageName); ?></span>
                            </td>
                            <td>
                                <?php if($close): ?>
                                    <span class="<?php echo e($isOverdue ? 'deals-date-overdue' : ($isSoon ? 'deals-date-soon' : 'text-muted')); ?>">
                                        <?php echo e($close->format('d M Y')); ?>

                                        <?php if($isOverdue): ?>
                                            <span class="d-block small">Overdue</span>
                                        <?php elseif($isSoon): ?>
                                            <span class="d-block small">Due soon</span>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo e(route('deals.show', $deal->potentialid)); ?>" class="btn btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="<?php echo e(route('deals.edit', $deal->potentialid)); ?>" class="btn btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="deals-empty">
                                    <div class="deals-empty-icon"><i class="bi bi-briefcase"></i></div>
                                    <h6 class="mt-3 mb-2"><?php echo e($search !== '' || $currentStage !== '' ? 'No matching deals' : 'No deals yet'); ?></h6>
                                    <p class="text-muted mb-3">
                                        <?php if($search !== '' || $currentStage !== ''): ?>
                                            Try a different search or clear the stage filter.
                                        <?php else: ?>
                                            Create an opportunity to start tracking your pipeline.
                                        <?php endif; ?>
                                    </p>
                                    <?php if($search !== '' || $currentStage !== ''): ?>
                                        <a href="<?php echo e(route('deals.index')); ?>" class="btn btn-outline-secondary">Clear filters</a>
                                    <?php else: ?>
                                        <a href="<?php echo e(route('deals.create')); ?>" class="btn btn-primary-custom"><i class="bi bi-plus-lg me-1"></i>New Deal</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($deals->hasPages()): ?>
            <div class="card-footer bg-transparent border-top py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 px-4">
                <span class="text-muted small">Showing <?php echo e($deals->firstItem() ?? 0); ?>–<?php echo e($deals->lastItem() ?? 0); ?> of <?php echo e(number_format($deals->total())); ?></span>
                <?php echo e($deals->withQueryString()->links('pagination::bootstrap-5')); ?>

            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.deals-hero {
    background: linear-gradient(135deg, var(--agile-primary-dark, #122952) 0%, var(--agile-primary, #1B3F7A) 60%, #2563eb 100%);
    border-radius: 16px;
    color: #fff;
    padding: 1.5rem 1.75rem;
}
.deals-hero-icon {
    width: 2.75rem; height: 2.75rem; border-radius: 12px;
    background: rgba(255,255,255,0.15);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
}
.deals-hero-title { font-size: 1.6rem; font-weight: 700; letter-spacing: -0.02em; }
.deals-hero-desc { color: rgba(255,255,255,0.78); max-width: 36rem; }
.deals-kpi {
    background: #fff;
    border: 1px solid var(--agile-border, #e2e8f0);
    border-radius: 14px;
    padding: 1rem 1.15rem;
    height: 100%;
}
.deals-kpi-featured {
    background: var(--agile-primary-muted, #eef3fb);
    border-color: transparent;
}
.deals-kpi-label { display: block; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--agile-text-muted, #64748b); }
.deals-kpi-value { display: block; font-size: 1.35rem; font-weight: 700; color: var(--agile-text, #0f172a); margin-top: 0.2rem; }
.deals-kpi-featured .deals-kpi-value { color: var(--agile-primary, #1B3F7A); }
.deals-kpi-meta { font-size: 0.78rem; color: var(--agile-text-muted, #64748b); }
.deals-pills { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.deals-pill {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.4rem 0.85rem; border-radius: 999px;
    border: 1px solid var(--agile-border, #e2e8f0);
    background: #fff; color: var(--agile-text, #0f172a);
    text-decoration: none; font-size: 0.82rem; font-weight: 600;
}
.deals-pill:hover { border-color: var(--agile-primary, #1B3F7A); color: var(--agile-primary, #1B3F7A); }
.deals-pill.active { background: var(--agile-primary, #1B3F7A); border-color: var(--agile-primary, #1B3F7A); color: #fff; }
.deals-pill-count { font-size: 0.75rem; opacity: 0.8; }
.deals-pill-closed-won.active { background: #059669; border-color: #059669; }
.deals-pill-closed-lost.active { background: #dc2626; border-color: #dc2626; }
.deals-pill-negotiation.active { background: #d97706; border-color: #d97706; }
.deals-toolbar {
    display: flex; flex-wrap: wrap; align-items: center; gap: 0.65rem;
    padding: 0.9rem 1.15rem; border-bottom: 1px solid var(--agile-border, #e2e8f0);
}
.deals-search {
    position: relative; flex: 1; min-width: 220px;
}
.deals-search i { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.deals-search input { padding-left: 2.1rem; }
.deals-search-btn { background: var(--agile-primary, #1B3F7A); color: #fff; border-radius: 8px; padding: 0.4rem 1rem; }
.deals-search-btn:hover { color: #fff; background: var(--agile-primary-dark, #122952); }
.deals-table thead th {
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
    color: var(--agile-text-muted, #64748b); background: var(--agile-primary-muted, #eef3fb);
    padding: 0.85rem 1.15rem; border-bottom: 0;
}
.deals-table td { padding: 0.95rem 1.15rem; }
.deals-table tbody tr:hover { background: var(--agile-primary-muted, #eef3fb); }
.deals-name-link { display: flex; align-items: center; gap: 0.7rem; text-decoration: none; color: inherit; }
.deals-name-link:hover { color: var(--agile-primary, #1B3F7A); }
.deals-avatar {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    background: var(--agile-primary, #1B3F7A); color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 0.72rem; font-weight: 700;
}
.deals-meta { font-size: 0.75rem; color: #94a3b8; font-family: ui-monospace, monospace; }
.deals-amount { font-variant-numeric: tabular-nums; font-weight: 600; white-space: nowrap; }
.deals-stage {
    display: inline-block; font-size: 0.72rem; font-weight: 600;
    padding: 0.28rem 0.7rem; border-radius: 999px;
    background: var(--agile-primary-muted, #eef3fb); color: var(--agile-primary, #1B3F7A);
}
.deals-stage-closed-won { background: rgba(5, 150, 105, 0.14); color: #059669; }
.deals-stage-closed { background: rgba(5, 150, 105, 0.14); color: #059669; }
.deals-stage-closed-lost, .deals-stage-dead { background: rgba(220, 38, 38, 0.12); color: #dc2626; }
.deals-stage-negotiation { background: rgba(217, 119, 6, 0.16); color: #d97706; }
.deals-stage-proposal { background: rgba(14, 165, 233, 0.16); color: #0284c7; }
.deals-stage-qualification { background: rgba(99, 102, 241, 0.14); color: #4f46e5; }
.deals-date-overdue { color: #dc2626; font-weight: 600; }
.deals-date-soon { color: #d97706; font-weight: 600; }
.deals-empty-icon {
    width: 72px; height: 72px; margin: 0 auto; border-radius: 18px;
    background: var(--agile-primary-muted, #eef3fb); color: var(--agile-primary, #1B3F7A);
    display: flex; align-items: center; justify-content: center; font-size: 1.75rem;
}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/deals/index.blade.php ENDPATH**/ ?>