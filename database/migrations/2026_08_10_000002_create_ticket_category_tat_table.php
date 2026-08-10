<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ticket_category_tat')) {
            Schema::create('ticket_category_tat', function (Blueprint $table) {
                $table->id();
                $table->string('category', 150)->unique();
                $table->unsignedInteger('tat_hours')->default(24);
                $table->timestamps();
            });
        }

        // Preserve existing SLA behaviour: copy department TAT rows into category TAT
        // (historically category was treated as department).
        if (Schema::hasTable('ticket_department_tat') && Schema::hasTable('ticket_category_tat')) {
            $now = now();
            $rows = DB::table('ticket_department_tat')->get();
            foreach ($rows as $row) {
                $name = trim((string) ($row->department ?? ''));
                if ($name === '') {
                    continue;
                }
                DB::table('ticket_category_tat')->updateOrInsert(
                    ['category' => $name],
                    [
                        'tat_hours' => (int) ($row->tat_hours ?? 24),
                        'updated_at' => $now,
                        'created_at' => $row->created_at ?? $now,
                    ]
                );
            }
        }

        // Ensure org departments have TAT rows (from departments table / config).
        $deptNames = [];
        try {
            if (Schema::hasTable('departments')) {
                $deptNames = DB::table('departments')->orderBy('name')->pluck('name')->all();
            }
        } catch (\Throwable $e) {
            $deptNames = [];
        }
        if ($deptNames === []) {
            $deptNames = config('users.departments_list', []);
        }

        $now = now();
        foreach ($deptNames as $name) {
            $name = trim((string) $name);
            if ($name === '' || ! Schema::hasTable('ticket_department_tat')) {
                continue;
            }
            if (! DB::table('ticket_department_tat')->where('department', $name)->exists()) {
                DB::table('ticket_department_tat')->insert([
                    'department' => $name,
                    'tat_hours' => 24,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_category_tat');
    }
};
