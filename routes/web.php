<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BrandClassController;
use App\Http\Controllers\Admin\BrandCountryController;
use App\Http\Controllers\Admin\BrandStatusController;
use App\Http\Controllers\Admin\BrandTypeController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\ContractCommentController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ContractApprovalController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractFlowController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\TemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('auth.microsoft-login');
})->name('landing');

Route::view('/activacion-pendiente', 'auth.pending-activation')->name('activation.pending');

Route::middleware('guest')->group(function () {
    Route::get('/auth/microsoft/redirect', [MicrosoftAuthController::class, 'redirect'])->name('auth.microsoft.redirect');
    Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])->name('auth.microsoft.callback');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/flujo/add', [ContractFlowController::class, 'create'])->name('flows.create');
    Route::post('/flujo/add', [ContractFlowController::class, 'store'])->name('flows.store');

    Route::get('/contracts/add-zero', [ContractFlowController::class, 'createZero'])->name('contracts.create-zero');
    Route::post('/contracts/add-zero', [ContractFlowController::class, 'storeZero'])->name('contracts.store-zero');
    Route::get('/contracts/add-template', [ContractFlowController::class, 'create'])->name('contracts.create-template');




    Route::middleware('role:marcas')->prefix('marcas')->name('brands.')->group(function () {
        Route::get('/', [BrandController::class, 'index'])->name('index');
        Route::get('/crear', [BrandController::class, 'create'])->name('create');
        Route::post('/', [BrandController::class, 'store'])->name('store');
        Route::get('/exportar', [BrandController::class, 'export'])->name('export');
        Route::get('/{brand}', [BrandController::class, 'show'])->name('show');
        Route::post('/{brand}/comentarios', [BrandController::class, 'storeComment'])->name('comments.store');
        Route::patch('/{brand}/estado', [BrandController::class, 'updateStatus'])->name('status.update');
    });

    Route::get('/contracts/{contract}', [ContractController::class, 'show'])
        ->name('contracts.show');

    Route::get('/contracts/{contract}/versions/{version}', [ContractController::class, 'show'])
        ->name('contracts.versions.show');

    Route::get('/contracts/{contract}/approved-document', [ContractController::class, 'downloadApprovedDocument'])
        ->name('contracts.documents.approved');

    Route::post('/contracts/{contract}/versions', [ContractController::class, 'storeVersion'])
        ->name('contracts.versions.store');

    Route::patch('/contracts/{contract}/advisor', [ContractController::class, 'updateAdvisor'])
        ->name('contracts.advisor.update');

    Route::get('/contracts/{contract}/documents/{history}', [ContractController::class, 'downloadDocument'])
        ->name('contracts.documents.download');

    Route::get('/contracts/{contract}/final-document', [ContractController::class, 'downloadFinalDocument'])
        ->name('contracts.documents.final');

    Route::get('/contracts/{contract}/signed-document', [ContractController::class, 'downloadSignedDocument'])
        ->name('contracts.documents.signed');

    Route::post('/contracts/{contract}/signed-document', [ContractController::class, 'uploadSignedDocument'])
        ->name('contracts.documents.signed.store');

    Route::post('/contracts/{contract}/observe', [ContractController::class, 'observe'])
        ->name('contracts.observe');

    Route::post('/contracts/{contract}/attachments', [ContractController::class, 'storeAttachment'])
        ->name('contracts.attachments.store');

    Route::delete('/contracts/{contract}/attachments', [ContractController::class, 'destroyAttachment'])
        ->name('contracts.attachments.destroy');

    Route::get('/contracts/{contract}/attachments/download', [ContractController::class, 'downloadAttachment'])
      ->name('contracts.attachments.download');

    Route::post('/contracts/{contract}/approvals', [ContractApprovalController::class, 'update'])
        ->name('contracts.approvals.update');

    Route::post('/contracts/{contract}/approvals/{approval}/approve', [ContractApprovalController::class, 'approve'])
        ->name('contracts.approvals.approve');

    Route::delete('/contracts/{contract}/approvals/{approval}', [ContractApprovalController::class, 'destroy'])
        ->name('contracts.approvals.destroy');

    Route::post('/contracts/{contract}/comments', [ContractCommentController::class, 'store'])
        ->name('contracts.comments.store');

});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])
        ->middleware('can:view-admin-dashboard')
        ->name('dashboard');

    Route::middleware('can:manage-users')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.update-role');
        Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus'])->name('users.update-status');
        Route::patch('/users/{user}/organization', [UserManagementController::class, 'updateOrganization'])->name('users.update-organization');

        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        Route::get('/countries', [CountryController::class, 'index'])->name('countries.index');
        Route::post('/countries', [CountryController::class, 'store'])->name('countries.store');
        Route::put('/countries/{country}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('/countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');

        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        Route::get('/brand-types', [BrandTypeController::class, 'index'])->name('brand-types.index');
        Route::post('/brand-types', [BrandTypeController::class, 'store'])->name('brand-types.store');
        Route::put('/brand-types/{brandType}', [BrandTypeController::class, 'update'])->name('brand-types.update');
        Route::delete('/brand-types/{brandType}', [BrandTypeController::class, 'destroy'])->name('brand-types.destroy');

        Route::get('/brand-countries', [BrandCountryController::class, 'index'])->name('brand-countries.index');
        Route::post('/brand-countries', [BrandCountryController::class, 'store'])->name('brand-countries.store');
        Route::put('/brand-countries/{brandCountry}', [BrandCountryController::class, 'update'])->name('brand-countries.update');
        Route::delete('/brand-countries/{brandCountry}', [BrandCountryController::class, 'destroy'])->name('brand-countries.destroy');

        Route::get('/brand-classes', [BrandClassController::class, 'index'])->name('brand-classes.index');
        Route::post('/brand-classes', [BrandClassController::class, 'store'])->name('brand-classes.store');
        Route::put('/brand-classes/{brandClass}', [BrandClassController::class, 'update'])->name('brand-classes.update');
        Route::delete('/brand-classes/{brandClass}', [BrandClassController::class, 'destroy'])->name('brand-classes.destroy');

        Route::get('/brand-statuses', [BrandStatusController::class, 'index'])->name('brand-statuses.index');
        Route::post('/brand-statuses', [BrandStatusController::class, 'store'])->name('brand-statuses.store');
        Route::put('/brand-statuses/{brandStatus}', [BrandStatusController::class, 'update'])->name('brand-statuses.update');
        Route::delete('/brand-statuses/{brandStatus}', [BrandStatusController::class, 'destroy'])->name('brand-statuses.destroy');
    });

    Route::middleware('can:manage-categories')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::post('/subcategories', [SubcategoryController::class, 'store'])->name('subcategories.store');
        Route::put('/subcategories/{subcategory}', [SubcategoryController::class, 'update'])->name('subcategories.update');
        Route::delete('/subcategories/{subcategory}', [SubcategoryController::class, 'destroy'])->name('subcategories.destroy');

        Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
        Route::get('/templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
        Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
        Route::put('/templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
        Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
    });
});

require __DIR__.'/auth.php';
