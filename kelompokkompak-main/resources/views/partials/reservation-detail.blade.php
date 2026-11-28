<dl class="row mb-0">
    <dt class="col-sm-4">Kode</dt><dd class="col-sm-8">{{ $reservation->code }}</dd>
    <dt class="col-sm-4">Status</dt><dd class="col-sm-8">@include('partials.badge', ['r' => $reservation])</dd>
    <dt class="col-sm-4">Lapangan</dt><dd class="col-sm-8">{{ $reservation->court->name }} ({{ $reservation->court->sportCategory->name }})</dd>
    <dt class="col-sm-4">Jadwal</dt><dd class="col-sm-8">{{ $reservation->booking_date->format('d-m-Y') }}, {{ $reservation->timeRange() }} WIB</dd>
    <dt class="col-sm-4">Durasi</dt><dd class="col-sm-8">{{ $reservation->duration_hours }} jam</dd>
    @if ($reservation->type === 'booking')
        <dt class="col-sm-4">Tarif per jam</dt><dd class="col-sm-8">Rp {{ number_format($reservation->price_per_hour, 0, ',', '.') }}</dd>
        <dt class="col-sm-4">Biaya sewa</dt><dd class="col-sm-8">Rp {{ number_format($reservation->total_price, 0, ',', '.') }}</dd>
        <dt class="col-sm-4">Kelebihan main</dt><dd class="col-sm-8">{{ $reservation->overtime_hours }} jam</dd>
        <dt class="col-sm-4">Denda</dt><dd class="col-sm-8">Rp {{ number_format($reservation->penalty, 0, ',', '.') }}</dd>
        <dt class="col-sm-4">Total</dt><dd class="col-sm-8 fw-bold">Rp {{ number_format($reservation->grandTotal(), 0, ',', '.') }}</dd>
        <dt class="col-sm-4">Bukti transfer</dt>
        <dd class="col-sm-8">
            @if ($reservation->payment_proof)
                <a href="{{ asset('storage/'.$reservation->payment_proof) }}" target="_blank" rel="noopener">Lihat file</a>
            @else
                -
            @endif
        </dd>
    @endif
    <dt class="col-sm-4">Catatan</dt><dd class="col-sm-8">{{ $reservation->notes ?: '-' }}</dd>
    <dt class="col-sm-4">Catatan admin</dt><dd class="col-sm-8">{{ $reservation->admin_note ?: '-' }}</dd>
</dl>
