<?php

use App\Enums\Locale;
use App\Livewire\Admin\Roles\Form as RoleForm;
use App\Livewire\Admin\Roles\Index as RoleIndex;
use App\Livewire\Admin\Users\Form as UserForm;
use App\Livewire\Admin\Users\Index as UserIndex;
use App\Livewire\Aids\Form as AidForm;
use App\Livewire\Aids\Index as AidIndex;
use App\Livewire\Aids\Show as AidShow;
use App\Livewire\Approvals\Inbox as ApprovalsInbox;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Beneficiaries\Form as BeneficiaryForm;
use App\Livewire\Beneficiaries\Index as BeneficiaryIndex;
use App\Livewire\Beneficiaries\Show as BeneficiaryShow;
use App\Livewire\Dashboard;
use App\Livewire\Settings\AidPrograms\Form as AidProgramForm;
use App\Livewire\Settings\AidPrograms\Index as AidProgramIndex;
use App\Livewire\Settings\ApprovalFlows\Form as ApprovalFlowForm;
use App\Livewire\Settings\ApprovalFlows\Index as ApprovalFlowIndex;
use App\Livewire\Settings\Categories\Index as CategoryIndex;
use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', function (Request $request) {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::post('/locale/{locale}', function (Request $request, string $locale) {
        if (! in_array($locale, array_column(Locale::cases(), 'value'), true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);

        if ($user = $request->user()) {
            $user->update(['preferred_locale' => $locale]);
        }

        return back();
    })->name('locale.switch');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::prefix('users')->name('users.')->group(function (): void {
            Route::get('/', UserIndex::class)->name('index')->middleware('permission:users.view');
            Route::get('/create', UserForm::class)->name('create')->middleware('permission:users.create');
            Route::get('/{user}/edit', UserForm::class)->name('edit')->middleware('permission:users.update');
        });

        Route::prefix('roles')->name('roles.')->group(function (): void {
            Route::get('/', RoleIndex::class)->name('index')->middleware('permission:roles.view');
            Route::get('/create', RoleForm::class)->name('create')->middleware('permission:roles.create');
            Route::get('/{role}/edit', RoleForm::class)->name('edit')->middleware('permission:roles.update');
        });

        Route::prefix('beneficiaries')->name('beneficiaries.')->group(function (): void {
            Route::get('/', BeneficiaryIndex::class)->name('index')->middleware('permission:beneficiaries.view');
            Route::get('/create', BeneficiaryForm::class)->name('create')->middleware('permission:beneficiaries.create');
            Route::get('/{beneficiary}/edit', BeneficiaryForm::class)->name('edit')->middleware('permission:beneficiaries.update');
            Route::get('/{beneficiary}/documents/{media}', function (Beneficiary $beneficiary, Media $media) {
                Gate::authorize('view', $beneficiary);

                abort_unless(
                    $media->model_type === Beneficiary::class
                    && (int) $media->model_id === $beneficiary->getKey(),
                    404,
                );

                return response()->download($media->getPath(), $media->file_name);
            })->name('documents.download')->middleware('permission:beneficiaries.view');
            Route::get('/{beneficiary}', BeneficiaryShow::class)->name('show')->middleware('permission:beneficiaries.view');
        });

        Route::prefix('settings')->name('settings.')->group(function (): void {
            Route::get('/categories', CategoryIndex::class)->name('categories.index')->middleware('permission:settings.view');

            Route::prefix('aid-programs')->name('aid-programs.')->group(function (): void {
                Route::get('/', AidProgramIndex::class)->name('index')->middleware('permission:settings.manage');
                Route::get('/create', AidProgramForm::class)->name('create')->middleware('permission:settings.manage');
                Route::get('/{program}/edit', AidProgramForm::class)->name('edit')->middleware('permission:settings.manage');
            });

            Route::prefix('approval-flows')->name('approval-flows.')->group(function (): void {
                Route::get('/', ApprovalFlowIndex::class)->name('index')->middleware('permission:approvals.configure');
                Route::get('/create', ApprovalFlowForm::class)->name('create')->middleware('permission:approvals.configure');
                Route::get('/{flow}/edit', ApprovalFlowForm::class)->name('edit')->middleware('permission:approvals.configure');
            });
        });
    });

    Route::prefix('aids')->name('aids.')->group(function (): void {
        Route::get('/', AidIndex::class)->name('index')->middleware('permission:aids.view');
        Route::get('/create', AidForm::class)->name('create')->middleware('permission:aids.create');
        Route::get('/{aid}/edit', AidForm::class)->name('edit')->middleware('permission:aids.update');
        Route::get('/{aid}', AidShow::class)->name('show')->middleware('permission:aids.view');
    });

    Route::prefix('approvals')->name('approvals.')->group(function (): void {
        Route::get('/inbox', ApprovalsInbox::class)->name('inbox')->middleware('permission:approvals.view');
    });
});
