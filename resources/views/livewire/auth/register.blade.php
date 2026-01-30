<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.default')]
class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(CreatesNewUsers $creator): void
    {
        $user = $creator->create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        Auth::login($user);

        $this->redirect(route('dashboard'), navigate: true);
    }
};
?>

<div>
    <form wire:submit="register">
        <div>
            <label>Name</label><br>
            <input type="text" wire:model="name">
            @error('name')
            <div>{{ $message }}</div> @enderror
        </div>

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
            <label>Confirm password</label><br>
            <input type="password" wire:model="password_confirmation">
        </div>

        <button type="submit">Register</button>
    </form>
</div>
