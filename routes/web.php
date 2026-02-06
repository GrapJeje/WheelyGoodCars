<?php

use Livewire\Volt\Volt;

Volt::route('/', 'home')
    ->name('home');

Volt::route('/add', 'listings.create')
    ->name('add.car');

Route::middleware('guest')->group(function () {
    Volt::route('/register', 'auth.register')
        ->name('register');

    Volt::route('/login', 'auth.login')
        ->name('login');
});
