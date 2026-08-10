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
</style>

<div class="denied-wrap">
    <div class="denied-card">
        <div class="denied-icon"><i class="bi bi-shield-lock-fill"></i></div>
        <h1 class="h4 fw-bold mb-2">Access denied</h1>
        <p class="text-muted mb-3">
            Your role is restricted to <strong>assigned clients only</strong>.
            You are not allowed to open this customer record.
        </p>
        <div class="bg-light rounded-3 p-3 text-start small mb-4">
            <div><span class="text-muted">Policy:</span> <strong>{{ $policy }}</strong></div>
            @if(!empty($clientName))
            <div><span class="text-muted">Client:</span> <strong>{{ $clientName }}</strong></div>
            @endif
            @if(!empty($system))
            <div><span class="text-muted">Segment:</span> <strong>{{ $system }}</strong></div>
            @endif
        </div>

        @if(!empty($demoMode))
        <div class="alert alert-warning text-start small mb-4">
            <strong>Demo mode is ON.</strong> This is the expected result when attempting a DEMO-X (unassigned) client.
            Allowed clients use the <code>DEMO-R-</code> prefix.
        </div>
        @endif

        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a href="{{ route('support.customers') }}" class="btn btn-primary">
                <i class="bi bi-people me-1"></i> Back to Clients
            </a>
            @if(!empty($demoMode))
            <a href="{{ route('demo.restricted-access') }}" class="btn btn-outline-secondary">
                Demo walkthrough
            </a>
            <form action="{{ route('demo.restricted-access.stop') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger">Stop restricted mode</button>
            </form>
            @else
            <a href="{{ route('settings.crm', ['section' => 'client-access']) }}" class="btn btn-outline-secondary">
                Client Access settings
            </a>
            @endif
        </div>
    </div>
</div>
@endsection
