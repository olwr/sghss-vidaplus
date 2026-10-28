<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    foreach (glob(base_path('routes/api/v1/*.php')) as $arquivo) {
        require $arquivo;
    }
});
