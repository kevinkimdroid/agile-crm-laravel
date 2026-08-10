<div class="col-lg-4">
    <div class="card contact-detail-card mb-4">
        <div class="card-body p-4">
            <h6 class="text-uppercase small fw-bold text-muted mb-3">Quick actions</h6>
            <div class="d-flex flex-column gap-2">
                @if($displayPhone ?? false)
                <a href="tel:{{ tel_href($displayPhone) }}" class="btn btn-outline-primary"><i class="bi bi-telephone me-2"></i>Call</a>
                <a href="{{ route('support.sms-notifier', array_filter(['contact_id' => $contact->contactid, 'phone' => $displayPhone])) }}" class="btn btn-outline-primary"><i class="bi bi-chat-dots me-2"></i>Send Text</a>
                @endif
                @if(($displayEmail ?? '') !== '')
                <a href="{{ route('support.email-client', array_filter(['contact_id' => $contact->contactid, 'email' => $displayEmail, 'client_name' => $contact->full_name])) }}" class="btn btn-outline-primary"><i class="bi bi-envelope me-2"></i>Send Email</a>
                @endif
                <a href="{{ route('tickets.create', ['contact_id' => $contact->contactid]) }}" class="btn btn-outline-success"><i class="bi bi-ticket-perforated me-2"></i>Create Ticket</a>
                <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#followupModal"><i class="bi bi-calendar-check me-2"></i>Log Follow-up</a>
                @if($canCollectMpesa ?? false)
                <button type="button" class="btn btn-outline-success text-start mpesa-stk-trigger" data-bs-toggle="modal" data-bs-target="#mpesaStkModal">
                    <i class="bi bi-phone me-2"></i>Collect via M-Pesa
                </button>
                @endif
                <a href="{{ route('contacts.edit', $contact->contactid) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-2"></i>Edit prospect</a>
            </div>
        </div>
    </div>

    @if($canCollectMpesa ?? false)
    <div class="card contact-detail-card mb-4 client-summary-payments mpesa-ui">
        <div class="card-body p-4">
            <h6 class="text-uppercase small fw-bold text-muted mb-3">Payments</h6>
            <p class="text-muted small mb-3">Collect premium via M-Pesa STK push for policy <code>{{ $prospectPolicy }}</code>.</p>
            <button type="button" class="btn btn-success w-100 mpesa-stk-trigger" data-bs-toggle="modal" data-bs-target="#mpesaStkModal">
                <i class="bi bi-phone me-1"></i>Collect premium via M-Pesa
            </button>
        </div>
    </div>
    @endif

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
                No follow-ups yet. Use "Log Follow-up" to track outreach.
            </div>
            @endif
        </div>
    </div>
</div>
