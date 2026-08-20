<?php

use App\Http\Controllers\Api\SupportApiController;
use App\Http\Middleware\AuthenticateInstallation;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(AuthenticateInstallation::class)->group(function (): void {
    Route::get('installations/ping', [SupportApiController::class, 'ping']);
    Route::post('church/events', [SupportApiController::class, 'event']);
    Route::get('community/questions', [SupportApiController::class, 'questions']);
    Route::post('community/questions', [SupportApiController::class, 'createQuestion']);
    Route::get('knowledge/articles', [SupportApiController::class, 'knowledge']);
    Route::get('knowledge/articles/{article}', [SupportApiController::class, 'article']);
    Route::post('knowledge/articles/{article}/helpful', [SupportApiController::class, 'rateArticle']);
    Route::get('live-support', [SupportApiController::class, 'live']);
    Route::post('live-support/messages', [SupportApiController::class, 'liveMessage']);
});

Route::post('v1/installations/enroll', [SupportApiController::class, 'enroll'])
    ->middleware('throttle:10,1')
    ->name('api.installations.enroll');
