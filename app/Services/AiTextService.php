<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiTextService
{
    public function isConfigured(): bool
    {
        return (bool) config('services.openai.api_key');
    }

    /**
     * @return array{text: string|null, error: string|null, model: string}
     */
    public function generate(string $prompt, ?string $systemPrompt = null, int $maxTokens = 400, float $temperature = 0.4): array
    {
        $apiKey = (string) config('services.openai.api_key', '');
        $model = (string) config('services.openai.model', 'gpt-4o-mini');
        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $timeout = (int) config('services.openai.timeout', 30);

        if ($apiKey === '') {
            return [
                'text' => null,
                'error' => 'AI is not available right now. Please contact your administrator.',
                'model' => $model,
            ];
        }

        $messages = [];
        if ($systemPrompt !== null && trim($systemPrompt) !== '') {
            $messages[] = ['role' => 'system', 'content' => trim($systemPrompt)];
        }
        $messages[] = ['role' => 'user', 'content' => trim($prompt)];

        try {
            $response = Http::timeout($timeout)
                ->withToken($apiKey)
                ->post($baseUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => $maxTokens,
                    'temperature' => $temperature,
                ]);

            if (! $response->successful()) {
                return [
                    'text' => null,
                    'error' => 'AI could not process that request. Please try again.',
                    'model' => $model,
                ];
            }

            $content = data_get($response->json(), 'choices.0.message.content');
            if (! is_string($content) || trim($content) === '') {
                return [
                    'text' => null,
                    'error' => 'AI returned an empty response. Please try again.',
                    'model' => $model,
                ];
            }

            return [
                'text' => trim($content),
                'error' => null,
                'model' => $model,
            ];
        } catch (\Throwable $e) {
            return [
                'text' => null,
                'error' => 'AI is temporarily unavailable. Please try again later.',
                'model' => $model,
            ];
        }
    }
}
