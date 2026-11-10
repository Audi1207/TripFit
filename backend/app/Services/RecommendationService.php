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
     * Mengubah kategori menjadi representasi biner (one-hot encoding).
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
        int $resultCount = 5
    ): Collection {

        // 1. Validasi preferensi pengguna
        $selectedCategories = array_values(
            array_unique($selectedCategories)
        );

        if (empty($regionName)) {
            throw new \InvalidArgumentException(
                'Wilayah harus dipilih.'
            );
        }

        if (empty($selectedCategories) || count($selectedCategories) > 3) {
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

        if ($resultCount < 1 || $resultCount > 5) {
            throw new \InvalidArgumentException(
                'Jumlah rekomendasi harus antara 1 sampai 5.'
            );
        }

        // 2. Filtering berdasarkan wilayah dan budget
        $destinations = Destination::with(['city', 'categories'])
            ->whereHas('city', function ($query) use ($regionName) {
                $query->where('city_name', $regionName);
            })
            ->where('ticket_price_rp', '<=', $maxBudget)
            ->get();

        // 3. Encoding preferensi kategori pengguna
        $userVector = $this->encodeCategories($selectedCategories);

        // 4. Menghitung jarak setiap destinasi
        $rankedDestinations = $destinations->map(
            function ($destination) use ($userVector) {

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

                // Skor kemiripan: semakin tinggi semakin mirip
                $destination->similarity_score = round(
                    1 / (1 + $distance),
                    5
                );

                return $destination;
            }
        );

        // 5. Ranking berdasarkan jarak terdekat.
        // Jika jarak sama, rating lebih tinggi diprioritaskan.
        $rankedDestinations = $rankedDestinations->sort(
            function ($a, $b) {

                $distanceComparison = $a->distance <=> $b->distance;

                if ($distanceComparison !== 0) {
                    return $distanceComparison;
                }

                return (float) $b->rating <=> (float) $a->rating;
            }
        );

        // 6. Mengambil sejumlah hasil yang diminta pengguna
        return $rankedDestinations
            ->take($resultCount)
            ->values();
    }
}
