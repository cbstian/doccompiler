<?php

use App\Filament\Resources\ApiClients\Pages\CreateApiClient;
use App\Filament\Resources\ApiClients\Pages\EditApiClient;
use App\Filament\Resources\ApiClients\Pages\ListApiClients;
use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    actingAs(User::factory()->admin()->create());
});

it('renders the list page', function () {
    ApiClient::factory()->count(3)->create();

    livewire(ListApiClients::class)
        ->assertOk()
        ->assertCanSeeTableRecords(ApiClient::all());
});

it('renders the create page', function () {
    livewire(CreateApiClient::class)
        ->assertOk();
});

it('creates an api client with auto-generated uuid and hashed token', function () {
    livewire(CreateApiClient::class)
        ->fillForm([
            'name' => 'cliente-test',
            'is_active' => true,
            'rate_limit_per_minute' => 120,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $client = ApiClient::query()->where('name', 'cliente-test')->firstOrFail();

    expect($client->uuid)->not->toBeEmpty()
        ->and($client->uuid)->toHaveLength(36)
        ->and($client->token)->toHaveLength(64)
        ->and($client->token)->toMatch('/^[a-f0-9]{64}$/')
        ->and($client->is_active)->toBeTrue()
        ->and($client->rate_limit_per_minute)->toBe(120);

    $plainTextToken = session()->get('api_client_plain_token.'.$client->id);

    expect($plainTextToken)->toBeString()
        ->and($plainTextToken)->toHaveLength(40)
        ->and(hash('sha256', $plainTextToken))->toBe($client->token);
});

it('validates required fields on create', function () {
    livewire(CreateApiClient::class)
        ->fillForm([
            'name' => null,
            'rate_limit_per_minute' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'rate_limit_per_minute' => 'required',
        ])
        ->assertNotNotified();
});

it('renders the edit page', function () {
    $client = ApiClient::factory()->create(['name' => 'original']);

    livewire(EditApiClient::class, ['record' => $client->id])
        ->assertOk()
        ->assertSchemaStateSet([
            'name' => 'original',
        ]);
});

it('updates an api client', function () {
    $client = ApiClient::factory()->create(['name' => 'original']);

    livewire(EditApiClient::class, ['record' => $client->id])
        ->fillForm([
            'name' => 'actualizado',
            'is_active' => false,
            'rate_limit_per_minute' => 30,
        ])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    $client->refresh();

    expect($client->name)->toBe('actualizado')
        ->and($client->is_active)->toBeFalse()
        ->and($client->rate_limit_per_minute)->toBe(30);
});

it('regenerates the token via header action', function () {
    $client = ApiClient::factory()->create(['name' => 'con-token']);
    $originalToken = $client->token;

    livewire(EditApiClient::class, ['record' => $client->id])
        ->callAction('regenerateToken')
        ->assertActionMounted('showRegeneratedToken');

    $client->refresh();

    expect($client->token)->not->toBe($originalToken)
        ->and($client->token)->toHaveLength(64)
        ->and($client->token)->toMatch('/^[a-f0-9]{64}$/');
});

it('filters active and inactive clients on the list page', function () {
    $active = ApiClient::factory()->create(['name' => 'activo', 'is_active' => true]);
    $inactive = ApiClient::factory()->create(['name' => 'inactivo', 'is_active' => false]);

    livewire(ListApiClients::class)
        ->assertCanSeeTableRecords([$active, $inactive])
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$inactive]);
});
