<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\CrmService;
use App\Services\ErpClientService;
use App\Services\PbxConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** @var CrmService */
    protected $crm;
    /** @var ErpClientService */
    protected $erp;
    /** @var PbxConfigService */
    protected $pbxConfig;

    public function __construct(CrmService $crm, ErpClientService $erp, PbxConfigService $pbxConfig)
    {
        $this->crm = $crm;
        $this->erp = $erp;
        $this->pbxConfig = $pbxConfig;
    }

    public function index(Request $request): View
    {
        $ownerId = config('dashboard.show_all_stats', true) ? null : crm_owner_filter();
        $stats = $this->crm->getDashboardStats(null, $ownerId);

        $overdueScope = resolve_overdue_activity_scope($request->get('overdue_scope'));
        $overdueCacheKey = 'dashboard_overdue:' . ($overdueScope['scope'] ?? 'mine') . ':' . ($overdueScope['ownerId'] ?? 0);
        $stats['overdueActivities'] = Cache::remember(
            $overdueCacheKey,
            120,
            fn () => $this->crm->getOverdueActivities(5, $overdueScope['ownerId'])
        );
        $stats['overdueScope'] = $overdueScope['scope'];
        $stats['canViewAllOverdue'] = $overdueScope['canViewAll'];

        if ($ownerId !== null) {
            $stats['contactsCount'] = (int) $this->crm->getContactsCount($ownerId);
        }
        $stats['contactsCountDeferred'] = false;

        // Clients = local KYC clients (+ ERP when configured). Prospects stay on Vtiger contacts.
        $localClientsCount = $this->localClientsCount();
        $source = config('erp.clients_view_source', 'crm');
        if (in_array($source, ['erp_http', 'erp_sync'], true)) {
            $cachedErpCount = Cache::get('agile_clients_count');
            if ($cachedErpCount !== null) {
                $stats['clientsCount'] = $localClientsCount + (int) $cachedErpCount;
                $stats['clientsCountDeferred'] = false;
            } else {
                // Show local count immediately; AJAX adds ERP when ready.
                $stats['clientsCount'] = $localClientsCount;
                $stats['clientsCountDeferred'] = true;
            }
        } else {
            $stats['clientsCount'] = $localClientsCount;
            $stats['clientsCountDeferred'] = false;
        }

        $stats['pbxCanCall'] = $this->pbxConfig->isConfigured();
        $stats['salesByPerson'] = Cache::remember('agile_dashboard_sales_by_person_top8', 120, fn () => $this->crm->getSalesByPerson(8));

        return view('dashboard', $stats);
    }

    /**
     * Lightweight endpoint for lazy-loaded clients count (avoids blocking dashboard on slow ERP).
     * Uses ErpClientService::getClientsCount() — same as Support > Clients “All” stat (group + individual
     * + mortgage + group pension when those views are configured in Laravel .env).
     */
    public function clientsCount(): \Illuminate\Http\JsonResponse
    {
        $local = $this->localClientsCount();
        $source = config('erp.clients_view_source', 'crm');
        if (! in_array($source, ['erp_http', 'erp_sync'], true)) {
            return response()->json(['count' => $local]);
        }
        $erp = Cache::remember('agile_clients_count', (int) config('performance.cache_ttl.erp_clients_count', 600), function () {
            try {
                return (int) ($this->erp->getClientsCount(8) ?? 0);
            } catch (\Throwable $e) {
                return 0;
            }
        });

        return response()->json(['count' => $local + (int) $erp]);
    }

    protected function localClientsCount(): int
    {
        try {
            return Client::tableExists() ? (int) Client::query()->count() : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
