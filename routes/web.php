<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ScanHistoryController;
use App\Http\Controllers\PullRequestController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\VulnerabilityController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

// Language switcher
Route::get('/lang/{locale}', [LocaleController::class, 'switch'])->name('lang.switch');

// ─── Public ───────────────────────────────────────────────
Route::get('/', [ScanController::class, 'home'])->name('home');

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Scan (public submit + status polling accessible without auth) ─
Route::post('/scan', [ScanController::class, 'store'])->name('scan.store');
Route::get('/scan/{scan}/loading', [ScanController::class, 'loading'])->name('scan.loading');
Route::get('/scan/{scan}/status', [ScanController::class, 'status'])->name('scan.status');
Route::get('/scan/{scan}/dashboard', [ScanController::class, 'dashboard'])->name('scan.dashboard');
Route::get('/scan/{scan}/pdf', [ScanController::class, 'exportPdf'])->name('scan.pdf');
Route::get('/vulnerability/{vulnerability}/fix', [VulnerabilityController::class, 'fix'])->name('vulnerability.fix');
Route::post('/vulnerability/{vulnerability}/save-fix', [VulnerabilityController::class, 'saveFix'])->name('vulnerability.save-fix');
Route::post('/vulnerabilities/fix-batch', [VulnerabilityController::class, 'generateFixes'])->name('vulnerabilities.fix-batch');

// ─── Authenticated ─────────────────────────────────────────
Route::middleware('auth')->group(function () {
    // My Scans history
    Route::get('/scans', [ScanHistoryController::class, 'index'])->name('scans.index');
    // Delete + Re-run
    Route::delete('/scan/{scan}', [ScanHistoryController::class, 'destroy'])->name('scan.destroy');
    Route::post('/scan/{scan}/rerun', [ScanHistoryController::class, 'rerun'])->name('scan.rerun');
    Route::post('/scan/{scan}/rescan', [ScanHistoryController::class, 'rescan'])->name('scan.rescan');
    // Pull Request
    Route::post('/scan/{scan}/pull-request', [PullRequestController::class, 'create'])->name('scan.pull-request');
    // Chatbot
    Route::post('/chat/message', [ChatController::class, 'message'])->name('chat.message');
    Route::delete('/vulnerability/{vulnerability}/explanation', [VulnerabilityController::class, 'clearExplanation'])->name('vulnerability.clear-explanation');
    // User Settings
    Route::get('/settings', [UserSettingsController::class, 'show'])->name('settings');
    Route::post('/settings', [UserSettingsController::class, 'update'])->name('settings.update');
    Route::get('/settings/gemini-status', [UserSettingsController::class, 'geminiStatus'])->name('settings.gemini-status');
});
