<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SupportManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'create']);
Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/installations/token', [DashboardController::class, 'createToken'])->name('installations.token');
    Route::patch('/installations/{installation}/toggle', [DashboardController::class, 'toggleInstallation'])->name('installations.toggle');
    Route::get('/support/tickets', [SupportManagementController::class, 'tickets'])->name('support.tickets');
    Route::post('/support/tickets', [SupportManagementController::class, 'storeTicket'])->name('support.tickets.store');
    Route::patch('/support/tickets/{ticket}', [SupportManagementController::class, 'updateTicket'])->name('support.tickets.update');
    Route::post('/support/tickets/{ticket}/replies', [SupportManagementController::class, 'replyTicket'])->name('support.tickets.replies.store');
    Route::delete('/support/tickets/{ticket}', [SupportManagementController::class, 'deleteTicket'])->name('support.tickets.delete');
    Route::get('/support/community', [SupportManagementController::class, 'community'])->name('support.community');
    Route::patch('/support/community/{question}', [SupportManagementController::class, 'updateQuestion'])->name('support.community.update');
    Route::delete('/support/community/{question}', [SupportManagementController::class, 'deleteQuestion'])->name('support.community.delete');
    Route::get('/support/knowledge', [SupportManagementController::class, 'knowledge'])->name('support.knowledge');
    Route::post('/support/knowledge', [SupportManagementController::class, 'storeArticle'])->name('support.knowledge.store');
    Route::patch('/support/knowledge/{article}', [SupportManagementController::class, 'updateArticle'])->name('support.knowledge.update');
    Route::delete('/support/knowledge/{article}', [SupportManagementController::class, 'deleteArticle'])->name('support.knowledge.delete');
    Route::get('/support/live', [SupportManagementController::class, 'live'])->name('support.live');
    Route::patch('/support/live/{message}', [SupportManagementController::class, 'updateLive'])->name('support.live.update');
    Route::get('/support/central-connection', [SupportManagementController::class, 'connection'])->name('support.connection');
    Route::post('/support/central-connection/installations', [SupportManagementController::class, 'registerInstallation'])->name('support.connection.installations.store');
    Route::post('/support/central-connection/remote-exchange', [SupportManagementController::class, 'exchangeGrant'])->name('support.remote.exchange');
});
