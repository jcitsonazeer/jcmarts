<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\Payment\RazorpayService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;

use Throwable;

class ReconcileRazorpayPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:reconcile-razorpay {--minutes=30 : Minutes to look back for pending payments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile pending Razorpay payments older than N minutes';

    /**
     * Execute the console command.
     */
    public function handle(OrderService $orderService, RazorpayService $razorpayService): int
    {
        $minutes = (int) $this->option('minutes');
        if ($minutes < 1) {
            $minutes = 30;
        }

        $cutoff = Carbon::now()->subMinutes($minutes);

        $payments = Payment::query()
            ->where('gateway', 'razorpay')
            ->whereIn('status', ['pending', 'failed'])
            ->whereNotNull('gateway_order_id')
            ->where(function ($query) use ($cutoff) {
                $query->where('created_date', '<=', $cutoff)
                    ->orWhere('created_at', '<=', $cutoff);
            })
            ->orderBy('id')
            ->get();

        if ($payments->isEmpty()) {
            $this->info('No pending Razorpay payments to reconcile.');
            return self::SUCCESS;
        }

        try {
            $api = new Api(config('razorpay.key'), config('razorpay.secret'));
        } catch (Throwable $e) {
            $this->error('Failed to initialize Razorpay API.');
            return self::FAILURE;
        }

        $reconciledCount = 0;
        $refundedCount = 0;

        foreach ($payments as $payment) {
            try {
                $orderPayment = $api->order->fetch((string) $payment->gateway_order_id)->fetchPayments();
            } catch (Throwable $e) {
                continue;
            }

            $items = $orderPayment['items'] ?? [];
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $status = (string) ($item['status'] ?? '');
                if ($status !== 'captured') {
                    continue;
                }

                $razorpayPaymentId = (string) ($item['id'] ?? '');
                $amount = isset($item['amount']) ? (int) $item['amount'] : null;
                $currency = (string) ($item['currency'] ?? config('razorpay.currency', 'INR'));

                try {
                    DB::transaction(function () use ($payment, $razorpayPaymentId, $amount, $currency, $razorpayService, &$reconciledCount, &$refundedCount) {
                        $lockedPayment = Payment::query()
                            ->where('id', $payment->id)
                            ->lockForUpdate()
                            ->first();

                        if (!$lockedPayment) {
                            return;
                        }

                        // Already handled.
                        if (in_array($lockedPayment->status, ['paid', 'refunded', 'refund_pending', 'partially_refunded'], true)) {
                            return;
                        }

                        $order = Order::query()
                            ->with('items')
                            ->where('id', $lockedPayment->order_id)
                            ->lockForUpdate()
                            ->first();

                        if (!$order) {
                            return;
                        }

                        if ($amount !== null) {
                            $expectedAmount = (int) round(((float) $order->total_amount) * 100);
                            if ($expectedAmount > 0 && $amount !== $expectedAmount) {
                                return;
                            }
                        }

                        // Open, non-released order: finalize the payment.
                        if ($order->is_active && is_null($order->reservation_released_at)) {
                            if ($lockedPayment->status !== 'pending') {
                                return;
                            }

                            $order->currency = $currency;
                            $order->reservation_released_at = null;
                            $order->reservation_release_reason = null;
                            $order->updated_by_id = $order->customer_id;
                            $order->updated_date = Carbon::now();
                            $order->save();

                            $lockedPayment->gateway_payment_id = $razorpayPaymentId !== '' ? $razorpayPaymentId : $lockedPayment->gateway_payment_id;
                            $lockedPayment->amount = (float) $order->total_amount;
                            $lockedPayment->currency = $currency;
                            $lockedPayment->status = 'paid';
                            $lockedPayment->paid_at = Carbon::now();
                            $lockedPayment->updated_by_id = $order->customer_id;
                            $lockedPayment->updated_date = Carbon::now();
                            $lockedPayment->save();

                            $reconciledCount++;
                            return;
                        }

                        // Released / inactive order: stock was already given back,
                        // so a captured payment must be refunded to avoid money loss.
                        if ($razorpayPaymentId === '') {
                            return;
                        }

                        try {
                            $refund = $razorpayService->refundPayment(
                                $razorpayPaymentId,
                                $amount ?? (int) round(((float) $order->total_amount) * 100),
                                'jcmart_released_' . $order->id
                            );

                            $lockedPayment->gateway_payment_id = $razorpayPaymentId;
                            $lockedPayment->status = $refund['status'] === 'processed' ? 'refunded' : 'refund_pending';
                            $lockedPayment->updated_by_id = $order->customer_id;
                            $lockedPayment->updated_date = Carbon::now();
                            $lockedPayment->save();

                            $refundedCount++;
                            Log::warning('Refunded captured payment on a released order during reconciliation.', [
                                'order_id' => $order->id,
                                'payment_id' => $lockedPayment->id,
                                'razorpay_payment_id' => $razorpayPaymentId,
                            ]);
                        } catch (Throwable $e) {
                            Log::error('Failed to refund captured payment on a released order.', [
                                'order_id' => $order->id,
                                'payment_id' => $lockedPayment->id,
                                'razorpay_payment_id' => $razorpayPaymentId,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        });
                } catch (Throwable $e) {
                    continue;
                }

                break;
            }
        }

        $this->info('Reconciliation completed. Reconciled: ' . $reconciledCount . ', refunded: ' . $refundedCount . '.');
        return self::SUCCESS;
    }
}
