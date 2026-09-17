<?php

namespace App\Services\AI;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements AIProviderInterface
{
    public function getProviderName(): string
    {
        return 'gemini';
    }

    public function generateText(string $prompt, array $options = []): array
    {
        $apiKey = Setting::get('gemini_api_key', config('services.gemini.api_key', env('GEMINI_API_KEY')));
        $model = $options['model'] ?? Setting::get('gemini_model', 'gemini-1.5-flash');

        if (!$apiKey) {
            // Graceful fallback to MockAIProvider when key is not yet set
            return (new MockAIProvider)->generateText($prompt, $options);
        }

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $parts = [];
            if (!empty($options['image_base64'])) {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $options['image_mime'] ?? 'image/jpeg',
                        'data' => $options['image_base64'],
                    ]
                ];
            }
            $parts[] = ['text' => $prompt];

            $generationConfig = [
                'temperature' => $options['temperature'] ?? 0.5,
                'maxOutputTokens' => $options['max_tokens'] ?? 1500,
            ];

            if (!empty($options['json_mode'])) {
                $generationConfig['responseMimeType'] = 'application/json';
            }

            $body = [
                'contents' => [
                    [
                        'parts' => $parts
                    ]
                ],
                'generationConfig' => $generationConfig,
            ];

            if (!empty($options['system_instruction'])) {
                $body['systemInstruction'] = [
                    'parts' => [
                        ['text' => $options['system_instruction']]
                    ]
                ];
            }

            $response = Http::timeout(15)->post($url, $body);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $tokens = $data['usageMetadata']['totalTokenCount'] ?? (int)(strlen($prompt . $text) / 4);

                return [
                    'success' => true,
                    'text' => trim($text),
                    'tokens' => $tokens,
                    'raw' => $data,
                ];
            }

            Log::error('Gemini API request failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('Gemini API exception', ['error' => $e->getMessage()]);
        }

        // Fallback to local intelligent mock if network or API error occurs
        return (new MockAIProvider)->generateText($prompt, $options);
    }
}
