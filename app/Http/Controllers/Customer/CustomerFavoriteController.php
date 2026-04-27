<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerFavorite;
use App\Models\Kit;
use App\Models\Product;
use Illuminate\Http\Request;

class CustomerFavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = $request->user('customers')
            ->favorites()
            ->with('favoritable')
            ->latest()
            ->get()
            ->map(function ($fav) {
                return [
                    'id'             => $fav->id,
                    'type'           => class_basename($fav->favoritable_type),
                    'favoritable_id' => $fav->favoritable_id,
                    'item'           => $fav->favoritable,
                ];
            });

        return response()->json($favorites);
    }

    public function toggle(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:product,kit',
            'id'   => 'required|integer',
        ]);

        $modelClass = $data['type'] === 'kit' ? Kit::class : Product::class;

        $existing = CustomerFavorite::where([
            'customer_id'      => $request->user('customers')->id,
            'favoritable_type' => $modelClass,
            'favoritable_id'   => $data['id'],
        ])->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['favorited' => false]);
        }

        CustomerFavorite::create([
            'customer_id'      => $request->user('customers')->id,
            'favoritable_type' => $modelClass,
            'favoritable_id'   => $data['id'],
        ]);

        return response()->json(['favorited' => true]);
    }
}
