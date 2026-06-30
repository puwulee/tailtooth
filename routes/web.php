<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ParticipantController;
use Illuminate\Support\Facades\Route;

// 首頁：輸入活動代碼加入
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/join', [HomeController::class, 'join'])->name('join');

// 觀眾端：活動提問牆（以 slug 進入）
Route::prefix('e/{event}')->group(function () {
    Route::get('/', [ParticipantController::class, 'show'])->name('events.show');
    Route::post('/verify', [ParticipantController::class, 'verify'])->name('events.verify');
    Route::get('/questions', [ParticipantController::class, 'questions'])->name('events.questions');
    Route::post('/questions', [ParticipantController::class, 'storeQuestion'])->name('events.questions.store');
    Route::post('/questions/{question}/vote', [ParticipantController::class, 'vote'])->name('events.questions.vote');
});

// 主辦者登入
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// 後台（需登入）
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('events', [EventController::class, 'store'])->name('events.store');
    Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::post('events/{event}/toggle', [EventController::class, 'toggleStatus'])->name('events.toggle');
    Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

    // 公司名單（主辦者共用，跨活動）
    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::post('companies/import', [CompanyController::class, 'import'])->name('companies.import');
    Route::put('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

    // 提問審理
    Route::post('events/{event}/questions/{question}/answer', [QuestionController::class, 'answer'])->name('questions.answer');
    Route::post('events/{event}/questions/{question}/approve', [QuestionController::class, 'approve'])->name('questions.approve');
    Route::post('events/{event}/questions/{question}/pin', [QuestionController::class, 'togglePin'])->name('questions.pin');
    Route::post('events/{event}/questions/{question}/archive', [QuestionController::class, 'archive'])->name('questions.archive');
    Route::delete('events/{event}/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
});
