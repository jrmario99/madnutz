<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user('customers')
            ->orders()
            ->with('items')
            ->latest()
            ->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, int $id)
    {
        $order = $request->user('customers')
            ->orders()
            ->with('items')
            ->findOrFail($id);

        return response()->json($order);
    }

    public function repeat(Request $request, int $id)
    {
        $order = $request->user('customers')
            ->orders()
            ->with('items')
            ->findOrFail($id);

        return response()->json([
            'items' => $order->items->map(fn ($item) => [
                'name'         => $item->kit_name_snapshot,
                'price'        => $item->price_snapshot,
                'qty'          => $item->quantity,
                'is_custom'    => $item->is_custom,
                'custom_items' => $item->custom_items,
            ]),
        ]);
    }
}
