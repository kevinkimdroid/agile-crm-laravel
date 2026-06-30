<div class="card contact-detail-card mb-4">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Tickets</h6>
            <a href="{{ route('support.clients.create-ticket', ['policy' => $clientPolicy]) }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Add Ticket
            </a>
        </div>
        @if($contact ?? null)
        <form action="{{ route('support.clients.show', $clientShowBase) }}" method="GET" class="p-3 border-bottom">
            <input type="hidden" name="tab" value="tickets">
            @if($fromServeClient ?? false)
            <input type="hidden" name="from" value="serve-client">
            @endif
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search tickets..." value="{{ $ticketSearch ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Status</label>
                    <select name="list" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="Open" {{ ($ticketStatus ?? '') === 'Open' ? 'selected' : '' }}>Open</option>
                        <option value="In Progress" {{ ($ticketStatus ?? '') === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="Wait For Response" {{ ($ticketStatus ?? '') === 'Wait For Response' ? 'selected' : '' }}>Wait For Response</option>
                        <option value="Closed" {{ ($ticketStatus ?? '') === 'Closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-search me-1"></i> Search</button>
                </div>
            </div>
        </form>
        @endif
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase fw-bold">Ticket</th>
                        <th class="small text-uppercase fw-bold">Title</th>
                        <th class="small text-uppercase fw-bold">Policy</th>
                        <th class="small text-uppercase fw-bold">Status</th>
                        <th class="small text-uppercase fw-bold">Priority</th>
                        <th class="small text-uppercase fw-bold">Assigned To</th>
                        <th class="small text-uppercase fw-bold">Created</th>
                        <th class="small text-uppercase fw-bold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets ?? [] as $ticket)
                    @php
                        $policyNum = pick_policy_excluding_pin($ticket->cf_860 ?? null, $ticket->cf_856 ?? null, $ticket->cf_872 ?? null) ?: $clientPolicy;
                        $ownerName = trim(($ticket->owner_first ?? '') . ' ' . ($ticket->owner_last ?? '')) ?: ($ticket->owner_username ?? '—');
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('tickets.show', $ticket->ticketid) }}" class="fw-semibold text-primary text-decoration-none">
                                {{ $ticket->ticket_no ?? 'TT' . $ticket->ticketid }}
                            </a>
                        </td>
                        <td><a href="{{ route('tickets.show', $ticket->ticketid) }}" class="text-decoration-none">{{ $ticket->title ?? 'Untitled' }}</a></td>
                        <td><span class="text-muted">{{ $policyNum ?? '—' }}</span></td>
                        <td><span class="badge tickets-badge-{{ Str::slug($ticket->status ?? '') }}">{{ $ticket->status ?? '—' }}</span></td>
                        <td><span class="text-muted">{{ $ticket->priority ?? 'Normal' }}</span></td>
                        <td><span class="text-muted small">{{ $ownerName }}</span></td>
                        <td><span class="text-muted small">{{ $ticket->createdtime ? date('d M Y', strtotime($ticket->createdtime)) : '—' }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('tickets.show', $ticket->ticketid) }}" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-ticket-perforated display-6 d-block mb-2 opacity-50"></i>
                            <p class="mb-2">No tickets for this client yet.</p>
                            <a href="{{ route('support.clients.create-ticket', ['policy' => $clientPolicy]) }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i> Add Ticket
                            </a>
                            @if(!($contact ?? null))
                            <p class="small mt-3 mb-0">Link a CRM prospect to this policy to see tickets tied to the prospect record.</p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(($ticketsPaginator ?? null) && $ticketsPaginator->hasPages())
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-top bg-light">
            <span class="small text-muted">Showing {{ $ticketsPaginator->firstItem() ?? 0 }}–{{ $ticketsPaginator->lastItem() ?? 0 }} of {{ $ticketsPaginator->total() }}</span>
            {{ $ticketsPaginator->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
