<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return response()->json(UserResource::make($user));
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'The provided password does not match our records.',
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'nullable',
            'gender' => 'nullable',
            'birth_date' => 'nullable|date',
            'address' => 'nullable',
            'emergency_name' => 'nullable',
            'emergency_phone' => 'nullable',
            'emergency_relation' => 'nullable',
        ]);

        $user = Auth::user();

        $person = $user->person;
        if ($person) {
            $person->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'birth_date' => $request->birth_date,
                'emergency_contact_name' => $request->emergency_name,
                'emergency_contact_phone' => $request->emergency_phone,
                'emergency_contact_relation' => $request->emergency_relation,
            ]);

            if ($request->has('address')) {
                $address = $person->defaultAddress;
                if ($address) {
                    $address->update(['text' => $request->address]);
                } else {
                    $person->defaultAddress()->create([
                        'text' => $request->address,
                        'type' => 'home'
                    ]);
                }
            }
        }

        return response()->json(UserResource::make($user));
    }

}
