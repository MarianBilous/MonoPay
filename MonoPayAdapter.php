<?php

namespace App\Services\Adapters;

use App\Interfaces\PayInterface;
use App\SDK\MonoPay;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Class representing a payment adapter for MonoPay.
 * Implements the PayInterface to provide integration with MonoPay.
 */
class MonoPayAdapter implements PayInterface
{
    /**
     * @var MonoPay The MonoPay service instance.
     */
    private MonoPay $monoPay;

    /**
     * MonoPayAdapter constructor.
     *
     * @param MonoPay $monoPay The MonoPay service instance to use for payment operations.
     */
    public function __construct(MonoPay $monoPay)
    {
        $this->monoPay = $monoPay;
    }

    /**
     * Creates a new payment with the given data.
     *
     * This method constructs the payment data array in the format required by the MonoPay API
     * and attempts to create the payment. If the payment is successfully created, it returns
     * the URL and invoice ID; otherwise, it logs the error and returns false.
     *
     * @param array $payData The data needed to create the payment.
     * @return array|false The result of the payment creation (URL and invoice ID) or false on failure.
     */
    public function create(array $payData)
    {
        try {
            $amount = $payData['amount'];
            $basketOrder = [];

            foreach ($payData['basketOrder'] as $cart) {
                $basketOrder[] = [
                    'name' => $cart->product_name,
                    "qty" => floatval($cart->amount),
                    "sum" => intval($cart->product_price  * 100),
                    "icon" => Storage::get($cart->product_image, 'products', $cart->product_id),
                    "unit" => "шт.",
                ];
            }

            $data = [
                'amount' => intval($amount),
                'redirectUrl' => $payData['redirectUrl'] ?? '',
//                'webHookUrl' => $payData['webHookUrl'] ?? '',
                "validity" => $payData['validity'],
                "paymentType" => "hold",
                "merchantPaymInfo" => [
                    "destination" => "Product purchase, order " . $payData['orderId'],
                    "comment" => "Product purchase, order " . $payData['orderId'],
                    'basketOrder' => $basketOrder
                ]
            ];

            $response = $this->monoPay->create($data);

            if ($response['status_code'] === 200) {
                return ['url' => $response['body']->pageUrl, 'invoiceId' => $response['body']->invoiceId];
            }

            $responseBody = $response['body'];

            Log::channel('mono')->error("payment|" . __METHOD__ . " " . (
                isset($response['status_code']) ? $response['status_code'] : ''
            ) . "|" . (
                isset($responseBody->errCode) ? $responseBody->errCode : ''
            ) . "|" .  (
                isset($responseBody->errText) ? $responseBody->errText : ''
            ));
            return false;
        } catch (\Exception $exception) {
            Log::channel('mono')->error("payment|" . __METHOD__ . " " . $exception->getMessage());
            return false;
        }
    }

    /**
     * Retrieves the status of a payment by its invoice ID.
     *
     * This method sends a request to the MonoPay API to get the current status of the payment.
     * If the request is successful, it returns the payment status data; otherwise, it logs the error and returns false.
     *
     * @param string $invoiceId The ID of the invoice to retrieve the status for.
     * @return array|false The status of the payment as an array or false on failure.
     */
    public function getStatus(string $invoiceId)
    {
        try {
            $response = $this->monoPay->getStatus($invoiceId);

            if ($response['status_code'] === 200) {
                return $response['body'];
            }

            $responseBody = $response['body'];

            Log::channel('mono')->error("payment|" . __METHOD__ . " " . (
                isset($response['status_code']) ? $response['status_code'] : ''
            ) . "|" . (
                isset($responseBody->errCode) ? $responseBody->errCode : ''
            ) . "|" .  (
                isset($responseBody->errText) ? $responseBody->errText : ''
            ));
            return false;
        } catch (\Exception $exception) {
            Log::channel('mono')->error("payment|" . __METHOD__ . $exception->getMessage());
            return false;
        }
    }

    public function finalizeInvoice($order)
    {
        try {
            $finalPrice = 0;
            $items = [];

            foreach ($order->cart as $cart) {
                if ($cart->final_cost) {
                    $finalPrice = $finalPrice + $cart->final_cost;
                } else {
                    $finalPrice = $finalPrice + ($cart->product_price * $cart->amount);
                }

                $items[] = [
                    'name' => $cart->product_name,
                    "qty" => floatval($cart->amount),
                    "sum" => intval($cart->product_price * 100),
                ];
            }

            if ($order->to_door) {
                $finalPrice += 75;
            }

            if ($order->delivery_price) {
                $finalPrice += $order->delivery_price;
            }

            $payData = [
                'invoiceId' => $order->transaction->invoice_id,
                'amount' => intval($finalPrice * 100),
                'items' => $items,
            ];

            $response = $this->monoPay->finalization($payData);

            if ($response['status_code'] === 200) {
                return $response['body'];
            }

            $responseBody = $response['body'];

            Log::channel('mono')->error("payment|" . __METHOD__ . " " . (
                isset($response['status_code']) ? $response['status_code'] : ''
            ) . "|" . (
                isset($responseBody->errCode) ? $responseBody->errCode : ''
            ) . "|" .  (
                isset($responseBody->errText) ? $responseBody->errText : ''
            ));

            if ($response['status_code'] === 400 && $responseBody->errText === 'finalization amount exceeds hold amount') {
                return ['errText' => 'The finalization amount exceeds the hold amount.'];
            }

            if ($response['status_code'] === 400 && $responseBody->errText === 'order on hold not found') {
                return ['errText' => 'Order on hold not found.'];
            }

            return false;
        } catch (\Exception $exception) {
            Log::channel('mono')->error("payment|" . __METHOD__ . " " . $exception->getMessage());
            return false;
        }
    }
}
