@php
    $slaTab = $slaTab ?? request('tab', 'categories');
    if (! in_array($slaTab, ['categories', 'departments', 'roles'], true)) {
        $slaTab = 'categories';
    }
    $categoryCount = count($categoryTat ?? []);
    $departmentCount = count($departmentTat ?? []);
    $missingCat = count($categoriesWithoutTat ?? []);
    $missingDept = count($departmentsWithoutTat ?? []);
@endphp

<style>
.sla-hero {
    background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 48%, #0ea5e9 100%);
    border-radius: 16px;
    color: #fff;
    padding: 1.5rem 1.75rem;
    margin-bottom: 1.25rem;
    position: relative;
    overflow: hidden;
}
.sla-hero::after {
    content: '';
    position: absolute;
    right: -40px;
    top: -40px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,.12);
}
.sla-hero h5 { font-weight: 700; letter-spacing: -.02em; }
.sla-stat {
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 12px;
    padding: .65rem 1rem;
    min-width: 110px;
}
.sla-stat .num { font-size: 1.35rem; font-weight: 700; line-height: 1.1; }
.sla-stat .lbl { font-size: .72rem; opacity: .85; text-transform: uppercase; letter-spacing: .04em; }
.sla-tabs {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: .5rem;
}
.sla-tab {
    border: 0;
    background: transparent;
    color: #64748b;
    font-weight: 600;
    font-size: .9rem;
    padding: .55rem 1rem;
    border-radius: 999px;
    transition: all .15s ease;
}
.sla-tab:hover { background: #f1f5f9; color: #0f172a; }
.sla-tab.active {
    background: #0c4a6e;
    color: #fff;
    box-shadow: 0 6px 16px rgba(12, 74, 110, .25);
}
.sla-tab .badge {
    font-weight: 600;
    font-size: .7rem;
    margin-left: .35rem;
    vertical-align: middle;
}
.sla-panel { display: none; }
.sla-panel.active { display: block; animation: slaFade .2s ease; }
@keyframes slaFade {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: none; }
}
.sla-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}
.sla-search {
    max-width: 280px;
    border-radius: 10px;
}
.sla-hours-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    background: #ecfeff;
    color: #0e7490;
    border: 1px solid #a5f3fc;
    border-radius: 999px;
    padding: .2rem .7rem;
    font-weight: 600;
    font-size: .85rem;
}
.sla-table-wrap {
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
}
.sla-table thead th {
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    font-size: .72rem;
    letter-spacing: .05em;
}
.sla-table tbody tr { transition: background .12s ease; }
.sla-table tbody tr:hover { background: #f8fafc; }
.sla-empty {
    text-align: center;
    padding: 2.5rem 1rem;
    color: #64748b;
}
.sla-roles-grid .form-check {
    padding: .65rem .85rem .65rem 2.1rem;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #fff;
    transition: border-color .12s, box-shadow .12s;
}
.sla-roles-grid .form-check:hover {
    border-color: #94a3b8;
}
.sla-roles-grid .form-check-input:checked ~ .form-check-label {
    font-weight: 600;
    color: #0c4a6e;
}
.sla-alert-soft {
    border: 0;
    border-radius: 12px;
    background: #fffbeb;
    color: #92400e;
    border-left: 4px solid #f59e0b;
}
</style>

<div class="sla-hero">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 position-relative" style="z-index:1">
        <div>
            <h5 class="mb-1">Ticket SLA &amp; TAT</h5>
            <p class="mb-0 opacity-90" style="max-width:560px;font-size:.92rem">
                Two separate turnaround settings: <strong>per ticket category</strong> and <strong>per org department</strong>.
                Breach reporting uses the stricter of the two when both apply.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <div class="sla-stat text-center">
                <div class="num">{{ $categoryCount }}</div>
                <div class="lbl">Categories</div>
            </div>
            <div class="sla-stat text-center">
                <div class="num">{{ $departmentCount }}</div>
                <div class="lbl">Departments</div>
            </div>
            <div class="sla-stat text-center">
                <div class="num">{{ $missingCat + $missingDept }}</div>
                <div class="lbl">Missing TAT</div>
            </div>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if (session('info'))
    <div class="alert alert-info alert-dismissible fade show d-flex align-items-center" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>{{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="sla-tabs" role="tablist">
    <button type="button" class="sla-tab {{ $slaTab === 'categories' ? 'active' : '' }}" data-sla-tab="categories">
        <i class="bi bi-tags me-1"></i> Turnaround per category
        <span class="badge bg-light text-dark">{{ $categoryCount }}</span>
    </button>
    <button type="button" class="sla-tab {{ $slaTab === 'departments' ? 'active' : '' }}" data-sla-tab="departments">
        <i class="bi bi-building me-1"></i> Turnaround per department
        <span class="badge bg-light text-dark">{{ $departmentCount }}</span>
    </button>
    <button type="button" class="sla-tab {{ $slaTab === 'roles' ? 'active' : '' }}" data-sla-tab="roles">
        <i class="bi bi-shield-check me-1"></i> Close permissions
    </button>
</div>

{{-- ========== CATEGORIES ========== --}}
<div class="sla-panel {{ $slaTab === 'categories' ? 'active' : '' }}" id="sla-panel-categories">
    @if(!empty($categoriesWithoutTat))
    <div class="alert sla-alert-soft d-flex align-items-start mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
        <div>
            <strong>{{ $missingCat }} categor{{ $missingCat === 1 ? 'y' : 'ies' }} without TAT:</strong>
            {{ implode(', ', $categoriesWithoutTat) }}
            <div class="mt-1 small">Sync ticket categories to add them with a 24h default.</div>
        </div>
    </div>
    @endif

    <div class="sla-toolbar">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <input type="search" class="form-control form-control-sm sla-search" id="slaCatSearch" placeholder="Search categories…">
            <form action="{{ route('settings.ticket-sla.sync-categories') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i> Sync from ticket categories
                </button>
            </form>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCatModal">
            <i class="bi bi-plus-lg me-1"></i> Add category
        </button>
    </div>

    <div class="sla-table-wrap">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 sla-table" id="slaCatTable">
                <thead>
                    <tr>
                        <th class="text-uppercase small fw-bold text-muted py-3 px-4">Category</th>
                        <th class="text-uppercase small fw-bold text-muted py-3 px-4">TAT</th>
                        <th class="text-uppercase small fw-bold text-muted py-3 px-4 text-end" width="160">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categoryTat ?? [] as $row)
                    <tr data-sla-name="{{ strtolower($row->category) }}">
                        <td class="px-4 fw-semibold">{{ $row->category }}</td>
                        <td class="px-4">
                            <span class="sla-hours-pill"><i class="bi bi-hourglass-split"></i>{{ $row->tat_hours }} hours</span>
                        </td>
                        <td class="px-4 text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#editCatModal"
                                data-cat="{{ $row->category }}" data-hours="{{ $row->tat_hours }}">Edit</button>
                            <form action="{{ route('settings.ticket-sla.delete-category', $row->category) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove TAT for this category?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="sla-empty">
                            <i class="bi bi-tags display-6 d-block mb-2 opacity-50"></i>
                            No category TAT yet. Sync from ticket categories or add one manually.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ========== DEPARTMENTS ========== --}}
