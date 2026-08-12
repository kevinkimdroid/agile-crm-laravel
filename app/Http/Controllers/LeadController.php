<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\SocialInteraction;
use App\Services\CrmService;
use App\Services\PrpUnprocessedLeadsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LeadController extends Controller
{
    /** @var CrmService */
    protected $crm;

    protected PrpUnprocessedLeadsService $prpLeads;

    public function __construct(CrmService $crm, PrpUnprocessedLeadsService $prpLeads)
    {
        $this->crm = $crm;
        $this->prpLeads = $prpLeads;
    }

    public function landing(): View
    {
        $ownerId = crm_owner_filter();
        $statusCounts = $this->crm->getLeadsByStatus();
        $todayCount = $this->crm->getLeadsTodayCount($ownerId);
        $crmTotal = $this->crm->getLeadsCount(null, $ownerId);
        $prpEnabled = $this->prpLeads->isEnabled() && $this->prpLeads->hasCacheTable();
        $prpCount = $prpEnabled ? $this->prpLeads->getCount(null) : 0;

        return view('leads.landing', [
            'statusCounts' => $statusCounts,
            'todayCount' => $todayCount,
            'crmTotal' => $crmTotal,
            'prpEnabled' => $prpEnabled,
            'prpCount' => $prpCount,
            'grandTotal' => $crmTotal + ($prpEnabled ? $prpCount : 0),
        ]);
    }

    public function index(Request $request): View
    {
        $search = $request->get('q');
        $status = $request->filled('status') ? trim((string) $request->get('status')) : null;
        $perPage = 25;
        $page = max(1, (int) $request->get('page', 1));
        $source = $this->resolveLeadSource($request->get('source'));

        $offset = ($page - 1) * $perPage;
        $ownerId = crm_owner_filter();
        $prpEnabled = $this->prpLeads->isEnabled() && $this->prpLeads->hasCacheTable();
        $statusCounts = $this->crm->getLeadsByStatus();
        $todayCount = $this->crm->getLeadsTodayCount($ownerId);

        if ($source === 'prp' && $prpEnabled) {
            $total = $this->prpLeads->getCount($search);
            $leads = $this->prpLeads->makePaginator(
                $this->prpLeads->get($perPage, $offset, $search),
                $total,
                $perPage,
                $page,
                $request->url(),
                $request->query()
            );
        } elseif ($source === 'all' && $prpEnabled) {
            $combined = $this->prpLeads->paginateCombined(
                $perPage,
                $page,
                $search,
                fn (int $limit, int $offset, ?string $term) => $this->crm->getLeads($limit, $offset, $term, $ownerId, $status),
                fn (?string $term) => $this->crm->getLeadsCount($term, $ownerId, $status)
            );
            $leads = $this->prpLeads->makePaginator(
                $combined['items'],
                $combined['total'],
                $perPage,
                $page,
                $request->url(),
                $request->query()
            );
            $total = $combined['total'];
        } else {
            $total = $this->crm->getLeadsCount($search, $ownerId, $status);
            $leads = new LengthAwarePaginator(
                $this->crm->getLeads($perPage, $offset, $search, $ownerId, $status),
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('leads.index', [
            'leads' => $leads,
            'total' => $total ?? $leads->total(),
            'search' => $search,
            'currentStatus' => $status,
            'statusCounts' => $statusCounts,
            'todayCount' => $todayCount,
            'grandTotal' => array_sum($statusCounts) ?: ($total ?: 0),
            'leadSource' => $source,
            'prpEnabled' => $prpEnabled,
            'prpCount' => $prpEnabled ? $this->prpLeads->getCount($search) : 0,
        ]);
    }

    protected function resolveLeadSource(?string $source): string
    {
        $source = strtolower(trim((string) ($source ?? '')));
        if (in_array($source, ['crm', 'prp', 'all'], true)) {
            return $source;
        }

        if ($this->prpLeads->isEnabled() && $this->prpLeads->hasCacheTable()) {
            return 'all';
        }

        return 'crm';
    }

    public function create(): View
    {
        return view('leads.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'leadsource' => 'nullable|string|max:100',
        ]);

        try {
            $ownerId = \Illuminate\Support\Facades\Auth::guard('vtiger')->id() ?? 1;
            $label = trim($validated['firstname'] . ' ' . $validated['lastname']) ?: $validated['company'] ?? 'Lead';
            $now = now()->format('Y-m-d H:i:s');
            $id = null;

            \DB::connection('vtiger')->transaction(function () use ($validated, $ownerId, $label, $now, &$id) {
                $id = (int) \DB::connection('vtiger')->table('vtiger_crmentity')->max('crmid') + 1;

                \DB::connection('vtiger')->table('vtiger_crmentity')->insert([
                    'crmid' => $id,
                    'smcreatorid' => $ownerId,
                    'smownerid' => $ownerId,
                    'modifiedby' => $ownerId,
                    'setype' => 'Leads',
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

                \DB::connection('vtiger')->table('vtiger_leaddetails')->insert([
                    'leadid' => $id,
                    'lead_no' => 'LD' . $id,
                    'firstname' => $validated['firstname'],
                    'lastname' => $validated['lastname'],
                    'company' => $validated['company'] ?? '',
                    'email' => $validated['email'] ?? '',
                    'leadsource' => $validated['leadsource'] ?? '',
                ]);

                $phone = $validated['phone'] ?? '';
                if ($phone !== '') {
                    \DB::connection('vtiger')->table('vtiger_leadaddress')->updateOrInsert(
                        ['leadaddressid' => $id],
                        ['mobile' => $phone, 'phone' => $phone]
                    );
                }
            });
            if ($request->filled('from_interaction_id')) {
                SocialInteraction::where('id', $request->from_interaction_id)->update(['lead_id' => $id]);
            }
            Cache::forget('agile_leads_count');
            Cache::forget('agile_leads_today_' . now()->format('Y-m-d'));
            Cache::forget('agile_dashboard_stats');
            \App\Events\DashboardStatsUpdated::dispatch();
            return redirect()->route('leads.show', $id)->with('success', 'Lead created.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to create lead: ' . $e->getMessage());
        }
    }

    /** @return View|RedirectResponse */
    public function show(int $id)
    {
        $lead = $this->crm->getLead($id);
        if (!$lead) {
            return redirect()->route('leads.index')->with('error', 'Lead not found.');
        }
        if (!crm_user_can_access_record($lead)) {
            return redirect()->route('leads.index')->with('info', 'That lead is assigned to someone else. Showing your leads.');
        }
        return view('leads.show', ['lead' => $lead, 'isPrpLead' => false]);
    }

    /** @return View|RedirectResponse */
    public function showPrp(string $policyNumber)
    {
        $lead = $this->prpLeads->findByPolicyNumber($policyNumber);
        if (! $lead) {
            return redirect()->route('leads.index', ['source' => 'prp'])->with('error', 'PRP lead not found. Run prp-leads:sync if data is missing.');
        }

        return view('leads.show', ['lead' => $lead, 'isPrpLead' => true]);
    }

    /** @return View|RedirectResponse */
    public function edit(int $id)
    {
        $lead = $this->crm->getLead($id);
        if (!$lead) {
            return redirect()->route('leads.index')->with('error', 'Lead not found.');
        }
        if (!crm_user_can_access_record($lead)) {
            return redirect()->route('leads.index')->with('info', 'That lead is assigned to someone else. Showing your leads.');
        }
        return view('leads.edit', ['lead' => $lead]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $lead = $this->crm->getLead($id);
        if (!$lead) {
            return redirect()->route('leads.index')->with('error', 'Lead not found.');
        }
        if (!crm_user_can_access_record($lead)) {
            return redirect()->route('leads.index')->with('info', 'That lead is assigned to someone else. Showing your leads.');
        }

        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'leadsource' => 'nullable|string|max:100',
        ]);

        try {
            Lead::on('vtiger')->where('leadid', $id)->update([
                'firstname' => $validated['firstname'],
                'lastname' => $validated['lastname'],
                'company' => $validated['company'] ?? '',
                'email' => $validated['email'] ?? '',
                'leadsource' => $validated['leadsource'] ?? '',
            ]);
            $phone = $validated['phone'] ?? '';
            \DB::connection('vtiger')->table('vtiger_leadaddress')->updateOrInsert(
                ['leadaddressid' => $id],
                ['mobile' => $phone, 'phone' => $phone]
            );
            return redirect()->route('leads.show', $id)->with('success', 'Lead updated.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            \DB::connection('vtiger')->table('vtiger_crmentity')->where('crmid', $id)->update(['deleted' => 1]);
            Cache::forget('agile_leads_count');
            Cache::forget('agile_dashboard_stats');
            \App\Events\DashboardStatsUpdated::dispatch();
            return redirect()->route('leads.index')->with('success', 'Lead deleted.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }
}
