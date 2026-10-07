<?php

namespace App\Exceptions;

final class AccountInactiveException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Akun Anda dinonaktifkan. Hubungi pemilik kafe.');
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'ACCOUNT_INACTIVE';
    }
}
