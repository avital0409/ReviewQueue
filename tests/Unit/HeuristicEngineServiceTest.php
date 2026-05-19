<?php

namespace Tests\Unit;

use App\Services\HeuristicEngineService;
use PHPUnit\Framework\TestCase;

class HeuristicEngineServiceTest extends TestCase
{
    private HeuristicEngineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HeuristicEngineService;
    }

    /**
     * Test that clean text results in a low score, no flags, and an 'approve' suggestion.
     */
    public function test_clean_content_analysis(): void
    {
        $result = $this->service->analyze('Hello world! This is a completely clean support message.');

        $this->assertEquals(0, $result['risk_score']);
        $this->assertEmpty($result['heuristic_flags']);
        $this->assertEquals('approve', $result['auto_suggestion']);
    }

    /**
     * Test that financial keywords trigger the correct flags and scores.
     */
    public function test_financial_keywords_analysis(): void
    {
        $result = $this->service->analyze('I want to invest in crypto today.');

        $this->assertEquals(35, $result['risk_score']);
        $this->assertContains('financial_keywords', $result['heuristic_flags']);
        $this->assertEquals('none', $result['auto_suggestion']);
    }

    /**
     * Test that links trigger the correct flags and scores.
     */
    public function test_external_links_analysis(): void
    {
        $result = $this->service->analyze('Check out my website at http://example.ru');

        $this->assertEquals(25, $result['risk_score']);
        $this->assertContains('external_links', $result['heuristic_flags']);
        $this->assertEquals('none', $result['auto_suggestion']);
    }

    /**
     * Test that urgent keywords trigger the correct flags and scores.
     */
    public function test_urgent_intent_analysis(): void
    {
        $result = $this->service->analyze('Please review this immediately!');

        $this->assertEquals(25, $result['risk_score']);
        $this->assertContains('urgent_language', $result['heuristic_flags']);
        $this->assertEquals('none', $result['auto_suggestion']);
    }

    /**
     * Test that combined triggers exceed 75 and suggest 'reject'.
     */
    public function test_combined_high_risk_analysis(): void
    {
        $result = $this->service->analyze('URGENT: Invest in our new bitcoin wallet at https://scam.ru immediately!');

        // Financial (35) + Link (25) + Urgent (25) = 85
        $this->assertEquals(85, $result['risk_score']);
        $this->assertContains('financial_keywords', $result['heuristic_flags']);
        $this->assertContains('external_links', $result['heuristic_flags']);
        $this->assertContains('urgent_language', $result['heuristic_flags']);
        $this->assertEquals('reject', $result['auto_suggestion']);
    }
}
