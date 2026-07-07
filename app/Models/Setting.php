<?php

namespace App\Models;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A single key/value application setting (e.g. Taqnyat sender name,
 * SMS/WhatsApp channel toggles). Read/write through {@see Settings}
 * rather than this model directly, so the settings cache stays consistent.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    //
}
