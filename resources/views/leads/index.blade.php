@extends('layouts.app')

@section('title', 'Leads')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">Leads</h1>
        <p class="page-subtitle">CRM leads and PRP policies with unprocessed receipts.</p>
    </div>
    <a href="{{ route('leads.create') }}" class="btn btn-primary-custom">
        <i class="bi bi-plus-lg me-2"></i>Add Lead
    </a>
</div>

@if (!empty($prpEnabled))
<div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
    @php
        $tabs = [
            'all' => 'All',
            'prp' => 'PRP — Unprocessed receipts' . (isset($prpCount) ? ' (' . number_format($prpCount) . ')' : ''),
            'crm' => 'CRM leads',
        ];
        $active = $leadSource ?? 'all';
    @endphp
    @foreach ($tabs as $key => $label)
        <a href="{{ route('leads.index', array_merge(request()->except('page'), ['source' => $key])) }}"
           class="btn btn-sm {{ $active === $key ? 'btn-primary-custom' : 'btn-outline-secondary' }}">
            {{ $label }}
        </a>
    @endforeach
    <span class="text-muted small ms-1">Sync: <code>php artisan prp-leads:sync</code></span>
</div>
@endif

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if (session('info'))
    <div class="alert alert-info alert-dismissible fade show d-flex align-items-center" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>{{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <form method="GET" action="{{ route('leads.index') }}" class="leads-search-form">
            @if (!empty($leadSource))
                <input type="hidden" name="source" value="{{ $leadSource }}">
            @endif
            @if($currentStatus ?? null)
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0" placeholder="Search leads by name, company, or email..." value="{{ $search ?? request('q') }}">
                <button type="submit" class="btn btn-primary-custom">Search</button>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="d-flex gap-3 align-items-center">
            <div class="lead-stat-pill">
                <span class="lead-stat-value">{{ number_format($total ?? 0) }}</span>
                <span class="lead-stat-label">Total Leads</span>
            </div>
        </div>
    </div>
</div>

<div class="card leads-table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle leads-table mb-0">
                <thead>
                    <tr>
                        <th class="leads-th">Lead</th>
                        <th class="leads-th">{{ ($leadSource ?? '') === 'crm' ? 'Company' : 'Policy / Company' }}</th>
                        <th class="leads-th">Contact</th>
                        @if (($leadSource ?? 'all') !== 'crm')
                            <th class="leads-th">Unprocessed</th>
                            <th class="leads-th">Paid to</th>
                        @endif
                        <th class="leads-th">Status</th>
                        <th class="leads-th text-end" width="140">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        @php
                            $isPrp = !empty($lead->_prp_source);
                            $leadUrl = $isPrp
                                ? route('leads.show-prp', ['policyNumber' => $lead->policy_number])
                                : route('leads.show', $lead->leadid);
                            $displayName = $lead->full_name ?? trim(($lead->firstname ?? '') . ' ' . ($lead->lastname ?? ''));
                            $status = trim((string) ($lead->leadstatus ?? '')) ?: 'New';
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ $leadUrl }}" class="lead-name-link">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="lead-avatar">{{ strtoupper(substr($lead->firstname ?? '?', 0, 1)) }}{{ strtoupper(substr($lead->lastname ?? '', 0, 1)) }}</div>
                                        <div>
                                            <span class="fw-semibold">{{ $displayName }}</span>
                                            @if ($isPrp)
                                                <small class="d-block text-muted">PRP · {{ $lead->leadsource ?? 'Unprocessed receipts' }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <span class="text-muted">{{ $isPrp ? ($lead->policy_number ?: '—') : ($lead->company ?: '—') }}</span>
                            </td>
                            <td>
                                <div class="lead-contact">
                                    @if($lead->email)
                                        <a href="mailto:{{ $lead->email }}" class="text-decoration-none"><i class="bi bi-envelope me-1"></i>{{ Str::limit($lead->email, 25) }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                    @if($lead->phone || ($lead->mobile ?? null))
                                        <small class="d-block text-muted mt-0"><i class="bi bi-telephone me-1"></i>{{ $lead->phone ?: $lead->mobile }}</small>
                                    @endif
                                </div>
                            </td>
                            @if (($leadSource ?? 'all') !== 'crm')
                                <td>
                                    @if ($isPrp)
                                        <span class="badge bg-warning text-dark">{{ number_format((int) ($lead->unprocessed_rct ?? 0)) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($isPrp && !empty($lead->paid_to))
                                        {{ \Carbon\Carbon::parse($lead->paid_to)->format('d M Y') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <span class="badge lead-status-badge lead-status-{{ Str::slug($status) }}">{{ $status }}</span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ $leadUrl }}" class="btn btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                    @if (! $isPrp)
                                        <a href="{{ route('leads.edit', $lead->leadid) }}" class="btn btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($leadSource ?? 'all') === 'crm' ? 5 : 7 }}" class="text-center py-5">
                                <div class="leads-empty-state">
                                    <div class="leads-empty-icon"><i class="bi bi-people"></i></div>
                                    <h6 class="mt-3 mb-2">No leads yet</h6>
                                    <p class="text-muted mb-3">Get started by adding your first lead.</p>
                                    <a href="{{ route('leads.create') }}" class="btn btn-primary-custom"><i class="bi bi-plus-lg me-1"></i>Add Lead</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($leads->hasPages())
        <div class="card-footer bg-transparent border-top py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="text-muted small">Showing {{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }} of {{ number_format($leads->total()) }}</span>
            {{ $leads->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

<style>
.leads-search-form .input-group { border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(14, 67, 133, 0.08); }
.leads-search-form .input-group-text { border-radius: 12px 0 0 12px; border-color: var(--card-border); }
.leads-search-form .form-control { border-radius: 0; border-color: var(--card-border); }
.leads-search-form .form-control:focus { box-shadow: none; border-color: var(--primary); }
.leads-search-form .btn { border-radius: 0 12px 12px 0; }
.lead-stat-pill { background: var(--primary-light); padding: 0.75rem 1.25rem; border-radius: 12px; text-align: center; }
.lead-stat-value { display: block; font-size: 1.5rem; font-weight: 700; color: var(--primary); }
.lead-stat-label { font-size: 0.75rem; color: var(--text-muted); }
.leads-table-card { overflow: hidden; border-radius: 16px; }
.leads-th { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); padding: 1rem 1.25rem; background: var(--primary-muted); }
.leads-table td { padding: 1rem 1.25rem; vertical-align: middle; }
.leads-table tbody tr { transition: background .15s; }
.leads-table tbody tr:hover { background: var(--primary-muted); }
.lead-avatar { width: 36px; height: 36px; border-radius: 10px; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0; }
.lead-name-link { color: var(--text); text-decoration: none; }
.lead-name-link:hover { color: var(--primary); }
.lead-status-badge { font-size: 0.7rem; font-weight: 600; padding: 0.35rem 0.75rem; border-radius: 20px; }
.lead-status-new, .lead-status- { background: var(--primary-light); color: var(--primary); }
.lead-status-qualified { background: rgba(5, 150, 105, 0.15); color: var(--success); }
.lead-status-contacted { background: rgba(56, 189, 248, 0.2); color: var(--accent); }
.lead-status-converted { background: rgba(5, 150, 105, 0.2); color: var(--success); }
.lead-status-lost { background: rgba(220, 38, 38, 0.12); color: var(--danger); }
.leads-empty-icon { width: 80px; height: 80px; margin: 0 auto; background: var(--primary-light); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--primary); }
</style>
@endsection
