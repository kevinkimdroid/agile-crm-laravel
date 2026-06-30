@if (($profileFormMode ?? null) === 'create')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">Create Profile</h5>
        <p class="text-muted small mb-0">Define module privileges and client segment access for users on this profile.</p>
    </div>
    <a href="{{ route('settings.crm', ['section' => 'profiles']) }}" class="btn btn-outline-secondary btn-sm">Back to Profiles</a>
</div>
@include('settings.partials.profile-form-body')

@elseif (($profileFormMode ?? null) === 'edit')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">Profile — {{ $profile->profilename ?? '' }}</h5>
        <p class="text-muted small mb-0">Edit privileges of this profile.</p>
    </div>
    <a href="{{ route('settings.crm', ['section' => 'profiles']) }}" class="btn btn-outline-secondary btn-sm">Back to Profiles</a>
</div>
@include('settings.partials.profile-form-body')

@else
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">Profiles</h5>
        <p class="text-muted small mb-0">Manage user profiles, module privileges, and client segment access.</p>
    </div>
    <a href="{{ route('settings.crm', ['section' => 'profiles', 'action' => 'create']) }}" class="btn btn-primary-custom btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Create Profile
    </a>
</div>

@if ($profileFormError ?? null)
<div class="alert alert-danger">{{ $profileFormError }}</div>
@endif

<div class="row g-4">
    @forelse ($profiles ?? [] as $profile)
    <div class="col-md-6 col-lg-4">
        <div class="app-card h-100 overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 fw-bold">{{ $profile->profilename }}</h6>
                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ $profile->roles_count }} role(s)</span>
                </div>
                @if ($profile->description)
                    <p class="text-muted small mb-3">{{ Str::limit($profile->description, 60) }}</p>
                @endif
                <a href="{{ route('settings.crm', ['section' => 'profiles', 'action' => 'edit', 'profile' => $profile->profileid]) }}" class="btn btn-primary-custom btn-sm w-100">Edit Profile</a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="app-card overflow-hidden">
            <div class="card-body text-center py-5">
                <i class="bi bi-person-badge text-muted" style="font-size: 3rem;"></i>
                <h6 class="mt-3 mb-2">No profiles found</h6>
                <p class="text-muted mb-3">Create a profile to control module access and client types.</p>
                <a href="{{ route('settings.crm', ['section' => 'profiles', 'action' => 'create']) }}" class="btn btn-primary-custom btn-sm">Create Profile</a>
            </div>
        </div>
    </div>
    @endforelse
</div>
@endif
