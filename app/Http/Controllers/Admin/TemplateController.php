<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        $templates = Template::with(['category', 'subcategory'])
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        return view('admin.templates.list', compact('templates'));
    }

    public function create(): View
    {
        return view('admin.templates.create', array_merge(
            $this->baseViewData(),
            ['template' => null]
        ));
    }

    public function edit(Template $template): View
    {
        return view('admin.templates.edit', array_merge(
            $this->baseViewData(),
            ['template' => $template->load('category', 'subcategory')]
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $subcategory = Subcategory::findOrFail($data['subcategoria_id']);
        $data['categoria_id'] = $subcategory->category_id;
        $data['slug'] = $this->generateUniqueSlug($data['nombre']);

        Template::create($data);

        return redirect()->route('admin.templates.index')->with('status', 'Template creado');
    }

    public function update(Request $request, Template $template): RedirectResponse
    {
        $data = $this->validateData($request, $template);

        $subcategory = Subcategory::findOrFail($data['subcategoria_id']);
        $data['categoria_id'] = $subcategory->category_id;

        if ($template->nombre !== $data['nombre']) {
            $data['slug'] = $this->generateUniqueSlug($data['nombre'], $template->id);
        }

        $template->update($data);

        return redirect()->route('admin.templates.index')->with('status', 'Template actualizado');
    }

    public function destroy(Template $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('admin.templates.index')->with('status', 'Template eliminado');
    }

    private function validateData(Request $request, ?Template $template = null): array
    {
        $payload = $request->all();
        if (array_key_exists('forms', $payload) && blank($payload['forms'])) {
            $payload['forms'] = null;
        }

        $validator = Validator::make($payload, [
            'nombre' => ['required', 'string', 'max:180'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => ['nullable', 'exists:categories,id'],
            'subcategoria_id' => ['required', 'exists:subcategories,id'],
            'forms' => ['nullable', 'json'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $categoryId = $request->input('categoria_id');
            $subcategoryId = $request->input('subcategoria_id');

            if ($categoryId && $subcategoryId && ! Subcategory::where('id', $subcategoryId)
                ->where('category_id', $categoryId)->exists()) {
                $validator->errors()->add('subcategoria_id', 'La subcategoría no pertenece a la categoría seleccionada.');
            }
        });

        $validated = $validator->validate();
        unset($validated['categoria_id']);

        if (array_key_exists('forms', $validated)) {
            $validated['forms'] = blank($validated['forms'])
                ? null
                : json_decode($validated['forms'], true);
        }

        return $validated;
    }

    private function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $slug = Str::slug($value) ?: Str::random(8);
        $original = $slug;
        $counter = 1;

        while (Template::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $original.'-'.++$counter;
        }

        return $slug;
    }

    private function baseViewData(): array
    {
        $categories = Category::with('subcategories:id,nombre,category_id')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $categoryMatrix = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'subs' => $category->subcategories->map(fn ($sub) => [
                    'id' => $sub->id,
                    'nombre' => $sub->nombre,
                ])->values(),
            ];
        })->values();

        return compact('categories', 'categoryMatrix');
    }
}
