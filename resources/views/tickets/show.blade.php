@extends('layouts.app')

@section('title', ($ticket->title ?? 'Ticket') . ' — Ticket')

@section('content')
@php
    $ticketPolicy = pick_policy_excluding_pin($ticket->cf_860 ?? null, $ticket->cf_856 ?? null, $ticket->cf_872 ?? null);
    if (! $ticketPolicy && ! empty($ticket->description ?? '') && preg_match('/Related policy:\s*([^\n]+)/i', (string) $ticket->description, $m)) {
        $p = trim($m[1]);
        $cid = (string) ($ticket->contact_id ?? '');
        if ($p !== '' && $p !== $cid && ! looks_like_kra_pin($p) && ! looks_like_client_id($p)) {
            $ticketPolicy = $p;
        }
    }
    $tab = $activeTab ?? 'summary';
    $ticketTabUrl = function (string $tabName) use ($ticket) {
        $params = ['ticket' => $ticket->ticketid];
        if ($tabName !== 'summary') {
            $params['tab'] = $tabName;
        }
        return route('tickets.show', $params);
    };
    $commentsCount = ($comments ?? collect())->count();
@endphp

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="contact-detail-header client-profile-hero card contact-detail-card mb-4">
    <div class="card-body p-4">
        <nav class="mb-3 text-uppercase small client-profile-breadcrumb">
            <a href="{{ route('tickets.index') }}" class="text-muted">Tickets</a>
            @if($ticketPolicy)
            <span class="text-muted mx-1">/</span>
            <a href="{{ route('support.clients.show', ['policy' => $ticketPolicy, 'tab' => 'tickets']) }}" class="text-muted">Client</a>
            @elseif($ticket->contact_id ?? null)
            <span class="text-muted mx-1">/</span>
            <a href="{{ route('contacts.show', $ticket->contact_id) }}?tab=tickets" class="text-muted">Prospect</a>
            @endif
            <span class="text-muted mx-1">/</span>
            <span class="text-dark">{{ $ticket->ticket_no ?? $ticket->ticketid }}</span>
        </nav>
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-start gap-3 flex-grow-1">
                <div class="contact-avatar-lg"><i class="bi bi-ticket-perforated"></i></div>
                <div class="flex-grow-1" style="min-width:220px">
                    <h1 class="page-title mb-2 client-hero-name">{{ $ticket->title ?? 'Untitled Ticket' }}</h1>
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                        <span class="ticket-status-badge ticket-status-{{ Str::slug($ticket->status ?? '') }}">{{ $ticket->status ?? '—' }}</span>
                        @if($ticket->priority ?? null)
                        <span class="badge bg-light text-dark border">{{ $ticket->priority }} priority</span>
                        @endif
                        @if($ticket->category ?? null)
                        <span class="badge bg-primary-subtle text-primary-emphasis">{{ $ticket->category }}</span>
                        @endif
                        @if($ticketPolicy)
                        <span class="client-hero-policy font-monospace">{{ $ticketPolicy }}</span>
                        @endif
                        <span class="text-muted small font-monospace">{{ $ticket->ticket_no ?? $ticket->ticketid }}</span>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center client-hero-actions">
                @if($ticketPolicy)
                <a href="{{ route('support.clients.show', ['policy' => $ticketPolicy, 'tab' => 'tickets']) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-person me-1"></i>Client
                </a>
                @elseif($ticket->contact_id ?? null)
                <a href="{{ route('contacts.show', $ticket->contact_id) }}?tab=tickets" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-person me-1"></i>Prospect
                </a>
                @endif
                @if($ticket->contact_id ?? null)
                <a href="{{ route('support.sms-notifier', array_filter(['contact_id' => $ticket->contact_id, 'return_policy' => $ticketPolicy, 'ticket_id' => $ticket->ticketid])) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chat-dots me-1"></i>SMS
                </a>
                <a href="{{ route('support.email-client', array_filter(['contact_id' => $ticket->contact_id, 'return_policy' => $ticketPolicy, 'ticket_id' => $ticket->ticketid])) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-envelope me-1"></i>Email
                </a>
                @endif
                @if(($ticket->status ?? '') !== 'Closed' && ($canCloseTickets ?? true))
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#closeTicketModal">
                    <i class="bi bi-check-circle me-1"></i>Close
                </button>
                @endif
                @if(($ticket->status ?? '') !== 'Inactive')
                <form action="{{ route('tickets.inactivate', $ticket->ticketid) }}" method="POST" onsubmit="return confirm('Inactivate this ticket? It will no longer appear in active lists.');" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Inactivate ticket"><i class="bi bi-pause-circle me-1"></i>Inactivate</button>
                </form>
                @endif
                <a href="{{ route('tickets.edit', $ticket->ticketid) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

