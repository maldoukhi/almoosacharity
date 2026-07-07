<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\User;

class SurveyPolicy
{
    /**
     * System admins bypass this policy entirely via Gate::before, so every
     * method below only needs to check the relevant permission.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('surveys.view');
    }

    public function view(User $user, Survey $survey): bool
    {
        return $user->can('surveys.view');
    }

    /**
     * Single ability covering both creating and updating a survey (and
     * its question list).
     */
    public function create(User $user): bool
    {
        return $user->can('surveys.manage');
    }

    public function update(User $user, Survey $survey): bool
    {
        return $user->can('surveys.manage');
    }

    public function delete(User $user, Survey $survey): bool
    {
        return $user->can('surveys.manage');
    }

    public function viewResults(User $user, Survey $survey): bool
    {
        return $user->can('surveys.results.view');
    }
}
