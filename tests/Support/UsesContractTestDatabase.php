<?php

namespace Tests\Support;

use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

trait UsesContractTestDatabase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--realpath' => true,
            '--path' => array_values(array_filter(glob(database_path('migrations/*.php')), fn ($file) => ! str_contains(basename($file), 'brand'))),
        ];
    }

    protected function beforeRefreshingDatabase(): void
    {
        Event::listen(MigrationStarted::class, function (MigrationStarted $event) {
            $file = (new \ReflectionClass($event->migration))->getFileName();
            if (DB::getDriverName() === 'sqlite' && $event->method === 'up'
                && basename($file) === '2025_11_10_221000_drop_legacy_role_permission_columns.php'
                && ! Schema::hasIndex('permissions', 'permissions_nombre_unique')) {
                Schema::table('permissions', fn (Blueprint $table) => $table->unique('nombre', 'permissions_nombre_unique'));
            }
        });
    }
}
