@php
    $sortCol = $activitySort ?? 'date_start';
    $sortDir = $activitySortDir ?? 'desc';
    $activitiesTab = $activitiesTab ?? 'updates';
    $baseQuery = array_merge(request()->except(['page']), ['tab' => $activitiesTab]);
    $activitiesPageRoute = $activitiesPageRoute ?? 'contacts.show';
    $activitiesPageParams = $activitiesPageParams ?? ['contact' => $contact->contactid];
    $sortLink = function (string $column) use ($sortCol, $sortDir, $baseQuery, $activitiesPageRoute, $activitiesPageParams) {
        $dir = ($sortCol === $column && $sortDir === 'asc') ? 'desc' : 'asc';
        return route($activitiesPageRoute, array_merge($activitiesPageParams, $baseQuery, ['sort' => $column, 'dir' => $dir]));
    };
    $activitiesClearUrl = route($activitiesPageRoute, array_merge($activitiesPageParams, ['tab' => $activitiesTab]));
    $activitiesFormAction = route($activitiesPageRoute, $activitiesPageParams);
    $sortIcon = function (string $column) use ($sortCol, $sortDir) {
        if ($sortCol !== $column) {
            return 'bi-arrow-down-up';
        }
        return $sortDir === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down';
    };
    $activityCreateUrl = function (string $type) use ($contact, $activitiesPageRoute, $activitiesPageParams, $activitiesTab) {
        return route('activities.create', [
            'type' => $type,
            'related_to' => $contact->contactid,
            'lock_related' => 1,
            'return_to' => route($activitiesPageRoute, array_merge($activitiesPageParams, ['tab' => $activitiesTab])),
        ]);
    };
    $activityEditUrl = function ($act) use ($contact, $activitiesPageRoute, $activitiesPageParams, $activitiesTab) {
        return route('activities.edit', [
            'activity' => $act->activityid,
            'lock_related' => 1,
            'return_to' => route($activitiesPageRoute, array_merge($activitiesPageParams, ['tab' => $activitiesTab])),
        ]);
    };
    $calendarReturnTo = route($activitiesPageRoute, array_merge($activitiesPageParams, ['tab' => $activitiesTab]));
@endphp

