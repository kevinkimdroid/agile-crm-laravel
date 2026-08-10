<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\VtigerUser;
use App\Exports\ComplaintsExport;
use App\Services\AutoComplaintFromEmailService;
use App\Services\ComplaintClassificationService;
use App\Services\CrmService;
use App\Services\ErpClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ComplaintController extends Controller
{
    public function __construct(
        protected CrmService $crm,
        protected ComplaintClassificationService $classifier,
    ) {}

    public function index(Request $request): View
    {
        $registerFilter = $request->get('register', 'complaints');
        $hasRegisterColumn = Schema::connection((new Complaint)->getConnectionName())->hasColumn('complaints', 'register_status');

        $query = Complaint::query();

        if ($hasRegisterColumn) {
            match ($registerFilter) {
                'review' => $query->where('register_status', Complaint::REGISTER_REVIEW),
                'excluded' => $query->where('register_status', Complaint::REGISTER_EXCLUDED),
                'all' => null,
                default => $query->where('register_status', Complaint::REGISTER_ACTIVE),
            };
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('complaint_ref', 'like', $term)
                    ->orWhere('complainant_name', 'like', $term)
                    ->orWhere('policy_number', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('nature', 'like', $term)
                    ->orWhere('complainant_email', 'like', $term);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('nature')) {
            $query->where('nature', $request->nature);
        }

        $complaints = $query->orderByDesc('date_received')->orderByDesc('id')->paginate(20)->withQueryString();

        if ($hasRegisterColumn) {
            foreach ($complaints as $complaint) {
                if ($complaint->source === 'Email' && $complaint->classification_score === null) {
                    $this->classifier->classifyComplaint($complaint);
                }
            }
        }

        $statsQuery = Complaint::query();
        $stats = [
            'total' => $hasRegisterColumn
                ? (clone $statsQuery)->where('register_status', Complaint::REGISTER_ACTIVE)->count()
                : Complaint::count(),
            'open' => (clone $statsQuery)->when($hasRegisterColumn, fn ($q) => $q->where('register_status', Complaint::REGISTER_ACTIVE))
                ->whereIn('status', ['Received', 'Under Investigation', 'Pending Response'])->count(),
            'review' => $hasRegisterColumn ? (clone $statsQuery)->where('register_status', Complaint::REGISTER_REVIEW)->count() : 0,
            'excluded' => $hasRegisterColumn ? (clone $statsQuery)->where('register_status', Complaint::REGISTER_EXCLUDED)->count() : 0,
            'resolved' => Complaint::where('status', 'Resolved')->count(),
            'closed' => Complaint::whereIn('status', ['Closed', 'Escalated to IRA'])->count(),
        ];

        $byStatus = [
            'Received' => Complaint::where('status', 'Received')->count(),
            'Under Investigation' => Complaint::where('status', 'Under Investigation')->count(),
            'Resolved' => $stats['resolved'],
            'Closed' => $stats['closed'],
        ];

        return view('compliance.complaints', [
            'complaints' => $complaints,
            'stats' => $stats,
            'registerFilter' => $registerFilter,
            'hasRegisterColumn' => $hasRegisterColumn,
            'byStatus' => $byStatus,
            'classifier' => $this->classifier,
        ]);
    }

    public function create(): View
    {
        return view('compliance.complaints-create', [
            'users' => $this->assignableUsers(),
            'agents' => \App\Models\Agent::forDropdown(),
        ]);
    }

    /** Active staff who can be assigned a complaint. */
    protected function assignableUsers(): Collection
    {
        try {
            return VtigerUser::query()
                ->where('status', 'Active')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'user_name'])
                ->map(fn ($u) => (object) [
                    'id' => $u->id,
                    'name' => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: $u->user_name,
                ]);
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * JSON autocomplete for ERP clients (returns policy number + contact details).
     * Used by the complaint register "Client / Policy" dropdown.
     */
    public function lookupClients(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        // Locally-created clients (POC) are searched first so they always appear,
        // even when the ERP API is unavailable. With no query, this returns the
        // most recent local clients so the dropdown shows options on open.
        $local = $this->lookupLocalClients($q);

        // Only hit the (broad) ERP search once there is a meaningful query.
        $erp = collect();
        if (strlen($q) >= 2 && ! empty(config('erp.clients_http_url'))) {
            try {
                $res = app(ErpClientService::class)->getClientsFromHttpApi(15, 0, $q, 20);
                $rows = $res['data'] instanceof Collection ? $res['data'] : collect($res['data'] ?? []);
                $erp = $rows->map(function ($r) {
                    $name = trim((string) ($r->life_assur ?? $r->client_name ?? ''));

                    return [
                        'policy_no' => trim((string) ($r->policy_no ?? $r->policy_number ?? '')),
                        'name' => $name !== '' ? \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($name)) : '',
                        'email' => trim((string) ($r->client_email ?? $r->email_adr ?? $r->email ?? '')),
                        'phone' => trim((string) ($r->client_phone ?? $r->phone ?? $r->mobile ?? '')),
                        'product' => trim((string) ($r->product ?? '')),
                        'source' => 'ERP',
                    ];
                })->filter(fn ($r) => $r['policy_no'] !== '' || $r['name'] !== '')->values();
            } catch (\Throwable $e) {
                // Ignore ERP errors — local results (if any) are still returned.
            }
        }

        $results = $local->concat($erp)
            ->unique(fn ($r) => ($r['policy_no'] ?? '').'|'.($r['name'] ?? ''))
            ->take(25)
            ->values();

        return response()->json(['results' => $results]);
    }

    /** Search locally-created clients for the complaint client/policy lookup. */
    protected function lookupLocalClients(string $q): Collection
    {
        if (! \App\Models\Client::tableExists()) {
            return collect();
        }

        try {
            $like = '%'.$q.'%';

            return \App\Models\Client::query()
                ->where(fn ($w) => $w->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('policy_no', 'like', $like)
                    ->orWhere('id_no', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like))
                ->orderByDesc('id')
                ->limit(15)
                ->get()
                ->map(fn ($c) => [
                    'policy_no' => (string) $c->policy_no,
                    'name' => $c->fullName(),
                    'email' => (string) ($c->email ?? ''),
                    'phone' => (string) ($c->phone ?? ''),
                    'product' => (string) ($c->product ?? ''),
                    'source' => 'Local',
                ]);
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * JSON autocomplete for prospects (Vtiger contacts).
     * Used by the complaint register "Prospect" dropdown.
     */
    public function lookupProspects(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        try {
            $query = DB::connection('vtiger')
                ->table('vtiger_contactdetails as c')
                ->join('vtiger_crmentity as e', 'c.contactid', '=', 'e.crmid')
                ->where('e.deleted', 0)
                ->whereIn('e.setype', ['Contacts', 'Contact']);

            if (strlen($q) >= 1) {
                $term = '%'.$q.'%';
                $query->where(function ($w) use ($term) {
                    $w->where('c.firstname', 'like', $term)
                        ->orWhere('c.lastname', 'like', $term)
                        ->orWhere('c.email', 'like', $term)
                        ->orWhere('c.mobile', 'like', $term)
                        ->orWhere('c.phone', 'like', $term);
                });
                $query->orderBy('c.lastname');
            } else {
                // No query: show the most recently created prospects as initial options.
                $query->orderByDesc('e.crmid');
            }

            $rows = $query->limit(15)
                ->get(['c.contactid as id', 'c.firstname', 'c.lastname', 'c.email', 'c.mobile', 'c.phone']);

            $results = $rows->map(fn ($r) => [
                'id' => (int) $r->id,
                'name' => trim(($r->firstname ?? '').' '.($r->lastname ?? '')) ?: ('Prospect #'.$r->id),
                'email' => trim((string) ($r->email ?? '')),
                'phone' => trim((string) ($r->mobile ?? $r->phone ?? '')),
            ])->values();

            return response()->json(['results' => $results]);
        } catch (\Throwable $e) {
            return response()->json(['results' => [], 'error' => 'Lookup failed.']);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date_received' => 'required|date',
            'complainant_name' => 'required|string|max:255',
            'complainant_phone' => 'nullable|string|max:50',
            'complainant_email' => 'nullable|email|max:255',
            'contact_id' => 'nullable|integer',
            'policy_number' => 'nullable|string|max:64',
            'nature' => 'nullable|string|max:100',
            'description' => 'required|string|max:5000',
            'source' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'priority' => 'nullable|string|max:20',
            'assigned_type' => 'nullable|in:user,agent',
            'assigned_user' => 'nullable|string|max:255',
            'assigned_agent' => 'nullable|string|max:255',
        ]);

        $validated['complaint_ref'] = Complaint::generateRef();
        $validated['status'] = $validated['status'] ?? 'Received';
        $validated['priority'] = $validated['priority'] ?? 'Medium';
        $validated['contact_id'] = $validated['contact_id'] ?: null;
        $validated['assigned_to'] = $this->resolveAssignee($request);
        $validated['register_status'] = Complaint::REGISTER_ACTIVE;
        $validated['classification_score'] = 95;
        $validated['classification_reason'] = 'Manually registered';
        unset($validated['assigned_type'], $validated['assigned_user'], $validated['assigned_agent']);

        Complaint::create($validated);

        return redirect()->route('compliance.complaints.index')->with('success', 'Complaint registered.');
    }

    public function show(Complaint $complaint): View
    {
        $contact = $complaint->contact_id ? $this->crm->getContact($complaint->contact_id) : null;
        $cleanDescription = AutoComplaintFromEmailService::cleanDescriptionForExport($complaint->description);

        return view('compliance.complaints-show', [
            'complaint' => $complaint,
            'contact' => $contact,
            'cleanDescription' => $cleanDescription,
        ]);
    }

    public function edit(Complaint $complaint): View
    {
        return view('compliance.complaints-edit', [
            'complaint' => $complaint,
            'users' => $this->assignableUsers(),
            'agents' => \App\Models\Agent::forDropdown(),
        ]);
    }

    /**
     * Resolve "Assigned To" from the User / Agent choice on the form.
     * Stored as "User: Name" or "Agent: Name (CODE)" for clarity on the register.
     */
    protected function resolveAssignee(Request $request): ?string
    {
        $type = $request->input('assigned_type');
        if ($type === 'user') {
            $name = trim((string) $request->input('assigned_user', ''));

            return $name !== '' ? 'User: '.$name : null;
        }
        if ($type === 'agent') {
            $name = trim((string) $request->input('assigned_agent', ''));

            return $name !== '' ? 'Agent: '.$name : null;
        }

        return null;
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'date_received' => 'required|date',
            'complainant_name' => 'required|string|max:255',
            'complainant_phone' => 'nullable|string|max:50',
            'complainant_email' => 'nullable|email|max:255',
            'contact_id' => 'nullable|integer',
            'policy_number' => 'nullable|string|max:64',
            'nature' => 'nullable|string|max:100',
            'description' => 'required|string|max:5000',
            'source' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'priority' => 'nullable|string|max:20',
            'assigned_type' => 'nullable|in:user,agent',
            'assigned_user' => 'nullable|string|max:255',
            'assigned_agent' => 'nullable|string|max:255',
            'date_resolved' => 'nullable|date',
            'resolution_notes' => 'nullable|string|max:5000',
        ]);

        $validated['assigned_to'] = $this->resolveAssignee($request);
        unset($validated['assigned_type'], $validated['assigned_user'], $validated['assigned_agent']);

        $complaint->update($validated);

        return redirect()->route('compliance.complaints.show', $complaint)->with('success', 'Complaint updated.');
    }

    public function updateRegisterStatus(Request $request, Complaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'register_status' => 'required|in:active,review,excluded',
        ]);

        $complaint->update(['register_status' => $validated['register_status']]);

        $message = match ($validated['register_status']) {
            Complaint::REGISTER_ACTIVE => 'Marked as a complaint in the register.',
            Complaint::REGISTER_REVIEW => 'Moved to needs review.',
            Complaint::REGISTER_EXCLUDED => 'Removed from the complaint register (not a complaint).',
        };

        return redirect()->back()->with('success', $message);
    }

    public function destroy(Complaint $complaint): RedirectResponse
    {
        $complaint->delete();

        return redirect()->route('compliance.complaints.index')->with('success', 'Complaint deleted.');
    }

    public function export(Request $request)
    {
        $hasRegisterColumn = Schema::connection((new Complaint)->getConnectionName())->hasColumn('complaints', 'register_status');

        $query = Complaint::query();

        if ($hasRegisterColumn && $request->get('register', 'complaints') !== 'all') {
            $query->where('register_status', Complaint::REGISTER_ACTIVE);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('complaint_ref', 'like', $term)
                    ->orWhere('complainant_name', 'like', $term)
                    ->orWhere('policy_number', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('nature', 'like', $term);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('nature')) {
            $query->where('nature', $request->nature);
        }

        $complaints = $query->orderByDesc('date_received')->orderByDesc('id')->limit(10000)->get();

        $rows = $complaints->map(function ($c) {
            $contactName = null;
            if ($c->contact_id) {
                try {
                    $contact = $this->crm->getContact((int) $c->contact_id);
                    $contactName = $contact ? trim(($contact->firstname ?? '') . ' ' . ($contact->lastname ?? '')) : null;
                } catch (\Throwable $e) {
                    $contactName = null;
                }
            }

            return [
                $c->complaint_ref ?? '',
                $c->date_received?->format('Y-m-d') ?? '',
                $c->complainant_name ?? '',
                $c->complainant_phone ?? '',
                $c->complainant_email ?? '',
                $contactName ?? '',
                $c->policy_number ?? '',
                $c->nature ?? '',
                $c->source ?? '',
                $c->status ?? '',
                $c->register_status ?? '',
                $c->classification_score ?? '',
                $c->priority ?? '',
                $c->assigned_to ?? '',
                $c->date_resolved?->format('Y-m-d') ?? '',
                AutoComplaintFromEmailService::cleanDescriptionForExport($c->description),
                AutoComplaintFromEmailService::cleanDescriptionForExport($c->resolution_notes),
                $c->created_at?->format('Y-m-d H:i') ?? '',
                $c->updated_at?->format('Y-m-d H:i') ?? '',
            ];
        })->toArray();

        $filename = 'complaints-register-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new ComplaintsExport($rows), $filename);
    }
}
