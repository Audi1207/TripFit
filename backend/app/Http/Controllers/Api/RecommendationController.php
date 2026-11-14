<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecommendationController extends Controller
{
    public function recommend(
        Request $request,
        RecommendationService $recommendationService
    ) {
        $validated = $request->validate([
            'region_name' => ['required', 'string'],

            'selected_categories' => ['required', 'array', 'min:1', 'max:3'],
            'selected_categories.*' => [
                'required',
                'string',
                Rule::in([
                    'Alam & Pegunungan',
                    'Pantai & Bahari',
                    'Budaya & Sejarah',
                    'Kuliner',
                    'Hiburan & Rekreasi',
                    'Religi',
                ]),
            ],

            'max_budget' => ['required', 'integer', 'min:0'],

            'duration_level' => [
                'required',
                Rule::in(['Short', 'Medium', 'Long']),
            ],

            'result_count' => ['sometimes', 'integer', 'min:1', 'max:5'],
        ]);

        $recommendations = $recommendationService->getRecommendations(
            $validated['region_name'],
            $validated['selected_categories'],
            (int) $validated['max_budget'],
            $validated['duration_level'],
            (int) ($validated['result_count'] ?? 5)
        );

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi destinasi berhasil diperoleh.',
            'data' => $recommendations,
        ]);
    }
}
