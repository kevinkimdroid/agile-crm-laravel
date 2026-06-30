@php
    $profileCancelUrl = $profileCancelUrl ?? route('settings.crm', ['section' => 'profiles']);
@endphp

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ ($isCreate ?? false) ? route('profiles.store') : route('profiles.update', $profile->profileid) }}" method="POST">
    @csrf
    @if(!($isCreate ?? false)) @method('PUT') @endif

    <div class="card profile-form-card mb-4">
        <div class="card-body p-4">
            <div class="row g-4 align-items-start">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Profile name <span class="text-danger">*</span></label>
                        <input type="text" name="profilename" class="form-control" value="{{ old('profilename', $profile->profilename ?? '') }}" maxlength="100" required>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2" maxlength="255" placeholder="e.g. Customer service team">{{ old('description', $profile->description ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="profile-form-hint">
                        <i class="bi bi-info-circle me-2"></i>
                        Add the profile name, then add details of the profile.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card profile-form-card mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-1">Edit privileges of this profile</h5>
            <p class="text-muted small mb-4">Control view, create, edit, and delete access for each module.</p>
            <div class="table-responsive">
                <table class="table table-bordered align-middle profile-modules-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Modules</th>
                            <th class="text-center" style="width:90px">View<br><input type="checkbox" class="form-check-input profile-select-col" data-col="view-vt"></th>
                            <th class="text-center" style="width:90px">Create<br><input type="checkbox" class="form-check-input profile-select-col" data-col="create-vt"></th>
                            <th class="text-center" style="width:90px">Edit<br><input type="checkbox" class="form-check-input profile-select-col" data-col="edit-vt"></th>
                            <th class="text-center" style="width:90px">Delete<br><input type="checkbox" class="form-check-input profile-select-col" data-col="delete-vt"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($moduleList ?? [] as $mod)
                        <tr>
                            <td><strong>{{ $mod['label'] }}</strong></td>
                            @foreach (['view' => 'view-vt', 'create' => 'create-vt', 'edit' => 'edit-vt', 'delete' => 'delete-vt'] as $perm => $colClass)
                            <td class="text-center">
                                <input type="hidden" name="modules[{{ $mod['tabid'] }}][{{ $perm }}]" value="0">
                                <input type="checkbox" class="form-check-input profile-perm-{{ $colClass }}" name="modules[{{ $mod['tabid'] }}][{{ $perm }}]" value="1" {{ ($mod[$perm] ?? false) ? 'checked' : '' }}>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card profile-form-card mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-1">Agile Craft modules</h5>
            <p class="text-muted small mb-4">Clients, Serve Client, broadcast, tools, and other app modules.</p>
            <div class="table-responsive">
                <table class="table table-bordered align-middle profile-modules-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Modules</th>
                            <th class="text-center" style="width:90px">View<br><input type="checkbox" class="form-check-input profile-select-col" data-col="view-app"></th>
                            <th class="text-center" style="width:90px">Create<br><input type="checkbox" class="form-check-input profile-select-col" data-col="create-app"></th>
                            <th class="text-center" style="width:90px">Edit<br><input type="checkbox" class="form-check-input profile-select-col" data-col="edit-app"></th>
                            <th class="text-center" style="width:90px">Delete<br><input type="checkbox" class="form-check-input profile-select-col" data-col="delete-app"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($appModuleList ?? [] as $mod)
                        <tr>
                            <td><strong>{{ $mod['label'] }}</strong></td>
                            @foreach (['view' => 'view-app', 'create' => 'create-app', 'edit' => 'edit-app', 'delete' => 'delete-app'] as $perm => $colClass)
                            <td class="text-center">
                                <input type="hidden" name="app_modules[{{ $mod['key'] }}][{{ $perm }}]" value="0">
                                <input type="checkbox" class="form-check-input profile-perm-{{ $colClass }}" name="app_modules[{{ $mod['key'] }}][{{ $perm }}]" value="1" {{ ($mod[$perm] ?? false) ? 'checked' : '' }}>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card profile-form-card mb-4 border-start border-4 border-primary">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-1">Client segment access</h5>
            <p class="text-muted small mb-3">Choose which client types users on this profile can open on <strong>Support → Clients</strong>. Leave all checked for full access.</p>
            <div class="row g-3">
                @php
                    $allSegments = array_keys($segmentLabels ?? []);
                    $selectedSegments = old('client_segments', $clientSegments ?? $allSegments);
                @endphp
                @foreach ($segmentLabels ?? [] as $segmentKey => $segmentLabel)
                <div class="col-md-6 col-lg-3">
                    <label class="profile-segment-option">
                        <input type="checkbox" class="form-check-input me-2" name="client_segments[]" value="{{ $segmentKey }}" {{ in_array($segmentKey, $selectedSegments, true) ? 'checked' : '' }}>
                        <span>{{ $segmentLabel }}</span>
                    </label>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card profile-form-card mb-4 border-start border-4 border-warning">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-1">Specific client access</h5>
            <p class="text-muted small mb-3">Choose whether users see every client in their allowed segments, or only clients explicitly assigned per user.</p>
            @php
                $selectedAccessMode = old('client_access_mode', $clientAccessMode ?? \App\Services\ProfileAccessService::CLIENT_ACCESS_ALL);
            @endphp
            <div class="vstack gap-2">
                <label class="profile-segment-option">
                    <input type="radio" class="form-check-input me-2" name="client_access_mode" value="{{ \App\Services\ProfileAccessService::CLIENT_ACCESS_ALL }}" {{ $selectedAccessMode !== \App\Services\ProfileAccessService::CLIENT_ACCESS_ASSIGNED_ONLY ? 'checked' : '' }}>
                    <span><strong>All clients</strong> in allowed segments</span>
                </label>
                <label class="profile-segment-option">
                    <input type="radio" class="form-check-input me-2" name="client_access_mode" value="{{ \App\Services\ProfileAccessService::CLIENT_ACCESS_ASSIGNED_ONLY }}" {{ $selectedAccessMode === \App\Services\ProfileAccessService::CLIENT_ACCESS_ASSIGNED_ONLY ? 'checked' : '' }}>
                    <span><strong>Assigned clients only</strong> — assign policy numbers per user in Client Access</span>
                </label>
            </div>
            <p class="text-muted small mt-3 mb-0">
                <a href="{{ route('settings.crm', ['section' => 'client-access']) }}">Manage client assignments by user</a>
            </p>
        </div>
    </div>

    <div class="card profile-form-card mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Field and Tool Privileges</h5>
            <div class="d-flex flex-wrap gap-4 mb-4 small">
                <span><span class="profile-legend-dot profile-legend-invisible"></span> Invisible</span>
                <span><span class="profile-legend-dot profile-legend-readonly"></span> Read only</span>
                <span><span class="profile-legend-dot profile-legend-write"></span> Write</span>
            </div>
            @foreach ($moduleList ?? [] as $mod)
                @if (!empty($mod['fields']))
                <div class="mb-4">
                    <h6 class="fw-semibold mb-2">Fields — {{ $mod['label'] }}</h6>
                    <div class="row g-2">
                        @foreach ($mod['fields'] as $field)
                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="profile-field-label">{{ $field['label'] }}</span>
                                <div class="btn-group btn-group-sm" role="group">
                                    <input type="radio" class="btn-check" name="fields[{{ $mod['tabid'] }}_{{ $field['fieldid'] }}]" value="invisible" id="f{{ $mod['tabid'] }}_{{ $field['fieldid'] }}_inv" {{ ($field['access'] ?? '') === 'invisible' ? 'checked' : '' }}>
                                    <label for="f{{ $mod['tabid'] }}_{{ $field['fieldid'] }}_inv" class="btn btn-outline-secondary rounded-circle p-0 profile-legend-invisible" style="width:28px;height:28px;" title="Invisible"></label>
                                    <input type="radio" class="btn-check" name="fields[{{ $mod['tabid'] }}_{{ $field['fieldid'] }}]" value="readonly" id="f{{ $mod['tabid'] }}_{{ $field['fieldid'] }}_ro" {{ ($field['access'] ?? '') === 'readonly' ? 'checked' : '' }}>
                                    <label for="f{{ $mod['tabid'] }}_{{ $field['fieldid'] }}_ro" class="btn btn-outline-secondary rounded-circle p-0 profile-legend-readonly" style="width:28px;height:28px;" title="Read only"></label>
                                    <input type="radio" class="btn-check" name="fields[{{ $mod['tabid'] }}_{{ $field['fieldid'] }}]" value="write" id="f{{ $mod['tabid'] }}_{{ $field['fieldid'] }}_w" {{ ($field['access'] ?? 'write') === 'write' ? 'checked' : '' }}>
                                    <label for="f{{ $mod['tabid'] }}_{{ $field['fieldid'] }}_w" class="btn btn-outline-secondary rounded-circle p-0 profile-legend-write" style="width:28px;height:28px;" title="Write"></label>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach
            <h6 class="fw-semibold mb-2 mt-2">Tools</h6>
            <div class="d-flex flex-wrap gap-4">
                @foreach (['Import', 'Export', 'DuplicatesHandling'] as $tool)
                <label class="d-inline-flex align-items-center gap-2">
                    <input type="hidden" name="tools[{{ $tool }}]" value="0">
                    <input type="checkbox" name="tools[{{ $tool }}]" value="1" {{ ($tools[$tool] ?? false) ? 'checked' : '' }}>
                    {{ $tool }}
                </label>
                @endforeach
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-2">
        <button type="submit" class="btn btn-primary-custom">Save</button>
        <a href="{{ $profileCancelUrl }}" class="btn btn-link text-danger text-decoration-none">Cancel</a>
    </div>
