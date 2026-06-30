@extends('layouts.app')

@section('title', 'Complaint Register')

@push('head')
<style>
    .cmp-hero {
        background: linear-gradient(135deg, var(--agile-primary-dark, #122952) 0%, var(--agile-primary, #1B3F7A) 55%, #2563eb 100%);
        border-radius: 16px;
        color: #fff;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    .cmp-hero::after {
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
    .cmp-hero-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 12px;
        background: rgba(255,255,255,0.15);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }
    .mat-stat-card {
        background: #fff;
        border: 1px solid var(--agile-border, #e2e8f0);
        border-radius: 14px;
        padding: 1rem 1.15rem;
        height: 100%;
        box-shadow: 0 1px 3px rgba(15,23,42,0.04);
    }
    .mat-stat-label {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--agile-text-muted, #64748b);
    }
    .mat-stat-value { font-size: 1.75rem; font-weight: 700; line-height: 1.1; }
    .mat-stat-icon {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
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
    .cmp-register-pills { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .cmp-register-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.9rem;
        border-radius: 999px;
        background: #fff;
        border: 1px solid var(--agile-border, #e2e8f0);
        color: var(--agile-text);
        text-decoration: none;
        font-size: 0.8125rem;
        font-weight: 500;
    }
    .cmp-register-pill.active { background: var(--agile-primary); border-color: var(--agile-primary); color: #fff; }
    .cmp-table-card { border-top: 3px solid var(--agile-primary, #1B3F7A); overflow: hidden; }
    .cmp-table-card .table thead th {
        background: #f8fafc;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--agile-text-muted);
        white-space: nowrap;
        padding-top: 0.85rem;
        padding-bottom: 0.85rem;
    }
    .cmp-table-card .table tbody td { vertical-align: middle; padding-top: 0.85rem; padding-bottom: 0.85rem; }
    .cmp-ref-link { font-family: ui-monospace, monospace; font-weight: 600; color: var(--agile-primary); text-decoration: none; }
    .cmp-ref-link:hover { text-decoration: underline; }
    .cmp-summary { max-width: 280px; font-size: 0.8125rem; color: #475569; line-height: 1.45; }
    .cmp-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
        font-size: 0.6875rem;
        font-weight: 600;
    }
    .cmp-type-active { background: var(--agile-primary-muted); color: var(--agile-primary); }
    .cmp-type-review { background: #fef3c7; color: #b45309; }
    .cmp-type-excluded { background: #f1f5f9; color: #64748b; }
    .cmp-status-badge { font-size: 0.6875rem; font-weight: 600; padding: 0.25rem 0.55rem; border-radius: 999px; display: inline-block; }
    .cmp-status-received, .cmp-status-pending-response { background: #fef3c7; color: #b45309; }
    .cmp-status-under-investigation { background: #dbeafe; color: #1d4ed8; }
    .cmp-status-resolved { background: var(--agile-primary-muted); color: var(--agile-primary); }
    .cmp-status-closed, .cmp-status-escalated-to-ira { background: #f1f5f9; color: #64748b; }
    .mat-empty-state { padding: 3rem 1.5rem; text-align: center; }
    .mat-empty-icon {
        width: 4rem; height: 4rem; margin: 0 auto 1rem; border-radius: 50%;
        background: var(--agile-primary-muted); color: var(--agile-primary);
        display: flex; align-items: center; justify-content: center; font-size: 1.75rem;
    }
</style>
@endpush

@section('content')
@php
    $registerFilter = $registerFilter ?? 'complaints';
    $stats = $stats ?? ['total' => 0, 'open' => 0, 'review' => 0, 'excluded' => 0];
    $queryBase = request()->except(['page']);
@endphp

<nav class="mb-3">
    <a href="{{ route('support') }}" class="text-muted small text-decoration-none">Support</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Complaint Register</span>
</nav>

<div class="cmp-hero">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 position-relative" style="z-index:1">
        <div class="d-flex align-items-start gap-3">
            <div class="cmp-hero-icon" aria-hidden="true"><i class="bi bi-clipboard2-check-fill"></i></div>
            <div>
                <h1 class="h3 fw-bold mb-1">Complaint Register</h1>
                <p class="mb-0 opacity-90 small" style="max-width:36rem">
                    IRA compliance register for genuine customer complaints.
                    Inbound email is classified automatically — general inquiries and automated mail are kept out of the register.
                </p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('compliance.complaints.export', request()->query()) }}" class="btn btn-light btn-sm fw-semibold shadow-sm">
                <i class="bi bi-file-earmark-excel text-success me-1"></i>Export Excel
            </a>
            <a href="{{ route('compliance.complaints.create') }}" class="btn btn-light btn-sm fw-semibold shadow-sm">
                <i class="bi bi-plus-lg me-1"></i>Register Complaint
            </a>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="mat-stat-label">In register</span>
                <span class="mat-stat-icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-clipboard2-data"></i></span>
            </div>
            <div class="mat-stat-value">{{ number_format($stats['total']) }}</div>
            <p class="text-muted small mb-0 mt-1">Confirmed complaints</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="mat-stat-label">Open</span>
                <span class="mat-stat-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-hourglass-split"></i></span>
            </div>
            <div class="mat-stat-value">{{ number_format($stats['open']) }}</div>
            <p class="text-muted small mb-0 mt-1">Awaiting resolution</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="mat-stat-label">Needs review</span>
                <span class="mat-stat-icon" style="background:#fef3c7;color:#b45309"><i class="bi bi-search"></i></span>
            </div>
            <div class="mat-stat-value">{{ number_format($stats['review']) }}</div>
            <p class="text-muted small mb-0 mt-1">Confirm or exclude</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="mat-stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="mat-stat-label">Not complaints</span>
                <span class="mat-stat-icon" style="background:#f1f5f9;color:#64748b"><i class="bi bi-filter"></i></span>
            </div>
            <div class="mat-stat-value">{{ number_format($stats['excluded']) }}</div>
            <p class="text-muted small mb-0 mt-1">Filtered out</p>
        </div>
    </div>
</div>

@if($hasRegisterColumn ?? false)
<div class="cmp-register-pills mb-3">
    <a href="{{ route('compliance.complaints.index', array_merge($queryBase, ['register' => 'complaints'])) }}" class="cmp-register-pill {{ $registerFilter === 'complaints' ? 'active' : '' }}">
        Complaints <span class="opacity-75">{{ number_format($stats['total']) }}</span>
    </a>
    <a href="{{ route('compliance.complaints.index', array_merge($queryBase, ['register' => 'review'])) }}" class="cmp-register-pill {{ $registerFilter === 'review' ? 'active' : '' }}">
        Needs review <span class="opacity-75">{{ number_format($stats['review']) }}</span>
    </a>
    <a href="{{ route('compliance.complaints.index', array_merge($queryBase, ['register' => 'excluded'])) }}" class="cmp-register-pill {{ $registerFilter === 'excluded' ? 'active' : '' }}">
        Not a complaint <span class="opacity-75">{{ number_format($stats['excluded']) }}</span>
    </a>
    <a href="{{ route('compliance.complaints.index', array_merge($queryBase, ['register' => 'all'])) }}" class="cmp-register-pill {{ $registerFilter === 'all' ? 'active' : '' }}">All records</a>
</div>
@endif

<div class="mat-toolbar mb-3">
    <div class="d-flex flex-wrap align-items-end gap-3">
        <form method="GET" action="{{ route('compliance.complaints.index') }}" class="d-flex flex-column gap-1 flex-grow-1" style="min-width:240px;max-width:420px">
            @foreach(request()->except(['search', 'page']) as $k => $v)
                @if($v !== null && $v !== '') <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach
            <label class="form-label mb-0">Search</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="search" name="search" class="form-control border-start-0" placeholder="Ref, complainant, policy, email…" value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
        <form method="GET" action="{{ route('compliance.complaints.index') }}" class="d-flex flex-column gap-1">
            @foreach(request()->except(['status', 'page']) as $k => $v)
                @if($v !== null && $v !== '') <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach
            <label class="form-label mb-0">Status</label>
            <select name="status" class="form-select form-select-sm" style="min-width:10rem" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach(\App\Models\Complaint::STATUSES as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <form method="GET" action="{{ route('compliance.complaints.index') }}" class="d-flex flex-column gap-1">
            @foreach(request()->except(['nature', 'page']) as $k => $v)
                @if($v !== null && $v !== '') <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach
            <label class="form-label mb-0">Nature</label>
            <select name="nature" class="form-select form-select-sm" style="min-width:11rem" onchange="this.form.submit()">
                <option value="">All natures</option>
                @foreach(\App\Models\Complaint::NATURES as $val => $label)
                    <option value="{{ $val }}" {{ request('nature') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>
    @if(request('search') || request('status') || request('nature'))
    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-3 border-top">
        <span class="small text-muted fw-semibold">Active filters:</span>
        @if(request('search'))
            <a href="{{ route('compliance.complaints.index', request()->except(['search', 'page'])) }}" class="mat-filter-chip">Search: {{ \Illuminate\Support\Str::limit(request('search'), 20) }} <i class="bi bi-x"></i></a>
        @endif
        @if(request('status'))
            <a href="{{ route('compliance.complaints.index', request()->except(['status', 'page'])) }}" class="mat-filter-chip">{{ request('status') }} <i class="bi bi-x"></i></a>
        @endif
        @if(request('nature'))
            <a href="{{ route('compliance.complaints.index', request()->except(['nature', 'page'])) }}" class="mat-filter-chip">{{ \Illuminate\Support\Str::limit(request('nature'), 20) }} <i class="bi bi-x"></i></a>
        @endif
    </div>
    @endif
</div>

<div class="app-card cmp-table-card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Received</th>
                    <th>Complainant</th>
                    <th>Summary</th>
                    <th>Nature</th>
                    <th>Status</th>
                    @if($hasRegisterColumn ?? false)<th>Type</th>@endif
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($complaints as $c)
                    @php
                        $summary = $classifier->summary($c->description);
                        $registerStatus = $c->register_status ?? 'active';
                        $typeClass = match ($registerStatus) {
                            'review' => 'cmp-type-review',
                            'excluded' => 'cmp-type-excluded',
                            default => 'cmp-type-active',
                        };
                        $typeLabel = \App\Models\Complaint::REGISTER_STATUSES[$registerStatus] ?? 'Complaint';
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('compliance.complaints.show', $c) }}" class="cmp-ref-link">{{ $c->complaint_ref }}</a>
                            @if($c->policy_number)
                                <div class="small text-muted font-monospace">{{ $c->policy_number }}</div>
                            @endif
                        </td>
                        <td class="text-nowrap small text-muted">{{ $c->date_received?->format('d M Y') ?? '—' }}</td>
                        <td>
                            <div class="fw-medium">{{ $c->complainant_name }}</div>
                            @if($c->complainant_email)<div class="small text-muted">{{ $c->complainant_email }}</div>@endif
                        </td>
                        <td><div class="cmp-summary" title="{{ $summary }}">{{ $summary ?: '—' }}</div></td>
                        <td><span class="small text-muted">{{ $c->nature ?: '—' }}</span></td>
                        <td>
                            <span class="cmp-status-badge cmp-status-{{ Str::slug($c->status ?? '') }}">{{ $c->status ?? '—' }}</span>
                        </td>
                        @if($hasRegisterColumn ?? false)
                        <td>
                            <span class="cmp-type-badge {{ $typeClass }}">{{ $typeLabel }}</span>
                            @if($c->classification_score)
                                <div class="small text-muted">{{ $c->classification_score }}%</div>
                            @endif
                        </td>
                        @endif
                        <td class="text-end text-nowrap">
                            <a href="{{ route('compliance.complaints.show', $c) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                            @if($hasRegisterColumn ?? false)
                                @if($registerStatus !== 'active')
                                    <form method="POST" action="{{ route('compliance.complaints.register-status', $c) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="register_status" value="active">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Confirm as complaint"><i class="bi bi-check2"></i></button>
                                    </form>
                                @endif
                                @if($registerStatus !== 'excluded')
                                    <form method="POST" action="{{ route('compliance.complaints.register-status', $c) }}" class="d-inline" onsubmit="return confirm('Remove this from the complaint register?')">
                                        @csrf
                                        <input type="hidden" name="register_status" value="excluded">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Not a complaint"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ($hasRegisterColumn ?? false) ? 8 : 7 }}" class="p-0 border-0">
                            <div class="mat-empty-state">
                                <div class="mat-empty-icon"><i class="bi bi-clipboard2-x"></i></div>
                                <h6 class="fw-semibold mb-1">
                                    @if($registerFilter === 'excluded')
                                        No excluded items
                                    @elseif($registerFilter === 'review')
                                        Nothing needs review
                                    @else
                                        No complaints in the register
                                    @endif
                                </h6>
                                <p class="text-muted small mb-3">
                                    @if(request('search'))
                                        No matches for your search. General inquiries from email are filtered out automatically.
                                    @else
                                        Register a complaint manually or wait for classified inbound email.
                                    @endif
                                </p>
                                <a href="{{ route('compliance.complaints.create') }}" class="btn btn-sm btn-primary">Register Complaint</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($complaints->hasPages() || $complaints->total() > 0)
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-3 border-top bg-light">
            <span class="small text-muted">
                Showing {{ $complaints->firstItem() ?? 0 }}–{{ $complaints->lastItem() ?? 0 }} of {{ number_format($complaints->total()) }}
            </span>
            @if($complaints->hasPages())
                {{ $complaints->links('pagination::bootstrap-5') }}
            @endif
        </div>
    @endif
</div>

<details class="mt-3 mb-0">
    <summary class="small text-muted" style="cursor:pointer"><i class="bi bi-info-circle me-1"></i>How complaint detection works</summary>
    <p class="text-muted small mt-2 mb-0">
        Inbound client emails are scored for complaint language (dissatisfaction, dispute, escalation, etc.).
        Auto-replies, ticket confirmations, marketing, and general inquiries are excluded.
        Uncertain items appear under <strong>Needs review</strong> for a compliance officer to confirm or dismiss.
        Manual registrations are always treated as complaints.
    </p>
</details>
@endsection
