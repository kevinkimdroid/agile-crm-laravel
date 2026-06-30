<?php

namespace App\Http\Controllers;

use App\Models\ErpClientConsent;
use App\Models\ErpClientComment;
use App\Models\ErpClientDocument;
use App\Models\MpesaStkTransaction;
use App\Models\SmsLog;
use App\Services\MpesaStkPushService;
use App\Services\CrmService;
use App\Services\Receipts\ReceiptDataSource;
use App\Services\ErpClientService;
use App\Services\MailService;
use App\Services\PbxCallService;
use App\Services\PbxConfigService;
use App\Services\ProfileAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    private const CLIENTS_API_CACHE_VERSION = 'v3';

    /** @var CrmService */
    protected $crm;
    /** @var ErpClientService */
    protected $erp;
    /** @var MailService */
    protected $mailService;
    /** @var PbxCallService */
    protected $pbxCalls;
    /** @var PbxConfigService */
    protected $pbxConfig;
    /** @var ProfileAccessService */
    protected $profileAccess;

    public function __construct(CrmService $crm, ErpClientService $erp, MailService $mailService, PbxCallService $pbxCalls, PbxConfigService $pbxConfig, ProfileAccessService $profileAccess)
    {
        $this->crm = $crm;
        $this->erp = $erp;
        $this->mailService = $mailService;
        $this->pbxCalls = $pbxCalls;
        $this->pbxConfig = $pbxConfig;
        $this->profileAccess = $profileAccess;
    }

    /**
     * Show client details by policy number (ERP clients) or redirect to contact (CRM).
     */
    /** @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse */
    public function show(Request $request)
    {
        $policy = $request->get('policy', '');
        $policy = trim($policy);
        $fromServeClient = $request->get('from') === 'serve-client';
        if ($policy === '') {
            return redirect()->route('support.customers')->with('error', 'Policy number required.');
        }

        $system = trim((string) $request->get('system', ''));
        if (! user_can_access_client_policy($policy, $system !== '' ? $system : null)) {
            return redirect()->route('support.customers', array_filter(['system' => $system ?: null]))
                ->with('error', 'You do not have access to this client.');
        }

        $source = config('erp.clients_view_source', 'crm');
        $useErp = in_array($source, ['erp', 'erp_sync', 'erp_http']);

        if ($useErp) {
            $client = $this->erp->getPolicyDetails($policy);
        if (!$client) {
            // Fallback: try CRM contact by policy (ERP/API may be down with ORA-03113)
            $contact = $this->crm->findContactByPolicyNumber($policy);
            if ($contact) {
                return redirect()->route('contacts.show', ['contact' => $contact->contactid, 'tab' => 'summary'])
                    ->with('info', 'ERP unavailable. Showing CRM prospect for policy ' . $policy);
            }
            $redirect = $fromServeClient
                ? redirect()->route('support.serve-client', ['search' => $policy])
                : redirect()->route('support.customers', array_filter(['system' => $request->get('system')]));
            return $redirect->with('error', 'Client not found for policy ' . $policy . '. (Ensure erp-clients-api is running and Oracle is reachable.)');
        }

            $contact = $this->findCachedContactByPolicy($policy);
            $client['policy_no'] = $policy;
            $client['policy_number'] = $policy;

            $tab = $request->get('tab', 'summary');
            $contactId = $contact?->contactid;
            $ownerId = crm_owner_filter();
            $clientShowBase = array_filter([
                'policy' => $policy,
                'from' => $fromServeClient ? 'serve-client' : null,
            ]);
            $clientShowPath = route('support.clients.show', $clientShowBase);

            if ($tab === 'calendar') {
                return redirect()->route('support.clients.show', array_merge($clientShowBase, $request->except(['tab']), ['tab' => 'updates']));
            }

            $tickets = collect();
            $ticketsPaginator = null;
            $ticketStatus = null;
            $ticketSearch = null;
            $calls = collect();
            $callsPaginator = null;
            $pbxFromVtiger = false;
            $pbxCanCall = false;
            $smsLogs = collect();
            $smsPaginator = null;
            $emails = [];
            $emailsPaginator = null;
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
            $activities = collect();
            $summaryTickets = collect();
            $emailsCount = 0;
            $policies = [];
            $policiesError = null;
            $policiesCount = 0;
            $premiums = [];
            $premiumsError = null;
            $premiumsCount = 0;
            $policyPremiums = [];
            $selectedPremiumPolicy = $policy;
            $premiumViewAll = false;
            $smsCount = 0;

            $clientName = trim((string) ($client['life_assur'] ?? $client['client_name'] ?? $client['name'] ?? ''));
            $nameParts = preg_split('/\s+/', $clientName, 2);
            $clientPhone = trim((string) ($client['phone_no'] ?? $client['phoneNo'] ?? $client['mobile'] ?? $client['phone'] ?? $client['client_contact'] ?? ''));
            $clientEmail = trim((string) ($client['email_adr'] ?? $client['client_email'] ?? $client['email'] ?? ''));
            if ($clientEmail !== '' && ! filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
                $clientEmail = '';
            }

            $erpLookup = (object) [
                'policy_number' => $policy,
                'policy_no' => $policy,
                'firstname' => $nameParts[0] ?? $clientName,
                'lastname' => $nameParts[1] ?? '',
                'mobile' => $clientPhone,
                'phone' => $clientPhone,
                'email' => $clientEmail,
                'email_adr' => $clientEmail,
            ];
            $mailSubject = $this->buildClientMailLookup($contact, $erpLookup, $policy, $clientEmail);
            $callSubject = $contact ?? $erpLookup;

            if ($tab === 'policies') {
                $result = $this->erp->getPoliciesForContact($erpLookup);
                $policies = $result['data'] ?? [];
                $policiesError = $result['error'] ?? null;
            }

            if ($tab === 'premiums') {
                $premiumBundle = $this->buildClientPremiumPoliciesData($erpLookup, $policy, $request);
                $policyPremiums = $premiumBundle['policyPremiums'];
                $selectedPremiumPolicy = $premiumBundle['selectedPremiumPolicy'];
                $premiumViewAll = $premiumBundle['premiumViewAll'];
                $premiums = $premiumBundle['premiums'];
                $premiumsError = $premiumBundle['premiumsError'];
                $policies = $premiumBundle['policies'];
                $policiesError = $premiumBundle['policiesError'] ?? ($policiesError ?? null);
                $policiesCount = count($policies);
                $premiumsCount = $premiumBundle['premiumsCount'];
            }

            if ($tab === 'tickets' && $contactId) {
                $page = max(1, (int) $request->get('page', 1));
                $perPage = 20;
                $offset = ($page - 1) * $perPage;
                $ticketStatus = $request->get('list');
                $ticketSearch = $request->get('search');
                $tickets = $this->crm->getTicketsForContactPaginated($contactId, $perPage, $offset, $ticketStatus, $ticketSearch, $ownerId);
                $total = $this->crm->getTicketsForContactCount($contactId, $ticketStatus, $ticketSearch, $ownerId);
                $ticketsPaginator = new LengthAwarePaginator(
                    $tickets instanceof Collection ? $tickets : collect($tickets),
                    $total,
                    $perPage,
                    $page,
                    ['path' => $clientShowPath, 'query' => $request->query()]
                );
            } elseif ($tab === 'summary' && $contactId) {
                $activities = $this->crm->getContactActivities($contactId, 5);
                $summaryTickets = $this->crm->getTicketsForContact($contactId, 5);
            }

            if ($tab === 'emails') {
                $emailsPage = max(1, (int) $request->get('page', 1));
                $emailsPerPage = 20;
                $emailsOffset = ($emailsPage - 1) * $emailsPerPage;
                $emails = $this->mailService->getEmailsForContact($mailSubject, $emailsPerPage, $emailsOffset, $contactId);
                $emailsTotal = $this->mailService->getEmailsForContactCount($mailSubject, $contactId);
                $emailsPaginator = new LengthAwarePaginator(
                    $emails,
                    $emailsTotal,
                    $emailsPerPage,
                    $emailsPage,
                    ['path' => $clientShowPath, 'query' => array_merge($request->query(), ['tab' => 'emails'])]
                );
            }

            if ($tab === 'sms') {
                $smsPage = max(1, (int) $request->get('page', 1));
                $smsPerPage = 20;
                $smsOffset = ($smsPage - 1) * $smsPerPage;
                $phoneDigits = $clientPhone !== '' ? preg_replace('/\D/', '', $clientPhone) : '';
                $hasSmsFilter = $contactId || $phoneDigits !== '' || $policy !== '';
                if ($hasSmsFilter) {
                    $smsQuery = SmsLog::query()->orderByDesc('sent_at');
                    $smsQuery->where(function ($q) use ($contactId, $phoneDigits, $policy) {
                        if ($contactId) {
                            $q->orWhere('contact_id', $contactId);
                        }
                        if ($phoneDigits !== '') {
                            $q->orWhere('phone', 'like', '%' . $phoneDigits . '%');
                        }
                        if ($policy !== '') {
                            $q->orWhere('erp_policy_no', $policy);
                        }
                    });
                    $smsTotal = (clone $smsQuery)->count();
                    $smsLogs = $smsQuery->skip($smsOffset)->take($smsPerPage)->get();
                } else {
                    $smsTotal = 0;
                    $smsLogs = collect();
                }
                $smsPaginator = new LengthAwarePaginator(
                    $smsLogs,
                    $smsTotal,
                    $smsPerPage,
                    $smsPage,
                    ['path' => $clientShowPath, 'query' => array_merge($request->query(), ['tab' => 'sms'])]
                );
            }

            if ($tab === 'calls') {
                $callsPage = max(1, (int) $request->get('page', 1));
                $callsPerPage = 20;
                $callsOffset = ($callsPage - 1) * $callsPerPage;
                $callsResult = $this->pbxCalls->getCallsForContact($callSubject, $callsPerPage, $callsOffset);
                $calls = $callsResult['calls'];
                $callsTotal = $callsResult['total'];
                $callsPaginator = new LengthAwarePaginator(
                    $calls,
                    $callsTotal,
                    $callsPerPage,
                    $callsPage,
                    ['path' => $clientShowPath, 'query' => array_merge($request->query(), ['tab' => 'calls'])]
                );
                $pbxFromVtiger = $callsResult['from_vtiger'] ?? false;
                $pbxCanCall = $this->pbxConfig->isConfigured();
            }

            if ($tab === 'updates' && $contactId) {
                $activityType = $request->get('type');
                $activityStatus = $request->get('status');
                $activitySearch = $request->get('search');
                $activitySort = $request->get('sort', 'date_start');
                $activitySortDir = $request->get('dir', 'desc');
                $calPage = max(1, (int) $request->get('page', 1));
                $calPerPage = 25;
                $calOffset = ($calPage - 1) * $calPerPage;
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
                    $contactId,
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
                    $contactId,
                    null,
                    $ownerId,
                    $activityAssignedToFilter
                );
                $calendarPaginator = new LengthAwarePaginator(
                    $calendarActivities,
                    $calendarTotal,
                    $calPerPage,
                    $calPage,
                    ['path' => $clientShowPath, 'query' => array_merge($request->query(), ['tab' => 'updates'])]
                );
            }

            $fastTabCounts = $this->getClientTabCountsFast($contactId, $ownerId, $policy, $clientPhone);
            $activitiesCount = (int) ($fastTabCounts['activitiesCount'] ?? 0);
            $ticketsCount = (int) ($fastTabCounts['ticketsCount'] ?? 0);
            $smsCount = (int) ($fastTabCounts['smsCount'] ?? 0);
            $emailsCount = 0;
            $policiesCount = 0;
            $premiumsCount = 0;
            $tabCountsDeferred = true;

            $clientConsent = ErpClientConsent::forPolicy($policy);
            $clientComments = ErpClientComment::forPolicy($policy);
            $clientDocuments = ErpClientDocument::forPolicy($policy);
            $documentsCount = $clientDocuments->count();
            $commentsCount = $clientComments->count();
            $mpesaService = app(MpesaStkPushService::class);
            $mpesaConfigured = $mpesaService->isConfigured();
            $mpesaSandboxSimulate = $mpesaService->isSandboxSimulate();
            $mpesaConfigurationError = $mpesaService->configurationError();
            $mpesaTransactions = $mpesaConfigured && \Illuminate\Support\Facades\Schema::hasTable('mpesa_stk_transactions')
                ? MpesaStkTransaction::forPolicy($policy)->latest()->limit(8)->get()
                : collect();

            return view('support.client-show', [
                'client' => (object) $client,
                'tickets' => $tab === 'tickets' ? $tickets : $summaryTickets,
                'ticketsPaginator' => $ticketsPaginator,
                'ticketStatus' => $ticketStatus,
                'ticketSearch' => $ticketSearch,
                'contact' => $contact,
                'policy' => $policy,
                'system' => $system !== '' ? $system : null,
                'fromServeClient' => $fromServeClient,
                'activeTab' => $tab,
                'clientShowBase' => $clientShowBase,
                'activities' => $activities,
                'activitiesCount' => $activitiesCount,
                'ticketsCount' => $ticketsCount,
                'calendarActivities' => $calendarActivities,
                'calendarPaginator' => $calendarPaginator,
                'activityType' => $activityType,
                'activityStatus' => $activityStatus,
                'activitySearch' => $activitySearch,
                'activitySort' => $activitySort,
                'activitySortDir' => $activitySortDir,
                'activityAssignedToFilter' => $activityAssignedToFilter,
                'calendarUsers' => $calendarUsers,
                'canFilterActivitiesByAssignee' => $canFilterActivitiesByAssignee,
                'activitiesPageRoute' => 'support.clients.show',
                'activitiesPageParams' => $clientShowBase,
                'calls' => $calls,
                'callsPaginator' => $callsPaginator,
                'pbxFromVtiger' => $pbxFromVtiger,
                'pbxCanCall' => $pbxCanCall,
                'smsLogs' => $smsLogs,
                'smsPaginator' => $smsPaginator,
                'emails' => $emails,
                'emailsPaginator' => $emailsPaginator,
                'emailsCount' => $emailsCount,
                'policies' => $policies,
                'policiesError' => $policiesError,
                'policiesCount' => $policiesCount,
                'premiums' => $premiums,
                'premiumsError' => $premiumsError,
                'premiumsCount' => $premiumsCount,
                'policyPremiums' => $policyPremiums ?? [],
                'selectedPremiumPolicy' => $selectedPremiumPolicy ?? $policy,
                'premiumViewAll' => $premiumViewAll ?? false,
                'smsCount' => $smsCount,
                'clientConsent' => $clientConsent,
                'clientComments' => $clientComments,
                'commentsCount' => $commentsCount,
                'clientDocuments' => $clientDocuments,
                'documentsCount' => $documentsCount,
                'mpesaConfigured' => $mpesaConfigured,
                'mpesaSandboxSimulate' => $mpesaSandboxSimulate,
                'mpesaConfigurationError' => $mpesaConfigurationError,
                'mpesaTransactions' => $mpesaTransactions,
                'tabCountsDeferred' => $tabCountsDeferred ?? false,
                'clientPolicy' => $policy,
            ]);
        }

        $contact = $this->crm->findContactByPolicyNumber($policy);
        if ($contact) {
            return redirect()->route('contacts.show', ['contact' => $contact->contactid, 'tab' => 'summary']);
        }

        return redirect()->route('support.customers')->with('error', 'Client not found.');
    }

    /**
     * Lazy-load tab badge counts (ERP policies, premiums, emails) so client pages render immediately.
     */
    public function clientTabCounts(Request $request): JsonResponse
    {
        $policy = trim((string) $request->get('policy', ''));
        if ($policy === '') {
            return response()->json(['error' => 'Policy required'], 422);
        }

        $system = trim((string) $request->get('system', ''));
        if (! user_can_access_client_policy($policy, $system !== '' ? $system : null)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $contact = $this->findCachedContactByPolicy($policy);
        $contactId = $contact?->contactid;
        $ownerId = crm_owner_filter();

        $client = $this->erp->getPolicyDetails($policy);
        if (! $client) {
            return response()->json([
                'activitiesCount' => 0,
                'ticketsCount' => 0,
                'emailsCount' => 0,
                'policiesCount' => 0,
                'premiumsCount' => 0,
                'smsCount' => 0,
            ]);
        }

        $clientName = trim((string) ($client['life_assur'] ?? $client['client_name'] ?? $client['name'] ?? ''));
        $nameParts = preg_split('/\s+/', $clientName, 2);
        $clientPhone = trim((string) ($client['phone_no'] ?? $client['phoneNo'] ?? $client['mobile'] ?? $client['phone'] ?? $client['client_contact'] ?? ''));
        $clientEmail = trim((string) ($client['email_adr'] ?? $client['client_email'] ?? $client['email'] ?? ''));
        if ($clientEmail !== '' && ! filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            $clientEmail = '';
        }

        $erpLookup = (object) [
            'policy_number' => $policy,
            'policy_no' => $policy,
            'firstname' => $nameParts[0] ?? $clientName,
            'lastname' => $nameParts[1] ?? '',
            'mobile' => $clientPhone,
            'phone' => $clientPhone,
            'email' => $clientEmail,
            'email_adr' => $clientEmail,
        ];
        $mailSubject = $this->buildClientMailLookup($contact, $erpLookup, $policy, $clientEmail);

        $fast = $this->getClientTabCountsFast($contactId, $ownerId, $policy, $clientPhone);
        $slow = $this->getClientTabCountsSlow($contactId, $ownerId, $mailSubject, $erpLookup, $policy);

        return response()->json(array_merge($fast, $slow));
    }

    /**
     * @return array{activitiesCount:int,ticketsCount:int,smsCount:int}
     */
    private function getClientTabCountsFast(?int $contactId, ?int $ownerId, string $policy, string $clientPhone): array
    {
        $cacheKey = 'client_tab_counts_fast:' . sha1($policy . '|' . ($contactId ?? 0) . '|' . ($ownerId ?? 0));

        return Cache::remember($cacheKey, (int) config('performance.cache_ttl.client_tab_counts', 600), function () use ($contactId, $ownerId, $policy, $clientPhone) {
            $phoneDigits = $clientPhone !== '' ? preg_replace('/\D/', '', $clientPhone) : '';
            $smsCount = 0;
            if ($contactId || $phoneDigits !== '' || $policy !== '') {
                $smsCountQuery = SmsLog::query();
                $smsCountQuery->where(function ($q) use ($contactId, $phoneDigits, $policy) {
                    if ($contactId) {
                        $q->orWhere('contact_id', $contactId);
                    }
                    if ($phoneDigits !== '') {
                        $q->orWhere('phone', 'like', '%' . $phoneDigits . '%');
                    }
                    if ($policy !== '') {
                        $q->orWhere('erp_policy_no', $policy);
                    }
                });
                $smsCount = $smsCountQuery->count();
            }

            return [
                'activitiesCount' => $contactId
                    ? (int) $this->crm->countActivities(null, null, null, $contactId, null, $ownerId)
                    : 0,
                'ticketsCount' => $contactId
                    ? (int) $this->crm->getTicketsForContactCount($contactId, null, null, $ownerId)
                    : 0,
                'smsCount' => $smsCount,
            ];
        });
    }

    /**
     * @return array{emailsCount:int,policiesCount:int,premiumsCount:int}
     */
    private function getClientTabCountsSlow(?int $contactId, ?int $ownerId, object $mailSubject, object $erpLookup, string $policy): array
    {
        $cacheKey = 'client_tab_counts_slow:' . sha1($policy . '|' . ($contactId ?? 0));

        return Cache::remember($cacheKey, (int) config('performance.cache_ttl.client_tab_counts_slow', 900), function () use ($contactId, $mailSubject, $erpLookup, $policy) {
            $policies = $this->erp->getPoliciesForContact($erpLookup, 50)['data'] ?? [];

            return [
                'emailsCount' => (int) $this->mailService->getEmailsForContactCount($mailSubject, $contactId),
                'policiesCount' => count($policies),
                'premiumsCount' => $this->countClientPremiumReceipts($erpLookup, $policy),
            ];
        });
    }

    /**
     * Debug: show raw ERP API response. Use ?policy=GEMPPP0335&system=group or ?search=GEMPPP0335&system=group
     */
    public function debugApi(Request $request)
    {
        $policy = trim((string) $request->get('policy', ''));
        $search = trim((string) $request->get('search', ''));
        $system = trim((string) $request->get('system', ''));
        $url = config('erp.clients_http_url');
        if (empty($url)) {
            return response()->json(['error' => 'ERP_CLIENTS_HTTP_URL not configured'], 500);
        }
        $url = rtrim($url, '/');
        $sep = (strpos($url, '?') !== false) ? '&' : '?';
        $params = ['limit' => 5, 'offset' => 0];
        if ($policy !== '') {
            $params['policy'] = $policy;
        }
        if ($search !== '') {
            $params['search'] = $search;
        }
        if (in_array($system, ['group', 'individual', 'mortgage', 'group_pension'], true)) {
            $params['system'] = $system;
        }
        if ($request->get('debug')) {
            $params['debug'] = '1';
        }
        $query = http_build_query($params);
        $apiUrl = $url . $sep . $query;
        $response = \Illuminate\Support\Facades\Http::timeout(20)->get($apiUrl);
        $body = $response->json();
        $parsed = parse_url($url);
        $base = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? 'localhost') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
        $columnsView = match ($system) {
            'group' => 'group',
            'mortgage' => 'mortgage',
            'group_pension' => 'group_pension',
            default => null,
        };
        $columnsUrl = $base . '/columns' . ($columnsView ? '?view=' . $columnsView : '');
        $columnsResp = \Illuminate\Support\Facades\Http::timeout(5)->get($columnsUrl);
        $columnsData = $columnsResp->successful() ? $columnsResp->json() : null;

        return response()->json([
            'policy' => $policy ?: null,
            'search' => $search ?: null,
            'system' => $system ?: null,
            'api_url' => $apiUrl,
            'api_status' => $response->status(),
            'data' => $body['data'] ?? $body['clients'] ?? [],
            'total' => $body['total'] ?? null,
            'error' => $body['error'] ?? null,
            'view_columns' => $columnsData['columns'] ?? null,
            'raw' => $body,
        ]);
    }

    /**
     * Debug: show distinct PRODUCT values from Oracle. Use to configure ERP_GROUP_LIFE_KEYWORDS.
     */
    public function debugProducts(Request $request)
    {
        $url = config('erp.clients_http_url');
        if (empty($url)) {
            return response()->json(['error' => 'ERP_CLIENTS_HTTP_URL not configured'], 500);
        }
        $parsed = parse_url(rtrim($url, '/'));
        $base = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? 'localhost') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
        $response = \Illuminate\Support\Facades\Http::timeout(15)->get($base . '/products');
        $body = $response->json();
        return response()->json($body ?? ['error' => 'API not reachable']);
    }

    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $search = $request->get('search');
        $columnFilters = $this->parseColumnFilters($request);
        $hasColumnFilters = $this->hasActiveColumnFilters($columnFilters);
        $source = config('erp.clients_view_source', 'crm');
        $listRoute = request()->routeIs('contacts.index') ? 'contacts.index' : 'support.customers';
        $systemInput = trim((string) $request->get('system', ''));
        if ($systemInput !== '' && ! user_can_access_client_segment($systemInput)) {
            return redirect()->route($listRoute, $request->except('system'))
                ->with('error', 'You do not have access to the selected client type.');
        }
        $system = $systemInput !== '' ? $systemInput : null;
        $allowedClientSegments = allowed_client_segments();
        if (
            in_array($source, ['erp_sync', 'erp_http'])
            && ! $system
            && ! $search
            && ! $hasColumnFilters
            && ! $request->boolean('all')
            && count($allowedClientSegments) > 0
        ) {
            $defaultSegment = null;
            foreach (['group', 'individual', 'mortgage', 'group_pension'] as $seg) {
                if (in_array($seg, $allowedClientSegments, true)) {
                    $defaultSegment = $seg;
                    break;
                }
            }
            $defaultSegment ??= $allowedClientSegments[0];

            return redirect()->route($listRoute, array_merge(
                $request->except(['system', 'all']),
                ['system' => $defaultSegment]
            ));
        }
        if (
            in_array($source, ['erp_sync', 'erp_http'])
            && ! $system
            && count($allowedClientSegments) === 1
        ) {
            return redirect()->route($listRoute, array_merge(
                $request->except('system'),
                ['system' => $allowedClientSegments[0]]
            ));
        }
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $clientsError = null;
        $clientsGrandTotal = null;
        $useErp = in_array($source, ['erp', 'erp_sync', 'erp_http'])
            && (in_array($source, ['erp_sync', 'erp_http']) || $this->erp->isConfigured())
            && ($source !== 'erp_http' || ! empty(config('erp.clients_http_url')));

        // Paint the page first; rows load via /api/support/clients when lazy-load is enabled.
        $lazyLoad = $useErp
            && in_array($source, ['erp_sync', 'erp_http'])
            && config('performance.erp_clients_lazy_load', true)
            && ! $search
            && ! $hasColumnFilters;

        $erpSearch = $hasColumnFilters ? $this->primarySearchFromColumnFilters($columnFilters) : $search;

        if ($useErp && ! $lazyLoad) {
            try {
                $user = Auth::guard('vtiger')->user();
                if ($user && $this->profileAccess->userIsLimitedToAssignedClients($user) && ! $hasColumnFilters && ! $erpSearch) {
                    $result = $this->profileAccess->fetchAssignedClientsPage((int) $user->id, $system, $perPage, $offset, $this->erp);
                    $customers = $result['data'];
                    $total = $result['total'];
                    $clientsGrandTotal = $result['grand_total'] ?? null;
                    $clientsError = $result['error'] ?? null;
                } elseif ($hasColumnFilters) {
                    $result = $this->getCachedErpFilterBatch($erpSearch, $system);
                    $customers = $this->applyColumnFilters($result['data'], $columnFilters);
                    if ($user && $this->profileAccess->userIsLimitedToAssignedClients($user)) {
                        $customers = $this->profileAccess->filterCustomersToAssignedPolicies($customers, (int) $user->id, $system);
                    }
                    $total = $customers->count();
                    $customers = $customers->slice($offset, $perPage)->values();
                    $clientsError = $result['error'] ?? null;
                    $clientsGrandTotal = $user && $this->profileAccess->userIsLimitedToAssignedClients($user)
                        ? count($this->profileAccess->getAssignedPolicyNumbersForUser((int) $user->id, $system))
                        : ($result['grand_total'] ?? null);
                } else {
                    $result = $this->fetchClientsListResult($perPage, $offset, $erpSearch, $system);
                    $customers = $result['data'];
                    $total = $result['total'];
                    $clientsGrandTotal = $result['grand_total'] ?? null;
                    $clientsError = $result['error'] ?? null;
                    if ($user && $this->profileAccess->userIsLimitedToAssignedClients($user)) {
                        $customers = $this->profileAccess->filterCustomersToAssignedPolicies(
                            $customers instanceof Collection ? $customers : collect($customers),
                            (int) $user->id,
                            $system
                        );
                        $total = count($this->profileAccess->getAssignedPolicyNumbersForUser((int) $user->id, $system));
                        $clientsGrandTotal = $total;
                    }
                }
                if ($clientsError && $customers->isEmpty()) {
                    // API .env missing mortgage/pension/group view vars — do not substitute CRM contacts (misleading).
                    $erpHttpConfigGap = $source === 'erp_http' && str_contains((string) $clientsError, 'erp-clients-api/.env');
                    if (! $erpHttpConfigGap) {
                        $ownerId = crm_owner_filter();
                        $customers = $this->crm->getCustomers($perPage, $offset, $search, $ownerId);
                        $total = $this->crm->getCustomersCount($search, $ownerId);
                        $clientsGrandTotal = null;
                        $clientsError = $clientsError . ' Showing CRM contacts below (if any).';
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Clients list ERP load failed', [
                    'system' => $system,
                    'message' => $e->getMessage(),
                ]);
                $customers = collect();
                $total = 0;
                $clientsGrandTotal = null;
                $clientsError = config('app.debug')
                    ? 'Could not load clients: ' . $e->getMessage()
                    : 'Could not load clients from ERP right now. Check that erp-clients-api is running, then refresh this page.';
            }
        } elseif ($lazyLoad) {
            $customers = collect();
            $total = 0;
        } else {
            $ownerId = crm_owner_filter();
            $customers = $this->crm->getCustomers($perPage, $offset, $search, $ownerId);
            $total = $this->crm->getCustomersCount($search, $ownerId);
        }

        $customers = new LengthAwarePaginator(
            $customers instanceof Collection ? $customers : collect($customers),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $listRoute = request()->routeIs('contacts.index') ? 'contacts.index' : 'support.customers';

        return view('support.customers', [
            'customers' => $customers,
            'total' => $total,
            'clientsGrandTotal' => $clientsGrandTotal,
            'search' => $search,
            'columnFilters' => $columnFilters,
            'system' => $system,
            'page' => $page,
            'clientsSource' => $source,
            'clientsError' => $clientsError ?? null,
            'listRoute' => $listRoute,
            'clientsLazyLoad' => $lazyLoad ?? false,
            'allowedClientSegments' => $allowedClientSegments,
        ]);
    }

    /**
     * JSON API for clients list (used for lazy-load to avoid blocking page).
     */
    public function clientsApi(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $columnFilters = $this->parseColumnFilters($request);
        $hasColumnFilters = $this->hasActiveColumnFilters($columnFilters);
        $erpSearch = $hasColumnFilters ? $this->primarySearchFromColumnFilters($columnFilters) : $search;
        $systemInput = trim((string) $request->get('system', ''));
        if ($systemInput !== '' && ! user_can_access_client_segment($systemInput)) {
            return response()->json([
                'error' => 'You do not have access to the selected client type.',
                'customers' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 25,
            ], 403);
        }
        $system = $systemInput !== '' ? $systemInput : null;
        $page = max(1, (int) $request->get('page', 1));
        $source = config('erp.clients_view_source', 'crm');
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $useErp = in_array($source, ['erp', 'erp_sync', 'erp_http'])
            && (in_array($source, ['erp_sync', 'erp_http']) || $this->erp->isConfigured())
            && ($source !== 'erp_http' || ! empty(config('erp.clients_http_url')));

        $batchAll = $request->boolean('batch_all');
        $apiCacheKey = 'customers:api:' . sha1(json_encode([
            'v' => self::CLIENTS_API_CACHE_VERSION,
            'search' => $erpSearch,
            'filters' => $batchAll ? null : $columnFilters,
            'system' => $system,
            'page' => $batchAll ? 1 : $page,
            'batch_all' => $batchAll,
            'all' => $request->boolean('all'),
            'source' => $source,
            'owner' => crm_owner_filter(),
        ]));
        $cachedPayload = \Illuminate\Support\Facades\Cache::get($apiCacheKey);
        if (is_array($cachedPayload)) {
            return response()->json($cachedPayload);
        }

        if ($useErp && $request->boolean('batch_all') && $hasColumnFilters) {
            $batch = $this->getCachedErpFilterBatch($erpSearch, $system);
            $rows = $this->mapCustomersToApiRows($batch['data'], $source);
            $payload = [
                'customers' => $rows,
                'total' => count($rows),
                'page' => 1,
                'per_page' => count($rows) ?: 25,
                'error' => $batch['error'] ?? null,
                'source' => $source,
                'batch_mode' => true,
                'erp_search' => $erpSearch,
            ];
            if ($useErp) {
                $payload['grand_total'] = $batch['grand_total'] ?? null;
            }
            \Illuminate\Support\Facades\Cache::put($apiCacheKey, $payload, (int) config('performance.cache_ttl.clients_list', 180));

            return response()->json($payload);
        }

        $clientsGrandTotal = null;
        $clientsError = null;
        if ($useErp) {
            try {
                $user = Auth::guard('vtiger')->user();
                if ($user && $this->profileAccess->userIsLimitedToAssignedClients($user) && ! $hasColumnFilters && ! $erpSearch) {
                    $result = $this->profileAccess->fetchAssignedClientsPage((int) $user->id, $system, $perPage, $offset, $this->erp);
                    $customers = $result['data'];
                    $total = $result['total'];
                    $clientsGrandTotal = $result['grand_total'] ?? null;
                    $clientsError = $result['error'] ?? null;
                } elseif ($hasColumnFilters) {
                    $result = $this->getCachedErpFilterBatch($erpSearch, $system);
                    $customers = $this->applyColumnFilters($result['data'], $columnFilters);
                    if ($user && $this->profileAccess->userIsLimitedToAssignedClients($user)) {
                        $customers = $this->profileAccess->filterCustomersToAssignedPolicies($customers, (int) $user->id, $system);
                    }
                    $total = $customers->count();
                    $customers = $customers->slice($offset, $perPage)->values();
                    $clientsError = $result['error'] ?? null;
                    $clientsGrandTotal = $user && $this->profileAccess->userIsLimitedToAssignedClients($user)
                        ? count($this->profileAccess->getAssignedPolicyNumbersForUser((int) $user->id, $system))
                        : ($result['grand_total'] ?? null);
                } else {
                    $result = $this->fetchClientsListResult($perPage, $offset, $erpSearch, $system);
                    $customers = $result['data'];
                    $total = $result['total'];
                    $clientsGrandTotal = $result['grand_total'] ?? null;
                    $clientsError = $result['error'] ?? null;
                    if ($user && $this->profileAccess->userIsLimitedToAssignedClients($user)) {
                        $customers = $this->profileAccess->filterCustomersToAssignedPolicies(
                            $customers instanceof Collection ? $customers : collect($customers),
                            (int) $user->id,
                            $system
                        );
                        $total = count($this->profileAccess->getAssignedPolicyNumbersForUser((int) $user->id, $system));
                        $clientsGrandTotal = $total;
                    }
                }
                if ($clientsError && $customers->isEmpty()) {
                    $erpHttpConfigGap = $source === 'erp_http' && str_contains((string) $clientsError, 'erp-clients-api/.env');
                    if (! $erpHttpConfigGap) {
                        $ownerId = crm_owner_filter();
                        $customers = $this->crm->getCustomers($perPage, $offset, $search, $ownerId);
                        $total = $this->crm->getCustomersCount($search, $ownerId);
                        $clientsGrandTotal = null;
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Clients API ERP load failed', [
                    'system' => $system,
                    'message' => $e->getMessage(),
                ]);
                $customers = collect();
                $total = 0;
                $clientsGrandTotal = null;
                $clientsError = config('app.debug')
                    ? 'Could not load clients: ' . $e->getMessage()
                    : 'Could not load clients from ERP right now. Check that erp-clients-api is running, then refresh this page.';
            }
        } else {
            $ownerId = crm_owner_filter();
            $customers = $this->crm->getCustomers($perPage, $offset, $search, $ownerId);
            $total = $this->crm->getCustomersCount($search, $ownerId);
            $clientsError = null;
            $clientsGrandTotal = null;
        }

        $rows = $this->mapCustomersToApiRows($customers, $source);

        $payload = [
            'customers' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'error' => $clientsError,
            'source' => $source,
        ];
        if ($useErp) {
            $payload['grand_total'] = $clientsGrandTotal;
        }

        \Illuminate\Support\Facades\Cache::put($apiCacheKey, $payload, (int) config('performance.cache_ttl.clients_list', 180));

        return response()->json($payload);
    }

    /**
     * @return array{data: Collection, total: int, error: ?string, grand_total?: mixed}
     */
    private function fetchClientsListResult(int $perPage, int $offset, ?string $erpSearch, ?string $system): array
    {
        $source = config('erp.clients_view_source', 'crm');
        $version = Cache::get('clients_list_version', 0);
        $cacheKey = 'clients_list_' . $version . '_' . sha1(json_encode([
            'v' => self::CLIENTS_API_CACHE_VERSION,
            'source' => $source,
            'perPage' => $perPage,
            'offset' => $offset,
            'search' => $erpSearch ?? '',
            'system' => $system ?? '',
        ]));
        $ttl = (int) config('performance.cache_ttl.clients_list', 180);

        $result = Cache::remember($cacheKey, $ttl, fn () => $this->erp->getClientsForListView($perPage, $offset, $erpSearch, $system));

        return [
            'data' => $result['data'] instanceof Collection ? $result['data'] : collect($result['data']),
            'total' => (int) ($result['total'] ?? 0),
            'error' => $result['error'] ?? null,
            'grand_total' => $result['grand_total'] ?? null,
        ];
    }

    private function findCachedContactByPolicy(string $policy): mixed
    {
        return Cache::remember(
            'crm:contact_by_policy:' . sha1(trim($policy)),
            (int) config('performance.cache_ttl.counts', 300),
            fn () => $this->crm->findContactByPolicyNumber($policy)
        );
    }

    /**
     * Merge CRM contact with ERP client fields so email tab filters use the client's actual address.
     */
    private function buildClientMailLookup(?object $contact, object $erpLookup, string $policy, string $clientEmail): object
    {
        $lookup = new \stdClass();

        if ($contact) {
            foreach ((array) $contact as $key => $value) {
                $lookup->{$key} = $value;
            }
        }

        foreach ([
            'mobile' => $erpLookup->mobile ?? null,
            'phone' => $erpLookup->phone ?? null,
            'firstname' => $erpLookup->firstname ?? null,
            'lastname' => $erpLookup->lastname ?? null,
            'policy_number' => $policy,
            'policy_no' => $policy,
        ] as $field => $value) {
            if ($value !== null && trim((string) $value) !== '') {
                if (! isset($lookup->{$field}) || trim((string) ($lookup->{$field} ?? '')) === '') {
                    $lookup->{$field} = $value;
                }
            }
        }

        if ($clientEmail !== '') {
            $lookup->email = $clientEmail;
            $lookup->email_adr = $clientEmail;
            $lookup->client_email = $clientEmail;
        }

        return $lookup;
    }

    private function resolveAllowedClientSystem(mixed $system): ?string
    {
        $system = trim((string) ($system ?? ''));
        if ($system === '') {
            return null;
        }

        if (! user_can_access_client_segment($system)) {
            return null;
        }

        return $system;
    }

    /**
     * @return array{data: Collection, error: ?string, grand_total: mixed}
     */
    private function getCachedErpFilterBatch(?string $erpSearch, ?string $system): array
    {
        $cacheKey = 'clients:erp_batch:' . sha1(json_encode([
            'v' => self::CLIENTS_API_CACHE_VERSION,
            'search' => $erpSearch ?? '',
            'system' => $system ?? '',
        ]));
        $ttl = (int) config('performance.cache_ttl.clients_list', 120);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, $ttl, function () use ($erpSearch, $system) {
            $result = $this->erp->getClientsForListView(100, 0, $erpSearch, $system);

            return [
                'data' => $result['data'] instanceof Collection ? $result['data'] : collect($result['data']),
                'error' => $result['error'] ?? null,
                'grand_total' => $result['grand_total'] ?? null,
            ];
        });
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $customers
     * @return array<int, array<string, mixed>>
     */
    private function mapCustomersToApiRows(Collection|array $customers, string $source): array
    {
        return collect($customers)->map(function ($c) use ($source) {
            $c = (object) (is_array($c) ? $c : (array) $c);
            $row = (array) $c;
            $policy = trim((string) $this->pickFirstNonEmpty($row, [
                'policy_no', 'policy_number', 'ipol_policy_no', 'pol_policy_no', 'contract_no', 'scheme_no',
                'POLICY_NO', 'POLICY_NUMBER', 'IPOL_POLICY_NO', 'POL_POLICY_NO', 'CONTRACT_NO', 'SCHEME_NO',
            ]));
            $polPreparedBy = trim((string) $this->pickFirstNonEmpty($row, [
                'pol_prepared_by', 'POL_PREPARED_BY', 'bra_manager', 'unit_manar',
            ]));
            $intermediary = trim((string) $this->pickFirstNonEmpty($row, [
                'intermediary', 'INTERMEDIARY', 'intermediary_name', 'agency', 'agn_name', 'agnName',
            ]));
            $lifeAssured = trim((string) $this->pickFirstNonEmpty($row, [
                'life_assur', 'life_assured', 'lifeAssur', 'lifeAssured',
                'client_name', 'CLIENT_NAME', 'name', 'NAME', 'member_name', 'mem_surname',
            ]));
            $product = trim((string) $this->pickFirstNonEmpty($row, [
                'product', 'PRODUCT', 'prod_desc', 'PROD_DESC', 'prod_sht_desc', 'scheme_name', 'SCHEME_NAME',
            ]));
            $status = trim((string) $this->pickFirstNonEmpty($row, [
                'status', 'STATUS', 'mendr_status', 'MENDR_STATUS', 'endr_status', 'ENDR_STATUS', 'policy_status', 'POLICY_STATUS',
            ]));
            $email = $this->pickFirstNonEmpty($row, ['email', 'EMAIL', 'email_adr', 'EMAIL_ADR']) ?? null;
            $mobile = $this->pickFirstNonEmpty($row, ['mobile', 'phone', 'phone_no', 'PHONE_NO']) ?? null;

            $isErp = ($c->_erp_source ?? false) && in_array($source, ['erp_sync', 'erp_http']);
            $lifeSystem = $c->life_system ?? $this->erp->getLifeSystemFromProduct($product ?: null);

            return [
                'policy' => $policy,
                'policy_no' => $policy,
                'policy_number' => $policy,
                'pol_prepared_by' => $polPreparedBy !== '' ? $polPreparedBy : '—',
                'intermediary' => $intermediary !== '' ? $intermediary : '—',
                'life_assur' => $lifeAssured !== '' ? $lifeAssured : '—',
                'product' => $product !== '' ? $product : '—',
                'life_system' => $lifeSystem,
                'status' => $status !== '' ? $status : '—',
                'is_erp' => $isErp,
                'name' => trim(($c->firstname ?? '') . ' ' . ($c->lastname ?? '')) ?: '—',
                'email' => personal_email_only($email) ?? '—',
                'mobile' => $mobile ?: '—',
            ];
        })->values()->all();
    }

    /**
     * @param  array<string,mixed>  $row
     * @param  array<int,string>  $keys
     */
    private function pickFirstNonEmpty(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $value = $row[$key];
            if ($value !== null && trim((string) $value) !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array{policy:string,prepared:string,intermediary:string,name:string,product:string,status:string}
     */
    private function parseColumnFilters(Request $request): array
    {
        $legacy = trim((string) $request->get('search', ''));

        return [
            'policy' => trim((string) $request->get('f_policy', '')),
            'prepared' => trim((string) $request->get('f_prepared', '')),
            'intermediary' => trim((string) $request->get('f_intermediary', '')),
            'name' => trim((string) ($request->get('f_name', '') ?: $legacy)),
            'product' => trim((string) $request->get('f_product', '')),
            'status' => trim((string) $request->get('f_status', '')),
        ];
    }

    /**
     * @param  array{policy:string,prepared:string,intermediary:string,name:string,product:string,status:string}  $filters
     */
    private function hasActiveColumnFilters(array $filters): bool
    {
        foreach ($filters as $value) {
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{policy:string,prepared:string,intermediary:string,name:string,product:string,status:string}  $filters
     */
    private function primarySearchFromColumnFilters(array $filters): ?string
    {
        foreach (['policy', 'name', 'prepared', 'intermediary'] as $key) {
            $value = trim($filters[$key] ?? '');
            if ($value !== '' && mb_strlen($value) >= 2) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, mixed>  $customers
     * @param  array{policy:string,prepared:string,intermediary:string,name:string,product:string,status:string}  $filters
     * @return Collection<int, mixed>
     */
    private function applyColumnFilters(Collection $customers, array $filters): Collection
    {
        if (! $this->hasActiveColumnFilters($filters)) {
            return $customers;
        }

        return $customers->filter(function ($customer) use ($filters) {
            $row = (array) (is_array($customer) ? $customer : (array) $customer);

            return $this->rowMatchesColumnFilters($row, $filters);
        })->values();
    }

    /**
     * @param  array<string,mixed>  $row
     * @param  array{policy:string,prepared:string,intermediary:string,name:string,product:string,status:string}  $filters
     */
    private function rowMatchesColumnFilters(array $row, array $filters): bool
    {
        $map = [
            'policy' => ['policy_no', 'policy_number', 'ipol_policy_no', 'pol_policy_no', 'POLICY_NO', 'POLICY_NUMBER'],
            'prepared' => ['pol_prepared_by', 'POL_PREPARED_BY', 'bra_manager', 'unit_manar'],
            'intermediary' => ['intermediary', 'INTERMEDIARY', 'intermediary_name', 'agency', 'agn_name'],
            'name' => ['life_assur', 'life_assured', 'client_name', 'CLIENT_NAME', 'name', 'NAME', 'member_name', 'mem_surname'],
            'product' => ['product', 'PRODUCT', 'prod_desc', 'PROD_DESC', 'scheme_name', 'SCHEME_NAME'],
            'status' => ['status', 'STATUS', 'mendr_status', 'MENDR_STATUS', 'policy_status', 'POLICY_STATUS'],
        ];

        foreach ($filters as $key => $term) {
            if ($term === '') {
                continue;
            }
            $haystack = strtolower((string) ($this->pickFirstNonEmpty($row, $map[$key]) ?? ''));

            if (! str_contains($haystack, strtolower($term))) {
                return false;
            }
        }

        return true;
    }

    public function updateConsent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'policy' => 'required|string|max:64',
            'consent' => 'required|boolean',
        ]);

        $policy = trim($validated['policy']);
        if ($policy === '') {
            return response()->json(['error' => 'Policy number required.'], 422);
        }

        try {
            $user = Auth::guard('vtiger')->user();
            $record = ErpClientConsent::setForPolicy($policy, (bool) $validated['consent'], $user);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'policy' => $record->policy_number,
            'consent_granted' => $record->consent_granted,
            'consented_at' => $record->consented_at?->toIso8601String(),
            'consented_by_name' => $record->consented_by_name,
            'updated_by_name' => $record->updated_by_name,
        ]);
    }

    public function storeComment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'policy' => 'required|string|max:64',
            'body' => 'required|string|max:10000',
        ]);

        $policy = trim($validated['policy']);
        if ($policy === '') {
            return redirect()->route('support.customers')->with('error', 'Policy number required.');
        }

        if (! user_can_access_client_policy($policy)) {
            return redirect()->route('support.customers')->with('error', 'You do not have access to this client.');
        }

        if (! ErpClientComment::tableExists()) {
            return redirect()->back()->with('error', 'Comments are not available yet. Run database migrations.');
        }

        [$userId, $authorName] = $this->resolveStaffAuthor();

        ErpClientComment::create([
            'policy_number' => $policy,
            'user_id' => $userId,
            'author_name' => $authorName,
            'body' => $validated['body'],
        ]);

        return redirect()
            ->to(route('support.clients.show', ['policy' => $policy, 'tab' => 'updates']) . '#client-comments')
            ->with('success', 'Comment posted.');
    }

    public function uploadDocument(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'policy' => 'required|string|max:64',
            'title' => 'nullable|string|max:255',
            'document' => 'required|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,gif,webp',
        ]);

        $policy = trim($validated['policy']);
        if ($policy === '') {
            return redirect()->route('support.customers')->with('error', 'Policy number required.');
        }

        if (! user_can_access_client_policy($policy)) {
            return redirect()->route('support.customers')->with('error', 'You do not have access to this client.');
        }

        if (! ErpClientDocument::tableExists()) {
            return redirect()->back()->with('error', 'Documents are not available yet. Run database migrations.');
        }

        [$userId, $authorName] = $this->resolveStaffAuthor();
        $file = $request->file('document');
        $storagePath = $file->store('erp-client-documents/' . $policy, 'public');

        ErpClientDocument::create([
            'policy_number' => $policy,
            'title' => trim((string) ($validated['title'] ?? '')) ?: null,
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $storagePath,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'uploaded_by_user_id' => $userId,
            'uploaded_by_name' => $authorName,
        ]);

        return redirect()
            ->to(route('support.clients.show', ['policy' => $policy, 'tab' => 'summary']) . '#client-documents')
            ->with('success', 'Document uploaded.');
    }

    public function downloadDocument(Request $request, int $document): StreamedResponse|RedirectResponse
    {
        if (! ErpClientDocument::tableExists()) {
            abort(404);
        }

        $doc = ErpClientDocument::query()->findOrFail($document);
        $policy = trim((string) $doc->policy_number);

        if ($policy === '' || ! user_can_access_client_policy($policy)) {
            abort(403);
        }

        if (! $doc->storage_path || ! Storage::disk('public')->exists($doc->storage_path)) {
            return redirect()->back()->with('error', 'File not found.');
        }

        return Storage::disk('public')->download($doc->storage_path, $doc->original_filename);
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    private function resolveStaffAuthor(): array
    {
        $authUser = Auth::guard('vtiger')->user();
        if (! $authUser) {
            return [null, 'Staff'];
        }

        $userId = (int) ($authUser->id ?? $authUser->getAuthIdentifier());
        $authorName = trim(($authUser->first_name ?? '') . ' ' . ($authUser->last_name ?? ''))
            ?: ($authUser->user_name ?? 'User');

        return [$userId, $authorName];
    }

    private function policyNumberFromErpRow(array|object $row): ?string
    {
        $data = (array) $row;
        $policyNo = trim((string) ($data['policy_no'] ?? $data['policy_number'] ?? $data['POLICY_NO'] ?? $data['POLICY_NUMBER'] ?? ''));
        if ($policyNo === '' || looks_like_kra_pin($policyNo)) {
            return null;
        }

        return $policyNo;
    }

    /**
     * @return array<int, string>
     */
    private function clientPolicyNumbersForPremiums(object $erpLookup, string $currentPolicy): array
    {
        $currentPolicy = trim($currentPolicy);
        $policyNumbers = $currentPolicy !== '' ? [$currentPolicy] : [];

        $result = $this->erp->getPoliciesForContact($erpLookup, 50);
        foreach ($result['data'] ?? [] as $policyRow) {
            $policyNo = $this->policyNumberFromErpRow($policyRow);
            if ($policyNo) {
                $policyNumbers[] = $policyNo;
            }
        }

        return array_values(array_unique($policyNumbers));
    }

    private function countClientPremiumReceipts(object $erpLookup, string $currentPolicy): int
    {
        try {
            $receiptSource = app(ReceiptDataSource::class);
            $total = 0;
            foreach ($this->clientPolicyNumbersForPremiums($erpLookup, $currentPolicy) as $policyNo) {
                $total += count($receiptSource->search($policyNo, 'policy'));
            }

            return $total;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @return array{
     *   policyPremiums: array<int, array<string, mixed>>,
     *   selectedPremiumPolicy: string,
     *   premiumViewAll: bool,
     *   premiums: array<int, array<string, mixed>>,
     *   premiumsError: ?string,
     *   policiesError: ?string,
     *   premiumsCount: int,
     *   policies: array<int, array<string, mixed>>
     * }
     */
    private function buildClientPremiumPoliciesData(object $erpLookup, string $currentPolicy, Request $request): array
    {
        $currentPolicy = trim($currentPolicy);
        $result = $this->erp->getPoliciesForContact($erpLookup, 50);
        $policiesError = $result['error'] ?? null;

        $policyCatalog = [];
        if ($currentPolicy !== '') {
            $policyCatalog[$currentPolicy] = [
                'policy_no' => $currentPolicy,
                'name' => trim(($erpLookup->firstname ?? '') . ' ' . ($erpLookup->lastname ?? '')),
                'product' => null,
                'is_current' => true,
            ];
        }

        foreach ($result['data'] ?? [] as $policyRow) {
            $policyNo = $this->policyNumberFromErpRow($policyRow);
            if (! $policyNo) {
                continue;
            }
            $data = (array) $policyRow;
            $policyCatalog[$policyNo] = [
                'policy_no' => $policyNo,
                'name' => $data['name'] ?? $data['client_name'] ?? $data['life_assur'] ?? $data['CLIENT_NAME'] ?? '—',
                'product' => $data['product'] ?? $data['prod_desc'] ?? $data['PRODUCT'] ?? '—',
                'is_current' => $policyNo === $currentPolicy,
            ];
        }

        uasort($policyCatalog, function (array $a, array $b): int {
            if (($a['is_current'] ?? false) !== ($b['is_current'] ?? false)) {
                return ($b['is_current'] ?? false) <=> ($a['is_current'] ?? false);
            }

            return strcmp((string) $a['policy_no'], (string) $b['policy_no']);
        });

        $selectedPolicy = trim((string) $request->get('premium_policy', ''));
        if ($selectedPolicy === '' || ! isset($policyCatalog[$selectedPolicy])) {
            $selectedPolicy = $currentPolicy !== '' ? $currentPolicy : (string) (array_key_first($policyCatalog) ?: '');
        }

        $viewAll = $request->get('premium_view') === 'all'
            || (! $request->filled('premium_policy') && count($policyCatalog) > 1);

        $receiptSource = app(ReceiptDataSource::class);
        $oracleError = null;
        $policyPremiums = [];
        $totalReceipts = 0;

        foreach ($policyCatalog as $policyNo => $meta) {
            try {
                $receipts = $receiptSource->search($policyNo, 'policy');
                $count = count($receipts);
                $totalReceipts += $count;
                $policyPremiums[] = array_merge($meta, [
                    'receipts' => $receipts,
                    'receipt_count' => $count,
                    'error' => null,
                ]);
            } catch (\Throwable $e) {
                $msg = oracle_oci8_available()
                    ? $e->getMessage()
                    : 'Premium receipts need Oracle OCI8 on this server, or enable RECEIPT_DEMO=true for local testing.';
                $oracleError ??= $msg;
                $policyPremiums[] = array_merge($meta, [
                    'receipts' => [],
                    'receipt_count' => 0,
                    'error' => $msg,
                ]);
            }
        }

        $premiums = [];
        $premiumsError = $oracleError;
        foreach ($policyPremiums as $block) {
            if (($block['policy_no'] ?? '') === $selectedPolicy) {
                $premiums = $block['receipts'];
                if (! empty($block['error'])) {
                    $premiumsError = $block['error'];
                }
                break;
            }
        }

        return [
            'policyPremiums' => $policyPremiums,
            'selectedPremiumPolicy' => $selectedPolicy,
            'premiumViewAll' => $viewAll,
            'premiums' => $premiums,
            'premiumsError' => $premiumsError,
            'policiesError' => $policiesError,
            'premiumsCount' => $totalReceipts,
            'policies' => array_values($policyCatalog),
        ];
    }
}
