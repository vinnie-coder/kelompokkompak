@if ($court->image)
    <img src="{{ asset('storage/'.$court->image) }}" class="card-img-top court-img" alt="{{ $court->name }}">
@else
    <div class="card-img-top court-empty">Belum ada gambar</div>
@endif
