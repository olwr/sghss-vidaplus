<?php

use App\Shared\Http\Controllers\SaudeController;
use Illuminate\Support\Facades\Route;

// Pública de propósito: usada pela sonda de disponibilidade e pelo healthcheck do proxy.
Route::get('saude', SaudeController::class)->middleware('throttle:60,1');
