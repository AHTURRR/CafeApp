<?php

namespace App\Exceptions;

final class ResourceInUseException extends ApiException
{
    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'RESOURCE_IN_USE';
    }
}
