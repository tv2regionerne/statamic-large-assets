<?php

use Illuminate\Support\Facades\Route;

Route::prefix('large-assets/api')->name('large-assets.api.')->group(function () {
    require 'api.php';
});
