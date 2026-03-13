<?php

namespace App\Http\Livewire\Listings;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Cars as CarModel;
use App\Models\Tags as TagModel;
use App\Models\CarTags as CarTagModel;
use Livewire\WithFileUploads;

new #[Layout('layouts.default')]
class extends Component {

    use WithFileUploads;

    public string $plate = '';
    public int $step = 1;

    // RDW / form properties
    public ?string $brand = null;
    public ?string $model = null;
    public ?string $variant = null;
    public ?string $body_type = null;
    public ?string $fuel_type = null;
    public ?string $transmission = null;
    public ?int $year = null;
    public ?string $first_registration = null;
    public ?int $seats = null;
    public ?int $doors = null;
    public ?int $curb_weight = null;
    public ?string $color = null;
    public ?int $power_kw = null;
    public ?int $co2 = null;
    public ?string $vin = null;
    public $image = null;

    // Tag properties
    public array $selectedTags = [];
    public string $newTagName = '';
    public string $newTagColor = 'blue';
    public array $availableTags = [];

    // user-entered
    public ?string $km = null;
    public ?string $price = null;

    public function mount(): void
    {
        $this->availableTags = TagModel::all()->toArray();
    }

    // Helper to pick the first available RDW key from a list
    private function firstFrom(array $vehicle, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (isset($vehicle[$k]) && $vehicle[$k] !== '') {
                return trim((string)$vehicle[$k]);
            }
        }
        return null;
    }

    // Normalise number-like strings to integer (strip non-digits)
    private function intFromString(?string $value): ?int
    {
        if ($value === null) return null;
        $digits = preg_replace('/[^0-9-]/', '', $value);
        if ($digits === '' || $digits === '-') return null;
        return intval($digits);
    }

    // Convert PK to kW when needed (1 PK ≈ 0.7355 kW)
    private function convertPkToKw(?string $pkStr): ?int
    {
        if ($pkStr === null) return null;
        $pk = $this->intFromString($pkStr);
        if ($pk === null) return null;
        return (int)round($pk * 0.7355);
    }

    public function addTag(int $tagId): void
    {
        if (count($this->selectedTags) >= 5) return;
        if (!in_array($tagId, $this->selectedTags)) {
            $this->selectedTags[] = $tagId;
        }
    }

    public function removeTag(int $tagId): void
    {
        $this->selectedTags = array_values(
            array_filter($this->selectedTags, fn($id) => $id !== $tagId)
        );
    }

    public function createTag(): void
    {
        $this->validate([
            'newTagName' => 'required|string|max:30|unique:car_tags,name',
        ], [
            'newTagName.required' => 'Vul een tagnaam in.',
            'newTagName.unique' => 'Deze tag bestaat al.',
            'newTagName.max' => 'Naam mag maximaal 30 tekens zijn.',
        ]);

        // Enforce max 5 tags per car (should not happen due to UI, but just in case)
        if (count($this->selectedTags) >= 5) return;

        $tag = TagModel::create([
            'name' => strtolower(trim($this->newTagName)),
            'color' => $this->newTagColor,
        ]);

        $this->availableTags[] = $tag->toArray();
        $this->selectedTags[] = $tag->id;
        $this->newTagName = '';
    }

    public function step1Submit(): void
    {
        // Check if plate is not empty
        $this->validate([
            'plate' => 'required'
        ], [
            'plate.required' => 'Vul een kenteken in.'
        ]);

        // Remove all non-alphanumeric characters and convert to uppercase
        $this->plate = strtoupper(
            preg_replace('/[^A-Z0-9]/', '', $this->plate)
        );

        // Validate the plate format (5 or 6 alphanumeric characters)
        if (!preg_match('/^[A-Z0-9]{5,6}$/', $this->plate)) {
            throw ValidationException::withMessages([
                'plate' => 'Ongeldig Nederlands kenteken formaat.',
            ]);
        }

        if (CarModel::where('license_plate', $this->plate)->whereNull('sold_at')->exists()) {
            throw ValidationException::withMessages([
                'plate' => 'Er bestaat al een actief aanbod met dit kenteken.',
            ]);
        }

        // Validate the plate against the RDW API
        try {
            $response = Http::timeout(5)->get(
                'https://opendata.rdw.nl/resource/m9d7-ebf2.json',
                ['kenteken' => $this->plate]
            );

            if (!$response->successful()) {
                throw ValidationException::withMessages([
                    'plate' => 'RDW service is momenteel niet bereikbaar.',
                ]);
            }

            $data = $response->json();

            if (empty($data)) {
                throw ValidationException::withMessages([
                    'plate' => 'Dit kenteken bestaat niet in het RDW register.',
                ]);
            }

            $vehicle = $data[0];

            // Map RDW fields into component properties (use several fallbacks)
            $this->brand = $this->firstFrom($vehicle, ['merk', 'merknaam', 'merk_nm']);
            $this->model = $this->firstFrom($vehicle, ['handelsbenaming', 'model', 'type', 'handelsbenaming_nm']);
            $this->variant = $this->firstFrom($vehicle, ['uitvoering', 'variant', 'handelsbenaming_toevoeging']);
            $this->body_type = $this->firstFrom($vehicle, ['carrosserieomschrijving', 'voertuigsoort', 'carrosserie']);
            $this->fuel_type = $this->firstFrom($vehicle, ['brandstof_omschrijving', 'brandstof', 'brandstof_omschrijving_nm']);
            $this->transmission = $this->firstFrom($vehicle, ['transmissie', 'versnellingsbak']);

            // Year / first registration
            $firstRegRaw = $this->firstFrom($vehicle, ['datum_eerste_toelating', 'eerste_afgifte', 'datum_eerste_afgifte']);
            if ($firstRegRaw) {
                $ts = strtotime($firstRegRaw);
                if ($ts !== false) {
                    $this->first_registration = date('Y-m-d', $ts);
                    $this->year = (int)date('Y', $ts);
                } else {
                    // try extracting a 4-digit year
                    if (preg_match('/(19|20)\d{2}/', $firstRegRaw, $m)) {
                        $this->year = (int)$m[0];
                        $this->first_registration = null;
                    }
                }
            } else {
                // fallback for year field
                $maybeYear = $this->firstFrom($vehicle, ['bouwjaar', 'jaar']);
                $this->year = $this->intFromString($maybeYear);
            }

            // Seats, doors
            $this->seats = $this->intFromString($this->firstFrom($vehicle, ['aantal_zitplaatsen', 'aantal_zitplaatsen_normaal', 'zitplaatsen']));
            $this->doors = $this->intFromString($this->firstFrom($vehicle, ['aantal_deuren', 'deuren', 'aantal_deuren_voertuig']));

            // Curb weight
            $this->curb_weight = $this->intFromString($this->firstFrom($vehicle, ['massa_rijklaar', 'massa_rijklaar_kg', 'massa_ledig']));

            // Color
            $colorRaw = $this->firstFrom($vehicle, ['eerste_kleur', 'kleur', 'kleur_buiten', 'kleur_omschrijving']);
            if ($colorRaw) {
                // pick first color if list
                $parts = preg_split('/[,;\/\|]/', $colorRaw);
                $c = trim($parts[0]);
                $c = preg_replace('/\bmetallic\b/i', '', $c);
                $this->color = trim($c);
            }

            // Power (kW or PK)
            $kwRaw = $this->firstFrom($vehicle, ['vermogen_kw', 'vermogen', 'vermogen_netto_kw']);
            $pkRaw = $this->firstFrom($vehicle, ['vermogen_pk', 'vermogen_pk_netto', 'vermogen_pk_bruto']);
            if ($kwRaw) {
                $this->power_kw = $this->intFromString($kwRaw);
            } elseif ($pkRaw) {
                $this->power_kw = $this->convertPkToKw($pkRaw);
            }

            // CO2
            $this->co2 = $this->intFromString($this->firstFrom($vehicle, ['co2_uitstoot', 'co2']));

            // VIN / chassis
            $this->vin = $this->firstFrom($vehicle, ['chassisnummer', 'voertuig_identificatienummer', 'vin']);
            if ($this->vin) {
                $this->vin = strtoupper(preg_replace('/\s+/', '', $this->vin));
            }

            $this->step = 2;

        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'plate' => $e->getMessage(),
            ]);
        }
    }

    public function step2Submit()
    {
        $rules = [
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'nullable|integer|min:1886|max:' . date('Y'),
            'seats' => 'nullable|integer|min:1|max:99',
            'doors' => 'nullable|integer|min:1|max:9',
            'curb_weight' => 'nullable|integer|min:50|max:10000',
            'color' => 'nullable|string|max:50',
            'km' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'vin' => 'nullable|string|max:32',
            'image' => 'nullable|image|max:5120'
        ];

        $messages = [
            'brand.required' => 'Vul het merk in.',
            'model.required' => 'Vul het model in.',
            'year.integer' => 'Gebruik een geldig jaartal.',
            'year.min' => 'Het jaar lijkt onjuist.',
            'year.max' => 'Het jaar kan niet in de toekomst liggen.',
            'seats.integer' => 'Aantal zitplaatsen moet een heel getal zijn.',
            'doors.integer' => 'Aantal deuren moet een heel getal zijn.',
            'curb_weight.integer' => 'Geef een geldig gewicht in (kg).',
            'km.required' => 'Vul de kilometerstand in.',
            'km.numeric' => 'Kilometerstand moet een getal zijn.',
            'km.min' => 'Kilometerstand kan niet negatief zijn.',
            'price.required' => 'Vul de vraagprijs in.',
            'price.numeric' => 'Vraagprijs moet een getal zijn.',
            'price.min' => 'Vraagprijs kan niet negatief zijn.',
            'image.image' => 'Het bestand moet een afbeelding zijn.',
            'image.max'   => 'De afbeelding mag maximaal 5 MB zijn.',
        ];

        $validated = $this->validate($rules, $messages);

        // Normalize input
        if (isset($validated['year'])) $this->year = (int)$validated['year'];
        if (isset($validated['seats'])) $this->seats = (int)$validated['seats'];
        if (isset($validated['doors'])) $this->doors = (int)$validated['doors'];
        if (isset($validated['curb_weight'])) $this->curb_weight = (int)$validated['curb_weight'];
        if (isset($validated['km'])) $this->km = (string)$validated['km'];
        if (isset($validated['price'])) $this->price = (string)$validated['price'];

        $this->step = 3;
    }

    public function step3Submit(): void
    {
        // Persist the car and a default tag and link them. Wrap in a DB transaction.
        try {
            DB::transaction(function () {
                $user = Auth::user();

                if (!$user) {
                    $this->redirect('/login');
                    return;
                }

                $car = CarModel::create([
                    'user_id' => $user->id,
                    'license_plate' => $this->plate,
                    'make' => $this->brand ?? '',
                    'model' => $this->model ?? '',
                    'price' => (float)$this->price,
                    'mileage' => (int)$this->km,
                    'seats' => $this->seats,
                    'doors' => $this->doors,
                    'production_year' => $this->year,
                    'weight' => $this->curb_weight,
                    'color' => $this->color,
                    'image' => $this->image ? $this->image->store('cars', 'public') : null,
                ]);

                foreach ($this->selectedTags as $tagId) {
                    CarTagModel::create([
                        'car_id' => $car->id,
                        'tag_id' => $tagId,
                    ]);
                }
            });

            $this->redirect(route('user.listings'));

        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'general' => 'Kon het aanbod niet opslaan: ' . $e->getMessage(),
            ]);
        }
    }

    public function previous(): void
    {
        $this->step--;
    }

}; ?>

