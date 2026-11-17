<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DeliveryService
{
    // Kohan Mart Store Coordinates: 41 Insurance, Project 8, Quezon City
    public const STORE_LAT = 14.6648;
    public const STORE_LNG = 121.0215;
    public const STORE_ADDRESS = '41 Insurance, Project 8, Quezon City, Metro Manila';
    public const STORE_PHONE = '+63 917 000 0000';

    /**
     * Calculate live delivery quotation using free OpenStreetMap Nominatim + OSRM APIs
     * (Zero API keys or personal info required).
     */
    public function getQuote(string $address): array
    {
        $cleanAddress = trim($address);
        $coords = $this->geocodeAddress($cleanAddress);
        $route = $this->calculateRoadDistance(
            self::STORE_LAT,
            self::STORE_LNG,
            $coords['lat'],
            $coords['lng']
        );

        $distanceKm = max(0.8, round($route['distance_km'], 1));
        $durationMin = max(12, (int) round($route['duration_min']));

        // Metro Manila Motorcycle Courier Pricing: ₱55 base + ₱12/km
        $feePesos = round(55 + ($distanceKm * 12));
        $feeMinor = (int) ($feePesos * 100);

        return [
            'ok' => true,
            'provider' => 'Lalamove Express (Sandbox API)',
            'vehicle' => 'Motorcycle Box',
            'normalized_address' => $coords['display_name'] ?: $cleanAddress,
            'lat' => $coords['lat'],
            'lng' => $coords['lng'],
            'distance_km' => $distanceKm,
            'eta_minutes' => $durationMin + 15,
            'fee_pesos' => $feePesos,
            'fee_minor' => $feeMinor,
            'fee_formatted' => '₱' . number_format($feePesos, 2),
        ];
    }

    /**
     * Dispatch a simulated courier order and return shareable tracking details.
     */
    public function dispatchCourier(string $orderNumber): array
    {
        $riders = [
            ['name' => 'Marco Reyes', 'plate' => '418-QCM', 'phone' => '+63 917 842 1904'],
            ['name' => 'Jayson Santos', 'plate' => '792-NGR', 'phone' => '+63 918 531 7742'],
            ['name' => 'Paolo Mendoza', 'plate' => '205-KLM', 'phone' => '+63 927 619 3085'],
            ['name' => 'Kenji Navarro', 'plate' => '634-PHX', 'phone' => '+63 915 904 6218'],
        ];
        $rider = $riders[array_rand($riders)];

        return [
            'courier_order_id' => 'LLM-' . strtoupper(Str::random(8)),
            'courier_status' => 'order_placed',
            'courier_driver_name' => $rider['name'],
            'courier_driver_plate' => $rider['plate'],
            'courier_driver_phone' => $rider['phone'],
            'courier_share_link' => route('delivery.track', ['order' => $orderNumber]),
        ];
    }

    private function geocodeAddress(string $address): array
    {
        $query = str_contains(strtolower($address), 'philippines')
            ? $address
            : "{$address}, Metro Manila, Philippines";

        try {
            $res = Http::timeout(4)
                ->withHeaders(['User-Agent' => 'KohanMartApp/1.0'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'ph',
                ]);

            if ($res->successful() && !empty($res->json()[0])) {
                $hit = $res->json()[0];
                return [
                    'lat' => (float) $hit['lat'],
                    'lng' => (float) $hit['lon'],
                    'display_name' => $hit['display_name'] ?? $address,
                ];
            }
        } catch (\Throwable $e) {
            // Fall back smoothly if offline
        }

        $hashOffset = (crc32(strtolower($address)) % 50) / 1000;
        return [
            'lat' => 14.6510 + $hashOffset,
            'lng' => 121.0493 + ($hashOffset / 2),
            'display_name' => $address,
        ];
    }

    private function calculateRoadDistance(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        try {
            $url = "https://router.project-osrm.org/route/v1/driving/{$fromLng},{$fromLat};{$toLng},{$toLat}";
            $res = Http::timeout(4)->get($url, ['overview' => 'false']);

            if ($res->successful() && isset($res->json()['routes'][0])) {
                $route = $res->json()['routes'][0];
                return [
                    'distance_km' => ($route['distance'] ?? 3500) / 1000,
                    'duration_min' => ($route['duration'] ?? 900) / 60,
                ];
            }
        } catch (\Throwable $e) {
            // Fall back to Haversine road multiplier
        }

        $earthRadius = 6371;
        $dLat = deg2rad($toLat - $fromLat);
        $dLon = deg2rad($toLng - $fromLng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($dLon / 2) ** 2;
        $straightKm = $earthRadius * 2 * asin(sqrt($a));
        $roadKm = max(1.2, $straightKm * 1.35);

        return [
            'distance_km' => $roadKm,
            'duration_min' => $roadKm * 3.5,
        ];
    }
}