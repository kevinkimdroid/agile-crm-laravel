<?php

namespace App\Services\Receipts;

/**
 * Safe fallback when Oracle OCI8 is unavailable — avoids fatal oci_connect errors.
 */
class NullReceiptRepository implements ReceiptDataSource
{
    public function search(string $query, string $type): array
    {
        return [];
    }

    public function find(string $receiptNo, ?string $branch = null): ?array
    {
        return null;
    }
}
