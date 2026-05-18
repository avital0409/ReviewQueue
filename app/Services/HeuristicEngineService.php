<?php

namespace App\Services;

class HeuristicEngineService
{
    /**
     * Analyze a given text content and return heuristic analysis results.
     *
     * @param string $content
     * @return array{risk_score: int, heuristic_flags: array<string>, auto_suggestion: string}
     */
    public function analyze(string $content): array
    {
        $score = 0;
        $flags = [];

        // 1. Financial Risk Check
        $financialKeywords = ['crypto', 'bitcoin', 'wallet', 'yield', 'invest', 'get rich'];
        foreach ($financialKeywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $score += 35;
                $flags[] = 'financial_keywords';
                break; // Ensure we flag once
            }
        }

        // 2. External Link Scan
        $linkKeywords = ['http', 'https', '.com', '.xyz', '.net', '.ru'];
        foreach ($linkKeywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $score += 25;
                $flags[] = 'external_links';
                break; // Ensure we flag once
            }
        }

        // 3. Urgent Intent Check
        $urgentPhrases = ['urgent', 'immediately', 'unauthorized action', 'account lock'];
        foreach ($urgentPhrases as $phrase) {
            if (stripos($content, $phrase) !== false) {
                $score += 25;
                $flags[] = 'urgent_language';
                break; // Ensure we flag once
            }
        }

        // 4. Action Resolution Auto-Suggestion
        $suggestion = 'none';
        if ($score >= 75) {
            $suggestion = 'reject';
        } elseif ($score <= 15) {
            $suggestion = 'approve';
        }

        return [
            'risk_score' => $score,
            'heuristic_flags' => $flags,
            'auto_suggestion' => $suggestion,
        ];
    }
}
