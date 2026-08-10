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
    $mpesaConfigured = $mpesaConfigured ?? app(\App\Services\MpesaStkPushService::class)->isConfigured();
    $mpesaSandboxSimulate = $mpesaSandboxSimulate ?? app(\App\Services\MpesaStkPushService::class)->isSandboxSimulate();
    $mpesaTransactions = $mpesaTransactions ?? collect();
    $tab = $activeTab ?? 'summary';
    $clientShowBase = $clientShowBase ?? array_filter([
        'policy' => $clientPolicy,
        'system' => $client->system ?: null,
        'from' => ($fromServeClient ?? false) ? 'serve-client' : null,
    ]);
    $clientTabUrl = function (string $tabName) use ($clientShowBase) {
        $params = $clientShowBase;
        if ($tabName !== 'summary') {
            $params['tab'] = $tabName;
        }
        return route('support.clients.show', $params);
    };
    $initials = strtoupper(
        substr((string) ($client->first_name ?: $clientName), 0, 1)
        .substr((string) ($client->last_name ?: preg_replace('/^.*\s/', '', trim($clientName))), 0, 1)
    );
    $suggestedAmount = client_suggested_premium_amount($client);
    $emailClientRouteParams = array_filter([
        'policy' => $clientPolicy,
        'email' => $clientEmail,
        'client_name' => $clientName,
        'contact_id' => ($contact ?? null)?->contactid,
    ]);
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
            @if($fromServeClient ?? false)
            <a href="{{ route('support.serve-client', ['search' => $clientPolicy]) }}" class="text-muted">Serve Client</a>
            @else
            <a href="{{ route('support.customers') }}" class="text-muted">Clients</a>
            <span class="text-muted mx-1">/</span>
            <a href="{{ route('support.customers') }}" class="text-muted">All</a>
            @endif
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
                <a href="{{ route('support.email-client', array_merge($emailClientRouteParams, ['return_policy' => $clientPolicy])) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-envelope me-1"></i>Email</a>
                @endif
                @if($clientPhone)
                <a href="{{ route('support.sms-notifier', array_filter(['phone' => $clientPhone, 'return_policy' => $clientPolicy, 'contact_id' => ($contact ?? null)?->contactid])) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat-dots me-1"></i>SMS</a>
                @endif
                <a href="{{ route('support.clients.create-ticket', ['policy' => $clientPolicy]) }}" class="btn btn-sm btn-success"><i class="bi bi-ticket-perforated me-1"></i>Create Ticket</a>
                <button type="button" class="btn btn-sm btn-outline-success {{ $mpesaConfigured ? 'mpesa-stk-trigger' : '' }}"
                    @if($mpesaConfigured) data-bs-toggle="modal" data-bs-target="#mpesaStkModal" @endif
                    @if(! $mpesaConfigured) disabled title="M-Pesa unavailable" @endif>
                    <i class="bi bi-phone me-1"></i>M-Pesa
                </button>
                <a href="{{ ($fromServeClient ?? false) ? route('support.serve-client', ['search' => $clientPolicy]) : route('support.customers') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

@include('support.partials.client-page-toasts')