<div class="card activities-card mb-4">
    <div class="card-body p-0">
        <div class="activities-table-header bg-primary text-white px-3 py-2">
            <div class="row g-0 align-items-center text-uppercase small fw-bold">
                <div class="col"><a href="{{ $sortLink('status') }}" class="text-white text-decoration-none d-inline-flex align-items-center">Status <i class="bi {{ $sortIcon('status') }} ms-1"></i></a></div>
                <div class="col"><a href="{{ $sortLink('activitytype') }}" class="text-white text-decoration-none d-inline-flex align-items-center">Activity Type <i class="bi {{ $sortIcon('activitytype') }} ms-1"></i></a></div>
                <div class="col"><a href="{{ $sortLink('subject') }}" class="text-white text-decoration-none d-inline-flex align-items-center">Subject <i class="bi {{ $sortIcon('subject') }} ms-1"></i></a></div>
                <div class="col"><a href="{{ $sortLink('related_to') }}" class="text-white text-decoration-none d-inline-flex align-items-center">Related To <i class="bi {{ $sortIcon('related_to') }} ms-1"></i></a></div>
                <div class="col"><a href="{{ $sortLink('date_start') }}" class="text-white text-decoration-none d-inline-flex align-items-center">Start Date &amp; Time <i class="bi {{ $sortIcon('date_start') }} ms-1"></i></a></div>
                <div class="col"><a href="{{ $sortLink('due_date') }}" class="text-white text-decoration-none d-inline-flex align-items-center">Due Date <i class="bi {{ $sortIcon('due_date') }} ms-1"></i></a></div>
                <div class="col"><a href="{{ $sortLink('recurringtype') }}" class="text-white text-decoration-none d-inline-flex align-items-center">Repeat <i class="bi {{ $sortIcon('recurringtype') }} ms-1"></i></a></div>
                <div class="col"><a href="{{ $sortLink('assigned_to') }}" class="text-white text-decoration-none d-inline-flex align-items-center">Assigned To <i class="bi {{ $sortIcon('assigned_to') }} ms-1"></i></a></div>
                <div class="col col-actions text-end">Actions</div>
            </div>
        </div>

        <form action="{{ $activitiesFormAction }}" method="GET" class="p-3 border-bottom bg-light">
            <input type="hidden" name="tab" value="{{ $activitiesTab }}">
            @if($sortCol !== 'date_start' || $sortDir !== 'desc')
            <input type="hidden" name="sort" value="{{ $sortCol }}">
            <input type="hidden" name="dir" value="{{ $sortDir }}">
            @endif
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Status</label>
                    <input type="text" name="status" class="form-control form-control-sm" placeholder="Status" value="{{ $activityStatus ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="Task" {{ ($activityType ?? '') === 'Task' ? 'selected' : '' }}>Task</option>
                        <option value="Event" {{ ($activityType ?? '') === 'Event' ? 'selected' : '' }}>Event</option>
                        <option value="Meeting" {{ ($activityType ?? '') === 'Meeting' ? 'selected' : '' }}>Meeting</option>
                        <option value="Call" {{ ($activityType ?? '') === 'Call' ? 'selected' : '' }}>Call</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Subject</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Subject" value="{{ $activitySearch ?? '' }}">
                </div>
                @if($canFilterActivitiesByAssignee ?? false)
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Assigned To</label>
                    <select name="assigned_to" class="form-select form-select-sm">
                        <option value="">Everyone</option>
                        @foreach($calendarUsers ?? [] as $u)
                        <option value="{{ $u->id }}" {{ (int) ($activityAssignedToFilter ?? 0) === (int) $u->id ? 'selected' : '' }}>{{ $u->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-auto">
                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-search me-1"></i> Search</button>
                </div>
                @if(($activityType ?? null) || ($activityStatus ?? null) || ($activitySearch ?? null) || ($activityAssignedToFilter ?? null))
                <div class="col-auto">
                    <a href="{{ $activitiesClearUrl }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
                @endif
            </div>
        </form>

        <div class="quick-create-bar d-flex flex-wrap align-items-center gap-2 p-3 border-bottom bg-white">
            <a href="{{ $activityCreateUrl('Event') }}" class="btn btn-success btn-sm">
                <i class="bi bi-calendar-event me-1"></i> Add Event
            </a>
            <a href="{{ $activityCreateUrl('Task') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-check2-square me-1"></i> Add Task
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle activities-table mb-0">
                <tbody>
                    @forelse ($calendarActivities ?? [] as $act)
                    <tr>
                        <td class="activities-td">{{ $act->status ?? '—' }}</td>
                        <td class="activities-td"><span class="badge bg-secondary">{{ $act->activitytype ?? 'Task' }}</span></td>
                        <td class="activities-td fw-semibold">{{ $act->subject ?? 'Untitled' }}</td>
                        <td class="activities-td">
                            @if(!empty(trim($act->related_to_name ?? '')))
                                <a href="{{ route('contacts.show', $act->related_to_id) }}">{{ trim($act->related_to_name) }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="activities-td text-nowrap">{{ $act->date_start ? date('d M Y', strtotime($act->date_start)) . ($act->time_start ? ' ' . $act->time_start : '') : '—' }}</td>
                        <td class="activities-td text-nowrap">{{ $act->due_date ? date('d M Y', strtotime($act->due_date)) : '—' }}</td>
                        <td class="activities-td">{{ $act->recurringtype ?: '—' }}</td>
                        <td class="activities-td">{{ trim($act->assigned_to_name ?? '') ?: '—' }}</td>
                        <td class="activities-td activities-td-actions text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ $activityEditUrl($act) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('activities.destroy', $act->activityid) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Delete this activity? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="return_to" value="{{ $calendarReturnTo }}">
                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="activities-empty-state">
                                <div class="activities-empty-icon"><i class="bi bi-calendar3"></i></div>
                                <h6 class="mt-3 mb-2">No activities yet</h6>
                                <p class="text-muted mb-3">Schedule a meeting or task for this prospect to get started.</p>
                                <div class="d-flex gap-2 justify-content-center">
                                    <a href="{{ $activityCreateUrl('Event') }}" class="btn btn-primary-custom btn-sm"><i class="bi bi-plus-lg me-1"></i> Add Event</a>
                                    <a href="{{ $activityCreateUrl('Task') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus-lg me-1"></i> Add Task</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(($calendarPaginator ?? null) && $calendarPaginator->hasPages())
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-top bg-light">
            <span class="small text-muted">Showing {{ $calendarPaginator->firstItem() ?? 0 }}–{{ $calendarPaginator->lastItem() ?? 0 }} of {{ $calendarPaginator->total() }}</span>
            {{ $calendarPaginator->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

<style>
.activities-card { border-radius: 16px; border: 1px solid var(--card-border, rgba(14, 67, 133, 0.12)); overflow: hidden; }
.activities-table-header { font-size: 0.7rem; letter-spacing: 0.08em; }
.activities-table-header .row > .col { flex: 1 1 0; min-width: 0; padding: 0 0.35rem; }
.activities-table tbody tr:hover { background: var(--primary-muted, rgba(14, 67, 133, 0.06)); }
.activities-table .activities-td { flex: 1 1 0; min-width: 0; padding: 0.75rem 0.35rem; border: none; vertical-align: middle; font-size: 0.875rem; }
.activities-table .activities-td-actions { flex: 0 0 5.5rem; }
.activities-table-header .col-actions { flex: 0 0 5.5rem; }
.activities-table tbody tr { display: table; width: 100%; table-layout: fixed; }
.activities-empty-icon { width: 64px; height: 64px; margin: 0 auto; border-radius: 50%; background: rgba(14, 67, 133, 0.08); display: flex; align-items: center; justify-content: center; font-size: 1.75rem; color: var(--primary, #0E4385); }
</style>
