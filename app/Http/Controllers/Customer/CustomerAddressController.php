<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->addresses()->orderByDesc('is_default')->orderBy('created_at')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'          => 'required|string|max:50',
            'recipient_name' => 'nullable|string|max:150',
            'zip'            => 'required|string|max:10',
            'street'         => 'required|string|max:255',
            'number'         => 'nullable|string|max:20',
            'complement'     => 'nullable|string|max:100',
            'neighborhood'   => 'nullable|string|max:100',
            'city'           => 'required|string|max:100',
            'state'          => 'required|string|size:2',
            'is_default'     => 'boolean',
        ]);

        $customer = $request->user();

        if (!empty($data['is_default'])) {
            $customer->addresses()->update(['is_default' => false]);
        }

        if ($customer->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        $address = $customer->addresses()->create($data);

        return response()->json($address, 201);
    }

    public function update(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);

        $data = $request->validate([
            'label'          => 'sometimes|string|max:50',
            'recipient_name' => 'nullable|string|max:150',
            'zip'            => 'sometimes|string|max:10',
            'street'         => 'sometimes|string|max:255',
            'number'         => 'nullable|string|max:20',
            'complement'     => 'nullable|string|max:100',
            'neighborhood'   => 'nullable|string|max:100',
            'city'           => 'sometimes|string|max:100',
            'state'          => 'sometimes|string|size:2',
            'is_default'     => 'boolean',
        ]);

        if (!empty($data['is_default'])) {
            $request->user()->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json($address);
    }

    public function destroy(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $request->user()->addresses()->orderBy('created_at')->first()?->update(['is_default' => true]);
        }

        return response()->json(['ok' => true]);
    }

    public function setDefault(Request $request, $id)
    {
        $customer = $request->user();
        $customer->addresses()->update(['is_default' => false]);
        $customer->addresses()->findOrFail($id)->update(['is_default' => true]);

        return response()->json(['ok' => true]);
    }
}