<div class="sla-panel {{ $slaTab === 'departments' ? 'active' : '' }}" id="sla-panel-departments">
    @if(!empty($departmentsWithoutTat))
    <div class="alert sla-alert-soft d-flex align-items-start mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
        <div>
            <strong>{{ $missingDept }} department{{ $missingDept === 1 ? '' : 's' }} without TAT:</strong>
            {{ implode(', ', $departmentsWithoutTat) }}
            <div class="mt-1 small">Sync org departments to add them with a 24h default.</div>
        </div>
    </div>
    @endif

    <div class="sla-toolbar">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <input type="search" class="form-control form-control-sm sla-search" id="slaDeptSearch" placeholder="Search departments…">
            <form action="{{ route('settings.ticket-sla.sync-departments') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i> Sync from org departments
                </button>
            </form>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importExcelModal">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Excel
            </button>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDeptModal">
            <i class="bi bi-plus-lg me-1"></i> Add department
        </button>
    </div>

    <div class="sla-table-wrap">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 sla-table" id="slaDeptTable">
                <thead>
                    <tr>
                        <th class="text-uppercase small fw-bold text-muted py-3 px-4">Department</th>
                        <th class="text-uppercase small fw-bold text-muted py-3 px-4">TAT</th>
                        <th class="text-uppercase small fw-bold text-muted py-3 px-4 text-end" width="160">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departmentTat ?? [] as $row)
                    <tr data-sla-name="{{ strtolower($row->department) }}">
                        <td class="px-4 fw-semibold">{{ $row->department }}</td>
                        <td class="px-4">
                            <span class="sla-hours-pill"><i class="bi bi-hourglass-split"></i>{{ $row->tat_hours }} hours</span>
                        </td>
                        <td class="px-4 text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#editDeptModal"
                                data-dept="{{ $row->department }}" data-hours="{{ $row->tat_hours }}">Edit</button>
                            <form action="{{ route('settings.ticket-sla.delete-department', $row->department) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove TAT for this department?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="sla-empty">
                            <i class="bi bi-building display-6 d-block mb-2 opacity-50"></i>
                            No department TAT yet. Sync from org departments or add one manually.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ========== ROLES ========== --}}
