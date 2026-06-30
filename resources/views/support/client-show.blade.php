@extends('layouts.app')

@section('title', ($clientName ?? 'Client') . ' — Client')

@section('content')
@php
$clientPhone = $client->phone_no ?? $client->phoneNo ?? $client->mobile ?? $client->phone ?? $client->client_contact ?? $client->PHONE_NO ?? null;
$clientName = $client->life_assur ?? $client->client_name ?? $client->name ?? $client->member_name ?? $client->mem_surname ?? 'Client';
$clientProduct = $client->product ?? $client->prod_desc ?? '—';
$clientPolicy = $client->policy_no ?? $policy ?? '—';
$rawClientEmail = $client->email_adr ?? $client->client_email ?? $client->email ?? null;
$clientEmail = ($rawClientEmail && filter_var(trim((string) $rawClientEmail), FILTER_VALIDATE_EMAIL)) ? trim((string) $rawClientEmail) : null;
$emailClientRouteParams = array_filter(['policy' => ($clientPolicy && $clientPolicy !== '—') ? $clientPolicy : null]);
if ($contact ?? null) {
    $emailClientRouteParams['contact_id'] = $contact->contactid;
} elseif ($clientEmail) {
    $emailClientRouteParams['email'] = $clientEmail;
    $emailClientRouteParams['client_name'] = $clientName !== 'Client' ? $clientName : null;
}
$canSendEmailToClient = ($contact ?? null) || $clientEmail;
$tab = $activeTab ?? 'summary';
$clientShowBase = $clientShowBase ?? array_filter(['policy' => $policy, 'from' => ($fromServeClient ?? false) ? 'serve-client' : null]);
$clientTabUrl = function (string $tabName) use ($clientShowBase) {
    $params = $clientShowBase;
    if ($tabName !== 'summary') {
        $params['tab'] = $tabName;
    }
    return route('support.clients.show', $params);
};
$erpSvc = app(\App\Services\ErpClientService::class);
$lifeSystem = $client->life_system ?? $erpSvc->getLifeSystemFromProduct($clientProduct);
$lifeSystemLabel = $erpSvc->getClientSystemLabel($lifeSystem);
$headerStatusCode = strtoupper(trim((string) ($client->status ?? '')));
$headerStatusLabel = match ($headerStatusCode) {
    'A' => 'Active',
    'FL' => 'Lapsed',
    default => $client->status ?? '—',
};
$headerStatusClass = $headerStatusCode === 'A' ? 'active' : ($headerStatusCode === 'FL' ? 'lapsed' : 'other');
$mpesaConfigured = $mpesaConfigured ?? app(\App\Services\MpesaStkPushService::class)->isConfigured();
$mpesaSandboxSimulate = $mpesaSandboxSimulate ?? app(\App\Services\MpesaStkPushService::class)->isSandboxSimulate();
@endphp

@include('support.partials.client-mpesa-styles')

