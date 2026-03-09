<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\UserActivatedMail;
use App\Models\Company;
use App\Models\Country;
use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $users = User::with(['roles', 'company', 'countries', 'department.company'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('email', 'like', "%{$search}%")
                        ->orWhere('nombre', 'like', "%{$search}%");
                });
            })
            ->orderByRaw("COALESCE(nombre, email)")
            ->paginate(12)
            ->withQueryString();

        $roles = Role::orderBy('name')->pluck('name');
        $countries = Country::orderBy('nombre')->get();
        $companies = Company::orderBy('nombre')->get();
        $departments = Department::with('company:id,nombre')
            ->orderBy('nombre')
            ->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'search' => $search,
            'countries' => $countries,
            'companies' => $companies,
            'departments' => $departments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-users');

        $roleNames = Role::pluck('name')->toArray();

        $countryIds = Country::pluck('id')->toArray();

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'cargo' => ['nullable', 'string', 'max:120'],
            'empresa_id' => ['nullable', 'exists:companies,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'country_ids' => ['nullable', 'array'],
            'country_ids.*' => ['integer', Rule::in($countryIds)],
            'primary_country_id' => ['nullable', 'integer', Rule::in($countryIds)],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::in($roleNames)],
            'active' => ['required', 'boolean'],
        ]);

        $user = User::create([
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'cargo' => $data['cargo'] ?? null,
            'password' => Hash::make(Str::random(40)),
            'active' => (bool) $data['active'],
            'empresa_id' => $data['empresa_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
        ]);

        $roles = $data['roles'] ?? [];
        if (! empty($roles)) {
            $user->syncRoles($roles);
        }

        $this->syncCountries($user, $data['country_ids'] ?? [], $data['primary_country_id'] ?? null);

        if ($user->active) {
            Mail::to($user->email)
                ->bcc('nrivasq@medifarma.com.pe')
                ->send(new UserActivatedMail($user));
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Usuario '.$user->email.' agregado correctamente.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage-users');

        $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::in(Role::pluck('name')->toArray())],
        ]);

        $roles = $request->input('roles', []);

        $user->syncRoles($roles);

        return back()->with('status', 'Rol actualizado para '.$user->email);
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $wasInactive = ! $user->active;

        $user->active = $data['active'];
        $user->save();

        if ($wasInactive && $user->active) {
            Mail::to($user->email)
                ->bcc('nrivasq@medifarma.com.pe')
                ->send(new UserActivatedMail($user));
        }

        return back()->with('status', 'Estado actualizado para '.$user->email);
    }

    public function updateOrganization(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage-users');

        $countryIds = Country::pluck('id')->toArray();

        $data = $request->validate([
            'empresa_id' => ['nullable', 'exists:companies,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'country_ids' => ['nullable', 'array'],
            'country_ids.*' => ['integer', Rule::in($countryIds)],
            'primary_country_id' => ['nullable', 'integer', Rule::in($countryIds)],
        ]);

        $user->empresa_id = $data['empresa_id'] ?? null;
        $user->department_id = $data['department_id'] ?? null;
        $user->save();

        $this->syncCountries($user, $data['country_ids'] ?? [], $data['primary_country_id'] ?? null);

        return back()->with('status', 'Organización actualizada para '.$user->email);
    }

    private function syncCountries(User $user, array $countryIds, ?int $primaryCountryId): void
    {
        $countryCollection = collect($countryIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($primaryCountryId) {
            $primaryCountryId = (int) $primaryCountryId;
            if ($countryCollection->doesntContain($primaryCountryId)) {
                $countryCollection->push($primaryCountryId);
            }
        } else {
            $primaryCountryId = null;
        }

        if ($countryCollection->isEmpty()) {
            $user->countries()->detach();
            return;
        }

        $syncData = $countryCollection->mapWithKeys(function ($id) use ($primaryCountryId) {
            return [$id => ['is_primary' => $primaryCountryId ? $id === $primaryCountryId : false]];
        })->toArray();

        $user->countries()->sync($syncData);
    }
}
