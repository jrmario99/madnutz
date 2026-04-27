<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index()
    {
        return response()->json(Coupon::orderByDesc('created_at')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'       => 'required|string|max:50|unique:coupons,code',
            'type'       => 'required|in:percent,fixed',
            'value'      => 'required|numeric|min:0.01',
            'min_order'  => 'nullable|numeric|min:0',
            'max_uses'   => 'nullable|integer|min:1',
            'active'     => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        $data['code']      = strtoupper($data['code']);
        $data['min_order'] = $data['min_order'] ?? 0;

        return response()->json(Coupon::create($data), 201);
    }

    public function show(string $id)
    {
        return response()->json(Coupon::findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $coupon = Coupon::findOrFail($id);

        $data = $request->validate([
            'code'       => "sometimes|string|max:50|unique:coupons,code,{$id}",
            'type'       => 'sometimes|in:percent,fixed',
            'value'      => 'sometimes|numeric|min:0.01',
            'min_order'  => 'nullable|numeric|min:0',
            'max_uses'   => 'nullable|integer|min:1',
            'active'     => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $coupon->update($data);
        return response()->json($coupon->fresh());
    }

    public function destroy(string $id)
    {
        Coupon::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
