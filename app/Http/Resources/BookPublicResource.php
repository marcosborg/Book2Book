<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BookPublicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'description' => $this->description,
            'genre' => $this->genre,
            'language' => $this->language,
            'condition' => $this->condition?->value ?? $this->condition,
            'cover_image_url' => $this->cover_image_path
                ? Storage::disk('public')->url($this->cover_image_path)
                : null,
            'is_available' => $this->is_available,
            'owner' => $this->whenLoaded('owner', function () {
                $owner = $this->owner;

                if ($owner && isset($this->distance_km)) {
                    $owner->setAttribute('distance_km', $this->distance_km);
                }

                return new UserPublicResource($owner);
            }),
            'created_at' => $this->created_at,
        ];
    }
}
