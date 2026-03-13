<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Cars;
use Livewire\Attributes\On;

new #[Layout('layouts.default')]
class extends Component {
    public Cars $car;
    public bool $showViews = false;
    public int $viewCount = 0;

    public function mount($plate)
    {
        $this->car = Cars::where('license_plate', $plate)
            ->firstOrFail();

        // View count logic TEMP
        $this->viewCount = rand(5, 15);
    }

    // Event listener for showing the views popup after 10 seconds
    #[On('show-views-popup')]
    public function showViewsPopup()
    {
        $this->showViews = true;
    }

    public function closePopup()
    {
        $this->showViews = false;
    }
};
?>

<div>
    <div class="car-detail-page">
        <div class="car-detail-hero">
            @if($car->image)
                <img src="{{ asset('storage/' . $car->image) }}"
                     alt="{{ $car->make }} {{ $car->model }}" class="car-detail-image">
            @else
                <div class="car-detail-image-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13"/>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                        <circle cx="5.5" cy="18.5" r="2.5"/>
                        <circle cx="18.5" cy="18.5" r="2.5"/>
                    </svg>
                </div>
            @endif

            @if(!$car->isOwner())
                <div class="car-detail-status">
                <span class="status-badge selling">
                    <span class="status-dot"></span>
                    Te Koop
                </span>
                </div>
            @endif
        </div>

        <div class="car-detail-container">
            <div class="car-detail-main">
                <div class="car-detail-header">
                    <div>
                        <h1 class="car-detail-title">{{ $car->make }} {{ $car->model }}</h1>
                        <p class="car-detail-meta">
                            {{ $car->production_year }} • {{ number_format($car->mileage, 0, ',', '.') }} km
                        </p>
                    </div>
                    <div class="car-detail-license">
                        {{ $car->license_plate }}
                    </div>
                </div>

                <div class="car-detail-specs">
                    <div class="spec-item">
                        <span class="spec-label">Kleur</span>
                        <span class="spec-value">{{ $car->color }}</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Deuren</span>
                        <span class="spec-value">{{ $car->doors }}</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Zitplaatsen</span>
                        <span class="spec-value">{{ $car->seats }}</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Gewicht</span>
                        <span class="spec-value">{{ number_format($car->weight, 0, ',', '.') }} kg</span>
                    </div>
                </div>

                @if($car->tags->count() > 0)
                    <div class="car-detail-tags">
                        @foreach($car->tags as $tag)
                            <span class="detail-tag">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="car-detail-section">
                    <h3 class="section-title">Over deze auto</h3>
                    <p class="section-text">
                        Deze {{ $car->make }} {{ $car->model }} uit {{ $car->production_year }} is in uitstekende staat.
                        Met {{ number_format($car->mileage, 0, ',', '.') }} kilometer op de teller en een gewicht
                        van {{ number_format($car->weight, 0, ',', '.') }} kg,
                        biedt dit voertuig een perfecte combinatie van betrouwbaarheid en comfort.
                    </p>
                </div>
            </div>

            <div class="car-detail-sidebar">
                <div class="price-card">
                    <span class="price-label">Vraagprijs</span>
                    <span class="price-value">€ {{ number_format($car->price, 2, ',', '.') }}</span>
                </div>

                @if(!$car->isOwner())
                    <button class="btn-primary" wire:navigate href="/">
                        Contacteer Verkoper
                    </button>


                    <div class="info-section">
                        <h4 class="info-title">Hoe werkt het?</h4>
                        <ol class="info-list">
                            <li>Stuur een bericht naar de verkoper</li>
                            <li>Plan een kijkmoment in</li>
                            <li>Sluit jouw auto in ruil in</li>
                            <li>Handel af bij de verkoper</li>
                        </ol>
                    </div>
                @else
                    <div class="owner-info">
                        <p class="owner-text">Dit is jouw advertentie</p>
                        <button class="btn-secondary" wire:navigate href="{{ route('user.listings') }}">
                            Mijn Aanbod
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(!$car->isOwner())
        @if ($showViews)
        <div class="views-popup-overlay">
            <div class="views-popup">
                <button class="popup-close" wire:click="closePopup">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <div class="popup-content">
                    <div class="popup-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 001.591-.079m-.5-9.37a9.347 9.347 0 011.085.071m0 0a9.333 9.333 0 011.591.079m0 0A9.36 9.36 0 0121 12a9.359 9.359 0 01-9 9.359m0 0a9.362 9.362 0 01-9-9.359m0 0a9.38 9.38 0 012.625-.372m0 0a9.337 9.337 0 011.591.079"/>
                        </svg>
                    </div>
                    <h3 class="popup-title">Populair!</h3>
                    <p class="popup-text">
                        {{ $viewCount }} klanten bekeken deze auto vandaag
                    </p>
                    <p class="popup-subtext">
                        Wees snel, deze advertentie trekt veel aandacht!
                    </p>
                </div>
            </div>
        </div>
        @endif

        <script>
            document.addEventListener('livewire:initialized', function () {
                setTimeout(function () {
                    console.log("AAAA");
                    Livewire.dispatch('show-views-popup');
                }, 10000); // 10 seconds
            });
        </script>
    @endif
</div>
