<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminConfigController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = AdminSetting::pluck('value', 'key')->toArray();

        return response()->json(['settings' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            AdminSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return response()->json(['message' => 'Configuración actualizada.']);
    }

    public function rateLimits(): JsonResponse
    {
        $settings = AdminSetting::whereIn('key', [
            'rate_limit_login',
            'rate_limit_api',
            'rate_limit_upload',
            'max_posts_per_day',
            'max_chars_per_post',
        ])->pluck('value', 'key')->toArray();

        return response()->json(['settings' => $settings]);
    }

    public function updateRateLimits(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.rate_limit_login' => ['sometimes', 'integer', 'min:1'],
            'settings.rate_limit_api' => ['sometimes', 'integer', 'min:1'],
            'settings.rate_limit_upload' => ['sometimes', 'integer', 'min:1'],
            'settings.max_posts_per_day' => ['sometimes', 'integer', 'min:1'],
            'settings.max_chars_per_post' => ['sometimes', 'integer', 'min:1'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            AdminSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return response()->json(['message' => 'Rate limits actualizados.']);
    }
}
