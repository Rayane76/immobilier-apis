<?php

namespace App\Services;

use App\Data\User\AssignRoleDTO;
use App\Data\User\CreateUserDTO;
use App\Data\User\UpdateUserDTO;
use App\Data\UserDTO;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage)
            ->through(fn(User $user) => UserDTO::fromModel($user));
    }

    /**
     * Fetch the raw model — used by the controller to authorize before acting.
     */
    public function findModelOrFail(int $id): User
    {
        $user = $this->userRepository->findById($id);

        abort_unless($user, 404, 'User not found.');

        return $user;
    }

    public function create(CreateUserDTO $data): UserDTO
    {
        $user = $this->userRepository->create($data);

        $user->assignRole('visiteur');

        return UserDTO::fromModel($user->load('roles'));
    }

    /**
     * Model already fetched & authorized by the controller — no second lookup.
     */
    public function updateModel(User $user, UpdateUserDTO $data): UserDTO
    {
        return UserDTO::fromModel(
            $this->userRepository->update($user, $data)
        );
    }

    /**
     * Model already fetched & authorized by the controller — no second lookup.
     */
    public function deleteModel(User $user): void
    {
        $this->userRepository->delete($user);
    }

    /**
     * Model already fetched & authorized by the controller — no second lookup.
     */
    public function assignRole(User $user, AssignRoleDTO $data): UserDTO
    {
        return UserDTO::fromModel(
            $this->userRepository->assignRole($user, $data->role)
        );
    }

    /**
     * Model already fetched & authorized by the controller — no second lookup.
     */
    public function revokeRole(User $user, AssignRoleDTO $data): UserDTO
    {
        return UserDTO::fromModel(
            $this->userRepository->revokeRole($user, $data->role)
        );
    }
}
