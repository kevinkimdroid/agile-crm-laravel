@extends('layouts.app')

@section('title', 'Reports')

@section('content')
@php
$monthStart = now()->startOfMonth()->format('Y-m-d');
$today = now()->format('Y-m-d');
$yearStart = now()->startOfYear()->format('Y-m-d');
$ticketTotal = array_sum($ticketsByStatus ?? []);
$openTickets = collect($ticketsByStatus ?? [])
    ->reject(fn ($_, $status) => in_array(strtolower((string) $status), ['closed', 'inactive'], true))
    ->sum();
$analytics = $analyticsSummary ?? [];
$agingTicketCount = (int) ($agingTicketCount ?? 0);
$ticketsDailyTrend = $ticketsDailyTrend ?? [];
$ticketsMonthlyTrend = $ticketsMonthlyTrend ?? [];
$topCategories30d = $topCategories30d ?? [];
$leadsMonthlyTrend = $leadsMonthlyTrend ?? [];
$callsSummary = $callsSummary ?? ['by_status' => [], 'total_calls' => 0];
$ticketChart = collect($ticketsByStatus ?? [])->map(fn ($count, $status) => [
    'status' => $status ?: 'Unknown',
    'count' => (int) $count,
])->values()->toArray();
$leadStatusChart = collect($leadsByStatus ?? [])->map(fn ($count, $status) => [
    'status' => $status ?: 'Unknown',
    'count' => (int) $count,
])->values()->toArray();
$salesChart = collect($salesByPerson ?? [])->take(10)->map(fn ($r) => [
    'name' => trim($r->name ?? '') ?: 'Unassigned',
    'total' => (float) ($r->total ?? 0),
])->values()->toArray();

$reportSections = [
    'Audit & compliance' => [
        ['route' => 'reports.sla-broken', 'icon' => 'bi-shield-exclamation', 'badge' => 'SLA', 'name' => 'Broken SLA Report', 'hint' => 'Tickets that exceeded department TAT.', 'keywords' => 'sla broken tat compliance'],
        ['route' => 'reports.ticket-aging', 'icon' => 'bi-hourglass-split', 'badge' => 'Aging', 'name' => 'Ticket Aging', 'hint' => 'Open tickets older than 7+ days.', 'keywords' => 'aging stale open tickets'],
        ['route' => 'reports.tickets-by-date', 'icon' => 'bi-calendar-range', 'badge' => 'Tickets', 'name' => 'Tickets by Date', 'hint' => 'Filter by created date with Excel export.', 'keywords' => 'tickets date range created'],
        ['route' => 'reports.reassignment-audit', 'icon' => 'bi-journal-text', 'badge' => 'Audit', 'name' => 'Ticket Audit Trail', 'hint' => 'Reassignment history for CRM and work tickets.', 'keywords' => 'reassignment audit trail history'],
        ['route' => 'reports.assignment-handlers', 'icon' => 'bi-person-check', 'badge' => 'Handlers', 'name' => 'Assignment Handlers', 'hint' => 'Created, checked, authorized, and closed by.', 'keywords' => 'assignment handlers handlers'],
        ['route' => 'reports.bounced-emails', 'icon' => 'bi-envelope-x', 'badge' => 'Email', 'name' => 'Bounced Emails', 'hint' => 'Failed delivery addresses from company mail.', 'keywords' => 'bounced email delivery failed'],
    ],
    'Performance & workload' => [
        ['route' => 'reports.management-usage', 'icon' => 'bi-graph-up', 'badge' => 'Management', 'name' => 'Usage & Top Issues', 'hint' => 'Trend over time and top reported categories.', 'keywords' => 'management usage issues trend'],
        ['route' => 'reports.ticket-workload-performance', 'icon' => 'bi-speedometer2', 'badge' => 'Workload', 'name' => 'Ticket Workload', 'hint' => 'Work tickets handled and reassignments per user.', 'keywords' => 'workload performance users'],
        ['route' => 'reports.ticket-automation-analysis', 'icon' => 'bi-robot', 'badge' => 'Automation', 'name' => 'Automation Analysis', 'hint' => 'Normal vs work tickets and automation priorities.', 'keywords' => 'automation analysis rules'],
        ['route' => 'reports.work-activities', 'icon' => 'bi-calendar-check', 'badge' => 'Activities', 'name' => 'Work Activities', 'hint' => 'Calendar tasks and work ticket updates by user.', 'keywords' => 'activities calendar work updates'],
    ],
    'CRM & operations' => [
        ['route' => 'reports.contacts-summary', 'icon' => 'bi-people', 'badge' => 'Prospects', 'name' => 'Prospects Summary', 'hint' => 'Total and new prospect overview.', 'keywords' => 'contacts prospects clients'],
        ['route' => 'reports.calls-summary', 'icon' => 'bi-telephone', 'badge' => 'Calls', 'name' => 'Calls Summary', 'hint' => 'PBX call volume and duration.', 'keywords' => 'calls pbx phone volume'],
        ['route' => 'deals.index', 'icon' => 'bi-briefcase', 'badge' => 'Deals', 'name' => 'Deals Pipeline', 'hint' => 'Open opportunities and closed won revenue.', 'keywords' => 'deals pipeline sales'],
        ['route' => 'leads.index', 'icon' => 'bi-funnel', 'badge' => 'Leads', 'name' => 'Leads', 'hint' => 'Lead sources and conversion funnel.', 'keywords' => 'leads source marketing'],
    ],
];

