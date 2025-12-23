<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GrammarCorrectionService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.mistral.ai/v1/chat/completions';
    private string $model = 'mistral-large-latest';

    public function __construct()
    {
        $this->apiKey = config('services.mistral.api_key');
    }

    /**
     * Correct grammar in a paragraph
     */
    public function correctGrammar(string $text): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional grammar correction assistant. Correct all grammar, spelling, and punctuation errors in the text. Maintain the original meaning and tone. Return only the corrected text without explanations.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Correct the following text:\n\n{$text}"
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 4000,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $correctedText = $data['content'][0]['text'] ??
                    $data['choices'][0]['message']['content'] ?? '';

                return [
                    'success' => true,
                    'original' => $text,
                    'corrected' => trim($correctedText),
                    'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                ];
            }

            return [
                'success' => false,
                'error' => 'API request failed: ' . $response->status(),
                'message' => $response->json()['message'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            Log::error('Grammar correction failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Correct grammar with detailed changes
     */
    public function correctWithDetails(string $text): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional grammar correction assistant. Correct all grammar, spelling, and punctuation errors. Return a JSON object with: {"corrected": "corrected text", "changes": ["list of changes made"]}'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Correct and explain changes:\n\n{$text}"
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 4000,
                'response_format' => ['type' => 'json_object'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '{}';
                $result = json_decode($content, true);

                return [
                    'success' => true,
                    'original' => $text,
                    'corrected' => $result['corrected'] ?? '',
                    'changes' => $result['changes'] ?? [],
                    'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                ];
            }

            return [
                'success' => false,
                'error' => 'API request failed: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Grammar correction with details failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process long text by splitting into chunks
     */
    public function correctLongText(string $text, int $chunkSize = 2000): array
    {
        $chunks = $this->splitTextIntoChunks($text, $chunkSize);
        $correctedChunks = [];
        $totalTokens = 0;

        foreach ($chunks as $index => $chunk) {
            $result = $this->correctGrammar($chunk);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => "Failed at chunk " . ($index + 1),
                    'details' => $result['error'] ?? 'Unknown error',
                ];
            }

            $correctedChunks[] = $result['corrected'];
            $totalTokens += $result['tokens_used'] ?? 0;
        }

        return [
            'success' => true,
            'original' => $text,
            'corrected' => implode("\n\n", $correctedChunks),
            'chunks_processed' => count($chunks),
            'total_tokens' => $totalTokens,
        ];
    }

    /**
     * Split text into manageable chunks
     */
    private function splitTextIntoChunks(string $text, int $chunkSize): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $currentChunk = '';

        foreach ($sentences as $sentence) {
            if (strlen($currentChunk . ' ' . $sentence) > $chunkSize && !empty($currentChunk)) {
                $chunks[] = trim($currentChunk);
                $currentChunk = $sentence;
            } else {
                $currentChunk .= (empty($currentChunk) ? '' : ' ') . $sentence;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }
}
