<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Bet;
use Illuminate\Auth\Access\HandlesAuthorization;

class BetPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Bet');
    }

    public function view(AuthUser $authUser, Bet $bet): bool
    {
        return $authUser->can('View:Bet');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Bet');
    }

    public function update(AuthUser $authUser, Bet $bet): bool
    {
        return $authUser->can('Update:Bet');
    }

    public function delete(AuthUser $authUser, Bet $bet): bool
    {
        return $authUser->can('Delete:Bet');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Bet');
    }

    public function restore(AuthUser $authUser, Bet $bet): bool
    {
        return $authUser->can('Restore:Bet');
    }

    public function forceDelete(AuthUser $authUser, Bet $bet): bool
    {
        return $authUser->can('ForceDelete:Bet');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Bet');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Bet');
    }

    public function replicate(AuthUser $authUser, Bet $bet): bool
    {
        return $authUser->can('Replicate:Bet');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Bet');
    }

}