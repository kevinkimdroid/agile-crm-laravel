<?php

namespace App\Http\Controllers;

use App\Exports\MortgageRenewalsExport;
use App\Services\AdvantaSmsService;
use App\Services\ErpClientService;
use App\Services\MaturityClientNotificationService;
use App\Services\RenewalDemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MortgageRenewalController extends Controller
{
    /** Allowed “due within” periods (days). Matches API mendr_window_days cap (120). */
    public const RENEWAL_WINDOWS = [7, 14, 30, 90, 120];

    /**
     * Renewable product types. Each maps to the ERP "view" used by the clients API.
     * Only products with a configured ERP view can return live data; the rest render an
     * informative empty state until their source is wired up.
     */
    public const PRODUCTS = [
        'individual' => ['label' => 'Individual', 'icon' => 'bi-person-fill'],
        'group' => ['label' => 'Group', 'icon' => 'bi-people-fill'],
        'pension' => ['label' => 'Pension', 'icon' => 'bi-piggy-bank-fill'],
        'annuities' => ['label' => 'Annuities', 'icon' => 'bi-cash-coin'],
    ];

    public function __construct(
        protected ErpClientService $erp,
        protected RenewalDemoData $demoRenewals,
    ) {}

    /** POC: use seeded Orient demo renewals (10 per product). Default on. */
    protected function useDemoRenewals(): bool
    {
        return filter_var(env('RENEWALS_DEMO', true), FILTER_VALIDATE_BOOLEAN);
    }

    protected function normalizeWindow(Request $request): int
    {
        $w = (int) $request->get('window', 30);
        if (in_array($w, self::RENEWAL_WINDOWS, true)) {
            return $w;
        }

        return 30;
    }

    protected function normalizeProduct(Request $request): string
    {
        $p = strtolower(trim((string) $request->get('product', 'individual')));

        return array_key_exists($p, self::PRODUCTS) ? $p : 'individual';
    }

    /** ERP "view" key backing a product, or null when the product has no configured source yet. */
    protected function erpViewForProduct(string $product): ?string
    {
        return match ($product) {
            // Orient Group Mortgage lives under the Group class — reuse mortgage ERP view when available.
            'group' => trim((string) config('erp.clients_mortgage_view')) !== '' ? 'mortgage' : null,
            default => null,
        };
    }

    /**
     * Policies due for renewal in the next N calendar days (demo or ERP).
     */
    public function index(Request $request): View
    {
        $product = $this->normalizeProduct($request);
        $erpView = $this->erpViewForProduct($product);
        $productConfigured = $erpView !== null;
        $useDemo = $this->useDemoRenewals() || ! $productConfigured;
        $useHttp = ! empty(config('erp.clients_http_url'));

        $window = $this->normalizeWindow($request);
        $search = trim((string) $request->get('search', ''));
        $searchParam = $search !== '' ? $search : null;
        $notifyService = app(MaturityClientNotificationService::class);
        $renewalDateStart = now()->startOfDay();
        $renewalDateEnd = now()->startOfDay()->addDays($window);
        $fromStr = $renewalDateStart->format('Y-m-d');
        $toStr = $renewalDateEnd->format('Y-m-d');

        $page = max(1, (int) $request->get('page', 1));
        $perPage = in_array((int) $request->get('per_page', 25), [25, 50, 100], true)
            ? (int) $request->get('per_page', 25)
            : 25;
        $offset = ($page - 1) * $perPage;

        $productLabel = self::PRODUCTS[$product]['label'] ?? ucfirst($product);
        $error = null;
        $rows = collect();
        $total = 0;
        $stats = ['total' => 0, 'today' => 0, 'this_week' => 0, 'pending_notify' => 0];
        $isDemo = false;

        if ($useDemo) {
            $isDemo = true;
            $productConfigured = true;
            $all = $this->demoRenewals->forProduct($product, $window, $searchParam);
            $stats = $this->demoRenewals->statsFor($all);
            $total = $all->count();
            $rows = $all->slice($offset, $perPage)->values();
        } elseif (! $useHttp) {
            $error = 'Renewals need a live connection to the policy system. Ask an administrator to enable ERP HTTP client access.';
        } else {
            $countRes = $this->erp->getClientsFromHttpApi(1, 0, $searchParam, 25, true, $erpView, null, $fromStr, $toStr, true, $window);
            $error = $countRes['error'] ?? null;
            $total = (int) ($countRes['total'] ?? 0);
            $stats = $this->buildStats($fromStr, $toStr, $window, $searchParam, $total, $erpView);

            $dataRes = $this->erp->getClientsFromHttpApi($perPage, $offset, $searchParam, 45, false, $erpView, null, $fromStr, $toStr, true, $window);
            if (! $error && ! empty($dataRes['error'])) {
                $error = $dataRes['error'];
            }
            $rows = $dataRes['data'] instanceof Collection ? $dataRes['data'] : collect($dataRes['data'] ?? []);
            if ($rows->isNotEmpty()) {
                $rows = $notifyService->enrichContactsFromClientDetails($rows, 'policy_no');
                $rows = $notifyService->annotateRows($rows, $erpView, 'policy_no', 'mendr_renewal_date');
                $stats['pending_notify'] = $rows->filter(
                    fn ($row) => empty($row->client_notified_email) && empty($row->client_notified_sms)
                )->count();
            }
        }

        $paginator = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('support.mortgage-renewals', [
            'customers' => $paginator,
            'stats' => $stats,
            'renewalDateStart' => $renewalDateStart,
            'renewalDateEnd' => $renewalDateEnd,
            'window' => $window,
            'search' => $search,
            'perPage' => $perPage,
            'pageError' => $error,
            'product' => $product,
            'productLabel' => $productLabel,
            'products' => self::PRODUCTS,
            'mortgageConfigured' => $productConfigured,
            'useHttp' => $useHttp,
            'isDemoRenewals' => $isDemo,
            'notifyService' => $notifyService,
            'smsConfigured' => app(AdvantaSmsService::class)->isConfigured(),
        ]);
    }

    /**
     * @return array{total: int, today: int, this_week: int, pending_notify: int}
     */
    protected function buildStats(string $fromStr, string $toStr, int $window, ?string $search, int $total, string $erpView = 'mortgage'): array
    {
        $stats = [
            'total' => $total,
            'today' => 0,
            'this_week' => 0,
            'pending_notify' => 0,
        ];

        $today = now()->startOfDay()->format('Y-m-d');
        $weekEnd = now()->startOfDay()->addDays(7)->format('Y-m-d');
        $rangeEnd = min($toStr, $weekEnd);

        if ($today >= $fromStr && $today <= $toStr) {
            $todayRes = $this->erp->getClientsFromHttpApi(1, 0, $search, 20, true, $erpView, null, $today, $today, true, max(1, min($window, 7)));
            $stats['today'] = (int) ($todayRes['total'] ?? 0);
        }

        if ($rangeEnd >= $today) {
            $weekRes = $this->erp->getClientsFromHttpApi(1, 0, $search, 20, true, $erpView, null, $today, $rangeEnd, true, max(1, min($window, 7)));
            $stats['this_week'] = (int) ($weekRes['total'] ?? 0);
        }

        return $stats;
    }

    /**
     * Export renewals in the selected window.
     */
    public function export(Request $request): RedirectResponse|BinaryFileResponse
    {
        $product = $this->normalizeProduct($request);
        $erpView = $this->erpViewForProduct($product);
        $productConfigured = $erpView !== null;
        $useDemo = $this->useDemoRenewals() || ! $productConfigured;
        $useHttp = ! empty(config('erp.clients_http_url'));
        $window = $this->normalizeWindow($request);
        $renewalDateStart = now()->startOfDay();
        $renewalDateEnd = now()->startOfDay()->addDays($window);
        $fromStr = $renewalDateStart->format('Y-m-d');
        $toStr = $renewalDateEnd->format('Y-m-d');
        $backParams = ['window' => $window, 'product' => $product];

        if ($useDemo) {
            $all = $this->demoRenewals->forProduct($product, $window, null);
            $filename = $product.'-renewals-demo-'.$window.'d-'.now()->format('Y-m-d-His');

            return Excel::download(new MortgageRenewalsExport($all), $filename.'.xlsx');
        }

        if (! $productConfigured) {
            return redirect()
                ->route('support.mortgage-renewals', $backParams)
                ->with('error', ucfirst($product).' renewals do not have a live policy source configured.');
        }
        if (! $useHttp) {
            return redirect()
                ->route('support.mortgage-renewals', $backParams)
                ->with('error', 'ERP HTTP URL is not configured.');
        }

        $countRes = $this->erp->getClientsFromHttpApi(1, 0, null, 60, true, $erpView, null, $fromStr, $toStr, true, $window);
        if (! empty($countRes['error'])) {
            return redirect()
                ->route('support.mortgage-renewals', $backParams)
                ->with('error', $countRes['error']);
        }

        $total = (int) ($countRes['total'] ?? 0);
        $all = collect();
        $pageSize = 100;
        $offset = 0;
        $guard = 0;
        while ($offset < $total && $guard < 200) {
            $guard++;
            $dataRes = $this->erp->getClientsFromHttpApi($pageSize, $offset, null, 120, false, $erpView, null, $fromStr, $toStr, true, $window);
            if (! empty($dataRes['error'])) {
                return redirect()
                    ->route('support.mortgage-renewals', $backParams)
                    ->with('error', $dataRes['error']);
            }
            $chunk = $dataRes['data'] instanceof Collection ? $dataRes['data'] : collect($dataRes['data'] ?? []);
            if ($chunk->isEmpty()) {
                break;
            }
            $all = $all->merge($chunk);
            $offset += $pageSize;
        }

        $filename = $product.'-renewals-'.$window.'d-'.now()->format('Y-m-d-His');

        return Excel::download(new MortgageRenewalsExport($all), $filename.'.xlsx');
    }
}
