<?php

namespace App\Services;

use App\Models\AgileProfileSetting;
use App\Models\UserClientAssignment;
use App\Models\VtigerUser;
use App\Services\ErpClientService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProfileAccessService
{
    /** @var list<string> */
    public const CLIENT_SEGMENT_KEYS = ['group', 'individual', 'mortgage', 'group_pension'];

    public const CLIENT_ACCESS_ALL = 'all';

    public const CLIENT_ACCESS_ASSIGNED_ONLY = 'assigned_only';

    public function assignmentsTableExists(): bool
    {
        return UserClientAssignment::tableExists();
    }

    public function tableExists(): bool
    {
        return Schema::hasTable('agile_profile_settings');
    }

    public function getProfileIdForUser(?VtigerUser $user): ?int
    {
        if (! $user) {
            return null;
        }

        if ($user->isAdministrator()) {
            return null;
        }

        try {
            $roleId = DB::connection('vtiger')
                ->table('vtiger_user2role')
                ->where('userid', $user->id)
                ->orderBy('roleid')
                ->value('roleid');

            if (! $roleId) {
                return null;
            }

            $profileId = DB::connection('vtiger')
                ->table('vtiger_role2profile')
                ->where('roleid', $roleId)
                ->orderBy('profileid')
                ->value('profileid');

            return $profileId ? (int) $profileId : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    public function allClientSegmentKeys(): array
    {
        return self::CLIENT_SEGMENT_KEYS;
    }

    /**
     * Segments this profile may access. Empty stored value = all segments.
     *
     * @return list<string>
     */
    public function getClientSegmentsForProfile(?int $profileId): array
    {
        if (! $profileId || ! $this->tableExists()) {
            return $this->allClientSegmentKeys();
        }

        try {
            $row = AgileProfileSetting::query()->where('profileid', $profileId)->first();
            $segments = is_array($row?->client_segments) ? array_values($row->client_segments) : [];

            if ($segments === []) {
                return $this->allClientSegmentKeys();
            }

            return array_values(array_intersect($segments, $this->allClientSegmentKeys()));
        } catch (\Throwable $e) {
            Log::warning('Profile client segments lookup failed', [
                'profileid' => $profileId,
                'error' => $e->getMessage(),
            ]);

            return $this->allClientSegmentKeys();
        }
    }

    /**
     * @return list<string>
     */
    public function getClientSegmentsForUser(?VtigerUser $user): array
    {
        if (! $user || $user->isAdministrator()) {
            return $this->allClientSegmentKeys();
        }

        $profileId = $this->getProfileIdForUser($user);

        try {
            return safe_cache_remember(
                'agile_client_segments_user_' . $user->id,
                600,
                fn () => $this->getClientSegmentsForProfile($profileId)
            );
        } catch (\Throwable $e) {
            Log::warning('Profile client segments cache failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->getClientSegmentsForProfile($profileId);
        }
    }

    public function userCanAccessClientSegment(?VtigerUser $user, ?string $system): bool
    {
        $system = trim((string) ($system ?? ''));
        if ($system === '') {
            return true;
        }

        if (! $user || $user->isAdministrator()) {
            return true;
        }

        try {
            return in_array($system, $this->getClientSegmentsForUser($user), true);
        } catch (\Throwable $e) {
            Log::warning('Client segment permission check failed', [
                'system' => $system,
                'error' => $e->getMessage(),
            ]);

            return true;
        }
    }

    public function getClientAccessModeForProfile(?int $profileId): string
    {
        if (! $profileId || ! $this->tableExists()) {
            return self::CLIENT_ACCESS_ALL;
        }

        try {
            $mode = AgileProfileSetting::query()->where('profileid', $profileId)->value('client_access_mode');

            return $mode === self::CLIENT_ACCESS_ASSIGNED_ONLY
                ? self::CLIENT_ACCESS_ASSIGNED_ONLY
                : self::CLIENT_ACCESS_ALL;
        } catch (\Throwable $e) {
            return self::CLIENT_ACCESS_ALL;
        }
    }

    public function getClientAccessModeForUser(?VtigerUser $user): string
    {
        if (! $user || $user->isAdministrator()) {
            return self::CLIENT_ACCESS_ALL;
        }

        $profileId = $this->getProfileIdForUser($user);

        try {
            return safe_cache_remember(
                'agile_client_access_mode_user_' . $user->id,
                600,
                fn () => $this->getClientAccessModeForProfile($profileId)
            );
        } catch (\Throwable $e) {
            return $this->getClientAccessModeForProfile($profileId);
        }
    }

    public function userIsLimitedToAssignedClients(?VtigerUser $user): bool
    {
        return $this->getClientAccessModeForUser($user) === self::CLIENT_ACCESS_ASSIGNED_ONLY;
    }

    /**
     * @return list<string>
     */
    public function getAssignedPolicyNumbersForUser(int $userId, ?string $system = null): array
    {
        if (! $this->assignmentsTableExists()) {
            return [];
        }

        try {
            $cacheKey = 'agile_client_assignments_user_' . $userId . '_' . ($system ?? 'all');

            return safe_cache_remember($cacheKey, 300, function () use ($userId, $system) {
                $query = UserClientAssignment::query()->where('userid', $userId);
                if ($system !== null && $system !== '') {
                    $query->where(function ($q) use ($system) {
                        $q->whereNull('system')->orWhere('system', $system);
                    });
                }

                return $query->orderBy('policy_number')
                    ->pluck('policy_number')
                    ->map(fn ($p) => UserClientAssignment::normalizePolicyNumber($p))
                    ->filter()
                    ->values()
                    ->all();
            });
        } catch (\Throwable $e) {
            Log::warning('Client assignment lookup failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function userCanAccessClientPolicy(?VtigerUser $user, ?string $policyNumber, ?string $system = null): bool
    {
        $policyNumber = UserClientAssignment::normalizePolicyNumber($policyNumber);
        if ($policyNumber === '') {
            return false;
        }

        if (! $user || $user->isAdministrator()) {
            return true;
        }

        if ($system !== null && $system !== '' && ! $this->userCanAccessClientSegment($user, $system)) {
            return false;
        }

        if (! $this->userIsLimitedToAssignedClients($user)) {
            return true;
        }

        $assigned = $this->getAssignedPolicyNumbersForUser((int) $user->id, $system);

        return in_array($policyNumber, $assigned, true);
    }

    /**
     * @return array{data: Collection, total: int, error: ?string, grand_total: int}
     */
    public function fetchAssignedClientsPage(int $userId, ?string $system, int $perPage, int $offset, ErpClientService $erp): array
    {
        if (! $this->assignmentsTableExists()) {
            return ['data' => collect(), 'total' => 0, 'error' => null, 'grand_total' => 0];
        }

        $query = UserClientAssignment::query()->where('userid', $userId);
        if ($system !== null && $system !== '') {
            $query->where(function ($q) use ($system) {
                $q->whereNull('system')->orWhere('system', $system);
            });
        }

        $total = (int) $query->count();
        $assignments = (clone $query)
            ->orderBy('policy_number')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $rows = collect();
        foreach ($assignments as $assignment) {
            $policy = UserClientAssignment::normalizePolicyNumber($assignment->policy_number);
            $detail = $erp->getPolicyDetails($policy);
            if (is_array($detail)) {
                $detail['policy_no'] = $policy;
                $detail['policy_number'] = $policy;
                $detail['_erp_source'] = true;
                $rows->push((object) $detail);
            } else {
                $rows->push((object) [
                    'policy_no' => $policy,
                    'policy_number' => $policy,
                    'client_name' => $assignment->client_label ?: $policy,
                    'life_assur' => $assignment->client_label ?: $policy,
                    '_erp_source' => true,
                ]);
            }
        }

        return [
            'data' => $rows,
            'total' => $total,
            'error' => null,
            'grand_total' => $total,
        ];
    }

    /**
     * @param  Collection<int, mixed>  $customers
     * @return Collection<int, mixed>
     */
    public function filterCustomersToAssignedPolicies(Collection $customers, int $userId, ?string $system = null): Collection
    {
        $assigned = $this->getAssignedPolicyNumbersForUser($userId, $system);
        if ($assigned === []) {
            return collect();
        }

        $allowed = array_fill_keys($assigned, true);

        return $customers->filter(function ($customer) use ($allowed) {
            $row = (array) (is_object($customer) ? $customer : $customer);
            foreach (['policy_no', 'policy_number', 'ipol_policy_no', 'pol_policy_no', 'contract_no', 'scheme_no'] as $key) {
                $policy = UserClientAssignment::normalizePolicyNumber($row[$key] ?? '');
                if ($policy !== '' && isset($allowed[$policy])) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    public function clearClientAssignmentCacheForUser(int $userId): void
    {
        Cache::forget('agile_client_assignments_user_' . $userId . '_all');
        foreach (self::CLIENT_SEGMENT_KEYS as $segment) {
            Cache::forget('agile_client_assignments_user_' . $userId . '_' . $segment);
        }
        Cache::forget('agile_client_access_mode_user_' . $userId);
    }

    /**
     * @return array<string, array{view: bool, create: bool, edit: bool, delete: bool}>
     */
    public function getAppModulePermissionsForProfile(?int $profileId): array
    {
        $defaults = $this->defaultAppModulePermissions();

        if (! $profileId || ! $this->tableExists()) {
            return $defaults;
        }

        try {
            $row = AgileProfileSetting::query()->where('profileid', $profileId)->first();
            $stored = is_array($row?->app_modules) ? $row->app_modules : [];

            foreach ($defaults as $key => $perms) {
                if (! isset($stored[$key]) || ! is_array($stored[$key])) {
                    continue;
                }
                foreach (['view', 'create', 'edit', 'delete'] as $action) {
                    $defaults[$key][$action] = ! empty($stored[$key][$action]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Profile app module permissions lookup failed', [
                'profileid' => $profileId,
                'error' => $e->getMessage(),
            ]);
        }

        return $defaults;
    }

    /**
     * @return array<string, array{view: bool, create: bool, edit: bool, delete: bool}>
     */
    public function defaultAppModulePermissions(): array
    {
        $out = [];
        foreach (array_keys(config('profile_modules.app_modules', [])) as $key) {
            $out[$key] = [
                'view' => true,
                'create' => true,
                'edit' => true,
                'delete' => true,
            ];
        }

        return $out;
    }

    /**
     * @param  list<string>  $segments
     * @param  array<string, array<string, mixed>>  $appModules
     */
    public function saveForProfile(
        int $profileId,
        array $segments,
        array $appModules,
        string $clientAccessMode = self::CLIENT_ACCESS_ALL
    ): void {
        if (! $this->tableExists()) {
            return;
        }

        $segments = array_values(array_intersect($segments, $this->allClientSegmentKeys()));
        $clientAccessMode = $clientAccessMode === self::CLIENT_ACCESS_ASSIGNED_ONLY
            ? self::CLIENT_ACCESS_ASSIGNED_ONLY
            : self::CLIENT_ACCESS_ALL;
        $normalizedModules = [];
        foreach ($this->defaultAppModulePermissions() as $key => $defaults) {
            $incoming = is_array($appModules[$key] ?? null) ? $appModules[$key] : [];
            $normalizedModules[$key] = [
                'view' => ! empty($incoming['view']),
                'create' => ! empty($incoming['create']),
                'edit' => ! empty($incoming['edit']),
                'delete' => ! empty($incoming['delete']),
            ];
        }

        try {
            AgileProfileSetting::query()->updateOrCreate(
                ['profileid' => $profileId],
                [
                    'client_segments' => $segments,
                    'client_access_mode' => $clientAccessMode,
                    'app_modules' => $normalizedModules,
                ]
            );

            $this->clearUserCachesForProfile($profileId);
        } catch (\Throwable $e) {
            Log::error('Failed to save profile access settings', [
                'profileid' => $profileId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function clearUserCachesForProfile(int $profileId): void
    {
        try {
            $userIds = DB::connection('vtiger')
                ->table('vtiger_user2role')
                ->join('vtiger_role2profile', 'vtiger_user2role.roleid', '=', 'vtiger_role2profile.roleid')
                ->where('vtiger_role2profile.profileid', $profileId)
                ->pluck('vtiger_user2role.userid');

            foreach ($userIds as $userId) {
                Cache::forget('agile_allowed_modules_v2_' . $userId);
                Cache::forget('agile_client_segments_user_' . $userId);
                Cache::forget('agile_client_access_mode_user_' . $userId);
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Filter allowed app module keys using profile app module view permissions.
     *
     * @param  list<string>  $allowedModules
     * @return list<string>
     */
    public function applyAppModuleViewFilter(?VtigerUser $user, array $allowedModules): array
    {
        if (! $user || $user->isAdministrator() || ! $this->tableExists()) {
            return $allowedModules;
        }

        $profileId = $this->getProfileIdForUser($user);
        if (! $profileId) {
            return $allowedModules;
        }

        $appPerms = $this->getAppModulePermissionsForProfile($profileId);
        $manageable = array_keys(config('profile_modules.app_modules', []));

        return array_values(array_filter($allowedModules, function ($key) use ($appPerms, $manageable) {
            if (! in_array($key, $manageable, true)) {
                return true;
            }

            return ! empty($appPerms[$key]['view']);
        }));
    }
}
