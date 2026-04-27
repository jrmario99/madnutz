<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function b4you(Request $request)
    {
        // Validar assinatura do webhook quando a API key estiver configurada
        $secret = config('services.b4you.webhook_secret');
        if ($secret) {
            $signature = $request->header('X-B4You-Signature');
            $expected  = hash_hmac('sha256', $request->getContent(), $secret);
            if (!hash_equals($expected, (string) $signature)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->json()->all();
        $event   = $payload['event']     ?? null;
        $ref     = $payload['order_ref'] ?? null;

        Log::info('B4you webhook', ['event' => $event, 'ref' => $ref]);

        if (!$ref) {
            return response()->json(['ok' => true]);
        }

        $order = Order::where('number', $ref)->first();
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        match ($event) {
            'payment.approved' => $order->update([
                'status'      => 'paid',
                'paid_at'     => now(),
                'payment_ref' => $payload['transaction_id'] ?? null,
            ]),
            'payment.refunded', 'payment.cancelled' => $order->update([
                'status' => 'cancelled',
            ]),
            default => null,
        };

        return response()->json(['ok' => true]);
    }
}