<div class="card contact-detail-card client-module-tabs-shell mb-4">
    <div class="card-body py-2 px-2">
        <ul class="nav contact-module-tabs client-module-tabs mb-0">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'summary' ? 'active' : '' }}" href="{{ $ticketTabUrl('summary') }}">Summary</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'details' ? 'active' : '' }}" href="{{ $ticketTabUrl('details') }}">Details</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'updates' ? 'active' : '' }}" href="{{ $ticketTabUrl('updates') }}">
                    Updates
                    @if($commentsCount > 0)
                    <span class="badge bg-primary ms-1">{{ $commentsCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'kb' ? 'active' : '' }}" href="{{ $ticketTabUrl('kb') }}">
                    <i class="bi bi-journal-bookmark me-1"></i>Knowledge Base
                </a>
            </li>
            <li class="nav-item client-module-tabs-divider" aria-hidden="true"></li>
            @if($ticket->contact_id ?? null)
            <li class="nav-item">
                <a class="nav-link" href="{{ route('support.email-client', array_filter(['contact_id' => $ticket->contact_id, 'return_policy' => $ticketPolicy, 'ticket_id' => $ticket->ticketid])) }}" title="Email">
                    <i class="bi bi-envelope"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('support.sms-notifier', array_filter(['contact_id' => $ticket->contact_id, 'return_policy' => $ticketPolicy, 'ticket_id' => $ticket->ticketid])) }}" title="SMS">
                    <i class="bi bi-chat-dots"></i>
                </a>
            </li>
            @endif
            @if($ticketPolicy)
            <li class="nav-item">
                <a class="nav-link" href="{{ route('support.clients.show', ['policy' => $ticketPolicy, 'tab' => 'tickets']) }}" title="Client">
                    <i class="bi bi-person"></i>
                </a>
            </li>
            @elseif($ticket->contact_id ?? null)
            <li class="nav-item">
                <a class="nav-link" href="{{ route('contacts.show', $ticket->contact_id) }}?tab=tickets" title="Prospect">
                    <i class="bi bi-person"></i>
                </a>
            </li>
            @endif
        </ul>
    </div>
</div>

{{-- Workflow strip (all tabs) --}}
<div class="ticket-workflow mb-4">
    <div class="ticket-workflow-step">
        <span class="ticket-workflow-label">Created by</span>
        <span class="ticket-workflow-value">{{ $ticket->created_by_name ?? '—' }}</span>
    </div>
    <i class="bi bi-arrow-right ticket-workflow-arrow"></i>
    <div class="ticket-workflow-step">
        <span class="ticket-workflow-label">Assigned to</span>
        <span class="ticket-workflow-value">{{ $ticket->assigned_to_name ?? '—' }}</span>
    </div>
    @if(($ticket->status ?? '') === 'Closed')
    <i class="bi bi-arrow-right ticket-workflow-arrow"></i>
    <div class="ticket-workflow-step">
        <span class="ticket-workflow-label">Closed by</span>
        <span class="ticket-workflow-value">{{ $ticket->closed_by_name ?? '—' }}</span>
    </div>
    @endif
</div>

