<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingRequest;
use App\Http\Resources\SettingResource;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function __construct(private SettingService $settings) {}

    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => new SettingResource($this->settings->get())]);
    }

    public function update(SettingRequest $request): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Settings updated successfully',
            'data' => new SettingResource($this->settings->update($request->validated()))]);
    }

    public function publicSettings(): JsonResponse
    {
        $settings = $this->settings->get();

        return response()->json([
            'site_name' => $settings->site_name, 'site_description' => $settings->site_description,
            'social_media' => ['facebook' => $settings->facebook, 'linkedin' => $settings->linkedin,
                'instagram' => $settings->instagram, 'twitter' => $settings->twitter],
            'contact' => ['email' => $settings->email, 'phone' => $settings->phone, 'address' => $settings->address],
            'logo' => $settings->logo, 'copyright' => $settings->copyright_text,
        ]);
    }
}
