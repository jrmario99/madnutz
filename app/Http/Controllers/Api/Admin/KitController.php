<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kit;
use App\Models\KitSlot;
use App\Models\KitImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KitController extends Controller
{
    public function index()
    {
        return response()->json(
            Kit::with(['slots', 'images'])->orderBy('sort_order')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'sale_price'  => 'nullable|numeric|min:0',
            'image'       => 'nullable|string',
            'active'      => 'boolean',
            'featured'    => 'boolean',
            'badge'         => 'nullable|string|max:100',
            'sort_order'    => 'integer|min:0',
            'free_shipping' => 'boolean',
            'slots'              => 'sometimes|array',
            'slots.*.size'       => 'required_with:slots|string',
            'slots.*.quantity'   => 'required_with:slots|integer|min:1',
            'images'             => 'sometimes|array',
            'images.*.url'       => 'required_with:images|string',
            'images.*.sort_order'=> 'integer|min:0',
        ]);

        $slots  = $data['slots'] ?? [];
        $images = $data['images'] ?? [];
        unset($data['slots'], $data['images']);

        $data['slug'] = Str::slug($data['name']) . '-' . uniqid();
        $kit = Kit::create($data);

        foreach ($slots as $i => $slot) {
            $kit->slots()->create(['size' => $slot['size'], 'quantity' => $slot['quantity']]);
        }
        foreach ($images as $i => $img) {
            $kit->images()->create(['url' => $img['url'], 'sort_order' => $img['sort_order'] ?? $i]);
        }

        return response()->json($kit->load(['slots', 'images']), 201);
    }

    public function show(string $id)
    {
        return response()->json(Kit::with(['slots', 'images'])->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $kit = Kit::findOrFail($id);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'sometimes|numeric|min:0',
            'sale_price'  => 'nullable|numeric|min:0',
            'image'       => 'nullable|string',
            'active'      => 'boolean',
            'featured'    => 'boolean',
            'badge'         => 'nullable|string|max:100',
            'sort_order'    => 'integer|min:0',
            'free_shipping' => 'boolean',
            'slots'              => 'sometimes|array',
            'slots.*.size'       => 'required_with:slots|string',
            'slots.*.quantity'   => 'required_with:slots|integer|min:1',
            'images'             => 'sometimes|array',
            'images.*.url'       => 'required_with:images|string',
            'images.*.sort_order'=> 'integer|min:0',
        ]);

        if (array_key_exists('slots', $data)) {
            $kit->slots()->delete();
            foreach ($data['slots'] as $slot) {
                $kit->slots()->create(['size' => $slot['size'], 'quantity' => $slot['quantity']]);
            }
            unset($data['slots']);
        }

        if (array_key_exists('images', $data)) {
            $kit->images()->delete();
            foreach ($data['images'] as $i => $img) {
                $kit->images()->create(['url' => $img['url'], 'sort_order' => $img['sort_order'] ?? $i]);
            }
            unset($data['images']);
        }

        $kit->update($data);
        return response()->json($kit->load(['slots', 'images']));
    }

    public function destroy(string $id)
    {
        Kit::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:kits,id',
        ]);

        foreach ($request->order as $position => $kitId) {
            Kit::where('id', $kitId)->update(['sort_order' => $position]);
        }

        return response()->json(['message' => 'Ordem atualizada.']);
    }
}
