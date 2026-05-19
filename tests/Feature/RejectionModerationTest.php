<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Mail\ItemRejectedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RejectionModerationTest extends TestCase
{
    use RefreshDatabase;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->item = Item::create([
            'author_email' => 'submitter@example.com',
            'content' => 'Please checkout our new yield farming crypto pool at pool.com!',
            'status' => 'pending',
            'risk_score' => 85,
            'heuristic_flags' => ['financial_keywords', 'external_links'],
            'auto_suggestion' => 'reject',
        ]);
    }

    /**
     * Test rejecting a submission without sending an email notification.
     */
    public function test_item_rejection_without_email_updates_status_only(): void
    {
        Mail::fake();

        $payload = [
            'status' => 'rejected',
            'reviewer_note' => 'Spam link content.',
            'send_email' => false,
        ];

        $response = $this->patchJson("/api/items/{$this->item->id}/review", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->item->id,
                'status' => 'rejected',
            ]);

        $this->item->refresh();
        $this->assertEquals('rejected', $this->item->status);
        $this->assertEquals('Spam link content.', $this->item->reviewer_note);

        Mail::assertNothingSent();
    }

    /**
     * Test rejecting a submission and dispatching the rejection email notification.
     */
    public function test_item_rejection_with_email_sends_notification(): void
    {
        Mail::fake();

        $payload = [
            'status' => 'rejected',
            'reviewer_note' => 'Spam financial keywords found.',
            'send_email' => true,
            'email_body' => 'Dear Submitter, your content regarding crypto was rejected because it violates our community policies. ReviewQueue Moderation Hub',
        ];

        $response = $this->patchJson("/api/items/{$this->item->id}/review", $payload);

        $response->assertStatus(200);

        $this->item->refresh();
        $this->assertEquals('rejected', $this->item->status);

        // Verify the rejection email was sent to the correct recipient with exact custom email body
        Mail::assertSent(ItemRejectedMail::class, function (ItemRejectedMail $mail) {
            return $mail->hasTo('submitter@example.com') &&
                   $mail->rejectionReason === 'Dear Submitter, your content regarding crypto was rejected because it violates our community policies. ReviewQueue Moderation Hub' &&
                   $mail->submissionContent === 'Please checkout our new yield farming crypto pool at pool.com!';
        });
    }

    /**
     * Test prefetching the AI rejection email draft.
     */
    public function test_rejection_email_prefetched_draft_endpoint(): void
    {
        $payload = [
            'reviewer_note' => 'Spam content and external links.',
        ];

        $response = $this->postJson("/api/items/{$this->item->id}/rejection-draft", $payload);

        $response->assertStatus(200)
            ->assertJsonStructure(['draft']);

        $this->assertNotEmpty($response->json('draft'));
    }
}
