<?php

use Illuminate\Container\Attributes\Tag;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Cars;
use Livewire\Attributes\On;

new #[Layout('layouts.default')]
class extends Component {
    public Cars $car;
    public bool $showViews = false;
    public int $viewCount = 0;
    public bool $isOwner = false;

    // Price edit
    public bool $isEditingPrice = false;
    public int $price = 0;

    // Tag edit
    public bool $isEditingTags = false;
    public string $newTag = '';

    // Delete
    public bool $showDeleteConfirm = false;

    public function mount($plate)
    {
        $this->car = Cars::where('license_plate', $plate)
            ->with('tags')
            ->firstOrFail();

        $this->isOwner = $this->car->isOwner();

        if (!$this->isOwner) {
            // Increment views with caching to prevent multiple increments from the same user within 24 hours
            $key = 'viewed_' . $this->car->id;
            if (!cache()->has($key)) {
                $this->car->increment('views');
                cache()->put($key, true, now()->addHours(24));
            }
        }

        $this->viewCount = $this->car->views;
        $this->price = $this->car->price;
    }

    // Popup listener
    #[On('show-views-popup')]
    public function showViewsPopup()
    {
        $this->showViews = true;
    }

    public function closePopup()
    {
        $this->showViews = false;
    }

    public function toggleStatus()
    {
        abort_unless($this->car->isOwner(), 403);

        $this->car->sold_at = $this->car->sold_at ? null : now();
        $this->car->save();
        $this->car->refresh();

        $this->dispatch('refresh');
    }

    public function togglePriceEdit()
    {
        $this->isEditingPrice = true;
        $this->price = $this->car->price;
    }

    public function cancelPriceEdit()
    {
        $this->isEditingPrice = false;
        $this->price = $this->car->price;
    }

    public function savePrice()
    {
        abort_unless($this->car->isOwner(), 403);

        $this->validate([
            'price' => 'required|integer|min:0',
        ]);

        $this->car->update(['price' => $this->price]);
        $this->car->refresh();
        $this->isEditingPrice = false;
        session()->flash('success', 'Prijs succesvol bijgewerkt!');
    }

    public function toggleTagEditor()
    {
        $this->isEditingTags = !$this->isEditingTags;
        $this->newTag = '';
    }

    public function addTag()
    {
        abort_unless($this->car->isOwner(), 403);

        $this->validate([
            'newTag' => 'required|string|max:30',
        ]);

        $tag = \App\Models\Tags::firstOrCreate(['name' => trim($this->newTag)]);
        $this->car->tags()->syncWithoutDetaching([$tag->id]);
        $this->car->load('tags');
        $this->newTag = '';
    }

    public function removeTag(int $tagId)
    {
        abort_unless($this->car->isOwner(), 403);

        $this->car->tags()->detach($tagId);
        $this->car->load('tags');
    }

