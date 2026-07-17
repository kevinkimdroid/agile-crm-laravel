@extends('layouts.app')

@section('title', 'Leads')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">Leads</h1>
        <p class="page-subtitle">Manage and track your sales leads.</p>
    </div>
    <a href="{{ route('leads.create') }}" class="btn btn-primary-custom">
        <i class="bi bi-plus-lg me-2"></i>Add Lead
    </a>
</div>

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

<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
    <form method="GET" action="{{ route('leads.index') }}" class="flex-grow-1" style="max-width: 420px;">
        @if($currentStatus ?? null)<input type="hidden" name="status" value="{{ $currentStatus }}">@endif
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="q" class="form-control" placeholder="Search leads…" value="{{ $search ?? request('q') }}">
            <button type="submit" class="btn btn-outline-secondary">Search</button>
        </div>
    </form>
    <span class="text-muted small ms-auto">{{ number_format($total ?? 0) }} leads</span>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Lead</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th class="text-end" width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        @php $status = trim((string) ($lead->leadstatus ?? '')) ?: 'New'; @endphp
                        <tr>
                            <td>
                                <a href="{{ route('leads.show', $lead->leadid) }}" class="text-decoration-none fw-semibold">
                                    {{ $lead->full_name }}
                                </a>
                            </td>
                            <td class="text-muted">{{ $lead->company ?: '—' }}</td>
                            <td>
                                @if($lead->email)
                                    <a href="mailto:{{ $lead->email }}" class="text-decoration-none">{{ Str::limit($lead->email, 30) }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                                @if($lead->phone || ($lead->mobile ?? null))
                                    <div class="small text-muted">{{ $lead->phone ?: $lead->mobile }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge text-bg-light border">{{ $status }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('leads.show', $lead->leadid) }}" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('leads.edit', $lead->leadid) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                No leads found.
                                <a href="{{ route('leads.create') }}">Add a lead</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($leads->hasPages())
        <div class="card-footer bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="text-muted small">Showing {{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }} of {{ number_format($leads->total()) }}</span>
            {{ $leads->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
