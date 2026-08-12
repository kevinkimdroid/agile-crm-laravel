@php
    $pendingContext = $pendingContext ?? 'contact';
@endphp

@if($pendingContext === 'contact')
    @php
        $summaryActivityTaskUrl = route('activities.create', [
            'type' => 'Task',
            'related_to' => $contact->contactid,
            'lock_related' => 1,
            'return_to' => route('contacts.show', ['contact' => $contact->contactid, 'tab' => 'summary']),
        ]);
        $summaryActivityEventUrl = route('activities.create', [
            'type' => 'Event',
            'related_to' => $contact->contactid,
            'lock_related' => 1,
            'return_to' => route('contacts.show', ['contact' => $contact->contactid, 'tab' => 'summary']),
        ]);
        $summaryActivitiesViewAllUrl = route('contacts.show', [$contact->contactid, 'tab' => 'updates']);
    @endphp
    @include('partials.profile-summary-activities', [
        'activities' => $activities ?? collect(),
        'summaryActivityTaskUrl' => $summaryActivityTaskUrl,
        'summaryActivityEventUrl' => $summaryActivityEventUrl,
        'summaryActivitiesViewAllUrl' => $summaryActivitiesViewAllUrl,
    ])
    @include('contacts.partials.contact-comments', [
        'contact' => $contact,
        'contactComments' => $contactComments ?? collect(),
        'comments' => $comments ?? collect(),
    ])
@else
    @if($contact ?? null)
        @php
            $clientReturnTo = route('support.clients.show', array_merge($clientShowBase ?? [], ['tab' => 'summary']));
            $summaryActivityTaskUrl = route('activities.create', [
                'type' => 'Task',
                'related_to' => $contact->contactid,
                'lock_related' => 1,
                'return_to' => $clientReturnTo,
            ]);
            $summaryActivityEventUrl = route('activities.create', [
                'type' => 'Event',
                'related_to' => $contact->contactid,
                'lock_related' => 1,
                'return_to' => $clientReturnTo,
            ]);
            $summaryActivitiesViewAllUrl = route('support.clients.show', array_merge($clientShowBase ?? [], ['tab' => 'updates']));
            $summaryActivityEditUrl = function ($act) use ($clientShowBase) {
                return route('activities.edit', [
                    'activity' => $act->activityid,
                    'lock_related' => 1,
                    'return_to' => route('support.clients.show', array_merge($clientShowBase ?? [], ['tab' => 'summary'])),
                ]);
            };
        @endphp
        @include('partials.profile-summary-activities', [
            'activities' => $activities ?? collect(),
            'summaryActivityTaskUrl' => $summaryActivityTaskUrl,
            'summaryActivityEventUrl' => $summaryActivityEventUrl,
            'summaryActivitiesViewAllUrl' => $summaryActivitiesViewAllUrl,
            'summaryActivityEditUrl' => $summaryActivityEditUrl,
        ])
    @else
        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">Activities</h6>
                <div class="summary-empty-box py-4 text-center text-muted">
                    No pending activities. Link a CRM prospect to schedule tasks and events.
                </div>
            </div>
        </div>
    @endif
    @include('support.partials.client-comments', [
        'clientPolicy' => $clientPolicy ?? ($policy ?? ''),
        'clientComments' => $clientComments ?? collect(),
        'clientShowBase' => $clientShowBase ?? [],
        'commentReturnTab' => 'summary',
    ])
@endif
