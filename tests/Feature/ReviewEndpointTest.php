<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewEndpointTest extends TestCase
{
    use RefreshDatabase;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup an initial pending item
        $this->item = Item::create([
            'author_email' => 'user@gmail.com',
            'content' => 'Please review my general submission.',
            'status' => 'pending',
            'risk_score' => 0,
            'heuristic_flags' => [],
            'auto_suggestion' => 'approve',
        ]);
    }

    /**
     * Test validation rules for the review patch route.
     */
    public function test_review_validation_errors(): void
    {
        // 1. Missing status
        $response = $this->patchJson("/api/items/{$this->item->id}/review", []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // 2. Invalid status value
        $response = $this->patchJson("/api/items/{$this->item->id}/review", [
            'status' => 'pending', // Pending is not a terminal state allowed in review request
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test successful review to 'approved' status.
     */
    public function test_successful_review_approval(): void
    {
        $payload = [
            'status' => 'approved',
            'reviewer_note' => 'Valid submission, clean content.',
        ];

        $response = $this->patchJson("/api/items/{$this->item->id}/review", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->item->id,
                'status' => 'approved',
                'reviewer_note' => 'Valid submission, clean content.',
            ]);

        // Verify database state updates
        $this->item->refresh();
        $this->assertEquals('approved', $this->item->status);
        $this->assertEquals('Valid submission, clean content.', $this->item->reviewer_note);
        $this->assertNotNull($this->item->reviewed_at);
    }

    /**
     * Test successful review to 'rejected' status.
     */
    public function test_successful_review_rejection(): void
    {
        $payload = [
            'status' => 'rejected',
            'reviewer_note' => 'Inappropriate content.',
        ];

        $response = $this->patchJson("/api/items/{$this->item->id}/review", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->item->id,
                'status' => 'rejected',
                'reviewer_note' => 'Inappropriate content.',
            ]);

        // Verify database state updates
        $this->item->refresh();
        $this->assertEquals('rejected', $this->item->status);
        $this->assertEquals('Inappropriate content.', $this->item->reviewer_note);
        $this->assertNotNull($this->item->reviewed_at);
    }
}
