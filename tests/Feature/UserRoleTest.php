<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('only treats flagged users as admins', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    expect($admin->hasRole('admin'))->toBeTrue()
        ->and($user->hasRole('admin'))->toBeFalse()
        ->and($admin->hasRole('editor'))->toBeFalse();
});

it('only allows admins to access the Filament admin panel', function (): void {
    $panel = Filament::getPanel('admin');
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    expect($admin->canAccessPanel($panel))->toBeTrue()
        ->and($user->canAccessPanel($panel))->toBeFalse();
});
