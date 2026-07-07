<?php

namespace App\Livewire\Admin\Roles;

use App\Enums\RoleName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', Role::class);
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles()
    {
        return Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Delete a role, unless it is one of the three default system roles or
     * still has users assigned to it.
     */
    public function delete(int $roleId): void
    {
        $role = Role::findOrFail($roleId);

        Gate::authorize('delete', $role);

        $defaultRoleNames = array_column(RoleName::cases(), 'value');

        if (in_array($role->name, $defaultRoleNames, true)) {
            $this->dispatch('toast', type: 'error', message: __('roles.messages.cannot_delete_default'));

            return;
        }

        if ($role->users()->exists()) {
            $this->dispatch('toast', type: 'error', message: __('roles.messages.cannot_delete_in_use'));

            return;
        }

        $role->delete();

        $this->dispatch('toast', type: 'success', message: __('roles.messages.deleted'));
    }

    public function render()
    {
        return view('livewire.admin.roles.index');
    }
}
