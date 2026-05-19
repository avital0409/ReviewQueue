<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Mail\ItemBannedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserBanManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test manual ban and unban triggers via toggle endpoint.
     */
    public function test_manual_ban_and_unban_toggles(): void
    {
        $email = 'spammer@example.com';

        // 1. Trigger Ban
        $response = $this->postJson('/api/users/ban', [
            'email' => $email,
            'action' => 'ban',
            'reason' => 'Repeated violations'
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('banned_users', [
            'email' => $email,
            'ban_reason' => 'Repeated violations'
        ]);

        // 2. Trigger Unban
        $response = $this->postJson('/api/users/ban', [
            'email' => $email,
            'action' => 'unban'
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('banned_users', [
            'email' => $email
        ]);
    }

    /**
     * Test moderation review with ban escalation dispatches ItemBannedMail notice.
     */
    public function test_moderation_ban_escalation_dispatches_email(): void
    {
        Mail::fake();

        $item = Item::create([
            'author_email' => 'infractor@example.com',
            'content' => 'Send money immediately to recover credentials.',
            'status' => 'pending',
            'risk_score' => 90,
            'heuristic_flags' => ['urgent_language', 'phishing_patterns'],
            'auto_suggestion' => 'reject',
        ]);

        $payload = [
            'status' => 'rejected',
            'reviewer_note' => 'Strike 3 exceeded. Permanent ban applied.',
            'ban_user' => true,
            'ban_reason' => 'Repeated phishing scams.',
            'send_email' => true,
        ];

        $response = $this->patchJson("/api/items/{$item->id}/review", $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('banned_users', [
            'email' => 'infractor@example.com',
            'ban_reason' => 'Repeated phishing scams.'
        ]);

        // Verify banned user email dispatch
        Mail::assertSent(ItemBannedMail::class, function (ItemBannedMail $mail) {
            return $mail->hasTo('infractor@example.com') &&
                   $mail->banReason === 'Repeated phishing scams.' &&
                   $mail->submissionContent === 'Send money immediately to recover credentials.';
        });
    }

    /**
     * Test that any post submitted by a banned email is immediately intercepted and blocked.
     */
    public function test_banned_user_submissions_are_blocked_at_gateway(): void
    {
        $email = 'suspended@example.com';

        // Insert ban record directly
        DB::table('banned_users')->insert([
            'email' => $email,
            'ban_reason' => 'Phishing campaign',
            'banned_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $payload = [
            'author_email' => $email,
            'content' => 'Please visit my awesome link',
        ];

        // Submit new item via gateway
        $response = $this->postJson('/api/items', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'status' => 'blocked',
                'risk_score' => 100,
            ]);

        $this->assertDatabaseHas('items', [
            'author_email' => $email,
            'status' => 'blocked',
        ]);
    }
}
