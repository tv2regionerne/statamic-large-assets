<?php

use Illuminate\Support\Facades\Route;
use Tv2regionerne\StatamicLargeAssets\Http\Controllers\API\UploadS3Controller;
use Tv2regionerne\StatamicLargeAssets\Http\Controllers\API\UploadTusController;

Route::prefix('upload-s3')->group(function () {
    Route::post('create', [UploadS3Controller::class, 'create']);
    Route::post('sign-part', [UploadS3Controller::class, 'signPart']);
    Route::post('complete', [UploadS3Controller::class, 'complete']);
    Route::post('abort', [UploadS3Controller::class, 'abort']);
});

Route::prefix('upload-tus')->group(function () {
    Route::post('complete', [UploadTusController::class, 'complete']);
});
