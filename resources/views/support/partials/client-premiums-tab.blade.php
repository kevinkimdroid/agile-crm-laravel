@php
$canPreviewReceipts = auth()->user()?->can('finance.receipts') ?? false;
$activePremiumPolicy = $selectedPremiumPolicy ?? $clientPolicy ?? $policy ?? '';
$premiumBlocks = $policyPremiums ?? [];
$premiumViewAll = $premiumViewAll ?? (count($premiumBlocks) > 1);
$premiumPolicyUrl = function (string $policyNo, bool $all = false) use ($clientShowBase) {
    $params = array_merge($clientShowBase, ['tab' => 'premiums']);
    if ($all) {
        $params['premium_view'] = 'all';
    } else {
        $params['premium_policy'] = $policyNo;
    }
    return route('support.clients.show', $params);
};
$mpesaPolicyNumber = $premiumViewAll ? ($activePremiumPolicy ?: ($premiumBlocks[0]['policy_no'] ?? '')) : $activePremiumPolicy;

$totalReceipts = (int) ($premiumsCount ?? 0);
$policyCount = count($premiumBlocks);
$totalPremiumAmount = 0;
foreach ($premiumBlocks as $block) {
    foreach ($block['receipts'] ?? [] as $row) {
        $r = is_array($row) ? $row : (array) $row;
        $amt = $r['amount'] ?? $r['AMOUNT'] ?? null;
        if (is_numeric($amt)) {
            $totalPremiumAmount += (float) $amt;
        }
    }
}

$activeBlock = null;
foreach ($premiumBlocks as $block) {
    if (($block['policy_no'] ?? '') === $activePremiumPolicy) {
        $activeBlock = $block;
        break;
    }
}
if (! $activeBlock && ! empty($premiumBlocks)) {
    $activeBlock = $premiumBlocks[0];
}
@endphp

