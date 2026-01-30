<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

new
#[Layout('layouts.default')]
class extends Component {

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register()
    {
        $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        return redirect('/login');
    }
}

?>

<div style="max-width: 400px; margin: 100px auto; font-family: sans-serif;">
    <h2>Register</h2>

    <form wire:submit.prevent="register">

        <div style="margin-bottom: 10px;">
            <label>Name</label><br>
            <input
                type="text"
                wire:model.defer="name"
                style="width: 100%; padding: 6px;"
            >
            @error('name')
            <div style="color: red; font-size: 12px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="margin-bottom: 10px;">
            <label>Email</label><br>
            <input
                type="email"
                wire:model.defer="email"
                style="width: 100%; padding: 6px;"
            >
            @error('email')
            <div style="color: red; font-size: 12px;">{{ $message }}</div>
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
            <div style="color: red; font-size: 12px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="margin-bottom: 10px;">
            <label>Confirm password</label><br>
            <input
                type="password"
                wire:model.defer="password_confirmation"
                style="width: 100%; padding: 6px;"
            >
        </div>

        <button type="submit" style="padding: 8px 12px;">
            Register
        </button>
    </form>
</div>
