<?php

namespace App\Interfaces;

/**
 * Interface PayInterface
 * Defines the contract for payment processing classes.
 */
interface PayInterface
{
    /**
     * Creates a new payment with the provided data.
     *
     * @param array $payData The data required to create a payment.
     * @return array|false The payment creation result or false on failure.
     */
    public function create(array $payData);

    /**
     * Retrieves the status of a specific payment by invoice ID.
     *
     * @param string $invoiceId The ID of the invoice to check.
     * @return array|false The status of the payment or false on failure.
     */
    public function getStatus(string $invoiceId);

    public function finalizeInvoice($order);
}
