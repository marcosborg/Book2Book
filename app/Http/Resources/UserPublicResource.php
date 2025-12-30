<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserPublicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rating = $this->reviews_received_avg_rating;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'city' => $this->city,
            'photo_url' => $this->profile_photo_path
                ? Storage::disk('public')->url($this->profile_photo_path)
                : null,
            'distance_km' => isset($this->distance_km)
                ? round((float) $this->distance_km, 2)
                : null,
            'rating_avg' => $rating !== null ? round((float) $rating, 2) : null,
        ];
    }
}
