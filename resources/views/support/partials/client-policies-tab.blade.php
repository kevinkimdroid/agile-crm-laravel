<div class="card contact-detail-card mb-4">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-bottom bg-light">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Other policies</h6>
            <a href="{{ route('support.serve-client', ['search' => $clientPolicy ?? $policy ?? '']) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-search me-1"></i> Search more in ERP
            </a>
        </div>
        @if($policiesError ?? null)
        <div class="p-4">
            <div class="alert alert-warning mb-0">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ $policiesError }}
            </div>
        </div>
        @elseif(empty($policies ?? []))
        <div class="p-5 text-center text-muted">
            <i class="bi bi-box display-6 d-block mb-2 opacity-50"></i>
            <p class="mb-2">No other policies found for this client in the ERP.</p>
            <a href="{{ route('support.serve-client', ['search' => $clientPolicy ?? $policy ?? '']) }}" class="btn btn-primary btn-sm">Search in Serve Client</a>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase fw-bold">Policy</th>
                        <th class="small text-uppercase fw-bold">Name</th>
                        <th class="small text-uppercase fw-bold">Phone</th>
                        <th class="small text-uppercase fw-bold">Product</th>
                        <th class="small text-uppercase fw-bold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($policies ?? [] as $policyRow)
                    @php
                        $policyNo = $policyRow['policy_no'] ?? $policyRow['policy_number'] ?? $policyRow['POLICY_NO'] ?? $policyRow['POLICY_NUMBER'] ?? '—';
                        $name = $policyRow['name'] ?? $policyRow['client_name'] ?? $policyRow['life_assur'] ?? $policyRow['CLIENT_NAME'] ?? '—';
                        $phone = $policyRow['phone'] ?? $policyRow['phone_no'] ?? $policyRow['mobile'] ?? $policyRow['PHONE'] ?? '—';
                        $product = $policyRow['product'] ?? $policyRow['prod_desc'] ?? $policyRow['PRODUCT'] ?? '—';
                        $isCurrent = $policyNo !== '—' && ($policyNo === ($clientPolicy ?? $policy ?? ''));
                    @endphp
                    <tr class="{{ $isCurrent ? 'table-active' : '' }}">
                        <td class="fw-semibold font-monospace">
                            {{ $policyNo }}
                            @if($isCurrent)
                            <span class="badge bg-primary ms-1">Current</span>
                            @endif
                        </td>
                        <td>{{ $name }}</td>
                        <td><span class="text-muted">{{ $phone }}</span></td>
                        <td><span class="text-muted">{{ $product }}</span></td>
                        <td class="text-end">
                            @if($policyNo !== '—' && ! $isCurrent)
                            <a href="{{ route('support.clients.show', array_filter(['policy' => $policyNo, 'from' => ($fromServeClient ?? false) ? 'serve-client' : null])) }}" class="btn btn-sm btn-outline-primary" title="View client">
                                <i class="bi bi-eye"></i>
                            </a>
                            @endif
                            @if($policyNo !== '—' && ! looks_like_kra_pin($policyNo))
                            <a href="{{ route('support.clients.create-ticket', ['policy' => $policyNo]) }}" class="btn btn-sm btn-success" title="Create ticket">
                                <i class="bi bi-ticket-perforated"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
