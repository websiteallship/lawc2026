<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Settlement;
use Illuminate\Auth\Access\HandlesAuthorization;

class SettlementPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Settlement');
    }

    public function view(AuthUser $authUser, Settlement $settlement): bool
    {
        return $authUser->can('View:Settlement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Settlement');
    }

    public function update(AuthUser $authUser, Settlement $settlement): bool
    {
        return $authUser->can('Update:Settlement');
    }

    public function delete(AuthUser $authUser, Settlement $settlement): bool
    {
        return $authUser->can('Delete:Settlement');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Settlement');
    }

    public function restore(AuthUser $authUser, Settlement $settlement): bool
    {
        return $authUser->can('Restore:Settlement');
    }

    public function forceDelete(AuthUser $authUser, Settlement $settlement): bool
    {
        return $authUser->can('ForceDelete:Settlement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Settlement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Settlement');
    }

    public function replicate(AuthUser $authUser, Settlement $settlement): bool
    {
        return $authUser->can('Replicate:Settlement');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Settlement');
    }

}