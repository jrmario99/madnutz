<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            return response()->json([
                'order_number' => $order->number,
                'total'        => $order->total,
            ], 201);
        });
    }
}
