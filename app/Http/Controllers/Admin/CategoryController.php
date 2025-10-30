<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.masterdata.categories.index');
    }

    public function data(Request $request)
    {
        $categories = Category::latest()->get()->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ];
        });

        return response()->json(['data' => $categories]);
    }

    public function create()
    {
        return view('admin.masterdata.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        $slug = Str::slug($validated['name']);
        // Ensure slug unique
        $base = $slug; $i = 1;
        while (Category::where('slug', $slug)->exists()) { $slug = $base.'-'.$i++; }

        Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        return redirect()->route('admin.masterdata.categories.index')->with('success', 'Kategori berhasil dibuat');
    }

    public function edit(Category $category)
    {
        return view('admin.masterdata.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
        ]);

        $slug = Str::slug($validated['name']);
        $base = $slug; $i = 1;
        while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) { $slug = $base.'-'.$i++; }

        $category->update([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        return redirect()->route('admin.masterdata.categories.index')->with('success', 'Kategori berhasil diperbarui');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('admin.masterdata.categories.index')->with('success', 'Kategori berhasil dihapus');
    }
}

