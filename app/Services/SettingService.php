<?php

namespace App\Services;

use App\Factories\UploadStrategyFactory;
use App\Models\Setting;

final class SettingService
{
    public function __construct(private UploadStrategyFactory $uploads) {}

    public function get(): Setting
    {
        return Setting::getSettings();
    }

    public function update(array $data): Setting
    {
        $settings = $this->get();
        foreach (['logo', 'favicon'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->uploads->make('settings')->store($data[$field]);
            }
        }
        $settings->update($data);

        return $settings;
    }
}
