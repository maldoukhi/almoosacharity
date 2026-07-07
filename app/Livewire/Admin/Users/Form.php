<?php

namespace App\Livewire\Admin\Users;

use App\Actions\Users\AuthorizeRoleAssignment;
use App\Actions\Users\CreateUser;
use App\Actions\Users\UpdateUser;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Form extends Component
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $job_title = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = '';

    public string $status = 'active';

    public string $preferred_locale = 'ar';

    public function mount(?User $user = null): void
    {
        $this->user = $user;

        if ($this->user?->exists) {
            Gate::authorize('update', $this->user);

            $this->name = $this->user->name;
            $this->email = $this->user->email;
            $this->phone = (string) $this->user->phone;
            $this->job_title = (string) $this->user->job_title;
            $this->status = $this->user->status->value;
            $this->preferred_locale = $this->user->preferred_locale->value;
            $this->role = $this->user->roles->first()?->name ?? '';

            return;
        }

        Gate::authorize('create', User::class);
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles()
    {
        return Role::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $isUpdate = $this->user?->exists ?? false;

        if ($isUpdate) {
            Gate::authorize('update', $this->user);
        } else {
            Gate::authorize('create', User::class);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'status' => ['required', 'string', 'in:active,suspended'],
            'preferred_locale' => ['required', 'string', 'in:ar,en'],
            'password' => [$isUpdate ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ]);

        $currentRole = $isUpdate ? $this->user->roles->first()?->name : null;

        try {
            app(AuthorizeRoleAssignment::class)->handle($validated['role'], $currentRole);
        } catch (AuthorizationException $exception) {
            $this->addError('role', $exception->getMessage());

            return;
        }

        // Re-verified in UpdateUser::handle() as the source of truth; this
        // check just gives a clean 403 as soon as possible and mirrors the
        // self-suspend protection enforced by UserPolicy::suspend().
        if ($isUpdate && $validated['status'] !== $this->user->status->value) {
            Gate::authorize('suspend', $this->user);
        }

        if ($isUpdate) {
            app(UpdateUser::class)->handle($this->user, $validated);
        } else {
            app(CreateUser::class)->handle($validated);
        }

        $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.users.form');
    }
}
