@extends('layouts.app')

@section('title', 'Campaigns')

@section('content')
<div class="page-header">
    <h1 class="page-title">Campaigns</h1>
    <p class="page-subtitle">Create and manage marketing campaigns.</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card support-stat-card h-100">
            <div class="card-body">
                <p class="support-stat-label">Total</p>
                <h3 class="support-stat-value mb-0">{{ number_format($totalCampaigns ?? 0) }}</h3>
                <p class="text-muted small mb-0">All campaigns</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card support-stat-card h-100">
            <div class="card-body">
                <p class="support-stat-label">Active</p>
                <h3 class="support-stat-value mb-0" style="color:var(--agile-success, #059669);">{{ number_format($activeCampaigns ?? 0) }}</h3>
                <p class="text-muted small mb-0">Running now</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card support-stat-card h-100">
            <div class="card-body">
                <p class="support-stat-label">Planning</p>
                <h3 class="support-stat-value mb-0" style="color:var(--agile-warning, #d97706);">{{ number_format($planningCampaigns ?? 0) }}</h3>
                <p class="text-muted small mb-0">In preparation</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card support-stat-card h-100">
            <div class="card-body">
                <p class="support-stat-label">Expected revenue</p>
                <h3 class="support-stat-value mb-0" style="font-size:1.25rem;">KES {{ number_format($totalExpectedRevenue ?? 0, 0) }}</h3>
                <p class="text-muted small mb-0">Active campaigns</p>
            </div>
        </div>
    </div>
</div>

<p class="text-muted small mb-2">Quick access to campaign workflows.</p>
<div class="row g-4">
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('marketing.campaigns.index') }}" class="card support-quick-card text-decoration-none h-100" style="border:2px solid var(--agile-primary);background:var(--agile-primary-muted)">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="support-quick-icon" style="background:var(--agile-primary-muted)"><i class="bi bi-megaphone-fill"></i></div>
                <div>
                    <h6 class="mb-1 fw-bold">View all campaigns</h6>
                    <p class="text-muted small mb-0">Browse, search, and filter campaigns</p>
                </div>
                <i class="bi bi-chevron-right ms-auto text-primary"></i>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('marketing.campaigns.create') }}" class="card support-quick-card text-decoration-none h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="support-quick-icon"><i class="bi bi-plus-circle-fill"></i></div>
                <div>
                    <h6 class="mb-1">New campaign</h6>
                    <p class="text-muted small mb-0">Set up a new marketing campaign</p>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('marketing.campaigns.index', ['status' => 'Active']) }}" class="card support-quick-card text-decoration-none h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="support-quick-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <h6 class="mb-1">Active campaigns</h6>
                    <p class="text-muted small mb-0">{{ number_format($activeCampaigns ?? 0) }} currently running</p>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('marketing.campaigns.index', ['status' => 'Planning']) }}" class="card support-quick-card text-decoration-none h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="support-quick-icon"><i class="bi bi-clock-fill"></i></div>
                <div>
                    <h6 class="mb-1">Planning</h6>
                    <p class="text-muted small mb-0">{{ number_format($planningCampaigns ?? 0) }} campaigns in preparation</p>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
    @if(isset($can) && ($can('marketing.broadcast') || $can('support.customers')))
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('marketing.broadcast') }}" class="card support-quick-card text-decoration-none h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="support-quick-icon"><i class="bi bi-broadcast"></i></div>
                <div>
                    <h6 class="mb-1">Email &amp; SMS broadcast</h6>
                    <p class="text-muted small mb-0">Mass message contacts from a campaign list</p>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
    @endif
    @if(isset($can) && $can('leads'))
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('leads') }}" class="card support-quick-card text-decoration-none h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="support-quick-icon"><i class="bi bi-people-fill"></i></div>
                <div>
                    <h6 class="mb-1">Leads</h6>
                    <p class="text-muted small mb-0">View leads generated from campaigns</p>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
    @endif
</div>

<style>
.support-stat-card { border-radius: 16px; border: 1px solid var(--card-border, rgba(14, 67, 133, 0.12)); transition: transform .2s, box-shadow .2s; }
.support-stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(14, 67, 133, 0.1); }
.support-stat-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted, #64748b); margin-bottom: 0.25rem; }
.support-stat-value { font-size: 1.75rem; font-weight: 700; color: var(--primary, #0E4385); }
.support-quick-card { border-radius: 16px; border: 1px solid var(--card-border, rgba(14, 67, 133, 0.12)); transition: all .2s; color: inherit; }
.support-quick-card:hover { border-color: var(--primary, #0E4385); background: var(--primary-muted, rgba(14, 67, 133, 0.06)); }
.support-quick-icon { width: 48px; height: 48px; border-radius: 12px; background: var(--primary-light, rgba(14, 67, 133, 0.12)); color: var(--primary, #0E4385); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
</style>
@endsection
