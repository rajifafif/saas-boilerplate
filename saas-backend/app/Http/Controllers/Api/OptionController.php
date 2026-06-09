<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OptionController extends Controller
{
    public function prefix()
    {
        $prefixes = collect(config('option.prefixes'));
        return $prefixes->map(function ($prefix) {
            return ['label' => $prefix, 'value' => $prefix];
        });
    }

    public function suffix()
    {
        $suffixes = collect(config('option.suffixes'));
        return $suffixes->map(function ($suffix) {
            return ['label' => $suffix, 'value' => $suffix];
        });
    }

    public function roles()
    {
        $user = Auth::user();
        $project = $user->project;

        $roles = $project->roles()->where('guard_name', 'web')
            ->get()
            ->map(function ($role) {
                return [
                    'label' => $role->name,
                    'value' => $role->id,
                ];
            });

        return $roles;
    }
}
