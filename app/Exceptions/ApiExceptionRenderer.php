<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Satu-satunya tempat pemetaan exception ke respons JSON API.
 * Bentuk: { "message": "...", "code": "KODE", "errors": {...}? }.
 * Detail internal (stack trace, query) tidak pernah dikirim ke client.
 */
final class ApiExceptionRenderer
{
    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        if (! ($request->is('api/*') || $request->expectsJson())) {
            return null;
        }

        return match (true) {
            $e instanceof ApiException => self::json($e->status(), $e->errorCode(), $e->getMessage(), $e->extra()),
            $e instanceof ValidationException => self::json(422, 'VALIDATION_ERROR', 'Data yang dikirim tidak valid.', ['errors' => $e->errors()]),
            $e instanceof AuthenticationException => self::json(401, 'UNAUTHENTICATED', 'Sesi Anda telah berakhir. Silakan login kembali.'),
            $e instanceof AuthorizationException,
            $e instanceof AccessDeniedHttpException => self::json(403, 'FORBIDDEN', 'Anda tidak memiliki akses ke halaman ini.'),
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => self::json(404, 'NOT_FOUND', 'Data tidak ditemukan.'),
            $e instanceof MethodNotAllowedHttpException => self::json(405, 'METHOD_NOT_ALLOWED', 'Metode request tidak diizinkan.'),
            $e instanceof TooManyRequestsHttpException => self::json(429, 'TOO_MANY_REQUESTS', 'Terlalu banyak permintaan. Coba lagi beberapa saat lagi.', [], $e->getHeaders()),
            $e instanceof HttpExceptionInterface && $e->getStatusCode() < 500 => self::json($e->getStatusCode(), 'HTTP_ERROR', 'Permintaan tidak dapat diproses.', [], $e->getHeaders()),
            default => self::json(500, 'SERVER_ERROR', 'Terjadi kesalahan pada server. Silakan coba lagi.'),
        };
    }

    /**
     * @param  array<string, mixed>  $extra
     * @param  array<string, string>  $headers
     */
    private static function json(int $status, string $code, string $message, array $extra = [], array $headers = []): JsonResponse
    {
        return new JsonResponse(['message' => $message, 'code' => $code] + $extra, $status, $headers);
    }
}
