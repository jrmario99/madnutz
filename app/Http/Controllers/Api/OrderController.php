<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\PersonalAccessToken;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'          => 'required|string|max:255',
            'customer_email'         => 'required|email|max:255',
            'customer_phone'         => 'required|string|max:50',
            'customer_address'       => 'nullable|array',
            'items'                  => 'required|array|min:1',
            'items.*.name'           => 'required|string',
            'items.*.price'          => 'required|numeric|min:0',
            'items.*.qty'            => 'required|integer|min:1',
            'items.*.is_custom'      => 'boolean',
            'items.*.product_id'     => 'nullable|integer',
            'items.*.custom_items'   => 'nullable|array',
        ]);

        // Resolve optional customer from Bearer token
        $customerId = null;
        if ($request->bearerToken()) {
            $pat = PersonalAccessToken::findToken($request->bearerToken());
            if ($pat && $pat->tokenable instanceof Customer) {
                $customerId = $pat->tokenable->id;
            }
        }

        return DB::transaction(function () use ($data, $customerId) {
            $subtotal = collect($data['items'])->sum(fn($i) => $i['price'] * $i['qty']);
            $shipping  = $subtotal >= 199 ? 0 : 19.90;

            $order = Order::create([
                'customer_id'      => $customerId,
                'customer_name'    => $data['customer_name'],
                'customer_email'   => $data['customer_email'],
                'customer_phone'   => $data['customer_phone'],
                'customer_address' => $data['customer_address'] ?? null,
                'subtotal'         => $subtotal,
                'shipping'         => $shipping,
                'total'            => $subtotal + $shipping,
                'status'           => 'pending',
            ]);

            foreach ($data['items'] as $item) {
                $isCustom = $item['is_custom'] ?? false;
                OrderItem::create([
                    'order_id'          => $order->id,
                    'kit_id'            => $isCustom ? null : ($item['product_id'] ?? null),
                    'kit_name_snapshot' => $item['name'],
                    'price_snapshot'    => $item['price'],
                    'quantity'          => $item['qty'],
                    'is_custom'         => $isCustom,
                    'custom_items'      => $isCustom ? ($item['custom_items'] ?? null) : null,
                ]);
            }

            $checkoutUrl = $this->createB4YouCheckout($order, $data);

            return response()->json([
                'order_number' => $order->number,
                'total'        => $order->total,
                'checkout_url' => $checkoutUrl,
            ], 201);
        });
    }

    private function createB4YouCheckout(Order $order, array $data): ?string
    {
        $apiKey = config('services.b4you.api_key');
        if (!$apiKey) return null;

        $baseUrl = config('services.b4you.base_url', 'https://api.b4you.com.br/v1');
        $appUrl  = config('app.url');

        try {
            $response = Http::withToken($apiKey)
                ->withoutVerifying()
                ->timeout(15)
                ->post("{$baseUrl}/checkout", [
                    'order_ref' => $order->number,
                    'amount'    => (int) round($order->total * 100),
                    'customer'  => [
                        'name'  => $data['customer_name'],
                        'email' => $data['customer_email'],
                        'phone' => $data['customer_phone'],
                    ],
                    'items' => collect($data['items'])->map(fn($i) => [
                        'title'    => $i['name'],
                        'quantity' => $i['qty'],
                        'price'    => (int) round($i['price'] * 100),
                    ])->values()->all(),
                    'webhook_url' => "{$appUrl}/api/webhooks/b4you",
                    'success_url' => "{$appUrl}/pedido-confirmado?ref={$order->number}",
                    'cancel_url'  => "{$appUrl}/carrinho",
                ]);

            if ($response->successful()) {
                return $response->json('checkout_url');
            }

            \Illuminate\Support\Facades\Log::error('B4You checkout failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('B4You request error: ' . $e->getMessage());
        }

        return null;
    }
}
