<?php

namespace Konectdigital\Mpesa;

use Illuminate\Support\Facades\File;

class Mpesa
{

    private $base_url;
    public  $consumer_key;
    public  $consumer_secret;
    public  $paybill;
    public  $lipa_na_mpesa;
    public  $lipa_na_mpesa_key;
    public  $initiator_username;
    public  $initiator_password;
    private $callback_baseurl;
    private $test_msisdn;
    private $credentials;
    private $access_token;

    /*Callbacks*/
    public $bctimeout;
    public $bcresult;
    public $bbtimeout;
    public $bbresult;
    public $baltimeout;
    public $balresult;
    public $statustimeout;
    public $statusresult;
    public $reversetimeout;
    public $reverseresult;
    public $cbvalidate;
    public $cbconfirm;
    public $lnmocallback;

    /**
     * Construct method.
     *
     * Initializes the class with an array of API values.
     *
     * @param array $config
     * @return void
     * @throws exception if the values array is not valid
     */
    public function __construct()
    {
        // Set the base URL for API calls based on the application environment
        if (config('mpesa.mpesa_env') == 'sandbox') {
            $this->base_url = 'https://sandbox.safaricom.co.ke/mpesa/';
        } else {
            $this->base_url = 'https://api.safaricom.co.ke/mpesa/';
        }

        $this->consumer_key = config('mpesa.consumer_key');
        $this->consumer_secret = config('mpesa.consumer_secret');
        $this->paybill = config('mpesa.paybill');
        $this->lipa_na_mpesa = config('mpesa.lipa_na_mpesa');
        $this->lipa_na_mpesa_key = config('mpesa.lipa_na_mpesa_passkey');
        $this->initiator_username = config('mpesa.initiator_username');
        $this->initiator_password = config('mpesa.initiator_password');

        // Set the access token
        $this->access_token = $this->getAccessToken();

        // Mpesa express (STK) callbacks
        $this->callback_baseurl = 'https://91c77dd6.ngrok.io/api/callback';
        $this->lnmocallback = config('mpesa.lnmocallback');
        $this->test_msisdn = config('mpesa.test_msisdn');

        // C2B callback urls
        $this->cbvalidate = config('mpesa.c2b_validate_callback');
        $this->cbconfirm = config('mpesa.c2b_confirm_callback');

        // B2C URLs
        $this->bctimeout = config('mpesa.b2c_timeout');
        $this->bcresult = config('mpesa.b2c_result');

        // Till balance URLS
        $this->balresult = config('mpesa.balance_callback');
        $this->baltimeout = config('mpesa.balance_timeout');

        // Reversal URLs
        $this->reverseresult = config('mpesa.reversal_result_callback');
        $this->reversetimeout = config('mpesa.reversal_timeout_callback');
    }

    /**
     * Submit Request.
     *
     * Handles submission of all API endpoints queries
     *
     * @param string $url The API endpoint URL
     * @param json $data The data to POST to the endpoint $url
     * @return object|bool Curl response or FALSE on failure
     * @throws exception if the Access Token is not valid
     */
    public function setCredentials()
    {
        // Set public key certificate based on environment
        if (config('mpesa.mpesa_env') == 'sandbox') {
            $pubkey = File::get(__DIR__ . '/cert/sandbox.cer');
        } else {
            $pubkey = File::get(__DIR__ . '/cert/production.cer');
        }

        openssl_public_encrypt($this->initiator_password, $output, $pubkey, OPENSSL_PKCS1_PADDING);
        $this->credentials = base64_encode($output);

        return $this->credentials;
    }

    public function getAccessToken()
    {
        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);
        $ch = curl_init();
        $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        if (config('mpesa.mpesa_env') == 'sandbox') {
            $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        }
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials, 'Content-Type: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response);
        $access_token = $response->access_token;
        // The above $access_token expires after an hour, find a way to cache it to minimize requests to the server

        if (!$access_token) {
            // Invalid token
            return false;
        }

        $this->access_token = $access_token;

