<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourtRequest;
use App\Models\Court;
use App\Models\SportCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourtController extends Controller
{
    public function index(Request $request)
    {
        $courts = Court::with('sportCategory')
            ->filter($request->only(['q', 'category']))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $categories = SportCategory::orderBy('name')->get();

        return view('admin.courts.index', compact('courts', 'categories'));
    }

    public function create()
    {
        return view('admin.courts.form', [
            'court' => new Court(['is_active' => true]),
            'categories' => SportCategory::orderBy('name')->get(),
        ]);
    }

    public function store(CourtRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('courts', 'public');
        }

        Court::create($data);

        return redirect()->route('admin.courts.index')->with('success', 'Lapangan ditambahkan.');
    }

    public function edit(Court $court)
    {
        return view('admin.courts.form', [
            'court' => $court,
            'categories' => SportCategory::orderBy('name')->get(),
        ]);
    }

    public function update(CourtRequest $request, Court $court)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        unset($data['image']);
        $oldImage = null;

        if ($request->hasFile('image')) {
            $oldImage = $court->image;
            $data['image'] = $request->file('image')->store('courts', 'public');
        }

        $court->update($data);

        // Gambar lama dihapus setelah data baru tersimpan
        if ($oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.courts.index')->with('success', 'Lapangan diperbarui.');
    }

    public function destroy(Court $court)
    {
        if ($court->reservations()->exists()) {
            return back()->with('error', 'Lapangan punya riwayat reservasi. Nonaktifkan saja lewat menu edit.');
        }

        $court->delete(); // file gambar terhapus lewat event model

        return back()->with('success', 'Lapangan dihapus.');
    }
}
