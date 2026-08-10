<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactComment;
use App\Models\ContactFollowup;
use App\Services\CrmService;
use Illuminate\Support\Facades\Cache;
use App\Services\ErpClientService;
use App\Services\MailService;
use App\Services\PbxCallService;
use App\Services\PbxConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ContactController extends Controller
{
    /** @var CrmService */
    protected $crm;
    /** @var ErpClientService */
    protected $erp;
    /** @var PbxCallService */
    protected $pbxCalls;
    /** @var PbxConfigService */
    protected $pbxConfig;
    /** @var MailService */
    protected $mailService;

    public function __construct(CrmService $crm, ErpClientService $erp, PbxCallService $pbxCalls, PbxConfigService $pbxConfig, MailService $mailService)
    {
        $this->crm = $crm;
        $this->erp = $erp;
        $this->pbxCalls = $pbxCalls;
        $this->pbxConfig = $pbxConfig;
        $this->mailService = $mailService;
    }

    public function index(Request $request): View
    {
        $perPage = 25;
        $page = max(1, (int) $request->get('page', 1));
        $offset = ($page - 1) * $perPage;
        $search = trim((string) $request->get('search', ''));

        $ownerId = crm_owner_filter();

        if ($search !== '') {
            $contactsData = $this->crm->getContacts($perPage, $offset, $ownerId, $search);
            $total = $this->crm->getContactsCount($ownerId, $search);
        } else {
            $cacheKey = 'contacts_list_' . ($ownerId ?? 'all') . '_' . $page;
            $ttl = 45;
            $cached = Cache::remember($cacheKey, $ttl, function () use ($perPage, $offset, $ownerId) {
                $contacts = $this->crm->getContacts($perPage, $offset, $ownerId);
                $total = $this->crm->getContactsCount($ownerId);
                return ['contacts' => $contacts, 'total' => $total];
            });
            $contactsData = $cached['contacts'];
            $total = $cached['total'];
        }

        $contacts = new LengthAwarePaginator(
            $contactsData instanceof Collection ? $contactsData : collect($contactsData),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('contacts.index', [
            'contacts' => $contacts,
            'total' => $total,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('contacts.create', [
            'leadSources' => $this->leadSources(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProspect($request);

        try {
            $ownerId = Auth::id() ?? Auth::guard('vtiger')->id() ?? 1;
            $label = trim($validated['firstname'] . ' ' . $validated['lastname']);
            $now = now()->format('Y-m-d H:i:s');
            $id = (int) DB::connection('vtiger')->table('vtiger_crmentity')->max('crmid') + 1;

            DB::connection('vtiger')->transaction(function () use ($id, $ownerId, $label, $now, $validated) {
                DB::connection('vtiger')->table('vtiger_crmentity')->insert([
                    'crmid' => $id,
                    'smcreatorid' => $ownerId,
                    'smownerid' => $ownerId,
                    'modifiedby' => $ownerId,
                    'setype' => 'Contacts',
                    'description' => $validated['description'] ?? '',
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

                $details = [
                    'contactid' => $id,
                    'contact_no' => 'CON' . $id,
                    'firstname' => $validated['firstname'],
                    'lastname' => $validated['lastname'],
                    'email' => $validated['email'] ?? '',
                    'secondaryemail' => $validated['secondaryemail'] ?? '',
                    'phone' => $validated['phone'] ?? '',
                    'mobile' => $validated['mobile'] ?? '',
                    'fax' => $validated['fax'] ?? '',
                    'title' => $validated['title'] ?? '',
                    'department' => $validated['department'] ?? '',
                    'donotcall' => ! empty($validated['donotcall']) ? 1 : 0,
                    'emailoptout' => ! empty($validated['emailoptout']) ? 1 : 0,
                ];
                $detailCols = Schema::connection('vtiger')->getColumnListing('vtiger_contactdetails');
                DB::connection('vtiger')->table('vtiger_contactdetails')
                    ->insert(array_intersect_key($details, array_flip($detailCols)));

                $this->syncProspectRelated($id, $validated);
            });

            Cache::forget('agile_contacts_count');
            Cache::forget('ticket_create_clients');
            Cache::forget('agile_dashboard_stats');
            $this->forgetContactsListCache($ownerId);
            \App\Events\DashboardStatsUpdated::dispatch();

            return redirect()->route('contacts.show', $id)->with('success', 'Prospect created.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to create contact: ' . $e->getMessage());
        }
    }

    /** @return View|RedirectResponse */
    public function show(Request $request, int $id)
    {
        try {
            $contact = $this->crm->getContact($id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Contact show failed loading contact', ['id' => $id, 'error' => $e->getMessage()]);

            return redirect()->route('contacts.index')->with('error', 'Could not open this prospect. Please try again or contact support.');
        }
        if (!$contact) {
            return redirect()->route('contacts.index')->with('error', 'Contact not found.');
        }
        if (!contact_can_access($id)) {
            return redirect()->route('contacts.index')->with('info', 'That contact is assigned to someone else. Showing your contacts.');
        }
        $tab = $request->get('tab', 'summary');
        if ($tab === 'calendar') {
            return redirect()->route('contacts.show', array_merge(['contact' => $id], $request->except(['tab']), ['tab' => 'updates']));
        }
        $tickets = collect();
        $ticketsPaginator = null;
        $policies = [];
        $policiesError = null;
        $calls = collect();
        $callsTotal = 0;
        $smsLogs = collect();
        $smsPaginator = null;
        $emails = [];
        $emailsPaginator = null;
        $campaigns = collect();
        $followups = collect();

        try {
        if ($tab === 'campaigns') {
            $campaigns = $this->crm->getCampaignsForContact($id);
        }

        if ($tab === 'summary') {
            $followups = ContactFollowup::where('contact_id', $id)->orderByDesc('followup_date')->orderByDesc('created_at')->limit(20)->get();
        }

        if ($tab === 'emails') {
            $emailsPage = max(1, (int) $request->get('page', 1));
            $emailsPerPage = 20;
            $emailsOffset = ($emailsPage - 1) * $emailsPerPage;
            $emails = $this->mailService->getEmailsForContact($contact, $emailsPerPage, $emailsOffset, $id);
            $emailsTotal = $this->mailService->getEmailsForContactCount($contact, $id);
            $emailsPaginator = new LengthAwarePaginator(
                $emails,
                $emailsTotal,
                $emailsPerPage,
                $emailsPage,
                ['path' => route('contacts.show', $id), 'query' => array_merge($request->query(), ['tab' => 'emails'])]
            );
        }

        if ($tab === 'sms') {
            $smsPage = max(1, (int) $request->get('page', 1));
            $smsPerPage = 20;
            $smsOffset = ($smsPage - 1) * $smsPerPage;
            $smsLogs = $this->crm->getSmsForContact($id, $smsPerPage, $smsOffset);
            $smsTotal = $this->crm->getSmsForContactCount($id);
            $smsPaginator = new LengthAwarePaginator(
                $smsLogs,
                $smsTotal,
                $smsPerPage,
                $smsPage,
                ['path' => route('contacts.show', $id), 'query' => array_merge($request->query(), ['tab' => 'sms'])]
            );
        }

        if ($tab === 'calls') {
            $callsPage = max(1, (int) $request->get('page', 1));
            $callsPerPage = 20;
            $callsOffset = ($callsPage - 1) * $callsPerPage;
            $callsResult = $this->pbxCalls->getCallsForContact($contact, $callsPerPage, $callsOffset);
            $calls = $callsResult['calls'];
            $callsTotal = $callsResult['total'];
            $callsPaginator = new LengthAwarePaginator(
                $calls,
                $callsTotal,
                $callsPerPage,
                $callsPage,
                ['path' => route('contacts.show', $id), 'query' => array_merge($request->query(), ['tab' => 'calls'])]
            );
            $pbxFromVtiger = $callsResult['from_vtiger'] ?? false;
            $pbxCanCall = $this->pbxConfig->isConfigured();
        } else {
            $callsPaginator = null;
            $pbxFromVtiger = false;
            $pbxCanCall = false;
        }

        if ($tab === 'policies') {
            if (! config('erp.enabled', true)) {
                $policies = [];
                $policiesError = null;
                if (! empty($contact->policy_number)) {
                    $policies = [[
                        'policy_no' => $contact->policy_number,
                        'POLICY_NO' => $contact->policy_number,
                        'name' => $contact->full_name ?? null,
                        'phone' => $contact->mobile ?? $contact->phone ?? null,
                        'email' => $contact->email ?? null,
                    ]];
                }
            } else {
                $result = $this->erp->getPoliciesForContact($contact);
                $policies = $result['data'] ?? [];
                $policiesError = $result['error'] ?? null;
            }
        }

        if ($tab === 'tickets') {
            $page = max(1, (int) $request->get('page', 1));
            $perPage = 20;
            $offset = ($page - 1) * $perPage;
            $ticketStatus = $request->get('list');
            $ticketSearch = $request->get('search');

            $ownerId = crm_owner_filter();
            $tickets = $this->crm->getTicketsForContactPaginated($id, $perPage, $offset, $ticketStatus, $ticketSearch, $ownerId);
            $total = $this->crm->getTicketsForContactCount($id, $ticketStatus, $ticketSearch, $ownerId);

            $ticketsPaginator = new LengthAwarePaginator(
                $tickets instanceof Collection ? $tickets : collect($tickets),
                $total,
                $perPage,
                $page,
                ['path' => route('contacts.show', $id), 'query' => $request->query()]
            );
        }

        $calendarActivities = collect();
        $calendarPaginator = null;
        $activityType = null;
        $activityStatus = null;
        $activitySearch = null;
        $activitySort = 'date_start';
        $activitySortDir = 'desc';
        $activityAssignedToFilter = null;
        $calendarUsers = collect();
        $canFilterActivitiesByAssignee = false;

        if ($tab === 'updates') {
            $activityType = $request->get('type');
            $activityStatus = $request->get('status');
            $activitySearch = $request->get('search');
            $activitySort = $request->get('sort', 'date_start');
            $activitySortDir = $request->get('dir', 'desc');
            $calPage = max(1, (int) $request->get('page', 1));
            $calPerPage = 25;
            $calOffset = ($calPage - 1) * $calPerPage;
            $ownerId = crm_owner_filter();
            $vtigerUser = Auth::guard('vtiger')->user();
            $canFilterActivitiesByAssignee = (bool) $vtigerUser?->isAdministrator();
            if ($canFilterActivitiesByAssignee) {
                try {
                    $calendarUsers = \App\Models\VtigerUser::on('vtiger')->where('status', 'Active')->orderBy('first_name')->orderBy('last_name')->get();
                } catch (\Throwable $e) {
                    $calendarUsers = collect();
                }
            }
            if ($vtigerUser?->isAdministrator() && $request->filled('assigned_to')) {
                $aid = (int) $request->get('assigned_to');
                $activityAssignedToFilter = $aid > 0 ? $aid : null;
            }
            $calendarActivities = $this->crm->getActivities(
                $calPerPage,
                $calOffset,
                $activityType,
                $activityStatus,
                $activitySearch,
                $id,
                null,
                $ownerId,
                $activityAssignedToFilter,
                $activitySort,
                $activitySortDir
            );
            $calendarTotal = $this->crm->countActivities(
                $activityType,
                $activityStatus,
                $activitySearch,
                $id,
                null,
                $ownerId,
                $activityAssignedToFilter
            );
            $calendarPaginator = new LengthAwarePaginator(
                $calendarActivities,
                $calendarTotal,
                $calPerPage,
                $calPage,
                ['path' => route('contacts.show', $id), 'query' => array_merge($request->query(), ['tab' => 'updates'])]
            );
        }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Contact show tab data failed', ['id' => $id, 'tab' => $tab, 'error' => $e->getMessage()]);
            $policiesError = $policiesError ?? ('Some data could not be loaded: ' . $e->getMessage());
            $callsPaginator = $callsPaginator ?? null;
            $pbxFromVtiger = $pbxFromVtiger ?? false;
            $pbxCanCall = $pbxCanCall ?? false;
            $calendarActivities = $calendarActivities ?? collect();
            $calendarPaginator = $calendarPaginator ?? null;
            $activityType = $activityType ?? null;
            $activityStatus = $activityStatus ?? null;
            $activitySearch = $activitySearch ?? null;
            $activitySort = $activitySort ?? 'date_start';
            $activitySortDir = $activitySortDir ?? 'desc';
            $activityAssignedToFilter = $activityAssignedToFilter ?? null;
            $calendarUsers = $calendarUsers ?? collect();
            $canFilterActivitiesByAssignee = $canFilterActivitiesByAssignee ?? false;
        }

        $ownerId = crm_owner_filter();
        $adjacent = ['prev' => null, 'next' => null];
        $ticketsCount = 0;
        $activitiesCount = 0;
        $commentsCount = 0;
        $emailsCount = 0;
        $allCampaigns = collect();

        try {
            $adjacent = $this->crm->getAdjacentContactIds($id, $ownerId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Contact adjacent ids failed', ['id' => $id, 'error' => $e->getMessage()]);
        }
        try {
            $ticketsCount = $this->crm->getTicketsForContactCount($id, null, null, $ownerId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Contact tickets count failed', ['id' => $id, 'error' => $e->getMessage()]);
        }
        try {
            $activitiesCount = $this->crm->countActivities(null, null, null, $id, null, $ownerId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Contact activities count failed', ['id' => $id, 'error' => $e->getMessage()]);
        }
        try {
            $commentsCount = ContactComment::where('contact_id', $id)->count();
        } catch (\Throwable $e) {
            $commentsCount = 0;
        }

        $deals = $activities = $comments = $contactComments = collect();
        if ($tab === 'summary') {
            try {
                $deals = $this->crm->getContactDeals($id, 5);
                $activities = $this->crm->getContactActivities($id, 5);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Contact summary data failed', ['id' => $id, 'error' => $e->getMessage()]);
            }
        }
        if ($tab === 'updates') {
            try {
                $comments = $this->crm->getContactComments($id, 100);
                $contactComments = ContactComment::where('contact_id', $id)
                    ->orderByDesc('created_at')
                    ->get();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Contact updates data failed', ['id' => $id, 'error' => $e->getMessage()]);
            }
        }

        try {
            $emailsCount = (int) Cache::remember('agile_emails_contact_' . $id, 120, fn () => $this->mailService->getEmailsForContactCount($contact, $id));
        } catch (\Throwable $e) {
            $emailsCount = 0;
        }
        try {
            $allCampaigns = Cache::remember('agile_all_campaigns', 300, fn () => Campaign::orderBy('campaign_name')->get());
        } catch (\Throwable $e) {
            $allCampaigns = collect();
        }

        return view('contacts.show', [
            'contact' => $contact,
            'deals' => $deals,
            'activities' => $activities,
            'comments' => $comments,
            'contactComments' => $contactComments,
            'activeTab' => $tab,
            'tickets' => $tickets,
            'ticketsPaginator' => $ticketsPaginator,
            'ticketStatus' => $tab === 'tickets' ? $request->get('list') : null,
            'ticketSearch' => $tab === 'tickets' ? $request->get('search') : null,
            'policies' => $policies ?? [],
            'policiesError' => $policiesError ?? null,
            'calls' => $calls ?? collect(),
            'callsPaginator' => $callsPaginator ?? null,
            'smsLogs' => $smsLogs ?? collect(),
            'smsPaginator' => $smsPaginator ?? null,
            'pbxFromVtiger' => $pbxFromVtiger ?? false,
            'pbxCanCall' => $pbxCanCall ?? false,
            'prevContactId' => $adjacent['prev'],
            'nextContactId' => $adjacent['next'],
            'ticketsCount' => $ticketsCount,
            'emails' => $emails ?? [],
            'emailsPaginator' => $emailsPaginator ?? null,
            'emailsCount' => $emailsCount,
            'campaigns' => $campaigns ?? collect(),
            'followups' => $followups ?? collect(),
            'allCampaigns' => $allCampaigns,
            'calendarActivities' => $calendarActivities,
            'calendarPaginator' => $calendarPaginator,
            'activityType' => $activityType,
            'activityStatus' => $activityStatus,
            'activitySearch' => $activitySearch,
            'activitySort' => $activitySort,
            'activitySortDir' => $activitySortDir,
            'activityAssignedToFilter' => $activityAssignedToFilter,
            'activitiesCount' => $activitiesCount,
            'commentsCount' => $commentsCount,
            'calendarUsers' => $calendarUsers,
            'canFilterActivitiesByAssignee' => $canFilterActivitiesByAssignee,
        ]);
    }

    /** @return View|RedirectResponse */
    public function edit(int $id)
    {
        $contact = $this->crm->getContact($id);
        if (!$contact) {
            return redirect()->route('contacts.index')->with('error', 'Contact not found.');
        }
        if (!contact_can_access($id)) {
            return redirect()->route('contacts.index')->with('info', 'That contact is assigned to someone else. Showing your contacts.');
        }
        return view('contacts.edit', [
            'contact' => $contact,
            'leadSources' => $this->leadSources(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $contact = $this->crm->getContact($id);
        if (!$contact) {
            return redirect()->route('contacts.index')->with('error', 'Contact not found.');
        }
        if (!contact_can_access($id)) {
            return redirect()->route('contacts.index')->with('info', 'That contact is assigned to someone else. Showing your contacts.');
        }

        $validated = $this->validateProspect($request);

        try {
            $ownerId = Auth::id() ?? Auth::guard('vtiger')->id() ?? 1;
            $label = trim($validated['firstname'] . ' ' . $validated['lastname']);
            $now = now()->format('Y-m-d H:i:s');

            DB::connection('vtiger')->transaction(function () use ($id, $ownerId, $label, $now, $validated) {
                $details = [
                    'firstname' => $validated['firstname'],
                    'lastname' => $validated['lastname'],
                    'email' => $validated['email'] ?? '',
                    'secondaryemail' => $validated['secondaryemail'] ?? '',
                    'phone' => $validated['phone'] ?? '',
                    'mobile' => $validated['mobile'] ?? '',
                    'fax' => $validated['fax'] ?? '',
                    'title' => $validated['title'] ?? '',
                    'department' => $validated['department'] ?? '',
                    'donotcall' => ! empty($validated['donotcall']) ? 1 : 0,
                    'emailoptout' => ! empty($validated['emailoptout']) ? 1 : 0,
                ];
                $detailCols = Schema::connection('vtiger')->getColumnListing('vtiger_contactdetails');
                Contact::on('vtiger')->where('contactid', $id)
                    ->update(array_intersect_key($details, array_flip($detailCols)));

                DB::connection('vtiger')->table('vtiger_crmentity')->where('crmid', $id)->update([
                    'label' => $label,
                    'description' => $validated['description'] ?? '',
                    'modifiedby' => $ownerId,
                    'modifiedtime' => $now,
                ]);

                $this->syncProspectRelated($id, $validated);
            });

            $this->forgetContactsListCache(null);
            Cache::forget('agile_contacts_count');
            Cache::forget('agile_dashboard_stats');

            return redirect()->route('contacts.show', $id)->with('success', 'Prospect updated.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            \DB::connection('vtiger')->table('vtiger_crmentity')->where('crmid', $id)->update(['deleted' => 1]);
            Cache::forget('agile_contacts_count');
            Cache::forget('ticket_create_clients');
            Cache::forget('agile_dashboard_stats');
            $this->forgetContactsListCache(null);
            \App\Events\DashboardStatsUpdated::dispatch();
            return redirect()->route('contacts.index')->with('success', 'Prospect deleted.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }

    public function storeFollowup(Request $request, int $contact): RedirectResponse
    {
        $validated = $request->validate([
            'note' => 'required|string|max:5000',
            'followup_date' => 'nullable|date',
        ]);

        ContactFollowup::create([
            'contact_id' => $contact,
            'user_id' => ($user = $request->user()) ? $user->id : null,
            'note' => $validated['note'],
            'followup_date' => $validated['followup_date'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('contacts.show', $contact)->with('success', 'Follow-up logged.');
    }

    public function storeComment(Request $request, int $contact): RedirectResponse
    {
        $contactRecord = $this->crm->getContact($contact);
        if (! $contactRecord) {
            return redirect()->route('contacts.index')->with('error', 'Contact not found.');
        }
        if (! contact_can_access($contact)) {
            return redirect()->route('contacts.index')->with('info', 'That contact is assigned to someone else.');
        }

        $validated = $request->validate([
            'body' => 'required|string|max:10000',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,gif,webp',
        ]);

        $authUser = Auth::guard('vtiger')->user();
        $authorName = 'Unknown';
        $userId = null;
        if ($authUser) {
            $userId = (int) ($authUser->id ?? $authUser->getAuthIdentifier());
            $authorName = trim(($authUser->first_name ?? '') . ' ' . ($authUser->last_name ?? ''))
                ?: ($authUser->user_name ?? 'User');
        }

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('contact-comments', 'public');
        }

        ContactComment::create([
            'contact_id' => $contact,
            'user_id' => $userId,
            'author_name' => $authorName,
            'body' => $validated['body'],
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        return redirect()
            ->to(route('contacts.show', ['contact' => $contact, 'tab' => 'updates']) . '#contact-comments')
            ->with('success', 'Comment posted.');
    }

    public function addToCampaign(Request $request, int $contact): RedirectResponse
    {
        $request->validate([
            'campaign_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('campaigns', 'id')],
        ]);

        if ($this->crm->addContactToCampaign($contact, (int) $request->campaign_id)) {
            return redirect()->route('contacts.show', $contact)->with('success', 'Contact added to campaign.');
        }
        return back()->with('error', 'Could not add contact to campaign.');
    }

    public function removeFromCampaign(int $contact, int $campaign): RedirectResponse
    {
        if ($this->crm->removeContactFromCampaign($contact, $campaign)) {
            return redirect()->route('contacts.show', $contact)->with('success', 'Contact removed from campaign.');
        }
        return back()->with('error', 'Could not remove contact from campaign.');
    }

    private function forgetContactsListCache(?int $ownerId): void
    {
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget('contacts_list_all_' . $page);
        }
        if ($ownerId !== null) {
            for ($page = 1; $page <= 10; $page++) {
                Cache::forget('contacts_list_' . $ownerId . '_' . $page);
            }
        }
    }

    /** @return list<string> */
    protected function leadSources(): array
    {
        try {
            if (! Schema::connection('vtiger')->hasTable('vtiger_leadsource')) {
                return ['Cold Call', 'Referral', 'Web Site', 'Agent', 'Social Media', 'Other'];
            }

            return DB::connection('vtiger')
                ->table('vtiger_leadsource')
                ->orderBy('sortorderid')
                ->pluck('leadsource')
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return ['Cold Call', 'Referral', 'Web Site', 'Agent', 'Social Media', 'Other'];
        }
    }

    /** @return array<string, mixed> */
    protected function validateProspect(Request $request): array
    {
        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'title' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'secondaryemail' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'homephone' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'leadsource' => 'nullable|string|max:100',
            'birthday' => 'nullable|date',
            'id_number' => 'nullable|string|max:64',
            'kra_pin' => 'nullable|string|max:32',
            'policy_number' => 'nullable|string|max:64',
            'lead_business_worth' => 'nullable|string|max:64',
            'mailingstreet' => 'nullable|string|max:255',
            'mailingcity' => 'nullable|string|max:100',
            'mailingstate' => 'nullable|string|max:100',
            'mailingzip' => 'nullable|string|max:32',
            'mailingcountry' => 'nullable|string|max:100',
            'mailingpobox' => 'nullable|string|max:64',
            'description' => 'nullable|string|max:5000',
            'donotcall' => 'nullable|boolean',
            'emailoptout' => 'nullable|boolean',
        ]);

        $validated['donotcall'] = $request->boolean('donotcall');
        $validated['emailoptout'] = $request->boolean('emailoptout');

        return $validated;
    }

    /** @param array<string, mixed> $validated */
    protected function syncProspectRelated(int $id, array $validated): void
    {
        if (Schema::connection('vtiger')->hasTable('vtiger_contactscf')) {
            $scf = [
                'contactid' => $id,
                'idNumber' => $validated['id_number'] ?? null,
                'cf_856' => $validated['id_number'] ?? null,
                'cf_852' => $validated['kra_pin'] ?? null,
                'cf_860' => $validated['policy_number'] ?? null,
                'cf_872' => $validated['lead_business_worth'] ?? null,
            ];
            $cols = Schema::connection('vtiger')->getColumnListing('vtiger_contactscf');
            DB::connection('vtiger')->table('vtiger_contactscf')->updateOrInsert(
                ['contactid' => $id],
                array_intersect_key($scf, array_flip($cols))
            );
        }

        if (Schema::connection('vtiger')->hasTable('vtiger_contactaddress')) {
            $addr = [
                'contactaddressid' => $id,
                'mailingstreet' => $validated['mailingstreet'] ?? '',
                'mailingcity' => $validated['mailingcity'] ?? '',
                'mailingstate' => $validated['mailingstate'] ?? '',
                'mailingzip' => $validated['mailingzip'] ?? '',
                'mailingcountry' => $validated['mailingcountry'] ?? '',
                'mailingpobox' => $validated['mailingpobox'] ?? '',
            ];
            $cols = Schema::connection('vtiger')->getColumnListing('vtiger_contactaddress');
            DB::connection('vtiger')->table('vtiger_contactaddress')->updateOrInsert(
                ['contactaddressid' => $id],
                array_intersect_key($addr, array_flip($cols))
            );
        }

        if (Schema::connection('vtiger')->hasTable('vtiger_contactsubdetails')) {
            $sub = [
                'contactsubscriptionid' => $id,
                'birthday' => $validated['birthday'] ?? null,
                'leadsource' => $validated['leadsource'] ?? '',
                'homephone' => $validated['homephone'] ?? '',
            ];
            $cols = Schema::connection('vtiger')->getColumnListing('vtiger_contactsubdetails');
            DB::connection('vtiger')->table('vtiger_contactsubdetails')->updateOrInsert(
                ['contactsubscriptionid' => $id],
                array_intersect_key($sub, array_flip($cols))
            );
        }
    }
}
