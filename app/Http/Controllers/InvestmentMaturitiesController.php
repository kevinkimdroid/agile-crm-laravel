<?php

namespace App\Http\Controllers;

use App\Services\InvestmentMaturityService;
use App\Services\MaturityClientNotificationService;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class InvestmentMaturitiesController extends Controller
{
    public function __construct(protected InvestmentMaturityService $service) {}

    public function index(Request $request): View
    {
        $days = max(1, min(30, (int) $request->get('days', 14)));
        $search = trim((string) $request->get('search', ''));
        $product = trim((string) $request->get('product', ''));
        $notifyStatus = trim((string) $request->get('notify_status', ''));
        $sort = trim((string) $request->get('sort', 'maturity'));
        $dir = strtolower((string) $request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = in_array((int) $request->get('per_page', 25), [25, 50, 100], true)
            ? (int) $request->get('per_page', 25)
            : 25;
        $to = (string) config('maturities.investment_notifications.to', 'douglas.nyakwara@geminialife.co.ke');
        $cc = $this->ccRecipients();
        $notifyService = app(MaturityClientNotificationService::class);

        $error = null;
        $rows = collect();
        $products = collect();
        $stats = ['total' => 0, 'today' => 0, 'this_week' => 0, 'pending_notify' => 0];
        $paginator = new LengthAwarePaginator([], 0, $perPage, 1, [
            'path' => route('support.investment-maturities'),
            'query' => $request->query(),
        ]);

        try {
            $rows = $this->service->dueWithinDays($days);
            $rows = $this->service->withNotificationStatus($rows, $to);
            $rows = $notifyService->enrichContactsFromClientDetails($rows, 'pol_policy_no');
            $rows = $notifyService->annotateRows($rows, 'investment', 'pol_policy_no', 'pol_maturity_date');

            $products = $rows
                ->map(fn ($row) => trim((string) ($row->product ?? '')))
                ->filter(fn ($p) => $p !== '')
                ->unique()
                ->sort()
                ->values();

            $rows = $notifyService->filterBySearch($rows, $search);
            $rows = $this->applyProductFilter($rows, $product);
            $rows = $this->applyNotifyFilter($rows, $notifyStatus);
            $stats = $this->buildStats($rows);
            $rows = $this->sortRows($rows, $sort, $dir);

            $page = max(1, (int) $request->get('page', 1));
            $paginator = new LengthAwarePaginator(
                $rows->forPage($page, $perPage)->values(),
                $rows->count(),
                $perPage,
                $page,
                ['path' => route('support.investment-maturities'), 'query' => $request->query()]
            );
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Log::error('Investment maturities load failed', ['error' => $e->getMessage()]);
        }

        return view('support.investment-maturities', [
            'rows' => $paginator,
            'stats' => $stats,
            'days' => $days,
            'search' => $search,
            'product' => $product,
            'notifyStatus' => $notifyStatus,
            'sort' => $sort,
            'dir' => $dir,
            'perPage' => $perPage,
            'to' => $to,
            'cc' => $cc,
            'error' => $error,
            'trackingEnabled' => $this->service->notificationsTableExists(),
            'notifyService' => $notifyService,
            'smsConfigured' => app(\App\Services\AdvantaSmsService::class)->isConfigured(),
            'productCodes' => config('maturities.investment_notifications.product_codes', []),
            'products' => $products,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array{total: int, today: int, this_week: int, pending_notify: int}
     */
    protected function buildStats($rows): array
    {
        $stats = [
            'total' => $rows->count(),
            'today' => 0,
            'this_week' => 0,
            'pending_notify' => 0,
        ];

        $today = now()->startOfDay();
        $weekEnd = now()->addDays(7)->startOfDay();

        foreach ($rows as $row) {
            try {
                $maturity = \Carbon\Carbon::parse($row->pol_maturity_date ?? '')->startOfDay();
            } catch (\Throwable) {
                continue;
            }

            if ($maturity->isSameDay($today)) {
                $stats['today']++;
            }
            if ($maturity->gte($today) && $maturity->lte($weekEnd)) {
                $stats['this_week']++;
            }
            if (empty($row->client_notified_email) && empty($row->client_notified_sms)) {
                $stats['pending_notify']++;
            }
        }

        return $stats;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     */
    protected function applyProductFilter($rows, string $product): Collection
    {
        if ($product === '') {
            return $rows;
        }

        return $rows->filter(fn ($row) => trim((string) ($row->product ?? '')) === $product)->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     */
    protected function applyNotifyFilter($rows, string $status): Collection
    {
        return match ($status) {
            'pending' => $rows->filter(fn ($row) => empty($row->client_notified_email) && empty($row->client_notified_sms))->values(),
            'notified' => $rows->filter(fn ($row) => ! empty($row->client_notified_email) || ! empty($row->client_notified_sms))->values(),
            default => $rows,
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     */
    protected function sortRows($rows, string $sort, string $dir): Collection
    {
        $getter = match ($sort) {
            'policy' => fn ($row) => strtolower(trim((string) ($row->pol_policy_no ?? ''))),
            'client' => fn ($row) => strtolower(trim((string) ($row->full_name ?? ''))),
            'product' => fn ($row) => strtolower(trim((string) ($row->product ?? ''))),
            default => fn ($row) => (string) ($row->pol_maturity_date ?? ''),
        };

        return $dir === 'desc'
            ? $rows->sortByDesc($getter)->values()
            : $rows->sortBy($getter)->values();
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:30',
            'resend' => 'nullable|boolean',
        ]);
        $days = (int) ($validated['days'] ?? 14);
        $resend = (bool) ($validated['resend'] ?? false);
        $to = trim((string) config('maturities.investment_notifications.to', 'douglas.nyakwara@geminialife.co.ke'));
        $cc = $this->ccRecipients();

        if (! $this->service->notificationsTableExists()) {
            return redirect()->route('support.investment-maturities', ['days' => $days])
                ->with('error', 'Notification tracking table missing. Run php artisan migrate.');
        }

        try {
            $due = $this->service->dueWithinDays($days);
            $unsent = $this->service->unsentRows($due, $to);
            $targetRows = $resend ? $due : $unsent;

            if ($targetRows->isEmpty()) {
                return redirect()->route('support.investment-maturities', ['days' => $days])
                    ->with('success', 'No new investment maturities to email. Already-sent rows were skipped.');
            }

            $subject = '[Maturities] Investment policies due in next ' . $days . ' days (' . $targetRows->count() . ')';
            $html = view('emails.investment-maturities-notification', [
                'rows' => $targetRows,
                'days' => $days,
                'generatedAt' => now(),
                'resend' => $resend,
            ])->render();

            if (! $this->sendNotificationEmail($to, $cc, $subject, $html)) {
                return redirect()->route('support.investment-maturities', ['days' => $days])
                    ->with('error', 'Failed to send email. Check mail connectivity (Graph/SMTP).');
            }

            $this->service->markAsSent($targetRows, $to, $cc !== [] ? implode(',', $cc) : null);

            return redirect()->route('support.investment-maturities', ['days' => $days])
                ->with('success', 'Email sent to ' . $this->recipientLabel($to, $cc) . ' (' . $targetRows->count() . ' row(s)).');
        } catch (\Throwable $e) {
            Log::error('Investment maturities email send failed', ['error' => $e->getMessage()]);

            return redirect()->route('support.investment-maturities', ['days' => $days])
                ->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    /**
     * @return array<int, string>
     */
    private function ccRecipients(): array
    {
        $cc = config('maturities.investment_notifications.cc', []);
        if (is_string($cc)) {
            $cc = explode(',', $cc);
        }

        return array_values(array_filter(array_map(fn ($value) => trim((string) $value), (array) $cc)));
    }

    /**
     * @param  array<int, string>  $cc
     */
    private function recipientLabel(string $to, array $cc): string
    {
        if ($cc === []) {
            return $to;
        }

        return $to . ' (cc: ' . implode(', ', $cc) . ')';
    }

    /**
     * @param  array<int, string>  $cc
     */
    private function sendNotificationEmail(string $to, array $cc, string $subject, string $html): bool
    {
        $graph = app(MicrosoftGraphMailService::class);
        if ($graph->isConfigured()) {
            $ok = $graph->sendMail($to, null, $subject, $html, true);
            if (! $ok) {
                Log::warning('Investment maturities mail: Graph send failed for primary recipient', ['to' => $to]);
            } else {
                foreach ($cc as $ccAddress) {
                    $ccOk = $graph->sendMail($ccAddress, null, $subject, $html, true);
                    if (! $ccOk) {
                        Log::warning('Investment maturities mail: Graph send failed for CC recipient', ['cc' => $ccAddress]);
                    }
                }

                return true;
            }
        }

        $maxAttempts = 3;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                Mail::send([], [], function ($message) use ($to, $cc, $subject, $html) {
                    $message->to($to)->subject($subject)->setBody($html, 'text/html');
                    if ($cc !== []) {
                        $message->cc($cc);
                    }
                });

                return true;
            } catch (\Throwable $e) {
                Log::warning('Investment maturities mail: SMTP send failed', [
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'error' => $e->getMessage(),
                ]);
                if ($attempt < $maxAttempts) {
                    usleep(800 * 1000);
                }
            }
        }

        return false;
    }
}
