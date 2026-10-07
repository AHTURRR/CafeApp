<?php

namespace App\Exceptions;

final class InvalidCredentialsException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Email atau password salah.');
    }

    public function status(): int
    {
        return 401;
    }

    public function errorCode(): string
    {
        return 'INVALID_CREDENTIALS';
    }
}
