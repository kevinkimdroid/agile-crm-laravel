@extends('layouts.app')

@section('title', 'Preview assigned client access')

@section('content')
<style>
.rad-hero {
    background: linear-gradient(135deg, #0E4385 0%, #1d4ed8 55%, #2563eb 100%);
    border-radius: 16px;
    color: #fff;
    padding: 1.5rem 1.75rem;
    margin-bottom: 1.25rem;
}
.rad-card {
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    background: #fff;
    overflow: hidden;
}
.rad-card-head {
    padding: .9rem 1.1rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
    font-weight: 700;
}
.rad-card-body { padding: 1.1rem; }
.rad-step {
    display: flex; gap: .75rem; align-items: flex-start;
    padding: .75rem 0; border-bottom: 1px dashed #e5e7eb;
}
.rad-step:last-child { border-bottom: 0; }
.rad-step-num {
    width: 28px; height: 28px; border-radius: 50%;
    background: #0E4385; color: #fff; font-weight: 700; font-size: .8rem;
    display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.rad-pill-ok { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; border-radius:999px; padding:.15rem .65rem; font-size:.8rem; font-weight:600; }
.rad-pill-no { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; border-radius:999px; padding:.15rem .65rem; font-size:.8rem; font-weight:600; }
</style>

<nav class="breadcrumb-nav mb-3">
    <a href="{{ route('support') }}" class="text-muted small text-decoration-none">Support</a>
    <span class="text-muted mx-2">/</span>
    <a href="{{ route('settings.crm', ['section' => 'client-access']) }}" class="text-muted small text-decoration-none">Client Access</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Preview assigned access</span>
</nav>

<div class="rad-hero">
    <h1 class="h4 mb-1 fw-bold">See how exclusive client access works</h1>
    <p class="mb-0 opacity-90" style="max-width:680px">
        When a client is assigned to a user, <strong>only that user</strong> can see and open the record.
        Use this preview to experience the Clients list and the access-denied screen as an assigned-only user.
    </p>
</div>

@if (session('success'))
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if (session('info'))
<div class="alert alert-info alert-dismissible fade show"><i class="bi bi-info-circle me-1"></i>{{ session('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="rad-card h-100">
            <div class="rad-card-head"><i class="bi bi-list-ol me-2"></i>Steps</div>
            <div class="rad-card-body">
                <div class="rad-step">
                    <span class="rad-step-num">1</span>
                    <div>
                        <strong>Prepare sample clients</strong>
                        <div class="text-muted small">Creates clients assigned to you, plus other clients that are not assigned to you.</div>
                    </div>
                </div>
                <div class="rad-step">
                    <span class="rad-step-num">2</span>
                    <div>
                        <strong>Start assigned-only preview</strong>
                        <div class="text-muted small">This session behaves like a user with <em>Assigned clients only</em>.</div>
                    </div>
                </div>
                <div class="rad-step">
                    <span class="rad-step-num">3</span>
                    <div>
                        <strong>Open Clients</strong>
                        <div class="text-muted small">You only see clients assigned to you.</div>
                    </div>
                </div>
                <div class="rad-step">
                    <span class="rad-step-num">4</span>
                    <div>
                        <strong>Try a client assigned to someone else</strong>
                        <div class="text-muted small">You get a clear Access Denied message.</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <form action="{{ route('demo.restricted-access.seed') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-person-plus me-1"></i> Prepare sample clients
                        </button>
                    </form>
                    @if($demoActive)
                    <form action="{{ route('demo.restricted-access.stop') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-stop-circle me-1"></i> Exit preview
                        </button>
                    </form>
                    @else
                    <form action="{{ route('demo.restricted-access.start') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-shield-lock me-1"></i> Start assigned-only preview
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('support.customers') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-people me-1"></i> Open Clients
                    </a>
                </div>

                <div class="mt-3 small">
                    Status:
                    @if($demoActive)
                        <span class="rad-pill-no">Assigned-only preview ON</span>
                    @else
                        <span class="rad-pill-ok">Full access</span>
                    @endif
                    @if($profileLimited && !$demoActive)
                        <span class="rad-pill-no ms-1">Profile already assigned-only</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="rad-card mb-3">
            <div class="rad-card-head d-flex justify-content-between align-items-center">
                <span><i class="bi bi-check2-circle text-success me-2"></i>Assigned to you — visible</span>
                <span class="badge bg-success">{{ $allowedClients->count() }}</span>
            </div>
            <div class="rad-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th class="ps-3">Policy</th><th>Client</th><th>Product</th><th class="pe-3 text-end">Open</th></tr></thead>
                        <tbody>
                        @forelse($allowedClients as $c)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $c->policy_no }}</td>
                                <td>{{ $c->fullName() }}</td>
                                <td class="small text-muted">{{ $c->product }}</td>
                                <td class="pe-3 text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('support.clients.show', ['policy' => $c->policy_no]) }}">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No sample clients yet. Click <strong>Prepare sample clients</strong>.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="rad-card">
            <div class="rad-card-head d-flex justify-content-between align-items-center">
                <span><i class="bi bi-slash-circle text-danger me-2"></i>Not assigned to you — blocked</span>
                <span class="badge bg-danger">{{ $forbiddenClients->count() }}</span>
            </div>
            <div class="rad-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th class="ps-3">Policy</th><th>Client</th><th>Product</th><th class="pe-3 text-end">Try</th></tr></thead>
                        <tbody>
                        @forelse($forbiddenClients as $c)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $c->policy_no }}</td>
                                <td>{{ $c->fullName() }}</td>
                                <td class="small text-muted">{{ $c->product }}</td>
                                <td class="pe-3 text-end">
                                    <a class="btn btn-sm btn-outline-danger" href="{{ route('demo.restricted-access.attempt', ['policy' => $c->policy_no]) }}">
                                        <i class="bi bi-shield-exclamation me-1"></i>Open client
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No sample clients yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
