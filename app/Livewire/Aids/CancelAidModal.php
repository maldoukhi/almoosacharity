<?php

namespace App\Livewire\Aids;

use App\Actions\Aids\CancelAid;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use Illuminate\Support\Facades\Gate;
use LivewireUI\Modal\ModalComponent;

/**
 * Confirmation modal for cancelling an aid that has not yet been decided
 * on. Replaces the browser `confirm()` with a summary so the actor sees
 * exactly which aid they are cancelling. Cancelling sends no notification.
 */
class CancelAidModal extends ModalComponent
{
    public Aid $aid;

    public function mount(Aid $aid): void
    {
        $this->aid = $aid;

        Gate::authorize('cancel', $this->aid);
    }

    public function confirm(): void
    {
        Gate::authorize('cancel', $this->aid);

        try {
            app(CancelAid::class)->handle($this->aid);
        } catch (InvalidAidTransitionException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());
            $this->closeModal();

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('aids.messages.cancelled'));
        $this->dispatch('aid-submitted');

        $this->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return 'md';
    }

    public function render()
    {
        return view('livewire.aids.cancel-aid-modal');
    }
}
