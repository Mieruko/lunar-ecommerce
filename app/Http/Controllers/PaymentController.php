<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function vnpayRedirect(Request $request, Order $order)
    {
        $this->authorizeOrderPayment($request, $order);
        $payment = $order->payments()->where('provider', 'vnpay')->latest()->firstOrFail();
        $code = config('services.vnpay.tmn_code'); $secret = config('services.vnpay.hash_secret');
        abort_unless($code && $secret, 503, 'VNPAY sandbox chưa được cấu hình.');
        $params = ['vnp_Version' => '2.1.0', 'vnp_Command' => 'pay', 'vnp_TmnCode' => $code, 'vnp_Amount' => $payment->amount * 100, 'vnp_CurrCode' => 'VND', 'vnp_TxnRef' => $order->order_number, 'vnp_OrderInfo' => 'Thanh toan don hang '.$order->order_number, 'vnp_OrderType' => 'other', 'vnp_Locale' => 'vn', 'vnp_ReturnUrl' => route('payments.vnpay.return'), 'vnp_IpnUrl' => route('payments.vnpay.ipn'), 'vnp_IpAddr' => request()->ip(), 'vnp_CreateDate' => now()->format('YmdHis')];
        if ($payment->payment_method === 'vnpay_qr') $params['vnp_BankCode'] = 'VNPAYQR';
        ksort($params); $hashData = urldecode(http_build_query($params)); $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $secret);
        return redirect(config('services.vnpay.url').'?'.http_build_query($params));
    }

    public function vnpayReturn(Request $request)
    {
        [$order, $valid, $data] = $this->processVnpay($request);
        return redirect()->route('checkout.confirmation', $order)->with($valid ? 'success' : 'error', $valid ? 'Đã nhận kết quả thanh toán VNPAY.' : 'Không thể xác thực giao dịch VNPAY.');
    }

    public function vnpayIpn(Request $request)
    {
        [, $valid] = $this->processVnpay($request);
        return response()->json(['RspCode' => $valid ? '00' : '97', 'Message' => $valid ? 'Confirm Success' : 'Invalid signature']);
    }

    public function paypalRedirect(Request $request, Order $order)
    {
        $this->authorizeOrderPayment($request, $order);
        $payment = $order->payments()->where('provider', 'paypal')->latest()->firstOrFail();
        $client = config('services.paypal.client_id'); $secret = config('services.paypal.secret'); abort_unless($client && $secret, 503, 'PayPal sandbox chưa được cấu hình.');
        $base = config('services.paypal.base_url');
        try {
            $token = $this->paypalAccessToken($client, $secret, $base);
            $response = Http::withToken($token)
                ->connectTimeout(15)
                ->timeout(30)
                ->retry(2, 500, fn ($exception) => $exception instanceof ConnectionException)
                ->withHeaders(['PayPal-Request-Id' => 'lunar-'.$order->order_number])
                ->post($base.'/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'reference_id' => $order->order_number,
                        'amount' => ['currency_code' => 'USD', 'value' => number_format((float) $payment->provider_amount, 2, '.', '')],
                    ]],
                    'application_context' => ['return_url' => route('payments.paypal.return'), 'cancel_url' => route('checkout.confirmation', $order)],
                ])->throw()->json();

            $payment->update(['transaction_id' => $response['id'], 'provider_payload' => $response]);
            $approvalLink = collect($response['links'] ?? [])->firstWhere('rel', 'approve');
            $approve = $approvalLink['href'] ?? null;
            abort_unless($approve, 502, 'PayPal không trả về liên kết phê duyệt.');

            return redirect($approve);
        } catch (\Throwable $e) {
            report($e);
            $this->payments->markFailed($payment, [
                'stage' => 'create_paypal_order',
                'message' => Str::limit($e->getMessage(), 500),
            ]);

            return redirect()
                ->route('checkout.payment')
                ->with('error', 'Chưa thể kết nối PayPal. Đơn thử đã được giải phóng tồn kho; vui lòng thử lại sau ít phút.');
        }
    }

    public function paypalReturn(Request $request)
    {
        $payment = Payment::where('provider', 'paypal')->where('transaction_id', $request->string('token'))->firstOrFail();
        $order = Order::findOrFail($payment->order_id);
        if ($payment->status === 'paid') {
            return redirect()->route('checkout.confirmation', $order)->with('success', 'Thanh toán PayPal đã được xác nhận.');
        }

        $client = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');
        $base = config('services.paypal.base_url');

        if (! $client || ! $secret) {
            return redirect()->route('checkout.confirmation', $order)->with('error', 'PayPal chưa được cấu hình đầy đủ. Đơn vẫn đang chờ thanh toán.');
        }

        try {
            $token = $this->paypalAccessToken($client, $secret, $base);
            // PayPal requires an explicit JSON request body for this endpoint, even when empty.
            $response = Http::withToken($token)
                ->acceptJson()
                ->withBody('{}', 'application/json')
                ->post($base.'/v2/checkout/orders/'.$payment->transaction_id.'/capture');

            $capture = $response->json() ?: [];
            if ($response->successful() && ($capture['status'] ?? '') === 'COMPLETED') {
                $this->payments->markPaid($payment, null, $capture);

                return redirect()->route('checkout.confirmation', $order)->with('success', 'Thanh toán PayPal đã được xác nhận.');
            }

            $this->recordPaypalCaptureIssue($payment, $response->status(), $capture);
        } catch (\Throwable $e) {
            report($e);
            $this->recordPaypalCaptureIssue($payment, null, [
                'name' => class_basename($e),
                'message' => $e->getMessage(),
            ]);
        }

        // A transient API error is not proof that the buyer failed to pay. The webhook or admin
        // reconciliation can still mark this payment paid, so leave stock and the order pending.
        return redirect()
            ->route('checkout.confirmation', $order)
            ->with('error', 'PayPal đã duyệt yêu cầu nhưng hệ thống chưa xác nhận được giao dịch. Đơn vẫn được giữ ở trạng thái chờ; vui lòng không thanh toán lại.');
    }

    /** @param array<string, mixed> $payload */
    private function recordPaypalCaptureIssue(Payment $payment, ?int $httpStatus, array $payload): void
    {
        $details = array_filter([
            'name' => $payload['name'] ?? null,
            'message' => $payload['message'] ?? null,
            'debug_id' => $payload['debug_id'] ?? null,
            'status' => $payload['status'] ?? null,
            'details' => $payload['details'] ?? null,
        ], fn ($value) => $value !== null);

        $payment->update([
            'provider_payload' => [
                ...(is_array($payment->provider_payload) ? $payment->provider_payload : []),
                'capture_issue' => [
                    'http_status' => $httpStatus,
                    'details' => $details,
                    'recorded_at' => now()->toIso8601String(),
                ],
            ],
        ]);

        logger()->warning('PayPal capture remains pending.', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'http_status' => $httpStatus,
            'details' => $details,
        ]);
    }

    public function paypalWebhook(Request $request)
    {
        $client = config('services.paypal.client_id'); $secret = config('services.paypal.secret'); $webhookId = config('services.paypal.webhook_id');
        if (! $client || ! $secret || ! $webhookId) return response()->json(['ignored' => true], 202);
        $base = config('services.paypal.base_url');
        $token = $this->paypalAccessToken($client, $secret, $base);
        $verified = Http::withToken($token)->post($base.'/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'), 'cert_url' => $request->header('PAYPAL-CERT-URL'),
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'), 'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'), 'webhook_id' => $webhookId, 'webhook_event' => $request->all(),
        ])->throw()->json('verification_status') === 'SUCCESS';
        if (! $verified) return response()->json(['error' => 'Invalid webhook signature'], 400);
        $resource = $request->input('resource', []); $transaction = $resource['supplementary_data']['related_ids']['order_id'] ?? $resource['id'] ?? null;
        $payment = Payment::where('provider', 'paypal')->where('transaction_id', $transaction)->first();
        if ($payment && $request->input('event_type') === 'PAYMENT.CAPTURE.COMPLETED') $this->payments->markPaid($payment, null, $request->all());
        return response()->json(['ok' => true]);
    }

    private function processVnpay(Request $request): array
    {
        $data = $request->all(); $hash = $data['vnp_SecureHash'] ?? ''; unset($data['vnp_SecureHash'], $data['vnp_SecureHashType']); ksort($data);
        $valid = config('services.vnpay.hash_secret') && hash_equals(hash_hmac('sha512', urldecode(http_build_query($data)), config('services.vnpay.hash_secret')), $hash);
        $order = Order::where('order_number', $data['vnp_TxnRef'] ?? null)->firstOrFail();
        $payment = $order->payments()->where('provider', 'vnpay')->latest()->first();
        if ($payment && $valid && ($data['vnp_ResponseCode'] ?? '') === '00') $this->payments->markPaid($payment, null, $data);
        elseif ($payment && $valid) $this->payments->markFailed($payment, $data);
        return [$order, (bool) $valid, $data];
    }

    private function paypalAccessToken(string $client, string $secret, string $base): string
    {
        return Http::asForm()
            ->withBasicAuth($client, $secret)
            ->connectTimeout(15)
            ->timeout(30)
            ->retry(2, 500, fn ($exception) => $exception instanceof ConnectionException)
            ->post($base.'/v1/oauth2/token', ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');
    }

    private function authorizeOrderPayment(Request $request, Order $order): void
    {
        $isOrderOwner = $request->user() !== null && $request->user()->id === $order->user_id;
        $isCurrentCheckout = in_array($order->id, (array) $request->session()->get('confirmation_order_ids', []), true);

        abort_unless($isOrderOwner || $isCurrentCheckout, 403);
    }
}
