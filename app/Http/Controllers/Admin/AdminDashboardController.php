<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Contracts\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $totalContracts = Contract::count();

        $statusCounts = collect(Contract::STATUS_LABELS)->mapWithKeys(function ($label, $value) {
            $count = Contract::where('estado', $value)->count();

            return [$label => $count];
        });

        $topCreators = User::withCount('createdContracts')
            ->orderByDesc('created_contracts_count')
            ->take(5)
            ->get();

        $activeBrands = Brand::whereRaw('LOWER(status) = ?', ['activo'])->count();

        return view('admin.dashboard', [
            'totalContracts' => $totalContracts,
            'statusCounts' => $statusCounts,
            'topCreators' => $topCreators,
            'activeBrands' => $activeBrands,
        ]);
    }
}
