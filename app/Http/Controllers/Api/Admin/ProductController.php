<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::withTrashed()->latest();

        if ($request->search) {
            $query->where(fn($q) => $q
                ->where('name', 'ilike', "%{$request->search}%")
                ->orWhere('brand', 'ilike', "%{$request->search}%")
            );
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'brand'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'size'        => 'nullable|string|max:50',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'integer|min:0',
            'active'      => 'boolean',
            'thumbnail'   => 'nullable|url',
        ]);

        $data['slug'] = Str::slug($data['name'] . '-' . ($data['size'] ?? '') . '-' . uniqid());

        return response()->json(Product::create($data), 201);
    }

    public function show(string $id)
    {
        return response()->json(Product::withTrashed()->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $product = Product::withTrashed()->findOrFail($id);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'brand'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'size'        => 'nullable|string|max:50',
            'price'       => 'sometimes|numeric|min:0',
            'stock'       => 'integer|min:0',
            'active'      => 'boolean',
            'thumbnail'   => 'nullable|url',
        ]);

        $product->update($data);
        return response()->json($product);
    }

    public function destroy(string $id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->trashed() ? $product->forceDelete() : $product->delete();
        return response()->json(null, 204);
    }

    public function restore(string $id)
    {
        Product::withTrashed()->findOrFail($id)->restore();
        return response()->json(['message' => 'Restaurado.']);
    }
}
