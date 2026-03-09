<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::with('subcategories')->orderBy('nombre')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-categories');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150', 'unique:categories,nombre'],
        ]);

        Category::create([
            'nombre' => $data['nombre'],
            'slug' => Str::slug($data['nombre']),
        ]);

        return back()->with('status', 'Categoría creada');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('manage-categories');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150', Rule::unique('categories', 'nombre')->ignore($category->id)],
        ]);

        $category->update([
            'nombre' => $data['nombre'],
            'slug' => Str::slug($data['nombre']),
        ]);

        return back()->with('status', 'Categoría actualizada');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('manage-categories');

        $category->delete();

        return back()->with('status', 'Categoría eliminada');
    }
}
