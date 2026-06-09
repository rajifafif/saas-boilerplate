<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KelurahanResource;
use App\Models\Kelurahan;
use App\Models\KodePos;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    public function kelurahan(Request $request)
    {
        $kelurahans = Kelurahan::search($request->search ?? '')
            ->options(['query_by' => 'full_name'])
            ->take(10)
            ->get()
            ->load(['kecamatan', 'kota', 'provinsi']);

        return KelurahanResource::collection($kelurahans);
    }

    public function kodePos($kelurahanId)
    {
        return KodePos::where('kelurahan_id', $kelurahanId)->get()->map(function ($kodePos) {
            return [
                'label' => $kodePos->code,
                'value' => $kodePos->code
            ];
        });
    }
}