<div class="card contact-detail-card client-module-tabs-shell mb-4">
    <div class="card-body py-2 px-2">
        <ul class="nav contact-module-tabs client-module-tabs mb-0">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'summary' ? 'active' : '' }}" href="{{ $clientTabUrl('summary') }}">Summary</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'details' ? 'active' : '' }}" href="{{ $clientTabUrl('details') }}">Details</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'updates' ? 'active' : '' }}" href="{{ $clientTabUrl('updates') }}">
                    Updates
                    @if((($activitiesCount ?? 0) + ($commentsCount ?? 0)) > 0)
                    <span class="badge bg-primary ms-1">{{ ($activitiesCount ?? 0) + ($commentsCount ?? 0) }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ in_array($tab, ['premiums', 'mpesa'], true) ? 'active' : '' }}" href="{{ $clientTabUrl('premiums') }}" title="M-Pesa & premiums">
                    <i class="bi bi-phone me-1"></i>M-Pesa
                    @if(($mpesaCount ?? 0) > 0)
                    <span class="badge bg-success ms-1">{{ $mpesaCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item client-module-tabs-divider" aria-hidden="true"></li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'tickets' ? 'active' : '' }}" href="{{ $clientTabUrl('tickets') }}" title="Tickets">
                    <i class="bi bi-ticket-perforated"></i>
                    @if(($ticketsCount ?? 0) > 0)<span class="badge bg-primary ms-1">{{ $ticketsCount }}</span>@endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'emails' ? 'active' : '' }}" href="{{ $clientTabUrl('emails') }}" title="Emails">
                    <i class="bi bi-envelope"></i>
                    @if(($emailsCount ?? 0) > 0)<span class="badge bg-primary ms-1">{{ $emailsCount }}</span>@endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'documents' ? 'active' : '' }}" href="{{ $clientTabUrl('documents') }}" title="Documents">
                    <i class="bi bi-folder"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'policies' ? 'active' : '' }}" href="{{ $clientTabUrl('policies') }}" title="Policies">
                    <i class="bi bi-box"></i>
                    <span class="badge bg-primary ms-1">1</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'calls' ? 'active' : '' }}" href="{{ $clientTabUrl('calls') }}" title="Calls"><i class="bi bi-telephone"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'sms' ? 'active' : '' }}" href="{{ $clientTabUrl('sms') }}" title="SMS">
                    <i class="bi bi-chat-dots"></i>
                    @if(($smsCount ?? 0) > 0)<span class="badge bg-primary ms-1">{{ $smsCount }}</span>@endif
                </a>
            </li>
        </ul>
    </div>
</div>

@if($tab === 'summary')
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
                </div>
                <div class="mt-3 pt-3 border-top">
                    <a href="{{ $clientTabUrl('details') }}" class="btn btn-sm btn-outline-primary">View full details</a>
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
                <div class="mt-3">
                    <a href="{{ $clientTabUrl('premiums') }}" class="btn btn-sm btn-outline-secondary">Open M-Pesa tab</a>
                </div>
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
                    <a href="{{ route('support.sms-notifier', array_filter(['phone' => $clientPhone, 'return_policy' => $clientPolicy, 'contact_id' => ($contact ?? null)?->contactid])) }}" class="btn btn-outline-primary"><i class="bi bi-chat-dots me-2"></i>Send Text</a>
                    @endif
                    @if($clientEmail)
                    <a href="{{ route('support.email-client', array_merge($emailClientRouteParams, ['return_policy' => $clientPolicy])) }}" class="btn btn-outline-primary"><i class="bi bi-envelope me-2"></i>Send Email</a>
                    @endif
                    <a href="{{ route('support.clients.create-ticket', ['policy' => $clientPolicy]) }}" class="btn btn-outline-success"><i class="bi bi-ticket-perforated me-2"></i>Create Ticket</a>
                    <button type="button" class="btn btn-outline-success text-start {{ $mpesaConfigured ? 'mpesa-stk-trigger' : '' }}"
                        @if($mpesaConfigured) data-bs-toggle="modal" data-bs-target="#mpesaStkModal" @endif
                        @if(! $mpesaConfigured) disabled @endif>
                        <i class="bi bi-phone me-2"></i>Collect via M-Pesa
                    </button>
                    @if($contact ?? null)
                    <a href="{{ route('contacts.show', $contact->contactid) }}" class="btn btn-outline-secondary"><i class="bi bi-person me-2"></i>View CRM Contact</a>
                    @endif
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

