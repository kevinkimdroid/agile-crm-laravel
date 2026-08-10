<?php

namespace App\Services;

use App\Models\Client;
use App\Models\UserClientAssignment;
use App\Models\VtigerUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

/**
 * POC helper: seed faker clients and let an admin preview "assigned clients only" access.
 */
class ClientAccessDemoService
{
    public const SESSION_KEY = 'demo_client_access_restricted';

    public const ALLOWED_PREFIX = 'DEMO-R-';

    public const FORBIDDEN_PREFIX = 'DEMO-X-';

    /** @var list<array{first:string,last:string,system:string,product:string,city:string}> */
    protected array $allowedPeople = [
        ['first' => 'Amina', 'last' => 'Otieno', 'system' => 'individual', 'product' => 'Orient Endowment Plan', 'city' => 'Nairobi'],
        ['first' => 'Brian', 'last' => 'Mwangi', 'system' => 'individual', 'product' => 'Orient Educator', 'city' => 'Nakuru'],
        ['first' => 'Carol', 'last' => 'Wanjiku', 'system' => 'individual', 'product' => 'Orient 4 Life', 'city' => 'Mombasa'],
        ['first' => 'Daniel', 'last' => 'Kiptoo', 'system' => 'group', 'product' => 'Orient Group Life', 'city' => 'Eldoret'],
        ['first' => 'Esther', 'last' => 'Njeri', 'system' => 'group', 'product' => 'Group Last Expense', 'city' => 'Kisumu'],
        ['first' => 'Felix', 'last' => 'Ochieng', 'system' => 'mortgage', 'product' => 'Orient Group Mortgage', 'city' => 'Thika'],
    ];

    /** @var list<array{first:string,last:string,system:string,product:string,city:string}> */
    protected array $forbiddenPeople = [
        ['first' => 'Grace', 'last' => 'Mutiso', 'system' => 'individual', 'product' => 'Orient Smart Asset', 'city' => 'Nyeri'],
        ['first' => 'Hassan', 'last' => 'Ali', 'system' => 'individual', 'product' => 'Jipange Smart', 'city' => 'Garissa'],
        ['first' => 'Irene', 'last' => 'Chebet', 'system' => 'pension', 'product' => 'Orient Personal Retirement Plan', 'city' => 'Kericho'],
        ['first' => 'James', 'last' => 'Kamau', 'system' => 'group', 'product' => 'Group Credit', 'city' => 'Nairobi'],
    ];

    public function isDemoModeActive(): bool
    {
        return (bool) Session::get(self::SESSION_KEY, false);
    }

    public function startDemoMode(): void
    {
        Session::put(self::SESSION_KEY, true);
    }

    public function stopDemoMode(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Seed faker local clients + assign ALLOWED policies to $userId.
     *
     * @return array{allowed: list<string>, forbidden: list<string>, assigned_to: int}
     */
    public function seed(int $userId): array
    {
        if (! Client::tableExists()) {
            throw new \RuntimeException('clients table is missing. Run migrations first.');
        }
        if (! UserClientAssignment::tableExists()) {
            throw new \RuntimeException('agile_user_client_assignments table is missing. Run migrations first.');
        }

        $allowedPolicies = [];
        foreach ($this->allowedPeople as $i => $person) {
            $n = $i + 1;
            $policy = self::ALLOWED_PREFIX . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
            $this->upsertClient($policy, $person, true);
            $allowedPolicies[] = $policy;
        }

        $forbiddenPolicies = [];
        foreach ($this->forbiddenPeople as $i => $person) {
            $n = $i + 1;
            $policy = self::FORBIDDEN_PREFIX . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
            $this->upsertClient($policy, $person, false);
            $forbiddenPolicies[] = $policy;
        }

        // Clear previous demo assignments for this user, then assign allowed only.
        UserClientAssignment::query()
            ->where('userid', $userId)
            ->where(function ($q) {
                $q->where('policy_number', 'like', self::ALLOWED_PREFIX . '%')
                    ->orWhere('policy_number', 'like', self::FORBIDDEN_PREFIX . '%');
            })
            ->delete();

        $actor = Auth::guard('vtiger')->user();
        foreach ($allowedPolicies as $idx => $policy) {
            $person = $this->allowedPeople[$idx];
            UserClientAssignment::query()->updateOrCreate(
                [
                    'userid' => $userId,
                    'policy_number' => UserClientAssignment::normalizePolicyNumber($policy),
                ],
                [
                    'client_label' => $person['first'] . ' ' . $person['last'],
                    'system' => $person['system'] === 'pension' ? 'group_pension' : $person['system'],
                    'assigned_by' => $actor ? (int) $actor->id : $userId,
                ]
            );
        }

        app(ProfileAccessService::class)->clearClientAssignmentCacheForUser($userId);

        return [
            'allowed' => $allowedPolicies,
            'forbidden' => $forbiddenPolicies,
            'assigned_to' => $userId,
        ];
    }

    /**
     * @return array{allowed: Collection<int, Client>, forbidden: Collection<int, Client>}
     */
    public function demoClients(): array
    {
        if (! Client::tableExists()) {
            return ['allowed' => collect(), 'forbidden' => collect()];
        }

        return [
            'allowed' => Client::query()->where('policy_no', 'like', self::ALLOWED_PREFIX . '%')->orderBy('policy_no')->get(),
            'forbidden' => Client::query()->where('policy_no', 'like', self::FORBIDDEN_PREFIX . '%')->orderBy('policy_no')->get(),
        ];
    }

    public function isDemoPolicy(?string $policy): bool
    {
        $policy = strtoupper(trim((string) $policy));

        return str_starts_with($policy, self::ALLOWED_PREFIX) || str_starts_with($policy, self::FORBIDDEN_PREFIX);
    }

    /**
     * @param  array{first:string,last:string,system:string,product:string,city:string}  $person
     */
    protected function upsertClient(string $policy, array $person, bool $allowed): void
    {
        $system = $person['system'] === 'pension' ? 'group_pension' : $person['system'];
        Client::query()->updateOrCreate(
            ['policy_no' => $policy],
            [
                'first_name' => $person['first'],
                'last_name' => $person['last'],
                'id_no' => 'ID' . substr(preg_replace('/\D/', '', md5($policy)) ?: '10000000', 0, 8),
                'kra_pin' => 'A' . strtoupper(substr(md5($policy), 0, 9)) . 'Z',
                'date_of_birth' => now()->subYears(rand(28, 55))->subDays(rand(0, 300))->toDateString(),
                'gender' => rand(0, 1) ? 'Female' : 'Male',
                'email' => strtolower($person['first'] . '.' . $person['last']) . '@demo.orient.ke',
                'phone' => '07' . str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'address' => rand(10, 99) . ' Demo Street',
                'city' => $person['city'],
                'postal_code' => (string) random_int(1000, 9999),
                'occupation' => Client::OCCUPATIONS[array_rand(Client::OCCUPATIONS)],
                'product' => $person['product'],
                'intermediary' => 'Demo Agency',
                'system' => $system,
                'status' => 'A',
                'notes' => $allowed
                    ? 'POC: assigned to restricted demo user (allowed).'
                    : 'POC: NOT assigned — use to demo access denied.',
                'source' => 'demo',
                'created_by_name' => 'Restricted Access Demo',
            ]
        );
    }
}
