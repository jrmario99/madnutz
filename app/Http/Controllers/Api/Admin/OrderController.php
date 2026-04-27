<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('items')->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(fn($q) => $q
                ->where('customer_name', 'ilike', "%{$request->search}%")
                ->orWhere('customer_email', 'ilike', "%{$request->search}%")
                ->orWhere('number', 'ilike', "%{$request->search}%")
                ->orWhere('payment_ref', 'ilike', "%{$request->search}%")
            );
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return response()->json($query->paginate(20));
    }

    public function show(string $id)
    {
        return response()->json(
            Order::with(['items.kit'])->findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|in:' . implode(',', Order::STATUSES),
            'notes'  => 'nullable|string',
        ]);

        if ($data['status'] === 'paid' && !$order->paid_at) {
            $data['paid_at'] = now();
        }

        $order->update($data);
        return response()->json($order->load('items.kit'));
    }

    public function destroy(string $id)
    {
        Order::findOrFail($id)->update(['status' => 'cancelled']);
        return response()->json(null, 204);
    }

    public function stats()
    {
        return response()->json([
            'total_orders'  => Order::count(),
            'total_revenue' => Order::where('status', 'paid')->sum('total'),
            'pending'       => Order::where('status', 'pending')->count(),
            'paid'          => Order::where('status', 'paid')->count(),
            'shipped'       => Order::where('status', 'shipped')->count(),
            'cancelled'     => Order::where('status', 'cancelled')->count(),
        ]);
    }
}
