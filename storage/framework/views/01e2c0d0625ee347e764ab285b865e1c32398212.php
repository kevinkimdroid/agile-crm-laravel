<?php
    $commentReturnTab = $commentReturnTab ?? 'updates';
    $clientDocumentsUrl = ! empty($clientShowBase)
        ? route('support.clients.show', array_merge($clientShowBase, ['tab' => 'documents']))
        : route('support.clients.show', ['policy' => $clientPolicy, 'tab' => 'documents']);
?>
<div class="card contact-detail-card mb-4" id="client-comments">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Comments</h6>
        </div>
        <form method="POST" action="<?php echo e(route('support.clients.comments.store')); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="policy" value="<?php echo e($clientPolicy); ?>">
            <input type="hidden" name="return_tab" value="<?php echo e($commentReturnTab); ?>">
            <textarea name="body" class="form-control mb-2 <?php $__errorArgs = ['body'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" rows="3" placeholder="Post your comment here" required><?php echo e(old('body')); ?></textarea>
            <?php $__errorArgs = ['body'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <a href="<?php echo e($clientDocumentsUrl); ?>#client-documents" class="btn btn-sm btn-outline-primary mb-0">
                        <i class="bi bi-paperclip me-1"></i>Attach Files
                    </a>
                    <i class="bi bi-info-circle text-muted" title="Upload files on the Documents tab"></i>
                </div>
                <button type="submit" class="btn btn-sm btn-success">Post</button>
            </div>
        </form>
        <?php if(($clientComments ?? collect())->isNotEmpty()): ?>
        <h6 class="text-uppercase small fw-bold text-muted mt-4 mb-0 pt-3 border-top">Recent Comments</h6>
        <ul class="list-unstyled mb-0 mt-2">
            <?php $__currentLoopData = $clientComments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="py-3 <?php echo e(! $loop->last ? 'border-bottom' : ''); ?>">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <span class="fw-semibold small"><?php echo e($comment->author_display); ?></span>
                    <span class="text-muted small text-nowrap"><?php echo e($comment->created_at?->format('d M Y, H:i') ?? ''); ?></span>
                </div>
                <p class="mb-0 small"><?php echo nl2br(e($comment->body)); ?></p>
            </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
        <?php else: ?>
        <div class="summary-empty-box py-4 text-center text-muted mt-3 border-top">
            <i class="bi bi-chat-dots opacity-50 d-block mb-2"></i>
            No comments
        </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/support/partials/client-comments.blade.php ENDPATH**/ ?>