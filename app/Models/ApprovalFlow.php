<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'slug', 'is_default', 'is_active', 'description'])]
class ApprovalFlow extends Model
{
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Activity log options: track all fillable attributes, only log actual
     * changes, and skip empty log entries.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getFillable())
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return HasMany<ApprovalFlowStage, $this>
     */
    public function stages(): HasMany
    {
        return $this->hasMany(ApprovalFlowStage::class)->orderBy('order');
    }

    /**
     * @return HasMany<AidProgram, $this>
     */
    public function programs(): HasMany
    {
        return $this->hasMany(AidProgram::class);
    }

    /**
     * Aids whose approval_flow_id snapshot points at this flow.
     *
     * @return HasMany<Aid, $this>
     */
    public function aids(): HasMany
    {
        return $this->hasMany(Aid::class);
    }

    /**
     * @param  Builder<ApprovalFlow>  $query
     * @return Builder<ApprovalFlow>
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
