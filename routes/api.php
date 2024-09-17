<?php

use Illuminate\Support\Facades\Route;
use Tv2regionerne\StatamicLargeAssets\Http\Controllers\API\UploadController;

Route::post('upload/create', [UploadController::class, 'create']);
Route::post('upload/complete', [UploadController::class, 'complete']);