<div class="contact-detail-header client-profile-hero card contact-detail-card mb-4">
    <div class="card-body p-4">
    <nav class="mb-2 text-uppercase small client-profile-breadcrumb">
        @if($fromServeClient ?? false)
        <a href="{{ route('support.serve-client', ['search' => $clientPolicy]) }}" class="text-muted">Serve Client</a>
        @else
        <a href="{{ route('support.customers') }}" class="text-muted">Clients</a>
        <span class="text-muted mx-1">&gt;</span>
        <a href="{{ route('support.customers') }}" class="text-muted">All</a>
        @endif
        <span class="text-muted mx-1">&gt;</span>
        <span class="text-dark">{{ Str::limit($clientName, 40) }}</span>
    </nav>
    <div class="d-flex flex-wrap align-items-start gap-4">
        <div class="contact-avatar-lg">
            {{ strtoupper(substr($clientName, 0, 1)) }}{{ strtoupper(substr(strrchr(trim($clientName), ' ') ?: $clientName, 1, 1)) }}
        </div>
        <div class="flex-grow-1">
            <h1 class="page-title mb-2">{{ $clientName }}</h1>
            @if($clientPhone)
            <p class="mb-2">
                <i class="bi bi-telephone me-1 text-muted"></i>
                <a href="tel:{{ tel_href($clientPhone) }}" class="text-decoration-none">{{ $clientPhone }}</a>
                <span class="text-muted mx-2">·</span>
                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(trim($clientName . ' ' . $clientPhone)) }}" target="_blank" rel="noopener" class="text-primary small">Show Map</a>
            </p>
            @endif
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <span class="clients-system-badge clients-system-{{ $lifeSystem }}">{{ $lifeSystemLabel }}</span>
                <span class="client-status-badge client-status-{{ $headerStatusClass }}">{{ $headerStatusLabel }}</span>
                <span class="text-muted small font-monospace">{{ $clientPolicy }}</span>
                <span class="client-consent-header-badge {{ optional($clientConsent ?? null)->consent_granted ? 'is-granted' : 'is-pending' }}" id="clientConsentHeaderBadge">
                    <i class="bi {{ optional($clientConsent ?? null)->consent_granted ? 'bi-shield-check' : 'bi-shield-exclamation' }}"></i>
                    {{ optional($clientConsent ?? null)->consent_granted ? 'Consent on file' : 'No consent recorded' }}
                </span>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @if($clientPhone)
                <a href="tel:{{ tel_href($clientPhone) }}" class="btn btn-sm btn-success"><i class="bi bi-telephone me-1"></i>Call</a>
                @endif
                @if($canSendEmailToClient)
                <a href="{{ route('support.email-client', $emailClientRouteParams) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-envelope me-1"></i>Email</a>
                @endif
                @if($clientPhone)
                <a href="{{ route('support.sms-notifier', $contact ? ['contact_id' => $contact->contactid] : ['phone' => $clientPhone]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat-dots me-1"></i>SMS</a>
                @endif
                <a href="{{ route('support.clients.create-ticket', ['policy' => $clientPolicy]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-ticket-perforated me-1"></i>Create Ticket</a>
                <a href="{{ route('support.serve-client', ['search' => $clientPolicy]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-person-plus me-1"></i>Serve Client</a>
                @if($contact ?? null)
                <a href="{{ route('contacts.show', $contact->contactid) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-person me-1"></i>CRM Prospect</a>
                @endif
                <button type="button" class="btn btn-sm btn-outline-success {{ $mpesaConfigured ? 'mpesa-stk-trigger' : '' }}"
                    @if($mpesaConfigured) data-bs-toggle="modal" data-bs-target="#mpesaStkModal" @endif
                    @if(! $mpesaConfigured) disabled @endif
                    title="{{ $mpesaConfigured ? ($mpesaSandboxSimulate ? 'Sandbox mode — simulated STK' : 'Send M-Pesa STK push') : 'M-Pesa unavailable' }}">
                    <i class="bi bi-phone-vibrate me-1"></i>M-Pesa
                </button>
                <a href="{{ ($fromServeClient ?? false) ? route('support.serve-client', ['search' => $clientPolicy]) : route('support.customers') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
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
                    <span class="badge bg-primary ms-1 client-tab-badge" data-tab-badge="updates" @if((($activitiesCount ?? 0) + ($commentsCount ?? 0)) <= 0) hidden @endif>{{ ($activitiesCount ?? 0) + ($commentsCount ?? 0) }}</span>
                </a>
            </li>
            <li class="nav-item client-module-tabs-divider" aria-hidden="true"></li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'tickets' ? 'active' : '' }}" href="{{ $clientTabUrl('tickets') }}" title="Tickets">
                    <i class="bi bi-ticket-perforated"></i>
                    <span class="badge bg-primary ms-1 client-tab-badge" data-tab-badge="tickets" @if(($ticketsCount ?? 0) <= 0) hidden @endif>{{ $ticketsCount ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'emails' ? 'active' : '' }}" href="{{ $clientTabUrl('emails') }}" title="Emails">
                    <i class="bi bi-envelope"></i>
                    <span class="badge bg-primary ms-1 client-tab-badge" data-tab-badge="emails" @if(($emailsCount ?? 0) <= 0) hidden @endif>{{ $emailsCount ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'summary' ? 'active' : '' }}" href="{{ $clientTabUrl('summary') }}#client-documents" title="Documents">
                    <i class="bi bi-file-earmark"></i>
                    @if(($documentsCount ?? 0) > 0)
                    <span class="badge bg-primary ms-1">{{ $documentsCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'policies' ? 'active' : '' }}" href="{{ $clientTabUrl('policies') }}" title="Other policies">
                    <i class="bi bi-box"></i>
                    <span class="badge bg-primary ms-1 client-tab-badge" data-tab-badge="policies" @if(($policiesCount ?? 0) <= 0) hidden @endif>{{ $policiesCount ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'calls' ? 'active' : '' }}" href="{{ $clientTabUrl('calls') }}" title="Calls"><i class="bi bi-telephone"></i></a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'sms' ? 'active' : '' }}" href="{{ $clientTabUrl('sms') }}" title="SMS sent">
                    <i class="bi bi-chat-dots"></i>
                    <span class="badge bg-primary ms-1 client-tab-badge" data-tab-badge="sms" @if(($smsCount ?? 0) <= 0) hidden @endif>{{ $smsCount ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'premiums' ? 'active' : '' }}" href="{{ $clientTabUrl('premiums') }}" title="Premiums">
                    <i class="bi bi-receipt"></i>
                    <span class="badge bg-primary ms-1 client-tab-badge" data-tab-badge="premiums" @if(($premiumsCount ?? 0) <= 0) hidden @endif>{{ $premiumsCount ?? 0 }}</span>
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
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="client-details-block-icon"><i class="bi bi-person-vcard"></i></div>
                    <h6 class="text-uppercase small fw-bold text-muted mb-0">Personal information</h6>
                </div>
                <div class="client-summary-personal-grid">
                    <div class="client-summary-field">
                        <span class="client-summary-label">Life assured</span>
                        <span class="client-summary-value client-summary-name">{{ $clientName }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Phone</span>
                        <span class="client-summary-value">
                            @if($clientPhone)
                            <a href="tel:{{ tel_href($clientPhone) }}" class="client-details-action-link">{{ $clientPhone }}</a>
                            @else — @endif
                        </span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Email</span>
                        <span class="client-summary-value">
                            @if($clientEmail)
                            <a href="mailto:{{ $clientEmail }}" class="client-details-action-link">{{ $clientEmail }}</a>
                            @else — @endif
                        </span>
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
                </div>
                <div class="mt-3 pt-3 border-top">
                    <a href="{{ $clientTabUrl('details') }}" class="btn btn-sm btn-outline-primary">View full details</a>
                </div>
            </div>
        </div>

        @if(($tickets ?? collect())->isNotEmpty())
        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-uppercase small fw-bold text-muted mb-0">Recent Tickets</h6>
                    <a href="{{ $clientTabUrl('tickets') }}" class="btn btn-sm btn-outline-secondary">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(($tickets ?? collect())->take(5) as $t)
                            <tr>
                                <td class="font-monospace small">{{ $t->ticket_no ?? $t->ticketid }}</td>
                                <td>{{ Str::limit($t->title ?? '—', 40) }}</td>
                                <td><span class="ticket-status-badge ticket-status-{{ Str::slug($t->status ?? '') }}">{{ $t->status ?? '—' }}</span></td>
                                <td class="text-end"><a href="{{ route('tickets.show', $t->ticketid) }}" class="btn btn-sm btn-link p-0">View</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @include('support.partials.client-summary-notes')
    </div>

    <div class="col-lg-4">
        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">Quick actions</h6>
                <div class="d-flex flex-column gap-2">
                    @if($clientPhone)
                    <a href="tel:{{ tel_href($clientPhone) }}" class="btn btn-outline-primary"><i class="bi bi-telephone me-2"></i>Call</a>
                    <a href="{{ route('support.sms-notifier', $contact ? ['contact_id' => $contact->contactid] : ['phone' => $clientPhone]) }}" class="btn btn-outline-primary"><i class="bi bi-chat-dots me-2"></i>Send Text</a>
                    @endif
                    @if($canSendEmailToClient)
                    <a href="{{ route('support.email-client', $emailClientRouteParams) }}" class="btn btn-outline-primary"><i class="bi bi-envelope me-2"></i>Send Email</a>
                    @endif
                    <a href="{{ route('support.clients.create-ticket', ['policy' => $clientPolicy]) }}" class="btn btn-outline-success"><i class="bi bi-ticket-perforated me-2"></i>Create Ticket</a>
                    <a href="{{ route('support.serve-client', ['search' => $clientPolicy]) }}" class="btn btn-outline-primary"><i class="bi bi-person-plus me-2"></i>Serve Client</a>
                    @if($contact ?? null)
                    <a href="{{ route('contacts.show', $contact->contactid) }}" class="btn btn-outline-secondary"><i class="bi bi-person me-2"></i>View CRM Prospect</a>
                    @endif
                </div>
            </div>
        </div>

        @if($contact ?? null)
        @php
            $clientActivityCreateUrl = function (string $type) use ($contact, $clientShowBase) {
                return route('activities.create', [
                    'type' => $type,
                    'related_to' => $contact->contactid,
                    'lock_related' => 1,
                    'return_to' => route('support.clients.show', array_merge($clientShowBase, ['tab' => 'updates'])),
                ]);
            };
            $clientCalendarReturn = route('support.clients.show', array_merge($clientShowBase, ['tab' => 'updates']));
            $clientActivityEditUrl = function ($act) use ($clientCalendarReturn) {
                return route('activities.edit', [
                    'activity' => $act->activityid,
                    'lock_related' => 1,
                    'return_to' => $clientCalendarReturn,
                ]);
            };
        @endphp
        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-uppercase small fw-bold text-muted mb-0">Activities</h6>
                    <a href="{{ $clientTabUrl('updates') }}" class="btn btn-sm btn-outline-secondary">View all</a>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="{{ $clientActivityCreateUrl('Event') }}" class="btn btn-sm btn-success"><i class="bi bi-calendar-event me-1"></i>Add Event</a>
                    <a href="{{ $clientActivityCreateUrl('Task') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-check2-square me-1"></i>Add Task</a>
                </div>
                @if(($activities ?? collect())->isNotEmpty())
                <ul class="list-unstyled mb-0">
                    @foreach($activities as $act)
                    <li class="py-2 border-bottom d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <strong>{{ $act->subject ?? 'Untitled' }}</strong>
                            <span class="badge bg-secondary ms-1">{{ $act->activitytype ?? 'Task' }}</span>
                            <p class="text-muted small mb-0">{{ $act->date_start ?? '' }}</p>
                        </div>
                        <a href="{{ $clientActivityEditUrl($act) }}" class="btn btn-sm btn-outline-primary flex-shrink-0" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="summary-empty-box py-4 text-center text-muted">No pending activities</div>
                @endif
            </div>
        </div>
        @endif

        <div class="card contact-detail-card mb-4 client-summary-payments mpesa-ui">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="client-payments-zone-icon client-payments-zone-icon-sm"><i class="bi bi-wallet2"></i></div>
                    <h6 class="text-uppercase small fw-bold text-muted mb-0">Payments</h6>
                </div>
                <p class="text-muted small mb-3">Collect premium via M-Pesa STK push for policy <span class="font-monospace">{{ $clientPolicy }}</span>.</p>
                <button type="button" class="btn btn-success w-100 mb-3 {{ $mpesaConfigured ? 'mpesa-stk-trigger' : '' }}"
                    @if($mpesaConfigured) data-bs-toggle="modal" data-bs-target="#mpesaStkModal" @endif
                    @if(! $mpesaConfigured) disabled title="M-Pesa unavailable" @endif>
                    <i class="bi bi-phone-vibrate me-1"></i> Pay premium (M-Pesa)
                </button>
                @if(!empty($mpesaConfigured) && ($mpesaTransactions ?? collect())->isNotEmpty())
                <div class="mpesa-tx-list border-top pt-3">
                    <div class="mpesa-tx-list-head mb-2">Recent payments</div>
                    @foreach(($mpesaTransactions ?? collect())->take(3) as $tx)
                    @php
                        $st = $tx->status;
                        $iconClass = match ($st) {
                            'success' => 'success',
                            'pending' => 'pending',
                            'cancelled' => 'cancelled',
                            default => 'failed',
                        };
                        $icon = match ($st) {
                            'success' => 'bi-check-lg',
                            'pending' => 'bi-hourglass-split',
                            'cancelled' => 'bi-x-lg',
                            default => 'bi-exclamation-lg',
                        };
                    @endphp
                    <div class="mpesa-tx-item">
                        <div class="mpesa-tx-icon {{ $iconClass }}"><i class="bi {{ $icon }}"></i></div>
                        <div class="mpesa-tx-body">
                            <div class="mpesa-tx-amount">KES {{ number_format((float) $tx->amount, 0) }}</div>
                            <div class="mpesa-tx-meta">{{ $tx->created_at?->format('d M Y') ?? '—' }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <a href="{{ $clientTabUrl('premiums') }}" class="btn btn-sm btn-link px-0 mt-2">View all premiums & receipts</a>
                @else
                <a href="{{ $clientTabUrl('premiums') }}" class="btn btn-sm btn-outline-secondary w-100">Premium receipts</a>
                @endif
            </div>
        </div>
    </div>
</div>

@elseif($tab === 'details')
@include('support.partials.client-details-tab')

@elseif($tab === 'updates')
@include('support.partials.client-comments')
@if($contact ?? null)
@include('contacts.partials.activities-related-list', [
    'activitiesPageRoute' => 'support.clients.show',
    'activitiesPageParams' => $clientShowBase,
    'activitiesTab' => 'updates',
])
@else
<div class="card contact-detail-card mb-4">
    <div class="card-body p-5 text-center">
        <i class="bi bi-calendar3 display-6 text-muted d-block mb-3"></i>
        <h6 class="mb-2">Tasks &amp; events require a linked CRM prospect</h6>
        <p class="text-muted mb-3">Activities are stored against CRM prospects. No prospect is linked to policy <code>{{ $clientPolicy }}</code> yet.</p>
        <a href="{{ route('support.clients.create-ticket', ['policy' => $clientPolicy]) }}" class="btn btn-primary btn-sm">Create ticket for this client</a>
    </div>
</div>
@endif

@elseif($tab === 'tickets')
@include('support.partials.client-tickets-tab')

@elseif($tab === 'policies')
@include('support.partials.client-policies-tab')

@elseif($tab === 'premiums')
@include('support.partials.client-premiums-tab')

@elseif($tab === 'calls')
<div class="card contact-detail-card mb-4">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Calls (PBX)</h6>
            @if($clientPhone)
            <a href="tel:{{ tel_href($clientPhone) }}" class="btn btn-success btn-sm"><i class="bi bi-telephone-outbound-fill me-1"></i>Call</a>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase fw-bold">Status</th>
                        <th class="small text-uppercase fw-bold">Direction</th>
                        <th class="small text-uppercase fw-bold">Number</th>
                        <th class="small text-uppercase fw-bold">Agent</th>
                        <th class="small text-uppercase fw-bold">Duration</th>
                        <th class="small text-uppercase fw-bold">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($calls ?? [] as $call)
                    <tr>
                        <td><span class="badge">{{ $call->call_status ?? '—' }}</span></td>
                        <td>{{ $call->direction ?? '—' }}</td>
                        <td class="font-monospace">{{ $call->customer_number ?? '—' }}</td>
                        <td>{{ $call->user_name ?? '—' }}</td>
                        <td>{{ $call->duration_sec ?? 0 }}s</td>
                        <td class="text-nowrap">{{ optional($call->start_time)->format('d M Y H:i') ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No PBX calls found for this client.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(($callsPaginator ?? null) && $callsPaginator->hasPages())
        <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light">
            {{ $callsPaginator->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

@elseif($tab === 'sms')
<div class="card contact-detail-card mb-4">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">SMS sent</h6>
            @if($clientPhone)
            <a href="{{ route('support.sms-notifier', $contact ? ['contact_id' => $contact->contactid] : ['phone' => $clientPhone, 'policy' => $clientPolicy !== '—' ? $clientPolicy : null]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-send-fill me-1"></i>Send SMS</a>
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
                            <span class="badge bg-danger bg-opacity-10 text-danger">Failed</span>
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
        @if(($smsPaginator ?? null) && $smsPaginator->hasPages())
        <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light">
            {{ $smsPaginator->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

@elseif($tab === 'emails')
@include('support.partials.client-emails-tab')
@endif

<style>
.contact-detail-header { margin-bottom: 0; }
.client-profile-hero {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 55%, #f0f6fc 100%);
    box-shadow: 0 4px 24px rgba(14, 67, 133, 0.08);
}
.client-profile-breadcrumb a { text-decoration: none; }
.client-profile-breadcrumb a:hover { color: var(--agile-primary, #0E4385) !important; }
.contact-avatar-lg {
    width: 84px; height: 84px; border-radius: 20px;
    background: linear-gradient(145deg, #1A468A 0%, #0E4385 100%);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.55rem; font-weight: 700; flex-shrink: 0;
    box-shadow: 0 8px 20px rgba(14, 67, 133, 0.25);
}
.client-module-tabs-shell {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(14, 67, 133, 0.05);
    overflow: hidden;
}
.client-module-tabs {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.15rem;
    border: none;
}
.client-module-tabs .nav-item.client-module-tabs-divider {
    width: 1px;
    height: 1.75rem;
    margin: 0 0.35rem;
    padding: 0;
    background: var(--agile-border, #e2e8f0);
    align-self: center;
    pointer-events: none;
    flex: 0 0 1px;
}
@media (max-width: 767.98px) {
    .client-module-tabs .nav-item.client-module-tabs-divider { display: none; }
}
.contact-module-tabs { border-bottom: none; }
.contact-module-tabs .nav-link {
    color: var(--text-muted, #64748b); font-weight: 500;
    padding: 0.7rem 1rem; border: none; border-radius: 10px;
    margin-bottom: 0;
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

/* Details tab */
.client-details-page { display: flex; flex-direction: column; gap: 1.25rem; }

.client-personal-hero {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 50%, #f0f6fc 100%);
    border: 1px solid rgba(14, 67, 133, 0.12);
    overflow: hidden;
}
.client-personal-hero-grid {
    display: grid;
    grid-template-columns: auto 1fr minmax(140px, 200px);
    gap: 1.25rem 1.5rem;
    align-items: start;
}
@media (max-width: 767.98px) {
    .client-personal-hero-grid { grid-template-columns: 1fr; }
    .client-personal-hero-product { grid-row: auto; }
}
.client-personal-hero-avatar {
    width: 72px; height: 72px; border-radius: 18px;
    background: linear-gradient(145deg, #1A468A 0%, #0E4385 100%);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; font-weight: 700;
    box-shadow: 0 8px 20px rgba(14, 67, 133, 0.22);
}
.client-personal-hero-eyebrow {
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em;
    text-transform: uppercase; color: var(--agile-text-muted, #64748b);
}
.client-personal-hero-name {
    font-size: 1.45rem; font-weight: 700; color: var(--agile-text, #1e293b); line-height: 1.25;
}
.client-personal-hero-chips { display: flex; flex-wrap: wrap; gap: 0.45rem; align-items: center; }
.client-personal-hero-policy {
    font-size: 0.8rem; color: var(--agile-text-muted, #64748b);
    background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 6px;
}
.client-personal-hero-contacts { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.client-personal-contact-pill {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.4rem 0.75rem; border-radius: 999px;
    background: #fff; border: 1px solid #e2e8f0;
    font-size: 0.82rem; font-weight: 600; color: var(--agile-primary, #0E4385);
    text-decoration: none; transition: border-color 0.15s, box-shadow 0.15s;
}
.client-personal-contact-pill:hover { border-color: var(--agile-primary, #0E4385); box-shadow: 0 2px 8px rgba(14, 67, 133, 0.1); color: var(--agile-primary-dark, #0a3266); }
.client-personal-contact-pill.is-static { color: #475569; font-weight: 500; cursor: default; }
.client-personal-contact-pill.is-static:hover { box-shadow: none; border-color: #e2e8f0; }
.client-personal-hero-product {
    background: rgba(14, 67, 133, 0.05); border: 1px solid rgba(14, 67, 133, 0.1);
    border-radius: 12px; padding: 0.85rem 1rem;
}
.client-personal-hero-product-label {
    display: block; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; color: var(--agile-text-muted, #64748b); margin-bottom: 0.35rem;
}
.client-personal-hero-product-value {
    font-size: 0.88rem; font-weight: 600; color: var(--agile-text, #1e293b); line-height: 1.4;
}

.client-payments-zone {
    display: flex; flex-direction: column; gap: 1rem;
    padding: 1.25rem 1.35rem 0;
    background: linear-gradient(180deg, #f8fdf9 0%, #fff 100%);
    border: 1px solid #bbf7d0; border-radius: 16px;
}
.client-payments-zone-head {
    display: flex; flex-wrap: wrap; align-items: center; gap: 1rem;
}
.client-payments-zone-icon {
    width: 2.75rem; height: 2.75rem; border-radius: 12px;
    background: linear-gradient(145deg, #30B54A, #1a7f37);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.client-payments-zone-icon-sm {
    width: 2rem; height: 2rem; border-radius: 8px; font-size: 0.95rem;
}
.client-payments-zone-title { font-size: 1rem; font-weight: 700; color: #14532d; }
.client-payments-zone-desc { font-size: 0.82rem; color: #4b5563; }
.client-payments-total-paid {
    margin-left: auto; text-align: right;
    background: #fff; border: 1px solid #a7f3d0; border-radius: 12px;
    padding: 0.65rem 1rem;
}
.client-payments-total-label {
    display: block; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; color: #047857;
}
.client-payments-total-value { font-size: 1.05rem; font-weight: 700; color: #065f46; }
.client-payments-zone .client-mpesa-pay-card { margin-bottom: 1.25rem !important; }

.client-summary-personal-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem 1.25rem;
}
@media (max-width: 575.98px) { .client-summary-personal-grid { grid-template-columns: 1fr; } }
.client-summary-field { display: flex; flex-direction: column; gap: 0.2rem; }
.client-summary-label {
    font-size: 0.68rem; font-weight: 600; letter-spacing: 0.04em;
    text-transform: uppercase; color: var(--agile-text-muted, #64748b);
}
.client-summary-value { font-size: 0.95rem; font-weight: 600; color: var(--agile-text, #1e293b); }
.client-summary-name { font-size: 1.1rem; color: var(--agile-primary, #0E4385); }
.client-summary-payments { border-color: #bbf7d0; background: linear-gradient(180deg, #f8fdf9 0%, #fff 100%); }

.client-documents-grid { display: flex; flex-direction: column; gap: 1rem; }
.client-document-item {
    border: 1px solid var(--agile-border, #e2e8f0);
    border-radius: 12px;
    padding: 1rem;
    background: #fafbfc;
}
.client-document-item-head { display: flex; align-items: flex-start; gap: 0.75rem; }
.client-document-item-icon { font-size: 1.35rem; line-height: 1; margin-top: 0.1rem; }
.client-document-item-meta { min-width: 0; flex: 1; }
.client-document-preview-img {
    max-width: 100%; max-height: 220px; border-radius: 8px;
    border: 1px solid var(--agile-border, #e2e8f0); object-fit: contain;
}
.client-document-preview-pdf {
    width: 100%; height: 280px; border: 1px solid var(--agile-border, #e2e8f0);
    border-radius: 8px; background: #fff;
}
.client-document-item-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }

.client-details-highlights {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.85rem;
}
@media (max-width: 991.98px) { .client-details-highlights { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 575.98px) { .client-details-highlights { grid-template-columns: 1fr; } }
.client-details-highlight {
    background: #fff;
    border: 1px solid var(--agile-border, #e2e8f0);
    border-radius: 14px;
    padding: 1rem 1.15rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}
.client-details-highlight-accent {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-color: #a7f3d0;
}
.client-details-highlight-label {
    display: block;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--agile-text-muted, #64748b);
    margin-bottom: 0.35rem;
}
.client-details-highlight-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--agile-text, #1e293b);
    line-height: 1.35;
}
.client-details-money-lg { font-size: 1.05rem; color: #047857; }
.client-details-muted { color: #94a3b8; font-weight: 400; }
.client-details-shell { overflow: hidden; }
.client-details-block { padding: 1.35rem 1.5rem 1.5rem; }
.client-details-block-divider { border-top: 1px solid var(--agile-border, #e2e8f0); }
.client-details-block-head {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.15rem;
}
.client-details-block-icon {
    width: 2.35rem; height: 2.35rem; border-radius: 10px;
    background: linear-gradient(145deg, rgba(14, 67, 133, 0.12) 0%, rgba(14, 67, 133, 0.06) 100%);
    color: var(--agile-primary, #0E4385);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem;
}
.client-details-block-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--agile-text, #1e293b);
}
.client-details-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}
@media (max-width: 767.98px) { .client-details-fields { grid-template-columns: 1fr; } }
.client-details-field {
    display: flex;
    gap: 0.85rem;
    align-items: flex-start;
    padding: 0.95rem 1rem;
    border-radius: 12px;
    border: 1px solid #eef2f7;
    background: #fafbfc;
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}
.client-details-field:hover {
    border-color: rgba(14, 67, 133, 0.18);
    background: #fff;
    box-shadow: 0 4px 14px rgba(14, 67, 133, 0.06);
}
.client-details-field.is-empty {
    background: #fcfdfe;
    border-style: dashed;
    border-color: #e8edf3;
}
.client-details-field.is-empty:hover { box-shadow: none; }
.client-details-field.is-featured {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, rgba(14, 67, 133, 0.06) 0%, rgba(14, 67, 133, 0.02) 100%);
    border-color: rgba(14, 67, 133, 0.14);
}
.client-details-field-icon {
    width: 2rem; height: 2rem; border-radius: 8px;
    background: #fff;
    border: 1px solid #e8edf3;
    color: var(--agile-primary, #0E4385);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; flex-shrink: 0;
}
.client-details-field.is-empty .client-details-field-icon { color: #94a3b8; opacity: 0.7; }
.client-details-field-label {
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--agile-text-muted, #64748b);
    margin-bottom: 0.25rem;
}
.client-details-field-value {
    font-size: 0.92rem;
    color: var(--agile-text, #1e293b);
    line-height: 1.45;
    word-break: break-word;
}
.client-details-name { font-size: 1.08rem; font-weight: 700; color: var(--agile-primary, #0E4385); }
.client-details-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.88rem; font-weight: 600;
    color: var(--agile-primary, #0E4385);
}
.client-details-money { font-weight: 700; color: #047857; }
.client-details-action-link {
    color: var(--agile-primary, #0E4385);
    font-weight: 600;
    text-decoration: none;
}
.client-details-action-link:hover { text-decoration: underline; }
.client-details-linked {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
    padding: 0.85rem 1.15rem;
    border-radius: 12px;
    background: rgba(14, 67, 133, 0.06);
    border: 1px solid rgba(14, 67, 133, 0.12);
    font-size: 0.88rem;
    color: var(--agile-text-muted, #64748b);
}
.client-details-linked i { color: var(--agile-primary, #0E4385); font-size: 1.1rem; }

.client-status-badge { font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 6px; display: inline-block; }
.clients-system-badge { display: inline-block; padding: 0.2rem 0.55rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600; }
.clients-system-badge.clients-system-group { background: #ccfbf1; color: #0f766e; }
.clients-system-badge.clients-system-individual { background: #e0e7ff; color: #4338ca; }
.clients-system-badge.clients-system-mortgage { background: #ffedd5; color: #9a3412; }
.clients-system-badge.clients-system-group_pension { background: #ede9fe; color: #5b21b6; }
.client-status-active, .clients-status-active { background: #dcfce7; color: #166534; }
.client-status-lapsed, .clients-status-lapsed { background: #fee2e2; color: #991b1b; }
.client-status-other, .clients-status-other { background: #f1f5f9; color: #475569; }
.ticket-status-badge { font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 4px; }
.ticket-status-open { background: var(--agile-primary-muted, rgba(14, 67, 133, 0.12)); color: var(--agile-primary, #0E4385); }
.ticket-status-closed { background: rgba(5, 150, 105, 0.15); color: #059669; }
.tickets-badge-open, .tickets-badge-Open { background: rgba(14, 67, 133, 0.12); color: var(--primary, #0E4385); }
.tickets-badge-in-progress, .tickets-badge-In-Progress { background: rgba(245, 158, 11, 0.2); color: #d97706; }
.tickets-badge-closed, .tickets-badge-Closed { background: rgba(5, 150, 105, 0.15); color: #059669; }
.tickets-badge-wait-for-response, .tickets-badge-Wait-For-Response { background: rgba(56, 189, 248, 0.2); color: #0ea5e9; }
.summary-empty-box { background: rgba(0,0,0,0.02); border-radius: 8px; min-height: 60px; }

.client-consent-card { border-left: 4px solid var(--agile-primary, #0E4385); }
.client-consent-head {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    margin-bottom: 1rem;
}
.client-consent-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(14, 67, 133, 0.1);
    color: var(--agile-primary, #0E4385);
    flex-shrink: 0;
}
.client-consent-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--agile-text, #0f172a);
}
.client-consent-intro {
    font-size: 0.88rem;
    color: var(--agile-text-muted, #64748b);
}
.client-consent-control {
    padding-left: calc(42px + 0.85rem);
}
@media (max-width: 575.98px) {
    .client-consent-control { padding-left: 0; }
}
.client-consent-check .form-check-input {
    width: 1.15rem;
    height: 1.15rem;
    margin-top: 0.15rem;
    cursor: pointer;
}
.client-consent-check .form-check-label {
    font-size: 0.92rem;
    line-height: 1.45;
    color: var(--agile-text, #0f172a);
    cursor: pointer;
}
.client-consent-meta {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin-top: 0.75rem;
    font-size: 0.82rem;
    color: var(--agile-text-muted, #64748b);
}
.client-consent-meta .bi-check-circle-fill { color: #059669; }
.client-consent-meta .bi-exclamation-circle { color: #d97706; }
.client-consent-header-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
}
.client-consent-header-badge.is-granted {
    background: #dcfce7;
    color: #166534;
}
.client-consent-header-badge.is-pending {
    background: #fef3c7;
    color: #92400e;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkbox = document.getElementById('clientConsentCheckbox');
    if (!checkbox) return;

    var meta = document.getElementById('clientConsentMeta');
    var status = document.getElementById('clientConsentStatus');
    var headerBadge = document.getElementById('clientConsentHeaderBadge');
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var saving = false;

    function formatRecordedAt(iso) {
        if (!iso) return '';
        var d = new Date(iso);
        if (isNaN(d.getTime())) return '';
        return d.toLocaleString(undefined, { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function setMeta(granted, byName, atIso) {
        if (!meta) return;
        if (granted) {
            var when = formatRecordedAt(atIso);
            var by = (byName || '').trim();
            meta.innerHTML = '<i class="bi bi-check-circle-fill"></i><span>Recorded'
                + (when ? ' ' + when : '')
                + (by ? ' by ' + by : '')
                + '</span>';
        } else {
            meta.innerHTML = '<i class="bi bi-exclamation-circle"></i><span>No consent recorded yet.</span>';
        }
    }

    function setHeaderBadge(granted) {
        if (!headerBadge) return;
        headerBadge.classList.toggle('is-granted', granted);
        headerBadge.classList.toggle('is-pending', !granted);
        headerBadge.innerHTML = granted
            ? '<i class="bi bi-shield-check"></i> Consent on file'
            : '<i class="bi bi-shield-exclamation"></i> No consent recorded';
    }

    function setStatus(message, isError) {
        if (!status) return;
        status.textContent = message || '';
        status.classList.toggle('text-danger', !!isError);
        status.classList.toggle('text-muted', !isError);
        status.classList.toggle('d-none', !message);
    }

    checkbox.addEventListener('change', function () {
        if (saving) return;
        var granted = checkbox.checked;
        var previous = !granted;
        saving = true;
        checkbox.disabled = true;
        setStatus('Saving…', false);

        fetch(checkbox.dataset.url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                policy: checkbox.dataset.policy,
                consent: granted
            })
        })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (res) {
                if (!res.ok || res.data.error) {
                    throw new Error(res.data.error || 'Could not save consent.');
                }
                setMeta(res.data.consent_granted, res.data.consented_by_name, res.data.consented_at);
                setHeaderBadge(res.data.consent_granted);
                setStatus('Saved.', false);
                window.setTimeout(function () { setStatus('', false); }, 1800);
            })
            .catch(function (err) {
                checkbox.checked = previous;
                setMeta(previous, '', null);
                setHeaderBadge(previous);
                setStatus(err.message || 'Save failed.', true);
            })
            .finally(function () {
                saving = false;
                checkbox.disabled = false;
            });
    });
});
</script>

@include('support.partials.client-mpesa-stk-modal', [
    'mpesaPolicyNumber' => ($tab ?? '') === 'premiums' ? ($selectedPremiumPolicy ?? $clientPolicy ?? $policy) : null,
])

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($tabCountsDeferred ?? false)
    (function() {
        var policy = @json($clientPolicy ?? $policy ?? '');
        if (!policy) return;
        var commentsCount = {{ (int) ($commentsCount ?? 0) }};
        var params = new URLSearchParams({ policy: policy });
        @if($system ?? null)
        params.set('system', @json($system));
        @endif
        fetch(@json(route('api.support.clients.tab-counts')) + '?' + params.toString(), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function(r) { return r.ok ? r.json() : null; })
        .then(function(d) {
            if (!d) return;
            function setBadge(key, value) {
                var el = document.querySelector('[data-tab-badge="' + key + '"]');
                if (!el) return;
                var n = Number(value) || 0;
                if (key === 'updates') {
                    n = (Number(d.activitiesCount) || 0) + commentsCount;
                }
                el.textContent = String(n);
                el.hidden = n <= 0;
            }
            setBadge('tickets', d.ticketsCount);
            setBadge('emails', d.emailsCount);
            setBadge('policies', d.policiesCount);
            setBadge('premiums', d.premiumsCount);
            setBadge('sms', d.smsCount);
            setBadge('updates', null);
        })
        .catch(function() {});
    })();
    @endif

    var hash = window.location.hash;
    if (hash && (hash === '#client-documents' || hash === '#client-comments')) {
        var target = document.querySelector(hash);
        if (target) {
            setTimeout(function() { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 150);
        }
    }

    document.querySelectorAll('[data-dismiss-toast]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var toast = btn.closest('.client-toast');
            if (toast) {
                toast.classList.remove('show');
                setTimeout(function() { toast.remove(); }, 350);
            }
        });
    });

    var modalEl = document.getElementById('mpesaStkModal');
    var amountInput = document.getElementById('mpesa_amount');
    var statusEl = document.getElementById('mpesaStkStatus');
    var statusTitle = document.getElementById('mpesaStkStatusTitle');
    var statusDetail = document.getElementById('mpesaStkStatusDetail');
    var pageToast = document.getElementById('mpesaPageToast');
    var pageToastTitle = document.getElementById('mpesaPageToastTitle');
    var pageToastMessage = document.getElementById('mpesaPageToastMessage');
    var pageToastProgress = document.getElementById('mpesaPageToastProgress');
    var pendingId = @json(session('mpesa_stk_transaction_id'));
    var statusUrlBase = @json(url('/support/clients/mpesa-stk'));
    var previewAmountEl = document.querySelector('#mpesaModalPhonePreview [data-mpesa-preview-amount]');

    function formatKes(val) {
        var n = parseInt(String(val).replace(/\D/g, ''), 10);
        return isNaN(n) || n < 1 ? 'KES —' : 'KES ' + n.toLocaleString();
    }

    function syncPreviewAmount() {
        if (previewAmountEl && amountInput) {
            previewAmountEl.textContent = formatKes(amountInput.value);
        }
    }

    function setQuickActive(amt) {
        document.querySelectorAll('.mpesa-quick-amt').forEach(function(btn) {
            btn.classList.toggle('is-active', amt && btn.getAttribute('data-amt') === String(amt));
        });
    }

    function setMpesaStatus(kind, title, detail, receipt) {
        if (!statusEl) return;
        statusEl.classList.remove('d-none', 'is-pending', 'is-success', 'is-warning', 'is-danger');
        statusEl.classList.add('is-' + kind);
        if (statusTitle) statusTitle.textContent = title;
        if (statusDetail) {
            statusDetail.innerHTML = detail
                + (receipt ? ' <span class="client-mpesa-receipt-pill">' + receipt + '</span>' : '');
        }
        var icon = statusEl.querySelector('.client-mpesa-status-icon i');
        if (icon) {
            icon.className = kind === 'success' ? 'bi bi-check-circle-fill'
                : (kind === 'warning' ? 'bi bi-x-circle'
                : (kind === 'danger' ? 'bi bi-exclamation-triangle-fill' : 'bi bi-phone-vibrate'));
        }
    }

    function updatePageToast(kind, title, detail) {
        if (!pageToast) return;
        pageToast.classList.remove('client-toast-mpesa-sent', 'client-toast-mpesa-done', 'client-toast-mpesa-failed');
        if (kind === 'success') pageToast.classList.add('client-toast-mpesa-done');
        else if (kind === 'danger' || kind === 'warning') pageToast.classList.add('client-toast-mpesa-failed');
        else pageToast.classList.add('client-toast-mpesa-sent');
        if (pageToastTitle) pageToastTitle.textContent = title;
        if (pageToastMessage) pageToastMessage.textContent = detail;
        if (pageToastProgress) {
            pageToastProgress.style.display = kind === 'pending' ? 'inline-flex' : 'none';
        }
    }

    if (amountInput) {
        amountInput.addEventListener('input', function() {
            syncPreviewAmount();
            setQuickActive(amountInput.value);
        });
        syncPreviewAmount();
    }

    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function(e) {
            if (statusEl) statusEl.classList.add('d-none');
            var trigger = e.relatedTarget;
            if (trigger && trigger.getAttribute('data-mpesa-amount') && amountInput) {
                amountInput.value = trigger.getAttribute('data-mpesa-amount');
                syncPreviewAmount();
                setQuickActive(trigger.getAttribute('data-mpesa-amount'));
            }
        });
        if (pendingId) {
            var bsModal = window.bootstrap && bootstrap.Modal ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
            if (bsModal) bsModal.show();
        }
    }

    document.querySelectorAll('.mpesa-quick-amt').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (amountInput) {
                amountInput.value = btn.getAttribute('data-amt') || '';
                syncPreviewAmount();
                setQuickActive(amountInput.value);
            }
        });
    });

    if (pendingId && statusEl) {
        setMpesaStatus('pending', 'Waiting for M-Pesa PIN', 'STK sent — the payment prompt should appear on the phone now.');
        if (pageToast) updatePageToast('pending', 'STK push sent', @json(session('success')));
        var polls = 0;
        var timer = setInterval(function() {
            polls++;
            fetch(statusUrlBase + '/' + pendingId, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'pending') return;
                    clearInterval(timer);
                    if (data.status === 'success') {
                        setMpesaStatus('success', 'Payment received', 'Premium paid successfully.', data.mpesa_receipt_number || '');
                        updatePageToast('success', 'Payment received', data.mpesa_receipt_number
                            ? 'Receipt ' + data.mpesa_receipt_number
                            : 'M-Pesa payment completed.');
                    } else if (data.status === 'cancelled') {
                        setMpesaStatus('warning', 'Payment cancelled', 'The client cancelled the prompt on their phone.');
                        updatePageToast('warning', 'Payment cancelled', 'No charge was made.');
                    } else {
                        setMpesaStatus('danger', 'Payment failed', data.result_desc || 'The M-Pesa request did not complete.');
                        updatePageToast('danger', 'Payment failed', data.result_desc || 'Try sending the STK push again.');
                    }
                })
                .catch(function() {});
            if (polls >= 40) {
                clearInterval(timer);
                setMpesaStatus('pending', 'Still waiting', 'No update yet — refresh the page or check the phone again.');
            }
        }, 3000);
    }
});
</script>
@endpush
@endsection
