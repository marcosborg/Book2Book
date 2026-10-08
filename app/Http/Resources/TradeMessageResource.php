<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TradeMessageResource extends JsonResource
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
            'message' => $this->message,
            'attachment_name' => $this->attachment_name,
            'attachment_mime' => $this->attachment_mime,
            'attachment_url' => $this->attachment_path
                ? route('trade-messages.attachment', ['trade' => $this->trade_request_id, 'message' => $this->id], false)
                : null,
            'read_at' => $this->read_at,
            'sender' => $this->whenLoaded('sender', function () {
                return new UserPublicResource($this->sender);
            }),
            'created_at' => $this->created_at,
        ];
    }
}
