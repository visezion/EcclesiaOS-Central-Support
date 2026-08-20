<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SupportManagementController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UpdateController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'create']);
Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
Route::get('/setup', [SetupController::class, 'create'])->name('setup');
Route::post('/setup', [SetupController::class, 'store'])->middleware('throttle:3,10')->name('setup.store');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'super_admin'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/system/update', [UpdateController::class, 'page'])->name('system.update.page');
    Route::post('/system/update', [UpdateController::class, 'run'])->middleware('throttle:2,10')->name('system.update');
    Route::get('/system/update/status', [UpdateController::class, 'status'])->name('system.update.status');
    Route::post('/installations/token', [DashboardController::class, 'createToken'])->name('installations.token');
    Route::get('/installations/token', fn () => redirect()->to(route('dashboard').'#connect-church'));
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
    Route::get('/support/knowledge/create', [SupportManagementController::class, 'createArticle'])->name('support.knowledge.create');
    Route::get('/support/knowledge/{article}', [SupportManagementController::class, 'showArticle'])->name('support.knowledge.show');
    Route::get('/support/knowledge/{article}/edit', [SupportManagementController::class, 'editArticle'])->name('support.knowledge.edit');
    Route::post('/support/knowledge', [SupportManagementController::class, 'storeArticle'])->name('support.knowledge.store');
    Route::patch('/support/knowledge/{article}', [SupportManagementController::class, 'updateArticle'])->name('support.knowledge.update');
    Route::delete('/support/knowledge/{article}', [SupportManagementController::class, 'deleteArticle'])->name('support.knowledge.delete');
    Route::get('/support/live', [SupportManagementController::class, 'live'])->name('support.live');
    Route::patch('/support/live/{message}', [SupportManagementController::class, 'updateLive'])->name('support.live.update');
    Route::get('/support/central-connection', [SupportManagementController::class, 'connection'])->name('support.connection');
    Route::get('/support/audit', [SupportManagementController::class, 'audit'])->name('support.audit');
    Route::post('/support/central-connection/installations', [SupportManagementController::class, 'registerInstallation'])->name('support.connection.installations.store');
    Route::post('/support/central-connection/remote-exchange', [SupportManagementController::class, 'exchangeGrant'])->name('support.remote.exchange');
});
