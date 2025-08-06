<?php

namespace App\Http\Controllers;

use App\Models\Donor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class DonorController extends Controller
{
    public function index()
    {
        return Donor::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'don_name'         => 'required|string|max:255',
            'don_email'        => 'required|email|unique:donors,don_email',
            'don_password'     => 'required|string|min:8',
            'don_description'  => 'nullable|string|max:255',
            'don_image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'don_phone'        => 'nullable|string|max:20',
            'don_cep'          => 'nullable|string|max:10',
            'don_houseNumber'  => 'nullable|string|max:10',
            'don_complement'   => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('don_image')) {
            $image = $request->file('don_image');
            $path = $image->store('Donor', 'public');
            $data['don_image'] = $path;
        }

        $data['don_password'] = bcrypt($data['don_password']);

        return Donor::create($data);
    }


    public function show($id)
    {
        return Donor::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $donor = Donor::findOrFail($id);

        $data = $request->validate([
            'don_name'         => 'sometimes|required|string|max:255',
            'don_email'        => 'sometimes|required|email|unique:donors,don_email,' . $id . ',id',
            'don_password'     => 'sometimes|required|string|min:8',
            'don_description'  => 'nullable|string|max:255',
            'don_image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'don_phone'        => 'nullable|string|max:20',
            'don_cep'          => 'nullable|string|max:10',
            'don_houseNumber'  => 'nullable|string|max:10',
            'don_complement'   => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('don_image')) {
            if ($donor->don_image && Storage::disk('public')->exists($donor->don_image)) {
                Storage::disk('public')->delete($donor->don_image);
            }

            $image = $request->file('don_image');
            $path = $image->store('Donor', 'public');
            $data['don_image'] = $path;
        }

        if (isset($data['don_password'])) {
            $data['don_password'] = bcrypt($data['don_password']);
        }

    $donor->update($data);
    return $donor;
}



    public function destroy($id)
    {
        Donor::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
