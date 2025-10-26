<?php

use Illuminate\Support\Facades\Route;
use Zaimea\OAuth2Client\Http\Controllers\ConnectController;

Route::group(['middleware' => ['web','auth'], 'prefix' => 'oauth2-client', 'as' => 'oauth2-client.'], function () {
    Route::get('providers', [ConnectController::class,'index'])->name('providers.index');
    Route::get('providers/{provider}', [ConnectController::class,'show'])->name('providers.show');
    Route::get('connect/{provider}', [ConnectController::class,'redirectToProvider'])->name('connect');
    Route::get('connect/{provider}/callback', [ConnectController::class,'callback'])->name('callback');
    Route::post('connect/{provider}/detach', [ConnectController::class,'detach'])->name('detach');
});
