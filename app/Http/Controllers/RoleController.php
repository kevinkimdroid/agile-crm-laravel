<?php

namespace App\Http\Controllers;

use App\Models\VtigerRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function assignProfile(Request $request, string $roleId): RedirectResponse
    {
        $validated = $request->validate([
            'profileid' => 'required|integer|min:1',
        ]);

        $role = VtigerRole::on('vtiger')->find($roleId);
        if (! $role) {
            return back()->withErrors(['role' => 'Role not found.']);
        }

        $profileExists = DB::connection('vtiger')
            ->table('vtiger_profile')
            ->where('profileid', $validated['profileid'])
            ->exists();

        if (! $profileExists) {
            return back()->withErrors(['profile' => 'Profile not found.']);
        }

        DB::connection('vtiger')->transaction(function () use ($role, $validated) {
            DB::connection('vtiger')->table('vtiger_role2profile')
                ->where('roleid', $role->roleid)
                ->delete();

            DB::connection('vtiger')->table('vtiger_role2profile')->insert([
                'roleid' => $role->roleid,
                'profileid' => (int) $validated['profileid'],
            ]);
        });

        return redirect()
            ->route('settings.crm', ['section' => 'roles'])
            ->with('success', 'Profile assigned to role "' . $role->rolename . '".');
    }
}