@elseif($tab === 'details')
<div class="card contact-detail-card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary,#0E4385)">Client details</h6>
        <div class="client-summary-personal-grid">
            <div class="client-summary-field">
                <span class="client-summary-label">Full name</span>
                <span class="client-summary-value client-summary-name">{{ $clientName }}</span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Policy number</span>
                <span class="client-summary-value font-monospace">{{ $clientPolicy }}</span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Product</span>
                <span class="client-summary-value">{{ $clientProduct }}</span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">System</span>
                <span class="client-summary-value"><span class="clients-system-badge clients-system-{{ $lifeSystem }}">{{ $lifeSystemLabel }}</span></span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Status</span>
                <span class="client-summary-value">{{ \App\Models\Client::STATUSES[$client->status] ?? $client->status }}</span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Phone</span>
                <span class="client-summary-value">{{ $clientPhone ?: '—' }}</span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Email</span>
                <span class="client-summary-value">{{ $clientEmail ?: '—' }}</span>
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
                <span class="client-summary-label">Gender</span>
                <span class="client-summary-value">{{ $client->gender ?: '—' }}</span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Occupation</span>
                <span class="client-summary-value">{{ $client->occupation ?: '—' }}</span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Address</span>
                <span class="client-summary-value">{{ $client->address ?: '—' }}</span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">City</span>
                <span class="client-summary-value">{{ $client->city ?: '—' }}</span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Postal code</span>
                <span class="client-summary-value">{{ $client->postal_code ?: '—' }}</span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Intermediary</span>
                <span class="client-summary-value">{{ $client->intermediary ?: '—' }}</span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Created by</span>
                <span class="client-summary-value">{{ $client->created_by_name ?: '—' }}</span>
            </div>
            <div class="client-summary-field">
                <span class="client-summary-label">Created</span>
                <span class="client-summary-value">{{ $client->created_at?->format('d M Y, H:i') ?: '—' }}</span>
            </div>
        </div>
        @if($client->notes)
        <div class="mt-4 pt-3 border-top">
            <span class="client-summary-label d-block mb-1">Notes</span>
            <p class="mb-0">{{ $client->notes }}</p>
        </div>
        @endif
    </div>
</div>

@elseif($tab === 'updates')
<div class="card contact-detail-card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small fw-bold text-muted mb-3">Updates</h6>
        @if($client->notes)
        <div class="mb-4">
            <p class="small text-muted mb-1 fw-semibold">Client notes</p>
            <p class="mb-0">{{ $client->notes }}</p>
        </div>
        @endif
        @if(($activities ?? collect())->isNotEmpty())
        <ul class="list-unstyled mb-0">
            @foreach($activities as $act)
            <li class="py-2 border-bottom">
                <strong>{{ $act->subject ?? 'Untitled' }}</strong>
                <span class="badge bg-secondary ms-1">{{ $act->activitytype ?? 'Task' }}</span>
                <p class="text-muted small mb-0">{{ $act->date_start ?? '' }}</p>
            </li>
            @endforeach
        </ul>
        @else
        <div class="summary-empty-box py-5 text-center text-muted">
            <i class="bi bi-chat-left-text opacity-50 d-block mb-2 fs-3"></i>
            No updates yet for this client.
            @if($contact ?? null)
            <div class="mt-3">
                <a href="{{ route('activities.create', [
                    'type' => 'Task',
                    'related_to' => $contact->contactid,
                    'lock_related' => 1,
                    'return_to' => $clientTabUrl('updates'),
                ]) }}" class="btn btn-sm btn-outline-primary">Add task</a>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>

