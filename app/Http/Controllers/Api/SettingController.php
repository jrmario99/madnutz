<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class SettingController extends Controller
{
    private const PUBLIC_KEYS = ['custom_kit_enabled'];

    public function index()
    {
        $settings = Setting::whereIn('key', self::PUBLIC_KEYS)
            ->pluck('value', 'key');

        return response()->json($settings);
    }
}
