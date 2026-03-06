<?php

namespace App\Repositories\Contracts;

use App\Data\Auth\RegisterDTO;
use App\Data\User\CreateUserDTO;
use App\Data\User\UpdateUserDTO;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function create(RegisterDTO|CreateUserDTO $data): User;

    public function update(User $user, UpdateUserDTO $data): User;

    public function delete(User $user): void;

    public function assignRole(User $user, string $role): User;

    public function revokeRole(User $user, string $role): User;
}
