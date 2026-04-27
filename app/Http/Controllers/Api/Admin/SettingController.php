<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private const ALLOWED_KEYS = ['custom_kit_enabled'];

    public function index()
    {
        return response()->json(
            Setting::whereIn('key', self::ALLOWED_KEYS)->pluck('value', 'key')
        );
    }

    public function update(Request $request)
    {
        foreach (self::ALLOWED_KEYS as $key) {
            if ($request->has($key)) {
                Setting::set($key, $request->input($key));
            }
        }

        return response()->json(
            Setting::whereIn('key', self::ALLOWED_KEYS)->pluck('value', 'key')
        );
    }
}
