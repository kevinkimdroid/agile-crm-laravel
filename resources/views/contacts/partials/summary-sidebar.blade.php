<div class="col-lg-4">
    <div class="card contact-detail-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-uppercase small fw-bold text-muted mb-0">Activities</h6>
                <div class="d-flex gap-1">
                    <a href="{{ route('contacts.show', [$contact->contactid, 'tab' => 'updates']) }}" class="btn btn-sm btn-outline-secondary">View all</a>
                    <a href="{{ route('activities.create', [
                        'type' => 'Task',
                        'related_to' => $contact->contactid,
                        'lock_related' => 1,
                        'return_to' => route('contacts.show', ['contact' => $contact->contactid, 'tab' => 'updates']),
                    ]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus-lg me-1"></i>Add Task</a>
                </div>
            </div>
            @if($activities->isNotEmpty())
                <ul class="list-unstyled mb-0">
                    @foreach($activities as $act)
                    <li class="py-2 border-bottom">
                        <strong>{{ $act->subject ?? 'Untitled' }}</strong>
                        <span class="badge bg-secondary ms-1">{{ $act->activitytype ?? 'Task' }}</span>
                        <p class="text-muted small mb-0">{{ $act->date_start ?? '' }}</p>
                    </li>
                    @endforeach
                </ul>
            @else
                <div class="summary-empty-box py-4 text-center text-muted">No pending activities</div>
            @endif
        </div>
    </div>

    <div class="card contact-detail-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-uppercase small fw-bold text-muted mb-0">Follow-ups</h6>
                <a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#followupModal"><i class="bi bi-plus-lg me-1"></i>Log Follow-up</a>
            </div>
            @if(($followups ?? collect())->isNotEmpty())
            <ul class="list-unstyled mb-0">
                @foreach($followups as $fu)
                <li class="py-2 border-bottom">
                    <p class="mb-0 small">{{ Str::limit($fu->note, 100) }}</p>
                    <small class="text-muted">{{ $fu->followup_date ? $fu->followup_date->format('d M Y') : optional($fu->created_at)->format('d M Y') }} · {{ $fu->status }}</small>
                </li>
                @endforeach
            </ul>
            @else
            <div class="summary-empty-box py-4 text-center text-muted">
                <i class="bi bi-calendar-check opacity-50 d-block mb-2"></i>
                No follow-ups yet. Use "Log Follow-up" to track client outreach.
            </div>
            @endif
        </div>
    </div>
</div>
