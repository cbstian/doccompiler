<?php

use App\Rules\RealMimeType;
use Illuminate\Http\UploadedFile;

it('accepts text files whose content matches the extension', function () {
    $rule = new RealMimeType;
    $file = UploadedFile::fake()->createWithContent('sample.txt', 'plain text');
    $messages = [];

    $rule->validate('document', $file, function (string $message) use (&$messages): void {
        $messages[] = $message;
    });

    expect($messages)->toBeEmpty();
});

it('rejects files whose real mime type does not match the extension', function () {
    $rule = new RealMimeType;
    $file = UploadedFile::fake()->createWithContent('sample.pdf', 'plain text');
    $messages = [];

    $rule->validate('document', $file, function (string $message) use (&$messages): void {
        $messages[] = $message;
    });

    expect($messages)->not->toBeEmpty();
});
