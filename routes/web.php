<?php

use Livewire\Volt\Volt;

Volt::route('/', 'auth.login')
    ->name('home');

Volt::route('/register', 'auth.register')
    ->name('register');

Volt::route('/login', 'auth.login')
    ->name('login');
