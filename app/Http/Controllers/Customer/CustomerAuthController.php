<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Mail\CustomerOtpMail;
use App\Models\Customer;
use App\Models\CustomerOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class CustomerAuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:customers,email',
            'password' => 'nullable|string|min:6',
            'phone'    => 'nullable|string|max:20',
        ]);

        $customer = Customer::create($data);
        $token    = $customer->createToken('customer', ['customer'])->plainTextToken;

        return response()->json(['token' => $token, 'customer' => $customer], 201);
    }

    public function loginPassword(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $customer = Customer::where('email', $data['email'])->first();

        if (! $customer || ! $customer->password || ! Hash::check($data['password'], $customer->password)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        $token = $customer->createToken('customer', ['customer'])->plainTextToken;

        return response()->json(['token' => $token, 'customer' => $customer]);
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->email;
        $key   = 'otp-send:' . $email;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Aguarde {$seconds}s antes de solicitar um novo código.",
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        CustomerOtp::where('email', $email)->whereNull('used_at')->update(['used_at' => now()]);

        CustomerOtp::create([
            'email'      => $email,
            'code'       => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($email)->send(new CustomerOtpMail($code));

        return response()->json(['message' => 'Código enviado para o seu e-mail.']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $otp = CustomerOtp::where('email', $request->email)
            ->valid()
            ->latest()
            ->first();

        if (! $otp) {
            return response()->json(['message' => 'Código inválido ou expirado.'], 422);
        }

        $otp->increment('attempts');

        if (! Hash::check($request->code, $otp->code)) {
            if ($otp->attempts >= 3) {
                $otp->update(['used_at' => now()]);
                return response()->json(['message' => 'Muitas tentativas. Solicite um novo código.'], 422);
            }
            return response()->json(['message' => 'Código incorreto.'], 422);
        }

        $otp->update(['used_at' => now()]);

        $customer = Customer::firstOrCreate(
            ['email' => $request->email],
            ['name'  => explode('@', $request->email)[0]],
        );

        if (! $customer->email_verified_at) {
            $customer->update(['email_verified_at' => now()]);
        }

        $token = $customer->createToken('customer', ['customer'])->plainTextToken;

        return response()->json(['token' => $token, 'customer' => $customer]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user('customers'));
    }

    public function logout(Request $request)
    {
        $request->user('customers')->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado.']);
    }
}
