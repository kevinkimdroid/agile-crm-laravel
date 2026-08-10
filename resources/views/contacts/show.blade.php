@extends('layouts.app')

@section('title', $contact->full_name . ' — Prospect')

@section('content')
@php
    $displayPhone = trim((string) ($contact->mobile ?? '')) ?: trim((string) ($contact->phone ?? ''));
    $displayEmail = trim((string) ($contact->email ?? ''));
    $prospectPolicy = trim((string) ($contact->policy_number ?? ''));
    $mpesaConfigured = app(\App\Services\MpesaStkPushService::class)->isConfigured();
    $mpesaSandboxSimulate = app(\App\Services\MpesaStkPushService::class)->isSandboxSimulate();
    $canCollectMpesa = $mpesaConfigured && $prospectPolicy !== '';
    $mpesaTransactions = ($canCollectMpesa && \Illuminate\Support\Facades\Schema::hasTable('mpesa_stk_transactions'))
        ? \App\Models\MpesaStkTransaction::query()->where('policy_number', $prospectPolicy)->orderByDesc('id')->limit(6)->get()
        : collect();
    $tab = $activeTab ?? 'summary';
    $tabSuffix = in_array($tab, ['tickets','policies','calls','sms','emails','campaigns','calendar','details','updates'], true) ? '?tab='.$tab : '';
@endphp

@include('support.partials.client-mpesa-styles')

