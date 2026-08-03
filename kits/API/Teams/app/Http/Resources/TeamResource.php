<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class TeamResource extends JsonApiResource
{
    protected bool $usesRequestQueryString = false;

    /**
     * The resource's attributes.
     *
     * @var array<int, string>
     */
    public $attributes = [
        'name',
        'slug',
        'is_personal',
        'created_at',
        'updated_at',
    ];
}
