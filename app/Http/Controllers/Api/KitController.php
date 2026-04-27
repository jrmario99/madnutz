<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kit;
use App\Models\Product;

class KitController extends Controller
{
    public function index()
    {
        return response()->json(
            Kit::with(['slots', 'images'])
                ->where('active', true)
                ->orderBy('sort_order')
                ->get()
        );
    }

    public function show(string $slug)
    {
        $kit = Kit::with(['slots', 'images'])
            ->where('active', true)
            ->where(fn($q) => is_numeric($slug)
                ? $q->where('id', $slug)
                : $q->where('slug', $slug))
            ->firstOrFail();

        // Attach available products grouped by size (from kit slots)
        $sizes = $kit->slots->pluck('size')->unique()->values();
        $kit->available_products = Product::where('active', true)
            ->whereIn('size', $sizes)
            ->orderBy('name')
            ->get(['id', 'name', 'brand', 'size', 'price', 'thumbnail']);

        return response()->json($kit);
    }

    public function products(string $slug)
    {
        $kit = Kit::with('slots')
            ->where('active', true)
            ->where(fn($q) => is_numeric($slug)
                ? $q->where('id', $slug)
                : $q->where('slug', $slug))
            ->firstOrFail();
        $sizes = $kit->slots->pluck('size')->unique()->values();

        return response()->json(
            Product::where('active', true)
                ->whereIn('size', $sizes)
                ->orderBy('name')
                ->get(['id', 'name', 'brand', 'size', 'price', 'thumbnail'])
        );
    }
}
