<?php

namespace App\Services\Account;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

final class ProfileService
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    /**
     * Hanya nama dan telepon yang dapat diubah sendiri. Email, role, dan status tidak termasuk.
     *
     * @param  array{name?: string, phone?: ?string}  $data
     */
    public function update(User $user, array $data): User
    {
        return $this->users->update($user, array_intersect_key($data, array_flip(['name', 'phone'])));
    }
}
