@php
    $receiptRows = $receiptRows ?? [];
    $canPreviewReceipts = $canPreviewReceipts ?? (auth()->user()?->can('finance.receipts') ?? false);
    $compact = $compact ?? false;
@endphp
@if(empty($receiptRows))
<div class="premiums-receipts-empty">
    <div class="premiums-receipts-empty-visual">
        <i class="bi bi-receipt-cutoff"></i>
    </div>
    <p class="fw-semibold mb-1">No receipts yet</p>
    <p class="small text-muted mb-0">Premium payments for this policy will appear here once recorded in finance.</p>
</div>
@else
<div class="table-responsive premiums-receipts-wrap">
    <table class="table align-middle mb-0 premiums-receipts-table">
        <thead>
            <tr>
                <th>Receipt</th>
                <th>Date</th>
                <th class="text-end">Amount</th>
                @if(! $compact)
                <th class="d-none d-md-table-cell">Paid by</th>
                @endif
                @if($canPreviewReceipts)
                <th class="text-end"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($receiptRows as $row)
            @php
                $receiptRow = is_array($row) ? $row : (array) $row;
                $receiptNo = $receiptRow['receipt_no'] ?? $receiptRow['RECEIPT_NO'] ?? '—';
                $branch = $receiptRow['branch_code'] ?? $receiptRow['BRANCH_CODE'] ?? null;
                $rctNo = $receiptRow['rct_no'] ?? $receiptRow['RCT_NO'] ?? null;
                $amount = $receiptRow['amount'] ?? $receiptRow['AMOUNT'] ?? null;
                $currency = $receiptRow['currency'] ?? $receiptRow['CURRENCY'] ?? 'KES';
                $linkParams = array_filter([
                    'receiptNo' => $rctNo ?: $receiptNo,
                    'branch' => $branch,
                ], fn ($v) => $v !== null && $v !== '' && $v !== '—');
                $amountDisplay = is_numeric($amount)
                    ? $currency . ' ' . number_format((float) $amount, 0)
                    : ($amount ?? '—');
            @endphp
            <tr>
                <td>
                    <div class="premiums-receipt-no">
                        <span class="premiums-receipt-icon"><i class="bi bi-receipt"></i></span>
                        <span class="font-monospace fw-semibold">{{ $receiptNo }}</span>
                    </div>
                </td>
                <td class="text-nowrap text-muted">{{ $receiptRow['receipt_date'] ?? $receiptRow['RECEIPT_DATE'] ?? '—' }}</td>
                <td class="text-end fw-semibold premiums-amount-cell">{{ $amountDisplay }}</td>
                @if(! $compact)
                <td class="d-none d-md-table-cell text-muted small">{{ $receiptRow['client_name'] ?? $receiptRow['CLIENT_NAME'] ?? '—' }}</td>
                @endif
                @if($canPreviewReceipts)
                <td class="text-end">
                    @if(! empty($linkParams))
                    <a href="{{ route('finance.receipts.preview', $linkParams) }}" class="btn btn-sm btn-light premiums-receipt-view-btn" title="View receipt">
                        <i class="bi bi-eye"></i><span class="d-none d-sm-inline ms-1">View</span>
                    </a>
                    @endif
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
