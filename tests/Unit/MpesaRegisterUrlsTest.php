<?php

namespace Konectdigital\Mpesa\Tests\Unit;

use Konectdigital\Mpesa\Tests\TestCase;
use Konectdigital\Mpesa\Facades\Mpesa;

class MpesaRegisterUrlsTest extends TestCase
{
    /** @test */
    public function it_can_register_urls()
    {
        $response = Mpesa::registerUrls();

        $data = json_decode($response, true);

        $this->assertArrayHasKey('ConversationID', $data);
        $this->assertArrayHasKey('OriginatorConversationID', $data);
        $this->assertArrayHasKey('ResponseDescription', $data);
    }
}
