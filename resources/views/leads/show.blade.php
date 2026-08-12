@extends('layouts.app')

@php
    $isPrpLead = !empty($isPrpLead);
    $displayName = $lead->full_name ?? trim(($lead->firstname ?? '') . ' ' . ($lead->lastname ?? ''));
@endphp

@section('title', $displayName . ' — Lead')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <nav class="breadcrumb-nav mb-2">
            <a href="{{ route('leads') }}" class="text-muted">Leads</a>
            <span class="mx-2 text-muted">/</span>
            <span class="text-dark fw-semibold">{{ $displayName }}</span>
        </nav>
        <h1 class="page-title">{{ $displayName }}</h1>
        <p class="page-subtitle">
            @if ($isPrpLead)
                Policy {{ $lead->policy_number ?? '—' }} · {{ $lead->leadsource ?? 'PRP' }}
            @else
                {{ $lead->company ?: 'No company' }}
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        @if ($isPrpLead && !empty($lead->policy_number))
            <a href="{{ route('support.serve-client', ['search' => $lead->policy_number]) }}" class="btn btn-primary-custom">
                <i class="bi bi-person-badge me-1"></i>Serve Client
            </a>
        @elseif((isset($can) ? $can('tickets') : true))
        <a href="{{ route('tickets.create', ['from' => 'lead', 'lead_id' => $lead->leadid]) }}" class="btn btn-primary-custom">
            <i class="bi bi-ticket-perforated me-1"></i>Create Ticket
        </a>
        @endif
        @if (! $isPrpLead)
        <a href="{{ route('leads.edit', $lead->leadid) }}" class="btn btn-primary-custom">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <form action="{{ route('leads.destroy', $lead->leadid) }}" method="POST" onsubmit="return confirm('Delete this lead?');" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-secondary text-danger">
                <i class="bi bi-trash me-1"></i>Delete
            </button>
        </form>
        @endif
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-4">
    {{-- Profile Header Card --}}
    <div class="col-12">
        <div class="card lead-profile-card p-4">
            <div class="d-flex flex-wrap align-items-center gap-4">
                <div class="lead-profile-avatar">
                    {{ strtoupper(substr($lead->firstname ?? '?', 0, 1)) }}{{ strtoupper(substr($lead->lastname ?? '', 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <h5 class="mb-1">{{ $displayName }}</h5>
                    <p class="text-muted mb-2">
                        @if ($isPrpLead)
                            Policy {{ $lead->policy_number ?? '—' }}
                        @else
                            {{ $lead->company ?: 'No company' }}
                        @endif
                    </p>
                    @if($lead->leadstatus)
                        <span class="badge lead-status-badge lead-status-{{ Str::slug($lead->leadstatus) }}">{{ $lead->leadstatus }}</span>
                    @endif
                </div>
                @if($lead->email || $lead->phone || $lead->mobile)
                    <div class="lead-quick-actions">
                        @if($lead->email)
                            <a href="mailto:{{ $lead->email }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-envelope me-1"></i>Email</a>
                        @endif
                        @if($lead->phone || $lead->mobile)
                            <a href="tel:{{ tel_href($lead->phone ?: $lead->mobile) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-telephone me-1"></i>Call</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Lead Details --}}
    <div class="col-lg-8">
        <div class="card p-4">
            <h6 class="card-section-title"><i class="bi bi-person me-2"></i>Lead Details</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">Name</span>
                        <span class="detail-value">{{ $displayName }}</span>
                    </div>
                </div>
                @if ($isPrpLead)
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">Policy number</span>
                        <span class="detail-value">{{ $lead->policy_number ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">Unprocessed receipts</span>
                        <span class="detail-value">{{ number_format((int) ($lead->unprocessed_rct ?? 0)) }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">Paid to date</span>
                        <span class="detail-value">
                            @if (!empty($lead->paid_to))
                                {{ \Carbon\Carbon::parse($lead->paid_to)->format('d M Y') }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                </div>
                @else
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">Company</span>
                        <span class="detail-value">{{ $lead->company ?: '—' }}</span>
                    </div>
                </div>
                @endif
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value">
                            @if($lead->email)
                                <a href="mailto:{{ $lead->email }}" class="text-decoration-none">{{ $lead->email }}</a>
                            @else
                                —
                            @endif
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value">{{ $lead->phone ?: '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">Mobile</span>
                        <span class="detail-value">{{ $lead->mobile ?: '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">{{ $lead->leadstatus ?: '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">Source</span>
                        <span class="detail-value">{{ $lead->leadsource ?: '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Info Sidebar --}}
    <div class="col-lg-4">
        <div class="card p-4">
            <h6 class="card-section-title"><i class="bi bi-info-circle me-2"></i>Quick Info</h6>
            <div class="quick-info-list">
                @if ($isPrpLead)
                <div class="quick-info-item">
                    <i class="bi bi-database text-muted"></i>
                    <span>From LMS PRP cache (unprocessed receipts)</span>
                </div>
                @else
                <div class="quick-info-item">
                    <i class="bi bi-calendar3 text-muted"></i>
                    <span>Created {{ $lead->createdtime ? \Carbon\Carbon::parse($lead->createdtime)->diffForHumans() : '—' }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.breadcrumb-nav a { text-decoration: none; font-size: 0.9rem; }
.breadcrumb-nav a:hover { color: var(--primary) !important; }
.lead-profile-card { border-left: 4px solid var(--primary); }
.lead-profile-avatar { width: 72px; height: 72px; border-radius: 16px; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; flex-shrink: 0; }
.lead-quick-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.card-section-title { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--primary); margin-bottom: 1.25rem; }
.detail-item { padding: 0.75rem 0; border-bottom: 1px solid var(--card-border); }
.detail-item:last-child { border-bottom: none; }
.detail-label { display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem; }
.detail-value { font-size: 0.95rem; }
.quick-info-list { display: flex; flex-direction: column; gap: 0.75rem; }
.quick-info-item { display: flex; align-items: center; gap: 0.75rem; font-size: 0.9rem; }
.quick-info-item i { font-size: 1.1rem; }
.lead-status-badge { font-size: 0.7rem; font-weight: 600; padding: 0.35rem 0.75rem; border-radius: 20px; }
.lead-status-new, .lead-status- { background: var(--primary-light); color: var(--primary); }
.lead-status-qualified { background: rgba(5, 150, 105, 0.15); color: var(--success); }
.lead-status-contacted { background: rgba(56, 189, 248, 0.2); color: var(--accent); }
.lead-status-converted { background: rgba(5, 150, 105, 0.2); color: var(--success); }
.lead-status-lost { background: rgba(220, 38, 38, 0.12); color: var(--danger); }
</style>
@endsection
