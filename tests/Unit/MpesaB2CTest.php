<?php

namespace Konectdigital\Mpesa\Tests\Unit;

use Konectdigital\Mpesa\Tests\TestCase;
use Konectdigital\Mpesa\Facades\Mpesa;

class MpesaB2CTest extends TestCase
{
    /** @test */
    public function test_b2c()
    {
        $response = Mpesa::b2c(100, '254706423251', 'LoanDisbursment', 'test');

        $data = json_decode($response, true);

        $this->assertArrayHasKey('ConversationID', $data, "response don't have ConversationID");
        $this->assertArrayHasKey('OriginatorConversationID', $data, "response dont have OriginatorConversationID");
        $this->assertArrayHasKey('ResponseDescription', $data, "response dont have ResponseDescription");
    }
}