<div class="sla-panel {{ $slaTab === 'roles' ? 'active' : '' }}" id="sla-panel-roles">
    <div class="app-card overflow-hidden border-0 shadow-sm" style="border-radius:14px">
        <div class="card-header bg-transparent border-bottom py-3">
            <h6 class="mb-0 fw-bold">Roles allowed to close tickets</h6>
            <p class="text-muted small mb-0 mt-1">Only users with these roles can set ticket status to Closed (assignees can always close their own).</p>
        </div>
        <div class="card-body">
            <form action="{{ route('settings.ticket-sla.update-roles') }}" method="POST">
                @csrf
                <div class="row g-2 sla-roles-grid">
                    @foreach($roles ?? [] as $role)
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check">
                            <input type="checkbox" name="roles_can_close[]" value="{{ $role->rolename }}" class="form-check-input" id="role_{{ $role->roleid }}"
                                {{ in_array($role->rolename, $rolesCanClose ?? []) ? 'checked' : '' }}>
                            <label class="form-check-label" for="role_{{ $role->roleid }}">{{ $role->rolename }}</label>
                        </div>
                    </div>
                    @endforeach
                </div>
                @if(empty($roles))
                <p class="text-muted small mb-0">No roles found. Administrator can close by default.</p>
                @endif
                <button type="submit" class="btn btn-primary mt-3">
                    <i class="bi bi-check2 me-1"></i> Save permissions
                </button>
            </form>
        </div>
    </div>
</div>

<div class="mt-4 d-flex flex-wrap gap-2">
    <a href="{{ route('reports.sla-broken') }}" class="btn btn-outline-primary">
        <i class="bi bi-person-badge me-1"></i> Client Tickets – Broken SLA
    </a>
    <a href="{{ route('reports.sla-broken-work') }}" class="btn btn-outline-primary">
        <i class="bi bi-kanban me-1"></i> Work Tickets – Broken SLA
    </a>
</div>

