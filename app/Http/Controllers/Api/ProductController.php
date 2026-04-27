<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Product::where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'brand', 'size', 'price', 'thumbnail'])
        );
    }

    public function show(string $slug)
    {
        return response()->json(
            Product::where('slug', $slug)->where('active', true)->firstOrFail()
        );
    }
}
