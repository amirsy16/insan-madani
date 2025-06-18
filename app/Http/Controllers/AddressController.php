<?php

namespace App\Http\Controllers;

use App\Models\Regency;
use App\Models\District;
use App\Models\Village;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function getCities(Request $request)
    {
        $provinceId = $request->input('province_id');
        $cities = Regency::where('province_id', $provinceId)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        return response()->json($cities);
    }
    
    public function getDistricts(Request $request)
    {
        $regencyId = $request->input('regency_id');
        $districts = District::where('regency_id', $regencyId)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        return response()->json($districts);
    }
    
    public function getVillages(Request $request)
    {
        $districtId = $request->input('district_id');
        $villages = Village::where('district_id', $districtId)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        return response()->json($villages);
    }
}
