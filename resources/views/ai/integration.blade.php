@extends('layouts.app')

@section('title', 'AI Integration')

@section('content')
<div class="page-header">
    <h1 class="page-title">AI Integration</h1>
    <p class="page-subtitle">Automation already in the CRM, and where language-model AI can be added. This section is separate from Tools.</p>
</div>

<div class="alert alert-light border mb-4">
    <strong>Today:</strong> no OpenAI / Claude / Gemini key is connected. What runs now is <strong>rule-based automation</strong> (keywords and scores). Emails are <strong>not</strong> in that live set on this computer.
</div>

<h2 class="h6 text-uppercase text-muted mb-3">Live now (rules, not a model)</h2>
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('compliance.complaints.index') }}" class="card tools-card h-100 text-decoration-none text-dark">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="tools-icon"><i class="bi bi-clipboard2-data"></i></div>
                    <div>
                        <span class="badge text-bg-success mb-1">Live</span>
                        <h6 class="fw-bold mb-1">Complaint detection</h6>
                        <p class="text-muted small mb-0">Inbound mail is scored for complaint language when a message is already in Mail Manager. Strong matches go on the Complaint Register.</p>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('tickets.index') }}" class="card tools-card h-100 text-decoration-none text-dark">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="tools-icon"><i class="bi bi-signpost-split"></i></div>
                    <div>
                        <span class="badge text-bg-success mb-1">Live</span>
                        <h6 class="fw-bold mb-1">Ticket assignment rules</h6>
                        <p class="text-muted small mb-0">Keywords in the title or description route the ticket to a named user (Settings → Assignment Rules).</p>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<h2 class="h6 text-uppercase text-muted mb-3">Emails (own item — not live here)</h2>
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('tools.mail-manager') }}" class="card tools-card h-100 text-decoration-none text-dark">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="tools-icon"><i class="bi bi-envelope-at"></i></div>
                    <div>
                        <span class="badge text-bg-warning mb-1">Not running here</span>
                        <h6 class="fw-bold mb-1">Mail fetch &amp; send</h6>
                        <p class="text-muted small mb-0">Inbox fetch needs Mail Manager or the scheduler. Outbound SMTP to the mail host is timing out on this PC, so auto-replies and CRM emails are not going out.</p>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card tools-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="tools-icon"><i class="bi bi-robot"></i></div>
                    <div>
                        <span class="badge text-bg-light border mb-1">Planned</span>
                        <h6 class="fw-bold mb-1">Email AI</h6>
                        <p class="text-muted small mb-0">Classify inbound mail, suggest a reply, and draft broadcast copy. Human still sends. Not connected until mail fetch/send works and an AI key is set.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<h2 class="h6 text-uppercase text-muted mb-3">Planned AI (needs a model)</h2>
<div class="row g-3">
    <div class="col-md-6 col-lg-4">
        <div class="card tools-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="tools-icon"><i class="bi bi-tags"></i></div>
                    <div>
                        <span class="badge text-bg-light border mb-1">Planned</span>
                        <h6 class="fw-bold mb-1">Smarter classify</h6>
                        <p class="text-muted small mb-0">Model decides complaint vs inquiry vs claim vs spam, and suggests nature and priority.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card tools-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="tools-icon"><i class="bi bi-chat-quote"></i></div>
                    <div>
                        <span class="badge text-bg-light border mb-1">Planned</span>
                        <h6 class="fw-bold mb-1">Suggest reply</h6>
                        <p class="text-muted small mb-0">Draft a ticket or complaint reply from the case, policy, and FAQ. Agent edits and sends.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card tools-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="tools-icon"><i class="bi bi-broadcast"></i></div>
                    <div>
                        <span class="badge text-bg-light border mb-1">Planned</span>
                        <h6 class="fw-bold mb-1">Broadcast &amp; SMS copy</h6>
                        <p class="text-muted small mb-0">Draft email or SMS in Kenya Orient tone, keep merge tokens. Human approval before send.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card tools-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="tools-icon"><i class="bi bi-search"></i></div>
                    <div>
                        <span class="badge text-bg-light border mb-1">Planned</span>
                        <h6 class="fw-bold mb-1">Ask in your own words</h6>
                        <p class="text-muted small mb-0">Natural-language search across clients, tickets, drafts, and sent SMS — not keyword-only.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card tools-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="tools-icon"><i class="bi bi-journal-text"></i></div>
                    <div>
                        <span class="badge text-bg-light border mb-1">Planned</span>
                        <h6 class="fw-bold mb-1">Call &amp; email summary</h6>
                        <p class="text-muted small mb-0">Short summary and next action on the client profile after Mail Manager or PBX activity.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card tools-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="tools-icon"><i class="bi bi-question-circle"></i></div>
                    <div>
                        <span class="badge text-bg-light border mb-1">Planned</span>
                        <h6 class="fw-bold mb-1">FAQ helper</h6>
                        <p class="text-muted small mb-0">Match a customer question to a published FAQ and offer a short answer with a link.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('head')
<style>
    .tools-card { border-radius: 16px; border: 1px solid var(--card-border, rgba(14, 67, 133, 0.12)); transition: transform .2s, box-shadow .2s; }
    .tools-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(14, 67, 133, 0.1); }
    .tools-icon { width: 48px; height: 48px; border-radius: 12px; background: var(--agile-primary-muted, rgba(14, 67, 133, 0.12)); color: var(--agile-primary, #202665); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
</style>
@endpush
