<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandClass;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class BrandClassController extends Controller
{
    public function index(): View
    {
        $brandClasses = BrandClass::query()
            ->orderBy('number')
            ->get();

        return view('admin.brands.classes.index', compact('brandClasses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'number' => ['required', 'integer', 'min:1', 'max:255', 'unique:brand_classes,number'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        BrandClass::create($data);

        return Redirect::route('admin.brand-classes.index')->with('status', 'Clase registrada correctamente.');
    }

    public function update(Request $request, BrandClass $brandClass): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'number' => ['required', 'integer', 'min:1', 'max:255', Rule::unique('brand_classes', 'number')->ignore($brandClass->id)],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $brandClass->update($data);

        return Redirect::route('admin.brand-classes.index')->with('status', 'Clase actualizada correctamente.');
    }

    public function destroy(BrandClass $brandClass): RedirectResponse
    {
        $this->authorize('manage-users');

        $brandClass->delete();

        return Redirect::route('admin.brand-classes.index')->with('status', 'Clase eliminada.');
    }
}

