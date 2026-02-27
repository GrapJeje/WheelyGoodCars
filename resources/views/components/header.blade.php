<header>
    <div class="container">
        <div class="header">
            <a href="{{ route('home') }}" class="logo">WheelyGoodCars!</a>

            <div class="btn-container">
                <a href="{{ route('add.car') }}" class="add-car">Plaats aanbod</a>
                @if(!auth()->user())
                <a href="{{ route('login') }}" class="login">Inloggen</a>
                @else
                    <livewire:components.profile />
                @endif
            </div>
        </div>
    </div>
</header>
