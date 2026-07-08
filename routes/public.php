<?php

use App\Livewire\Public\ConfirmReceipt;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public (unauthenticated) Routes
|--------------------------------------------------------------------------
|
| Beneficiary-facing routes reached only via a short, single-purpose link
| — never linked to from anywhere inside the authenticated app. The raw
| token in the path is the sole secret: it is stored only as a sha256
| digest, is cryptographically strong (~140 bits, unguessable), can be
| used once, and its expiry is enforced by the AidConfirmation model.
| Rate limited to blunt brute-forcing.
|
*/
Route::middleware(['web', 'throttle:10,1'])->group(function (): void {
    Route::get('/c/{token}', ConfirmReceipt::class)
        ->name('public.confirm');
});
