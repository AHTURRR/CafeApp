<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\DataTransferObjects\AuthResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Controller tipis: validasi dilakukan FormRequest, aturan bisnis di AuthService. */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register(
            $request->safe()->only(['name', 'email', 'phone', 'password']),
            (string) ($request->validated('device_name') ?? 'mobile'),
        );

        return $this->tokenResponse($result, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('device_name'),
        );

        return $this->tokenResponse($result, 200);
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout($request->user());

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    private function tokenResponse(AuthResult $result, int $status): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => (new UserResource($result->user))->resolve(),
                'token' => $result->token,
                'token_type' => 'Bearer',
                'expires_at' => $result->expiresAt->toIso8601String(),
            ],
        ], $status);
    }
}
