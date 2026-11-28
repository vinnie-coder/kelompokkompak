<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SportCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SportCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = SportCategory::withCount('courts')
            ->when($request->query('q'), fn ($query, $q) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.form', ['category' => new SportCategory]);
    }

    public function store(Request $request)
    {
        SportCategory::create($this->validated($request));

        return redirect()->route('admin.categories.index')->with('success', 'Kategori ditambahkan.');
    }

    public function edit(SportCategory $category)
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, SportCategory $category)
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('admin.categories.index')->with('success', 'Kategori diperbarui.');
    }

    public function destroy(SportCategory $category)
    {
        if ($category->courts()->exists()) {
            return back()->with('error', 'Kategori masih dipakai lapangan, tidak bisa dihapus.');
        }

        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }

    private function validated(Request $request, ?SportCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('sport_categories')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
