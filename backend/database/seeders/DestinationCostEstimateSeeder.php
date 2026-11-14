<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DestinationCostEstimateSeeder extends Seeder
{
    public function run(): void
    {
        $destinations = DB::table('destinations')
            ->leftJoin(
                'destination_categories',
                'destinations.id',
                '=',
                'destination_categories.destination_id'
            )
            ->leftJoin(
                'categories',
                'destination_categories.category_id',
                '=',
                'categories.id'
            )
            ->select(
                'destinations.*',
                'categories.category_name'
            )
            ->get();

        if ($destinations->count() !== 62) {
            throw new RuntimeException(
                'Jumlah destinasi harus 62. Ditemukan: '
                . $destinations->count()
            );
        }

        // Perkiraan biaya makan per hari untuk kategori non-kuliner.
        $dailyFoodCost = 100000;

        // Perkiraan transportasi lokal per hari.
        $dailyTransportCost = 60000;

        // Perkiraan biaya aktivitas tambahan per hari.
        $dailyActivityCosts = [
            'Alam & Pegunungan' => 50000,
            'Pantai & Bahari' => 60000,
            'Budaya & Sejarah' => 40000,
            'Kuliner' => 30000,
            'Hiburan & Rekreasi' => 75000,
            'Religi' => 20000,
        ];

        // Durasi menggunakan angka representatif.
        $durationLevels = [
            'Short' => 1,
            'Medium' => 2.5,
            'Long' => 4.5,
        ];

        DB::transaction(function () use (
            $destinations,
            $dailyFoodCost,
            $dailyTransportCost,
            $dailyActivityCosts,
            $durationLevels
        ) {
            foreach ($destinations as $destination) {
                $category = $destination->category_name;

                if (!isset($dailyActivityCosts[$category])) {
                    throw new RuntimeException(
                        "Kategori tidak dikenali: {$category}"
                    );
                }

                // Biaya makan khusus destinasi kuliner.
                if ($category === 'Kuliner') {
                    if (
                        $destination->main_food_price_min_rp === null ||
                        $destination->main_food_price_max_rp === null
                    ) {
                        throw new RuntimeException(
                            'Harga makanan belum tersedia: '
                            . $destination->destination_name
                        );
                    }

                    $foodCostPerDay = (
                        $destination->main_food_price_min_rp +
                        $destination->main_food_price_max_rp
                    ) / 2;
                } else {
                    $foodCostPerDay = $dailyFoodCost;
                }

                foreach ($durationLevels as $level => $days) {
                    $foodCost = $foodCostPerDay * $days;

                    $transportCost = $dailyTransportCost * $days;

                    $activityCost =
                        $dailyActivityCosts[$category] * $days;

                    // Tiket masuk dihitung satu kali.
                    $ticketCost = $destination->ticket_price_rp;

                    $totalCost = (int) round(
                        $ticketCost +
                        $foodCost +
                        $transportCost +
                        $activityCost
                    );

                    DB::table('destination_cost_estimates')
                        ->updateOrInsert(
                            [
                                'destination_id' => $destination->id,
                                'duration_level' => $level,
                            ],
                            [
                                'estimated_cost_rp' => $totalCost,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                }
            }
        });

        $this->command->info(
            'DestinationCostEstimateSeeder berhasil dijalankan!'
        );

        $this->command->info(
            'Total estimasi biaya: '
            . ($destinations->count() * count($durationLevels))
        );
    }
}
