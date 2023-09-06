<?php

namespace App\Policies;

use App\Models\DataFile;
use App\Models\User;

class DataFilePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, DataFile $dataFile): bool
    {
        return $user->id === $dataFile->user_id;
    }

    public function update(User $user, DataFile $dataFile): bool
    {
        return $user->id === $dataFile->user_id;
    }
}
