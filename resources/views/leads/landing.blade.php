@extends('layouts.app')

@section('title', 'Leads')

@section('content')
<div class="page-header">
    <h1 class="page-title">Leads</h1>
    <p class="page-subtitle">CRM leads, PRP policies, and sales pipeline.</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card support-stat-card h-100">
            <div class="card-body">
                <p class="support-stat-label">Total</p>
                <h3 class="support-stat-value mb-0">{{ number_format($grandTotal ?? 0) }}</h3>
                <p class="text-muted small mb-0">All sources</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card support-stat-card h-100">
            <div class="card-body">
                <p class="support-stat-label">CRM leads</p>
                <h3 class="support-stat-value mb-0">{{ number_format($crmTotal ?? 0) }}</h3>
                <p class="text-muted small mb-0">In Vtiger</p>
            </div>
        </div>
    </div>
    @if (!empty($prpEnabled))
    <div class="col-6 col-lg-3">
        <div class="card support-stat-card h-100">
            <div class="card-body">
                <p class="support-stat-label">PRP unprocessed</p>
                <h3 class="support-stat-value mb-0">{{ number_format($prpCount ?? 0) }}</h3>
                <p class="text-muted small mb-0">Receipts pending</p>
            </div>
        </div>
    </div>
    @endif
    <div class="col-6 col-lg-3">
        <div class="card support-stat-card support-stat-closed h-100">
            <div class="card-body">
                <p class="support-stat-label">Today</p>
                <h3 class="support-stat-value mb-0">{{ number_format($todayCount ?? 0) }}</h3>
                <p class="text-muted small mb-0">New leads</p>
            </div>
        </div>
    </div>
</div>

@if (!empty($statusCounts))
<p class="text-muted small mb-2">Pipeline by status</p>
<div class="d-flex flex-wrap gap-2 mb-4">
    @foreach (array_slice($statusCounts, 0, 6, true) as $status => $count)
        <a href="{{ route('leads.index', ['status' => $status]) }}" class="badge rounded-pill text-decoration-none bg-light text-dark border px-3 py-2">
            {{ $status }} <span class="fw-bold ms-1">{{ number_format($count) }}</span>
        </a>
    @endforeach
</div>
@endif

<p class="text-muted small mb-2">Quick access to lead workflows.</p>
<div class="row g-4">
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('leads.index') }}" class="card support-quick-card text-decoration-none h-100" style="border:2px solid var(--agile-primary);background:var(--agile-primary-muted)">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="support-quick-icon" style="background:var(--agile-primary-muted)"><i class="bi bi-table"></i></div>
                <div>
                    <h6 class="mb-1 fw-bold">View all leads</h6>
                    <p class="text-muted small mb-0">Browse, search, and filter the full list</p>
                </div>
                <i class="bi bi-chevron-right ms-auto text-primary"></i>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('leads.create') }}" class="card support-quick-card text-decoration-none h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="support-quick-icon"><i class="bi bi-person-plus-fill"></i></div>
                <div>
                    <h6 class="mb-1">Add lead</h6>
                    <p class="text-muted small mb-0">Create a new CRM lead manually</p>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
    @if (!empty($prpEnabled))
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('leads.index', ['source' => 'prp']) }}" class="card support-quick-card text-decoration-none h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="support-quick-icon"><i class="bi bi-receipt"></i></div>
                <div>
                    <h6 class="mb-1">PRP — Unprocessed receipts</h6>
                    <p class="text-muted small mb-0">Policies with receipts not yet processed</p>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
    @endif
    @if(isset($can) && $can('marketing.social-media'))
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('marketing.social-media') }}" class="card support-quick-card text-decoration-none h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="support-quick-icon"><i class="bi bi-facebook"></i></div>
                <div>
                    <h6 class="mb-1">Social media leads</h6>
                    <p class="text-muted small mb-0">Interactions and leads from social channels</p>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
    @endif
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('leads.index', ['source' => 'crm']) }}" class="card support-quick-card text-decoration-none h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="support-quick-icon"><i class="bi bi-funnel-fill"></i></div>
                <div>
                    <h6 class="mb-1">CRM leads only</h6>
                    <p class="text-muted small mb-0">Exclude PRP policies from the list</p>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
</div>

<style>
.support-stat-card { border-radius: 16px; border: 1px solid var(--card-border, rgba(14, 67, 133, 0.12)); transition: transform .2s, box-shadow .2s; }
.support-stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(14, 67, 133, 0.1); }
.support-stat-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted, #64748b); margin-bottom: 0.25rem; }
.support-stat-value { font-size: 1.75rem; font-weight: 700; color: var(--primary, #0E4385); }
.support-stat-closed .support-stat-value { color: var(--success, #059669); }
.support-quick-card { border-radius: 16px; border: 1px solid var(--card-border, rgba(14, 67, 133, 0.12)); transition: all .2s; color: inherit; }
.support-quick-card:hover { border-color: var(--primary, #0E4385); background: var(--primary-muted, rgba(14, 67, 133, 0.06)); }
.support-quick-icon { width: 48px; height: 48px; border-radius: 12px; background: var(--primary-light, rgba(14, 67, 133, 0.12)); color: var(--primary, #0E4385); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
</style>
@endsection
