<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Deterministic demo investment maturities for the Orient POC.
 * Used when INVESTMENT_MATURITIES_DEMO=true (no ERP API / Oracle required).
 *
 * @see OrientPocCatalog
 */
class InvestmentMaturityDemoData
{
    /**
     * @return Collection<int, object>
     */
    public function forWindow(int $days = 14, ?string $search = null): Collection
    {
        return OrientPocCatalog::investmentMaturityRows($days, $search);
    }
}
