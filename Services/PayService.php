<?php

namespace App\Services;

use App\Interfaces\PayInterface;

/**
 * Service class for handling payment operations.
 */
class PayService
{
    /**
     * @var PayInterface The payment adapter instance.
     */
    private PayInterface $payAdapter;

    /**
     * Constructor for the PayService.
     *
     * @param PayInterface $payAdapter The payment adapter to use for processing payments.
     */
    public function __construct(PayInterface $payAdapter)
    {
        $this->payAdapter = $payAdapter;
    }

    /**
     * Creates an invoice using the specified payment data.
     *
     * @param array $payData The data required to create an invoice.
     * @return mixed The response from the payment adapter's create method.
     */
    public function createInvoice(array $payData)
    {
        return $this->payAdapter->create($payData);
    }

    /**
     * Retrieves the status of a given invoice.
     *
     * @param string $invoiceId The ID of the invoice to check the status for.
     * @return mixed The status of the invoice from the payment adapter.
     */
    public function getStatus(string $invoiceId)
    {
        return $this->payAdapter->getStatus($invoiceId);
    }

    public function finalizeInvoice($order)
    {
        return $this->payAdapter->finalizeInvoice($order);
    }
}
