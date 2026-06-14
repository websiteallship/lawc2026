<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FootballMatch;
use Illuminate\Auth\Access\HandlesAuthorization;

class FootballMatchPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FootballMatch');
    }

    public function view(AuthUser $authUser, FootballMatch $footballMatch): bool
    {
        return $authUser->can('View:FootballMatch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FootballMatch');
    }

    public function update(AuthUser $authUser, FootballMatch $footballMatch): bool
    {
        return $authUser->can('Update:FootballMatch');
    }

    public function delete(AuthUser $authUser, FootballMatch $footballMatch): bool
    {
        return $authUser->can('Delete:FootballMatch');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FootballMatch');
    }

    public function restore(AuthUser $authUser, FootballMatch $footballMatch): bool
    {
        return $authUser->can('Restore:FootballMatch');
    }

    public function forceDelete(AuthUser $authUser, FootballMatch $footballMatch): bool
    {
        return $authUser->can('ForceDelete:FootballMatch');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FootballMatch');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FootballMatch');
    }

    public function replicate(AuthUser $authUser, FootballMatch $footballMatch): bool
    {
        return $authUser->can('Replicate:FootballMatch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FootballMatch');
    }

}