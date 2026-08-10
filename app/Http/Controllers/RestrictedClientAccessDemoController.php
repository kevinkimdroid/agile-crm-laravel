<?php

namespace App\Http\Controllers;

use App\Services\ClientAccessDemoService;
use App\Services\ProfileAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RestrictedClientAccessDemoController extends Controller
{
    public function __construct(
        protected ClientAccessDemoService $demo,
        protected ProfileAccessService $profileAccess
    ) {
    }

    /**
     * Demo walkthrough page: seed faker clients, start restricted mode, attempt access.
     */
    public function index(): View
    {
        $user = Auth::guard('vtiger')->user();
        $clients = $this->demo->demoClients();
        $active = $this->demo->isDemoModeActive();
        $limited = $user ? $this->profileAccess->userIsLimitedToAssignedClients($user) : false;

        return view('support.restricted-access-demo', [
            'demoActive' => $active,
            'profileLimited' => $limited,
            'allowedClients' => $clients['allowed'],
            'forbiddenClients' => $clients['forbidden'],
            'user' => $user,
        ]);
    }

    public function seed(Request $request): RedirectResponse
    {
        $user = Auth::guard('vtiger')->user();
        if (! $user) {
            return redirect()->route('login');
        }

        try {
            $result = $this->demo->seed((int) $user->id);
        } catch (\Throwable $e) {
            return redirect()->route('demo.restricted-access')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('demo.restricted-access')
            ->with('success', 'Prepared ' . count($result['allowed']) . ' clients assigned to you and ' . count($result['forbidden']) . ' clients that are not assigned to you.');
    }

    public function start(): RedirectResponse
    {
        $user = Auth::guard('vtiger')->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $clients = $this->demo->demoClients();
        if ($clients['allowed']->isEmpty()) {
            $this->demo->seed((int) $user->id);
        } else {
            // Ensure assignments exist for current user.
            $this->demo->seed((int) $user->id);
        }

        $this->demo->startDemoMode();

        return redirect()->route('support.customers')
            ->with('success', 'Assigned-only preview is on. You can only see clients assigned to you. Opening a client assigned to someone else will be blocked.');
    }

    public function stop(): RedirectResponse
    {
        $this->demo->stopDemoMode();

        return redirect()->route('demo.restricted-access')
            ->with('info', 'Assigned-only preview ended. Full client access restored for this session.');
    }

    /**
     * Attempt to open a forbidden demo client (forces the deny path when demo mode is on).
     */
    public function attempt(Request $request): RedirectResponse
    {
        $policy = strtoupper(trim((string) $request->get('policy', '')));
        if ($policy === '') {
            $forbidden = $this->demo->demoClients()['forbidden']->first();
            $policy = $forbidden?->policy_no ?? (ClientAccessDemoService::FORBIDDEN_PREFIX . '001');
        }

        if (! $this->demo->isDemoModeActive()) {
            $this->demo->startDemoMode();
        }

        return redirect()->route('support.clients.show', ['policy' => $policy]);
    }
}
