<?php

namespace Konectdigital\Mpesa\Tests\Unit;

use Konectdigital\Mpesa\Tests\TestCase;
use Konectdigital\Mpesa\Facades\Mpesa;

class MpesaAuthTokenTest extends TestCase
{
    /** @test */
    function it_can_get_token()
    {
        $response = Mpesa::getAccessToken();
        $this->assertTrue($response);
    }
}
