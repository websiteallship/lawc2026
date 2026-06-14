<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Market;
use Illuminate\Auth\Access\HandlesAuthorization;

class MarketPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Market');
    }

    public function view(AuthUser $authUser, Market $market): bool
    {
        return $authUser->can('View:Market');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Market');
    }

    public function update(AuthUser $authUser, Market $market): bool
    {
        return $authUser->can('Update:Market');
    }

    public function delete(AuthUser $authUser, Market $market): bool
    {
        return $authUser->can('Delete:Market');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Market');
    }

    public function restore(AuthUser $authUser, Market $market): bool
    {
        return $authUser->can('Restore:Market');
    }

    public function forceDelete(AuthUser $authUser, Market $market): bool
    {
        return $authUser->can('ForceDelete:Market');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Market');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Market');
    }

    public function replicate(AuthUser $authUser, Market $market): bool
    {
        return $authUser->can('Replicate:Market');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Market');
    }

}