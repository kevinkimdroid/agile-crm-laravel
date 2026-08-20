<?php

namespace App\Http\Controllers;

use App\Services\AiTextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiIntegrationController extends Controller
{
    public function index(AiTextService $ai): View
    {
        return view('ai.integration', [
            'aiConfigured' => $ai->isConfigured(),
        ]);
    }

    public function generate(Request $request, AiTextService $ai): RedirectResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:4000',
            'system_prompt' => 'nullable|string|max:1500',
            'max_tokens' => 'nullable|integer|min:64|max:2000',
            'temperature' => 'nullable|numeric|min:0|max:2',
        ]);

        $result = $ai->generate(
            (string) $validated['prompt'],
            isset($validated['system_prompt']) ? (string) $validated['system_prompt'] : null,
            (int) ($validated['max_tokens'] ?? 400),
            (float) ($validated['temperature'] ?? 0.4),
        );

        if ($result['error']) {
            return redirect()
                ->route('ai.integration')
                ->withInput()
                ->with('error', $result['error']);
        }

        return redirect()
            ->route('ai.integration')
            ->withInput()
            ->with('success', 'AI draft generated.')
            ->with('ai_result', $result['text'])
            ->with('ai_model', $result['model']);
    }
}
