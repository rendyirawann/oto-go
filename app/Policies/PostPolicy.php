<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/**
 * Blog management is a back-office task: anyone holding the data-master
 * permission (admin) can manage posts. Superadmin passes via Gate::before.
 */
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_data_master');
    }

    public function create(User $user): bool
    {
        return $user->can('view_data_master');
    }

    public function update(User $user, Post $post): bool
    {
        return $user->can('view_data_master');
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->can('view_data_master');
    }
}
