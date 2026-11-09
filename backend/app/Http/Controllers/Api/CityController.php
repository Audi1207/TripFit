<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;

class CityController extends Controller
{
    // Get all cities
    public function index()
    {
        $cities = City::orderBy('city_name', 'asc')->get();

        return response()->json([
            'message' => 'Cities retrieved successfully',
            'data' => $cities,
        ]);
    }

    // Get city detail
    public function show($id)
    {
        $city = City::find($id);

        if (!$city) {
            return response()->json([
                'message' => 'City not found',
            ], 404);
        }

        return response()->json([
            'message' => 'City retrieved successfully',
            'data' => $city,
        ]);
    }
}
