<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContractCategorySeeder extends Seeder
{
    private array $categories = [
        'Contratos de Investigación y Desarrollo (I+D)' => [
            'Acuerdos de Colaboración en I+D (R&D Collaboration Agreements)',
            'Contratos de Organización de Investigación por Contrato (CRO Contracts)',
            'Acuerdos de Licencia de Patentes y Tecnología (Licensing Agreements)',
            'Contratos de Servicios de Laboratorio Específicos',
            'Contrato de Locación de Servicios Contratode Locacion de Servi',
        ],
        'Contratos de Fabricación y Cadena de Suministro' => [
            'Acuerdos de Fabricación por Contrato (CMO/CDMO Agreements)',
            'Contratos de Suministro de Materias Primas (Supply Agreements)',
            'Contratos de Logística y Distribución (Distribution Agreements)',
            'Contrato de Locación de Servicios',
        ],
        'Contratos de Comercialización y Acceso al Mercado' => [
            'Acuerdos de Co-Promoción/Co-Marketing',
            'Contratos de Servicios de Marketing y Publicidad',
            'Contratos con Profesionales de la Salud (HCP Contracts)',
            'Acuerdos de Descuento/Reembolso con Pagadores',
            'Contrato de Locación de Servicios',
        ],
        'Contratos de Recursos Humanos, Corporativos y TI' => [
            'Contratos de Empleo y Consultoría de Personal',
            'Acuerdos de Confidencialidad (NDA/CDA)',
            'Contratos de Adquisición de Tecnología y Servicios de TI',
            'Acuerdos de Calidad (Quality Agreements)',
            'Términos de uso y políticas de datos',
            'Contrato de Locación de Servicios',
        ],
    ];

    public function run(): void
    {
        $usedCategorySlugs = [];

        foreach ($this->categories as $categoryName => $subcategories) {
            $categorySlug = $this->uniqueSlug($categoryName, $usedCategorySlugs);

            $category = Category::updateOrCreate(
                ['slug' => $categorySlug],
                ['nombre' => $categoryName]
            );

            foreach ($subcategories as $subName) {
                Subcategory::updateOrCreate(
                    ['nombre' => $subName, 'category_id' => $category->id],
                    []
                );
            }
        }
    }

    private function uniqueSlug(string $value, array &$registry): string
    {
        $base = Str::slug($value) ?: 'item';
        $slug = $base;
        $suffix = 1;

        while (in_array($slug, $registry, true)) {
            $slug = $base.'-'.++$suffix;
        }

        $registry[] = $slug;

        return $slug;
    }
}
