<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppResource;
use App\Models\Project;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function index(Request $request)
    {
        // TODO Search based on X-PROJECT-ORIGIN header

        $project = Project::first();

        return response()->json(AppResource::make($project));
    }
}
