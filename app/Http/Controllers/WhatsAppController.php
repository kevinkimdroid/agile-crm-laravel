<?php

namespace App\Http\Controllers;

use App\Services\AutoTicketFromWhatsAppService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WhatsAppController extends Controller
{
    public function __construct(
        private WhatsAppService $whatsapp,
        private AutoTicketFromWhatsAppService $autoTicket,
    ) {}

    public function index(Request $request): View
    {
        $company = $this->whatsapp->getCompanyProfile();
        $sandbox = $company['sandbox'] ?? false;
        if ($sandbox) {
            $this->whatsapp->ensureSandboxDemoConversation();
        }
        $configured = $company['configured'];
        $conversations = $configured ? $this->whatsapp->getConversations() : [];

        $activePhone = $request->query('phone');
        if ($activePhone) {
            $activePhone = $this->whatsapp->normalizePhonePublic((string) $activePhone);
        }

        $activeContact = null;
        $messages = [];
        if ($configured && $activePhone) {
            $messages = $this->whatsapp->getThreadMessages($activePhone);
            foreach ($conversations as $conv) {
                if ($conv['phone'] === $activePhone) {
                    $activeContact = $conv;
                    break;
                }
            }
            if (!$activeContact) {
                $activeContact = [
                    'phone' => $activePhone,
                    'name' => null,
                    'display_phone' => $this->whatsapp->formatPhoneDisplay($activePhone),
                    'preview' => null,
                    'last_at' => null,
                    'last_at_human' => null,
                ];
            }
        }

        $activeTicket = ($configured && $activePhone)
            ? $this->autoTicket->getTicketSummaryForPhone($activePhone)
            : null;

        return view('marketing.whatsapp-chat', [
            'company' => $company,
            'configured' => $configured,
            'sandbox' => $sandbox,
            'setupInstructions' => $this->whatsapp->setupInstructions(),
            'conversations' => $conversations,
            'activePhone' => $activePhone,
            'activeContact' => $activeContact,
            'activeTicket' => $activeTicket,
            'messages' => $messages,
        ]);
    }

    public function send(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:4096'],
        ], [
            'phone.required' => 'Recipient phone number is required.',
            'message.required' => 'Message text is required.',
        ]);

        $phone = $this->whatsapp->normalizePhonePublic($validated['phone']);
        $wantsJson = $request->expectsJson() || $request->boolean('ajax');

        if (!$this->whatsapp->isConfigured()) {
            if ($wantsJson) {
                return response()->json(['ok' => false, 'error' => $this->whatsapp->setupInstructions()], 422);
            }

            return redirect()->route('marketing.whatsapp')
                ->with('error', $this->whatsapp->setupInstructions());
        }

        $sentBy = Auth::guard('vtiger')->user()?->name
            ?? Auth::guard('vtiger')->user()?->username
            ?? Auth::user()?->name
            ?? null;
        $result = $this->whatsapp->sendTextMessage($phone, $validated['message'], $sentBy);

        if (!$result['success']) {
            if ($wantsJson) {
                return response()->json(['ok' => false, 'error' => $result['error'] ?? 'Send failed'], 422);
            }

            return redirect()->route('marketing.whatsapp', ['phone' => $phone])
                ->with('error', $result['error'] ?? 'Failed to send WhatsApp message.');
        }

        if ($wantsJson) {
            $interaction = null;
            if ($result['interaction_id']) {
                $interaction = \App\Models\SocialInteraction::find($result['interaction_id']);
            }

            return response()->json([
                'ok' => true,
                'message' => $interaction ? $this->whatsapp->formatMessage($interaction) : null,
                'phone' => $phone,
            ]);
        }

        return redirect()->route('marketing.whatsapp', ['phone' => $phone])
            ->with('success', 'Message sent from Kenya Orient WhatsApp.');
    }

    public function poll(Request $request): JsonResponse
    {
        if (!$this->whatsapp->isConfigured()) {
            return response()->json(['ok' => false, 'error' => 'Not configured'], 422);
        }

        $phone = $request->query('phone');
        $since = $request->query('since');

        $payload = [
            'ok' => true,
            'conversations' => $this->whatsapp->getConversations(),
            'messages' => [],
        ];

        if ($phone) {
            $phone = $this->whatsapp->normalizePhonePublic((string) $phone);
            $payload['messages'] = $this->whatsapp->getThreadMessages($phone, $since ? (string) $since : null);
            $payload['phone'] = $phone;
        }

        return response()->json($payload);
    }

    public function simulateInbound(Request $request): RedirectResponse|JsonResponse
    {
        if (!$this->whatsapp->isSandbox()) {
            if ($request->expectsJson() || $request->boolean('ajax')) {
                return response()->json(['ok' => false, 'error' => 'Sandbox mode is not enabled.'], 403);
            }

            return redirect()->route('marketing.whatsapp')->with('error', 'Sandbox mode is not enabled.');
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:4096'],
            'contact_name' => ['nullable', 'string', 'max:120'],
        ]);

        $phone = $this->whatsapp->normalizePhonePublic($validated['phone']);
        $result = $this->whatsapp->simulateInboundMessage(
            $phone,
            $validated['message'],
            $validated['contact_name'] ?? null
        );

        if (!$result['success']) {
            if ($request->expectsJson() || $request->boolean('ajax')) {
                return response()->json(['ok' => false, 'error' => $result['error'] ?? 'Simulate failed'], 422);
            }

            return redirect()->route('marketing.whatsapp', ['phone' => $phone])
                ->with('error', $result['error'] ?? 'Could not simulate message.');
        }

        if ($request->expectsJson() || $request->boolean('ajax')) {
            return response()->json([
                'ok' => true,
                'phone' => $phone,
                'message' => $result['message'],
                'ticket' => $result['ticket'] ?? null,
                'conversations' => $this->whatsapp->getConversations(),
            ]);
        }

        $flash = 'Sandbox: customer message simulated.';
        if (!empty($result['ticket']['ticket_no'])) {
            $flash .= ' Ticket ' . $result['ticket']['ticket_no'] . ' created.';
        }

        return redirect()->route('marketing.whatsapp', ['phone' => $phone])
            ->with('success', $flash);
    }
}
