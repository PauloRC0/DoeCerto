<?php

namespace App\Http\Controllers;

use App\Models\Ong;
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
}