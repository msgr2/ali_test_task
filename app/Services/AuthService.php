<?php

namespace App\Services;

use App\Enums\UserRoleEnum;
use Illuminate\Auth\Access\AuthorizationException;

class AuthService
{
    public static function isModelOwner($model, $user = null): bool|AuthorizationException
    {
        if (!$user) {
            $user = auth()->user();
        }
        if (!$user) {
            throw new AuthorizationException('User must be authenticated to access this resource.');
        }

        if ($model->team_id !== $user->current_team_id) {
            throw new AuthorizationException('You are not authorized to access this resource.');
        }

        return true;
    }

    public static function isAdmin($user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            throw new AuthorizationException('User must be authenticated to access this resource.');
        }

        $role = UserRoleEnum::tryFrom($user->role) ?? UserRoleEnum::user();

        return $role->equals(UserRoleEnum::admin());
    }
}
