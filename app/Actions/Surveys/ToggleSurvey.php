<?php

namespace App\Actions\Surveys;

use App\Models\Survey;

class ToggleSurvey
{
    public function handle(Survey $survey): Survey
    {
        $survey->update(['is_active' => ! $survey->is_active]);

        return $survey;
    }
}
