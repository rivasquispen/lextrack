<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-categories');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        Subcategory::create([
            'nombre' => $data['nombre'],
            'category_id' => $data['category_id'],
        ]);

        return back()->with('status', 'Subcategoría creada');
    }

    public function update(Request $request, Subcategory $subcategory): RedirectResponse
    {
        $this->authorize('manage-categories');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $subcategory->update([
            'nombre' => $data['nombre'],
            'category_id' => $data['category_id'],
        ]);

        return back()->with('status', 'Subcategoría actualizada');
    }

    public function destroy(Subcategory $subcategory): RedirectResponse
    {
        $this->authorize('manage-categories');

        $subcategory->delete();

        return back()->with('status', 'Subcategoría eliminada');
    }
}
