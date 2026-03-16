<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.default')]
class extends Component {
    public $listings = [];

    public function mount()
    {
        $this->listings = auth()->user()->listings()->get();
    }
};
?>

<div class="my-listings">
    <div class="listings-header">
        <h1>Mijn aanbod</h1>
        <span
            class="listings-count">{{ count($listings) }} {{ count($listings) === 1 ? 'voertuig' : 'voertuigen' }}</span>
    </div>

    <div class="list">
        @forelse($listings as $listing)
            <div class="list-item">
                <a href="{{ route('car.detail', $listing->license_plate) }}" class="list-item-link">
                    <div class="list-image">
                        @if($listing->image)
                            <img src="{{ asset('storage/' . $listing->image) }}" alt="{{ $listing->make }} {{ $listing->model }}">
                        @else
                            <div class="image-placeholder">
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
                    </div>

                    <div class="list-info">
                        <div class="car-title">{{ $listing->make }} {{ $listing->model }}</div>
                        <div class="car-meta">
                            {{ $listing->production_year }} &middot; {{ number_format($listing->mileage) }} km
                        </div>
                    </div>

                    <div class="list-meta-row">
                        <div class="list-plate">
                            <span class="license-plate">{{ $listing->license_plate }}</span>
                        </div>

                        <div class="list-status">
                            <span class="selling-label {{ $listing->sold_at ? 'sold' : 'selling' }}">
                                {{ $listing->sold_at ? 'Verkocht' : 'Te koop' }}
                            </span>
                        </div>

                        <div class="list-price">
                            <span class="price">€&nbsp;{{ number_format($listing->price, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="list-empty">
                <p>Je hebt nog geen advertenties geplaatst.</p>
            </div>
        @endforelse
    </div>
</div>
