<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    protected bool $usesRequestQueryString = false;

    /**
     * The resource's attributes.
     *
     * @var array<int, string>
     */
    public $attributes = [
        'name',
        'email',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];
}
