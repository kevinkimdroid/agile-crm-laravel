<?php

namespace App\Http\Controllers;

use App\Models\MpesaStkTransaction;
use App\Services\MpesaStkPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MpesaStkPushController extends Controller
{
    public function initiate(Request $request, MpesaStkPushService $mpesa): RedirectResponse
    {
        $validated = $request->validate([
            'policy_number' => 'required|string|max:64',
            'phone' => 'required|string|max:20',
            'amount' => 'required|numeric|min:1|max:999999',
            'client_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:100',
            'return_url' => 'nullable|string|max:2000',
        ]);

        $policy = trim($validated['policy_number']);
        if (! user_can_access_client_policy($policy)) {
            return redirect()->back()->with('error', 'You do not have access to this client.');
        }

        $userId = Auth::guard('vtiger')->id() ?? Auth::id();
        $result = $mpesa->initiate(
            $policy,
            $validated['phone'],
            (float) $validated['amount'],
            $validated['client_name'] ?? null,
            $validated['description'] ?? null,
            $userId ? (int) $userId : null,
        );

        $back = $this->safeRedirect($request->input('return_url'), $policy);

        if (! $result['ok']) {
            return redirect()->to($back)->with('error', $result['error']);
        }

        $normalizedPhone = $mpesa->normalizePhone($validated['phone']);
        $successMessage = $mpesa->isSandboxSimulate()
            ? 'Sandbox: simulated STK push for '.$normalizedPhone.' (KES '.number_format((float) $validated['amount'], 0).'). No real M-Pesa prompt was sent.'
            : 'M-Pesa prompt sent to '.$normalizedPhone.'. Check the phone and enter the M-Pesa PIN when it appears.';

        $redirect = redirect()->to($back)->with('success', $successMessage);

        if ($result['transaction']?->id) {
            $redirect->with('mpesa_stk_transaction_id', $result['transaction']->id);
        }

        return $redirect;
    }

    public function callback(Request $request, MpesaStkPushService $mpesa): JsonResponse
    {
        $payload = $request->all();
        if ($payload === []) {
            $decoded = json_decode($request->getContent(), true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        try {
            $mpesa->handleCallback($payload);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function status(MpesaStkTransaction $transaction, MpesaStkPushService $mpesa): JsonResponse
    {
        if (! user_can_access_client_policy($transaction->policy_number)) {
            abort(403);
        }

        if ($transaction->isPending()) {
            $transaction = $mpesa->syncPendingTransaction($transaction);
        }

        return response()->json([
            'status' => $transaction->status,
            'result_code' => $transaction->result_code,
            'result_desc' => $transaction->result_desc,
            'mpesa_receipt_number' => $transaction->mpesa_receipt_number,
            'completed_at' => optional($transaction->completed_at)?->toIso8601String(),
        ]);
    }

    private function safeRedirect(?string $url, string $policy): string
    {
        if ($url !== null && $url !== '' && str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        return route('support.clients.show', ['policy' => $policy]);
    }
}
