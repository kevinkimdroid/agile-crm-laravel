<?php

namespace App\Http\Controllers;

use App\Models\VtigerUser;
use App\Services\CrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivityController extends Controller
{
    /** @var CrmService */
    protected $crm;

    public function __construct(CrmService $crm)
    {
        $this->crm = $crm;
    }

    public function index(Request $request): View
    {
        $overdueOnly = $request->boolean('overdue');
        $activityType = $request->get('type');
        $status = $request->get('status');
        $search = $request->get('search');
        $contactId = $request->filled('contact_id') ? (int) $request->get('contact_id') : null;
        $ticketId = $request->filled('ticket_id') ? (int) $request->get('ticket_id') : null;
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;
        $sortBy = $request->get('sort', 'date_start');
        $sortDir = $request->get('dir', 'desc');

        $ownerScope = crm_owner_filter();
        $vtigerUser = Auth::guard('vtiger')->user();
        $assignedToFilter = null;
        if ($vtigerUser?->isAdministrator() && $request->filled('assigned_to')) {
            $aid = (int) $request->get('assigned_to');
            $assignedToFilter = $aid > 0 ? $aid : null;
        }

        $overdueScope = resolve_overdue_activity_scope($request->get('scope'));
        $overdueTotal = 0;

        $activities = collect();
        $assigneeSummary = collect();
        if ($overdueOnly) {
            $activities = $this->crm->getOverdueActivitiesList($perPage, $offset, $overdueScope['ownerId'], $search);
            $overdueTotal = $this->crm->countOverdueActivities($overdueScope['ownerId'], $search);
        } elseif ($contactId || $ticketId) {
            $activities = $this->crm->getActivities($perPage, $offset, $activityType, $status, $search, $contactId, $ticketId, $ownerScope, $assignedToFilter, $sortBy, $sortDir);
            $assigneeSummary = $this->crm->getActivitiesAssigneeSummary($activityType, $status, $search, $contactId, $ticketId, $ownerScope);
        }

        try {
            $users = VtigerUser::on('vtiger')->where('status', 'Active')->orderBy('first_name')->orderBy('last_name')->get();
        } catch (\Throwable $e) {
            $users = collect();
        }
        $contacts = $this->crm->getContacts(200, 0, $ownerScope);
        $tickets = $contactId ? $this->crm->getTicketsForContact($contactId, 200, $ownerScope) : collect();

        return view('activities.index', [
            'activities' => $activities,
            'activityType' => $activityType,
            'status' => $status,
            'search' => $search,
            'contactId' => $contactId,
            'ticketId' => $ticketId,
            'assignedToFilter' => $assignedToFilter,
            'assigneeSummary' => $assigneeSummary,
            'canFilterActivitiesByAssignee' => (bool) $vtigerUser?->isAdministrator(),
            'users' => $users,
            'contacts' => $contacts,
            'tickets' => $tickets,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'overdueOnly' => $overdueOnly,
            'overdueScope' => $overdueScope['scope'],
            'canViewAllOverdue' => $overdueScope['canViewAll'],
            'overdueTotal' => $overdueTotal,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): View
    {
        $type = $request->get('type', 'Event');
        $relatedTo = $request->filled('related_to') ? (int) $request->get('related_to') : null;
        $lockRelated = $request->boolean('lock_related') && $relatedTo;
        $returnTo = $this->normalizeReturnTo($request->get('return_to'));

        $relatedContact = null;
        if ($relatedTo) {
            $relatedContact = $this->crm->getContact($relatedTo);
            if (! $relatedContact) {
                $relatedTo = null;
                $lockRelated = false;
            }
        }

        $contacts = $lockRelated ? collect() : $this->crm->getContacts(200, 0, crm_owner_filter());

        return view('activities.create', [
            'type' => $type,
            'contacts' => $contacts,
            'relatedTo' => $relatedTo,
            'relatedContact' => $relatedContact,
            'lockRelated' => $lockRelated,
            'returnTo' => $returnTo,
            'users' => $this->activeVtigerUsers(),
        ]);
    }

    public function edit(Request $request, int $activity): View|RedirectResponse
    {
        $record = $this->crm->getActivity($activity);
        if (! $record) {
            return redirect()->route('activities.index')->with('error', 'Activity not found.');
        }

        if (! $this->canManageActivity($record)) {
            return redirect()->back()->with('error', 'You do not have permission to edit this activity.');
        }

        $relatedTo = (int) ($record->related_to_id ?? 0) ?: null;
        $lockRelated = $request->boolean('lock_related') && $relatedTo;
        $returnTo = $this->normalizeReturnTo($request->get('return_to'));

        $relatedContact = $relatedTo ? $this->crm->getContact($relatedTo) : null;
        $contacts = $lockRelated ? collect() : $this->crm->getContacts(200, 0, crm_owner_filter());

        return view('activities.edit', [
            'activity' => $record,
            'contacts' => $contacts,
            'relatedTo' => $relatedTo,
            'relatedContact' => $relatedContact,
            'lockRelated' => $lockRelated,
            'returnTo' => $returnTo,
            'users' => $this->activeVtigerUsers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::guard('vtiger')->user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to schedule activities.');
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'activitytype' => 'required|in:Task,Event,Meeting,Call',
            'date_start' => 'required|date',
            'due_date' => 'nullable|date',
            'time_start' => 'nullable|string|max:20',
            'time_end' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:50',
            'priority' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:10000',
            'related_to' => 'nullable|integer',
            'ticket_id' => 'nullable|integer',
            'assigned_to' => 'nullable|integer',
            'lock_related' => 'nullable|boolean',
            'locked_related_to' => 'nullable|integer',
            'return_to' => 'nullable|string|max:500',
        ]);

        if ($request->boolean('lock_related') && ! empty($validated['locked_related_to'])) {
            $validated['related_to'] = (int) $validated['locked_related_to'];
        }

        $validated['due_date'] = $validated['due_date'] ?? $validated['date_start'];
        $ownerId = !empty($validated['assigned_to']) ? (int) $validated['assigned_to'] : $user->id;

        $id = $this->crm->createActivity($validated, $ownerId);
        if ($id) {
            return $this->redirectAfterActivitySave($validated, 'Activity scheduled successfully.');
        }

        return back()->withInput()->with('error', 'Failed to create activity.');
    }

    public function update(Request $request, int $activity): RedirectResponse
    {
        $user = Auth::guard('vtiger')->user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Please log in to update activities.');
        }

        $record = $this->crm->getActivity($activity);
        if (! $record) {
            return redirect()->route('activities.index')->with('error', 'Activity not found.');
        }

        if (! $this->canManageActivity($record)) {
            return redirect()->back()->with('error', 'You do not have permission to edit this activity.');
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'activitytype' => 'required|in:Task,Event,Meeting,Call',
            'date_start' => 'required|date',
            'due_date' => 'nullable|date',
            'time_start' => 'nullable|string|max:20',
            'time_end' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:50',
            'priority' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:10000',
            'related_to' => 'nullable|integer',
            'assigned_to' => 'nullable|integer',
            'lock_related' => 'nullable|boolean',
            'locked_related_to' => 'nullable|integer',
            'return_to' => 'nullable|string|max:500',
        ]);

        if ($request->boolean('lock_related') && ! empty($validated['locked_related_to'])) {
            $validated['related_to'] = (int) $validated['locked_related_to'];
        }

        $validated['due_date'] = $validated['due_date'] ?? $validated['date_start'];
        $modifierId = (int) $user->id;

        if ($this->crm->updateActivity($activity, $validated, $modifierId)) {
            return $this->redirectAfterActivitySave($validated, 'Activity updated successfully.');
        }

        return back()->withInput()->with('error', 'Failed to update activity.');
    }

    public function destroy(Request $request, int $activity): RedirectResponse
    {
        $user = Auth::guard('vtiger')->user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Please log in to delete activities.');
        }

        $record = $this->crm->getActivity($activity);
        if (! $record) {
            return redirect()->route('activities.index')->with('error', 'Activity not found.');
        }

        if (! $this->canManageActivity($record)) {
            return redirect()->back()->with('error', 'You do not have permission to delete this activity.');
        }

        $returnTo = $this->normalizeReturnTo($request->get('return_to'));

        if ($this->crm->deleteActivity($activity, (int) $user->id)) {
            if ($returnTo) {
                return redirect()->to($returnTo)->with('success', 'Activity deleted.');
            }

            $params = [];
            if (! empty($record->related_to_id)) {
                $params['contact_id'] = (int) $record->related_to_id;
            }

            return redirect()->route('activities.index', $params)->with('success', 'Activity deleted.');
        }

        return redirect()->back()->with('error', 'Failed to delete activity.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function redirectAfterActivitySave(array $validated, string $message): RedirectResponse
    {
        $returnTo = $this->normalizeReturnTo($validated['return_to'] ?? null);
        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', $message);
        }

        $params = [];
        if (! empty($validated['related_to'])) {
            $params['contact_id'] = $validated['related_to'];
        }
        if (! empty($validated['ticket_id'])) {
            $params['ticket_id'] = $validated['ticket_id'];
        }

        return redirect()->route('activities.index', $params)->with('success', $message);
    }

    private function canManageActivity(object $activity): bool
    {
        $ownerScope = crm_owner_filter();
        if ($ownerScope && (int) ($activity->smownerid ?? 0) !== $ownerScope) {
            return false;
        }

        $relatedId = (int) ($activity->related_to_id ?? 0);
        if ($relatedId && function_exists('contact_can_access') && ! contact_can_access($relatedId)) {
            return false;
        }

        return true;
    }

    private function activeVtigerUsers(): \Illuminate\Support\Collection
    {
        try {
            return VtigerUser::on('vtiger')->where('status', 'Active')->orderBy('first_name')->orderBy('last_name')->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function normalizeReturnTo(mixed $returnTo): ?string
    {
        $returnTo = trim((string) ($returnTo ?? ''));
        if ($returnTo === '') {
            return null;
        }

        if (str_starts_with($returnTo, '/')) {
            return $returnTo;
        }

        $appUrl = rtrim((string) url('/'), '/');
        if (str_starts_with($returnTo, $appUrl . '/')) {
            return $returnTo;
        }

        return null;
    }

    public function ticketsForContact(int $contact): JsonResponse
    {
        $tickets = $this->crm->getTicketsForContact($contact, 200, crm_owner_filter());
        return response()->json($tickets);
    }
}
