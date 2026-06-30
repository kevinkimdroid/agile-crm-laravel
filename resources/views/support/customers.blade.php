@extends('layouts.app')

@section('title', ($listRoute ?? 'support.customers') === 'contacts.index' ? 'Prospects' : 'Clients')

@section('content')
<nav class="breadcrumb-nav mb-3">
    @if(($listRoute ?? 'support.customers') === 'contacts.index')
    <a href="{{ route('dashboard') }}" class="text-muted small text-decoration-none">Home</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Prospects</span>
    @else
    <a href="{{ route('support') }}" class="text-muted small text-decoration-none">Support</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Clients</span>
    @endif
</nav>
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h1 class="page-title">{{ ($listRoute ?? 'support.customers') === 'contacts.index' ? 'Prospects' : 'Clients' }}</h1>
        <p class="page-subtitle">{{ ($listRoute ?? 'support.customers') === 'contacts.index' ? 'Manage sales prospects before they become clients.' : 'Manage your clients and policy assignments.' }}</p>
    </div>
    <a href="{{ route('contacts.create') }}" class="btn btn-primary-custom">
        <i class="bi bi-plus-lg me-2"></i>Add {{ ($listRoute ?? 'support.customers') === 'contacts.index' ? 'Prospect' : 'Client' }}
    </a>
