<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
});

it('fails when RESEND_API_KEY is not configured', function (): void {
    Config::set('services.resend.key', '');

    $this->artisan('app:send-resend-test-mail')
        ->expectsOutput('RESEND_API_KEY no está configurada. Define esta variable en tu .env antes de ejecutar el comando.')
        ->assertFailed();
});

it('fails when services.resend.key is null', function (): void {
    Config::set('services.resend.key', null);

    $this->artisan('app:send-resend-test-mail')
        ->expectsOutput('RESEND_API_KEY no está configurada. Define esta variable en tu .env antes de ejecutar el comando.')
        ->assertFailed();
});