@if($tab === 'summary')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary,#0E4385)">Description</h6>
                <div class="ticket-description">{{ $ticket->description ? nl2br(e(preg_replace("/\n{2,}/", "\n", $ticket->description))) : 'No description.' }}</div>
            </div>
        </div>
        @if($ticket->solution ?? null)
        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary,#0E4385)">Resolution</h6>
                <div class="ticket-description">{{ nl2br(e(preg_replace("/\n{2,}/", "\n", $ticket->solution ?? ''))) }}</div>
            </div>
        </div>
        @elseif(($ticket->status ?? '') !== 'Closed' && ($canCloseTickets ?? true))
        <div class="card contact-detail-card mb-4 border border-success">
            <div class="card-body p-4">
                <p class="text-muted small mb-2"><i class="bi bi-info-circle me-1"></i>Use the <strong>Knowledge Base</strong> tab → <em>Use as solution</em>, then close the ticket.</p>
                <a href="{{ $ticketTabUrl('kb') }}" class="btn btn-sm btn-outline-primary me-2"><i class="bi bi-journal-bookmark me-1"></i>Open Knowledge Base</a>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#closeTicketModal"><i class="bi bi-check-circle me-1"></i> Close Ticket</button>
            </div>
        </div>
        @elseif(($ticket->status ?? '') !== 'Closed' && !($canCloseTickets ?? true))
        <div class="card contact-detail-card mb-4 border border-warning">
            <div class="card-body p-4">
                <p class="text-muted small mb-0"><i class="bi bi-lock me-1"></i>Only certain roles or the ticket assignee can close this ticket.</p>
            </div>
        </div>
        @endif
        @if(($ticket->status ?? '') === 'Closed' && ($feedback ?? null))
        <div class="card contact-detail-card mb-4 border-start border-4 border-success">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary,#0E4385)">Client Feedback</h6>
                <p class="mb-2">
                    <strong>Rating:</strong>
                    @if($feedback->rating === 'happy')
                        <span class="text-success"><i class="bi bi-emoji-smile me-1"></i>Happy with the service</span>
                    @else
                        <span class="text-warning"><i class="bi bi-emoji-frown me-1"></i>Not satisfied</span>
                    @endif
                </p>
                @if($feedback->comment)
                <p class="text-muted small mb-0"><strong>Comment:</strong> {{ e($feedback->comment) }}</p>
                @endif
                <p class="text-muted small mt-2 mb-0">Submitted {{ $feedback->created_at?->diffForHumans() ?? '' }}</p>
            </div>
        </div>
        @endif
        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-uppercase small fw-bold mb-0" style="color:var(--agile-primary,#0E4385)">Latest updates</h6>
                    <a href="{{ $ticketTabUrl('updates') }}" class="btn btn-sm btn-outline-secondary">View all</a>
                </div>
                @forelse(($comments ?? collect())->take(3) as $comment)
                <div class="ticket-comment mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color:var(--agile-border)!important">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <span class="fw-semibold small">{{ e($comment->author_display) }}</span>
                        <span class="text-muted small">{{ $comment->created_at?->format('d M Y, H:i') ?? '' }}</span>
                    </div>
                    <div class="ticket-comment-body">{{ Str::limit($comment->body, 160) }}</div>
                </div>
                @empty
                <p class="text-muted small mb-0">No comments yet.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">Quick actions</h6>
                <div class="d-flex flex-column gap-2">
                    <a href="{{ $ticketTabUrl('kb') }}" class="btn btn-outline-primary"><i class="bi bi-journal-bookmark me-2"></i>Knowledge Base</a>
                    <a href="{{ $ticketTabUrl('updates') }}" class="btn btn-outline-primary"><i class="bi bi-chat-text me-2"></i>Add comment</a>
                    @if($ticket->contact_id ?? null)
                    <a href="{{ route('support.sms-notifier', array_filter(['contact_id' => $ticket->contact_id, 'return_policy' => $ticketPolicy])) }}" class="btn btn-outline-primary"><i class="bi bi-chat-dots me-2"></i>Send SMS</a>
                    <a href="{{ route('support.email-client', array_filter(['contact_id' => $ticket->contact_id, 'return_policy' => $ticketPolicy])) }}" class="btn btn-outline-primary"><i class="bi bi-envelope me-2"></i>Send Email</a>
                    @endif
                    @if(($ticket->status ?? '') !== 'Closed' && ($canCloseTickets ?? true))
                    <button type="button" class="btn btn-outline-success text-start" data-bs-toggle="modal" data-bs-target="#closeTicketModal">
                        <i class="bi bi-check-circle me-2"></i>Close Ticket
                    </button>
                    @endif
                    <a href="{{ route('tickets.edit', $ticket->ticketid) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-2"></i>Edit ticket</a>
                </div>
            </div>
        </div>
        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">Details</h6>
                <dl class="ticket-details mb-0">
                    <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:var(--agile-border)!important">
                        <dt class="text-muted small mb-0">Status</dt>
                        <dd class="mb-0"><span class="ticket-status-badge ticket-status-{{ Str::slug($ticket->status ?? '') }}">{{ $ticket->status ?? '—' }}</span></dd>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:var(--agile-border)!important">
                        <dt class="text-muted small mb-0">Assigned To</dt>
                        <dd class="mb-0">{{ $ticket->assigned_to_name ?? '—' }}</dd>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:var(--agile-border)!important">
                        <dt class="text-muted small mb-0">Priority</dt>
                        <dd class="mb-0">{{ $ticket->priority ?? '—' }}</dd>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <dt class="text-muted small mb-0">Ticket #</dt>
                        <dd class="mb-0 font-monospace small">{{ $ticket->ticket_no ?? $ticket->ticketid }}</dd>
                    </div>
                </dl>
                <a href="{{ $ticketTabUrl('details') }}" class="btn btn-sm btn-outline-primary mt-3">View full details</a>
            </div>
        </div>
    </div>
</div>

@elseif($tab === 'details')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary,#0E4385)">Ticket details</h6>
                <div class="client-summary-personal-grid">
                    <div class="client-summary-field">
                        <span class="client-summary-label">Title</span>
                        <span class="client-summary-value client-summary-name">{{ $ticket->title ?? '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Ticket #</span>
                        <span class="client-summary-value font-monospace">{{ $ticket->ticket_no ?? $ticket->ticketid }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Status</span>
                        <span class="client-summary-value"><span class="ticket-status-badge ticket-status-{{ Str::slug($ticket->status ?? '') }}">{{ $ticket->status ?? '—' }}</span></span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Priority</span>
                        <span class="client-summary-value">{{ $ticket->priority ?? '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Category</span>
                        <span class="client-summary-value">{{ $ticket->category ?? '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Assigned to</span>
                        <span class="client-summary-value">{{ $ticket->assigned_to_name ?? '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Created by</span>
                        <span class="client-summary-value">{{ $ticket->created_by_name ?? '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Created</span>
                        <span class="client-summary-value">{{ ($ticket->createdtime ?? null) ? date('d M Y, H:i', strtotime($ticket->createdtime)) : '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Policy</span>
                        <span class="client-summary-value font-monospace">{{ $ticketPolicy ?: '—' }}</span>
                    </div>
                    <div class="client-summary-field">
                        <span class="client-summary-label">Contact ID</span>
                        <span class="client-summary-value font-monospace">{{ $ticket->contact_id ?? '—' }}</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-top">
                    <span class="client-summary-label d-block mb-2">Description</span>
                    <div class="ticket-description">{{ $ticket->description ? nl2br(e(preg_replace("/\n{2,}/", "\n", $ticket->description))) : '—' }}</div>
                </div>
                @if($ticket->solution ?? null)
                <div class="mt-4 pt-3 border-top">
                    <span class="client-summary-label d-block mb-2">Resolution</span>
                    <div class="ticket-description">{{ nl2br(e($ticket->solution)) }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="bi bi-person-badge me-1"></i>Reassignment trail</h6>
                @if(($reassignments ?? collect())->isNotEmpty())
                <div class="ticket-reassignment-trail">
                    @foreach($reassignments as $r)
                    <div class="d-flex align-items-start gap-2 mb-3 {{ !$loop->last ? 'pb-3 border-bottom' : '' }}" style="border-color:var(--agile-border)!important">
                        <span class="ticket-reassign-arrow flex-shrink-0"><i class="bi bi-arrow-right-short text-muted"></i></span>
                        <div class="flex-grow-1 small">
                            <span class="text-muted">{{ e($r->from_user_name ?? 'Unassigned') }}</span>
                            <span class="text-muted mx-1">→</span>
                            <span class="fw-semibold">{{ e($r->to_user_name ?? '—') }}</span>
                            @if($r->reassigned_by_name ?? null)
                            <p class="text-muted mb-0 mt-1" style="font-size:0.75rem">by {{ e($r->reassigned_by_name) }} · {{ $r->created_at?->format('d M Y, H:i') ?? '' }}</p>
                            @else
                            <p class="text-muted mb-0 mt-1" style="font-size:0.75rem">{{ $r->created_at?->format('d M Y, H:i') ?? '' }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-muted small mb-0">No reassignments recorded.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@elseif($tab === 'updates')
<div class="card contact-detail-card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary,#0E4385)"><i class="bi bi-chat-text me-1"></i>Comments & further details</h6>
        <form method="POST" action="{{ route('tickets.comments.store', $ticket->ticketid) }}" class="mb-4">
            @csrf
            <label class="form-label fw-semibold">Add a comment</label>
            <textarea name="body" id="ticketCommentBox" class="form-control mb-2" rows="3" placeholder="Add details, updates, or notes about this ticket..." required>{{ old('body') }}</textarea>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Add Comment</button>
            @error('body')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </form>
        @forelse($comments ?? [] as $comment)
        <div class="ticket-comment mb-4 pb-4 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color:var(--agile-border)!important">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <span class="fw-semibold small">{{ e($comment->author_display) }}</span>
                <span class="text-muted small">{{ $comment->created_at?->format('d M Y, H:i') ?? '' }}</span>
            </div>
            <div class="ticket-comment-body">{{ nl2br(e($comment->body)) }}</div>
        </div>
        @empty
        <p class="text-muted small mb-0">No comments yet. Add one above to record updates or further details.</p>
        @endforelse
    </div>
</div>

@elseif($tab === 'kb')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card contact-detail-card mb-4" id="kbPanel"
             data-search-url="{{ route('support.faq.search') }}"
             data-mark-url="{{ route('support.faq.helpful') }}"
             data-ticket-title="{{ $ticket->title ?? '' }}"
             data-ticket-category="{{ $ticket->category ?? '' }}"
             data-can-close="{{ (($ticket->status ?? '') !== 'Closed' && ($canCloseTickets ?? true)) ? '1' : '0' }}">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="text-uppercase small fw-bold mb-0" style="color:var(--agile-primary,#0E4385)"><i class="bi bi-journal-bookmark me-1"></i>Knowledge Base</h6>
                    <a href="{{ route('support.faq') }}" class="small text-decoration-none" target="_blank" rel="noopener">Open FAQs</a>
                </div>
                <ol class="kb-steps small text-muted mb-3 ps-3">
                    <li>Search FAQs for this issue</li>
                    <li>Insert guidance into a comment, or</li>
                    <li><strong>Use as solution</strong> then close the ticket</li>
                </ol>
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="search" id="kbSearchInput" class="form-control" placeholder="Search FAQs…" autocomplete="off">
                </div>
                <div id="kbResults" class="kb-results"><p class="text-muted small mb-0">Loading suggested FAQs…</p></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card contact-detail-card mb-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold text-muted mb-3">Resolve ticket</h6>
                <p class="text-muted small mb-3">Pick an FAQ, use it as the solution, then close.</p>
                @if(($ticket->status ?? '') !== 'Closed' && ($canCloseTickets ?? true))
                <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#closeTicketModal">
                    <i class="bi bi-check-circle me-1"></i>Close Ticket
                </button>
                @else
                <p class="text-muted small mb-0">This ticket is closed or you cannot close it.</p>
                @endif
                <a href="{{ $ticketTabUrl('updates') }}" class="btn btn-outline-secondary w-100 mt-2">
                    <i class="bi bi-chat-text me-1"></i>Go to comments
                </a>
            </div>
        </div>
    </div>
</div>
@endif

<style>
.client-profile-hero {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 55%, #f0f6fc 100%);
    box-shadow: 0 4px 24px rgba(14, 67, 133, 0.08);
}
.client-profile-breadcrumb a { text-decoration: none; }
.contact-avatar-lg {
    width: 84px; height: 84px; border-radius: 20px;
    background: linear-gradient(145deg, #1A468A 0%, #0E4385 100%);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.75rem; flex-shrink: 0;
    box-shadow: 0 8px 20px rgba(14, 67, 133, 0.25);
}
.client-hero-name { color: var(--agile-primary, #0E4385); font-weight: 800; }
.client-hero-policy { font-size: 0.82rem; color: #64748b; background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 999px; }
.client-hero-actions .btn { border-radius: 8px; font-weight: 600; }
.client-module-tabs-shell { border-radius: 16px; box-shadow: 0 2px 12px rgba(14, 67, 133, 0.05); overflow: hidden; }
.client-module-tabs { display: flex; flex-direction: row; flex-wrap: wrap; align-items: center; gap: 0.15rem; border: none; }
.client-module-tabs .nav-item.client-module-tabs-divider {
    width: 1px; height: 1.75rem; margin: 0 0.35rem; padding: 0;
    background: #e2e8f0; align-self: center; pointer-events: none; flex: 0 0 1px;
}
@media (max-width: 767.98px) { .client-module-tabs .nav-item.client-module-tabs-divider { display: none; } }
.contact-module-tabs { border-bottom: none; }
.contact-module-tabs .nav-link {
    color: #64748b; font-weight: 500; padding: 0.7rem 1rem; border: none; border-radius: 10px; margin-bottom: 0;
}
.contact-module-tabs .nav-link:hover { color: #0E4385; background: rgba(14, 67, 133, 0.06); }
.contact-module-tabs .nav-link.active {
    color: #fff; background: #0E4385; box-shadow: 0 4px 12px rgba(14, 67, 133, 0.22);
}
.contact-module-tabs .nav-link.active .badge { background: rgba(255,255,255,0.25) !important; color: #fff !important; }
.contact-detail-card { border-radius: 16px; border: 1px solid rgba(14, 67, 133, 0.12); box-shadow: 0 2px 12px rgba(14, 67, 133, 0.04); }
.client-summary-personal-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.25rem; }
@media (max-width: 575.98px) { .client-summary-personal-grid { grid-template-columns: 1fr; } }
.client-summary-field { display: flex; flex-direction: column; gap: 0.2rem; }
.client-summary-label { font-size: 0.68rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; }
.client-summary-value { font-size: 0.95rem; font-weight: 600; color: #1e293b; }
.client-summary-name { font-size: 1.1rem; color: var(--agile-primary, #0E4385); }
.ticket-workflow {
    display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem 1rem;
    padding: 1rem 1.25rem; background: #f8fafc; border: 1px solid var(--agile-border, #e2e8f0); border-radius: 12px;
}
.ticket-workflow-step { display: flex; flex-direction: column; gap: 0.15rem; }
.ticket-workflow-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--agile-text-muted, #64748b); }
.ticket-workflow-value { font-weight: 500; color: var(--agile-text, #1e293b); }
.ticket-workflow-arrow { color: var(--agile-border, #e2e8f0); font-size: 0.9rem; }
.ticket-status-badge {
    font-size: 0.75rem; font-weight: 600; padding: 0.35rem 0.75rem; border-radius: 9999px; display: inline-block;
}
.ticket-status-open { background: var(--agile-primary-muted, rgba(14,67,133,.12)); color: var(--agile-primary, #0E4385); }
.ticket-status-in-progress, .ticket-status-In-Progress { background: rgba(217, 119, 6, 0.15); color: #b45309; }
.ticket-status-wait-for-response, .ticket-status-Wait-For-Response { background: rgba(14, 165, 233, 0.15); color: #0284c7; }
.ticket-status-closed { background: rgba(5, 150, 105, 0.15); color: #059669; }
.ticket-status-inactive { background: rgba(107, 114, 128, 0.15); color: #6b7280; }
.ticket-description { line-height: 1.65; color: var(--agile-text, #1e293b); }
.ticket-comment-body { line-height: 1.6; color: var(--agile-text, #1e293b); font-size: 0.9rem; }
.kb-results { max-height: 32rem; overflow: auto; }
.kb-steps { line-height: 1.45; }
.kb-article { border: 1px solid var(--agile-border, #e2e8f0); border-radius: 10px; padding: 0.7rem 0.8rem; margin-bottom: 0.6rem; background: #fff; }
.kb-article-q { font-weight: 600; font-size: 0.85rem; color: var(--agile-text, #1e293b); }
.kb-article-cat { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; }
.kb-article-a { font-size: 0.82rem; color: #475569; line-height: 1.5; max-height: 4.5rem; overflow: hidden; transition: max-height 0.2s ease; }
.kb-article-a.expanded { max-height: 40rem; }
.kb-article-actions .btn { font-size: 0.72rem; }
</style>

@if(($ticket->status ?? '') !== 'Closed' && ($canCloseTickets ?? true))
<div class="modal fade" id="closeTicketModal" tabindex="-1" aria-labelledby="closeTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="closeTicketModalLabel"><i class="bi bi-check-circle text-success me-2"></i>Close Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('tickets.close', $ticket->ticketid) }}">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="closeTicketHint">Add a brief resolution (or leave blank to use "Closed").</p>
                    <label class="form-label fw-semibold">Solution <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea name="solution" id="ticketSolutionBox" class="form-control" rows="5" placeholder="e.g. Issue resolved using FAQ guidance…" autofocus>{{ old('solution') }}</textarea>
                    <div class="form-text" id="kbSolutionSource" style="display:none"><i class="bi bi-journal-bookmark me-1"></i>Filled from knowledge base</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Close Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
(function () {
    const panel = document.getElementById('kbPanel');
    if (!panel) return;
    const url = panel.getAttribute('data-search-url');
    const markUrl = panel.getAttribute('data-mark-url');
    const input = document.getElementById('kbSearchInput');
    const box = document.getElementById('kbResults');
    if (!url || !input || !box) return;
    const canClose = panel.getAttribute('data-can-close') === '1';
    const seed = (panel.getAttribute('data-ticket-title') + ' ' + panel.getAttribute('data-ticket-category')).trim();
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function formatSolution(r) {
        return 'Resolved using FAQ guidance:\n'
            + r.question + '\n\n'
            + r.answer
            + (r.category ? ('\n\n(Source: ' + r.category + ' knowledge base)') : '');
    }

    function markHelpful(id) {
        if (!markUrl || !id) return;
        fetch(markUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ id: id }),
        }).catch(() => {});
    }

    function render(rows) {
        if (!rows.length) {
            box.innerHTML = '<p class="text-muted small mb-0">No matching FAQs. Try different keywords or open the FAQs page.</p>';
            return;
        }
        box.innerHTML = rows.map(r => {
            const solBtn = canClose
                ? `<button type="button" class="btn btn-sm btn-success" data-act="solution" data-id="${r.id}"><i class="bi bi-check-circle me-1"></i>Use as solution</button>`
                : '';
            return `<div class="kb-article" data-id="${r.id}">
                <div class="kb-article-cat mb-1">${escapeHtml(r.category)}</div>
                <div class="kb-article-q mb-1">${escapeHtml(r.question)}</div>
                <div class="kb-article-a mb-2" data-answer>${escapeHtml(r.answer).replace(/\n/g, '<br>')}</div>
                <div class="kb-article-actions d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-act="expand"><i class="bi bi-arrows-expand me-1"></i>More</button>
                    <a href="{{ $ticketTabUrl('updates') }}" class="btn btn-sm btn-outline-primary" data-act="comment" data-id="${r.id}"><i class="bi bi-chat-left-text me-1"></i>Insert into comment</a>
                    ${solBtn}
                </div>
            </div>`;
        }).join('');

        const store = {};
        rows.forEach(r => store[r.id] = r);

        box.querySelectorAll('[data-act]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                const act = this.getAttribute('data-act');
                const card = this.closest('.kb-article');
                const id = this.getAttribute('data-id');
                const r = id ? store[id] : null;
                if (act === 'expand') {
                    e.preventDefault();
                    card.querySelector('[data-answer]').classList.toggle('expanded');
                } else if (act === 'comment' && r) {
                    e.preventDefault();
                    try {
                        sessionStorage.setItem('ticketKbCommentDraft', 'KB guidance — ' + r.question + '\n' + r.answer + '\n');
                    } catch (err) {}
                    markHelpful(r.id);
                    window.location.href = this.getAttribute('href') || '{{ $ticketTabUrl('updates') }}';
                } else if (act === 'solution' && r) {
                    e.preventDefault();
                    const s = document.getElementById('ticketSolutionBox');
                    const hint = document.getElementById('closeTicketHint');
                    const source = document.getElementById('kbSolutionSource');
                    if (s) { s.value = formatSolution(r); }
                    if (hint) { hint.textContent = 'Solution filled from the knowledge base. Review and close the ticket.'; }
                    if (source) { source.style.display = ''; }
                    markHelpful(r.id);
                    const modalEl = document.getElementById('closeTicketModal');
                    if (modalEl && window.bootstrap) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                }
            });
        });
    }

    let t;
    function doSearch(q) {
        fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.json())
            .then(data => render((data && data.results) || []))
            .catch(() => { box.innerHTML = '<p class="text-muted small mb-0">Could not load knowledge base.</p>'; });
    }

    input.addEventListener('input', function () {
        clearTimeout(t);
        const q = this.value.trim();
        t = setTimeout(() => doSearch(q), 250);
    });

    doSearch(seed);
})();

document.addEventListener('DOMContentLoaded', function () {
    const box = document.getElementById('ticketCommentBox');
    if (!box) return;
    try {
        const draft = sessionStorage.getItem('ticketKbCommentDraft');
        if (draft) {
            box.value = box.value ? (box.value.replace(/\s*$/, '') + '\n\n' + draft) : draft;
            sessionStorage.removeItem('ticketKbCommentDraft');
            box.focus();
        }
    } catch (e) {}
});
</script>
@endpush
@endsection
