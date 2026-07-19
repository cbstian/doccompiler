<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

it('allows pulse dashboard payloads to be restored from cache', function (): void {
    Config::set('cache.stores.pulse-serialization-test', [
        'driver' => 'array',
        'serialize' => true,
    ]);

    Cache::store('pulse-serialization-test')->put('pulse-card-payload-test', [
        collect([(object) ['count' => 1, 'latest' => CarbonImmutable::now('UTC')]]),
        1.23,
        now()->toDateTimeString(),
    ]);

    $payload = Cache::store('pulse-serialization-test')->get('pulse-card-payload-test');

    expect($payload[0])
        ->toBeInstanceOf(Collection::class)
        ->first()->toBeInstanceOf(stdClass::class)
        ->and($payload[0]->first()->count)->toBe(1)
        ->and($payload[0]->first()->latest)->toBeInstanceOf(CarbonImmutable::class);
});
