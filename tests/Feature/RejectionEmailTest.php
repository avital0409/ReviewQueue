<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Mail\ItemRejectedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RejectionEmailTest extends TestCase
{
    use RefreshDatabase;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->item = Item::create([
            'author_email' => 'victim@spam.com',
            'content' => 'Earn 1000% yield farming on our bitcoin wallet right now!',
            'status' => 'pending',
            'risk_score' => 95,
            'heuristic_flags' => ['financial_keywords', 'urgent_language'],
            'auto_suggestion' => 'reject',
        ]);
    }

    /**
     * Test generating a rejection email draft via API endpoint.
     */
    public function test_can_generate_rejection_email_draft(): void
    {
        $payload = [
            'reviewer_note' => 'Spam keywords and urgent language detected.',
        ];

        $response = $this->postJson("/api/items/{$this->item->id}/rejection-draft", $payload);

        $response->assertStatus(200)
            ->assertJsonStructure(['draft']);

        $this->assertNotEmpty($response->json('draft'));
        $this->assertStringContainsString('Spam keywords and urgent language detected.', $response->json('draft'));
    }

    /**
     * Test that review rejection dispatches the ItemRejectedMail notification.
     */
    public function test_rejection_sends_email_notification(): void
    {
        Mail::fake();

        $payload = [
            'status' => 'rejected',
            'reviewer_note' => 'Spam content violating community policy.',
            'send_email' => true,
            'email_body' => 'Customized rejection notice body.',
        ];

        $response = $this->patchJson("/api/items/{$this->item->id}/review", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->item->id,
                'status' => 'rejected',
            ]);

        // Verify email was sent to correct author with correct content
        Mail::assertSent(ItemRejectedMail::class, function (ItemRejectedMail $mail) {
            return $mail->hasTo('victim@spam.com') &&
                   $mail->rejectionReason === 'Customized rejection notice body.' &&
                   $mail->submissionContent === 'Earn 1000% yield farming on our bitcoin wallet right now!';
        });

        // Verify database state updates
        $this->item->refresh();
        $this->assertEquals('rejected', $this->item->status);
        $this->assertEquals('Spam content violating community policy.', $this->item->reviewer_note);
    }
}
