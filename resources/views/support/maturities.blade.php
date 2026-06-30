@extends('layouts.app')

@section('title', 'Maturing Policies')

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
    .mat-filter-chip .bi-x { opacity: 0.7; }

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
    .mat-table-card .table thead th a { color: inherit; }
    .mat-table-card .table thead th a:hover { color: var(--agile-primary); }
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

    .mat-actions {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.25rem;
    }
    .mat-actions .btn { border-radius: 8px; }
    .mat-actions-ticket { font-weight: 600; }

    .maturity-renewal-details { margin-top: 0.35rem; }
    .maturity-renewal-details > summary {
        cursor: pointer;
        list-style: none;
        font-size: 0.8125rem;
        color: var(--agile-primary, #1A468A);
        font-weight: 500;
    }
    .maturity-renewal-details > summary::-webkit-details-marker { display: none; }
    .maturity-renewal-details .renewal-form-panel {
        margin-top: 0.5rem;
        padding-top: 0.75rem;
        border-top: 1px solid var(--agile-border, #e2e8f0);
    }

    .mat-footer-note summary {
        cursor: pointer;
        color: var(--agile-text-muted);
        font-size: 0.8125rem;
    }
    .mat-empty-state {
        padding: 3.5rem 1.5rem;
        text-align: center;
    }
    .mat-empty-icon {
        width: 4rem;
        height: 4rem;
        margin: 0 auto 1rem;
        border-radius: 50%;
        background: var(--agile-primary-muted, rgba(27,63,122,0.08));
        color: var(--agile-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
    }
</style>
@endpush

@section('content')
@php
    $maturitiesColspan = ! empty($trackingEnabled) ? 7 : 6;
    $stats = $stats ?? ['total' => $policies->total(), 'today' => 0, 'this_week' => 0, 'pending_renewal' => 0];
    $hasActiveFilters = request('search') || request('product') || request('renewal_status');
@endphp

<nav class="mb-3">
    <a href="{{ route('support') }}" class="text-muted small text-decoration-none">Support</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Maturing Policies</span>
</nav>

<div class="mat-hero">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 position-relative" style="z-index:1">
        <div class="d-flex align-items-start gap-3">
            <div class="mat-hero-icon" aria-hidden="true"><i class="bi bi-calendar2-event-fill"></i></div>
            <div>
                <h1 class="h3 fw-bold mb-1">Maturing Policies</h1>
                <p class="mb-2 opacity-90 small" style="max-width:36rem">
                    Track policies reaching maturity in the next <strong>{{ $days }} days</strong>.
                    Notify clients, issue discharge vouchers, and create support tickets — all from one place.
                </p>
                <div class="mat-hero-links d-flex flex-wrap gap-3">
                    <a href="{{ route('support.investment-maturities') }}"><i class="bi bi-piggy-bank me-1"></i>Investment maturities</a>
                    <a href="{{ route('support.mortgage-renewals') }}"><i class="bi bi-house-heart me-1"></i>Mortgage renewals</a>
                </div>
            </div>
        </div>
        <a href="{{ route('support.maturities.export', request()->except(['per_page', 'page'])) }}" class="btn btn-light btn-sm fw-semibold d-inline-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-file-earmark-excel text-success"></i> Export Excel
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                <span class="mat-stat-label">Total in window</span>
                <span class="mat-stat-icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-collection"></i></span>
            </div>
            <div class="mat-stat-value">{{ number_format($stats['total']) }}</div>
            <p class="text-muted small mb-0 mt-1">Next {{ $days }} days</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                <span class="mat-stat-label">Maturing today</span>
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
    @if(!empty($trackingEnabled))
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                <span class="mat-stat-label">Renewal pending</span>
                <span class="mat-stat-icon" style="background:#f1f5f9;color:#64748b"><i class="bi bi-hourglass-split"></i></span>
            </div>
            <div class="mat-stat-value">{{ number_format($stats['pending_renewal']) }}</div>
            <p class="text-muted small mb-0 mt-1">Awaiting follow-up</p>
        </div>
    </div>
    @endif
</div>

<div class="mat-toolbar mb-3">
    <div class="d-flex flex-wrap align-items-end gap-3">
        <form method="GET" action="{{ route('support.maturities') }}" class="d-flex flex-column gap-1">
            @foreach(request()->except(['days', 'per_page', 'page']) as $k => $v)
                @if($v !== null && $v !== '') <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach
            <label class="form-label mb-0">Time window</label>
            <select name="days" class="form-select form-select-sm" style="min-width:9rem" onchange="this.form.submit()">
                @foreach([14, 30, 60, 90, 180, 365] as $d)
                    <option value="{{ $d }}" {{ $days == $d ? 'selected' : '' }}>{{ $d }} days</option>
                @endforeach
            </select>
        </form>
        @if(!empty($products))
        <form method="GET" action="{{ route('support.maturities') }}" class="d-flex flex-column gap-1">
            @foreach(request()->except(['product', 'per_page', 'page']) as $k => $v)
                @if($v !== null && $v !== '') <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach
            <label class="form-label mb-0">Product</label>
            <select name="product" class="form-select form-select-sm" style="min-width:14rem;max-width:18rem" onchange="this.form.submit()">
                <option value="">All products</option>
                @foreach($products as $p)
                    <option value="{{ $p }}" {{ ($product ?? '') === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
        </form>
        @endif
        @if(!empty($trackingEnabled))
        <form method="GET" action="{{ route('support.maturities') }}" class="d-flex flex-column gap-1">
            @foreach(request()->except(['renewal_status', 'per_page', 'page']) as $k => $v)
                @if($v !== null && $v !== '') <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach
            <label class="form-label mb-0">Renewal status</label>
            <select name="renewal_status" class="form-select form-select-sm" style="min-width:10rem" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach($renewalStatusLabels as $key => $label)
                    <option value="{{ $key }}" {{ ($renewalStatus ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        @endif
        <form method="GET" action="{{ route('support.maturities') }}" class="d-flex flex-column gap-1 flex-grow-1" style="min-width:220px;max-width:320px">
            @foreach(request()->except(['search', 'per_page', 'page']) as $k => $v)
                @if($v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach
            <label class="form-label mb-0">Search</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="search" name="search" class="form-control border-start-0" placeholder="Policy, client, product…" value="{{ request('search') }}" aria-label="Search">
                <button type="submit" class="btn btn-primary">Go</button>
            </div>
        </form>
    </div>
    @if($hasActiveFilters)
    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-3 border-top">
        <span class="small text-muted fw-semibold">Active filters:</span>
        @if(request('search'))
            <a href="{{ route('support.maturities', request()->except(['search', 'page'])) }}" class="mat-filter-chip">
                Search: {{ request('search') }} <i class="bi bi-x"></i>
            </a>
        @endif
        @if(request('product'))
            <a href="{{ route('support.maturities', request()->except(['product', 'page'])) }}" class="mat-filter-chip">
                {{ \Illuminate\Support\Str::limit(request('product'), 28) }} <i class="bi bi-x"></i>
            </a>
        @endif
        @if(request('renewal_status'))
            <a href="{{ route('support.maturities', request()->except(['renewal_status', 'page'])) }}" class="mat-filter-chip">
                {{ $renewalStatusLabels[request('renewal_status')] ?? request('renewal_status') }} <i class="bi bi-x"></i>
            </a>
        @endif
        <a href="{{ route('support.maturities', ['days' => $days]) }}" class="small text-muted ms-auto">Clear all</a>
    </div>
    @endif
</div>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <strong>Could not save renewal.</strong>
        <ul class="mb-0 small mt-2">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
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
@if (session('info'))
    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
        {{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="app-card mat-table-card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th scope="col">
                        <a href="{{ route('support.maturities', array_merge(request()->except(['sort', 'dir', 'page']), ['sort' => 'policy_number', 'dir' => ($sort ?? 'maturity') === 'policy_number' && ($dir ?? 'asc') === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none d-inline-flex align-items-center gap-1">
                            Policy @if(($sort ?? '') === 'policy_number')<i class="bi bi-chevron-{{ ($dir ?? 'asc') === 'desc' ? 'down' : 'up' }}"></i>@endif
                        </a>
                    </th>
                    <th scope="col">
                        <a href="{{ route('support.maturities', array_merge(request()->except(['sort', 'dir', 'page']), ['sort' => 'life_assured', 'dir' => ($sort ?? '') === 'life_assured' && ($dir ?? 'asc') === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none d-inline-flex align-items-center gap-1">
                            Client / Agent @if(($sort ?? '') === 'life_assured')<i class="bi bi-chevron-{{ ($dir ?? 'asc') === 'desc' ? 'down' : 'up' }}"></i>@endif
                        </a>
                    </th>
                    <th scope="col">
                        <a href="{{ route('support.maturities', array_merge(request()->except(['sort', 'dir', 'page']), ['sort' => 'product', 'dir' => ($sort ?? '') === 'product' && ($dir ?? 'asc') === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none d-inline-flex align-items-center gap-1">
                            Product @if(($sort ?? '') === 'product')<i class="bi bi-chevron-{{ ($dir ?? 'asc') === 'desc' ? 'down' : 'up' }}"></i>@endif
                        </a>
                    </th>
                    <th scope="col">
                        <a href="{{ route('support.maturities', array_merge(request()->except(['sort', 'dir', 'page']), ['sort' => 'maturity', 'dir' => ($sort ?? 'maturity') === 'maturity' && ($dir ?? 'asc') === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none d-inline-flex align-items-center gap-1">
                            Maturity @if(($sort ?? 'maturity') === 'maturity')<i class="bi bi-chevron-{{ ($dir ?? 'asc') === 'desc' ? 'down' : 'up' }}"></i>@endif
                        </a>
                    </th>
                    <th scope="col" class="text-nowrap">Countdown</th>
                    @if(!empty($trackingEnabled))
                    <th scope="col">
                        <a href="{{ route('support.maturities', array_merge(request()->except(['sort', 'dir', 'page']), ['sort' => 'renewal_status', 'dir' => ($sort ?? '') === 'renewal_status' && ($dir ?? 'asc') === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none d-inline-flex align-items-center gap-1">
                            Renewal @if(($sort ?? '') === 'renewal_status')<i class="bi bi-chevron-{{ ($dir ?? 'asc') === 'desc' ? 'down' : 'up' }}"></i>@endif
                        </a>
                    </th>
                    @endif
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($policies as $row)
                    @php
                        $policy = trim($row->policy_number ?? '');
                        $rawClient = trim($row->life_assur ?? $row->life_assured ?? '') ?: trim(($row->pol_prepared_by ?? '') . ' ' . ($row->intermediary ?? '')) ?: '—';
                        $clientName = $rawClient !== '—' ? \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($rawClient)) : '—';
                        $product = $row->product ?? '—';
                        $maturity = $row->maturity ?? null;
                        $maturityFormatted = $maturity ? \Carbon\Carbon::parse($maturity)->format('d M Y') : '—';
                        $daysToMaturity = ($maturity ?? null)
                            ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($maturity)->startOfDay(), false)
                            : null;
                        $urgencyClass = match (true) {
                            $daysToMaturity !== null && $daysToMaturity < 0 => 'mat-row-urgency-overdue',
                            $daysToMaturity === 0 => 'mat-row-urgency-today',
                            $daysToMaturity !== null && $daysToMaturity <= 7 => 'mat-row-urgency-soon',
                            default => '',
                        };
                        $renewalKey = $row->renewal_status ?? null;
                        if ($renewalKey === null || $renewalKey === '') {
                            $renewalKey = 'pending';
                        }
                        $renewalLabel = ($renewalStatusLabels[$renewalKey] ?? ucfirst(str_replace('_', ' ', $renewalKey)));
                        $dvMaturityYmd = $maturity ? \Carbon\Carbon::parse($maturity)->format('Y-m-d') : null;
                        $rowContact = $notifyService->contactFromRow($row);
                        $dvPrefillEmail = trim((string) ($rowContact['email'] ?? ''));
                        if ($dvPrefillEmail !== '' && ! filter_var($dvPrefillEmail, FILTER_VALIDATE_EMAIL)) {
                            $dvPrefillEmail = '';
                        }
                        $clientPhone = trim((string) ($rowContact['phone'] ?? ''));
                        $notifySubject = ($policy && $maturityFormatted !== '—')
                            ? $notifyService->defaultSubject('maturity', $policy, $maturityFormatted)
                            : '';
                    @endphp
                    <tr class="{{ $urgencyClass }}">
                        <td><span class="mat-policy-no">{{ $policy }}</span></td>
                        <td><span class="mat-client-name d-inline-block text-truncate" title="{{ $clientName }}">{{ $clientName }}</span></td>
                        <td>
                            @if($product !== '—')
                                <span class="mat-product-badge" title="{{ $product }}">{{ $product }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-nowrap fw-medium">{{ $maturityFormatted }}</td>
                        <td>
                            @if($daysToMaturity !== null)
                                @if($daysToMaturity < 0)
                                    <span class="mat-days-pill mat-days-pill--overdue"><i class="bi bi-exclamation-circle"></i> Overdue {{ abs($daysToMaturity) }}d</span>
                                @elseif($daysToMaturity === 0)
                                    <span class="mat-days-pill mat-days-pill--today"><i class="bi bi-alarm"></i> Today</span>
                                @elseif($daysToMaturity <= 7)
                                    <span class="mat-days-pill mat-days-pill--soon"><i class="bi bi-clock"></i> {{ $daysToMaturity }} days</span>
                                @else
                                    <span class="mat-days-pill mat-days-pill--normal">{{ $daysToMaturity }} days</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        @if(!empty($trackingEnabled))
                        <td class="align-top small">
                            @php
                                $badgeClass = match ($renewalKey) {
                                    'renewed' => 'bg-success',
                                    'lapsed', 'not_renewing' => 'bg-secondary',
                                    'in_progress' => 'bg-primary',
                                    default => 'bg-light text-dark border',
                                };
                            @endphp
                            <div><span class="badge {{ $badgeClass }}">{{ $renewalLabel }}</span></div>
                            @if($maturity)
                                <details class="maturity-renewal-details">
                                    <summary><i class="bi bi-pencil-square me-1"></i>Update renewal</summary>
                                    <div class="renewal-form-panel bg-light rounded-2 p-2 p-md-3">
                                        <form method="post" action="{{ route('support.maturities.renewal-status') }}" class="row g-2 align-items-end">
                                            @csrf
                                            <input type="hidden" name="policy_number" value="{{ $policy }}">
                                            <input type="hidden" name="maturity" value="{{ \Carbon\Carbon::parse($maturity)->format('Y-m-d') }}">
                                            <div class="col-12 col-md-4 col-lg-2">
                                                <label class="form-label small text-muted mb-0">Status</label>
                                                <select name="renewal_status" class="form-select form-select-sm" required>
                                                    @foreach($renewalStatusLabels as $key => $lbl)
                                                        <option value="{{ $key }}" {{ $renewalKey === $key ? 'selected' : '' }}>{{ $lbl }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-4 col-lg-2">
                                                <label class="form-label small text-muted mb-0">Renewal date</label>
                                                <input type="date" name="renewal_date" class="form-control form-control-sm" value="{{ !empty($row->renewal_date) ? \Carbon\Carbon::parse($row->renewal_date)->format('Y-m-d') : '' }}">
                                            </div>
                                            <div class="col-12 col-md-12 col-lg-5">
                                                <label class="form-label small text-muted mb-0">Notes</label>
                                                <input type="text" name="notes" class="form-control form-control-sm" maxlength="5000" value="{{ $row->renewal_notes ?? '' }}" placeholder="Internal notes">
                                            </div>
                                            <div class="col-12 col-md-4 col-lg-3 text-lg-end">
                                                <button type="submit" class="btn btn-primary btn-sm">Save renewal</button>
                                            </div>
                                        </form>
                                    </div>
                                </details>
                            @endif
                        </td>
                        @endif
                        <td class="text-end">
                            <div class="mat-actions">
                                @if($dvMaturityYmd)
                                    <a href="{{ route('support.maturities.discharge-voucher.pdf', ['policy_number' => $policy, 'maturity' => $dvMaturityYmd]) }}" class="btn btn-sm btn-outline-danger" target="_blank" rel="noopener" title="Download discharge voucher (PDF)">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Email discharge voucher"
                                        data-bs-toggle="modal" data-bs-target="#dvEmailModal"
                                        data-dv-policy="{{ $policy }}" data-dv-maturity="{{ $dvMaturityYmd }}" data-dv-email="{{ $dvPrefillEmail }}">
                                        <i class="bi bi-envelope"></i>
                                    </button>
                                @endif
                                @include('support.partials.maturity-notify-buttons', [
                                    'notifyScreen' => 'maturities',
                                    'notifyEventType' => 'maturity',
                                    'notifyPolicy' => $policy,
                                    'notifyEventDate' => $dvMaturityYmd ?? '',
                                    'notifyClientName' => $clientName !== '—' ? $clientName : '',
                                    'notifyProduct' => $product !== '—' ? $product : '',
                                    'notifyEmail' => $dvPrefillEmail,
                                    'notifyPhone' => $clientPhone,
                                    'notifySubject' => $notifySubject,
                                    'emailSent' => ! empty($row->client_notified_email),
                                    'smsSent' => ! empty($row->client_notified_sms),
                                ])
                                <a href="{{ route('support.clients.create-ticket', ['policy' => $policy]) }}" class="btn btn-sm btn-success mat-actions-ticket" title="Create ticket">
                                    <i class="bi bi-ticket-perforated me-1"></i>Ticket
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $maturitiesColspan }}">
                            <div class="mat-empty-state">
                                <div class="mat-empty-icon"><i class="bi bi-calendar-x"></i></div>
                                <h6 class="fw-semibold mb-1">{{ $hasActiveFilters ? 'No matching policies' : 'All clear for now' }}</h6>
                                <p class="text-muted small mb-3 mx-auto" style="max-width:28rem">
                                    @if(request('search'))
                                        Nothing matches "{{ request('search') }}". Try a different term or clear the filter.
                                    @elseif(request('product'))
                                        No maturing policies for this product in the next {{ $days }} days.
                                    @else
                                        No policies maturing within the next {{ $days }} days. Try widening the time window.
                                    @endif
                                </p>
                                @if($hasActiveFilters)
                                    <a href="{{ route('support.maturities', ['days' => $days]) }}" class="btn btn-sm btn-outline-primary">Clear filters</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($policies->hasPages() || $policies->total() > 0)
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-3 border-top bg-light">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="small text-muted">Showing <strong>{{ $policies->firstItem() ?? 0 }}–{{ $policies->lastItem() ?? 0 }}</strong> of <strong>{{ number_format($policies->total()) }}</strong></span>
                <form method="GET" action="{{ route('support.maturities') }}" class="d-inline">
                    @foreach(request()->except(['per_page', 'page']) as $k => $v)
                        @if($v !== null && $v !== '') <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
                    @endforeach
                    <select name="per_page" class="form-select form-select-sm" style="width: auto" onchange="this.form.submit()">
                        <option value="25" {{ ($perPage ?? 50) == 25 ? 'selected' : '' }}>25 / page</option>
                        <option value="50" {{ ($perPage ?? 50) == 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ ($perPage ?? 50) == 100 ? 'selected' : '' }}>100 / page</option>
                    </select>
                </form>
            </div>
            @if($policies->hasPages())
                {{ $policies->withQueryString()->links('pagination::bootstrap-5') }}
            @endif
        </div>
    @endif
</div>

<details class="mat-footer-note mt-3 mb-0">
    <summary><i class="bi bi-info-circle me-1"></i>Data source &amp; actions guide</summary>
    <p class="text-muted small mt-2 mb-0">
        Data from {{ \Illuminate\Support\Facades\Schema::hasTable('maturities_cache') && \Illuminate\Support\Facades\DB::table('maturities_cache')->exists() ? 'maturities cache' : (\Illuminate\Support\Facades\Schema::hasTable('erp_clients_cache') ? 'ERP cache' : 'ERP API') }}.
        Auto-tickets use the same source order: maturities cache, then ERP cache, then API.
        @if(!empty($trackingEnabled))
            Renewal status is stored in the CRM database and kept when the cache is refreshed.
        @else
            Run <code class="small">php artisan migrate</code> to enable renewal tracking.
        @endif
        Green email/SMS buttons mean the client was already notified for this policy and date.
    </p>
</details>

@include('support.partials.maturity-notify-modal')

<div class="modal fade" id="dvEmailModal" tabindex="-1" aria-labelledby="dvEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="{{ route('support.maturities.discharge-voucher.email') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="dvEmailModalLabel">Email discharge voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="policy_number" id="dv_modal_policy" value="">
                    <input type="hidden" name="maturity" id="dv_modal_maturity" value="">
                    <div class="mb-2 small text-muted">Policy <span class="font-monospace fw-semibold text-dark" id="dv_modal_policy_label"></span> · maturity <span id="dv_modal_maturity_label"></span></div>
                    <div class="mb-3">
                        <label for="dv_modal_to_email" class="form-label small mb-0">Recipient email</label>
                        <input type="email" class="form-control form-control-sm" name="to_email" id="dv_modal_to_email" required placeholder="client@example.com" value="{{ old('to_email') }}">
                    </div>
                    <div class="mb-3">
                        <label for="dv_modal_to_name" class="form-label small mb-0">Recipient name (optional)</label>
                        <input type="text" class="form-control form-control-sm" name="to_name" id="dv_modal_to_name" placeholder="Life assured name" value="{{ old('to_name') }}">
                    </div>
                    <div class="mb-0">
                        <label for="dv_modal_message" class="form-label small mb-0">Cover message (optional)</label>
                        <textarea class="form-control form-control-sm" name="message" id="dv_modal_message" rows="3" maxlength="5000" placeholder="Defaults to a short standard message if left blank.">{{ old('message') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i> Send PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('dvEmailModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn || !btn.getAttribute) return;
        var pol = btn.getAttribute('data-dv-policy') || '';
        var mat = btn.getAttribute('data-dv-maturity') || '';
        var em = btn.getAttribute('data-dv-email') || '';
        var polEl = document.getElementById('dv_modal_policy');
        var matEl = document.getElementById('dv_modal_maturity');
        var polLab = document.getElementById('dv_modal_policy_label');
        var matLab = document.getElementById('dv_modal_maturity_label');
        var emailEl = document.getElementById('dv_modal_to_email');
        if (polEl) polEl.value = pol;
        if (matEl) matEl.value = mat;
        if (polLab) polLab.textContent = pol;
        if (matLab) matLab.textContent = mat;
        if (emailEl) emailEl.value = em || @json(old('to_email', ''));
    });
})();
</script>
@endpush
