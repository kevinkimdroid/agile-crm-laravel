<?php
    $summaryActivities = $activities ?? collect();
    $summaryShowAddButtons = $summaryShowAddButtons ?? true;
?>
<div class="card contact-detail-card mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Activities</h6>
            <?php if(! empty($summaryActivitiesViewAllUrl)): ?>
            <a href="<?php echo e($summaryActivitiesViewAllUrl); ?>" class="btn btn-sm btn-outline-secondary">View all</a>
            <?php endif; ?>
        </div>
        <?php if($summaryShowAddButtons && ! empty($summaryActivityTaskUrl) && ! empty($summaryActivityEventUrl)): ?>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="<?php echo e($summaryActivityTaskUrl); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus-lg me-1"></i>Add Task</a>
            <a href="<?php echo e($summaryActivityEventUrl); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus-lg me-1"></i>Add Event</a>
        </div>
        <?php endif; ?>
        <?php if($summaryActivities->isNotEmpty()): ?>
        <ul class="list-unstyled mb-0">
            <?php $__currentLoopData = $summaryActivities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $act): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="py-2 border-bottom d-flex justify-content-between align-items-start gap-2">
                <div>
                    <strong><?php echo e($act->subject ?? 'Untitled'); ?></strong>
                    <span class="badge bg-secondary ms-1"><?php echo e($act->activitytype ?? 'Task'); ?></span>
                    <p class="text-muted small mb-0"><?php echo e($act->date_start ?? ''); ?></p>
                </div>
                <?php if(! empty($summaryActivityEditUrl)): ?>
                <a href="<?php echo e($summaryActivityEditUrl($act)); ?>" class="btn btn-sm btn-outline-primary flex-shrink-0" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <?php endif; ?>
            </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
        <?php else: ?>
        <div class="summary-empty-box py-4 text-center text-muted">No pending activities</div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/partials/profile-summary-activities.blade.php ENDPATH**/ ?>