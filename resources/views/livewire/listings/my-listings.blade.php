<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.default')]
class extends Component {
    public $listings = [];

    public function mount()
    {
        $this->listings = auth()->user()->listings;
    }

    public function deleteListing($id)
    {
        $listing = auth()->user()->listings()->findOrFail($id);
        $listing->delete();
        $this->mount();
        $this->dispatch('notify', message: 'Advertentie verwijderd');
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
                <a href="" class="list-item-link">
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
                </a>

                <div class="list-actions">
                    <button wire:click="deleteListing({{ $listing->id }})"
                            wire:confirm="Weet je zeker dat je deze advertentie wilt verwijderen?"
                            class="btn-delete"
                            title="Verwijderen">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="list-empty">
                <p>Je hebt nog geen advertenties geplaatst.</p>
            </div>
        @endforelse
    </div>
</div>
