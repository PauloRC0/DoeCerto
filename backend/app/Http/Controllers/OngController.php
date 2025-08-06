<?php

namespace App\Http\Controllers;

use App\Models\Ong;
use App\Models\Donor; 
use App\Services\CnpjValidatorService;
use App\Services\NominatimService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OngController extends Controller
{
    protected $nominatimService;

    public function __construct(NominatimService $nominatimService)
    {
        $this->nominatimService = $nominatimService;
    }

    public function index()
    {
        return Ong::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ong_name'        => 'required|string|max:255',
            'ong_email'       => 'required|email|unique:ongs,ong_email',
            'ong_password'    => 'required|string|min:8|confirmed',
            'ong_cnpj'        => 'required|string|unique:ongs,ong_cnpj',
            'ong_cep'         => 'nullable|string|max:10',
        ]);

        if (!CnpjValidatorService::validate($validated['ong_cnpj'])) {
            return response()->json([
                'message' => 'O CNPJ informado não é válido ou não foi encontrado na Receita Federal.'
            ], 422);
        }

        if (!empty($validated['ong_cep'])) {
            $coords = $this->nominatimService->getCoordinatesFromCep($validated['ong_cep']);
            if ($coords) {
                $validated['ong_latitude'] = $coords['lat'];
                $validated['ong_longitude'] = $coords['lon'];
            }
        }

        $validated['ong_password'] = bcrypt($validated['ong_password']);

        $ong = Ong::create($validated);

        return response()->json($ong, 201);
    }

    public function show(Ong $ong)
    {
        return response()->json($ong);
    }

    public function update(Request $request, Ong $ong)
    {
        $validated = $request->validate([
            'ong_name'        => 'sometimes|required|string|max:255',
            'ong_email'       => 'sometimes|required|email|unique:ongs,ong_email,' . $ong->id,
            'ong_password'    => 'sometimes|required|string|min:8|confirmed',
            'ong_cnpj'        => 'sometimes|required|string|unique:ongs,ong_cnpj,' . $ong->id,
            'ong_cep'         => 'nullable|string|max:10',
        ]);

        if (isset($validated['ong_cnpj']) && $validated['ong_cnpj'] !== $ong->ong_cnpj) {
            if (!CnpjValidatorService::validate($validated['ong_cnpj'])) {
                return response()->json([
                    'message' => 'O CNPJ informado não é válido ou não foi encontrado na Receita Federal.'
                ], 422);
            }
        }

        if (isset($validated['ong_password'])) {
            $validated['ong_password'] = bcrypt($validated['ong_password']);
        }

        if (isset($validated['ong_cep']) && $validated['ong_cep'] !== $ong->ong_cep) {
            $coords = $this->nominatimService->getCoordinatesFromCep($validated['ong_cep']);
            if ($coords) {
                $validated['ong_latitude'] = $coords['lat'];
                $validated['ong_longitude'] = $coords['lon'];
            } else {
                $validated['ong_latitude'] = null;
                $validated['ong_longitude'] = null;
            }
        }

        $ong->update($validated);

        return response()->json($ong);
    }

    public function destroy(Ong $ong)
    {
        $ong->delete();
        return response()->json(null, 204);
    }

    public function listarOngsPorProximidade(Request $request)
    {
        $request->validate([
            'cep' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'donor_id' => 'nullable|exists:donors,id', // Novo campo: ID do doador
            'radius' => 'nullable|numeric|min:1|max:500',
        ]);

        $targetLat = null;
        $targetLon = null;

        // Prioriza o ID do doador se fornecido
        if ($request->has('donor_id')) {
            $donor = Donor::find($request->input('donor_id'));
            if ($donor && $donor->don_latitude && $donor->don_longitude) {
                $targetLat = (float) $donor->don_latitude;
                $targetLon = (float) $donor->don_longitude;
            } else {
                return response()->json(['message' => 'Doador não encontrado ou sem coordenadas registradas.'], 404);
            }
        }
        // Se não, prioriza latitude e longitude se fornecidas
        elseif ($request->has('latitude') && $request->has('longitude')) {
            $targetLat = (float) $request->input('latitude');
            $targetLon = (float) $request->input('longitude');
        }
        // Se não, tenta usar o CEP
        elseif ($request->has('cep')) {
            $targetCep = $request->input('cep');
            $targetCoords = $this->nominatimService->getCoordinatesFromCep($targetCep);
            if ($targetCoords) {
                $targetLat = (float) $targetCoords['lat'];
                $targetLon = (float) $targetCoords['lon'];
            }
        }

        if (is_null($targetLat) || is_null($targetLon)) {
            return response()->json(['message' => 'Não foi possível determinar as coordenadas de origem. Forneça um CEP válido, latitude e longitude, ou um ID de doador com coordenadas.'], 400);
        }

        $radius = $request->input('radius', 50);

        $ongs = Ong::whereNotNull('ong_latitude')
                    ->whereNotNull('ong_longitude')
                    ->get();

        $nearbyOngs = [];

        foreach ($ongs as $ong) {
            $ongLat = (float) $ong->ong_latitude;
            $ongLon = (float) $ong->ong_longitude;

            $distance = $this->haversineGreatCircleDistance(
                $targetLat, $targetLon, $ongLat, $ongLon
            );

            if ($distance <= $radius) {
                $ong->distance = round($distance, 2);
                $nearbyOngs[] = $ong;
            }
        }

        usort($nearbyOngs, function ($a, $b) {
            return $a->distance <=> $b->distance;
        });

        return response()->json($nearbyOngs);
    }

    private function haversineGreatCircleDistance(
        float $latitudeFrom, float $longitudeFrom, float $latitudeTo, float $longitudeTo, int $earthRadius = 6371000
    ): float {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return ($angle * $earthRadius) / 1000;
    }
}