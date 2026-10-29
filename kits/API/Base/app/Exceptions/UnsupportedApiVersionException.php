<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class UnsupportedApiVersionException extends NotAcceptableHttpException
{
    public function __construct(string $version)
    {
        parent::__construct("Unsupported API version [{$version}].");
    }
}
