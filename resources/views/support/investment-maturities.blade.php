@extends('layouts.app')

@section('title', 'Investment Maturities')

@push('head')
<style>
    .inv-hero {
        background: linear-gradient(135deg, var(--agile-primary-dark, #122952) 0%, var(--agile-primary, #1B3F7A) 55%, #2563eb 100%);
        border-radius: 16px;
        color: #fff;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    .inv-hero::after {
        content: '';
        position: absolute;
        right: -2rem;
        top: -2rem;
        width: 12rem;
        height: 12rem;
        border-radius: 50%;
        background: rgba(255,255,255,0.07);
        pointer-events: none;
    }
    .inv-hero-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 12px;
        background: rgba(255,255,255,0.16);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }
    .inv-hero-links a {
        color: rgba(255,255,255,0.92);
        text-decoration: none;
        font-size: 0.8125rem;
        font-weight: 500;
    }
    .inv-hero-links a:hover { color: #fff; text-decoration: underline; }

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

    .inv-table-card {
        border-top: 3px solid var(--agile-primary, #1B3F7A);
        overflow: hidden;
    }
    .inv-table-card .table thead th {
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
    .inv-table-card .table tbody td { vertical-align: middle; padding-top: 0.85rem; padding-bottom: 0.85rem; }
    .inv-table-card .table tbody tr { transition: background 0.12s ease; }
    .inv-table-card .table tbody tr:hover { background: #f8fafc; }

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

    .inv-contact-line {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8125rem;
        line-height: 1.4;
    }
    .inv-notify-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .inv-notify-badge--done { background: var(--agile-primary-muted, rgba(27,63,122,0.08)); color: var(--agile-primary, #1B3F7A); }
    .inv-notify-badge--pending { background: #f1f5f9; color: #64748b; }

    .mat-actions {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.25rem;
    }
    .mat-actions .btn { border-radius: 8px; }

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
        color: var(--agile-primary, #1B3F7A);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
    }

    .inv-action-panel { width: min(560px, 100vw); }
    .inv-panel-header {
        background: linear-gradient(135deg, var(--agile-primary-dark, #122952) 0%, var(--agile-primary, #1B3F7A) 55%, #2563eb 100%);
        color: #fff;
        padding: 1.25rem 1.35rem;
    }
    .inv-panel-eyebrow {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        opacity: 0.85;
    }
    .inv-panel-meta { font-size: 0.875rem; opacity: 0.95; }
    .inv-product-badge {
        display: inline-block;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.16);
        font-size: 0.75rem;
        font-weight: 600;
    }
    .inv-panel-body { background: #f8fafc; }
    .inv-panel-client { background: #fff; border-bottom: 1px solid var(--agile-border, #e2e8f0); }
    .inv-panel-avatar {
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
    .inv-contact-card {
        background: #f8fafc;
        border: 1px solid var(--agile-border, #e2e8f0);
        border-radius: 10px;
        padding: 0.65rem 0.75rem;
        height: 100%;
    }
    .inv-action-block {
        background: #fff;
        border: 1px solid var(--agile-border, #e2e8f0);
        border-radius: 14px;
        padding: 1rem 1.1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .inv-action-block-head {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }
    .inv-action-icon {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .inv-search-wide { min-width: min(100%, 420px); flex: 1 1 320px; }
    .inv-table-card .table thead th a { color: inherit; text-decoration: none; }
    .inv-table-card .table thead th a:hover { color: var(--agile-primary, #1B3F7A); }
    .inv-manage-btn { font-weight: 600; }
</style>
@endpush

@section('content')
@php
    $stats = $stats ?? ['total' => $rows->total(), 'today' => 0, 'this_week' => 0, 'pending_notify' => 0];
    $hasActiveFilters = $search || ($product ?? '') || ($notifyStatus ?? '');
    $productCodes = $productCodes ?? config('maturities.investment_notifications.product_codes', []);
    $sortUrl = function (string $column) use ($sort, $dir) {
        $newDir = ($sort ?? '') === $column && ($dir ?? 'asc') === 'asc' ? 'desc' : 'asc';

        return route('support.investment-maturities', array_merge(request()->except(['sort', 'dir', 'page']), [
            'sort' => $column,
            'dir' => $newDir,
        ]));
    };
@endphp

<nav class="mb-3">
    <a href="{{ route('support') }}" class="text-muted small text-decoration-none">Support</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Investment maturities</span>
</nav>

<div class="inv-hero">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 position-relative" style="z-index:1">
        <div class="d-flex align-items-start gap-3">
            <div class="inv-hero-icon" aria-hidden="true"><i class="bi bi-piggy-bank-fill"></i></div>
            <div>
                <h1 class="h3 fw-bold mb-1">Investment Maturities</h1>
                <p class="mb-2 opacity-90 small" style="max-width:36rem">
                    Investment policies reaching full maturity in the next <strong>{{ $days }} days</strong>.
                    Search and filter the list, then use <strong>Manage</strong> to email discharge vouchers, send reminders, or SMS clients.
                </p>
                <div class="inv-hero-links d-flex flex-wrap gap-3">
                    <a href="{{ route('support.maturities') }}"><i class="bi bi-calendar2-event me-1"></i>Maturing policies</a>
                    <a href="{{ route('support.mortgage-renewals') }}"><i class="bi bi-house-heart me-1"></i>Mortgage renewals</a>
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('support.investment-maturities.send') }}">
            @csrf
            <input type="hidden" name="days" value="{{ $days }}">
            <button type="submit" class="btn btn-light btn-sm fw-semibold d-inline-flex align-items-center gap-1 shadow-sm" title="Email internal staff list">
                <i class="bi bi-envelope-paper"></i> Email staff list
            </button>
        </form>
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
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                <span class="mat-stat-label">Client notify pending</span>
                <span class="mat-stat-icon" style="background:#f1f5f9;color:#64748b"><i class="bi bi-bell"></i></span>
            </div>
            <div class="mat-stat-value">{{ number_format($stats['pending_notify']) }}</div>
            <p class="text-muted small mb-0 mt-1">Not yet emailed or SMS'd</p>
        </div>
    </div>
</div>

<div class="mat-toolbar mb-3">
    <div class="d-flex flex-wrap align-items-end gap-3">
        <form method="GET" action="{{ route('support.investment-maturities') }}" class="d-flex flex-column gap-1">
            @foreach(request()->except(['days', 'page']) as $k => $v)
                @if($v !== null && $v !== '') <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach
            <label class="form-label mb-0">Time window</label>
            <select name="days" class="form-select form-select-sm" style="min-width:9rem" onchange="this.form.submit()">
                @foreach([7, 14, 21, 30] as $d)
                    <option value="{{ $d }}" {{ $days === $d ? 'selected' : '' }}>{{ $d }} days</option>
                @endforeach
            </select>
        </form>
        @if(($products ?? collect())->isNotEmpty())
        <form method="GET" action="{{ route('support.investment-maturities') }}" class="d-flex flex-column gap-1">
            @foreach(request()->except(['product', 'page']) as $k => $v)
                @if($v !== null && $v !== '') <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach
            <label class="form-label mb-0">Product</label>
            <select name="product" class="form-select form-select-sm" style="min-width:12rem;max-width:16rem" onchange="this.form.submit()">
                <option value="">All products</option>
                @foreach($products as $p)
                    <option value="{{ $p }}" {{ ($product ?? '') === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
        </form>
        @endif
        <form method="GET" action="{{ route('support.investment-maturities') }}" class="d-flex flex-column gap-1">
            @foreach(request()->except(['notify_status', 'page']) as $k => $v)
                @if($v !== null && $v !== '') <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach
            <label class="form-label mb-0">Client notified</label>
            <select name="notify_status" class="form-select form-select-sm" style="min-width:10rem" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="pending" {{ ($notifyStatus ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="notified" {{ ($notifyStatus ?? '') === 'notified' ? 'selected' : '' }}>Notified</option>
            </select>
        </form>
        <form method="GET" action="{{ route('support.investment-maturities') }}" class="d-flex flex-column gap-1 inv-search-wide">
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
        @if($search)
            <a href="{{ route('support.investment-maturities', request()->except(['search', 'page'])) }}" class="mat-filter-chip">
                Search: {{ \Illuminate\Support\Str::limit($search, 24) }} <i class="bi bi-x"></i>
            </a>
        @endif
        @if($product ?? '')
            <a href="{{ route('support.investment-maturities', request()->except(['product', 'page'])) }}" class="mat-filter-chip">
                {{ \Illuminate\Support\Str::limit($product, 28) }} <i class="bi bi-x"></i>
            </a>
        @endif
        @if($notifyStatus ?? '')
            <a href="{{ route('support.investment-maturities', request()->except(['notify_status', 'page'])) }}" class="mat-filter-chip">
                {{ $notifyStatus === 'pending' ? 'Pending notify' : 'Notified' }} <i class="bi bi-x"></i>
            </a>
        @endif
        <a href="{{ route('support.investment-maturities', ['days' => $days]) }}" class="small text-muted ms-auto">Clear all</a>
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
@if ($error)
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-octagon-fill me-1"></i>{{ $error }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (! $trackingEnabled)
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        Staff notification tracking table missing. Run <code>php artisan migrate</code>.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (! ($smsConfigured ?? false))
    <div class="alert alert-info py-2 small mb-4 alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-1"></i>SMS requires Advanta credentials in <code>.env</code>. Email works via Graph/SMTP.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="app-card inv-table-card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th scope="col">
                        <a href="{{ $sortUrl('policy') }}" class="d-inline-flex align-items-center gap-1">
                            Policy @if(($sort ?? '') === 'policy')<i class="bi bi-chevron-{{ ($dir ?? 'asc') === 'desc' ? 'down' : 'up' }}"></i>@endif
                        </a>
                    </th>
                    <th scope="col">
                        <a href="{{ $sortUrl('maturity') }}" class="d-inline-flex align-items-center gap-1">
                            Maturity @if(($sort ?? 'maturity') === 'maturity')<i class="bi bi-chevron-{{ ($dir ?? 'asc') === 'desc' ? 'down' : 'up' }}"></i>@endif
                        </a>
                    </th>
                    <th scope="col" class="text-nowrap">Countdown</th>
                    <th scope="col">
                        <a href="{{ $sortUrl('client') }}" class="d-inline-flex align-items-center gap-1">
                            Client @if(($sort ?? '') === 'client')<i class="bi bi-chevron-{{ ($dir ?? 'asc') === 'desc' ? 'down' : 'up' }}"></i>@endif
                        </a>
                    </th>
                    <th scope="col">
                        <a href="{{ $sortUrl('product') }}" class="d-inline-flex align-items-center gap-1">
                            Product @if(($sort ?? '') === 'product')<i class="bi bi-chevron-{{ ($dir ?? 'asc') === 'desc' ? 'down' : 'up' }}"></i>@endif
                        </a>
                    </th>
                    <th scope="col">Contact</th>
                    <th scope="col">Client notified</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                @php
                    $policy = trim((string) ($row->pol_policy_no ?? ''));
                    $maturityRaw = (string) ($row->pol_maturity_date ?? '');
                    $maturityYmd = '';
                    $maturity = '—';
                    try {
                        $maturityYmd = \Carbon\Carbon::parse($maturityRaw)->format('Y-m-d');
                        $maturity = \Carbon\Carbon::parse($maturityRaw)->format('d M Y');
                    } catch (\Throwable $e) {
                        $maturity = $maturityRaw !== '' ? $maturityRaw : '—';
                    }
                    $rawClientName = trim((string) ($row->full_name ?? ''));
                    $clientName = $rawClientName !== ''
                        ? \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($rawClientName))
                        : '—';
                    $product = trim((string) ($row->product ?? ''));
                    $rowContact = $notifyService->contactFromRow($row);
                    $clientEmail = trim((string) ($row->client_email ?? '')) ?: ($rowContact['email'] ?? '');
                    $clientPhone = trim((string) ($row->client_phone ?? '')) ?: ($rowContact['phone'] ?? '');
                    $notifySubject = ($policy && $maturity !== '—')
                        ? $notifyService->defaultSubject('maturity', $policy, $maturity)
                        : '';
                    $daysToMaturity = $maturityYmd !== ''
                        ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($maturityYmd)->startOfDay(), false)
                        : null;
                    $urgencyClass = match (true) {
                        $daysToMaturity !== null && $daysToMaturity < 0 => 'mat-row-urgency-overdue',
                        $daysToMaturity === 0 => 'mat-row-urgency-today',
                        $daysToMaturity !== null && $daysToMaturity <= 7 => 'mat-row-urgency-soon',
                        default => '',
                    };
                    $clientNotified = ! empty($row->client_notified_email) || ! empty($row->client_notified_sms);
                @endphp
                <tr class="{{ $urgencyClass }}">
                    <td><span class="mat-policy-no">{{ $policy !== '' ? $policy : '—' }}</span></td>
                    <td class="text-nowrap fw-medium">{{ $maturity }}</td>
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
                    <td>
                        <span class="mat-client-name d-inline-block text-truncate" title="{{ $clientName }}">{{ $clientName }}</span>
                    </td>
                    <td>
                        @if($product !== '')
                            <span class="mat-product-badge" title="{{ $product }}">{{ $product }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="small">
                        @if($clientEmail)
                            <div class="inv-contact-line"><i class="bi bi-envelope text-muted"></i><span class="text-truncate" style="max-width:160px" title="{{ $clientEmail }}">{{ $clientEmail }}</span></div>
                        @endif
                        @if($clientPhone)
                            <div class="inv-contact-line"><i class="bi bi-phone text-muted"></i><a href="tel:{{ tel_href($clientPhone) }}" class="text-decoration-none">{{ $clientPhone }}</a></div>
                        @endif
                        @if(! $clientEmail && ! $clientPhone)
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($clientNotified)
                            <div class="d-flex flex-wrap gap-1">
                                @if(!empty($row->client_notified_email))
                                    <span class="inv-notify-badge inv-notify-badge--done"><i class="bi bi-envelope-check"></i> Email</span>
                                @endif
                                @if(!empty($row->client_notified_sms))
                                    <span class="inv-notify-badge inv-notify-badge--done"><i class="bi bi-chat-dots-fill"></i> SMS</span>
                                @endif
                            </div>
                        @else
                            <span class="inv-notify-badge inv-notify-badge--pending"><i class="bi bi-hourglass-split"></i> Pending</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if($policy && $maturityYmd)
                            <div class="mat-actions">
                                <button type="button"
                                    class="btn btn-sm btn-primary inv-manage-btn inv-open-panel"
                                    data-bs-toggle="offcanvas"
                                    data-bs-target="#invMaturityPanel"
                                    data-policy="{{ $policy }}"
                                    data-maturity-ymd="{{ $maturityYmd }}"
                                    data-maturity-display="{{ $maturity }}"
                                    data-client-name="{{ $clientName !== '—' ? $clientName : $rawClientName }}"
                                    data-product="{{ $product }}"
                                    data-email="{{ $clientEmail }}"
                                    data-phone="{{ $clientPhone }}"
                                    data-subject="{{ $notifySubject }}"
                                    data-days="{{ $daysToMaturity }}"
                                    data-email-sent="{{ ! empty($row->client_notified_email) ? '1' : '0' }}"
                                    data-sms-sent="{{ ! empty($row->client_notified_sms) ? '1' : '0' }}"
                                    title="Open full client actions">
                                    <i class="bi bi-sliders me-1"></i>Manage
                                </button>
                                <a href="{{ route('support.maturities.discharge-voucher.pdf', ['policy_number' => $policy, 'maturity' => $maturityYmd]) }}"
                                    class="btn btn-sm btn-outline-danger" target="_blank" rel="noopener" title="Download discharge voucher (PDF)">
                                    <i class="bi bi-file-pdf"></i>
                                </a>
                                @include('support.partials.maturity-notify-buttons', [
                                    'notifyScreen' => 'investment',
                                    'notifyEventType' => 'maturity',
                                    'notifyPolicy' => $policy,
                                    'notifyEventDate' => $maturityYmd,
                                    'notifyClientName' => $rawClientName,
                                    'notifyProduct' => $product,
                                    'notifyEmail' => $clientEmail,
                                    'notifyPhone' => $clientPhone,
                                    'notifySubject' => $notifySubject,
                                    'emailSent' => ! empty($row->client_notified_email),
                                    'smsSent' => ! empty($row->client_notified_sms),
                                ])
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="p-0 border-0">
                        <div class="mat-empty-state">
                            <div class="mat-empty-icon"><i class="bi bi-piggy-bank"></i></div>
                            @if($hasActiveFilters)
                                <h6 class="fw-semibold mb-1">No matching policies</h6>
                                <p class="text-muted small mb-3">Try a different search term or clear your filters.</p>
                                <a href="{{ route('support.investment-maturities', ['days' => $days]) }}" class="btn btn-sm btn-outline-primary">Clear filters</a>
                            @else
                                <p class="text-muted mb-0">No investment policies maturing in the next {{ $days }} days.</p>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->total() > 0)
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-3 border-top bg-light">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="small text-muted">
                    Showing <strong>{{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }}</strong>
                    of <strong>{{ number_format($rows->total()) }}</strong>
                </span>
                <form method="GET" action="{{ route('support.investment-maturities') }}" class="d-inline">
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
            @if($rows->hasPages())
                {{ $rows->withQueryString()->links('pagination::bootstrap-5') }}
            @elseif($to)
                <span class="small text-muted">Staff list emails to <strong>{{ $to }}</strong>@if(!empty($cc)) + {{ count($cc) }} CC @endif</span>
            @endif
        </div>
    @endif
</div>

<details class="mat-footer-note mt-3 mb-0">
    <summary><i class="bi bi-info-circle me-1"></i>Data source &amp; actions guide</summary>
    <p class="text-muted small mt-2 mb-0">
        Policies are loaded from <strong>LMS_POLICIES</strong> using the full maturity date (<code>POL_MATURITY_DATE</code>),
        filtered to investment product codes
        @if(!empty($productCodes))
            ({{ implode(', ', $productCodes) }}).
        @else
            configured in <code>INVESTMENT_MATURITY_PRODUCT_CODES</code>.
        @endif
        This is separate from the partial maturities register on Maturing Policies.
        Use <strong>Manage</strong> to open the full client panel — send discharge voucher email, maturity reminder email, or SMS in one place.
        Green quick-action buttons mean the client was already contacted for this policy and date.
        @if(! $trackingEnabled)
            Run <code class="small">php artisan migrate</code> to enable staff email tracking.
        @endif
    </p>
</details>

@include('support.partials.maturity-notify-modal')
@include('support.partials.investment-maturity-panel')
@endsection
