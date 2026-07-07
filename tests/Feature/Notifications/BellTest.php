<?php

use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Livewire\Notifications\Bell;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Notifications\AidConfirmedNotification;
use Database\Factories\AidFactory;
use Livewire\Livewire;

function aidForNotification()
{
    seedAidCatalog();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    return AidFactory::new()->delivered()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 200,
    ]);
}

it('marks a single notification as read and updates the unread count', function () {
    $user = asManager();
    $aid = aidForNotification();

    $user->notify(new AidConfirmedNotification($aid));
    $notification = $user->notifications()->first();

    expect(Livewire::test(Bell::class)->instance()->unreadCount)->toBe(1);

    Livewire::test(Bell::class)->call('markAsRead', $notification->id);

    expect($notification->fresh()->read_at)->not->toBeNull();
    expect(Livewire::test(Bell::class)->instance()->unreadCount)->toBe(0);
});

it('marks every notification as read via markAllAsRead', function () {
    $user = asManager();
    $aid = aidForNotification();

    $user->notify(new AidConfirmedNotification($aid));
    $user->notify(new AidConfirmedNotification($aid));

    expect(Livewire::test(Bell::class)->instance()->unreadCount)->toBe(2);

    Livewire::test(Bell::class)->call('markAllAsRead');

    expect($user->unreadNotifications()->count())->toBe(0);
});

it("only shows the signed-in user their own notifications", function () {
    $user = asManager();
    $otherUser = userWithRole(\App\Enums\RoleName::Manager);
    $aid = aidForNotification();

    $otherUser->notify(new AidConfirmedNotification($aid));

    expect(Livewire::test(Bell::class)->instance()->unreadCount)->toBe(0);
    expect(Livewire::test(Bell::class)->instance()->recent)->toHaveCount(0);
});
