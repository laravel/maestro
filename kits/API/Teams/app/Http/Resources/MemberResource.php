<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class MemberResource extends JsonApiResource
{
    /**
     * Get the resource's type.
     */
    public function toType(Request $request): string
    {
        return 'members';
    }

    /**
     * Get the resource's attributes.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        $role = $this->resource->pivot->role ?? null;

        return [
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'role' => $role?->value,
            'role_label' => $role?->label(),
        ];
    }
}
