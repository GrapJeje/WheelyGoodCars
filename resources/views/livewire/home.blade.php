<?php

namespace App\Http\Livewire;

use App\Models\Tags;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Cars;

new #[Layout('layouts.default')]
class extends Component {
    use WithPagination;

    public $perPage = 12;
    public $bigChance = 15;

    // Tag filter state
    public $tagSearch = '';
    public $selectedTags = [];
    public $showTagDropdown = false;

    // Reset pagination when search term changes and show dropdown
    public function updatedTagSearch()
    {
        $this->resetPage();
        $this->showTagDropdown = true;
    }

    // Reset pagination when selected tags change
    public function updatedSelectedTags()
    {
        $this->resetPage();
    }

    // Add a tag to the active filter, prevent duplicates
    public function selectTag($tagId, $tagName)
    {
        if (!collect($this->selectedTags)->contains('id', $tagId)) {
            $this->selectedTags[] = ['id' => $tagId, 'name' => $tagName];
        }
        $this->tagSearch = '';
        $this->showTagDropdown = false;
        $this->resetPage();
    }

    // Remove a single tag from the active filter
    public function removeTag($tagId)
    {
        $this->selectedTags = collect($this->selectedTags)
            ->reject(fn($tag) => $tag['id'] === $tagId)
            ->values()
            ->toArray();
        $this->resetPage();
    }

    // Clear all active tag filters
    public function clearTags()
    {
        $this->selectedTags = [];
        $this->tagSearch = '';
        $this->resetPage();
    }

    // Return tags matching the search term, excluding already selected ones
    #[Computed]
    public function suggestedTags()
    {
        if (strlen($this->tagSearch) < 1) {
            return collect();
        }

        $selectedIds = collect($this->selectedTags)->pluck('id');

        return Tags::where('name', 'like', '%' . $this->tagSearch . '%')
            ->whereNotIn('id', $selectedIds)
            ->limit(8)
            ->get();
    }

    // Fetch paginated cars, apply bigChance and fill any leftover grid spots
    // with extra cards from the next page to avoid empty columns
    #[Computed]
    public function cars()
    {
        $selectedIds = collect($this->selectedTags)->pluck('id');

        $query = Cars::with('owner', 'tags')
            ->orderBy('created_at', 'desc');

        // Filter on selected tags if any are active
        if ($selectedIds->isNotEmpty()) {
            $query->whereHas('tags', function ($q) use ($selectedIds) {
                $q->whereIn('tags.id', $selectedIds);
            });
        }

        $cars = $query->paginate($this->perPage);

        // Randomly assign the 'bigger' class based on bigChance percentage
        $cars->getCollection()->transform(function ($car) {
            $car->isBig = rand(1, 100) <= $this->bigChance;
            return $car;
        });

        // Count occupied columns (big = 2, small = 1) and fetch extra cards
        // if the last row is incomplete
        $cols = $cars->getCollection()->sum(fn($car) => $car->isBig ? 2 : 1);
        $remainder = $cols % 3;

        if ($remainder !== 0 && $cars->hasMorePages()) {
            $extra = 3 - $remainder;
            $extraCars = Cars::with('owner', 'tags')
                ->orderBy('created_at', 'desc')
                ->skip($this->perPage)
                ->take($extra)
                ->get()
                ->transform(function ($car) {
                    $car->isBig = false; // extra cards are never big
                    return $car;
                });

            $cars->getCollection()->push(...$extraCars);
        }

        return $cars;
    }
}; ?>

<div class="listings">
    <div class="listings-header">
        <h1>Alle advertenties</h1>
    </div>

    <div class="listings-filter">
        <div class="tag-filter" x-data="{ open: @entangle('showTagDropdown') }">

            @if(!empty($selectedTags))
                <div class="selected-tags">
                    @foreach($selectedTags as $tag)
                        <span class="selected-tag">
                            {{ $tag['name'] }}
                            <button wire:click="removeTag({{ $tag['id'] }})" type="button" aria-label="Verwijder tag">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2.5"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        </span>
                    @endforeach
                    <button wire:click="clearTags" class="clear-tags" type="button">Wis alles</button>
                </div>
            @endif

            <div class="tag-search-wrapper">
                <svg class="tag-search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.200ms="tagSearch"
                    @focus="open = true"
                    @click.outside="open = false"
                    placeholder="Filter op tags..."
                    class="tag-search-input"
                    autocomplete="off"
                />

                <div class="tag-dropdown" x-show="open && $wire.tagSearch.length > 0" x-cloak>
                    @if($this->suggestedTags->isNotEmpty())
                        @foreach($this->suggestedTags as $tag)
                            <button
                                type="button"
                                class="tag-option"
                                wire:click="selectTag({{ $tag->id }}, '{{ $tag->name }}')"
                                @mousedown.prevent
                            >
                                {{ $tag->name }}
                            </button>
                        @endforeach
                    @else
                        <div class="tag-no-results">Geen tags gevonden voor "{{ $tagSearch }}"</div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    @if($this->cars->isEmpty())
        <div class="listings-empty">
            @if(!empty($selectedTags))
                Geen advertenties gevonden met de geselecteerde tags.
            @else
                Er zijn nog geen advertenties geplaatst.
            @endif
        </div>
    @else
        <div class="listings-grid">
            @foreach($this->cars as $car)
                <a href="/#" class="listings-card {{ !$car->image ? 'no-image' : '' }} {{ $car->isBig ? 'bigger' : '' }}">

                    <div class="listings-image">
                        @if($car->image)
                            <img src="{{ asset('storage/' . $car->image) }}"
                                 alt="{{ $car->make }} {{ $car->model }}">
                        @else
                            <div class="image-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="1.5"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="1" y="3" width="15" height="13"/>
                                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                    <circle cx="5.5" cy="18.5" r="2.5"/>
                                    <circle cx="18.5" cy="18.5" r="2.5"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    @if($car->tags->isNotEmpty())
                        <div class="listings-tags">
                            @foreach($car->tags as $tag)
                                <span class="tag">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="listings-info">
                        <div class="listings-info-top">
                            <span class="car-title">{{ $car->make }} {{ $car->model }}</span>
                            <span class="license-plate">{{ $car->license_plate }}</span>
                        </div>
                        <div class="listings-info-bottom">
                            <span class="car-meta">{{ $car->production_year }} · {{ number_format($car->mileage, 0, ',', '.') }} km</span>
                            <span class="price">€ {{ number_format($car->price, 0, ',', '.') }}</span>
                        </div>
                    </div>

                </a>
            @endforeach
        </div>

        <div class="pagination-wrapper">
            {{ $this->cars->links() }}
        </div>
    @endif
</div>
