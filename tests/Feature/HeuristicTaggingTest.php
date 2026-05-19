<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeuristicTaggingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test validation rules for store endpoint.
     */
    public function test_post_creation_requires_email_and_content(): void
    {
        $response = $this->postJson('/api/items', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['author_email', 'content']);
    }

    /**
     * Test that external links in submission trigger correct risk score and flag.
     */
    public function test_submission_with_external_links_adds_correct_score_and_flag(): void
    {
        $payload = [
            'author_email' => 'author@example.com',
            'content' => 'Please visit my website cool.com for information.',
        ];

        $response = $this->postJson('/api/items', $payload);

        $response->assertStatus(201);
        
        $item = Item::first();
        $this->assertNotNull($item);
        $this->assertEquals(25, $item->risk_score);
        $this->assertContains('external_links', $item->heuristic_flags);
    }

    /**
     * Test that urgent language in submission triggers correct risk score and flag.
     */
    public function test_submission_with_urgent_language_adds_correct_score_and_flag(): void
    {
        $payload = [
            'author_email' => 'author@example.com',
            'content' => 'Please act immediately to verify this ticket.',
        ];

        $response = $this->postJson('/api/items', $payload);

        $response->assertStatus(201);
        
        $item = Item::first();
        $this->assertNotNull($item);
        $this->assertEquals(25, $item->risk_score);
        $this->assertContains('urgent_language', $item->heuristic_flags);
    }

    /**
     * Test that financial keywords trigger correct risk score and flag.
     */
    public function test_submission_with_financial_keywords_adds_correct_score_and_flag(): void
    {
        $payload = [
            'author_email' => 'author@example.com',
            'content' => 'Want to invest and grow your money?',
        ];

        $response = $this->postJson('/api/items', $payload);

        $response->assertStatus(201);
        
        $item = Item::first();
        $this->assertNotNull($item);
        $this->assertEquals(35, $item->risk_score);
        $this->assertContains('financial_keywords', $item->heuristic_flags);
    }

    /**
     * Test that cumulative risk score determines auto-suggestions.
     */
    public function test_cumulative_risk_determines_auto_suggestions(): void
    {
        // 1. High Risk: Financial (35) + Link (25) + Urgent (25) = 85 (>= 75 triggers reject)
        $payloadHigh = [
            'author_email' => 'scam@example.com',
            'content' => 'URGENT: Invest in our new bitcoin coin right now! http://scam.ru',
        ];

        $responseHigh = $this->postJson('/api/items', $payloadHigh);
        $responseHigh->assertStatus(201);

        $itemHigh = Item::where('author_email', 'scam@example.com')->first();
        $this->assertNotNull($itemHigh);
        $this->assertEquals(85, $itemHigh->risk_score);
        $this->assertEquals('reject', $itemHigh->auto_suggestion);

        // 2. Safe/Approve suggest: Risk score <= 15 (e.g. 0 score) -> auto_suggestion 'approve'
        $payloadSafe = [
            'author_email' => 'safe@example.com',
            'content' => 'Hello team, hope you are having a wonderful Tuesday.',
        ];

        $responseSafe = $this->postJson('/api/items', $payloadSafe);
        $responseSafe->assertStatus(201);

        $itemSafe = Item::where('author_email', 'safe@example.com')->first();
        $this->assertNotNull($itemSafe);
        $this->assertEquals(0, $itemSafe->risk_score);
        $this->assertEquals('approve', $itemSafe->auto_suggestion);
    }
}