</form>

<style>
.profile-form-card { border-radius: 12px; border: 1px solid var(--agile-border, #e2e8f0); box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); }
.profile-form-hint { background: #fff8e6; border: 1px solid #fde68a; border-radius: 10px; padding: 1rem; font-size: 0.88rem; color: #92400e; }
.profile-modules-table th, .profile-modules-table td { vertical-align: middle; }
.profile-legend-dot { width: 14px; height: 14px; border-radius: 50%; display: inline-block; margin-right: 0.35rem; vertical-align: middle; }
.profile-legend-invisible { background: #1e293b !important; }
.profile-legend-readonly { background: #f59e0b !important; }
.profile-legend-write { background: #eab308 !important; }
.profile-field-label { font-size: 0.9rem; min-width: 120px; }
.profile-segment-option { display: flex; align-items: center; padding: 0.75rem 1rem; border: 1px solid var(--agile-border, #e2e8f0); border-radius: 10px; background: #fafbfc; cursor: pointer; margin: 0; }
.profile-segment-option:hover { border-color: var(--agile-primary, #0E4385); background: #f8fbff; }
</style>

<script>
document.querySelectorAll('.profile-select-col').forEach(function(header) {
    header.addEventListener('change', function() {
        var col = header.dataset.col;
        document.querySelectorAll('.profile-perm-' + col).forEach(function(box) {
            box.checked = header.checked;
        });
    });
});
</script>
