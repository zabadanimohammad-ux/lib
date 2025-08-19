<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group([
    'prefix' => 'auth'
], function ($router) {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user-profile', [AuthController::class, 'userProfile'])->name('user-profile');
});

Route::group([
    'middleware' => 'jwt.auth',
    'prefix' => 'book'
], function ($router) {
    Route::post('/refill-wallet', [BookController::class, 'refillWallet']);
    Route::post('/add-book', [BookController::class, 'addBook']);
    Route::post('/update-book/{id}', [BookController::class, 'updateBook']);
    Route::post('/delete-book/{id}', [BookController::class, 'deleteBook']);
    Route::post('/type-book/{type}', [BookController::class, 'getType']);
    Route::post('/author-book/{author}', [BookController::class, 'getAuthor']);
    Route::get('/random-book', [BookController::class, 'getRandom']);
    Route::post('/get-book/{id}', [BookController::class, 'getBook']);
    Route::post('/name-book', [BookController::class, 'getNameBook']);
    Route::get('/get-all-books', [BookController::class, 'getAllBook']);
    Route::post('/download-book', [BookController::class, 'getDownloadBook']);
    Route::post('/upload-book', [BookController::class, 'getUploadBook']);
    Route::post('/borrow-book/{id}',[BookController::class, 'borrowBook']);
    Route::get('/get-borrows-books', [BookController::class, 'GetBorrowsBooks']);
    Route::post('/order-book/{id}',[BookController::class, 'OrderBook']);
    Route::get('/get-orders-books', [BookController::class, 'GetMyBook']);
    Route::post('/add-to-favourite/{id}',[BookController::class, 'AddToFavourite']);
    Route::get('/get-favourite-books', [BookController::class, 'GetMyFavourite']);

});
