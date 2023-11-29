<?php

namespace App\Policies;

use Spatie\Activitylog\Models\Activity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;


class ActivityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any activity logs.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_activity');
    }

    /**
     * Determine whether the user can view the activity log.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Activity $activity
     * @return bool
     */
    public function view(User $user, Activity $activity): bool
    {
        return $user->can('view_activity');
    }

    /**
     * Determine whether the user can create activity logs.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('create_activity');
    }

    /**
     * Determine whether the user can update the activity log.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Activity $activity
     * @return bool
     */
    public function update(User $user, Activity $activity): bool
    {
        return $user->can('update_activity');
    }

    /**
     * Determine whether the user can delete the activity log.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Activity $activity
     * @return bool
     */
    public function delete(User $user, Activity $activity): bool
    {
        return $user->can('delete_activity');
    }

    /**
     * Determine whether the user can bulk delete activity logs.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_activity');
    }

    /**
     * Determine whether the user can permanently delete activity logs.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function forceDelete(User $user): bool
    {
        return $user->can('force_delete_activity');
    }

    /**
     * Determine whether the user can permanently bulk delete activity logs.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_activity');
    }

    /**
     * Determine whether the user can restore activity logs.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function restore(User $user): bool
    {
        return $user->can('restore_activity');
    }

    /**
     * Determine whether the user can bulk restore activity logs.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_activity');
    }
}
