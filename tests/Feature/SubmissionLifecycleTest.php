<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test validation rules for store endpoint.
     */
    public function test_store_validation_errors(): void
    {
        // 1. Missing fields
        $response = $this->postJson('/api/items', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['author_email', 'content']);

        // 2. Invalid email format
        $response = $this->postJson('/api/items', [
            'author_email' => 'not-an-email',
            'content' => 'Valid content structure.',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['author_email']);
    }

    /**
     * Test successful item creation and automatic heuristic scoring/flagging.
     */
    public function test_successful_item_submission(): void
    {
        $payload = [
            'author_email' => 'client@gmail.com',
            'content' => 'URGENT: Can we check this out immediately? http://website.com',
        ];

        $response = $this->postJson('/api/items', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'author_email',
                'content',
                'status',
                'risk_score',
                'heuristic_flags',
                'auto_suggestion',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'author_email' => 'client@gmail.com',
                'status' => 'pending',
                'auto_suggestion' => 'none',
            ]);

        // Assert database persistence
        $this->assertDatabaseHas('items', [
            'author_email' => 'client@gmail.com',
            'status' => 'pending',
        ]);

        $item = Item::first();
        $this->assertNotNull($item);
        // Score should be 25 (link) + 25 (urgent) = 50
        $this->assertEquals(50, $item->risk_score);
        $this->assertContains('external_links', $item->heuristic_flags);
        $this->assertContains('urgent_language', $item->heuristic_flags);
    }
}
