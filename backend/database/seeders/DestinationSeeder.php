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
            throw new RuntimeException('File destinations.csv tidak ditemukan.');
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new RuntimeException('File CSV gagal dibuka.');
        }

        $headers = fgetcsv($handle, 0, ',');

        if ($headers === false) {
            fclose($handle);
            throw new RuntimeException('Header CSV tidak ditemukan.');
        }

        // Menghapus BOM jika CSV disimpan dari Excel.
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

        $categories = [
            'Alam & Pegunungan',
            'Pantai & Bahari',
            'Budaya & Sejarah',
            'Kuliner',
            'Hiburan & Rekreasi',
            'Religi',
        ];

        DB::transaction(function () use ($handle, $headers, $categories) {

            // 1. Membuat master kategori.
            foreach ($categories as $categoryName) {
                DB::table('categories')->updateOrInsert(
                    ['category_name' => $categoryName],
                    ['updated_at' => now()]
                );
            }

            // 2. Membaca setiap baris CSV.
            while (($row = fgetcsv($handle, 0, ',')) !== false) {

                if (count($row) !== count($headers)) {
                    throw new RuntimeException(
                        'Jumlah kolom CSV tidak sesuai.'
                    );
                }

                $data = array_combine($headers, $row);

                $destinationId = (int) $data['destination_id'];
                $destinationName = trim($data['destination_name']);
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

                // 3. Membuat atau mengambil wilayah.
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

                // 4. Mengambil kategori.
                $categoryId = DB::table('categories')
                    ->where('category_name', $categoryName)
                    ->value('id');

                if (!$categoryId) {
                    throw new RuntimeException(
                        "Kategori tidak ditemukan: {$categoryName}"
                    );
                }

                // 5. Memasukkan atau memperbarui destinasi.
                DB::table('destinations')->updateOrInsert(
                    ['id' => $destinationId],
                    [
                        'city_id' => $cityId,
                        'destination_name' => $destinationName,
                        'administrative_city_regency' => $administrativeLocation,
                        'ticket_price_rp' => $ticketPrice,
                        'rating' => $rating,
                        'updated_at' => now(),
                    ]
                );

                // 6. Menghubungkan destinasi dengan kategori.
                DB::table('destination_categories')->insertOrIgnore([
                    'destination_id' => $destinationId,
                    'category_id' => $categoryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        fclose($handle);

        $this->command->info(
            'DestinationSeeder berhasil dijalankan!'
        );
    }
}
