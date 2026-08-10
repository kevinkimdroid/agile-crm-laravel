<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TicketSlaService
{
    /**
     * Roles that are allowed to close tickets. Stored in ticket_sla_settings.
     */
    public function getRolesCanClose(): array
    {
        $row = DB::table('ticket_sla_settings')->where('key', 'roles_can_close')->first();
        if (!$row || empty($row->value)) {
            return ['Administrator'];
        }
        $decoded = json_decode($row->value, true);
        return is_array($decoded) ? $decoded : ['Administrator'];
    }

    public function setRolesCanClose(array $roles): void
    {
        DB::table('ticket_sla_settings')->updateOrInsert(
            ['key' => 'roles_can_close'],
            ['value' => json_encode($roles), 'updated_at' => now()]
        );
    }

    /**
     * Check if the current user's role can close tickets.
     */
    public function canUserCloseTickets(?string $userRoleName): bool
    {
        if (!$userRoleName) {
            return false;
        }
        $allowed = $this->getRolesCanClose();
        return in_array($userRoleName, $allowed, true);
    }

    /**
     * Check if the current user can close a specific ticket.
     * Allows: users whose role can close, OR the ticket assignee.
     */
    public function canUserCloseThisTicket(int $ticketId): bool
    {
        $user = \Illuminate\Support\Facades\Auth::guard('vtiger')->user()
            ?? \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return false;
        }
        $userRole = $user->primary_role ?? null;
        $roleName = $userRole?->rolename ?? null;
        if ($roleName && $this->canUserCloseTickets($roleName)) {
            return true;
        }
        $userId = (int) ($user->id ?? $user->getAuthIdentifier() ?? 0);
        if ($userId <= 0) {
            return false;
        }
        $ownerId = \Illuminate\Support\Facades\DB::connection('vtiger')
            ->table('vtiger_crmentity')
            ->where('crmid', $ticketId)
            ->value('smownerid');
        return $ownerId !== null && (int) $ownerId == $userId;
    }

    /**
     * Get TAT (hours) for a department.
     */
    public function getTatForDepartment(?string $department): ?int
    {
        if (!$department) {
            return null;
        }
        $row = DB::table('ticket_department_tat')->where('department', $department)->first();
        return $row ? (int) $row->tat_hours : null;
    }

    /**
     * Get TAT (hours) for a ticket category.
     */
    public function getTatForCategory(?string $category): ?int
    {
        if (!$category) {
            return null;
        }
        if (! $this->categoryTatTableExists()) {
            return $this->getTatForDepartment($category);
        }
        $row = DB::table('ticket_category_tat')->where('category', $category)->first();
        return $row ? (int) $row->tat_hours : null;
    }

    /**
     * Resolve effective TAT for a ticket.
     * Uses the stricter (minimum) of category TAT and owner-department TAT when both exist.
     *
     * @return array{hours: int, source: string, category_hours: ?int, department_hours: ?int}
     */
    public function resolveTatForTicket(?string $category, ?string $ownerDepartment = null): array
    {
        $categoryHours = $this->getTatForCategory($category);
        $departmentHours = $this->getTatForDepartment($ownerDepartment);

        if ($categoryHours !== null && $departmentHours !== null) {
            $hours = min($categoryHours, $departmentHours);
            $source = $categoryHours <= $departmentHours ? 'category' : 'department';
            if ($categoryHours === $departmentHours) {
                $source = 'category+department';
            }
        } elseif ($categoryHours !== null) {
            $hours = $categoryHours;
            $source = 'category';
        } elseif ($departmentHours !== null) {
            $hours = $departmentHours;
            $source = 'department';
        } else {
            $hours = 24;
            $source = 'default';
        }

        return [
            'hours' => max(1, (int) $hours),
            'source' => $source,
            'category_hours' => $categoryHours,
            'department_hours' => $departmentHours,
        ];
    }

    /**
     * Get all department TAT configs.
     */
    public function getAllDepartmentTat(): \Illuminate\Support\Collection
    {
        return DB::table('ticket_department_tat')->orderBy('department')->get();
    }

    /**
     * Get all category TAT configs.
     */
    public function getAllCategoryTat(): \Illuminate\Support\Collection
    {
        if (! $this->categoryTatTableExists()) {
            return collect();
        }

        return DB::table('ticket_category_tat')->orderBy('category')->get();
    }

    /**
     * Save or update department TAT.
     */
    public function setDepartmentTat(string $department, int $tatHours): void
    {
        $exists = DB::table('ticket_department_tat')->where('department', $department)->exists();
        if ($exists) {
            DB::table('ticket_department_tat')->where('department', $department)->update(['tat_hours' => $tatHours, 'updated_at' => now()]);
        } else {
            $now = now();
            DB::table('ticket_department_tat')->insert([
                'department' => $department,
                'tat_hours' => $tatHours,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Save or update category TAT.
     */
    public function setCategoryTat(string $category, int $tatHours): void
    {
        if (! $this->categoryTatTableExists()) {
            $this->setDepartmentTat($category, $tatHours);

            return;
        }

        $exists = DB::table('ticket_category_tat')->where('category', $category)->exists();
        if ($exists) {
            DB::table('ticket_category_tat')->where('category', $category)->update(['tat_hours' => $tatHours, 'updated_at' => now()]);
        } else {
            $now = now();
            DB::table('ticket_category_tat')->insert([
                'category' => $category,
                'tat_hours' => $tatHours,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Delete department TAT.
     */
    public function deleteDepartmentTat(string $department): void
    {
        DB::table('ticket_department_tat')->where('department', $department)->delete();
    }

    /**
     * Delete category TAT.
     */
    public function deleteCategoryTat(string $category): void
    {
        if (! $this->categoryTatTableExists()) {
            return;
        }
        DB::table('ticket_category_tat')->where('category', $category)->delete();
    }

    /**
     * Categories from .env / config plus Settings → Create ticket form custom lines.
     *
     * @return list<string>
     */
    public function allConfiguredTicketCategories(): array
    {
        $fromConfig = config('tickets.categories', []);
        $custom = \App\Models\CrmSetting::tableExists()
            ? \App\Models\CrmSetting::parsedLines(\App\Models\CrmSetting::get('ticket_categories_custom'))
            : [];

        return array_values(array_unique(array_map('trim', array_merge($fromConfig, $custom))));
    }

    /**
     * Sync category TAT from ticket categories. Adds any category that doesn't have TAT configured.
     *
     * @return array{added: array<string>, existing: array<string>}
     */
    public function syncCategoriesFromTicketCategories(): array
    {
        $categories = $this->allConfiguredTicketCategories();
        $existing = $this->getAllCategoryTat()->pluck('category')->map(fn ($d) => (string) $d)->toArray();
        $added = [];

        foreach ($categories as $cat) {
            $cat = trim((string) $cat);
            if ($cat === '') {
                continue;
            }
            if (! in_array($cat, $existing, true)) {
                $this->setCategoryTat($cat, 24);
                $added[] = $cat;
                $existing[] = $cat;
            }
        }

        return ['added' => $added, 'existing' => $existing];
    }

    /**
     * @deprecated Use syncCategoriesFromTicketCategories()
     */
    public function syncDepartmentsFromCategories(): array
    {
        return $this->syncCategoriesFromTicketCategories();
    }

    /**
     * Sync department TAT from org departments list.
     *
     * @return array{added: array<string>, existing: array<string>}
     */
    public function syncDepartmentsFromOrgList(): array
    {
        $departments = app(UserDepartmentService::class)->getDepartmentsList();
        $existing = $this->getAllDepartmentTat()->pluck('department')->map(fn ($d) => (string) $d)->toArray();
        $added = [];

        foreach ($departments as $dept) {
            $dept = trim((string) $dept);
            if ($dept === '') {
                continue;
            }
            if (! in_array($dept, $existing, true)) {
                $this->setDepartmentTat($dept, 24);
                $added[] = $dept;
                $existing[] = $dept;
            }
        }

        return ['added' => $added, 'existing' => $existing];
    }

    /**
     * Get ticket categories that don't have category TAT configured.
     */
    public function getCategoriesWithoutTat(): array
    {
        $categories = $this->allConfiguredTicketCategories();
        $configured = $this->getAllCategoryTat()->pluck('category')->map(fn ($d) => strtolower((string) $d))->toArray();

        // Fallback while migration not run: treat department TAT as category config
        if ($configured === [] && ! $this->categoryTatTableExists()) {
            $configured = $this->getAllDepartmentTat()->pluck('department')->map(fn ($d) => strtolower((string) $d))->toArray();
        }

        return array_values(array_filter($categories, function ($cat) use ($configured) {
            return ! in_array(strtolower(trim((string) $cat)), $configured, true);
        }));
    }

    /**
     * Get org departments that don't have department TAT configured.
     */
    public function getDepartmentsWithoutTat(): array
    {
        $departments = app(UserDepartmentService::class)->getDepartmentsList();
        $configured = $this->getAllDepartmentTat()->pluck('department')->map(fn ($d) => strtolower((string) $d))->toArray();

        return array_values(array_filter($departments, function ($dept) use ($configured) {
            return ! in_array(strtolower(trim((string) $dept)), $configured, true);
        }));
    }

    /**
     * Get tickets that have broken SLA (exceeded TAT).
     * Only active (not closed/resolved) tickets are considered violations.
     * TAT = stricter of category TAT and assignee department TAT.
     */
    public function getBrokenSlaTickets(int $limit = 100): \Illuminate\Support\Collection
    {
        $tickets = DB::connection('vtiger')
            ->table('vtiger_troubletickets as t')
            ->join('vtiger_crmentity as e', 't.ticketid', '=', 'e.crmid')
            ->leftJoin('vtiger_contactdetails as c', 't.contact_id', '=', 'c.contactid')
            ->leftJoin('vtiger_users as u', 'e.smownerid', '=', 'u.id')
            ->where('e.deleted', 0)
            ->whereIn('e.setype', ['HelpDesk', 'Ticket'])
            ->whereNotNull('t.contact_id')
            ->where('t.contact_id', '>', 0)
            ->whereRaw("LOWER(TRIM(COALESCE(t.status, ''))) NOT IN ('closed', 'resolved')")
            ->select(
                't.ticketid',
                't.title',
                't.ticket_no',
                't.status',
                't.category',
                't.priority',
                'e.createdtime',
                'e.modifiedtime',
                'e.smownerid',
                'c.firstname as contact_first',
                'c.lastname as contact_last',
                'u.first_name as owner_first',
                'u.last_name as owner_last'
            )
            ->orderByDesc('e.createdtime')
            ->limit($limit * 2)
            ->get();

        $userIds = $tickets->pluck('smownerid')->filter()->unique()->values()->all();
        $ownerDepartments = app(UserDepartmentService::class)->getDepartmentsForUsers($userIds);

        $broken = collect();
        foreach ($tickets as $t) {
            $category = $t->category ?? null;
            $ownerDept = isset($t->smownerid) ? ($ownerDepartments[$t->smownerid] ?? null) : null;
            $resolved = $this->resolveTatForTicket($category, $ownerDept);
            $tatHours = $resolved['hours'];
            $created = \Carbon\Carbon::parse($t->createdtime);
            $dueAt = $created->copy()->addHours($tatHours);
            $isBreached = now()->gt($dueAt);

            if ($isBreached) {
                $broken->push((object) array_merge((array) $t, [
                    'tat_hours' => $tatHours,
                    'tat_source' => $resolved['source'],
                    'category_tat_hours' => $resolved['category_hours'],
                    'department_tat_hours' => $resolved['department_hours'],
                    'owner_department' => $ownerDept,
                    'due_at' => $dueAt,
                    'breached_at' => now(),
                    'hours_overdue' => now()->diffInHours($dueAt),
                ]));
                if ($broken->count() >= $limit) {
                    break;
                }
            }
        }

        return $broken->take($limit);
    }

    /**
     * Get work tickets that have broken SLA (exceeded TAT due time).
     * Includes open tickets past due, and closed tickets completed after due.
     */
    public function getBrokenWorkSlaTickets(int $limit = 100): \Illuminate\Support\Collection
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('work_tickets')) {
            return collect();
        }

        $tickets = \App\Models\WorkTicket::query()
            ->whereNotNull('tat_due_at')
            ->where(function ($q): void {
                $q->where(function ($open): void {
                    $open->whereNotIn('status', ['Done', 'Closed'])
                        ->where('tat_due_at', '<', now());
                })->orWhere(function ($closed): void {
                    $closed->whereIn('status', ['Done', 'Closed'])
                        ->whereNotNull('completed_at')
                        ->whereColumn('completed_at', '>', 'tat_due_at');
                });
            })
            ->orderByDesc('tat_due_at')
            ->limit($limit)
            ->get();

        $userIds = $tickets->flatMap(fn ($t) => [
            (int) ($t->assignee_id ?? 0),
            (int) ($t->reporting_manager_id ?? 0),
            (int) ($t->created_by ?? 0),
        ])->filter(fn ($id) => $id > 0)->unique()->values()->all();

        $users = collect();
        if ($userIds !== []) {
            $users = DB::connection('vtiger')
                ->table('vtiger_users')
                ->whereIn('id', $userIds)
                ->select('id', 'first_name', 'last_name', 'user_name')
                ->get()
                ->keyBy('id');
        }

        $departments = app(UserDepartmentService::class)->getDepartmentsForUsers($userIds);

        return $tickets->map(function ($t) use ($users, $departments) {
            $assignee = $users->get((int) ($t->assignee_id ?? 0));
            $dueAt = $t->tat_due_at;
            $isClosed = in_array((string) $t->status, ['Done', 'Closed'], true);
            $reference = $isClosed && $t->completed_at ? $t->completed_at : now();
            $hoursOverdue = ($dueAt && $reference->gt($dueAt)) ? $dueAt->diffInHours($reference) : 0;

            if ($t->tat_breached_at) {
                $breachedAt = $t->tat_breached_at;
            } elseif ($isClosed && $t->completed_at) {
                $breachedAt = $t->completed_at;
            } else {
                $breachedAt = now();
            }

            return (object) [
                'id' => $t->id,
                'ticket_no' => $t->ticket_no,
                'title' => $t->title,
                'status' => $t->status,
                'priority' => $t->priority,
                'assignee_id' => $t->assignee_id,
                'assignee_name' => $assignee
                    ? (trim(($assignee->first_name ?? '') . ' ' . ($assignee->last_name ?? '')) ?: ($assignee->user_name ?? '—'))
                    : 'Unassigned',
                'owner_department' => $departments[$t->assignee_id ?? 0] ?? null,
                'tat_hours' => $t->tat_hours,
                'due_at' => $dueAt,
                'created_at' => $t->created_at,
                'completed_at' => $t->completed_at,
                'breached_at' => $breachedAt,
                'hours_overdue' => $hoursOverdue,
            ];
        })->values();
    }

    protected function categoryTatTableExists(): bool
    {
        static $exists = null;
        if ($exists === null) {
            try {
                $exists = \Illuminate\Support\Facades\Schema::hasTable('ticket_category_tat');
            } catch (\Throwable $e) {
                $exists = false;
            }
        }

        return $exists;
    }
}
