<?php

namespace App\Http\Controllers\Fe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index()
    {
        $provinces = \DB::table('locations')->whereNull('parent_id')->get();

        return view('fe.profile', [
            'user' => Auth::user(),
            'provinces' => $provinces,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'province_id' => 'nullable|exists:locations,id',
            'city' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = $request->only(['name', 'phone', 'address', 'province_id', 'city', 'latitude', 'longitude']);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $path = $request->file('photo')->store('profiles', 'public');
            $data['photo'] = $path;
        }

        $user->update($data);

        return back()->with('success', 'IDENTITY PROFILE UPDATED.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'ACCESS KEY REGENERATED.');
    }
}