$quickExports = [
    ['route' => 'reports.export.all-excel', 'params' => [], 'label' => 'Export all', 'primary' => true],
    ['route' => 'reports.export.management-usage', 'params' => ['date_from' => $yearStart, 'date_to' => $today, 'simple' => 1, 'format' => 'xlsx'], 'label' => 'Management summary'],
    ['route' => 'reports.export.tickets-by-date', 'params' => ['date_from' => $monthStart, 'date_to' => $today, 'format' => 'xlsx'], 'label' => 'Tickets this month'],
    ['route' => 'reports.export.ticket-workload-performance', 'params' => ['date_from' => $monthStart, 'date_to' => $today, 'target' => 200, 'format' => 'xlsx'], 'label' => 'Ticket workload'],
    ['route' => 'reports.export.assignment-handlers', 'params' => ['date_from' => $monthStart, 'date_to' => $today, 'limit' => 50000, 'format' => 'xlsx'], 'label' => 'Assignment handlers'],
];
@endphp

<div class="reports-hub">
    <div class="reports-hero mb-4">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <div class="reports-hero-icon mb-3"><i class="bi bi-bar-chart-line-fill"></i></div>
                <h1 class="reports-hero-title mb-1">Reports & Analytics</h1>
                <p class="reports-hero-desc mb-0">Audit compliance, ticket performance, sales pipeline, and operational insights for Kenya Orient.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ route('reports.export.all-excel') }}" class="btn btn-light btn-sm fw-semibold">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export all (Excel)
                </a>
                <a href="{{ route('reports.export.all-pdf') }}" class="btn btn-outline-light btn-sm fw-semibold">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Export all (PDF)
                </a>
                <span class="reports-hero-time small">
                    <i class="bi bi-clock me-1"></i>{{ now()->format('d M Y, g:i A') }}
                </span>
            </div>
        </div>
        <div class="reports-search-wrap mt-4">
            <i class="bi bi-search"></i>
            <input type="search" id="reportsSearch" class="form-control" placeholder="Search reports by name or topic…" autocomplete="off" aria-label="Search reports">
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="reports-kpi">
                <span class="reports-kpi-label">Won revenue</span>
                <span class="reports-kpi-value">KES {{ number_format($wonRevenue ?? 0, 0) }}</span>
                <a href="{{ route('deals.index') }}" class="reports-kpi-link small">View deals <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="reports-kpi">
                <span class="reports-kpi-label">Pipeline value</span>
                <span class="reports-kpi-value">KES {{ number_format($pipelineValue ?? 0, 0) }}</span>
                <a href="{{ route('deals.index') }}" class="reports-kpi-link small">Active opportunities <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="reports-kpi">
                <span class="reports-kpi-label">Open tickets</span>
                <span class="reports-kpi-value">{{ number_format($openTickets) }}</span>
                <a href="{{ route('tickets.index') }}" class="reports-kpi-link small">Ticket queue <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="reports-kpi">
                <span class="reports-kpi-label">All tickets</span>
                <span class="reports-kpi-value">{{ number_format($ticketTotal) }}</span>
                <span class="reports-kpi-hint small text-muted">{{ count($ticketsByCategory ?? []) }} categories tracked</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="reports-kpi reports-kpi-analytics">
                <span class="reports-kpi-label">Tickets created (30d)</span>
                <span class="reports-kpi-value">{{ number_format($analytics['tickets_created_30d'] ?? 0) }}</span>
                <span class="reports-kpi-hint small text-muted">New help desk volume</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="reports-kpi reports-kpi-analytics">
                <span class="reports-kpi-label">Tickets closed (30d)</span>
                <span class="reports-kpi-value">{{ number_format($analytics['tickets_closed_30d'] ?? 0) }}</span>
                <span class="reports-kpi-hint small text-muted">Resolved or closed</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="reports-kpi reports-kpi-analytics">
                <span class="reports-kpi-label">Closure rate (30d)</span>
                <span class="reports-kpi-value">{{ number_format($analytics['closure_rate_30d'] ?? 0, 1) }}%</span>
                <span class="reports-kpi-hint small text-muted">Closed ÷ created</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="reports-kpi reports-kpi-analytics">
                <span class="reports-kpi-label">Aging tickets (7d+)</span>
                <span class="reports-kpi-value">{{ number_format($agingTicketCount) }}</span>
                <a href="{{ route('reports.ticket-aging') }}" class="reports-kpi-link small">View aging report <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="reports-kpi reports-kpi-analytics">
                <span class="reports-kpi-label">Prospects</span>
                <span class="reports-kpi-value">{{ number_format($analytics['prospects_total'] ?? 0) }}</span>
                <span class="reports-kpi-hint small text-muted">{{ number_format($analytics['prospects_new_30d'] ?? 0) }} new in 30 days</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="reports-kpi reports-kpi-analytics">
                <span class="reports-kpi-label">Total calls</span>
                <span class="reports-kpi-value">{{ number_format($analytics['total_calls'] ?? 0) }}</span>
                <span class="reports-kpi-hint small text-muted">{{ number_format($analytics['call_duration_hours'] ?? 0, 1) }} hours logged</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="reports-kpi reports-kpi-analytics">
                <span class="reports-kpi-label">Leads (all time)</span>
                <span class="reports-kpi-value">{{ number_format(array_sum($leadsBySource ?? [])) }}</span>
                <a href="{{ route('leads.index') }}" class="reports-kpi-link small">Lead pipeline <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="reports-kpi reports-kpi-analytics">
                <span class="reports-kpi-label">Categories (30d)</span>
                <span class="reports-kpi-value">{{ number_format(array_sum($topCategories30d)) }}</span>
                <span class="reports-kpi-hint small text-muted">{{ count($topCategories30d) }} top categories</span>
            </div>
        </div>
    </div>

    <section class="reports-section mb-4">
        <h2 class="reports-section-title">Analytics & trends</h2>
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="reports-chart-card card">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h6 class="fw-semibold mb-1">Ticket volume — last 30 days</h6>
                                <p class="text-muted small mb-0">Daily tickets created vs closed</p>
                            </div>
                            <span class="badge bg-light text-dark border">30-day window</span>
                        </div>
                        <div class="reports-chart-wrap" data-chart-wrap="chart-tickets-daily">
                            <canvas id="chart-tickets-daily" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="reports-chart-card card h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-1">Monthly tickets</h6>
                        <p class="text-muted small mb-3">Created per month (6 months)</p>
                        <div class="reports-chart-wrap" data-chart-wrap="chart-tickets-monthly">
                            <canvas id="chart-tickets-monthly" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="reports-chart-card card h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-1">Top categories (30d)</h6>
                        <p class="text-muted small mb-3">Most common ticket types</p>
                        <div class="reports-chart-wrap" data-chart-wrap="chart-categories-30d">
                            <canvas id="chart-categories-30d" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="reports-chart-card card h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-1">Calls by status</h6>
                        <p class="text-muted small mb-3">PBX call outcomes</p>
                        <div class="reports-chart-wrap" data-chart-wrap="chart-calls-status">
                            <canvas id="chart-calls-status" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="reports-chart-card card h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-1">Leads created per month</h6>
                        <p class="text-muted small mb-3">New lead volume (6 months)</p>
                        <div class="reports-chart-wrap" data-chart-wrap="chart-leads-monthly">
                            <canvas id="chart-leads-monthly" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="reports-chart-card card h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-1">Lead status breakdown</h6>
                        <p class="text-muted small mb-3">Current pipeline by status</p>
                        <div class="reports-chart-wrap" data-chart-wrap="chart-leads-status">
                            <canvas id="chart-leads-status" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="reports-workflow card border-0 mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <h6 class="reports-workflow-title mb-1">Quick exports</h6>
                    <p class="text-muted small mb-0">Open a report for filters, or download common summaries below.</p>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($quickExports as $export)
                <a href="{{ route($export['route'], $export['params']) }}"
                   class="btn btn-sm {{ ($export['primary'] ?? false) ? 'btn-primary' : 'btn-outline-primary' }}">
                    @if($export['primary'] ?? false)<i class="bi bi-download me-1"></i>@endif
                    {{ $export['label'] }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    @foreach($reportSections as $sectionName => $cards)
    <section class="reports-section mb-4" data-reports-section>
        <h2 class="reports-section-title">{{ $sectionName }}</h2>
        <div class="reports-grid">
            @foreach($cards as $card)
            <a href="{{ route($card['route']) }}"
               class="reports-card"
               data-reports-card
               data-search="{{ strtolower($card['name'] . ' ' . $card['hint'] . ' ' . ($card['keywords'] ?? '') . ' ' . $sectionName) }}">
                <span class="reports-card-icon"><i class="bi {{ $card['icon'] }}"></i></span>
                <span class="reports-card-badge">{{ $card['badge'] }}</span>
                <span class="reports-card-name">{{ $card['name'] }}</span>
                <span class="reports-card-hint">{{ $card['hint'] }}</span>
                <span class="reports-card-arrow"><i class="bi bi-arrow-right"></i></span>
            </a>
            @endforeach
        </div>
    </section>
    @endforeach

    <div id="reportsSearchEmpty" class="reports-empty d-none mb-4">
        <i class="bi bi-search"></i>
        <p class="mb-0">No reports match your search. Try different keywords.</p>
    </div>

    <section class="reports-section mb-4">
        <h2 class="reports-section-title">Live snapshots</h2>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="reports-snapshot card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-semibold">Sales by person</h6>
                            <a href="{{ route('reports.export.sales-by-person', ['format' => 'pdf']) }}" class="btn btn-sm btn-outline-danger" title="Export PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                            <a href="{{ route('reports.export.sales-by-person', ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-secondary" title="Export Excel"><i class="bi bi-download"></i></a>
                        </div>
                        <div class="reports-list">
                            @forelse($salesByPerson ?? [] as $row)
                            <div class="reports-list-row">
                                <span>{{ trim($row->name) ?: 'Unassigned' }}</span>
                                <strong>KES {{ number_format($row->total, 0) }}</strong>
                            </div>
                            @empty
                            <p class="text-muted small mb-0">No closed deals yet</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="reports-snapshot card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-semibold">Tickets by status</h6>
                            <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="reports-list">
                            @forelse($ticketsByStatus ?? [] as $status => $cnt)
                            <div class="reports-list-row">
                                <span>{{ $status }}</span>
                                <strong>{{ $cnt }}</strong>
                            </div>
                            @empty
                            <p class="text-muted small mb-0">No tickets yet</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="reports-snapshot card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-semibold">Tickets by category</h6>
                            <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="reports-list">
                            @forelse($ticketsByCategory ?? [] as $cat => $cnt)
                            <div class="reports-list-row">
                                <span>{{ $cat }}</span>
                                <strong>{{ $cnt }}</strong>
                            </div>
                            @empty
                            <p class="text-muted small mb-0">No categories yet</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="reports-snapshot card h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3">Leads by source</h6>
                        @if(count($leadsBySource ?? []) > 0)
                        <div class="reports-list">
                            @foreach($leadsBySource as $source => $cnt)
                            <div class="reports-list-row">
                                <span>{{ $source }}</span>
                                <strong>{{ $cnt }}</strong>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-muted small mb-0">No lead source data yet</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="reports-snapshot card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-semibold">Pipeline by stage</h6>
                            <a href="{{ route('reports.export.pipeline-by-stage', ['format' => 'pdf']) }}" class="btn btn-sm btn-outline-danger" title="Export PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                            <a href="{{ route('reports.export.pipeline-by-stage', ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-secondary" title="Export Excel"><i class="bi bi-download"></i></a>
                        </div>
                        @if(count($pipelineByStage ?? []) > 0)
                        <div class="reports-list">
                            @foreach($pipelineByStage as $stage => $data)
                            <div class="reports-list-row">
                                <span>{{ $stage }}</span>
                                <span><strong>{{ $data['count'] }}</strong> · KES {{ number_format($data['amount'], 0) }}</span>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-muted small mb-0">No pipeline data yet</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="reports-section mb-4">
        <h2 class="reports-section-title">Overview charts</h2>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="reports-chart-card card h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3">Pipeline by stage</h6>
                        <div class="reports-chart-wrap" data-chart-wrap="chart-pipeline">
                            <canvas id="chart-pipeline" height="220"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="reports-chart-card card h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3">Tickets by status</h6>
                        <div class="reports-chart-wrap" data-chart-wrap="chart-tickets-status">
                            <canvas id="chart-tickets-status" height="220"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="reports-chart-card card h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3">Leads by source</h6>
                        <div class="reports-chart-wrap" data-chart-wrap="chart-leads-source">
                            <canvas id="chart-leads-source" height="220"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="reports-chart-card card h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3">Sales by person (top 10)</h6>
                        <div class="reports-chart-wrap" data-chart-wrap="chart-sales-person">
                            <canvas id="chart-sales-person" height="220"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="reports-export-footer card border-0">
        <div class="card-body p-4">
            <h6 class="text-uppercase small fw-bold text-muted mb-3">Download center</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('reports.export.all-excel') }}" class="btn btn-primary btn-sm"><i class="bi bi-file-earmark-spreadsheet me-1"></i>All reports (Excel)</a>
                <a href="{{ route('reports.export.all-pdf') }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>All reports (PDF)</a>
                <a href="{{ route('reports.export.sla-broken', ['format' => 'pdf']) }}" class="btn btn-outline-secondary btn-sm">Broken SLA (PDF)</a>
                <a href="{{ route('reports.export.ticket-aging', ['format' => 'pdf']) }}" class="btn btn-outline-secondary btn-sm">Ticket aging (PDF)</a>
                <a href="{{ route('reports.export.tickets-by-date', ['date_from' => $monthStart, 'date_to' => $today, 'format' => 'pdf']) }}" class="btn btn-outline-secondary btn-sm">Tickets by date (PDF)</a>
                <a href="{{ route('reports.export.reassignment-audit', ['format' => 'pdf']) }}" class="btn btn-outline-secondary btn-sm">Reassignment audit (PDF)</a>
                <a href="{{ route('reports.export.work-activities', ['date_from' => $monthStart, 'date_to' => $today, 'scope' => 'summary', 'format' => 'pdf']) }}" class="btn btn-outline-secondary btn-sm">Work activities (PDF)</a>
                <a href="{{ route('reports.export.sales-by-person', ['format' => 'pdf']) }}" class="btn btn-outline-secondary btn-sm">Sales by person (PDF)</a>
                <a href="{{ route('reports.export.pipeline-by-stage', ['format' => 'pdf']) }}" class="btn btn-outline-secondary btn-sm">Pipeline (PDF)</a>
            </div>
        </div>
    </div>
</div>

<style>
.reports-hero {
    background: linear-gradient(135deg, var(--agile-primary-dark, #122952) 0%, var(--agile-primary, #0E4385) 55%, #2563eb 100%);
    border-radius: 16px;
    color: #fff;
    padding: 1.5rem 1.75rem;
    position: relative;
    overflow: hidden;
}
.reports-hero::after {
    content: '';
    position: absolute;
    right: -2rem;
    top: -2rem;
    width: 11rem;
    height: 11rem;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    pointer-events: none;
}
.reports-hero-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 12px;
    background: rgba(255,255,255,0.15);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
}
.reports-hero-title { font-size: 1.5rem; font-weight: 700; color: #fff; margin: 0; }
.reports-hero-desc { font-size: 0.92rem; color: rgba(255,255,255,0.88); max-width: 42rem; }
.reports-hero-time { color: rgba(255,255,255,0.75); }
.reports-search-wrap {
    position: relative;
    max-width: 480px;
}
.reports-search-wrap i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    z-index: 2;
}
.reports-search-wrap .form-control {
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    border: none;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}
.reports-kpi {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 100%);
    border: 1px solid rgba(14, 67, 133, 0.12);
    border-radius: 14px;
    padding: 1rem 1.15rem;
    height: 100%;
    box-shadow: 0 2px 8px rgba(14, 67, 133, 0.04);
}
.reports-kpi-label {
    display: block;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.35rem;
}
.reports-kpi-value {
    display: block;
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--agile-primary, #0E4385);
    line-height: 1.2;
    word-break: break-word;
}
.reports-kpi-link {
    display: inline-block;
    margin-top: 0.5rem;
    color: var(--agile-primary, #0E4385);
    text-decoration: none;
    font-weight: 500;
}
.reports-kpi-link:hover { text-decoration: underline; }
.reports-kpi-hint { display: block; margin-top: 0.35rem; }
.reports-kpi-analytics {
    background: linear-gradient(135deg, #f8fbff 0%, #eef4ff 100%);
    border-color: rgba(37, 99, 235, 0.15);
}
.reports-chart-wrap { position: relative; min-height: 180px; }
.reports-chart-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 180px;
    color: #94a3b8;
    font-size: 0.88rem;
    text-align: center;
    padding: 1rem;
}
.reports-workflow {
    background: rgba(14, 67, 133, 0.04);
    border-radius: 14px;
    border: 1px solid rgba(14, 67, 133, 0.1);
}
.reports-workflow-title { font-size: 0.95rem; font-weight: 700; color: var(--agile-text, #1e293b); }
.reports-section-title {
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #64748b;
    margin: 0 0 0.85rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--agile-border, #e2e8f0);
}
.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
}
.reports-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    position: relative;
    padding: 1.25rem 1.35rem;
    background: #fafbfc;
    border: 1px solid var(--agile-border, #e2e8f0);
    border-radius: 14px;
    text-decoration: none;
    color: var(--agile-text, #1e293b);
    transition: border-color 0.2s, background 0.2s, transform 0.2s, box-shadow 0.2s;
    min-height: 100%;
}
.reports-card:hover {
    background: #fff;
    border-color: var(--agile-primary, #0E4385);
    color: var(--agile-primary, #0E4385);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(14, 67, 133, 0.1);
}
.reports-card-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(14, 67, 133, 0.08);
    color: var(--agile-primary, #0E4385);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    margin-bottom: 0.75rem;
    transition: background 0.2s, color 0.2s;
}
.reports-card:hover .reports-card-icon { background: var(--agile-primary, #0E4385); color: #fff; }
.reports-card-badge {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.35rem;
}
.reports-card:hover .reports-card-badge { color: inherit; opacity: 0.8; }
.reports-card-name { font-size: 0.98rem; font-weight: 600; margin-bottom: 0.3rem; padding-right: 1.5rem; }
.reports-card-hint { font-size: 0.82rem; color: #64748b; line-height: 1.45; flex: 1; }
.reports-card:hover .reports-card-hint { color: inherit; opacity: 0.85; }
.reports-card-arrow {
    position: absolute;
    top: 1.1rem;
    right: 1.1rem;
    color: #cbd5e1;
    transition: color 0.2s, transform 0.2s;
}
.reports-card:hover .reports-card-arrow { color: var(--agile-primary, #0E4385); transform: translateX(3px); }
.reports-section.is-hidden { display: none; }
.reports-empty {
    text-align: center;
    padding: 2rem;
    background: #f8fafc;
    border-radius: 14px;
    border: 1px dashed #e2e8f0;
    color: #64748b;
}
.reports-empty i { font-size: 1.75rem; display: block; margin-bottom: 0.5rem; opacity: 0.5; }
.reports-snapshot,
.reports-chart-card {
    border-radius: 14px;
    border: 1px solid var(--agile-border, #e2e8f0);
    box-shadow: 0 1px 3px rgba(15,23,42,0.04);
}
.reports-list-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.55rem 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.88rem;
}
.reports-list-row:last-child { border-bottom: none; }
.reports-export-footer {
    background: rgba(14, 67, 133, 0.04);
    border-radius: 14px;
    border: 1px solid rgba(14, 67, 133, 0.1);
}
@media (max-width: 575.98px) {
    .reports-grid { grid-template-columns: 1fr; }
    .reports-kpi-value { font-size: 1.15rem; }
}
</style>

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var search = document.getElementById('reportsSearch');
    var cards = document.querySelectorAll('[data-reports-card]');
    var sections = document.querySelectorAll('[data-reports-section]');
    var empty = document.getElementById('reportsSearchEmpty');

    if (search) {
        search.addEventListener('input', function() {
            var q = (this.value || '').toLowerCase().trim();
            var visible = 0;
            cards.forEach(function(card) {
                var text = card.getAttribute('data-search') || '';
                var match = q.length < 2 || text.indexOf(q) >= 0;
                card.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            sections.forEach(function(section) {
                var any = false;
                section.querySelectorAll('[data-reports-card]').forEach(function(card) {
                    if (card.style.display !== 'none') any = true;
                });
                section.classList.toggle('is-hidden', q.length >= 2 && !any);
            });
            if (empty) empty.classList.toggle('d-none', q.length < 2 || visible > 0);
        });
    }

    var colors = ['#0E4385','#2563eb','#3b82f6','#60a5fa','#93c5fd','#0ea5e9','#06b6d4','#14b8a6'];
    var pipelineData = @json($pipelineByStage ?? []);
    var ticketsStatusData = @json($ticketsByStatus ?? []);
    var leadsSourceData = @json($leadsBySource ?? []);
    var salesPersonData = @json($salesChart ?? []);
    var ticketsDailyTrend = @json($ticketsDailyTrend);
    var ticketsMonthlyTrend = @json($ticketsMonthlyTrend);
    var topCategories30d = @json($topCategories30d);
    var leadsMonthlyTrend = @json($leadsMonthlyTrend);
    var leadStatusChart = @json($leadStatusChart);
    var callsByStatus = @json($callsSummary['by_status'] ?? []);

    function showChartEmpty(canvasId, message) {
        var el = document.getElementById(canvasId);
        if (!el) return;
        var wrap = el.closest('.reports-chart-wrap');
        if (!wrap) return;
        el.style.display = 'none';
        var empty = document.createElement('div');
        empty.className = 'reports-chart-empty';
        empty.textContent = message || 'No data available yet';
        wrap.appendChild(empty);
    }

    function hasData(values) {
        if (!values || values.length === 0) return false;
        return values.some(function(v) { return Number(v) > 0; });
    }

    function makeBar(el, labels, values, isHorizontal, label) {
        if (!el) return;
        if (!hasData(values)) {
            showChartEmpty(el.id, 'No data available yet');
            return;
        }
        new Chart(el, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{ label: label || 'Count', data: values, backgroundColor: colors.slice(0, labels.length), borderWidth: 1, borderRadius: 4 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: isHorizontal ? 'y' : 'x',
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    function makeDoughnut(el, labels, values) {
        if (!el) return;
        if (!hasData(values)) {
            showChartEmpty(el.id, 'No data available yet');
            return;
        }
        new Chart(el, {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 1 }] },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } } }
        });
    }

    function makeLineTrend(el, labels, created, closed) {
        if (!el) return;
        if (!hasData(created) && !hasData(closed)) {
            showChartEmpty(el.id, 'No ticket activity in the last 30 days');
            return;
        }
        new Chart(el, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Created',
                        data: created,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.12)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2,
                        pointHoverRadius: 4
                    },
                    {
                        label: 'Closed',
                        data: closed,
                        borderColor: '#14b8a6',
                        backgroundColor: 'rgba(20, 184, 166, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2,
                        pointHoverRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', align: 'end' } },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    var pipelineLabels = Object.keys(pipelineData);
    var pipelineCounts = pipelineLabels.map(function(k) { return pipelineData[k].count || 0; });
    var ticketLabels = Object.keys(ticketsStatusData);
    var ticketCounts = ticketLabels.map(function(k) { return ticketsStatusData[k] || 0; });
    var leadLabels = Object.keys(leadsSourceData);
    var leadCounts = leadLabels.map(function(k) { return leadsSourceData[k] || 0; });
    var salesLabels = salesPersonData.map(function(r) { return r.name; });
    var salesValues = salesPersonData.map(function(r) { return r.total; });

    var dailyLabels = ticketsDailyTrend.map(function(r) { return r.label; });
    var dailyCreated = ticketsDailyTrend.map(function(r) { return r.created; });
    var dailyClosed = ticketsDailyTrend.map(function(r) { return r.closed; });

    var monthlyTicketLabels = ticketsMonthlyTrend.map(function(r) { return r.label; });
    var monthlyTicketValues = ticketsMonthlyTrend.map(function(r) { return r.created; });

    var categoryLabels = Object.keys(topCategories30d);
    var categoryValues = categoryLabels.map(function(k) { return topCategories30d[k] || 0; });

    var leadsMonthlyLabels = leadsMonthlyTrend.map(function(r) { return r.label; });
    var leadsMonthlyValues = leadsMonthlyTrend.map(function(r) { return r.created; });

    var leadStatusLabels = leadStatusChart.map(function(r) { return r.status; });
    var leadStatusValues = leadStatusChart.map(function(r) { return r.count; });

    var callStatusLabels = Object.keys(callsByStatus);
    var callStatusValues = callStatusLabels.map(function(k) { return callsByStatus[k] || 0; });

    makeLineTrend(document.getElementById('chart-tickets-daily'), dailyLabels, dailyCreated, dailyClosed);
    makeBar(document.getElementById('chart-tickets-monthly'), monthlyTicketLabels, monthlyTicketValues, false, 'Tickets');
    makeBar(document.getElementById('chart-categories-30d'), categoryLabels, categoryValues, true, 'Tickets');
    makeDoughnut(document.getElementById('chart-calls-status'), callStatusLabels, callStatusValues);
    makeBar(document.getElementById('chart-leads-monthly'), leadsMonthlyLabels, leadsMonthlyValues, false, 'Leads');
    makeDoughnut(document.getElementById('chart-leads-status'), leadStatusLabels, leadStatusValues);
    makeBar(document.getElementById('chart-pipeline'), pipelineLabels, pipelineCounts, false);
    makeDoughnut(document.getElementById('chart-tickets-status'), ticketLabels, ticketCounts);
    makeDoughnut(document.getElementById('chart-leads-source'), leadLabels, leadCounts);
    makeBar(document.getElementById('chart-sales-person'), salesLabels, salesValues, true);
});
</script>
@endpush
@endsection
