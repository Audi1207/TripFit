<?php
namespace App\Services;

use App\Models\Destination;
use Illuminate\Support\Collection;

class RecommendationService
{
    /**
     * Daftar seluruh kategori TripFit.
     */
    private function getAllCategories(): array
    {
        return [
            'Alam & Pegunungan',
            'Pantai & Bahari',
            'Budaya & Sejarah',
            'Kuliner',
            'Hiburan & Rekreasi',
            'Religi',
        ];
    }

    /**
     * Mengubah kategori menjadi one-hot encoding.
     */
    private function encodeCategories(array $categoryNames): array
    {
        $encoded = [];

        foreach ($this->getAllCategories() as $category) {
            $encoded[] = in_array($category, $categoryNames, true)
                ? 1
                : 0;
        }

        return $encoded;
    }

    /**
     * Menghitung Euclidean Distance.
     */
    private function calculateEuclideanDistance(
        array $userVector,
        array $destinationVector
    ): float {
        if (count($userVector) !== count($destinationVector)) {
            throw new \InvalidArgumentException(
                'Jumlah fitur kedua vektor harus sama.'
            );
        }

        $sum = 0;

        foreach ($userVector as $index => $value) {
            $difference = $value - $destinationVector[$index];
            $sum += pow($difference, 2);
        }

        return sqrt($sum);
    }

    /**
     * Menghasilkan rekomendasi destinasi menggunakan KNN.
     */
    public function getRecommendations(
        string $regionName,
        array $selectedCategories,
        int $maxBudget,
        string $durationLevel,
        int $resultCount = 5
    ): Collection {

        // 1. Validasi preferensi pengguna.
        $selectedCategories = array_values(
            array_unique($selectedCategories)
        );

        if (empty($regionName)) {
            throw new \InvalidArgumentException(
                'Wilayah harus dipilih.'
            );
        }

        if (
            empty($selectedCategories) ||
            count($selectedCategories) > 3
        ) {
            throw new \InvalidArgumentException(
                'Pilih minimal 1 dan maksimal 3 kategori.'
            );
        }

        if (array_diff(
            $selectedCategories,
            $this->getAllCategories()
        )) {
            throw new \InvalidArgumentException(
                'Kategori yang dipilih tidak valid.'
            );
        }

        if ($maxBudget < 0) {
            throw new \InvalidArgumentException(
                'Budget tidak boleh negatif.'
            );
        }

        if (!in_array(
            $durationLevel,
            ['Short', 'Medium', 'Long'],
            true
        )) {
            throw new \InvalidArgumentException(
                'Durasi perjalanan tidak valid.'
            );
        }

        if ($resultCount < 1 || $resultCount > 5) {
            throw new \InvalidArgumentException(
                'Jumlah rekomendasi harus antara 1 sampai 5.'
            );
        }

        // 2. Filtering berdasarkan wilayah,
        // durasi, dan estimasi total biaya.
        $destinations = Destination::with([
            'city',
            'categories',
            'costEstimates' => function ($query) use ($durationLevel) {
                $query->where('duration_level', $durationLevel);
            },
        ])
            ->whereHas('city', function ($query) use ($regionName) {
                $query->where('city_name', $regionName);
            })
            ->whereHas('costEstimates', function ($query) use (
                $durationLevel,
                $maxBudget
            ) {
                $query->where('duration_level', $durationLevel)
                    ->where('estimated_cost_rp', '<=', $maxBudget);
            })
            ->get();

        // 3. Encoding preferensi kategori pengguna.
        $userVector = $this->encodeCategories($selectedCategories);

        // 4. Menghitung jarak setiap destinasi.
        $rankedDestinations = $destinations->map(
            function ($destination) use ($userVector, $durationLevel) {

                $destinationCategories = $destination
                    ->categories
                    ->pluck('category_name')
                    ->all();

                $destinationVector = $this->encodeCategories(
                    $destinationCategories
                );

                $distance = $this->calculateEuclideanDistance(
                    $userVector,
                    $destinationVector
                );

                $destination->distance = round($distance, 5);

                $destination->similarity_score = round(
                    1 / (1 + $distance),
                    5
                );

                // Menyimpan estimasi biaya sesuai durasi.
                $costEstimate = $destination->costEstimates->first();

                $destination->estimated_cost_rp =
                    $costEstimate->estimated_cost_rp;

                $destination->duration_level = $durationLevel;

                return $destination;
            }
        );

        // 5. Menentukan rekomendasi utama dan alternatif.
        $rankedDestinations = $rankedDestinations->map(
            function ($destination) use ($selectedCategories) {

                $destinationCategories = $destination
                    ->categories
                    ->pluck('category_name')
                    ->all();

                $hasMatchingCategory = !empty(
                    array_intersect(
                        $selectedCategories,
                        $destinationCategories
                    )
                );

                $destination->is_alternative = !$hasMatchingCategory;

                $destination->recommendation_type = $hasMatchingCategory
                    ? 'Rekomendasi Utama'
                    : 'Alternatif';

                return $destination;
            }
        );

        // 6. Ranking berdasarkan jenis rekomendasi,
        // jarak terdekat, kemudian rating tertinggi.
        $rankedDestinations = $rankedDestinations->sort(
            function ($a, $b) {

                $typeComparison =
                    $a->is_alternative <=> $b->is_alternative;

                if ($typeComparison !== 0) {
                    return $typeComparison;
                }

                $distanceComparison =
                    $a->distance <=> $b->distance;

                if ($distanceComparison !== 0) {
                    return $distanceComparison;
                }

                // Rating NULL diperlakukan sebagai 0.
                return (float) ($b->rating ?? 0)
                    <=> (float) ($a->rating ?? 0);
            }
        );

        // 7. Mengambil hasil sesuai jumlah yang diminta.
        return $rankedDestinations
            ->take($resultCount)
            ->values();
    }
}
