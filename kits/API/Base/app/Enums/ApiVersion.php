<?php

namespace App\Enums;

use App\Exceptions\UnsupportedApiVersionException;

enum ApiVersion: string
{
    case V1 = 'v1';

    public static function default(): self
    {
        return self::V1;
    }

    public static function requested(): self
    {
        $version = request()->header('X-API-Version');

        if ($version === null || $version === '') {
            return self::default();
        }

        return self::tryFrom($version)
            ?? throw new UnsupportedApiVersionException($version);
    }
}
