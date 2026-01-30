<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new
#[Layout('layouts.default')]
class extends Component {

    public string $email = '';
    public string $password = '';

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt([
            'email' => $this->email,
            'password' => $this->password,
        ])) {
            request()->session()->regenerate();
            return redirect('/dashboard');
        }

        $this->addError('email', 'Invalid login credentials.');
    }
}

?>

<div style="max-width: 400px; margin: 100px auto; font-family: sans-serif;">
    <h2>Login</h2>

    <form wire:submit.prevent="login">
        <div style="margin-bottom: 10px;">
            <label>Email</label><br>
            <input
                type="email"
                wire:model.defer="email"
                style="width: 100%; padding: 6px;"
            >
            @error('email')
            <div style="color: red; font-size: 12px;">
                {{ $message }}
            </div>
            @enderror
        </div>

        <div style="margin-bottom: 10px;">
            <label>Password</label><br>
            <input
                type="password"
                wire:model.defer="password"
                style="width: 100%; padding: 6px;"
            >
            @error('password')
            <div style="color: red; font-size: 12px;">
                {{ $message }}
            </div>
            @enderror
        </div>

        <button type="submit" style="padding: 8px 12px;">
            Login
        </button>
    </form>
</div>
