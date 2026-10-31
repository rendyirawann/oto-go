<?php

namespace App\Policies;

use App\Models\CustomerVehicle;
use App\Models\User;

/** A customer may only see and change their own vehicles. */
class CustomerVehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, CustomerVehicle $vehicle): bool
    {
        return $vehicle->user_id === $user->getKey();
    }

    public function update(User $user, CustomerVehicle $vehicle): bool
    {
        return $vehicle->user_id === $user->getKey();
    }

    public function delete(User $user, CustomerVehicle $vehicle): bool
    {
        return $vehicle->user_id === $user->getKey();
    }
}
