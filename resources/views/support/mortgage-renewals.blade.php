@extends('layouts.app')

@section('title', 'Mortgage Renewals')

@push('head')
<style>
    .mat-hero {
        background: linear-gradient(135deg, var(--agile-primary-dark, #122952) 0%, var(--agile-primary, #1B3F7A) 55%, #2563eb 100%);
        border-radius: 16px;
        color: #fff;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    .mat-hero::after {
        content: '';
        position: absolute;
        right: -2rem;
        top: -2rem;
        width: 12rem;
        height: 12rem;
        border-radius: 50%;
        background: rgba(255,255,255,0.06);
        pointer-events: none;
    }
    .mat-hero-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 12px;
        background: rgba(255,255,255,0.15);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }
    .mat-hero-links a {
        color: rgba(255,255,255,0.92);
        text-decoration: none;
        font-size: 0.8125rem;
        font-weight: 500;
    }
    .mat-hero-links a:hover { color: #fff; text-decoration: underline; }

    .mat-stat-card {
        background: #fff;
        border: 1px solid var(--agile-border, #e2e8f0);
        border-radius: 14px;
        padding: 1rem 1.15rem;
        height: 100%;
        box-shadow: 0 1px 3px rgba(15,23,42,0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .mat-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(15,23,42,0.08);
    }
    .mat-stat-icon {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    .mat-stat-label {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--agile-text-muted, #64748b);
        margin-bottom: 0.15rem;
    }
    .mat-stat-value { font-size: 1.75rem; font-weight: 700; line-height: 1.1; color: var(--agile-text, #0f172a); }

    .mat-toolbar {
        background: #fff;
        border: 1px solid var(--agile-border, #e2e8f0);
        border-radius: 14px;
        padding: 0.85rem 1rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .mat-toolbar .form-label { font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--agile-text-muted); }
    .mat-filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        background: var(--agile-primary-muted, rgba(27,63,122,0.08));
        color: var(--agile-primary, #1B3F7A);
        font-size: 0.8125rem;
        font-weight: 500;
        text-decoration: none;
    }
    .mat-filter-chip:hover { background: rgba(27,63,122,0.14); color: var(--agile-primary-dark); }

    .mat-table-card {
        border-top: 3px solid var(--agile-primary, #1B3F7A);
        overflow: hidden;
    }
    .mat-table-card .table thead th {
        background: #f8fafc;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--agile-text-muted, #64748b);
        border-bottom-width: 1px;
        white-space: nowrap;
        padding-top: 0.85rem;
        padding-bottom: 0.85rem;
    }
    .mat-table-card .table tbody td { vertical-align: middle; padding-top: 0.85rem; padding-bottom: 0.85rem; }
    .mat-table-card .table tbody tr { transition: background 0.12s ease; }
    .mat-table-card .table tbody tr:hover { background: #f8fafc; }

    .mat-row-urgency-today { box-shadow: inset 3px 0 0 #f59e0b; }
    .mat-row-urgency-overdue { box-shadow: inset 3px 0 0 #ef4444; }
    .mat-row-urgency-soon { box-shadow: inset 3px 0 0 #3b82f6; }

    .mat-policy-no {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--agile-primary, #1B3F7A);
    }
    .mat-client-name { font-weight: 500; color: var(--agile-text, #0f172a); max-width: 220px; }
    .mat-product-badge {
        display: inline-block;
        max-width: 180px;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        background: #f1f5f9;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }
    .mat-days-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .mat-days-pill--today { background: #fef3c7; color: #b45309; }
    .mat-days-pill--overdue { background: #fee2e2; color: #b91c1c; }
    .mat-days-pill--soon { background: #dbeafe; color: #1d4ed8; }
    .mat-days-pill--normal { background: #f1f5f9; color: #475569; }

    .mat-contact-line {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8125rem;
        line-height: 1.4;
    }
    .mat-notify-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .mat-notify-badge--done { background: var(--agile-primary-muted, rgba(27,63,122,0.08)); color: var(--agile-primary, #1B3F7A); }
    .mat-notify-badge--pending { background: #f1f5f9; color: #64748b; }

    .mat-actions {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.25rem;
    }
    .mat-actions .btn { border-radius: 8px; }
    .mat-manage-btn { font-weight: 600; }

    .mat-footer-note summary { cursor: pointer; color: var(--agile-text-muted); font-size: 0.8125rem; }
    .mat-empty-state { padding: 3.5rem 1.5rem; text-align: center; }
    .mat-empty-icon {
        width: 4rem;
        height: 4rem;
        margin: 0 auto 1rem;
        border-radius: 50%;
        background: var(--agile-primary-muted, rgba(27,63,122,0.08));
        color: var(--agile-primary, #1B3F7A);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
    }

    .mat-action-panel { width: min(560px, 100vw); }
    .mat-panel-header {
        background: linear-gradient(135deg, var(--agile-primary-dark, #122952) 0%, var(--agile-primary, #1B3F7A) 55%, #2563eb 100%);
        color: #fff;
        padding: 1.25rem 1.35rem;
    }
    .mat-panel-eyebrow {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        opacity: 0.85;
    }
    .mat-panel-meta { font-size: 0.875rem; opacity: 0.95; }
    .mat-product-badge-light {
        display: inline-block;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.16);
        font-size: 0.75rem;
        font-weight: 600;
    }
    .mat-panel-body { background: #f8fafc; }
    .mat-panel-client { background: #fff; border-bottom: 1px solid var(--agile-border, #e2e8f0); }
    .mat-panel-avatar {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 12px;
        background: var(--agile-primary-muted, rgba(27,63,122,0.08));
        color: var(--agile-primary, #1B3F7A);
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .mat-contact-card {
        background: #f8fafc;
        border: 1px solid var(--agile-border, #e2e8f0);
        border-radius: 10px;
        padding: 0.65rem 0.75rem;
        height: 100%;
    }
    .mat-action-block {
        background: #fff;
        border: 1px solid var(--agile-border, #e2e8f0);
        border-radius: 14px;
        padding: 1rem 1.1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .mat-action-block-head { display: flex; align-items: flex-start; gap: 0.75rem; }
    .mat-action-icon {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .mat-search-wide { min-width: min(100%, 420px); flex: 1 1 320px; }
</style>
@endpush

@section('content')
@php
    $stats = $stats ?? ['total' => (int) ($customers->total() ?? 0), 'today' => 0, 'this_week' => 0, 'pending_notify' => 0];
    $hasActiveFilters = ! empty($search);
    $statusLabel = fn (string $code) => match ($code) {
        'A' => 'Active',
        'FL' => 'Lapsed',
        default => $code !== '' ? $code : '—',
    };
@endphp

<nav class="mb-3">
    <a href="{{ route('support') }}" class="text-muted small text-decoration-none">Support</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Mortgage renewals</span>
</nav>

<div class="mat-hero">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 position-relative" style="z-index:1">
        <div class="d-flex align-items-start gap-3">
            <div class="mat-hero-icon" aria-hidden="true"><i class="bi bi-house-heart-fill"></i></div>
            <div>
                <h1 class="h3 fw-bold mb-1">Mortgage Renewals</h1>
                <p class="mb-2 opacity-90 small" style="max-width:36rem">
                    Mortgages due for renewal in the next <strong>{{ $window }} days</strong>
                    (through {{ $renewalDateEnd->format('d M Y') }}).
                    Search the list, then use <strong>Manage</strong> to email or SMS clients and create tickets.
                </p>
                <div class="mat-hero-links d-flex flex-wrap gap-3">
                    <a href="{{ route('support.maturities') }}"><i class="bi bi-calendar2-event me-1"></i>Maturing policies</a>
                    <a href="{{ route('support.investment-maturities') }}"><i class="bi bi-piggy-bank me-1"></i>Investment maturities</a>
                    <a href="{{ route('support.customers', ['system' => 'mortgage']) }}"><i class="bi bi-people me-1"></i>Full mortgage register</a>
                </div>
            </div>
        </div>
        @if (($mortgageConfigured ?? false) && ($useHttp ?? false) && ! ($pageError ?? null))
            <a href="{{ route('support.mortgage-renewals.export', request()->only(['window', 'search'])) }}" class="btn btn-light btn-sm fw-semibold d-inline-flex align-items-center gap-1 shadow-sm" title="Download all rows in this period as Excel">
                <i class="bi bi-file-earmark-excel text-success"></i> Export Excel
            </a>
        @endif
    </div>
</div>

@if (($mortgageConfigured ?? false) && ($useHttp ?? false) && ! ($pageError ?? null))
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                <span class="mat-stat-label">Total in window</span>
                <span class="mat-stat-icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-collection"></i></span>
            </div>
            <div class="mat-stat-value">{{ number_format($stats['total']) }}</div>
            <p class="text-muted small mb-0 mt-1">Next {{ $window }} days</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                <span class="mat-stat-label">Renewing today</span>
                <span class="mat-stat-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-alarm"></i></span>
            </div>
            <div class="mat-stat-value">{{ number_format($stats['today']) }}</div>
            <p class="text-muted small mb-0 mt-1">Needs attention now</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                <span class="mat-stat-label">This week</span>
                <span class="mat-stat-icon" style="background:#dbeafe;color:#2563eb"><i class="bi bi-calendar-week"></i></span>
            </div>
            <div class="mat-stat-value">{{ number_format($stats['this_week']) }}</div>
            <p class="text-muted small mb-0 mt-1">Within 7 days</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                <span class="mat-stat-label">Pending notify</span>
                <span class="mat-stat-icon" style="background:#f1f5f9;color:#64748b"><i class="bi bi-bell"></i></span>
            </div>
            <div class="mat-stat-value">{{ number_format($stats['pending_notify']) }}</div>
            <p class="text-muted small mb-0 mt-1">On this page</p>
        </div>
    </div>
</div>
@endif

<div class="mat-toolbar mb-3">
    <div class="d-flex flex-wrap align-items-end gap-3">
        <form method="GET" action="{{ route('support.mortgage-renewals') }}" class="d-flex flex-column gap-1">
            @foreach(request()->except(['window', 'page']) as $k => $v)
                @if($v !== null && $v !== '') <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach
            <label class="form-label mb-0">Time window</label>
            <select name="window" class="form-select form-select-sm" style="min-width:11rem" onchange="this.form.submit()">
                @foreach(\App\Http\Controllers\MortgageRenewalController::RENEWAL_WINDOWS as $w)
                    <option value="{{ $w }}" {{ $window === $w ? 'selected' : '' }}>{{ $w }} days</option>
                @endforeach
            </select>
        </form>
        <form method="GET" action="{{ route('support.mortgage-renewals') }}" class="d-flex flex-column gap-1 mat-search-wide">
            @foreach(request()->except(['search', 'page']) as $k => $v)
                @if($v !== null && $v !== '') <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach
            <label class="form-label mb-0">Search</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="search" name="search" class="form-control border-start-0" placeholder="Policy, client, product, email, phone…" value="{{ $search }}" aria-label="Search">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>
    @if($hasActiveFilters)
    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-3 border-top">
        <span class="small text-muted fw-semibold">Active filters:</span>
        <a href="{{ route('support.mortgage-renewals', request()->except(['search', 'page'])) }}" class="mat-filter-chip">
            Search: {{ \Illuminate\Support\Str::limit($search, 24) }} <i class="bi bi-x"></i>
        </a>
        <a href="{{ route('support.mortgage-renewals', ['window' => $window]) }}" class="small text-muted ms-auto">Clear all</a>
    </div>
    @endif
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if ($pageError ?? null)
    <div class="alert alert-warning alert-dismissible fade show mb-4 d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 mt-0 text-warning"></i>
        <div class="flex-grow-1">{{ $pageError }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (! ($smsConfigured ?? false))
    <div class="alert alert-info py-2 small mb-4 alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-1"></i>SMS requires Advanta credentials in <code>.env</code>. Email works via Graph/SMTP.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="app-card mat-table-card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th scope="col">Policy</th>
                    <th scope="col">Renewal</th>
                    <th scope="col">Countdown</th>
                    <th scope="col">Client</th>
                    <th scope="col">Product</th>
                    <th scope="col">Status</th>
                    <th scope="col">Contact</th>
                    <th scope="col">Client notified</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    @php
                        $rowPolicy = trim((string) ($customer->policy_no ?? $customer->policy_number ?? ''));
                        $renewRaw = $customer->mendr_renewal_date ?? $customer->maturity ?? null;
                        $renewYmd = '';
                        $renewStr = '—';
                        if ($renewRaw !== null && $renewRaw !== '') {
                            try {
                                $renewYmd = \Carbon\Carbon::parse($renewRaw)->format('Y-m-d');
                                $renewStr = \Carbon\Carbon::parse($renewRaw)->format('d M Y');
                            } catch (\Throwable $e) {
                                $renewStr = (string) $renewRaw;
                            }
                        }
                        $rawClientName = trim((string) ($customer->life_assur ?? $customer->client_name ?? ''));
                        $clientName = $rawClientName !== ''
                            ? \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($rawClientName))
                            : '—';
                        $productName = trim((string) ($customer->product ?? ''));
                        $rowContact = $notifyService->contactFromRow($customer);
                        $clientEmail = trim((string) ($customer->client_email ?? '')) ?: ($rowContact['email'] ?? '');
                        $clientPhone = trim((string) ($customer->client_phone ?? '')) ?: ($rowContact['phone'] ?? '');
                        $notifySubject = ($rowPolicy !== '' && $renewStr !== '—')
                            ? $notifyService->defaultSubject('renewal', $rowPolicy, $renewStr)
                            : '';
                        $st = strtoupper(trim((string) ($customer->status ?? '')));
                        $statusBadgeClass = match ($st) {
                            'A' => 'bg-success',
                            'FL' => 'bg-danger',
                            default => $st !== '' ? 'bg-secondary' : 'bg-light text-dark border',
                        };
                        $daysToRenewal = $renewYmd !== ''
                            ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($renewYmd)->startOfDay(), false)
                            : null;
                        $urgencyClass = match (true) {
                            $daysToRenewal !== null && $daysToRenewal < 0 => 'mat-row-urgency-overdue',
                            $daysToRenewal === 0 => 'mat-row-urgency-today',
                            $daysToRenewal !== null && $daysToRenewal <= 7 => 'mat-row-urgency-soon',
                            default => '',
                        };
                        $clientNotified = ! empty($customer->client_notified_email) || ! empty($customer->client_notified_sms);
                    @endphp
                    <tr class="{{ $urgencyClass }}">
                        <td><span class="mat-policy-no">{{ $rowPolicy !== '' ? $rowPolicy : '—' }}</span></td>
                        <td class="text-nowrap fw-medium">{{ $renewStr }}</td>
                        <td>
                            @if($daysToRenewal !== null)
                                @if($daysToRenewal < 0)
                                    <span class="mat-days-pill mat-days-pill--overdue"><i class="bi bi-exclamation-circle"></i> Overdue {{ abs($daysToRenewal) }}d</span>
                                @elseif($daysToRenewal === 0)
                                    <span class="mat-days-pill mat-days-pill--today"><i class="bi bi-alarm"></i> Today</span>
                                @elseif($daysToRenewal <= 7)
                                    <span class="mat-days-pill mat-days-pill--soon"><i class="bi bi-clock"></i> {{ $daysToRenewal }} days</span>
                                @else
                                    <span class="mat-days-pill mat-days-pill--normal">{{ $daysToRenewal }} days</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="mat-client-name d-inline-block text-truncate" title="{{ $clientName }}">{{ $clientName }}</span>
                        </td>
                        <td>
                            @if($productName !== '')
                                <span class="mat-product-badge" title="{{ $productName }}">{{ $productName }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($st !== '')
                                <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel($st) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="small">
                            @if($clientEmail)
                                <div class="mat-contact-line"><i class="bi bi-envelope text-muted"></i><span class="text-truncate" style="max-width:160px" title="{{ $clientEmail }}">{{ $clientEmail }}</span></div>
                            @endif
                            @if($clientPhone)
                                <div class="mat-contact-line"><i class="bi bi-phone text-muted"></i><a href="tel:{{ tel_href($clientPhone) }}" class="text-decoration-none">{{ $clientPhone }}</a></div>
                            @endif
                            @if(! $clientEmail && ! $clientPhone)
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($clientNotified)
                                <div class="d-flex flex-wrap gap-1">
                                    @if(!empty($customer->client_notified_email))
                                        <span class="mat-notify-badge mat-notify-badge--done"><i class="bi bi-envelope-check"></i> Email</span>
                                    @endif
                                    @if(!empty($customer->client_notified_sms))
                                        <span class="mat-notify-badge mat-notify-badge--done"><i class="bi bi-chat-dots-fill"></i> SMS</span>
                                    @endif
                                </div>
                            @else
                                <span class="mat-notify-badge mat-notify-badge--pending"><i class="bi bi-hourglass-split"></i> Pending</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($rowPolicy !== '' && $renewYmd)
                                <div class="mat-actions">
                                    <button type="button"
                                        class="btn btn-sm btn-primary mat-manage-btn mr-open-panel"
                                        data-bs-toggle="offcanvas"
                                        data-bs-target="#mortgageRenewalPanel"
                                        data-policy="{{ $rowPolicy }}"
                                        data-renewal-ymd="{{ $renewYmd }}"
                                        data-renewal-display="{{ $renewStr }}"
                                        data-client-name="{{ $clientName !== '—' ? $clientName : $rawClientName }}"
                                        data-product="{{ $productName }}"
                                        data-email="{{ $clientEmail }}"
                                        data-phone="{{ $clientPhone }}"
                                        data-subject="{{ $notifySubject }}"
                                        data-status="{{ $statusLabel($st) }}"
                                        data-days="{{ $daysToRenewal }}"
                                        data-email-sent="{{ ! empty($customer->client_notified_email) ? '1' : '0' }}"
                                        data-sms-sent="{{ ! empty($customer->client_notified_sms) ? '1' : '0' }}"
                                        title="Open full client actions">
                                        <i class="bi bi-sliders me-1"></i>Manage
                                    </button>
                                    @include('support.partials.maturity-notify-buttons', [
                                        'notifyScreen' => 'mortgage',
                                        'notifyEventType' => 'renewal',
                                        'notifyPolicy' => $rowPolicy,
                                        'notifyEventDate' => $renewYmd,
                                        'notifyClientName' => $rawClientName,
                                        'notifyProduct' => $productName,
                                        'notifyEmail' => $clientEmail,
                                        'notifyPhone' => $clientPhone,
                                        'notifySubject' => $notifySubject,
                                        'emailSent' => ! empty($customer->client_notified_email),
                                        'smsSent' => ! empty($customer->client_notified_sms),
                                    ])
                                </div>
                            @elseif($rowPolicy !== '')
                                <span class="text-muted small">No renewal date</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-0 border-0">
                            <div class="mat-empty-state">
                                <div class="mat-empty-icon"><i class="bi bi-house-heart"></i></div>
                                @if ($pageError ?? null)
                                    <p class="text-muted mb-0">Resolve the message above and refresh.</p>
                                @elseif($hasActiveFilters)
                                    <h6 class="fw-semibold mb-1">No matching mortgages</h6>
                                    <p class="text-muted small mb-3">Try a different search term or clear your filters.</p>
                                    <a href="{{ route('support.mortgage-renewals', ['window' => $window]) }}" class="btn btn-sm btn-outline-primary">Clear filters</a>
                                @else
                                    <h6 class="fw-semibold mb-1">All clear for now</h6>
                                    <p class="text-muted small mb-0">No mortgages with a renewal in the next {{ $window }} days.</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(($customers->total() ?? 0) > 0)
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-3 border-top bg-light">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="small text-muted">
                    Showing <strong>{{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }}</strong>
                    of <strong>{{ number_format($customers->total()) }}</strong>
                </span>
                <form method="GET" action="{{ route('support.mortgage-renewals') }}" class="d-inline">
                    @foreach(request()->except(['per_page', 'page']) as $k => $v)
                        @if($v !== null && $v !== '') <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
                    @endforeach
                    <select name="per_page" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        @foreach([25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>{{ $n }} / page</option>
                        @endforeach
                    </select>
                </form>
            </div>
            @if($customers->hasPages())
                {{ $customers->withQueryString()->links('pagination::bootstrap-5') }}
            @endif
        </div>
    @endif
</div>

<details class="mat-footer-note mt-3 mb-0">
    <summary><i class="bi bi-info-circle me-1"></i>Data source &amp; actions guide</summary>
    <p class="text-muted small mt-2 mb-0">
        This list shows mortgages whose <strong>renewal date</strong> falls between today and {{ $renewalDateEnd->format('d M Y') }} — not the full mortgage register.
        Use <strong>Manage</strong> to send renewal reminder emails or SMS, or create a support ticket.
        Green quick-action buttons mean the client was already contacted for this policy and renewal date.
        Full mortgage register: <a href="{{ route('support.customers', ['system' => 'mortgage']) }}">Support → Clients → Mortgage</a>.
    </p>
</details>

@include('support.partials.maturity-notify-modal')
@include('support.partials.mortgage-renewal-panel')
@endsection
