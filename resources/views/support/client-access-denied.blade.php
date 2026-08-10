@extends('layouts.app')

@section('title', 'Access Denied')

@section('content')
<style>
.denied-wrap {
    max-width: 640px;
    margin: 2rem auto;
    text-align: center;
}
.denied-icon {
    width: 88px; height: 88px; border-radius: 50%;
    background: #fef2f2; color: #b91c1c;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 2.4rem; margin-bottom: 1rem;
}
.denied-card {
    border: 1px solid #fecaca;
    background: #fff;
    border-radius: 16px;
    padding: 2rem 1.5rem;
    box-shadow: 0 10px 30px rgba(185, 28, 28, .08);
}
.denied-rule {
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 12px;
    padding: 1rem 1.1rem;
    text-align: left;
    margin-bottom: 1.25rem;
}
</style>

<div class="denied-wrap">
    <div class="denied-card">
        <div class="denied-icon"><i class="bi bi-shield-lock-fill"></i></div>
        <h1 class="h4 fw-bold mb-2">You cannot open this client</h1>
        <p class="text-muted mb-3">
            This client record is assigned to another user.
            <strong>Only the assigned user</strong> can view or work on it.
        </p>

        <div class="denied-rule small">
            <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i>How client access works</div>
            <ul class="mb-0 ps-3">
                <li>Clients are assigned to specific users in <strong>Settings → Client Access</strong>.</li>
                <li>If your profile is set to <strong>Assigned clients only</strong>, you only see clients assigned to you.</li>
                <li>Other users’ clients are hidden and blocked if opened by link.</li>
            </ul>
        </div>

        <div class="bg-light rounded-3 p-3 text-start small mb-4">
            <div><span class="text-muted">Policy:</span> <strong>{{ $policy }}</strong></div>
            @if(!empty($clientName))
            <div><span class="text-muted">Client:</span> <strong>{{ $clientName }}</strong></div>
            @endif
            @if(!empty($system))
            <div><span class="text-muted">Segment:</span> <strong>{{ $system }}</strong></div>
            @endif
        </div>

        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a href="{{ route('support.customers') }}" class="btn btn-primary">
                <i class="bi bi-people me-1"></i> Back to my clients
            </a>
            @if(!empty($demoMode))
            <form action="{{ route('demo.restricted-access.stop') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">Return to full access</button>
            </form>
            @elseif(Auth::guard('vtiger')->user()?->isAdministrator())
            <a href="{{ route('settings.crm', ['section' => 'client-access']) }}" class="btn btn-outline-secondary">
                Manage client assignments
            </a>
            @endif
        </div>
    </div>
</div>
@endsection
