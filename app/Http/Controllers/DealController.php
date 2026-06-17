<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Services\CrmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DealController extends Controller
{
    /** @var CrmService */
    protected $crm;

    public function __construct(CrmService $crm)
    {
        $this->crm = $crm;
    }

    public function index(Request $request): View
    {
        $perPage = 25;
        $page = max(1, (int) $request->get('page', 1));
        $offset = ($page - 1) * $perPage;

        $ownerId = crm_owner_filter();
        $deals = $this->crm->getDeals($perPage, $offset, $ownerId);
        $total = $this->crm->getDealsCount($ownerId);

        $deals = new LengthAwarePaginator(
            $deals instanceof Collection ? $deals : collect($deals),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $pipelineCacheKey = $ownerId !== null ? 'agile_pipeline_value_' . $ownerId : 'agile_pipeline_value';
        $pipelineValue = Cache::remember($pipelineCacheKey, 90, fn () => $this->crm->getPipelineValue($ownerId));

        return view('deals.index', [
            'deals' => $deals,
            'pipelineValue' => $pipelineValue,
        ]);
    }

    public function create(): View
    {
        return view('deals.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'potentialname' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'sales_stage' => 'nullable|string|max:100',
            'closingdate' => 'nullable|date',
        ]);

        try {
            $ownerId = Auth::guard('vtiger')->id() ?? 1;
            $label = $validated['potentialname'];
            $now = now()->format('Y-m-d H:i:s');
            $id = null;

            DB::connection('vtiger')->transaction(function () use ($validated, $ownerId, $label, $now, &$id) {
                $id = (int) DB::connection('vtiger')->table('vtiger_crmentity')->max('crmid') + 1;

                DB::connection('vtiger')->table('vtiger_crmentity')->insert([
                    'crmid' => $id,
                    'smcreatorid' => $ownerId,
                    'smownerid' => $ownerId,
                    'modifiedby' => $ownerId,
                    'setype' => 'Potentials',
                    'description' => '',
                    'createdtime' => $now,
                    'modifiedtime' => $now,
                    'viewedtime' => null,
                    'status' => '',
                    'version' => 0,
                    'presence' => 1,
                    'deleted' => 0,
                    'smgroupid' => 0,
                    'source' => 'CRM',
                    'label' => $label,
                ]);

                DB::connection('vtiger')->table('vtiger_potential')->insert([
                    'potentialid' => $id,
                    'potentialname' => $validated['potentialname'],
                    'potential_no' => 'POT' . $id,
                    'amount' => $validated['amount'] ?? 0,
                    'sales_stage' => $validated['sales_stage'] ?? 'Prospecting',
                    'closingdate' => $validated['closingdate'] ?? null,
                ]);
            });

            Cache::forget('agile_pipeline_value');
            if ($ownerId) {
                Cache::forget('agile_pipeline_value_' . $ownerId);
            }
            Cache::forget('agile_deals_count');
            Cache::forget('agile_reports_index');
            Cache::forget('agile_dashboard_stats');
            \App\Events\DashboardStatsUpdated::dispatch();

            return redirect()->route('deals.show', $id)->with('success', 'Deal created.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to create deal: ' . $e->getMessage());
        }
    }

    /** @return View|RedirectResponse */
    public function show(int $id)
    {
        $deal = $this->crm->getDeal($id);
        if (!$deal) {
            return redirect()->route('deals.index')->with('error', 'Deal not found.');
        }
        if (!crm_user_can_access_record($deal)) {
            return redirect()->route('deals.index')->with('info', 'That deal is assigned to someone else. Showing your deals.');
        }
        return view('deals.show', ['deal' => $deal]);
    }

    /** @return View|RedirectResponse */
    public function edit(int $id)
    {
        $deal = $this->crm->getDeal($id);
        if (!$deal) {
            return redirect()->route('deals.index')->with('error', 'Deal not found.');
        }
        if (!crm_user_can_access_record($deal)) {
            return redirect()->route('deals.index')->with('info', 'That deal is assigned to someone else. Showing your deals.');
        }
        return view('deals.edit', ['deal' => $deal]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $deal = $this->crm->getDeal($id);
        if (!$deal) {
            return redirect()->route('deals.index')->with('error', 'Deal not found.');
        }
        if (!crm_user_can_access_record($deal)) {
            return redirect()->route('deals.index')->with('info', 'That deal is assigned to someone else. Showing your deals.');
        }

        $validated = $request->validate([
            'potentialname' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'sales_stage' => 'nullable|string|max:100',
            'closingdate' => 'nullable|date',
        ]);

        try {
            Deal::on('vtiger')->where('potentialid', $id)->update([
                'potentialname' => $validated['potentialname'],
                'amount' => $validated['amount'] ?? 0,
                'sales_stage' => $validated['sales_stage'] ?? $deal->sales_stage,
                'closingdate' => $validated['closingdate'] ?? $deal->closingdate,
            ]);
            DB::connection('vtiger')->table('vtiger_crmentity')->where('crmid', $id)->update([
                'label' => $validated['potentialname'],
                'modifiedtime' => now()->format('Y-m-d H:i:s'),
            ]);
            Cache::forget('agile_pipeline_value');
            Cache::forget('agile_deals_count');
            Cache::forget('agile_reports_index');
            Cache::forget('agile_dashboard_stats');
            \App\Events\DashboardStatsUpdated::dispatch();
            return redirect()->route('deals.show', $id)->with('success', 'Deal updated.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            \DB::connection('vtiger')->table('vtiger_crmentity')->where('crmid', $id)->update(['deleted' => 1]);
            Cache::forget('agile_pipeline_value');
            Cache::forget('agile_deals_count');
            Cache::forget('agile_reports_index');
            Cache::forget('agile_dashboard_stats');
            \App\Events\DashboardStatsUpdated::dispatch();
            return redirect()->route('deals.index')->with('success', 'Deal deleted.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }
}
