<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OngProfile;
use App\Models\Ong;

class OngProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $ong = auth('ong')->user();
        
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,|max:2048',
            'phone' => 'required|string|max:20',
        ]);

        $image = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image')->store('logos', 'public');
            
        }

        $ongProfile = OngProfile::create([
            'ong_id' => $ong->ong_id,
            'description' => $request->description,
            'image' => $image,
            'phone' => $request->phone,
        ]);

        return response()->json(['ongProfile' => $ongProfile], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
