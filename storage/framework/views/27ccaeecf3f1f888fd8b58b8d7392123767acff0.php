<?php $__env->startSection('title', 'Email Client'); ?>

<?php $__env->startSection('content'); ?>
<nav class="breadcrumb-nav mb-3">
    <a href="<?php echo e(route('support')); ?>" class="text-muted small text-decoration-none">Support</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Email Client</span>
</nav>

<div class="page-header d-flex flex-wrap align-items-center gap-3 mb-4">
    <div class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width:52px;height:52px;background:linear-gradient(135deg,var(--agile-primary),#0a4a8f);">
        <i class="bi bi-envelope-fill fs-4"></i>
    </div>
    <div>
        <h1 class="app-page-title mb-1">Email Client</h1>
        <p class="app-page-sub mb-0">Send email from your configured mailbox (Microsoft Graph or SMTP), same as ticket notifications.</p>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e(session('error')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php
    $toEmailDefault = old('to_email', $presetEmail ?? '');
    $toNameDefault = old('to_name', $presetName ?? '');
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="app-card p-4">
            <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary);letter-spacing:0.08em">Compose</h6>
            <form method="POST" action="<?php echo e(route('support.email-client.send')); ?>" id="clientEmailForm">
                <?php echo csrf_field(); ?>
                <?php $__currentLoopData = $composeContext ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ctxKey => $ctxVal): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <input type="hidden" name="compose_<?php echo e($ctxKey); ?>" value="<?php echo e($ctxVal); ?>">
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">To <span class="text-danger">*</span></label>
                    <?php if($presetContact && $presetEmail): ?>
                        <div class="d-flex align-items-center gap-2 p-3 rounded-2 bg-light border mb-2" style="border-color: var(--card-border, #dee2e6) !important;">
                            <i class="bi bi-person-fill text-primary"></i>
                            <div class="flex-grow-1">
                                <span class="fw-semibold"><?php echo e(trim(($presetContact->firstname ?? '') . ' ' . ($presetContact->lastname ?? '')) ?: 'Client'); ?></span>
                                <?php if($presetPolicy): ?><span class="text-muted small ms-2 font-monospace">· <?php echo e($presetPolicy); ?></span><?php endif; ?>
                            </div>
                            <a href="<?php echo e(route('support.email-client')); ?>" class="btn btn-sm btn-outline-secondary">Change recipient</a>
                        </div>
                    <?php elseif($presetEmail && !($presetContact)): ?>
                        <div class="d-flex align-items-center gap-2 p-3 rounded-2 bg-light border mb-2" style="border-color: var(--card-border, #dee2e6) !important;">
                            <i class="bi bi-person-fill text-primary"></i>
                            <div class="flex-grow-1">
                                <span class="fw-semibold"><?php echo e($presetName ?? 'Client'); ?></span>
                                <?php if($presetPolicy): ?><span class="text-muted small ms-2 font-monospace">· <?php echo e($presetPolicy); ?></span><?php endif; ?>
                            </div>
                            <a href="<?php echo e(route('support.email-client')); ?>" class="btn btn-sm btn-outline-secondary">Change recipient</a>
                        </div>
                    <?php endif; ?>
                    <input type="email" name="to_email" class="form-control <?php $__errorArgs = ['to_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required maxlength="255"
                           value="<?php echo e($toEmailDefault); ?>" placeholder="client@example.com"
                           <?php if(!empty($presetEmail)): ?> readonly <?php endif; ?>>
                    <?php $__errorArgs = ['to_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <?php if(!empty($presetEmail)): ?>
                        <button type="button" class="btn btn-link btn-sm px-0 mt-1" id="unlockEmailBtn">Edit address</button>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Recipient name <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="to_name" class="form-control <?php $__errorArgs = ['to_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" maxlength="255" value="<?php echo e($toNameDefault); ?>" placeholder="Jane Doe">
                    <?php $__errorArgs = ['to_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Subject <span class="text-danger">*</span></label>
                    <input type="text" name="subject" class="form-control <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required maxlength="255" value="<?php echo e(old('subject')); ?>">
                    <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold small">Message <span class="text-danger">*</span></label>
                    <textarea name="body" class="form-control <?php $__errorArgs = ['body'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" rows="10" required maxlength="50000" placeholder="Your message (plain text)"><?php echo e(old('body')); ?></textarea>
                    <?php $__errorArgs = ['body'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <button type="submit" class="btn app-btn-primary btn-lg px-4">
                    <i class="bi bi-send-fill me-2"></i>Send email
                </button>
            </form>
        </div>

        <?php if($customers->isNotEmpty()): ?>
            <div class="app-card p-4 mt-4">
                <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary);letter-spacing:0.08em">Pick from CRM</h6>
                <p class="small text-muted mb-3">Click a row to fill the address above.</p>
                <div class="list-group list-group-flush border rounded-2 overflow-auto" style="max-height:260px;">
                    <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $em = trim((string) ($c->email ?: $c->email1 ?: '')); ?>
                        <?php if($em !== '' && filter_var($em, FILTER_VALIDATE_EMAIL)): ?>
                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center pick-contact-email py-2"
                                    data-email="<?php echo e(e($em)); ?>"
                                    data-name="<?php echo e(e(trim(($c->firstname ?? '') . ' ' . ($c->lastname ?? '')))); ?>">
                                <span><?php echo e(trim(($c->firstname ?? '') . ' ' . ($c->lastname ?? ''))); ?></span>
                                <span class="font-monospace small text-muted"><?php echo e($em); ?></span>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-4">
        <div class="app-card p-4 h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Notes</h6>
            <ul class="small text-muted ps-3 mb-4">
                <li class="mb-2">Uses the same sender as ticket notifications (Graph or <code>MAIL_*</code>).</li>
                <li class="mb-2">Plain text only — no attachments from this screen.</li>
                <li class="mb-0">To add a password to a PDF, use <a href="<?php echo e(route('tools.pdf-protect')); ?>">Tools → Protect PDF</a>.</li>
            </ul>
            <a href="<?php echo e(($returnPolicy ?? null) ? route('support.clients.show', ['policy' => $returnPolicy]) : route('support.customers')); ?>" class="btn btn-outline-secondary w-100 mb-2">
                <i class="bi bi-arrow-left me-2"></i><?php echo e(($returnPolicy ?? null) ? 'Back to Client' : 'Clients'); ?>

            </a>
            <?php if(!($returnPolicy ?? null)): ?>
            <a href="<?php echo e(route('support.customers')); ?>" class="btn btn-outline-secondary w-100">
                <i class="bi bi-people me-2"></i>Clients
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('unlockEmailBtn')?.addEventListener('click', function() {
        var i = document.querySelector('input[name="to_email"]');
        if (i) { i.removeAttribute('readonly'); i.focus(); }
        this.remove();
    });
    document.querySelectorAll('.pick-contact-email').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var e = document.querySelector('input[name="to_email"]');
            var n = document.querySelector('input[name="to_name"]');
            if (e) {
                e.value = btn.getAttribute('data-email') || '';
                e.removeAttribute('readonly');
            }
            if (n) n.value = btn.getAttribute('data-name') || '';
        });
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/support/client-email.blade.php ENDPATH**/ ?>