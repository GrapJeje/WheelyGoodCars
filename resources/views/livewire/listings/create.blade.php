<?php

use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.default')]
class extends Component {
    public string $plate = '';
    public int $step = 1;

    public function submit(): void
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
            $this->step = 2;

        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'plate' => $e->getMessage(),
            ]);
        }
    }

}; ?>

<div>
    @if ($this->step === 1)
        <div class="create-listing">
            <h1>Vul je kenteken in</h1>
            <h2>Klik daarna op 'GO' om verder te gaan</h2>

            <form wire:submit.prevent="submit" novalidate>
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
        <p>STAP 2</p>
    @endif
</div>
