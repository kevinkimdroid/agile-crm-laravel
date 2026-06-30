@php
    $isEdit = $isEdit ?? false;
    $activity = $activity ?? null;
    $type = old('activitytype', $activity?->activitytype ?? ($type ?? 'Event'));
    $isTaskType = in_array($type, ['Task'], true);
    $relatedContactName = null;
    if (! empty($relatedContact)) {
        $relatedContactName = trim(($relatedContact->firstname ?? '') . ' ' . ($relatedContact->lastname ?? ''))
            ?: ('Contact #' . ($relatedTo ?? ''));
    } elseif (! empty($activity?->related_to_name)) {
        $relatedContactName = trim($activity->related_to_name);
    }
    $formatTime = function ($time) {
        $time = trim((string) ($time ?? ''));
        if ($time === '') {
            return '';
        }
        if (preg_match('/^(\d{2}:\d{2})/', $time, $m)) {
            return $m[1];
        }

        return $time;
    };
    $defaultStatus = $isTaskType ? 'Not Started' : 'Planned';
    $taskStatuses = ['Not Started', 'In Progress', 'Completed', 'Pending Input', 'Deferred'];
    $eventStatuses = ['Planned', 'Held', 'Not Held'];
@endphp

<div class="activity-form-card card contact-detail-card">
    <div class="card-body p-4 p-lg-5">
        <form action="{{ $formAction }}" method="POST" class="activity-form">
            @csrf
            @if($isEdit)
            @method('PUT')
            @endif
            @if($lockRelated ?? false)
            <input type="hidden" name="lock_related" value="1">
            <input type="hidden" name="locked_related_to" value="{{ $relatedTo ?? $activity?->related_to_id ?? '' }}">
            @endif
            @if($returnTo ?? null)
            <input type="hidden" name="return_to" value="{{ $returnTo }}">
            @endif

            <div class="activity-form-section mb-4">
                <h6 class="activity-form-section-title"><i class="bi bi-card-text me-2"></i>Details</h6>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                            placeholder="e.g. Follow up on premium payment"
                            value="{{ old('subject', $activity?->subject ?? '') }}" required>
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Type</label>
                        <select name="activitytype" class="form-select @error('activitytype') is-invalid @enderror" id="activityTypeSelect">
                            @foreach(['Task', 'Event', 'Meeting', 'Call'] as $opt)
                            <option value="{{ $opt }}" {{ $type === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('activitytype')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                            placeholder="Notes, agenda, or follow-up details…">{{ old('description', $activity?->description ?? '') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="activity-form-section mb-4">
                <h6 class="activity-form-section-title"><i class="bi bi-calendar3 me-2"></i>Schedule</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Start date <span class="text-danger">*</span></label>
                        <input type="date" name="date_start" class="form-control @error('date_start') is-invalid @enderror"
                            value="{{ old('date_start', $activity?->date_start ?? date('Y-m-d')) }}" required>
                        @error('date_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Due / end date</label>
                        <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
                            value="{{ old('due_date', $activity?->due_date ?? $activity?->date_start ?? date('Y-m-d')) }}">
                        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Start time</label>
                        <input type="time" name="time_start" class="form-control"
                            value="{{ old('time_start', $formatTime($activity?->time_start ?? null)) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">End time</label>
                        <input type="time" name="time_end" class="form-control"
                            value="{{ old('time_end', $formatTime($activity?->time_end ?? null)) }}">
                    </div>
                </div>
            </div>

            <div class="activity-form-section mb-4">
                <h6 class="activity-form-section-title"><i class="bi bi-sliders me-2"></i>Status &amp; assignment</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select" id="activityStatusSelect">
                            @foreach($isTaskType ? $taskStatuses : $eventStatuses as $st)
                            <option value="{{ $st }}" {{ old('status', $activity?->status ?? $defaultStatus) === $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Priority</label>
                        <select name="priority" class="form-select">
                            @foreach(['High', 'Medium', 'Low'] as $pr)
                            <option value="{{ $pr }}" {{ old('priority', $activity?->priority ?? 'Medium') === $pr ? 'selected' : '' }}>{{ $pr }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Assigned to</label>
                        <select name="assigned_to" class="form-select">
                            @if(($users ?? collect())->isEmpty())
                            <option value="{{ auth()->guard('vtiger')->id() ?? 1 }}">{{ auth()->guard('vtiger')->user()->full_name ?? 'Current user' }}</option>
                            @else
                            @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ (int) old('assigned_to', $activity?->smownerid ?? auth()->guard('vtiger')->id()) === (int) $u->id ? 'selected' : '' }}>
                                {{ $u->full_name }}
                            </option>
                            @endforeach
                            @endif
                        </select>
                    </div>
                </div>
            </div>

            <div class="activity-form-section mb-4">
                <h6 class="activity-form-section-title"><i class="bi bi-person me-2"></i>Client</h6>
                @if($lockRelated ?? false)
                <input type="hidden" name="related_to" value="{{ $relatedTo ?? $activity?->related_to_id ?? '' }}">
                <div class="activity-client-lock">
                    <i class="bi bi-person-check"></i>
                    <div>
                        <div class="fw-semibold">{{ $relatedContactName ?: 'Current client' }}</div>
                        <div class="small text-muted">Linked to this client — cannot be changed here.</div>
                    </div>
                </div>
                @else
                <select name="related_to" class="form-select">
                    <option value="">— None —</option>
                    @foreach ($contacts ?? [] as $c)
                    <option value="{{ $c->contactid }}" {{ (int) old('related_to', $relatedTo ?? $activity?->related_to_id ?? 0) === (int) $c->contactid ? 'selected' : '' }}>
                        {{ trim(($c->firstname ?? '') . ' ' . ($c->lastname ?? '')) ?: 'Contact #' . $c->contactid }}
                    </option>
                    @endforeach
                </select>
                @endif
            </div>

            <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                <button type="submit" class="btn btn-primary-custom">
                    <i class="bi bi-check-lg me-1"></i> {{ $isEdit ? 'Save changes' : 'Schedule activity' }}
                </button>
                <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">Cancel</a>
                @if($isEdit)
                <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteActivityModal">
                    <i class="bi bi-trash me-1"></i> Delete
                </button>
                @endif
            </div>
        </form>
    </div>
</div>

@if($isEdit)
<div class="modal fade" id="deleteActivityModal" tabindex="-1" aria-labelledby="deleteActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="deleteActivityModalLabel">Delete activity?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">This will remove <strong>{{ $activity?->subject ?? 'this activity' }}</strong>. This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('activities.destroy', $activity?->activityid) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    @if($returnTo ?? null)
                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    @endif
                    <button type="submit" class="btn btn-danger">Delete activity</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

<style>
.activity-form-card { max-width: 760px; border-radius: 16px; }
.activity-form-section-title {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; color: var(--agile-text-muted, #64748b);
    margin-bottom: 1rem;
}
.activity-client-lock {
    display: flex; align-items: flex-start; gap: 0.75rem;
    padding: 0.85rem 1rem; border-radius: 12px;
    background: rgba(14, 67, 133, 0.06); border: 1px solid rgba(14, 67, 133, 0.12);
}
.activity-client-lock > i { font-size: 1.25rem; color: var(--agile-primary, #0E4385); margin-top: 0.1rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var typeSelect = document.getElementById('activityTypeSelect');
    var statusSelect = document.getElementById('activityStatusSelect');
    if (!typeSelect || !statusSelect) return;

    var taskStatuses = @json($taskStatuses);
    var eventStatuses = @json($eventStatuses);

    function refreshStatusOptions() {
        var isTask = typeSelect.value === 'Task';
        var options = isTask ? taskStatuses : eventStatuses;
        var current = statusSelect.value;
        statusSelect.innerHTML = '';
        options.forEach(function(st) {
            var opt = document.createElement('option');
            opt.value = st;
            opt.textContent = st;
            if (st === current || (!options.includes(current) && st === (isTask ? 'Not Started' : 'Planned'))) {
                opt.selected = true;
            }
            statusSelect.appendChild(opt);
        });
    }

    typeSelect.addEventListener('change', refreshStatusOptions);
});
</script>
