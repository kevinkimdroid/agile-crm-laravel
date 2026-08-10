@extends('layouts.app')

@section('title', 'Work Tickets – Broken SLA')

@section('content')
@include('partials.reports-audit-styles')
<div class="reports-audit-page">
    <div class="reports-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <nav class="reports-breadcrumb mb-2">
                <a href="{{ route('reports') }}">Reports</a>
                <span class="reports-breadcrumb-sep">/</span>
                <span class="reports-breadcrumb-current">Work Tickets SLA</span>
            </nav>
            <h1 class="reports-audit-title mb-1">Work Tickets – Broken SLA</h1>
            <p class="reports-audit-subtitle mb-0">Internal work tickets that exceeded their priority-based Turnaround Time (TAT).</p>
            <div class="d-flex flex-wrap gap-2 mt-3 no-print">
                <a href="{{ route('reports.sla-broken') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-person-badge me-1"></i> Client tickets
                </a>
                <a href="{{ route('reports.sla-broken-work') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-kanban me-1"></i> Work tickets
                </a>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center no-print">
            @include('partials.report-export-buttons', ['route' => 'reports.export.sla-broken-work', 'csvWithoutFormat' => true])
            <a href="{{ route('work-tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-kanban me-1"></i>Work tickets
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()" title="Print report">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>

    <div class="reports-table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned to</th>
                        <th>User Dept</th>
                        <th>Created</th>
                        <th>Due by</th>
                        <th>Completed at</th>
                        <th>TAT</th>
                        <th>Hours Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets ?? [] as $t)
                    <tr>
                        <td>
                            <a href="{{ route('work-tickets.show', $t->id) }}" class="fw-semibold text-primary text-decoration-none">
                                {{ $t->ticket_no ?? 'WT' . $t->id }}
                            </a>
                        </td>
                        <td>{{ Str::limit($t->title ?? '—', 40) }}</td>
                        <td>
                            @php
                                $prio = $t->priority ?? 'Medium';
                                $prioClass = match ($prio) {
                                    'Urgent' => 'bg-danger',
                                    'High' => 'bg-warning text-dark',
                                    'Low' => 'bg-secondary',
                                    default => 'bg-info text-dark',
                                };
                            @endphp
                            <span class="badge {{ $prioClass }}">{{ $prio }}</span>
                        </td>
                        <td>
                            <span class="badge {{ in_array((string) ($t->status ?? ''), ['Done', 'Closed'], true) ? 'bg-success' : 'bg-warning' }}">
                                {{ $t->status ?? '—' }}
                            </span>
                        </td>
                        <td>{{ $t->assignee_name ?? 'Unassigned' }}</td>
                        <td>{{ $t->owner_department ?? '—' }}</td>
                        <td class="text-nowrap">{{ $t->created_at ? $t->created_at->format('d M Y H:i') : '—' }}</td>
                        <td class="text-nowrap">{{ isset($t->due_at) ? $t->due_at->format('d M Y H:i') : '—' }}</td>
                        <td class="text-nowrap">
                            @if(in_array((string) ($t->status ?? ''), ['Done', 'Closed'], true) && $t->completed_at)
                                {{ $t->completed_at->format('d M Y H:i') }}
                            @else
                                Still open
                            @endif
                        </td>
                        <td>{{ $t->tat_hours ?? '—' }}{{ isset($t->tat_hours) ? 'h' : '' }}</td>
                        <td>
                            <span class="text-danger fw-semibold">{{ $t->hours_overdue ?? 0 }}h</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle display-6 d-block mb-2 text-success"></i>
                            No broken SLAs. All work tickets are within their TAT.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="reports-meta text-muted small mt-3 py-2">
        <i class="bi bi-clock me-1"></i>Report generated: {{ now()->format('l, F j, Y \a\t g:i A') }}
        · {{ count($tickets ?? []) }} ticket(s)
    </div>
</div>
@endsection
