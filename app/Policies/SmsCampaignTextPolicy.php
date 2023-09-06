<?php

namespace App\Policies;

use App\Models\SmsCampaignText;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SmsCampaignTextPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, SmsCampaignText $smsCampaignText): bool
    {
    }

    public function create(User $user): bool
    {
    }

    public function update(User $user, SmsCampaignText $smsCampaignText): bool
    {
    }

    public function delete(User $user, SmsCampaignText $smsCampaignText): bool
    {
    }

    public function restore(User $user, SmsCampaignText $smsCampaignText): bool
    {
    }

    public function forceDelete(User $user, SmsCampaignText $smsCampaignText): bool
    {
    }
}