        return $access_token;
    }

    private function submit_request($url, $data)
    {
        if (isset($this->access_token)) {
            $access_token = $this->access_token;
        } else {
            $access_token = $this->getAccessToken();
        }

        if ($access_token != '' || $access_token !== false) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $access_token]);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

            $response = curl_exec($curl);
            curl_close($curl);

            return $response;
        } else {
            return false;
        }
    }

    /**
     * Client to Business.
     *
     * This method is used to register URLs for callbacks when money is sent from the MPesa toolkit menu
     *
     * @param string $confirmURL The local URL that MPesa calls to confirm a payment
     * @param string $ValidationURL The local URL that MPesa calls to validate a payment
     * @return object Curl Response from submit_request, FALSE on failure
     */
    public function c2bRegisterUrls()
    {
        $request_data = [
            'ShortCode' => $this->paybill,
            'ResponseType' => 'Completed',
            'ConfirmationURL' => $this->cbconfirm,
            'ValidationURL' => $this->cbvalidate,
        ];
        $data = json_encode($request_data);
        //header('Content-Type: application/json');

        $url = $this->base_url . 'c2b/v1/registerurl';
        $response = $this->submit_request($url, $data);

        return $response;
    }

    /**
     * C2B Simulation.
     *
     * This method is used to simulate a C2B Transaction to test your ConfirmURL and ValidationURL in the Client to Business method
     *
     * @param int $amount The amount to send to Paybill number
     * @param int $msisdn A dummy Safaricom phone number to simulate transaction in the format 2547xxxxxxxx
     * @param string $ref A reference name for the transaction
     * @return object Curl Response from submit_request, FALSE on failure
     */
    public function simulateC2B($amount, $msisdn, $ref)
    {
        $data = [
            'ShortCode' => $this->paybill,
            'CommandID' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'Msisdn' => $msisdn,
            'BillRefNumber' => $ref,
        ];
        $data = json_encode($data);
        $url = $this->base_url . 'c2b/v1/simulate';
        $response = $this->submit_request($url, $data);

        return $response;
    }

    /*********************************************************************
     *
     * 	LNMO APIs
     *
     * *******************************************************************/

    public function express($amount, $phone, $ref = 'Payment', $desc = 'Payment')
    {
        $phone     = (substr($phone, 0, 1) == "+") ? str_replace("+", "", $phone) : $phone;
        $phone     = (substr($phone, 0, 1) == "0") ? preg_replace("/^0/", "254", $phone) : $phone;
        $phone     = (substr($phone, 0, 1) == "7") ? "254{$phone}" : $phone;

        $timestamp = date('YmdHis');
        $passwd = base64_encode($this->lipa_na_mpesa . $this->lipa_na_mpesa_key . $timestamp);
        $data = [
            'BusinessShortCode' => $this->lipa_na_mpesa,
            'Password' => $passwd,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $this->lipa_na_mpesa,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->lnmocallback,
            'AccountReference' => $ref,
            'TransactionDesc' => $desc,
        ];

        $data = json_encode($data);
        $url = $this->base_url . 'stkpush/v1/processrequest';
        $response = $this->submit_request($url, $data);
        $result = json_decode($response);

        return $result;
    }

    private function lnmo_query($checkoutRequestID = null)
    {
        $timestamp = date('YmdHis');
        $passwd = base64_encode($this->lipa_na_mpesa . $this->lipa_na_mpesa_key . $timestamp);

        if ($checkoutRequestID == null || $checkoutRequestID == '') {
            return false;
        }

        $data = [
            'BusinessShortCode' => $this->lipa_na_mpesa,
            'Password' => $passwd,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestID,
        ];
        $data = json_encode($data);
        $url = $this->base_url . 'stkpushquery/v1/query';
        $response = $this->submit_request($url, $data);

        return $response;
    }

    /**
     * Business to Client.
     *
     * This method is used to send money to the clients Mpesa account.
     *
     * @param int $amount The amount to send to the client
     * @param int $phone The phone number of the client in the format 2547xxxxxxxx
     * @return object Curl Response from submit_request, FALSE on failure
     */
    public function b2c($amount, $phone, $command_id, $remarks)
    {
        $this->setCredentials();
        $request_data = [
            'InitiatorName' => $this->initiator_username,
            'SecurityCredential' => $this->credentials,
            'CommandID' => $command_id,
            'Amount' => $amount,
            'PartyA' => $this->paybill,
            'PartyB' => $phone,
            'Remarks' => $remarks,
            'QueueTimeOutURL' => $this->bctimeout,
            'ResultURL' => $this->bcresult,
            'Occasion' => '', //Optional
        ];
        $data = json_encode($request_data);
        $url = $this->base_url . 'b2c/v1/paymentrequest';
        $response = $this->submit_request($url, $data);

        return $response;
    }

    /**
     * Business to Business.
     *
     * This method is used to send money to other business Mpesa paybills.
     *
     * @param int $amount The amount to send to the business
     * @param int $shortcode The shortcode of the business to send to
     * @return object Curl Response from submit_request, FALSE on failure
     */
    public function b2b($amount, $shortcode)
    {
        $request_data = [
            'Initiator' => $this->initiator_username,
            'SecurityCredential' => $this->cred,
            'CommandID' => 'BusinessToBusinessTransfer',
            'SenderIdentifierType' => 'Shortcode',
            'RecieverIdentifierType' => 'Shortcode',
            'Amount' => 100,
            'PartyA' => $this->paybill,
            'PartyB' => 600000,
            'AccountReference' => 'Bennito',
            'Remarks' => 'This is a test comment or remark',
            'QueueTimeOutURL' => $this->bbtimeout,
            'ResultURL' => $this->bbresult,
        ];
        $data = json_encode($request_data);
        $url = $this->base_url . 'b2b/v1/paymentrequest';
        $response = $this->submit_request($url, $data);

        return $response;
    }

    /**
     * Check Balance.
     *
     * Check Paybill balance
     *
     * @return object Curl Response from submit_request, FALSE on failure
     */
    public function check_balance()
    {
        $data = [
            'CommandID' => 'AccountBalance',
            'PartyA' => $this->paybill,
            'IdentifierType' => '4',
            'Remarks' => 'Remarks or short description',
            'Initiator' => $this->initiator_username,
            'SecurityCredential' => $this->credentials,
            'QueueTimeOutURL' => $this->baltimeout,
            'ResultURL' => $this->balresult,
        ];
        $data = json_encode($data);
        $url = $this->base_url . 'accountbalance/v1/query';
        $response = $this->submit_request($url, $data);

        return $response;
    }

    /**
     * Transaction status request.
     *
     * This method is used to check a transaction status
     *
     * @param string $transaction ID eg LH7819VXPE
     * @return object Curl Response from submit_request, FALSE on failure
     */
    public function status_request($transaction = 'LH7819VXPE')
    {
        $data = [
            'CommandID' => 'TransactionStatusQuery',
            'PartyA' => $this->paybill,
            'IdentifierType' => 4,
            'Remarks' => 'Testing API',
            'Initiator' => $this->initiator_username,
            'SecurityCredential' => $this->credentials,
            'QueueTimeOutURL' => $this->statustimeout,
            'ResultURL' => $this->statusresult,
            'TransactionID' => $transaction,
            'Occassion' => 'Test',
        ];
        $data = json_encode($data);
        $url = $this->base_url . 'transactionstatus/v1/query';
        $response = $this->submit_request($url, $data);

        return $response;
    }

    /**
     * Transaction Reversal.
     *
     * This method is used to reverse a transaction
     *
     * @param int $receiver Phone number in the format 2547xxxxxxxx
     * @param string $trx_id Transaction ID of the Transaction you want to reverse eg LH7819VXPE
     * @param int $amount The amount from the transaction to reverse
     * @return object Curl Response from submit_request, FALSE on failure
     */
    public function reverse_transaction($receiver, $trx_id, $amount)
    {
        $data = [
            'CommandID' => 'TransactionReversal',
            'ReceiverParty' => $this->test_msisdn,
            'RecieverIdentifierType' => 1, //1=MSISDN, 2=Till_Number, 4=Shortcode
            'Remarks' => 'Testing',
            'Amount' => $amount,
            'Initiator' => $this->initiator_username,
            'SecurityCredential' => $this->credentials,
            'QueueTimeOutURL' => $this->reversetimeout,
            'ResultURL' => $this->reverseresult,
            'TransactionID' => $trx_id,
        ];
        $data = json_encode($data);
        $url = $this->base_url . 'reversal/v1/request';
        $response = $this->submit_request($url, $data);

        return $response;
    }
}
