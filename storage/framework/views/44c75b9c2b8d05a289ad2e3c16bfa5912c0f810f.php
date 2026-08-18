<?php $__env->startSection('title', 'Mail Manager'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="page-title">Mail Manager</h1>
            <p class="page-subtitle mb-0">
                <?php if($useMicrosoftGraph ?? false): ?>
                    Emails from <?php echo e(config('microsoft-graph.mailbox')); ?> (Microsoft Graph)
                <?php elseif($useEmailService ?? false): ?>
                    Emails from <?php echo e(config('email-service.sender')); ?> (HTTP)
                <?php else: ?>
                    Emails from <?php echo e(config('email-service.sender', 'info@agilecraft.co.ke')); ?> (IMAP)
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('tools.mail-manager.create')); ?>" class="btn btn-outline-primary">
                <i class="bi bi-plus-lg me-1"></i> Create Email
            </a>
            <form action="<?php echo e(route('tools.mail-manager.fetch')); ?>" method="POST" class="d-inline">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-download me-1"></i> Fetch Emails
                </button>
            </form>
        </div>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center py-2" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center py-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e(session('error')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
    $mailHealth = $mailFetchHealth ?? [];
    $lastSuccess = !empty($mailHealth['last_success_at']) ? \Carbon\Carbon::parse($mailHealth['last_success_at']) : null;
    $lastAttempt = !empty($mailHealth['last_attempt_at']) ? \Carbon\Carbon::parse($mailHealth['last_attempt_at']) : null;
    $healthIsOk = ($mailHealth['status'] ?? 'unknown') === 'ok';
    $healthIsStale = (bool) ($mailHealth['is_stale'] ?? true);
    $staleMinutes = (int) ($mailHealth['stale_minutes'] ?? 15);
?>

<div class="alert <?php echo e(($healthIsOk && !$healthIsStale) ? 'alert-info' : 'alert-warning'); ?> py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="small">
        <strong>Mail Fetch Health:</strong>
        <?php if($healthIsStale): ?>
            <span class="fw-semibold text-warning-emphasis">Stale</span> (no successful fetch in <?php echo e($staleMinutes); ?>+ min).
        <?php else: ?>
            <span class="fw-semibold text-success">OK</span>
        <?php endif; ?>
        <?php if($lastSuccess): ?>
            Last success <?php echo e($lastSuccess->diffForHumans()); ?> (<?php echo e($lastSuccess->format('M d, Y H:i')); ?>)
            · fetched <?php echo e((int) ($mailHealth['fetched'] ?? 0)); ?>, stored <?php echo e((int) ($mailHealth['stored'] ?? 0)); ?>

        <?php else: ?>
            No successful fetch recorded yet.
        <?php endif; ?>
        <?php if($lastAttempt): ?>
            · last attempt <?php echo e($lastAttempt->diffForHumans()); ?>

        <?php endif; ?>
        <?php if(!empty($mailHealth['error'])): ?>
            · error: <?php echo e(\Illuminate\Support\Str::limit($mailHealth['error'], 180)); ?>

        <?php endif; ?>
    </div>
    <form action="<?php echo e(route('tools.mail-manager.fetch')); ?>" method="POST" class="m-0">
        <?php echo csrf_field(); ?>
        <button type="submit" class="btn btn-sm <?php echo e(($healthIsOk && !$healthIsStale) ? 'btn-outline-primary' : 'btn-warning'); ?>">
            <i class="bi bi-arrow-repeat me-1"></i>Fetch now
        </button>
    </form>
</div>


