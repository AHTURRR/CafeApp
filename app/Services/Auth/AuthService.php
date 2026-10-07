<?php

namespace App\Services\Auth;

use App\DataTransferObjects\AuthResult;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Exceptions\AccountInactiveException;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Aturan bisnis autentikasi. Tidak bergantung pada Request/Response (HTTP-agnostic).
 */
final class AuthService
{
    private static ?string $dummyHash = null;

    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    /**
     * Pendaftaran mandiri: SELALU role customer. Role dari client tidak pernah dipakai.
     *
     * @param  array{name: string, email: string, phone?: ?string, password: string}  $data
     */
    public function register(array $data, string $deviceName): AuthResult
    {
        $user = $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => Role::Customer,
            'status' => UserStatus::Active,
        ]);

        return $this->issueToken($user, $deviceName);
    }

    public function login(string $email, string $password, string $deviceName): AuthResult
    {
        $user = $this->users->findByEmail($email);

        // Hash::check tetap dijalankan walau user tidak ada, agar waktu respons tidak membocorkan keberadaan email.
        $passwordOk = Hash::check($password, $user?->password ?? self::dummyHash());

        if ($user === null || ! $passwordOk) {
            throw new InvalidCredentialsException();
        }

        // Status akun baru diperiksa setelah password benar, sehingga status tidak bisa ditebak tanpa kredensial.
        if (! $user->isActive()) {
            throw new AccountInactiveException();
        }

        $this->users->touchLastLogin($user);

        return $this->issueToken($user, $deviceName);
    }

    /** Mencabut token yang sedang dipakai (satu perangkat). */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /** Mencabut semua token (dipakai saat ganti password, nonaktifkan akun, atau ubah role). */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    private function issueToken(User $user, string $deviceName): AuthResult
    {
        $days = (int) config('cafe.token_ttl_days.'.($user->role->isStaff() ? 'staff' : 'customer'));
        $expiresAt = now()->addDays($days);

        $token = $user->createToken($deviceName, ['*'], $expiresAt);

        return new AuthResult($user, $token->plainTextToken, $expiresAt);
    }

    private static function dummyHash(): string
    {
        return self::$dummyHash ??= Hash::make(Str::random(32));
    }
}