{{-- Import from Excel Modal --}}
<div class="modal fade" id="importExcelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('settings.ticket-sla.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import department TAT from Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Upload an Excel file with one sheet per department. Each sheet should have a <strong>Defined Time frame</strong> column (e.g. "1 day", "5 days", "24 Hours"). The shortest TAT per department will be used.</p>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Excel file <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                        <small class="text-muted">.xlsx or .xls, max 10 MB</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Category Modal --}}
<div class="modal fade" id="addCatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('settings.ticket-sla.add-category') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add category TAT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category name <span class="text-danger">*</span></label>
                        <input type="text" name="category" class="form-control" list="slaCategorySuggestions" placeholder="e.g. Disability Claim" required>
                        <datalist id="slaCategorySuggestions">
                            @foreach(($categoriesWithoutTat ?? []) as $sug)
                                <option value="{{ $sug }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">TAT (hours) <span class="text-danger">*</span></label>
                        <input type="number" name="tat_hours" class="form-control" value="24" min="1" max="720" required>
                        <small class="text-muted">Hours allowed to resolve tickets in this category.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Category Modal --}}
<div class="modal fade" id="editCatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('settings.ticket-sla.update-category') }}" method="POST">
                @csrf
                <input type="hidden" name="category" id="editCatName">
                <div class="modal-header">
                    <h5 class="modal-title">Edit category TAT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="editCatLabel"></p>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">TAT (hours) <span class="text-danger">*</span></label>
                        <input type="number" name="tat_hours" class="form-control" id="editCatHours" min="1" max="720" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Department Modal --}}
<div class="modal fade" id="addDeptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('settings.ticket-sla.add-department') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add department TAT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department name <span class="text-danger">*</span></label>
                        <input type="text" name="department" class="form-control" list="slaDeptSuggestions" placeholder="e.g. Claims, Underwriting" required>
                        <datalist id="slaDeptSuggestions">
                            @foreach(($departmentsWithoutTat ?? []) as $sug)
                                <option value="{{ $sug }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">TAT (hours) <span class="text-danger">*</span></label>
                        <input type="number" name="tat_hours" class="form-control" value="24" min="1" max="720" required>
                        <small class="text-muted">Hours allowed for tickets owned by this department.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Department Modal --}}
<div class="modal fade" id="editDeptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('settings.ticket-sla.update-department') }}" method="POST" id="editDeptForm">
                @csrf
                <input type="hidden" name="department" id="editDeptName">
                <div class="modal-header">
                    <h5 class="modal-title">Edit department TAT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="editDeptLabel"></p>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">TAT (hours) <span class="text-danger">*</span></label>
                        <input type="number" name="tat_hours" class="form-control" id="editDeptHours" min="1" max="720" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const tabs = document.querySelectorAll('[data-sla-tab]');
    const panels = {
        categories: document.getElementById('sla-panel-categories'),
        departments: document.getElementById('sla-panel-departments'),
        roles: document.getElementById('sla-panel-roles'),
    };

    function activate(tab) {
        tabs.forEach(btn => btn.classList.toggle('active', btn.dataset.slaTab === tab));
        Object.keys(panels).forEach(key => {
            panels[key]?.classList.toggle('active', key === tab);
        });
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('section', 'ticket-sla');
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        } catch (e) {}
    }

    tabs.forEach(btn => btn.addEventListener('click', () => activate(btn.dataset.slaTab)));

    function bindSearch(inputId, tableId) {
        const input = document.getElementById(inputId);
        const table = document.getElementById(tableId);
        if (!input || !table) return;
        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            table.querySelectorAll('tbody tr[data-sla-name]').forEach(row => {
                row.style.display = !q || row.dataset.slaName.includes(q) ? '' : 'none';
            });
        });
    }
    bindSearch('slaCatSearch', 'slaCatTable');
    bindSearch('slaDeptSearch', 'slaDeptTable');

    document.getElementById('editCatModal')?.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        if (btn?.dataset?.cat) {
            document.getElementById('editCatName').value = btn.dataset.cat;
            document.getElementById('editCatHours').value = btn.dataset.hours || 24;
            document.getElementById('editCatLabel').textContent = btn.dataset.cat;
        }
    });

    document.getElementById('editDeptModal')?.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        if (btn?.dataset?.dept) {
            document.getElementById('editDeptName').value = btn.dataset.dept;
            document.getElementById('editDeptHours').value = btn.dataset.hours || 24;
            document.getElementById('editDeptLabel').textContent = btn.dataset.dept;
        }
    });
})();
</script>
