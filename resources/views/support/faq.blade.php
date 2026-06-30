@extends('layouts.app')

@section('title', 'FAQ')

@section('content')
@php
$categoryCounts = $categoryCounts ?? [];
$activeCategory = $activeCategory ?? 'all';
$search = $search ?? '';
$totalFaqs = (int) ($totalFaqs ?? 0);
$groupedFaqs = $groupedFaqs ?? collect();
$categories = $categories ?? collect();
$allCount = array_sum($categoryCounts);
@endphp

<div class="faq-page">
    <div class="faq-hero mb-4">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <div class="faq-hero-icon mb-3"><i class="bi bi-question-circle-fill"></i></div>
                <h1 class="faq-hero-title mb-1">FAQ & Knowledge Base</h1>
                <p class="faq-hero-desc mb-0">Answers for your team from the Kenya Orient knowledge base — search by topic or browse categories.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('tickets.create') }}" class="btn btn-light btn-sm fw-semibold">
                    <i class="bi bi-ticket-perforated me-1"></i>Open ticket
                </a>
                <a href="{{ route('support') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Support
                </a>
            </div>
        </div>
        <form method="GET" action="{{ route('support.faq') }}" class="faq-search-form mt-4">
            @if($activeCategory !== 'all')
            <input type="hidden" name="category" value="{{ $activeCategory }}">
            @endif
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" name="search" class="form-control" placeholder="Search questions, answers, or tags…" value="{{ $search }}" autocomplete="off">
                @if($search !== '')
                <a href="{{ route('support.faq', $activeCategory !== 'all' ? ['category' => $activeCategory] : []) }}" class="btn btn-outline-secondary">Clear</a>
                @endif
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="faq-stat">
                <span class="faq-stat-label">Articles</span>
                <span class="faq-stat-value">{{ number_format($totalFaqs) }}</span>
                <span class="faq-stat-hint">{{ $search !== '' || $activeCategory !== 'all' ? 'Matching filters' : 'Active in knowledge base' }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="faq-stat">
                <span class="faq-stat-label">Categories</span>
                <span class="faq-stat-value">{{ number_format(count($categoryCounts)) }}</span>
                <span class="faq-stat-hint">Topics with content</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="faq-stat">
                <span class="faq-stat-label">Source</span>
                <span class="faq-stat-value faq-stat-small">Kenya Orient</span>
                <span class="faq-stat-hint">Company knowledge base</span>
            </div>
        </div>
    </div>

    <div class="faq-toolbar mb-4">
        <span class="faq-toolbar-label">Category</span>
        <div class="faq-chips">
            <a href="{{ route('support.faq', array_filter(['search' => $search ?: null])) }}"
               class="faq-chip {{ $activeCategory === 'all' ? 'active' : '' }}">
                All <span class="faq-chip-count">{{ $allCount }}</span>
            </a>
            @foreach($categories as $cat)
            @php
                $catName = $cat->faqcategories ?? '';
                if ($catName === '') continue;
                $count = $categoryCounts[$catName] ?? 0;
            @endphp
            <a href="{{ route('support.faq', array_filter(['category' => $catName, 'search' => $search ?: null])) }}"
               class="faq-chip {{ $activeCategory === $catName ? 'active' : '' }}">
                {{ $catName }} <span class="faq-chip-count">{{ $count }}</span>
            </a>
            @endforeach
        </div>
    </div>

    @if($totalFaqs === 0)
    <div class="card faq-empty-card">
        <div class="faq-empty-icon"><i class="bi bi-journal-x"></i></div>
        <h5 class="mb-2">No FAQ articles found</h5>
        <p class="text-muted mb-3">
            @if($search !== '')
                Nothing matched “{{ $search }}”. Try different keywords or clear filters.
            @elseif($activeCategory !== 'all')
                No articles in the “{{ $activeCategory }}” category yet.
            @else
                The knowledge base has no active articles yet. Ask an administrator to add FAQs in Kenya Orient (exclude Obsolete status).
            @endif
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            @if($search !== '' || $activeCategory !== 'all')
            <a href="{{ route('support.faq') }}" class="btn btn-outline-primary btn-sm">Show all articles</a>
            @endif
            <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm">Open a ticket</a>
        </div>
    </div>
    @else
        @foreach($groupedFaqs as $groupName => $items)
        <section class="faq-group mb-4">
            <div class="faq-group-head">
                <h2 class="faq-group-title">{{ $groupName }}</h2>
                <span class="badge bg-light text-muted border">{{ $items->count() }} article{{ $items->count() === 1 ? '' : 's' }}</span>
            </div>
            <div class="accordion faq-accordion" id="faqAccordion{{ Str::slug($groupName) }}">
                @foreach($items as $faq)
                @php
                    $accordionId = 'faq-' . $faq->id;
                    $statusClass = match ($faq->status ?? '') {
                        'Published' => 'published',
                        'Reviewed' => 'reviewed',
                        'Draft' => 'draft',
                        default => 'other',
                    };
                @endphp
                <div class="accordion-item faq-item">
                    <h3 class="accordion-header" id="heading-{{ $accordionId }}">
                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapse-{{ $accordionId }}"
                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="collapse-{{ $accordionId }}">
                            <span class="faq-question-text">{{ $faq->question }}</span>
                            <span class="faq-item-meta ms-2">
                                @if(($faq->status ?? '') !== 'Published')
                                <span class="badge faq-status faq-status-{{ $statusClass }}">{{ $faq->status }}</span>
                                @endif
                                @if(!empty($faq->faq_no))
                                <span class="faq-no font-monospace">{{ $faq->faq_no }}</span>
                                @endif
                            </span>
                        </button>
                    </h3>
                    <div id="collapse-{{ $accordionId }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                        aria-labelledby="heading-{{ $accordionId }}" data-bs-parent="#faqAccordion{{ Str::slug($groupName) }}">
                        <div class="accordion-body faq-answer">
                            {!! nl2br(e($faq->answer ?? '')) !!}
                            @if(!empty($faq->tags))
                            <div class="faq-tags mt-3">
                                @foreach(array_filter(array_map('trim', explode(',', $faq->tags))) as $tag)
                                <span class="faq-tag">{{ $tag }}</span>
                                @endforeach
                            </div>
                            @endif
                            @if(!empty($faq->modifiedtime))
                            <p class="faq-updated small text-muted mt-3 mb-0">
                                Updated {{ \Carbon\Carbon::parse($faq->modifiedtime)->format('d M Y') }}
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endforeach
    @endif
</div>

<style>
.faq-hero {
    background: linear-gradient(135deg, var(--agile-primary-dark, #122952) 0%, var(--agile-primary, #0E4385) 55%, #2563eb 100%);
    border-radius: 16px;
    color: #fff;
    padding: 1.5rem 1.75rem;
    position: relative;
    overflow: hidden;
}
.faq-hero::after {
    content: '';
    position: absolute;
    right: -2rem;
    top: -2rem;
    width: 10rem;
    height: 10rem;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    pointer-events: none;
}
.faq-hero-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 12px;
    background: rgba(255,255,255,0.15);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
}
.faq-hero-title { font-size: 1.5rem; font-weight: 700; color: #fff; margin: 0; }
.faq-hero-desc { font-size: 0.92rem; color: rgba(255,255,255,0.88); max-width: 40rem; }
.faq-search-form .input-group {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}
.faq-search-form .input-group-text,
.faq-search-form .form-control,
.faq-search-form .btn { border: none; }
.faq-search-form .form-control { padding: 0.75rem 1rem; }
.faq-stat {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 100%);
    border: 1px solid rgba(14, 67, 133, 0.12);
    border-radius: 14px;
    padding: 1rem 1.15rem;
    height: 100%;
    box-shadow: 0 2px 8px rgba(14, 67, 133, 0.04);
}
.faq-stat-label {
    display: block;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.35rem;
}
.faq-stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--agile-primary, #0E4385);
    line-height: 1.1;
}
.faq-stat-value.faq-stat-small { font-size: 0.95rem; font-weight: 600; }
.faq-stat-hint { display: block; font-size: 0.75rem; color: #94a3b8; margin-top: 0.35rem; }
.faq-toolbar {
    background: #fff;
    border: 1px solid var(--agile-border, #e2e8f0);
    border-radius: 14px;
    padding: 1rem 1.15rem;
}
.faq-toolbar-label {
    display: block;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.65rem;
}
.faq-chips { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.faq-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    font-size: 0.84rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.15s ease;
}
.faq-chip:hover {
    border-color: var(--agile-primary, #0E4385);
    color: var(--agile-primary, #0E4385);
    background: rgba(14, 67, 133, 0.06);
}
.faq-chip.active {
    background: var(--agile-primary, #0E4385);
    border-color: var(--agile-primary, #0E4385);
    color: #fff;
}
.faq-chip-count {
    font-size: 0.72rem;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    background: rgba(0,0,0,0.08);
}
.faq-chip.active .faq-chip-count { background: rgba(255,255,255,0.2); }
.faq-group-head {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
}
.faq-group-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--agile-text, #1e293b);
    margin: 0;
}
.faq-accordion .accordion-item {
    border: 1px solid var(--agile-border, #e2e8f0);
    border-radius: 12px !important;
    margin-bottom: 0.65rem;
    overflow: hidden;
}
.faq-accordion .accordion-button {
    font-weight: 600;
    color: var(--agile-text, #1e293b);
    background: #fff;
    box-shadow: none;
    padding: 1rem 1.15rem;
    gap: 0.5rem;
}
.faq-accordion .accordion-button:not(.collapsed) {
    background: rgba(14, 67, 133, 0.05);
    color: var(--agile-primary, #0E4385);
}
.faq-accordion .accordion-button::after { flex-shrink: 0; }
.faq-question-text { flex: 1; text-align: left; line-height: 1.45; }
.faq-item-meta {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}
.faq-no { font-size: 0.72rem; color: #94a3b8; }
.faq-status { font-size: 0.68rem; font-weight: 600; }
.faq-status-published { background: #dcfce7; color: #166534; }
.faq-status-reviewed { background: #dbeafe; color: #1d4ed8; }
.faq-status-draft { background: #fef3c7; color: #92400e; }
.faq-status-other { background: #f1f5f9; color: #475569; }
.faq-answer { color: #334155; line-height: 1.6; padding: 1rem 1.15rem 1.15rem; }
.faq-tags { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.faq-tag {
    font-size: 0.72rem;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: #f1f5f9;
    color: #64748b;
}
.faq-empty-card {
    border-radius: 16px;
    border: 1px dashed #e2e8f0;
    padding: 3rem 1.5rem;
    text-align: center;
    background: #fafbfc;
}
.faq-empty-icon {
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
@media (max-width: 767.98px) {
    .faq-accordion .accordion-button { flex-wrap: wrap; }
    .faq-item-meta { margin-left: 0 !important; margin-top: 0.35rem; }
}
</style>
@endsection
