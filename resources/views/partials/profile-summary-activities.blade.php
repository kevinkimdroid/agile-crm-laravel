@php
    $summaryActivities = $activities ?? collect();
    $summaryShowAddButtons = $summaryShowAddButtons ?? true;
@endphp
<div class="card contact-detail-card mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Activities</h6>
            @if(! empty($summaryActivitiesViewAllUrl))
            <a href="{{ $summaryActivitiesViewAllUrl }}" class="btn btn-sm btn-outline-secondary">View all</a>
            @endif
        </div>
        @if($summaryShowAddButtons && ! empty($summaryActivityTaskUrl) && ! empty($summaryActivityEventUrl))
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ $summaryActivityTaskUrl }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus-lg me-1"></i>Add Task</a>
            <a href="{{ $summaryActivityEventUrl }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus-lg me-1"></i>Add Event</a>
        </div>
        @endif
        @if($summaryActivities->isNotEmpty())
        <ul class="list-unstyled mb-0">
            @foreach($summaryActivities as $act)
            <li class="py-2 border-bottom d-flex justify-content-between align-items-start gap-2">
                <div>
                    <strong>{{ $act->subject ?? 'Untitled' }}</strong>
                    <span class="badge bg-secondary ms-1">{{ $act->activitytype ?? 'Task' }}</span>
                    <p class="text-muted small mb-0">{{ $act->date_start ?? '' }}</p>
                </div>
                @if(! empty($summaryActivityEditUrl))
                <a href="{{ $summaryActivityEditUrl($act) }}" class="btn btn-sm btn-outline-primary flex-shrink-0" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>
                @endif
            </li>
            @endforeach
        </ul>
        @else
        <div class="summary-empty-box py-4 text-center text-muted">No pending activities</div>
        @endif
    </div>
</div>
