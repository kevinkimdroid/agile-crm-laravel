@extends('layouts.app')

@section('title', $client->fullName().' — Client')

@section('content')
@php
    $clientName = $client->fullName();
    $clientPhone = $client->phone ?: null;
    $clientEmail = ($client->email && filter_var(trim((string) $client->email), FILTER_VALIDATE_EMAIL)) ? trim((string) $client->email) : null;
    $clientPolicy = $client->policy_no;
    $clientProduct = $client->product ?: '—';
    $lifeSystem = $client->system ?: 'individual';
    $lifeSystemLabel = \App\Models\Client::SYSTEMS[$lifeSystem] ?? $lifeSystem;
    $mpesaConfigured = app(\App\Services\MpesaStkPushService::class)->isConfigured();
    $mpesaSandboxSimulate = app(\App\Services\MpesaStkPushService::class)->isSandboxSimulate();
    $mpesaTransactions = ($mpesaConfigured && \Illuminate\Support\Facades\Schema::hasTable('mpesa_stk_transactions'))
        ? \App\Models\MpesaStkTransaction::query()->where('policy_number', $clientPolicy)->orderByDesc('id')->limit(8)->get()
        : collect();
    $initials = strtoupper(
        substr((string) ($client->first_name ?: $clientName), 0, 1)
        .substr((string) ($client->last_name ?: preg_replace('/^.*\s/', '', trim($clientName))), 0, 1)
    );
    $suggestedAmount = client_suggested_premium_amount($client);
@endphp

@include('support.partials.client-mpesa-styles')

