<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

new #[Layout('layouts.default')]
class extends Component {
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            throw ValidationException::withMessages([
                'email' => __('Deze gegevens kloppen niet.'),
            ]);
        }

        session()->regenerate();

        $this->redirectIntended(route('dashboard'), navigate: true);
    }
};
?>

<div>
    <form wire:submit="login">
        <div>
            <label>Email</label><br>
            <input type="email" wire:model="email">
            @error('email')
            <div>{{ $message }}</div> @enderror
        </div>

        <div>
            <label>Password</label><br>
            <input type="password" wire:model="password">
            @error('password')
            <div>{{ $message }}</div> @enderror
        </div>

        <div>
            <label>
                <input type="checkbox" wire:model="remember">
                Remember me
            </label>
        </div>

        <button type="submit">Login</button>
    </form>
</div>
