<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    protected string $ollamaUrl;
    protected string $ollamaModel;

    public function __construct()
    {
        $this->ollamaUrl = env('OLLAMA_URL', 'http://127.0.0.1:11434');
        $this->ollamaModel = env('OLLAMA_MODEL', 'llama3');
    }

    /**
     * Check if Ollama is running locally.
     */
    public function isOllamaAvailable(): bool
    {
        try {
            $response = Http::timeout(2)->get("{$this->ollamaUrl}/api/tags");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate a creative mock submission from Ollama.
     *
     * @return array{email: string, content: string}
     */
    public function generateMockItem(): array
    {
        if (!$this->isOllamaAvailable()) {
            throw new \Exception("Local Ollama is not configured or active.");
        }

        try {
            $prompt = "Generate a creative, highly realistic post or ticket submission for a content moderation queue.
            Choose randomly between these three types:
            1. A highly sophisticated crypto, yield-farming, or bitcoin phishing spam.
            2. An urgent operational account issue (e.g. locked account, unauthorized transaction alert).
            3. A safe, polite, professional support ticket or user message.

            Return your response ONLY in valid JSON format matching this schema:
            {
              \"email\": \"author email address matching the persona\",
              \"content\": \"the actual creative, fully-written body content\"
            }
            Do not include any markdown code block wrappers. Return raw JSON.";

            // Auto-detect installed models in Ollama to ensure zero configuration friction
            $modelsResponse = Http::timeout(2)->get("{$this->ollamaUrl}/api/tags");
            $model = $this->ollamaModel;

            if ($modelsResponse->successful()) {
                $modelsList = $modelsResponse->json()['models'] ?? [];
                if (!empty($modelsList)) {
                    $hasModel = false;
                    foreach ($modelsList as $m) {
                        if (isset($m['name'])) {
                            $itemModelName = strtolower($m['name']);
                            $targetModelName = strtolower($this->ollamaModel);
                            
                            if ($itemModelName === $targetModelName || $itemModelName === $targetModelName . ':latest' || $targetModelName === $itemModelName . ':latest') {
                                $hasModel = true;
                                $model = $m['name'];
                                break;
                            }
                        }
                    }
                    // Fall back to the first available local model if the preferred model is not pulled.
                    if (!$hasModel && isset($modelsList[0]['name'])) {
                        $model = $modelsList[0]['name'];
                    }
                }
            }

            $response = Http::timeout(90)->post("{$this->ollamaUrl}/api/generate", [
                'model' => $model,
                'prompt' => $prompt,
                'format' => 'json',
                'stream' => false
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['response'] ?? '';
                
                $parsed = json_decode($text, true);
                if (isset($parsed['email']) && isset($parsed['content'])) {
                    return [
                        'email' => $parsed['email'],
                        'content' => $parsed['content'],
                    ];
                }
            }

            Log::error("Ollama response mismatch: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Failed to generate with Ollama: " . $e->getMessage());
        }

        throw new \Exception("Ollama generation failed.");
    }

    /**
     * Generate a polite, custom rejection email draft using local Ollama.
     */
    public function generateRejectionEmailDraft(string $content, string $authorEmail, ?string $reason): string
    {
        $reason = $reason ?: 'Content did not comply with our standard guidelines.';

        if (!$this->isOllamaAvailable()) {
            return $this->getFallbackRejectionDraft($content, $reason);
        }

        try {
            $prompt = "Draft a polite, professional, and clear rejection email from the ReviewQueue Moderation Hub to a user whose submission was rejected.
            
            Recipient Email: {$authorEmail}
            Reviewer's Reason for Rejection: {$reason}
            Original Submitted Content: {$content}
            
            Instructions:
            - Write in a highly professional, constructive, and polite tone.
            - Explain the rejection based on the reviewer's reasoning.
            - Include constructive advice on how they can submit policy-compliant content in the future.
            - Do not include subject lines, markdown signatures, or HTML templates. Return ONLY the drafted body text of the email starting with a greeting and ending with a signature.";

            // Auto-detect installed models
            $modelsResponse = Http::timeout(2)->get("{$this->ollamaUrl}/api/tags");
            $model = $this->ollamaModel;

            if ($modelsResponse->successful()) {
                $modelsList = $modelsResponse->json()['models'] ?? [];
                if (!empty($modelsList)) {
                    $hasModel = false;
                    foreach ($modelsList as $m) {
                        if (isset($m['name'])) {
                            $itemModelName = strtolower($m['name']);
                            $targetModelName = strtolower($this->ollamaModel);
                            if ($itemModelName === $targetModelName || $itemModelName === $targetModelName . ':latest' || $targetModelName === $itemModelName . ':latest') {
                                $hasModel = true;
                                $model = $m['name'];
                                break;
                            }
                        }
                    }
                    if (!$hasModel && isset($modelsList[0]['name'])) {
                        $model = $modelsList[0]['name'];
                    }
                }
            }

            $response = Http::timeout(45)->post("{$this->ollamaUrl}/api/generate", [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $draft = trim($data['response'] ?? '');
                if (!empty($draft)) {
                    return $draft;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to generate rejection draft with Ollama: " . $e->getMessage());
        }

        return $this->getFallbackRejectionDraft($content, $reason);
    }

    /**
     * Premium static fallback rejection email text.
     */
    protected function getFallbackRejectionDraft(string $content, string $reason): string
    {
        $snippet = strlen($content) > 120 ? substr($content, 0, 120) . '...' : $content;
        return "Dear Submitter,\n\n" .
               "Thank you for sharing your submission with our platform. After careful review by our moderation team, we regret to inform you that your content has been rejected and will not be published.\n\n" .
               "Reason for Rejection:\n" .
               "• " . $reason . "\n\n" .
               "Original Content Reference:\n" .
               "\"" . $snippet . "\"\n\n" .
               "If you believe this decision was made in error or would like to submit updated content that adheres to our policies, please contact support or revise your submission details.\n\n" .
               "Warm regards,\n" .
               "ReviewQueue Moderation Hub";
    }
}
