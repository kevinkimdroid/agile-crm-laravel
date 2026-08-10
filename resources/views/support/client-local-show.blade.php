@extends('layouts.app')

@section('title', $client->fullName().' — Client')

@section('content')
<nav class="breadcrumb-nav mb-3">
    <a href="{{ route('support.customers') }}" class="text-muted small text-decoration-none">Clients</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">{{ $client->fullName() }}</span>
</nav>

@if (session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="app-page-title mb-1">{{ $client->fullName() }}</h1>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-info-subtle text-info-emphasis">Locally created</span>
            <span class="text-muted small font-monospace">{{ $client->policy_no }}</span>
            <span class="badge {{ ($client->status === 'A') ? 'bg-success' : 'bg-danger' }}">{{ \App\Models\Client::STATUSES[$client->status] ?? $client->status }}</span>
            <span class="text-muted small">• {{ \App\Models\Client::SYSTEMS[$client->system] ?? $client->system }}</span>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('support.clients.create-ticket', ['policy' => $client->policy_no]) }}" class="btn btn-sm btn-success">
            <i class="bi bi-ticket-perforated me-1"></i>Create ticket
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="app-card p-4 h-100">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">Contact</h6>
            <dl class="row mb-0">
                <dt class="col-5 text-muted small">Email</dt><dd class="col-7">{{ $client->email ?: '—' }}</dd>
                <dt class="col-5 text-muted small">Phone</dt><dd class="col-7">{{ $client->phone ?: '—' }}</dd>
                <dt class="col-5 text-muted small">Address</dt><dd class="col-7">{{ $client->address ?: '—' }}</dd>
                <dt class="col-5 text-muted small">City / Town</dt><dd class="col-7">{{ $client->city ?: '—' }}</dd>
                <dt class="col-5 text-muted small">Postal Code</dt><dd class="col-7">{{ $client->postal_code ?: '—' }}</dd>
            </dl>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="app-card p-4 h-100">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">KYC</h6>
            <dl class="row mb-0">
                <dt class="col-5 text-muted small">ID / Passport</dt><dd class="col-7 font-monospace">{{ $client->id_no ?: '—' }}</dd>
                <dt class="col-5 text-muted small">KRA PIN</dt><dd class="col-7 font-monospace">{{ $client->kra_pin ?: '—' }}</dd>
                <dt class="col-5 text-muted small">Date of Birth</dt><dd class="col-7">{{ $client->date_of_birth?->format('d M Y') ?: '—' }}</dd>
                <dt class="col-5 text-muted small">Gender</dt><dd class="col-7">{{ $client->gender ?: '—' }}</dd>
                <dt class="col-5 text-muted small">Occupation</dt><dd class="col-7">{{ $client->occupation ?: '—' }}</dd>
            </dl>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="app-card p-4 h-100">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">Policy</h6>
            <dl class="row mb-0">
                <dt class="col-5 text-muted small">Policy No.</dt><dd class="col-7 font-monospace">{{ $client->policy_no }}</dd>
                <dt class="col-5 text-muted small">Product</dt><dd class="col-7">{{ $client->product ?: '—' }}</dd>
                <dt class="col-5 text-muted small">Intermediary</dt><dd class="col-7">{{ $client->intermediary ?: '—' }}</dd>
                <dt class="col-5 text-muted small">Client Type</dt><dd class="col-7">{{ \App\Models\Client::SYSTEMS[$client->system] ?? $client->system }}</dd>
            </dl>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="app-card p-4 h-100">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">Record</h6>
            <dl class="row mb-0">
                <dt class="col-5 text-muted small">Created by</dt><dd class="col-7">{{ $client->created_by_name ?: '—' }}</dd>
                <dt class="col-5 text-muted small">Created</dt><dd class="col-7">{{ $client->created_at?->format('d M Y, H:i') }}</dd>
                <dt class="col-5 text-muted small">Source</dt><dd class="col-7">{{ ucfirst($client->source) }}</dd>
            </dl>
            @if($client->notes)
            <hr>
            <p class="text-muted small mb-1 fw-semibold">Notes</p>
            <p class="mb-0">{{ $client->notes }}</p>
            @endif
        </div>
    </div>
</div>
@endsection