<div class="contact-detail-header client-profile-hero card contact-detail-card mb-4">
    <div class="card-body p-4">
        <nav class="mb-3 text-uppercase small client-profile-breadcrumb">
            <a href="{{ route('contacts.index') }}" class="text-muted">Prospects</a>
            <span class="text-muted mx-1">/</span>
            <a href="{{ route('contacts.index') }}" class="text-muted">All</a>
            <span class="text-muted mx-1">/</span>
            <span class="text-dark">{{ Str::limit($contact->full_name, 40) }}</span>
        </nav>
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-start gap-3 flex-grow-1">
                <div class="contact-avatar-lg">
                    {{ strtoupper(substr($contact->firstname ?? '?', 0, 1)) }}{{ strtoupper(substr($contact->lastname ?? '', 0, 1)) }}
                </div>
                <div class="flex-grow-1" style="min-width:220px">
                    <h1 class="page-title mb-2 client-hero-name">{{ $contact->full_name }}</h1>
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                        @if($displayPhone)
                        <a href="tel:{{ tel_href($displayPhone) }}" class="client-hero-phone text-decoration-none">
                            <i class="bi bi-telephone-fill me-1"></i>{{ $displayPhone }}
                        </a>
                        @endif
                        @if($contact->title)
                        <span class="badge bg-light text-dark border">{{ $contact->title }}</span>
                        @endif
                        @if($prospectPolicy !== '')
                        <span class="client-hero-policy font-monospace">{{ $prospectPolicy }}</span>
                        @endif
                        @if($contact->leadsource)
                        <span class="badge bg-primary-subtle text-primary-emphasis">{{ $contact->leadsource }}</span>
                        @endif
                        @if($contact->contact_no)
                        <span class="text-muted small font-monospace">{{ $contact->contact_no }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center client-hero-actions">
                @if($displayPhone)
                <a href="tel:{{ tel_href($displayPhone) }}" class="btn btn-sm btn-success"><i class="bi bi-telephone me-1"></i>Call</a>
                @endif
                @if($displayEmail !== '')
                <a href="{{ route('support.email-client', array_filter(['contact_id' => $contact->contactid, 'email' => $displayEmail, 'client_name' => $contact->full_name])) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-envelope me-1"></i>Email</a>
                @endif
                @if($displayPhone)
                <a href="{{ route('support.sms-notifier', ['contact_id' => $contact->contactid, 'phone' => $displayPhone]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat-dots me-1"></i>SMS</a>
                @endif
                <a href="{{ route('tickets.create', ['contact_id' => $contact->contactid]) }}" class="btn btn-sm btn-success"><i class="bi bi-ticket-perforated me-1"></i>Create Ticket</a>
                @if($canCollectMpesa)
                <button type="button" class="btn btn-sm btn-outline-success mpesa-stk-trigger" data-bs-toggle="modal" data-bs-target="#mpesaStkModal">
                    <i class="bi bi-phone me-1"></i>M-Pesa
                </button>
                @endif
                <a href="{{ route('contacts.edit', $contact->contactid) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">More</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#followupModal"><i class="bi bi-calendar-check me-2"></i>Log Follow-up</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('contacts.destroy', $contact->contactid) }}" method="POST" onsubmit="return confirm('Delete this prospect?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger">Delete</button>
                            </form>
                        </li>
                    </ul>
                </div>
                @if(($prevContactId ?? null) || ($nextContactId ?? null))
                <div class="btn-group">
                    @if($prevContactId ?? null)
                    <a href="{{ route('contacts.show', $prevContactId) }}{{ $tabSuffix }}" class="btn btn-sm btn-outline-secondary" title="Previous prospect"><i class="bi bi-chevron-left"></i></a>
                    @endif
                    @if($nextContactId ?? null)
                    <a href="{{ route('contacts.show', $nextContactId) }}{{ $tabSuffix }}" class="btn btn-sm btn-outline-secondary" title="Next prospect"><i class="bi bi-chevron-right"></i></a>
                    @endif
                </div>
                @endif
                <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@include('support.partials.client-page-toasts')

<div class="card contact-detail-card client-module-tabs-shell mb-4">
    <div class="card-body py-2 px-2">
        <ul class="nav contact-module-tabs client-module-tabs mb-0">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'summary' ? 'active' : '' }}" href="{{ route('contacts.show', $contact->contactid) }}?tab=summary">Summary</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'details' ? 'active' : '' }}" href="{{ route('contacts.show', $contact->contactid) }}?tab=details">Details</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'updates' ? 'active' : '' }}" href="{{ route('contacts.show', $contact->contactid) }}?tab=updates">
                    Updates
                    @if((($activitiesCount ?? 0) + ($commentsCount ?? 0)) > 0)
                    <span class="badge bg-primary ms-1">{{ ($activitiesCount ?? 0) + ($commentsCount ?? 0) }}</span>
                    @endif
                </a>
            </li>
            @if($canCollectMpesa)
            <li class="nav-item">
                <a class="nav-link" href="#prospect-mpesa" onclick="document.getElementById('mpesaStkModal') && bootstrap.Modal.getOrCreateInstance(document.getElementById('mpesaStkModal')).show(); return false;">
                    <i class="bi bi-phone me-1"></i>M-Pesa
                </a>
            </li>
            @endif
            <li class="nav-item client-module-tabs-divider" aria-hidden="true"></li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'tickets' ? 'active' : '' }}" href="{{ route('contacts.show', $contact->contactid) }}?tab=tickets" title="Tickets">
                    <i class="bi bi-ticket-perforated"></i>
                    @if(($ticketsCount ?? 0) > 0)<span class="badge bg-primary ms-1">{{ $ticketsCount }}</span>@endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'emails' ? 'active' : '' }}" href="{{ route('contacts.show', $contact->contactid) }}?tab=emails" title="Emails">
                    <i class="bi bi-envelope"></i>
                    @if(($emailsCount ?? 0) > 0)<span class="badge bg-primary ms-1">{{ $emailsCount }}</span>@endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'policies' ? 'active' : '' }}" href="{{ route('contacts.show', $contact->contactid) }}?tab=policies" title="Policies"><i class="bi bi-box"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'calls' ? 'active' : '' }}" href="{{ route('contacts.show', $contact->contactid) }}?tab=calls" title="Calls"><i class="bi bi-telephone"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'campaigns' ? 'active' : '' }}" href="{{ route('contacts.show', $contact->contactid) }}?tab=campaigns" title="Campaigns">
                    <i class="bi bi-megaphone"></i>
                    @if(($campaigns ?? collect())->isNotEmpty())
                    <span class="badge bg-primary ms-1">{{ ($campaigns ?? collect())->count() }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'sms' ? 'active' : '' }}" href="{{ route('contacts.show', $contact->contactid) }}?tab=sms" title="SMS"><i class="bi bi-chat-dots"></i></a>
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
                        <span class="client-summary-label">Full name</span>
                        <span class="client-summary-value client-summary-name">{{ $contact->full_name }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Title / position</span>
                        <span class="client-summary-value">{{ $contact->title ?: '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Mobile</span>
                        <span class="client-summary-value">
                            @if($displayPhone)<a href="tel:{{ tel_href($displayPhone) }}">{{ $displayPhone }}</a>@else — @endif
                        </span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Office phone</span>
                        <span class="client-summary-value">{{ $contact->phone ?: '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Primary email</span>
                        <span class="client-summary-value">{{ $displayEmail !== '' ? $displayEmail : '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Department</span>
                        <span class="client-summary-value">{{ $contact->department ?: '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">ID / Passport</span>
                        <span class="client-summary-value font-monospace">{{ $contact->idNumber ?? $contact->id_number ?? '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Policy number</span>
                        <span class="client-summary-value font-monospace">{{ $prospectPolicy !== '' ? $prospectPolicy : '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Lead source</span>
                        <span class="client-summary-value">{{ $contact->leadsource ?: '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Mailing city</span>
                        <span class="client-summary-value">{{ $contact->mailingcity ?: '—' }}</span>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <a href="{{ route('contacts.show', $contact->contactid) }}?tab=details" class="btn btn-sm btn-outline-primary">View full details</a>
                </div>
            </div>
        </div>

        @if($canCollectMpesa)
        <div class="card contact-detail-card mb-4 client-mpesa-summary-card mpesa-ui" id="prospect-mpesa">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h6 class="text-uppercase small fw-bold mb-1" style="color:var(--agile-primary,#0E4385)">M-Pesa premium collection</h6>
                        <p class="text-muted small mb-0">Send an STK push for policy <code>{{ $prospectPolicy }}</code> and track payment status.</p>
                    </div>
                    <button type="button" class="btn btn-success mpesa-stk-trigger" data-bs-toggle="modal" data-bs-target="#mpesaStkModal">
                        <i class="bi bi-phone me-1"></i>Collect via M-Pesa
                    </button>
                </div>
                @if($mpesaTransactions->isNotEmpty())
                <div class="mpesa-tx-list border-top pt-3 mt-3">
                    @foreach($mpesaTransactions->take(3) as $tx)
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
        @endif

        @if(($deals ?? collect())->isNotEmpty())
        <div class="card contact-detail-card mb-4" id="deals">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary,#0E4385)">Deals</h6>
                <div class="list-group list-group-flush">
                    @foreach($deals as $deal)
                    <a href="{{ route('deals.show', $deal->potentialid) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                        <span>{{ $deal->potentialname ?? 'Untitled' }}</span>
                        <span class="badge bg-primary">{{ $deal->sales_stage ?? '—' }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    @include('contacts.partials.summary-sidebar')
</div>
@elseif($tab === 'details')
@include('contacts.partials.details-tab')
@elseif($tab === 'updates')
@include('contacts.partials.contact-comments')
@include('contacts.partials.activities-related-list', ['activitiesTab' => 'updates'])
@else
    @if($tab === 'tickets')
    <div class="mb-4">
        <div class="card contact-detail-card">
            <div class="card-body p-0">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
                    <h6 class="text-uppercase small fw-bold text-muted mb-0">Tickets</h6>
                    <a href="{{ route('tickets.create', ['contact_id' => $contact->contactid]) }}" class="btn btn-success btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> Add Ticket
                    </a>
                </div>
                <form action="{{ route('contacts.show', $contact->contactid) }}" method="GET" class="p-3 border-bottom">
                    <input type="hidden" name="tab" value="tickets">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">Search</label>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search tickets..." value="{{ $ticketSearch ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Status</label>
                            <select name="list" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="Open" {{ ($ticketStatus ?? '') === 'Open' ? 'selected' : '' }}>Open</option>
                                <option value="In Progress" {{ ($ticketStatus ?? '') === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="Wait For Response" {{ ($ticketStatus ?? '') === 'Wait For Response' ? 'selected' : '' }}>Wait For Response</option>
                                <option value="Closed" {{ ($ticketStatus ?? '') === 'Closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-search me-1"></i> Search</button>
                        </div>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small text-uppercase fw-bold">Ticket</th>
                                <th class="small text-uppercase fw-bold">Title</th>
                                <th class="small text-uppercase fw-bold">Policy</th>
                                <th class="small text-uppercase fw-bold">Status</th>
                                <th class="small text-uppercase fw-bold">Priority</th>
                                <th class="small text-uppercase fw-bold">Assigned To</th>
                                <th class="small text-uppercase fw-bold">Assigned By</th>
                                <th class="small text-uppercase fw-bold">Created</th>
                                <th class="small text-uppercase fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets ?? [] as $ticket)
                            @php
                                // POLICY = policy_number only (from contact cf or "Related policy"). Never contact_id.
                                $policyNum = pick_policy_excluding_pin($ticket->cf_860 ?? null, $ticket->cf_856 ?? null, $ticket->cf_872 ?? null);
                                if (!$policyNum && !empty($ticket->description ?? '') && preg_match('/Related policy:\s*([^\n]+)/i', $ticket->description, $m)) {
                                    $p = trim($m[1]);
                                    $cid = (string)($ticket->contact_id ?? '');
                                    if ($p !== '' && $p !== $cid && !looks_like_kra_pin($p) && !looks_like_client_id($p)) {
                                        $policyNum = $p;
                                    }
                                }
                                $ownerName = trim(($ticket->owner_first ?? '') . ' ' . ($ticket->owner_last ?? '')) ?: ($ticket->owner_username ?? '—');
                                $assignedByName = trim(($ticket->assigned_by_first ?? '') . ' ' . ($ticket->assigned_by_last ?? '')) ?: ($ticket->assigned_by_username ?? '—');
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('tickets.show', $ticket->ticketid) }}" class="fw-semibold text-primary text-decoration-none">
                                        {{ $ticket->ticket_no ?? 'TT' . $ticket->ticketid }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('tickets.show', $ticket->ticketid) }}" class="text-decoration-none">{{ $ticket->title ?? 'Untitled' }}</a>
                                </td>
                                <td><span class="text-muted">{{ $policyNum ?? '—' }}</span></td>
                                <td>
                                    <span class="badge tickets-badge-{{ Str::slug($ticket->status ?? '') }}">
                                        {{ $ticket->status ?? '—' }}
                                    </span>
                                </td>
                                <td><span class="text-muted">{{ $ticket->priority ?? 'Normal' }}</span></td>
                                <td><span class="text-muted small">{{ $ownerName }}</span></td>
                                <td><span class="text-muted small">{{ $assignedByName }}</span></td>
                                <td><span class="text-muted small">{{ $ticket->createdtime ? date('d M Y', strtotime($ticket->createdtime)) : '—' }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('tickets.show', $ticket->ticketid) }}" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('tickets.edit', $ticket->ticketid) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-ticket-perforated display-6 d-block mb-2"></i>
                                        <p class="mb-2">No tickets for this client yet.</p>
                                        <a href="{{ route('tickets.create', ['contact_id' => $contact->contactid]) }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-lg me-1"></i> Add Ticket
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($ticketsPaginator && $ticketsPaginator->hasPages())
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-top bg-light">
                    <span class="small text-muted">Showing {{ $ticketsPaginator->firstItem() ?? 0 }}–{{ $ticketsPaginator->lastItem() ?? 0 }} of {{ $ticketsPaginator->total() }}</span>
                    {{ $ticketsPaginator->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Policies tab content --}}
    @if($tab === 'policies')
    @php
        $erpDisabled = ! config('erp.enabled', true);
        $policiesErrorIsDisabled = $erpDisabled
            || str_contains((string) ($policiesError ?? ''), 'ERP integration is disabled');
    @endphp
    <div class="mb-4">
        <div class="card contact-detail-card">
            <div class="card-body p-0">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
                    <h6 class="text-uppercase small fw-bold text-muted mb-0">Policies</h6>
                    @if(! $erpDisabled)
                    <a href="{{ route('support.serve-client') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-search me-1"></i> Search more in ERP
                    </a>
                    @endif
                </div>
                @if(($policiesError ?? null) && ! $policiesErrorIsDisabled)
                <div class="p-4">
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>{{ $policiesError }}
                    </div>
                </div>
                @elseif(empty($policies ?? []))
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-box display-6 d-block mb-2 opacity-50"></i>
                    <p class="mb-0">{{ $erpDisabled ? 'No policy number on this prospect yet.' : 'No policies found for this prospect.' }}</p>
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small text-uppercase fw-bold">Policy</th>
                                <th class="small text-uppercase fw-bold">Name</th>
                                <th class="small text-uppercase fw-bold">Phone</th>
                                <th class="small text-uppercase fw-bold">Email</th>
                                <th class="small text-uppercase fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($policies ?? [] as $policy)
                            @php
                                $policyNo = $policy['policy_no'] ?? $policy['policy_number'] ?? $policy['POLICY_NO'] ?? $policy['POLICY_NUMBER'] ?? '—';
                                $name = $policy['name'] ?? $policy['client_name'] ?? $policy['CLIENT_NAME'] ?? '—';
                                $phone = $policy['phone'] ?? $policy['mobile'] ?? $policy['PHONE'] ?? $policy['MOBILE'] ?? '—';
                                $email = $policy['email'] ?? $policy['EMAIL'] ?? '—';
                            @endphp
                            <tr class="policy-row cursor-pointer" data-policy='@json($policy)' role="button" tabindex="0">
                                <td class="fw-semibold font-monospace">{{ $policyNo }}</td>
                                <td>{{ $name }}</td>
                                <td><span class="text-muted">{{ $phone }}</span></td>
                                <td><span class="text-muted">{{ $email }}</span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary policy-view-btn" data-policy='@json($policy)' title="View details">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @php
                                        $ticketParams = ['contact_id' => $contact->contactid];
                                        if ($policyNo !== '—' && !looks_like_kra_pin($policyNo)) { $ticketParams['policy'] = $policyNo; }
                                    @endphp
                                    <a href="{{ route('tickets.create', $ticketParams) }}" class="btn btn-sm btn-success" title="Create ticket">
                                        <i class="bi bi-ticket-perforated"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Policy detail modal --}}
    <div class="modal fade" id="policyDetailModal" tabindex="-1" aria-labelledby="policyDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="policyDetailModalLabel">
                        <i class="bi bi-box me-2"></i>Policy details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="policyDetailBody">
                    <div class="row" id="policyDetailFields"></div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="policyDetailViewClient" class="btn btn-primary me-auto" style="display:none;" title="View full client details">
                        <i class="bi bi-eye me-1"></i> View full details
                    </a>
                    <a href="#" id="policyDetailCreateTicket" class="btn btn-success" style="display:none;">
                        <i class="bi bi-ticket-perforated me-1"></i> Create ticket
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Calls tab content (PBX call history) --}}
    @if($tab === 'calls')
    <div class="mb-4">
        <div class="card contact-detail-card">
            <div class="card-body p-0">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
                    <h6 class="text-uppercase small fw-bold text-muted mb-0">Calls (PBX)</h6>
                    <div class="d-flex gap-2 align-items-center">
                        @if($contact->mobile ?? $contact->phone)
                        <a href="tel:{{ tel_href($contact->mobile ?? $contact->phone ?? '') }}" class="btn btn-success btn-sm" title="Call (opens MicroSIP)">
                            <i class="bi bi-telephone-outbound-fill me-1"></i>Call
                        </a>
                        @endif
                        <a href="{{ route('tools.pbx-manager') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-telephone me-1"></i>All Calls
                        </a>
                    </div>
                </div>
                @if(!($contact->mobile ?? $contact->phone))
                <div class="p-4">
                    <p class="text-muted mb-0">No phone number on file. Add mobile or phone in the contact details to match PBX calls.</p>
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 contact-calls-table">
                        <thead class="table-light">
                            <tr>
                                <th class="small text-uppercase fw-bold">Status</th>
                                <th class="small text-uppercase fw-bold">Direction</th>
                                <th class="small text-uppercase fw-bold">Number</th>
                                <th class="small text-uppercase fw-bold">Agent</th>
                                <th class="small text-uppercase fw-bold">Recording</th>
                                <th class="small text-uppercase fw-bold">Duration</th>
                                <th class="small text-uppercase fw-bold">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($calls ?? [] as $call)
                            <tr>
                                <td>
                                    <span class="badge pbx-badge pbx-badge-{{ Str::slug($call->call_status ?? '') }}">
                                        {{ $call->call_status ?? '—' }}
                                    </span>
                                </td>
                                <td>{{ $call->direction ?? '—' }}</td>
                                <td>
                                    @if(!empty($call->customer_number))
                                    <a href="tel:{{ tel_href($call->customer_number) }}" class="btn btn-sm btn-link p-0 text-primary text-decoration-none me-1" title="Call (opens MicroSIP)">
                                        <i class="bi bi-telephone-outbound"></i>
                                    </a>
                                    @endif
                                    <span class="font-monospace">{{ $call->customer_number ?? '—' }}</span>
                                </td>
                                <td>{{ $call->user_name ?? '—' }}</td>
                                <td>
                                    @if(($call->from_vtiger ?? false) && !empty($call->id))
                                    <button type="button" class="btn btn-sm btn-outline-primary pbx-play-btn" data-recording-url="{{ route('tools.pbx-manager.recording.vtiger', $call->id) }}" data-call-info="{{ $call->customer_number ?? '' }} — {{ optional($call->start_time)->format('d/m H:i') ?: '' }}">
                                        <i class="bi bi-play-circle me-1"></i>Listen
                                    </button>
                                    @elseif(!empty($call->recording_url))
                                    <button type="button" class="btn btn-sm btn-outline-primary pbx-play-btn" data-recording-url="{{ route('tools.pbx-manager.recording', $call->id) }}" data-call-info="{{ $call->customer_number ?? '' }} — {{ optional($call->start_time)->format('d/m H:i') ?: '' }}">
                                        <i class="bi bi-play-circle me-1"></i>Listen
                                    </button>
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $call->duration_sec ?? 0 }}s</td>
                                <td class="text-nowrap">{{ optional($call->start_time)->format('d M Y H:i') ?: '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-telephone display-6 d-block mb-2 opacity-50"></i>
                                    <p class="mb-2">No PBX calls found for this client.</p>
                                    <a href="{{ route('tools.pbx-manager') }}" class="btn btn-outline-primary btn-sm">View all calls</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($callsPaginator && $callsPaginator->hasPages())
                <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light">
                    <span class="small text-muted">{{ $callsPaginator->firstItem() ?? 0 }}–{{ $callsPaginator->lastItem() ?? 0 }} of {{ $callsPaginator->total() }}</span>
                    {{ $callsPaginator->links('pagination::bootstrap-5') }}
                </div>
                @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Make Call modal is in layout (partials.pbx-tel-handler) for use app-wide including tel: links --}}
    <div class="modal fade" id="pbxRecordingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-mic-fill me-2"></i>Call Recording</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="pbxRecordingInfo">—</p>
                    <audio id="pbxRecordingAudio" controls preload="metadata" class="w-100" style="height:48px;"></audio>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        document.querySelectorAll('.pbx-play-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.dataset.recordingUrl, info = this.dataset.callInfo || '—';
                if (!url) return;
                const modal = new bootstrap.Modal(document.getElementById('pbxRecordingModal'));
                const audio = document.getElementById('pbxRecordingAudio');
                document.getElementById('pbxRecordingInfo').textContent = info;
                audio.pause(); audio.src = url; audio.load();
                modal.show();
                audio.addEventListener('canplaythrough', () => audio.play(), { once: true });
            });
        });
    })();
    </script>
    @endif

    {{-- Emails tab content (sent to client via life@geminialife.co.ke) --}}
    @if($tab === 'emails')
    <div class="mb-4">
        <div class="card contact-detail-card">
            <div class="card-body p-0">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
                    <h6 class="text-uppercase small fw-bold text-muted mb-0">Emails <small class="text-muted">(to/from {{ config('email-service.sender', 'life@geminialife.co.ke') }})</small></h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('tools.mail-manager.create', ['from_address' => $contact->email ?? $contact->secondaryemail ?? $contact->otheremail ?? '', 'from_name' => trim(($contact->firstname ?? '') . ' ' . ($contact->lastname ?? ''))]) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-plus-lg me-1"></i>Create Email
                        </a>
                        <a href="{{ route('tools.mail-manager') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-inbox me-1"></i>Mail Manager
                        </a>
                    </div>
                </div>
                @if(!($contact->email ?? $contact->secondaryemail ?? $contact->otheremail))
                <div class="p-4">
                    <p class="text-muted mb-0">No email address on file for this client. Add a primary, secondary, or other email in contact details to match emails, or <a href="{{ route('tools.mail-manager.create') }}">create an email record</a> manually.</p>
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small text-uppercase fw-bold">From</th>
                                <th class="small text-uppercase fw-bold">Subject</th>
                                <th class="small text-uppercase fw-bold">Date</th>
                                <th class="small text-uppercase fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($emails ?? [] as $email)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $email->from_name ?: $email->from_address }}</span>
                                    @if($email->from_name && $email->from_address)
                                    <br><small class="text-muted">{{ $email->from_address }}</small>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('tools.mail-manager.show', $email->id) }}" class="text-decoration-none text-dark">
                                        {{ Str::limit($email->subject ?? '(No subject)', 60) }}
                                    </a>
                                </td>
                                <td class="text-nowrap text-muted small">{{ $email->date ? \Carbon\Carbon::parse($email->date)->format('d M Y H:i') : '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('tools.mail-manager.show', $email->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-envelope display-6 d-block mb-2 opacity-50"></i>
                                    <p class="mb-2">No emails for this client yet.</p>
                                    <p class="small mb-0"><a href="{{ route('tools.mail-manager.create', ['from_address' => $contact->email ?? $contact->secondaryemail ?? '', 'from_name' => trim(($contact->firstname ?? '') . ' ' . ($contact->lastname ?? ''))]) }}">Create Email</a> to log one, or <a href="{{ route('tools.mail-manager') }}">Fetch emails</a> from life@geminialife.co.ke.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($emailsPaginator && $emailsPaginator->hasPages())
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-top bg-light">
                    <span class="small text-muted">Showing {{ $emailsPaginator->firstItem() ?? 0 }}–{{ $emailsPaginator->lastItem() ?? 0 }} of {{ $emailsPaginator->total() }}</span>
                    {{ $emailsPaginator->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
                @endif
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Campaigns tab content --}}
    @if($tab === 'campaigns')
    <div class="mb-4">
        <div class="card contact-detail-card">
            <div class="card-body p-0">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
                    <h6 class="text-uppercase small fw-bold text-muted mb-0">Campaigns this client is part of</h6>
                    @php $availableCampaigns = ($allCampaigns ?? collect())->whereNotIn('id', ($campaigns ?? collect())->pluck('id')); @endphp
                    <div class="d-flex gap-2 align-items-center">
                        @if($availableCampaigns->isNotEmpty())
                        <form action="{{ route('contacts.campaigns.add', $contact->contactid) }}" method="POST" class="d-flex gap-2 align-items-center">
                            @csrf
                            <select name="campaign_id" class="form-select form-select-sm" style="min-width: 200px" required>
                                <option value="">Select Campaign</option>
                                @foreach($availableCampaigns as $c)
                                <option value="{{ $c->id }}">{{ $c->campaign_name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i>Add to Campaign</button>
                        </form>
                        @else
                        <span class="text-muted small">Client is in all campaigns</span>
                        @endif
                        <a href="{{ route('marketing.campaigns.index') }}" class="btn btn-outline-secondary btn-sm">All Campaigns</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small text-uppercase fw-bold">Campaign Name</th>
                                <th class="small text-uppercase fw-bold">Assigned To</th>
                                <th class="small text-uppercase fw-bold">Status</th>
                                <th class="small text-uppercase fw-bold">Type</th>
                                <th class="small text-uppercase fw-bold">Expected Close</th>
                                <th class="small text-uppercase fw-bold">Revenue</th>
                                <th class="small text-uppercase fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campaigns ?? [] as $campaign)
                            <tr>
                                <td>
                                    <a href="{{ route('marketing.campaigns.edit', $campaign->id) }}" class="fw-semibold text-decoration-none">{{ $campaign->campaign_name ?? '—' }}</a>
                                </td>
                                <td><span class="text-muted">{{ $campaign->assigned_to ?? '—' }}</span></td>
                                <td><span class="badge bg-{{ ($campaign->campaign_status ?? '') === 'Active' ? 'success' : (($campaign->campaign_status ?? '') === 'Completed' ? 'secondary' : 'warning') }} bg-opacity-25 text-dark">{{ $campaign->campaign_status ?? '—' }}</span></td>
                                <td><span class="text-muted">{{ $campaign->campaign_type ?? '—' }}</span></td>
                                <td><span class="text-muted small">{{ $campaign->expected_close_date ? \Carbon\Carbon::parse($campaign->expected_close_date)->format('d M Y') : '—' }}</span></td>
                                <td><strong class="text-primary">KES {{ number_format($campaign->expected_revenue ?? 0, 0) }}</strong></td>
                                <td class="text-end">
                                    <form action="{{ route('contacts.campaigns.remove', [$contact->contactid, $campaign->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this client from the campaign?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove from campaign"><i class="bi bi-dash-lg"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-megaphone display-6 d-block mb-2 opacity-50"></i>
                                    <p class="mb-2">This client is not part of any campaign yet.</p>
                                    <p class="small mb-0">Use "Select Campaign" above to add them to a marketing campaign.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- SMS tab content --}}
    @if($tab === 'sms')
    <div class="mb-4">
        <div class="card contact-detail-card">
            <div class="card-body p-0">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
                    <h6 class="text-uppercase small fw-bold text-muted mb-0">SMS sent</h6>
                    <a href="{{ route('support.sms-notifier', ['contact_id' => $contact->contactid]) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-send-fill me-1"></i>Send SMS
                    </a>
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
                                    <span class="badge bg-danger bg-opacity-10 text-danger" title="{{ $log->error_message ?? 'Failed' }}">Failed</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-chat-dots display-6 d-block mb-2 opacity-50"></i>
                                    <p class="mb-2">No SMS sent to this client yet.</p>
                                    <a href="{{ route('support.sms-notifier', ['contact_id' => $contact->contactid]) }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-send-fill me-1"></i>Send SMS
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($smsPaginator && $smsPaginator->hasPages())
                <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light">
                    <span class="small text-muted">{{ $smsPaginator->firstItem() ?? 0 }}–{{ $smsPaginator->lastItem() ?? 0 }} of {{ $smsPaginator->total() }}</span>
                    {{ $smsPaginator->links('pagination::bootstrap-5') }}
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
@endif

{{-- Follow-up modal --}}
<div class="modal fade" id="followupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('contacts.followup.store', $contact->contactid) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-calendar-check me-2"></i>Log Follow-up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Note</label>
                        <textarea name="note" class="form-control" rows="4" placeholder="What was discussed? Next steps?" required></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Follow-up Date (optional)</label>
                        <input type="date" name="followup_date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Log Follow-up</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($canCollectMpesa)
@include('support.partials.client-mpesa-stk-modal', [
    'mpesaPolicyNumber' => $prospectPolicy,
    'clientPhone' => $displayPhone ?: null,
    'clientName' => $contact->full_name,
    'defaultAmount' => '',
    'mpesaConfigured' => $mpesaConfigured,
    'mpesaSandboxSimulate' => $mpesaSandboxSimulate,
])
@endif

<style>
.client-profile-hero {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 55%, #f0f6fc 100%);
    box-shadow: 0 4px 24px rgba(14, 67, 133, 0.08);
}
.client-profile-breadcrumb a { text-decoration: none; }
.client-profile-breadcrumb a:hover { color: var(--agile-primary, #0E4385) !important; }
.contact-detail-header { margin-bottom: 1.5rem; }
.contact-avatar-lg {
    width: 84px; height: 84px; border-radius: 20px;
    background: linear-gradient(145deg, #1A468A 0%, #0E4385 100%);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.55rem; font-weight: 700; flex-shrink: 0;
    box-shadow: 0 8px 20px rgba(14, 67, 133, 0.25);
}
.client-hero-name { color: var(--agile-primary, #0E4385); font-weight: 800; }
.client-hero-phone { font-weight: 600; color: #334155; }
.client-hero-policy { font-size: 0.82rem; color: #64748b; background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 999px; }
.client-hero-actions .btn { border-radius: 8px; font-weight: 600; }
.client-module-tabs-shell {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(14, 67, 133, 0.05);
    overflow: hidden;
}
.client-module-tabs {
    display: flex; flex-direction: row; flex-wrap: wrap; align-items: center; gap: 0.15rem; border: none;
}
.client-module-tabs .nav-item.client-module-tabs-divider {
    width: 1px; height: 1.75rem; margin: 0 0.35rem; padding: 0;
    background: var(--agile-border, #e2e8f0); align-self: center; pointer-events: none; flex: 0 0 1px;
}
@media (max-width: 767.98px) {
    .client-module-tabs .nav-item.client-module-tabs-divider { display: none; }
}
.contact-module-tabs { border-bottom: none; }
.contact-module-tabs .nav-link {
    color: var(--text-muted, #64748b); font-weight: 500;
    padding: 0.7rem 1rem; border: none; border-radius: 10px; margin-bottom: 0;
}
.contact-module-tabs .nav-link:hover { color: var(--primary, #0E4385); background: rgba(14, 67, 133, 0.06); }
.contact-module-tabs .nav-link.active {
    color: #fff; background: var(--primary, #0E4385);
    border-bottom-color: transparent;
    box-shadow: 0 4px 12px rgba(14, 67, 133, 0.22);
}
.contact-module-tabs .nav-link.active .badge { background: rgba(255,255,255,0.25) !important; color: #fff !important; }
.contact-module-tabs .nav-link i { font-size: 1.05rem; }
.contact-detail-card { border-radius: 16px; border: 1px solid var(--card-border, rgba(14, 67, 133, 0.12)); box-shadow: 0 2px 12px rgba(14, 67, 133, 0.04); }
.client-summary-personal-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.25rem; }
@media (max-width: 575.98px) { .client-summary-personal-grid { grid-template-columns: 1fr; } }
.client-summary-field { display: flex; flex-direction: column; gap: 0.2rem; }
.client-summary-label { font-size: 0.68rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; }
.client-summary-value { font-size: 0.95rem; font-weight: 600; color: #1e293b; }
.client-summary-name { font-size: 1.1rem; color: var(--agile-primary, #0E4385); }
.client-mpesa-summary-card, .client-summary-payments { border-color: #bbf7d0; background: linear-gradient(180deg, #f8fdf9 0%, #fff 70%); }
.policy-row { cursor: pointer; }
.policy-row:hover { background-color: rgba(14, 67, 133, 0.04); }
.pbx-badge { font-size: .7rem; }
.pbx-badge-completed { background: rgba(5, 150, 105, 0.15); color: #059669; }
.pbx-badge-busy, .pbx-badge-no-response, .pbx-badge-no-answer { background: rgba(217, 119, 6, 0.15); color: #d97706; }
.summary-empty-box { background: #f8fafc; border-radius: 10px; min-height: 60px; }
.tickets-badge-open, .tickets-badge-Open { background: rgba(14, 67, 133, 0.12); color: var(--primary); }
.tickets-badge-in-progress, .tickets-badge-In-Progress { background: rgba(245, 158, 11, 0.2); color: #d97706; }
.tickets-badge-closed, .tickets-badge-Closed { background: rgba(5, 150, 105, 0.15); color: #059669; }
.tickets-badge-wait-for-response, .tickets-badge-Wait-For-Response { background: rgba(56, 189, 248, 0.2); color: #0ea5e9; }
</style>

@if($canCollectMpesa)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var phoneInput = document.getElementById('mpesa_phone');
    @if($displayPhone)
    if (phoneInput && !phoneInput.value) phoneInput.value = @json($displayPhone);
    @endif
});
</script>
@endpush
@endif

@if($tab === 'policies' && !empty($policies ?? []))
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('policyDetailModal');
    if (!modal) return;
    const bsModal = new bootstrap.Modal(modal);
    const body = document.getElementById('policyDetailFields');
    const createTicketBtn = document.getElementById('policyDetailCreateTicket');
    const viewClientBtn = document.getElementById('policyDetailViewClient');
    const contactId = {{ $contact->contactid }};
    const clientsShowUrl = '{{ url("/support/clients/show") }}';

    function formatKey(k) {
        return k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function showPolicy(policy) {
        if (!policy || typeof policy !== 'object') return;
        const skip = ['POLICY_NO', 'POLICY_NUMBER', 'policy_no', 'policy_number'];
        const fields = [];
        for (const [k, v] of Object.entries(policy)) {
            if (skip.includes(k) || v === null || v === '') continue;
            fields.push({ key: formatKey(k), value: String(v) });
        }
        // Add policy number at top
        const policyNo = policy.policy_no ?? policy.policy_number ?? policy.POLICY_NO ?? policy.POLICY_NUMBER ?? '—';
        body.innerHTML = '<div class="col-12 mb-2"><strong>Policy number</strong>: <code>' + policyNo + '</code></div>' +
            fields.map(f => '<div class="col-md-6 mb-2"><strong>' + f.key + '</strong>: ' + f.value + '</div>').join('');
        if (createTicketBtn) {
            createTicketBtn.href = '{{ url("/tickets/create") }}?contact_id=' + contactId + '&policy=' + encodeURIComponent(policyNo);
            createTicketBtn.style.display = 'inline-block';
        }
        if (viewClientBtn && policyNo && policyNo !== '—') {
            viewClientBtn.href = clientsShowUrl + '?policy=' + encodeURIComponent(policyNo);
            viewClientBtn.style.display = 'inline-block';
        } else if (viewClientBtn) {
            viewClientBtn.style.display = 'none';
        }
        bsModal.show();
    }

    document.querySelectorAll('.policy-row, .policy-view-btn').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (e.target.closest('a')) return;
            const data = el.getAttribute('data-policy');
            if (data) {
                try {
                    showPolicy(JSON.parse(data));
                } catch (err) {}
            }
        });
    });

    document.querySelectorAll('.policy-row').forEach(function(row) {
        row.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const data = row.getAttribute('data-policy');
                if (data) {
                    try { showPolicy(JSON.parse(data)); } catch (err) {}
                }
            }
        });
    });
});
</script>
@endif

@if(($prevContactId ?? null) || ($nextContactId ?? null))
<script>
(function() {
    const prevId = @json($prevContactId ?? null);
    const nextId = @json($nextContactId ?? null);
    const tab = {{ in_array($activeTab ?? '', ['tickets','policies','calls','sms','emails','campaigns','calendar','details','updates']) ? "'?tab=" . ($activeTab ?? '') . "'" : "''" }};
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) return;
        if (e.key === 'ArrowLeft' && prevId) {
            e.preventDefault();
            window.location.href = '{{ url('/contacts') }}/' + prevId + tab;
        } else if (e.key === 'ArrowRight' && nextId) {
            e.preventDefault();
            window.location.href = '{{ url('/contacts') }}/' + nextId + tab;
        }
    });
})();
</script>
@endif
<script>
(function () {
    var hash = window.location.hash;
    if (hash === '#contact-comments') {
        var target = document.querySelector(hash);
        if (target) {
            setTimeout(function () { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 150);
        }
    }

    var input = document.getElementById('contactCommentAttachment');
    var label = document.getElementById('contactCommentFileName');
    if (!input || !label) return;
    input.addEventListener('change', function () {
        label.textContent = input.files && input.files[0] ? input.files[0].name : '';
    });
})();
</script>
@endsection