<div class="premiums-page mpesa-ui">
    {{-- Toolbar --}}
    <div class="premiums-toolbar card contact-detail-card mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="premiums-toolbar-avatar">
                    {{ strtoupper(substr($clientName ?? 'C', 0, 1)) }}{{ strtoupper(substr(strrchr(trim($clientName ?? 'C'), ' ') ?: ($clientName ?? 'C'), 1, 1)) }}
                </div>
                <div class="flex-grow-1 min-width-0">
                    <div class="premiums-toolbar-eyebrow">Premiums & receipts</div>
                    <div class="premiums-toolbar-name text-truncate">{{ $clientName ?? 'Client' }}</div>
                </div>
                <div class="premiums-toolbar-metrics d-flex flex-wrap gap-2">
                    <span class="premiums-metric"><i class="bi bi-file-earmark-text"></i>{{ $policyCount }} {{ Str::plural('policy', $policyCount) }}</span>
                    <span class="premiums-metric"><i class="bi bi-receipt"></i>{{ $totalReceipts }} receipts</span>
                    <span class="premiums-metric premiums-metric-paid"><i class="bi bi-cash-stack"></i>KES {{ number_format($totalPremiumAmount, 0) }}</span>
                </div>
                <div class="premiums-toolbar-actions d-flex flex-wrap gap-2">
                    @if($clientPhone ?? null)
                    <a href="tel:{{ tel_href($clientPhone) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-telephone me-1"></i>Call</a>
                    @endif
                    <a href="{{ $clientTabUrl('details') }}" class="btn btn-sm btn-outline-primary">Details</a>
                </div>
            </div>
        </div>
    </div>

    @if($policiesError ?? null)
    <div class="alert alert-warning mb-4 border-0"><i class="bi bi-exclamation-triangle me-2"></i>{{ $policiesError }}</div>
    @endif

    @if(empty($premiumBlocks))
    <div class="card contact-detail-card">
        <div class="card-body p-5 text-center">
            <div class="premiums-empty-icon mx-auto mb-3"><i class="bi bi-shield-x"></i></div>
            <h5 class="mb-2">No policies found</h5>
            <p class="text-muted mb-3">We could not find policies linked to this client.</p>
            <a href="{{ route('support.serve-client', ['search' => $clientPolicy ?? $policy ?? '']) }}" class="btn btn-primary btn-sm">Search in Serve Client</a>
        </div>
    </div>
    @else
    <div class="row g-4 premiums-layout">
        @if($policyCount > 1)
        <div class="col-lg-3">
            <div class="premiums-sidebar card contact-detail-card h-100">
                <div class="card-body p-3">
                    <h6 class="premiums-sidebar-title">Policies</h6>
                    <nav class="premiums-policy-nav">
                        <a href="{{ $premiumPolicyUrl($activePremiumPolicy, true) }}"
                           class="premiums-policy-nav-item {{ $premiumViewAll ? 'is-active' : '' }}">
                            <span class="premiums-policy-nav-icon"><i class="bi bi-grid"></i></span>
                            <span class="premiums-policy-nav-body">
                                <span class="premiums-policy-nav-label">All</span>
                                <span class="premiums-policy-nav-meta">{{ $policyCount }} policies</span>
                            </span>
                        </a>
                        @foreach($premiumBlocks as $block)
                        @php
                            $policyNo = $block['policy_no'] ?? '—';
                            $isActive = ! $premiumViewAll && $policyNo === $activePremiumPolicy;
                        @endphp
                        <a href="{{ $premiumPolicyUrl($policyNo) }}"
                           class="premiums-policy-nav-item {{ $isActive ? 'is-active' : '' }} client-premium-policy-pill"
                           data-policy="{{ $policyNo }}">
                            <span class="premiums-policy-nav-icon"><i class="bi bi-hash"></i></span>
                            <span class="premiums-policy-nav-body">
                                <span class="premiums-policy-nav-label font-monospace">{{ $policyNo }}</span>
                                <span class="premiums-policy-nav-meta">{{ (int) ($block['receipt_count'] ?? 0) }} receipts</span>
                            </span>
                        </a>
                        @endforeach
                    </nav>
                </div>
            </div>
        </div>
        @endif

        <div class="{{ $policyCount > 1 ? 'col-lg-9' : 'col-12' }}">
            @if($policyCount === 1 && $activeBlock)
            <div class="premiums-policy-banner mb-4">
                <div class="premiums-policy-banner-main">
                    <span class="premiums-policy-banner-label">Policy</span>
                    <span class="premiums-policy-banner-no font-monospace">{{ $activeBlock['policy_no'] ?? $activePremiumPolicy }}</span>
                    @if($activeBlock['is_current'] ?? false)<span class="badge rounded-pill text-bg-primary">Current</span>@endif
                </div>
                <div class="premiums-policy-banner-product">{{ $activeBlock['product'] ?? '—' }}</div>
            </div>
            @endif

            @if($premiumViewAll && $policyCount > 1)
                @foreach($premiumBlocks as $block)
                @php
                    $policyNo = $block['policy_no'] ?? '—';
                    $blockAmount = 0;
                    foreach ($block['receipts'] ?? [] as $row) {
                        $r = is_array($row) ? $row : (array) $row;
                        if (is_numeric($r['amount'] ?? $r['AMOUNT'] ?? null)) {
                            $blockAmount += (float) ($r['amount'] ?? $r['AMOUNT']);
                        }
                    }
                @endphp
                <div class="card contact-detail-card premiums-workspace mb-4" id="policy-premiums-{{ $policyNo }}">
                    <div class="premiums-workspace-head">
                        <div>
                            <span class="font-monospace fw-bold">{{ $policyNo }}</span>
                            @if($block['is_current'] ?? false)<span class="badge rounded-pill text-bg-primary ms-1">Current</span>@endif
                            <div class="small text-muted">{{ $block['product'] ?? '—' }}</div>
                        </div>
                        <div class="text-end small text-muted">
                            {{ (int) ($block['receipt_count'] ?? 0) }} receipts
                            @if($blockAmount > 0)· KES {{ number_format($blockAmount, 0) }}@endif
                        </div>
                    </div>
                    <div class="row g-0 premiums-workspace-body">
                        <div class="col-lg-5 border-end premiums-workspace-pay">
                            @include('support.partials.client-mpesa-premium-compact', [
                                'clientPolicy' => $policyNo,
                                'policy' => $policyNo,
                            ])
                        </div>
                        <div class="col-lg-7 premiums-workspace-receipts">
                            <div class="premiums-receipts-panel-head">
                                <span class="fw-semibold small text-uppercase text-muted">Receipts</span>
                                @if($canPreviewReceipts)
                                <a href="{{ route('finance.receipts.search', ['query' => $policyNo, 'type' => 'policy']) }}" class="btn btn-sm btn-link">Finance search</a>
                                @endif
                            </div>
                            @if($block['error'] ?? null)
                            <div class="alert alert-warning border-0 m-3 mb-0 small">{{ $block['error'] }}</div>
                            @else
                            @include('support.partials.client-premium-receipts-table', [
                                'receiptRows' => $block['receipts'] ?? [],
                                'canPreviewReceipts' => $canPreviewReceipts,
                                'compact' => true,
                            ])
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            @else
                <div class="card contact-detail-card premiums-workspace">
                    <div class="premiums-workspace-head">
                        <div>
                            <span class="fw-bold">Pay & receipts</span>
                            <div class="small text-muted">Policy <span class="font-monospace" id="clientPremiumActivePolicyLabel">{{ $activePremiumPolicy }}</span></div>
                        </div>
                        @if($canPreviewReceipts && $activePremiumPolicy)
                        <a href="{{ route('finance.receipts.search', ['query' => $activePremiumPolicy, 'type' => 'policy']) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-search me-1"></i>Finance
                        </a>
                        @endif
                    </div>
                    <div class="row g-0 premiums-workspace-body">
                        <div class="col-lg-5 border-end premiums-workspace-pay" id="clientPremiumPaymentZone">
                            @include('support.partials.client-mpesa-premium-compact', [
                                'clientPolicy' => $mpesaPolicyNumber,
                                'policy' => $mpesaPolicyNumber,
                            ])
                        </div>
                        <div class="col-lg-7 premiums-workspace-receipts">
                            <div class="premiums-receipts-panel-head">
                                <span class="fw-semibold small text-uppercase text-muted">Premium receipts</span>
                                @if($activeBlock)
                                <span class="small text-muted">{{ (int) ($activeBlock['receipt_count'] ?? 0) }} on file</span>
                                @endif
                            </div>
                            @if($premiumsError ?? null)
                            <div class="alert alert-warning border-0 m-3 small">{{ $premiumsError }}</div>
                            @else
                            @include('support.partials.client-premium-receipts-table', [
                                'receiptRows' => $premiums ?? [],
                                'canPreviewReceipts' => $canPreviewReceipts,
                            ])
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>

