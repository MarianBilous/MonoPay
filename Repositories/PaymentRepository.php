<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository
{
    public static function storeNew($order, $request): bool
    {
        $data = [
            'rrn' => $request['rrn'] ?? null,
            'payment_id' => $request['paymentInfo']['tranId'] ?? null,
            'order_id' => $order->id ?? null,
            'user_id' => $order->customer_id ?? 0,
            'amount' => $request['amount'] ?? null,
            'response_status' => $request['status'] ?? 'created',
            'failure_reason' => $request['failureReason'] ?? null,
            'err_code' => $request['errCode'] ?? null,
            'invoice_id' => $request['invoiceId'] ?? null,
            'updated_at' => time(),
            'time' => time(),
        ];

        $result   = Payment::create($data);
        $insertID = $result ? $result->id : null;

        if (!$result && $insertID) {
            return true;
        }

        return false;
    }

    public static function update($response)
    {
        $data = [
            'rrn' => $response->paymentInfo->rrn ?? null,
            'payment_id' => $response->paymentInfo->tranId ?? null,
            'amount' => isset($response->amount) ? ($response->amount / 100) : null,
            'response_status' => $response->status,
            'failure_reason' => $response->failureReason ?? null,
            'err_code' => $response->errCode ?? null,
            'updated_at' => time(),
        ];

        $result = Payment::where('invoice_id', $response->invoiceId)->update($data); // Update row

        if ($result) {
            return true;
        }

        return false;
    }

    public static function updateStatus($invoiceId, $status, $reasonErr = '')
    {
        $data = [
            'response_status' => $status,
        ];

        if ($status == 'error') {
            $data['failure_reason'] = $reasonErr;
        }

        $result = Payment::where('invoice_id', $invoiceId)->update($data); // Update row

        if ($result) {
            return true;
        }

        return false;
    }

    public static function touchPayment($id)
    {
        return Payment::where('id', $id)->update(['updated_at' => time()]);
    }
}
