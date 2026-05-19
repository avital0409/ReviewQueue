<?php

namespace Tests\Feature;

use App\Mail\ItemBannedMail;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
            'reason' => 'Repeated violations',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('banned_users', [
            'email' => $email,
            'ban_reason' => 'Repeated violations',
        ]);

        // 2. Trigger Unban
        $response = $this->postJson('/api/users/ban', [
            'email' => $email,
            'action' => 'unban',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('banned_users', [
            'email' => $email,
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
            'ban_reason' => 'Repeated phishing scams.',
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
            'updated_at' => now(),
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

    /**
     * Test that having 2 existing rejections and submitting a 3rd reject-heuristic post escalates auto_suggestion to 'ban'.
     */
    public function test_strike_three_escalation_triggers_auto_suggestion_ban(): void
    {
        $email = 'frequent_spammer@example.com';

        // Seed 2 rejected items for the same user
        for ($i = 0; $i < 2; $i++) {
            Item::create([
                'author_email' => $email,
                'content' => 'Old violating content '.$i,
                'status' => 'rejected',
                'risk_score' => 85,
                'heuristic_flags' => ['financial_keywords'],
                'auto_suggestion' => 'reject',
            ]);
        }

        // Submit a 3rd high-risk post via gateway
        $payload = [
            'author_email' => $email,
            'content' => 'URGENT: invest in our bitcoin crypto yield pool immediately! Visit pool.com',
        ];

        $response = $this->postJson('/api/items', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'author_email' => $email,
                'status' => 'pending',
                'auto_suggestion' => 'ban',
            ]);

        $this->assertDatabaseHas('items', [
            'author_email' => $email,
            'status' => 'pending',
            'auto_suggestion' => 'ban',
        ]);
    }

    /**
     * Test that even borderline posts (which normally trigger 'none' / manual review) escalate to 'ban' recommendation on the 3rd strike.
     */
    public function test_borderline_strike_three_escalation_triggers_auto_suggestion_ban(): void
    {
        $email = 'borderline_spammer@example.com';

        // Seed 2 rejected items for the same user
        for ($i = 0; $i < 2; $i++) {
            Item::create([
                'author_email' => $email,
                'content' => 'Old violating content '.$i,
                'status' => 'rejected',
                'risk_score' => 85,
                'heuristic_flags' => ['financial_keywords'],
                'auto_suggestion' => 'reject',
            ]);
        }

        // Submit a 3rd post with borderline risk (score 25, triggers 'none' / manual review normally)
        $payload = [
            'author_email' => $email,
            'content' => 'Please click here to see details at test.net.', // triggers external link rule once -> +25 risk -> auto_suggestion = 'none'
        ];

        $response = $this->postJson('/api/items', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'author_email' => $email,
                'status' => 'pending',
                'auto_suggestion' => 'ban',
            ]);

        $this->assertDatabaseHas('items', [
            'author_email' => $email,
            'status' => 'pending',
            'auto_suggestion' => 'ban',
        ]);
    }

    /**
     * Test that banning a user during item review automatically transitions other pending items to blocked.
     */
    public function test_banning_user_in_review_automatically_blocks_other_pending_items(): void
    {
        $email = 'multi_pending@example.com';

        // 1. Create primary review item
        $primaryItem = Item::create([
            'author_email' => $email,
            'content' => 'Primary offensive post.',
            'status' => 'pending',
            'risk_score' => 85,
            'heuristic_flags' => ['financial_keywords'],
            'auto_suggestion' => 'reject',
        ]);

        // 2. Create two other pending items for same author
        $otherPending1 = Item::create([
            'author_email' => $email,
            'content' => 'Second post.',
            'status' => 'pending',
            'risk_score' => 25,
            'heuristic_flags' => [],
            'auto_suggestion' => 'none',
        ]);

        $otherPending2 = Item::create([
            'author_email' => $email,
            'content' => 'Third post.',
            'status' => 'pending',
            'risk_score' => 0,
            'heuristic_flags' => [],
            'auto_suggestion' => 'approve',
        ]);

        $payload = [
            'status' => 'rejected',
            'reviewer_note' => 'Spam content.',
            'send_email' => false,
            'ban_user' => true,
            'ban_reason' => 'Banning due to multi-spam content.',
        ];

        $response = $this->patchJson("/api/items/{$primaryItem->id}/review", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $primaryItem->id,
                'status' => 'rejected',
                'blocked_count' => 2,
            ]);

        // Verify other pending items were automatically blocked
        $this->assertEquals('blocked', $otherPending1->fresh()->status);
        $this->assertEquals('blocked', $otherPending2->fresh()->status);
        $this->assertEquals('Automatically blocked: submitter banned.', $otherPending1->fresh()->reviewer_note);
    }

    /**
     * Test that manually banning a user via the Directory automatically transitions all of their pending items to blocked.
     */
    public function test_manually_banning_user_in_directory_automatically_blocks_all_pending_items(): void
    {
        $email = 'manual_multi@example.com';

        // 1. Create three pending items
        $item1 = Item::create([
            'author_email' => $email,
            'content' => 'Post A',
            'status' => 'pending',
            'risk_score' => 0,
            'heuristic_flags' => [],
            'auto_suggestion' => 'approve',
        ]);

        $item2 = Item::create([
            'author_email' => $email,
            'content' => 'Post B',
            'status' => 'pending',
            'risk_score' => 0,
            'heuristic_flags' => [],
            'auto_suggestion' => 'approve',
        ]);

        $item3 = Item::create([
            'author_email' => $email,
            'content' => 'Post C',
            'status' => 'pending',
            'risk_score' => 0,
            'heuristic_flags' => [],
            'auto_suggestion' => 'approve',
        ]);

        $payload = [
            'email' => $email,
            'action' => 'ban',
            'reason' => 'Manual ban from user reputation screen.',
        ];

        $response = $this->postJson('/api/users/ban', $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'blocked_count' => 3,
            ]);

        // Verify all three pending items were transitioned to blocked
        $this->assertEquals('blocked', $item1->fresh()->status);
        $this->assertEquals('blocked', $item2->fresh()->status);
        $this->assertEquals('blocked', $item3->fresh()->status);
    }
}
