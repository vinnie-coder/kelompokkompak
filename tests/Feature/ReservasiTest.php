<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\Reservation;
use App\Models\SportCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReservasiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private User $other;
    private Court $court;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->travelTo(Carbon::parse('2026-12-01 08:00:00', 'Asia/Jakarta'));

        $this->admin = $this->makeUser('admin@test.com', 'admin');
        $this->user = $this->makeUser('user@test.com', 'user');
        $this->other = $this->makeUser('lain@test.com', 'user');

        $category = SportCategory::create(['name' => 'Futsal']);
        $this->court = Court::create([
            'sport_category_id' => $category->id,
            'name' => 'Futsal A',
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);
    }

    private function makeUser(string $email, string $role): User
    {
        $user = new User(['name' => $email, 'email' => $email, 'password' => 'password']);
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function payload(int $start, int $end): array
    {
        return [
            'court_id' => $this->court->id,
            'booking_date' => '2026-12-02',
            'start_hour' => $start,
            'end_hour' => $end,
            'payment_proof' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ];
    }

    private function book(int $start = 10, int $end = 12): Reservation
    {
        $this->actingAs($this->user)
            ->post(route('user.reservations.store'), $this->payload($start, $end))
            ->assertSessionHasNoErrors();

        return Reservation::latest('id')->firstOrFail();
    }

    public function test_tarif_dihitung_server_dan_input_manipulasi_diabaikan(): void
    {
        $this->actingAs($this->user)->post(route('user.reservations.store'), $this->payload(10, 13) + [
            'total_price' => 1,
            'status' => 'approved',
        ]);

        $r = Reservation::firstOrFail();
        $this->assertSame(3, $r->duration_hours);
        $this->assertSame(300000, $r->total_price);
        $this->assertSame('pending', $r->status);
        Storage::disk('public')->assertExists($r->payment_proof);
    }

    public function test_jadwal_bentrok_ditolak_dan_file_tidak_tertinggal(): void
    {
        $this->book(10, 12);

        $this->actingAs($this->other)
            ->post(route('user.reservations.store'), $this->payload(11, 13))
            ->assertSessionHasErrors('start_hour');

        $this->assertSame(1, Reservation::count());
        $this->assertCount(1, Storage::disk('public')->files('payment-proofs'));
    }

    public function test_jadwal_bersambungan_diterima(): void
    {
        $this->book(10, 12);
        $this->book(12, 13);

        $this->assertSame(2, Reservation::count());
    }

    public function test_jadwal_perawatan_memblokir_pemesanan(): void
    {
        $this->actingAs($this->admin)->post(route('admin.maintenance.store'), [
            'court_id' => $this->court->id,
            'booking_date' => '2026-12-02',
            'start_hour' => 14,
            'end_hour' => 18,
            'notes' => 'Ganti rumput',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->user)
            ->post(route('user.reservations.store'), $this->payload(15, 16))
            ->assertSessionHasErrors('start_hour');
    }

    public function test_user_tidak_bisa_mengakses_pesanan_orang_lain(): void
    {
        $r = $this->book();

        $this->actingAs($this->other)->get(route('user.reservations.show', $r))->assertForbidden();
        $this->actingAs($this->other)->get(route('user.reservations.edit', $r))->assertForbidden();
        $this->actingAs($this->other)->patch(route('user.reservations.cancel', $r))->assertForbidden();
        $this->actingAs($this->other)
            ->put(route('user.reservations.update', $r), $this->payload(15, 16))
            ->assertForbidden();
    }

    public function test_role_dipisahkan(): void
    {
        $this->actingAs($this->user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('user.dashboard'))->assertForbidden();
    }

    public function test_file_lama_dihapus_saat_bukti_diganti(): void
    {
        $r = $this->book();
        $old = $r->payment_proof;

        $this->actingAs($this->user)
            ->put(route('user.reservations.update', $r), $this->payload(13, 14))
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($r->fresh()->payment_proof);
        $this->assertSame(100000, $r->fresh()->total_price);
    }

    public function test_alur_approve_sampai_completed_menambah_poin(): void
    {
        $r = $this->book(10, 12);

        $this->actingAs($this->admin)->patch(route('admin.reservations.approve', $r))->assertSessionHasNoErrors();

        // Setelah disetujui, user tidak bisa membatalkan
        $this->actingAs($this->user)->patch(route('user.reservations.cancel', $r))->assertSessionHasErrors('status');

        $this->travelTo(Carbon::parse('2026-12-02 12:30:00', 'Asia/Jakarta'));
        $this->actingAs($this->admin)->patch(route('admin.reservations.complete', $r), [
            'overtime_hours' => 1,
            'penalty' => 1, // input nominal denda harus diabaikan
        ])->assertSessionHasNoErrors();

        $this->assertSame('completed', $r->fresh()->status);
        $this->assertSame(100000, $r->fresh()->penalty);
        $this->assertSame(300000, $r->fresh()->grandTotal());
        $this->assertSame(2, $this->user->fresh()->points);
        $this->assertSame(2, $this->court->fresh()->total_hours_used);
    }

    public function test_semua_halaman_dapat_dibuka(): void
    {
        $r = $this->book();

        foreach ([
            route('user.dashboard'), route('user.courts.index'), route('user.courts.show', $this->court),
            route('user.reservations.index'), route('user.reservations.create'),
            route('user.reservations.show', $r), route('user.reservations.edit', $r), route('profile.edit'),
        ] as $url) {
            $this->actingAs($this->user)->get($url)->assertOk();
        }

        foreach ([
            route('admin.dashboard'), route('admin.categories.index'), route('admin.categories.create'),
            route('admin.categories.edit', $this->court->sport_category_id), route('admin.courts.index'),
            route('admin.courts.create'), route('admin.courts.edit', $this->court),
            route('admin.reservations.index'), route('admin.reservations.index', ['type' => 'maintenance']),
            route('admin.reservations.show', $r), route('admin.maintenance.create'),
        ] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }
}
