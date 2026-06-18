<?php
namespace App\Domain\Modal\Contracts;

use App\Models\User;

interface ModalCheckerInterface
{
    public function shouldShow(User $user): bool;
    public function getData(User $user): array;
    public function markAsSeen(User $user): void;
}
