<?php $__env->startSection('title', 'Prospects'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h1 class="page-title">Prospects</h1>
        <p class="page-subtitle">Manage sales prospects before they become clients.</p>
    </div>
    <a href="<?php echo e(route('contacts.create')); ?>" class="btn btn-primary-custom">
        <i class="bi bi-plus-lg me-1"></i> Add Prospect
    </a>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo e(session('success')); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?php echo e(session('error')); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if(session('info')): ?>
    <div class="alert alert-info alert-dismissible fade show"><?php echo e(session('info')); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3 p-md-4">
        <label class="form-label fw-semibold mb-2" for="contactsSearch"><i class="bi bi-search me-1"></i>Search prospects</label>
        <form action="<?php echo e(route('contacts.index')); ?>" method="GET" class="d-flex flex-column flex-md-row gap-2">
            <input type="text" id="contactsSearch" name="search" class="form-control form-control-lg" placeholder="Search by name, email, phone or mobile…" value="<?php echo e($search ?? ''); ?>" autocomplete="off">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom btn-lg px-4"><i class="bi bi-search me-1"></i>Search</button>
                <?php if(!empty($search)): ?>
                    <a href="<?php echo e(route('contacts.index')); ?>" class="btn btn-outline-secondary btn-lg">Clear</a>
                <?php endif; ?>
            </div>
        </form>
        <p class="text-muted small mb-0 mt-2">
            <?php if(!empty($search)): ?>
                Showing results for “<?php echo e($search); ?>”. Filter the results below as you type.
            <?php else: ?>
                Searches all prospects. Start typing to filter the current page instantly.
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Total Prospects</div>
                <div class="h5 mb-0"><?php echo e(number_format($contacts->total() ?? 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Showing This Page</div>
                <div class="h5 mb-0"><?php echo e(number_format($contacts->count() ?? 0)); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="contactsTable">
            <thead>
                <tr>
                    <th><button type="button" class="btn btn-link p-0 text-decoration-none sort-btn" data-col="0">Name</button></th>
                    <th><button type="button" class="btn btn-link p-0 text-decoration-none sort-btn" data-col="1">Email</button></th>
                    <th><button type="button" class="btn btn-link p-0 text-decoration-none sort-btn" data-col="2">Phone</button></th>
                    <th><button type="button" class="btn btn-link p-0 text-decoration-none sort-btn" data-col="3">Mobile</button></th>
                    <th width="120"></th>
                </tr>
            </thead>
            <tbody id="contactsTableBody">
                <?php $__empty_1 = true; $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="contact-row">
                        <td><a href="<?php echo e(route('contacts.show', $contact->contactid)); ?>" class="text-decoration-none fw-semibold"><?php echo e($contact->full_name); ?></a></td>
                        <td><?php echo e(personal_email_only($contact->email ?? null) ?? '—'); ?></td>
                        <td><?php echo e($contact->phone ?: '—'); ?></td>
                        <td><?php echo e($contact->mobile ?: '—'); ?></td>
                        <td>
                            <a href="<?php echo e(route('contacts.show', $contact->contactid)); ?>" class="btn btn-sm btn-outline-primary me-1">Open</a>
                            <a href="<?php echo e(route('contacts.edit', $contact->contactid)); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php if(!empty($search)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No prospects match “<?php echo e($search); ?>”. <a href="<?php echo e(route('contacts.index')); ?>">Clear search</a>.</td></tr>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No prospects found. <a href="<?php echo e(route('contacts.create')); ?>">Add your first prospect</a>.</td></tr>
                    <?php endif; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($contacts->hasPages()): ?>
        <div class="card-footer bg-transparent border-top py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="text-muted small">Showing <?php echo e($contacts->firstItem() ?? 0); ?>–<?php echo e($contacts->lastItem() ?? 0); ?> of <?php echo e($contacts->total()); ?></span>
            <?php echo e($contacts->withQueryString()->links('pagination::bootstrap-5')); ?>

        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('contactsSearch');
    const tbody = document.getElementById('contactsTableBody');
    const rows = () => Array.from(tbody?.querySelectorAll('tr.contact-row') || []);

    searchInput?.addEventListener('input', function () {
        const q = (this.value || '').toLowerCase().trim();
        rows().forEach(row => {
            const txt = (row.textContent || '').toLowerCase();
            row.style.display = txt.includes(q) ? '' : 'none';
        });
    });

    let sortState = { col: -1, asc: true };
    document.querySelectorAll('.sort-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const col = parseInt(this.dataset.col || '-1', 10);
            if (col < 0) return;
            sortState.asc = sortState.col === col ? !sortState.asc : true;
            sortState.col = col;

            const sorted = rows().sort((a, b) => {
                const av = (a.children[col]?.innerText || '').trim().toLowerCase();
                const bv = (b.children[col]?.innerText || '').trim().toLowerCase();
                return sortState.asc ? av.localeCompare(bv) : bv.localeCompare(av);
            });
            sorted.forEach(r => tbody.appendChild(r));
        });
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/contacts/index.blade.php ENDPATH**/ ?>