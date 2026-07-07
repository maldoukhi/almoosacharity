<?php

namespace App\Livewire\Admin\Roles;

use App\Actions\Roles\CreateRole;
use App\Actions\Roles\UpdateRole;
use App\Enums\RoleName;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Form extends Component
{
    public ?Role $role = null;

    public string $name = '';

    /**
     * @var array<int, string>
     */
    public array $selectedPermissions = [];

    public function mount(?Role $role = null): void
    {
        $this->role = $role;

        if ($this->role?->exists) {
            Gate::authorize('update', $this->role);

            $this->name = $this->role->name;
            $this->selectedPermissions = $this->role->permissions->pluck('name')->all();

            return;
        }

        Gate::authorize('create', Role::class);
    }

    /**
     * Permissions grouped by the first segment of their dot-notation name,
     * e.g. 'users.view' groups under 'users'.
     *
     * @return array<string, \Illuminate\Support\Collection<int, Permission>>
     */
    #[Computed]
    public function permissionGroups(): array
    {
        return Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission): string => explode('.', $permission->name)[0])
            ->all();
    }

    public function save(): void
    {
        $isUpdate = $this->role?->exists ?? false;

        if ($isUpdate) {
            Gate::authorize('update', $this->role);
        } else {
            Gate::authorize('create', Role::class);
        }

        $defaultRoleNames = array_column(RoleName::cases(), 'value');
        $isDefaultRole = $isUpdate && in_array($this->role->name, $defaultRoleNames, true);

        if ($isDefaultRole && $this->name !== $this->role->name) {
            $this->addError('name', __('roles.messages.cannot_rename_default'));

            return;
        }

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($this->role?->id),
            ],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $data = [
            'name' => $validated['name'],
            'permissions' => $validated['selectedPermissions'] ?? [],
        ];

        if ($isUpdate) {
            app(UpdateRole::class)->handle($this->role, $data);
        } else {
            app(CreateRole::class)->handle($data);
        }

        $this->redirectRoute('admin.roles.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.roles.form');
    }
}
