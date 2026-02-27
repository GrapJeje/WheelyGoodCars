<?php

use Livewire\Volt\Component;

new class extends Component {
    public bool $dropdown = false;
};
?>

<div class="profile-container">
    <a href="#" wire:click.prevent="$toggle('dropdown')" class="profile-toggle" aria-expanded="{{ $dropdown ? 'true' : 'false' }}">
        <img src="{{ asset('svg/person.svg') }}" alt="Profile Icon">
    </a>

    <div class="dropdown" style="display: {{ $dropdown ? 'block' : 'none' }}">
        <a href="">Mijn aanbod</a>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <input type="submit" value="Logout" class="logout-button">
        </form>
    </div>
</div>
