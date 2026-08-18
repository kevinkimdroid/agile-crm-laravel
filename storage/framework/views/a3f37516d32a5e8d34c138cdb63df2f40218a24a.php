<div class="card contact-detail-card mb-4">
        <div class="card-body p-4">
            <h6 class="text-uppercase small fw-bold text-muted mb-3">Quick actions</h6>
            <div class="d-flex flex-column gap-2">
                <?php if($displayPhone ?? false): ?>
                <a href="tel:<?php echo e(tel_href($displayPhone)); ?>" class="btn btn-outline-primary"><i class="bi bi-telephone me-2"></i>Call</a>
                <a href="<?php echo e(route('support.sms-notifier', array_filter(['contact_id' => $contact->contactid, 'phone' => $displayPhone]))); ?>" class="btn btn-outline-primary"><i class="bi bi-chat-dots me-2"></i>Send Text</a>
                <?php endif; ?>
                <?php if(($displayEmail ?? '') !== ''): ?>
                <a href="<?php echo e(route('support.email-client', array_filter(['contact_id' => $contact->contactid, 'email' => $displayEmail, 'client_name' => $contact->full_name]))); ?>" class="btn btn-outline-primary"><i class="bi bi-envelope me-2"></i>Send Email</a>
                <?php endif; ?>
                <a href="<?php echo e(route('tickets.create', ['contact_id' => $contact->contactid])); ?>" class="btn btn-outline-success"><i class="bi bi-ticket-perforated me-2"></i>Create Ticket</a>
                <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#followupModal"><i class="bi bi-calendar-check me-2"></i>Log Follow-up</a>
                <?php if($canCollectMpesa ?? false): ?>
                <?php echo $__env->make('support.partials.client-mpesa-trigger-button', [
                    'mpesaTriggerClass' => 'btn btn-outline-success text-start',
                    'mpesaTriggerLabel' => '<i class="bi bi-phone me-2"></i>Collect via M-Pesa',
                    'mpesaConfigured' => $mpesaConfigured ?? app(\App\Services\MpesaStkPushService::class)->isConfigured(),
                    'mpesaSandboxSimulate' => $mpesaSandboxSimulate ?? app(\App\Services\MpesaStkPushService::class)->isSandboxSimulate(),
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php endif; ?>
                <a href="<?php echo e(route('contacts.edit', $contact->contactid)); ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil me-2"></i>Edit prospect</a>
            </div>
        </div>
    </div>

    <?php if($canCollectMpesa ?? false): ?>
    <div class="card contact-detail-card mb-4 client-summary-payments mpesa-ui">
        <div class="card-body p-4">
            <h6 class="text-uppercase small fw-bold text-muted mb-3">Payments</h6>
            <p class="text-muted small mb-3">Collect premium via M-Pesa STK push for policy <code><?php echo e($prospectPolicy); ?></code>.</p>
            <?php echo $__env->make('support.partials.client-mpesa-trigger-button', [
                'mpesaTriggerClass' => 'btn btn-success w-100',
                'mpesaTriggerLabel' => '<i class="bi bi-phone me-1"></i>Collect premium via M-Pesa',
                'mpesaConfigured' => $mpesaConfigured ?? app(\App\Services\MpesaStkPushService::class)->isConfigured(),
                'mpesaSandboxSimulate' => $mpesaSandboxSimulate ?? app(\App\Services\MpesaStkPushService::class)->isSandboxSimulate(),
            ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card contact-detail-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-uppercase small fw-bold text-muted mb-0">Follow-ups</h6>
                <a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#followupModal"><i class="bi bi-plus-lg me-1"></i>Log Follow-up</a>
            </div>
            <?php if(($followups ?? collect())->isNotEmpty()): ?>
            <ul class="list-unstyled mb-0">
                <?php $__currentLoopData = $followups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li class="py-2 border-bottom">
                    <p class="mb-0 small"><?php echo e(Str::limit($fu->note, 100)); ?></p>
                    <small class="text-muted"><?php echo e($fu->followup_date ? $fu->followup_date->format('d M Y') : optional($fu->created_at)->format('d M Y')); ?> · <?php echo e($fu->status); ?></small>
                </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
            <?php else: ?>
            <div class="summary-empty-box py-4 text-center text-muted">
                <i class="bi bi-calendar-check opacity-50 d-block mb-2"></i>
                No follow-ups yet. Use "Log Follow-up" to track outreach.
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/contacts/partials/summary-sidebar.blade.php ENDPATH**/ ?>