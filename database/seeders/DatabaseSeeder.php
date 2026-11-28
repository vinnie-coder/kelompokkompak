<?php

namespace Database\Seeders;

use App\Models\Court;
use App\Models\Reservation;
use App\Models\SportCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->makeUser('Admin', 'admin', 'admin', '123');
        $user1 = $this->makeUser('User 1', 'user1', 'user', '321');
        $user2 = $this->makeUser('User 2', 'user2', 'user', '231');

        $futsal = SportCategory::create(['name' => 'Futsal', 'description' => 'Lapangan futsal indoor dan outdoor.']);
        $padel = SportCategory::create(['name' => 'Padel', 'description' => 'Lapangan padel berdinding kaca.']);

        $courts = collect([
            [$padel, 'Lapangan Padel Queen', 'Indoor, kaca panoramik.', 200000],
            [$padel, 'Lapangan Padel Keiko', 'Semi outdoor.', 175000],
            [$padel, 'Lapangan Padel Sabrine', 'Outdoor, rumput sintetis.', 150000],
            [$futsal, 'Futsal Ahmad', 'Indoor, lantai vinyl.', 120000],
            [$futsal, 'Futsal Temola', 'Indoor, rumput sintetis.', 100000],
            [$futsal, 'Futsal FTI', 'Outdoor, rumput sintetis.', 80000],
        ])->map(fn (array $c, int $i) => Court::create([
            'sport_category_id' => $c[0]->id,
            'name' => $c[1],
            'description' => $c[2],
            'price_per_hour' => $c[3],
            'image' => $this->courtImage($i + 1),
            'is_active' => true,
        ]));

        // Contoh bukti transfer (gambar PNG 1x1) agar tautan bukti pada data awal tidak rusak
        $proof = 'payment-proofs/contoh-bukti.png';
        Storage::disk('public')->put($proof, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        ));

        // [user, lapangan, selisih hari, jam mulai, jam selesai, status]
        $samples = [
            [$user1, $courts[1], -3, 18, 20, 'completed'],
            [$user2, $courts[0], -1, 19, 21, 'approved'],
            [$user1, $courts[0], 1, 16, 18, 'pending'],
            [$user2, $courts[1], 2, 9, 10, 'pending'],
            [$user1, $courts[2], 3, 15, 17, 'approved'],
        ];

        foreach ($samples as $i => [$user, $court, $offset, $start, $end, $status]) {
            $duration = $end - $start;
            $verified = in_array($status, ['approved', 'completed']);
            // File contoh disalin per pesanan supaya penghapusan satu data tidak merusak data lain
            $ownProof = "payment-proofs/contoh-bukti-{$i}.png";
            Storage::disk('public')->copy($proof, $ownProof);

            Reservation::create([
                'code' => Reservation::generateCode(),
                'user_id' => $user->id,
                'court_id' => $court->id,
                'type' => 'booking',
                'booking_date' => today()->addDays($offset)->toDateString(),
                'start_time' => sprintf('%02d:00:00', $start),
                'end_time' => sprintf('%02d:00:00', $end),
                'duration_hours' => $duration,
                'price_per_hour' => $court->price_per_hour,
                'total_price' => $duration * $court->price_per_hour,
                'status' => $status,
                'payment_proof' => $ownProof,
                'verified_by' => $verified ? $admin->id : null,
                'verified_at' => $verified ? now() : null,
                'completed_at' => $status === 'completed' ? now() : null,
            ]);

            if ($status === 'completed') {
                $user->increment('points', $duration);
                $court->increment('total_hours_used', $duration);
            }
        }

        Storage::disk('public')->delete($proof);
    }

    /** Salin gambar contoh ke storage. Mengembalikan null jika file tidak ada. */
    private function courtImage(int $number): ?string
    {
        $source = database_path("seeders/images/lapangan-{$number}.jpg");

        if (! file_exists($source)) {
            return null;
        }

        $path = "courts/lapangan-{$number}.jpg";
        Storage::disk('public')->put($path, file_get_contents($source));

        return $path;
    }

    private function makeUser(string $name, string $email, string $role, string $password): User
    {
        $user = new User(['name' => $name, 'email' => $email, 'password' => $password, 'phone' => '081234567890']);
        $user->role = $role;
        $user->save();

        return $user;
    }
}