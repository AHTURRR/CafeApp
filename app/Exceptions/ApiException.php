<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Basis untuk semua error domain yang dipetakan ke format error API
 * (API_SPECIFICATION bagian 6): { message, code, ...extra }.
 */
abstract class ApiException extends RuntimeException
{
    abstract public function status(): int;

    abstract public function errorCode(): string;

    /** @return array<string, mixed> field tambahan pada body error */
    public function extra(): array
    {
        return [];
    }
}
