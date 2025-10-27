<?php

use Illuminate\Support\Facades\Route;
use Zaimea\OAuth2Client\Http\Controllers\ConnectController;

Route::middleware(['web','auth'])->group(function() {
    Route::get('/oauth2-client/providers', [ConnectController::class, 'index'])->name('oauth2-client.providers.index');
    Route::get('/oauth2-client/connect/{provider}', [ConnectController::class, 'redirectToProvider'])->name('oauth2-client.connect');
    Route::get('/oauth2-client/connect/{provider}/callback', [ConnectController::class, 'callback'])->name('oauth2-client.callback');
    Route::post('/oauth2-client/connect/{provider}/detach', [ConnectController::class, 'detach'])->name('oauth2-client.detach');
});
