<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LocationService
{
    public function fillCoordinates(User $user): void
    {
        if (! $user->city || ($user->lat !== null && $user->lng !== null)) {
            return;
        }

        $city = trim($user->city);
        $coordinates = Cache::remember('book2book.city.'.mb_strtolower($city), now()->addDays(30), function () use ($city): ?array {
            try {
                $result = Http::acceptJson()
                    ->withHeaders(['User-Agent' => 'Book2Book/1.0 (book2book.pt)'])
                    ->timeout(5)
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $city.', Portugal',
                        'format' => 'jsonv2',
                        'limit' => 1,
                        'countrycodes' => 'pt',
                    ])
                    ->json('0');
            } catch (\Throwable) {
                return null;
            }

            return is_array($result) && isset($result['lat'], $result['lon'])
                ? [(float) $result['lat'], (float) $result['lon']]
                : null;
        });

        if ($coordinates) {
            [$user->lat, $user->lng] = $coordinates;
            $user->save();
        }
    }
}
