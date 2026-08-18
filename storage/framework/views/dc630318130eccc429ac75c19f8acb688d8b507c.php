<?php $__env->startSection('title', $client->fullName().' — Client'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $clientName = $client->fullName();
    $clientPhone = $client->phone ?: null;
    $clientEmail = ($client->email && filter_var(trim((string) $client->email), FILTER_VALIDATE_EMAIL)) ? trim((string) $client->email) : null;
    $clientPolicy = $client->policy_no;
    $clientProduct = $client->product ?: '—';
    $lifeSystem = $client->system ?: 'individual';
    $lifeSystemLabel = \App\Models\Client::SYSTEMS[$lifeSystem] ?? $lifeSystem;
    $mpesaConfigured = $mpesaConfigured ?? app(\App\Services\MpesaStkPushService::class)->isConfigured();
    $mpesaSandboxSimulate = $mpesaSandboxSimulate ?? app(\App\Services\MpesaStkPushService::class)->isSandboxSimulate();
    $mpesaTransactions = $mpesaTransactions ?? collect();
    $tab = $activeTab ?? 'summary';
    $clientShowBase = $clientShowBase ?? array_filter([
        'policy' => $clientPolicy,
        'system' => $client->system ?: null,
        'from' => ($fromServeClient ?? false) ? 'serve-client' : null,
    ]);
    $clientTabUrl = function (string $tabName) use ($clientShowBase) {
        $params = $clientShowBase;
        if ($tabName !== 'summary') {
            $params['tab'] = $tabName;
        }
        return route('support.clients.show', $params);
    };
    $initials = strtoupper(
        substr((string) ($client->first_name ?: $clientName), 0, 1)
        .substr((string) ($client->last_name ?: preg_replace('/^.*\s/', '', trim($clientName))), 0, 1)
    );
    $suggestedAmount = client_suggested_premium_amount($client);
    $emailClientRouteParams = array_filter([
        'policy' => $clientPolicy,
        'email' => $clientEmail,
        'client_name' => $clientName,
        'contact_id' => ($contact ?? null)?->contactid,
    ]);
?>

<?php echo $__env->make('support.partials.client-mpesa-styles', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php if(session('success')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-1"></i><?php echo e(session('success')); ?>

    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if(user_is_limited_to_assigned_clients()): ?>
<div class="alert mb-3" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;" role="status">
    <i class="bi bi-shield-check me-1"></i>
    <strong>Assigned to you.</strong> Only you can view this client while assigned-only access is enabled.
</div>
<?php endif; ?>

<div class="contact-detail-header client-profile-hero card contact-detail-card mb-4">
    <div class="card-body p-4">
        <nav class="mb-3 text-uppercase small client-profile-breadcrumb">
            <?php if($fromServeClient ?? false): ?>
            <a href="<?php echo e(route('support.serve-client', ['search' => $clientPolicy])); ?>" class="text-muted">Serve Client</a>
            <?php else: ?>
            <a href="<?php echo e(route('support.customers')); ?>" class="text-muted">Clients</a>
            <span class="text-muted mx-1">/</span>
            <a href="<?php echo e(route('support.customers')); ?>" class="text-muted">All</a>
            <?php endif; ?>
            <span class="text-muted mx-1">/</span>
            <span class="text-dark"><?php echo e(Str::limit($clientName, 40)); ?></span>
        </nav>
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-start gap-3 flex-grow-1">
                <div class="contact-avatar-lg"><?php echo e($initials); ?></div>
                <div class="flex-grow-1" style="min-width:220px">
                    <h1 class="page-title mb-2 client-hero-name"><?php echo e($clientName); ?></h1>
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                        <?php if($clientPhone): ?>
                        <a href="tel:<?php echo e(tel_href($clientPhone)); ?>" class="client-hero-phone text-decoration-none">
                            <i class="bi bi-telephone-fill me-1"></i><?php echo e($clientPhone); ?>

                        </a>
                        <?php endif; ?>
                        <span class="clients-system-badge clients-system-<?php echo e($lifeSystem); ?>"><?php echo e($lifeSystemLabel); ?></span>
                        <span class="client-hero-policy font-monospace"><?php echo e($clientPolicy); ?></span>
                        <span class="badge <?php echo e(($client->status === 'A') ? 'bg-success' : 'bg-danger'); ?>"><?php echo e(\App\Models\Client::STATUSES[$client->status] ?? $client->status); ?></span>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center client-hero-actions">
                <?php if($clientPhone): ?>
                <a href="tel:<?php echo e(tel_href($clientPhone)); ?>" class="btn btn-sm btn-success"><i class="bi bi-telephone me-1"></i>Call</a>
                <?php endif; ?>
                <?php if($clientEmail): ?>
                <a href="<?php echo e(route('support.email-client', array_merge($emailClientRouteParams, ['return_policy' => $clientPolicy]))); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-envelope me-1"></i>Email</a>
                <?php endif; ?>
                <?php if($clientPhone): ?>
                <a href="<?php echo e(route('support.sms-notifier', array_filter(['phone' => $clientPhone, 'return_policy' => $clientPolicy, 'contact_id' => ($contact ?? null)?->contactid]))); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat-dots me-1"></i>SMS</a>
                <?php endif; ?>
                <a href="<?php echo e(route('support.clients.create-ticket', ['policy' => $clientPolicy])); ?>" class="btn btn-sm btn-success"><i class="bi bi-ticket-perforated me-1"></i>Create Ticket</a>
                <?php echo $__env->make('support.partials.client-mpesa-trigger-button', [
                    'mpesaTriggerClass' => 'btn btn-sm btn-outline-success',
                    'mpesaTriggerLabel' => '<i class="bi bi-phone me-1"></i>M-Pesa',
                    'mpesaConfigured' => $mpesaConfigured,
                    'mpesaSandboxSimulate' => $mpesaSandboxSimulate,
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <a href="<?php echo e(($fromServeClient ?? false) ? route('support.serve-client', ['search' => $clientPolicy]) : route('support.customers')); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

<?php echo $__env->make('support.partials.client-page-toasts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="card contact-detail-card client-module-tabs-shell mb-4">
    <div class="card-body py-2 px-2">
        <ul class="nav contact-module-tabs client-module-tabs mb-0">
            <li class="nav-item">
                <a class="nav-link <?php echo e($tab === 'summary' ? 'active' : ''); ?>" href="<?php echo e($clientTabUrl('summary')); ?>">Summary</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e($tab === 'details' ? 'active' : ''); ?>" href="<?php echo e($clientTabUrl('details')); ?>">Details</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e($tab === 'updates' ? 'active' : ''); ?>" href="<?php echo e($clientTabUrl('updates')); ?>">
                    Updates
                    <?php if((($activitiesCount ?? 0) + ($commentsCount ?? 0)) > 0): ?>
                    <span class="badge bg-primary ms-1"><?php echo e(($activitiesCount ?? 0) + ($commentsCount ?? 0)); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(in_array($tab, ['premiums', 'mpesa'], true) ? 'active' : ''); ?>" href="<?php echo e($clientTabUrl('premiums')); ?>" title="M-Pesa & premiums">
                    <i class="bi bi-phone me-1"></i>M-Pesa
                    <?php if(($mpesaCount ?? 0) > 0): ?>
                    <span class="badge bg-success ms-1"><?php echo e($mpesaCount); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item client-module-tabs-divider" aria-hidden="true"></li>
            <li class="nav-item">
                <a class="nav-link <?php echo e($tab === 'tickets' ? 'active' : ''); ?>" href="<?php echo e($clientTabUrl('tickets')); ?>" title="Tickets">
                    <i class="bi bi-ticket-perforated"></i>
                    <?php if(($ticketsCount ?? 0) > 0): ?><span class="badge bg-primary ms-1"><?php echo e($ticketsCount); ?></span><?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e($tab === 'emails' ? 'active' : ''); ?>" href="<?php echo e($clientTabUrl('emails')); ?>" title="Emails">
                    <i class="bi bi-envelope"></i>
                    <?php if(($emailsCount ?? 0) > 0): ?><span class="badge bg-primary ms-1"><?php echo e($emailsCount); ?></span><?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e($tab === 'documents' ? 'active' : ''); ?>" href="<?php echo e($clientTabUrl('documents')); ?>" title="Documents">
                    <i class="bi bi-folder"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e($tab === 'policies' ? 'active' : ''); ?>" href="<?php echo e($clientTabUrl('policies')); ?>" title="Policies">
                    <i class="bi bi-box"></i>
                    <span class="badge bg-primary ms-1">1</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e($tab === 'calls' ? 'active' : ''); ?>" href="<?php echo e($clientTabUrl('calls')); ?>" title="Calls"><i class="bi bi-telephone"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e($tab === 'sms' ? 'active' : ''); ?>" href="<?php echo e($clientTabUrl('sms')); ?>" title="SMS">
                    <i class="bi bi-chat-dots"></i>
                    <?php if(($smsCount ?? 0) > 0): ?><span class="badge bg-primary ms-1"><?php echo e($smsCount); ?></span><?php endif; ?>
                </a>
            </li>
        </ul>
    </div>
</div>

<?php if($tab === 'summary'): ?>
<div class="row g-4">
    <div class="col-lg-5">
        <?php echo $__env->make('partials.profile-summary-key-fields', [
            'keyFields' => [
                ['label' => 'Last Name', 'value' => e($client->last_name ?: '—'), 'class' => 'client-summary-name'],
                ['label' => 'First Name', 'value' => e($client->first_name ?: '—')],
                ['label' => 'Primary Email', 'value' => e($clientEmail ?: '—')],
                ['label' => 'Phone', 'value' => $clientPhone ? '<a href="tel:'.e(tel_href($clientPhone)).'">'.e($clientPhone).'</a>' : '—'],
                ['label' => 'Policy number', 'value' => e($clientPolicy), 'class' => 'font-monospace'],
                ['label' => 'Product', 'value' => e($clientProduct)],
            ],
            'keyFieldsViewDetailsUrl' => $clientTabUrl('details'),
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">Quick actions</h6>
                <div class="d-flex flex-column gap-2">
                    <?php if($clientPhone): ?>
                    <a href="tel:<?php echo e(tel_href($clientPhone)); ?>" class="btn btn-outline-primary"><i class="bi bi-telephone me-2"></i>Call</a>
                    <a href="<?php echo e(route('support.sms-notifier', array_filter(['phone' => $clientPhone, 'return_policy' => $clientPolicy, 'contact_id' => ($contact ?? null)?->contactid]))); ?>" class="btn btn-outline-primary"><i class="bi bi-chat-dots me-2"></i>Send Text</a>
                    <?php endif; ?>
                    <?php if($clientEmail): ?>
                    <a href="<?php echo e(route('support.email-client', array_merge($emailClientRouteParams, ['return_policy' => $clientPolicy]))); ?>" class="btn btn-outline-primary"><i class="bi bi-envelope me-2"></i>Send Email</a>
                    <?php endif; ?>
                    <a href="<?php echo e(route('support.clients.create-ticket', ['policy' => $clientPolicy])); ?>" class="btn btn-outline-success"><i class="bi bi-ticket-perforated me-2"></i>Create Ticket</a>
                    <?php echo $__env->make('support.partials.client-mpesa-trigger-button', [
                        'mpesaTriggerClass' => 'btn btn-outline-success text-start',
                        'mpesaTriggerLabel' => '<i class="bi bi-phone me-2"></i>Collect via M-Pesa',
                        'mpesaConfigured' => $mpesaConfigured,
                        'mpesaSandboxSimulate' => $mpesaSandboxSimulate,
                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <?php if($contact ?? null): ?>
                    <a href="<?php echo e(route('contacts.show', $contact->contactid)); ?>" class="btn btn-outline-secondary"><i class="bi bi-person me-2"></i>View CRM Contact</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <?php echo $__env->make('partials.profile-summary-pending', [
            'pendingContext' => 'client',
            'contact' => $contact ?? null,
            'activities' => $activities ?? collect(),
            'clientComments' => $clientComments ?? collect(),
            'clientPolicy' => $clientPolicy,
            'clientShowBase' => $clientShowBase,
            'policy' => $policy ?? $clientPolicy,
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="card contact-detail-card mb-4 client-mpesa-summary-card mpesa-ui">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h6 class="text-uppercase small fw-bold mb-1" style="color:var(--agile-primary,#0E4385)">M-Pesa premium collection</h6>
                        <p class="text-muted small mb-0">Send an STK push to the client’s phone and track payment status in real time.</p>
                    </div>
                    <?php echo $__env->make('support.partials.client-mpesa-trigger-button', [
                        'mpesaTriggerClass' => 'btn btn-success',
                        'mpesaTriggerLabel' => '<i class="bi bi-phone me-1"></i>Collect via M-Pesa',
                        'mpesaConfigured' => $mpesaConfigured,
                        'mpesaSandboxSimulate' => $mpesaSandboxSimulate,
                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
                <?php if($mpesaTransactions->isNotEmpty()): ?>
                <div class="mpesa-tx-list border-top pt-3 mt-3">
                    <?php $__currentLoopData = $mpesaTransactions->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $st = $tx->status;
                        $iconClass = match ($st) { 'success' => 'success', 'pending' => 'pending', 'cancelled' => 'cancelled', default => 'failed' };
                        $icon = match ($st) { 'success' => 'bi-check-lg', 'pending' => 'bi-hourglass-split', 'cancelled' => 'bi-x-lg', default => 'bi-exclamation-lg' };
                    ?>
                    <div class="mpesa-tx-item">
                        <div class="mpesa-tx-icon <?php echo e($iconClass); ?>"><i class="bi <?php echo e($icon); ?>"></i></div>
                        <div class="mpesa-tx-body">
                            <div class="mpesa-tx-amount">KES <?php echo e(number_format((float) $tx->amount, 0)); ?></div>
                            <div class="mpesa-tx-meta"><?php echo e($tx->created_at?->format('d M Y, H:i') ?? '—'); ?> · <?php echo e(ucfirst((string) $st)); ?></div>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php else: ?>
                <div class="summary-empty-box py-3 text-center text-muted mt-3 border-top">
                    No M-Pesa payment prompts sent yet.
                </div>
                <?php endif; ?>
                <div class="mt-3">
                    <a href="<?php echo e($clientTabUrl('premiums')); ?>" class="btn btn-sm btn-outline-secondary">Open M-Pesa tab</a>
                </div>
            </div>
        </div>

        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">Record</h6>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Created by</dt><dd class="col-7"><?php echo e($client->created_by_name ?: '—'); ?></dd>
                    <dt class="col-5 text-muted">Created</dt><dd class="col-7"><?php echo e($client->created_at?->format('d M Y, H:i')); ?></dd>
                    <dt class="col-5 text-muted">Intermediary</dt><dd class="col-7"><?php echo e($client->intermediary ?: '—'); ?></dd>
                </dl>
                <?php if($client->notes): ?>
                <hr>
                <p class="text-muted small mb-1 fw-semibold">Notes</p>
                <p class="mb-0 small"><?php echo e($client->notes); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php elseif($tab === 'details'): ?>
<div class="card contact-detail-card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary,#0E4385)">Client details</h6>
        <div class="client-summary-personal-grid">
            <div class="client-summary-field">
                <span class="client-summary-label">Full name</span>
                <span class="client-summary-value client-summary-name"><?php echo e($clientName); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Policy number</span>
                <span class="client-summary-value font-monospace"><?php echo e($clientPolicy); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Product</span>
                <span class="client-summary-value"><?php echo e($clientProduct); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">System</span>
                <span class="client-summary-value"><span class="clients-system-badge clients-system-<?php echo e($lifeSystem); ?>"><?php echo e($lifeSystemLabel); ?></span></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Status</span>
                <span class="client-summary-value"><?php echo e(\App\Models\Client::STATUSES[$client->status] ?? $client->status); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Phone</span>
                <span class="client-summary-value"><?php echo e($clientPhone ?: '—'); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Email</span>
                <span class="client-summary-value"><?php echo e($clientEmail ?: '—'); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">ID / Passport</span>
                <span class="client-summary-value font-monospace"><?php echo e($client->id_no ?: '—'); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">KRA PIN</span>
                <span class="client-summary-value font-monospace"><?php echo e($client->kra_pin ?: '—'); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Date of birth</span>
                <span class="client-summary-value"><?php echo e($client->date_of_birth?->format('d M Y') ?: '—'); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Gender</span>
                <span class="client-summary-value"><?php echo e($client->gender ?: '—'); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Occupation</span>
                <span class="client-summary-value"><?php echo e($client->occupation ?: '—'); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Address</span>
                <span class="client-summary-value"><?php echo e($client->address ?: '—'); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">City</span>
                <span class="client-summary-value"><?php echo e($client->city ?: '—'); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Postal code</span>
                <span class="client-summary-value"><?php echo e($client->postal_code ?: '—'); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Intermediary</span>
                <span class="client-summary-value"><?php echo e($client->intermediary ?: '—'); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Created by</span>
                <span class="client-summary-value"><?php echo e($client->created_by_name ?: '—'); ?></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Created</span>
                <span class="client-summary-value"><?php echo e($client->created_at?->format('d M Y, H:i') ?: '—'); ?></span>
            </div>
        </div>
        <?php if($client->notes): ?>
        <div class="mt-4 pt-3 border-top">
            <span class="client-summary-label d-block mb-1">Notes</span>
            <p class="mb-0"><?php echo e($client->notes); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif($tab === 'updates'): ?>
<?php echo $__env->make('support.partials.client-comments', [
    'clientPolicy' => $clientPolicy,
    'clientComments' => $clientComments ?? collect(),
    'clientShowBase' => $clientShowBase,
    'commentReturnTab' => 'updates',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php if($contact ?? null): ?>
<?php echo $__env->make('contacts.partials.activities-related-list', [
    'activitiesPageRoute' => 'support.clients.show',
    'activitiesPageParams' => $clientShowBase,
    'activitiesTab' => 'updates',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php else: ?>
<div class="card contact-detail-card mb-4">
    <div class="card-body p-5 text-center">
        <i class="bi bi-calendar3 display-6 text-muted d-block mb-3"></i>
        <h6 class="mb-2">Tasks &amp; events require a linked CRM prospect</h6>
        <p class="text-muted mb-3">Activities are stored against CRM prospects. No prospect is linked to policy <code><?php echo e($clientPolicy); ?></code> yet.</p>
        <a href="<?php echo e(route('support.clients.create-ticket', ['policy' => $clientPolicy])); ?>" class="btn btn-primary btn-sm">Create ticket for this client</a>
    </div>
</div>
<?php endif; ?>

<?php elseif($tab === 'premiums'): ?>
<div class="card contact-detail-card mb-4 client-mpesa-summary-card mpesa-ui">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h6 class="text-uppercase small fw-bold mb-1" style="color:var(--agile-primary,#0E4385)">M-Pesa premium collection</h6>
                <p class="text-muted small mb-0">Policy <code><?php echo e($clientPolicy); ?></code> — sandbox STK for POC.</p>
            </div>
            <?php echo $__env->make('support.partials.client-mpesa-trigger-button', [
                'mpesaTriggerClass' => 'btn btn-success',
                'mpesaTriggerLabel' => '<i class="bi bi-phone me-1"></i>Collect via M-Pesa',
                'mpesaConfigured' => $mpesaConfigured,
                'mpesaSandboxSimulate' => $mpesaSandboxSimulate,
            ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
        <?php if($mpesaTransactions->isNotEmpty()): ?>
        <div class="mpesa-tx-list border-top pt-3">
            <?php $__currentLoopData = $mpesaTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $st = $tx->status;
                $iconClass = match ($st) { 'success' => 'success', 'pending' => 'pending', 'cancelled' => 'cancelled', default => 'failed' };
                $icon = match ($st) { 'success' => 'bi-check-lg', 'pending' => 'bi-hourglass-split', 'cancelled' => 'bi-x-lg', default => 'bi-exclamation-lg' };
            ?>
            <div class="mpesa-tx-item">
                <div class="mpesa-tx-icon <?php echo e($iconClass); ?>"><i class="bi <?php echo e($icon); ?>"></i></div>
                <div class="mpesa-tx-body">
                    <div class="mpesa-tx-amount">KES <?php echo e(number_format((float) $tx->amount, 0)); ?></div>
                    <div class="mpesa-tx-meta"><?php echo e($tx->created_at?->format('d M Y, H:i') ?? '—'); ?> · <?php echo e(ucfirst((string) $st)); ?> · <?php echo e($tx->phone ?? ''); ?></div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php else: ?>
        <div class="summary-empty-box py-5 text-center text-muted border-top">
            No M-Pesa payment prompts sent yet.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif($tab === 'tickets'): ?>
<div class="card contact-detail-card mb-4">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Tickets</h6>
            <a href="<?php echo e(route('support.clients.create-ticket', ['policy' => $clientPolicy])); ?>" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Create Ticket
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase fw-bold">Ticket</th>
                        <th class="small text-uppercase fw-bold">Title</th>
                        <th class="small text-uppercase fw-bold">Status</th>
                        <th class="small text-uppercase fw-bold">Priority</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $tickets ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="font-monospace">
                            <a href="<?php echo e(route('tickets.show', $ticket->ticketid ?? $ticket->id)); ?>"><?php echo e($ticket->ticket_no ?? $ticket->ticketid ?? '—'); ?></a>
                        </td>
                        <td><?php echo e($ticket->title ?? $ticket->ticket_title ?? '—'); ?></td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary"><?php echo e($ticket->status ?? '—'); ?></span></td>
                        <td><?php echo e($ticket->priority ?? '—'); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">No tickets for this client yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif($tab === 'emails'): ?>
<div class="card contact-detail-card mb-4">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Emails</h6>
            <?php if($clientEmail || ($contact ?? null)): ?>
            <a href="<?php echo e(route('support.email-client', array_merge($emailClientRouteParams, ['return_policy' => $clientPolicy]))); ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-envelope me-1"></i>Compose email
            </a>
            <?php endif; ?>
        </div>
        <div class="p-5 text-center text-muted">
            No emails logged for this client yet.
        </div>
    </div>
</div>

<?php elseif($tab === 'documents'): ?>
<div class="card contact-detail-card mb-4">
    <div class="card-body p-5 text-center text-muted">
        <i class="bi bi-folder opacity-50 d-block mb-2 fs-3"></i>
        No documents uploaded for this client yet.
    </div>
</div>

<?php elseif($tab === 'policies'): ?>
<div class="card contact-detail-card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small fw-bold text-muted mb-3">Policies</h6>
        <div class="list-group list-group-flush">
            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold font-monospace"><?php echo e($clientPolicy); ?></div>
                    <div class="text-muted small"><?php echo e($clientProduct); ?> · <?php echo e($lifeSystemLabel); ?></div>
                </div>
                <span class="badge <?php echo e(($client->status === 'A') ? 'bg-success' : 'bg-danger'); ?>"><?php echo e(\App\Models\Client::STATUSES[$client->status] ?? $client->status); ?></span>
            </div>
        </div>
    </div>
</div>

<?php elseif($tab === 'calls'): ?>
<div class="card contact-detail-card mb-4">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Calls (PBX)</h6>
            <?php if($clientPhone): ?>
            <a href="tel:<?php echo e(tel_href($clientPhone)); ?>" class="btn btn-success btn-sm"><i class="bi bi-telephone-outbound-fill me-1"></i>Call</a>
            <?php endif; ?>
        </div>
        <div class="p-5 text-center text-muted">No PBX calls found for this client.</div>
    </div>
</div>

<?php elseif($tab === 'sms'): ?>
<div class="card contact-detail-card mb-4">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">SMS sent</h6>
            <?php if($clientPhone): ?>
            <a href="<?php echo e(route('support.sms-notifier', array_filter(['phone' => $clientPhone, 'return_policy' => $clientPolicy, 'contact_id' => ($contact ?? null)?->contactid]))); ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-send-fill me-1"></i>Send SMS</a>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase fw-bold">Date</th>
                        <th class="small text-uppercase fw-bold">To</th>
                        <th class="small text-uppercase fw-bold">Message</th>
                        <th class="small text-uppercase fw-bold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $smsLogs ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="text-nowrap"><?php echo e(optional($log->sent_at)->format('d M Y H:i') ?: optional($log->created_at)->format('d M Y H:i') ?: '—'); ?></td>
                        <td class="font-monospace"><?php echo e($log->phone ?? '—'); ?></td>
                        <td><span class="text-muted"><?php echo e(Str::limit($log->message ?? '', 80)); ?></span></td>
                        <td>
                            <?php if(($log->status ?? '') === 'sent'): ?>
                            <span class="badge bg-success bg-opacity-10 text-success">Sent</span>
                            <?php else: ?>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary"><?php echo e(ucfirst((string) ($log->status ?? '—'))); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">No SMS sent to this client yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.client-profile-hero {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 55%, #f0f6fc 100%);
    box-shadow: 0 4px 24px rgba(14, 67, 133, 0.08);
    border: 1px solid #e2e8f0; border-radius: 16px;
}
.client-profile-breadcrumb a { text-decoration: none; }
.contact-avatar-lg {
    width: 84px; height: 84px; border-radius: 20px;
    background: linear-gradient(145deg, #1A468A 0%, #0E4385 100%);
    color: #fff; display: inline-flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1.35rem; flex-shrink: 0;
    box-shadow: 0 8px 20px rgba(14, 67, 133, 0.25);
}
.client-hero-name { color: var(--agile-primary, #0E4385); font-weight: 800; }
.client-hero-phone { font-weight: 600; color: #334155; }
.client-hero-policy { font-size: 0.82rem; color: #64748b; background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 999px; }
.client-hero-actions .btn { border-radius: 8px; font-weight: 600; }
.client-module-tabs-shell { border-radius: 16px; box-shadow: 0 2px 12px rgba(14, 67, 133, 0.05); overflow: hidden; }
.client-module-tabs { display: flex; flex-direction: row; flex-wrap: wrap; align-items: center; gap: 0.15rem; border: none; }
.client-module-tabs .nav-item.client-module-tabs-divider {
    width: 1px; height: 1.75rem; margin: 0 0.35rem; padding: 0;
    background: #e2e8f0; align-self: center; pointer-events: none; flex: 0 0 1px;
}
@media (max-width: 767.98px) { .client-module-tabs .nav-item.client-module-tabs-divider { display: none; } }
.contact-module-tabs { border-bottom: none; }
.contact-module-tabs .nav-link {
    color: #64748b; font-weight: 500; padding: 0.7rem 1rem; border: none; border-radius: 10px; margin-bottom: 0;
}
.contact-module-tabs .nav-link:hover { color: #0E4385; background: rgba(14, 67, 133, 0.06); }
.contact-module-tabs .nav-link.active {
    color: #fff; background: #0E4385; box-shadow: 0 4px 12px rgba(14, 67, 133, 0.22);
}
.contact-module-tabs .nav-link.active .badge { background: rgba(255,255,255,0.25) !important; color: #fff !important; }
.contact-detail-card { border-radius: 16px; border: 1px solid rgba(14, 67, 133, 0.12); box-shadow: 0 2px 12px rgba(14, 67, 133, 0.04); }
.client-summary-personal-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.25rem; }
@media (max-width: 575.98px) { .client-summary-personal-grid { grid-template-columns: 1fr; } }
.client-summary-field { display: flex; flex-direction: column; gap: 0.2rem; }
.client-summary-label { font-size: 0.68rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; }
.client-summary-value { font-size: 0.95rem; font-weight: 600; color: #1e293b; }
.client-summary-name { font-size: 1.1rem; color: var(--agile-primary, #0E4385); }
.client-mpesa-summary-card, .client-summary-payments { border-color: #bbf7d0; background: linear-gradient(180deg, #f8fdf9 0%, #fff 70%); }
.summary-empty-box { border-radius: 10px; background: #f8fafc; }
.clients-system-badge { display: inline-block; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 999px; }
.clients-system-badge.clients-system-group { background: #ccfbf1; color: #0f766e; }
.clients-system-badge.clients-system-individual { background: #e0e7ff; color: #4338ca; }
.clients-system-badge.clients-system-mortgage { background: #ffedd5; color: #9a3412; }
.clients-system-badge.clients-system-group_pension { background: #ede9fe; color: #5b21b6; }
</style>

<?php echo $__env->make('support.partials.client-mpesa-stk-modal', [
    'mpesaPolicyNumber' => $clientPolicy,
    'clientPhone' => $clientPhone,
    'clientName' => $clientName,
    'defaultAmount' => $suggestedAmount,
    'mpesaConfigured' => $mpesaConfigured,
    'mpesaSandboxSimulate' => $mpesaSandboxSimulate,
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var phoneInput = document.getElementById('mpesa_phone');
    var amountInput = document.getElementById('mpesa_amount');
    <?php if($clientPhone): ?>
    if (phoneInput && !phoneInput.value) phoneInput.value = <?php echo json_encode($clientPhone, 15, 512) ?>;
    <?php endif; ?>
    <?php if($suggestedAmount): ?>
    if (amountInput && !amountInput.value) amountInput.value = <?php echo json_encode((int) $suggestedAmount, 15, 512) ?>;
    <?php endif; ?>
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/support/client-local-show.blade.php ENDPATH**/ ?>