<?php

use Livewire\Volt\Volt;

Volt::route('/', 'home')
    ->name('home');

Volt::route('/add', 'listings.create')
    ->name('add.car');

Volt::route('/mijn-aanbod', 'listings.my-listings')
    ->name('user.listings');

Route::middleware('guest')->group(function () {
    Volt::route('/register', 'auth.register')
        ->name('register');

    Volt::route('/login', 'auth.login')
        ->name('login');
});