</div>

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <div>{{ session('error') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if (user_is_limited_to_assigned_clients() && ($total ?? 0) === 0 && !($clientsError ?? null))
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="bi bi-info-circle-fill me-2"></i>
    Your profile is limited to assigned clients only. An administrator must assign policy numbers to you under
    <strong>Settings → Client Access</strong>.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if ($clientsError ?? null)
@php
    $clientsErrorRaw = (string) ($clientsError ?? '');
    $crmFallbackNote = ' Showing CRM contacts below (if any).';
    $clientsErrorTechnical = str_ends_with($clientsErrorRaw, $crmFallbackNote)
        ? substr($clientsErrorRaw, 0, -strlen($crmFallbackNote))
        : $clientsErrorRaw;
    $isErpQueryBindError = str_contains($clientsErrorTechnical, 'DPY-4008')
        || str_contains($clientsErrorTechnical, 'no bind placeholder')
        || str_contains($clientsErrorTechnical, 'ORA-00932')
        || preg_match('/\bDPY-\d+/i', $clientsErrorTechnical) === 1;
    $isLikelyNetworkBlock = str_contains($clientsErrorTechnical, 'Connection refused')
        || str_contains($clientsErrorTechnical, 'timed out')
        || str_contains($clientsErrorTechnical, 'Could not resolve')
        || str_contains($clientsErrorTechnical, 'cURL error')
        || preg_match('/\bORA-12[56]\d{3}\b/', $clientsErrorTechnical) === 1;
@endphp
<div class="alert alert-warning alert-dismissible fade show d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <div class="flex-grow-1">
        @if($isErpQueryBindError)
        <strong>ERP client search failed</strong><br>
        <span class="small">The ERP service returned a database error while loading clients (usually a temporary API bug or outdated <code>erp-clients-api</code>). Any CRM contacts in the list below are shown as a fallback—not a full ERP result. Ask IT to restart or redeploy <code>erp-clients-api</code> after updating it.</span>
        <details class="small mt-2 mb-0"><summary class="text-muted" style="cursor: pointer;">Technical detail (for IT)</summary><code class="d-block mt-1 user-select-all text-break">{{ e($clientsErrorTechnical) }}</code></details>
        @else
        <strong>ERP / Oracle connection issue</strong><br>
        <span class="small">{{ $clientsErrorRaw }}</span>
        @if(in_array($clientsSource ?? 'crm', ['erp_http', 'erp_sync']) && $isLikelyNetworkBlock)
        <p class="small mb-0 mt-2 text-muted">
            <strong>If this server uses Apache on CentOS/RHEL with SELinux:</strong> allow outbound connections from HTTPd, then restart Apache:
            <code>sudo setsebool -P httpd_can_network_connect 1</code> and <code>sudo systemctl restart httpd</code>.
            Otherwise confirm the ERP API process is running and reachable from this app.
        </p>
        @elseif(in_array($clientsSource ?? 'crm', ['erp_http', 'erp_sync']))
        <p class="small mb-0 mt-2 text-muted">
            Ensure the <code>erp-clients-api</code> service is running and <code>ERP_CLIENTS_HTTP_URL</code> in <code>.env</code> points to it. Check the technical message above or your application logs.
        </p>
        @endif
        @endif
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(in_array($clientsSource ?? 'crm', ['erp_sync', 'erp_http']))
@php
    $erpClientSvc = app(\App\Services\ErpClientService::class);
    $allowedClientSegments = $allowedClientSegments ?? allowed_client_segments();
    $segmentLabels = config('clients_ui.tab_labels', []);
    $showAllClientsPill = count($allowedClientSegments) > 1;
@endphp
@if(!empty($allowedClientSegments))
<div class="clients-system-pills mb-3">
    @if($showAllClientsPill)
    <a href="{{ route($listRoute ?? 'support.customers', array_merge(collect(request()->query())->except('system')->all(), ['all' => 1])) }}" class="clients-system-pill {{ !($system ?? '') && request()->boolean('all') ? 'active' : '' }}">All</a>
    @endif
    @if(in_array('group', $allowedClientSegments, true))
    <a href="{{ route($listRoute ?? 'support.customers', array_merge(collect(request()->query())->except('all')->all(), ['system' => 'group'])) }}" class="clients-system-pill clients-system-group {{ ($system ?? '') === 'group' ? 'active' : '' }}"><i class="bi bi-people-fill me-1"></i>{{ $segmentLabels['group'] ?? config('clients_ui.tab_labels.group') }}</a>
    @endif
    @if(in_array('individual', $allowedClientSegments, true))
    <a href="{{ route($listRoute ?? 'support.customers', array_merge(collect(request()->query())->except('all')->all(), ['system' => 'individual'])) }}" class="clients-system-pill clients-system-individual {{ ($system ?? '') === 'individual' ? 'active' : '' }}"><i class="bi bi-person-fill me-1"></i>{{ $segmentLabels['individual'] ?? config('clients_ui.tab_labels.individual') }}</a>
    @endif
    @if(in_array('mortgage', $allowedClientSegments, true))
    <a href="{{ route($listRoute ?? 'support.customers', array_merge(collect(request()->query())->except('all')->all(), ['system' => 'mortgage'])) }}" class="clients-system-pill clients-system-mortgage {{ ($system ?? '') === 'mortgage' ? 'active' : '' }}"><i class="bi bi-house-fill me-1"></i>{{ $segmentLabels['mortgage'] ?? config('clients_ui.tab_labels.mortgage') }}</a>
    @endif
    @if(in_array('group_pension', $allowedClientSegments, true))
    <a href="{{ route($listRoute ?? 'support.customers', array_merge(collect(request()->query())->except('all')->all(), ['system' => 'group_pension'])) }}" class="clients-system-pill clients-system-group-pension {{ ($system ?? '') === 'group_pension' ? 'active' : '' }}"><i class="bi bi-piggy-bank-fill me-1"></i>{{ $segmentLabels['group_pension'] ?? config('clients_ui.tab_labels.group_pension') }}</a>
    @endif
</div>
@endif
@endif

@php
    $isErpList = in_array($clientsSource ?? 'crm', ['erp_sync', 'erp_http']);
    $columnFilters = $columnFilters ?? [
        'policy' => request('f_policy', ''),
        'prepared' => request('f_prepared', ''),
        'intermediary' => request('f_intermediary', ''),
        'name' => request('f_name', request('search', '')),
        'product' => request('f_product', ''),
        'status' => request('f_status', ''),
    ];
    $clientsStatTotal = ($clientsGrandTotal !== null && ! ($system ?? '') && ($clientsSource ?? '') === 'erp_http')
        ? (int) $clientsGrandTotal
        : (int) ($clientsGrandTotal ?? $total ?? 0);
    $tableColspan = $isErpList ? 8 : (in_array($clientsSource ?? 'crm', ['erp']) ? 6 : 5);
@endphp

{{-- Clients Table --}}
<div class="clients-table-card">
    <div class="clients-table-toolbar">
        <span class="clients-table-filter-status" id="clientsFilterStatus">Filter by column — type at least 2 characters</span>
        <div class="clients-table-toolbar-meta">
            <span class="clients-toolbar-stat">
                <span class="clients-toolbar-stat-value" id="clientsTotalValue">{{ number_format($clientsStatTotal) }}</span>
                <span class="clients-toolbar-stat-label">
                    @if(($system ?? '') === 'group')
                        {{ config('clients_ui.tab_labels.group') }}
                    @elseif(($system ?? '') === 'individual')
                        {{ config('clients_ui.tab_labels.individual') }}
                    @elseif(($system ?? '') === 'mortgage')
                        {{ config('clients_ui.tab_labels.mortgage') }}
                    @elseif(($system ?? '') === 'group_pension')
                        {{ config('clients_ui.tab_labels.group_pension') }}
                    @else
                        {{ ($listRoute ?? 'support.customers') === 'contacts.index' ? 'Prospects' : 'Clients' }}
                    @endif
                </span>
            </span>
        </div>
    </div>
    <div class="clients-table-wrapper">
        <table class="clients-table">
            <thead>
                @if($isErpList)
                <tr class="clients-table-head-labels">
                    <th>Policy Number</th>
                    <th>Who Prepared Policy</th>
                    <th>Intermediary (Agent)</th>
                    <th>Life Assured (Client)</th>
                    <th>Product</th>
                    <th>System</th>
                    <th>Policy Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                <tr class="clients-table-head-filters">
                    <th><input type="search" class="clients-col-filter" data-col-filter="policy" placeholder="Policy…" value="{{ $columnFilters['policy'] ?? '' }}" autocomplete="off" spellcheck="false"></th>
                    <th><input type="search" class="clients-col-filter" data-col-filter="prepared" placeholder="Prepared by…" value="{{ $columnFilters['prepared'] ?? '' }}" autocomplete="off" spellcheck="false"></th>
                    <th><input type="search" class="clients-col-filter" data-col-filter="intermediary" placeholder="Agent…" value="{{ $columnFilters['intermediary'] ?? '' }}" autocomplete="off" spellcheck="false"></th>
                    <th><input type="search" class="clients-col-filter" data-col-filter="name" placeholder="Name…" value="{{ $columnFilters['name'] ?? '' }}" autocomplete="off" spellcheck="false"></th>
                    <th><input type="search" class="clients-col-filter" data-col-filter="product" placeholder="Product…" value="{{ $columnFilters['product'] ?? '' }}" autocomplete="off" spellcheck="false"></th>
                    <th><span class="clients-col-filter-spacer" aria-hidden="true"></span></th>
                    <th><input type="search" class="clients-col-filter" data-col-filter="status" placeholder="Status…" value="{{ $columnFilters['status'] ?? '' }}" autocomplete="off" spellcheck="false"></th>
                    <th class="text-end">
                        <button type="button" class="clients-clear-filters" id="clearColumnFilters" title="Clear all filters">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </th>
                </tr>
                @else
                <tr class="clients-table-head-labels">
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    @if(in_array($clientsSource ?? 'crm', ['erp']))
                    <th>Product</th>
                    @endif
                    <th>Assigned To</th>
                    <th class="text-end">Actions</th>
                </tr>
                <tr class="clients-table-head-filters">
                    <th><input type="search" class="clients-col-filter" data-col-filter="name" placeholder="Name…" value="{{ $columnFilters['name'] ?? '' }}" autocomplete="off"></th>
                    <th><input type="search" class="clients-col-filter" data-col-filter="email" placeholder="Email…" value="{{ request('f_email', '') }}" autocomplete="off"></th>
                    <th><input type="search" class="clients-col-filter" data-col-filter="mobile" placeholder="Mobile…" value="{{ request('f_mobile', '') }}" autocomplete="off"></th>
                    @if(in_array($clientsSource ?? 'crm', ['erp']))
                    <th><input type="search" class="clients-col-filter" data-col-filter="product" placeholder="Product…" value="{{ $columnFilters['product'] ?? '' }}" autocomplete="off"></th>
                    @endif
                    <th><span class="clients-col-filter-spacer" aria-hidden="true"></span></th>
                    <th class="text-end">
                        <button type="button" class="clients-clear-filters" id="clearColumnFilters" title="Clear all filters">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </th>
                </tr>
                @endif
            </thead>
            <tbody id="clientsTableBody">
                @if($clientsLazyLoad ?? false)
                <tr id="clientsLoadingRow">
                    <td colspan="{{ $tableColspan }}" class="text-center py-5">
                        <div class="clients-empty">
                            <div class="spinner-border text-primary mb-2" role="status"></div>
                            <p class="text-muted mb-0">Loading clients...</p>
                        </div>
                    </td>
                </tr>
                @else
                @forelse ($customers as $customer)
                    @php
                        $rowPolicy = $customer->policy_no ?? $customer->policy_number ?? $customer->ipol_policy_no ?? $customer->pol_policy_no ?? (is_array($customer) ? ($customer['policy_no'] ?? $customer['policy_number'] ?? $customer['ipol_policy_no'] ?? $customer['pol_policy_no'] ?? '') : '');
                        $rowIdentifier = trim((string) $rowPolicy);
                    @endphp
                    <tr @if($rowIdentifier && ($customer->_erp_source ?? false) && in_array($clientsSource ?? 'crm', ['erp_sync', 'erp_http'])) class="clients-row-open" data-client-open="{{ route('support.clients.show', array_filter(['policy' => $rowIdentifier, 'system' => $system ?? null])) }}" @endif>
                        @if(($customer->_erp_source ?? false) && in_array($clientsSource ?? 'crm', ['erp_sync', 'erp_http']))
                        <td>
                            <a href="{{ route('support.clients.show', array_filter(['policy' => $rowIdentifier, 'system' => $system ?? null])) }}" class="clients-policy-link">
                                {{ $rowPolicy ?: '—' }}
                            </a>
                        </td>
                        <td>{{ $customer->pol_prepared_by ?? '—' }}</td>
                        <td>{{ Str::limit($customer->intermediary ?? '—', 25) }}</td>
                        <td>
                            <a href="{{ route('support.clients.show', array_filter(['policy' => $rowIdentifier, 'system' => $system ?? null])) }}" class="clients-name-link text-decoration-none">
                                <span class="clients-name">{{ $customer->life_assur ?? $customer->client_name ?? '—' }}</span>
                            </a>
                        </td>
                        <td class="clients-product">{{ Str::limit($customer->product ?? '—', 40) }}</td>
                        <td>
                            @php
                                $ls = $customer->life_system ?? $erpClientSvc->getLifeSystemFromProduct($customer->product ?? null);
                                $lsLabel = $erpClientSvc->getClientSystemLabel($ls);
                            @endphp
                            <span class="clients-system-badge clients-system-{{ $ls }}">{{ $lsLabel }}</span>
                        </td>
                        <td>
                            @php $st = $customer->status ?? ''; @endphp
                            <span class="clients-status-badge clients-status-{{ $st === 'A' ? 'active' : ($st === 'FL' ? 'lapsed' : 'other') }}">
                                {{ $st ?: '—' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="clients-actions">
                                @if($rowIdentifier)
                                <a href="{{ route('support.clients.create-ticket', ['policy' => $rowIdentifier]) }}" class="btn btn-sm btn-success" title="Create ticket">
                                    <i class="bi bi-ticket-perforated"></i> Ticket
                                </a>
                                <a href="{{ route('support.clients.show', array_filter(['policy' => $rowIdentifier, 'system' => $system ?? null])) }}" class="btn btn-sm clients-btn-view" title="View full details">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="{{ route('support.serve-client', ['search' => $rowIdentifier]) }}" class="btn btn-sm clients-btn-serve" title="Serve client">
                                    <i class="bi bi-person-plus"></i> Serve
                                </a>
                                @else
                                <span class="text-muted small">—</span>
                                @endif
                            </div>
                        </td>
                        @else
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="clients-avatar">{{ strtoupper(substr($customer->firstname ?? '?', 0, 1)) }}{{ strtoupper(substr($customer->lastname ?? '', 0, 1)) }}</div>
                                @if(($customer->_erp_source ?? false))
                                <span class="clients-name">{{ trim(($customer->firstname ?? '') . ' ' . ($customer->lastname ?? '')) ?: ($rowPolicy ?: '—') }}</span>
                                @else
                                <a href="{{ route('contacts.show', ['contact' => $customer->contactid, 'tab' => 'summary']) }}" class="clients-name-link">{{ trim(($customer->firstname ?? '') . ' ' . ($customer->lastname ?? '')) ?: '—' }}</a>
                                @endif
                            </div>
                        </td>
                        <td>
                            @php $displayEmail = personal_email_only($customer->email ?? null); @endphp
                            @if($displayEmail)
                            <a href="mailto:{{ $displayEmail }}" class="text-decoration-none">{{ Str::limit($displayEmail, 35) }}</a>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($customer->mobile ?? $customer->phone)
                            <a href="tel:{{ tel_href($customer->mobile ?? $customer->phone) }}" class="text-decoration-none">{{ $customer->mobile ?? $customer->phone }}</a>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        @if(($clientsSource ?? 'crm') === 'erp')
                        <td><span class="badge bg-secondary">{{ Str::limit($customer->product ?? '—', 35) }}</span></td>
                        @endif
                        <td><span class="text-muted small">{{ trim(($customer->owner_first ?? '') . ' ' . ($customer->owner_last ?? '')) ?: ($customer->owner_username ?? '—') ?: '—' }}</span></td>
                        <td class="text-end">
                            @if(($customer->_erp_source ?? false))
                            <a href="{{ route('support.clients.show', array_filter(['policy' => $rowPolicy, 'system' => $system ?? null])) }}" class="btn btn-sm clients-btn-view" title="View full details"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('support.serve-client', ['search' => $rowPolicy]) }}" class="btn btn-sm clients-btn-serve" title="Serve client"><i class="bi bi-person-plus"></i></a>
                            @else
                            <a href="{{ route('contacts.show', ['contact' => $customer->contactid, 'tab' => 'summary']) }}" class="btn btn-sm clients-btn-view"><i class="bi bi-eye"></i></a>
                            @endif
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $tableColspan }}" class="text-center py-5">
                            <div class="clients-empty">
                                <div class="clients-empty-icon"><i class="bi bi-people"></i></div>
                                <h6 class="mt-3 mb-2">No {{ ($listRoute ?? 'support.customers') === 'contacts.index' ? 'prospects' : 'clients' }} found</h6>
                                <p class="text-muted mb-3">
                                    @if($search ?? '')
                                    Try a different search or <a href="{{ route($listRoute ?? 'support.customers') }}">view all</a>.
                                    @if(($system ?? '') === 'group' && in_array($clientsSource ?? 'crm', ['erp_http', 'erp_sync']))
                                    <br><a href="{{ route('support.clients.debug-api', ['policy' => $search, 'search' => $search, 'system' => 'group', 'debug' => '1']) }}" target="_blank" class="small">Debug API response</a>
                                    @endif
                                    @else
                                    Get started by adding your first {{ ($listRoute ?? 'support.customers') === 'contacts.index' ? 'prospect' : 'client' }}.
                                    @if(in_array($clientsSource ?? 'crm', ['erp_http', 'erp_sync']) && in_array(($system ?? ''), ['group', 'mortgage', 'group_pension'], true))
                                    <br><span class="small text-muted">ERP list empty: confirm <code>erp-clients-api</code> is running and <code>ERP_CLIENTS_…_VIEW</code> matches Oracle.</span>
                                    <br><a href="{{ route('support.clients.debug-api', array_filter(['system' => $system ?? null])) }}" target="_blank" rel="noopener" class="small">Debug ERP API response</a>
                                    @endif
                                    @endif
                                </p>
                                @if(!($search ?? ''))
                                <a href="{{ route('contacts.create') }}" class="btn btn-primary-custom"><i class="bi bi-plus-lg me-1"></i>Add {{ ($listRoute ?? 'support.customers') === 'contacts.index' ? 'Prospect' : 'Client' }}</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                @endif
            </tbody>
        </table>
    </div>
    @if ($clientsLazyLoad ?? false)
    <div class="clients-table-footer" id="clientsTableFooter" style="display:none">
        <span class="clients-pagination-info" id="clientsPaginationInfo">—</span>
        <nav id="clientsPaginationNav" aria-label="Clients pagination"></nav>
    </div>
    @elseif ($isErpList)
    <div class="clients-table-footer" id="clientsTableFooter" style="{{ ($customers->hasPages() || ($search ?? '')) ? '' : 'display:none' }}">
        <span class="clients-pagination-info" id="clientsPaginationInfo">
            @if ($customers->total() > 0)
                Showing {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }} of {{ number_format($customers->total()) }}
            @else
                —
            @endif
        </span>
        <nav id="clientsPaginationNav" aria-label="Clients pagination">
            @if ($customers->hasPages())
                {{ $customers->withQueryString()->links('pagination::bootstrap-5') }}
            @endif
        </nav>
    </div>
    @elseif ($customers->hasPages())
    <div class="clients-table-footer">
        <span class="clients-pagination-info">Showing {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }} of {{ number_format($customers->total()) }}</span>
        {{ $customers->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

<style>
/* Clients page - modern, fast, presentable */
.clients-table-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.85rem 1.25rem;
    background: #fff;
    border-bottom: 1px solid var(--agile-border);
}
.clients-table-filter-status {
    font-size: 0.82rem;
    color: var(--agile-text-muted);
}
.clients-table-toolbar-meta { flex-shrink: 0; }
.clients-toolbar-stat {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-end;
    padding: 0.45rem 0.85rem;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--agile-primary) 0%, var(--agile-primary-dark) 100%);
    color: #fff;
    min-width: 7rem;
    text-align: right;
    box-shadow: 0 4px 14px rgba(26, 70, 138, 0.18);
}
.clients-toolbar-stat-value { font-size: 1.25rem; font-weight: 700; line-height: 1.1; }
.clients-toolbar-stat-label { font-size: 0.68rem; opacity: 0.92; text-transform: uppercase; letter-spacing: 0.04em; }

