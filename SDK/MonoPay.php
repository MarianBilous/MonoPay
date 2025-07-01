<?php

namespace App\SDK;

use Illuminate\Support\Facades\Http;

/**
 * Class MonoPay SDK service
 */
class MonoPay
{
    /**
     * @var string The API token for MonoPay authentication.
     */
    private string $token;

    /**
     * @var string The base URL for the MonoPay API.
     */
    private string $url = 'https://api.monobank.ua/api/';

    /**
     * Constructor for the MonoPay.
     *
     * @param string $token The API token for authenticating with MonoPay.
     */
    public function __construct(string $token)
    {
        $this->setToken($token);
    }

    /**
     * Sets the API token for MonoPay.
     *
     * @param string $token The API token.
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Retrieves the API token for MonoPay.
     *
     * @return string The API token.
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Creates a new invoice using MonoPay API.
     *
     * @param array $invoiceData The data required to create the invoice.
     * @return array The response from the MonoPay API.
     * @throws \Exception If the required invoice data is invalid or missing.
     */
    public function create(array $invoiceData): array
    {
        $this->validateData($invoiceData);

        return Http::withHeaders([
            'X-Token' => $this->getToken()
        ])->post($this->url . 'merchant/invoice/create', $invoiceData);
    }

    /**
     * Retrieves the status of an invoice from MonoPay.
     *
     * @param string $invoiceId The ID of the invoice to check the status for.
     * @return array The response from the MonoPay API.
     */
    public function getStatus(string $invoiceId): array
    {
        return Http::withHeaders([
            'X-Token' => $this->getToken()
        ])->get($this->url . 'merchant/invoice/status?invoiceId=' . $invoiceId);
    }

    public function finalization(array $invoiceData): array
    {
        return Http::withHeaders([
            'X-Token' => $this->getToken()
        ])->post($this->url . 'merchant/invoice/finalize', $invoiceData);
    }

    /**
     * Validates the invoice data before sending it to MonoPay.
     *
     * @param array $invoiceData The data to validate.
     * @throws Exception If the required fields are missing or invalid.
     */
    private function validateData(array $invoiceData): void
    {
        if (!isset($invoiceData['redirectUrl'])) {
            throw new \Exception('redirectUrl is required');
        }

//        if (!isset($invoiceData['webHookUrl'])) {
//            throw new \Exception('webHookUrl is required');
//        }

        if (!isset($invoiceData['paymentType'])) {
            throw new \Exception('paymentType is required');
        }
    }
}