@if (session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if (user_is_limited_to_assigned_clients())
<div class="alert mb-3" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;" role="status">
    <i class="bi bi-shield-check me-1"></i>
    <strong>Assigned to you.</strong> Only you can view this client while assigned-only access is enabled.
</div>
@endif

<div class="contact-detail-header client-profile-hero card contact-detail-card mb-4">
    <div class="card-body p-4">
        <nav class="mb-3 text-uppercase small client-profile-breadcrumb">
            <a href="{{ route('support.customers') }}" class="text-muted">Clients</a>
            <span class="text-muted mx-1">/</span>
            <a href="{{ route('support.customers') }}" class="text-muted">All</a>
            <span class="text-muted mx-1">/</span>
            <span class="text-dark">{{ Str::limit($clientName, 40) }}</span>
        </nav>
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-start gap-3 flex-grow-1">
                <div class="contact-avatar-lg">{{ $initials }}</div>
                <div class="flex-grow-1" style="min-width:220px">
                    <h1 class="page-title mb-2 client-hero-name">{{ $clientName }}</h1>
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                        @if($clientPhone)
                        <a href="tel:{{ tel_href($clientPhone) }}" class="client-hero-phone text-decoration-none">
                            <i class="bi bi-telephone-fill me-1"></i>{{ $clientPhone }}
                        </a>
                        @endif
                        <span class="clients-system-badge clients-system-{{ $lifeSystem }}">{{ $lifeSystemLabel }}</span>
                        <span class="client-hero-policy font-monospace">{{ $clientPolicy }}</span>
                        <span class="badge {{ ($client->status === 'A') ? 'bg-success' : 'bg-danger' }}">{{ \App\Models\Client::STATUSES[$client->status] ?? $client->status }}</span>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center client-hero-actions">
                @if($clientPhone)
                <a href="tel:{{ tel_href($clientPhone) }}" class="btn btn-sm btn-success"><i class="bi bi-telephone me-1"></i>Call</a>
                @endif
                @if($clientEmail)
                <a href="mailto:{{ $clientEmail }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-envelope me-1"></i>Email</a>
                @endif
                @if($clientPhone)
                <a href="{{ route('support.sms-notifier', ['phone' => $clientPhone, 'return_policy' => $clientPolicy]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat-dots me-1"></i>SMS</a>
                @endif
                <a href="{{ route('support.clients.create-ticket', ['policy' => $clientPolicy]) }}" class="btn btn-sm btn-success"><i class="bi bi-ticket-perforated me-1"></i>Create Ticket</a>
                <button type="button" class="btn btn-sm btn-outline-success {{ $mpesaConfigured ? 'mpesa-stk-trigger' : '' }}"
                    @if($mpesaConfigured) data-bs-toggle="modal" data-bs-target="#mpesaStkModal" @endif
                    @if(! $mpesaConfigured) disabled title="M-Pesa unavailable" @endif>
                    <i class="bi bi-phone me-1"></i>M-Pesa
                </button>
                <a href="{{ route('support.customers') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

@include('support.partials.client-page-toasts')

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card contact-detail-card mb-4 client-summary-personal">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary,#0E4385)">Personal information</h6>
                <div class="client-summary-personal-grid">
                    <div class="client-summary-field">
                        <span class="client-summary-label">Life assured</span>
                        <span class="client-summary-value client-summary-name">{{ $clientName }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Policy number</span>
                        <span class="client-summary-value font-monospace">{{ $clientPolicy }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Phone</span>
                        <span class="client-summary-value">
                            @if($clientPhone)<a href="tel:{{ tel_href($clientPhone) }}">{{ $clientPhone }}</a>@else — @endif
                        </span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Product</span>
                        <span class="client-summary-value">{{ $clientProduct }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Email</span>
                        <span class="client-summary-value">{{ $clientEmail ?: '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">System</span>
                        <span class="client-summary-value"><span class="clients-system-badge clients-system-{{ $lifeSystem }}">{{ $lifeSystemLabel }}</span></span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">ID / Passport</span>
                        <span class="client-summary-value font-monospace">{{ $client->id_no ?: '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">KRA PIN</span>
                        <span class="client-summary-value font-monospace">{{ $client->kra_pin ?: '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Date of birth</span>
                        <span class="client-summary-value">{{ $client->date_of_birth?->format('d M Y') ?: '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">City</span>
                        <span class="client-summary-value">{{ $client->city ?: '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card contact-detail-card mb-4 client-mpesa-summary-card mpesa-ui">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h6 class="text-uppercase small fw-bold mb-1" style="color:var(--agile-primary,#0E4385)">M-Pesa premium collection</h6>
                        <p class="text-muted small mb-0">Send an STK push to the client’s phone and track payment status in real time.</p>
                    </div>
                    <button type="button" class="btn btn-success {{ $mpesaConfigured ? 'mpesa-stk-trigger' : '' }}"
                        @if($mpesaConfigured) data-bs-toggle="modal" data-bs-target="#mpesaStkModal" @endif
                        @if(! $mpesaConfigured) disabled title="M-Pesa unavailable" @endif>
                        <i class="bi bi-phone me-1"></i>Collect via M-Pesa
                    </button>
                </div>
                @if($mpesaTransactions->isNotEmpty())
                <div class="mpesa-tx-list border-top pt-3 mt-3">
                    @foreach($mpesaTransactions->take(4) as $tx)
                    @php
                        $st = $tx->status;
                        $iconClass = match ($st) { 'success' => 'success', 'pending' => 'pending', 'cancelled' => 'cancelled', default => 'failed' };
                        $icon = match ($st) { 'success' => 'bi-check-lg', 'pending' => 'bi-hourglass-split', 'cancelled' => 'bi-x-lg', default => 'bi-exclamation-lg' };
                    @endphp
                    <div class="mpesa-tx-item">
                        <div class="mpesa-tx-icon {{ $iconClass }}"><i class="bi {{ $icon }}"></i></div>
                        <div class="mpesa-tx-body">
                            <div class="mpesa-tx-amount">KES {{ number_format((float) $tx->amount, 0) }}</div>
                            <div class="mpesa-tx-meta">{{ $tx->created_at?->format('d M Y, H:i') ?? '—' }} · {{ ucfirst((string) $st) }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="summary-empty-box py-3 text-center text-muted mt-3 border-top">
                    No M-Pesa payment prompts sent yet.
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">Quick actions</h6>
                <div class="d-flex flex-column gap-2">
                    @if($clientPhone)
                    <a href="tel:{{ tel_href($clientPhone) }}" class="btn btn-outline-primary"><i class="bi bi-telephone me-2"></i>Call</a>
                    <a href="{{ route('support.sms-notifier', ['phone' => $clientPhone, 'return_policy' => $clientPolicy]) }}" class="btn btn-outline-primary"><i class="bi bi-chat-dots me-2"></i>Send Text</a>
                    @endif
                    @if($clientEmail)
                    <a href="mailto:{{ $clientEmail }}" class="btn btn-outline-primary"><i class="bi bi-envelope me-2"></i>Send Email</a>
                    @endif
                    <a href="{{ route('support.clients.create-ticket', ['policy' => $clientPolicy]) }}" class="btn btn-outline-success"><i class="bi bi-ticket-perforated me-2"></i>Create Ticket</a>
                    <button type="button" class="btn btn-outline-success text-start {{ $mpesaConfigured ? 'mpesa-stk-trigger' : '' }}"
                        @if($mpesaConfigured) data-bs-toggle="modal" data-bs-target="#mpesaStkModal" @endif
                        @if(! $mpesaConfigured) disabled @endif>
                        <i class="bi bi-phone me-2"></i>Collect via M-Pesa
                    </button>
                </div>
            </div>
        </div>

        <div class="card contact-detail-card mb-4 client-summary-payments mpesa-ui">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">Payments</h6>
                <p class="text-muted small mb-3">Collect premium via M-Pesa STK push and track prompts sent to this client.</p>
                <button type="button" class="btn btn-success w-100 {{ $mpesaConfigured ? 'mpesa-stk-trigger' : '' }}"
                    @if($mpesaConfigured) data-bs-toggle="modal" data-bs-target="#mpesaStkModal" @endif
                    @if(! $mpesaConfigured) disabled title="M-Pesa unavailable" @endif>
                    <i class="bi bi-phone me-1"></i>Collect premium via M-Pesa
                </button>
            </div>
        </div>

        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">Record</h6>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Created by</dt><dd class="col-7">{{ $client->created_by_name ?: '—' }}</dd>
                    <dt class="col-5 text-muted">Created</dt><dd class="col-7">{{ $client->created_at?->format('d M Y, H:i') }}</dd>
                    <dt class="col-5 text-muted">Intermediary</dt><dd class="col-7">{{ $client->intermediary ?: '—' }}</dd>
                </dl>
                @if($client->notes)
                <hr>
                <p class="text-muted small mb-1 fw-semibold">Notes</p>
                <p class="mb-0 small">{{ $client->notes }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.client-profile-hero { border: 1px solid #e2e8f0; border-radius: 16px; }
.contact-avatar-lg {
    width: 72px; height: 72px; border-radius: 50%;
    background: var(--agile-primary, #0E4385); color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1.35rem; flex-shrink: 0;
}
.client-hero-name { color: var(--agile-primary, #0E4385); font-weight: 800; }
.client-hero-phone { font-weight: 600; color: #334155; }
.client-hero-policy { font-size: 0.82rem; color: #64748b; background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 999px; }
.client-hero-actions .btn { border-radius: 8px; font-weight: 600; }
.client-summary-personal-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.25rem; }
@media (max-width: 575.98px) { .client-summary-personal-grid { grid-template-columns: 1fr; } }
.client-summary-field { display: flex; flex-direction: column; gap: 0.2rem; }
.client-summary-label { font-size: 0.68rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; }
.client-summary-value { font-size: 0.95rem; font-weight: 600; color: #1e293b; }
.client-summary-name { font-size: 1.1rem; color: var(--agile-primary, #0E4385); }
.client-mpesa-summary-card, .client-summary-payments { border-color: #bbf7d0; background: linear-gradient(180deg, #f8fdf9 0%, #fff 70%); }
.summary-empty-box { border-radius: 10px; background: #f8fafc; }
</style>

@include('support.partials.client-mpesa-stk-modal', [
    'mpesaPolicyNumber' => $clientPolicy,
    'clientPhone' => $clientPhone,
    'defaultAmount' => $suggestedAmount,
    'mpesaConfigured' => $mpesaConfigured,
    'mpesaSandboxSimulate' => $mpesaSandboxSimulate,
])

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('mpesaStkModal');
    var phoneInput = document.getElementById('mpesa_phone');
    var amountInput = document.getElementById('mpesa_amount');
    @if($clientPhone)
    if (phoneInput && !phoneInput.value) phoneInput.value = @json($clientPhone);
    @endif
    @if($suggestedAmount)
    if (amountInput && !amountInput.value) amountInput.value = @json((int) $suggestedAmount);
    @endif
    document.querySelectorAll('.mpesa-stk-trigger').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (modalEl && window.bootstrap) {
                // Modal opened via data-bs-toggle
            }
        });
    });
});
</script>
@endpush
@endsection