.clients-stat-card {
    background: linear-gradient(135deg, var(--agile-primary) 0%, var(--agile-primary-dark) 100%);
    color: #fff; padding: 1rem 1.5rem; border-radius: 14px; text-align: center; box-shadow: 0 4px 14px rgba(26, 70, 138, 0.25);
}
.clients-stat-value { display: block; font-size: 1.75rem; font-weight: 700; }
.clients-stat-label { font-size: 0.75rem; opacity: 0.9; }

.clients-table-card {
    position: relative;
    background: #fff; border-radius: 16px; box-shadow: 0 2px 16px rgba(0,0,0,0.06); overflow: hidden; border: 1px solid var(--agile-border);
}
.clients-table-wrapper { overflow-x: auto; }
.clients-table {
    width: 100%; border-collapse: collapse; font-size: 0.9rem;
}
.clients-table thead { background: linear-gradient(180deg, #1e3a5f 0%, #1A468A 100%); color: #fff; }
.clients-table-head-labels th {
    padding: 0.85rem 0.75rem 0.45rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: none;
    white-space: nowrap;
}
.clients-table-head-filters th {
    padding: 0 0.75rem 0.85rem;
    vertical-align: top;
    border-bottom: 2px solid rgba(255,255,255,0.12);
    background: linear-gradient(180deg, #1A468A 0%, #163a72 100%);
}
.clients-col-filter {
    width: 100%;
    min-width: 4.5rem;
    max-width: 100%;
    border: 1px solid rgba(255,255,255,0.22);
    background: rgba(255,255,255,0.96);
    border-radius: 8px;
    padding: 0.38rem 0.55rem;
    font-size: 0.78rem;
    font-weight: 400;
    text-transform: none;
    letter-spacing: normal;
    color: #1e293b;
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}
.clients-col-filter::placeholder { color: #94a3b8; font-size: 0.76rem; }
.clients-col-filter:focus {
    outline: none;
    border-color: #fff;
    background: #fff;
    box-shadow: 0 0 0 2px rgba(147, 197, 253, 0.45);
}
.clients-col-filter-active {
    border-color: #bfdbfe;
    background: #fff;
}
.clients-col-filter-spacer { display: block; height: 2rem; }
.clients-clear-filters {
    border: 1px solid rgba(255,255,255,0.25);
    background: rgba(255,255,255,0.12);
    color: #fff;
    width: 2rem;
    height: 2rem;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: background 0.15s;
}
.clients-clear-filters:hover { background: rgba(255,255,255,0.22); color: #fff; }
.clients-table th {
    padding: 1rem 1.25rem; text-align: left; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;
}
.clients-table tbody tr {
    border-bottom: 1px solid var(--agile-border); transition: background 0.15s;
}
.clients-table tbody tr:hover { background: var(--agile-primary-muted); }
.clients-table tbody tr:last-child { border-bottom: none; }
.clients-table td { padding: 1rem 1.25rem; vertical-align: middle; }

.clients-policy-link { font-weight: 600; color: var(--agile-primary); text-decoration: none; }
.clients-policy-link:hover { color: var(--agile-primary-dark); text-decoration: underline; }
.clients-name { font-weight: 500; color: var(--agile-text); }
.clients-product { color: var(--agile-text-muted); font-size: 0.85rem; }
.clients-kra { font-family: ui-monospace, monospace; font-size: 0.85rem; }

.clients-status-badge {
    display: inline-block; padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600;
}
.clients-status-active { background: #dcfce7; color: #166534; }
.clients-status-lapsed { background: #fee2e2; color: #991b1b; }
.clients-status-other { background: #f1f5f9; color: #475569; }

.clients-system-pills { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.clients-system-pill {
    padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; text-decoration: none; color: var(--agile-text-muted);
    border: 1px solid var(--agile-border); background: #fff; transition: all 0.15s;
}
.clients-system-pill:hover { border-color: var(--agile-primary); color: var(--agile-primary); background: var(--agile-primary-muted); }
.clients-system-pill.active { background: var(--agile-primary); border-color: var(--agile-primary); color: #fff; }
.clients-system-group.active { background: #0d9488; border-color: #0d9488; }
.clients-system-individual.active { background: #6366f1; border-color: #6366f1; }
.clients-system-mortgage.active { background: #c2410c; border-color: #c2410c; }
.clients-system-group-pension.active { background: #7c3aed; border-color: #7c3aed; }
.clients-system-badge {
    display: inline-block; padding: 0.2rem 0.55rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600;
}
.clients-system-badge.clients-system-group { background: #ccfbf1; color: #0f766e; }
.clients-system-badge.clients-system-individual { background: #e0e7ff; color: #4338ca; }
.clients-system-badge.clients-system-mortgage { background: #ffedd5; color: #9a3412; }
.clients-system-badge.clients-system-group_pension { background: #ede9fe; color: #5b21b6; }

.clients-actions { display: flex; gap: 0.35rem; justify-content: flex-end; flex-wrap: wrap; }
.clients-btn-view { background: var(--agile-primary-muted); color: var(--agile-primary) !important; border: none; padding: 0.35rem 0.65rem; border-radius: 8px; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; }
.clients-btn-view:hover { background: var(--agile-primary-light); color: var(--agile-primary-dark) !important; }
.clients-btn-serve { background: var(--agile-primary); color: #fff !important; border: none; padding: 0.35rem 0.65rem; border-radius: 8px; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; }
.clients-btn-serve:hover { background: var(--agile-primary-dark); color: #fff !important; }

.clients-avatar { width: 36px; height: 36px; border-radius: 10px; background: var(--agile-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0; }
.clients-name-link { color: var(--agile-text); text-decoration: none; font-weight: 500; }
.clients-name-link:hover { color: var(--agile-primary); }
.clients-row-open { cursor: pointer; }
.clients-row-open:hover { background: rgba(26, 70, 138, 0.04); }

.clients-table-footer {
    padding: 1rem 1.25rem; background: #f8fafc; border-top: 1px solid var(--agile-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;
}
.clients-pagination-info { font-size: 0.85rem; color: var(--agile-text-muted); }

.clients-empty { padding: 2rem; }
.clients-empty-icon { width: 72px; height: 72px; margin: 0 auto; background: var(--agile-primary-muted); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; color: var(--agile-primary); }

/* Modal */
.clients-modal-content { border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.clients-modal-header { background: linear-gradient(135deg, var(--agile-primary) 0%, var(--agile-primary-dark) 100%); color: #fff; padding: 1.25rem 1.5rem; border: none; }
.clients-modal-body { padding: 1.5rem; }
.clients-detail-row { display: flex; flex-direction: column; gap: 0.25rem; }
.clients-detail-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--agile-text-muted); font-weight: 600; }
.clients-detail-value { font-size: 0.95rem; color: var(--agile-text); font-weight: 500; }
.clients-search-overlay { position: absolute; inset: 0; background: rgba(255,255,255,0.85); display: flex; align-items: center; justify-content: center; z-index: 10; border-radius: inherit; }
.clients-search-overlay-inner { text-align: center; color: var(--agile-text-muted); font-size: 0.9rem; }
.clients-table-card.is-filter-loading tbody { opacity: 0.55; transition: opacity 0.15s; }
.clients-ajax-page { padding: 0.35rem 0.75rem; border-radius: 6px; text-decoration: none; color: var(--agile-primary); font-weight: 500; }
.clients-ajax-page:hover { background: var(--agile-primary-muted); color: var(--agile-primary-dark); }
</style>

<script>
(function() {
    var apiUrl = @json(route('api.support.clients'));
    var listUrl = @json(route($listRoute ?? 'support.customers'));
    var serveUrl = @json(route('support.serve-client'));
    var showUrl = @json(route('support.clients.show'));
    var ticketUrl = @json(route('support.clients.create-ticket'));
    var isErpList = @json($isErpList);
    var tableColspan = @json($tableColspan);
    var system = @json($system ?? '');
    var showAllClients = @json(request()->boolean('all'));
    var initialPage = {{ (int) ($page ?? 1) }};
    var lazyLoad = @json($clientsLazyLoad ?? false);
    var systemLabels = @json(config('clients_ui.tab_labels'));
    var grandTotalOnAll = @json(($clientsGrandTotal !== null && ! ($system ?? '') && ($clientsSource ?? '') === 'erp_http'));
    var filterKeys = isErpList
        ? ['policy', 'prepared', 'intermediary', 'name', 'product', 'status']
        : ['name', 'email', 'mobile', 'product'];

    var statusEl = document.getElementById('clientsFilterStatus');
    var tableCard = document.querySelector('.clients-table-card');
    var tbody = document.getElementById('clientsTableBody');
    var footer = document.getElementById('clientsTableFooter');
    var paginationInfo = document.getElementById('clientsPaginationInfo');
    var paginationNav = document.getElementById('clientsPaginationNav');
    var totalEl = document.getElementById('clientsTotalValue');
    var clearAllBtn = document.getElementById('clearColumnFilters');
    var filterInputs = Array.prototype.slice.call(document.querySelectorAll('[data-col-filter]'));

    var currentPage = initialPage;
    var debounceTimer = null;
    var localFilterTimer = null;
    var fetchController = null;
    var localBatch = [];
    var batchFetchKey = '';
    var perPage = 25;
    var erpFilterKeys = ['policy', 'name', 'prepared', 'intermediary'];
    var minFilterChars = 2;
    var fetchDebounceMs = 600;
    var localFilterDebounceMs = 150;

    function erpQueryFromFilters(filters) {
        var i, v;
        for (i = 0; i < erpFilterKeys.length; i++) {
            v = (filters[erpFilterKeys[i]] || '').trim();
            if (v.length >= minFilterChars) return v;
        }
        return '';
    }

    function getBatchFetchKey(filters) {
        return (system || '') + '|' + erpQueryFromFilters(filters).toLowerCase();
    }

    function filtersNeedMinChars(filters) {
        return filterKeys.some(function(key) {
            var v = (filters[key] || '').trim();
            return v.length > 0 && v.length < minFilterChars;
        });
    }

    function rowMatchesFilters(row, filters) {
        var map = {
            policy: (row.policy_no || row.policy || '').toString(),
            prepared: (row.pol_prepared_by || '').toString(),
            intermediary: (row.intermediary || '').toString(),
            name: (row.life_assur || '').toString(),
            product: (row.product || '').toString(),
            status: (row.status || '').toString()
        };
        var key, term, hay;
        for (key in filters) {
            if (!Object.prototype.hasOwnProperty.call(filters, key)) continue;
            term = (filters[key] || '').trim();
            if (!term) continue;
            hay = (map[key] || '').toLowerCase();
            if (hay.indexOf(term.toLowerCase()) === -1) return false;
        }
        return true;
    }

    function setLoading(loading) {
        if (tableCard) tableCard.classList.toggle('is-filter-loading', !!loading);
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function parseApiResponse(r) {
        return r.json().then(function(d) {
            if (!r.ok) {
                var msg = (d && d.error) ? d.error : ('Request failed (' + r.status + ')');
                return Promise.reject({ message: msg, status: r.status, data: d });
            }
            return d;
        });
    }

    function showLoadError(message) {
        setLoading(false);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="' + tableColspan + '" class="text-center py-4 text-warning">' + esc(message) + '</td></tr>';
        }
        setStatus('Could not load clients');
    }

    function getFilters() {
        var filters = {};
        filterKeys.forEach(function(key) {
            var el = document.querySelector('[data-col-filter="' + key + '"]');
            filters[key] = el ? el.value.trim() : '';
        });
        return filters;
    }

    function hasActiveFilters(filters) {
        return filterKeys.some(function(key) { return (filters[key] || '').length > 0; });
    }

    function syncFilterStyles(filters) {
        filterInputs.forEach(function(el) {
            var active = (el.value || '').trim().length > 0;
            el.classList.toggle('clients-col-filter-active', active);
        });
    }

    function setStatus(text) {
        if (statusEl) statusEl.textContent = text;
    }

    function updateUrl(filters, page) {
        var params = new URLSearchParams();
        if (system) params.set('system', system);
        else if (showAllClients) params.set('all', '1');
        if (page && page > 1) params.set('page', String(page));
        filterKeys.forEach(function(key) {
            if (filters[key]) params.set('f_' + key, filters[key]);
        });
        var qs = params.toString();
        window.history.replaceState({}, '', listUrl + (qs ? '?' + qs : ''));
    }

    function buildApiUrl(page, filters, batchAll) {
        var url = apiUrl + '?page=' + (page || 1);
        if (system) url += '&system=' + encodeURIComponent(system);
        else if (showAllClients) url += '&all=1';
        if (batchAll) url += '&batch_all=1';
        filterKeys.forEach(function(key) {
            if (filters[key]) url += '&f_' + key + '=' + encodeURIComponent(filters[key]);
        });
        return url;
    }

    function renderErpRow(c) {
        var policy = (c.policy_no || c.policy_number || c.policy || '').toString().trim();
        var statusClass = (c.status === 'A') ? 'active' : ((c.status === 'FL') ? 'lapsed' : 'other');
        var ls = c.life_system || 'individual';
        var systemLabel = systemLabels[ls] || systemLabels.individual || 'Individual Life';
        var sysQ = system ? '&system=' + encodeURIComponent(system) : '';
        var showLink = showUrl + '?policy=' + encodeURIComponent(policy) + sysQ;
        return '<tr class="clients-row-open" data-client-open="' + esc(showLink) + '">' +
            '<td><a href="' + esc(showLink) + '" class="clients-policy-link">' + esc(policy || '—') + '</a></td>' +
            '<td>' + esc(c.pol_prepared_by || '—') + '</td>' +
            '<td>' + esc((c.intermediary || '—').substring(0, 25)) + '</td>' +
            '<td><a href="' + esc(showLink) + '" class="clients-name-link text-decoration-none"><span class="clients-name">' + esc(c.life_assur || '—') + '</span></a></td>' +
            '<td class="clients-product">' + esc((c.product || '—').substring(0, 40)) + '</td>' +
            '<td><span class="clients-system-badge clients-system-' + esc(ls) + '">' + esc(systemLabel) + '</span></td>' +
            '<td><span class="clients-status-badge clients-status-' + statusClass + '">' + esc(c.status || '—') + '</span></td>' +
            '<td class="text-end"><div class="clients-actions">' +
            (policy ? (
                '<a href="' + ticketUrl + '?policy=' + encodeURIComponent(policy) + '" class="btn btn-sm btn-success" title="Create ticket"><i class="bi bi-ticket-perforated"></i> Ticket</a> ' +
                '<a href="' + esc(showLink) + '" class="btn btn-sm clients-btn-view" title="View full details"><i class="bi bi-eye"></i> View</a> ' +
                '<a href="' + serveUrl + '?search=' + encodeURIComponent(policy) + '" class="btn btn-sm clients-btn-serve" title="Serve client"><i class="bi bi-person-plus"></i> Serve</a>'
            ) : '<span class="text-muted small">—</span>') +
            '</div></td></tr>';
    }

    function renderEmptyRow() {
        return '<tr><td colspan="' + tableColspan + '" class="text-center py-5">' +
            '<div class="clients-empty"><div class="clients-empty-icon"><i class="bi bi-funnel"></i></div>' +
            '<h6 class="mt-3 mb-2">No clients match your filters</h6>' +
            '<p class="text-muted mb-0">Try adjusting the column filters above.</p></div></td></tr>';
    }

    function renderPagination(page, lastPage, onPage) {
        if (!paginationNav || lastPage <= 1) {
            if (paginationNav) paginationNav.innerHTML = '';
            return;
        }
        var html = '';
        if (page > 1) html += '<button type="button" class="page-link clients-ajax-page border-0 bg-transparent" data-page="' + (page - 1) + '">Previous</button> ';
        html += '<span class="mx-2">Page ' + page + ' of ' + lastPage + '</span> ';
        if (page < lastPage) html += '<button type="button" class="page-link clients-ajax-page border-0 bg-transparent" data-page="' + (page + 1) + '">Next</button>';
        paginationNav.innerHTML = html;
        paginationNav.querySelectorAll('.clients-ajax-page').forEach(function(btn) {
            btn.addEventListener('click', function() {
                onPage(parseInt(btn.dataset.page, 10));
            });
        });
    }

    function paintTable(rows, filters, page, total, options) {
        options = options || {};
        if (!tbody) return;
        tbody.innerHTML = rows.length ? rows.map(renderErpRow).join('') : renderEmptyRow();

        if (totalEl) {
            var statTotal = options.grandTotal != null ? options.grandTotal : total;
            totalEl.textContent = Number(statTotal).toLocaleString();
        }

        var pg = page || 1;
        var first = total ? ((pg - 1) * perPage + 1) : 0;
        var last = Math.min(pg * perPage, total);
        if (paginationInfo) paginationInfo.textContent = 'Showing ' + first + '–' + last + ' of ' + Number(total).toLocaleString();
        if (footer) footer.style.display = (total > 0 || hasActiveFilters(filters)) ? 'flex' : footer.style.display;

        if (hasActiveFilters(filters)) {
            setStatus(total ? (Number(total).toLocaleString() + ' match' + (total === 1 ? '' : 'es')) : 'No rows match filters');
        } else {
            setStatus('Filter by column — type at least 2 characters');
        }
        syncFilterStyles(filters);
    }

    function renderLocalPage(page, filters) {
        page = page || 1;
        currentPage = page;
        var filtered = localBatch.filter(function(row) { return rowMatchesFilters(row, filters); });
        var total = filtered.length;
        var pageRows = filtered.slice((page - 1) * perPage, page * perPage);
        paintTable(pageRows, filters, page, total, {});
        renderPagination(page, Math.ceil(total / perPage) || 1, function(p) {
            renderLocalPage(p, filters);
            updateUrl(filters, p);
        });
        updateUrl(filters, page);
    }

    function applyResponse(d, page, filters) {
        setLoading(false);
        if (!tbody) return;

        if (d.error && !(d.customers || []).length) {
            showLoadError(d.error);
            return;
        }

        if (d.batch_mode) {
            localBatch = d.customers || [];
            batchFetchKey = getBatchFetchKey(filters);
            renderLocalPage(1, filters);
            return;
        }

        localBatch = [];
        batchFetchKey = '';
        var rows = d.customers || [];
        var grand = null;
        if (!hasActiveFilters(filters)) {
            if (!system && grandTotalOnAll && d.grand_total != null) {
                grand = d.grand_total;
            } else if (system) {
                grand = d.grand_total != null ? d.grand_total : (d.total || null);
            }
        }
        paintTable(rows, filters, d.page || page || 1, d.total || 0, { grandTotal: grand });
        renderPagination(d.page || page || 1, Math.ceil((d.total || 0) / perPage) || 1, function(p) {
            loadClients(p, filters, false);
        });
    }

    function fetchBatch(filters, key) {
        if (fetchController) fetchController.abort();
        fetchController = new AbortController();
        setLoading(true);
        setStatus('Loading matches…');

        fetch(buildApiUrl(1, filters, true), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin', signal: fetchController.signal })
            .then(parseApiResponse)
            .then(function(d) {
                batchFetchKey = key;
                applyResponse(d, 1, filters);
            })
            .catch(function(err) {
                if (err && err.name === 'AbortError') return;
                showLoadError((err && err.message) ? err.message : 'Filter failed — try again');
            });
    }

    function loadClients(page, filters, batchMode) {
        page = page || 1;
        filters = filters || getFilters();
        currentPage = page;

        if (!isErpList) {
            debounceTimer = setTimeout(function() {
                updateUrl(filters, page);
                window.location.href = listUrl + (window.location.search || '');
            }, fetchDebounceMs);
            return;
        }

        if (hasActiveFilters(filters)) {
            var key = getBatchFetchKey(filters);
            if (localBatch.length && key === batchFetchKey) {
                renderLocalPage(page, filters);
                return;
            }
            fetchBatch(filters, key);
            return;
        }

        localBatch = [];
        batchFetchKey = '';

        if (fetchController) fetchController.abort();
        fetchController = new AbortController();
        setLoading(true);
        setStatus('Loading clients…');

        fetch(buildApiUrl(page, filters, false), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin', signal: fetchController.signal })
            .then(parseApiResponse)
            .then(function(d) {
                applyResponse(d, page, filters);
                updateUrl(filters, page);
            })
            .catch(function(err) {
                if (err && err.name === 'AbortError') return;
                showLoadError((err && err.message) ? err.message : 'Failed to load clients. Please try again.');
            });
    }

    function scheduleFilter() {
        var filters = getFilters();
        syncFilterStyles(filters);

        clearTimeout(debounceTimer);
        clearTimeout(localFilterTimer);

        if (!hasActiveFilters(filters)) {
            localBatch = [];
            batchFetchKey = '';
            debounceTimer = setTimeout(function() { loadClients(1, filters, false); }, 400);
            return;
        }

        if (filtersNeedMinChars(filters)) {
            setStatus('Type at least ' + minFilterChars + ' characters to filter');
            return;
        }

        var key = getBatchFetchKey(filters);
        if (localBatch.length && key === batchFetchKey) {
            localFilterTimer = setTimeout(function() { renderLocalPage(1, filters); }, localFilterDebounceMs);
            return;
        }

        debounceTimer = setTimeout(function() {
            fetchBatch(filters, key);
        }, fetchDebounceMs);
    }

    filterInputs.forEach(function(el) {
        el.addEventListener('input', scheduleFilter);
        el.addEventListener('search', function() {
            if (!(el.value || '').trim()) scheduleFilter();
        });
    });

    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function() {
            filterInputs.forEach(function(el) { el.value = ''; });
            localBatch = [];
            batchFetchKey = '';
            syncFilterStyles(getFilters());
            loadClients(1, getFilters(), false);
        });
    }

    document.addEventListener('click', function(e) {
        var row = e.target.closest('tr.clients-row-open[data-client-open]');
        if (!row || e.target.closest('a, button, .clients-actions')) return;
        window.location.href = row.getAttribute('data-client-open');
    });

    syncFilterStyles(getFilters());

    function hasServerRenderedRows() {
        if (!tbody) return false;
        return !!tbody.querySelector('tr.clients-row-open, a.clients-policy-link');
    }

    if (lazyLoad || (isErpList && (!hasServerRenderedRows() || hasActiveFilters(getFilters())))) {
        loadClients(initialPage, getFilters(), hasActiveFilters(getFilters()));
    }
})();
</script>
@endsection
