<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserReputationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_fetch_reputation_directory_list()
    {
        // Seed some review items
        Item::create([
            'content' => 'First content',
            'author_email' => 'user1@example.com',
            'status' => 'approved',
            'risk_score' => 10,
        ]);

        Item::create([
            'content' => 'Violating content 1',
            'author_email' => 'user1@example.com',
            'status' => 'rejected',
            'risk_score' => 80,
        ]);

        Item::create([
            'content' => 'Violating content 2',
            'author_email' => 'user1@example.com',
            'status' => 'rejected',
            'risk_score' => 85,
        ]);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'users' => [
                    '*' => [
                        'author_email',
                        'total_count',
                        'approved_count',
                        'rejected_count',
                        'blocked_count',
                        'is_banned',
                    ],
                ],
            ]);

        $userData = collect($response->json('users'))->firstWhere('author_email', 'user1@example.com');
        $this->assertEquals(3, $userData['total_count']);
        $this->assertEquals(1, $userData['approved_count']);
        $this->assertEquals(2, $userData['rejected_count']);
        $this->assertEquals(0, $userData['blocked_count']);
    }

    /** @test */
    public function it_can_fetch_individual_user_history_timeline()
    {
        Item::create([
            'content' => 'Audit check content',
            'author_email' => 'user2@example.com',
            'status' => 'pending',
            'risk_score' => 12,
        ]);

        $response = $this->getJson('/api/users/user2@example.com/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'email',
                'is_banned',
                'ban_reason',
                'banned_at',
                'history' => [
                    '*' => [
                        'id',
                        'content',
                        'status',
                        'risk_score',
                        'reviewer_note',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('history'));
        $this->assertEquals('Audit check content', $response->json('history')[0]['content']);
    }

    /** @test */
    public function it_can_toggle_user_ban_status()
    {
        $email = 'spam_merchant@example.com';

        // Ban the user
        $response = $this->postJson('/api/users/ban', [
            'email' => $email,
            'action' => 'ban',
            'reason' => 'Repeated spam bot actions',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('banned_users', [
            'email' => $email,
            'ban_reason' => 'Repeated spam bot actions',
        ]);

        // Unban the user
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
}
