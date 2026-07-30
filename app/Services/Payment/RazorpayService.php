<?php

namespace App\Services\Payment;

use Razorpay\Api\Api;

class RazorpayService
{
    public function createOrder(int $amountInPaise, string $receipt, string $currency = 'INR'): array
    {
        $api = new Api(config('razorpay.key'), config('razorpay.secret'));

        $order = $api->order->create([
            'receipt' => $receipt,
            'amount' => $amountInPaise,
            'currency' => $currency,
        ]);

        return [
            'id' => $order['id'],
            'amount' => (int) $order['amount'],
            'currency' => (string) $order['currency'],
        ];
    }

    public function verifySignature(string $orderId, string $paymentId, string $signature): void
    {
        $api = new Api(config('razorpay.key'), config('razorpay.secret'));

        $api->utility->verifyPaymentSignature([
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
        ]);
    }

    public function refundPayment(string $paymentId, int $amountInPaise, string $receipt): array
    {
        $api = new Api(config('razorpay.key'), config('razorpay.secret'));

        $refund = $api->payment->fetch($paymentId)->refund([
            'amount' => $amountInPaise,
            'speed' => 'normal',
            'notes' => [
                'receipt' => $receipt,
            ],
        ]);

        return [
            'id' => (string) $refund['id'],
            'status' => (string) $refund['status'],
            'amount' => (int) $refund['amount'],
        ];
    }
}
