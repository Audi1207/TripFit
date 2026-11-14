<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class DestinationSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('seeders/data/destinations.csv');

        if (!File::exists($filePath)) {
            throw new RuntimeException(
                'File destinations.csv tidak ditemukan.'
            );
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new RuntimeException(
                'File CSV gagal dibuka.'
            );
        }

        try {
            // 1. Membaca header CSV.
            $headers = fgetcsv($handle, 0, ',');

            if ($headers === false) {
                throw new RuntimeException(
                    'Header CSV tidak ditemukan.'
                );
            }

            // Menghapus BOM dan spasi pada header.
            $headers[0] = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $headers[0]
            );

            $headers = array_map(
                fn ($header) => trim($header),
                $headers
            );

            // 2. Memastikan seluruh kolom tersedia.
            $requiredHeaders = [
                'destination_id',
                'destination_name',
                'region_name',
                'province',
                'administrative_city_regency',
                'category',
                'ticket_price_rp',
                'rating',
                'main_food_price_min_rp',
                'main_food_price_max_rp',
            ];

            foreach ($requiredHeaders as $header) {
                if (!in_array($header, $headers, true)) {
                    throw new RuntimeException(
                        "Kolom CSV tidak ditemukan: {$header}"
                    );
                }
            }

            $categories = [
                'Alam & Pegunungan',
                'Pantai & Bahari',
                'Budaya & Sejarah',
                'Kuliner',
                'Hiburan & Rekreasi',
                'Religi',
            ];

            DB::transaction(function () use (
                $handle,
                $headers,
                $categories
            ) {
                // 3. Membuat master kategori.
                foreach ($categories as $categoryName) {
                    DB::table('categories')->updateOrInsert(
                        ['category_name' => $categoryName],
                        ['updated_at' => now()]
                    );
                }

                $processed = 0;

                // 4. Membaca setiap baris CSV.
                while (($row = fgetcsv($handle, 0, ',')) !== false) {

                    // Melewati baris kosong.
                    if ($row === [null] || $row === []) {
                        continue;
                    }

                    if (count($row) !== count($headers)) {
                        throw new RuntimeException(
                            'Jumlah kolom CSV tidak sesuai pada baris data '
                            . ($processed + 2)
                        );
                    }

                    $data = array_combine($headers, $row);

                    // 5. Mengambil data destinasi.
                    $destinationId = (int) $data['destination_id'];

                    $destinationName = trim(
                        $data['destination_name']
                    );

                    $regionName = trim($data['region_name']);
                    $province = trim($data['province']);

                    $administrativeLocation = trim(
                        $data['administrative_city_regency']
                    );

                    $categoryName = trim($data['category']);

                    $ticketPrice = (int) $data['ticket_price_rp'];

                    $rating = trim($data['rating']) !== ''
                        ? (float) $data['rating']
                        : null;

                    $foodPriceMin = trim(
                        $data['main_food_price_min_rp'] ?? ''
                    ) !== ''
                        ? (int) $data['main_food_price_min_rp']
                        : null;

                    $foodPriceMax = trim(
                        $data['main_food_price_max_rp'] ?? ''
                    ) !== ''
                        ? (int) $data['main_food_price_max_rp']
                        : null;

                    // 6. Validasi data wajib.
                    if (
                        $destinationId <= 0 ||
                        $destinationName === '' ||
                        $regionName === '' ||
                        $province === ''
                    ) {
                        throw new RuntimeException(
                            "Data destinasi tidak lengkap: ID {$destinationId}"
                        );
                    }

                    if (!in_array($categoryName, $categories, true)) {
                        throw new RuntimeException(
                            "Kategori tidak valid: {$categoryName}"
                        );
                    }

                    // 7. Validasi harga makanan.
                    if ($categoryName === 'Kuliner') {

                        if (
                            $foodPriceMin === null ||
                            $foodPriceMax === null ||
                            $foodPriceMin > $foodPriceMax
                        ) {
                            throw new RuntimeException(
                                "Rentang harga makanan tidak valid: "
                                . $destinationName
                            );
                        }

                    } else {

                        if (
                            $foodPriceMin !== null ||
                            $foodPriceMax !== null
                        ) {
                            throw new RuntimeException(
                                "Harga makanan hanya boleh diisi untuk "
                                . "kategori Kuliner: {$destinationName}"
                            );
                        }
                    }

                    // 8. Membuat atau mengambil wilayah.
                    DB::table('cities')->updateOrInsert(
                        [
                            'city_name' => $regionName,
                            'province' => $province,
                        ],
                        ['updated_at' => now()]
                    );

                    $cityId = DB::table('cities')
                        ->where('city_name', $regionName)
                        ->where('province', $province)
                        ->value('id');

                    // 9. Mengambil kategori.
                    $categoryId = DB::table('categories')
                        ->where('category_name', $categoryName)
                        ->value('id');

                    if (!$categoryId) {
                        throw new RuntimeException(
                            "Kategori tidak ditemukan: {$categoryName}"
                        );
                    }

                    // 10. Menyimpan atau memperbarui destinasi.
                    DB::table('destinations')->updateOrInsert(
                        ['id' => $destinationId],
                        [
                            'city_id' => $cityId,
                            'destination_name' => $destinationName,
                            'administrative_city_regency' =>
                                $administrativeLocation,
                            'ticket_price_rp' => $ticketPrice,
                            'rating' => $rating,
                            'main_food_price_min_rp' => $foodPriceMin,
                            'main_food_price_max_rp' => $foodPriceMax,
                            'updated_at' => now(),
                        ]
                    );

                    // 11. Menghubungkan destinasi dengan kategori.
                    DB::table('destination_categories')->insertOrIgnore([
                        'destination_id' => $destinationId,
                        'category_id' => $categoryId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $processed++;
                }

                $this->command->info(
                    "Total baris CSV berhasil diproses: {$processed}"
                );
            });

            $this->command->info(
                'DestinationSeeder berhasil dijalankan!'
            );

        } finally {
            fclose($handle);
        }
    }
}
