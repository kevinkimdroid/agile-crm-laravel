<nav class="breadcrumb-nav mb-3" aria-label="Breadcrumb">
    <a href="{{ route('settings.crm') }}" class="text-muted small text-decoration-none">Settings</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-muted small">User Management</span>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Client Access</span>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h5 class="fw-bold mb-1">Client Access</h5>
        <p class="text-muted small mb-0">Assign specific policy numbers to users. Enable <strong>Assigned clients only</strong> on their profile to enforce this list.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('settings.crm', ['section' => 'profiles']) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
            <i class="bi bi-person-vcard me-1"></i>Profile settings
        </a>
        <a href="{{ route('settings.crm', ['section' => 'users']) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
            <i class="bi bi-people me-1"></i>Users
        </a>
    </div>
</div>

@if (!($clientAccessReady ?? true))
<div class="alert alert-danger d-flex align-items-start mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
    <div>
        <strong>Client access is not set up yet.</strong>
        <div class="small mt-1">Run database migrations on this server: <code>php artisan migrate</code></div>
    </div>
</div>
@endif

@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-4">
    <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="client-access-steps mb-4">
    <div class="client-access-step">
        <span class="client-access-step-num">1</span>
        <span>Select a user</span>
    </div>
    <div class="client-access-step-arrow"><i class="bi bi-chevron-right"></i></div>
    <div class="client-access-step">
        <span class="client-access-step-num">2</span>
        <span>Add policy numbers</span>
    </div>
    <div class="client-access-step-arrow"><i class="bi bi-chevron-right"></i></div>
    <div class="client-access-step">
        <span class="client-access-step-num">3</span>
        <span>Set profile to <em>Assigned clients only</em></span>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="client-access-panel h-100">
            <div class="client-access-panel-head">
                <h6 class="mb-0 fw-bold"><i class="bi bi-people me-2"></i>Users</h6>
            </div>
            <div class="client-access-panel-body">
                <form method="GET" action="{{ route('settings.crm') }}" class="mb-3">
                    <input type="hidden" name="section" value="client-access">
                    @if ($selectedClientAccessUserId ?? null)
                    <input type="hidden" name="user" value="{{ $selectedClientAccessUserId }}">
                    @endif
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search name or username..." value="{{ $clientAccessSearch ?? '' }}">
                    </div>
                </form>
                <div class="client-access-user-list">
                    @forelse ($clientAccessUsers ?? [] as $u)
                    @php
                        $displayName = $u->full_name ?? trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: $u->user_name;
                        $isActive = (int) ($selectedClientAccessUserId ?? 0) === (int) $u->id;
                        $count = $clientAccessCounts[$u->id] ?? 0;
                    @endphp
                    <a href="{{ route('settings.crm', array_filter(['section' => 'client-access', 'user' => $u->id, 'search' => $clientAccessSearch ?? null])) }}"
                       class="client-access-user-item {{ $isActive ? 'is-active' : '' }}">
                        <div class="client-access-user-meta">
                            <span class="client-access-user-name">{{ $displayName }}</span>
                            <span class="client-access-user-sub">{{ $u->user_name }}@if($u->email1) · {{ $u->email1 }}@endif</span>
                        </div>
                        <span class="client-access-user-badge">{{ $count }}</span>
                    </a>
                    @empty
                    <div class="text-center text-muted small py-4">No active users match your search.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if ($selectedClientAccessUser ?? null)
        @php
            $selectedName = $selectedClientAccessUser->full_name ?? $selectedClientAccessUser->user_name;
            $assignmentCount = ($clientAccessAssignments ?? collect())->count();
        @endphp
        <div class="client-access-panel mb-4">
            <div class="client-access-panel-head d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="mb-0 fw-bold">{{ $selectedName }}</h6>
                    <span class="text-muted small">{{ $assignmentCount }} assigned client{{ $assignmentCount === 1 ? '' : 's' }}</span>
                </div>
                <a href="{{ route('settings.crm', ['section' => 'client-access', 'user' => $selectedClientAccessUser->id]) }}" class="btn btn-sm btn-outline-secondary">Refresh</a>
            </div>
            <div class="client-access-panel-body">
                <ul class="nav nav-tabs client-access-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-add-one" data-bs-toggle="tab" data-bs-target="#pane-add-one" type="button" role="tab">Add one client</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-bulk" data-bs-toggle="tab" data-bs-target="#pane-bulk" type="button" role="tab">Upload CSV</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pane-add-one" role="tabpanel">
                        <form action="{{ route('settings.client-access.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="userid" value="{{ $selectedClientAccessUser->id }}">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold">Policy number <span class="text-danger">*</span></label>
                                    <input type="text" name="policy_number" class="form-control form-control-lg" placeholder="e.g. GEMIL001234" required autocomplete="off">
                                    <div class="form-text">Client name is filled automatically from ERP when possible.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Client name (optional)</label>
                                    <input type="text" name="client_label" class="form-control" placeholder="Override display name">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Segment</label>
                                    <select name="system" class="form-select">
                                        <option value="">Any segment</option>
                                        @foreach ($segmentLabels ?? [] as $segKey => $segLabel)
                                        <option value="{{ $segKey }}">{{ $segLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary-custom" {{ !($clientAccessReady ?? true) ? 'disabled' : '' }}>
                                        <i class="bi bi-plus-lg me-1"></i>Assign client
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="pane-bulk" role="tabpanel">
                        <form action="{{ route('settings.client-access.bulk') }}" method="POST" enctype="multipart/form-data" id="clientAccessBulkForm">
                            @csrf
                            <input type="hidden" name="userid" value="{{ $selectedClientAccessUser->id }}">

                            <div class="client-access-upload-zone mb-4" id="clientAccessDropZone">
                                <input type="file" name="csv_file" id="clientAccessCsvFile" accept=".csv,.txt,text/csv" class="client-access-upload-input" {{ !($clientAccessReady ?? true) ? 'disabled' : '' }}>
                                <div class="client-access-upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                <p class="client-access-upload-title mb-1">Drop CSV here or click to browse</p>
                                <p class="client-access-upload-sub mb-3">Columns: <strong>user</strong>, <strong>policy_number</strong>, <strong>client_name</strong></p>
                                <p class="text-muted small mb-3">Use username or email in the <strong>user</strong> column. Leave blank to assign all rows to <strong>{{ $selectedClientAccessUser->user_name }}</strong>.</p>
                                <span class="client-access-upload-filename text-muted small" id="clientAccessFileName">No file selected</span>
                            </div>

                            @error('csv_file')
                            <div class="alert alert-danger py-2 small mb-3">{{ $message }}</div>
                            @enderror

                            <div class="d-flex flex-wrap gap-3 align-items-end mb-4">
                                <div>
                                    <label class="form-label small fw-semibold mb-1">Default segment</label>
                                    <select name="system" class="form-select form-select-sm">
                                        <option value="">Any segment</option>
                                        @foreach ($segmentLabels ?? [] as $segKey => $segLabel)
                                        <option value="{{ $segKey }}">{{ $segLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary-custom" {{ !($clientAccessReady ?? true) ? 'disabled' : '' }}>
                                    <i class="bi bi-upload me-1"></i>Upload &amp; import
                                </button>
                                <a href="{{ route('settings.client-access.template', ['user' => $selectedClientAccessUser->id]) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-download me-1"></i>Download template
                                </a>
                            </div>

                            <details class="client-access-template-box">
                                <summary class="fw-semibold small mb-0" style="cursor:pointer;">CSV format help</summary>
                                <div class="mt-3">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-2 client-access-template-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>user</th>
                                                    <th>policy_number</th>
                                                    <th>client_name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td><code>{{ $selectedClientAccessUser->user_name }}</code></td><td><code>GEMIL001234</code></td><td>John Kamau</td></tr>
                                                <tr><td><code>{{ $selectedClientAccessUser->user_name }}</code></td><td><code>GEMPPP0335</code></td><td></td></tr>
                                                <tr><td><code>other.user</code></td><td><code>GEMIL009999</code></td><td>Jane Doe</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="text-muted small mb-0">Each row can target a different user. Blank user column = selected user above. User matches username or email.</p>
                                </div>
                            </details>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="client-access-panel">
            <div class="client-access-panel-head">
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Assigned clients</h6>
            </div>
            <div class="table-responsive">
                <table class="settings-table mb-0">
                    <thead>
                        <tr>
                            <th>Policy number</th>
                            <th>Client name</th>
                            <th>Segment</th>
                            <th class="text-end" style="width:100px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clientAccessAssignments ?? [] as $assignment)
                        <tr>
                            <td><span class="client-access-policy">{{ $assignment->policy_number }}</span></td>
                            <td>{{ $assignment->client_label ?: '—' }}</td>
                            <td>
                                @if ($assignment->system)
                                <span class="badge bg-light text-dark border">{{ $segmentLabels[$assignment->system] ?? $assignment->system }}</span>
                                @else
                                <span class="text-muted small">Any</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form action="{{ route('settings.client-access.destroy', $assignment->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove access to {{ $assignment->policy_number }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove" {{ !($clientAccessReady ?? true) ? 'disabled' : '' }}>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4">
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size:2rem;opacity:.5;"></i>
                                    <p class="mb-0 mt-2">No clients assigned to {{ $selectedName }} yet.</p>
                                    <p class="small mb-0">Add a policy number above, then set their profile to <strong>Assigned clients only</strong>.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="client-access-panel">
            <div class="client-access-panel-body text-center py-5 text-muted">
                <i class="bi bi-person-check" style="font-size:3rem;opacity:.4;"></i>
                <p class="mt-3 mb-0 fw-semibold text-dark">Select a user to manage client access</p>
                <p class="small mb-0">Choose someone from the list on the left, or search by name.</p>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
.client-access-steps {
    display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem 1rem;
    padding: 1rem 1.25rem; background: #f8fafc; border: 1px solid var(--agile-border, #e2e8f0);
    border-radius: 12px;
}
.client-access-step { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: var(--agile-text, #1e293b); }
.client-access-step-num {
    width: 1.6rem; height: 1.6rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
    background: var(--agile-primary, #0E4385); color: #fff; font-size: 0.75rem; font-weight: 700;
}
.client-access-step-arrow { color: var(--agile-text-muted, #64748b); font-size: 0.85rem; }
.client-access-panel {
    background: #fff; border: 1px solid var(--agile-border, #e2e8f0); border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04); overflow: hidden;
}
.client-access-panel-head {
    padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; background: #fafbfc;
}
.client-access-panel-body { padding: 1.25rem; }
.client-access-user-list { max-height: 520px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.35rem; }
.client-access-user-item {
    display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
    padding: 0.75rem 0.875rem; border-radius: 10px; text-decoration: none; color: inherit;
    border: 1px solid transparent; transition: background .15s, border-color .15s;
}
.client-access-user-item:hover { background: var(--agile-primary-muted, #eef4fb); border-color: rgba(14, 67, 133, 0.12); color: inherit; }
.client-access-user-item.is-active { background: var(--agile-primary, #0E4385); border-color: var(--agile-primary, #0E4385); color: #fff; }
.client-access-user-item.is-active .client-access-user-sub { color: rgba(255,255,255,.75); }
.client-access-user-item.is-active .client-access-user-badge { background: rgba(255,255,255,.2); color: #fff; }
.client-access-user-name { display: block; font-weight: 600; font-size: 0.92rem; line-height: 1.3; }
.client-access-user-sub { display: block; font-size: 0.75rem; color: var(--agile-text-muted, #64748b); margin-top: 0.1rem; }
.client-access-user-badge {
    min-width: 1.75rem; height: 1.75rem; padding: 0 0.45rem; border-radius: 999px;
    display: inline-flex; align-items: center; justify-content: center;
    background: var(--agile-primary-muted, #eef4fb); color: var(--agile-primary, #0E4385);
    font-size: 0.75rem; font-weight: 700; flex-shrink: 0;
}
.client-access-tabs .nav-link { font-size: 0.9rem; font-weight: 500; color: var(--agile-text-muted, #64748b); border: none; border-bottom: 2px solid transparent; border-radius: 0; }
.client-access-tabs .nav-link.active { color: var(--agile-primary, #0E4385); border-bottom-color: var(--agile-primary, #0E4385); background: transparent; }
.client-access-policy {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.85rem; font-weight: 600; color: var(--agile-primary-dark, #0a3266);
    background: #f1f5f9; padding: 0.2rem 0.5rem; border-radius: 6px;
}
.client-access-template-box {
    padding: 1rem 1.125rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px;
}
.client-access-upload-zone {
    position: relative; text-align: center; padding: 2.5rem 1.5rem;
    border: 2px dashed #94a3b8; border-radius: 14px; background: #f8fafc;
    transition: border-color .2s, background .2s;
    cursor: pointer;
}
.client-access-upload-zone.is-dragover { border-color: var(--agile-primary, #0E4385); background: #eef4fb; }
.client-access-upload-zone.has-file { border-color: #22c55e; background: #f0fdf4; }
.client-access-upload-input {
    position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;
}
.client-access-upload-icon { font-size: 2.5rem; color: var(--agile-primary, #0E4385); line-height: 1; margin-bottom: .5rem; }
.client-access-upload-title { font-weight: 600; color: var(--agile-text, #1e293b); }
.client-access-upload-sub { font-size: 0.875rem; color: var(--agile-text-muted, #64748b); margin: 0; }
.client-access-upload-filename { display: inline-block; margin-top: .25rem; }
.client-access-template-table { background: #fff; font-size: 0.85rem; margin-bottom: 0; }
@media (max-width: 991px) {
    .client-access-steps { flex-direction: column; align-items: flex-start; }
    .client-access-step-arrow { display: none; }
}
</style>

@if ($selectedClientAccessUser ?? null)
<script>
(function() {
    var zone = document.getElementById('clientAccessDropZone');
    var input = document.getElementById('clientAccessCsvFile');
    var nameEl = document.getElementById('clientAccessFileName');
    var form = document.getElementById('clientAccessBulkForm');

    function showFile(file) {
        if (!file || !nameEl || !zone) return;
        nameEl.textContent = file.name + ' (' + Math.max(1, Math.round(file.size / 1024)) + ' KB)';
        zone.classList.add('has-file');
    }

    if (input) {
        input.addEventListener('change', function() {
            if (input.files && input.files[0]) showFile(input.files[0]);
        });
    }

    if (zone && input) {
        ['dragenter', 'dragover'].forEach(function(ev) {
            zone.addEventListener(ev, function(e) {
                e.preventDefault();
                zone.classList.add('is-dragover');
            });
        });
        ['dragleave', 'drop'].forEach(function(ev) {
            zone.addEventListener(ev, function(e) {
                e.preventDefault();
                zone.classList.remove('is-dragover');
            });
        });
        zone.addEventListener('drop', function(e) {
            var files = e.dataTransfer && e.dataTransfer.files;
            if (!files || !files.length) return;
            input.files = files;
            showFile(files[0]);
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            if (!input || !input.files || !input.files.length) {
                e.preventDefault();
                alert('Please choose a CSV file to upload.');
            }
        });
    }
})();
</script>
@endif
