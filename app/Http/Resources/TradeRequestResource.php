<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TradeRequestResource extends JsonResource
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
            'status' => $this->status?->value ?? $this->status,
            'message' => $this->message,
            'book' => $this->whenLoaded('book', function () {
                return new BookResource($this->book);
            }),
            'requester' => $this->whenLoaded('requester', function () {
                return new UserPublicResource($this->requester);
            }),
            'owner' => $this->whenLoaded('owner', function () {
                return new UserPublicResource($this->owner);
            }),
            'accepted_at' => $this->accepted_at,
            'declined_at' => $this->declined_at,
            'cancelled_at' => $this->cancelled_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
