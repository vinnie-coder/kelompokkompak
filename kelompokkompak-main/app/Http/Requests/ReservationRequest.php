<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Saat edit, hanya pemilik pesanan yang boleh (ownership check)
        $reservation = $this->route('reservation');

        return ! $reservation || $reservation->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        $open = config('booking.jam_buka');
        $close = config('booking.jam_tutup');

        // Tarif, durasi, status, dan user_id tidak diterima dari input
        return [
            'court_id' => ['required', 'exists:courts,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(30)->toDateString()],
            'start_hour' => ['required', 'integer', "between:{$open},".($close - 1)],
            'end_hour' => ['required', 'integer', 'gt:start_hour', 'between:'.($open + 1).",{$close}"],
            'payment_proof' => [$this->isMethod('post') ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_date.after_or_equal' => 'Tanggal main tidak boleh sebelum hari ini.',
            'booking_date.before_or_equal' => 'Pemesanan maksimal 30 hari ke depan.',
        ];
    }
}
