<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows only active admins to access the backoffice panel', function () {
    $panel = Filament::getPanel('admin');
    $regularUser = User::factory()->create();
    $adminUser = User::factory()->create(['is_admin' => true]);
    $blockedAdmin = User::factory()->create([
        'is_admin' => true,
        'is_blocked' => true,
    ]);

    expect($regularUser->canAccessPanel($panel))->toBeFalse()
        ->and($adminUser->canAccessPanel($panel))->toBeTrue()
        ->and($blockedAdmin->canAccessPanel($panel))->toBeFalse();
});