<style>
.premiums-page { --premiums-blue: var(--agile-primary, #0E4385); }

.premiums-toolbar { border-radius: 16px; }
.premiums-toolbar-avatar {
    width: 2.75rem; height: 2.75rem; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(145deg, var(--premiums-blue), #1e5a9e);
    color: #fff; font-weight: 700; font-size: 0.9rem;
    display: flex; align-items: center; justify-content: center;
}
.premiums-toolbar-eyebrow {
    font-size: 0.65rem; font-weight: 700; letter-spacing: 0.08em;
    text-transform: uppercase; color: var(--agile-text-muted, #64748b);
}
.premiums-toolbar-name { font-size: 1.1rem; font-weight: 700; color: var(--agile-text, #1e293b); }
.premiums-metric {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.35rem 0.7rem; border-radius: 999px;
    background: #f1f5f9; font-size: 0.8rem; font-weight: 600; color: #475569;
}
.premiums-metric-paid { background: #ecfdf5; color: #047857; }

.premiums-policy-banner {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;
    padding: 1rem 1.15rem; border-radius: 14px;
    background: linear-gradient(90deg, rgba(14, 67, 133, 0.08), rgba(14, 67, 133, 0.02));
    border: 1px solid rgba(14, 67, 133, 0.12);
}
.premiums-policy-banner-main { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
.premiums-policy-banner-label {
    font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; color: var(--agile-text-muted);
}
.premiums-policy-banner-no { font-size: 1.05rem; font-weight: 700; color: var(--premiums-blue); }
.premiums-policy-banner-product { font-size: 0.85rem; color: var(--agile-text-muted); }

.premiums-sidebar { border-radius: 14px; position: sticky; top: 1rem; }
.premiums-sidebar-title {
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; color: var(--agile-text-muted); margin-bottom: 0.75rem;
}
.premiums-policy-nav { display: flex; flex-direction: column; gap: 0.35rem; }
.premiums-policy-nav-item {
    display: flex; align-items: center; gap: 0.6rem; padding: 0.6rem 0.7rem;
    border-radius: 10px; text-decoration: none; color: inherit;
    border: 1px solid transparent; transition: background 0.15s, border-color 0.15s;
}
.premiums-policy-nav-item:hover { background: #f8fafc; border-color: #e2e8f0; }
.premiums-policy-nav-item.is-active {
    background: rgba(14, 67, 133, 0.08); border-color: rgba(14, 67, 133, 0.2);
}
.premiums-policy-nav-icon {
    width: 1.85rem; height: 1.85rem; border-radius: 8px; flex-shrink: 0;
    background: #e2e8f0; color: #64748b;
    display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
}
.premiums-policy-nav-item.is-active .premiums-policy-nav-icon {
    background: var(--premiums-blue); color: #fff;
}
.premiums-policy-nav-label { display: block; font-size: 0.82rem; font-weight: 600; }
.premiums-policy-nav-meta { font-size: 0.72rem; color: var(--agile-text-muted); }

.premiums-workspace { border-radius: 16px; overflow: hidden; }
.premiums-workspace-head {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;
    padding: 0.9rem 1.15rem; border-bottom: 1px solid #e2e8f0; background: #fafbfc;
}
.premiums-workspace-body { min-height: 280px; }
.premiums-workspace-pay {
    padding: 1.15rem; background: linear-gradient(180deg, #f8fdf9, #fff);
}
.premiums-workspace-receipts { display: flex; flex-direction: column; min-height: 100%; }
.premiums-receipts-panel-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.75rem 1.15rem; border-bottom: 1px solid #f1f5f9; background: #fff;
}

.premiums-mpesa-compact-top { display: flex; gap: 0.85rem; margin-bottom: 1rem; }
.premiums-mpesa-compact-icon {
    width: 2.5rem; height: 2.5rem; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(145deg, #30B54A, #1a7f37); color: #fff;
    display: flex; align-items: center; justify-content: center;
}
.premiums-mpesa-compact-title { font-weight: 700; font-size: 0.95rem; color: #14532d; }
.premiums-mpesa-compact-desc { font-size: 0.82rem; color: #64748b; line-height: 1.45; }
.premiums-mpesa-compact-note {
    font-size: 0.78rem; color: #0369a1; background: #eff6ff;
    border-radius: 8px; padding: 0.5rem 0.65rem; margin-bottom: 1rem;
}
.premiums-mpesa-compact-note-muted { color: #64748b; background: #f1f5f9; }
.premiums-mpesa-compact-amounts {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 0.75rem;
}
@media (min-width: 1200px) { .premiums-mpesa-compact-amounts { grid-template-columns: repeat(3, 1fr); } }
.premiums-mpesa-amt {
    border: 1px solid #bbf7d0; background: #fff; border-radius: 10px;
    padding: 0.55rem 0.4rem; text-align: center; cursor: pointer;
    transition: border-color 0.15s, background 0.15s, transform 0.1s;
}
.premiums-mpesa-amt:hover { border-color: #30B54A; background: #ecfdf5; transform: translateY(-1px); }
.premiums-mpesa-amt.is-suggested { border-color: #30B54A; background: #ecfdf5; box-shadow: 0 0 0 2px rgba(48, 181, 74, 0.2); }
.premiums-mpesa-amt-value { display: block; font-weight: 700; font-size: 0.9rem; color: #14532d; line-height: 1.2; }
.premiums-mpesa-amt-currency { display: block; font-size: 0.62rem; font-weight: 600; color: #64748b; text-transform: uppercase; }
.premiums-mpesa-compact-cta { border-radius: 10px; font-weight: 600; }
.premiums-mpesa-compact-tx { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #d1fae5; }
.premiums-mpesa-compact-tx-label {
    font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; color: #64748b; margin-bottom: 0.5rem;
}
.premiums-mpesa-compact-tx-row {
    display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem;
    padding: 0.35rem 0; border-bottom: 1px solid #f1f5f9;
}
.premiums-mpesa-compact-tx-row:last-child { border-bottom: none; }
.premiums-mpesa-compact-tx-dot { font-size: 0.55rem; line-height: 1; }

.premiums-empty-icon {
    width: 3.5rem; height: 3.5rem; border-radius: 50%;
    background: rgba(14, 67, 133, 0.08); color: var(--premiums-blue);
    display: flex; align-items: center; justify-content: center; font-size: 1.35rem;
}

.premiums-receipts-empty {
    padding: 2.5rem 1.5rem; text-align: center; color: var(--agile-text-muted, #64748b); flex: 1;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.premiums-receipts-empty-visual {
    width: 3.5rem; height: 3.5rem; margin-bottom: 0.85rem; border-radius: 14px;
    background: linear-gradient(145deg, #f1f5f9, #e2e8f0); color: var(--premiums-blue);
    display: flex; align-items: center; justify-content: center; font-size: 1.35rem;
}
.premiums-receipts-table thead th {
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.05em;
    text-transform: uppercase; color: var(--agile-text-muted); padding: 0.7rem 1rem; background: #f8fafc;
}
.premiums-receipts-table tbody td { padding: 0.8rem 1rem; border-color: #f1f5f9; font-size: 0.875rem; }
.premiums-receipts-table tbody tr:hover { background: rgba(14, 67, 133, 0.03); }
.premiums-receipt-no { display: flex; align-items: center; gap: 0.55rem; }
.premiums-receipt-icon {
    width: 1.85rem; height: 1.85rem; border-radius: 8px; flex-shrink: 0;
    background: rgba(14, 67, 133, 0.08); color: var(--premiums-blue);
    display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
}
.premiums-amount-cell { color: #047857; font-variant-numeric: tabular-nums; }

@media (max-width: 991.98px) {
    .premiums-workspace-pay { border-right: none !important; border-bottom: 1px solid #e2e8f0; }
    .premiums-toolbar-metrics { width: 100%; }
    .premiums-mpesa-compact-amounts { grid-template-columns: repeat(2, 1fr); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var activePolicy = @json($mpesaPolicyNumber);
    var policyInput = document.querySelector('#mpesaStkForm input[name="policy_number"]');
    var policyLabel = document.getElementById('clientPremiumActivePolicyLabel');
    var descInput = document.getElementById('mpesa_description');

    function setActivePolicy(policyNo) {
        if (!policyNo) return;
        activePolicy = policyNo;
        if (policyInput) policyInput.value = policyNo;
        if (policyLabel) policyLabel.textContent = policyNo;
        document.querySelectorAll('.policy-ref').forEach(function(el) { el.textContent = policyNo; });
        if (descInput && descInput.value.indexOf('Premium') === 0) {
            descInput.value = 'Premium — ' + policyNo;
        }
    }

    document.querySelectorAll('.client-premium-policy-pill').forEach(function(pill) {
        pill.addEventListener('click', function() {
            setActivePolicy(pill.getAttribute('data-policy'));
        });
    });

    setActivePolicy(activePolicy);
});
</script>