@section('title', 'Nieuw aanbod')

<div>
    @if ($this->step === 1)
        <div class="create-listing-step-1">
            <h1>Vul je kenteken in</h1>
            <h2>Klik daarna op 'GO' om verder te gaan</h2>

            <form wire:submit.prevent="step1Submit" novalidate>
                <div class="form-group">
                    <div>
                        <p>NL</p>
                    </div>
                    <div>
                        <input
                            id="plate-input"
                            type="text"
                            placeholder="AA-BB-12"
                            maxlength="8"
                            wire:model="plate"
                            aria-describedby="plate-error"
                        />
                    </div>
                    <div>
                        <button type="submit">GO!</button>
                    </div>
                </div>

                @error('plate')
                <p id="plate-error" class="error-message visible">{{ $message }}</p>
                @enderror
            </form>
            <div>
                <p>{{ $this->step }}/3</p>
            </div>
        </div>
    @elseif ($this->step === 2)
        <div class="create-listing-step-2">
            <div class="header-row">
                <button type="button" wire:click="previous" class="back-btn" aria-label="Vorige stap">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                              stroke-linejoin="round"/>
                    </svg>
                </button>
                <h1>Nieuw aanbod</h1>
            </div>
            <div class="plate">
                <p>NL</p>
                <p>{{ $this->plate }}</p>
            </div>

            <form wire:submit.prevent="step2Submit">
                <div class="form-group">
                    <div class="form-item">
                        <label for="brand">Merk</label>
                        <input type="text" id="brand" wire:model.defer="brand"/>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-item">
                        <label for="model">Model</label>
                        <input type="text" id="model" wire:model.defer="model"/>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-item">
                        <label for="spaces">Zitplaatsen</label>
                        <input type="number" id="spaces" wire:model.defer="seats"/>
                    </div>

                    <div class="form-item">
                        <label for="doors">Aantal deuren</label>
                        <input type="number" id="doors" wire:model.defer="doors"/>
                    </div>

                    <div class="form-item">
                        <label for="curbWeight">Massa rijklaar</label>
                        <input type="number" id="curbWeight" name="curbWeight" wire:model.defer="curb_weight"/>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-item">
                        <label for="year">Jaar van productie</label>
                        <input type="number" id="year" name="year" wire:model.defer="year"/>
                    </div>

                    <div class="form-item">
                        <label for="color">Kleur</label>
                        <input type="text" id="color" name="color" wire:model.defer="color"/>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-item">
                        <label for="km">Kilometerstand</label>
                        <div class="boxed-input measure-box">
                            <input type="number" id="km" name="km" wire:model.defer="km"/>
                            <span class="unit">km</span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-item">
                        <label for="price">Vraagprijs</label>
                        <div class="boxed-input money-box">
                            <span class="currency">€</span>
                            <input type="number" id="price" name="price" wire:model.defer="price"/>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-item" style="grid-column: 1 / -1;">
                        <label for="image">Afbeelding</label>
                        <div class="image-upload-box" id="image-drop-zone">
                            @if ($image)
                                <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="image-preview"/>
                                <button type="button" wire:click="$set('image', null)" class="image-remove-btn" aria-label="Afbeelding verwijderen">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                    Verwijderen
                                </button>
                            @else
                                <label for="image" class="image-upload-label">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    <span>Klik om een foto te uploaden</span>
                                    <span class="image-upload-hint">JPG, PNG, WEBP — max. 5 MB</span>
                                    <input type="file" id="image" wire:model="image" accept="image/*" class="image-file-input"/>
                                </label>
                            @endif
                        </div>
                        @error('image') <p class="error-message visible">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-group">
                    @if ($errors->any())
                        <p class="error-message visible">{{ $errors->first() }}</p>
                    @endif
                </div>

                <div class="form-group">
                    <input type="submit" class="btn-primary" value="Volgende stap"/>
                </div>
            </form>
            <div class="step-counter">
                <p>{{ $this->step }}/3</p>
            </div>
        </div>
    @else
        <div class="create-listing-step-3">
            <div class="header-row">
                <button type="button" wire:click="previous" class="back-btn" aria-label="Vorige stap">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                              stroke-linejoin="round"/>
                    </svg>
                </button>
                <h1>Voeg tags toe</h1>
            </div>

            <div class="plate">
                <p>NL</p>
                <p>{{ $this->plate }}</p>
            </div>

            {{-- Geselecteerde tags --}}
            <div class="tag-section">
                <div class="tag-section-header">
                    <span class="tag-section-label">Geselecteerde tags</span>
                    <span class="tag-count {{ count($this->selectedTags) >= 5 ? 'tag-count--full' : '' }}">
                    {{ count($this->selectedTags) }}/5
                </span>
                </div>

                <div class="selected-tags">
                    @forelse ($this->selectedTags as $selectedId)
                        @php
                            $tag = collect($this->availableTags)->firstWhere('id', $selectedId);
                        @endphp
                        @if ($tag)
                            <div class="tag-chip tag-chip--selected tag-color--{{ $tag['color'] }}">
                                <span>{{ $tag['name'] }}</span>
                                <button type="button" wire:click="removeTag({{ $tag['id'] }})" class="tag-remove"
                                        aria-label="Verwijder tag">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                         stroke-linecap="round">
                                        <path d="M18 6L6 18M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    @empty
                        <p class="tag-empty">Nog geen tags geselecteerd.</p>
                    @endforelse
                </div>
            </div>

            {{-- Beschikbare tags --}}
            @if (count($this->availableTags) > 0)
                <div class="tag-section">
                    <div class="tag-section-header">
                        <span class="tag-section-label">Beschikbare tags</span>
                    </div>
                    <div class="available-tags">
                        @foreach ($this->availableTags as $tag)
                            @php $isSelected = in_array($tag['id'], $this->selectedTags); @endphp
                            <button
                                type="button"
                                wire:click="addTag({{ $tag['id'] }})"
                                class="tag-chip tag-color--{{ $tag['color'] }} {{ $isSelected ? 'tag-chip--active' : '' }}"
                                {{ ($isSelected || count($this->selectedTags) >= 5) ? 'disabled' : '' }}
                                aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                            >
                                {{ $tag['name'] }}
                                @if ($isSelected)
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                         stroke-linecap="round" stroke-linejoin="round"
                                         style="width:13px;height:13px;margin-left:4px">
                                        <path d="M20 6L9 17l-5-5"/>
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Nieuwe tag aanmaken --}}
            @if (count($this->selectedTags) < 5)
                <div class="tag-section">
                    <div class="tag-section-header">
                        <span class="tag-section-label">Nieuwe tag aanmaken</span>
                    </div>
                    <div class="new-tag-row">
                        <div class="boxed-input" style="flex:1">
                            <input
                                type="text"
                                placeholder="tagnaam..."
                                wire:model.defer="newTagName"
                                maxlength="30"
                            />
                        </div>
                        <select wire:model="newTagColor" class="color-select">
                            <option value="blue">🔵 Blauw</option>
                            <option value="yellow">🟡 Geel</option>
                            <option value="green">🟢 Groen</option>
                            <option value="red">🔴 Rood</option>
                            <option value="gray">⚪ Grijs</option>
                        </select>
                        <button type="button" wire:click="createTag" class="btn-secondary">
                            + Aanmaken
                        </button>
                    </div>
                    @error('newTagName')
                    <p class="error-message visible" style="margin-top:.5rem">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <form wire:submit.prevent="step3Submit">
                <div class="form-group" style="margin-top: 1rem">
                    @error('general')
                    <p class="error-message visible">{{ $message }}</p>
                    @enderror
                    <input type="submit" class="btn-primary" value="Aanbod afronden"/>
                </div>
            </form>

            <div class="step-counter">
                <p>{{ $this->step }}/3</p>
            </div>
        </div>
    @endif
</div>
