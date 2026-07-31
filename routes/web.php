<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $locale = request()->getPreferredLanguage(['en', 'es']);

    return redirect('/'.(in_array($locale, ['en', 'es']) ? $locale : 'en'));
});

Route::get('/{locale}', function (string $locale) {
    if (! in_array($locale, ['en', 'es'])) {
        abort(404);
    }

    App::setLocale($locale);

    return view('welcome');
})->where(['locale' => 'en|es']);
