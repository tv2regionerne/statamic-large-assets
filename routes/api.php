<?php

use Illuminate\Support\Facades\Route;
use Tv2regionerne\StatamicLargeAssets\Http\Controllers\API\UploadController;

Route::post('upload/create', [UploadController::class, 'create']);
Route::post('upload/sign-part', [UploadController::class, 'signPart']);
Route::post('upload/complete', [UploadController::class, 'complete']);
Route::post('upload/abort', [UploadController::class, 'abort']);
