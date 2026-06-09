<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $modules = Module::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->when($request->order_by, function ($query) use ($request) {
                $orderByColumn = $request->order_by; // column name to sort by
                $orderByDirection = $request->order_direction ?? 'asc'; // default to ascending if not specified

                $query->orderBy($orderByColumn, $orderByDirection);
            });

        if ($request->has('sync')) {
            // Return all results if 'sync' is present
            $modules->where('updated_at', '>', $request->sync);
            return response()->json($modules->get());
        } else {
            // Apply pagination with perPage
            return response()->json($modules->paginate($request->perPage ?? 15)); // default to 15 per page
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required',
            'fe_path' => 'required_if:type,pages'
        ]);

        try {
            $data = $request->only(['name', 'type', 'permission_id','fe_path']);
            $newModule = Module::create($data);

            return response()->json($newModule,201);
        } catch (\Throwable $th) {
           return response()->json([
                'message' => $th->getMessage(),
           ],500);
        }
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
        $request->validate([
            'name' => 'required',
            'type' => 'required'
        ]);

        try {
            $module = Module::findOrFail($id);

            $data = $request->only(['name', 'type', 'permission_id', 'fe_path']);
            $module->fill($data);
            $module->save();

            return response()->json($module, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
            ],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $module = Module::findOrFail($id);
            $module->delete();

            return response()->json($module, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
            ],200);
        }
    }
}
