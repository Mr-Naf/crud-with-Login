<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Item $item)
{
    return $user->id === $item->user_id;
}
public function update(User $user, Item $item)
{
    return $user->id === $item->user_id;
}
public function delete(User $user, Item $item)
{
    return $user->id === $item->user_id;
}
public function viewAny(User $user) { return true; }
public function create(User $user) { return true; }

}