<div class="mail-manager-panels d-flex border rounded overflow-hidden bg-white shadow-sm">
    
    <div class="mail-list-panel flex-shrink-0 d-flex flex-column border-end">
        <div class="p-2 border-bottom bg-light flex-shrink-0">
            <form method="GET" action="<?php echo e(route('tools.mail-manager')); ?>" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="hidden" name="selected" value="<?php echo e($selected ?? ''); ?>">
                <div class="input-group input-group-sm flex-grow-1" style="min-width: 140px;">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo e($search ?? ''); ?>">
                </div>
                <select name="per_page" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="50" <?php echo e(($perPageParam ?? 50) == 50 ? 'selected' : ''); ?>>50</option>
                    <option value="100" <?php echo e(($perPageParam ?? '') == 100 ? 'selected' : ''); ?>>100</option>
                    <option value="250" <?php echo e(($perPageParam ?? '') == 250 ? 'selected' : ''); ?>>250</option>
                    <option value="all" <?php echo e(($perPageParam ?? '') === 'all' ? 'selected' : ''); ?>>All</option>
                </select>
                <button type="submit" class="btn btn-outline-primary btn-sm">Search</button>
                <?php if($search ?? null): ?>
                    <a href="<?php echo e(route('tools.mail-manager', ['selected' => $selected ?? '', 'per_page' => $perPageParam ?? '50'])); ?>" class="btn btn-outline-secondary btn-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="mail-list-scroll flex-grow-1 overflow-y-auto">
            <?php $__empty_1 = true; $__currentLoopData = $emails ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $email): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a href="<?php echo e(route('tools.mail-manager', ['selected' => $email->id, 'search' => $search ?? '', 'page' => $page ?? 1, 'per_page' => $perPageParam ?? '50'])); ?>"
               class="list-group-item list-group-item-action py-2 px-3 text-decoration-none border-0 border-bottom rounded-0 <?php echo e(($selected ?? null) == $email->id ? 'active' : ''); ?>">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <span class="fw-semibold small text-truncate"><?php echo e($email->from_name ?: $email->from_address); ?></span>
                    <small class="text-nowrap flex-shrink-0 <?php echo e(($selected ?? null) == $email->id ? 'text-white-50' : 'text-muted'); ?>"><?php echo e($email->date ? \Carbon\Carbon::parse($email->date)->format('M d') : ''); ?></small>
                </div>
                <div class="small mt-0 mb-1 <?php echo e(($selected ?? null) == $email->id ? 'text-white' : 'text-dark'); ?>">
                    <?php echo e(Str::limit($email->subject ?? '(No subject)', 55)); ?>

                    <?php if($email->has_attachments): ?>
                        <i class="bi bi-paperclip ms-1 opacity-75"></i>
                    <?php endif; ?>
                    <?php if($email->ticket_id ?? null): ?>
                        <i class="bi bi-ticket-perforated-fill ms-1 opacity-75" title="Linked to ticket"></i>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="list-group-item text-center py-5 text-muted small border-0">
                <?php if($search ?? null): ?>
                    No emails match your search.
                <?php else: ?>
                    No emails yet. Click "Fetch Emails" to pull emails<?php echo e(($useMicrosoftGraph ?? false) ? ' via Microsoft Graph' : (($useEmailService ?? false) ? ' via HTTP' : ' from ' . (config('email-service.sender') ?: 'life@geminialife.co.ke'))); ?>.
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if(($total ?? 0) > 0): ?>
        <div class="p-2 border-top bg-light flex-shrink-0 d-flex justify-content-between align-items-center">
            <span class="text-muted small"><?php echo e(number_format($total)); ?> emails</span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?php echo e(($page ?? 1) <= 1 ? 'disabled' : ''); ?>">
                        <a class="page-link" href="<?php echo e(route('tools.mail-manager', ['page' => ($page ?? 1) - 1, 'search' => $search ?? '', 'selected' => $selected ?? '', 'per_page' => $perPageParam ?? '50'])); ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    <li class="page-item <?php echo e(($page ?? 1) * ($perPage ?? 50) >= ($total ?? 0) ? 'disabled' : ''); ?>">
                        <a class="page-link" href="<?php echo e(route('tools.mail-manager', ['page' => ($page ?? 1) + 1, 'search' => $search ?? '', 'selected' => $selected ?? '', 'per_page' => $perPageParam ?? '50'])); ?>"><i class="bi bi-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    
    <div class="mail-preview-panel flex-grow-1 d-flex flex-column min-w-0">
        <?php if($selectedEmail ?? null): ?>
        <div class="mail-preview-scroll flex-grow-1 overflow-y-auto">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <div class="min-w-0">
                        <h5 class="mb-1"><?php echo e($selectedEmail->subject ?? '(No subject)'); ?></h5>
                        <p class="text-muted small mb-0">
                            <?php echo e($selectedEmail->from_name ? $selectedEmail->from_name . ' <' . $selectedEmail->from_address . '>' : $selectedEmail->from_address); ?>

                            · <?php echo e($selectedEmail->date ? \Carbon\Carbon::parse($selectedEmail->date)->format('M d, Y H:i') : ''); ?>

                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <?php if($selectedEmail->ticket_id ?? null): ?>
                            <a href="<?php echo e(route('tickets.show', $selectedEmail->ticket_id)); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bi bi-ticket-perforated me-1"></i> View Ticket
                            </a>
                        <?php else: ?>
                            <a href="<?php echo e(route('tools.mail-manager.create-ticket', $selectedEmail->id)); ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Create Ticket
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if($selectedEmail->to_addresses): ?>
                    <p class="mb-1 small"><strong>To:</strong> <?php echo e($selectedEmail->to_addresses); ?></p>
                <?php endif; ?>
                <?php if($selectedEmail->cc_addresses): ?>
                    <p class="mb-2 small"><strong>Cc:</strong> <?php echo e($selectedEmail->cc_addresses); ?></p>
                <?php endif; ?>
                <?php if($selectedEmail->has_attachments): ?>
                    <p class="mb-2 text-muted small"><i class="bi bi-paperclip"></i> Has attachments</p>
                <?php endif; ?>
                <hr>
                <div class="email-body" style="max-width: 720px;">
                    <?php if($selectedEmail->body_html): ?>
                        <?php echo $selectedEmail->body_html; ?>

                    <?php else: ?>
                        <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;"><?php echo e($selectedEmail->body_text ?? 'No content'); ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="mail-preview-empty flex-grow-1 d-flex align-items-center justify-content-center text-muted">
            <div class="text-center py-5">
                <i class="bi bi-envelope-open display-4 mb-3 opacity-25"></i>
                <p class="mb-0">Select an email to read</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.mail-manager-panels {
    height: calc(100vh - 220px);
    min-height: 400px;
}
.mail-list-panel {
    width: 360px;
    max-width: 40%;
}
.mail-list-scroll {
    min-height: 0;
}
.mail-preview-panel {
    min-height: 0;
}
.mail-preview-scroll {
    min-height: 0;
}
.mail-preview-empty {
    min-height: 0;
}
.email-body img {
    max-width: 100%;
    height: auto;
}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/tools/mail-manager.blade.php ENDPATH**/ ?>