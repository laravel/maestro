<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class TeamInvitationResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        return [
            'code' => $this->resource->code,
            'email' => $this->resource->email,
            'role' => $this->resource->role?->value,
            'role_label' => $this->resource->role?->label(),
            'expires_at' => $this->resource->expires_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
