<?php

namespace App\Http\Controllers;

use App\Services\MaturityClientNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaturityClientNotificationController extends Controller
{
    public function lookupContact(Request $request, MaturityClientNotificationService $notify): JsonResponse
    {
        $policy = trim((string) $request->get('policy', ''));
        if ($policy === '') {
            return response()->json(['error' => 'Policy number required.'], 422);
        }

        return response()->json($notify->contactFromClientDetails($policy));
    }

    public function send(Request $request, MaturityClientNotificationService $notify): RedirectResponse
    {
        $validated = $request->validate([
            'screen' => 'required|string|in:maturities,investment,mortgage',
            'channel' => 'required|string|in:email,sms',
            'event_type' => 'required|string|in:maturity,renewal',
            'policy_number' => 'required|string|max:64',
            'event_date' => 'required|date',
            'client_name' => 'nullable|string|max:255',
            'product' => 'nullable|string|max:255',
            'to_email' => 'nullable|email|max:255',
            'to_phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:2000',
        ]);

        $toEmail = trim((string) ($validated['to_email'] ?? ''));
        $toPhone = trim((string) ($validated['to_phone'] ?? ''));
        if ($toEmail === '' || $toPhone === '') {
            $contact = $notify->contactFromClientDetails($validated['policy_number']);
            $toEmail = $toEmail !== '' ? $toEmail : ($contact['email'] ?? '');
            $toPhone = $toPhone !== '' ? $toPhone : ($contact['phone'] ?? '');
        }

        $userId = Auth::guard('vtiger')->id() ?? Auth::id();
        $result = $notify->send(
            $validated['channel'],
            $validated['screen'],
            $validated['event_type'],
            $validated['policy_number'],
            $validated['event_date'],
            $validated['client_name'] ?? null,
            $validated['product'] ?? null,
            $toEmail !== '' ? $toEmail : null,
            $toPhone !== '' ? $toPhone : null,
            $validated['subject'] ?? null,
            $validated['message'] ?? null,
            $userId ? (int) $userId : null,
        );

        $back = $this->safeRedirect($request->input('return_url'));

        if (! $result['ok']) {
            return redirect()->to($back)->withInput()->with('error', $result['error']);
        }

        $channelLabel = $validated['channel'] === 'email' ? 'Email' : 'SMS';

        return redirect()->to($back)->with('success', $channelLabel.' sent to client for policy '.$validated['policy_number'].'.');
    }

    private function safeRedirect(?string $url): string
    {
        $fallback = route('support.maturities');
        if ($url === null || $url === '') {
            return $fallback;
        }

        $parsed = parse_url($url);
        if (isset($parsed['host'])) {
            return $fallback;
        }

        return str_starts_with($url, '/') ? $url : $fallback;
    }
}
