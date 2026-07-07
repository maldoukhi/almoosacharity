<?php

namespace App\Actions\Surveys;

use App\Enums\SurveyScope;
use App\Models\Aid;
use App\Models\Survey;
use Illuminate\Database\Eloquent\Builder;

/**
 * Picks which survey (if any) should be offered to a beneficiary after
 * their delivery confirmation for a given aid: a program-scoped survey
 * for the aid's own program takes priority over a general one, both
 * subject to being active and within their optional publication window.
 */
class ResolveSurveyForAid
{
    public function handle(Aid $aid): ?Survey
    {
        return $this->activeWithinWindow(SurveyScope::Program)
            ->where('aid_program_id', $aid->aid_program_id)
            ->first()
            ?? $this->activeWithinWindow(SurveyScope::General)->first();
    }

    /**
     * @return Builder<Survey>
     */
    private function activeWithinWindow(SurveyScope $scope): Builder
    {
        $now = now();

        return Survey::query()
            ->active()
            ->where('scope', $scope->value)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
