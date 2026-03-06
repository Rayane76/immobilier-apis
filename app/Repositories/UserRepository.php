<?php

namespace App\Repositories;

use App\Data\Auth\RegisterDTO;
use App\Data\User\CreateUserDTO;
use App\Data\User\UpdateUserDTO;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Spatie\LaravelData\Optional;

class UserRepository implements UserRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        // Eager load roles to prevent N+1 on getRoleNames() in UserDTO::fromModel()
        return User::with('roles')->paginate($perPage);
    }

    public function findById(int $id): ?User
    {
        return User::with('roles')->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::with('roles')->where('email', $email)->first();
    }

    public function create(RegisterDTO|CreateUserDTO $data): User
    {
        return User::create([
            'name'     => $data->name,
            'email'    => $data->email,
            'password' => Hash::make($data->password),
        ]);
    }

    public function update(User $user, UpdateUserDTO $data): User
    {
        $payload = collect($data->toArray())
            ->reject(fn($v) => $v instanceof Optional)
            ->when(
                fn($c) => $c->has('password') && $c->get('password') !== null,
                fn($c) => $c->put('password', Hash::make($c->get('password')))
            )
            ->toArray();

        $user->update($payload);

        return $user->load('roles');
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function assignRole(User $user, string $role): User
    {
        $user->assignRole($role);

        return $user->load('roles');
    }

    public function revokeRole(User $user, string $role): User
    {
        $user->removeRole($role);

        return $user->load('roles');
    }
}