    public function confirmDelete()
    {
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete()
    {
        $this->showDeleteConfirm = false;
    }

    public function deleteCar()
    {
        abort_unless($this->car->isOwner(), 403);

        $this->car->delete();
        return redirect()->route('user.listings')->with('success', 'Auto succesvol verwijderd!');
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

            <div class="car-detail-status">
                @if(!$car->sold_at)
                    <span class="status-badge selling">
                    <span class="status-dot"></span>
                    Te Koop
                </span>
                @else
                    <span class="status-badge sold">
                    <span class="status-dot"></span>
                    Verkocht
                </span>
                @endif
            </div>
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

                @if($car->isOwner() && $car->tags->count() > 0 && !$car->sold_at)
                    <div class="car-detail-tags-section">
                        <div class="tags-header">
                            <span class="tags-section-label">Tags</span>
                            <button class="tag-edit-toggle" wire:click="toggleTagEditor" title="Tags bewerken">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.5"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 20.25l1.97-5.085a4.5 4.5 0 0 1 1.13-1.897l8.7-8.694z"/>
                                </svg>
                                Tags bewerken
                            </button>
                        </div>
                    </div>
                @endif

                @if($car->tags->count() > 0)
                    <div class="car-detail-tags">
                        @foreach($car->tags as $tag)
                            <span
                                class="detail-tag tag-color--{{ $tag->color ?? 'default' }}">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif

                @if($isEditingTags)
                    <div class="tag-editor">
                        <div class="tag-editor-current">
                            @foreach($car->tags as $tag)
                                <span class="detail-tag tag-removable">
                                    {{ $tag->name }}
                                    <button wire:click="removeTag({{ $tag->id }})" class="tag-remove-btn"
                                            title="Verwijder tag">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </span>
                            @endforeach
                        </div>

                        <div class="tag-add-row">
                            <input
                                type="text"
                                wire:model="newTag"
                                wire:keydown.enter.prevent="addTag"
                                placeholder="Nieuwe tag toevoegen..."
                                class="tag-input"
                                maxlength="30"
                            >
                            <button wire:click="addTag" class="tag-add-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                                Toevoegen
                            </button>
                        </div>
                        @error('newTag') <span class="error-text">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="car-detail-section">
                    <h3 class="section-title">Over deze auto</h3>
                    <p class="section-text">
                        Deze {{ $car->make }} {{ $car->model }} uit {{ $car->production_year }} is in
                        uitstekende staat.
                        Met {{ number_format($car->mileage, 0, ',', '.') }} kilometer op de teller en een
                        gewicht
                        van {{ number_format($car->weight, 0, ',', '.') }} kg,
                        biedt dit voertuig een perfecte combinatie van betrouwbaarheid en comfort.
                    </p>
                </div>
            </div>

            <div class="car-detail-sidebar">
                <div class="price-card">
                    <span class="price-label">Vraagprijs</span>
                    @if($isEditingPrice)
                        <div class="price-edit-row">
                            <span class="price-euro-sign">€</span>
                            <input
                                type="number"
                                wire:model="price"
                                class="price-edit-input"
                                min="0"
                                autofocus
                            >
                        </div>
                        @error('price') <span class="error-text-light">{{ $message }}</span> @enderror
                        <div class="price-edit-actions">
                            <button class="price-save-btn" wire:click="savePrice">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                                Opslaan
                            </button>
                            <button class="price-cancel-btn" wire:click="cancelPriceEdit">Annuleer</button>
                        </div>
                    @else
                        <span class="price-value">€ {{ number_format($car->price, 2, ',', '.') }}</span>
                        @if($car->isOwner() && !$car->sold_at)
                            <button class="price-edit-trigger" wire:click="togglePriceEdit">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.5"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 20.25l1.97-5.085a4.5 4.5 0 0 1 1.13-1.897l8.7-8.694z"/>
                                </svg>
                                Prijs aanpassen
                            </button>
                        @endif
                    @endif
                </div>

                @if (!$car->isOwner())
                    @if (!$car->sold_at)
                        <button class="btn-primary" wire:navigate href="/">
                            Contacteer Verkoper
                        </button>
                    @endif
                @else
                    <div class="status-toggle-card">
                        <div class="status-toggle-header">
                            <span class="status-toggle-label">Status advertentie</span>
                            <span class="status-toggle-current {{ $car->sold_at ? 'is-sold' : 'is-active' }}">
                        {{ $car->sold_at ? 'Verkocht' : 'Te Koop' }}
                    </span>
                        </div>
                        <button
                            class="status-toggle-btn {{ $car->sold_at ? 'btn-reactivate' : 'btn-mark-sold' }}"
                            wire:click="toggleStatus"
                            wire:loading.attr="disabled"
                        >
                    <span wire:loading.remove wire:target="toggleStatus">
                        @if($car->sold_at)
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                            </svg>
                            Zet terug als Te Koop
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                            Markeer als Verkocht
                        @endif
                    </span>
                            <span wire:loading wire:target="toggleStatus">Bezig...</span>
                        </button>
                        @if($car->sold_at)
                            <p class="status-toggle-note">Deze auto is verborgen voor bezoekers.</p>
                        @endif
                    </div>
                @endif

                @if(!$car->isOwner())
                    @if (!$car->sold_at)
                        <div class="info-section">
                            <h4 class="info-title">Hoe werkt het?</h4>
                            <ol class="info-list">
                                <li>Stuur een bericht naar de verkoper</li>
                                <li>Plan een kijkmoment in</li>
                                <li>Sluit jouw auto in ruil in</li>
                                <li>Handel af bij de verkoper</li>
                            </ol>
                        </div>
                    @endif
                @else
                    <div class="stats-card">
                        <div class="stat-item">
                            <span class="stat-label">Views vandaag</span>
                            <span class="stat-value">{{ $viewCount }}</span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <span class="stat-label">Geplaatst op</span>
                            <span class="stat-value">{{ $car->created_at->format('d M Y') }}</span>
                        </div>
                    </div>

                    @if (!$car->sold_at)
                        <button class="btn-danger owner-delete-btn" wire:click="confirmDelete">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path
                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                            Verwijder Auto
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    @if ($showDeleteConfirm)
        <div class="delete-modal-overlay show">
            <div class="delete-modal">
                <button class="modal-close-btn" wire:click="cancelDelete">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <div class="modal-icon warning">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>

                <h3 class="modal-title">Auto Verwijderen?</h3>
                <p class="modal-text">
                    Weet je zeker dat je deze <strong>{{ $car->make }} {{ $car->model }}</strong> wilt verwijderen?
                    Dit kan niet ongedaan gemaakt worden.
                </p>

                <div class="modal-actions">
                    <button class="btn-secondary" wire:click="cancelDelete">Annuleer</button>
                    <button class="btn-danger" wire:click="deleteCar" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="deleteCar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path
                                d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                        Definitief Verwijderen
                    </span>
                        <span wire:loading wire:target="deleteCar">Verwijderen...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showViews)
        <div class="views-popup-overlay show">
            <div class="views-popup">
                <button class="modal-close-btn" wire:click="closePopup">
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

    @if (!$car->isOwner() && !$car->sold_at)
        <script>
            document.addEventListener('livewire:initialized', function () {
                setTimeout(function () {
                    @this.
                    call('showViewsPopup');
                }, 10000);
            });
        </script>
    @endif
</div>
