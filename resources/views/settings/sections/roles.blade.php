<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">Roles</h5>
        <p class="text-muted small mb-0">Manage roles and link each role to a profile that controls module and client access.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('settings.crm', ['section' => 'profiles', 'action' => 'create']) }}" class="btn btn-primary-custom btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Create Profile
        </a>
        <a href="{{ route('settings.crm', ['section' => 'profiles']) }}" class="btn btn-outline-secondary btn-sm">Manage Profiles</a>
        <a href="{{ route('settings.crm', ['section' => 'users']) }}" class="btn btn-outline-secondary btn-sm">Manage Users</a>
    </div>
</div>

<div class="app-card overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="settings-table mb-0">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Profile</th>
                    <th class="text-center">Users</th>
                    <th style="min-width:280px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles ?? [] as $role)
                @php
                    $assignedProfile = $role->profiles->first();
                    $userCount = $roleUserCounts[$role->roleid] ?? 0;
                    $indent = max(0, (int) ($role->depth ?? 0));
                @endphp
                <tr>
                    <td>
                        <span style="padding-left: {{ $indent * 1.25 }}rem; display:inline-block;">
                            @if ($indent > 0)<span class="text-muted me-1">└</span>@endif
                            <strong>{{ $role->rolename }}</strong>
                        </span>
                    </td>
                    <td>
                        @if ($assignedProfile)
                            <a href="{{ route('settings.crm', ['section' => 'profiles', 'action' => 'edit', 'profile' => $assignedProfile->profileid]) }}" class="text-decoration-none">
                                {{ $assignedProfile->profilename }}
                            </a>
                        @else
                            <span class="text-warning small">No profile assigned</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $userCount }}</td>
                    <td>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @if ($assignedProfile)
                            <a href="{{ route('settings.crm', ['section' => 'profiles', 'action' => 'edit', 'profile' => $assignedProfile->profileid]) }}" class="btn btn-primary-custom btn-sm">
                                <i class="bi bi-grid-3x3-gap me-1"></i>Edit Privileges
                            </a>
                            @endif
                            <form action="{{ route('settings.roles.assign-profile', $role->roleid) }}" method="POST" class="d-flex align-items-center gap-2">
                                @csrf
                                <select name="profileid" class="form-select form-select-sm" style="min-width:160px" required>
                                    <option value="">Assign profile…</option>
                                    @foreach ($allProfiles ?? [] as $profileOption)
                                    <option value="{{ $profileOption->profileid }}" {{ $assignedProfile && (int) $assignedProfile->profileid === (int) $profileOption->profileid ? 'selected' : '' }}>
                                        {{ $profileOption->profilename }}
                                    </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-secondary btn-sm">Save</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-5 text-muted">No roles found in Vtiger.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<p class="text-muted small mb-0">
    <i class="bi bi-info-circle me-1"></i>
    Module privileges are managed on the <strong>Profile</strong>. For specific clients (policy numbers) per user, use
    <a href="{{ route('settings.crm', ['section' => 'client-access']) }}">Settings → Client Access</a>
    and set the profile to <strong>Assigned clients only</strong>.
</p>
