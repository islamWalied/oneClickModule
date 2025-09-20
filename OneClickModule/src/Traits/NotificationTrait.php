<?php

namespace App\Traits;

use App\Services\Implementation\FirebaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Models\User;
use Modules\Utilities\Models\Notification;
use Modules\Utilities\Models\UserNotification;

trait NotificationTrait
{
    public function saveAndSendNotification(
        array  $attributes,
        array  $userIds,
        string $title,
        string $body,
        array  $data = []): array
    {
        return DB::transaction(function () use ($attributes, $userIds, $title, $body, $data) {
            $result = $this->saveNotification($attributes, $userIds);

            if (!empty($result['tokens'])) {
                $firebaseService = app(FirebaseService::class);
                $not = $firebaseService->sendMulticastNotification($result['tokens'], $title, $body, $data);
                Log::info($not);
            }

            return [
                'notification' => $result['notification'],
                'tokens' => $result['tokens'],
            ];
        });
    }

    public function saveAndSendSingleNotification(
        array  $attributes,
        int    $userId,
        string $title,
        string $body,
        array  $data = []
    ): array
    {
        return DB::transaction(function () use ($attributes, $userId, $title, $body, $data) {
            $result = $this->saveNotification($attributes, $userId);
            $data = $this->prepareFirebaseData($data);
            if ($result['tokens']) {
                $firebaseService = app(FirebaseService::class);
                $not = $firebaseService->sendNotification($result['tokens'], $title, $body, $data);
                Log::info($not);
            }

            return [
                'notification' => $result['notification'],
                'token' => $result['tokens'],
            ];
        });
    }

    public function saveNotification(array $attributes, $userIds): array
    {
        $userIds = is_array($userIds) ? $userIds : [$userIds];

        $notificationData = [
            'title' => $attributes['title'],
            'content' => $attributes['content'],
            'date' =>  now()->toDateString(),
            'additional_data' => $attributes['additional_data'] ?? [],
        ];

        $notification = Notification::create($notificationData);

        $notificationUsers = array_map(function ($userId) use ($notification) {
            return [
                'notification_id' => $notification->id,
                'user_id' => $userId,
                'is_read' => false,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $userIds);

        UserNotification::insert($notificationUsers);

        $users = User::whereIn('id', $userIds)->get();
        $tokens = $users->pluck('fcm_token')->filter()->toArray();

        return [
            'notification' => $notification,
            'tokens' => count($userIds) === 1 ? ($tokens[0] ?? null) : $tokens,
        ];
    }

    protected function prepareFirebaseData(?array $data): ?array
    {
        if (empty($data)) {
            return null;
        }
        return array_map('strval', $data);
    }
}
