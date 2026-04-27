<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function validate(Request $request)
    {
        $request->validate([
            'code'        => 'required|string',
            'order_total' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', strtoupper($request->code))->first();

        if (! $coupon) {
            return response()->json(['message' => 'Cupom não encontrado.'], 422);
        }

        if (! $coupon->isValid((float) $request->order_total)) {
            if (! $coupon->active) {
                return response()->json(['message' => 'Cupom inativo.'], 422);
            }
            if ($coupon->expires_at && $coupon->expires_at->isPast()) {
                return response()->json(['message' => 'Cupom expirado.'], 422);
            }
            if ($coupon->max_uses !== null && $coupon->uses_count >= $coupon->max_uses) {
                return response()->json(['message' => 'Cupom esgotado.'], 422);
            }
            if ((float) $request->order_total < $coupon->min_order) {
                return response()->json([
                    'message' => "Pedido mínimo de R$ " . number_format($coupon->min_order, 2, ',', '.') . " para usar este cupom.",
                ], 422);
            }
            return response()->json(['message' => 'Cupom inválido.'], 422);
        }

        $discount = $coupon->discountFor((float) $request->order_total);

        return response()->json([
            'code'     => $coupon->code,
            'type'     => $coupon->type,
            'value'    => $coupon->value,
            'discount' => $discount,
        ]);
    }
}
