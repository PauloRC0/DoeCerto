<?php 

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NominatimService
{
    public function getCoordinatesFromCep(string $cep): ?array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'DoeCertoTest/0.1 (teste@exemplo.com)'
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $cep,
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => 1,
            'countrycodes' => 'br', 
        ]);

        if ($response->successful() && isset($response[0]['lat'], $response[0]['lon'])) {
            return [
                'lat' => (float) $response[0]['lat'],
                'lon' => (float) $response[0]['lon'],
            ];
        }

        return null;
    }
}
