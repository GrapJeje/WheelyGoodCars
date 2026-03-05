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

    public function register(CreatesNewUsers $creator)
    {
        $user = $creator->create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        Auth::login($user);

        return $this->redirect(route('user.listings'));
    }
};
?>

<div class="auth container-40">
    <div class="tab">
        <a href="{{ route('login') }}">Inloggen</a>
        <a href="{{ route('register') }}" class="active">Account aanmaken</a>
    </div>

    <div class="container">
        <div class="title">
            <h2>Account aanmaken</h2>
            <p>Al een account? <a href="{{ route('login') }}">Log nu in</a></p>
        </div>

        <form wire:submit="register">
            @csrf
            <div>
                <label for="name">Gebruikersnaam</label>
                <input id="name" type="text" wire:model="name">
                @error('name')
                <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email">E-mailadres</label>
                <input id="email" type="email" wire:model="email">
                @error('email')
                <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password">Wachtwoord</label>
                <div class="password-field">
                    <input id="password" type="password" wire:model="password">

                    <button
                        type="button"
                        class="toggle-password"
                        aria-label="Wachtwoord zichtbaarheid in-/uitschakelen"
                        aria-pressed="false"
                        title="Wachtwoord weergeven">
                        <svg class="icon icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="icon icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" style="display:none" aria-hidden="true">
                            <path
                                d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.3 20.3 0 0 1 5.06-6.06"></path>
                            <path d="M1 1l22 22"></path>
                        </svg>
                    </button>
                </div>
                @error('password')
                <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation">Wachtwoord herhalen</label>
                <div class="password-field">
                    <input id="password_confirmation" type="password" wire:model="password_confirmation">

                    <button
                        type="button"
                        class="toggle-password"
                        aria-label="Wachtwoord zichtbaarheid in-/uitschakelen"
                        aria-pressed="false"
                        title="Wachtwoord weergeven">
                        <svg class="icon icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="icon icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" style="display:none" aria-hidden="true">
                            <path
                                d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.3 20.3 0 0 1 5.06-6.06"></path>
                            <path d="M1 1l22 22"></path>
                        </svg>
                    </button>
                </div>
                @error('password_confirmation')
                <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit">Account aanmaken</button>
        </form>
    </div>
</div>
