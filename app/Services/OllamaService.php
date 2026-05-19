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

            Do NOT return a schema or template definition. Instead, return a single concrete instance of a submission with real mock content.
            Return your response ONLY as a raw JSON object containing exactly two keys:
            - \"email\": a realistic, creative email address matching the chosen persona.
            - \"content\": the fully-written message or post body (at least 2-3 sentences).

            Example output structure:
            {
              \"email\": \"crypto-trader42@gmail.net\",
              \"content\": \"Urgent: Please check our new decentralized farming pool. We have 500% APY available for the next 24 hours only. Join now at pool-yield.com!\"
            }

            Do not include any markdown code block wrappers, explanations, or trailing characters. Return only the raw JSON object.";

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
            - Do not include subject lines, markdown signatures, or HTML templates.
            - NEVER use ANY bracketed placeholders or template variables (e.g., '[User]', '[User Name]', '[Name]', '[Date]', '[Your Name]', '[Community Name]', '[insert...]'). All text must be fully written out, literal, and complete.
            - If you do not know the user's name, greet them simply as 'Dear Submitter' or 'Dear User'.
            - Sign the email strictly as 'ReviewQueue Moderation Hub'. Do not leave placeholders for your name or the community name.
            - Return ONLY the drafted body text of the email starting with a greeting and ending with a signature.";

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
     * Generate a formal permanent account ban and suspension notice using local Ollama model.
     */
    public function generateAccountBanEmailDraft(string $email, string $content, string $reason): string
    {
        if (!$this->isOllamaAvailable()) {
            return $this->getFallbackBanDraft($email, $content, $reason);
        }

        try {
            $prompt = "Write a highly professional, firm, yet polite Account Suspension & Permanent Ban Notice to a user with email address '{$email}'.
            They have repeatedly violated our content policies, exceeding our strike limit.
            
            Violating Content Snippet:
            '{$content}'
            
            Moderator Notes/Reason for Suspension:
            '{$reason}'
            
            Instructions:
            - Write in a firm, formal, clear, and highly professional tone.
            - Explicitly state that their email '{$email}' is banned from making future submissions.
            - Clarify that their account/email address has been permanently suspended due to repeated guidelines violations (reaching Strike 3).
            - Explain the suspension based on the moderator's notes and the violating content.
            - Do not include subject lines, markdown signatures, or HTML templates.
            - NEVER use ANY bracketed placeholders or template variables (e.g., '[User]', '[User Name]', '[Name]', '[Date]', '[Your Name]', '[Community Name]', '[insert...]'). All text must be fully written out, literal, and complete.
            - If you do not know the user's name, greet them simply as 'Dear Submitter' or 'Dear User'.
            - Sign the email strictly as 'ReviewQueue Moderation Hub'. Do not leave placeholders for your name or the community name.
            - Do not write dynamic date placeholders like '[Date]'. If referring to a date or time, refer to it generally as 'recently' or 'in your recent submissions'.
            - Return ONLY the drafted body text of the email starting with a formal greeting and ending with a signature.";

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
            Log::warning("Failed to generate ban draft with Ollama: " . $e->getMessage());
        }

        return $this->getFallbackBanDraft($email, $content, $reason);
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

    /**
     * Premium static fallback account ban notice email text.
     */
    protected function getFallbackBanDraft(string $email, string $content, string $reason): string
    {
        $snippet = strlen($content) > 120 ? substr($content, 0, 120) . '...' : $content;
        return "Dear Submitter,\n\n" .
               "This is a formal notice that your email address ({$email}) has been permanently suspended from submitting content to our platform.\n\n" .
               "Our Trust & Safety system identified repeated policy violations associated with your submissions, exceeding our allowed community standards limit (Strike 3 policy exceeded).\n\n" .
               "Official Ban Reason / Notes:\n" .
               "• " . $reason . "\n\n" .
               "Violating Submission Content:\n" .
               "\"" . $snippet . "\"\n\n" .
               "As a result of this permanent suspension, any future submissions sent from your email address will be automatically rejected and blocked by our gateway filter.\n\n" .
               "If you believe this suspension was made in error, you may reply to this notice to file an appeal with our senior moderation administration.\n\n" .
               "Sincerely,\n" .
               "ReviewQueue Moderation & Safety Hub";
    }
}
