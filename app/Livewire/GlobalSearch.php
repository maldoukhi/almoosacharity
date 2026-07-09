<?php

namespace App\Livewire;

use App\Models\Aid;
use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Topbar global search palette (Ctrl+K / Cmd+K). A single debounced query
 * fans out into two permission-gated groups — beneficiaries and aids —
 * each capped at a handful of rows so the palette stays fast and never
 * becomes a full search results page.
 *
 * Gates mirror the sidebar links exactly (resources/views/components/
 * layouts/sidebar.blade.php): beneficiaries.view and aids.view. A user
 * missing a gate simply never sees that group, not an empty/disabled one.
 */
class GlobalSearch extends Component
{
    public string $query = '';

    /**
     * Minimum characters before a query is actually run against the
     * database, so every keystroke on a 1-character term doesn't hit
     * the beneficiaries/aids tables with a leading-wildcard LIKE scan.
     */
    private const MIN_CHARS = 2;

    private function term(): string
    {
        return trim($this->query);
    }

    private function hasMinChars(): bool
    {
        return mb_strlen($this->term()) >= self::MIN_CHARS;
    }

    /**
     * @return Collection<int, Beneficiary>
     */
    #[Computed]
    public function beneficiaries(): Collection
    {
        if (! $this->hasMinChars() || ! Auth::user()?->can('beneficiaries.view')) {
            return new Collection;
        }

        $term = $this->term();

        return Beneficiary::query()
            ->where(function ($q) use ($term): void {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('second_name', 'like', "%{$term}%")
                    ->orWhere('third_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('national_id', 'like', "%{$term}%")
                    ->orWhere('mobile', 'like', "%{$term}%");
            })
            ->orderBy('last_name')
            ->limit(6)
            ->get();
    }

    /**
     * @return Collection<int, Aid>
     */
    #[Computed]
    public function aids(): Collection
    {
        if (! $this->hasMinChars() || ! Auth::user()?->can('aids.view')) {
            return new Collection;
        }

        $term = $this->term();

        return Aid::query()
            ->with('program')
            ->where(function ($q) use ($term): void {
                $q->where('reference', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%");
            })
            ->latest()
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function hasResults(): bool
    {
        return $this->beneficiaries->isNotEmpty() || $this->aids->isNotEmpty();
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}
