<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerProfileController extends Controller
{
    public function update(Request $request)
    {
        $customer = $request->user('customers');

        $data = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => 'sometimes|email|unique:customers,email,' . $customer->id,
        ]);

        $customer->update($data);

        return response()->json($customer->fresh());
    }

    public function updatePassword(Request $request)
    {
        $customer = $request->user('customers');

        $request->validate([
            'current_password' => $customer->password ? 'required|string' : 'nullable',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        if ($customer->password && ! Hash::check($request->current_password, $customer->password)) {
            throw ValidationException::withMessages(['current_password' => 'Senha atual incorreta.']);
        }

        $customer->update(['password' => $request->password]);

        return response()->json(['message' => 'Senha atualizada com sucesso.']);
    }
}
