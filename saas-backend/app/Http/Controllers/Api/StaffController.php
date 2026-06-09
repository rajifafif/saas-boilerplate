<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\StaffResource;
use App\Models\Staff;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $staffs = Staff::query()
            ->when($request->q, function($query, $search) {
                $query->whereHas('person', function($person) use ($search) {
                    $person->where('name', 'like', '%'.$search.'%');
                });
            })
            ->withTrashed()
            ->when($request->status, function($query, $status) {
                if( $status == 'active' ) {
                    $query->whereNull('deleted_at');
                }

                if ($status == 'inactive') {
                    $query->whereNotNull('deleted_at');
                }
            })
            ->paginate($request->per_page ?? 10);

        return Helper::paginatedResource($staffs, StaffResource::collection($staffs));
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
        //
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
        $staff = Staff::findOrFail($id);

        return $staff->delete();
    }
}
