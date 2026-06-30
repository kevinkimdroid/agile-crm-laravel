<?php

namespace App\Http\Controllers;

use App\Models\VtigerProfile;
use App\Models\VtigerTab;
use App\Services\ProfileAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function __construct(protected ProfileAccessService $profileAccess)
    {
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('settings.crm', ['section' => 'profiles']);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('settings.crm', ['section' => 'profiles', 'action' => 'create']);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildFormData(?VtigerProfile $profile = null, bool $isCreate = false): array
    {
        $profile = $profile ?? new VtigerProfile(['profilename' => '', 'description' => '']);
        $profileId = $profile->exists ? (int) $profile->profileid : null;

        if ($profile->exists) {
            $profile->load(['tabs', 'roles']);
        }

        return [
            'profile' => $profile,
            'isCreate' => $isCreate,
            'moduleList' => $this->buildModuleList($profile),
            'appModuleList' => $this->buildAppModuleList($profileId),
            'clientSegments' => $profileId
                ? $this->profileAccess->getClientSegmentsForProfile($profileId)
                : $this->profileAccess->allClientSegmentKeys(),
            'clientAccessMode' => $profileId
                ? $this->profileAccess->getClientAccessModeForProfile($profileId)
                : ProfileAccessService::CLIENT_ACCESS_ALL,
            'segmentLabels' => config('profile_modules.client_segments', []),
            'tools' => $profileId
                ? $this->getProfileTools($profileId)
                : ['Import' => false, 'Export' => false, 'DuplicatesHandling' => false],
            'profileCancelUrl' => route('settings.crm', ['section' => 'profiles']),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profilename' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $nextId = (int) VtigerProfile::on('vtiger')->max('profileid') + 1;
        $profile = VtigerProfile::on('vtiger')->create([
            'profileid' => $nextId,
            'profilename' => $validated['profilename'],
            'description' => $validated['description'] ?? '',
        ]);

        $this->persistProfileSettings($request, (int) $profile->profileid);

        return redirect()
            ->route('settings.crm', ['section' => 'profiles', 'action' => 'edit', 'profile' => $profile->profileid])
            ->with('success', 'Profile created.');
    }

    /** @return RedirectResponse */
    public function show(string $id)
    {
        $profile = VtigerProfile::on('vtiger')->find($id);
        if (! $profile) {
            return redirect()->route('settings.crm', ['section' => 'profiles'])
                ->withErrors(['profile' => 'Profile not found.']);
        }

        return redirect()->route('settings.crm', [
            'section' => 'profiles',
            'action' => 'edit',
            'profile' => $profile->profileid,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $profile = VtigerProfile::on('vtiger')->find($id);
        if (! $profile) {
            return back()->withErrors(['profile' => 'Profile not found.']);
        }

        $request->validate([
            'profilename' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        if ($request->filled('profilename')) {
            $profile->update(['profilename' => $request->profilename]);
        }
        if ($request->has('description')) {
            $profile->update(['description' => $request->description]);
        }

        $this->persistProfileSettings($request, (int) $profile->profileid);

        return redirect()
            ->route('settings.crm', ['section' => 'profiles', 'action' => 'edit', 'profile' => $profile->profileid])
            ->with('success', 'Profile permissions updated.');
    }

    protected function persistProfileSettings(Request $request, int $profileId): void
    {
        $modules = $request->input('modules', []);
        if (is_array($modules)) {
            $this->updateModulePermissions($profileId, $modules);
        }

        $tools = $request->input('tools', []);
        if (is_array($tools)) {
            $this->updateProfileTools($profileId, $tools);
        }

        $fields = $request->input('fields', []);
        if (is_array($fields)) {
            $this->updateFieldPermissions($profileId, $fields);
        }

        $segments = $request->input('client_segments', []);
        $appModules = $request->input('app_modules', []);
        if (! is_array($segments)) {
            $segments = [];
        }
        if (! is_array($appModules)) {
            $appModules = [];
        }

        $clientAccessMode = (string) $request->input('client_access_mode', ProfileAccessService::CLIENT_ACCESS_ALL);

        $this->profileAccess->saveForProfile($profileId, $segments, $appModules, $clientAccessMode);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildModuleList(VtigerProfile $profile): array
    {
        $fromApp = array_filter(array_unique(array_values(config('modules.app_to_vtiger', []))));
        $vtigerNames = array_values(array_unique(array_merge(
            config('profile_modules.vtiger_tabs', []),
            $fromApp
        )));
        $allTabs = VtigerTab::on('vtiger')
            ->whereIn('name', $vtigerNames)
            ->orderBy('name')
            ->get()
            ->sortBy(fn ($tab) => array_search($tab->name, $vtigerNames, true) ?: 999);

        $allowedTabIds = $profile->exists ? $profile->tabs->pluck('tabid')->toArray() : [];

        $standardPerms = $profile->exists
            ? DB::connection('vtiger')
                ->table('vtiger_profile2standardpermissions')
                ->where('profileid', $profile->profileid)
                ->get()
                ->groupBy('tabid')
            : collect();

        $fieldPerms = $profile->exists
            ? DB::connection('vtiger')
                ->table('vtiger_profile2field')
                ->where('profileid', $profile->profileid)
                ->get()
                ->groupBy('tabid')
            : collect();

        $moduleList = [];
        foreach ($allTabs as $tab) {
            $perms = $standardPerms->get($tab->tabid, collect());
            $hasView = in_array($tab->tabid, $allowedTabIds, true);
            $moduleList[] = [
                'tabid' => $tab->tabid,
                'name' => $tab->name,
                'label' => $this->tabLabel($tab->name),
                'view' => $hasView,
                'create' => (bool) (($p = $perms->firstWhere('operation', 0)) ? ($p->permissions ?? 0) : 0),
                'edit' => (bool) (($p = $perms->firstWhere('operation', 1)) ? ($p->permissions ?? 0) : 0),
                'delete' => (bool) (($p = $perms->firstWhere('operation', 2)) ? ($p->permissions ?? 0) : 0),
                'fields' => $hasView ? $this->getFieldsForTab($tab->tabid, $fieldPerms->get($tab->tabid, collect())) : [],
            ];
        }

        return $moduleList;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildAppModuleList(?int $profileId): array
    {
        $labels = config('profile_modules.app_modules', []);
        $perms = $this->profileAccess->getAppModulePermissionsForProfile($profileId);
        $list = [];

        foreach ($labels as $key => $label) {
            $p = $perms[$key] ?? ['view' => true, 'create' => true, 'edit' => true, 'delete' => true];
            $list[] = [
                'key' => $key,
                'label' => $label,
                'view' => (bool) ($p['view'] ?? true),
                'create' => (bool) ($p['create'] ?? true),
                'edit' => (bool) ($p['edit'] ?? true),
                'delete' => (bool) ($p['delete'] ?? true),
            ];
        }

        return $list;
    }

    protected function tabLabel(string $name): string
    {
        $labels = [
            'Home' => 'Dashboards',
            'Potentials' => 'Opportunities',
            'HelpDesk' => 'Tickets',
            'Contacts' => 'Contacts',
            'Leads' => 'Leads',
            'Calendar' => 'Calendar',
            'Emails' => 'Emails',
            'Campaigns' => 'Campaigns',
            'Reports' => 'Reports',
            'Documents' => 'Documents',
        ];

        return $labels[$name] ?? $name;
    }

    protected function getFieldsForTab(int $tabid, $fieldPerms): array
    {
        $fields = DB::connection('vtiger')
            ->table('vtiger_field')
            ->where('tabid', $tabid)
            ->whereIn('presence', [0, 2])
            ->orderBy('sequence')
            ->limit(30)
            ->get(['fieldid', 'fieldlabel', 'fieldname']);

        $permMap = $fieldPerms->keyBy('fieldid');

        return $fields->map(function ($f) use ($permMap) {
            $p = $permMap->get($f->fieldid);
            $visible = $p ? (int) $p->visible : 1;
            $readonly = $p ? (int) $p->readonly : 0;
            $access = $visible === 0 ? 'invisible' : ($readonly === 1 ? 'readonly' : 'write');

            return [
                'fieldid' => $f->fieldid,
                'label' => $f->fieldlabel ?: $f->fieldname,
                'access' => $access,
            ];
        })->values()->all();
    }

    protected function getProfileTools(int $profileid): array
    {
        try {
            $utils = DB::connection('vtiger')
                ->table('vtiger_profile2utility')
                ->where('profileid', $profileid)
                ->where('permission', 1)
                ->pluck('activityid')
                ->toArray();

            return [
                'Import' => in_array(5, $utils, true),
                'Export' => in_array(6, $utils, true),
                'DuplicatesHandling' => in_array(10, $utils, true),
            ];
        } catch (\Throwable $e) {
            return ['Import' => false, 'Export' => false, 'DuplicatesHandling' => false];
        }
    }

    protected function updateModulePermissions(int $profileid, array $modules): void
    {
        $managedTabIds = array_map('intval', array_keys($modules));
        $managedTabIds = array_filter($managedTabIds, fn ($id) => $id > 0);

        DB::connection('vtiger')->transaction(function () use ($profileid, $modules, $managedTabIds) {
            if (! empty($managedTabIds)) {
                DB::connection('vtiger')->table('vtiger_profile2tab')
                    ->where('profileid', $profileid)
                    ->whereIn('tabid', $managedTabIds)
                    ->delete();

                DB::connection('vtiger')->table('vtiger_profile2standardpermissions')
                    ->where('profileid', $profileid)
                    ->whereIn('tabid', $managedTabIds)
                    ->delete();
            }

            $ops = [0 => 'create', 1 => 'edit', 2 => 'delete'];

            foreach ($modules as $tabId => $perms) {
                $tabId = (int) $tabId;
                if ($tabId <= 0) {
                    continue;
                }
                $perms = is_array($perms) ? $perms : [];
                $hasView = ! empty($perms['view']);

                if ($hasView) {
                    DB::connection('vtiger')->table('vtiger_profile2tab')->insert([
                        'profileid' => $profileid,
                        'tabid' => $tabId,
                        'permissions' => 1,
                    ]);

                    foreach ($ops as $opNum => $key) {
                        DB::connection('vtiger')->table('vtiger_profile2standardpermissions')->insert([
                            'profileid' => $profileid,
                            'tabid' => $tabId,
                            'operation' => $opNum,
                            'permissions' => ! empty($perms[$key]) ? 1 : 0,
                        ]);
                    }
                    foreach ([3 => 1, 4 => 1] as $opNum => $perm) {
                        DB::connection('vtiger')->table('vtiger_profile2standardpermissions')->insert([
                            'profileid' => $profileid,
                            'tabid' => $tabId,
                            'operation' => $opNum,
                            'permissions' => $perm,
                        ]);
                    }
                }
            }
        });
    }

    protected function updateProfileTools(int $profileid, array $tools): void
    {
        $activityMap = ['Import' => 5, 'Export' => 6, 'DuplicatesHandling' => 10];
        $tabs = DB::connection('vtiger')->table('vtiger_profile2utility')
            ->where('profileid', $profileid)
            ->select('tabid')
            ->distinct()
            ->pluck('tabid');
        $tabid = $tabs->first() ?? 2;

        DB::connection('vtiger')->table('vtiger_profile2utility')
            ->where('profileid', $profileid)
            ->delete();

        foreach ($activityMap as $tool => $aid) {
            if (! empty($tools[$tool])) {
                DB::connection('vtiger')->table('vtiger_profile2utility')->insert([
                    'profileid' => $profileid,
                    'tabid' => $tabid,
                    'activityid' => $aid,
                    'permission' => 1,
                ]);
            }
        }
    }

    protected function updateFieldPermissions(int $profileid, array $fields): void
    {
        $tabIds = [];
        foreach (array_keys($fields) as $key) {
            $parts = explode('_', $key, 2);
            if (count($parts) === 2 && (int) $parts[0] > 0) {
                $tabIds[] = (int) $parts[0];
            }
        }
        $tabIds = array_unique($tabIds);

        if (! empty($tabIds)) {
            DB::connection('vtiger')->table('vtiger_profile2field')
                ->where('profileid', $profileid)
                ->whereIn('tabid', $tabIds)
                ->delete();
        }

        foreach ($fields as $key => $access) {
            $parts = explode('_', $key, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$tabid, $fieldid] = $parts;
            $tabid = (int) $tabid;
            $fieldid = (int) $fieldid;
            if ($tabid <= 0 || $fieldid <= 0) {
                continue;
            }
            $visible = $access === 'invisible' ? 0 : 1;
            $readonly = $access === 'readonly' ? 1 : 0;

            DB::connection('vtiger')->table('vtiger_profile2field')->insert([
                'profileid' => $profileid,
                'tabid' => $tabid,
                'fieldid' => $fieldid,
                'visible' => $visible,
                'readonly' => $readonly,
            ]);
        }
    }
}
