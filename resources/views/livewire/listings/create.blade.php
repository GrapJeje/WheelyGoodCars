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

new #[Layout('layouts.default')]
class extends Component {
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
    public ?string $notes = null;

    // user-entered
    public ?string $km = null;
    public ?string $price = null;

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

            // Notes: keep a JSON dump of the raw vehicle for debugging (short)
            $this->notes = substr(json_encode($vehicle), 0, 1000);

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
        ];

        $validated = $this->validate($rules, $messages);

        // Normalize input
        if (isset($validated['year'])) $this->year = (int)$validated['year'];
        if (isset($validated['seats'])) $this->seats = (int)$validated['seats'];
        if (isset($validated['doors'])) $this->doors = (int)$validated['doors'];
        if (isset($validated['curb_weight'])) $this->curb_weight = (int)$validated['curb_weight'];
        if (isset($validated['km'])) $this->km = (string)$validated['km'];
        if (isset($validated['price'])) $this->price = (string)$validated['price'];

        // Persist the car and a default tag and link them. Wrap in a DB transaction.
        try {
            DB::transaction(function () {
                $user = Auth::user();

                if ($user == null) {
                    $this->redirect('/login');
                    return;
                }

                $car = CarModel::create([
                    'user_id' => $user ? $user->id : null,
                    'license_plate' => $this->plate,
                    'make' => $this->brand ?? '',
                    'model' => $this->model ?? '',
                    'price' => isset($this->price) ? (float)str_replace([',', '€', ' '], ['', '', ''], $this->price) : 0.0,
                    'mileage' => isset($this->km) ? (int)preg_replace('/[^0-9]/', '', $this->km) : 0,
                    'seats' => $this->seats,
                    'doors' => $this->doors,
                    'production_year' => $this->year,
                    'weight' => $this->curb_weight,
                    'color' => $this->color,
                ]);

                // TEMP
                $tag = TagModel::firstOrCreate(
                    ['name' => 'for-sale'],
                    ['color' => 'blue']
                );

                CarTagModel::create([
                    'car_id' => $car->id,
                    'tag_id' => $tag->id,
                ]);

                // TODO: Change to listings page
                $this->redirect('/');
            });
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'general' => 'Kon het aanbod niet opslaan: ' . $e->getMessage(),
            ]);
        }

    }

    public function previous(): void
    {
        $this->step = 1;
    }

}; ?>

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
                <p>{{ $this->step }}/2</p>
            </div>
        </div>
    @else
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
                    @if ($errors->any())
                        <p class="error-message visible">{{ $errors->first() }}</p>
                    @endif
                </div>

                <div class="form-group">
                    <input type="submit" class="btn-primary" value="Aanbod afronden"/>
                </div>
            </form>
            <div class="step-counter">
                <p>{{ $this->step }}/2</p>
            </div>
        </div>
    @endif
</div>
