<nav class="breadcrumb-nav mb-3" aria-label="Breadcrumb">
    <a href="<?php echo e(route('settings.crm')); ?>" class="text-muted small text-decoration-none">Settings</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-muted small">User Management</span>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Users</span>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h5 class="fw-bold mb-1" style="color:var(--agile-text)">Users</h5>
        <p class="text-muted small mb-0">Assign users and roles.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?php echo e(route('settings.users.create')); ?>" class="btn btn-sm btn-outline-primary" style="border-radius:8px">
            <i class="bi bi-person-plus me-1"></i>Add user
        </a>
        <a href="<?php echo e(route('setup.users')); ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
            <i class="bi bi-diagram-3 me-1"></i>Reporting lines
        </a>
        <a href="<?php echo e(route('settings.crm')); ?>?section=roles" class="btn btn-sm" style="background:var(--agile-primary);color:#fff;border-radius:8px">Manage Roles</a>
        <a href="<?php echo e(route('settings.crm')); ?>?section=client-access" class="btn btn-sm btn-outline-primary" style="border-radius:8px">
            <i class="bi bi-person-check me-1"></i>Client Access
        </a>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e(session('error')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>


<form method="GET" action="<?php echo e(route('settings.crm')); ?>" class="mb-4" id="usersSearchForm">
    <input type="hidden" name="section" value="users">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="usersSearch" class="form-label small text-muted mb-1">Search users</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="usersSearch" name="search" class="form-control" placeholder="Name, email, or username..." value="<?php echo e($usersSearch ?? ''); ?>" aria-label="Search users">
            </div>
        </div>
        <div class="col-md-2">
            <label for="usersStatusFilter" class="form-label small text-muted mb-1">Status</label>
            <select id="usersStatusFilter" name="status" class="form-select">
                <option value="active" <?php echo e(($usersStatusFilter ?? 'active') === 'active' ? 'selected' : ''); ?>>Active</option>
                <option value="inactive" <?php echo e(($usersStatusFilter ?? '') === 'inactive' ? 'selected' : ''); ?>>Left</option>
                <option value="all" <?php echo e(($usersStatusFilter ?? '') === 'all' ? 'selected' : ''); ?>>All</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="usersRoleFilter" class="form-label small text-muted mb-1">Role</label>
            <select id="usersRoleFilter" name="role" class="form-select">
                <option value="">All roles</option>
                <?php $__currentLoopData = $roles ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($r->roleid); ?>" <?php echo e(($usersRoleFilter ?? '') == $r->roleid ? 'selected' : ''); ?>><?php echo e($r->rolename); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="usersDeptFilter" class="form-label small text-muted mb-1">Department</label>
            <select id="usersDeptFilter" name="department" class="form-select">
                <option value="">All</option>
                <?php $__currentLoopData = $departmentsList ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($d); ?>" <?php echo e(($usersDeptFilter ?? '') === $d ? 'selected' : ''); ?>><?php echo e($d); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm" style="background:var(--agile-primary);color:#fff;border-radius:8px">
                <i class="bi bi-search me-1"></i>Search
            </button>
            <?php if($usersSearch ?? $usersRoleFilter ?? $usersDeptFilter ?? ($usersStatusFilter ?? 'active') !== 'active'): ?>
                <a href="<?php echo e(route('settings.crm')); ?>?section=users" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">Clear</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<p class="text-muted small mb-3">
    <strong><?php echo e(count($users ?? [])); ?></strong> user<?php echo e(count($users ?? []) !== 1 ? 's' : ''); ?> found
    <?php if($usersSearch ?? $usersRoleFilter ?? $usersDeptFilter ?? ''): ?>
        <span class="ms-2">— <a href="<?php echo e(route('settings.crm')); ?>?section=users" class="text-decoration-none">Show all</a></span>
    <?php endif; ?>
</p>

<div class="table-responsive users-table-wrapper">
    <table class="table table-hover align-middle mb-0 settings-table">
        <thead>
            <tr>
                <th class="col-user">User</th>
                <th class="col-email">Email</th>
                <th class="col-username">Username</th>
                <th class="col-dept">Department</th>
                <th class="col-reporting">Reports To</th>
                <th class="col-role">Current Role</th>
                <th class="col-action">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $users ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php $isInactive = ($user->status ?? '') === 'Inactive'; ?>
            <tr class="<?php echo e($isInactive ? 'table-secondary' : ''); ?>">
                <td class="col-user">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:var(--agile-primary-muted);color:var(--agile-primary)"><i class="bi bi-person-fill"></i></div>
                        <div>
                            <strong><?php echo e($user->full_name); ?></strong>
                            <?php if($isInactive): ?>
                                <span class="badge bg-secondary ms-1">Left</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td class="col-email"><?php echo e($user->email1 ?? '—'); ?></td>
                <td class="col-username" title="<?php echo e($user->user_name); ?>"><code class="small"><?php echo e($user->user_name); ?></code></td>
                <td class="col-dept">
                    <?php if(!$isInactive): ?>
                    <form id="dept-form-<?php echo e($user->id); ?>" action="<?php echo e(route('settings.users.update-department', $user->id)); ?>" method="POST" class="d-inline-flex align-items-center gap-2" style="flex-wrap:nowrap">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>
                        <input type="hidden" name="redirect" value="<?php echo e(request()->fullUrl()); ?>">
                        <select name="department" class="form-select form-select-sm" style="min-width:140px">
                            <option value="">— Not set —</option>
                            <?php $__currentLoopData = $departmentsList ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($d); ?>" <?php echo e((($userDepartments ?? [])[$user->id] ?? '') === $d ? 'selected' : ''); ?>><?php echo e($d); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <button type="submit" form="dept-form-<?php echo e($user->id); ?>" name="save_dept" value="1" class="btn btn-sm" style="background:var(--agile-primary);color:#fff;border-radius:8px;padding:.25rem .65rem;white-space:nowrap">Save</button>
                    </form>
                    <?php else: ?>
                        <span class="text-muted"><?php echo e(($userDepartments ?? [])[$user->id] ?? '—'); ?></span>
                    <?php endif; ?>
                </td>
                <td class="col-reporting">
                    <?php if(!$isInactive): ?>
                    <form id="reporting-form-<?php echo e($user->id); ?>" action="<?php echo e(route('settings.users.update-reporting-manager', $user->id)); ?>" method="POST" class="d-inline-flex align-items-center gap-2" style="flex-wrap:nowrap">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>
                        <input type="hidden" name="redirect" value="<?php echo e(request()->fullUrl()); ?>">
                        <select name="manager_id" class="form-select form-select-sm" style="min-width:170px">
                            <option value="">— Not set —</option>
                            <?php $__currentLoopData = ($reportingManagerOptions ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mgr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if((int) $mgr->id === (int) $user->id) continue; ?>
                                <option value="<?php echo e($mgr->id); ?>" <?php echo e((int) (($reportingLines ?? [])[$user->id] ?? 0) === (int) $mgr->id ? 'selected' : ''); ?>>
                                    <?php echo e(trim(($mgr->first_name ?? '') . ' ' . ($mgr->last_name ?? '')) ?: ($mgr->user_name ?? ('User #' . $mgr->id))); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <button type="submit" form="reporting-form-<?php echo e($user->id); ?>" name="save_reporting" value="1" class="btn btn-sm" style="background:var(--agile-primary);color:#fff;border-radius:8px;padding:.25rem .65rem;white-space:nowrap">Save</button>
                    </form>
                    <?php else: ?>
                        <?php $rid = (int) (($reportingLines ?? [])[$user->id] ?? 0); ?>
                        <?php if($rid > 0): ?>
                            <span class="text-muted">User #<?php echo e($rid); ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <?php
                    $currentRoleId = ($userRoles ?? [])[$user->id] ?? null;
                    $role = $currentRoleId ? (($roles ?? collect())->firstWhere('roleid', $currentRoleId)) : null;
                    $roleDisplay = $role ? $role->rolename : ($currentRoleId ?? 'No role');
                ?>
                <td class="col-role" title="<?php echo e($roleDisplay); ?>">
                    <?php if($currentRoleId): ?>
                        <span class="badge" style="background:var(--agile-primary-muted);color:var(--agile-primary)"><?php echo e($roleDisplay); ?></span>
                    <?php else: ?>
                        <span class="text-muted">No role</span>
                    <?php endif; ?>
                </td>
                <td class="col-action">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <?php if($isInactive): ?>
                            <form action="<?php echo e(route('settings.users.reactivate', $user->id)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Reactivate <?php echo e($user->full_name); ?>? They will be able to sign in again.');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="redirect" value="<?php echo e(request()->fullUrl()); ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success" style="border-radius:8px" title="Reactivate user">
                                    <i class="bi bi-person-check"></i> Reactivate
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?php echo e(route('settings.users.edit', $user->id)); ?>?redirect=<?php echo e(urlencode(request()->fullUrl())); ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:8px" title="Edit user details">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <form action="<?php echo e(route('settings.users.send-reset-link', $user->id)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Send a password reset link to <?php echo e($user->email1 ?? $user->user_name); ?>?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="redirect" value="<?php echo e(request()->fullUrl()); ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary" style="border-radius:8px" title="Send password reset email" <?php echo e(empty(trim($user->email1 ?? '')) ? 'disabled' : ''); ?>>
                                    <i class="bi bi-key"></i> Reset
                                </button>
                            </form>
                            <form id="role-form-<?php echo e($user->id); ?>" action="<?php echo e(route('setup.users.update-role')); ?>" method="POST" class="d-inline-flex align-items-center gap-2">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="user_id" value="<?php echo e($user->id); ?>">
                                <input type="hidden" name="redirect" value="<?php echo e(request()->fullUrl()); ?>">
                                <select name="role_id" class="form-select form-select-sm" style="min-width:160px">
                                    <?php $__currentLoopData = $roles ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($r->roleid); ?>" <?php echo e((($userRoles ?? [])[$user->id] ?? '') === $r->roleid ? 'selected' : ''); ?>><?php echo e($r->rolename); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <button type="submit" form="role-form-<?php echo e($user->id); ?>" name="save_role" value="1" class="btn btn-sm" style="background:var(--agile-primary);color:#fff;border-radius:8px">Save</button>
                            </form>
                            <?php if($user->id !== (auth()->guard('vtiger')->id())): ?>
                            <a href="<?php echo e(route('settings.users.offboard', $user->id)); ?>?redirect=<?php echo e(urlencode(request()->fullUrl())); ?>" class="btn btn-sm btn-outline-danger" style="border-radius:8px" title="Offboard user (reassign records, then deactivate)">
                                <i class="bi bi-person-x"></i> Offboard
                            </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-people display-6 d-block mb-2"></i>
                    No users found. Try different search terms or clear filters.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.users-table-wrapper { max-height: calc(100vh - 380px); overflow-y: auto; }
.users-table-wrapper thead th { position: sticky; top: 0; background: #f8fafc !important; z-index: 1; box-shadow: 0 1px 0 var(--agile-border); }
.col-dept { min-width: 220px; }
.col-reporting { min-width: 250px; }
</style>
<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var roleFilter = document.getElementById('usersRoleFilter');
    if (roleFilter) roleFilter.addEventListener('change', function() { document.getElementById('usersSearchForm')?.submit(); });
});
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/settings/sections/users.blade.php ENDPATH**/ ?>