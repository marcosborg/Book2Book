<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

Route::view('/login', 'app', ['page' => 'login'])->name('login');
Route::view('/register', 'app', ['page' => 'register'])->name('register');
Route::view('/profile', 'app', ['page' => 'profile'])->name('profile');
Route::view('/books', 'app', ['page' => 'books'])->name('books.index');
Route::view('/books/create', 'app', ['page' => 'book-create'])->name('books.create');
Route::view('/library', 'app', ['page' => 'library'])->name('library');
Route::view('/trades', 'app', ['page' => 'trades'])->name('trades.index');
Route::view('/notifications', 'app', ['page' => 'notifications'])->name('notifications');

Route::get('/books/{book}', fn (string $book) => view('app', ['page' => 'book-detail']))->name('books.show');
Route::get('/trades/{trade}/chat', fn (string $trade) => view('app', ['page' => 'chat']))->name('trades.chat');
