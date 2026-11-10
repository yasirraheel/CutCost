<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CapturedPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CapturedPageController::class, 'show'])->defaults('page', 'index.html');
Route::get('/en', [CapturedPageController::class, 'show'])->defaults('page', 'en/index.html')->name('home');
Route::get('/en/', [CapturedPageController::class, 'show'])->defaults('page', 'en/index.html');
Route::get('/en/index.html', [CapturedPageController::class, 'show'])->defaults('page', 'en/index.html');

Route::get('/en/subscriptions', [CapturedPageController::class, 'show'])
    ->defaults('page', 'en/subscriptions.html')
    ->name('subscriptions');
Route::get('/en/subscriptions.html', [CapturedPageController::class, 'show'])
    ->defaults('page', 'en/subscriptions.html');
Route::get('/en/subscriptions/house.html', [CapturedPageController::class, 'show'])
    ->defaults('page', 'en/subscriptions/house.html');
Route::get('/en/brand/amazon.html', [CapturedPageController::class, 'show'])
    ->defaults('page', 'en/brand/amazon.html');

Route::middleware('guest')->group(function (): void {
    Route::get('/en/userlogin', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/en/userlogin.html', [AuthController::class, 'showLogin']);
    Route::post('/en/userlogin', [AuthController::class, 'login']);

    Route::get('/en/register', [AuthController::class, 'showRegister'])->name('register');
    Route::get('/en/register.html', [AuthController::class, 'showRegister']);
    Route::post('/en/register', [AuthController::class, 'register']);
});

Route::post('/en/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

$protectedPages = [
    'dashboard' => ['en/dashboard', 'en/dashboard.html'],
    'shared-subscriptions' => ['en/my-shared-subscriptions', 'en/my-shared-subscriptions.html'],
    'joined-subscriptions' => ['en/joined-subscriptions', 'en/joined-subscriptions.html'],
    'waiting-list' => ['en/waiting-list', 'en/waiting-list.html'],
    'wallet' => ['en/my-wallet', 'en/my-wallet.html'],
    'cards' => ['en/my-cards', 'en/my-cards.html'],
    'account' => ['en/my-account', 'en/my-account.html'],
    'settings' => ['en/my-settings', 'en/my-settings.html'],
    'chat' => ['en/my-chat/subscriptions', 'en/my-chat/subscriptions.html'],
];

foreach ($protectedPages as $name => [$canonicalPath, $htmlPath]) {
    Route::get('/'.$canonicalPath, [CapturedPageController::class, 'show'])
        ->defaults('page', $htmlPath)
        ->middleware('auth')
        ->name('user.'.$name);

    Route::get('/'.$htmlPath, [CapturedPageController::class, 'show'])
        ->defaults('page', $htmlPath)
        ->middleware('auth');
}

// Keep all supplied captured pages routable while avoiding a new design layer.
Route::get('/{path}', [CapturedPageController::class, 'fallback'])->where('path', '.*');
