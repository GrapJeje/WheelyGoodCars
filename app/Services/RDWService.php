<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RDWService
{
    private const RDW_URL = 'https://opendata.rdw.nl/resource/m9d7-ebf2.json';
    private const CACHE = 3600;

    // Fetch vehicle by license plate
    public static function getByPlate(string $plate): ?array
    {
        $plate = strtoupper(preg_replace('/[^A-Z0-9]/', '', $plate));

        return Cache::remember("rdw_plate_{$plate}", self::CACHE, function () use ($plate) {
            try {
                $response = Http::timeout(5)
                    ->get(self::RDW_URL, ['kenteken' => $plate]);

                if ($response->successful() && !empty($response->json()))
                    return self::mapVehicle($response->json()[0]);

            } catch (\Exception $e) {
                logger()->warning('RDW API error: ' . $e->getMessage());
            }
            return null;
        });
    }

    // Fetch multiple vehicles with pagination
    public static function getVehicles(int $limit = 250, int $offset = 0): array
    {
        return Cache::remember("rdw_vehicles_{$limit}_{$offset}", self::CACHE, function () use ($limit, $offset) {
            try {
                $response = Http::timeout(10)
                    ->get(self::RDW_URL, [
                        '$limit'  => $limit,
                        '$offset' => $offset,
                        '$order'  => 'kenteken ASC',
                    ]);

                if ($response->successful())
                    return array_map(fn($v) => self::mapVehicle($v), $response->json() ?? []);

            } catch (\Exception $e) {
                logger()->warning('RDW API error: ' . $e->getMessage());
            }
            return [];
        });
    }

    private static function mapVehicle(array $v): array
    {
        // Derive production year from first registration date or fallback field
        $year = null;
        $firstReg = $v['datum_eerste_toelating'] ?? $v['eerste_afgifte'] ?? null;
        if ($firstReg) {
            $ts = strtotime($firstReg);
            $year = $ts !== false ? (int)date('Y', $ts) : null;
        }
        if (!$year && isset($v['bouwjaar']))
            $year = (int)$v['bouwjaar'];

        // Strip metallic suffix and take the first color if multiple are listed
        $colorRaw = $v['eerste_kleur'] ?? $v['kleur'] ?? null;
        $color = $colorRaw
            ? trim(preg_replace('/\bmetallic\b/i', '', explode(',', $colorRaw)[0]))
            : null;

        // Prefer kW directly, otherwise convert from PK
        $powerKw = null;
        if (!empty($v['vermogen_kw']))
            $powerKw = (int)preg_replace('/[^0-9]/', '', $v['vermogen_kw']);
        elseif (!empty($v['vermogen_pk']))
            $powerKw = (int)round((int)preg_replace('/[^0-9]/', '', $v['vermogen_pk']) * 0.7355);

        return [
            'license_plate'      => $v['kenteken']               ?? null,
            'make'               => $v['merk']                   ?? null,
            'model'              => $v['handelsbenaming']         ?? $v['type'] ?? null,
            'variant'            => $v['uitvoering']              ?? null,
            'body_type'          => $v['carrosserieomschrijving'] ?? $v['voertuigsoort'] ?? null,
            'fuel_type'          => $v['brandstof_omschrijving']  ?? null,
            'transmission'       => $v['transmissie']             ?? null,
            'production_year'    => $year,
            'first_registration' => $firstReg && strtotime($firstReg)
                ? date('Y-m-d', strtotime($firstReg))
                : null,
            'seats'              => isset($v['aantal_zitplaatsen']) ? (int)$v['aantal_zitplaatsen'] : null,
            'doors'              => isset($v['aantal_deuren'])      ? (int)$v['aantal_deuren']      : null,
            'color'              => $color,
            'weight'             => isset($v['massa_rijklaar'])     ? (int)$v['massa_rijklaar']     : null,
            'power_kw'           => $powerKw,
            'co2'                => isset($v['co2_uitstoot'])       ? (int)$v['co2_uitstoot']       : null,
            'vin'                => isset($v['chassisnummer'])
                ? strtoupper(preg_replace('/\s+/', '', $v['chassisnummer']))
                : null,
        ];
    }
}
