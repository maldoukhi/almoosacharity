<?php

namespace App\Livewire\Admin\Users;

use App\Actions\Users\DeleteUser;
use App\Actions\Users\ToggleUserStatus;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $roleFilter = '';

    #[Url]
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->roleFilter !== '', function ($query): void {
                $query->whereHas('roles', function ($query): void {
                    $query->where('name', $this->roleFilter);
                });
            })
            ->when($this->statusFilter !== '', function ($query): void {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('name')
            ->paginate(15);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Role>
     */
    #[Computed]
    public function roles()
    {
        return Role::query()->orderBy('name')->get();
    }

    public function toggleStatus(int $userId): void
    {
        $user = User::findOrFail($userId);

        Gate::authorize('suspend', $user);

        app(ToggleUserStatus::class)->handle($user);

        $this->dispatch('toast', type: 'success', message: __('users.messages.status_updated'));
    }

    public function delete(int $userId): void
    {
        $user = User::findOrFail($userId);

        Gate::authorize('delete', $user);

        app(DeleteUser::class)->handle($user);

        $this->dispatch('toast', type: 'success', message: __('users.messages.deleted'));
    }

    public function render()
    {
        return view('livewire.admin.users.index');
    }
}
