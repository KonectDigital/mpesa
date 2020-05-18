<?php

namespace Konectdigital\Mpesa\Tests\Unit;

use Konectdigital\Mpesa\Tests\TestCase;
use Konectdigital\Mpesa\Facades\Mpesa;

class MpesaSimeulateC2BTest extends TestCase
{
    /** @test */
    public function it_can_simulate()
    {
        $response = Mpesa::simulateC2B(100, "254706423251", "Test");
        $data = json_decode($response, true);
        $this->assertArrayHasKey('ConversationID', $data);
        $this->assertArrayHasKey('OriginatorConversationID', $data);
        $this->assertArrayHasKey('ResponseDescription', $data);
    }
}
