<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotificationSetting;

class NotificationSettingsService
{
    public function getSettings(User $user): array
    {
        try {
            $settings = $user->initializeNotificationSettings();

            return $settings
                ? $this->formatSettings($settings)
                : $this->defaultSettingsStructure();
        } catch (\Throwable) {
            return $this->defaultSettingsStructure();
        }
    }

    public function update(
        User $user,
        array $data
    ): array {
        $settings = $user->initializeNotificationSettings();

        $settings->update($data);

        return $this->formatSettings($settings->fresh());
    }

    public function updateCategory(
        User $user,
        string $category,
        bool $enabled
    ): ?array {
        $fields = $this->getCategoryFields($category);

        if ($fields === []) {
            return null;
        }

        $updateData = [];

        foreach ($fields as $field) {
            $updateData[$field] = $enabled;
        }

        $settings = $user->initializeNotificationSettings();
        $settings->update($updateData);

        return $this->formatSettings($settings->fresh());
    }

    private function defaultSettingsStructure(): array
    {
        return [
            'order_delivery_updates' => [
                'category' => 'Order & Delivery Updates',
                'enabled' => true,
                'settings' => [
                    'order_confirmation' => true,
                    'order_shipped' => true,
                    'delivery_updates' => true,
                    'out_of_stock_alerts' => true,
                ],
            ],
            'deals_promotions' => [
                'category' => 'Deals & Promotions',
                'enabled' => true,
                'settings' => [
                    'weekly_discounts' => true,
                    'exclusive_member_offers' => true,
                    'seasonal_campaigns' => true,
                ],
            ],
            'account_reminders' => [
                'category' => 'Account & Reminders',
                'enabled' => true,
                'settings' => [
                    'cart_reminders' => true,
                    'payment_billing' => true,
                ],
            ],
            'channels' => [
                'category' => 'Notification Channels',
                'enabled' => true,
                'settings' => [
                    'email_notifications' => true,
                    'push_notifications' => true,
                    'sms_notifications' => false,
                ],
            ],
        ];
    }

    private function formatSettings(
        UserNotificationSetting $settings
    ): array {
        return [
            'order_delivery_updates' => [
                'category' => 'Order & Delivery Updates',
                'enabled' => $settings->order_confirmation
                    || $settings->order_shipped
                    || $settings->delivery_updates
                    || $settings->out_of_stock_alerts,
                'settings' => [
                    'order_confirmation' => $settings->order_confirmation,
                    'order_shipped' => $settings->order_shipped,
                    'delivery_updates' => $settings->delivery_updates,
                    'out_of_stock_alerts' => $settings->out_of_stock_alerts,
                ],
            ],
            'deals_promotions' => [
                'category' => 'Deals & Promotions',
                'enabled' => $settings->weekly_discounts
                    || $settings->exclusive_member_offers
                    || $settings->seasonal_campaigns,
                'settings' => [
                    'weekly_discounts' => $settings->weekly_discounts,
                    'exclusive_member_offers' => $settings->exclusive_member_offers,
                    'seasonal_campaigns' => $settings->seasonal_campaigns,
                ],
            ],
            'account_reminders' => [
                'category' => 'Account & Reminders',
                'enabled' => $settings->cart_reminders
                    || $settings->payment_billing,
                'settings' => [
                    'cart_reminders' => $settings->cart_reminders,
                    'payment_billing' => $settings->payment_billing,
                ],
            ],
            'channels' => [
                'category' => 'Notification Channels',
                'enabled' => $settings->email_notifications
                    || $settings->push_notifications
                    || $settings->sms_notifications,
                'settings' => [
                    'email_notifications' => $settings->email_notifications,
                    'push_notifications' => $settings->push_notifications,
                    'sms_notifications' => $settings->sms_notifications,
                ],
            ],
        ];
    }

    private function getCategoryFields(string $category): array
    {
        return [
            'order_delivery' => [
                'order_confirmation',
                'order_shipped',
                'delivery_updates',
                'out_of_stock_alerts',
            ],
            'deals_promotions' => [
                'weekly_discounts',
                'exclusive_member_offers',
                'seasonal_campaigns',
            ],
            'account_reminders' => [
                'cart_reminders',
                'payment_billing',
            ],
            'channels' => [
                'email_notifications',
                'push_notifications',
                'sms_notifications',
            ],
        ][$category] ?? [];
    }
}