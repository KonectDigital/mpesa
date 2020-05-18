<?php

namespace Konectdigital\Mpesa\Tests\Unit;

use Konectdigital\Mpesa\Tests\TestCase;
use Konectdigital\Mpesa\Facades\Mpesa;

class MpesaExpressTest extends TestCase
{
    /** @test */
    public function test_mpesa_express()
    {
        $response = Mpesa::express(100, '254705112855', '24242524', 'Testing Payment');
        $data = json_decode($response, true);

        if (isset($data['CheckoutRequestID'])) {
            $this->assertArrayHasKey('MerchantRequestID', $data, "response don't have MerchantRequestID");
            $this->assertArrayHasKey('CheckoutRequestID', $data, "response don't have CheckoutRequestID");
            $this->assertArrayHasKey('ResponseDescription', $data, "response don't have ResponseDescription");
        }
    }
}
