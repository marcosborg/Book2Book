<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\DeviceTokenRequest;
use App\Http\Requests\Api\V1\ProfilePhotoRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\UserResource;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MeController extends ApiController
{
    /**
     * Get authenticated user's profile.
     */
    public function show(Request $request): UserResource
    {
        return (new UserResource($request->user()))
            ->additional(['meta' => (object) []]);
    }

    /**
     * Update authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        $user->fill($request->validated());
        $user->save();

        return (new UserResource($user->refresh()))
            ->additional(['meta' => (object) []]);
    }

    /**
     * Upload profile photo.
     */
    public function photo(ProfilePhotoRequest $request): UserResource
    {
        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $request->file('photo')->store('profiles', 'public');
        $user->profile_photo_path = $path;
        $user->save();

        return (new UserResource($user->refresh()))
            ->additional(['meta' => (object) []]);
    }

    /**
     * Register or update a device token.
     */
    public function storeDeviceToken(DeviceTokenRequest $request): JsonResponse
    {
        $user = $request->user();

        DeviceToken::updateOrCreate(
            ['token' => $request->input('token')],
            [
                'user_id' => $user->id,
                'platform' => $request->input('platform'),
                'last_seen_at' => now(),
            ]
        );

        return response()->json(['data' => ['message' => 'Device token saved.'], 'meta' => (object) []]);
    }

    /**
     * List notifications.
     */
    public function notifications(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate($this->perPage($request));

        return NotificationResource::collection($notifications)
            ->additional(['meta' => $this->paginationMeta($notifications)]);
    }

    /**
     * Mark a notification as read.
     */
    public function readNotification(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json(['data' => ['message' => 'Notification marked as read.'], 'meta' => (object) []]);
    }
}