@elseif($tab === 'premiums')
<div class="card contact-detail-card mb-4 client-mpesa-summary-card mpesa-ui">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h6 class="text-uppercase small fw-bold mb-1" style="color:var(--agile-primary,#0E4385)">M-Pesa premium collection</h6>
                <p class="text-muted small mb-0">Policy <code>{{ $clientPolicy }}</code> — sandbox STK for POC.</p>
            </div>
            <button type="button" class="btn btn-success {{ $mpesaConfigured ? 'mpesa-stk-trigger' : '' }}"
                @if($mpesaConfigured) data-bs-toggle="modal" data-bs-target="#mpesaStkModal" @endif
                @if(! $mpesaConfigured) disabled @endif>
                <i class="bi bi-phone me-1"></i>Collect via M-Pesa
            </button>
        </div>
        @if($mpesaTransactions->isNotEmpty())
        <div class="mpesa-tx-list border-top pt-3">
            @foreach($mpesaTransactions as $tx)
            @php
                $st = $tx->status;
                $iconClass = match ($st) { 'success' => 'success', 'pending' => 'pending', 'cancelled' => 'cancelled', default => 'failed' };
                $icon = match ($st) { 'success' => 'bi-check-lg', 'pending' => 'bi-hourglass-split', 'cancelled' => 'bi-x-lg', default => 'bi-exclamation-lg' };
            @endphp
            <div class="mpesa-tx-item">
                <div class="mpesa-tx-icon {{ $iconClass }}"><i class="bi {{ $icon }}"></i></div>
                <div class="mpesa-tx-body">
                    <div class="mpesa-tx-amount">KES {{ number_format((float) $tx->amount, 0) }}</div>
                    <div class="mpesa-tx-meta">{{ $tx->created_at?->format('d M Y, H:i') ?? '—' }} · {{ ucfirst((string) $st) }} · {{ $tx->phone ?? '' }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="summary-empty-box py-5 text-center text-muted border-top">
            No M-Pesa payment prompts sent yet.
        </div>
        @endif
    </div>
</div>

@elseif($tab === 'tickets')
<div class="card contact-detail-card mb-4">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Tickets</h6>
            <a href="{{ route('support.clients.create-ticket', ['policy' => $clientPolicy]) }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Create Ticket
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase fw-bold">Ticket</th>
                        <th class="small text-uppercase fw-bold">Title</th>
                        <th class="small text-uppercase fw-bold">Status</th>
                        <th class="small text-uppercase fw-bold">Priority</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets ?? [] as $ticket)
                    <tr>
                        <td class="font-monospace">
                            <a href="{{ route('tickets.show', $ticket->ticketid ?? $ticket->id) }}">{{ $ticket->ticket_no ?? $ticket->ticketid ?? '—' }}</a>
                        </td>
                        <td>{{ $ticket->title ?? $ticket->ticket_title ?? '—' }}</td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ $ticket->status ?? '—' }}</span></td>
                        <td>{{ $ticket->priority ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">No tickets for this client yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@elseif($tab === 'emails')
<div class="card contact-detail-card mb-4">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Emails</h6>
            @if($clientEmail || ($contact ?? null))
            <a href="{{ route('support.email-client', array_merge($emailClientRouteParams, ['return_policy' => $clientPolicy])) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-envelope me-1"></i>Compose email
            </a>
            @endif
        </div>
        <div class="p-5 text-center text-muted">
            No emails logged for this client yet.
        </div>
    </div>
</div>

@elseif($tab === 'documents')
<div class="card contact-detail-card mb-4">
    <div class="card-body p-5 text-center text-muted">
        <i class="bi bi-folder opacity-50 d-block mb-2 fs-3"></i>
        No documents uploaded for this client yet.
    </div>
</div>

@elseif($tab === 'policies')
<div class="card contact-detail-card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small fw-bold text-muted mb-3">Policies</h6>
        <div class="list-group list-group-flush">
            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold font-monospace">{{ $clientPolicy }}</div>
                    <div class="text-muted small">{{ $clientProduct }} · {{ $lifeSystemLabel }}</div>
                </div>
                <span class="badge {{ ($client->status === 'A') ? 'bg-success' : 'bg-danger' }}">{{ \App\Models\Client::STATUSES[$client->status] ?? $client->status }}</span>
            </div>
        </div>
    </div>
</div>

@elseif($tab === 'calls')
<div class="card contact-detail-card mb-4">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Calls (PBX)</h6>
            @if($clientPhone)
            <a href="tel:{{ tel_href($clientPhone) }}" class="btn btn-success btn-sm"><i class="bi bi-telephone-outbound-fill me-1"></i>Call</a>
            @endif
        </div>
        <div class="p-5 text-center text-muted">No PBX calls found for this client.</div>
    </div>
</div>

@elseif($tab === 'sms')
<div class="card contact-detail-card mb-4">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">SMS sent</h6>
            @if($clientPhone)
            <a href="{{ route('support.sms-notifier', array_filter(['phone' => $clientPhone, 'return_policy' => $clientPolicy, 'contact_id' => ($contact ?? null)?->contactid])) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-send-fill me-1"></i>Send SMS</a>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase fw-bold">Date</th>
                        <th class="small text-uppercase fw-bold">To</th>
                        <th class="small text-uppercase fw-bold">Message</th>
                        <th class="small text-uppercase fw-bold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($smsLogs ?? [] as $log)
                    <tr>
                        <td class="text-nowrap">{{ optional($log->sent_at)->format('d M Y H:i') ?: optional($log->created_at)->format('d M Y H:i') ?: '—' }}</td>
                        <td class="font-monospace">{{ $log->phone ?? '—' }}</td>
                        <td><span class="text-muted">{{ Str::limit($log->message ?? '', 80) }}</span></td>
                        <td>
                            @if(($log->status ?? '') === 'sent')
                            <span class="badge bg-success bg-opacity-10 text-success">Sent</span>
                            @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ ucfirst((string) ($log->status ?? '—')) }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">No SMS sent to this client yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<style>
.client-profile-hero {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 55%, #f0f6fc 100%);
    box-shadow: 0 4px 24px rgba(14, 67, 133, 0.08);
    border: 1px solid #e2e8f0; border-radius: 16px;
}
.client-profile-breadcrumb a { text-decoration: none; }
.contact-avatar-lg {
    width: 84px; height: 84px; border-radius: 20px;
    background: linear-gradient(145deg, #1A468A 0%, #0E4385 100%);
    color: #fff; display: inline-flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1.35rem; flex-shrink: 0;
    box-shadow: 0 8px 20px rgba(14, 67, 133, 0.25);
}
.client-hero-name { color: var(--agile-primary, #0E4385); font-weight: 800; }
.client-hero-phone { font-weight: 600; color: #334155; }
.client-hero-policy { font-size: 0.82rem; color: #64748b; background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 999px; }
.client-hero-actions .btn { border-radius: 8px; font-weight: 600; }
.client-module-tabs-shell { border-radius: 16px; box-shadow: 0 2px 12px rgba(14, 67, 133, 0.05); overflow: hidden; }
.client-module-tabs { display: flex; flex-direction: row; flex-wrap: wrap; align-items: center; gap: 0.15rem; border: none; }
.client-module-tabs .nav-item.client-module-tabs-divider {
    width: 1px; height: 1.75rem; margin: 0 0.35rem; padding: 0;
    background: #e2e8f0; align-self: center; pointer-events: none; flex: 0 0 1px;
}
@media (max-width: 767.98px) { .client-module-tabs .nav-item.client-module-tabs-divider { display: none; } }
.contact-module-tabs { border-bottom: none; }
.contact-module-tabs .nav-link {
    color: #64748b; font-weight: 500; padding: 0.7rem 1rem; border: none; border-radius: 10px; margin-bottom: 0;
}
.contact-module-tabs .nav-link:hover { color: #0E4385; background: rgba(14, 67, 133, 0.06); }
.contact-module-tabs .nav-link.active {
    color: #fff; background: #0E4385; box-shadow: 0 4px 12px rgba(14, 67, 133, 0.22);
}
.contact-module-tabs .nav-link.active .badge { background: rgba(255,255,255,0.25) !important; color: #fff !important; }
.contact-detail-card { border-radius: 16px; border: 1px solid rgba(14, 67, 133, 0.12); box-shadow: 0 2px 12px rgba(14, 67, 133, 0.04); }
.client-summary-personal-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.25rem; }
@media (max-width: 575.98px) { .client-summary-personal-grid { grid-template-columns: 1fr; } }
.client-summary-field { display: flex; flex-direction: column; gap: 0.2rem; }
.client-summary-label { font-size: 0.68rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; }
.client-summary-value { font-size: 0.95rem; font-weight: 600; color: #1e293b; }
.client-summary-name { font-size: 1.1rem; color: var(--agile-primary, #0E4385); }
.client-mpesa-summary-card, .client-summary-payments { border-color: #bbf7d0; background: linear-gradient(180deg, #f8fdf9 0%, #fff 70%); }
.summary-empty-box { border-radius: 10px; background: #f8fafc; }
.clients-system-badge { display: inline-block; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 999px; }
.clients-system-badge.clients-system-group { background: #ccfbf1; color: #0f766e; }
.clients-system-badge.clients-system-individual { background: #e0e7ff; color: #4338ca; }
.clients-system-badge.clients-system-mortgage { background: #ffedd5; color: #9a3412; }
.clients-system-badge.clients-system-group_pension { background: #ede9fe; color: #5b21b6; }
</style>

@include('support.partials.client-mpesa-stk-modal', [
    'mpesaPolicyNumber' => $clientPolicy,
    'clientPhone' => $clientPhone,
    'clientName' => $clientName,
    'defaultAmount' => $suggestedAmount,
    'mpesaConfigured' => $mpesaConfigured,
    'mpesaSandboxSimulate' => $mpesaSandboxSimulate,
])

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var phoneInput = document.getElementById('mpesa_phone');
    var amountInput = document.getElementById('mpesa_amount');
    @if($clientPhone)
    if (phoneInput && !phoneInput.value) phoneInput.value = @json($clientPhone);
    @endif
    @if($suggestedAmount)
    if (amountInput && !amountInput.value) amountInput.value = @json((int) $suggestedAmount);
    @endif
});
</script>
@endpush
@endsection
