<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Exports\ComplaintsExport;
use App\Services\AutoComplaintFromEmailService;
use App\Services\ComplaintClassificationService;
use App\Services\CrmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        return view('compliance.complaints-create');
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
            'assigned_to' => 'nullable|string|max:255',
        ]);

        $validated['complaint_ref'] = Complaint::generateRef();
        $validated['status'] = $validated['status'] ?? 'Received';
        $validated['priority'] = $validated['priority'] ?? 'Medium';
        $validated['contact_id'] = $validated['contact_id'] ?: null;
        $validated['register_status'] = Complaint::REGISTER_ACTIVE;
        $validated['classification_score'] = 95;
        $validated['classification_reason'] = 'Manually registered';

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
        return view('compliance.complaints-edit', ['complaint' => $complaint]);
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
            'assigned_to' => 'nullable|string|max:255',
            'date_resolved' => 'nullable|date',
            'resolution_notes' => 'nullable|string|max:5000',
        ]);

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
