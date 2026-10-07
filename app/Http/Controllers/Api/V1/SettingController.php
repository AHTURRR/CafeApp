<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Catalog\SettingService;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    public function public(): JsonResponse
    {
        return response()->json(['data' => $this->settings->publicSettings()]);
    }
}
