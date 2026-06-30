@php
$mailbox = config('email-service.sender', config('mail.from.address', 'life@geminialife.co.ke'));
$emailRows = collect($emails ?? []);
$sentOnPage = $emailRows->filter(fn ($row) => str_contains(strtolower(is_array($row) ? ($row['from_address'] ?? '') : ($row->from_address ?? '')), 'geminialife'))->count();
$receivedOnPage = $emailRows->count() - $sentOnPage;
$hasFilter = $clientEmail || ($contact ?? null);
@endphp

<div class="client-emails-page mb-4">
    <div class="row g-3 mb-3">
        <div class="col-sm-4">
            <div class="client-emails-stat">
                <span class="client-emails-stat-label">On record</span>
                <span class="client-emails-stat-value">{{ number_format($emailsCount ?? 0) }}</span>
                <span class="client-emails-stat-hint">Matching this client</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="client-emails-stat">
                <span class="client-emails-stat-label">Client email</span>
                <span class="client-emails-stat-value client-emails-stat-email">{{ $clientEmail ?: 'Not on file' }}</span>
                <span class="client-emails-stat-hint">Used to match correspondence</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="client-emails-stat">
                <span class="client-emails-stat-label">Mailbox</span>
                <span class="client-emails-stat-value client-emails-stat-email">{{ $mailbox }}</span>
                <span class="client-emails-stat-hint">Company inbox / outbound sender</span>
            </div>
        </div>
    </div>

    <div class="card contact-detail-card client-emails-card">
        <div class="client-emails-toolbar">
            <div>
                <h6 class="client-emails-title mb-1">
                    <i class="bi bi-envelope-paper me-2"></i>Email correspondence
                </h6>
                <p class="client-emails-subtitle mb-0">
                    @if($clientEmail && ($contact ?? null))
                        Messages to or from <strong>{{ $clientEmail }}</strong>, plus emails linked to this client's tickets.
                    @elseif($clientEmail)
                        Messages exchanged with <strong>{{ $clientEmail }}</strong> via {{ $mailbox }}.
                    @elseif($contact ?? null)
                        Emails linked to tickets for policy <span class="font-monospace">{{ $clientPolicy }}</span>.
                    @else
                        Add a client email in ERP or link a CRM prospect to match correspondence.
                    @endif
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($canSendEmailToClient)
                <a href="{{ route('support.email-client', $emailClientRouteParams) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-send me-1"></i>Send Email
                </a>
                @endif
                @if($clientEmail)
                <a href="{{ route('tools.mail-manager.create', ['from_address' => $clientEmail, 'from_name' => $clientName]) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Log Email
                </a>
                @endif
                <a href="{{ route('tools.mail-manager') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-inbox me-1"></i>Mail Manager
                </a>
            </div>
        </div>

        @if(!$hasFilter)
        <div class="client-emails-callout">
            <div class="client-emails-callout-icon"><i class="bi bi-envelope-exclamation"></i></div>
            <div>
                <h6 class="mb-1">No way to match emails yet</h6>
                <p class="mb-2 text-muted small">This client has no email address on file and no CRM prospect link. Update the client record in ERP or open the CRM prospect to add an email.</p>
                <div class="d-flex flex-wrap gap-2">
                    @if($contact ?? null)
                    <a href="{{ route('contacts.show', $contact->contactid) }}?tab=details" class="btn btn-sm btn-outline-primary">Edit CRM prospect</a>
                    @endif
                    <a href="{{ route('tools.mail-manager.create') }}" class="btn btn-sm btn-outline-secondary">Log email manually</a>
                </div>
            </div>
        </div>
        @else
            @if($emailRows->isNotEmpty())
            <div class="client-emails-page-meta px-3 py-2 border-bottom">
                <span class="badge client-emails-dir client-emails-dir-sent me-1"><i class="bi bi-arrow-up-right me-1"></i>Sent {{ $sentOnPage }}</span>
                <span class="badge client-emails-dir client-emails-dir-received"><i class="bi bi-arrow-down-left me-1"></i>Received {{ $receivedOnPage }}</span>
                <span class="text-muted small ms-2">on this page</span>
            </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 client-emails-table">
                    <thead class="table-light">
                        <tr>
                            <th class="small text-uppercase fw-bold">Direction</th>
                            <th class="small text-uppercase fw-bold">From</th>
                            <th class="small text-uppercase fw-bold">Subject</th>
                            <th class="small text-uppercase fw-bold">Date</th>
                            <th class="small text-uppercase fw-bold text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($emailRows as $email)
                        @php
                            $emailRow = is_array($email) ? (object) $email : $email;
                            $fromAddress = strtolower((string) ($emailRow->from_address ?? ''));
                            $isSent = str_contains($fromAddress, 'geminialife');
                            $fromName = trim((string) ($emailRow->from_name ?? ''));
                            $subject = trim((string) ($emailRow->subject ?? ''));
                            if ($subject === '') {
                                $subject = '(No subject)';
                            }
                        @endphp
                        <tr>
                            <td>
                                @if($isSent)
                                <span class="badge client-emails-dir client-emails-dir-sent"><i class="bi bi-arrow-up-right me-1"></i>Sent</span>
                                @else
                                <span class="badge client-emails-dir client-emails-dir-received"><i class="bi bi-arrow-down-left me-1"></i>Received</span>
                                @endif
                            </td>
                            <td class="client-emails-from">
                                <span class="fw-semibold">{{ $fromName ?: ($emailRow->from_address ?? '—') }}</span>
                                @if($fromName && ($emailRow->from_address ?? null))
                                <span class="d-block small text-muted">{{ $emailRow->from_address }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('tools.mail-manager.show', $emailRow->id) }}" class="client-emails-subject-link">
                                    {{ Str::limit($subject, 72) }}
                                </a>
                                @if(!empty($emailRow->has_attachments))
                                <span class="client-emails-attach ms-1" title="Has attachments"><i class="bi bi-paperclip"></i></span>
                                @endif
                                @if(!empty($emailRow->ticket_id))
                                <a href="{{ route('tickets.show', $emailRow->ticket_id) }}" class="badge bg-light text-primary border ms-1 text-decoration-none" title="Linked ticket">
                                    <i class="bi bi-ticket-perforated me-1"></i>Ticket
                                </a>
                                @endif
                            </td>
                            <td class="text-nowrap text-muted small">
                                {{ !empty($emailRow->date) ? \Carbon\Carbon::parse($emailRow->date)->format('d M Y H:i') : '—' }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('tools.mail-manager.show', $emailRow->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="client-emails-empty">
                                <div class="client-emails-empty-icon"><i class="bi bi-envelope-open"></i></div>
                                <h6 class="mb-2">No emails for this client yet</h6>
                                <p class="text-muted small mb-3">
                                    @if($clientEmail)
                                        Nothing matched <span class="font-monospace">{{ $clientEmail }}</span> yet. Send a message or fetch mail from Mail Manager.
                                    @else
                                        No ticket-linked emails found. Create a ticket from inbound mail in Mail Manager to link messages here.
                                    @endif
                                </p>
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    @if($canSendEmailToClient)
                                    <a href="{{ route('support.email-client', $emailClientRouteParams) }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-send me-1"></i>Send Email
                                    </a>
                                    @endif
                                    <a href="{{ route('tools.mail-manager') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-inbox me-1"></i>Open Mail Manager
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(($emailsPaginator ?? null) && $emailsPaginator->hasPages())
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-top bg-light">
                <span class="small text-muted">
                    Showing {{ $emailsPaginator->firstItem() ?? 0 }}–{{ $emailsPaginator->lastItem() ?? 0 }} of {{ number_format($emailsPaginator->total()) }}
                </span>
                {{ $emailsPaginator->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
            @elseif(($emailsCount ?? 0) > 0 && $emailRows->isNotEmpty())
            <div class="px-3 py-2 border-top bg-light">
                <span class="small text-muted">{{ number_format($emailsCount) }} email{{ ($emailsCount ?? 0) === 1 ? '' : 's' }} total</span>
            </div>
            @endif
        @endif
    </div>
</div>

<style>
.client-emails-stat {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 100%);
    border: 1px solid rgba(14, 67, 133, 0.12);
    border-radius: 14px;
    padding: 1rem 1.15rem;
    height: 100%;
    box-shadow: 0 2px 8px rgba(14, 67, 133, 0.04);
}
.client-emails-stat-label {
    display: block;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.35rem;
}
.client-emails-stat-value {
    display: block;
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--agile-primary, #0E4385);
    line-height: 1.2;
}
.client-emails-stat-value.client-emails-stat-email {
    font-size: 0.82rem;
    font-weight: 600;
    word-break: break-all;
}
.client-emails-stat-hint {
    display: block;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 0.35rem;
}
.client-emails-card { overflow: hidden; }
.client-emails-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.15rem 1.25rem;
    border-bottom: 1px solid var(--agile-border, #e2e8f0);
    background: linear-gradient(135deg, #f8fbff 0%, #fff 100%);
}
.client-emails-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--agile-text, #1e293b);
    text-transform: none;
}
.client-emails-subtitle {
    font-size: 0.84rem;
    color: #64748b;
    max-width: 42rem;
}
.client-emails-callout {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    margin: 1.25rem;
    padding: 1.15rem 1.25rem;
    border-radius: 14px;
    border: 1px dashed rgba(14, 67, 133, 0.22);
    background: rgba(14, 67, 133, 0.04);
}
.client-emails-callout-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 12px;
    background: #fff;
    color: var(--agile-primary, #0E4385);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    border: 1px solid rgba(14, 67, 133, 0.12);
}
.client-emails-dir {
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.35rem 0.55rem;
    border-radius: 999px;
}
.client-emails-dir-sent {
    background: rgba(14, 67, 133, 0.1);
    color: var(--agile-primary, #0E4385);
}
.client-emails-dir-received {
    background: rgba(5, 150, 105, 0.12);
    color: #047857;
}
.client-emails-from { min-width: 10rem; max-width: 14rem; }
.client-emails-subject-link {
    color: var(--agile-text, #1e293b);
    text-decoration: none;
    font-weight: 500;
}
.client-emails-subject-link:hover {
    color: var(--agile-primary, #0E4385);
    text-decoration: underline;
}
.client-emails-attach { color: #64748b; }
.client-emails-empty {
    text-align: center;
    padding: 3rem 1.5rem !important;
    background: #fafbfc;
}
.client-emails-empty-icon {
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background: rgba(14, 67, 133, 0.08);
    color: var(--agile-primary, #0E4385);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
}
.client-emails-table tbody tr:hover { background: rgba(14, 67, 133, 0.03); }
@media (max-width: 575.98px) {
    .client-emails-toolbar { flex-direction: column; }
    .client-emails-stat-value { font-size: 1.1rem; }
}
</style>
