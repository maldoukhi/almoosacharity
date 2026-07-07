<?php

use App\Livewire\Public\ConfirmReceipt;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public (unauthenticated) Routes
|--------------------------------------------------------------------------
|
| Beneficiary-facing routes reached only via a signed, single-purpose
| link — never linked to from anywhere inside the authenticated app.
| Rate limited, and 'signed' additionally guarantees the query string
| (confirmation id + raw token) hasn't been tampered with and that the
| link hasn't outlived the expiry it was signed with.
|
*/
Route::middleware(['web', 'throttle:10,1'])->group(function (): void {
    Route::get('/confirm/{confirmation}', ConfirmReceipt::class)
        ->middleware('signed')
        ->name('public.confirm');
});
