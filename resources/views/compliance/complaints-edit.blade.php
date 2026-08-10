@extends('layouts.app')

@section('title', 'Edit Complaint — ' . $complaint->complaint_ref)

@section('content')
<nav class="breadcrumb-nav mb-3">
    <a href="{{ route('compliance.complaints.index') }}" class="text-muted small text-decoration-none">Complaint Register</a>
    <span class="text-muted mx-2">/</span>
    <a href="{{ route('compliance.complaints.show', $complaint) }}" class="text-muted small text-decoration-none">{{ $complaint->complaint_ref }}</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Edit</span>
</nav>
<h1 class="app-page-title mb-4">Edit Complaint</h1>

<form method="POST" action="{{ route('compliance.complaints.update', $complaint) }}">
    @csrf
    @method('PUT')

    <div class="app-card mb-4">
        <div class="p-4">
            <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary);letter-spacing:0.08em">Reference</h6>
            <p class="text-muted mb-0 font-monospace">{{ $complaint->complaint_ref }}</p>
        </div>
    </div>

    <div class="app-card mb-4">
        <div class="p-4">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">Link Records</h6>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Client / Policy No.</label>
                    <div class="position-relative" data-lookup="clients"
                         data-url="{{ route('compliance.complaints.lookup.clients') }}">
                        <input type="text" class="form-control" data-lookup-input autocomplete="off"
                               placeholder="Click to select or search client / policy…">
                        <div class="list-group position-absolute w-100 shadow-sm d-none" data-lookup-results style="z-index:20;max-height:16rem;overflow:auto"></div>
                    </div>
                    <div class="form-text">Search ERP clients — selecting one fills name, policy, email and phone below.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Prospect</label>
                    <div class="position-relative" data-lookup="prospects"
                         data-url="{{ route('compliance.complaints.lookup.prospects') }}"
                         data-target-id="complaint-contact-id">
                        <input type="text" class="form-control" data-lookup-input autocomplete="off"
                               value="{{ old('contact_id', $complaint->contact_id) ? 'Prospect #'.old('contact_id', $complaint->contact_id) : '' }}"
                               placeholder="Click to select or search prospect…">
                        <div class="list-group position-absolute w-100 shadow-sm d-none" data-lookup-results style="z-index:20;max-height:16rem;overflow:auto"></div>
                    </div>
                    <input type="hidden" name="contact_id" id="complaint-contact-id" value="{{ old('contact_id', $complaint->contact_id) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="app-card mb-4">
        <div class="p-4">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">Complainant Details</h6>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Date Received <span class="text-danger">*</span></label>
                    <input type="date" name="date_received" class="form-control" value="{{ old('date_received', $complaint->date_received?->format('Y-m-d')) }}" required>
                    @error('date_received')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Source</label>
                    <select name="source" class="form-select">
                        <option value="">Select source</option>
                        @foreach(\App\Models\Complaint::SOURCES as $val => $label)
                            <option value="{{ $val }}" {{ old('source', $complaint->source) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Complainant Name <span class="text-danger">*</span></label>
                    <input type="text" name="complainant_name" id="complaint-name" class="form-control" value="{{ old('complainant_name', $complaint->complainant_name) }}" required>
                    @error('complainant_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="complainant_phone" id="complaint-phone" class="form-control" value="{{ old('complainant_phone', $complaint->complainant_phone) }}">
                </div>
            </div>
            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="complainant_email" id="complaint-email" class="form-control" value="{{ old('complainant_email', $complaint->complainant_email) }}">
                    @error('complainant_email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Policy Number</label>
                    <input type="text" name="policy_number" id="complaint-policy" class="form-control font-monospace" value="{{ old('policy_number', $complaint->policy_number) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="app-card mb-4">
        <div class="p-4">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">Complaint & Resolution</h6>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nature</label>
                    <select name="nature" class="form-select">
                        <option value="">Select nature</option>
                        @foreach(\App\Models\Complaint::NATURES as $val => $label)
                            <option value="{{ $val }}" {{ old('nature', $complaint->nature) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        @foreach(\App\Models\Complaint::STATUSES as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $complaint->status) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Priority</label>
                    <select name="priority" class="form-select">
                        @foreach(\App\Models\Complaint::PRIORITIES as $val => $label)
                            <option value="{{ $val }}" {{ old('priority', $complaint->priority) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Date Resolved</label>
                    <input type="date" name="date_resolved" class="form-control" value="{{ old('date_resolved', $complaint->date_resolved?->format('Y-m-d')) }}">
                </div>
            </div>
            <div class="mt-4">
                <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                <textarea name="description" class="form-control" rows="5" required>{{ old('description', $complaint->description) }}</textarea>
                @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mt-4">
                <label class="form-label fw-semibold">Resolution Notes</label>
                <textarea name="resolution_notes" class="form-control" rows="4" placeholder="Actions taken, outcome, complainant response">{{ old('resolution_notes', $complaint->resolution_notes) }}</textarea>
            </div>
            <div class="mt-4">
                <label class="form-label fw-semibold">Assigned To</label>
                @php
                    $rawAssigned = old('assigned_to', $complaint->assigned_to);
                    $assignedType = old('assigned_type');
                    $assignedUser = old('assigned_user');
                    $assignedAgent = old('assigned_agent');
                    if ($assignedType === null) {
                        if (is_string($rawAssigned) && str_starts_with($rawAssigned, 'User: ')) {
                            $assignedType = 'user';
                            $assignedUser = $assignedUser ?: substr($rawAssigned, 6);
                        } elseif (is_string($rawAssigned) && str_starts_with($rawAssigned, 'Agent: ')) {
                            $assignedType = 'agent';
                            $assignedAgent = $assignedAgent ?: substr($rawAssigned, 7);
                        } elseif ($rawAssigned) {
                            // Legacy plain name — treat as user if it matches a staff name, else keep as free-text user.
                            $userNames = collect($users ?? [])->pluck('name')->all();
                            $agentLabels = collect($agents ?? [])->map->label()->all();
                            if (in_array($rawAssigned, $agentLabels, true)) {
                                $assignedType = 'agent';
                                $assignedAgent = $rawAssigned;
                            } else {
                                $assignedType = 'user';
                                $assignedUser = $rawAssigned;
                            }
                        } else {
                            $assignedType = '';
                        }
                    }
                @endphp
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <select name="assigned_type" id="assignedType" class="form-select">
                            <option value="" {{ $assignedType === '' || $assignedType === null ? 'selected' : '' }}>Unassigned</option>
                            <option value="user" {{ $assignedType === 'user' ? 'selected' : '' }}>User (staff)</option>
                            <option value="agent" {{ $assignedType === 'agent' ? 'selected' : '' }}>Agent (intermediary)</option>
                        </select>
                    </div>
                    <div class="col-md-8" id="assignedUserWrap" style="{{ $assignedType === 'user' ? '' : 'display:none' }}">
                        <select name="assigned_user" id="assignedUser" class="form-select">
                            <option value="">Select user…</option>
                            @php $userKnown = false; @endphp
                            @foreach(($users ?? []) as $u)
                                @php $sel = $assignedUser === $u->name; $userKnown = $userKnown || $sel; @endphp
                                <option value="{{ $u->name }}" {{ $sel ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                            @if($assignedUser && ! $userKnown)
                                <option value="{{ $assignedUser }}" selected>{{ $assignedUser }} (current)</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-8" id="assignedAgentWrap" style="{{ $assignedType === 'agent' ? '' : 'display:none' }}">
                        <select name="assigned_agent" id="assignedAgent" class="form-select">
                            <option value="">Select agent…</option>
                            @php $agentKnown = false; @endphp
                            @foreach(($agents ?? []) as $agent)
                                @php $sel = $assignedAgent === $agent->label(); $agentKnown = $agentKnown || $sel; @endphp
                                <option value="{{ $agent->label() }}" {{ $sel ? 'selected' : '' }}>{{ $agent->label() }}</option>
                            @endforeach
                            @if($assignedAgent && ! $agentKnown)
                                <option value="{{ $assignedAgent }}" selected>{{ $assignedAgent }} (current)</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="form-text">Choose whether this complaint is assigned to a staff user or an insurance agent.</div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn app-btn-primary"><i class="bi bi-check-lg me-1"></i>Update</button>
        <a href="{{ route('compliance.complaints.show', $complaint) }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

@include('compliance.partials.complaint-lookup-script')
@include('compliance.partials.complaint-assignee-script')
@endsection
