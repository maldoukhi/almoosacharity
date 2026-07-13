<?php

use App\Enums\Locale;
use App\Livewire\Admin\Roles\Form as RoleForm;
use App\Livewire\Admin\Roles\Index as RoleIndex;
use App\Livewire\Admin\SystemHealth;
use App\Livewire\Admin\Users\Form as UserForm;
use App\Livewire\Admin\Users\Index as UserIndex;
use App\Livewire\Aids\Form as AidForm;
use App\Livewire\Aids\Index as AidIndex;
use App\Livewire\Aids\RecurringPlans\Index as RecurringPlanIndex;
use App\Livewire\Aids\Show as AidShow;
use App\Livewire\Approvals\Inbox as ApprovalsInbox;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Beneficiaries\Form as BeneficiaryForm;
use App\Livewire\Beneficiaries\Import as BeneficiaryImport;
use App\Livewire\Beneficiaries\Index as BeneficiaryIndex;
use App\Livewire\Beneficiaries\ReviewInbox as BeneficiaryReviewInbox;
use App\Livewire\Beneficiaries\Show as BeneficiaryShow;
use App\Livewire\Dashboard;
use App\Livewire\Messaging\Broadcast as BroadcastScreen;
use App\Livewire\Notifications\Index;
use App\Livewire\Reports\AidsReport;
use App\Livewire\Reports\BeneficiariesReport;
use App\Livewire\Reports\FinancialReport;
use App\Livewire\Reports\MessagesReport;
use App\Livewire\Reports\SurveysReport;
use App\Livewire\Settings\AidPrograms\Form as AidProgramForm;
use App\Livewire\Settings\AidPrograms\Index as AidProgramIndex;
use App\Livewire\Settings\ApprovalFlows\Form as ApprovalFlowForm;
use App\Livewire\Settings\ApprovalFlows\Index as ApprovalFlowIndex;
use App\Livewire\Settings\BeneficiaryFlows\Form as BeneficiaryFlowForm;
use App\Livewire\Settings\BeneficiaryFlows\Index as BeneficiaryFlowIndex;
use App\Livewire\Settings\Categories\Index as CategoryIndex;
use App\Livewire\Settings\FiscalLock as FiscalLockSettings;
use App\Livewire\Settings\Notifications\Manage;
use App\Livewire\Surveys\Builder;
use App\Livewire\Surveys\Results;
use App\Models\Aid;
use App\Models\Beneficiary;
use App\Models\Disbursement;
use App\Support\AidReceiptPdf;
use App\Support\UserGuide;
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

    Route::get('/notifications', Index::class)->name('notifications.index');

    // The illustrated user guide (docs/user-guide.html), served to any
    // signed-in employee — a self-contained RTL page, so it opens in its
    // own tab rather than inside the SPA shell.
    Route::get('/help/user-guide', function () {
        return response()->file(base_path('docs/user-guide.html'));
    })->name('help.user-guide');

    // One guide section on its own page — loaded inside the contextual
    // "شرح" modal's iframe so each screen shows only its explanation.
    Route::get('/help/user-guide/{section}', function (string $section) {
        $page = UserGuide::sectionPage($section);

        abort_if($page === null, 404);

        return response($page)->header('Content-Type', 'text/html; charset=UTF-8');
    })->name('help.user-guide.section')->where('section', '[a-z-]+');

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
            Route::get('/import', BeneficiaryImport::class)->name('import')->middleware('permission:beneficiaries.import');
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

        Route::prefix('messaging')->name('messaging.')->group(function (): void {
            // Needs beneficiaries.view too: the screen lists beneficiary
            // names/mobiles and searches by national id.
            Route::get('/broadcast', BroadcastScreen::class)->name('broadcast')->middleware('permission:messages.broadcast', 'permission:beneficiaries.view');
        });

        Route::prefix('surveys')->name('surveys.')->group(function (): void {
            Route::get('/', App\Livewire\Surveys\Index::class)->name('index')->middleware('permission:surveys.view');
            Route::get('/create', Builder::class)->name('create')->middleware('permission:surveys.manage');
            Route::get('/{survey}/edit', Builder::class)->name('edit')->middleware('permission:surveys.manage');
            Route::get('/{survey}/results', Results::class)->name('results')->middleware('permission:surveys.results.view');
        });

        Route::prefix('settings')->name('settings.')->group(function (): void {
            Route::get('/categories', CategoryIndex::class)->name('categories.index')->middleware('permission:settings.view');
            Route::get('/notifications', Manage::class)->name('notifications.index')->middleware('permission:notifications.settings.manage');
            Route::get('/system-health', SystemHealth::class)->name('system-health')->middleware('permission:settings.view');
            Route::get('/fiscal-lock', FiscalLockSettings::class)->name('fiscal-lock.index')->middleware('permission:notifications.settings.manage');

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

            Route::prefix('beneficiary-flows')->name('beneficiary-flows.')->group(function (): void {
                Route::get('/', BeneficiaryFlowIndex::class)->name('index')->middleware('permission:beneficiaries.flows.configure');
                Route::get('/create', BeneficiaryFlowForm::class)->name('create')->middleware('permission:beneficiaries.flows.configure');
                Route::get('/{flow}/edit', BeneficiaryFlowForm::class)->name('edit')->middleware('permission:beneficiaries.flows.configure');
            });
        });
    });

    Route::prefix('aids')->name('aids.')->group(function (): void {
        Route::get('/', AidIndex::class)->name('index')->middleware('permission:aids.view');
        Route::get('/{aid}/receipt', function (Aid $aid) {
            Gate::authorize('view', $aid);

            return AidReceiptPdf::response($aid->load('beneficiary', 'program', 'items'));
        })->name('receipt')->middleware('permission:aids.view');
        Route::get('/recurring-plans', RecurringPlanIndex::class)->name('recurring-plans.index')->middleware('permission:aids.recurring.manage');
        Route::get('/create', AidForm::class)->name('create')->middleware('permission:aids.create');
        // Batch creation was unified into the single aid create screen; keep
        // the named route so any old link still resolves, now redirecting there.
        Route::redirect('/batch', '/aids/create')->name('batch')->middleware('permission:aids.create');
        Route::get('/{aid}/edit', AidForm::class)->name('edit')->middleware('permission:aids.update');
        Route::get('/{aid}', AidShow::class)->name('show')->middleware('permission:aids.view');
    });

    Route::prefix('approvals')->name('approvals.')->group(function (): void {
        Route::get('/inbox', ApprovalsInbox::class)->name('inbox')->middleware('permission:approvals.view');
    });

    Route::prefix('beneficiary-review')->name('beneficiary-review.')->group(function (): void {
        Route::get('/inbox', BeneficiaryReviewInbox::class)->name('inbox')->middleware('permission:beneficiaries.review');
    });

    Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function (): void {
        Route::get('/', App\Livewire\Reports\Index::class)->name('index');
        Route::get('/aids', AidsReport::class)->name('aids');
        Route::get('/beneficiaries', BeneficiariesReport::class)->name('beneficiaries');
        Route::get('/financial', FinancialReport::class)->name('financial');
        Route::get('/surveys', SurveysReport::class)->name('surveys');
        Route::get('/messages', MessagesReport::class)->name('messages');
    });

    Route::get('/disbursements/{disbursement}/proof', function (Disbursement $disbursement) {
        Gate::authorize('view', $disbursement);

        $media = $disbursement->getFirstMedia('delivery_proof');

        abort_unless($media !== null, 404);

        return response()->download($media->getPath(), $media->file_name);
    })->name('disbursements.proof.download')->middleware('permission:disbursements.view');
});
