<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Services\HeuristicEngineService;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $engine = new HeuristicEngineService;

        $samples = [
            // 1. Blatant bad-actor spam (High risk calculation scores, multiple content flags, auto-suggested rejection triggers)
            [
                'author_email' => 'scammer101@yieldmax.xyz',
                'content' => 'URGENT: Get 300% yield on your crypto assets! Just deposit to our bitcoin wallet immediately to unlock your account.',
                'status' => 'pending',
            ],
            [
                'author_email' => 'spammer@getrichquick.ru',
                'content' => 'Get rich now! Invest in the newest yield-farming coin at http://scam-yield.xyz. Do it immediately or miss out!',
                'status' => 'pending',
            ],

            // 2. Critical client operational issues (Medium risk metrics, urgent phrasing tags, manual review indicators)
            [
                'author_email' => 'support@businessclient.com',
                'content' => 'URGENT: I received an unauthorized action warning and my account is locked. Please help immediately!',
                'status' => 'pending',
            ],
            [
                'author_email' => 'customer-alert@mybank.net',
                'content' => 'I cannot access my secure online wallet. It says my account is locked. Is this an unauthorized action? Please review.',
                'status' => 'pending',
            ],

            // 3. Legitimate, clean safe entries (Low risk calculation scores, clear of automated flags, auto-suggested approvals)
            [
                'author_email' => 'alice@gmail.com',
                'content' => 'Hi, I would like to submit a request to update my profile information. Thank you!',
                'status' => 'pending',
            ],
            [
                'author_email' => 'bob@yahoo.com',
                'content' => 'The meeting has been rescheduled for tomorrow morning at 10 AM. Let me know if you can make it.',
                'status' => 'pending',
            ],

            // Reviewed items
            [
                'author_email' => 'spammer-past@spam.ru',
                'content' => 'Double your bitcoin today at http://getrich.xyz',
                'status' => 'rejected',
                'reviewer_note' => 'Blatant crypto phishing scam.',
                'reviewed_at' => now()->subHours(2),
            ],
            [
                'author_email' => 'clean-past@gmail.com',
                'content' => 'Just wanted to say thank you for the fast support response yesterday!',
                'status' => 'approved',
                'reviewer_note' => 'Helpful user feedback.',
                'reviewed_at' => now()->subHours(1),
            ],
        ];

        foreach ($samples as $sample) {
            $analysis = $engine->analyze($sample['content']);

            Item::create([
                'author_email' => $sample['author_email'],
                'content' => $sample['content'],
                'status' => $sample['status'],
                'risk_score' => $analysis['risk_score'],
                'heuristic_flags' => $analysis['heuristic_flags'],
                'auto_suggestion' => $analysis['auto_suggestion'],
                'reviewer_note' => $sample['reviewer_note'] ?? null,
                'reviewed_at' => $sample['reviewed_at'] ?? null,
            ]);
        }
    }
}
