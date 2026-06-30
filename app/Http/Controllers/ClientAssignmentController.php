<?php

namespace App\Http\Controllers;

use App\Models\UserClientAssignment;
use App\Models\VtigerUser;
use App\Services\ErpClientService;
use App\Services\ProfileAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientAssignmentController extends Controller
{
    public function __construct(
        protected ProfileAccessService $profileAccess,
        protected ErpClientService $erp
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        if (! UserClientAssignment::tableExists()) {
            return back()->with('error', 'Client access storage is not set up. Run: php artisan migrate');
        }

        $validated = $request->validate([
            'userid' => 'required|integer|min:1',
            'policy_number' => 'required|string|max:64',
            'client_label' => 'nullable|string|max:255',
            'system' => 'nullable|string|in:group,individual,mortgage,group_pension',
        ]);

        $user = VtigerUser::on('vtiger')->find($validated['userid']);
        if (! $user) {
            return back()->withErrors(['userid' => 'User not found.']);
        }

        $policy = UserClientAssignment::normalizePolicyNumber($validated['policy_number']);
        if ($policy === '') {
            return back()->withErrors(['policy_number' => 'Policy number is required.']);
        }

        $label = trim((string) ($validated['client_label'] ?? ''));
        if ($label === '') {
            $detail = $this->erp->getPolicyDetails($policy);
            if (is_array($detail)) {
                $label = trim((string) ($detail['life_assur'] ?? $detail['life_assured'] ?? $detail['client_name'] ?? $detail['name'] ?? ''));
            }
        }

        $actor = Auth::guard('vtiger')->user();

        UserClientAssignment::query()->updateOrCreate(
            [
                'userid' => (int) $validated['userid'],
                'policy_number' => $policy,
            ],
            [
                'client_label' => $label !== '' ? $label : null,
                'system' => $validated['system'] ?? null,
                'assigned_by' => $actor ? (int) $actor->id : null,
            ]
        );

        $this->profileAccess->clearClientAssignmentCacheForUser((int) $validated['userid']);

        return redirect()
            ->route('settings.crm', ['section' => 'client-access', 'user' => $validated['userid']])
            ->with('success', 'Client ' . $policy . ' assigned to ' . ($user->full_name ?? $user->user_name) . '.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        if (! UserClientAssignment::tableExists()) {
            return back()->with('error', 'Client access storage is not set up. Run: php artisan migrate');
        }

        $validated = $request->validate([
            'userid' => 'required|integer|min:1',
            'csv_file' => 'required|file|max:5120',
            'system' => 'nullable|string|in:group,individual,mortgage,group_pension',
        ], [
            'csv_file.required' => 'Choose a CSV file to upload.',
        ]);

        $user = VtigerUser::on('vtiger')->find($validated['userid']);
        if (! $user) {
            return back()->withErrors(['userid' => 'User not found.']);
        }

        $rows = [];
        if ($request->hasFile('csv_file')) {
            $ext = strtolower($request->file('csv_file')->getClientOriginalExtension());
            if (! in_array($ext, ['csv', 'txt'], true)) {
                return back()->withErrors(['csv_file' => 'Please upload a .csv or .txt file.'])->withInput();
            }
            $rows = $this->parseCsvUpload($request->file('csv_file'));
        }

        if ($rows === []) {
            return back()
                ->withErrors(['csv_file' => 'Upload a CSV file with at least one policy number, or use the sample template format.'])
                ->withInput();
        }

        $actor = Auth::guard('vtiger')->user();
        $added = 0;
        $skipped = 0;
        $affectedUserIds = [];

        foreach ($rows as $row) {
            $userId = $this->resolveUserId($row['user'] ?? '', (int) $validated['userid']);
            if (! $userId) {
                $skipped++;

                continue;
            }

            $policy = UserClientAssignment::normalizePolicyNumber($row['policy'] ?? '');
            if ($policy === '') {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                $detail = $this->erp->getPolicyDetails($policy);
                if (is_array($detail)) {
                    $label = trim((string) ($detail['life_assur'] ?? $detail['life_assured'] ?? $detail['client_name'] ?? ''));
                }
            }

            UserClientAssignment::query()->updateOrCreate(
                ['userid' => $userId, 'policy_number' => $policy],
                [
                    'client_label' => $label !== '' ? $label : null,
                    'system' => $validated['system'] ?? null,
                    'assigned_by' => $actor ? (int) $actor->id : null,
                ]
            );
            $added++;
            $affectedUserIds[$userId] = true;
        }

        if ($added === 0) {
            $message = $skipped > 0
                ? 'No rows were imported. Check the user column (username or email) and policy numbers.'
                : 'No valid policy numbers were found in the file. Check the template and try again.';

            return back()->withErrors(['csv_file' => $message])->withInput();
        }

        foreach (array_keys($affectedUserIds) as $userId) {
            $this->profileAccess->clearClientAssignmentCacheForUser($userId);
        }

        $userCount = count($affectedUserIds);
        $success = $added . ' client assignment(s) imported for ' . $userCount . ' user' . ($userCount === 1 ? '' : 's') . '.';
        if ($skipped > 0) {
            $success .= ' ' . $skipped . ' row(s) skipped (user not found).';
        }

        return redirect()
            ->route('settings.crm', ['section' => 'client-access', 'user' => $validated['userid']])
            ->with('success', $success);
    }

    protected function resolveUserId(?string $reference, int $fallbackUserId): ?int
    {
        $reference = trim((string) $reference);
        if ($reference === '') {
            return $fallbackUserId > 0 ? $fallbackUserId : null;
        }

        if (ctype_digit($reference)) {
            $user = VtigerUser::on('vtiger')->where('id', (int) $reference)->where('status', 'Active')->first();

            return $user ? (int) $user->id : null;
        }

        $user = VtigerUser::on('vtiger')
            ->where('status', 'Active')
            ->where(function ($query) use ($reference) {
                $query->where('user_name', $reference)
                    ->orWhere('email1', $reference);
            })
            ->first();

        return $user ? (int) $user->id : null;
    }

    /**
     * @return list<array{user: string, policy: string, label: string}>
     */
    protected function parseCsvUpload($file): array
    {
        $content = (string) file_get_contents($file->getRealPath());
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $lines = preg_split('/\R/', $content) ?: [];

        if ($lines === []) {
            return [];
        }

        $firstLine = trim((string) ($lines[0] ?? ''));
        $delimiter = str_contains($firstLine, ';') && ! str_contains($firstLine, ',') ? ';' : ',';
        $headerMap = $this->mapCsvHeaders($firstLine, $delimiter);
        $hasHeader = $headerMap !== null;

        $rows = [];
        foreach ($lines as $index => $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            if ($index === 0 && $hasHeader) {
                continue;
            }

            $parsed = $this->parseCsvLine($line, $delimiter, $headerMap);
            if (($parsed['policy'] ?? '') !== '') {
                $rows[] = $parsed;
            }
        }

        return $rows;
    }

    /**
     * @return array{user: int, policy: int, label: int}|null
     */
    protected function mapCsvHeaders(string $firstLine, string $delimiter): ?array
    {
        if (! preg_match('/policy|user|client/i', $firstLine)) {
            return null;
        }

        $cells = str_getcsv($firstLine, $delimiter);
        $map = ['user' => -1, 'policy' => -1, 'label' => -1];

        foreach ($cells as $index => $header) {
            $key = strtolower(trim((string) $header));
            if (in_array($key, ['user', 'username', 'user_name', 'email', 'email1', 'staff'], true)) {
                $map['user'] = $index;
            } elseif (in_array($key, ['policy', 'policy_number', 'policy_no', 'policy no'], true)) {
                $map['policy'] = $index;
            } elseif (in_array($key, ['client', 'client_name', 'name', 'label', 'life_assured'], true)) {
                $map['label'] = $index;
            }
        }

        return $map['policy'] >= 0 ? $map : null;
    }

    /**
     * @param  array{user: int, policy: int, label: int}|null  $headerMap
     * @return array{user: string, policy: string, label: string}
     */
    protected function parseCsvLine(string $line, string $delimiter, ?array $headerMap): array
    {
        if ($headerMap !== null && str_contains($line, $delimiter)) {
            $cells = str_getcsv($line, $delimiter);

            return [
                'user' => trim((string) ($cells[$headerMap['user']] ?? '')),
                'policy' => trim((string) ($cells[$headerMap['policy']] ?? '')),
                'label' => $headerMap['label'] >= 0 ? trim((string) ($cells[$headerMap['label']] ?? '')) : '',
            ];
        }

        if (str_contains($line, $delimiter)) {
            $cells = str_getcsv($line, $delimiter);

            if (count($cells) >= 3) {
                return [
                    'user' => trim((string) ($cells[0] ?? '')),
                    'policy' => trim((string) ($cells[1] ?? '')),
                    'label' => trim((string) ($cells[2] ?? '')),
                ];
            }

            return [
                'user' => '',
                'policy' => trim((string) ($cells[0] ?? '')),
                'label' => trim((string) ($cells[1] ?? '')),
            ];
        }

        if (str_contains($line, '|')) {
            $parts = array_map('trim', explode('|', $line));

            return [
                'user' => $parts[0] ?? '',
                'policy' => $parts[1] ?? '',
                'label' => $parts[2] ?? '',
            ];
        }

        return ['user' => '', 'policy' => $line, 'label' => ''];
    }

    public function destroy(Request $request, int $assignment): RedirectResponse
    {
        if (! UserClientAssignment::tableExists()) {
            return back()->with('error', 'Client access storage is not set up.');
        }

        $row = UserClientAssignment::query()->find($assignment);
        if (! $row) {
            return back()->withErrors(['assignment' => 'Assignment not found.']);
        }

        $userId = (int) $row->userid;
        $row->delete();
        $this->profileAccess->clearClientAssignmentCacheForUser($userId);

        return redirect()
            ->route('settings.crm', ['section' => 'client-access', 'user' => $userId])
            ->with('success', 'Client assignment removed.');
    }

    public function downloadTemplate(Request $request)
    {
        $username = 'username';
        if ($request->filled('user')) {
            $selected = VtigerUser::on('vtiger')->find($request->get('user'));
            if ($selected) {
                $username = $selected->user_name;
            }
        }

        $lines = [
            'user,policy_number,client_name',
            $username . ',GEMIL001234,John Kamau',
            $username . ',GEMPPP0335,',
            $username . ',GEMIL009999,Jane Doe',
        ];

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="client-access-template.csv"',
        ]);
    }
}
